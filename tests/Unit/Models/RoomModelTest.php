<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Room;

class RoomModelTest extends TestCase
{
    public function test_room_from_array(): void
    {
        $data = [
            'id' => 1,
            'hotel_id' => 1,
            'room_number' => '101',
            'room_type' => 'single',
            'floor_number' => 1,
            'base_price' => 4500.00,
            'max_occupancy' => 1,
            'num_beds' => 1,
            'bed_type' => 'Queen',
            'is_available' => true,
            'maintenance_status' => 'operational',
        ];
        $room = Room::fromArray($data);

        $this->assertEquals(1, $room->id);
        $this->assertEquals(1, $room->hotel_id);
        $this->assertEquals('101', $room->room_number);
        $this->assertEquals('single', $room->room_type);
        $this->assertEquals(4500.00, $room->base_price);
        $this->assertEquals('Queen', $room->bed_type);
    }

    public function test_room_to_array(): void
    {
        $data = [
            'id' => 2,
            'hotel_id' => 1,
            'room_number' => '202',
            'room_type' => 'deluxe',
            'base_price' => 12500.00,
        ];
        $room = Room::fromArray($data);
        $arr = $room->toArray();

        $this->assertIsArray($arr);
        $this->assertEquals(2, $arr['id']);
        $this->assertEquals('202', $arr['room_number']);
        $this->assertEquals('deluxe', $arr['room_type']);
    }

    public function test_get_amenities_with_json(): void
    {
        $amenities = ['WiFi', 'TV', 'Mini Bar'];
        $room = Room::fromArray([
            'id' => 1,
            'amenities' => json_encode($amenities),
        ]);

        $result = $room->getAmenities();
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains('WiFi', $result);
        $this->assertContains('TV', $result);
        $this->assertContains('Mini Bar', $result);
    }

    public function test_get_amenities_with_null(): void
    {
        $room = Room::fromArray(['id' => 1, 'amenities' => null]);

        $result = $room->getAmenities();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_amenities_with_invalid_json(): void
    {
        $room = Room::fromArray(['id' => 1, 'amenities' => 'not valid json']);

        $result = $room->getAmenities();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_set_amenities(): void
    {
        $room = new Room();
        $amenities = ['WiFi', 'Pool', 'Gym'];
        $room->setAmenities($amenities);

        $this->assertIsString($room->amenities);
        $decoded = json_decode($room->amenities, true);
        $this->assertEquals($amenities, $decoded);
    }

    public function test_get_images_with_json(): void
    {
        $images = ['room1.jpg', 'room2.jpg'];
        $room = Room::fromArray([
            'id' => 1,
            'images' => json_encode($images),
        ]);

        $result = $room->getImages();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('room1.jpg', $result);
    }

    public function test_get_images_with_null(): void
    {
        $room = Room::fromArray(['id' => 1, 'images' => null]);

        $result = $room->getImages();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_set_images(): void
    {
        $room = new Room();
        $images = ['img1.jpg', 'img2.png'];
        $room->setImages($images);

        $this->assertIsString($room->images);
        $decoded = json_decode($room->images, true);
        $this->assertEquals($images, $decoded);
    }

    public function test_get_formatted_type_single(): void
    {
        $room = Room::fromArray(['id' => 1, 'room_type' => 'single']);
        $this->assertEquals('Single', $room->getFormattedType());
    }

    public function test_get_formatted_type_deluxe(): void
    {
        $room = Room::fromArray(['id' => 1, 'room_type' => 'deluxe']);
        $this->assertEquals('Deluxe', $room->getFormattedType());
    }

    public function test_get_formatted_type_presidential(): void
    {
        $room = Room::fromArray(['id' => 1, 'room_type' => 'presidential']);
        $this->assertEquals('Presidential', $room->getFormattedType());
    }

    public function test_is_available_when_operational_and_available(): void
    {
        $room = Room::fromArray([
            'id' => 1,
            'is_available' => true,
            'maintenance_status' => 'operational',
        ]);

        $this->assertTrue($room->isAvailable());
    }

    public function test_is_not_available_when_unavailable(): void
    {
        $room = Room::fromArray([
            'id' => 1,
            'is_available' => false,
            'maintenance_status' => 'operational',
        ]);

        $this->assertFalse($room->isAvailable());
    }

    public function test_is_not_available_when_maintenance(): void
    {
        $room = Room::fromArray([
            'id' => 1,
            'is_available' => true,
            'maintenance_status' => 'maintenance',
        ]);

        $this->assertFalse($room->isAvailable());
    }

    public function test_is_not_available_when_out_of_service(): void
    {
        $room = Room::fromArray([
            'id' => 1,
            'is_available' => true,
            'maintenance_status' => 'out_of_service',
        ]);

        $this->assertFalse($room->isAvailable());
    }

    public function test_get_current_price(): void
    {
        $room = Room::fromArray(['id' => 1, 'base_price' => 4500.00]);
        $this->assertEquals(4500.00, $room->getCurrentPrice());
    }

    public function test_get_display_name(): void
    {
        $room = Room::fromArray([
            'id' => 1,
            'room_number' => '202',
            'room_type' => 'deluxe',
        ]);

        $this->assertEquals('Deluxe - Room 202', $room->getDisplayName());
    }

    public function test_default_values(): void
    {
        $room = new Room();

        $this->assertTrue($room->is_available);
        $this->assertEquals('operational', $room->maintenance_status);
        $this->assertEquals(2, $room->max_occupancy);
        $this->assertEquals(1, $room->num_beds);
        $this->assertFalse($room->is_deleted);
    }

    public function test_from_array_ignores_unknown_keys(): void
    {
        $data = [
            'id' => 1,
            'room_number' => '101',
            'fake_field' => 'ignored',
        ];
        $room = Room::fromArray($data);

        $this->assertEquals(1, $room->id);
        $this->assertEquals('101', $room->room_number);
    }
}
