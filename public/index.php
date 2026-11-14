<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Unified HTTP Front Controller
 *
 * Bootstraps the custom security-first framework, registers global defense-in-depth
 * middleware, and dispatches requests.
 */

$baseDir = dirname(__DIR__);

// 1. Register Native PSR-4 Autoloader
require_once $baseDir . '/src/Core/Autoloader.php';
$autoloader = new \Nexus\Core\Autoloader($baseDir);
$autoloader->addNamespace('Nexus', $baseDir . '/src');
$autoloader->addNamespace('Database\\Seeders', $baseDir . '/database/seeders');
$autoloader->register();

// 2. Initialize Micro-Kernel
$app = new \Nexus\Core\App($baseDir);

// 3. Attach Global Defense-in-Depth Middleware Stack
$trustedProxies = explode(',', (string)\Nexus\Core\Env::get('TRUSTED_PROXIES', '127.0.0.1,::1'));
$app->use(new \Nexus\Security\Headers());
$app->use(new \Nexus\Security\Cors());
$app->use(new \Nexus\Security\RateLimiter($app->getContainer()->make(\Nexus\Database\Database::class), 120, 60));

// 4. Capture Ingress Request
$request = \Nexus\Core\Request::capture($trustedProxies);

// 5. Run Kernel and Dispatch Route
$response = $app->handle($request);
$response->send();

