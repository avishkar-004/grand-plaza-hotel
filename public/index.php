<?php
/**
 * Front Controller
 *
 * Entry point for all requests
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;

try {
    $app = Application::getInstance(__DIR__ . '/..');

    $app->routes(function($router) {

        // === Public Routes ===
        $router->get('/', 'HomeController@index');
        $router->get('/search', 'HomeController@search');
        $router->get('/about', 'HomeController@about');
        $router->get('/contact', 'HomeController@contact');
        $router->post('/contact', 'HomeController@submitContact');

        // === Auth Routes ===
        $router->get('/login', 'AuthController@loginForm');
        $router->post('/login', 'AuthController@login');
        $router->get('/register', 'AuthController@registerForm');
        $router->post('/register', 'AuthController@register');
        $router->get('/logout', 'AuthController@logout');
        $router->post('/logout', 'AuthController@logout');
        $router->get('/forgot-password', 'AuthController@forgotPasswordForm');
        $router->post('/forgot-password', 'AuthController@forgotPassword');
        $router->get('/reset-password', 'AuthController@resetPasswordForm');
        $router->post('/reset-password', 'AuthController@resetPassword');

        // === Room Routes (Public) ===
        $router->get('/rooms', 'RoomController@index');
        $router->get('/room/{id}', 'RoomController@show');

        // === User Routes (Login Required) ===
        $router->get('/dashboard', 'UserController@dashboard');
        $router->get('/profile', 'UserController@profile');
        $router->post('/profile', 'UserController@updateProfile');

        // === Booking Routes (Login Required) ===
        $router->get('/book/{roomId}', 'BookingController@bookingForm');
        $router->post('/book', 'BookingController@createBooking');
        $router->get('/bookings', 'BookingController@myBookings');
        $router->post('/booking/{id}/cancel', 'BookingController@cancelBooking');
        $router->get('/booking/{id}/confirmation', 'BookingController@confirmation');

        // === Admin Routes ===
        $router->group(['prefix' => 'admin'], function($router) {
            $router->get('/', 'AdminController@dashboard');

            // Room Management
            $router->get('/rooms', 'AdminController@rooms');
            $router->get('/rooms/add', 'AdminController@addRoomForm');
            $router->post('/rooms/add', 'AdminController@addRoom');
            $router->get('/rooms/edit/{id}', 'AdminController@editRoomForm');
            $router->post('/rooms/edit/{id}', 'AdminController@editRoom');
            $router->post('/rooms/toggle/{id}', 'AdminController@toggleRoom');

            // Booking Management
            $router->get('/bookings', 'AdminController@bookings');
            $router->get('/booking/{id}', 'AdminController@viewBooking');
            $router->post('/bookings/status/{id}', 'AdminController@updateBookingStatus');
            $router->post('/bookings/payment/{id}', 'AdminController@updatePaymentStatus');

            // User Management
            $router->get('/users', 'AdminController@users');
            $router->post('/users/toggle/{id}', 'AdminController@toggleUserStatus');
            $router->post('/users/role/{id}', 'AdminController@changeUserRole');

            // Settings & Logs
            $router->get('/settings', 'AdminController@settings');
            $router->post('/settings', 'AdminController@updateSettings');
            $router->get('/logs', 'AdminController@logs');
        });
    });

    $app->run();

} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>500 - Server Error</h1>";
    if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    exit(1);
}
