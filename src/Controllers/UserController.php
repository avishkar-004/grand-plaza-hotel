<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\BookingRepository;

/**
 * User Controller
 *
 * Handles user dashboard, profile view, and profile updates
 *
 * @package App\Controllers
 */
class UserController extends BaseController
{
    /**
     * User dashboard
     */
    public function dashboard(array $params = [])
    {
        $this->requireLogin();

        $userId = $this->getCurrentUserId();
        $userRepo = new UserRepository($this->db);
        $bookingRepo = new BookingRepository($this->db);

        $userData = $userRepo->find($userId);
        if (!$userData) {
            $this->flash('error', 'User not found.');
            $this->redirect('/login');
            exit;
        }

        // Load all bookings for this user
        $allBookings = $bookingRepo->findByUser($userId);

        // Separate into categories
        $today = date('Y-m-d');
        $upcomingBookings = [];
        $pastBookings = [];
        $cancelledBookings = [];

        foreach ($allBookings as $booking) {
            $status = $booking['status'] ?? '';
            $checkIn = $booking['check_in'] ?? '';

            if ($status === 'cancelled') {
                $cancelledBookings[] = $booking;
            } elseif ($checkIn >= $today) {
                $upcomingBookings[] = $booking;
            } else {
                $pastBookings[] = $booking;
            }
        }

        // Stats
        $totalSpent = 0.0;
        foreach ($allBookings as $booking) {
            if (($booking['status'] ?? '') !== 'cancelled') {
                $totalSpent += (float)($booking['total_price'] ?? 0);
            }
        }

        $stats = [
            'total_bookings' => count($allBookings),
            'active_bookings' => count($upcomingBookings),
            'total_spent' => $totalSpent,
        ];

        // Recent activity logs (last 10, prepared statement)
        $recentActivity = $this->db->fetchAll(
            "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );

        return $this->view('pages.dashboard', [
            'title' => 'Dashboard',
            'user_data' => $userData,
            'upcoming_bookings' => array_slice($upcomingBookings, 0, 5),
            'stats' => $stats,
            'recent_activity' => $recentActivity,
        ])->send();
    }

    /**
     * Show profile page
     */
    public function profile(array $params = [])
    {
        $this->requireLogin();

        $userRepo = new UserRepository($this->db);
        $userData = $userRepo->find($this->getCurrentUserId());

        if (!$userData) {
            $this->flash('error', 'User not found.');
            $this->redirect('/login');
            exit;
        }

        return $this->view('pages.profile', [
            'title' => 'My Profile',
            'user_data' => $userData,
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
        ])->send();
    }

    /**
     * Update profile (POST)
     */
    public function updateProfile(array $params = [])
    {
        $this->requireLogin();

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/profile');
            exit;
        }

        $userId = $this->getCurrentUserId();
        $userRepo = new UserRepository($this->db);
        $currentUser = $userRepo->find($userId);

        if (!$currentUser) {
            $this->flash('error', 'User not found.');
            $this->redirect('/login');
            exit;
        }

        // Collect POST data
        $fullName = trim($this->request->post('full_name', ''));
        $email = trim($this->request->post('email', ''));
        $phone = trim($this->request->post('phone', ''));
        $currentPassword = $this->request->post('current_password', '');
        $newPassword = $this->request->post('new_password', '');
        $confirmPassword = $this->request->post('confirm_password', '');

        // Validation
        $errors = [];

        // Full name: required, 2-100 chars
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors[] = 'Full name must be between 2 and 100 characters.';
        }

        // Email: required, valid format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        // Phone: optional, must match pattern if provided
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors[] = 'Phone number must be 7-20 characters and contain only digits, +, -, spaces, and parentheses.';
        }

        // Check email uniqueness (excluding current user)
        if (strtolower($email) !== strtolower($currentUser->email)) {
            if ($userRepo->emailExists($email, $userId)) {
                $errors[] = 'This email address is already in use.';
            }
        }

        // Password change logic (only if new_password provided)
        $hashedNewPassword = null;
        if ($newPassword !== '') {
            if ($currentPassword === '') {
                $errors[] = 'Current password is required to set a new password.';
            } else {
                if (!password_verify($currentPassword, $currentUser->password)) {
                    $errors[] = 'Current password is incorrect.';
                }
            }

            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            }
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $errors[] = 'New password must contain at least one uppercase letter.';
            }
            if (!preg_match('/[a-z]/', $newPassword)) {
                $errors[] = 'New password must contain at least one lowercase letter.';
            }
            if (!preg_match('/[0-9]/', $newPassword)) {
                $errors[] = 'New password must contain at least one number.';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            }

            if (empty($errors)) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            }
        }

        if (!empty($errors)) {
            $this->flash('error', implode('<br>', array_map([$this, 'esc'], $errors)));
            $this->redirect('/profile');
            exit;
        }

        // Build update array (only changed fields)
        $updateData = [];

        if ($fullName !== $currentUser->full_name) {
            $updateData['full_name'] = $fullName;
        }
        if (strtolower($email) !== strtolower($currentUser->email)) {
            $updateData['email'] = $email;
        }
        if ($phone !== ($currentUser->phone ?? '')) {
            $updateData['phone'] = $phone;
        }
        if ($hashedNewPassword !== null) {
            $updateData['password'] = $hashedNewPassword;
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $userRepo->update($userId, $updateData);

            // Update session display name if full_name changed
            if (isset($updateData['full_name'])) {
                $_SESSION['username'] = $updateData['full_name'];
            }

            // Build activity log description
            $description = 'Profile updated';
            if (isset($updateData['password'])) {
                $description .= ' (password changed)';
            }

            // Log profile update to activity_logs (prepared statement)
            $this->db->execute(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, severity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
                [
                    $userId,
                    'profile_updated',
                    'user',
                    $userId,
                    $description,
                    $this->request->ip(),
                    'info',
                ]
            );

            $this->flash('success', 'Profile updated successfully.');
        } else {
            $this->flash('success', 'No changes detected.');
        }

        $this->redirect('/profile');
        exit;
    }
}
