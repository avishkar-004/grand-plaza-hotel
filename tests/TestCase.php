<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected ?PDO $db = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->createTables();
        $this->seedData();
    }

    protected function createTables(): void
    {
        $schema = file_get_contents(__DIR__ . '/../database/schema_sqlite.sql');
        // Remove PRAGMA and INSERT statements - we'll seed manually
        $statements = explode(';', $schema);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (stripos($stmt, 'CREATE TABLE') !== false || stripos($stmt, 'CREATE INDEX') !== false) {
                try {
                    $this->db->exec($stmt);
                } catch (\Exception $e) {
                    // Skip if table exists
                }
            }
        }
    }

    protected function seedData(): void
    {
        // Admin user with bcrypt password
        $this->db->exec("INSERT INTO users (id, username, email, password, full_name, role, is_active, email_verified_at) VALUES (1, 'admin', 'admin@test.com', '" . password_hash('admin123', PASSWORD_BCRYPT) . "', 'Admin User', 'admin', 1, datetime('now'))");

        // Regular user
        $this->db->exec("INSERT INTO users (id, username, email, password, full_name, role, is_active, email_verified_at) VALUES (2, 'user1', 'user1@test.com', '" . password_hash('user123', PASSWORD_BCRYPT) . "', 'Test User', 'user', 1, datetime('now'))");

        // Hotel
        $this->db->exec("INSERT INTO hotels (id, name, description, address, city, state, country, star_rating, is_active, created_by) VALUES (1, 'Test Hotel', 'A test hotel', '123 Test St', 'Mumbai', 'MH', 'India', 5, 1, 1)");

        // Rooms
        $this->db->exec("INSERT INTO rooms (id, hotel_id, room_number, room_type, floor_number, base_price, max_occupancy, num_beds, bed_type, is_available, maintenance_status, created_by) VALUES (1, 1, '101', 'single', 1, 4500, 1, 1, 'Queen', 1, 'operational', 1)");
        $this->db->exec("INSERT INTO rooms (id, hotel_id, room_number, room_type, floor_number, base_price, max_occupancy, num_beds, bed_type, is_available, maintenance_status, created_by) VALUES (2, 1, '201', 'deluxe', 2, 12500, 2, 1, 'King', 1, 'operational', 1)");
        $this->db->exec("INSERT INTO rooms (id, hotel_id, room_number, room_type, floor_number, base_price, max_occupancy, num_beds, bed_type, is_available, maintenance_status, created_by) VALUES (3, 1, '301', 'suite', 3, 24000, 3, 1, 'King', 0, 'maintenance', 1)");
    }

    protected function tearDown(): void
    {
        $this->db = null;
        parent::tearDown();
    }
}
