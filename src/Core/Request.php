<?php

namespace App\Core;

/**
 * HTTP Request Handler
 *
 * Handles incoming HTTP requests with security considerations
 *
 * @package App\Core
 */
class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private array $cookies;
    private string $method;
    private string $uri;
    private array $headers;
    private bool $secureMode;

    public function __construct(bool $secureMode = true)
    {
        $this->secureMode = $secureMode;
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->files = $_FILES ?? [];
        $this->server = $_SERVER ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->parseUri();
        $this->headers = $this->parseHeaders();
    }

    /**
     * Get request method
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Parse URI from server variables
     */
    private function parseUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Parse request headers
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get GET parameter
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->get;
        }

        $value = $this->get[$key] ?? $default;

        // VULNERABLE MODE: Return raw value
        if (!$this->secureMode) {
            return $value;
        }

        // SECURE MODE: Sanitize input
        return $this->sanitize($value);
    }

    /**
     * Get POST parameter
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }

        $value = $this->post[$key] ?? $default;

        // VULNERABLE MODE: Return raw value
        if (!$this->secureMode) {
            return $value;
        }

        // SECURE MODE: Sanitize input
        return $this->sanitize($value);
    }

    /**
     * Get input from GET or POST
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post($key) ?? $this->get($key, $default);
    }

    /**
     * Get all inputs (GET + POST)
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * Check if request has key
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->get[$key]);
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get header value
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get cookie value
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        // Check for proxy headers
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $this->server['HTTP_X_FORWARDED_FOR'])[0];
        }

        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return !empty($this->server['HTTP_X_REQUESTED_WITH']) &&
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
               (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Sanitize input value (for secure mode)
     *
     * @param mixed $value
     * @return mixed
     */
    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }

        if (is_string($value)) {
            // Basic sanitization - trim whitespace
            $value = trim($value);

            // Remove null bytes
            $value = str_replace(chr(0), '', $value);

            return $value;
        }

        return $value;
    }

    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(string $sessionToken): bool
    {
        $token = $this->post('csrf_token') ?? $this->get('csrf_token');
        return hash_equals($sessionToken, $token ?? '');
    }

    /**
     * Get request path segments
     */
    public function segments(): array
    {
        return array_filter(explode('/', $this->uri));
    }

    /**
     * Get specific path segment
     */
    public function segment(int $index, mixed $default = null): mixed
    {
        $segments = $this->segments();
        return $segments[$index - 1] ?? $default;
    }
}
