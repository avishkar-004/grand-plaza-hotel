# Architecture Document -- Grand Plaza Hotel Management System

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Request Lifecycle](#2-request-lifecycle)
3. [MVC + Repository Pattern](#3-mvc--repository-pattern)
4. [Core Framework](#4-core-framework)
5. [Middleware Pipeline](#5-middleware-pipeline)
6. [View System](#6-view-system)
7. [Database Architecture](#7-database-architecture)
8. [Authentication & Authorization](#8-authentication--authorization)
9. [Configuration System](#9-configuration-system)
10. [Directory Map](#10-directory-map)

---

## 1. System Overview

### What It Is

A single-hotel booking system for the **Grand Plaza Hotel & Resort**, a fictional 5-star property on Marine Drive, Mumbai. Guests can browse rooms, create accounts, make reservations, and manage their bookings. Administrators have a full back-office panel for room management, booking lifecycle control, user administration, and activity log monitoring.

The application is a hand-rolled PHP 8 framework -- no Laravel, Symfony, or other framework dependency. It was built as a security-audit teaching project for an **Ethical Security Engineering (ESE)** course, so it has a dual-mode design: a `secure` mode (prepared statements, bcrypt, CSRF, security headers) and a `vulnerable` mode (direct SQL, plaintext passwords) toggled by a single `SECURITY_MODE` environment variable.

### High-Level Architecture

```
 Browser
   |
   | HTTP Request
   v
+--------------------------------------------------+
| public/index.php  (Front Controller)             |
|  1. Composer autoload                            |
|  2. Application::getInstance() -- singleton      |
|  3. $app->routes(closure)                        |
|  4. $app->run()                                  |
+--------------------------------------------------+
   |
   v
+--------------------------------------------------+
| Application  (Bootstrap Singleton)               |
|  .env -> config/app.php + config/database.php    |
|  Session start -> Request -> Response -> Router  |
|  Database (PDO singleton)                        |
+--------------------------------------------------+
   |
   v
+--------------------------------------------------+
| Router::dispatch()                               |
|  1. Match URI against registered route patterns  |
|  2. Execute middleware pipeline                   |
|  3. Instantiate Controller, call method          |
+--------------------------------------------------+
   |
   v
+--------------------------------------------------+
| Controller -> Repository -> Database (PDO)       |
|  Controller validates input, calls Repository    |
|  Repository builds SQL, uses prepared statements |
|  Controller passes data to Response::view()      |
+--------------------------------------------------+
   |
   v
+--------------------------------------------------+
| View Layer                                       |
|  Page template rendered via ob_start/ob_get      |
|  Wrapped in layouts/main.php (layout wrapping)   |
|  Security headers applied to Response            |
+--------------------------------------------------+
   |
   v
 Browser receives HTML + security headers
```

### Key Design Decisions

| Decision | Rationale |
|---|---|
| **Custom micro-framework** (no Laravel/Symfony) | Educational clarity -- every line is auditable; no hidden magic. |
| **Singleton Application + Database** | Single entry point, single DB connection per request. Prevents accidental multi-connect. |
| **Repository pattern** over Active Record | Separates query logic from model data, easier to audit SQL for injection vulnerabilities. |
| **Dual security mode** (`secure` / `vulnerable`) | Allows side-by-side demonstration of secure vs. insecure code paths for the ESE audit. |
| **Server-side price calculation** | Prices and GST are always computed on the server; client-submitted totals are never trusted. |
| **Soft deletes everywhere** | `is_deleted` flag + `deleted_at` timestamp on every entity; no data is permanently lost. |
| **Activity logging in DB** | Every significant action (logins, bookings, admin changes, security events) is recorded to `activity_logs` for forensic review. |

---

## 2. Request Lifecycle

The following walks through exactly what happens when a browser sends `GET /book/5` to the server.

### Step-by-Step

```
 Browser: GET /book/5
    |
    v
 [1] public/index.php
    |-- require vendor/autoload.php  (Composer PSR-4)
    |-- Application::getInstance(__DIR__ . '/..')
    |       |
    |       v
    |   [2] Application::__construct()
    |       |-- loadEnvironment()      -> Dotenv parses .env
    |       |-- loadConfiguration()    -> require config/app.php, config/database.php
    |       |-- initializeCore()
    |       |       |-- startSession() -> session_set_cookie_params(), session_start()
    |       |       |                     CSRF token generated if absent
    |       |       |                     Session timeout check
    |       |       |-- new Request()  -> captures $_GET, $_POST, $_SERVER, $_COOKIE
    |       |       |-- new Response() -> empty, ready to build
    |       |       |-- new Router()   -> empty route table
    |       |       |-- Database::getInstance() -> PDO connection (singleton)
    |       |       |-- response->setSecurityHeaders() (if secure mode)
    |       |-- setupErrorHandling()   -> set_exception_handler, error_reporting
    |
    |-- $app->routes(function($router) { ... })
    |       |
    |       v
    |   [3] Route Registration
    |       Routes registered as [method, uri, pattern, action, middleware, params]
    |       Example: $router->get('/book/{roomId}', 'BookingController@bookingForm')
    |       {roomId} becomes regex (?P<roomId>[^/]+) in the pattern
    |       Group prefixes applied for /admin/* routes
    |
    |-- $app->run()
            |
            v
        [4] Router::dispatch(Request, Response)
            |-- $request->method() => 'GET'
            |-- $request->uri()    => '/book/5'
            |
            v
        [5] Router::match('GET', '/book/5')
            |-- Iterates route table, tests each regex pattern
            |-- Matches: /^\/book\/(?P<roomId>[^\/]+)$/
            |-- Extracts: matchedParams = ['roomId' => '5']
            |
            v
        [6] Middleware Pipeline
            |-- foreach ($route['middleware'] as $mw):
            |       $result = $mw->handle($request, $response)
            |       if ($result !== null) return $result  // short-circuit
            |-- All return null => continue to controller
            |
            v
        [7] Router::callAction()
            |-- Parses 'BookingController@bookingForm'
            |-- Resolves to App\Controllers\BookingController
            |-- new BookingController($request, $response)
            |       |-- BaseController::__construct() sets $this->db, $this->app
            |-- Calls $controller->bookingForm(['roomId' => '5'])
            |
            v
        [8] BookingController::bookingForm()
            |-- $this->requireLogin()  -> checks $_SESSION['user_id'], redirects if absent
            |-- Validates roomId is numeric via ctype_digit()
            |-- $roomRepo = new RoomRepository($this->db)
            |-- $room = $roomRepo->findWithHotel(5)
            |       |-- SQL: SELECT r.*, h.name... FROM rooms r JOIN hotels h...
            |       |         WHERE r.id = ? AND r.is_deleted = 0
            |       |-- Prepared statement with param [5]
            |
            v
        [9] BaseController::view('pages.booking_form', $data)
            |-- Injects globals: $user, $csrf_token, $security_mode, $app, $request
            |-- Calls Response::view('pages.booking_form', $data)
            |       |-- Resolves path: views/pages/booking_form.php
            |       |-- extract($data) into local scope
            |       |-- ob_start() -> require viewPath -> $content = ob_get_clean()
            |       |-- Loads layout: views/layouts/main.php
            |       |-- ob_start() -> require layoutPath -> return ob_get_clean()
            |       |   Layout uses $content variable to embed page content
            |
            v
        [10] Response::send()
             |-- http_response_code(200)
             |-- Sends headers:
             |     Content-Type: text/html; charset=UTF-8
             |     X-Content-Type-Options: nosniff
             |     X-Frame-Options: DENY
             |     X-XSS-Protection: 1; mode=block
             |     Strict-Transport-Security: max-age=31536000
             |     Content-Security-Policy: default-src 'self'; ...
             |     Referrer-Policy: strict-origin-when-cross-origin
             |     Permissions-Policy: geolocation=(), microphone=(), camera=()
             |-- echo $content  (full HTML page)

 Browser receives 200 OK with HTML + security headers
```

### Detailed Flow Diagram

```
+----------+     +-------+     +-----------+     +--------+     +------------+     +----------+     +------+
|  Browser | --> | index | --> |Application| --> | Router | --> | Middleware  | --> |Controller| --> | View |
|          |     | .php  |     | bootstrap |     |dispatch|     |  pipeline  |     |  method  |     |render|
+----------+     +-------+     +-----------+     +--------+     +------------+     +----------+     +------+
                                    |                                                    |
                                    v                                                    v
                               +---------+                                        +------------+
                               |  .env   |                                        | Repository |
                               | config/ |                                        +------------+
                               +---------+                                               |
                                                                                         v
                                                                                  +----------+
                                                                                  | Database  |
                                                                                  |  (PDO)   |
                                                                                  +----------+
```

---

## 3. MVC + Repository Pattern

The application uses a four-layer architecture: **Controller -> Repository -> Database**, with **Models** as plain data objects and **Views** as PHP templates.

### Layer Responsibilities

```
+-------------------------------------------------------------------+
|                        HTTP Layer                                  |
|  Controllers receive Request, validate input, orchestrate logic,  |
|  return Response (view or redirect)                               |
+-------------------------------------------------------------------+
          |                                          |
          v                                          v
+--------------------+                   +---------------------+
|    Repositories    |                   |       Views         |
|  SQL queries,      |                   |  PHP templates,     |
|  CRUD abstraction, |                   |  layout wrapping,   |
|  data mapping      |                   |  HTML output        |
+--------------------+                   +---------------------+
          |
          v
+--------------------+
|     Database       |
|  PDO, prepared     |
|  statements,       |
|  transactions      |
+--------------------+

+--------------------+
|      Models        |   (standalone data objects, no DB dependency)
|  fromArray(),      |
|  toArray(),        |
|  business helpers  |
+--------------------+
```

### Controllers

Controllers extend `BaseController`, which provides:

- Access to `$this->request`, `$this->response`, `$this->db`, `$this->app`
- `view()` -- renders a template with global variables injected
- `redirect()`, `back()` -- HTTP redirects
- `requireLogin()`, `requireAdmin()` -- access control guards
- `validateCsrf()` -- CSRF token verification
- `flash()`, `getFlash()` -- session flash messages
- `paginate()` -- array-based pagination
- `esc()` -- `htmlspecialchars` shortcut

Each controller instantiates its own Repository instances:

```php
// BookingController::createBooking()
$roomRepo = new RoomRepository($this->db);
$room = $roomRepo->findWithHotel($roomId);

$bookingRepo = new BookingRepository($this->db);
$conflicts = $bookingRepo->findByRoomAndDateRange($roomId, $checkIn, $checkOut);
```

Controller inventory:

| Controller | Purpose | Auth Required |
|---|---|---|
| `HomeController` | Homepage, room search, about, contact form | No |
| `AuthController` | Login, register, logout, forgot/reset password | No |
| `RoomController` | Room listing with filters, room detail page | No |
| `BookingController` | Create booking, list user bookings, cancel, confirmation | Yes (user) |
| `UserController` | Dashboard, profile view/update | Yes (user) |
| `AdminController` | Room/booking/user management, settings, logs | Yes (admin) |

### Models

Models are plain PHP objects with public properties, no database coupling. They provide:

- `fromArray(array $data): self` -- hydrates from an associative array (DB row)
- `toArray(): array` -- serializes to array
- Domain helper methods (no side effects):

```php
// User model
$user->isAdmin()          // role === 'admin'
$user->isLocked()         // locked_until > time()
$user->hasVerifiedEmail() // email_verified_at !== null
$user->getDisplayName()   // full_name ?: username

// Booking model
$booking->isActive()             // status in [pending, confirmed, checked_in]
$booking->isCancelled()          // status === 'cancelled'
$booking->isPaid()               // payment_status === 'paid'
$booking->getNumberOfNights()    // DateTime diff
$booking->getStatusBadgeClass()  // maps status to Bootstrap color
Booking::generateReference()     // 'BK' + uniqid suffix

// Room model
$room->isAvailable()      // is_available && maintenance_status === 'operational'
$room->getAmenities()     // JSON decode of amenities column
$room->getCurrentPrice()  // base_price (extensible for weekend/peak pricing)
```

Model inventory:

| Model | Table | Key Fields |
|---|---|---|
| `User` | `users` | username, email, password, role, failed_login_attempts, locked_until |
| `Hotel` | `hotels` | name, address, city, star_rating, amenities (JSON), check_in_time |
| `Room` | `rooms` | hotel_id, room_number, room_type, base_price, max_occupancy, amenities (JSON) |
| `Booking` | `bookings` | booking_reference, user_id, room_id, check_in/out, total_price, status |

### Repositories

Repositories extend `BaseRepository`, which provides generic CRUD:

```php
abstract class BaseRepository
{
    protected Database $db;
    protected string $table;       // e.g., 'bookings'
    protected string $modelClass;  // e.g., Booking::class

    public function find(int $id): ?object           // SELECT * WHERE id = ? AND is_deleted = 0
    public function findAll(?int $limit): array       // SELECT * WHERE is_deleted = 0
    public function findBy(string $column, $value)    // SELECT * WHERE $column = ?
    public function findOneBy(string $column, $value) // same, LIMIT 1
    public function create(array $data): int          // INSERT, returns lastInsertId
    public function update(int $id, array $data): bool// UPDATE ... WHERE id = ?
    public function delete(int $id): bool             // Soft delete: SET is_deleted = 1
    public function hardDelete(int $id): bool         // DELETE FROM ... WHERE id = ?
    public function count(array $where = []): int     // SELECT COUNT(*)
    public function exists(int $id): bool             // COUNT(*) > 0
}
```

Concrete repositories add domain-specific queries:

| Repository | Notable Methods |
|---|---|
| `UserRepository` | `findByUsername()`, `findByEmail()`, `incrementFailedLoginAttempts()`, `lockAccount()`, `updateLastLogin()` |
| `RoomRepository` | `findAvailable()`, `findWithHotel()`, `search()`, `updateAvailability()` |
| `BookingRepository` | `findByUser()`, `findByRoomAndDateRange()`, `cancel()`, `getUpcoming()`, `getStatistics()` |
| `HotelRepository` | `findActive()`, `getWithRoomCount()` |

### Why Repository Pattern over Active Record

1. **SQL auditability** -- Every query is an explicit string in the Repository, easy to grep and review for injection vulnerabilities. Active Record hides queries behind method chains.
2. **Separation of concerns** -- Models are pure data objects; Repositories own all database interaction. This makes unit testing models trivial (no DB mocking needed).
3. **Security mode toggle** -- The `Database` class can switch between prepared statements and raw queries. Having all SQL in Repositories makes this toggle point clear.
4. **Educational value** -- Students can read each Repository and see exactly what SQL hits the database, reinforcing secure coding practices.

---

## 4. Core Framework

### Application.php -- Singleton Bootstrap

`Application` is the central bootstrap class. It uses the Singleton pattern -- one instance per request.

**Constructor sequence:**

```
__construct(basePath)
  |-- loadEnvironment()       Dotenv::createImmutable()->load()
  |-- loadConfiguration()     require config/app.php, config/database.php
  |                           Sets $this->secureMode from config
  |-- initializeCore()
  |     |-- startSession()    Configures cookie params (httponly, samesite=Strict)
  |     |                     Generates CSRF token: bin2hex(random_bytes(32))
  |     |                     Enforces session timeout
  |     |-- new Request()     Captures superglobals
  |     |-- new Response()    Empty response ready to build
  |     |-- new Router()      Empty route table
  |     |-- Database::getInstance()  PDO connection
  |     |-- setSecurityHeaders()     (if secure mode)
  |-- setupErrorHandling()    set_exception_handler, error_reporting
```

**Key methods:**

```php
Application::getInstance(?string $basePath): self  // Singleton accessor
$app->routes(callable $callback): self              // Pass Router to closure for route registration
$app->run(): void                                   // Calls Router::dispatch()
$app->config('app.security.mode'): mixed            // Dot-notation config access
$app->isSecureMode(): bool                          // Security mode check
$app->basePath('views/errors'): string              // Resolve path relative to project root
```

**Session management details:**

```php
session_set_cookie_params([
    'lifetime' => 120,          // from config
    'path'     => '/',
    'domain'   => $host,        // stripped of port
    'secure'   => false,        // true in production
    'httponly'  => true,         // prevents JS access
    'samesite' => 'Strict',     // prevents CSRF via cross-site requests
]);
```

The session timeout is checked on every request. If `$_SESSION['LAST_ACTIVITY']` is older than the configured lifetime (in minutes), the session is destroyed and a fresh one started.

### Database.php -- PDO Wrapper with Dual-Driver Support

`Database` is also a Singleton wrapping PDO. It supports both MySQL and SQLite, selected via the `DB_CONNECTION` env var.

**Connection setup:**

```php
// SQLite
$dsn = "sqlite:$database";
$this->connection = new PDO($dsn);
$this->connection->exec('PRAGMA foreign_keys = ON;');

// MySQL
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$this->connection = new PDO($dsn, $username, $password, $options);
```

Both drivers use these PDO options:

```php
PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES   => false,  // MySQL only -- real prepared statements
```

**Dual-mode query execution:**

```php
public function query(string $query, array $params = []): PDOStatement|false
{
    if (!$this->secureMode && empty($params)) {
        // VULNERABLE: direct query (for security audit demos)
        return $this->connection->query($query);
    }
    // SECURE: prepared statement
    $stmt = $this->connection->prepare($query);
    $stmt->execute($params);
    return $stmt;
}
```

**Convenience methods:**

| Method | Returns | Description |
|---|---|---|
| `fetchAll($sql, $params)` | `array` | Execute + fetchAll |
| `fetchOne($sql, $params)` | `array\|false` | Execute + fetch (single row) |
| `execute($sql, $params)` | `int` | Execute + rowCount (for INSERT/UPDATE/DELETE) |
| `lastInsertId()` | `string` | PDO::lastInsertId wrapper |
| `beginTransaction()` | `bool` | Start transaction |
| `commit()` | `bool` | Commit transaction |
| `rollback()` | `bool` | Rollback transaction |

**SQL dialect helpers** (abstract MySQL vs. SQLite differences):

```php
$db->now()                  // SQLite: datetime('now')       MySQL: NOW()
$db->today()                // SQLite: date('now')           MySQL: CURDATE()
$db->dateAdd('minutes', 30) // SQLite: datetime('now', '+30 minutes')
                            // MySQL:  DATE_ADD(NOW(), INTERVAL 30 MINUTE)
$db->dateSub('days', 1)    // SQLite: datetime('now', '-1 days')
                            // MySQL:  DATE_SUB(NOW(), INTERVAL 1 DAY)
```

These are used throughout Repositories and Controllers to keep queries portable:

```php
// UserRepository::lockAccount()
$sql = "UPDATE users SET locked_until = {$this->db->dateAdd('minutes', $minutes)} WHERE id = ?";
```

### Router.php -- Route Registration and Dispatch

The Router stores an array of route definitions and matches incoming requests against regex patterns.

**Route registration:**

```php
$router->get('/room/{id}', 'RoomController@show');
$router->post('/book', 'BookingController@createBooking');
$router->any('/api/health', 'HealthController@check');   // All methods
```

Each call to `addRoute()`:
1. Applies group prefixes (e.g., `admin` prefix for `/admin/*` routes)
2. Normalizes the URI (leading slash, no trailing slash)
3. Converts `{param}` placeholders to named capture groups: `(?P<param>[^/]+)`
4. Stores the route with its compiled regex pattern

**Route groups:**

```php
$router->group(['prefix' => 'admin'], function($router) {
    $router->get('/', 'AdminController@dashboard');       // matches /admin
    $router->get('/rooms', 'AdminController@rooms');      // matches /admin/rooms
    $router->get('/rooms/edit/{id}', 'AdminController@editRoomForm'); // matches /admin/rooms/edit/5
});
```

Groups use a stack, so they can be nested. The `applyGroupPrefix()` method concatenates all active group prefixes.

**Middleware chaining:**

```php
$router->get('/dashboard', 'UserController@dashboard')->middleware(AuthMiddleware::class);
```

Middleware can also be attached at the group level. During dispatch, middleware is executed in order; if any returns a non-null value (a Response), the pipeline short-circuits.

**Dispatch algorithm:**

```php
public function dispatch(Request $request, Response $response): mixed
{
    $route = $this->match($request->method(), $request->uri());

    if (!$route) {
        // 404: render errors/404.php or fallback HTML
    }

    // Execute middleware pipeline
    foreach ($route['middleware'] as $middleware) {
        $result = (new $middleware())->handle($request, $response);
        if ($result !== null) return $result;  // short-circuit
    }

    // Call controller action
    return $this->callAction($route, $request, $response);
}
```

**Action resolution** supports three formats:

| Format | Example | Resolution |
|---|---|---|
| String `Controller@method` | `'BookingController@bookingForm'` | `new App\Controllers\BookingController($req, $res)->bookingForm($params)` |
| Closure | `function($req, $res, $params) { ... }` | Direct invocation |
| Array `[Controller, 'method']` | `[BookingController::class, 'show']` | Instantiate and call |

The controller's method receives `$route['matchedParams']` as its argument -- an associative array like `['id' => '5', 'roomId' => '3']`.

### Request.php -- Input Access and Sanitization

`Request` captures all PHP superglobals at construction time and provides a clean API.

**Core accessors:**

```php
$request->method()        // 'GET', 'POST', etc.
$request->uri()           // '/book/5' (no query string)
$request->get('check_in') // $_GET value, sanitized in secure mode
$request->post('room_id') // $_POST value, sanitized in secure mode
$request->input('key')    // POST first, then GET fallback
$request->has('key')      // exists in POST or GET
$request->all()           // merged GET + POST
$request->file('avatar')  // $_FILES entry
$request->header('Accept')// parsed from $_SERVER['HTTP_*']
$request->cookie('theme') // $_COOKIE value
$request->ip()            // Client IP (checks X-Forwarded-For, HTTP_CLIENT_IP)
$request->userAgent()     // $_SERVER['HTTP_USER_AGENT']
$request->isAjax()        // X-Requested-With check
$request->isPost()        // method === 'POST'
$request->isSecure()      // HTTPS check
$request->segments()      // URI split by '/'
```

**Sanitization (secure mode only):**

```php
private function sanitize(mixed $value): mixed
{
    if (is_string($value)) {
        $value = trim($value);                      // trim whitespace
        $value = str_replace(chr(0), '', $value);   // remove null bytes
    }
    return $value;  // arrays are recursively sanitized
}
```

In vulnerable mode, `get()` and `post()` return raw values -- this is intentional for the security audit demonstration.

**CSRF validation:**

```php
public function validateCsrfToken(string $sessionToken): bool
{
    $token = $this->post('csrf_token') ?? $this->get('csrf_token');
    return hash_equals($sessionToken, $token ?? '');
}
```

### Response.php -- View Rendering, JSON, Redirects

`Response` builds the HTTP response and sends it.

**Building responses:**

```php
// HTML view
$response->view('pages.booking_form', ['room' => $room])->send();

// JSON
$response->json(['status' => 'ok'], 200)->send();

// Redirect
$response->redirect('/login', 302);  // calls exit internally

// File download
$response->download('/path/to/invoice.pdf', 'invoice.pdf');

// Manual
$response->setStatusCode(403)->setContent('Forbidden')->send();
```

**Security headers (secure mode):**

```php
public function setSecurityHeaders(): self
{
    $this->setHeaders([
        'X-Content-Type-Options'    => 'nosniff',
        'X-Frame-Options'           => 'DENY',
        'X-XSS-Protection'          => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy'   => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;",
        'Referrer-Policy'           => 'strict-origin-when-cross-origin',
        'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=()',
    ]);
    return $this;
}
```

**View rendering internals:**

```php
private function renderView(string $view, array $data = []): string
{
    // 1. Resolve view path: 'pages.booking_form' -> views/pages/booking_form.php
    $viewPath = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';

    // 2. Extract data to local scope
    extract($data, EXTR_SKIP);

    // 3. Render view content into $content
    ob_start();
    require $viewPath;
    $content = ob_get_clean();

    // 4. Wrap in layout (unless no_layout is set)
    $layoutPath = __DIR__ . '/../../views/layouts/main.php';
    ob_start();
    require $layoutPath;   // layout uses $content, $title, $user, etc.
    return ob_get_clean();
}
```

---

## 5. Middleware Pipeline

### How Middleware Works

Middleware classes implement a simple contract:

```php
public function handle(Request $request, Response $response): mixed
```

**Rules:**
- Return `null` to continue to the next middleware (or the controller).
- Return a `Response` object to **short-circuit** the pipeline and stop execution.

**Execution in Router::dispatch():**

```php
foreach ($route['middleware'] as $middleware) {
    if (is_string($middleware)) {
        $middleware = new $middleware();
    }
    $result = $middleware->handle($request, $response);
    if ($result !== null) {
        return $result;  // Short-circuit: skip remaining middleware and controller
    }
}
// All middleware passed -- call the controller
return $this->callAction($route, $request, $response);
```

### Middleware Inventory

#### AuthMiddleware

**Purpose:** Ensures the user is logged in and their account is not locked.

**Logic:**
1. Check `$_SESSION['user_id']` exists. If not, redirect to `/login?redirect=<current_uri>`.
2. Check `$_SESSION['locked_until']`. If the lock is still active, destroy the session and redirect to `/login?error=account_locked`.
3. If the lock has expired, clear `locked_until` from the session.
4. Return `null` to continue.

**Error handling:** Wrapped in try/catch. On any exception, redirects to `/login`.

#### CsrfMiddleware

**Purpose:** Validates the CSRF token on state-changing requests (POST, PUT, DELETE).

**Logic:**
1. Skip validation for safe methods: GET, HEAD, OPTIONS.
2. Compare `$_POST['csrf_token']` against `$_SESSION['csrf_token']` using `hash_equals()`.
3. On failure, log the event and return a 403 response: `"CSRF token validation failed"`.
4. On success, return `null` to continue.

**Key detail:** The token is not rotated per-request; it persists for the session lifetime. Rotation happens on `session_regenerate_id()` at login.

**Fail-safe:** If an exception occurs inside the middleware, it returns `null` (allows the request through) rather than crashing. This is a deliberate design choice documented in the code.

#### RateLimitMiddleware

**Purpose:** Throttles requests per IP using session-based tracking.

**Configuration** (from `config/app.php`):
```
max_requests: 100  (default)
window:       60   (seconds)
```

**Logic:**
1. Read `$_SESSION['rate_limit']` -- tracks IP, count, and window start time.
2. If no tracking data, or IP changed, or window expired: reset counter to 1.
3. Increment counter on each request.
4. If counter exceeds `max_requests`: log a security event to `activity_logs`, return 429 with `Retry-After` header.
5. Otherwise, return `null`.

**Logging:** Rate limit violations are recorded as `is_security_event = 1` with severity `warning` in the `activity_logs` table.

#### LoggingMiddleware

**Purpose:** Records every HTTP request to `activity_logs` for audit trails. Never blocks.

**Logic:**
1. Log the request method + URI, client IP, user agent.
2. For POST requests, serialize `$_POST` with sensitive fields redacted:
   ```php
   private const SENSITIVE_FIELDS = [
       'password', 'password_confirm', 'current_password', 'new_password',
       'csrf_token', 'credit_card', 'card_number', 'cvv', 'cvc', 'ssn', 'token',
   ];
   ```
3. Scan the URI, user agent, and POST data for attack patterns:
   ```php
   private const ATTACK_PATTERNS = [
       'UNION', 'SELECT', '<script', 'javascript:', 'onerror',
       '../', '/etc/passwd', 'exec(', 'system(', ...
   ];
   ```
4. If an attack pattern is detected, mark the log entry as `is_security_event = 1` with severity `warning`.
5. Always return `null` -- never blocks requests.

---

## 6. View System

### Dot Notation Mapping

View names use dot notation that maps to the filesystem:

| View Name | File Path |
|---|---|
| `pages.home` | `views/pages/home.php` |
| `pages.booking_form` | `views/pages/booking_form.php` |
| `admin.dashboard` | `views/admin/dashboard.php` |
| `errors.404` | `views/errors/404.php` |
| `layouts.main` | `views/layouts/main.php` |

### Layout Wrapping

Every view is automatically wrapped in `views/layouts/main.php` unless `$data['no_layout']` is set to `true`.

The layout defines:
- `<!DOCTYPE html>` document structure
- Bootstrap 5 CSS and Font Awesome from CDN
- Custom CSS (`/assets/css/style.css`)
- Navigation bar with conditional menu items (login/register vs. user dropdown)
- Flash message rendering from `$_SESSION['flash']`
- `<?= $content ?? '' ?>` placeholder where the page view is injected
- Footer with hotel contact info
- Bootstrap JS bundle and custom JS (`/assets/js/app.js`)

**Layout structure (simplified):**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($title ?? 'Grand Plaza Hotel & Resort') ?></title>
    <!-- Bootstrap 5, Font Awesome, custom CSS -->
</head>
<body>
    <nav class="navbar">
        <!-- Dynamic nav: Home, Rooms, About, Contact -->
        <!-- Conditional: logged-in user dropdown vs. Login/Register links -->
        <!-- Admin link if role === 'admin' -->
    </nav>

    <main class="py-4">
        <div class="container">
            <!-- Flash messages -->
            <?php if (isset($_SESSION['flash'])): ?>
                <!-- render alert-success or alert-danger for each flash -->
            <?php endif; ?>

            <!-- Page content injected here -->
            <?= $content ?? '' ?>
        </div>
    </main>

    <footer><!-- Hotel address, phone, email, copyright --></footer>
    <!-- Bootstrap JS, custom JS -->
</body>
</html>
```

### Global Variables Injected into Every View

`BaseController::view()` injects these variables before rendering:

```php
$data['app']           = $this->app;           // Application instance
$data['request']       = $this->request;       // Request instance
$data['user']          = $this->getCurrentUser(); // ['id', 'username', 'role'] or null
$data['csrf_token']    = $_SESSION['csrf_token'] ?? '';
$data['security_mode'] = $this->app->isSecureMode() ? 'secure' : 'vulnerable';
```

Views use these directly:

```php
<!-- CSRF token in forms -->
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<!-- Conditional content based on user -->
<?php if ($user): ?>
    Welcome, <?= htmlspecialchars($user['username']) ?>
<?php endif; ?>
```

### Components

Reusable partials live in `views/components/`:

- **pagination.php** -- Renders Bootstrap pagination controls. Expects a `$pagination` array with `current_page`, `total_pages`, `total`, `has_prev`, `has_next`. Preserves existing query parameters and uses `htmlspecialchars()` for all URL output.

Components are included via `require` from within view templates.

### Error Pages

| File | Status | Content |
|---|---|---|
| `views/errors/403.php` | 403 Forbidden | "Access Denied" message |
| `views/errors/404.php` | 404 Not Found | "Page Not Found" with link home |
| `views/errors/500.php` | 500 Internal Server Error | Generic error page |

Error pages are wrapped in the main layout when possible (the exception handler in `Application` attempts layout wrapping for 500 errors).

---

## 7. Database Architecture

### Dual-Driver Support

The system supports two database backends, switchable via `DB_CONNECTION` in `.env`:

| Driver | Use Case | DSN Pattern |
|---|---|---|
| `sqlite` | Local development, testing | `sqlite:/path/to/database.sqlite` |
| `mysql` | Production, CI | `mysql:host=...;port=...;dbname=...;charset=utf8mb4` |

SQLite enables `PRAGMA foreign_keys = ON` on connect. MySQL uses `utf8mb4` charset with InnoDB engine.

### SQL Dialect Abstraction

Because MySQL and SQLite have different date/time function syntax, the `Database` class provides helper methods used throughout the codebase:

```php
// Instead of hardcoding MySQL syntax:
"WHERE created_at > NOW()"

// Use the abstraction:
"WHERE created_at > {$this->db->now()}"
// SQLite output: WHERE created_at > datetime('now')
// MySQL output:  WHERE created_at > NOW()
```

### Connection as Singleton

`Database::getInstance()` ensures exactly one PDO connection per request. The Singleton is enforced with:
- Private constructor
- Private `__clone()`
- `__wakeup()` throws RuntimeException

### Schema Overview

```
+----------+       +--------+       +----------+
|  users   |<------| hotels |       | sessions |
+----------+  1:N  +--------+       +----------+
     |                  |
     | 1:N              | 1:N
     v                  v
+----------+       +--------+
| bookings |------>|  rooms |
+----------+  N:1  +--------+
     |
     v
+---------------+
| activity_logs |
+---------------+
```

**Table details:**

#### users
```sql
id, username (UNIQUE), email (UNIQUE), password, full_name, phone,
role ('user'|'admin'),
failed_login_attempts, locked_until, last_login, last_login_ip,
email_verified_at, email_verification_token,
password_reset_token, password_reset_expires,
is_active,
created_at, updated_at, created_by, updated_by,
is_deleted, deleted_at, deleted_by
```

#### hotels
```sql
id, name, description, address, city, state, country, zip_code,
phone, email, website, star_rating (1-5),
amenities (JSON), check_in_time, check_out_time,
is_active,
created_at, updated_at, created_by, updated_by,
is_deleted, deleted_at
```

#### rooms
```sql
id, hotel_id (FK), room_number, room_type ('single'|'double'|'suite'|'deluxe'|'presidential'),
floor_number, description,
base_price, weekend_price, peak_season_price,
max_occupancy, num_beds, bed_type,
amenities (JSON), square_feet, view_type,
is_available, maintenance_status ('operational'|'maintenance'|'out_of_service'),
images (JSON),
created_at, updated_at, created_by,
is_deleted
UNIQUE(hotel_id, room_number)
```

#### bookings
```sql
id, booking_reference (UNIQUE), user_id (FK), room_id (FK),
check_in, check_out, booking_date,
num_guests, special_requests,
base_price, tax_amount, discount_amount, total_price,
status ('pending'|'confirmed'|'checked_in'|'checked_out'|'cancelled'|'no_show'),
payment_status ('unpaid'|'partial'|'paid'|'refunded'),
cancelled_at, cancelled_by (FK), cancellation_reason,
created_at, updated_at, created_by (FK),
is_deleted
```

#### activity_logs
```sql
id, user_id (FK, nullable),
action, entity_type, entity_id, description,
ip_address, user_agent, request_data,
severity ('info'|'warning'|'error'|'critical'),
is_security_event (boolean),
created_at
```

#### sessions
```sql
id (PK, VARCHAR), user_id (FK), ip_address, user_agent,
csrf_token, payload, last_activity
```

### Soft Delete Pattern

Every entity table has:
```sql
is_deleted  BOOLEAN DEFAULT 0
deleted_at  DATETIME NULL
deleted_by  INTEGER NULL       -- (on users table)
```

All `BaseRepository` methods automatically filter: `WHERE is_deleted = 0`.

The `delete()` method performs a soft delete:
```php
public function delete(int $id): bool
{
    $sql = "UPDATE {$this->table} SET is_deleted = 1, deleted_at = {$now} WHERE id = ?";
    return $this->db->execute($sql, [$id]) > 0;
}
```

A `hardDelete()` method exists for cases requiring physical removal.

### Audit Fields

Every entity table includes:
```sql
created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
created_by  INTEGER NULL  -- FK to users.id
```

These are populated by controllers when creating/updating records:
```php
$bookingRepo->create([
    // ...
    'created_at' => date('Y-m-d H:i:s'),
    'created_by' => $userId,
]);
```

### Prepared Statements

In secure mode, **every** database query uses prepared statements:

```php
$stmt = $this->connection->prepare($query);
$stmt->execute($params);
```

The PDO options disable emulated prepares on MySQL (`PDO::ATTR_EMULATE_PREPARES => false`), ensuring the database server performs real parameter binding.

---

## 8. Authentication & Authorization

### Session-Based Authentication

Authentication uses PHP's native session mechanism. No JWTs, no external auth providers.

**Session variables set on login:**
```php
$_SESSION['user_id']       = $user->id;
$_SESSION['username']      = $user->username;
$_SESSION['role']          = $user->role;       // 'user' or 'admin'
$_SESSION['LAST_ACTIVITY'] = time();
```

### Password Hashing

**Secure mode:** bcrypt with cost 12.
```php
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

**Verification:**
```php
$passwordValid = password_verify($password, $user->password);
```

**Vulnerable mode** (for audit demos): plaintext comparison `$password === $user->password`.

**Password migration utility** (`src/Utils/PasswordMigration.php`): A CLI script that finds users with non-bcrypt passwords (not starting with `$2y$`) and rehashes them. Run as:
```bash
php src/Utils/PasswordMigration.php
```

### Login Flow

```
1. AuthController::login() receives POST
2. Validate CSRF token
3. $userRepo->findByUsername($username)
4. If not found -> "Invalid credentials" (generic message)
5. $user->isLocked() -> "Account is locked. Please try again later."
6. password_verify($password, $user->password)
7. If invalid:
   a. $userRepo->incrementFailedLoginAttempts($user->id)
   b. If failed_login_attempts >= 5 -> $userRepo->lockAccount($user->id, 30)
      "Too many failed attempts. Account locked for 30 minutes."
   c. Otherwise -> "Invalid credentials"
8. If valid:
   a. $userRepo->resetFailedLoginAttempts($user->id)
   b. $userRepo->updateLastLogin($user->id, $request->ip())
   c. session_regenerate_id(true)  // prevent session fixation
   d. Set $_SESSION variables
   e. Redirect to ?redirect= param or '/'
```

### Role-Based Access Control

Two roles: `user` and `admin`. Enforced via `BaseController` guards:

**requireLogin():**
```php
protected function requireLogin(): void
{
    if (!$this->isLoggedIn()) {
        $this->redirect('/login?redirect=' . urlencode($this->request->uri()));
        exit;
    }
}
```

**requireAdmin():**
```php
protected function requireAdmin(): void
{
    $this->requireLogin();  // must be logged in first

    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        // Render 403 page and exit
    }
}
```

Usage in controllers:
```php
// BookingController -- any logged-in user
public function bookingForm(array $params = []) {
    $this->requireLogin();
    // ...
}

// AdminController -- admin only
public function dashboard(array $params = []) {
    $this->requireAdmin();
    // ...
}
```

### Account Lockout

After **5 failed login attempts**, the account is locked for **30 minutes**:

```php
// UserRepository::lockAccount()
// SQLite:
"UPDATE users SET locked_until = datetime('now', '+30 minutes') WHERE id = ?"
// MySQL:
"UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?"
```

The lock is checked both:
- In `AuthController::login()` via `$user->isLocked()`
- In `AuthMiddleware` via `$_SESSION['locked_until']`

On successful login, `resetFailedLoginAttempts()` clears the counter and `locked_until`.

### Password Reset

```
1. User submits email to /forgot-password
2. AuthController generates token: bin2hex(random_bytes(32))
3. Token + expiry (1 hour) saved to users table
4. Reset link shown (in demo) or emailed (in production)
5. User clicks /reset-password?token=...
6. AuthController validates token exists and is not expired
7. User submits new password with confirmation
8. Password policy enforced: min 8 chars, uppercase, lowercase, number
9. New password hashed with bcrypt cost 12
10. Token, expiry cleared; failed_login_attempts reset; locked_until cleared
11. All steps logged to activity_logs
```

**Security notes:**
- Generic response on forgot-password: "If an account exists with that email, reset instructions have been sent." This prevents email enumeration.
- Token comparison uses prepared statements.

### IDOR Prevention

Booking operations explicitly verify ownership:

```php
// BookingController::cancelBooking()
if ((int)$booking->user_id !== $this->getCurrentUserId() && !$isAdmin) {
    $this->flash('error', 'Unauthorized action.');
    $this->redirect('/bookings');
    exit;
}
```

`BookingRepository::findByUser()` filters by the authenticated user's ID, not by any user-supplied parameter.

---

## 9. Configuration System

### Environment Variables (.env)

Loaded at bootstrap via `vlucas/phpdotenv`:

```php
$dotenv = Dotenv::createImmutable($this->config['basePath']);
$dotenv->load();
```

Key variables:

| Variable | Purpose | Example |
|---|---|---|
| `APP_NAME` | Application display name | `Hotel Management System` |
| `APP_ENV` | Environment (development/production) | `development` |
| `APP_DEBUG` | Debug mode toggle | `false` |
| `DB_CONNECTION` | Database driver | `mysql` or `sqlite` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection | `127.0.0.1`, `3306`, etc. |
| `SECURITY_MODE` | `secure` or `vulnerable` | `secure` |
| `CSRF_ENABLED` | CSRF protection toggle | `true` |
| `RATE_LIMIT_ENABLED` | Rate limiting toggle | `true` |
| `RATE_LIMIT_MAX_REQUESTS` | Requests per window | `100` |
| `RATE_LIMIT_WINDOW` | Window in seconds | `60` |
| `PASSWORD_ALGO` | Hash algorithm | `BCRYPT` |
| `PASSWORD_COST` | bcrypt cost factor | `12` |
| `SESSION_LIFETIME` | Session timeout in minutes | `120` |

### Config Files

**config/app.php** -- Returns an associative array merging env vars with defaults:

```php
return [
    'name'     => $_ENV['APP_NAME'] ?? 'Hotel Management System',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'security' => [
        'mode'          => $_ENV['SECURITY_MODE'] ?? 'secure',
        'csrf_enabled'  => filter_var($_ENV['CSRF_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'password_algo' => $_ENV['PASSWORD_ALGO'] ?? 'BCRYPT',
        'password_cost' => (int)($_ENV['PASSWORD_COST'] ?? 12),
    ],
    'session' => [
        'lifetime'  => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'http_only' => true,
        'same_site' => 'Strict',
    ],
    'rate_limit' => [ ... ],
    'upload'     => [ ... ],
    'paths'      => [ 'root' => ..., 'logs' => ..., 'views' => ..., ],
];
```

**config/database.php** -- Database connection definitions:

```php
return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'sqlite',
    'connections' => [
        'mysql'  => [ 'driver' => 'mysql',  'host' => ..., 'options' => [ PDO::ATTR_EMULATE_PREPARES => false ] ],
        'sqlite' => [ 'driver' => 'sqlite', 'database' => ..., 'options' => [ ... ] ],
    ],
];
```

### Dot-Notation Access

`Application::config()` supports dot-notation traversal:

```php
$app->config('app.security.mode');         // 'secure'
$app->config('app.session.lifetime');      // 120
$app->config('database.default');          // 'sqlite'
$app->config('app.nonexistent', 'fallback'); // 'fallback'
```

Implementation:
```php
public function config(?string $key = null, $default = null)
{
    $keys = explode('.', $key);
    $value = $this->config;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}
```

---

## 10. Directory Map

```
hotel_management_system/
|
|-- public/                          # Web-accessible document root
|   |-- index.php                    # Front controller: all requests enter here
|   |-- assets/                      # Static files (CSS, JS, images)
|       |-- css/style.css            # Custom styles
|       |-- js/app.js                # Custom JavaScript
|       |-- images/                  # Static images, favicon
|
|-- src/                             # Application source code (PSR-4: App\)
|   |-- Core/                        # Framework core classes
|   |   |-- Application.php          # Singleton bootstrap: env, config, session, DB, error handling
|   |   |-- Database.php             # PDO wrapper: singleton, dual-driver, prepared statements, dialect helpers
|   |   |-- Router.php               # Route registration, regex matching, group prefixes, middleware dispatch
|   |   |-- Request.php              # HTTP request: input access, sanitization, CSRF validation, IP detection
|   |   |-- Response.php             # HTTP response: view rendering, JSON, redirects, security headers
|   |
|   |-- Controllers/                 # HTTP request handlers
|   |   |-- BaseController.php       # Abstract parent: view(), redirect(), requireLogin(), validateCsrf(), paginate()
|   |   |-- HomeController.php       # Homepage, room search, about page, contact form
|   |   |-- AuthController.php       # Login, register, logout, forgot-password, reset-password
|   |   |-- RoomController.php       # Room listing with filters, room detail page
|   |   |-- BookingController.php    # Create booking, list bookings, cancel booking, confirmation page
|   |   |-- UserController.php       # User dashboard, profile view/update
|   |   |-- AdminController.php      # Admin panel: rooms, bookings, users, settings, logs
|   |
|   |-- Models/                      # Data entities (plain PHP objects, no DB coupling)
|   |   |-- User.php                 # User entity: isAdmin(), isLocked(), hasVerifiedEmail()
|   |   |-- Hotel.php                # Hotel entity: getAmenities(), getFullAddress(), getStarRating()
|   |   |-- Room.php                 # Room entity: isAvailable(), getAmenities(), getCurrentPrice()
|   |   |-- Booking.php              # Booking entity: isActive(), isPaid(), generateReference(), GST status badge helpers
|   |
|   |-- Repositories/               # Database query layer (extends BaseRepository)
|   |   |-- BaseRepository.php       # Abstract CRUD: find(), findAll(), create(), update(), delete() (soft), count()
|   |   |-- UserRepository.php       # findByUsername(), findByEmail(), lockAccount(), incrementFailedLoginAttempts()
|   |   |-- HotelRepository.php      # findActive(), getWithRoomCount()
|   |   |-- RoomRepository.php       # findAvailable(), findWithHotel(), search(), updateAvailability()
|   |   |-- BookingRepository.php    # findByUser(), findByRoomAndDateRange(), cancel(), getStatistics()
|   |
|   |-- Middleware/                   # Request pipeline filters
|   |   |-- AuthMiddleware.php        # Session auth check, account lock check
|   |   |-- CsrfMiddleware.php        # CSRF token validation on POST/PUT/DELETE
|   |   |-- RateLimitMiddleware.php   # Session-based rate limiting per IP
|   |   |-- LoggingMiddleware.php     # Request logging, sensitive field redaction, attack pattern detection
|   |
|   |-- Utils/                        # Standalone utilities
|       |-- PasswordMigration.php     # CLI script: rehash plaintext passwords to bcrypt
|
|-- config/                           # Configuration files
|   |-- app.php                       # App config: name, debug, security, session, rate limits, paths
|   |-- database.php                  # Database config: MySQL and SQLite connection definitions
|
|-- views/                            # PHP templates (rendered by Response::renderView)
|   |-- layouts/
|   |   |-- main.php                  # Master layout: HTML skeleton, nav, flash messages, footer
|   |
|   |-- pages/                        # Public and user-facing pages
|   |   |-- home.php                  # Homepage: hotel hero, featured rooms, stats
|   |   |-- rooms.php                 # Room listing with filter form
|   |   |-- room_detail.php           # Single room detail, availability calendar
|   |   |-- search.php                # Room search results
|   |   |-- about.php                 # About the hotel
|   |   |-- contact.php               # Contact form
|   |   |-- login.php                 # Login form
|   |   |-- register.php              # Registration form
|   |   |-- forgot_password.php       # Forgot password form
|   |   |-- reset_password.php        # Reset password form (with token)
|   |   |-- dashboard.php             # User dashboard: upcoming bookings, stats, activity
|   |   |-- profile.php               # Profile view/edit form
|   |   |-- bookings.php              # User's booking list (upcoming, past, cancelled)
|   |   |-- booking_form.php          # New booking form with date/guest/room selection
|   |   |-- booking_confirmation.php  # Booking confirmation with reference number
|   |
|   |-- admin/                        # Admin panel views
|   |   |-- dashboard.php             # Admin overview: occupancy, revenue, today's check-ins/outs
|   |   |-- rooms.php                 # Room management list with filters
|   |   |-- room_form.php             # Add/edit room form
|   |   |-- bookings.php              # Booking management with search/filter
|   |   |-- booking_detail.php        # Single booking detail with status controls
|   |   |-- users.php                 # User management list
|   |   |-- settings.php              # Hotel settings (name, address, times)
|   |   |-- logs.php                  # Activity log viewer with severity/date filters
|   |
|   |-- components/                   # Reusable partials
|   |   |-- pagination.php            # Pagination controls with page numbers and ellipsis
|   |
|   |-- errors/                       # HTTP error pages
|       |-- 403.php                   # Forbidden
|       |-- 404.php                   # Not Found
|       |-- 500.php                   # Internal Server Error
|
|-- database/                         # Schema definitions
|   |-- schema_mysql.sql              # MySQL schema with CREATE TABLE, INSERT seed data
|   |-- schema_sqlite.sql            # SQLite schema (same structure, SQLite syntax)
|
|-- storage/                          # Runtime data (gitignored in production)
|   |-- database.sqlite               # SQLite database file
|   |-- logs/                         # PHP error logs, app logs
|   |-- cache/                        # Cache files (if used)
|   |-- uploads/                      # User uploads (if used)
|
|-- tests/                            # PHPUnit test suite
|   |-- TestCase.php                  # Base test case
|   |-- Unit/
|       |-- Models/
|       |   |-- BookingModelTest.php  # Booking model unit tests
|       |   |-- RoomModelTest.php     # Room model unit tests
|       |   |-- UserModelTest.php     # User model unit tests
|       |-- Security/
|       |   |-- CSRFTest.php          # CSRF protection tests
|       |   |-- GSTCalculationTest.php# GST tax calculation tests
|       |   |-- PasswordSecurityTest.php # Password hashing tests
|       |   |-- XSSPreventionTest.php # XSS sanitization tests
|       |-- Validation/
|           |-- InputValidationTest.php # Input validation tests
|
|-- .env                              # Environment variables (DB creds, security mode, etc.)
|-- composer.json                     # Dependencies: vlucas/phpdotenv, monolog/monolog, phpunit
|-- composer.lock                     # Locked dependency versions
|-- phpunit.xml                       # PHPUnit config: test suites, env overrides (SQLite :memory:)
|-- README.md                         # Project readme
|-- SECURITY_AUDIT.md                 # Security audit findings document
```

### Dependencies (composer.json)

| Package | Version | Purpose |
|---|---|---|
| `vlucas/phpdotenv` | ^5.5 | Parse `.env` files into `$_ENV` |
| `monolog/monolog` | ^3.5 | Structured logging (available, not heavily used yet) |
| `phpunit/phpunit` | ^10.0 | Unit/integration testing (dev only) |

**PHP requirements:** `>= 8.0` with extensions `pdo`, `mysqli`, `json`, `mbstring`.

### Running the Application

```bash
# Install dependencies
composer install

# Initialize SQLite database
sqlite3 storage/database.sqlite < database/schema_sqlite.sql

# Migrate passwords to bcrypt
php src/Utils/PasswordMigration.php

# Start development server
php -S localhost:8000 -t public
# or
composer serve
```

### Running Tests

```bash
# Run all tests (uses SQLite :memory: per phpunit.xml)
composer test
# or
./vendor/bin/phpunit
```
