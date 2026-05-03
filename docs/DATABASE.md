# Database Documentation

Hotel Management System -- Definitive Database Reference

---

## 1. Overview

| Property            | Value                                            |
|---------------------|--------------------------------------------------|
| **Primary DBMS**    | MySQL 9.x (InnoDB engine, utf8mb4_unicode_ci)    |
| **Dev Fallback**    | SQLite 3 (file-based, `storage/database.sqlite`) |
| **Tables**          | 6 (`users`, `hotels`, `rooms`, `bookings`, `sessions`, `activity_logs`) |
| **Charset**         | `utf8mb4` (full Unicode + emoji support)         |
| **Collation**       | `utf8mb4_unicode_ci`                             |
| **ORM**             | None -- raw PDO with repository pattern          |
| **Driver Selector** | `$_ENV['DB_CONNECTION']` (`mysql` or `sqlite`)   |

The application uses a **dual-driver abstraction** implemented in `src/Core/Database.php`. A singleton `Database` class wraps PDO, auto-detecting the configured driver and providing helper methods (`now()`, `today()`, `dateAdd()`, `dateSub()`) that emit the correct SQL dialect. Repository classes extend `BaseRepository`, which provides standard CRUD operations with prepared statements, soft-delete filtering, and pagination support.

**Connection configuration** lives in `config/database.php` and reads from `.env`:

```
DB_CONNECTION=mysql        # or sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotel_management_db
DB_USERNAME=hotel_app
DB_PASSWORD=<secret>
```

---

## 2. Entity Relationship Diagram

```
+----------------+         +----------------+         +----------------+
|     users      |         |     hotels     |         |     rooms      |
|----------------|         |----------------|         |----------------|
| PK id          |----+    | PK id          |    +----| PK id          |
| username (UQ)  |    |    | name           |    |    | FK hotel_id    |----->| hotels.id
| email (UQ)     |    |    | description    |    |    | room_number    |
| password       |    |    | address        |    |    | room_type      |
| full_name      |    |    | city           |    |    | floor_number   |
| phone          |    |    | state          |    |    | base_price     |
| role           |    |    | country        |    |    | weekend_price  |
| failed_login_  |    |    | zip_code       |    |    | peak_season_   |
|   attempts     |    |    | phone          |    |    |   price        |
| locked_until   |    |    | email          |    |    | max_occupancy  |
| last_login     |    |    | website        |    |    | num_beds       |
| last_login_ip  |    |    | star_rating    |    |    | bed_type       |
| email_verified_|    |    | amenities (J)  |    |    | amenities (J)  |
|   at           |    |    | check_in_time  |    |    | square_feet    |
| email_verifica-|    |    | check_out_time |    |    | view_type      |
|   tion_token   |    |    | is_active      |    |    | is_available   |
| password_reset_|    |    | created_at     |    |    | maintenance_   |
|   token        |    |    | updated_at     |    |    |   status       |
| password_reset_|    |    | FK created_by  |-+  |    | images (J)     |
|   expires      |    |    | FK updated_by  |-+  |    | created_at     |
| is_active      |    |    | is_deleted     |  | |    | updated_at     |
| created_at     |    |    | deleted_at     |  | |    | FK created_by  |--+
| updated_at     |    |    +----------------+  | |    | is_deleted     |  |
| FK created_by  |--+ |                        | |    +----------------+  |
| FK updated_by  |--+ |                        | |        |              |
| is_deleted     |  |  |                       | |    UQ(hotel_id,       |
| deleted_at     |  |  |                       | |      room_number)     |
| FK deleted_by  |--+  |                       | |                       |
+----------------+  |  |                       | |                       |
     |    |    |    |  |                       | |                       |
     |    |    +----+--+-----------------------+-+-----------------------+
     |    |         (self-referencing FKs)        |
     |    |                                       |
     |    |    +------------------+               |
     |    |    |    bookings      |               |
     |    |    |------------------|               |
     |    +----| FK user_id      |               |
     |         | FK room_id      |---------------+
     |         | PK id           |
     |         | booking_ref (UQ)|
     |         | check_in        |
     |         | check_out       |
     |         | booking_date    |
     |         | num_guests      |
     |         | special_requests|
     |         | base_price      |
     |         | tax_amount      |
     |         | discount_amount |
     |         | total_price     |
     |         | status          |
     |         | payment_status  |
     |         | cancelled_at    |
     |    +----| FK cancelled_by |
     |    |    | cancellation_   |
     |    |    |   reason        |
     |    |    | created_at      |
     |    |    | updated_at      |
     |    +----| FK created_by   |
     |         | is_deleted      |
     |         +------------------+
     |
     |    +------------------+          +------------------+
     |    |    sessions      |          |  activity_logs   |
     |    |------------------|          |------------------|
     +----| FK user_id       |     +----| FK user_id       |
          | PK id (VARCHAR)  |     |    | PK id (BIGINT)   |
          | ip_address       |     |    | action           |
          | user_agent       |     |    | entity_type      |
          | csrf_token       |     |    | entity_id        |
          | payload          |     |    | description      |
          | last_activity    |     |    | ip_address       |
          +------------------+     |    | user_agent       |
                                   |    | request_data     |
                                   |    | severity         |
                                   |    | is_security_event|
                                   |    | created_at       |
                                   |    +------------------+
```

### Cardinality Summary

```
users  1 ------< *  bookings  * >------  1  rooms  * >------  1  hotels
users  1 ------< *  sessions          (CASCADE on delete)
users  1 ------< *  activity_logs     (SET NULL on delete)
hotels.created_by  >------  0..1  users  (SET NULL on delete)
hotels.updated_by  >------  0..1  users  (SET NULL on delete)
rooms.created_by   >------  0..1  users  (SET NULL on delete)
rooms.hotel_id     >------  1     hotels (RESTRICT -- no ON DELETE clause)
```

---

## 3. Table Definitions

### 3.1 `users`

**Purpose**: Stores all user accounts -- both guests and administrators. Tracks authentication state, email verification, password resets, and account lockout.

| Column                     | MySQL Type              | SQLite Type     | Nullable | Default             | Description                                      |
|----------------------------|-------------------------|-----------------|----------|---------------------|--------------------------------------------------|
| `id`                       | INT UNSIGNED AUTO_INCREMENT | INTEGER AUTOINCREMENT | NO  | auto                | Primary key                                      |
| `username`                 | VARCHAR(50)             | VARCHAR(50)     | NO       | --                  | Unique login name                                |
| `email`                    | VARCHAR(100)            | VARCHAR(100)    | NO       | --                  | Unique email address                             |
| `password`                 | VARCHAR(255)            | VARCHAR(255)    | NO       | --                  | Bcrypt-hashed password (cost 12)                 |
| `full_name`                | VARCHAR(100)            | VARCHAR(100)    | NO       | --                  | Display name                                     |
| `phone`                    | VARCHAR(20)             | VARCHAR(20)     | YES      | NULL                | Phone number (Indian format supported)           |
| `role`                     | ENUM('user','admin')    | TEXT + CHECK    | NO       | 'user'              | Authorization role                               |
| `failed_login_attempts`    | INT UNSIGNED            | INTEGER         | YES      | 0                   | Counter for brute-force protection               |
| `locked_until`             | DATETIME                | DATETIME        | YES      | NULL                | Account lockout expiry timestamp                 |
| `last_login`               | DATETIME                | DATETIME        | YES      | NULL                | Timestamp of most recent successful login        |
| `last_login_ip`            | VARCHAR(45)             | VARCHAR(45)     | YES      | NULL                | IPv4/IPv6 address of last login                  |
| `email_verified_at`        | DATETIME                | DATETIME        | YES      | NULL                | When email was verified (NULL = unverified)       |
| `email_verification_token` | VARCHAR(64)             | VARCHAR(64)     | YES      | NULL                | One-time token for email verification            |
| `password_reset_token`     | VARCHAR(64)             | VARCHAR(64)     | YES      | NULL                | One-time token for password reset                |
| `password_reset_expires`   | DATETIME                | DATETIME        | YES      | NULL                | Expiry for password reset token                  |
| `is_active`                | BOOLEAN                 | BOOLEAN         | YES      | TRUE / 1            | Account enabled flag                             |
| `created_at`               | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP   | Record creation time                             |
| `updated_at`               | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP (MySQL: ON UPDATE) | Last modification time |
| `created_by`               | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id -- who created this record        |
| `updated_by`               | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id -- who last updated this record   |
| `is_deleted`               | BOOLEAN                 | BOOLEAN         | YES      | FALSE / 0           | Soft delete flag                                 |
| `deleted_at`               | DATETIME                | DATETIME        | YES      | NULL                | When soft-deleted                                |
| `deleted_by`               | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id -- who deleted this record        |

**Primary Key**: `id`

**Unique Constraints**: `username`, `email`

**Foreign Keys**:
| Column       | References   | ON DELETE  |
|--------------|-------------|------------|
| `created_by` | `users(id)` | SET NULL (SQLite only; MySQL schema omits this FK) |
| `updated_by` | `users(id)` | SET NULL (SQLite only; MySQL schema omits this FK) |
| `deleted_by` | `users(id)` | SET NULL (SQLite only; MySQL schema omits this FK) |

> Note: The MySQL schema does not declare self-referencing FKs on `created_by`/`updated_by`/`deleted_by` for `users`, but the SQLite schema does. The columns exist in both.

**Indexes** (MySQL):
- `idx_username` on `(username)`
- `idx_email` on `(email)`
- `idx_role` on `(role)`

**Indexes** (SQLite):
- `idx_users_username` on `(username)`
- `idx_users_email` on `(email)`
- `idx_users_role` on `(role)`

---

### 3.2 `hotels`

**Purpose**: Stores hotel properties. Currently a single-hotel system (Grand Plaza Hotel & Resort, id=1).

| Column           | MySQL Type              | SQLite Type     | Nullable | Default             | Description                                  |
|------------------|-------------------------|-----------------|----------|---------------------|----------------------------------------------|
| `id`             | INT UNSIGNED AUTO_INCREMENT | INTEGER AUTOINCREMENT | NO  | auto                | Primary key                                  |
| `name`           | VARCHAR(100)            | VARCHAR(100)    | NO       | --                  | Hotel display name                           |
| `description`    | TEXT                    | TEXT            | YES      | NULL                | Marketing description                        |
| `address`        | TEXT                    | TEXT            | NO       | --                  | Street address                               |
| `city`           | VARCHAR(50)             | VARCHAR(50)     | NO       | --                  | City                                         |
| `state`          | VARCHAR(50)             | VARCHAR(50)     | YES      | NULL                | State / province                             |
| `country`        | VARCHAR(50)             | VARCHAR(50)     | NO       | 'India'             | Country (defaults to India)                  |
| `zip_code`       | VARCHAR(10)             | VARCHAR(10)     | YES      | NULL                | Postal code                                  |
| `phone`          | VARCHAR(20)             | VARCHAR(20)     | YES      | NULL                | Contact phone                                |
| `email`          | VARCHAR(100)            | VARCHAR(100)    | YES      | NULL                | Reservation email                            |
| `website`        | VARCHAR(255)            | VARCHAR(255)    | YES      | NULL                | Hotel website URL                            |
| `star_rating`    | TINYINT                 | INTEGER         | YES      | NULL                | 1-5 star rating                              |
| `amenities`      | JSON                    | TEXT            | YES      | NULL                | JSON array of amenity strings                |
| `check_in_time`  | TIME                    | TIME            | YES      | '15:00:00'          | Default check-in time                        |
| `check_out_time` | TIME                    | TIME            | YES      | '11:00:00'          | Default check-out time                       |
| `is_active`      | BOOLEAN                 | BOOLEAN         | YES      | TRUE / 1            | Whether hotel is publicly listed             |
| `created_at`     | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP   | Record creation time                         |
| `updated_at`     | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP (MySQL: ON UPDATE) | Last modification time |
| `created_by`     | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id                               |
| `updated_by`     | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id                               |
| `is_deleted`     | BOOLEAN                 | BOOLEAN         | YES      | FALSE / 0           | Soft delete flag                             |
| `deleted_at`     | DATETIME                | DATETIME        | YES      | NULL                | When soft-deleted                            |

**Primary Key**: `id`

**Foreign Keys**:
| Column       | References   | ON DELETE  |
|--------------|-------------|------------|
| `created_by` | `users(id)` | SET NULL   |
| `updated_by` | `users(id)` | SET NULL   |

**CHECK Constraints**: `star_rating BETWEEN 1 AND 5`

---

### 3.3 `rooms`

**Purpose**: Stores individual rooms belonging to a hotel. Tracks pricing tiers, capacity, amenities, and maintenance status.

| Column               | MySQL Type              | SQLite Type     | Nullable | Default         | Description                                       |
|----------------------|-------------------------|-----------------|----------|-----------------|---------------------------------------------------|
| `id`                 | INT UNSIGNED AUTO_INCREMENT | INTEGER AUTOINCREMENT | NO  | auto            | Primary key                                       |
| `hotel_id`           | INT UNSIGNED            | INTEGER         | NO       | --              | FK to hotels.id                                   |
| `room_number`        | VARCHAR(10)             | VARCHAR(10)     | NO       | --              | Room number (e.g., '101', '303')                  |
| `room_type`          | ENUM('single','double','suite','deluxe','presidential') | TEXT + CHECK | NO | -- | Room category                     |
| `floor_number`       | INT                     | INTEGER         | YES      | NULL            | Physical floor                                    |
| `description`        | TEXT                    | TEXT            | YES      | NULL            | Room description                                  |
| `base_price`         | DECIMAL(10,2)           | DECIMAL(10,2)   | NO       | --              | Standard nightly rate in INR                      |
| `weekend_price`      | DECIMAL(10,2)           | DECIMAL(10,2)   | YES      | NULL            | Weekend nightly rate in INR                       |
| `peak_season_price`  | DECIMAL(10,2)           | DECIMAL(10,2)   | YES      | NULL            | Peak season nightly rate in INR                   |
| `max_occupancy`      | INT UNSIGNED            | INTEGER         | YES      | 2               | Maximum guests allowed                            |
| `num_beds`           | INT UNSIGNED            | INTEGER         | YES      | 1               | Number of beds                                    |
| `bed_type`           | VARCHAR(50)             | VARCHAR(50)     | YES      | NULL            | Bed configuration (e.g., 'King', 'Queen', 'King + Twin') |
| `amenities`          | JSON                    | TEXT            | YES      | NULL            | JSON array of in-room amenities                   |
| `square_feet`        | INT UNSIGNED            | INTEGER         | YES      | NULL            | Room size in square feet                          |
| `view_type`          | VARCHAR(50)             | VARCHAR(50)     | YES      | NULL            | View description (e.g., 'Sea View', 'Skyline')   |
| `is_available`       | BOOLEAN                 | BOOLEAN         | YES      | TRUE / 1        | General availability flag                         |
| `maintenance_status` | ENUM('operational','maintenance','out_of_service') | TEXT + CHECK | YES | 'operational' | Current maintenance state            |
| `images`             | JSON                    | TEXT            | YES      | NULL            | JSON array of image URLs                          |
| `created_at`         | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP | Record creation time                            |
| `updated_at`         | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP (MySQL: ON UPDATE) | Last modification time |
| `created_by`         | INT UNSIGNED            | INTEGER         | YES      | NULL            | FK to users.id                                    |
| `is_deleted`         | BOOLEAN                 | BOOLEAN         | YES      | FALSE / 0       | Soft delete flag                                  |

**Primary Key**: `id`

**Unique Constraints**: `(hotel_id, room_number)` -- no two rooms in the same hotel share a number.

**Foreign Keys**:
| Column       | References    | ON DELETE  |
|--------------|--------------|------------|
| `hotel_id`   | `hotels(id)` | RESTRICT (implicit -- no ON DELETE clause) |
| `created_by` | `users(id)`  | SET NULL   |

**Indexes** (MySQL):
- `idx_hotel_id` on `(hotel_id)`
- `idx_room_type` on `(room_type)`
- `idx_is_available` on `(is_available)`

**CHECK Constraints**: `room_type IN ('single', 'double', 'suite', 'deluxe', 'presidential')`, `maintenance_status IN ('operational', 'maintenance', 'out_of_service')`

---

### 3.4 `bookings`

**Purpose**: Central transactional table. Records every reservation with full pricing breakdown, status tracking, and cancellation audit trail.

| Column                | MySQL Type              | SQLite Type     | Nullable | Default             | Description                                      |
|-----------------------|-------------------------|-----------------|----------|---------------------|--------------------------------------------------|
| `id`                  | INT UNSIGNED AUTO_INCREMENT | INTEGER AUTOINCREMENT | NO  | auto                | Primary key                                      |
| `booking_reference`   | VARCHAR(20)             | VARCHAR(20)     | NO       | --                  | Unique human-readable ref (e.g., 'BK6627A3F1')  |
| `user_id`             | INT UNSIGNED            | INTEGER         | NO       | --                  | FK to users.id -- the guest                      |
| `room_id`             | INT UNSIGNED            | INTEGER         | NO       | --                  | FK to rooms.id -- the reserved room              |
| `check_in`            | DATE                    | DATE            | NO       | --                  | Arrival date                                     |
| `check_out`           | DATE                    | DATE            | NO       | --                  | Departure date                                   |
| `booking_date`        | DATETIME                | DATETIME        | YES      | CURRENT_TIMESTAMP   | When the booking was placed                      |
| `num_guests`          | INT UNSIGNED            | INTEGER         | NO       | 1                   | Number of guests                                 |
| `special_requests`    | TEXT                    | TEXT            | YES      | NULL                | Free-text guest requests                         |
| `base_price`          | DECIMAL(10,2)           | DECIMAL(10,2)   | NO       | --                  | Room charges before tax/discount                 |
| `tax_amount`          | DECIMAL(10,2)           | DECIMAL(10,2)   | YES      | 0                   | Tax charges in INR                               |
| `discount_amount`     | DECIMAL(10,2)           | DECIMAL(10,2)   | YES      | 0                   | Applied discount in INR                          |
| `total_price`         | DECIMAL(10,2)           | DECIMAL(10,2)   | NO       | --                  | Final amount (base + tax - discount)             |
| `status`              | ENUM(6 values)          | TEXT + CHECK    | YES      | 'pending'           | Booking lifecycle state                          |
| `payment_status`      | ENUM(4 values)          | TEXT + CHECK    | YES      | 'unpaid'            | Payment lifecycle state                          |
| `cancelled_at`        | DATETIME                | DATETIME        | YES      | NULL                | When cancellation occurred                       |
| `cancelled_by`        | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id -- who cancelled                  |
| `cancellation_reason` | TEXT                    | TEXT            | YES      | NULL                | Reason for cancellation                          |
| `created_at`          | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP   | Record creation time                             |
| `updated_at`          | TIMESTAMP               | DATETIME        | YES      | CURRENT_TIMESTAMP (MySQL: ON UPDATE) | Last modification time |
| `created_by`          | INT UNSIGNED            | INTEGER         | YES      | NULL                | FK to users.id                                   |
| `is_deleted`          | BOOLEAN                 | BOOLEAN         | YES      | FALSE / 0           | Soft delete flag                                 |

**Primary Key**: `id`

**Unique Constraints**: `booking_reference`

**Foreign Keys**:
| Column         | References    | ON DELETE  |
|----------------|--------------|------------|
| `user_id`      | `users(id)`  | RESTRICT (implicit) |
| `room_id`      | `rooms(id)`  | RESTRICT (implicit) |
| `cancelled_by` | `users(id)`  | SET NULL   |
| `created_by`   | `users(id)`  | SET NULL   |

**Indexes** (MySQL):
- `idx_user_id` on `(user_id)`
- `idx_room_id` on `(room_id)`
- `idx_check_in` on `(check_in)`
- `idx_status` on `(status)`
- `idx_booking_ref` on `(booking_reference)`

**CHECK Constraints**:
- `status IN ('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show')`
- `payment_status IN ('unpaid', 'partial', 'paid', 'refunded')`

---

### 3.5 `sessions`

**Purpose**: Server-side session storage. Maps session IDs to users, stores CSRF tokens, and tracks activity for session expiry.

| Column          | MySQL Type    | SQLite Type   | Nullable | Default | Description                               |
|-----------------|---------------|---------------|----------|---------|-------------------------------------------|
| `id`            | VARCHAR(255)  | VARCHAR(255)  | NO       | --      | Session ID string (primary key)           |
| `user_id`       | INT UNSIGNED  | INTEGER       | YES      | NULL    | FK to users.id (NULL = anonymous session) |
| `ip_address`    | VARCHAR(45)   | VARCHAR(45)   | YES      | NULL    | Client IP address                         |
| `user_agent`    | TEXT          | TEXT          | YES      | NULL    | Browser user-agent string                 |
| `csrf_token`    | VARCHAR(64)   | VARCHAR(64)   | YES      | NULL    | CSRF protection token                     |
| `payload`       | TEXT          | TEXT          | YES      | NULL    | Serialized session data                   |
| `last_activity` | INT           | INTEGER       | NO       | --      | Unix timestamp of last activity           |

**Primary Key**: `id` (VARCHAR -- not auto-increment)

**Foreign Keys**:
| Column    | References   | ON DELETE  |
|-----------|-------------|------------|
| `user_id` | `users(id)` | CASCADE (MySQL) / implicit (SQLite) |

> Note: This is the only table with `ON DELETE CASCADE`. When a user is hard-deleted, their sessions are automatically removed.

**Indexes**:
- `idx_user_id` / `idx_sessions_user_id` on `(user_id)`
- `idx_last_activity` / `idx_sessions_last_activity` on `(last_activity)`

---

### 3.6 `activity_logs`

**Purpose**: Immutable audit trail. Records every significant action: HTTP requests, login attempts, admin operations, security events. Never soft-deleted.

| Column              | MySQL Type               | SQLite Type     | Nullable | Default             | Description                                      |
|---------------------|--------------------------|-----------------|----------|---------------------|--------------------------------------------------|
| `id`                | BIGINT UNSIGNED AUTO_INCREMENT | INTEGER AUTOINCREMENT | NO  | auto                | Primary key (BIGINT for high-volume logging)     |
| `user_id`           | INT UNSIGNED             | INTEGER         | YES      | NULL                | FK to users.id (NULL = anonymous/system action)  |
| `action`            | VARCHAR(100)             | VARCHAR(100)    | NO       | --                  | Action identifier (e.g., 'login_success', 'room_created') |
| `entity_type`       | VARCHAR(50)              | VARCHAR(50)     | YES      | NULL                | Type of affected entity ('user', 'room', 'booking', etc.) |
| `entity_id`         | INT UNSIGNED             | INTEGER         | YES      | NULL                | ID of the affected entity                        |
| `description`       | TEXT                     | TEXT            | YES      | NULL                | Human-readable description                       |
| `ip_address`        | VARCHAR(45)              | VARCHAR(45)     | YES      | NULL                | Client IP address                                |
| `user_agent`        | TEXT                     | TEXT            | YES      | NULL                | Browser user-agent string                        |
| `request_data`      | TEXT                     | TEXT            | YES      | NULL                | Serialized request metadata                      |
| `severity`          | ENUM('info','warning','error','critical') | TEXT + CHECK | YES | 'info'       | Log severity level                               |
| `is_security_event` | BOOLEAN                  | BOOLEAN         | YES      | FALSE / 0           | Flag for security-relevant events                |
| `created_at`        | TIMESTAMP                | DATETIME        | YES      | CURRENT_TIMESTAMP   | When the event occurred                          |

**Primary Key**: `id`

**Foreign Keys**:
| Column    | References   | ON DELETE  |
|-----------|-------------|------------|
| `user_id` | `users(id)` | SET NULL   |

**Indexes**:
- `idx_user_id` / `idx_activity_logs_user_id` on `(user_id)`
- `idx_action` / `idx_activity_logs_action` on `(action)`
- `idx_created_at` / `idx_activity_logs_created_at` on `(created_at)`
- `idx_security` on `(is_security_event)` (MySQL only)

> Note: `activity_logs` does **not** have `is_deleted`, `updated_at`, or any soft-delete columns. Log entries are append-only and immutable.

---

## 4. Seed Data

Both schema files (`schema_mysql.sql` and `schema_sqlite.sql`) include identical seed data inserted at schema creation time.

### 4.1 Users (2 records)

| id | username | email                  | full_name              | role  | phone            | created_by |
|----|----------|------------------------|------------------------|-------|------------------|------------|
| 1  | `admin`  | admin@grandplaza.in    | System Administrator   | admin | --               | NULL       |
| 2  | `user1`  | user1@example.com      | Rahul Sharma           | user  | +91-98765-43210  | 1          |

**Passwords**: Seeded as plaintext (`admin123`, `user123`) and then hashed to bcrypt (cost 12) by running `PasswordMigration.php`. After migration, passwords match the `$2y$12$...` pattern. The migration script detects unhashed passwords (`WHERE password NOT LIKE '$2y$%'`) and rehashes them in place.

### 4.2 Hotels (1 record)

| id | name                          | city   | state       | country | star_rating | zip_code |
|----|-------------------------------|--------|-------------|---------|-------------|----------|
| 1  | Grand Plaza Hotel & Resort    | Mumbai | Maharashtra | India   | 5           | 400021   |

- **Address**: Marine Drive, Nariman Point
- **Phone**: +91-22-6789-0100
- **Email**: reservations@grandplaza.in
- **Website**: https://www.grandplaza.in
- **Check-in/out**: 15:00 / 11:00
- **Amenities** (13): WiFi, Pool, Gym, Spa, Restaurant, Bar, Room Service, Valet Parking, Concierge, Business Center, Laundry, Conference Room, Airport Shuttle

### 4.3 Rooms (11 records across 3 floors)

**Floor 1 -- Standard Rooms**

| Room  | Type   | Beds       | Max Occ. | Base (INR) | Weekend | Peak   | Sq Ft | View      |
|-------|--------|------------|----------|------------|---------|--------|-------|-----------|
| 101   | single | 1 Queen    | 1        | 4,500      | 5,500   | 6,500  | 280   | City      |
| 102   | single | 1 Queen    | 1        | 4,900      | 5,900   | 6,900  | 295   | Courtyard |
| 103   | double | 1 King     | 2        | 7,500      | 9,000   | 10,500 | 380   | City      |
| 104   | double | 1 King     | 2        | 8,500      | 10,200  | 11,900 | 410   | City      |

**Floor 2 -- Deluxe Rooms & Junior Suite**

| Room  | Type   | Beds       | Max Occ. | Base (INR) | Weekend | Peak   | Sq Ft | View           |
|-------|--------|------------|----------|------------|---------|--------|-------|----------------|
| 201   | double | 1 King     | 2        | 9,500      | 11,400  | 13,300 | 400   | Skyline        |
| 202   | deluxe | 1 King     | 2        | 12,500     | 15,000  | 17,500 | 480   | Sea View       |
| 203   | deluxe | 2 Queen    | 4        | 11,500     | 13,800  | 16,100 | 460   | Skyline        |
| 204   | suite  | 1 King     | 3        | 18,000     | 21,600  | 25,200 | 620   | Panoramic City |

**Floor 3 -- Premium Suites**

| Room  | Type          | Beds          | Max Occ. | Base (INR) | Weekend | Peak   | Sq Ft | View                 |
|-------|---------------|---------------|----------|------------|---------|--------|-------|----------------------|
| 301   | suite         | 1 King        | 3        | 24,000     | 28,800  | 33,600 | 850   | Marine Drive         |
| 302   | suite         | 3 King + Twin | 5        | 26,000     | 31,200  | 36,400 | 920   | Skyline              |
| 303   | presidential  | 2 King        | 4        | 55,000     | 65,000  | 75,000 | 1,800 | Panoramic Arabian Sea|

### 4.4 Activity Logs (1 record)

A single seed log entry records the database initialization:
```
user_id=1, action='database_initialized', severity='info'
```

---

## 5. Design Patterns

### 5.1 Soft Delete

Every entity table (`users`, `hotels`, `rooms`, `bookings`) uses a soft-delete pattern:

```
is_deleted  BOOLEAN DEFAULT FALSE
deleted_at  DATETIME NULL
```

The `users` table additionally tracks `deleted_by` (FK to `users.id`).

**All repository queries filter** with `WHERE is_deleted = 0`. The `BaseRepository::delete()` method sets the flag rather than issuing `DELETE`:

```php
// BaseRepository::delete()
$sql = "UPDATE {$this->table} SET is_deleted = 1, deleted_at = {$now} WHERE id = ?";
```

A `hardDelete()` method exists for cases that genuinely require physical removal (e.g., GDPR compliance) but is not used in normal workflows.

> The `sessions` and `activity_logs` tables do **not** use soft delete. Sessions are physically deleted on expiry; activity logs are append-only.

### 5.2 Audit Fields

All entity tables share these audit columns:

| Column       | Purpose                                          |
|--------------|--------------------------------------------------|
| `created_at` | Automatically set to `CURRENT_TIMESTAMP`         |
| `updated_at` | MySQL auto-updates via `ON UPDATE CURRENT_TIMESTAMP`; SQLite requires manual update |
| `created_by` | FK to `users.id` -- the actor who created the record |

Hotels also have `updated_by`. Users additionally have `updated_by` and `deleted_by`.

### 5.3 JSON Columns

Three columns store JSON arrays:

| Table   | Column      | MySQL Type | SQLite Type | Example Content                              |
|---------|-------------|------------|-------------|----------------------------------------------|
| `hotels`| `amenities` | JSON       | TEXT        | `["WiFi","Pool","Gym","Spa","Restaurant"]`   |
| `rooms` | `amenities` | JSON       | TEXT        | `["WiFi","55-inch Smart TV","Mini Bar"]`     |
| `rooms` | `images`    | JSON       | TEXT        | JSON array of image URLs (not seeded)        |

In MySQL, these use the native `JSON` type with validation. In SQLite, they are plain `TEXT` storing JSON strings -- the application is responsible for encoding/decoding.

### 5.4 Booking Reference

Booking references are auto-generated in `Booking::generateReference()`:

```php
public static function generateReference(): string
{
    return 'BK' . strtoupper(substr(uniqid(), -8));
}
```

This produces references like `BK6627A3F1` -- the prefix `BK` followed by 8 uppercase hex characters derived from the last 8 characters of PHP's `uniqid()` (microsecond-based). The `booking_reference` column has a `UNIQUE` constraint to prevent collisions.

### 5.5 Booking Lifecycle

```
                        +---> cancelled
                        |
pending ---> confirmed --+--> checked_in ---> checked_out
                        |
                        +---> no_show
```

**Status values**: `pending`, `confirmed`, `checked_in`, `checked_out`, `cancelled`, `no_show`

- Bookings start as `pending` at creation
- Admin confirms to `confirmed`
- On arrival: `checked_in`
- On departure: `checked_out`
- Can be cancelled from `pending` or `confirmed` (records `cancelled_at`, `cancelled_by`, `cancellation_reason`)
- `no_show` for guests who never arrive

### 5.6 Payment Lifecycle

```
unpaid ---> partial ---> paid
              |            |
              +----> refunded <----+
```

**Payment status values**: `unpaid`, `partial`, `paid`, `refunded`

---

## 6. Query Patterns

### 6.1 Room Availability Check (Excluding Conflicting Bookings)

Used in `RoomController::index()` to find rooms available for a given date range. The subquery excludes rooms that have any non-cancelled, non-no-show booking overlapping the requested dates:

```sql
SELECT r.*, h.name AS hotel_name, h.check_in_time, h.check_out_time
FROM rooms r
JOIN hotels h ON r.hotel_id = h.id
WHERE r.is_deleted = 0
  AND r.is_available = 1
  AND r.maintenance_status = 'operational'
  AND r.id NOT IN (
      SELECT b.room_id FROM bookings b
      WHERE b.status NOT IN ('cancelled', 'no_show')
        AND b.is_deleted = 0
        AND b.check_in < ?    -- ? = requested check_out
        AND b.check_out > ?   -- ? = requested check_in
  )
ORDER BY r.base_price ASC
```

The overlap logic: an existing booking conflicts if `existing.check_in < new.check_out AND existing.check_out > new.check_in`.

### 6.2 Booking with JOIN (Room + Hotel + User Details)

Used in `BookingRepository::findByUser()` and `AdminController::bookings()`:

```sql
-- User's bookings with room and hotel context
SELECT b.*, r.room_number, r.room_type, h.name AS hotel_name, h.city
FROM bookings b
JOIN rooms r ON b.room_id = r.id
JOIN hotels h ON r.hotel_id = h.id
WHERE b.user_id = ? AND b.is_deleted = 0
ORDER BY b.created_at DESC

-- Admin booking list with user details
SELECT b.*, u.username, u.full_name, u.email, r.room_number, r.room_type
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN rooms r ON b.room_id = r.id
WHERE b.is_deleted = 0
ORDER BY b.created_at DESC
LIMIT 100
```

### 6.3 Occupancy Rate Calculation

Used in `AdminController::dashboard()`:

```sql
-- Count distinct rooms currently occupied
SELECT COUNT(DISTINCT b.room_id) AS occupied
FROM bookings b
WHERE b.status IN ('confirmed', 'checked_in')
  AND b.is_deleted = 0
  AND b.check_in <= CURDATE()       -- or date('now') for SQLite
  AND b.check_out > CURDATE()
```

The PHP code then computes: `occupancyRate = (occupiedRooms / totalAvailableRooms) * 100`

### 6.4 Activity Log with User JOIN

Used in `AdminController::dashboard()` and the logs listing page:

```sql
-- Recent activity with username
SELECT al.*, u.username
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 15

-- Security events in last 24 hours
SELECT COUNT(*) AS count
FROM activity_logs
WHERE is_security_event = 1
  AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
  -- SQLite: datetime('now', '-1 days')
```

### 6.5 Booking Statistics (Aggregate)

Used in `BookingRepository::getStatistics()`:

```sql
SELECT
    COUNT(*) AS total_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
    SUM(total_price) AS total_revenue
FROM bookings
WHERE is_deleted = 0
```

### 6.6 Pagination Pattern

The application fetches a limited result set (typically `LIMIT 100` or `LIMIT 200`) and then paginates in PHP via `BaseController::paginate()`:

```sql
-- Fetch up to 100 rows
SELECT ... FROM bookings ... LIMIT 100

-- Then in PHP:
$pagination = $this->paginate($results, $perPage);  // e.g., 15 per page
```

This is a **client-side pagination** approach -- the database returns a bounded result set and PHP slices it into pages. Individual entity queries in `BaseRepository::findAll()` support `LIMIT` and `OFFSET` directly:

```sql
SELECT * FROM {$table} WHERE is_deleted = 0 LIMIT ? OFFSET ?
```

### 6.7 Date-Range Conflict Detection

Used in `BookingRepository::findByRoomAndDateRange()`:

```sql
SELECT * FROM bookings
WHERE room_id = ?
  AND status NOT IN ('cancelled', 'no_show')
  AND (
      (check_in <= ? AND check_out > ?)        -- existing booking spans start
      OR (check_in < ? AND check_out >= ?)     -- existing booking spans end
      OR (check_in >= ? AND check_out <= ?)    -- existing booking is within range
  )
  AND is_deleted = 0
```

---

## 7. SQL Dialect Handling

The application runs on both MySQL and SQLite. Since these databases have different SQL functions for dates and timestamps, the `Database` class provides abstraction methods, and repositories check `$_ENV['DB_CONNECTION']` when inline SQL is needed.

### 7.1 Abstraction Methods in `Database.php`

| Method                           | MySQL Output                                | SQLite Output                            |
|----------------------------------|---------------------------------------------|------------------------------------------|
| `$db->now()`                     | `NOW()`                                     | `datetime('now')`                        |
| `$db->today()`                   | `CURDATE()`                                 | `date('now')`                            |
| `$db->dateAdd('minutes', 30)`    | `DATE_ADD(NOW(), INTERVAL 30 MINUTE)`       | `datetime('now', '+30 minutes')`         |
| `$db->dateSub('days', 1)`        | `DATE_SUB(NOW(), INTERVAL 1 DAY)`           | `datetime('now', '-1 days')`             |
| `$db->getDriver()`               | `'mysql'`                                   | `'sqlite'`                               |

### 7.2 Direct Driver Checks in Repositories

When repository methods need driver-specific SQL that cannot use the `Database` helper (e.g., date arithmetic with a variable interval), they check the environment directly:

```php
// UserRepository::lockAccount()
$driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
if ($driver === 'sqlite') {
    $sql = "UPDATE users SET locked_until = datetime('now', '+{$minutes} minutes') WHERE id = ?";
} else {
    $sql = "UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL {$minutes} MINUTE) WHERE id = ?";
}
```

```php
// BaseRepository::delete()
$driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
$now = $driver === 'sqlite' ? "datetime('now')" : "NOW()";
```

### 7.3 Type Differences

| Feature           | MySQL                          | SQLite                                     |
|-------------------|--------------------------------|--------------------------------------------|
| ENUM types        | Native `ENUM(...)` type        | `TEXT` with `CHECK(col IN (...))` constraint |
| JSON columns      | Native `JSON` type             | `TEXT` (app-level JSON encode/decode)       |
| Auto-increment    | `INT UNSIGNED AUTO_INCREMENT`  | `INTEGER PRIMARY KEY AUTOINCREMENT`        |
| Boolean           | `BOOLEAN` (alias for TINYINT)  | `BOOLEAN` (stored as 0/1 INTEGER)          |
| BIGINT            | `BIGINT UNSIGNED`              | `INTEGER`                                  |
| Timestamp update  | `ON UPDATE CURRENT_TIMESTAMP`  | Manual update required in application code |
| Foreign keys      | Enabled by default (InnoDB)    | Requires `PRAGMA foreign_keys = ON`        |
| Index creation    | Inline in `CREATE TABLE`       | Separate `CREATE INDEX` statements         |

### 7.4 Connection Setup Differences

```php
// SQLite: enables foreign key enforcement
$this->connection->exec('PRAGMA foreign_keys = ON;');

// MySQL: charset specified in DSN
$dsn = "mysql:host=...;port=...;dbname=...;charset=utf8mb4";
```

---

## 8. Security

### 8.1 Prepared Statements

All repository queries use parameterized prepared statements via PDO. Parameters are passed as arrays and bound by the driver, preventing SQL injection:

```php
// BaseRepository::find() -- positional parameter
$sql = "SELECT * FROM {$this->table} WHERE id = ? AND is_deleted = 0 LIMIT 1";
$result = $this->db->fetchOne($sql, [$id]);

// UserRepository::usernameExists() -- multiple parameters
$sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND is_deleted = 0";
$params = [$username];
if ($excludeId) {
    $sql .= " AND id != ?";
    $params[] = $excludeId;
}
$result = $this->fetchOne($sql, $params);

// LoggingMiddleware -- insert with 8 bound parameters
$db->execute(
    "INSERT INTO activity_logs (user_id, action, description, ip_address,
     user_agent, request_data, severity, is_security_event, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$db->now()})",
    [$userId, $action, 'HTTP request logged', $ip, $userAgent,
     $requestData, $severity, $isSecurityEvent ? 1 : 0]
);
```

The `Database::query()` method enforces prepared statements when parameters are provided. The `PDO::ATTR_EMULATE_PREPARES => false` option (in MySQL config) ensures true server-side parameter binding.

### 8.2 Vulnerable Mode

The system has a dual-mode design for educational purposes. When `SECURITY_MODE=vulnerable` is set in `.env`:

```php
if (!$this->secureMode && empty($params)) {
    // VULNERABLE: Direct query execution (for demo purposes)
    return $this->connection->query($query);
}
```

In secure mode (`SECURITY_MODE=secure`), all queries go through `prepare()` + `execute()`.

### 8.3 Least-Privilege Database User

For MySQL production deployments, the application connects as user `hotel_app` with only DML privileges:

```
DB_USERNAME=hotel_app
```

The `hotel_app` user is granted only `SELECT`, `INSERT`, `UPDATE`, and `DELETE` -- no DDL (`CREATE`, `DROP`, `ALTER`) or administrative privileges. Schema changes require a separate privileged connection.

### 8.4 Password Column Exclusion

The admin user listing explicitly enumerates safe columns, **never selecting `password`**:

```php
// AdminController::users()
$columns = "id, username, email, full_name, phone, role, failed_login_attempts,
            locked_until, last_login, last_login_ip, is_active, created_at";

$sql = "SELECT {$columns} FROM users WHERE is_deleted = 0 ORDER BY created_at DESC";
```

### 8.5 CSRF Protection

The `sessions` table stores a `csrf_token` (VARCHAR(64)) per session. Forms include this token and the server validates it on POST requests before performing any state-changing operation.

### 8.6 Account Lockout

After repeated failed login attempts, `UserRepository::lockAccount()` sets `locked_until` to a future timestamp (default: 30 minutes ahead). The `failed_login_attempts` counter is incremented atomically:

```sql
UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?
```

---

## 9. Migration Strategy

### 9.1 Current Approach: Full Schema Files

The database is initialized by executing one of the full schema files:

| File                     | Target  | Usage                                      |
|--------------------------|---------|--------------------------------------------|
| `database/schema_mysql.sql`  | MySQL   | `DROP DATABASE` + full `CREATE` + seed data |
| `database/schema_sqlite.sql` | SQLite  | `CREATE TABLE IF NOT EXISTS` + seed data    |

These are **destructive** for MySQL (drops and recreates the entire database) and **idempotent** for SQLite (`IF NOT EXISTS`). Both include identical seed data.

### 9.2 Password Migration

`src/Utils/PasswordMigration.php` is a CLI-only one-time migration script:

```bash
php src/Utils/PasswordMigration.php
```

It finds all users with plaintext passwords (those not matching the `$2y$` bcrypt prefix) and rehashes them using `password_hash()` with `PASSWORD_BCRYPT` at cost 12:

```php
$stmt = $pdo->query("SELECT id, username, password FROM users WHERE password NOT LIKE '\$2y\$%'");
// ...
$hashed = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 12]);
$updateStmt->execute([$hashed, $user['id']]);
```

This script is safe to run multiple times -- it skips already-hashed passwords.

### 9.3 Future Incremental Migrations

Two empty directories exist for future migration infrastructure:

```
database/migrations/     # Incremental schema change files
database/seeds/          # Seed data scripts
```

The `config/database.php` references a migrations table name:

```php
'migrations' => [
    'table' => 'migrations',
],
```

This suggests a planned migration tracking system (recording which migrations have run) but it is not yet implemented. Currently, schema changes require editing the full schema files and re-initializing the database.

---

## Appendix: Quick Reference

### All Foreign Key Relationships

| Source Table    | Column          | Target Table | Target Column | ON DELETE   |
|-----------------|-----------------|--------------|---------------|-------------|
| hotels          | created_by      | users        | id            | SET NULL    |
| hotels          | updated_by      | users        | id            | SET NULL    |
| rooms           | hotel_id        | hotels       | id            | RESTRICT*   |
| rooms           | created_by      | users        | id            | SET NULL    |
| bookings        | user_id         | users        | id            | RESTRICT*   |
| bookings        | room_id         | rooms        | id            | RESTRICT*   |
| bookings        | cancelled_by    | users        | id            | SET NULL    |
| bookings        | created_by      | users        | id            | SET NULL    |
| sessions        | user_id         | users        | id            | CASCADE     |
| activity_logs   | user_id         | users        | id            | SET NULL    |
| users (SQLite)  | created_by      | users        | id            | SET NULL    |
| users (SQLite)  | updated_by      | users        | id            | SET NULL    |
| users (SQLite)  | deleted_by      | users        | id            | SET NULL    |

\* RESTRICT = implicit (no `ON DELETE` clause specified; the database default prevents deletion of referenced rows).

### All Indexes

| Table          | Index Name                    | Columns                    |
|----------------|-------------------------------|----------------------------|
| users          | idx_username                  | (username)                 |
| users          | idx_email                     | (email)                    |
| users          | idx_role                      | (role)                     |
| rooms          | idx_hotel_id                  | (hotel_id)                 |
| rooms          | idx_room_type                 | (room_type)                |
| rooms          | idx_is_available              | (is_available)             |
| rooms          | unique_room                   | (hotel_id, room_number) UQ |
| bookings       | idx_user_id                   | (user_id)                  |
| bookings       | idx_room_id                   | (room_id)                  |
| bookings       | idx_check_in                  | (check_in)                 |
| bookings       | idx_status                    | (status)                   |
| bookings       | idx_booking_ref               | (booking_reference)        |
| sessions       | idx_user_id                   | (user_id)                  |
| sessions       | idx_last_activity             | (last_activity)            |
| activity_logs  | idx_user_id                   | (user_id)                  |
| activity_logs  | idx_action                    | (action)                   |
| activity_logs  | idx_created_at                | (created_at)               |
| activity_logs  | idx_security                  | (is_security_event)        |
