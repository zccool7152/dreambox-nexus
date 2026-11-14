<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * Immutable Sanitized HTTP Request Representation
 */
class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $headers;
    private array $queryParams;
    private array $body;
    private string $rawBody;
    private string $clientIp;
    private string $traceId;

    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        array $queryParams = [],
        array $body = [],
        string $rawBody = '',
        ?string $clientIp = null,
        ?string $traceId = null,
        ?string $path = null
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->path = $path ?? self::normalizePath($uri);
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->queryParams = $queryParams;
        $this->body = $body;
        $this->rawBody = $rawBody;
        $this->clientIp = $clientIp ?? '127.0.0.1';
        $this->traceId = $traceId ?? self::generateUuidV4();
    }

    public static function normalizePath(string $uri, string $scriptName = ''): string
    {
        // 1. Check if route is specified via $_GET['r'] or query string (?r=/path)
        if (!empty($_GET['r'])) {
            $r = '/' . trim((string)$_GET['r'], '/');
            return $r === '' ? '/' : $r;
        }

        $parsedQuery = [];
        $queryString = parse_url($uri, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $parsedQuery);
            if (!empty($parsedQuery['r'])) {
                $r = '/' . trim((string)$parsedQuery['r'], '/');
                return $r === '' ? '/' : $r;
            }
        }

        // 2. Decode raw path
        $rawPath = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');

        // 3. Strip SCRIPT_NAME if matching prefix
        $cleanScriptName = str_replace('\\', '/', $scriptName);
        if ($cleanScriptName !== '' && str_starts_with($rawPath, $cleanScriptName)) {
            $rawPath = substr($rawPath, strlen($cleanScriptName));
        } else {
            // 4. Strip script directory if matching prefix
            $scriptDir = rtrim(str_replace('\\', '/', dirname($cleanScriptName)), '/');
            if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($rawPath, $scriptDir)) {
                $rawPath = substr($rawPath, strlen($scriptDir));
            }
        }

        $trimmed = trim($rawPath, '/');
        $normalized = $trimmed === '' ? '/' : '/' . $trimmed;

        if ($normalized === '/index.php' || $normalized === '/index.html') {
            return '/';
        }

        return $normalized;
    }

    public static function getAppBase(): string
    {
        $baseDir = dirname(__DIR__, 2);
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html';
        $realDocRoot = realpath($docRoot) ?: $docRoot;
        $realBaseDir = realpath($baseDir) ?: $baseDir;

        if ($realBaseDir !== false && $realDocRoot !== false && str_starts_with($realBaseDir, $realDocRoot)) {
            $rel = substr($realBaseDir, strlen($realDocRoot));
            return rtrim(str_replace('\\', '/', $rel), '/');
        }

        return '';
    }

    public static function capture(?array $trustedProxies = null): static
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $path = self::normalizePath($uri, $scriptName);
        $queryParams = $_GET;
        $rawBody = (string)file_get_contents('php://input');

        // Parse headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = (string)$value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headerName = strtolower(str_replace('_', '-', $key));
                $headers[$headerName] = (string)$value;
            }
        }

        // Parse JSON body if application/json
        $contentType = $headers['content-type'] ?? '';
        $body = $_POST;
        if (str_contains(strtolower($contentType), 'application/json') && trim($rawBody) !== '') {
            try {
                $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            } catch (\JsonException) {
                $body = [];
            }
        }

        // Secure Client IP Resolution via Trusted Proxies
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $trustedList = $trustedProxies ?? ['127.0.0.1', '::1'];
        $clientIp = $remoteAddr;

        if (in_array($remoteAddr, $trustedList, true)) {
            if (!empty($headers['x-forwarded-for'])) {
                $ips = array_map('trim', explode(',', $headers['x-forwarded-for']));
                if (!empty($ips[0]) && filter_var($ips[0], FILTER_VALIDATE_IP)) {
                    $clientIp = $ips[0];
                }
            } elseif (!empty($headers['x-real-ip']) && filter_var($headers['x-real-ip'], FILTER_VALIDATE_IP)) {
                $clientIp = $headers['x-real-ip'];
            }
        }

        // Trace ID header pass-through (validated) or generation
        $rawTrace = $headers['x-request-id'] ?? $headers['x-trace-id'] ?? null;
        $traceId = ($rawTrace !== null && preg_match('/^[a-zA-Z0-9\-_]{1,64}$/', (string)$rawTrace) === 1)
            ? (string)$rawTrace
            : self::generateUuidV4();

        return new static($method, $uri, $headers, $queryParams, $body, $rawBody, $clientIp, $traceId, $path);
    }

    public static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $val = $this->get($key);
        return is_numeric($val) ? (int)$val : $default;
    }

    public function getString(string $key, string $default = ''): string
    {
        $val = $this->get($key);
        return is_string($val) ? trim($val) : (is_scalar($val) ? (string)$val : $default);
    }

    public function getArray(string $key, array $default = []): array
    {
        $val = $this->get($key);
        return is_array($val) ? $val : $default;
    }

    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('authorization');
        if ($auth !== null && str_starts_with($auth, 'Bearer ')) {
            return trim(substr($auth, 7));
        }
        return null;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function wantsJson(): bool
    {
        $accept = (string)$this->getHeader('accept', '');
        return str_contains($accept, '/json') || str_contains($accept, '+json') || $this->getString('format') === 'json';
    }

    public function getUserAgent(): string
    {
        return (string)$this->getHeader('user-agent', 'Unknown');
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->queryParams) || array_key_exists($key, $this->body);
    }
}

