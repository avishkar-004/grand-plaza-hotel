<?php

namespace App\Repositories;

use App\Models\Hotel;

/**
 * Hotel Repository
 *
 * Handles database operations for hotels
 *
 * @package App\Repositories
 */
class HotelRepository extends BaseRepository
{
    protected string $table = 'hotels';
    protected string $modelClass = Hotel::class;

    /**
     * Find active hotels
     */
    public function findActive(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 AND is_deleted = 0";
        $results = $this->fetchAll($sql);

        return array_map(fn($row) => Hotel::fromArray($row), $results);
    }

    /**
     * Find hotels by city
     */
    public function findByCity(string $city): array
    {
        return $this->findBy('city', $city);
    }

    /**
     * Search hotels
     */
    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE (name LIKE ? OR city LIKE ? OR state LIKE ?)
                AND is_active = 1 AND is_deleted = 0
                LIMIT 50";

        $searchTerm = "%$query%";
        $results = $this->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);

        return array_map(fn($row) => Hotel::fromArray($row), $results);
    }

    /**
     * Get hotels with room count
     */
    public function getWithRoomCount(): array
    {
        $sql = "SELECT h.*, COUNT(r.id) as room_count
                FROM {$this->table} h
                LEFT JOIN rooms r ON r.hotel_id = h.id AND r.is_deleted = 0
                WHERE h.is_active = 1 AND h.is_deleted = 0
                GROUP BY h.id";

        return $this->fetchAll($sql);
    }
}
