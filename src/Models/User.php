<?php

namespace App\Models;

/**
 * User Model
 *
 * Represents a user in the system
 *
 * @package App\Models
 */
class User
{
    public ?int $id = null;
    public string $username;
    public string $email;
    public string $password;
    public string $full_name;
    public ?string $phone = null;
    public string $role = 'user'; // user, admin

    // Security tracking
    public int $failed_login_attempts = 0;
    public ?string $locked_until = null;
    public ?string $last_login = null;
    public ?string $last_login_ip = null;

    // Email verification
    public ?string $email_verified_at = null;
    public ?string $email_verification_token = null;

    // Password reset
    public ?string $password_reset_token = null;
    public ?string $password_reset_expires = null;

    // Account status
    public bool $is_active = true;

    // Audit fields
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?int $created_by = null;
    public ?int $updated_by = null;
    public bool $is_deleted = false;
    public ?string $deleted_at = null;
    public ?int $deleted_by = null;

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $user = new self();

        foreach ($data as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }

        return $user;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a regular user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if account is locked
     */
    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }

        return strtotime($this->locked_until) > time();
    }

    /**
     * Check if email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Get user's display name
     */
    public function getDisplayName(): string
    {
        return $this->full_name ?: $this->username;
    }
}
