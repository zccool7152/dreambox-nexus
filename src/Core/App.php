<?php
declare(strict_types=1);

namespace Nexus\Core;

use Nexus\Database\Database;
use Nexus\Security\Cors;
use Nexus\Security\Headers;
use Nexus\Security\RateLimiter;
use RuntimeException;

/**
 * Nexus Core Micro-Kernel
 *
 * Bootstraps environment, error containment, dependency container,
 * global security pipelines, and request-response lifecycle.
 */
class App
{
    private string $basePath;
    private Container $container;
    private Router $router;
    private array $globalMiddleware = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->container = Container::getInstance();
        $this->container->instance(self::class, $this);

        $this->bootstrap();
    }

    private function bootstrap(): void
    {
        // 1. Load Environment Configuration
        $envFile = $this->basePath . '/.env';
        if (file_exists($envFile)) {
            Env::load($envFile);
        }

        $appKey = (string)Env::get('APP_KEY', '');
        if (trim($appKey) === '' || $appKey === 'default_secret' || strlen($appKey) < 16) {
            throw new RuntimeException('Insecure or missing APP_KEY configuration. A high-entropy APP_KEY of at least 16 characters is required.');
        }

        // 2. Register Logger & Error Containment
        $logPath = $this->basePath . '/storage/logs/nexus.log';
        $logger = new Logger($logPath);
        $this->container->instance(Logger::class, $logger);

        $debug = (bool)Env::get('APP_DEBUG', false);
        ErrorHandler::register($logger, $debug);

        // 3. Register Database Layer
        $dbConfig = require $this->basePath . '/config/database.php';
        $defaultConn = $dbConfig['default'] ?? 'mysql';
        $connConfig = $dbConfig['connections'][$defaultConn] ?? [];
        $db = new Database($connConfig);
        $this->container->instance(Database::class, $db);

        // 4. Register Router & Routes
        $this->router = new Router($this->container);
        $this->container->instance(Router::class, $this->router);

        $routesFile = $this->basePath . '/config/routes.php';
        if (file_exists($routesFile)) {
            $router = $this->router;
            $app = $this;
            require $routesFile;
        }

        // 5. Register Translator
        $translator = new Translator($this->basePath . '/lang', (string)Env::get('APP_LOCALE', 'en'));
        $this->container->instance(Translator::class, $translator);

        // 6. Register Default Global Defense-in-Depth Middleware Stack
        $this->globalMiddleware = [
            new Headers(),
            new Cors(),
            new RateLimiter($db, 120, 60),
        ];
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function use(mixed $middleware): static
    {
        if (is_object($middleware)) {
            $class = get_class($middleware);
            foreach ($this->globalMiddleware as $idx => $existing) {
                if (is_object($existing) && get_class($existing) === $class) {
                    $this->globalMiddleware[$idx] = $middleware;
                    return $this;
                }
            }
        }
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function handle(Request $request): Response
    {
        // Maintenance Mode Check
        $downFile = $this->basePath . '/storage/framework/down';
        if (file_exists($downFile)) {
            $downData = json_decode((string)file_get_contents($downFile), true) ?: [];
            $secret = $downData['secret'] ?? null;
            $cookieBypass = $_COOKIE['nexus_maintenance_bypass'] ?? null;

            if ($secret === null || $cookieBypass !== $secret) {
                return Response::error(
                    $downData['message'] ?? 'Service Unavailable for Maintenance.',
                    ['retry_after' => 60],
                    503,
                    ['Retry-After' => '60'],
                    $request->getTraceId()
                );
            }
        }

        // Run Global Middleware -> Router
        $destination = fn(Request $req): Response => $this->router->dispatch($req);

        $pipeline = array_reduce(
            array_reverse($this->globalMiddleware),
            function ($next, $middleware) {
                return function (Request $req) use ($next, $middleware): Response {
                    $instance = is_string($middleware) ? $this->container->make($middleware) : $middleware;
                    if ($instance instanceof MiddlewareInterface) {
                        return $instance->handle($req, $next);
                    }
                    if (is_callable($instance)) {
                        return $instance($req, $next);
                    }
                    throw new RuntimeException("Invalid global middleware provided.");
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    public function run(?Request $request = null): void
    {
        $trustedProxies = explode(',', (string)Env::get('TRUSTED_PROXIES', '127.0.0.1,::1'));
        $req = $request ?? Request::capture($trustedProxies);
        $this->container->instance(Request::class, $req);

        $response = $this->handle($req);
        $response->send();
    }
}
