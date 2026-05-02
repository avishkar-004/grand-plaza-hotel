<?php

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

class LoggingMiddleware
{
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirm',
        'current_password',
        'new_password',
        'csrf_token',
        'credit_card',
        'card_number',
        'cvv',
        'cvc',
        'ssn',
        'token',
    ];

    private const ATTACK_PATTERNS = [
        'UNION',
        'SELECT',
        'INSERT',
        'UPDATE',
        'DELETE',
        'DROP',
        '<script',
        'javascript:',
        'onerror',
        'onload',
        'eval(',
        '../',
        '..\\',
        '/etc/passwd',
        'cmd=',
        'exec(',
        'system(',
    ];

    public function handle(Request $request, Response $response): mixed
    {
        try {
            $this->logRequest($request);
        } catch (\Throwable $e) {
            error_log("LoggingMiddleware error: " . $e->getMessage());
        }

        // Never block — always continue
        return null;
    }

    private function logRequest(Request $request): void
    {
        $db = Application::getInstance()->db();
        if ($db === null) {
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $action = $request->method() . ' ' . $request->uri();
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Sanitize POST data for logging
        $requestData = null;
        if ($request->isPost()) {
            $postData = $_POST;
            foreach (self::SENSITIVE_FIELDS as $field) {
                if (isset($postData[$field])) {
                    $postData[$field] = '[REDACTED]';
                }
            }
            $requestData = json_encode($postData, JSON_UNESCAPED_SLASHES);
        }

        // Detect potential attack patterns
        $isSecurityEvent = false;
        $severity = 'info';
        $fullUri = $request->uri() . '?' . ($_SERVER['QUERY_STRING'] ?? '');

        $checkTargets = [$fullUri, $userAgent, $requestData ?? ''];
        $haystack = strtoupper(implode(' ', $checkTargets));

        foreach (self::ATTACK_PATTERNS as $pattern) {
            if (str_contains($haystack, strtoupper($pattern))) {
                $isSecurityEvent = true;
                $severity = 'warning';
                break;
            }
        }

        $db->execute(
            "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, request_data, severity, is_security_event, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$db->now()})",
            [
                $userId,
                $action,
                'HTTP request logged',
                $ip,
                $userAgent,
                $requestData,
                $severity,
                $isSecurityEvent ? 1 : 0,
            ]
        );
    }
}
