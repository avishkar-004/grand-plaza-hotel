<?php

namespace App\Models;

/**
 * Booking Model
 *
 * Represents a room booking/reservation
 *
 * @package App\Models
 */
class Booking
{
    public ?int $id = null;
    public string $booking_reference;
    public int $user_id;
    public int $room_id;

    // Dates
    public string $check_in;
    public string $check_out;
    public ?string $booking_date = null;

    // Guest info
    public int $num_guests = 1;
    public ?string $special_requests = null;

    // Pricing
    public float $base_price;
    public float $tax_amount = 0.00;
    public float $discount_amount = 0.00;
    public float $total_price;

    // Status
    public string $status = 'pending'; // pending, confirmed, checked_in, checked_out, cancelled, no_show
    public string $payment_status = 'unpaid'; // unpaid, partial, paid, refunded

    // Cancellation
    public ?string $cancelled_at = null;
    public ?int $cancelled_by = null;
    public ?string $cancellation_reason = null;

    // Audit
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?int $created_by = null;
    public bool $is_deleted = false;

    // Related models (loaded separately)
    public ?User $user = null;
    public ?Room $room = null;

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $booking = new self();

        foreach ($data as $key => $value) {
            if (property_exists($booking, $key)) {
                $booking->$key = $value;
            }
        }

        return $booking;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Calculate number of nights
     */
    public function getNumberOfNights(): int
    {
        $checkIn = new \DateTime($this->check_in);
        $checkOut = new \DateTime($this->check_out);

        return $checkOut->diff($checkIn)->days;
    }

    /**
     * Check if booking is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'checked_in']);
    }

    /**
     * Check if booking is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'checked_out';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'checked_in' => 'primary',
            'checked_out' => 'success',
            'cancelled' => 'danger',
            'no_show' => 'dark',
            default => 'secondary'
        };
    }

    /**
     * Get payment status badge class
     */
    public function getPaymentStatusBadgeClass(): string
    {
        return match($this->payment_status) {
            'unpaid' => 'danger',
            'partial' => 'warning',
            'paid' => 'success',
            'refunded' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Generate unique booking reference
     */
    public static function generateReference(): string
    {
        return 'BK' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Format date for display
     */
    public function getFormattedCheckIn(): string
    {
        return date('M d, Y', strtotime($this->check_in));
    }

    /**
     * Format date for display
     */
    public function getFormattedCheckOut(): string
    {
        return date('M d, Y', strtotime($this->check_out));
    }

    /**
     * Get booking summary
     */
    public function getSummary(): string
    {
        return sprintf(
            '%s - %s (%d night%s)',
            $this->getFormattedCheckIn(),
            $this->getFormattedCheckOut(),
            $this->getNumberOfNights(),
            $this->getNumberOfNights() !== 1 ? 's' : ''
        );
    }
}
