# Development Guide

## 1. Prerequisites

| Tool | Version | Notes |
|------|---------|-------|
| PHP | 8.0+ | With extensions: `pdo`, `pdo_mysql`, `mbstring`, `json`, `mysqli` |
| MySQL | 8.0+ | Or SQLite 3 for lightweight local dev |
| Composer | Latest | PHP dependency manager |
| Git | Latest | Version control |

Verify your PHP setup:

```bash
php -v
php -m | grep -E 'pdo|pdo_mysql|mbstring|json|mysqli'
```

## 2. Local Setup

### Option A: MySQL (recommended for production-like environment)

```bash
# Clone the repository
git clone <repo-url> hotel_management_system
cd hotel_management_system

# Install PHP dependencies
composer install

# Create environment file
cp .env.example .env

# Edit .env with your database credentials:
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=hotel_management_db
#   DB_USERNAME=root
#   DB_PASSWORD=your_root_password

# Create the database and import the schema
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS hotel_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p hotel_management_db < database/schema_mysql.sql

# Create a dedicated application database user (recommended)
mysql -u root -p -e "CREATE USER 'hotel_app'@'localhost' IDENTIFIED BY 'your_password'; GRANT SELECT, INSERT, UPDATE, DELETE ON hotel_management_db.* TO 'hotel_app'@'localhost'; FLUSH PRIVILEGES;"

# If using the dedicated user, update .env:
#   DB_USERNAME=hotel_app
#   DB_PASSWORD=your_password

# Seed the database (if seed files exist)
mysql -u root -p hotel_management_db < database/seeds/*.sql

# Hash any plaintext seed passwords
php src/Utils/PasswordMigration.php

# Start the development server
php -S localhost:8000 -t public
```

Open http://localhost:8000 in your browser.

### Option B: SQLite (no MySQL needed)

```bash
# Clone and install
git clone <repo-url> hotel_management_system
cd hotel_management_system
composer install
cp .env.example .env

# Edit .env:
#   DB_CONNECTION=sqlite
#   DB_DATABASE=storage/database.sqlite

# Create the SQLite database and import the schema
php -r "\$db = new PDO('sqlite:storage/database.sqlite'); \$db->exec(file_get_contents('database/schema_sqlite.sql'));"

# Hash seed passwords
php src/Utils/PasswordMigration.php

# Start the development server
php -S localhost:8000 -t public
```

### Post-Setup Verification

1. Visit http://localhost:8000 -- you should see the hotel homepage
2. Visit http://localhost:8000/login -- the login form should render
3. Visit http://localhost:8000/rooms -- the rooms listing should load
4. Check `storage/logs/app.log` for any startup errors

## 3. Project Structure

```
hotel_management_system/
├── config/
│   ├── app.php              # Application settings (security, session, paths)
│   └── database.php         # Database connection configuration
├── database/
│   ├── migrations/          # Incremental DB migration SQL files
│   ├── seeds/               # Seed data SQL files
│   ├── schema_mysql.sql     # Full MySQL schema
│   └── schema_sqlite.sql    # Full SQLite schema
├── docs/                    # Documentation
├── public/
│   ├── index.php            # Front controller (entry point)
│   └── assets/              # CSS, JS, images
├── src/
│   ├── Controllers/         # Request handlers
│   │   ├── BaseController.php
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── BookingController.php
│   │   ├── HomeController.php
│   │   ├── RoomController.php
│   │   └── UserController.php
│   ├── Core/                # Framework core (Application, Router, Database, Request, Response)
│   │   └── Application.php  # Singleton bootstrap class
│   ├── Middleware/           # Request middleware
│   ├── Models/              # Data models (Booking, Hotel, Room, User)
│   ├── Repositories/        # Database access layer
│   │   ├── BaseRepository.php
│   │   ├── BookingRepository.php
│   │   ├── HotelRepository.php
│   │   ├── RoomRepository.php
│   │   └── UserRepository.php
│   ├── Services/            # Business logic layer
│   ├── Utils/               # Utility scripts (PasswordMigration.php)
│   └── Validators/          # Input validation
├── storage/
│   ├── cache/               # Application cache
│   ├── logs/                # Log files (app.log, php_errors.log)
│   ├── uploads/             # User-uploaded files
│   └── database.sqlite      # SQLite database file (if using SQLite)
├── tests/
│   ├── TestCase.php         # Base test class
│   ├── Unit/                # Unit tests (Models, Security, Validation)
│   └── Integration/         # Integration tests
├── vendor/                  # Composer dependencies (git-ignored)
├── views/
│   ├── admin/               # Admin panel views
│   ├── components/          # Reusable view partials
│   ├── errors/              # Error pages (403, 404, 500)
│   ├── layouts/             # Layout templates (main.php)
│   └── pages/               # Public-facing page views
├── .env                     # Environment config (git-ignored)
├── .env.example             # Template for .env
├── composer.json            # PHP dependencies and autoloading
└── phpunit.xml              # Test configuration
```

## 4. Project Conventions

### Namespaces and Autoloading

PSR-4 autoloading maps `App\` to `src/`. All classes must be namespaced accordingly:

```php
// File: src/Controllers/BookingController.php
namespace App\Controllers;

// File: src/Models/Booking.php
namespace App\Models;

// File: src/Repositories/BookingRepository.php
namespace App\Repositories;
```

### Controllers

All controllers extend `BaseController` and receive `Request` and `Response` via the constructor. Route parameters arrive as an `array $params` argument:

```php
namespace App\Controllers;

class RoomController extends BaseController
{
    public function show(array $params): Response
    {
        $roomId = (int) $params['id'];
        // ...
        return $this->view('pages.room_detail', ['room' => $room]);
    }
}
```

Key `BaseController` methods:
- `$this->view('dotted.path', $data)` -- render a view (dot notation maps to directory path)
- `$this->json($data, $statusCode)` -- return JSON
- `$this->redirect('/url')` -- HTTP redirect
- `$this->back()` -- redirect to previous page
- `$this->requireLogin()` -- redirect to login if not authenticated
- `$this->requireAdmin()` -- require admin role or show 403
- `$this->validateCsrf()` -- validate CSRF token from form submission
- `$this->flash('key', 'message')` -- set a flash message
- `$this->esc($string)` -- HTML-encode output

### Views

Views use dot notation that maps to filesystem paths:
- `pages.rooms` resolves to `views/pages/rooms.php`
- `admin.dashboard` resolves to `views/admin/dashboard.php`
- `errors.403` resolves to `views/errors/403.php`

Views receive extracted `$data` variables plus these globals:
- `$user` -- current user array or null
- `$csrf_token` -- CSRF token string
- `$security_mode` -- "secure" or "vulnerable"
- `$app` -- Application instance
- `$request` -- Request instance

### Repositories

All repositories extend `BaseRepository`. Set `$table` and `$modelClass` properties:

```php
namespace App\Repositories;

use App\Models\Room;

class RoomRepository extends BaseRepository
{
    protected string $table = 'rooms';
    protected string $modelClass = Room::class;

    // Custom query methods go here
}
```

Inherited methods: `find($id)`, `findAll()`, `findBy($col, $val)`, `findOneBy($col, $val)`, `create($data)`, `update($id, $data)`, `delete($id)` (soft delete), `hardDelete($id)`, `count($where)`, `exists($id)`.

### Models

Models use a static `fromArray()` factory method to hydrate from database rows.

### Routes

All routes are defined in `public/index.php` inside the `$app->routes()` closure. The router supports:
- `$router->get('/path', 'Controller@method')`
- `$router->post('/path', 'Controller@method')`
- `$router->group(['prefix' => 'admin'], function($router) { ... })` for grouped routes
- Route parameters: `{id}`, `{roomId}`, etc.

## 5. How to Add a New Feature

Step-by-step example: **Add a Guest Reviews feature**.

### a) Create the Model

```php
// src/Models/Review.php
namespace App\Models;

class Review
{
    public int $id;
    public int $user_id;
    public int $room_id;
    public int $rating;
    public string $comment;
    public string $created_at;

    public static function fromArray(array $data): self
    {
        $review = new self();
        $review->id = (int) ($data['id'] ?? 0);
        $review->user_id = (int) ($data['user_id'] ?? 0);
        $review->room_id = (int) ($data['room_id'] ?? 0);
        $review->rating = (int) ($data['rating'] ?? 0);
        $review->comment = $data['comment'] ?? '';
        $review->created_at = $data['created_at'] ?? '';
        return $review;
    }
}
```

### b) Create the Repository

```php
// src/Repositories/ReviewRepository.php
namespace App\Repositories;

use App\Models\Review;

class ReviewRepository extends BaseRepository
{
    protected string $table = 'reviews';
    protected string $modelClass = Review::class;

    public function findByRoom(int $roomId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE room_id = ? AND is_deleted = 0 ORDER BY created_at DESC";
        $results = $this->fetchAll($sql, [$roomId]);
        return array_map(fn($row) => Review::fromArray($row), $results);
    }
}
```

### c) Add Controller Methods

Add to an existing controller or create `src/Controllers/ReviewController.php`:

```php
namespace App\Controllers;

use App\Repositories\ReviewRepository;

class ReviewController extends BaseController
{
    public function store(array $params): void
    {
        $this->requireLogin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid CSRF token.');
            $this->back();
            return;
        }

        $repo = new ReviewRepository($this->db);
        $repo->create([
            'user_id' => $this->getCurrentUserId(),
            'room_id' => (int) $this->request->post('room_id'),
            'rating' => (int) $this->request->post('rating'),
            'comment' => $this->request->post('comment'),
        ]);

        $this->flash('success', 'Review submitted.');
        $this->redirect('/room/' . $this->request->post('room_id'));
    }
}
```

### d) Create the View

Create `views/pages/review_form.php` with a form that includes the CSRF token:

```php
<form method="POST" action="/reviews">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="room_id" value="<?= (int) $room->id ?>">
    <!-- rating, comment fields -->
    <button type="submit">Submit Review</button>
</form>
```

### e) Register Routes

In `public/index.php`, add inside the routes closure:

```php
$router->post('/reviews', 'ReviewController@store');
$router->get('/room/{id}/reviews', 'ReviewController@listByRoom');
```

### f) Create the Migration

Add `database/migrations/YYYYMMDD_create_reviews_table.sql`:

```sql
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_deleted TINYINT DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### g) Write Tests

Create `tests/Unit/Models/ReviewModelTest.php` or `tests/Integration/ReviewRepositoryTest.php`.

## 6. Coding Standards

### Type Safety

```php
// Use typed properties
protected string $table = 'bookings';
protected int $perPage = 10;

// Use return types on all public methods
public function find(int $id): ?Booking { ... }
public function findAll(): array { ... }
```

### Output Escaping

**Every** piece of user-derived data rendered in HTML must be escaped:

```php
// CORRECT
<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>

// ALSO CORRECT (using BaseController helper in controller context)
$this->esc($user['username'])

// WRONG - never do this
<?= $user['username'] ?>
```

### Database Queries

**Always** use prepared statements with `?` placeholders. Never concatenate user input into SQL:

```php
// CORRECT
$sql = "SELECT * FROM rooms WHERE id = ? AND status = ?";
$result = $this->db->fetchOne($sql, [$id, 'available']);

// WRONG - SQL injection risk
$sql = "SELECT * FROM rooms WHERE id = $id";
```

### CSRF Protection

Every form submission must include a CSRF token and every POST handler must validate it:

```php
// In the view
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

// In the controller
if (!$this->validateCsrf()) {
    $this->flash('error', 'Invalid security token.');
    $this->back();
    return;
}
```

### Access Control

Protect sensitive controller methods:

```php
public function dashboard(array $params): Response
{
    $this->requireLogin();      // Redirects to /login if not authenticated
    // ...
}

public function adminPanel(array $params): Response
{
    $this->requireAdmin();      // Requires login + admin role, shows 403 otherwise
    // ...
}
```

### Logging

Log all admin actions and security-relevant events to the `activity_logs` table for audit trail purposes.

## 7. Running Tests

Tests use PHPUnit 10 with SQLite in-memory for isolation. Configuration is in `phpunit.xml`.

```bash
# Run all tests
./vendor/bin/phpunit

# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run only integration tests
./vendor/bin/phpunit --testsuite Integration

# Run a specific test file
./vendor/bin/phpunit tests/Unit/Models/BookingModelTest.php

# Run tests with verbose output
./vendor/bin/phpunit --testdox

# Generate HTML coverage report (requires Xdebug or PCOV)
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage/

# Generate text coverage summary
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
```

Test environment variables (set automatically via `phpunit.xml`):

| Variable | Test Value | Purpose |
|----------|------------|---------|
| `APP_ENV` | `testing` | Identifies test environment |
| `DB_CONNECTION` | `sqlite` | Uses SQLite for speed |
| `DB_DATABASE` | `:memory:` | In-memory DB, no cleanup needed |
| `SECURITY_MODE` | `secure` | Tests run in secure mode |
| `CSRF_ENABLED` | `true` | CSRF validation active |

Test directory structure:
- `tests/Unit/Models/` -- model unit tests
- `tests/Unit/Security/` -- security-focused tests
- `tests/Unit/Validation/` -- input validation tests
- `tests/Integration/` -- integration tests

## 8. Environment Variables Reference

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| **Application** | | | |
| `APP_NAME` | string | `Hotel Management System` | Application display name |
| `APP_ENV` | string | `production` | Environment: `development`, `production`, `testing` |
| `APP_DEBUG` | boolean | `false` | Show detailed errors. **Must be `false` in production.** |
| `APP_URL` | string | `http://localhost` | Base URL of the application |
| `APP_KEY` | string | _(empty)_ | Application encryption key |
| **Database** | | | |
| `DB_CONNECTION` | string | `sqlite` | Database driver: `mysql` or `sqlite` |
| `DB_HOST` | string | `127.0.0.1` | MySQL host (MySQL only) |
| `DB_PORT` | string | `3306` | MySQL port (MySQL only) |
| `DB_DATABASE` | string | `hotel_management_db` | Database name, or file path for SQLite |
| `DB_USERNAME` | string | `root` | MySQL username (MySQL only) |
| `DB_PASSWORD` | string | _(empty)_ | MySQL password (MySQL only) |
| **Security** | | | |
| `SECURITY_MODE` | string | `secure` | `secure` enables all protections; `vulnerable` disables them (for security audit testing only) |
| `CSRF_ENABLED` | boolean | `true` | Enable CSRF token validation on forms |
| `FORCE_HTTPS` | boolean | `false` | Force HTTPS redirects |
| **Session** | | | |
| `SESSION_LIFETIME` | integer | `120` | Session timeout in minutes |
| `SESSION_DRIVER` | string | `file` | Session storage driver |
| `SESSION_SECURE` | boolean | `false` | Set `true` to only send session cookie over HTTPS |
| **Rate Limiting** | | | |
| `RATE_LIMIT_ENABLED` | boolean | `false` | Enable request rate limiting |
| `RATE_LIMIT_MAX_REQUESTS` | integer | `100` | Max requests per window |
| `RATE_LIMIT_WINDOW` | integer | `60` | Rate limit window in seconds |
| **Password Hashing** | | | |
| `PASSWORD_ALGO` | string | `BCRYPT` | Hashing algorithm |
| `PASSWORD_COST` | integer | `12` | Bcrypt cost factor |
| **CORS** | | | |
| `CORS_ENABLED` | boolean | `false` | Enable CORS headers |
| `CORS_ALLOWED_ORIGINS` | string | `*` | Allowed origins (comma-separated or `*`) |
| **File Upload** | | | |
| `MAX_UPLOAD_SIZE` | integer | `5242880` | Maximum upload size in bytes (default 5 MB) |
| `ALLOWED_EXTENSIONS` | string | `jpg,jpeg,png,pdf` | Comma-separated list of allowed file extensions |
| **Logging** | | | |
| `LOG_CHANNEL` | string | `single` | Log channel type |
| `LOG_LEVEL` | string | `debug` | Minimum log level |
| `LOG_PATH` | string | `storage/logs/app.log` | Log file path |
| **Email** | | | |
| `MAIL_MAILER` | string | `smtp` | Mail transport |
| `MAIL_HOST` | string | `localhost` | SMTP host |
| `MAIL_PORT` | integer | `1025` | SMTP port |
| `MAIL_USERNAME` | string | `null` | SMTP username |
| `MAIL_PASSWORD` | string | `null` | SMTP password |
| `MAIL_ENCRYPTION` | string | `null` | SMTP encryption (`tls`, `ssl`, or `null`) |
| `MAIL_FROM_ADDRESS` | string | `noreply@hotel.local` | Default sender email |
| `MAIL_FROM_NAME` | string | `${APP_NAME}` | Default sender name |

## 9. Troubleshooting

### "Class not found" errors

```bash
# Regenerate the autoloader
composer dump-autoload

# If that doesn't work, clear and rebuild
rm -rf vendor/
composer install
```

### "CSRF token invalid" on form submission

- Clear your browser cookies and try again
- Check that `CSRF_ENABLED` matches between your `.env` and your test expectations
- Verify the form includes `<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">`
- Check that `session_start()` is running (Application.php handles this automatically)

### MySQL connection refused

```bash
# Check MySQL is running
mysqladmin -u root -p status

# On macOS with Homebrew
brew services start mysql

# On Linux
sudo systemctl start mysql

# Verify credentials work
mysql -u hotel_app -p -e "SELECT 1" hotel_management_db
```

### Views not rendering / blank page

- Verify the dot notation maps to an actual file: `pages.rooms` needs `views/pages/rooms.php`
- Check `storage/logs/app.log` for PHP errors
- Temporarily set `APP_DEBUG=true` in `.env` to see the full error

### 500 Internal Server Error

```bash
# Check the application log
tail -50 storage/logs/app.log

# Check PHP error log
tail -50 storage/logs/php_errors.log

# Enable debug mode temporarily
# Set APP_DEBUG=true in .env, then reload
```

### SQLite "database is locked"

- Ensure only one process writes to the SQLite file at a time
- Avoid SQLite in production with concurrent users; use MySQL instead

### Composer dependency issues

```bash
# Clear Composer cache
composer clear-cache

# Update dependencies
composer update

# If using a specific PHP version that differs from CLI
composer install --ignore-platform-reqs
```

### Permission errors on storage/

```bash
# Fix permissions
chmod -R 775 storage/
chmod -R 775 storage/logs/
chmod -R 775 storage/uploads/
chmod -R 775 storage/cache/
```
