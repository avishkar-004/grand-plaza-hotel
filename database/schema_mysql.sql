-- =====================================================
-- Hotel Management System Database Schema (MySQL Version)
-- Aligned with SQLite schema - same columns, same seed data
-- =====================================================

DROP DATABASE IF EXISTS hotel_management_db;
CREATE DATABASE hotel_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_management_db;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',

    failed_login_attempts INT UNSIGNED DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    last_login_ip VARCHAR(45),

    email_verified_at DATETIME NULL,
    email_verification_token VARCHAR(64) NULL,

    password_reset_token VARCHAR(64) NULL,
    password_reset_expires DATETIME NULL,

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at DATETIME NULL,
    deleted_by INT UNSIGNED NULL,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: hotels
-- =====================================================
CREATE TABLE hotels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50),
    country VARCHAR(50) NOT NULL DEFAULT 'India',
    zip_code VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(255),
    star_rating TINYINT CHECK (star_rating BETWEEN 1 AND 5),

    amenities JSON,
    check_in_time TIME DEFAULT '15:00:00',
    check_out_time TIME DEFAULT '11:00:00',

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at DATETIME NULL,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: rooms
-- =====================================================
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    room_type ENUM('single', 'double', 'suite', 'deluxe', 'presidential') NOT NULL,
    floor_number INT,
    description TEXT,

    base_price DECIMAL(10,2) NOT NULL,
    weekend_price DECIMAL(10,2),
    peak_season_price DECIMAL(10,2),

    max_occupancy INT UNSIGNED DEFAULT 2,
    num_beds INT UNSIGNED DEFAULT 1,
    bed_type VARCHAR(50),

    amenities JSON,
    square_feet INT UNSIGNED,
    view_type VARCHAR(50),

    is_available BOOLEAN DEFAULT TRUE,
    maintenance_status ENUM('operational', 'maintenance', 'out_of_service') DEFAULT 'operational',

    images JSON,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    is_deleted BOOLEAN DEFAULT FALSE,

    UNIQUE KEY unique_room (hotel_id, room_number),
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_room_type (room_type),
    INDEX idx_is_available (is_available),

    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: bookings
-- =====================================================
CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,

    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    num_guests INT UNSIGNED NOT NULL DEFAULT 1,
    special_requests TEXT,

    base_price DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL,

    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',

    cancelled_at DATETIME NULL,
    cancelled_by INT UNSIGNED NULL,
    cancellation_reason TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    is_deleted BOOLEAN DEFAULT FALSE,

    INDEX idx_user_id (user_id),
    INDEX idx_room_id (room_id),
    INDEX idx_check_in (check_in),
    INDEX idx_status (status),
    INDEX idx_booking_ref (booking_reference),

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: sessions
-- =====================================================
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    csrf_token VARCHAR(64),
    payload TEXT,
    last_activity INT NOT NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: activity_logs
-- =====================================================
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT UNSIGNED,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data TEXT,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    is_security_event BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_security (is_security_event),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA - Single Hotel: Grand Plaza Hotel & Resort
-- =====================================================

INSERT INTO users (username, email, password, full_name, role, email_verified_at, is_active) VALUES
('admin', 'admin@grandplaza.in', 'admin123', 'System Administrator', 'admin', NOW(), TRUE);

INSERT INTO users (username, email, password, full_name, phone, role, email_verified_at, is_active, created_by) VALUES
('user1', 'user1@example.com', 'user123', 'Rahul Sharma', '+91-98765-43210', 'user', NOW(), TRUE, 1);

INSERT INTO hotels (name, description, address, city, state, country, zip_code, phone, email, website, star_rating, amenities, check_in_time, check_out_time, is_active, created_by) VALUES
('Grand Plaza Hotel & Resort',
 'Experience timeless elegance at the Grand Plaza Hotel & Resort, a landmark 5-star property on the iconic Marine Drive in Mumbai. Featuring world-class dining, a rooftop infinity pool, a full-service spa, and breathtaking Arabian Sea views, our hotel offers an unparalleled blend of luxury and comfort.',
 'Marine Drive, Nariman Point', 'Mumbai', 'Maharashtra', 'India', '400021',
 '+91-22-6789-0100', 'reservations@grandplaza.in', 'https://www.grandplaza.in', 5,
 '["WiFi","Pool","Gym","Spa","Restaurant","Bar","Room Service","Valet Parking","Concierge","Business Center","Laundry","Conference Room","Airport Shuttle"]',
 '15:00:00', '11:00:00', TRUE, 1);

INSERT INTO rooms (hotel_id, room_number, room_type, floor_number, description, base_price, weekend_price, peak_season_price, max_occupancy, num_beds, bed_type, amenities, square_feet, view_type, is_available, created_by) VALUES
(1, '101', 'single', 1, 'Comfortable single room with modern furnishings and a cozy workspace.', 4500.00, 5500.00, 6500.00, 1, 1, 'Queen', '["WiFi","40-inch TV","Mini Bar","In-Room Safe","Work Desk","Coffee Maker","Iron & Board"]', 280, 'City', TRUE, 1),
(1, '102', 'single', 1, 'Well-appointed single room featuring a plush queen bed and marble bathroom.', 4900.00, 5900.00, 6900.00, 1, 1, 'Queen', '["WiFi","40-inch TV","Mini Bar","In-Room Safe","Work Desk","Coffee Maker","Rainfall Shower"]', 295, 'Courtyard', TRUE, 1),
(1, '103', 'double', 1, 'Spacious double room perfect for couples, with a king bed and sitting area.', 7500.00, 9000.00, 10500.00, 2, 1, 'King', '["WiFi","50-inch TV","Mini Bar","In-Room Safe","Sitting Area","Coffee Maker","Bathrobes"]', 380, 'City', TRUE, 1),
(1, '104', 'double', 1, 'Corner double room offering extra natural light and city views from two windows.', 8500.00, 10200.00, 11900.00, 2, 1, 'King', '["WiFi","50-inch TV","Mini Bar","In-Room Safe","Sitting Area","Coffee Maker","Bathrobes","Corner Views"]', 410, 'City', TRUE, 1),
(1, '201', 'double', 2, 'Premium double room with upgraded linens and a Nespresso machine.', 9500.00, 11400.00, 13300.00, 2, 1, 'King', '["WiFi","55-inch Smart TV","Premium Mini Bar","In-Room Safe","Nespresso Machine","Bathrobes","Slippers"]', 400, 'Skyline', TRUE, 1),
(1, '202', 'deluxe', 2, 'Deluxe king room with private balcony overlooking Marine Drive.', 12500.00, 15000.00, 17500.00, 2, 1, 'King', '["WiFi","55-inch Smart TV","Premium Mini Bar","In-Room Safe","Balcony","Soaking Tub","Rain Shower","Nespresso Machine","Bathrobes"]', 480, 'Sea View', TRUE, 1),
(1, '203', 'deluxe', 2, 'Spacious deluxe room with twin queen beds, perfect for families.', 11500.00, 13800.00, 16100.00, 4, 2, 'Queen', '["WiFi","55-inch Smart TV","Mini Bar","In-Room Safe","Nespresso Machine","Bathrobes","Extra Pillows"]', 460, 'Skyline', TRUE, 1),
(1, '204', 'suite', 2, 'Junior suite with separate living area, wet bar, and panoramic views.', 18000.00, 21600.00, 25200.00, 3, 1, 'King', '["WiFi","65-inch Smart TV","Wet Bar","In-Room Safe","Living Area","Nespresso Machine","Bathrobes","Turndown Service"]', 620, 'Panoramic City', TRUE, 1),
(1, '301', 'suite', 3, 'Executive suite with grand living room, dining area, and walk-in closet.', 24000.00, 28800.00, 33600.00, 3, 1, 'King', '["WiFi","65-inch Smart TV","Full Bar","In-Room Safe","Living Room","Dining Area","Jacuzzi Tub","Butler Service"]', 850, 'Marine Drive', TRUE, 1),
(1, '302', 'suite', 3, 'Family suite with two bedrooms and shared living space.', 26000.00, 31200.00, 36400.00, 5, 3, 'King + Twin', '["WiFi","Two 55-inch TVs","Mini Bar","Two Bedrooms","Living Room","Kitchenette","Child-Friendly"]', 920, 'Skyline', TRUE, 1),
(1, '303', 'presidential', 3, 'Penthouse suite with floor-to-ceiling windows, private terrace, grand piano, and butler service.', 55000.00, 65000.00, 75000.00, 4, 2, 'King', '["WiFi","75-inch OLED TV","Full Kitchen","Private Terrace","Grand Piano","Butler Service","Jacuzzi","Steam Shower","Dining Room"]', 1800, 'Panoramic Arabian Sea', TRUE, 1);

INSERT INTO activity_logs (user_id, action, description, ip_address, severity) VALUES
(1, 'database_initialized', 'Database schema created and initial data loaded', '127.0.0.1', 'info');

SELECT 'MySQL schema created successfully!' AS Status;
