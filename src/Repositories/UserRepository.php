<?php

namespace App\Repositories;

use App\Models\User;

/**
 * User Repository
 *
 * Handles database operations for users
 *
 * @package App\Repositories
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected string $modelClass = User::class;

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy('username', $username);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy('email', $email);
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ? AND is_deleted = 0";
        $params = [$username];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ? AND is_deleted = 0";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Update failed login attempts
     */
    public function incrementFailedLoginAttempts(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?";
        $this->execute($sql, [$userId]);
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedLoginAttempts(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?";
        $this->execute($sql, [$userId]);
    }

    /**
     * Lock user account
     */
    public function lockAccount(int $userId, int $minutes = 30): void
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        if ($driver === 'sqlite') {
            $sql = "UPDATE {$this->table} SET locked_until = datetime('now', '+{$minutes} minutes') WHERE id = ?";
        } else {
            $sql = "UPDATE {$this->table} SET locked_until = DATE_ADD(NOW(), INTERVAL {$minutes} MINUTE) WHERE id = ?";
        }
        $this->execute($sql, [$userId]);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $userId, string $ipAddress): void
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        if ($driver === 'sqlite') {
            $sql = "UPDATE {$this->table} SET last_login = datetime('now'), last_login_ip = ? WHERE id = ?";
        } else {
            $sql = "UPDATE {$this->table} SET last_login = NOW(), last_login_ip = ? WHERE id = ?";
        }
        $this->execute($sql, [$ipAddress, $userId]);
    }

    /**
     * Get users by role
     */
    public function findByRole(string $role): array
    {
        return $this->findBy('role', $role);
    }

    /**
     * Search users
     */
    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?)
                AND is_deleted = 0
                LIMIT 50";

        $searchTerm = "%$query%";
        $results = $this->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);

        return array_map(fn($row) => User::fromArray($row), $results);
    }
}
