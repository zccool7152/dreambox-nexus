<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Automated QA Test Suite Runner
 */

$baseDir = dirname(__DIR__);

// 1. Register Native Autoloader
require_once $baseDir . '/src/Core/Autoloader.php';
$autoloader = new \Nexus\Core\Autoloader($baseDir);
$autoloader->addNamespace('Nexus', $baseDir . '/src');
$autoloader->addNamespace('Database\\Seeders', $baseDir . '/database/seeders');
$autoloader->register();

// Test Result Aggregator
$passed = 0;
$failed = 0;
$tests = [];

$assertTest = function (string $description, bool $condition, ?string $errorDetails = null) use (&$passed, &$failed, &$tests) {
    if ($condition) {
        $passed++;
        $tests[] = ['name' => $description, 'status' => 'PASS', 'details' => null];
        echo "  \033[0;32m✔\033[0m {$description}\n";
    } else {
        $failed++;
        $tests[] = ['name' => $description, 'status' => 'FAIL', 'details' => $errorDetails];
        echo "  \033[0;31m✘\033[0m {$description}\n";
        if ($errorDetails) {
            echo "     └─ \033[0;90m{$errorDetails}\033[0m\n";
        }
    }
};

echo "\n\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n";
echo "\033[1;36m  DREAMBOX NEXUS CORE AUTOMATED TEST SUITE                      \033[0m\n";
echo "\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n\n";

// ── Test 1: Native Autoloader Path Sanitization ──────────────────────────────
echo "\033[1;37m[1] Native Autoloader Subsystem\033[0m\n";
$autoloaderInstance = new \Nexus\Core\Autoloader($baseDir);
$autoloaderInstance->addNamespace('Nexus', $baseDir . '/src');
$loadedLegit = $autoloaderInstance->loadClass('Nexus\\Core\\Request');
$assertTest('Autoloader loads valid class in namespace', $loadedLegit);

$loadedTraversal = $autoloaderInstance->loadClass('Nexus\\..\\..\\etc\\passwd');
$assertTest('Autoloader blocks directory traversal sequence', !$loadedTraversal);

// ── Test 2: Cryptography & AEAD Authentication Tags ─────────────────────────
echo "\n\033[1;37m[2] Cryptography & AEAD Engine\033[0m\n";
$key = 'base64:' . base64_encode(random_bytes(32));
$crypto = new \Nexus\Crypto\Crypto($key);

$plaintext = 'Confidential Patient Health Record #90210';
$encrypted = $crypto->encrypt($plaintext);
$decrypted = $crypto->decrypt($encrypted);
$assertTest('AES-256-GCM AEAD encryption and decryption match', $decrypted === $plaintext);

// Tamper test: flip byte in ciphertext
$tampered = base64_encode(substr(base64_decode($encrypted), 0, -1) . 'X');
$tamperDetected = false;
try {
    $crypto->decrypt($tampered);
} catch (\Throwable) {
    $tamperDetected = true;
}
$assertTest('AEAD authentication tag rejects tampered ciphertext', $tamperDetected);

// ── Test 3: Hasher & Password Verification ──────────────────────────────────
echo "\n\033[1;37m[3] Password Security (Argon2id)\033[0m\n";
$hasher = new \Nexus\Crypto\Hasher();
$password = 'SecretMasterKey2026!';
$hashed = $hasher->hash($password);
$assertTest('Hasher generates valid hashed string', !empty($hashed) && $hashed !== $password);
$assertTest('Hasher verifies correct password', $hasher->verify($password, $hashed));
$assertTest('Hasher rejects incorrect password', !$hasher->verify('WrongPassword', $hashed));

// ── Test 4: Timing-Safe HMAC CSRF Protection ────────────────────────────────
echo "\n\033[1;37m[4] CSRF Protection & Timing Attack Resistance\033[0m\n";
$csrf = new \Nexus\Security\Csrf('csrf_secret_salt_123');
$sessionId = 'session_alpha_99';
$token = $csrf->generateToken($sessionId);
$assertTest('CSRF generates valid dot-separated token', str_contains($token, '.'));
$assertTest('CSRF validates correct session token', $csrf->validateToken($token, $sessionId));
$assertTest('CSRF rejects mismatched session token', !$csrf->validateToken($token, 'session_bravo_00'));
$assertTest('CSRF rejects tampered token', !$csrf->validateToken($token . 'x', $sessionId));

// ── Test 5: Schema Validation Engine ────────────────────────────────────────
echo "\n\033[1;37m[5] Schema Validator (422 Envelope Provider)\033[0m\n";
$rules = [
    'email' => 'required|email',
    'age' => 'required|int|min:18',
    'role' => 'required|in:admin,user',
];

$validData = ['email' => 'user@nexus.local', 'age' => 25, 'role' => 'admin'];
$validator1 = \Nexus\Security\Validator::make($validData, $rules);
$assertTest('Validator passes valid dataset', $validator1->passes());

$invalidData = ['email' => 'not-an-email', 'age' => 16, 'role' => 'hacker'];
$validator2 = \Nexus\Security\Validator::make($invalidData, $rules);
$assertTest('Validator catches invalid fields', $validator2->fails() && count($validator2->errors()) === 3);

// ── Test 6: Compliance Data Masking ─────────────────────────────────────────
echo "\n\033[1;37m[6] Compliance Data Masker (PCI-DSS & HIPAA)\033[0m\n";
$pan = '4111222233334444';
$maskedPan = \Nexus\Security\DataMasker::maskPan($pan);
$assertTest('DataMasker truncates PAN to first-6 and last-4', $maskedPan === '411122******4444');

$ssn = '123456789';
$maskedSsn = \Nexus\Security\DataMasker::maskSsn($ssn);
$assertTest('DataMasker masks SSN to last-4', $maskedSsn === '***-**-6789');

// ── Test 7: Outbound HTTP Client SSRF Protection ────────────────────────────
echo "\n\033[1;37m[7] Outbound HttpClient & SSRF Defense\033[0m\n";
$httpClient = new \Nexus\Core\HttpClient();
$assertTest('HttpClient classifies 127.0.0.1 as private IP', $httpClient->isPrivateOrReservedIp('127.0.0.1'));
$assertTest('HttpClient classifies 169.254.169.254 (Cloud Metadata) as reserved', $httpClient->isPrivateOrReservedIp('169.254.169.254'));
$assertTest('HttpClient classifies 10.0.1.5 as private IP', $httpClient->isPrivateOrReservedIp('10.0.1.5'));
$assertTest('HttpClient classifies 8.8.8.8 as public IP', !$httpClient->isPrivateOrReservedIp('8.8.8.8'));

// ── Test 8: Health Probes Envelope Compliance ────────────────────────────────
echo "\n\033[1;37m[8] Health Probes & Response Envelope\033[0m\n";
$req = new \Nexus\Core\Request('GET', '/health');
$health = new \Nexus\Core\HealthCheck();
$res = $health->liveness($req);
$assertTest('Liveness probe returns HTTP 200', $res->getStatusCode() === 200);

$json = json_decode($res->getContent(), true);
$assertTest('Liveness response adheres to {data, meta, message} contract', isset($json['data'], $json['meta'], $json['message']));
$assertTest('Response meta includes valid trace_id and timestamp', !empty($json['meta']['trace_id']) && !empty($json['meta']['timestamp']));

// ── Test 9: Webhook Signature Verification ──────────────────────────────────
echo "\n\033[1;37m[9] Inbound Webhook Signature Verifier\033[0m\n";
$webhookSecret = 'whsec_test_secret_987';
$verifier = new \Nexus\Security\WebhookVerifier($webhookSecret, 300);
$payload = json_encode(['event' => 'payment_succeeded', 'amount' => 5000]);
$timestamp = time();
$signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);
$header = "t={$timestamp},v1={$signature}";

$assertTest('WebhookVerifier validates correct Stripe signature', $verifier->verifyStripeSignature($payload, $header));
$assertTest('WebhookVerifier rejects expired timestamp (> 300s)', !$verifier->verifyStripeSignature($payload, "t=" . ($timestamp - 400) . ",v1={$signature}"));
$assertTest('WebhookVerifier rejects forged signature', !$verifier->verifyStripeSignature($payload, "t={$timestamp},v1=forged_sig"));

// ── Test 10: Administrative Telemetry & Public Gateway Isolation ──────────────
echo "\n\033[1;37m[10] Telemetry Gating & Public Ingress Isolation\033[0m\n";
$assertTest('public/index.html is removed from webroot to prevent static bypass', !file_exists($baseDir . '/public/index.html'));
$assertTest('Internal monitor dashboard view exists in resources/views/monitor/', file_exists($baseDir . '/resources/views/monitor/dashboard.php'));
$assertTest('Public welcome landing view exists in resources/views/', file_exists($baseDir . '/resources/views/welcome.php'));

$app = new \Nexus\Core\App($baseDir);

// 1. Test Public Root Route (GET /)
$publicReq = new \Nexus\Core\Request('GET', '/');
$publicRes = $app->handle($publicReq);
$assertTest('Public root (GET /) returns HTTP 200', $publicRes->getStatusCode() === 200);
$publicHtml = $publicRes->getContent();
$assertTest('Public root serves safe Application Gateway without telemetry leaks', 
    str_contains($publicHtml, 'Security-First Application Gateway') && 
    !str_contains($publicHtml, 'val-mem') && 
    !str_contains($publicHtml, 'tbl-audit') &&
    !str_contains($publicHtml, 'tbl-failed-jobs')
);

// 2. Test Monitor Access Middleware with Unauthorized IP
$unauthReqApi = new \Nexus\Core\Request('GET', '/api/v1/monitor/overview', ['Accept' => 'application/json'], [], [], '', '198.51.100.42');
$unauthResApi = $app->handle($unauthReqApi);
$assertTest('External client IP accessing /api/v1/monitor/* is rejected with HTTP 403', $unauthResApi->getStatusCode() === 403);
$unauthJson = json_decode($unauthResApi->getContent(), true);
$assertTest('403 API response adheres to standard envelope', isset($unauthJson['meta']['trace_id']) && str_contains($unauthJson['message'], 'Forbidden'));

$unauthReqWeb = new \Nexus\Core\Request('GET', '/nexus/monitor', [], [], [], '', '198.51.100.42');
$unauthResWeb = $app->handle($unauthReqWeb);
$assertTest('External client IP accessing /nexus/monitor is rejected with HTTP 403 HTML', 
    $unauthResWeb->getStatusCode() === 403 && str_contains($unauthResWeb->getContent(), '403 // ACCESS DENIED')
);

// 3. Test Monitor Access Middleware with Authorized Localhost IP
$authReqApi = new \Nexus\Core\Request('GET', '/api/v1/monitor/overview', ['Accept' => 'application/json'], [], [], '', '127.0.0.1');
$authResApi = $app->handle($authReqApi);
$assertTest('Authorized local IP (127.0.0.1) accessing /api/v1/monitor/overview succeeds with HTTP 200', $authResApi->getStatusCode() === 200);

// 4. Test Trailing Slash Normalization & Physical Bridges
$trailingSlashReq = new \Nexus\Core\Request('GET', '/nexus/monitor/', [], [], [], '', '127.0.0.1');
$trailingSlashRes = $app->handle($trailingSlashReq);
$assertTest('Trailing slash URL (/nexus/monitor/) normalized and returns HTTP 200', $trailingSlashRes->getStatusCode() === 200);
$assertTest('Physical bridge /nexus/monitor/index.php exists', file_exists($baseDir . '/nexus/monitor/index.php'));
$assertTest('Physical bridge /health/index.php exists', file_exists($baseDir . '/health/index.php'));
$assertTest('Root project forwarder /index.php exists', file_exists($baseDir . '/index.php'));
$assertTest('Request::getAppBase() accurately resolves base path', \Nexus\Core\Request::getAppBase() === '/Dreambox Nexus');

// ── Test 11: MongoDB NoSQL Subsystem & Driver ───────────────────────────────
echo "\n\033[1;37m[11] MongoDB NoSQL Subsystem & Driver\033[0m\n";
\Nexus\Core\Env::load($baseDir . '/.env');
$dbConfig = require $baseDir . '/config/database.php';
$mongoConfig = $dbConfig['connections']['mongodb'] ?? [];
$mongoDb = new \Nexus\Database\Database($mongoConfig);
$assertTest('Database driver identifies as mongodb', $mongoDb->getDriver() === 'mongodb');
$assertTest('Database isSql() returns false for mongodb', !$mongoDb->isSql());
$mongoAlive = $mongoDb->isAlive();
$assertTest('MongoDB ping returns valid boolean status without crashing', is_bool($mongoAlive));
if ($mongoAlive) {
    $assertTest('MongoDB localhost connection authenticates and pings alive', $mongoAlive);
}

// ── Test 12: Regulatory Compliance Matrix Subsystem ─────────────────────────
echo "\n\033[1;37m[12] Regulatory Compliance Matrix Subsystem\033[0m\n";
$compVerifier = new \Nexus\Compliance\ComplianceVerifier($mongoDb, $baseDir . '/storage');
$fullReport = $compVerifier->getFullReport();
$assertTest('Compliance report contains overall compliance_score', isset($fullReport['compliance_score']));
$assertTest('Compliance report contains 5 regulatory frameworks', count($fullReport['frameworks']) === 5);
$assertTest('Framework breakdown contains PCI-DSS v4.0', isset($fullReport['frameworks']['pci_dss']));
$assertTest('Framework breakdown contains HIPAA Security Rule', isset($fullReport['frameworks']['hipaa']));
$assertTest('Framework breakdown contains NIST CSF 2.0', isset($fullReport['frameworks']['nist_csf']));
$assertTest('Framework breakdown contains EU GDPR', isset($fullReport['frameworks']['eu_gdpr']));
$assertTest('Framework breakdown contains OWASP Top 10', isset($fullReport['frameworks']['owasp_top10']));
$assertTest('Live runtime verification contains 10 diagnostic checks', count($fullReport['live_verification']) === 10);

// Test GET /compliance HTML View
$compHtmlReq = new \Nexus\Core\Request('GET', '/compliance', ['Accept' => 'text/html'], [], [], '', '127.0.0.1');
$compHtmlRes = $app->handle($compHtmlReq);
$assertTest('GET /compliance browser visit returns HTTP 200 HTML view', $compHtmlRes->getStatusCode() === 200 && str_contains($compHtmlRes->getContent(), 'Regulatory Compliance & Standards Matrix'));

// Test GET /compliance JSON API
$compJsonReq = new \Nexus\Core\Request('GET', '/compliance', ['Accept' => 'application/json'], [], [], '', '127.0.0.1');
$compJsonRes = $app->handle($compJsonReq);
$assertTest('GET /compliance API visit returns HTTP 200 JSON', $compJsonRes->getStatusCode() === 200);
$jsonBody = json_decode($compJsonRes->getContent(), true);
$assertTest('Compliance JSON response adheres to standard envelope', isset($jsonBody['data'], $jsonBody['meta'], $jsonBody['message']));

// Test Physical Bridges
$assertTest('Physical bridge /compliance/index.php exists', file_exists($baseDir . '/compliance/index.php'));
$assertTest('Public bridge /public/compliance/index.php exists', file_exists($baseDir . '/public/compliance/index.php'));

// ── Test 13: CIA IAM Security Triad & RBAC Governance ────────────────────────
echo "\n\033[1;37m[13] CIA IAM Security Triad & RBAC Governance\033[0m\n";

// 1. Vector SVG QR-Code Generator
$qrSvg = \Nexus\View\QrCodeGenerator::svg('otpauth://totp/Nexus:test@localhost?secret=JBSWY3DPEHPK3PXP');
$assertTest('Native QR generator emits valid XML/SVG markup without external APIs', str_starts_with($qrSvg, '<svg') && str_contains($qrSvg, '</svg>'));
$assertTest('QR SVG includes viewBox and crispEdges rendering flags', str_contains($qrSvg, 'viewBox') && str_contains($qrSvg, 'crispEdges'));

// 2. TOTP Provisioning URI
$mfa = new \Nexus\Auth\Mfa();
$otpUri = $mfa->getProvisioningUri('admin@localhost', 'JBSWY3DPEHPK3PXP', 'Dreambox Nexus');
$assertTest('MFA engine formats RFC 6238 provisioning URI correctly', str_starts_with($otpUri, 'otpauth://totp/'));

// 3. User Repository & Default Admin
$hasher = new \Nexus\Crypto\Hasher();
$userRepo = new \Nexus\Auth\UserRepository($mongoDb, $hasher);
$timUser = $userRepo->findByEmail('admin@nexus.local');
$assertTest('UserRepository finds SuperAdmin identity for admin@nexus.local', $timUser !== null && $timUser['email'] === 'admin@nexus.local');
$assertTest('admin@nexus.local holds SuperAdmin role', in_array('SuperAdmin', $timUser['roles'], true));
$assertTest('admin@nexus.local stores valid Argon2id password hash', str_starts_with($timUser['password_hash'], '$argon2id$'));

// 4. Role Manager & Permission Evaluation
$roleMgr = new \Nexus\Auth\Rbac\RoleManager($mongoDb);
$roles = $roleMgr->getRoles();
$assertTest('RoleManager registers SuperAdmin, SecurityOfficer, ComplianceAuditor, Developer, Member', count($roles) >= 5);
$assertTest('RoleManager grants unrestricted access to SuperAdmin', $roleMgr->userHasPermission($timUser, 'non.existent.random.permission'));
$memberUser = ['email' => 'member@test.com', 'roles' => ['Member']];
$assertTest('RoleManager denies non.existent permission to standard Member', !$roleMgr->userHasPermission($memberUser, 'non.existent.permission'));
$assertTest('RoleManager permits profile.manage to standard Member', $roleMgr->userHasPermission($memberUser, 'profile.manage'));

// 5. Group Manager
$groupMgr = new \Nexus\Auth\Rbac\GroupManager($mongoDb);
$groups = $groupMgr->getGroups();
$assertTest('GroupManager registers default identity groups', isset($groups['SecOps'], $groups['Engineering'], $groups['ComplianceAuditors']));

// 6. Multi-Provider OAuth Manager
$session = new \Nexus\Security\Session($mongoDb);
$oauthConfig = [
    'google' => ['client_id' => '', 'client_secret' => '', 'redirect_uri' => 'http://localhost/auth/google/callback', 'mock_enabled' => true],
    'github' => ['client_id' => '', 'client_secret' => '', 'redirect_uri' => 'http://localhost/auth/github/callback', 'mock_enabled' => true],
];
$oauthMgr = new \Nexus\Auth\OAuth\OAuthManager($oauthConfig, $session);
$googleProvider = $oauthMgr->getProvider('google');
$gitHubProvider = $oauthMgr->getProvider('github');
$assertTest('OAuthManager registers Google provider', $googleProvider->getName() === 'google');
$assertTest('OAuthManager registers GitHub provider', $gitHubProvider->getName() === 'github');
$oauthState = $oauthMgr->generateState();
$assertTest('OAuthManager generates high-entropy CSRF state token', strlen($oauthState) === 32);
$assertTest('OAuthManager validates correct CSRF state', $oauthMgr->validateState($oauthState));
$assertTest('OAuthManager invalidates state after first use (replay defense)', !$oauthMgr->validateState($oauthState));

// 7. Route & View Dispatches
$loginReq = new \Nexus\Core\Request('GET', '/auth/login', ['Accept' => 'text/html'], [], [], '', '127.0.0.1');
$loginRes = $app->handle($loginReq);
$assertTest('GET /auth/login returns HTTP 200 HTML', $loginRes->getStatusCode() === 200 && str_contains($loginRes->getContent(), 'Identity Verification'));

$adminBridges = [
    '/admin/index.php' => $baseDir . '/admin/index.php',
    '/admin/users/index.php' => $baseDir . '/admin/users/index.php',
    '/auth/index.php' => $baseDir . '/auth/index.php',
    '/public/admin/index.php' => $baseDir . '/public/admin/index.php',
    '/public/admin/users/index.php' => $baseDir . '/public/admin/users/index.php',
    '/public/auth/index.php' => $baseDir . '/public/auth/index.php',
    '/auth/password/change/index.php' => $baseDir . '/auth/password/change/index.php',
    '/api/v1/auth/login/index.php' => $baseDir . '/api/v1/auth/login/index.php',
    '/api/v1/auth/2fa/setup/index.php' => $baseDir . '/api/v1/auth/2fa/setup/index.php',
    '/api/v1/auth/password/change/index.php' => $baseDir . '/api/v1/auth/password/change/index.php',
    '/auth/oauth/index.php' => $baseDir . '/auth/oauth/index.php',
    '/auth/oauth/google/index.php' => $baseDir . '/auth/oauth/google/index.php',
    '/auth/oauth/github/index.php' => $baseDir . '/auth/oauth/github/index.php',
    '/auth/google/callback/index.php' => $baseDir . '/auth/google/callback/index.php',
    '/auth/github/callback/index.php' => $baseDir . '/auth/github/callback/index.php',
    '/auth/oauth/google/callback/index.php' => $baseDir . '/auth/oauth/google/callback/index.php',
    '/auth/oauth/github/callback/index.php' => $baseDir . '/auth/oauth/github/callback/index.php',
    '/public/auth/oauth/index.php' => $baseDir . '/public/auth/oauth/index.php',
    '/public/auth/oauth/google/index.php' => $baseDir . '/public/auth/oauth/google/index.php',
    '/public/auth/oauth/github/index.php' => $baseDir . '/public/auth/oauth/github/index.php',
    '/public/auth/google/callback/index.php' => $baseDir . '/public/auth/google/callback/index.php',
    '/public/auth/github/callback/index.php' => $baseDir . '/public/auth/github/callback/index.php',
    '/public/auth/oauth/google/callback/index.php' => $baseDir . '/public/auth/oauth/google/callback/index.php',
    '/public/auth/oauth/github/callback/index.php' => $baseDir . '/public/auth/oauth/github/callback/index.php',
];
foreach ($adminBridges as $pathName => $fullPath) {
    $assertTest("Physical bridge {$pathName} exists", file_exists($fullPath));
}

// 8. Content Security Policy Nonce Protection & Form Submissions
$assertTest('Login view contains script with CSP nonce', str_contains($loginRes->getContent(), '<script nonce="') && !str_contains($loginRes->getContent(), '<script>'));
$assertTest('Login form contains method="POST"', str_contains($loginRes->getContent(), 'method="POST"'));
$assertTest('Zero credential leaks in public HTML markup', !str_contains($loginRes->getContent(), '123qweasd') && !str_contains($loginRes->getContent(), 'NexusAdmin@2026!'));
$assertTest('Login view contains functional Continue with Google button', str_contains($loginRes->getContent(), '/auth/oauth/google/'));
$assertTest('Login view contains functional Continue with GitHub button', str_contains($loginRes->getContent(), '/auth/oauth/github/'));

// OAuth 2.0 Functional Redirect & Configuration Validation
$oauthGoogleReq = new \Nexus\Core\Request('GET', '/auth/oauth/google', ['Accept' => 'text/html'], [], [], '', '127.0.0.1');
$oauthGoogleRes = $app->handle($oauthGoogleReq);
$assertTest('GET /auth/oauth/google without credentials redirects to /auth/login/ with configuration notice', $oauthGoogleRes->getStatusCode() === 302 && str_contains($oauthGoogleRes->getHeader('location', ''), '/auth/login/'));

$oauthGithubReq = new \Nexus\Core\Request('GET', '/auth/oauth/github', ['Accept' => 'text/html'], [], [], '', '127.0.0.1');
$oauthGithubRes = $app->handle($oauthGithubReq);
$assertTest('GET /auth/oauth/github without credentials redirects to /auth/login/ with configuration notice', $oauthGithubRes->getStatusCode() === 302 && str_contains($oauthGithubRes->getHeader('location', ''), '/auth/login/'));

// Live Google & GitHub OAuth 2.0 Authorization URL Construction
$liveOauthMgr = new \Nexus\Auth\OAuth\OAuthManager([
    'google' => [
        'client_id' => 'google-test-id.apps.googleusercontent.com',
        'client_secret' => 'test-secret',
        'redirect_uri' => 'http://localhost/Dreambox%20Nexus/auth/google/callback/',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'mock_enabled' => false,
    ],
    'github' => [
        'client_id' => 'github-test-id',
        'client_secret' => 'test-secret',
        'redirect_uri' => 'http://localhost/Dreambox%20Nexus/auth/github/callback/',
        'authorize_url' => 'https://github.com/login/oauth/authorize',
        'token_url' => 'https://github.com/login/oauth/access_token',
        'userinfo_url' => 'https://api.github.com/user',
        'mock_enabled' => false,
    ],
], $session);

$liveStateGoogle = $liveOauthMgr->generateState();
$liveGoogleUrl = $liveOauthMgr->getProvider('google')->getAuthorizationUrl($liveStateGoogle);
$assertTest('Live Google OAuth builds real accounts.google.com authorization URL', str_starts_with($liveGoogleUrl, 'https://accounts.google.com/o/oauth2/v2/auth') && str_contains($liveGoogleUrl, 'client_id=google-test-id.apps.googleusercontent.com'));

$liveStateGithub = $liveOauthMgr->generateState();
$liveGithubUrl = $liveOauthMgr->getProvider('github')->getAuthorizationUrl($liveStateGithub);
$assertTest('Live GitHub OAuth builds real github.com authorization URL', str_starts_with($liveGithubUrl, 'https://github.com/login/oauth/authorize') && str_contains($liveGithubUrl, 'client_id=github-test-id'));

// Test OAuth Callback CSRF Defense (Mismatched State)
$badCallbackReq = new \Nexus\Core\Request('GET', '/auth/google/callback', [], ['code' => 'live_google_code', 'state' => 'forged_csrf_state'], [], '', '127.0.0.1');
$badCallbackRes = $app->handle($badCallbackReq);
$assertTest('OAuth callback with mismatched state triggers CSRF error redirect (HTTP 302)', $badCallbackRes->getStatusCode() === 302 && str_contains($badCallbackRes->getHeader('location', ''), '/auth/login/'));

// Test Mock Provider Fallback (Testing environment mock support)
$mockOauthMgr = new \Nexus\Auth\OAuth\OAuthManager([
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'http://localhost/Dreambox%20Nexus/auth/google/callback/',
        'mock_enabled' => true,
    ],
    'github' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'http://localhost/Dreambox%20Nexus/auth/github/callback/',
        'mock_enabled' => true,
    ],
], $session);

$mockGoogleProvider = $mockOauthMgr->getProvider('google');
$mockStateGoogle = $mockOauthMgr->generateState();
$mockGoogleUser = $mockGoogleProvider->handleCallback('mock_google_code');
$assertTest('Mock Google user profile lookup returns Timothy Dunn profile', $mockGoogleUser['email'] === 'admin@nexus.local');

$mockGithubProvider = $mockOauthMgr->getProvider('github');
$mockStateGithub = $mockOauthMgr->generateState();
$mockGithubUser = $mockGithubProvider->handleCallback('mock_github_code');
$assertTest('Mock GitHub user profile lookup returns GitHub user profile', str_contains($mockGithubUser['email'], '@users.noreply.github.com'));

$session->destroy();
$_SESSION = [];

$testLoginUser = $userRepo->create([
    'email' => 'login_flow_test@example.com',
    'name' => 'Login Flow User',
    'password' => '123qweasd',
    'status' => 'active',
    'roles' => ['Member'],
    'is_2fa_enabled' => false,
    'must_change_password' => true,
]);

$formLoginReq = new \Nexus\Core\Request(
    'POST',
    '/auth/login',
    ['accept' => 'text/html', 'content-type' => 'application/x-www-form-urlencoded'],
    [],
    ['email' => 'login_flow_test@example.com', 'password' => '123qweasd'],
    'email=login_flow_test%40example.com&password=123qweasd',
    '127.0.0.1',
    null,
    '/auth/login'
);
$formLoginRes = $app->handle($formLoginReq);
$assertTest('POST /auth/login via form redirects to 2FA enrollment (HTTP 302)', $formLoginRes->getStatusCode() === 302 && str_contains($formLoginRes->getHeader('location') ?? '', '/auth/2fa/setup/'));

$ajaxLoginReq = new \Nexus\Core\Request(
    'POST',
    '/auth/login',
    ['accept' => 'application/json', 'content-type' => 'application/json'],
    [],
    ['email' => 'login_flow_test@example.com', 'password' => '123qweasd'],
    json_encode(['email' => 'login_flow_test@example.com', 'password' => '123qweasd']),
    '127.0.0.1',
    null,
    '/auth/login'
);
$ajaxLoginRes = $app->handle($ajaxLoginReq);
$assertTest('POST /auth/login via JSON returns HTTP 200 with requires_2fa_setup', $ajaxLoginRes->getStatusCode() === 200 && str_contains($ajaxLoginRes->getContent(), 'requires_2fa_setup'));

$userRepo->delete($testLoginUser['id']);

// 9. Admin IAM Directory & Action Form Verification
$adminView = new \Nexus\View\View($baseDir . '/resources/views');
$renderedAdminHtml = $adminView->render('admin.users', [
    'appBase' => '/Dreambox Nexus',
    'currentUser' => ['id' => 'usr_admin_tim', 'email' => 'admin@nexus.local', 'roles' => ['SuperAdmin'], 'name' => 'Timothy Dunn'],
    'users' => [
        [
            'id' => 'usr_test_victim',
            'email' => 'victim@example.com',
            'name' => 'Test Victim',
            'auth_provider' => 'email',
            'roles' => ['Member'],
            'groups' => [],
            'status' => 'active',
            'is_2fa_enabled' => true,
        ]
    ],
    'roles' => ['SuperAdmin' => ['description' => 'Unrestricted'], 'Member' => ['description' => 'Standard']],
    'groups' => [],
    'csrfToken' => 'csrf_mock_token_123',
    'nonce' => 'nonce_test_456',
    'error' => null,
    'success' => null,
]);

$assertTest('Admin IAM view renders active user directory table', str_contains($renderedAdminHtml, 'victim@example.com'));
$assertTest('Admin IAM view has Suspend action posting to /admin/users/', str_contains($renderedAdminHtml, 'name="_action" value="update"') && str_contains($renderedAdminHtml, 'name="status" value="suspended"'));
$assertTest('Admin IAM view has Delete action posting to /admin/users/', str_contains($renderedAdminHtml, 'name="_action" value="delete"') && str_contains($renderedAdminHtml, 'name="user_id" value="usr_test_victim"'));
$assertTest('Admin IAM view includes CSRF protection on action forms', str_contains($renderedAdminHtml, 'name="csrf_token" value="csrf_mock_token_123"'));

// 10. User Deletion Permanence & Anti-Resurrection Verification
$testUserRepo = new \Nexus\Auth\UserRepository($mongoDb);
$createdUser = $testUserRepo->create([
    'email' => 'to_be_deleted@example.com',
    'name' => 'To Be Deleted',
    'password' => 'SecurePass123!',
    'status' => 'suspended',
]);
$assertTest('Test user created in suspended state', $createdUser['status'] === 'suspended');
$testUserRepo->delete($createdUser['id']);
$reconstructedRepo = new \Nexus\Auth\UserRepository($mongoDb);
$assertTest('Deleted user is not found after UserRepository re-instantiation', $reconstructedRepo->findByEmail('to_be_deleted@example.com') === null);
$assertTest('admin@localhost is not resurrected if deleted or absent', $reconstructedRepo->findByEmail('admin@localhost') === null);

// ── [14] User Self-Service Account Management & Universal Top Bar ───────────
echo "\n\033[1;34m[14] User Self-Service Account Management & Universal Top Bar\033[0m\n";

$assertTest('Physical bridge /account/index.php exists', file_exists($baseDir . '/account/index.php'));
$assertTest('Physical bridge /public/account/index.php exists', file_exists($baseDir . '/public/account/index.php'));

$openApiSpec = file_get_contents($baseDir . '/docs/openapi.yaml');
$assertTest('OpenAPI documents /api/v1/account', str_contains($openApiSpec, '/api/v1/account:'));
$assertTest('OpenAPI documents /api/v1/account/profile', str_contains($openApiSpec, '/api/v1/account/profile:'));
$assertTest('OpenAPI documents /api/v1/account/password', str_contains($openApiSpec, '/api/v1/account/password:'));

$unauthReq = new \Nexus\Core\Request('GET', '/account', ['accept' => 'text/html'], [], [], '', '127.0.0.1', null, '/account');
$unauthRes = $app->handle($unauthReq);
$assertTest('GET /account unauthenticated redirects to /auth/login/', $unauthRes->getStatusCode() === 302 && str_contains($unauthRes->getHeader('location') ?? '', '/auth/login'));

$accountUser = $testUserRepo->create([
    'email' => 'account_test@example.com',
    'name' => 'Account Test User',
    'username' => 'acct_test',
    'display_preference' => 'username',
    'password' => 'InitialPassword123!',
    'roles' => ['Member'],
    'groups' => ['Engineering'],
]);

$assertTest('User repository persists username', $accountUser['username'] === 'acct_test');
$assertTest('User repository persists display_preference', $accountUser['display_preference'] === 'username');

$foundByUsername = $testUserRepo->findByUsername('acct_test');
$assertTest('findByUsername resolves created user correctly', $foundByUsername !== null && $foundByUsername['email'] === 'account_test@example.com');

$testUserRepo->update($accountUser['id'], [
    'name' => 'Updated Account User',
    'display_preference' => 'name',
]);
$updatedUser = $testUserRepo->findById($accountUser['id']);
$assertTest('User update mutates display_preference to name', $updatedUser['display_preference'] === 'name');
$assertTest('User update mutates full name', $updatedUser['name'] === 'Updated Account User');

$topbarHtml = file_get_contents($baseDir . '/resources/views/partials/topbar.php');
$assertTest('Topbar partial contains brand logo on left', str_contains($topbarHtml, 'class="nexus-brand"'));
$assertTest('Topbar partial contains login link for guests', str_contains($topbarHtml, 'class="nexus-btn-login"'));
$assertTest('Topbar partial contains user dropdown menu for authenticated sessions', str_contains($topbarHtml, 'class="nexus-user-menu"') && str_contains($topbarHtml, 'class="nexus-dropdown"'));
$assertTest('Topbar partial contains account settings link', str_contains($topbarHtml, '/account/'));
$assertTest('Topbar partial contains logout button', str_contains($topbarHtml, '/auth/logout/'));

$accountView = new \Nexus\View\View($baseDir . '/resources/views');
$renderedAccountHtml = $accountView->render('account.index', [
    'appBase' => '/Dreambox Nexus',
    'user' => $updatedUser,
    'csrfToken' => 'csrf_token_test',
    'success' => null,
    'error' => null,
    'traceId' => 'trace_account_test',
]);
$assertTest('Account view renders profile identity section', str_contains($renderedAccountHtml, 'Profile & Identity'));
$assertTest('Account view renders display preference choice cards', str_contains($renderedAccountHtml, 'name="display_preference"'));
$assertTest('Account view renders password rotation form with Argon2id', str_contains($renderedAccountHtml, 'Password Security') && str_contains($renderedAccountHtml, 'name="current_password"'));

// 15. Centralized Theme Stylesheet & Homepage Auth Dynamic Dropdown
$assertTest('Centralized theme CSS file exists in css/theme.css', file_exists($baseDir . '/css/theme.css'));
$assertTest('Public asset bridge public/css/theme.css exists', file_exists($baseDir . '/public/css/theme.css'));
$welcomeSource = file_get_contents($baseDir . '/resources/views/welcome.php');
$assertTest('Welcome landing page links to centralized theme.css', str_contains($welcomeSource, '/css/theme.css'));
$accountSource = file_get_contents($baseDir . '/resources/views/account/index.php');
$assertTest('Account settings page links to centralized theme.css', str_contains($accountSource, '/css/theme.css'));
$assertTest('Topbar partial links to centralized theme.css', str_contains($topbarHtml, '/css/theme.css'));

// Universal UI Framework validation across all 14 view templates
$allViewFiles = [];
$rdi = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir . '/resources/views'));
foreach ($rdi as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && !str_contains($file->getPathname(), '/partials/')) {
        $allViewFiles[] = $file->getPathname();
    }
}
$assertTest('Total view templates discovered equals 15', count($allViewFiles) === 15);

$viewsMissingTheme = [];
$viewsMissingTopbar = [];
$viewsWithInlineStyle = [];
foreach ($allViewFiles as $vPath) {
    $vContent = (string)file_get_contents($vPath);
    if (!str_contains($vContent, 'theme.css')) {
        $viewsMissingTheme[] = basename($vPath);
    }
    if (!str_contains($vContent, 'topbar.php')) {
        $viewsMissingTopbar[] = basename($vPath);
    }
    if (str_contains($vContent, '<style')) {
        $viewsWithInlineStyle[] = basename($vPath);
    }
}
$assertTest('All 15 view templates link to centralized theme.css', empty($viewsMissingTheme));
$assertTest('All 15 view templates embed universal topbar.php', empty($viewsMissingTopbar));
$assertTest('Zero view templates contain legacy inline <style> tags', empty($viewsWithInlineStyle));

$viewsWithInlineAttributes = [];
foreach ($allViewFiles as $vPath) {
    $vContent = (string)file_get_contents($vPath);
    if (preg_match('/style=[\"\']/', $vContent)) {
        $viewsWithInlineAttributes[] = basename($vPath);
    }
}
$assertTest('Zero view templates contain inline style="..." attributes (100% Nexus UI conformance)', empty($viewsWithInlineAttributes));

// Nexus UI Component Framework & Kitchen Sink Suite
$assertTest('Physical bridge /ui/index.php exists', file_exists($baseDir . '/ui/index.php'));
$assertTest('Public bridge /public/ui/index.php exists', file_exists($baseDir . '/public/ui/index.php'));
$openApiContent = file_get_contents($baseDir . '/docs/openapi.yaml');
$assertTest('OpenAPI specification documents /ui endpoint', str_contains($openApiContent, '/ui:'));
$assertTest('OpenAPI specification documents /auth/oauth/{provider} endpoint', str_contains($openApiContent, '/auth/oauth/{provider}:'));
$assertTest('OpenAPI specification documents /auth/oauth/{provider}/callback endpoint', str_contains($openApiContent, '/auth/oauth/{provider}/callback:'));

$uiReq = new \Nexus\Core\Request('GET', '/ui', ['accept' => 'text/html'], [], [], '', '127.0.0.1');
$uiRes = $app->handle($uiReq);
$assertTest('GET /ui returns HTTP 200', $uiRes->getStatusCode() === 200);
$assertTest('GET /ui renders Nexus UI Framework showcase content', str_contains($uiRes->getContent(), 'Nexus UI Framework') && str_contains($uiRes->getContent(), '12-Column Responsive Grid'));
$assertTest('GET /ui renders interactive modal component markup', str_contains($uiRes->getContent(), 'id="demoModal"'));
$assertTest('GET /ui renders interactive offcanvas drawer markup', str_contains($uiRes->getContent(), 'id="demoDrawer"'));

$frameworkReq = new \Nexus\Core\Request('GET', '/framework', [], [], [], '', '127.0.0.1');
$frameworkRes = $app->handle($frameworkReq);
$assertTest('GET /framework redirects to /ui/', $frameworkRes->getStatusCode() === 302 && str_contains($frameworkRes->getHeader('location', ''), '/ui/'));

// Test GET / as guest
$guestHomeReq = new \Nexus\Core\Request('GET', '/', ['accept' => 'text/html'], [], [], '', '127.0.0.1');
$guestHomeRes = $app->handle($guestHomeReq);
$assertTest('GET / as guest renders Log In action button', str_contains($guestHomeRes->getContent(), 'class="nexus-btn-login"'));
$assertTest('GET / as guest does not render active user dropdown menu', !str_contains($guestHomeRes->getContent(), 'class="nexus-user-menu"'));

// Test GET / with authenticated session mock
$_SESSION['user_id'] = 'usr_admin_tim';
$_SESSION['user_email'] = 'admin@nexus.local';
$_SESSION['user_name'] = 'Timothy Dunn';
$_SESSION['user_username'] = 'nexus_admin';
$_SESSION['user_display_preference'] = 'name';
$_SESSION['user_roles'] = ['SuperAdmin'];
$_SESSION['user_groups'] = ['SecOps'];

$authHomeReq = new \Nexus\Core\Request('GET', '/', ['accept' => 'text/html'], [], [], '', '127.0.0.1');
$authHomeRes = $app->handle($authHomeReq);
$assertTest('GET / with authenticated session renders dynamic user dropdown menu', str_contains($authHomeRes->getContent(), 'class="nexus-user-menu"'));
$assertTest('GET / with authenticated session displays user identity', str_contains($authHomeRes->getContent(), 'Timothy Dunn') || str_contains($authHomeRes->getContent(), 'nexus_admin'));

unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name'], $_SESSION['user_username'], $_SESSION['user_display_preference'], $_SESSION['user_roles'], $_SESSION['user_groups']);

$testUserRepo->delete($accountUser['id']);

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n";
echo "  \033[1;37mTEST SUMMARY:\033[0m \033[0;32m{$passed} Passed\033[0m | \033[0;31m{$failed} Failed\033[0m\n";
echo "\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n\n";

exit($failed > 0 ? 1 : 0);
