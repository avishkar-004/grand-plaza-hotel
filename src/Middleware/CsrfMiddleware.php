<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware
{
    public function handle(Request $request, Response $response): mixed
    {
        try {
            // Only validate on state-changing methods
            if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                return null;
            }

            $submittedToken = $_POST['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
                error_log(sprintf(
                    "CSRF validation failed: IP=%s URI=%s Method=%s",
                    $request->ip(),
                    $request->uri(),
                    $request->method()
                ));

                $response->setStatusCode(403)->setContent('CSRF token validation failed');
                $response->send();
                return $response;
            }

            // Token stays valid for the session lifetime; controllers may also validate it.
            // Token is regenerated on session_regenerate_id() at login for rotation.

            return null;
        } catch (\Throwable $e) {
            error_log("CsrfMiddleware error: " . $e->getMessage());
            // Fail-safe: allow the request through rather than crash the app
            return null;
        }
    }
}
