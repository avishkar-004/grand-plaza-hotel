<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Hotel Management System',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',

    'timezone' => 'UTC',
    'locale' => 'en',

    'security' => [
        'mode' => $_ENV['SECURITY_MODE'] ?? 'secure',
        'csrf_enabled' => filter_var($_ENV['CSRF_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'force_https' => filter_var($_ENV['FORCE_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'password_algo' => $_ENV['PASSWORD_ALGO'] ?? 'BCRYPT',
        'password_cost' => (int)($_ENV['PASSWORD_COST'] ?? 12),
    ],

    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'driver' => $_ENV['SESSION_DRIVER'] ?? 'file',
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'http_only' => true,
        'same_site' => 'Strict',
    ],

    'rate_limit' => [
        'enabled' => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'max_requests' => (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 100),
        'window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
    ],

    'upload' => [
        'max_size' => (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 5242880), // 5MB
        'allowed_extensions' => explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,pdf'),
        'path' => __DIR__ . '/../storage/uploads',
    ],

    'paths' => [
        'root' => dirname(__DIR__),
        'public' => dirname(__DIR__) . '/public',
        'storage' => dirname(__DIR__) . '/storage',
        'logs' => dirname(__DIR__) . '/storage/logs',
        'cache' => dirname(__DIR__) . '/storage/cache',
        'views' => dirname(__DIR__) . '/views',
    ],
];
