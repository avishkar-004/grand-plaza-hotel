# Security Audit Report - Grand Plaza Hotel Booking System

## Date: 2026-04-25
## Auditor: Automated Security Agent

---

## 1. SQL Injection Prevention
- **Status: PASS**
- **Method:** Prepared statements with PDO parameter binding (`?` placeholders)
- **Coverage:** All SQL queries across 12 controller and repository files
- **Details:**
  - All controller SQL queries use parameterized `?` placeholders with separate `$params` arrays
  - All repository methods (`BaseRepository`, `BookingRepository`, `RoomRepository`, `UserRepository`, `HotelRepository`) use parameterized queries
  - Search queries use `LIKE ?` with `%` prepended in PHP, not concatenated into SQL
  - Filter values are whitelist-validated before query construction (e.g., `in_array($statusFilter, $allowedStatuses, true)`)
  - Sort ordering uses PHP `switch/case` with hardcoded SQL clauses, never user input in ORDER BY
  - IDs are validated with `ctype_digit()` and cast to `(int)` before use
- **Notes:**
  - `BaseRepository::findBy()` and `findOneBy()` accept column name as a string parameter interpolated directly into SQL. This is safe because callers always pass hardcoded column names (e.g., `'username'`, `'email'`), never user input. However, adding a column whitelist validation would improve defense-in-depth.
  - `BaseRepository::findAll()` interpolates `$limit` and `$offset` but both are typed as `?int` and `int` respectively
  - `UserRepository::lockAccount()` interpolates `$minutes` into SQL but it is always called with a hardcoded int value (30)

## 2. Cross-Site Scripting (XSS) Prevention
- **Status: PASS (after fixes)**
- **Method:** `htmlspecialchars()` with `ENT_QUOTES`, `'UTF-8'`
- **Coverage:** All 18 audited view files
- **Issues Found and Fixed:**

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| 1 | `views/pages/profile.php:18` | `<?= $error ?>` output without escaping | Wrapped in `htmlspecialchars($error, ENT_QUOTES, 'UTF-8')` |
| 2 | `views/pages/home.php:9` | `htmlspecialchars()` missing `ENT_QUOTES, 'UTF-8'` params (hotel address) | Added `ENT_QUOTES, 'UTF-8'` |
| 3 | `views/pages/home.php:29` | `$hotel->getStarRating()` unescaped output | Wrapped in `htmlspecialchars()` |
| 4 | `views/pages/home.php:45` | `htmlspecialchars($hotel->description)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 5 | `views/pages/home.php:51` | `htmlspecialchars($hotel->getFullAddress())` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 6 | `views/pages/home.php:53` | `htmlspecialchars($hotel->phone)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 7 | `views/pages/home.php:56` | `htmlspecialchars($hotel->email)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 8 | `views/pages/home.php:69` | `htmlspecialchars(date(...check_in_time))` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 9 | `views/pages/home.php:82` | `htmlspecialchars(date(...check_out_time))` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 10 | `views/pages/home.php:113` | `htmlspecialchars($amenity)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 11 | `views/pages/home.php:135` | `htmlspecialchars($room->getFormattedType())` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 12 | `views/pages/home.php:136` | `htmlspecialchars($room->room_number)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 13 | `views/pages/home.php:139` | `htmlspecialchars($room->description)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 14 | `views/pages/home.php:143` | `htmlspecialchars($room->bed_type ?? 'Bed')` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 15 | `views/pages/home.php:145` | `htmlspecialchars($room->view_type)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 16 | `views/pages/search.php:10` | `htmlspecialchars($query ?? '')` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 17 | `views/pages/search.php:23` | `htmlspecialchars($query)` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 18 | `views/pages/search.php:37` | `htmlspecialchars($room['hotel_name'] ?? 'Hotel')` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 19 | `views/pages/search.php:39` | `htmlspecialchars($room['city'] ?? 'N/A')` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 20 | `views/pages/search.php:42` | `htmlspecialchars(ucfirst($room['room_type']))` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 21 | `views/pages/search.php:42` | `htmlspecialchars($room['room_number'])` missing params | Added `ENT_QUOTES, 'UTF-8'` |
| 22 | `views/pages/search.php:59` | `$room['id']` in `urlencode()` not cast to int | Cast to `(int)` |

- **Note:** Badge CSS classes derived from `match()` expressions with hardcoded allowlists (e.g., `'success'`, `'danger'`) are safe internal computed values and do not require escaping.

## 3. CSRF Protection
- **Status: PASS (after fixes)**
- **Method:** Token-based (`random_bytes(32)` + `hash_equals()`)
- **Coverage:** All 15 POST endpoints
- **Issues Found and Fixed:**

| # | Controller Method | Before | After |
|---|-------------------|--------|-------|
| 1 | `AuthController::login()` | Conditional: only validated when `isSecureMode()` | Now always calls `$this->validateCsrf()` |
| 2 | `AuthController::register()` | Conditional: only validated when `isSecureMode()` | Now always calls `$this->validateCsrf()` |
| 3 | `AuthController::forgotPassword()` | Conditional: only validated when `isSecureMode()` | Now always calls `$this->validateCsrf()` |
| 4 | `AuthController::resetPassword()` | Conditional: only validated when `isSecureMode()` | Now always calls `$this->validateCsrf()` |

- **Already Compliant (no changes needed):**
  - `BookingController::createBooking()` - calls `$this->validateCsrf()`
  - `BookingController::cancelBooking()` - calls `$this->validateCsrf()`
  - `UserController::updateProfile()` - calls `$this->validateCsrf()`
  - `AdminController::updateSettings()` - calls `$this->validateCsrf()`
  - `AdminController::addRoom()` - calls `$this->validateCsrf()`
  - `AdminController::editRoom()` - calls `$this->validateCsrf()`
  - `AdminController::toggleRoom()` - calls `$this->validateCsrf()`
  - `AdminController::updateBookingStatus()` - calls `$this->validateCsrf()`
  - `AdminController::updatePaymentStatus()` - calls `$this->validateCsrf()`
  - `AdminController::toggleUserStatus()` - calls `$this->validateCsrf()`
  - `AdminController::changeUserRole()` - calls `$this->validateCsrf()`
  - `HomeController::submitContact()` - calls `$this->validateCsrf()`

- **Token Implementation:** CSRF token is generated via `bin2hex(random_bytes(32))`, stored in `$_SESSION['csrf_token']`, and validated using `hash_equals()` to prevent timing attacks.

## 4. Authentication Security
- **Password Hashing:**
  - Registration (`AuthController::register()`): `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` in secure mode
  - Password Reset (`AuthController::resetPassword()`): `password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12])`
  - Profile Update (`UserController::updateProfile()`): `password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12])`
- **Password Verification:**
  - Login (`AuthController::login()`): `password_verify($password, $user->password)` in secure mode
  - Profile password change: `password_verify($currentPassword, $currentUser->password)` -- requires current password
- **Account Lockout:** After 5 failed login attempts, account locked for 30 minutes (`UserRepository::lockAccount()`)
- **Session Management:**
  - `session_regenerate_id(true)` on successful login (secure mode)
  - Session variables: `user_id`, `username`, `role`, `LAST_ACTIVITY`
- **Password Policy (enforced in resetPassword and updateProfile):**
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
- **Password Reset:**
  - Cryptographic tokens: `bin2hex(random_bytes(32))`
  - 1-hour expiry (`strtotime('+1 hour')`)
  - Token cleared after use
  - Same response regardless of email existence (prevents enumeration)
- **Note:** Registration only requires 6-character password minimum (weaker than reset/profile policy of 8 chars). Recommend aligning to 8-char minimum with complexity rules.

## 5. Authorization / Access Control
- **RBAC:** 2 roles (`admin`, `user`)
- **Admin Routes:** All admin controller methods call `$this->requireAdmin()` which checks `$_SESSION['role'] === 'admin'`
- **User Routes:** Protected routes call `$this->requireLogin()` which redirects to login if no session
- **IDOR Prevention:**
  - `BookingController::myBookings()` filters by `$this->getCurrentUserId()` -- users can only see their own bookings
  - `BookingController::cancelBooking()` verifies `$booking->user_id === $this->getCurrentUserId()` (with admin bypass)
  - `BookingController::confirmation()` verifies ownership (with admin bypass)
  - `AdminController::toggleUserStatus()` prevents admin from deactivating themselves
  - `AdminController::changeUserRole()` prevents admin from changing their own role

## 6. Security Headers
- Headers are expected to be set at the application/middleware level. Recommended headers:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `X-XSS-Protection: 1; mode=block`
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (when HTTPS enabled)
  - `Content-Security-Policy: default-src 'self'`
  - `Referrer-Policy: strict-origin-when-cross-origin`
- **Note:** Headers should be verified at the middleware/Application level. The `FORCE_HTTPS=false` in `.env` means HSTS is not active in current config.

## 7. Input Validation
- **Server-side validation on all forms:**
  - Booking: dates validated with `DateTime::createFromFormat('Y-m-d')`, check-in >= today, check-out > check-in, max 30 nights
  - Room IDs: `ctype_digit()` validation before casting to `(int)`
  - Guest count: validated as positive integer, checked against room max_occupancy
  - Special requests: `strip_tags()` applied, max 500 characters enforced
  - Admin filters: all whitelisted against allowed values (`in_array($value, $allowed, true)`)
  - Room types: validated against hardcoded allowlist (`['single', 'double', 'deluxe', 'suite', 'presidential']`)
  - Date formats: regex validated with `/^\d{4}-\d{2}-\d{2}$/` before use
  - Email: `filter_var($email, FILTER_VALIDATE_EMAIL)`
  - Phone: regex pattern validation `[0-9+\-\s()]{7,20}`
- **Price calculation:** Server-side only (`BookingController::createBooking()` calculates price from room's `base_price`, never trusts client-submitted prices)

## 8. Logging & Monitoring
- **Activity logging:** All CRUD operations logged to `activity_logs` table:
  - Booking creation, cancellation, status updates, payment updates
  - Room add/edit/toggle operations
  - User status toggling, role changes
  - Profile updates (including password changes)
  - Contact form submissions
  - Password reset requests and completions
- **Security events:** Flagged separately with `is_security_event = 1`:
  - User status toggles (warning severity)
  - User role changes (warning severity)
  - Rate limit violations (warning severity)
- **IP tracking:** IP address recorded on all log entries via `$this->request->ip()`
- **User agent tracking:** Recorded on admin actions via `$this->request->userAgent()`

## 9. Rate Limiting
- **Middleware available:** `RateLimitMiddleware` in `src/Middleware/RateLimitMiddleware.php`
- **Config:** 100 requests per 60-second window (configurable via `.env`)
- **Implementation:** Session-based tracking with IP verification
- **Violation logging:** Rate limit violations logged as security events to `activity_logs`
- **Response:** Returns HTTP 429 with `Retry-After` header
- **Note:** The middleware exists and is functional but is not explicitly wired into the route dispatch chain in `public/index.php`. The routes do not use a `->middleware()` chain. This means rate limiting depends on whether the Application bootstrap applies it globally. Recommend verifying the middleware is applied to sensitive endpoints (login, register, forgot-password, contact form).

## 10. Data Protection
- **Passwords:** Hashed with bcrypt (cost 12), never stored in plaintext in secure mode, never rendered in views
- **Admin user listing:** Explicitly selects columns WITHOUT password (`AdminController::users()` line 257-258)
- **Sensitive config:** Stored in `.env` file (database credentials, security settings)
- **APP_DEBUG:** Set to `false` in `.env` -- stack traces not shown to users
- **Error handling:** `public/index.php` catch block only shows error details when `APP_DEBUG=true`
- **Note:** `BaseRepository::find()` uses `SELECT *` which includes the password hash in the returned User model object. While no view renders the password field, the hash is technically available in view scope via `$user_data->password`. Recommend creating a `findWithoutPassword()` method or using explicit column selection for user data passed to views.

---

## Issues Found & Fixed

### XSS Fixes (22 instances across 3 files)

1. **`views/pages/profile.php` line 18** -- `$error` variable output without any escaping. This is a **high-severity** XSS vulnerability because error messages could contain reflected user input. Fixed by wrapping in `htmlspecialchars($error, ENT_QUOTES, 'UTF-8')`.

2. **`views/pages/home.php` (15 instances)** -- Multiple `htmlspecialchars()` calls missing the `ENT_QUOTES` flag and `'UTF-8'` charset parameter. Without `ENT_QUOTES`, single quotes are not escaped, which could allow attribute-context XSS if the output appears inside a single-quoted HTML attribute. All instances fixed.

3. **`views/pages/search.php` (6 instances)** -- Same missing `ENT_QUOTES, 'UTF-8'` parameters. Additionally, one instance of `$room['id']` used in `urlencode()` without `(int)` cast, which could allow injection if the value is tampered with. Fixed all instances.

### CSRF Enforcement Fixes (4 instances in 1 file)

4. **`src/Controllers/AuthController.php`** -- Four POST handler methods (`login`, `register`, `forgotPassword`, `resetPassword`) had CSRF validation wrapped in `if ($this->app->isSecureMode())` conditional, meaning CSRF protection was bypassed when not in secure mode. Fixed to always call `$this->validateCsrf()` unconditionally. The `validateCsrf()` method in `BaseController` already handles the case where CSRF is disabled via config (returns `true`), so the secure-mode check was redundant and created a bypass path.

---

## Recommendations

### High Priority

1. **Align password policy in registration:** `AuthController::register()` only requires 6-character minimum password with no complexity rules. The password reset and profile update enforce 8 characters + uppercase + lowercase + number. Registration should enforce the same policy.

2. **Remove vulnerable mode code paths:** `AuthController::login()` lines 80-85 contain a vulnerable mode code path that does direct string comparison instead of `password_verify()`. Similarly, `AuthController::register()` lines 201-206 store plaintext passwords in vulnerable mode. These intentional vulnerability toggles should be removed in production.

3. **Explicit column selection for User queries:** `BaseRepository::find()` uses `SELECT *` which returns the password hash to controller code and view scope. Recommend overriding `find()` in `UserRepository` to exclude the `password` column when the hash is not needed (e.g., for dashboard/profile display). A separate `findForAuth()` method can return the full record when password verification is needed.

### Medium Priority

4. **Apply rate limiting to routes:** Verify that `RateLimitMiddleware` is applied to sensitive endpoints (`/login`, `/register`, `/forgot-password`, `/contact`). The middleware exists but route-level middleware wiring is not visible in `public/index.php`.

5. **Column name validation in BaseRepository:** The `findBy()`, `findOneBy()`, and `count()` methods interpolate `$column` directly into SQL. While currently only called with hardcoded column names, adding a whitelist validation (`in_array($column, $this->allowedColumns)`) would prevent future misuse.

6. **Enable HTTPS:** `.env` has `FORCE_HTTPS=false`. In production, this should be `true` with HSTS headers enabled.

7. **Generate APP_KEY:** The `.env` has `APP_KEY=` (empty). A cryptographic application key should be generated and set.

8. **Session security:** Consider adding `SESSION_SECURE=true` when HTTPS is enabled, and verify `HttpOnly` and `SameSite=Strict` cookie flags are set at the PHP session configuration level.

### Low Priority

9. **Content Security Policy:** Implement a strict CSP header to prevent inline script execution.

10. **Login redirect validation:** `AuthController::login()` line 119 redirects to `$this->request->get('redirect', '/')` without validating that the redirect URL is a local path. This could enable open redirect attacks. Recommend validating that the redirect starts with `/` and does not contain `//`.

11. **Database credentials in .env:** The `.env` file contains database credentials (`DB_PASSWORD=HotelApp@Secure2026`). Ensure `.env` is in `.gitignore` and never committed to version control.

12. **Error message HTML in flash messages:** Several controllers use `implode('<br>', $errors)` to create flash messages with HTML. While `<br>` is not dangerous, this pattern of allowing HTML in flash messages creates risk if error messages ever include user input. Consider using a different approach (array of messages) or consistently escaping before display.
