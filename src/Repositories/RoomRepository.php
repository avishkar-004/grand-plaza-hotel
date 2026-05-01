<?php

namespace App\Repositories;

use App\Models\Room;

/**
 * Room Repository
 *
 * Handles database operations for rooms
 *
 * @package App\Repositories
 */
class RoomRepository extends BaseRepository
{
    protected string $table = 'rooms';
    protected string $modelClass = Room::class;

    /**
     * Find rooms by hotel
     */
    public function findByHotel(int $hotelId): array
    {
        return $this->findBy('hotel_id', $hotelId);
    }

    /**
     * Find available rooms
     */
    public function findAvailable(): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE is_available = 1
                AND maintenance_status = 'operational'
                AND is_deleted = 0";

        $results = $this->fetchAll($sql);

        return array_map(fn($row) => Room::fromArray($row), $results);
    }

    /**
     * Find available rooms by hotel
     */
    public function findAvailableByHotel(int $hotelId): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE hotel_id = ?
                AND is_available = 1
                AND maintenance_status = 'operational'
                AND is_deleted = 0";

        $results = $this->fetchAll($sql, [$hotelId]);

        return array_map(fn($row) => Room::fromArray($row), $results);
    }

    /**
     * Find rooms with hotel info
     */
    public function findWithHotel(int $roomId): ?array
    {
        $sql = "SELECT r.*, h.name as hotel_name, h.city, h.address
                FROM {$this->table} r
                JOIN hotels h ON r.hotel_id = h.id
                WHERE r.id = ? AND r.is_deleted = 0
                LIMIT 1";

        return $this->fetchOne($sql, [$roomId]) ?: null;
    }

    /**
     * Search rooms
     */
    public function search(string $query): array
    {
        $sql = "SELECT r.*, h.name as hotel_name, h.city
                FROM {$this->table} r
                JOIN hotels h ON r.hotel_id = h.id
                WHERE (h.city LIKE ? OR h.name LIKE ? OR r.room_type LIKE ?)
                AND r.is_available = 1
                AND r.is_deleted = 0
                LIMIT 50";

        $searchTerm = "%$query%";
        return $this->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }

    /**
     * Update room availability
     */
    public function updateAvailability(int $roomId, bool $isAvailable): bool
    {
        return $this->update($roomId, ['is_available' => $isAvailable]);
    }
}
