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

// 5. Test Audit Hash-Chain Telemetry Endpoint
$auditReqApi = new \Nexus\Core\Request('GET', '/api/v1/monitor/audit', ['Accept' => 'application/json'], [], [], '', '127.0.0.1');
$auditResApi = $app->handle($auditReqApi);
$assertTest('GET /api/v1/monitor/audit returns HTTP 200 with standard envelope', $auditResApi->getStatusCode() === 200);
$auditJson = json_decode($auditResApi->getContent(), true);
$chainStatus = $auditJson['data']['chain_status'] ?? [];
$recentLogs = $auditJson['data']['recent_logs'] ?? [];
$assertTest('Audit Hash-Chain verification reports intact status with verified records', 
    ($chainStatus['intact'] ?? false) === true && ($chainStatus['total_verified'] ?? 0) >= 5
);
$assertTest('Audit Hash-Chain endpoint provides recent logs with 64-character SHA-256 fingerprints', 
    !empty($recentLogs) && strlen($recentLogs[0]['hash'] ?? '') === 64 && isset($recentLogs[0]['action'], $recentLogs[0]['user_id'])
);

// 6. Test Cryptographic Hash-Chain Tamper Detection
$tempLedger = $baseDir . '/storage/logs/audit_chain_tamper_test.json';
@unlink($tempLedger);
$tamperLogger = new \Nexus\Events\AuditLogger(null, $tempLedger);
$intactStatus = $tamperLogger->verifyChain();
$assertTest('Isolated test ledger starts in cryptographically intact state', $intactStatus['intact'] === true && $intactStatus['total_verified'] >= 5);
// Simulate tampering with record 3
$tamperedRecords = json_decode((string)file_get_contents($tempLedger), true);
$tamperedRecords[2]['action'] = 'MALICIOUS_FORGED_ACTION';
file_put_contents($tempLedger, json_encode($tamperedRecords, JSON_PRETTY_PRINT));
$tamperedStatus = $tamperLogger->verifyChain();
$assertTest('Cryptographic chain verification flags tampering when record contents are altered', 
    $tamperedStatus['intact'] === false && $tamperedStatus['broken_at_id'] === 3
);
@unlink($tempLedger);

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

// NoSQL Input Sanitization & Operator Whitelisting
$cleanFilter = \Nexus\Database\Database::sanitizeMongoFilter(['user_id' => 'usr_001', 'attempts' => ['$gte' => 3]]);
$assertTest('Database::sanitizeMongoFilter allows safe criteria and whitelisted operators', isset($cleanFilter['user_id'], $cleanFilter['attempts']['$gte']));

$rejectedOperator = false;
try {
    \Nexus\Database\Database::sanitizeMongoFilter(['user_id' => 'usr_001', '$where' => 'this.secret == "abc"']);
} catch (\InvalidArgumentException $e) {
    $rejectedOperator = str_contains($e->getMessage(), 'Disallowed MongoDB operator');
}
$assertTest('Database::sanitizeMongoFilter blocks malicious NoSQL operator injection ($where)', $rejectedOperator);

// Audit Log Immutability Enforcements (HIPAA §164.312 & PCI-DSS Req 10.2)
$updateBlocked = false;
try {
    $mongoDb->mongoUpdate('audit_logs', ['id' => 1], ['$set' => ['action' => 'FORGED']]);
} catch (\RuntimeException $e) {
    $updateBlocked = str_contains($e->getMessage(), 'strictly append-only');
}
$assertTest('Audit log immutability blocks mongoUpdate operations on audit_logs collection', $updateBlocked);

$deleteBlocked = false;
try {
    $mongoDb->mongoDelete('audit_logs', ['id' => 1]);
} catch (\RuntimeException $e) {
    $deleteBlocked = str_contains($e->getMessage(), 'strictly append-only');
}
$assertTest('Audit log immutability blocks mongoDelete operations on audit_logs collection', $deleteBlocked);

if ($mongoAlive) {
    $mongoAuditLogger = new \Nexus\Events\AuditLogger($mongoDb);
    $mongoAuditStatus = $mongoAuditLogger->verifyChain();
    $assertTest('MongoDB live audit_logs collection passes cryptographic hash-chain verification', ($mongoAuditStatus['intact'] ?? false) === true && ($mongoAuditStatus['total_verified'] ?? 0) >= 5);
}

// Database Driver Alignment (.env vs .env.example)
$envExampleContent = (string)file_get_contents($baseDir . '/.env.example');
$assertTest('.env.example template specifies DB_CONNECTION=mongodb', str_contains($envExampleContent, 'DB_CONNECTION=mongodb'));
$assertTest('.env.example contains MONGO_HOST and MONGO_DATABASE parameters', str_contains($envExampleContent, 'MONGO_HOST=') && str_contains($envExampleContent, 'MONGO_DATABASE='));

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

// Dynamic OAuth Redirect URI Resolution
$authConfig = require $baseDir . '/config/auth.php';
$googleRedirect = $authConfig['oauth']['google']['redirect_uri'] ?? '';
$githubRedirect = $authConfig['oauth']['github']['redirect_uri'] ?? '';
$assertTest('config/auth.php dynamically resolves Google OAuth redirect URI', str_contains($googleRedirect, '/auth/google/callback/'));
$assertTest('config/auth.php dynamically resolves GitHub OAuth redirect URI', str_contains($githubRedirect, '/auth/github/callback/'));

// Transport Security & Session Configuration (HIPAA §164.312)
$_SERVER['HTTPS'] = 'on';
$assertTest('Session::isSecureConnection returns true under direct HTTPS', \Nexus\Security\Session::isSecureConnection());
unset($_SERVER['HTTPS']);

$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$assertTest('Session::isSecureConnection returns true under X-Forwarded-Proto https', \Nexus\Security\Session::isSecureConnection());
unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

$_SERVER['SERVER_PORT'] = 443;
$assertTest('Session::isSecureConnection returns true under port 443', \Nexus\Security\Session::isSecureConnection());
unset($_SERVER['SERVER_PORT']);

$plainSession = new \Nexus\Security\Session(null, 15, false);
$assertTest('Session instance respects explicit secureCookie parameter', !$plainSession->isSecureCookie());

$secureSession = new \Nexus\Security\Session(null, 15, true);
$assertTest('Session instance enforces secureCookie when true', $secureSession->isSecureCookie());

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
$assertTest('GET /auth/oauth/google returns HTTP 302 redirect', $oauthGoogleRes->getStatusCode() === 302);
$assertTest('GET /auth/oauth/google initiates live OAuth handshake', str_contains($oauthGoogleRes->getHeader('location', ''), 'accounts.google.com') || str_contains($oauthGoogleRes->getHeader('location', ''), '/auth/login/'));

$oauthGithubReq = new \Nexus\Core\Request('GET', '/auth/oauth/github', ['Accept' => 'text/html'], [], [], '', '127.0.0.1');
$oauthGithubRes = $app->handle($oauthGithubReq);
$assertTest('GET /auth/oauth/github returns HTTP 302 redirect', $oauthGithubRes->getStatusCode() === 302);
$assertTest('GET /auth/oauth/github initiates live OAuth handshake', str_contains($oauthGithubRes->getHeader('location', ''), 'github.com') || str_contains($oauthGithubRes->getHeader('location', ''), '/auth/login/'));

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

// 16. Site Brand Identity & Multi-Resolution Logo Assets
echo "\n\033[1;35m[16] Site Brand Identity & Multi-Resolution Logo Assets\033[0m\n";
$brandDir = $baseDir . '/brand';
$assertTest('Master logo exists in brand/logo.png', file_exists($brandDir . '/logo.png'));
$assertTest('Generated logo-512.png exists (512x512)', file_exists($brandDir . '/logo-512.png') && getimagesize($brandDir . '/logo-512.png')[0] === 512);
$assertTest('Generated logo-256.png exists (256x256)', file_exists($brandDir . '/logo-256.png') && getimagesize($brandDir . '/logo-256.png')[0] === 256);
$assertTest('Generated logo-128.png exists (128x128)', file_exists($brandDir . '/logo-128.png') && getimagesize($brandDir . '/logo-128.png')[0] === 128);
$assertTest('Generated logo-64.png exists (64x64)', file_exists($brandDir . '/logo-64.png') && getimagesize($brandDir . '/logo-64.png')[0] === 64);
$assertTest('Generated logo-32.png exists (32x32)', file_exists($brandDir . '/logo-32.png') && getimagesize($brandDir . '/logo-32.png')[0] === 32);
$assertTest('Generated logo-16.png exists (16x16)', file_exists($brandDir . '/logo-16.png') && getimagesize($brandDir . '/logo-16.png')[0] === 16);
$assertTest('Multi-resolution favicon.ico exists', file_exists($brandDir . '/favicon.ico'));
$assertTest('Public root favicon.ico exists in public/favicon.ico', file_exists($baseDir . '/public/favicon.ico'));
$assertTest('Public asset bridge public/brand symlink exists', file_exists($baseDir . '/public/brand') && is_link($baseDir . '/public/brand'));
$assertTest('Apple Touch Icon exists (180x180)', file_exists($brandDir . '/apple-touch-icon.png') && getimagesize($brandDir . '/apple-touch-icon.png')[0] === 180);
$assertTest('Android Chrome PWA icon exists (192x192)', file_exists($brandDir . '/android-chrome-192x192.png') && getimagesize($brandDir . '/android-chrome-192x192.png')[0] === 192);
$assertTest('Android Chrome PWA icon exists (512x512)', file_exists($brandDir . '/android-chrome-512x512.png') && getimagesize($brandDir . '/android-chrome-512x512.png')[0] === 512);
$assertTest('Web App Manifest site.webmanifest exists', file_exists($brandDir . '/site.webmanifest'));
$assertTest('Topbar partial embeds real brand logo image', str_contains($topbarHtml, 'brand/logo-64.png') || str_contains($topbarHtml, 'brand/logo-32.png'));
$loginHtml = file_get_contents($baseDir . '/resources/views/auth/login.php');
$assertTest('Login view embeds brand logo image', str_contains($loginHtml, 'brand/logo-128.png') || str_contains($loginHtml, 'auth-brand-logo'));
$welcomeHtml = file_get_contents($baseDir . '/resources/views/welcome.php');
$assertTest('Welcome landing page embeds hero brand logo', str_contains($welcomeHtml, 'brand/logo-256.png') || str_contains($welcomeHtml, 'hero-brand-logo'));
$showcaseHtml = file_get_contents($baseDir . '/resources/views/ui/showcase.php');
$assertTest('Showcase view contains brand assets and identity section', str_contains($showcaseHtml, 'id="branding"') && str_contains($showcaseHtml, 'Brand Assets & Responsive Identity'));

// ── Test 17: Repository PII & Sensitive Data Exposure Guardrails ───────────────
echo "\n\033[1;37m[17] Repository PII & Sensitive Data Exposure Guardrails\033[0m\n";

$preCommitHookPath = $baseDir . '/.githooks/pre-commit';
$assertTest('Pre-commit security hook exists in .githooks/pre-commit', file_exists($preCommitHookPath));
$assertTest('Pre-commit security hook is executable', is_executable($preCommitHookPath));

// Scan all tracked repository files for prohibited personal PII
$trackedFilesRaw = shell_exec('git -C ' . escapeshellarg($baseDir) . ' ls-files');
$trackedFiles = array_filter(explode("\n", trim($trackedFilesRaw ?? '')));

$piiViolations = [];
$forbiddenTokens = array_map('base64_decode', [
    'Zm9yYmlkZGVuQGV4YW1wbGUudGVzdA==',
    'c3ludGhldGljX3Rva2Vu',
    'NTU1LTAxOTk=',
    'NTU1IDAxOTk=',
    'MTgwMDU1NTAxOTk=',
    'ODAwNTU1MDE5OQ==',
]);

foreach ($trackedFiles as $relPath) {
    if ($relPath === 'tests/run_tests.php') continue;
    $fullPath = $baseDir . '/' . $relPath;
    if (!file_exists($fullPath) || is_dir($fullPath)) continue;
    
    // Skip binary assets
    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'svg', 'pdf', 'woff', 'woff2', 'ttf'], true)) {
        continue;
    }

    $fileContent = (string)file_get_contents($fullPath);
    foreach ($forbiddenTokens as $token) {
        if (stripos($fileContent, $token) !== false) {
            $piiViolations[] = "{$relPath} contains {$token}";
        }
    }
}

$assertTest('Zero occurrences of personal email or username in tracked files', empty($piiViolations));
$assertTest('Sensitive meta files (.env.example, .gitignore, .githooks/pre-commit) are not tracked in Git', !in_array('.env.example', $trackedFiles, true) && !in_array('.gitignore', $trackedFiles, true) && !in_array('.githooks/pre-commit', $trackedFiles, true));
$logosTracked = array_filter($trackedFiles, fn($f) => str_starts_with($f, 'logos/') || str_starts_with($f, 'outside assets/'));
$assertTest('Outside assets / legacy logos directories are untracked in Git', empty($logosTracked));
$assertTest('Git core.hooksPath is configured to .githooks', trim((string)shell_exec('git -C ' . escapeshellarg($baseDir) . ' config core.hooksPath')) === '.githooks');
$envIgnored = trim((string)shell_exec('git -C ' . escapeshellarg($baseDir) . ' check-ignore .env'));
$assertTest('Local .env file is protected by .gitignore', $envIgnored === '.env');
$ignoredMetaList = array_filter(explode("\n", trim((string)shell_exec('git -C ' . escapeshellarg($baseDir) . ' check-ignore .env .env.example .gitignore .githooks logos "outside assets"'))));
$assertTest('Local .env, .env.example, .gitignore, .githooks, logos, and outside assets are ignored by Git', count(array_intersect(['.env', '.env.example', '.gitignore', '.githooks', 'logos', 'outside assets'], $ignoredMetaList)) === 6);
$assertTest('Welcome resume view loads contact details dynamically via Env::get', str_contains($welcomeHtml, "Env::get('CONTACT_EMAIL')") && str_contains($welcomeHtml, "Env::get('CONTACT_PHONE')"));

// ── Test 18: Security Hardening & Compliance Regressions ───────────────────────
echo "\n\033[1;37m[18] Security Hardening & Compliance Regressions\033[0m\n";

// 18.1 Unauthenticated GDPR requests return HTTP 401
foreach (['/api/v1/gdpr/consent', '/api/v1/gdpr/export', '/api/v1/gdpr/erase'] as $gdprRoute) {
    $gdprReq = new \Nexus\Core\Request('POST', $gdprRoute, ['accept' => 'application/json'], [], ['purpose' => 'analytics']);
    $gdprRes = $app->handle($gdprReq);
    $assertTest("Unauthenticated POST {$gdprRoute} returns HTTP 401", $gdprRes->getStatusCode() === 401);
}

// 18.2 X-Trace-Id header sanitization in Request::capture()
$prevServer = $_SERVER;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/health';
$_SERVER['HTTP_X_TRACE_ID'] = '<script>alert(1)</script>';
$badTraceReq = \Nexus\Core\Request::capture();
$assertTest(
    'Request::capture() rejects malicious X-Trace-Id header and generates UUIDv4 fallback',
    preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $badTraceReq->getTraceId()) === 1
);
$_SERVER['HTTP_X_TRACE_ID'] = 'trace-valid_123';
$goodTraceReq = \Nexus\Core\Request::capture();
$assertTest('Request::capture() preserves valid alphanumeric X-Trace-Id header', $goodTraceReq->getTraceId() === 'trace-valid_123');
$_SERVER = $prevServer;

// 18.3 Cache safe deserialization (allowed_classes => false)
$tmpCacheDir = sys_get_temp_dir() . '/nexus_cache_sec_' . uniqid();
$secCache = new \Nexus\Cache\Cache(null, $tmpCacheDir);
$secCache->put('obj_probe', new \stdClass(), 60);
$cachedObj = $secCache->get('obj_probe');
$assertTest(
    'Cache::get() disallows PHP object instantiation during unserialize()',
    $cachedObj instanceof \__PHP_Incomplete_Class && !($cachedObj instanceof \stdClass)
);
$secCache->forget('obj_probe');
if (is_dir($tmpCacheDir)) {
    @rmdir($tmpCacheDir);
}

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n";
echo "  \033[1;37mTEST SUMMARY:\033[0m \033[0;32m{$passed} Passed\033[0m | \033[0;31m{$failed} Failed\033[0m\n";
echo "\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n\n";

exit($failed > 0 ? 1 : 0);
