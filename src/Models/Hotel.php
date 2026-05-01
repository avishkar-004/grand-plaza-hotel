<?php

namespace App\Models;

/**
 * Hotel Model
 *
 * Represents a hotel property
 *
 * @package App\Models
 */
class Hotel
{
    public ?int $id = null;
    public string $name;
    public ?string $description = null;
    public string $address;
    public string $city;
    public ?string $state = null;
    public string $country = 'USA';
    public ?string $zip_code = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $website = null;
    public ?int $star_rating = null;

    // Hotel features (JSON)
    public ?string $amenities = null;
    public string $check_in_time = '15:00:00';
    public string $check_out_time = '11:00:00';

    // Status
    public bool $is_active = true;

    // Audit fields
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?int $created_by = null;
    public ?int $updated_by = null;
    public bool $is_deleted = false;
    public ?string $deleted_at = null;

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $hotel = new self();

        foreach ($data as $key => $value) {
            if (property_exists($hotel, $key)) {
                $hotel->$key = $value;
            }
        }

        return $hotel;
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
     * Get full address
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get star rating display
     */
    public function getStarRating(): string
    {
        if (!$this->star_rating) {
            return 'Unrated';
        }

        return str_repeat('⭐', $this->star_rating);
    }
}
