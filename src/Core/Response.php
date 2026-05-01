<?php

namespace App\Core;

/**
 * HTTP Response Handler
 *
 * Handles HTTP responses with security headers support
 *
 * @package App\Core
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $content = '';
    private bool $secureMode;

    private const HTTP_STATUS_TEXTS = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public function __construct(bool $secureMode = true)
    {
        $this->secureMode = $secureMode;
    }

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Set security headers (for secure mode)
     */
    public function setSecurityHeaders(): self
    {
        if (!$this->secureMode) {
            return $this;
        }

        $this->setHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;",
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ]);

        return $this;
    }

    /**
     * Set content
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Send response
     */
    public function send(): void
    {
        // Send status
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
    }

    /**
     * Respond with JSON
     */
    public function json(mixed $data, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $this;
    }

    /**
     * Respond with view
     */
    public function view(string $view, array $data = []): self
    {
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->setContent($this->renderView($view, $data));
        return $this;
    }

    /**
     * Redirect to URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->send();
        exit;
    }

    /**
     * Redirect back
     */
    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    /**
     * Download file
     */
    public function download(string $filepath, ?string $filename = null): void
    {
        if (!file_exists($filepath)) {
            $this->setStatusCode(404)->setContent('File not found')->send();
            return;
        }

        $filename = $filename ?? basename($filepath);

        $this->setHeaders([
            'Content-Type' => mime_content_type($filepath),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($filepath),
        ]);

        $this->send();
        readfile($filepath);
        exit;
    }

    /**
     * Render view file
     */
    private function renderView(string $view, array $data = []): string
    {
        $viewPath = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: $view");
        }

        // Extract data to make variables available in view
        extract($data, EXTR_SKIP);

        // Render view content
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Wrap in layout if not explicitly disabled
        if (!isset($data['no_layout']) || !$data['no_layout']) {
            $layout = $data['layout'] ?? 'layouts.main';
            $layoutPath = __DIR__ . '/../../views/' . str_replace('.', '/', $layout) . '.php';

            if (file_exists($layoutPath)) {
                ob_start();
                require $layoutPath;
                return ob_get_clean();
            }
        }

        return $content;
    }

    /**
     * Get status text
     */
    private function getStatusText(int $code): string
    {
        return self::HTTP_STATUS_TEXTS[$code] ?? 'Unknown';
    }
}
