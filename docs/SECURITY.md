# Security Documentation - Grand Plaza Hotel Booking System

This document describes every security measure implemented in the hotel booking system, explains why each matters, and provides code references from the actual codebase.

---

## 1. Security Architecture Overview

The application implements defense-in-depth with multiple security layers. Every inbound HTTP request passes through the following pipeline before reaching business logic:

```
Internet
   |
   v
Rate Limiting (RateLimitMiddleware)
   |  -- Blocks excessive requests (429 Too Many Requests)
   v
CSRF Check (CsrfMiddleware / BaseController::validateCsrf)
   |  -- Rejects forged cross-origin POST requests
   v
Authentication Check (AuthMiddleware / BaseController::requireLogin)
   |  -- Redirects unauthenticated users to /login
   v
Authorization Check (BaseController::requireAdmin / IDOR checks)
   |  -- Enforces role-based access and resource ownership
   v
Input Validation (Controller-level server-side validation)
   |  -- Rejects malformed, out-of-range, or malicious input
   v
Prepared Statements (Database::query with PDO)
   |  -- Prevents SQL injection at the data layer
   v
Output Encoding (htmlspecialchars in all 28 view files)
   |  -- Prevents XSS in rendered HTML
   v
Response with Security Headers (Response::setSecurityHeaders)
   |  -- Adds CSP, HSTS, X-Frame-Options, and more
   v
Client
```

Each layer operates independently so that a failure in one does not compromise the entire system. Even if input validation misses a malicious value, prepared statements will prevent SQL injection, and output encoding will prevent XSS.

---

## 2. SQL Injection Prevention

### Why It Matters

SQL injection is consistently ranked as a top web application vulnerability (OWASP A03:2021 - Injection). An attacker who can inject arbitrary SQL can read, modify, or delete any data in the database, bypass authentication, and potentially execute operating system commands.

### How It Is Implemented

**All** database queries in this application use PDO prepared statements with positional `?` placeholders. The `Database` class wraps PDO and, in secure mode, always calls `prepare()` followed by `execute()` with a bound parameter array.

**Database.php -- Secure query execution:**

```php
// src/Core/Database.php -- query() method

public function query(string $query, array $params = []): PDOStatement|false
{
    try {
        if (!$this->secureMode && empty($params)) {
            // VULNERABLE: Direct query execution (for demo purposes)
            return $this->connection->query($query);
        }

        // SECURE: Prepared statement
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database Query Error: " . $e->getMessage());
        throw $e;
    }
}
```

When `secureMode` is `true`, every call to `query()`, `fetchAll()`, `fetchOne()`, and `execute()` routes through `prepare()` + `execute()`.

**Real example -- BookingRepository::findByUser():**

```php
// src/Repositories/BookingRepository.php

public function findByUser(int $userId): array
{
    $sql = "SELECT b.*, r.room_number, r.room_type, h.name as hotel_name, h.city
            FROM {$this->table} b
            JOIN rooms r ON b.room_id = r.id
            JOIN hotels h ON r.hotel_id = h.id
            WHERE b.user_id = ? AND b.is_deleted = 0
            ORDER BY b.created_at DESC";

    return $this->fetchAll($sql, [$userId]);
}
```

The `$userId` is bound as a parameter (`?`), never concatenated into the SQL string.

**Before (vulnerable) vs. After (secure):**

```php
// VULNERABLE -- string concatenation
$sql = "SELECT * FROM users WHERE username = '$username'";
$result = $pdo->query($sql);
// An attacker sending username = "admin' OR '1'='1" bypasses authentication.

// SECURE -- prepared statement with parameter binding
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$result = $stmt->fetch();
// The database treats $username as a literal value, not as SQL syntax.
```

### Additional Safeguards

- **ID validation**: All numeric IDs are validated with `ctype_digit()` and cast to `(int)` before reaching any query.
- **Allowlist validation**: Dynamic filter values (room types, booking statuses) are validated against hardcoded allowlists using `in_array($value, $allowed, true)` before being used in query construction.
- **LIKE clauses**: Search queries use `LIKE ?` with the `%` wildcard prepended in PHP, never concatenated into SQL.
- **ORDER BY**: Sort direction is determined by a `switch/case` with hardcoded SQL clauses, never from user input.

---

## 3. Cross-Site Scripting (XSS) Prevention

### Why It Matters

XSS (OWASP A03:2021 - Injection) allows an attacker to inject JavaScript into pages viewed by other users. This can steal session cookies, redirect users to phishing sites, or perform actions on behalf of the victim.

### How It Is Implemented

**Output encoding on every dynamic value in all view files:**

Every piece of user-controlled or database-sourced data is escaped using `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8` before being rendered in HTML. This converts special characters (`<`, `>`, `"`, `'`, `&`) into their HTML entity equivalents, making it impossible for injected content to be interpreted as HTML or JavaScript.

**Example from views/pages/rooms.php:**

```php
<p class="text-muted mb-0">
    <?= htmlspecialchars($hotel->name, ENT_QUOTES, 'UTF-8') ?>
</p>
```

If `$hotel->name` contained `<script>alert('xss')</script>`, the rendered output would be:

```html
<p class="text-muted mb-0">
    &lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;
</p>
```

The browser displays the text literally instead of executing it.

**Controller-level helper method (BaseController):**

```php
// src/Controllers/BaseController.php

protected function esc(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

Controllers use `$this->esc()` when embedding values in flash messages or other controller-generated output:

```php
// src/Controllers/BookingController.php line 231
$this->flash('success', 'Booking confirmed! Your reference number is <strong>'
    . $this->esc($reference) . '</strong>.');
```

### Input Sanitization (Defense-in-Depth)

In addition to output encoding, text inputs are sanitized on the way in:

```php
// src/Controllers/BookingController.php lines 144-145
$specialRequests = strip_tags($specialRequests);
if (mb_strlen($specialRequests) > 500) { ... }
```

`strip_tags()` removes HTML/script tags before storage, so even if output encoding were accidentally omitted somewhere, the stored value would be tag-free.

### Content Security Policy Header

The CSP header provides a second line of defense by restricting which scripts the browser may execute:

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
```

This prevents the browser from loading scripts from arbitrary domains, limiting the damage even if an attacker manages to inject a `<script src="https://evil.com/steal.js">` tag.

### Output Encoding vs. Input Sanitization

This project uses **both** strategies:

| Strategy | When | Purpose |
|---|---|---|
| Output encoding (`htmlspecialchars`) | At render time in views | Prevents browser interpretation of injected HTML/JS |
| Input sanitization (`strip_tags`) | At input time in controllers | Removes HTML tags before storage as defense-in-depth |

Output encoding is the primary defense because it operates at the point of output and cannot be bypassed by data entering through alternative paths (database imports, API calls, etc.).

---

## 4. Cross-Site Request Forgery (CSRF) Protection

### Why It Matters

CSRF (OWASP A01:2021 - Broken Access Control) tricks an authenticated user's browser into submitting a forged request (e.g., cancelling a booking, changing a password) from a malicious site. Because the browser automatically sends session cookies, the server cannot distinguish a legitimate request from a forged one without an additional anti-CSRF token.

### How It Is Implemented

**Step 1 -- Token generation on session start (Application.php):**

```php
// src/Core/Application.php -- startSession()

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

`random_bytes(32)` produces 32 bytes of cryptographically secure randomness. `bin2hex()` converts it to a 64-character hexadecimal string. The token is stored in the server-side session.

**Step 2 -- Token embedded in every form (BaseController):**

```php
// src/Controllers/BaseController.php -- view()

$data['csrf_token'] = $_SESSION['csrf_token'] ?? '';
```

Every view receives `$csrf_token` as a template variable. Forms include it as a hidden field:

```html
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
```

**Step 3 -- Token validated on every POST request (BaseController):**

```php
// src/Controllers/BaseController.php -- validateCsrf()

protected function validateCsrf(): bool
{
    if (!$this->app->config('app.security.csrf_enabled')) {
        return true; // CSRF disabled (vulnerable mode)
    }

    $token = $this->request->post('csrf_token');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return hash_equals($sessionToken, $token ?? '');
}
```

`hash_equals()` performs a timing-safe comparison, preventing an attacker from inferring the correct token character-by-character based on response timing differences.

**Step 4 -- Middleware-level enforcement (CsrfMiddleware):**

```php
// src/Middleware/CsrfMiddleware.php

if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
    return null; // Safe methods are not validated
}

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
    error_log(sprintf("CSRF validation failed: IP=%s URI=%s Method=%s",
        $request->ip(), $request->uri(), $request->method()));
    $response->setStatusCode(403)->setContent('CSRF token validation failed');
    $response->send();
    return $response;
}
```

The middleware provides an additional enforcement layer. Failed CSRF attempts are logged with the source IP and target URI.

**Token rotation**: The CSRF token is regenerated when `session_regenerate_id()` is called on login, which ties the token to the authenticated session.

### Coverage

CSRF validation is enforced on all 15 POST endpoints across all controllers:
- `AuthController`: login, register, forgotPassword, resetPassword
- `BookingController`: createBooking, cancelBooking
- `UserController`: updateProfile
- `AdminController`: updateSettings, addRoom, editRoom, toggleRoom, updateBookingStatus, updatePaymentStatus, toggleUserStatus, changeUserRole
- `HomeController`: submitContact

---

## 5. Authentication Security

### Password Hashing

Passwords are hashed using bcrypt with a cost factor of 12, which makes brute-force attacks computationally expensive. Each hash includes a unique random salt, so identical passwords produce different hashes.

**Registration (AuthController::register):**

```php
// src/Controllers/AuthController.php line 202
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

**Login verification (AuthController::login):**

```php
// src/Controllers/AuthController.php lines 80-81
$passwordValid = password_verify($password, $user->password);
```

`password_verify()` extracts the salt and cost factor from the stored hash, re-hashes the input, and compares securely. It is timing-safe by design.

**Password reset (AuthController::resetPassword):**

```php
// src/Controllers/AuthController.php line 431
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
```

### Account Lockout

After 5 consecutive failed login attempts, the account is locked for 30 minutes. This prevents brute-force password guessing.

```php
// src/Controllers/AuthController.php lines 88-96

// Increment failed attempts
$userRepo->incrementFailedLoginAttempts($user->id);

// Lock account after 5 failed attempts (secure mode only)
if ($this->app->isSecureMode() && $user->failed_login_attempts >= 4) {
    $userRepo->lockAccount($user->id, 30);
    $this->flash('error', 'Too many failed attempts. Account locked for 30 minutes.');
}
```

On successful login, the counter is reset:

```php
// src/Controllers/AuthController.php line 104
$userRepo->resetFailedLoginAttempts($user->id);
```

The `User::isLocked()` method checks if the lock has expired:

```php
// Checked before password verification
if ($user->isLocked()) {
    $this->flash('error', 'Account is locked. Please try again later.');
    $this->redirect('/login');
    exit;
}
```

### Session Security

**HttpOnly cookies** -- JavaScript cannot access the session cookie, preventing theft via XSS:

```php
// src/Core/Application.php -- startSession()
session_set_cookie_params([
    'lifetime' => $config['lifetime'] ?? 120,
    'path' => '/',
    'domain' => $domain,
    'secure' => $config['secure'] ?? false,
    'httponly' => $config['http_only'] ?? true,
    'samesite' => $config['same_site'] ?? 'Strict',
]);
```

**SameSite=Strict** -- The browser does not send the session cookie on cross-origin requests, providing an additional layer of CSRF protection.

**Session regeneration on login** -- Prevents session fixation attacks where an attacker pre-sets a session ID:

```php
// src/Controllers/AuthController.php lines 108-109
if ($this->app->isSecureMode()) {
    session_regenerate_id(true);
}
```

The `true` parameter deletes the old session file.

**Configurable session timeout** -- Sessions expire after a period of inactivity:

```php
// src/Core/Application.php -- startSession()
if (isset($_SESSION['LAST_ACTIVITY'])) {
    $timeout = $config['lifetime'] ?? 120;
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout * 60) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['LAST_ACTIVITY'] = time();
```

### Password Reset

The password reset flow is designed to be secure against token brute-force and user enumeration attacks.

**Cryptographic token generation:**

```php
// src/Controllers/AuthController.php lines 298-299
$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
```

A 64-character hex token (256 bits of entropy) is generated using the OS CSPRNG. Brute-forcing this is computationally infeasible.

**1-hour expiry**: Tokens are time-limited. After use or expiry, they cannot be reused:

```php
// src/Controllers/AuthController.php lines 435-439
$userRepo->update((int)$userData['id'], [
    'password' => $hashedPassword,
    'password_reset_token' => null,        // Cleared after use
    'password_reset_expires' => null,       // Cleared after use
    'failed_login_attempts' => 0,
    'locked_until' => null,
]);
```

**User enumeration prevention**: The same success message is returned regardless of whether the email exists in the database:

```php
// src/Controllers/AuthController.php line 326
$this->flash('success', 'If an account exists with that email, reset instructions have been sent.');
```

### Password Policy (reset and profile update)

- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number

```php
// src/Controllers/AuthController.php lines 406-416
if (strlen($newPassword) < 8) {
    $errors[] = 'Password must be at least 8 characters long';
}
if (!preg_match('/[A-Z]/', $newPassword)) {
    $errors[] = 'Password must contain at least one uppercase letter';
}
if (!preg_match('/[a-z]/', $newPassword)) {
    $errors[] = 'Password must contain at least one lowercase letter';
}
if (!preg_match('/[0-9]/', $newPassword)) {
    $errors[] = 'Password must contain at least one number';
}
```

---

## 6. Authorization and Access Control

### Role-Based Access Control (RBAC)

The system has two roles:

| Role | Capabilities |
|---|---|
| `user` | View rooms, create/view/cancel own bookings, edit own profile |
| `admin` | All user capabilities plus manage rooms, manage all bookings, manage users, view activity logs, change settings |

### requireLogin()

Redirects unauthenticated users to the login page, preserving the original URL for post-login redirect:

```php
// src/Controllers/BaseController.php

protected function requireLogin(): void
{
    if (!$this->isLoggedIn()) {
        $this->redirect('/login?redirect=' . urlencode($this->request->uri()));
        exit;
    }
}
```

### requireAdmin()

Returns a 403 Forbidden response for non-admin users:

```php
// src/Controllers/BaseController.php

protected function requireAdmin(): void
{
    $this->requireLogin();

    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        $this->response->setStatusCode(403);
        // ... renders 403 error page
        exit;
    }
}
```

### IDOR (Insecure Direct Object Reference) Prevention

IDOR occurs when an application uses user-supplied IDs to access resources without verifying ownership. For example, changing `/booking/5/cancel` to `/booking/6/cancel` to cancel another user's booking.

**BookingController::cancelBooking() -- ownership check:**

```php
// src/Controllers/BookingController.php lines 304-313

// IDOR prevention: verify the booking belongs to the current user
// Admin override: admins may cancel any booking
$currentUser = $this->getCurrentUser();
$isAdmin = ($currentUser['role'] ?? '') === 'admin';

if ((int)$booking->user_id !== $this->getCurrentUserId() && !$isAdmin) {
    $this->flash('error', 'Unauthorized action.');
    $this->redirect('/bookings');
    exit;
}
```

The same pattern is applied in `BookingController::confirmation()`:

```php
// src/Controllers/BookingController.php lines 389-395

// IDOR check: verify booking belongs to current user (admin bypass)
if ((int)$booking['user_id'] !== $this->getCurrentUserId() && !$isAdmin) {
    $this->flash('error', 'Unauthorized access.');
    $this->redirect('/bookings');
    exit;
}
```

**BookingController::myBookings() -- query-level filtering:**

```php
// src/Controllers/BookingController.php line 246

// IDOR prevention: findByUser filters by the authenticated user's ID
$bookings = $bookingRepo->findByUser($this->getCurrentUserId());
```

The user ID comes from the server-side session, never from user input.

---

## 7. Security Headers

All security headers are set by `Response::setSecurityHeaders()` in `src/Core/Response.php` when the application runs in secure mode:

```php
$this->setHeaders([
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;",
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
]);
```

| Header | Value | What It Prevents |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | Prevents the browser from MIME-sniffing a response away from the declared Content-Type. Stops attacks where a malicious file is served as a benign type. |
| `X-Frame-Options` | `DENY` | Prevents the page from being embedded in an `<iframe>`, blocking clickjacking attacks where a transparent overlay tricks users into clicking hidden elements. |
| `X-XSS-Protection` | `1; mode=block` | Enables the browser's built-in XSS filter and instructs it to block the page entirely rather than attempting to sanitize the response. (Legacy header; CSP provides stronger protection.) |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Instructs the browser to only connect via HTTPS for the next year, including subdomains. Prevents SSL stripping and downgrade attacks. |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;` | Restricts sources for scripts, styles, and other resources. Limits the damage of any XSS by preventing loading of attacker-controlled external scripts. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Sends only the origin (not the full URL path) in the Referer header for cross-origin requests. Prevents leaking sensitive URL parameters (tokens, IDs) to external sites. |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Disables browser APIs that the application does not need. Prevents malicious scripts from accessing the user's location, microphone, or camera. |

---

## 8. Rate Limiting

### Why It Matters

Rate limiting prevents brute-force attacks (password guessing), credential stuffing, denial-of-service, and automated scraping.

### How It Is Implemented

The `RateLimitMiddleware` (`src/Middleware/RateLimitMiddleware.php`) tracks requests per IP address using session storage.

**Configuration** (from `app.php` config, settable via `.env`):

- `max_requests`: Maximum requests per window (default: 100)
- `window`: Time window in seconds (default: 60)

**Request tracking:**

```php
// src/Middleware/RateLimitMiddleware.php

$ip = $request->ip();
$now = time();

// Initialize or reset rate limit tracking
if (
    !isset($_SESSION['rate_limit']) ||
    $_SESSION['rate_limit']['ip'] !== $ip ||
    ($now - $_SESSION['rate_limit']['window_start']) >= $this->window
) {
    $_SESSION['rate_limit'] = [
        'ip' => $ip,
        'count' => 1,
        'window_start' => $now,
    ];
    return null;
}

$_SESSION['rate_limit']['count']++;
```

**Enforcement:**

When the limit is exceeded, the middleware returns HTTP 429 with a `Retry-After` header:

```php
if ($_SESSION['rate_limit']['count'] > $this->maxRequests) {
    $this->logViolation($request);

    $retryAfter = $this->window - ($now - $_SESSION['rate_limit']['window_start']);
    $response->setStatusCode(429)
        ->setHeader('Retry-After', (string) max(1, $retryAfter))
        ->setContent('Too Many Requests. Please try again later.');
    $response->send();
    return $response;
}
```

**Violation logging:**

Rate limit violations are recorded as security events in the `activity_logs` table:

```php
$db->execute(
    "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, severity, is_security_event, created_at)
     VALUES (?, 'rate_limit_exceeded', ?, ?, ?, 'warning', 1, {$db->now()})",
    [$userId, $description, $ip, $request->userAgent()]
);
```

---

## 9. Input Validation

### Why It Matters

Server-side input validation (OWASP A03:2021 - Injection) ensures that only well-formed, expected data reaches business logic and the database. Client-side validation is easily bypassed; server-side validation is the authoritative gate.

### Validation Rules Applied

**Numeric IDs** -- Validated with `ctype_digit()` and cast to `(int)`:

```php
// src/Controllers/BookingController.php lines 29-33
if (!$roomId || !ctype_digit((string)$roomId)) {
    $this->flash('error', 'Invalid room ID.');
    $this->redirect('/rooms');
    exit;
}
```

**Date format** -- Validated with `DateTime::createFromFormat()` to catch invalid dates like `2026-02-30`:

```php
// src/Controllers/BookingController.php lines 414-422
private function isValidDate(?string $date): bool
{
    if (!$date) { return false; }
    $dt = \DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}
```

**Date range logic**:
- Check-in must be today or in the future
- Check-out must be after check-in
- Maximum stay is 30 nights

**Email** -- Validated with PHP's built-in filter:

```php
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}
```

**Guest count** -- Must be a positive integer within room capacity:

```php
if (!$numGuests || !ctype_digit((string)$numGuests) || (int)$numGuests < 1) {
    $this->flash('error', 'Number of guests must be at least 1.');
    ...
}
if ($numGuests > (int)$room['max_occupancy']) {
    $this->flash('error', 'Number of guests exceeds room capacity of ' . (int)$room['max_occupancy'] . '.');
    ...
}
```

**Text fields** -- `strip_tags()` removes HTML, `mb_strlen()` enforces maximum length:

```php
// src/Controllers/BookingController.php lines 144-150
$specialRequests = strip_tags($specialRequests);
if (mb_strlen($specialRequests) > 500) {
    $this->flash('error', 'Special requests must be 500 characters or fewer.');
    ...
}
```

**Allowlist validation** -- Room types, booking statuses, and sort options are validated against hardcoded arrays:

```php
$validTypes = ['single', 'double', 'suite', 'deluxe', 'presidential'];
if (!in_array($roomType, $validTypes, true)) { ... }
```

**Server-side price calculation (price tampering prevention)**:

The total price is **always** calculated on the server from the database room price, never from a client-submitted value:

```php
// src/Controllers/BookingController.php lines 186-191

// --- Server-side price calculation (NEVER trust client) ---
$pricePerNight = (float)$room['base_price'];
$baseTotal = round($pricePerNight * $nights, 2);
$gstRate = ($pricePerNight >= 7500) ? 0.18 : 0.12;
$taxAmount = round($baseTotal * $gstRate, 2);
$totalPrice = round($baseTotal + $taxAmount, 2);
```

If an attacker tampers with a hidden `price` field in the HTML form, it is ignored. The server recalculates from the authoritative database value.

---

## 10. Audit Logging

### Why It Matters

Audit logs provide accountability, support incident investigation, and enable detection of suspicious activity. Without logging, breaches may go undetected and forensic analysis is impossible.

### Implementation

All significant actions are recorded in the `activity_logs` table with the following fields:

| Field | Description |
|---|---|
| `user_id` | The authenticated user who performed the action (null for anonymous) |
| `action` | Machine-readable action name (e.g., `booking_created`, `rate_limit_exceeded`) |
| `entity_type` | Type of entity affected (e.g., `booking`, `user`, `room`) |
| `entity_id` | ID of the affected entity |
| `description` | Human-readable description of the action |
| `ip_address` | Client IP address |
| `user_agent` | Client User-Agent string |
| `request_data` | Sanitized POST data (sensitive fields redacted) |
| `severity` | `info`, `warning`, or `error` |
| `is_security_event` | Boolean flag for security-relevant events |
| `created_at` | Timestamp |

### Logged Events

- **Authentication**: login, registration, logout
- **Booking lifecycle**: creation, cancellation, status updates, payment updates
- **Room management**: add, edit, toggle availability
- **User management**: profile updates, password changes, status toggles, role changes
- **Password reset**: request and completion
- **Contact form**: inquiry submissions
- **Security events** (flagged with `is_security_event = 1`):
  - Rate limit violations (warning severity)
  - User status changes (warning severity)
  - User role changes (warning severity)

### LoggingMiddleware -- Request Logging and Attack Detection

The `LoggingMiddleware` (`src/Middleware/LoggingMiddleware.php`) logs every HTTP request and scans for potential attack patterns:

```php
private const ATTACK_PATTERNS = [
    'UNION', 'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP',
    '<script', 'javascript:', 'onerror', 'onload', 'eval(',
    '../', '..\\', '/etc/passwd', 'cmd=', 'exec(', 'system(',
];
```

When any of these patterns are detected in the URI, User-Agent, or POST data, the request is flagged as a security event with `warning` severity.

### Sensitive Data Redaction

The `LoggingMiddleware` redacts sensitive fields before logging POST data:

```php
private const SENSITIVE_FIELDS = [
    'password', 'password_confirm', 'current_password', 'new_password',
    'csrf_token', 'credit_card', 'card_number', 'cvv', 'cvc', 'ssn', 'token',
];

foreach (self::SENSITIVE_FIELDS as $field) {
    if (isset($postData[$field])) {
        $postData[$field] = '[REDACTED]';
    }
}
```

---

## 11. Data Protection

### Passwords

- Stored as bcrypt hashes (cost 12), never in plaintext (in secure mode)
- Password hash is never exposed in view templates
- Admin user listing explicitly excludes the password column from query results

### Configuration

- Database credentials and security settings are stored in `.env`, which is excluded from version control via `.gitignore`
- `APP_DEBUG=false` in production prevents stack traces from being displayed to users
- Error details are written to `storage/logs/php_errors.log`, not shown to end users

### Session Data

- Only minimal data is stored in the session: `user_id`, `username`, `role`, `LAST_ACTIVITY`
- No passwords, tokens, or sensitive data persist in the session after use
- Session cookies use `HttpOnly`, `SameSite=Strict`, and optionally `Secure` flags

### Database

- The application is designed for a least-privilege database user (no DDL permissions such as `CREATE`, `DROP`, `ALTER`)
- Foreign keys are enabled (`PRAGMA foreign_keys = ON` for SQLite) to enforce referential integrity

---

## 12. OWASP Top 10 (2021) Mapping

| # | OWASP Category | Implementation in This Project |
|---|---|---|
| **A01** | Broken Access Control | RBAC with `requireLogin()` and `requireAdmin()`. IDOR prevention via ownership checks on bookings (`user_id === session user_id`). Admin bypass is intentional and documented. |
| **A02** | Cryptographic Failures | Passwords hashed with bcrypt (cost 12). CSRF and reset tokens generated with `random_bytes(32)`. Session cookies use HttpOnly + SameSite=Strict. HSTS header enforces TLS. |
| **A03** | Injection | All SQL queries use PDO prepared statements with `?` placeholders. Output encoding with `htmlspecialchars(ENT_QUOTES, 'UTF-8')` on all view output. `strip_tags()` on text inputs. |
| **A04** | Insecure Design | Defense-in-depth architecture with middleware layers. Server-side price calculation prevents business logic manipulation. Date conflict checks prevent double-booking. |
| **A05** | Security Misconfiguration | Security headers set via `Response::setSecurityHeaders()`. Debug mode disabled in production. Error details logged, not displayed. Permissions-Policy disables unused browser APIs. |
| **A06** | Vulnerable and Outdated Components | Dependencies managed via Composer with `composer.lock` for reproducible builds. PHPUnit 10 for testing. |
| **A07** | Identification and Authentication Failures | Bcrypt password hashing. Account lockout after 5 failed attempts. Session regeneration on login. Configurable session timeout. Password complexity policy. |
| **A08** | Software and Data Integrity Failures | CSRF tokens on all state-changing requests. Server-side price calculation prevents client-side tampering. Input validation enforces allowlists for enumerated values. |
| **A09** | Security Logging and Monitoring Failures | `activity_logs` table captures all significant actions. `LoggingMiddleware` logs every request. Security events flagged with `is_security_event`. Attack patterns detected and logged. |
| **A10** | Server-Side Request Forgery (SSRF) | The application does not make outbound HTTP requests based on user input. No SSRF attack surface exists in the current design. |
