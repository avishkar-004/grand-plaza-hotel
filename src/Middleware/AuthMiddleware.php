<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request, Response $response): mixed
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $redirect = urlencode($request->uri());
                $response->redirect('/login?redirect=' . $redirect);
                // redirect() calls exit, but return for safety
                return $response;
            }

            // Check if account is locked
            if (isset($_SESSION['locked_until'])) {
                $lockedUntil = strtotime($_SESSION['locked_until']);
                if ($lockedUntil && $lockedUntil > time()) {
                    session_unset();
                    session_destroy();
                    $response->redirect('/login?error=account_locked');
                    return $response;
                }
                // Lock expired, clear it
                unset($_SESSION['locked_until']);
            }

            return null;
        } catch (\Throwable $e) {
            error_log("AuthMiddleware error: " . $e->getMessage());
            $response->redirect('/login');
            return $response;
        }
    }
}
