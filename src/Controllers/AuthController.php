<?php

namespace App\Controllers;

use App\Core\Response;
use App\Repositories\UserRepository;
use App\Models\User;

/**
 * Auth Controller
 *
 * Handles authentication (login, register, logout)
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function loginForm()
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/');
            exit;
        }

        return $this->view('pages.login', [
            'title' => 'Login',
            'error' => $this->getFlash('error'),
        ])->send();
    }

    /**
     * Process login
     */
    public function login(): Response
    {
        if (!$this->request->isPost()) {
            $this->redirect('/login');
            exit;
        }

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid CSRF token');
            $this->redirect('/login');
            exit;
        }

        $username = $this->request->post('username');
        $password = $this->request->post('password');

        if (!$username || !$password) {
            $this->flash('error', 'Username and password are required');
            $this->redirect('/login');
            exit;
        }

        $userRepo = new UserRepository($this->db);
        $user = $userRepo->findByUsername($username);

        if (!$user) {
            $this->flash('error', 'Invalid credentials');
            $this->redirect('/login');
            exit;
        }

        // Check if account is locked
        if ($user->isLocked()) {
            $this->flash('error', 'Account is locked. Please try again later.');
            $this->redirect('/login');
            exit;
        }

        // Password verification
        $passwordValid = false;

        if ($this->app->isSecureMode()) {
            // Secure mode: Use password_verify
            $passwordValid = password_verify($password, $user->password);
        } else {
            // Vulnerable mode: Direct comparison (SQL injection demo)
            $passwordValid = ($password === $user->password);
        }

        if (!$passwordValid) {
            // Increment failed attempts
            $userRepo->incrementFailedLoginAttempts($user->id);

            // Lock account after 5 failed attempts (secure mode only)
            if ($this->app->isSecureMode() && $user->failed_login_attempts >= 4) {
                $userRepo->lockAccount($user->id, 30);
                $this->flash('error', 'Too many failed attempts. Account locked for 30 minutes.');
            } else {
                $this->flash('error', 'Invalid credentials');
            }

            $this->redirect('/login');
            exit;
        }

        // Login successful
        $userRepo->resetFailedLoginAttempts($user->id);
        $userRepo->updateLastLogin($user->id, $this->request->ip());

        // Regenerate session ID (secure mode)
        if ($this->app->isSecureMode()) {
            session_regenerate_id(true);
        }

        // Set session variables
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        $_SESSION['LAST_ACTIVITY'] = time();

        // Redirect
        $redirect = $this->request->get('redirect', '/');
        $this->redirect($redirect);
        exit;
    }

    /**
     * Show register form
     */
    public function registerForm(): Response
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/');
            exit;
        }

        return $this->view('pages.register', [
            'title' => 'Register',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
        ])->send();
    }

    /**
     * Process registration
     */
    public function register(): Response
    {
        if (!$this->request->isPost()) {
            $this->redirect('/register');
            exit;
        }

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid CSRF token');
            $this->redirect('/register');
            exit;
        }

        $username = $this->request->post('username');
        $email = $this->request->post('email');
        $password = $this->request->post('password');
        $fullName = $this->request->post('full_name');
        $phone = $this->request->post('phone');

        // Validation
        $errors = [];

        if (!$username || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }

        if (!$password || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if (!$fullName) {
            $errors[] = 'Full name is required';
        }

        // Check for existing username/email
        $userRepo = new UserRepository($this->db);

        if ($userRepo->usernameExists($username)) {
            $errors[] = 'Username already exists';
        }

        if ($userRepo->emailExists($email)) {
            $errors[] = 'Email already exists';
        }

        if (!empty($errors)) {
            $this->flash('error', implode('<br>', $errors));
            $this->redirect('/register');
            exit;
        }

        // Hash password in secure mode
        if ($this->app->isSecureMode()) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            // Vulnerable mode: Store plaintext password
            $hashedPassword = $password;
        }

        // Create user
        $userId = $userRepo->create([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'full_name' => $fullName,
            'phone' => $phone,
            'role' => 'user',
            'is_active' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($userId) {
            // Auto-login
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';

            $this->flash('success', 'Registration successful! Welcome!');
            $this->redirect('/');
        } else {
            $this->flash('error', 'Registration failed. Please try again.');
            $this->redirect('/register');
        }

        exit;
    }

    /**
     * Logout
     */
    public function logout(): Response
    {
        session_unset();
        session_destroy();

        $this->redirect('/');
        exit;
    }

    /**
     * Show forgot password form
     */
    public function forgotPasswordForm()
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
            exit;
        }

        return $this->view('pages.forgot_password', [
            'title' => 'Forgot Password',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'reset_link' => $this->getFlash('reset_link'),
        ])->send();
    }

    /**
     * Process forgot password request
     */
    public function forgotPassword()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/forgot-password');
            exit;
        }

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid CSRF token');
            $this->redirect('/forgot-password');
            exit;
        }

        $email = trim($this->request->post('email', ''));

        // Validate email format
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid email address.');
            $this->redirect('/forgot-password');
            exit;
        }

        $userRepo = new UserRepository($this->db);
        $user = $userRepo->findByEmail($email);

        if ($user) {
            // Generate cryptographically secure reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token and expiry to user record
            $userRepo->update($user->id, [
                'password_reset_token' => $token,
                'password_reset_expires' => $expiry,
            ]);

            // Log the password reset request
            $this->db->execute(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, severity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
                [
                    $user->id,
                    'password_reset_requested',
                    'user',
                    $user->id,
                    'Password reset requested for ' . $email,
                    $this->request->ip(),
                    'info',
                ]
            );

            // In a real app, this token would be emailed. For demo, show it in a flash message.
            $this->flash('reset_link', '/reset-password?token=' . $token);
        }

        // Always show the same message regardless of whether user exists (prevent email enumeration)
        $this->flash('success', 'If an account exists with that email, reset instructions have been sent.');
        $this->redirect('/forgot-password');
        exit;
    }

    /**
     * Show reset password form
     */
    public function resetPasswordForm()
    {
        $token = $this->request->get('token', '');

        if (!$token) {
            $this->flash('error', 'No reset token provided.');
            $this->redirect('/login');
            exit;
        }

        // Look up user by token and check expiry
        $userData = $this->db->fetchOne(
            "SELECT * FROM users WHERE password_reset_token = ? AND is_deleted = 0",
            [$token]
        );

        if (!$userData || strtotime($userData['password_reset_expires']) < time()) {
            $this->flash('error', 'Invalid or expired reset link.');
            $this->redirect('/forgot-password');
            exit;
        }

        return $this->view('pages.reset_password', [
            'title' => 'Reset Password',
            'token' => $token,
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
        ])->send();
    }

    /**
     * Process password reset
     */
    public function resetPassword()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/login');
            exit;
        }

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid CSRF token');
            $this->redirect('/login');
            exit;
        }

        $token = $this->request->post('token', '');
        $newPassword = $this->request->post('new_password', '');
        $confirmPassword = $this->request->post('confirm_password', '');

        // Validate token
        if (!$token) {
            $this->flash('error', 'Invalid reset token.');
            $this->redirect('/login');
            exit;
        }

        // Look up user by token and check expiry
        $userData = $this->db->fetchOne(
            "SELECT * FROM users WHERE password_reset_token = ? AND is_deleted = 0",
            [$token]
        );

        if (!$userData || strtotime($userData['password_reset_expires']) < time()) {
            $this->flash('error', 'Invalid or expired reset link.');
            $this->redirect('/forgot-password');
            exit;
        }

        // Validate password policy: min 8 chars, uppercase, lowercase, number
        $errors = [];

        if (strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            $this->flash('error', implode('<br>', $errors));
            $this->redirect('/reset-password?token=' . urlencode($token));
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Update user: set new password, clear reset token/expiry, reset lockout
        $userRepo = new UserRepository($this->db);
        $userRepo->update((int)$userData['id'], [
            'password' => $hashedPassword,
            'password_reset_token' => null,
            'password_reset_expires' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Log the password reset
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, severity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                (int)$userData['id'],
                'password_reset_completed',
                'user',
                (int)$userData['id'],
                'Password was reset successfully',
                $this->request->ip(),
                'info',
            ]
        );

        $this->flash('success', 'Password reset successful! You can now login.');
        $this->redirect('/login');
        exit;
    }
}
