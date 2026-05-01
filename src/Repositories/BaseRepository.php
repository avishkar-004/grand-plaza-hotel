<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * Base Repository
 *
 * Abstract base class for all repositories
 *
 * @package App\Repositories
 */
abstract class BaseRepository
{
    protected Database $db;
    protected string $table;
    protected string $modelClass;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find record by ID
     */
    public function find(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND is_deleted = 0 LIMIT 1";
        $result = $this->db->fetchOne($sql, [$id]);

        if (!$result) {
            return null;
        }

        return $this->modelClass::fromArray($result);
    }

    /**
     * Find all records
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_deleted = 0";

        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $results = $this->db->fetchAll($sql);

        return array_map(fn($row) => $this->modelClass::fromArray($row), $results);
    }

    /**
     * Find by column value
     */
    public function findBy(string $column, $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE $column = ? AND is_deleted = 0";
        $results = $this->db->fetchAll($sql, [$value]);

        return array_map(fn($row) => $this->modelClass::fromArray($row), $results);
    }

    /**
     * Find one by column value
     */
    public function findOneBy(string $column, $value): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE $column = ? AND is_deleted = 0 LIMIT 1";
        $result = $this->db->fetchOne($sql, [$value]);

        if (!$result) {
            return null;
        }

        return $this->modelClass::fromArray($result);
    }

    /**
     * Create new record
     */
    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO {$this->table} (%s) VALUES (%s)",
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->execute($sql, array_values($data));

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = sprintf(
            "UPDATE {$this->table} SET %s WHERE id = ?",
            implode(', ', $sets)
        );

        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * Delete record (soft delete)
     */
    public function delete(int $id): bool
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        $now = $driver === 'sqlite' ? "datetime('now')" : "NOW()";
        $sql = "UPDATE {$this->table} SET is_deleted = 1, deleted_at = {$now} WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    /**
     * Hard delete record
     */
    public function hardDelete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->execute($sql, [$id]) > 0;
    }

    /**
     * Count records
     */
    public function count(array $where = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_deleted = 0";

        $params = [];
        if (!empty($where)) {
            foreach ($where as $column => $value) {
                $sql .= " AND $column = ?";
                $params[] = $value;
            }
        }

        $result = $this->db->fetchOne($sql, $params);

        return (int)($result['count'] ?? 0);
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ? AND is_deleted = 0";
        $result = $this->db->fetchOne($sql, [$id]);

        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Execute raw SQL query
     */
    protected function query(string $sql, array $params = [])
    {
        return $this->db->query($sql, $params);
    }

    /**
     * Fetch all from raw SQL
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Fetch one from raw SQL
     */
    protected function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Execute SQL statement
     */
    protected function execute(string $sql, array $params = []): int
    {
        return $this->db->execute($sql, $params);
    }
}
