<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Autoloader.php';
$autoloader = new \Nexus\Core\Autoloader(dirname(__DIR__));
$autoloader->addNamespace('Nexus', dirname(__DIR__) . '/src');
$autoloader->register();

\Nexus\Core\Env::load(dirname(__DIR__) . '/.env');
$app = new \Nexus\Core\App(dirname(__DIR__));

echo "════════════════════════════════════════════════════════════════\n";
echo "  LIVE AUTHENTICATION LIFECYCLE SIMULATION                      \n";
echo "════════════════════════════════════════════════════════════════\n";

$passCount = 0;
$failCount = 0;

$it = function (string $desc, bool $cond) use (&$passCount, &$failCount) {
    if ($cond) {
        $passCount++;
        echo "  ✔ {$desc}\n";
    } else {
        $failCount++;
        echo "  ✖ FAIL: {$desc}\n";
    }
};

// 1. GET /auth/login
$req1 = new \Nexus\Core\Request('GET', '/auth/login', ['accept' => 'text/html'], [], [], '', '127.0.0.1', null, '/auth/login');
$res1 = $app->handle($req1);
$it("GET /auth/login returns HTTP 200", $res1->getStatusCode() === 200);
$body1 = $res1->getBody();
$it("Login view contains script with CSP nonce", str_contains($body1, '<script nonce="') && !str_contains($body1, '<script>'));
$it("Login form contains method=POST", str_contains($body1, 'method="POST"'));
$it("Login form contains name=email and name=password", str_contains($body1, 'name="email"') && str_contains($body1, 'name="password"'));
$it("Zero credential leaks in HTML markup", !str_contains($body1, '123qweasd') && !str_contains($body1, 'NexusAdmin@2026!'));

// 2. POST /auth/login (Simulate browser form POST)
$req2 = new \Nexus\Core\Request(
    'POST',
    '/auth/login',
    ['accept' => 'text/html', 'content-type' => 'application/x-www-form-urlencoded'],
    [],
    ['email' => 'admin@nexus.local', 'password' => '123qweasd'],
    'email=admin%40nexus.local&password=123qweasd',
    '127.0.0.1',
    null,
    '/auth/login'
);
$res2 = $app->handle($req2);
$it("POST /auth/login via form redirects (HTTP 302)", $res2->getStatusCode() === 302);
$it("Redirect target is /auth/2fa/setup/", str_contains($res2->getHeader('location') ?? '', '/auth/2fa/setup/'));

// 3. POST /auth/login (Simulate browser fetch AJAX)
$req3 = new \Nexus\Core\Request(
    'POST',
    '/auth/login',
    ['accept' => 'application/json', 'content-type' => 'application/json'],
    [],
    ['email' => 'admin@nexus.local', 'password' => '123qweasd'],
    json_encode(['email' => 'admin@nexus.local', 'password' => '123qweasd']),
    '127.0.0.1',
    null,
    '/auth/login'
);
$res3 = $app->handle($req3);
$it("POST /auth/login via AJAX returns HTTP 200 JSON", $res3->getStatusCode() === 200);
$json3 = json_decode($res3->getBody(), true);
$it("AJAX response indicates 2FA setup is required", !empty($json3['data']['requires_2fa_setup']));

// 4. GET /auth/2fa/setup
$req4 = new \Nexus\Core\Request('GET', '/auth/2fa/setup', ['accept' => 'text/html'], [], [], '', '127.0.0.1', null, '/auth/2fa/setup');
$res4 = $app->handle($req4);
$it("GET /auth/2fa/setup returns HTTP 200", $res4->getStatusCode() === 200);
$body4 = $res4->getBody();
$it("Setup 2FA view contains script with CSP nonce", str_contains($body4, '<script nonce="') && !str_contains($body4, '<script>'));
$it("Setup 2FA contains pure SVG QR code", str_contains($body4, '<svg') && str_contains($body4, 'xmlns="http://www.w3.org/2000/svg"'));

// 5. Complete 2FA Setup
$db = $app->getContainer()->make(\Nexus\Database\Database::class);
$session = new \Nexus\Security\Session($db);
$session->start();
$secret = $session->get('pending_totp_secret');
$it("Session maintains pending TOTP secret", !empty($secret));

$mfa = new \Nexus\Auth\Mfa();
$validCode = $mfa->getOtp($secret);

$req5 = new \Nexus\Core\Request(
    'POST',
    '/auth/2fa/setup',
    ['accept' => 'application/json', 'content-type' => 'application/json'],
    [],
    ['code' => $validCode],
    json_encode(['code' => $validCode]),
    '127.0.0.1',
    null,
    '/auth/2fa/setup'
);
$res5 = $app->handle($req5);
$it("POST /auth/2fa/setup verifies valid code", $res5->getStatusCode() === 200);
$json5 = json_decode($res5->getBody(), true);
$it("2FA setup returns 8 emergency recovery codes", count($json5['data']['recovery_codes'] ?? []) === 8);
$it("2FA setup mandates password rotation", !empty($json5['data']['must_change_password']));
$it("2FA setup redirects to /auth/password/change/", str_contains($json5['data']['redirect'] ?? '', '/auth/password/change/'));

// 6. GET /auth/password/change
$req6 = new \Nexus\Core\Request('GET', '/auth/password/change', ['accept' => 'text/html'], [], [], '', '127.0.0.1', null, '/auth/password/change');
$res6 = $app->handle($req6);
$it("GET /auth/password/change returns HTTP 200", $res6->getStatusCode() === 200);
$body6 = $res6->getBody();
$it("Password rotation view contains script with CSP nonce", str_contains($body6, '<script nonce="') && !str_contains($body6, '<script>'));

// 7. POST /auth/password/change (Mismatched passwords rejected)
$req7a = new \Nexus\Core\Request(
    'POST',
    '/auth/password/change',
    ['accept' => 'application/json', 'content-type' => 'application/json'],
    [],
    ['current_password' => '123qweasd', 'new_password' => 'Pass12345!', 'confirm_password' => 'PassMismatch!'],
    json_encode(['current_password' => '123qweasd', 'new_password' => 'Pass12345!', 'confirm_password' => 'PassMismatch!']),
    '127.0.0.1',
    null,
    '/auth/password/change'
);
$res7a = $app->handle($req7a);
$it("Password change rejects mismatched passwords", $res7a->getStatusCode() === 422);

// 8. POST /auth/password/change (Identical to temporary rejected)
$req7b = new \Nexus\Core\Request(
    'POST',
    '/auth/password/change',
    ['accept' => 'application/json', 'content-type' => 'application/json'],
    [],
    ['current_password' => '123qweasd', 'new_password' => '123qweasd', 'confirm_password' => '123qweasd'],
    json_encode(['current_password' => '123qweasd', 'new_password' => '123qweasd', 'confirm_password' => '123qweasd']),
    '127.0.0.1',
    null,
    '/auth/password/change'
);
$res7b = $app->handle($req7b);
$it("Password change rejects identical password", $res7b->getStatusCode() === 422);

// 9. POST /auth/password/change (Valid rotation)
$req8 = new \Nexus\Core\Request(
    'POST',
    '/auth/password/change',
    ['accept' => 'application/json', 'content-type' => 'application/json'],
    [],
    ['current_password' => '123qweasd', 'new_password' => 'CyberNoir2026#Secure', 'confirm_password' => 'CyberNoir2026#Secure'],
    json_encode(['current_password' => '123qweasd', 'new_password' => 'CyberNoir2026#Secure', 'confirm_password' => 'CyberNoir2026#Secure']),
    '127.0.0.1',
    null,
    '/auth/password/change'
);
$res8 = $app->handle($req8);
$it("Valid password change returns HTTP 200", $res8->getStatusCode() === 200);

// Reset user back to temporary password 123qweasd for user testing
$userRepo = new \Nexus\Auth\UserRepository($db, new \Nexus\Crypto\Hasher());
$userRepo->update('usr_admin_tim', [
    'password_hash' => (new \Nexus\Crypto\Hasher())->hash('123qweasd'),
    'must_change_password' => 1,
    'is_2fa_enabled' => 0,
    'totp_secret' => null,
    'recovery_codes' => [],
]);
echo "  ℹ Reset admin@nexus.local back to temporary password (123qweasd, must_change_password=true, is_2fa_enabled=0) for user browser testing\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "  RESULT: {$passCount} Passed | {$failCount} Failed\n";
echo "════════════════════════════════════════════════════════════════\n";

if ($failCount > 0) {
    exit(1);
}
