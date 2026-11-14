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

$docsHandler = function (Request $req) use ($app) {
    $file = $app->getBasePath() . '/docs/openapi.yaml';
    if (!file_exists($file)) {
        return Response::error('OpenAPI specification not found.', [], 404, [], $req->getTraceId());
    }
    $yaml = (string)file_get_contents($file);

    $accept = $req->getHeader('accept', '');
    $format = $req->getString('format');
    $wantsYaml = str_contains($accept, 'text/yaml') || str_contains($accept, 'application/x-yaml') || $format === 'yaml';

    if (!$wantsYaml && (str_contains($accept, 'text/html') || $req->getPath() === '/docs' || empty($accept))) {
        /** @var Database $db */
        $db = $app->getContainer()->make(Database::class);
        $session = new \Nexus\Security\Session($db);
        $hasher = new \Nexus\Crypto\Hasher();
        $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
        $user = $auth->user($req);

        $view = new View($app->getBasePath() . '/resources/views');
        $html = $view->render('docs', [
            'rawYaml' => $yaml,
            'traceId' => $req->getTraceId(),
            'user' => $user,
            'currentUser' => $user,
            'appBase' => Request::getAppBase(),
        ]);
        return Response::html($html);
    }

    return new Response($yaml, 200, [
        'Content-Type' => 'text/yaml; charset=utf-8',
    ]);
};

$router->get('/docs', $docsHandler, [], 'docs.index');
$router->get('/docs/openapi.yaml', $docsHandler, [], 'docs.openapi');

// ── Security & CSRF ──────────────────────────────────────────────────────────
$router->get('/api/v1/security/csrf-token', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $session->start();
    $csrf = new Csrf((string)\Nexus\Core\Env::get('APP_KEY', ''));
    $token = $csrf->generate($session->getId());
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
    $csrf = new Csrf((string)\Nexus\Core\Env::get('APP_KEY', ''));

    return new \Nexus\Auth\AuthController(
        $auth, $userRepo, $mfa, $lockout, $oauth, $rbac, $view, $session, $csrf, $db
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
    $csrf = new Csrf((string)\Nexus\Core\Env::get('APP_KEY', ''));

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
    $csrf = new Csrf((string)\Nexus\Core\Env::get('APP_KEY', ''));

    return new \Nexus\Auth\AccountController(
        $auth, $userRepo, $hasher, $db, $view, $csrf, $session
    );
};

$getMailController = function () use ($app): \Nexus\Mail\MailController {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $userRepo = new \Nexus\Auth\UserRepository($db, $hasher);
    $roleMgr = new \Nexus\Auth\Rbac\RoleManager($db);
    $mailRepo = new \Nexus\Mail\MailRepository($db, $app->getBasePath() . '/storage/app/private/mail');
    $scanner = new \Nexus\Mail\MailScanner();
    $jail = new \Nexus\Mail\MailJail($app->getBasePath() . '/storage/cache/jails');
    $view = new View($app->getBasePath() . '/resources/views');
    $csrf = new Csrf((string)\Nexus\Core\Env::get('APP_KEY', ''));

    return new \Nexus\Mail\MailController(
        $auth, $userRepo, $roleMgr, $mailRepo, $scanner, $jail, $db, $view, $csrf, $session
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

$router->get('/auth/oauth/{provider}', function (Request $req, string $provider) use ($getAuthController) {
    return $getAuthController()->redirectToOAuth($req, $provider);
}, [], 'auth.oauth_redirect');

$router->get('/auth/oauth/{provider}/callback', function (Request $req, string $provider) use ($getAuthController) {
    return $getAuthController()->handleOAuthCallback($req, $provider);
}, [], 'auth.oauth_callback');

$router->get('/auth/{provider}/callback', function (Request $req, string $provider) use ($getAuthController) {
    return $getAuthController()->handleOAuthCallback($req, $provider);
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

// ── Nexus Gmail-Style Webmail, Virus Scanner & Controlled Reader Jail ────────
$router->get('/mail', function (Request $req) use ($getMailController) {
    return $getMailController()->index($req);
}, [], 'mail.index');

$router->post('/mail', function (Request $req) use ($getMailController) {
    $action = strtolower(trim($req->getString('_action', '')));
    return match ($action) {
        'claim' => $getMailController()->claim($req),
        'send', 'draft' => $getMailController()->send($req),
        'open_jail' => $getMailController()->openJail($req, $req->getString('message_id')),
        'close_jail' => $getMailController()->closeJail($req, $req->getString('jail_id')),
        'toggle_star', 'mark_read', 'mark_unread', 'trash', 'restore', 'delete_forever' => $getMailController()->mutateMessage($req, $req->getString('message_id')),
        default => Response::error('Unknown mail action.', ['_action' => ['Unsupported mail action.']], 422, [], $req->getTraceId()),
    };
}, [], 'mail.post_dispatch');

$router->post('/mail/claim', function (Request $req) use ($getMailController) {
    return $getMailController()->claim($req);
}, [], 'mail.claim');

$router->post('/mail/send', function (Request $req) use ($getMailController) {
    return $getMailController()->send($req);
}, [], 'mail.send');

$router->post('/mail/messages/{id}', function (Request $req, string $id) use ($getMailController) {
    return $getMailController()->mutateMessage($req, $id);
}, [], 'mail.message_mutate');

$router->get('/mail/jail/{jail_id}', function (Request $req, string $jailId) use ($getMailController) {
    return $getMailController()->serveJailFrame($req, $jailId);
}, [], 'mail.jail_frame');

$router->post('/mail/jail/{jail_id}/close', function (Request $req, string $jailId) use ($getMailController) {
    return $getMailController()->closeJail($req, $jailId);
}, [], 'mail.jail_close_web');

$router->get('/api/v1/mail/messages', function (Request $req) use ($getMailController) {
    return $getMailController()->listMessages($req);
}, [], 'mail.api_messages');

$router->post('/api/v1/mail/claim', function (Request $req) use ($getMailController) {
    return $getMailController()->claim($req);
}, [], 'mail.api_claim');

$router->post('/api/v1/mail/send', function (Request $req) use ($getMailController) {
    return $getMailController()->send($req);
}, [], 'mail.api_send');

$router->post('/api/v1/mail/messages/{id}/open-jail', function (Request $req, string $id) use ($getMailController) {
    return $getMailController()->openJail($req, $id);
}, [], 'mail.api_open_jail');

$router->post('/api/v1/mail/messages/{id}', function (Request $req, string $id) use ($getMailController) {
    return $getMailController()->mutateMessage($req, $id);
}, [], 'mail.api_mutate');

$router->post('/api/v1/mail/jail/{jail_id}/close', function (Request $req, string $jailId) use ($getMailController) {
    return $getMailController()->closeJail($req, $jailId);
}, [], 'mail.api_close_jail');

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

$router->post('/admin/users/{id}/update', function (Request $req, string $id) use ($getAdminController) {
    return $getAdminController()->updateUser($req, $id);
}, [], 'admin.users_update_form');

$router->put('/api/v1/admin/users/{id}', function (Request $req, string $id) use ($getAdminController) {
    return $getAdminController()->updateUser($req, $id);
}, [], 'admin.users_update_api');

$router->post('/admin/users/{id}/delete', function (Request $req, string $id) use ($getAdminController) {
    return $getAdminController()->deleteUser($req, $id);
}, [], 'admin.users_delete_form');

$router->delete('/api/v1/admin/users/{id}', function (Request $req, string $id) use ($getAdminController) {
    return $getAdminController()->deleteUser($req, $id);
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
$authorizeGdpr = function (Request $req) use ($app): array {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $user = $auth->user($req);

    if ($user === null) {
        return ['db' => $db, 'error' => Response::error('Authentication required.', [], 401, [], $req->getTraceId())];
    }

    $targetUserId = $req->getString('user_id', (string)$user['id']);
    $roles = (array)($user['roles'] ?? []);
    $isPrivileged = in_array('SuperAdmin', $roles, true) || in_array('SecurityOfficer', $roles, true);

    if ($targetUserId !== (string)$user['id'] && !$isPrivileged) {
        return ['db' => $db, 'error' => Response::error('Forbidden: You may only manage GDPR data for your own identity.', [], 403, [], $req->getTraceId())];
    }

    return ['db' => $db, 'user_id' => $targetUserId, 'error' => null];
};

$router->post('/api/v1/gdpr/consent', function (Request $req) use ($authorizeGdpr) {
    $ctx = $authorizeGdpr($req);
    if ($ctx['error'] !== null) {
        return $ctx['error'];
    }
    $consentMgr = new ConsentManager($ctx['db']);
    $consentMgr->recordConsent($ctx['user_id'], $req->getString('purpose', 'analytics'), $req->getString('version', 'v1.0'), $req);
    return Response::json(['consent_logged' => true], 'Consent recorded.', 200, [], $req->getTraceId());
}, [], 'gdpr.consent');

$router->post('/api/v1/gdpr/export', function (Request $req) use ($authorizeGdpr, $app) {
    $ctx = $authorizeGdpr($req);
    if ($ctx['error'] !== null) {
        return $ctx['error'];
    }
    $storage = new Storage($app->getBasePath() . '/storage/app');
    $gdpr = new GdprManager($ctx['db'], $storage);
    $data = $gdpr->exportData($ctx['user_id']);
    return Response::json($data, 'Data portability export generated.', 200, [], $req->getTraceId());
}, [], 'gdpr.export');

$router->post('/api/v1/gdpr/erase', function (Request $req) use ($authorizeGdpr, $app) {
    $ctx = $authorizeGdpr($req);
    if ($ctx['error'] !== null) {
        return $ctx['error'];
    }
    $storage = new Storage($app->getBasePath() . '/storage/app');
    $gdpr = new GdprManager($ctx['db'], $storage);
    $gdpr->eraseData($ctx['user_id']);
    return Response::json(['erased' => true], 'Right to be forgotten completed (data shredded).', 200, [], $req->getTraceId());
}, [], 'gdpr.erase');

// ── Nexus UI Component Framework & Interactive Showcase ──────────────────────
$router->get('/ui', function (Request $req) use ($app): Response {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $user = $auth->user($req);

    $view = new View($app->getBasePath() . '/resources/views');
    $html = $view->render('ui.showcase', [
        'user' => $user,
        'currentUser' => $user,
        'appBase' => Request::getAppBase(),
        'traceId' => $req->getTraceId(),
    ]);
    return Response::html($html);
}, [], 'ui.showcase');

$router->get('/framework', function (Request $req) use ($app): Response {
    return Response::redirect(Request::getAppBase() . '/ui/');
}, [], 'framework.redirect');

// ── Public Gateway ────────────────────────────────────────────────────────────
$router->get('/', function (Request $req) use ($app): Response {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $user = $auth->user($req);

    $view = new View($app->getBasePath() . '/resources/views');
    $html = $view->render('welcome', [
        'user' => $user,
        'currentUser' => $user,
        'appBase' => Request::getAppBase(),
    ]);
    return Response::html($html);
}, [], 'home');

// ── Secured Cyber-Noir Telemetry Cockpit (HTML) ──────────────────────────────
$router->get('/nexus/monitor', function (Request $req) use ($app) {
    /** @var Database $db */
    $db = $app->getContainer()->make(Database::class);
    $session = new \Nexus\Security\Session($db);
    $hasher = new \Nexus\Crypto\Hasher();
    $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
    $user = $auth->user($req);

    $view = new View($app->getBasePath() . '/resources/views');
    $profiler = new \Nexus\Monitor\SystemProfiler();
    $vitals = $profiler->getMetrics();
    $html = $view->render('monitor.dashboard', [
        'vitals' => $vitals,
        'user' => $user,
        'currentUser' => $user,
        'appBase' => Request::getAppBase(),
    ]);
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

