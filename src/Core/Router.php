<?php
declare(strict_types=1);

namespace Nexus\Core;

use Closure;
use RuntimeException;

/**
 * Fast Regex-Compiled HTTP Router
 */
class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private ?Container $container = null;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function addRoute(array|string $methods, string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        $methods = (array)$methods;
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, (array)$group['middleware']);
            }
        }

        $uri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        if ($uri === '') {
            $uri = '/';
        }

        $allMiddleware = array_merge($groupMiddleware, $middleware);

        foreach ($methods as $method) {
            $this->routes[] = [
                'method' => strtoupper($method),
                'uri' => $uri,
                'handler' => $handler,
                'middleware' => $allMiddleware,
                'name' => $name,
            ];
        }

        return $this;
    }

    public function get(string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        return $this->addRoute('GET', $uri, $handler, $middleware, $name);
    }

    public function post(string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        return $this->addRoute('POST', $uri, $handler, $middleware, $name);
    }

    public function put(string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        return $this->addRoute('PUT', $uri, $handler, $middleware, $name);
    }

    public function patch(string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        return $this->addRoute('PATCH', $uri, $handler, $middleware, $name);
    }

    public function delete(string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        return $this->addRoute('DELETE', $uri, $handler, $middleware, $name);
    }

    public function options(string $uri, mixed $handler, array $middleware = [], ?string $name = null): static
    {
        return $this->addRoute('OPTIONS', $uri, $handler, $middleware, $name);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function dispatch(Request $request): Response
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->getPath();

        $allowedMethodsForUri = [];

        foreach ($this->routes as $route) {
            $pattern = $this->compilePattern($route['uri']);

            if (preg_match($pattern, $requestPath, $matches)) {
                if ($route['method'] !== $requestMethod) {
                    $allowedMethodsForUri[] = $route['method'];
                    continue;
                }

                // Extract named parameters
                $params = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);

                // Run route middleware pipeline
                return $this->runPipeline($request, $route['middleware'], function (Request $req) use ($route, $params) {
                    return $this->invokeHandler($route['handler'], $req, $params);
                });
            }
        }

        if (!empty($allowedMethodsForUri)) {
            $allowed = implode(', ', array_unique($allowedMethodsForUri));
            return Response::error("Method Not Allowed. Supported: {$allowed}", [], 405, [
                'Allow' => $allowed,
            ], $request->getTraceId());
        }

        return Response::error("Route Not Found: {$requestMethod} {$requestPath}", [], 404, [], $request->getTraceId());
    }

    private function compilePattern(string $uri): string
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^\}]+))?\}/', function ($matches) {
            $name = $matches[1];
            $regex = $matches[2] ?? '[^/]+';
            return "(?P<{$name}>{$regex})";
        }, $uri);

        return '#^' . $pattern . '$#u';
    }

    private function runPipeline(Request $request, array $middlewareList, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function ($next, $middleware) {
                return function (Request $req) use ($next, $middleware): Response {
                    $instance = is_string($middleware) ? $this->container->make($middleware) : $middleware;
                    if ($instance instanceof MiddlewareInterface) {
                        return $instance->handle($req, $next);
                    }
                    if (is_callable($instance)) {
                        return $instance($req, $next);
                    }
                    throw new RuntimeException("Invalid middleware supplied.");
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    private function invokeHandler(mixed $handler, Request $request, array $params): Response
    {
        if ($handler instanceof Closure) {
            $result = $handler($request, ...array_values($params));
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = is_string($class) ? $this->container->make($class) : $class;
            $result = $controller->$method($request, ...array_values($params));
        } elseif (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = $this->container->make($class);
            $result = $controller->$method($request, ...array_values($params));
        } else {
            throw new RuntimeException("Invalid route handler format.");
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result, 'Success', 200, [], $request->getTraceId());
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        return Response::noContent();
    }
}

