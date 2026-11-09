<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * Standardized Response Emitter
 *
 * Enforces uniform JSON response envelope: { "data": {}, "meta": {}, "message": "" }
 */
class Response
{
    private int $statusCode;
    private array $headers;
    private string $content;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function json(
        mixed $data = [],
        string|int $message = '',
        int|string $statusCode = 200,
        array $headers = [],
        ?string $traceId = null
    ): static {
        if (is_int($message)) {
            $temp = $statusCode;
            $statusCode = $message;
            $message = is_string($temp) ? $temp : '';
        }
        $meta = [
            'trace_id' => $traceId ?? Request::generateUuidV4(),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        $payload = [
            'data' => $data,
            'meta' => $meta,
            'message' => $message,
        ];

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $headers['X-Trace-Id'] = $meta['trace_id'];

        return new static($json, $statusCode, $headers);
    }

    public static function error(
        string $message,
        array $errors = [],
        int $statusCode = 400,
        array $headers = [],
        ?string $traceId = null
    ): static {
        $data = !empty($errors) ? ['errors' => $errors] : new \stdClass();
        return self::json($data, $message, $statusCode, $headers, $traceId);
    }

    public static function noContent(array $headers = []): static
    {
        return new static('', 204, $headers);
    }

    public static function redirect(string $url, int $statusCode = 302, array $headers = []): static
    {
        $headers['Location'] = $url;
        return new static('', $statusCode, $headers);
    }

    public static function html(string $html, int $statusCode = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new static($html, $statusCode, $headers);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $k => $v) {
            if (strcasecmp($k, $name) === 0) {
                return $v;
            }
        }
        return $default;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }
}

