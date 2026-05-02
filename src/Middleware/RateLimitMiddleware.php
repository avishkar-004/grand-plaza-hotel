<?php

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

class RateLimitMiddleware
{
    private int $maxRequests;
    private int $window;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->maxRequests = (int) ($app->config('app.rate_limit.max_requests') ?? 100);
        $this->window = (int) ($app->config('app.rate_limit.window') ?? 60);
    }

    public function handle(Request $request, Response $response): mixed
    {
        try {
            $ip = $request->ip();
            $now = time();

            // Initialize or reset rate limit tracking
            if (
                !isset($_SESSION['rate_limit']) ||
                $_SESSION['rate_limit']['ip'] !== $ip ||
                ($now - $_SESSION['rate_limit']['window_start']) >= $this->window
            ) {
                $_SESSION['rate_limit'] = [
                    'ip' => $ip,
                    'count' => 1,
                    'window_start' => $now,
                ];
                return null;
            }

            $_SESSION['rate_limit']['count']++;

            if ($_SESSION['rate_limit']['count'] > $this->maxRequests) {
                $this->logViolation($request);

                $retryAfter = $this->window - ($now - $_SESSION['rate_limit']['window_start']);
                $response->setStatusCode(429)
                    ->setHeader('Retry-After', (string) max(1, $retryAfter))
                    ->setContent('Too Many Requests. Please try again later.');
                $response->send();
                return $response;
            }

            return null;
        } catch (\Throwable $e) {
            error_log("RateLimitMiddleware error: " . $e->getMessage());
            return null;
        }
    }

    private function logViolation(Request $request): void
    {
        try {
            $db = Application::getInstance()->db();
            if ($db === null) {
                return;
            }

            $userId = $_SESSION['user_id'] ?? null;
            $ip = $request->ip();
            $description = sprintf(
                'Rate limit exceeded: %d requests in %ds from IP %s',
                $_SESSION['rate_limit']['count'],
                $this->window,
                $ip
            );

            $db->execute(
                "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, severity, is_security_event, created_at)
                 VALUES (?, 'rate_limit_exceeded', ?, ?, ?, 'warning', 1, {$db->now()})",
                [$userId, $description, $ip, $request->userAgent()]
            );
        } catch (\Throwable $e) {
            error_log("RateLimitMiddleware log error: " . $e->getMessage());
        }
    }
}
