<?php

namespace App\Repositories;

use App\Models\Booking;

/**
 * Booking Repository
 *
 * Handles database operations for bookings
 *
 * @package App\Repositories
 */
class BookingRepository extends BaseRepository
{
    protected string $table = 'bookings';
    protected string $modelClass = Booking::class;

    /**
     * Find bookings by user
     */
    public function findByUser(int $userId): array
    {
        $sql = "SELECT b.*, r.room_number, r.room_type, h.name as hotel_name, h.city
                FROM {$this->table} b
                JOIN rooms r ON b.room_id = r.id
                JOIN hotels h ON r.hotel_id = h.id
                WHERE b.user_id = ? AND b.is_deleted = 0
                ORDER BY b.created_at DESC";

        return $this->fetchAll($sql, [$userId]);
    }

    /**
     * Find booking by reference
     */
    public function findByReference(string $reference): ?Booking
    {
        return $this->findOneBy('booking_reference', $reference);
    }

    /**
     * Find active bookings
     */
    public function findActive(): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status IN ('pending', 'confirmed', 'checked_in')
                AND is_deleted = 0";

        $results = $this->fetchAll($sql);

        return array_map(fn($row) => Booking::fromArray($row), $results);
    }

    /**
     * Find bookings for a room in date range
     */
    public function findByRoomAndDateRange(int $roomId, string $checkIn, string $checkOut): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE room_id = ?
                AND status NOT IN ('cancelled', 'no_show')
                AND (
                    (check_in <= ? AND check_out > ?)
                    OR (check_in < ? AND check_out >= ?)
                    OR (check_in >= ? AND check_out <= ?)
                )
                AND is_deleted = 0";

        $results = $this->fetchAll($sql, [
            $roomId,
            $checkIn, $checkIn,
            $checkOut, $checkOut,
            $checkIn, $checkOut
        ]);

        return array_map(fn($row) => Booking::fromArray($row), $results);
    }

    /**
     * Cancel booking
     */
    public function cancel(int $bookingId, int $cancelledBy, ?string $reason = null): bool
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        $now = $driver === 'sqlite' ? "datetime('now')" : "NOW()";
        $sql = "UPDATE {$this->table}
                SET status = 'cancelled',
                    cancelled_at = {$now},
                    cancelled_by = ?,
                    cancellation_reason = ?
                WHERE id = ?";

        return $this->execute($sql, [$cancelledBy, $reason, $bookingId]) > 0;
    }

    /**
     * Update booking status
     */
    public function updateStatus(int $bookingId, string $status): bool
    {
        return $this->update($bookingId, ['status' => $status]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $bookingId, string $paymentStatus): bool
    {
        return $this->update($bookingId, ['payment_status' => $paymentStatus]);
    }

    /**
     * Get upcoming bookings
     */
    public function getUpcoming(int $limit = 10): array
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        $today = $driver === 'sqlite' ? "date('now')" : "CURDATE()";
        $sql = "SELECT b.*, r.room_number, h.name as hotel_name
                FROM {$this->table} b
                JOIN rooms r ON b.room_id = r.id
                JOIN hotels h ON r.hotel_id = h.id
                WHERE b.check_in >= {$today}
                AND b.status IN ('confirmed', 'pending')
                AND b.is_deleted = 0
                ORDER BY b.check_in ASC
                LIMIT ?";

        return $this->fetchAll($sql, [$limit]);
    }

    /**
     * Get booking statistics
     */
    public function getStatistics(): array
    {
        $sql = "SELECT
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(total_price) as total_revenue
                FROM {$this->table}
                WHERE is_deleted = 0";

        return $this->fetchOne($sql) ?: [];
    }
}
