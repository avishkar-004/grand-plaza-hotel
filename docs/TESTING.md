# Testing Documentation - Grand Plaza Hotel Booking System

This document covers the test suite structure, how to run tests, what each test verifies, and guidance for adding new tests.

---

## 1. Test Environment

| Component | Details |
|---|---|
| **Framework** | PHPUnit 10 |
| **Database** | In-memory SQLite (`sqlite::memory:`) -- fast, isolated, no cleanup needed |
| **Configuration** | `phpunit.xml` at project root |
| **Autoloading** | Composer PSR-4 autoloader (`vendor/autoload.php`) |

### Environment Variables (set in phpunit.xml)

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="SECURITY_MODE" value="secure"/>
    <env name="CSRF_ENABLED" value="true"/>
</php>
```

These variables ensure tests run in secure mode with CSRF enabled, using an ephemeral in-memory database that is created fresh for each test.

### PHPUnit Configuration (phpunit.xml)

```xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         testdox="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

The `testdox="true"` setting outputs human-readable test descriptions by converting method names to sentences.

---

## 2. Test Structure

```
tests/
  TestCase.php                          Base class with DB setup/teardown
  Unit/
    Models/
      UserModelTest.php                 14 tests -- User model behavior
      BookingModelTest.php              24 tests -- Booking model behavior
      RoomModelTest.php                 20 tests -- Room model behavior
    Security/
      PasswordSecurityTest.php          11 tests -- Password hashing and policy
      XSSPreventionTest.php             12 tests -- XSS output encoding
      CSRFTest.php                      10 tests -- CSRF token validation
      GSTCalculationTest.php            11 tests -- Tax calculation and price integrity
    Validation/
      InputValidationTest.php           30 tests -- Input format and sanitization
```

**Total: 132 tests across 8 test files**

---

## 3. Running Tests

### Run all tests

```bash
./vendor/bin/phpunit
```

### Run a specific test suite

```bash
# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run only integration tests (when available)
./vendor/bin/phpunit --testsuite Integration
```

### Run a specific test file

```bash
./vendor/bin/phpunit tests/Unit/Models/UserModelTest.php
```

### Run a specific test class

```bash
./vendor/bin/phpunit --filter UserModelTest
```

### Run a specific test method

```bash
./vendor/bin/phpunit --filter test_bcrypt_hash_is_secure
```

### Run with verbose output

```bash
./vendor/bin/phpunit --verbose
```

### Run with code coverage (requires Xdebug or PCOV)

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

### Run with testdox output (human-readable)

```bash
./vendor/bin/phpunit --testdox
```

This is enabled by default in `phpunit.xml`, but can be passed explicitly.

---

## 4. Test Descriptions

### TestCase.php (Base Class)

The base `TestCase` class (`tests/TestCase.php`) provides shared setup and teardown for tests that need database access:

- **`setUp()`**: Creates an in-memory SQLite database, sets error mode to exceptions, creates all tables from `database/schema_sqlite.sql`, and seeds test data (admin user, regular user, hotel, 3 rooms).
- **`tearDown()`**: Destroys the database connection, ensuring complete isolation between tests.
- **Seed data**: Admin user (id=1, bcrypt-hashed password), regular user (id=2), one hotel (id=1), three rooms (single at 4500/night, deluxe at 12500/night, suite at 24000/night in maintenance).

---

### UserModelTest.php (14 tests)

Tests the `User` model's data hydration, role checks, lock status, and display methods.

| Test Method | Description |
|---|---|
| `test_user_from_array` | Verifies that `User::fromArray()` correctly populates all properties (id, username, email, role, full_name) from an associative array |
| `test_user_to_array` | Verifies that `toArray()` serializes the User back to an associative array with correct values |
| `test_is_admin` | Confirms `isAdmin()` returns true for role=admin and false for role=user |
| `test_is_user` | Confirms `isUser()` returns true for role=user and false for role=admin |
| `test_is_locked_with_future_date` | Verifies `isLocked()` returns true when `locked_until` is in the future |
| `test_is_not_locked_with_null` | Verifies `isLocked()` returns false when `locked_until` is null |
| `test_is_not_locked_with_past_date` | Verifies `isLocked()` returns false when `locked_until` is in the past (lock expired) |
| `test_has_verified_email` | Verifies `hasVerifiedEmail()` returns true when `email_verified_at` is set, false when null |
| `test_get_display_name_with_full_name` | Confirms `getDisplayName()` returns the full name when it is set |
| `test_get_display_name_falls_back_to_username` | Confirms `getDisplayName()` falls back to the username when full_name is empty |
| `test_default_role_is_user` | Verifies a new User object defaults to role=user |
| `test_default_is_active` | Verifies a new User object defaults to is_active=true |
| `test_default_failed_login_attempts_is_zero` | Verifies a new User object defaults to failed_login_attempts=0 |
| `test_from_array_ignores_unknown_keys` | Confirms `fromArray()` silently ignores keys that do not correspond to model properties |

---

### BookingModelTest.php (24 tests)

Tests the `Booking` model's data hydration, status checks, calculated fields, and badge classes.

| Test Method | Description |
|---|---|
| `test_booking_from_array` | Verifies `Booking::fromArray()` populates all booking properties correctly |
| `test_booking_to_array` | Verifies `toArray()` serializes the Booking with correct values |
| `test_is_active_confirmed` | Confirms `isActive()` returns true for status=confirmed |
| `test_is_active_pending` | Confirms `isActive()` returns true for status=pending |
| `test_is_active_checked_in` | Confirms `isActive()` returns true for status=checked_in |
| `test_is_not_active_cancelled` | Confirms `isActive()` returns false for status=cancelled |
| `test_is_not_active_checked_out` | Confirms `isActive()` returns false for status=checked_out |
| `test_is_not_active_no_show` | Confirms `isActive()` returns false for status=no_show |
| `test_is_cancelled` | Verifies `isCancelled()` returns true only for status=cancelled |
| `test_is_completed` | Verifies `isCompleted()` returns true only for status=checked_out |
| `test_is_paid` | Verifies `isPaid()` returns true for payment_status=paid and false for unpaid |
| `test_generate_reference` | Confirms `generateReference()` produces a 10-character string starting with "BK" |
| `test_generate_reference_unique` | Confirms two consecutive calls to `generateReference()` produce different values |
| `test_number_of_nights` | Verifies `getNumberOfNights()` correctly calculates 3 nights for Jun 01-04 |
| `test_number_of_nights_one_night` | Verifies `getNumberOfNights()` returns 1 for a single-night stay |
| `test_formatted_check_in` | Confirms `getFormattedCheckIn()` returns the date in "Jun 01, 2026" format |
| `test_formatted_check_out` | Confirms `getFormattedCheckOut()` returns the date in "Jun 03, 2026" format |
| `test_get_summary` | Verifies `getSummary()` includes formatted dates and "3 nights" text |
| `test_get_summary_single_night` | Verifies `getSummary()` uses singular "1 night" (not "nights") for a one-night stay |
| `test_status_badge_class` | Tests all 7 status-to-badge-class mappings (pending=warning, confirmed=info, checked_in=primary, checked_out=success, cancelled=danger, no_show=dark, unknown=secondary) |
| `test_payment_status_badge_class` | Tests all 5 payment-status-to-badge-class mappings (unpaid=danger, partial=warning, paid=success, refunded=info, unknown=secondary) |
| `test_default_status_is_pending` | Verifies a new Booking defaults to status=pending |
| `test_default_payment_status_is_unpaid` | Verifies a new Booking defaults to payment_status=unpaid |
| `test_default_num_guests_is_one` | Verifies a new Booking defaults to num_guests=1 |

---

### RoomModelTest.php (20 tests)

Tests the `Room` model's data hydration, availability logic, JSON field handling, and display methods.

| Test Method | Description |
|---|---|
| `test_room_from_array` | Verifies `Room::fromArray()` populates all room properties correctly |
| `test_room_to_array` | Verifies `toArray()` serializes the Room with correct values |
| `test_get_amenities_with_json` | Confirms `getAmenities()` correctly decodes a JSON array of amenities |
| `test_get_amenities_with_null` | Confirms `getAmenities()` returns an empty array when amenities is null |
| `test_get_amenities_with_invalid_json` | Confirms `getAmenities()` returns an empty array for malformed JSON (graceful degradation) |
| `test_set_amenities` | Verifies `setAmenities()` encodes an array as JSON and stores it in the property |
| `test_get_images_with_json` | Confirms `getImages()` correctly decodes a JSON array of image filenames |
| `test_get_images_with_null` | Confirms `getImages()` returns an empty array when images is null |
| `test_set_images` | Verifies `setImages()` encodes an array as JSON |
| `test_get_formatted_type_single` | Confirms `getFormattedType()` returns "Single" for room_type=single |
| `test_get_formatted_type_deluxe` | Confirms `getFormattedType()` returns "Deluxe" for room_type=deluxe |
| `test_get_formatted_type_presidential` | Confirms `getFormattedType()` returns "Presidential" for room_type=presidential |
| `test_is_available_when_operational_and_available` | Confirms `isAvailable()` returns true only when both is_available=true and maintenance_status=operational |
| `test_is_not_available_when_unavailable` | Confirms `isAvailable()` returns false when is_available=false (even if operational) |
| `test_is_not_available_when_maintenance` | Confirms `isAvailable()` returns false when maintenance_status=maintenance (even if is_available=true) |
| `test_is_not_available_when_out_of_service` | Confirms `isAvailable()` returns false when maintenance_status=out_of_service |
| `test_get_current_price` | Verifies `getCurrentPrice()` returns the base_price value |
| `test_get_display_name` | Confirms `getDisplayName()` returns formatted string "Deluxe - Room 202" |
| `test_default_values` | Verifies default property values for a new Room (is_available=true, maintenance_status=operational, max_occupancy=2, num_beds=1, is_deleted=false) |
| `test_from_array_ignores_unknown_keys` | Confirms `fromArray()` silently ignores unrecognized keys |

---

### PasswordSecurityTest.php (11 tests)

Tests PHP's bcrypt hashing functions and password policy enforcement, validating the same security mechanisms used in `AuthController`.

| Test Method | Description |
|---|---|
| `test_bcrypt_hash_is_secure` | Verifies bcrypt with cost 12 produces a hash starting with `$2y$12$` and that `password_verify()` confirms the password |
| `test_default_bcrypt_cost` | Verifies default bcrypt hashing works and produces a `$2y$` prefixed hash |
| `test_wrong_password_fails` | Confirms `password_verify()` returns false for wrong password, empty string, and case-different password |
| `test_same_password_different_hashes` | Proves that hashing the same password twice produces different hashes (salt uniqueness) while both still verify |
| `test_password_policy_strong` | Validates a good password (8+ chars, uppercase, lowercase, digit) passes all policy checks |
| `test_password_policy_too_short` | Confirms a 4-character password fails the length check |
| `test_password_policy_no_uppercase` | Confirms "password123" fails the uppercase requirement |
| `test_password_policy_no_lowercase` | Confirms "PASSWORD123" fails the lowercase requirement |
| `test_password_policy_no_digit` | Confirms "PasswordOnly" fails the digit requirement |
| `test_hash_length` | Verifies bcrypt hashes are exactly 60 characters |
| `test_plaintext_password_not_stored` | Confirms the original password string does not appear anywhere in the hash |

---

### XSSPreventionTest.php (12 tests)

Tests the `htmlspecialchars()` encoding function that is used across all view files to prevent XSS.

| Test Method | Description |
|---|---|
| `test_htmlspecialchars_blocks_script_tags` | Confirms `<script>alert("xss")</script>` is converted to `&lt;script&gt;` entities |
| `test_htmlspecialchars_blocks_event_handlers` | Confirms double quotes in `" onmouseover="alert(1)"` are converted to `&quot;` |
| `test_htmlspecialchars_blocks_img_onerror` | Confirms `<img src=x onerror="alert(1)">` is entity-encoded |
| `test_htmlspecialchars_blocks_javascript_uri` | Documents that `javascript:` URIs are NOT encoded by `htmlspecialchars` (no special chars), noting the limitation |
| `test_htmlspecialchars_encodes_ampersand` | Confirms `&` in "Tom & Jerry" is converted to `&amp;` |
| `test_htmlspecialchars_encodes_single_quotes` | Confirms single quotes in `' onclick='alert(1)'` are converted to `&#039;` (requires ENT_QUOTES) |
| `test_sql_injection_in_htmlspecialchars` | Shows that SQL injection payloads with quotes are entity-encoded |
| `test_nested_script_tags` | Confirms nested/doubled script tags are still encoded |
| `test_svg_xss_vector` | Confirms `<svg onload="alert(1)">` is entity-encoded |
| `test_encoded_xss_still_safe_after_double_encoding` | Confirms that already-encoded entities are double-encoded (`&lt;` becomes `&amp;lt;`), preventing decode-to-XSS |
| `test_empty_string_is_safe` | Confirms empty string passes through unchanged |
| `test_normal_text_unchanged` | Confirms normal alphanumeric text passes through unchanged |

---

### CSRFTest.php (10 tests)

Tests the CSRF token generation and timing-safe comparison mechanisms used in `BaseController::validateCsrf()` and `CsrfMiddleware`.

| Test Method | Description |
|---|---|
| `test_hash_equals_with_matching_tokens` | Confirms `hash_equals()` returns true when comparing a token with itself |
| `test_hash_equals_with_different_tokens` | Confirms `hash_equals()` returns false for two different random tokens |
| `test_hash_equals_is_timing_safe` | Tests that tokens differing at the first character and last character both return false equally (timing-safe) |
| `test_hash_equals_different_lengths` | Confirms `hash_equals()` returns false when strings have different lengths |
| `test_hash_equals_empty_strings` | Confirms `hash_equals()` returns true when both strings are empty |
| `test_csrf_token_generation_sufficient_length` | Verifies `bin2hex(random_bytes(32))` produces a 64-character hex string (256 bits of entropy) |
| `test_csrf_token_is_hex` | Verifies the generated token matches the regex `^[0-9a-f]{64}$` |
| `test_csrf_tokens_are_unique` | Confirms two consecutively generated tokens are different |
| `test_naive_comparison_vs_hash_equals` | Demonstrates that `===` and `hash_equals()` produce the same boolean results (but `hash_equals` is timing-safe) |
| `test_tampered_token_rejected` | Confirms that flipping a single character in a token causes `hash_equals()` to return false |

---

### GSTCalculationTest.php (11 tests)

Tests the GST (Goods and Services Tax) calculation logic that mirrors `BookingController::createBooking()`.

| Test Method | Description |
|---|---|
| `test_gst_18_percent_for_premium_rooms` | Verifies 18% GST rate for rooms at 12500/night (above 7500 threshold): base 37500, tax 6750, total 44250 |
| `test_gst_12_percent_for_standard_rooms` | Verifies 12% GST rate for rooms at 4500/night (below 7500 threshold): base 9000, tax 1080 |
| `test_gst_threshold_boundary_at_7500` | Confirms rooms at exactly 7500/night get the 18% rate (>=, not >) |
| `test_gst_threshold_boundary_below_7500` | Confirms rooms at 7499/night get the 12% rate |
| `test_server_side_price_not_tampered` | Simulates price tampering: client sends 100 but server recalculates 25000 from DB price, proving client value is ignored |
| `test_total_with_tax_for_single_room` | Verifies full calculation for a single room: 4500 x 5 nights = 22500, 12% tax = 2700, total = 25200 |
| `test_total_with_tax_for_suite` | Verifies full calculation for a suite: 24000 x 2 nights = 48000, 18% tax = 8640, total = 56640 |
| `test_total_with_tax_for_presidential` | Verifies full calculation for presidential: 55000 x 1 night, 18% tax = 9900, total = 64900 |
| `test_discount_applied_before_tax` | Verifies discount is subtracted from base before tax: 37500 - 5000 = 32500, 18% tax = 5850, total = 38350 |
| `test_zero_nights_booking` | Confirms zero nights results in zero base price |
| `test_negative_price_rejected` | Confirms negative prices are detected as invalid (price > 0 is false) |

---

### InputValidationTest.php (30 tests)

Tests all input validation functions and patterns used across controllers.

| Test Method | Description |
|---|---|
| **Email Validation** | |
| `test_valid_email` | Confirms `FILTER_VALIDATE_EMAIL` accepts standard emails, domain-specific emails, and tagged addresses |
| `test_invalid_email_no_at` | Rejects "notanemail" (no @ symbol) |
| `test_invalid_email_no_domain` | Rejects "user@" (missing domain) |
| `test_invalid_email_no_local` | Rejects "@domain.com" (missing local part) |
| `test_invalid_email_spaces` | Rejects "user @domain.com" (contains space) |
| `test_invalid_email_double_dots` | Rejects "user@domain..com" (consecutive dots in domain) |
| **Phone Validation** | |
| `test_valid_indian_phone` | Validates Indian phone formats: +91-98765-43210, +919876543210, +91 98765 43210 |
| `test_invalid_phone_too_short` | Rejects phone numbers with too few digits |
| `test_invalid_phone_letters` | Rejects phone numbers containing alphabetic characters |
| `test_valid_generic_phone` | Validates generic international phone numbers (7-15 digits, optional + prefix) |
| **Date Validation** | |
| `test_valid_date_format` | Confirms Y-m-d format accepts 2026-06-15, 2026-12-31, 2026-01-01 |
| `test_invalid_date_format_wrong_separator` | Rejects 2026/06/15 (wrong separator) |
| `test_invalid_date_month_out_of_range` | Rejects 2026-13-01 (month 13 does not exist) |
| `test_invalid_date_day_out_of_range` | Rejects 2026-02-30 (February has at most 29 days) |
| `test_invalid_date_not_a_date` | Rejects "not-a-date" (non-date string) |
| `test_checkout_after_checkin` | Confirms check-out date is after check-in date |
| `test_checkout_not_before_checkin` | Confirms check-out before check-in is detected as invalid |
| `test_checkin_not_in_past` | Verifies future dates pass and past dates fail the "not in past" check |
| **Numeric ID Validation** | |
| `test_valid_numeric_id` | Confirms `ctype_digit()` accepts "1", "123", "99999" |
| `test_invalid_numeric_id_negative` | Rejects "-1" (negative numbers) |
| `test_invalid_numeric_id_float` | Rejects "1.5" (decimal numbers) |
| `test_invalid_numeric_id_string` | Rejects "abc" (alphabetic strings) |
| `test_invalid_numeric_id_empty` | Rejects "" (empty string) |
| `test_invalid_numeric_id_sql_injection` | Rejects SQL injection payloads "1 OR 1=1" and "1; DROP TABLE users" |
| **String Sanitization** | |
| `test_trim_whitespace` | Verifies `trim()` removes leading/trailing spaces, newlines, and tabs |
| `test_strip_tags` | Verifies `strip_tags()` removes HTML tags while preserving text content |
| **Allowlist Validation** | |
| `test_valid_room_types` | Confirms all 5 valid room types (single, double, suite, deluxe, presidential) are in the allowlist |
| `test_invalid_room_type` | Confirms "penthouse" and empty string are rejected by the allowlist |
| `test_valid_booking_statuses` | Confirms all 6 valid booking statuses (pending, confirmed, checked_in, checked_out, cancelled, no_show) are in the allowlist |
| `test_invalid_booking_status` | Confirms "approved" and "deleted" are rejected by the allowlist |

---

## 5. What Is Tested

### Model Behavior
- Object creation from arrays (`fromArray`) and serialization (`toArray`)
- State checks (`isActive`, `isCancelled`, `isCompleted`, `isPaid`, `isLocked`, `isAvailable`)
- Calculated fields (`getNumberOfNights`, `getSummary`, `getFormattedCheckIn/CheckOut`)
- Display helpers (`getDisplayName`, `getFormattedType`, `getStatusBadgeClass`)
- Default values for new objects
- JSON field encoding/decoding (amenities, images)
- Graceful handling of null and invalid data

### Password Security
- Bcrypt hashing with correct cost factor (12)
- `password_verify()` correctly accepts/rejects passwords
- Salt uniqueness (same password produces different hashes)
- Password policy enforcement (length, uppercase, lowercase, digit)
- Plaintext password not present in hash

### XSS Prevention
- Script tag encoding (`<script>` to `&lt;script&gt;`)
- Event handler encoding (quotes to entities)
- Various attack vectors: img/onerror, svg/onload, javascript: URIs, nested tags
- Single quote encoding (requires `ENT_QUOTES`)
- Double encoding safety
- Normal text passthrough

### CSRF Validation
- `hash_equals()` timing-safe comparison behavior
- Token generation produces 64-character hex strings (256 bits of entropy)
- Token uniqueness across generations
- Single-character tampering is detected

### GST Calculation
- Correct rate selection at 7500 boundary (18% at/above, 12% below)
- Full price calculation chains (base x nights, tax, total)
- Server-side price overrides client-submitted values
- Edge cases: zero nights, negative prices, discount application order

### Input Validation
- Email format validation with `FILTER_VALIDATE_EMAIL`
- Phone number regex patterns (Indian and international)
- Date format validation with `DateTime::createFromFormat`
- Date range logic (check-out after check-in, not in past)
- Numeric ID validation with `ctype_digit()` (rejects SQL injection payloads)
- String sanitization (`trim`, `strip_tags`)
- Allowlist validation for room types and booking statuses

---

## 6. What Is NOT Tested (Future Work)

### Integration Tests
- Full HTTP request/response cycles through the router
- Middleware pipeline execution order
- Database transaction rollback on errors

### Controller Tests
- Require mocking `Request`, `Response`, `Database`, and `Application` objects
- CSRF flow end-to-end (generate, embed, submit, validate)
- Redirect behavior after successful/failed actions
- Flash message content verification
- Admin vs. user access control enforcement

### Database Repository Tests
- `BookingRepository::findByUser()` query correctness with test fixtures
- `BookingRepository::findByRoomAndDateRange()` conflict detection
- `UserRepository::incrementFailedLoginAttempts()` and `lockAccount()`
- `RoomRepository::findWithHotel()` join correctness
- Transaction commit/rollback behavior

### End-to-End Tests
- Browser automation with Selenium or Playwright
- Full booking flow: login, search room, book, view confirmation, cancel
- Admin workflow: manage rooms, manage bookings, view logs
- Cross-browser compatibility

### Performance and Load Tests
- Response time under concurrent requests
- Rate limiting effectiveness under load
- Database query performance with large datasets
- Memory usage profiling

### Security-Specific Tests
- Penetration testing (automated tools like OWASP ZAP)
- Session fixation attack simulation
- Account lockout timing verification
- Password reset token expiry enforcement
- IDOR attack simulation with HTTP requests

---

## 7. Adding New Tests

### Step 1: Create the test file

Place the file in the appropriate directory based on what it tests:

- `tests/Unit/Models/` -- Model and entity tests
- `tests/Unit/Security/` -- Security mechanism tests
- `tests/Unit/Validation/` -- Input validation tests
- `tests/Integration/` -- Tests that require multiple components working together

### Step 2: Extend the appropriate base class

```php
<?php

namespace Tests\Unit\Models;

// For tests that need a database:
use Tests\TestCase;

class MyNewModelTest extends TestCase
{
    // $this->db is available (in-memory SQLite with seed data)

    public function test_something_with_database(): void
    {
        $result = $this->db->query("SELECT * FROM users WHERE id = ?", [1]);
        $this->assertNotFalse($result);
    }
}
```

```php
<?php

namespace Tests\Unit\Security;

// For tests that do NOT need a database:
use PHPUnit\Framework\TestCase;

class MyNewSecurityTest extends TestCase
{
    public function test_some_security_function(): void
    {
        $this->assertTrue(true);
    }
}
```

### Step 3: Name test methods descriptively

Use the pattern `test_descriptive_name()` so PHPUnit's testdox output is readable:

```php
// Method name:
public function test_booking_with_past_checkin_is_rejected(): void

// Testdox output:
// Booking with past checkin is rejected
```

### Step 4: Run the new test

```bash
# Run just your new test class
./vendor/bin/phpunit --filter MyNewModelTest

# Run just one method
./vendor/bin/phpunit --filter test_booking_with_past_checkin_is_rejected

# Run the full suite to ensure nothing broke
./vendor/bin/phpunit
```

### Test Writing Guidelines

1. **One assertion per concept** -- Each test should verify one behavior. Multiple assertions are fine if they test the same concept.
2. **Descriptive names** -- Test names should describe the expected behavior, not the implementation.
3. **Arrange-Act-Assert** -- Structure tests in three phases: set up data, perform the action, verify the result.
4. **Independent tests** -- Tests must not depend on each other or on execution order. Each test gets a fresh database.
5. **Test edge cases** -- Include boundary values, null inputs, empty strings, and malformed data.
6. **No external dependencies** -- Unit tests should not make network calls, read files outside the project, or depend on system state.

---

## 8. CI/CD Integration

### GitHub Actions Workflow

Create `.github/workflows/tests.yml` to run tests automatically on every push and pull request:

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php-version: ['8.1', '8.2', '8.3']

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: pdo_sqlite, mbstring
          coverage: pcov

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Copy environment file
        run: cp .env.example .env

      - name: Run tests
        run: ./vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload coverage
        if: matrix.php-version == '8.2'
        uses: codecov/codecov-action@v4
        with:
          files: coverage.xml
```

### What This Workflow Does

1. **Triggers** on pushes to `main`/`develop` and on pull requests to `main`
2. **Tests against multiple PHP versions** (8.1, 8.2, 8.3) to ensure compatibility
3. **Installs dependencies** via Composer
4. **Runs the full test suite** with code coverage collection
5. **Uploads coverage reports** to Codecov (on PHP 8.2 only, to avoid duplicate uploads)

### Required Configuration

- Add a `CODECOV_TOKEN` secret to the GitHub repository settings (if using Codecov)
- Ensure `.env.example` exists with safe default values for the testing environment
- The `pdo_sqlite` extension is required for the in-memory test database
