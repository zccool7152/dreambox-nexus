<?php
declare(strict_types=1);

namespace Nexus\Core;

use RuntimeException;
use InvalidArgumentException;

/**
 * Hardened Outbound HTTP Client
 *
 * Implements strict SSRF defense (blocking private/reserved network ranges and cloud metadata IPs),
 * TLS 1.2/1.3 enforcement, and connection timeout controls.
 */
class HttpClient
{
    private int $timeout;
    private int $connectTimeout;
    private bool $allowPrivateIps;

    public function __construct(int $timeout = 10, int $connectTimeout = 3, bool $allowPrivateIps = false)
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->allowPrivateIps = $allowPrivateIps;
    }

    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, $headers);
    }

    public function post(string $url, array|string $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $headers, $data);
    }

    public function put(string $url, array|string $data = [], array $headers = []): array
    {
        return $this->request('PUT', $url, $headers, $data);
    }

    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, $headers);
    }

    public function request(string $method, string $url, array $headers = [], array|string $body = ''): array
    {
        $this->assertSsrfSafeUrl($url);

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException("Failed to initialize cURL.");
        }

        $formattedHeaders = [];
        $hasContentType = false;
        foreach ($headers as $key => $val) {
            $h = is_int($key) ? $val : "{$key}: {$val}";
            $formattedHeaders[] = $h;
            if (stripos(trim($h), 'Content-Type:') === 0) {
                $hasContentType = true;
            }
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION => false, // Prevents open redirect SSRF bypasses
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            if (is_array($body)) {
                $isForm = false;
                foreach ($formattedHeaders as $h) {
                    if (stripos($h, 'application/x-www-form-urlencoded') !== false) {
                        $isForm = true;
                        break;
                    }
                }
                if ($isForm) {
                    $options[CURLOPT_POSTFIELDS] = http_build_query($body);
                    if (!$hasContentType) {
                        $formattedHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
                    }
                } else {
                    $options[CURLOPT_POSTFIELDS] = json_encode($body);
                    if (!$hasContentType) {
                        $formattedHeaders[] = 'Content-Type: application/json';
                    }
                }
            } else {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
        }

        $options[CURLOPT_HTTPHEADER] = $formattedHeaders;

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($errno !== 0) {
            throw new RuntimeException("HTTP request failed: [{$errno}] {$error}");
        }

        return [
            'status' => $statusCode,
            'body' => (string)$response,
        ];
    }

    public function assertSsrfSafeUrl(string $url): void
    {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['host']) || !isset($parsed['scheme'])) {
            throw new InvalidArgumentException("Malformed URL provided: '{$url}'");
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("Unsupported protocol scheme: '{$scheme}'. Only http and https are allowed.");
        }

        if ($this->allowPrivateIps) {
            return;
        }

        $host = $parsed['host'];
        $ips = gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            throw new RuntimeException("Unable to resolve DNS for host: '{$host}'");
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                throw new InvalidArgumentException("SSRF Protection Alert: Destination IP '{$ip}' resolves to a private or reserved network range.");
            }
        }
    }

    public function isPrivateOrReservedIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}

