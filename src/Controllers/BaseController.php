<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Application;

/**
 * Base Controller
 *
 * Parent class for all controllers
 *
 * @package App\Controllers
 */
abstract class BaseController
{
    protected Request $request;
    protected Response $response;
    protected Database $db;
    protected Application $app;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->app = Application::getInstance();
        $this->db = $this->app->db();
    }

    /**
     * Render a view
     */
    protected function view(string $view, array $data = []): Response
    {
        // Add global data available to all views
        $data['app'] = $this->app;
        $data['request'] = $this->request;
        $data['user'] = $this->getCurrentUser();
        $data['csrf_token'] = $_SESSION['csrf_token'] ?? '';
        $data['security_mode'] = $this->app->isSecureMode() ? 'secure' : 'vulnerable';

        return $this->response->view($view, $data);
    }

    /**
     * Return JSON response
     */
    protected function json($data, int $statusCode = 200): Response
    {
        return $this->response->json($data, $statusCode);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        $this->response->redirect($url, $statusCode);
    }

    /**
     * Redirect back
     */
    protected function back(): void
    {
        $this->response->back();
    }

    /**
     * Check if user is logged in
     */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user (stub - would load from database)
     */
    protected function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        // In a real app, this would fetch from database
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? 'user',
        ];
    }

    /**
     * Require login (redirect if not logged in)
     */
    protected function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login?redirect=' . urlencode($this->request->uri()));
            exit;
        }
    }

    /**
     * Require admin role
     */
    protected function requireAdmin(): void
    {
        $this->requireLogin();

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            $this->response->setStatusCode(403);
            $errorView = $this->app->basePath('views/errors/403.php');
            if (file_exists($errorView)) {
                $this->view('errors.403', ['title' => 'Access Denied'])->send();
            } else {
                $this->response->setContent('403 - Forbidden')->send();
            }
            exit;
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        if (!$this->app->config('app.security.csrf_enabled')) {
            return true; // CSRF disabled (vulnerable mode)
        }

        $token = $this->request->post('csrf_token');
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return hash_equals($sessionToken, $token ?? '');
    }

    /**
     * Regenerate CSRF token
     */
    protected function regenerateCsrfToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Flash message to session
     */
    protected function flash(string $key, $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }

    /**
     * Get flash message
     */
    protected function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    /**
     * Paginate an array of items
     */
    protected function paginate(array $items, int $perPage = 10, string $pageParam = 'page'): array
    {
        $currentPage = max(1, (int)($this->request->get($pageParam) ?? 1));
        $total = count($items);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
        ];
    }

    /**
     * Sanitize output (HTML encode)
     */
    protected function esc(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
