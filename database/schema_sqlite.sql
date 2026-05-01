-- =====================================================
-- Hotel Management System Database Schema (SQLite Version)
-- Version: 1.0.0
-- =====================================================

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role TEXT CHECK(role IN ('user', 'admin')) DEFAULT 'user' NOT NULL,

    -- Security tracking
    failed_login_attempts INTEGER DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    last_login_ip VARCHAR(45),

    -- Email verification
    email_verified_at DATETIME NULL,
    email_verification_token VARCHAR(64) NULL,

    -- Password reset
    password_reset_token VARCHAR(64) NULL,
    password_reset_expires DATETIME NULL,

    -- Account status
    is_active BOOLEAN DEFAULT 1,

    -- Audit fields
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    is_deleted BOOLEAN DEFAULT 0,
    deleted_at DATETIME NULL,
    deleted_by INTEGER NULL,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- =====================================================
-- TABLE: hotels
-- =====================================================
CREATE TABLE IF NOT EXISTS hotels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    star_rating INTEGER CHECK (star_rating BETWEEN 1 AND 5),

    -- Hotel features
    amenities TEXT, -- JSON string
    check_in_time TIME DEFAULT '15:00:00',
    check_out_time TIME DEFAULT '11:00:00',

    -- Status
    is_active BOOLEAN DEFAULT 1,

    -- Audit fields
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    is_deleted BOOLEAN DEFAULT 0,
    deleted_at DATETIME NULL,

    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- =====================================================
-- TABLE: rooms
-- =====================================================
CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hotel_id INTEGER NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    room_type TEXT CHECK(room_type IN ('single', 'double', 'suite', 'deluxe', 'presidential')) NOT NULL,
    floor_number INTEGER,
    description TEXT,

    -- Pricing
    base_price DECIMAL(10,2) NOT NULL,
    weekend_price DECIMAL(10,2),
    peak_season_price DECIMAL(10,2),

    -- Capacity
    max_occupancy INTEGER DEFAULT 2,
    num_beds INTEGER DEFAULT 1,
    bed_type VARCHAR(50),

    -- Features
    amenities TEXT, -- JSON string
    square_feet INTEGER,
    view_type VARCHAR(50),

    -- Availability
    is_available BOOLEAN DEFAULT 1,
    maintenance_status TEXT CHECK(maintenance_status IN ('operational', 'maintenance', 'out_of_service')) DEFAULT 'operational',

    -- Media
    images TEXT, -- JSON array

    -- Audit fields
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NULL,
    is_deleted BOOLEAN DEFAULT 0,

    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    FOREIGN KEY (created_by) REFERENCES users(id),

    UNIQUE(hotel_id, room_number)
);

-- =====================================================
-- TABLE: bookings
-- =====================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,

    -- Dates
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Guest info
    num_guests INTEGER NOT NULL DEFAULT 1,
    special_requests TEXT,

    -- Pricing
    base_price DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL,

    -- Status
    status TEXT CHECK(status IN ('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show')) DEFAULT 'pending',
    payment_status TEXT CHECK(payment_status IN ('unpaid', 'partial', 'paid', 'refunded')) DEFAULT 'unpaid',

    -- Cancellation
    cancelled_at DATETIME NULL,
    cancelled_by INTEGER NULL,
    cancellation_reason TEXT NULL,

    -- Audit
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NULL,
    is_deleted BOOLEAN DEFAULT 0,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- =====================================================
-- TABLE: sessions
-- =====================================================
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INTEGER NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    csrf_token VARCHAR(64),
    payload TEXT,
    last_activity INTEGER NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- =====================================================
-- TABLE: activity_logs
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data TEXT,
    severity TEXT CHECK(severity IN ('info', 'warning', 'error', 'critical')) DEFAULT 'info',
    is_security_event BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

-- =====================================================
-- INSERT INITIAL DATA
-- =====================================================

-- Insert admin user (password: admin123 - will be hashed by PasswordMigration)
INSERT INTO users (username, email, password, full_name, role, email_verified_at, is_active) VALUES
('admin', 'admin@grandplaza.in', 'admin123', 'System Administrator', 'admin', datetime('now'), 1);

-- Insert regular user (password: user123)
INSERT INTO users (username, email, password, full_name, phone, role, email_verified_at, is_active, created_by) VALUES
('user1', 'user1@example.com', 'user123', 'Rahul Sharma', '+91-98765-43210', 'user', datetime('now'), 1, 1);

-- Insert the single hotel with rich data
INSERT INTO hotels (name, description, address, city, state, country, zip_code, phone, email, website, star_rating, amenities, check_in_time, check_out_time, is_active, created_by) VALUES
('Grand Plaza Hotel & Resort',
 'Experience timeless elegance at the Grand Plaza Hotel & Resort, a landmark 5-star property on the iconic Marine Drive in Mumbai. Featuring world-class dining, a rooftop infinity pool, a full-service spa, and breathtaking Arabian Sea views, our hotel offers an unparalleled blend of luxury and comfort. Whether you are visiting for business or leisure, our dedicated staff ensures every moment of your stay is exceptional.',
 'Marine Drive, Nariman Point',
 'Mumbai', 'Maharashtra', 'India', '400021',
 '+91-22-6789-0100',
 'reservations@grandplaza.in',
 'https://www.grandplaza.in',
 5,
 '["WiFi","Pool","Gym","Spa","Restaurant","Bar","Room Service","Valet Parking","Concierge","Business Center","Laundry","Conference Room","Airport Shuttle"]',
 '15:00:00', '11:00:00',
 1, 1);

-- Insert rooms across 3 floors (11 rooms total)
INSERT INTO rooms (hotel_id, room_number, room_type, floor_number, description, base_price, weekend_price, peak_season_price, max_occupancy, num_beds, bed_type, amenities, square_feet, view_type, is_available, created_by) VALUES

-- Floor 1: Standard rooms
(1, '101', 'single', 1,
 'Comfortable single room with modern furnishings and a cozy workspace. Ideal for solo business travelers.',
 4500.00, 5500.00, 6500.00, 1, 1, 'Queen',
 '["WiFi","40-inch TV","Mini Bar","In-Room Safe","Work Desk","Coffee Maker","Iron & Board"]',
 280, 'City', 1, 1),

(1, '102', 'single', 1,
 'Well-appointed single room featuring a plush queen bed and marble-accented bathroom.',
 4900.00, 5900.00, 6900.00, 1, 1, 'Queen',
 '["WiFi","40-inch TV","Mini Bar","In-Room Safe","Work Desk","Coffee Maker","Rainfall Shower"]',
 295, 'Courtyard', 1, 1),

(1, '103', 'double', 1,
 'Spacious double room perfect for couples, with a king bed and sitting area.',
 7500.00, 9000.00, 10500.00, 2, 1, 'King',
 '["WiFi","50-inch TV","Mini Bar","In-Room Safe","Sitting Area","Coffee Maker","Bathrobes"]',
 380, 'City', 1, 1),

(1, '104', 'double', 1,
 'Corner double room offering extra natural light and city views from two windows.',
 8500.00, 10200.00, 11900.00, 2, 1, 'King',
 '["WiFi","50-inch TV","Mini Bar","In-Room Safe","Sitting Area","Coffee Maker","Bathrobes","Corner Views"]',
 410, 'City', 1, 1),

-- Floor 2: Deluxe rooms
(1, '201', 'double', 2,
 'Premium double room on a higher floor with upgraded linens and a Nespresso machine.',
 9500.00, 11400.00, 13300.00, 2, 1, 'King',
 '["WiFi","55-inch Smart TV","Premium Mini Bar","In-Room Safe","Nespresso Machine","Bathrobes","Slippers"]',
 400, 'Skyline', 1, 1),

(1, '202', 'deluxe', 2,
 'Deluxe king room with a private balcony overlooking Marine Drive. Features a soaking tub and separate rain shower.',
 12500.00, 15000.00, 17500.00, 2, 1, 'King',
 '["WiFi","55-inch Smart TV","Premium Mini Bar","In-Room Safe","Balcony","Soaking Tub","Rain Shower","Nespresso Machine","Bathrobes"]',
 480, 'Sea View', 1, 1),

(1, '203', 'deluxe', 2,
 'Spacious deluxe room with twin queen beds, perfect for friends traveling together or families.',
 11500.00, 13800.00, 16100.00, 4, 2, 'Queen',
 '["WiFi","55-inch Smart TV","Mini Bar","In-Room Safe","Nespresso Machine","Bathrobes","Extra Pillows"]',
 460, 'Skyline', 1, 1),

(1, '204', 'suite', 2,
 'Junior suite with a separate living area, wet bar, and panoramic city views.',
 18000.00, 21600.00, 25200.00, 3, 1, 'King',
 '["WiFi","65-inch Smart TV","Wet Bar","In-Room Safe","Living Area","Nespresso Machine","Bathrobes","Slippers","Turndown Service"]',
 620, 'Panoramic City', 1, 1),

-- Floor 3: Premium suites
(1, '301', 'suite', 3,
 'Executive suite featuring a grand living room, dining area, and master bedroom with walk-in closet.',
 24000.00, 28800.00, 33600.00, 3, 1, 'King',
 '["WiFi","65-inch Smart TV","Full Bar","In-Room Safe","Living Room","Dining Area","Walk-In Closet","Jacuzzi Tub","Bathrobes","Butler Service"]',
 850, 'Marine Drive', 1, 1),

(1, '302', 'suite', 3,
 'Family suite with two bedrooms connected by a shared living space. Ideal for families with children.',
 26000.00, 31200.00, 36400.00, 5, 3, 'King + Twin',
 '["WiFi","Two 55-inch TVs","Mini Bar","In-Room Safe","Two Bedrooms","Living Room","Kitchenette","Bathrobes","Child-Friendly Amenities"]',
 920, 'Skyline', 1, 1),

(1, '303', 'presidential', 3,
 'The crown jewel of Grand Plaza. A sprawling penthouse suite with floor-to-ceiling windows, a private terrace, grand piano, and dedicated butler service. Features a master suite, guest bedroom, full kitchen, and formal dining room.',
 55000.00, 65000.00, 75000.00, 4, 2, 'King',
 '["WiFi","75-inch OLED TV","Full Kitchen","In-Room Safe","Private Terrace","Grand Piano","Butler Service","Jacuzzi","Steam Shower","Walk-In Closet","Dining Room","Study","Bathrobes","Slippers","Premium Minibar"]',
 1800, 'Panoramic Arabian Sea', 1, 1);

-- Log initial setup
INSERT INTO activity_logs (user_id, action, description, ip_address, severity) VALUES
(1, 'database_initialized', 'Database schema created and initial data loaded', '127.0.0.1', 'info');
