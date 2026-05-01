<?php

namespace App\Core;

use Dotenv\Dotenv;
use Exception;

/**
 * Application Bootstrap Class
 *
 * Initializes and runs the application
 *
 * @package App\Core
 */
class Application
{
    private static ?Application $instance = null;
    private array $config = [];
    private ?Database $db = null;
    private ?Router $router = null;
    private ?Request $request = null;
    private ?Response $response = null;
    private bool $secureMode = true;

    /**
     * Private constructor for singleton
     */
    private function __construct(string $basePath)
    {
        $this->config['basePath'] = $basePath;
        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->initializeCore();
        $this->setupErrorHandling();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(?string $basePath = null): self
    {
        if (self::$instance === null) {
            if ($basePath === null) {
                throw new \RuntimeException("Base path required for first initialization");
            }
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    /**
     * Load environment variables
     */
    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($this->config['basePath']);
        $dotenv->load();
    }

    /**
     * Load configuration files
     */
    private function loadConfiguration(): void
    {
        $configPath = $this->config['basePath'] . '/config';

        if (file_exists($configPath . '/app.php')) {
            $this->config['app'] = require $configPath . '/app.php';
        }

        if (file_exists($configPath . '/database.php')) {
            $this->config['database'] = require $configPath . '/database.php';
        }

        // Set security mode
        $this->secureMode = ($this->config['app']['security']['mode'] ?? 'secure') === 'secure';
    }

    /**
     * Initialize core components
     */
    private function initializeCore(): void
    {
        // Start session
        $this->startSession();

        // Initialize Request and Response
        $this->request = new Request($this->secureMode);
        $this->response = new Response($this->secureMode);

        // Initialize Router
        $this->router = new Router();

        // Initialize Database
        try {
            $dbConfig = $this->config['database'];
            $connection = $dbConfig['default'] ?? 'sqlite';
            $config = $dbConfig['connections'][$connection] ?? [];

            $this->db = Database::getInstance([
                'database' => $config,
                'security_mode' => $this->secureMode ? 'secure' : 'vulnerable'
            ]);
        } catch (Exception $e) {
            error_log("Database initialization failed: " . $e->getMessage());
            // Continue without database for error display
        }

        // Set security headers in secure mode
        if ($this->secureMode) {
            $this->response->setSecurityHeaders();
        }
    }

    /**
     * Start session with secure settings
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = $this->config['app']['session'] ?? [];

            // Strip port from HTTP_HOST for cookie domain (ports are invalid in cookie domains)
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $domain = strtok($host, ':') ?: '';

            session_set_cookie_params([
                'lifetime' => $config['lifetime'] ?? 120,
                'path' => '/',
                'domain' => $domain,
                'secure' => $config['secure'] ?? false,
                'httponly' => $config['http_only'] ?? true,
                'samesite' => $config['same_site'] ?? 'Strict',
            ]);

            session_start();

            // Generate CSRF token if not exists
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            // Session timeout check
            if (isset($_SESSION['LAST_ACTIVITY'])) {
                $timeout = $config['lifetime'] ?? 120;
                if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout * 60) {
                    session_unset();
                    session_destroy();
                    session_start();
                }
            }

            $_SESSION['LAST_ACTIVITY'] = time();
        }
    }

    /**
     * Setup error and exception handling
     */
    private function setupErrorHandling(): void
    {
        $debug = $this->config['app']['debug'] ?? false;

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');

            $logPath = $this->config['app']['paths']['logs'] ?? __DIR__ . '/../../storage/logs';
            if (!is_dir($logPath)) {
                @mkdir($logPath, 0755, true);
            }
            ini_set('error_log', $logPath . '/php_errors.log');
        }

        // Exception handler
        set_exception_handler(function ($exception) use ($debug) {
            error_log("Uncaught Exception: " . $exception->getMessage());

            http_response_code(500);

            if ($debug) {
                echo "<h1>Application Error</h1>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . ":" . $exception->getLine() . "</p>";
                echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            } else {
                $errorView = ($this->config['basePath'] ?? '') . '/views/errors/500.php';
                if (file_exists($errorView)) {
                    $content = '';
                    $title = 'Server Error';
                    $user = null;
                    $csrf_token = '';
                    $security_mode = 'secure';
                    ob_start();
                    include $errorView;
                    $content = ob_get_clean();
                    $layoutFile = ($this->config['basePath'] ?? '') . '/views/layouts/main.php';
                    if (file_exists($layoutFile)) {
                        include $layoutFile;
                    } else {
                        echo $content;
                    }
                } else {
                    echo "<h1>500 - Internal Server Error</h1><p>Sorry, something went wrong.</p>";
                }
            }
            exit(1);
        });
    }

    /**
     * Register routes
     */
    public function routes(callable $callback): self
    {
        $callback($this->router);
        return $this;
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $this->router->dispatch($this->request, $this->response);
        } catch (Exception $e) {
            error_log("Routing error: " . $e->getMessage());
            $this->response->setStatusCode(500)
                ->setContent('<h1>500 - Internal Server Error</h1>')
                ->send();
        }
    }

    /**
     * Get router instance
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get database instance
     */
    public function db(): ?Database
    {
        return $this->db;
    }

    /**
     * Get request instance
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Get response instance
     */
    public function response(): Response
    {
        return $this->response;
    }

    /**
     * Get configuration value
     */
    public function config(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Check if running in secure mode
     */
    public function isSecureMode(): bool
    {
        return $this->secureMode;
    }

    /**
     * Get base path
     */
    public function basePath(string $path = ''): string
    {
        return $this->config['basePath'] . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }
}
