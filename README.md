# Grand Plaza Hotel & Resort - Booking Management System

A production-ready hotel booking and management system built with PHP 8, MySQL, and Bootstrap 5. Designed for a single luxury hotel with online room booking, guest management, and a full-featured admin panel.

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 3. Import database
mysql -u root -p < database/schema_mysql.sql

# 4. Start development server
php -S localhost:8000 -t public

# 5. Open http://localhost:8000
```

**Default Credentials:**
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| User | user1 | user123 |

---

## Project Structure

```
hotel_management_system/
|
|-- config/                         # Application configuration
|   |-- app.php                     # App settings, security, sessions, rate limiting
|   |-- database.php                # Database connection config (MySQL / SQLite)
|
|-- database/                       # Database schemas and migrations
|   |-- schema_mysql.sql            # MySQL schema + seed data
|   |-- schema_sqlite.sql           # SQLite schema + seed data (development fallback)
|   |-- migrations/                 # Future migration files
|   |-- seeds/                      # Future seed files
|
|-- public/                         # Web root (document root for server)
|   |-- index.php                   # Front controller - all requests route through here
|   |-- assets/
|       |-- css/style.css           # Custom styles (hotel theme, responsive, print)
|       |-- js/app.js               # Client-side JS (validation, loading states, UX)
|       |-- images/favicon.svg      # Hotel favicon
|
|-- src/                            # Application source code (PSR-4 autoloaded)
|   |
|   |-- Core/                       # Framework core
|   |   |-- Application.php         # App bootstrap, DI, session management
|   |   |-- Database.php            # PDO wrapper (MySQL + SQLite), prepared statements
|   |   |-- Request.php             # HTTP request wrapper (GET, POST, files, IP)
|   |   |-- Response.php            # HTTP response (views, JSON, redirects, headers)
|   |   |-- Router.php              # URL routing with groups, params, middleware
|   |
|   |-- Controllers/                # Request handlers (MVC controllers)
|   |   |-- BaseController.php      # Abstract base - auth, CSRF, flash, view helpers
|   |   |-- HomeController.php      # Landing page, search, about, contact
|   |   |-- AuthController.php      # Login, register, logout, forgot/reset password
|   |   |-- RoomController.php      # Room listing with filters, room detail
|   |   |-- BookingController.php   # Booking form, create, my bookings, cancel, confirmation
|   |   |-- UserController.php      # Dashboard, profile management
|   |   |-- AdminController.php     # Admin dashboard, room CRUD, booking/user management
|   |
|   |-- Models/                     # Data models (entity classes)
|   |   |-- User.php                # User entity - roles, auth state, display helpers
|   |   |-- Hotel.php               # Hotel entity - amenities, address, ratings
|   |   |-- Room.php                # Room entity - pricing, capacity, availability
|   |   |-- Booking.php             # Booking entity - lifecycle, pricing, references
|   |
|   |-- Repositories/              # Data access layer (database queries)
|   |   |-- BaseRepository.php      # Abstract CRUD - find, create, update, soft delete
|   |   |-- UserRepository.php      # User queries - login tracking, search, lockout
|   |   |-- HotelRepository.php     # Hotel queries - active hotels, room counts
|   |   |-- RoomRepository.php      # Room queries - availability, search, filters
|   |   |-- BookingRepository.php   # Booking queries - by user, conflicts, statistics
|   |
|   |-- Middleware/                 # HTTP middleware (request/response pipeline)
|   |   |-- AuthMiddleware.php      # Authentication check - redirects unauthenticated
|   |   |-- CsrfMiddleware.php      # CSRF token validation on POST/PUT/DELETE
|   |   |-- RateLimitMiddleware.php # IP-based rate limiting with logging
|   |   |-- LoggingMiddleware.php   # Request logging to activity_logs table
|   |
|   |-- Utils/                     # Utility scripts
|       |-- PasswordMigration.php   # CLI tool to hash plaintext passwords with bcrypt
|
|-- views/                          # PHP view templates (rendered by Response)
|   |
|   |-- layouts/
|   |   |-- main.php                # Master layout - navbar, footer, flash messages
|   |
|   |-- pages/                      # Guest-facing pages
|   |   |-- home.php                # Hotel landing page with featured rooms
|   |   |-- rooms.php               # Room listing with filter form
|   |   |-- room_detail.php         # Single room detail with availability
|   |   |-- booking_form.php        # Booking form with price estimator
|   |   |-- booking_confirmation.php # Post-booking confirmation (printable)
|   |   |-- bookings.php            # My bookings (upcoming/past/cancelled tabs)
|   |   |-- dashboard.php           # User dashboard with stats
|   |   |-- profile.php             # Profile editor with password change
|   |   |-- login.php               # Login form
|   |   |-- register.php            # Registration form
|   |   |-- forgot_password.php     # Forgot password form
|   |   |-- reset_password.php      # Password reset form
|   |   |-- contact.php             # Contact form
|   |   |-- about.php               # About the hotel
|   |   |-- search.php              # Search results
|   |
|   |-- admin/                      # Admin panel pages
|   |   |-- dashboard.php           # Admin dashboard (stats, check-ins, activity)
|   |   |-- rooms.php               # Room management (list, toggle, edit/add links)
|   |   |-- room_form.php           # Add/edit room form
|   |   |-- bookings.php            # Booking list with status actions
|   |   |-- booking_detail.php      # Booking detail with status/payment controls
|   |   |-- users.php               # User list with activate/role actions
|   |   |-- settings.php            # Hotel settings editor
|   |   |-- logs.php                # Activity log viewer
|   |
|   |-- components/                 # Reusable view partials
|   |   |-- pagination.php          # Pagination controls
|   |
|   |-- errors/                     # Error pages
|       |-- 403.php                 # Access denied
|       |-- 404.php                 # Page not found
|       |-- 500.php                 # Server error
|
|-- tests/                          # PHPUnit test suite
|   |-- TestCase.php                # Base test class with in-memory SQLite setup
|   |-- Unit/
|       |-- Models/
|       |   |-- UserModelTest.php
|       |   |-- BookingModelTest.php
|       |   |-- RoomModelTest.php
|       |-- Security/
|       |   |-- PasswordSecurityTest.php
|       |   |-- XSSPreventionTest.php
|       |   |-- CSRFTest.php
|       |   |-- GSTCalculationTest.php
|       |-- Validation/
|           |-- InputValidationTest.php
|
|-- storage/                        # Runtime storage (git-ignored)
|   |-- database.sqlite             # SQLite database file (dev fallback)
|   |-- logs/                       # Application log files
|   |-- cache/                      # Cache files
|   |-- uploads/                    # User uploads
|
|-- .env                            # Environment config (not committed)
|-- .env.example                    # Environment template
|-- .gitignore                      # Git ignore rules
|-- composer.json                   # PHP dependencies (PSR-4 autoload)
|-- phpunit.xml                     # PHPUnit configuration
|-- SECURITY_AUDIT.md               # Security audit report
```

---

## Features

### Guest Portal

| Feature | Description |
|---------|-------------|
| **Room Browsing** | Filter by type, dates, price range, guest capacity. Date-aware availability check excludes rooms with conflicting bookings. |
| **Room Detail** | Full room info, amenities, pricing tiers (base/weekend/peak), booked dates for next 30 days. |
| **Online Booking** | Date selection, guest count, special requests. Server-side price calculation with GST (18% for rooms above Rs.7,500/night, 12% below). Date conflict prevention. |
| **Booking Confirmation** | Printable confirmation page with reference number, hotel details, pricing breakdown, and check-in/out times. |
| **My Bookings** | Tabbed view (Upcoming / Past / Cancelled). Cancel active bookings with IDOR protection. |
| **User Dashboard** | Booking stats, upcoming reservations, recent activity feed. |
| **Profile Management** | Edit name, email, phone. Change password with current password verification and strength policy (8+ chars, uppercase, lowercase, number). |
| **Contact Form** | Inquiry form with subject categories. Logged to activity table. |
| **Password Reset** | Forgot password flow with cryptographic token, 1-hour expiry. |

### Admin Panel

| Feature | Description |
|---------|-------------|
| **Dashboard** | Room occupancy rate, revenue, today's check-ins/check-outs, recent activity feed. |
| **Room Management** | Add new rooms, edit details/pricing, toggle availability. Full form with amenities, floor, bed type, view type, square footage. |
| **Booking Management** | View all bookings with filters (status, date range, search). Quick actions: Confirm, Check In, Check Out, Cancel. Detailed booking view with status and payment controls. |
| **User Management** | Search users, activate/deactivate accounts, change roles (admin/user). Self-modification prevented. |
| **Hotel Settings** | Edit hotel name, description, address, contact info, check-in/out times. |
| **Activity Logs** | Filterable log of all system activity with severity levels. Pagination. |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Language** | PHP 8.x |
| **Database** | MySQL 9.x (primary), SQLite 3 (development fallback) |
| **Frontend** | Bootstrap 5.3, Font Awesome 6, vanilla JavaScript |
| **Architecture** | MVC with Repository pattern |
| **Autoloading** | PSR-4 via Composer |
| **Testing** | PHPUnit 10 (132 tests, 254 assertions) |
| **Dependencies** | vlucas/phpdotenv, monolog/monolog |

---

## Database Schema

```
users           - Authentication, roles (admin/user), security tracking
hotels          - Hotel info (single row), amenities, policies
rooms           - Room inventory, pricing tiers, availability, maintenance
bookings        - Reservations with lifecycle (pending -> confirmed -> checked_in -> checked_out)
sessions        - Session management
activity_logs   - Audit trail with severity and security event flagging
```

### Room Types & Pricing (INR)

| Type | Price Range | Rooms |
|------|------------|-------|
| Single | Rs.4,500 - Rs.4,900 | 101, 102 |
| Double | Rs.7,500 - Rs.9,500 | 103, 104, 201 |
| Deluxe | Rs.11,500 - Rs.12,500 | 202, 203 |
| Suite | Rs.18,000 - Rs.26,000 | 204, 301, 302 |
| Presidential | Rs.55,000 | 303 |

---

## Security Implementation

### OWASP Top 10 Coverage

| OWASP Category | Implementation |
|---------------|----------------|
| **A01: Broken Access Control** | Role-based access (admin/user), `requireAdmin()` / `requireLogin()` on all protected routes, IDOR prevention via ownership checks on bookings |
| **A02: Cryptographic Failures** | Passwords hashed with bcrypt (cost 12), never stored or displayed in plaintext |
| **A03: Injection** | 100% prepared statements with PDO parameter binding across all SQL queries |
| **A05: Security Misconfiguration** | Debug mode off in production, security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy), proper error pages |
| **A07: Auth Failures** | Account lockout after 5 failed attempts, session regeneration on login, HttpOnly + SameSite=Strict cookies, password reset with crypto tokens |
| **A09: Logging Failures** | All actions logged to activity_logs with user ID, IP, user agent, severity. Security events flagged separately. |

### Additional Security

| Feature | Detail |
|---------|--------|
| **CSRF Protection** | Token-based with `hash_equals()` comparison on all POST forms |
| **XSS Prevention** | `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` on all output in all 23 view files |
| **Rate Limiting** | IP-based, configurable max requests per window |
| **Input Validation** | Server-side validation on all endpoints (types, formats, ranges) |
| **Server-Side Pricing** | Total price always calculated server-side, never trusted from client |
| **Least Privilege DB** | App connects with `hotel_app` user (SELECT/INSERT/UPDATE/DELETE only, no DDL) |

---

## API Routes

### Public (No auth required)
```
GET  /                    Home page
GET  /rooms               Room listing with filters
GET  /room/{id}           Room detail
GET  /about               About the hotel
GET  /contact             Contact page
POST /contact             Submit contact inquiry
GET  /search              Search rooms
GET  /login               Login form
POST /login               Process login
GET  /register            Registration form
POST /register            Process registration
GET  /forgot-password     Forgot password form
POST /forgot-password     Send reset token
GET  /reset-password      Reset password form (with token)
POST /reset-password      Process password reset
GET  /logout              Logout
```

### Authenticated (Login required)
```
GET  /dashboard           User dashboard
GET  /profile             Profile page
POST /profile             Update profile
GET  /book/{roomId}       Booking form
POST /book                Create booking
GET  /bookings            My bookings
POST /booking/{id}/cancel Cancel booking
GET  /booking/{id}/confirmation  Booking confirmation
```

### Admin (Admin role required)
```
GET  /admin               Admin dashboard
GET  /admin/rooms         Room management
GET  /admin/rooms/add     Add room form
POST /admin/rooms/add     Create room
GET  /admin/rooms/edit/{id}  Edit room form
POST /admin/rooms/edit/{id}  Update room
POST /admin/rooms/toggle/{id} Toggle room availability
GET  /admin/bookings      Booking management
GET  /admin/booking/{id}  Booking detail
POST /admin/bookings/status/{id}   Update booking status
POST /admin/bookings/payment/{id}  Update payment status
GET  /admin/users         User management
POST /admin/users/toggle/{id}  Toggle user active status
POST /admin/users/role/{id}    Change user role
GET  /admin/settings      Hotel settings
POST /admin/settings      Update hotel settings
GET  /admin/logs          Activity logs
```

**Total: 43 routes**

---

## Testing

```bash
# Run full test suite
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox
```

### Test Coverage

| Suite | Tests | Assertions |
|-------|-------|------------|
| Models (User, Room, Booking) | 58 | 120+ |
| Security (Password, XSS, CSRF, GST) | 44 | 80+ |
| Validation (Email, Phone, Dates, IDs) | 30 | 50+ |
| **Total** | **132** | **254** |

---

## Configuration

All configuration is in `.env`:

```env
# Database
DB_CONNECTION=mysql          # mysql or sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotel_management_db
DB_USERNAME=hotel_app
DB_PASSWORD=your_password

# Security
SECURITY_MODE=secure         # always use 'secure' in production
CSRF_ENABLED=true
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60

# Application
APP_DEBUG=false              # never true in production
APP_URL=http://localhost:8000
SESSION_LIFETIME=120
```

---

## Deployment

### Requirements
- PHP 8.0+ with extensions: pdo, pdo_mysql, mbstring, json
- MySQL 8.0+ or SQLite 3
- Apache/Nginx (or PHP built-in server for development)
- Composer

### Production Setup

1. Point web server document root to `public/`
2. Set `APP_DEBUG=false` and `SECURITY_MODE=secure`
3. Use a dedicated MySQL user with minimal privileges
4. Enable HTTPS and set `FORCE_HTTPS=true`
5. Set `SESSION_SECURE=true` for HTTPS-only cookies
6. Run `php src/Utils/PasswordMigration.php` to hash any plaintext passwords

---

## Architecture

```
Browser Request
      |
      v
public/index.php (Front Controller)
      |
      v
Application.php (Bootstrap: .env, config, session, DB)
      |
      v
Router.php (Match URI -> Controller@method)
      |
      v
Middleware Pipeline (Auth -> CSRF -> RateLimit -> Logging)
      |
      v
Controller (Business logic, validation)
      |
      v
Repository (Database queries via prepared statements)
      |
      v
Model (Data entity)
      |
      v
Response -> View (PHP template with layout) -> HTML to browser
```

---

## Documentation

Comprehensive documentation is available in the `docs/` directory:

| Document | What It Covers |
|----------|---------------|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | System design, request lifecycle, MVC pattern, core framework internals |
| [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md) | All 43 routes with parameters, validation, responses, curl examples |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema, ER diagram, table definitions, query patterns, seed data |
| [docs/SECURITY.md](docs/SECURITY.md) | OWASP Top 10 mapping, injection prevention, auth, CSRF, headers |
| [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) | Local setup, coding standards, how to add features, troubleshooting |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Apache/Nginx config, production settings, SSL, backups |
| [docs/TESTING.md](docs/TESTING.md) | Test suite structure, 132 tests documented, CI/CD integration |

Start with [docs/INDEX.md](docs/INDEX.md) for a guided reading order.

---

## License

Private project. All rights reserved.

#start application
php -S localhost:8000 -t public

# Run full test suite
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox
