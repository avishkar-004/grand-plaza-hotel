<?php

namespace App\Models;

/**
 * Room Model
 *
 * Represents a hotel room
 *
 * @package App\Models
 */
class Room
{
    public ?int $id = null;
    public int $hotel_id;
    public string $room_number;
    public string $room_type; // single, double, suite, deluxe, presidential
    public ?int $floor_number = null;
    public ?string $description = null;

    // Pricing
    public float $base_price;
    public ?float $weekend_price = null;
    public ?float $peak_season_price = null;

    // Capacity
    public int $max_occupancy = 2;
    public int $num_beds = 1;
    public ?string $bed_type = null;

    // Features (JSON)
    public ?string $amenities = null;
    public ?int $square_feet = null;
    public ?string $view_type = null;

    // Availability
    public bool $is_available = true;
    public string $maintenance_status = 'operational'; // operational, maintenance, out_of_service

    // Media (JSON array)
    public ?string $images = null;

    // Audit fields
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?int $created_by = null;
    public bool $is_deleted = false;

    // Related models (loaded separately)
    public ?Hotel $hotel = null;

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $room = new self();

        foreach ($data as $key => $value) {
            if (property_exists($room, $key)) {
                $room->$key = $value;
            }
        }

        return $room;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Get amenities as array
     */
    public function getAmenities(): array
    {
        if (!$this->amenities) {
            return [];
        }

        $decoded = json_decode($this->amenities, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set amenities from array
     */
    public function setAmenities(array $amenities): void
    {
        $this->amenities = json_encode($amenities);
    }

    /**
     * Get images as array
     */
    public function getImages(): array
    {
        if (!$this->images) {
            return [];
        }

        $decoded = json_decode($this->images, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set images from array
     */
    public function setImages(array $images): void
    {
        $this->images = json_encode($images);
    }

    /**
     * Get current price (considering weekend/peak)
     */
    public function getCurrentPrice(): float
    {
        // For now, just return base price
        // In real app, this would check date and apply appropriate pricing
        return $this->base_price;
    }

    /**
     * Get formatted room type
     */
    public function getFormattedType(): string
    {
        return ucfirst(str_replace('_', ' ', $this->room_type));
    }

    /**
     * Check if room is available
     */
    public function isAvailable(): bool
    {
        return $this->is_available && $this->maintenance_status === 'operational';
    }

    /**
     * Get room display name
     */
    public function getDisplayName(): string
    {
        return $this->getFormattedType() . ' - Room ' . $this->room_number;
    }
}
