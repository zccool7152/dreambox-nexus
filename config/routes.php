<?php
declare(strict_types=1);

use Nexus\Core\HealthCheck;
use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Core\Router;
use Nexus\Database\Database;
use Nexus\Monitor\CspReporter;
use Nexus\Monitor\MonitorController;
use Nexus\Security\Csrf;
use Nexus\Compliance\ConsentManager;
use Nexus\Compliance\GdprManager;
use Nexus\Security\MonitorAccessMiddleware;
use Nexus\Storage\Storage;
use Nexus\View\View;

/** @var Router $router */

// ── Health Probes ────────────────────────────────────────────────────────────
$router->get('/health', function (Request $req) use ($app) {
    return (new HealthCheck())->liveness($req, $app->getBasePath());
}, [], 'health.liveness');

$router->get('/health/ready', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    return (new HealthCheck())->readiness($req, $db, $app->getBasePath() . '/storage');
}, [], 'health.readiness');

// ── Compliance Matrix & Regulatory Verification ──────────────────────────────
$router->get('/compliance', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $controller = new \Nexus\Compliance\ComplianceController($db, $app->getBasePath());
    return $controller->index($req);
}, [], 'compliance.index');

$router->get('/docs/openapi.yaml', function (Request $req) use ($app) {
    $file = $app->getBasePath() . '/docs/openapi.yaml';
    if (!file_exists($file)) {
        return Response::error('OpenAPI specification not found.', [], 404, [], $req->getTraceId());
    }
    $yaml = (string)file_get_contents($file);

    $accept = $req->getHeader('accept', '');
    $format = $req->getString('format');
    $wantsYaml = str_contains($accept, 'text/yaml') || str_contains($accept, 'application/x-yaml') || $format === 'yaml';

    if (!$wantsYaml && str_contains($accept, 'text/html')) {
        $view = new View($app->getBasePath() . '/resources/views');
        $html = $view->render('docs', [
            'rawYaml' => $yaml,
            'traceId' => $req->getTraceId(),
        ]);
        return Response::html($html);
    }

    return new Response($yaml, 200, [
        'Content-Type' => 'text/yaml; charset=utf-8',
        'Access-Control-Allow-Origin' => '*',
    ]);
}, [], 'docs.openapi');

// ── Security & CSRF ──────────────────────────────────────────────────────────
$router->get('/api/v1/security/csrf-token', function (Request $req) use ($app) {
    $session = new \Nexus\Security\Session();
    $session->start();
    $csrf = new Csrf(\Nexus\Core\Env::get('APP_KEY', 'default_secret'));
    $token = $csrf->generateToken($session->getId());
    return Response::json(['csrf_token' => $token], 'CSRF token generated.', 200, [], $req->getTraceId());
}, [], 'security.csrf');

$router->post('/api/v1/security/csp-reports', function (Request $req) use ($app) {
    $reporter = new CspReporter($app->getBasePath() . '/storage/logs/csp_violations.log');
    return $reporter->ingest($req);
}, [], 'security.csp_reports');

// ── CIA Triad Authentication & Multi-Provider IAM ─────────────────────────────
$getAuthController = function () use ($app): \Nexus\Auth\AuthController {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $userRepo = new \Nexus\Auth\UserRepository($db, $hasher);
    $mfa = new \Nexus\Auth\Mfa();
    $lockout = new \Nexus\Auth\AccountLockout($db);
    $authConfig = file_exists($app->getBasePath() . '/config/auth.php')
        ? require $app->getBasePath() . '/config/auth.php'
        : [];
    $oauth = new \Nexus\Auth\OAuth\OAuthManager($authConfig['oauth'] ?? [], $session);
    $rbac = new \Nexus\Auth\Rbac\RoleManager($db);
    $view = new View($app->getBasePath() . '/resources/views');
    $csrf = new Csrf(\Nexus\Core\Env::get('APP_KEY', 'default_secret'));

    return new \Nexus\Auth\AuthController(
        $auth, $userRepo, $mfa, $lockout, $oauth, $rbac, $view, $session, $csrf
    );
};

$getAdminController = function () use ($app): \Nexus\Admin\AdminController {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $userRepo = new \Nexus\Auth\UserRepository($db, $hasher);
    $roleMgr = new \Nexus\Auth\Rbac\RoleManager($db);
    $groupMgr = new \Nexus\Auth\Rbac\GroupManager($db);
    $view = new View($app->getBasePath() . '/resources/views');
    $csrf = new Csrf(\Nexus\Core\Env::get('APP_KEY', 'default_secret'));

    return new \Nexus\Admin\AdminController(
        $auth, $userRepo, $roleMgr, $groupMgr, $db, $view, $csrf, $session
    );
};

$getAccountController = function () use ($app): \Nexus\Auth\AccountController {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $userRepo = new \Nexus\Auth\UserRepository($db, $hasher);
    $view = new View($app->getBasePath() . '/resources/views');
    $csrf = new Csrf(\Nexus\Core\Env::get('APP_KEY', 'default_secret'));

    return new \Nexus\Auth\AccountController(
        $auth, $userRepo, $hasher, $db, $view, $csrf, $session
    );
};

// Auth Views & Actions
$router->get('/auth/login', function (Request $req) use ($getAuthController) {
    return $getAuthController()->showLogin($req);
}, [], 'auth.show_login');

$router->get('/login', function (Request $req) use ($getAuthController) {
    return $getAuthController()->showLogin($req);
}, [], 'auth.login_alias');

$router->post('/auth/login', function (Request $req) use ($getAuthController) {
    return $getAuthController()->login($req);
}, [], 'auth.post_login');

$router->post('/login', function (Request $req) use ($getAuthController) {
    return $getAuthController()->login($req);
}, [], 'auth.post_login_alias');

$router->post('/api/v1/auth/login', function (Request $req) use ($getAuthController) {
    return $getAuthController()->login($req);
}, [], 'auth.login');

$router->get('/auth/2fa/setup', function (Request $req) use ($getAuthController) {
    return $getAuthController()->showSetup2fa($req);
}, [], 'auth.show_setup_2fa');

$router->post('/auth/2fa/setup', function (Request $req) use ($getAuthController) {
    return $getAuthController()->processSetup2fa($req);
}, [], 'auth.post_setup_2fa');

$router->post('/api/v1/auth/2fa/setup', function (Request $req) use ($getAuthController) {
    return $getAuthController()->processSetup2fa($req);
}, [], 'auth.process_setup_2fa');

$router->get('/auth/2fa', function (Request $req) use ($getAuthController) {
    return $getAuthController()->showTotp($req);
}, [], 'auth.show_2fa');

$router->post('/auth/2fa', function (Request $req) use ($getAuthController) {
    return $getAuthController()->verifyTotp($req);
}, [], 'auth.post_verify_2fa');

$router->post('/api/v1/auth/2fa/verify', function (Request $req) use ($getAuthController) {
    return $getAuthController()->verifyTotp($req);
}, [], 'auth.verify_2fa');

$router->get('/auth/password/change', function (Request $req) use ($getAuthController) {
    return $getAuthController()->showChangePassword($req);
}, [], 'auth.show_change_password');

$router->post('/auth/password/change', function (Request $req) use ($getAuthController) {
    return $getAuthController()->changePassword($req);
}, [], 'auth.post_change_password');

$router->post('/api/v1/auth/password/change', function (Request $req) use ($getAuthController) {
    return $getAuthController()->changePassword($req);
}, [], 'auth.change_password');

$router->get('/auth/oauth/{provider}', function (Request $req, array $params) use ($getAuthController) {
    return $getAuthController()->redirectToOAuth($req, $params['provider']);
}, [], 'auth.oauth_redirect');

$router->get('/auth/oauth/{provider}/callback', function (Request $req, array $params) use ($getAuthController) {
    return $getAuthController()->handleOAuthCallback($req, $params['provider']);
}, [], 'auth.oauth_callback');

$router->get('/auth/{provider}/callback', function (Request $req, array $params) use ($getAuthController) {
    return $getAuthController()->handleOAuthCallback($req, $params['provider']);
}, [], 'auth.oauth_callback_short');

$router->get('/auth/logout', function (Request $req) use ($getAuthController) {
    return $getAuthController()->logout($req);
}, [], 'auth.get_logout');

$router->post('/auth/logout', function (Request $req) use ($getAuthController) {
    return $getAuthController()->logout($req);
}, [], 'auth.post_logout');

$router->get('/logout', function (Request $req) use ($getAuthController) {
    return $getAuthController()->logout($req);
}, [], 'auth.get_logout_alias');

$router->post('/logout', function (Request $req) use ($getAuthController) {
    return $getAuthController()->logout($req);
}, [], 'auth.post_logout_alias');

$router->post('/api/v1/auth/logout', function (Request $req) use ($getAuthController) {
    return $getAuthController()->logout($req);
}, [], 'auth.logout');

// ── User Self-Service Account Management ─────────────────────────────────────
$router->get('/account', function (Request $req) use ($getAccountController) {
    return $getAccountController()->show($req);
}, [], 'account.show');

$router->post('/account', function (Request $req) use ($getAccountController) {
    $action = $req->getString('_action', '');
    if ($action === 'password') {
        return $getAccountController()->changePassword($req);
    }
    return $getAccountController()->updateProfile($req);
}, [], 'account.post_handler');

$router->post('/account/profile', function (Request $req) use ($getAccountController) {
    return $getAccountController()->updateProfile($req);
}, [], 'account.post_profile');

$router->put('/api/v1/account/profile', function (Request $req) use ($getAccountController) {
    return $getAccountController()->updateProfile($req);
}, [], 'account.api_profile');

$router->post('/account/password', function (Request $req) use ($getAccountController) {
    return $getAccountController()->changePassword($req);
}, [], 'account.post_password');

$router->post('/api/v1/account/password', function (Request $req) use ($getAccountController) {
    return $getAccountController()->changePassword($req);
}, [], 'account.api_password');

$router->get('/api/v1/account', function (Request $req) use ($getAccountController) {
    return $getAccountController()->show($req);
}, [], 'account.api_get');

// ── Admin Area & IAM Management Console ──────────────────────────────────────
$router->get('/admin', function (Request $req) use ($getAdminController) {
    return $getAdminController()->dashboard($req);
}, [], 'admin.dashboard');

$router->get('/admin/users', function (Request $req) use ($getAdminController) {
    return $getAdminController()->users($req);
}, [], 'admin.users');

$router->post('/admin/users', function (Request $req) use ($getAdminController) {
    $action = $req->getString('_action', '');
    $userId = $req->getString('user_id', '');

    if ($action === 'delete' && $userId !== '') {
        return $getAdminController()->deleteUser($req, $userId);
    }

    if ($action === 'update' && $userId !== '') {
        return $getAdminController()->updateUser($req, $userId);
    }

    return $getAdminController()->createUser($req);
}, [], 'admin.users_post_handler');

$router->post('/api/v1/admin/users', function (Request $req) use ($getAdminController) {
    return $getAdminController()->createUser($req);
}, [], 'admin.users_create_api');

$router->post('/admin/users/{id}/update', function (Request $req, array $params) use ($getAdminController) {
    return $getAdminController()->updateUser($req, $params['id']);
}, [], 'admin.users_update_form');

$router->put('/api/v1/admin/users/{id}', function (Request $req, array $params) use ($getAdminController) {
    return $getAdminController()->updateUser($req, $params['id']);
}, [], 'admin.users_update_api');

$router->post('/admin/users/{id}/delete', function (Request $req, array $params) use ($getAdminController) {
    return $getAdminController()->deleteUser($req, $params['id']);
}, [], 'admin.users_delete_form');

$router->delete('/api/v1/admin/users/{id}', function (Request $req, array $params) use ($getAdminController) {
    return $getAdminController()->deleteUser($req, $params['id']);
}, [], 'admin.users_delete_api');

$router->get('/admin/roles', function (Request $req) use ($getAdminController) {
    return $getAdminController()->roles($req);
}, [], 'admin.roles');

$router->get('/api/v1/admin/roles', function (Request $req) use ($getAdminController) {
    return $getAdminController()->roles($req);
}, [], 'admin.roles_api');

$router->get('/admin/groups', function (Request $req) use ($getAdminController) {
    return $getAdminController()->groups($req);
}, [], 'admin.groups');

$router->get('/api/v1/admin/groups', function (Request $req) use ($getAdminController) {
    return $getAdminController()->groups($req);
}, [], 'admin.groups_api');

$router->get('/admin/audit', function (Request $req) use ($getAdminController) {
    return $getAdminController()->audit($req);
}, [], 'admin.audit');

$router->get('/api/v1/admin/audit', function (Request $req) use ($getAdminController) {
    return $getAdminController()->audit($req);
}, [], 'admin.audit_api');

$router->get('/nexus/admin', function (Request $req) use ($getAdminController) {
    return $getAdminController()->dashboard($req);
}, [], 'admin.nexus_alias');

// ── GDPR & Data Subject Rights ───────────────────────────────────────────────
$router->post('/api/v1/gdpr/consent', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $consentMgr = new ConsentManager($db);
    $consentMgr->recordConsent($req->getString('user_id', 'anon'), $req->getString('purpose', 'analytics'), $req->getString('version', 'v1.0'), $req);
    return Response::json(['consent_logged' => true], 'Consent recorded.', 200, [], $req->getTraceId());
}, [], 'gdpr.consent');

$router->post('/api/v1/gdpr/export', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $storage = new Storage($app->getBasePath() . '/storage/app');
    $gdpr = new GdprManager($db, $storage);
    $data = $gdpr->exportData($req->getString('user_id', '1'));
    return Response::json($data, 'Data portability export generated.', 200, [], $req->getTraceId());
}, [], 'gdpr.export');

$router->post('/api/v1/gdpr/erase', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $storage = new Storage($app->getBasePath() . '/storage/app');
    $gdpr = new GdprManager($db, $storage);
    $gdpr->eraseData($req->getString('user_id', '1'));
    return Response::json(['erased' => true], 'Right to be forgotten completed (data shredded).', 200, [], $req->getTraceId());
}, [], 'gdpr.erase');

// ── Public Gateway ────────────────────────────────────────────────────────────
$router->get('/', function (Request $req) use ($app) {
    $view = new View($app->getBasePath() . '/resources/views');
    $html = $view->render('welcome');
    return Response::html($html);
}, [], 'home');

// ── Secured Cyber-Noir Telemetry Cockpit (HTML) ──────────────────────────────
$router->get('/nexus/monitor', function (Request $req) use ($app) {
    $view = new View($app->getBasePath() . '/resources/views');
    $profiler = new \Nexus\Monitor\SystemProfiler();
    $vitals = $profiler->getMetrics();
    $html = $view->render('monitor.dashboard', ['vitals' => $vitals]);
    return Response::html($html);
}, [MonitorAccessMiddleware::class], 'nexus.monitor');

// ── 360° Monitor Dashboard Telemetry API ─────────────────────────────────────
$router->group([
    'prefix' => '/api/v1/monitor',
    'middleware' => [MonitorAccessMiddleware::class],
], function (Router $r) use ($app) {
    $handler = function (string $method) use ($app) {
        return function (Request $req) use ($app, $method) {
            /** @var Database $db */
            $db = $app->getContainer()->make(Database::class);
            $controller = new MonitorController($db, $app->getRouter());
            return $controller->$method($req);
        };
    };

    $r->get('/overview', $handler('overview'), [], 'monitor.overview');
    $r->get('/security', $handler('security'), [], 'monitor.security');
    $r->get('/traffic', $handler('traffic'), [], 'monitor.traffic');
    $r->get('/database', $handler('database'), [], 'monitor.database');
    $r->get('/queue', $handler('queue'), [], 'monitor.queue');
    $r->post('/queue/retry', $handler('retryJob'), [], 'monitor.queue_retry');
    $r->get('/scheduler', $handler('scheduler'), [], 'monitor.scheduler');
    $r->get('/cache', $handler('cache'), [], 'monitor.cache');
    $r->delete('/cache', $handler('flushCache'), [], 'monitor.cache_flush');
    $r->get('/audit', $handler('audit'), [], 'monitor.audit');
    $r->get('/routes', $handler('routes'), [], 'monitor.routes');
});

