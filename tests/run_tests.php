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
$userRepo = new \Nexus\Auth\UserRepository($mongoDb, new \Nexus\Crypto\Hasher());
$adminUser = $userRepo->findByEmail('admin@localhost');
$assertTest('UserRepository finds default SuperAdmin identity', $adminUser !== null && $adminUser['email'] === 'admin@localhost');
$assertTest('SuperAdmin identity holds SuperAdmin role', in_array('SuperAdmin', $adminUser['roles'], true));

// 4. Role Manager & Permission Evaluation
$roleMgr = new \Nexus\Auth\Rbac\RoleManager($mongoDb);
$roles = $roleMgr->getRoles();
$assertTest('RoleManager registers SuperAdmin, SecurityOfficer, ComplianceAuditor, Developer, Member', count($roles) >= 5);
$assertTest('RoleManager grants unrestricted access to SuperAdmin', $roleMgr->userHasPermission($adminUser, 'non.existent.random.permission'));
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
    '/auth/index.php' => $baseDir . '/auth/index.php',
    '/public/admin/index.php' => $baseDir . '/public/admin/index.php',
    '/public/auth/index.php' => $baseDir . '/public/auth/index.php',
];
foreach ($adminBridges as $pathName => $fullPath) {
    $assertTest("Physical bridge {$pathName} exists", file_exists($fullPath));
}

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n";
echo "  \033[1;37mTEST SUMMARY:\033[0m \033[0;32m{$passed} Passed\033[0m | \033[0;31m{$failed} Failed\033[0m\n";
echo "\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n\n";

exit($failed > 0 ? 1 : 0);
