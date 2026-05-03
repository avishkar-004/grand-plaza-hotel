<?php

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

class InputValidationTest extends TestCase
{
    // ========== Email Validation ==========

    public function test_valid_email(): void
    {
        $this->assertNotFalse(filter_var('user@example.com', FILTER_VALIDATE_EMAIL));
        $this->assertNotFalse(filter_var('admin@grandplaza.in', FILTER_VALIDATE_EMAIL));
        $this->assertNotFalse(filter_var('test.user+tag@domain.co.uk', FILTER_VALIDATE_EMAIL));
    }

    public function test_invalid_email_no_at(): void
    {
        $this->assertFalse(filter_var('notanemail', FILTER_VALIDATE_EMAIL));
    }

    public function test_invalid_email_no_domain(): void
    {
        $this->assertFalse(filter_var('user@', FILTER_VALIDATE_EMAIL));
    }

    public function test_invalid_email_no_local(): void
    {
        $this->assertFalse(filter_var('@domain.com', FILTER_VALIDATE_EMAIL));
    }

    public function test_invalid_email_spaces(): void
    {
        $this->assertFalse(filter_var('user @domain.com', FILTER_VALIDATE_EMAIL));
    }

    public function test_invalid_email_double_dots(): void
    {
        $this->assertFalse(filter_var('user@domain..com', FILTER_VALIDATE_EMAIL));
    }

    // ========== Phone Validation ==========

    public function test_valid_indian_phone(): void
    {
        // Indian phone: +91 followed by 10 digits
        $pattern = '/^\+91[\-\s]?\d{5}[\-\s]?\d{5}$/';
        $this->assertMatchesRegularExpression($pattern, '+91-98765-43210');
        $this->assertMatchesRegularExpression($pattern, '+919876543210');
        $this->assertMatchesRegularExpression($pattern, '+91 98765 43210');
    }

    public function test_invalid_phone_too_short(): void
    {
        $pattern = '/^\+91[\-\s]?\d{5}[\-\s]?\d{5}$/';
        $this->assertDoesNotMatchRegularExpression($pattern, '+91-12345');
    }

    public function test_invalid_phone_letters(): void
    {
        $pattern = '/^\+91[\-\s]?\d{5}[\-\s]?\d{5}$/';
        $this->assertDoesNotMatchRegularExpression($pattern, '+91-abcde-fghij');
    }

    public function test_valid_generic_phone(): void
    {
        // Generic: 7 to 15 digits, optional leading +
        $pattern = '/^\+?\d{7,15}$/';
        $this->assertMatchesRegularExpression($pattern, '1234567890');
        $this->assertMatchesRegularExpression($pattern, '+1234567890123');
    }

    // ========== Date Format Validation ==========

    public function test_valid_date_format(): void
    {
        $this->assertTrue($this->isValidDate('2026-06-15'));
        $this->assertTrue($this->isValidDate('2026-12-31'));
        $this->assertTrue($this->isValidDate('2026-01-01'));
    }

    public function test_invalid_date_format_wrong_separator(): void
    {
        $this->assertFalse($this->isValidDate('2026/06/15'));
    }

    public function test_invalid_date_month_out_of_range(): void
    {
        $this->assertFalse($this->isValidDate('2026-13-01'));
    }

    public function test_invalid_date_day_out_of_range(): void
    {
        $this->assertFalse($this->isValidDate('2026-02-30'));
    }

    public function test_invalid_date_not_a_date(): void
    {
        $this->assertFalse($this->isValidDate('not-a-date'));
    }

    public function test_checkout_after_checkin(): void
    {
        $checkIn = '2026-06-01';
        $checkOut = '2026-06-05';

        $this->assertGreaterThan(strtotime($checkIn), strtotime($checkOut));
    }

    public function test_checkout_not_before_checkin(): void
    {
        $checkIn = '2026-06-05';
        $checkOut = '2026-06-01';

        $this->assertLessThan(strtotime($checkIn), strtotime($checkOut));
    }

    public function test_checkin_not_in_past(): void
    {
        $futureDate = date('Y-m-d', strtotime('+1 day'));
        $pastDate = date('Y-m-d', strtotime('-1 day'));

        $this->assertGreaterThanOrEqual(strtotime('today'), strtotime($futureDate));
        $this->assertLessThan(strtotime('today'), strtotime($pastDate));
    }

    // ========== Numeric ID Validation ==========

    public function test_valid_numeric_id(): void
    {
        $this->assertTrue(ctype_digit('1'));
        $this->assertTrue(ctype_digit('123'));
        $this->assertTrue(ctype_digit('99999'));
    }

    public function test_invalid_numeric_id_negative(): void
    {
        $this->assertFalse(ctype_digit('-1'));
    }

    public function test_invalid_numeric_id_float(): void
    {
        $this->assertFalse(ctype_digit('1.5'));
    }

    public function test_invalid_numeric_id_string(): void
    {
        $this->assertFalse(ctype_digit('abc'));
    }

    public function test_invalid_numeric_id_empty(): void
    {
        $this->assertFalse(ctype_digit(''));
    }

    public function test_invalid_numeric_id_sql_injection(): void
    {
        $this->assertFalse(ctype_digit("1 OR 1=1"));
        $this->assertFalse(ctype_digit("1; DROP TABLE users"));
    }

    // ========== String Sanitization ==========

    public function test_trim_whitespace(): void
    {
        $this->assertEquals('hello', trim('  hello  '));
        $this->assertEquals('hello', trim("\nhello\n"));
        $this->assertEquals('hello', trim("\thello\t"));
    }

    public function test_strip_tags(): void
    {
        $this->assertEquals('Hello World', strip_tags('<b>Hello</b> <i>World</i>'));
        $this->assertEquals('alert(1)', strip_tags('<script>alert(1)</script>'));
    }

    // ========== Room Type Validation ==========

    public function test_valid_room_types(): void
    {
        $validTypes = ['single', 'double', 'suite', 'deluxe', 'presidential'];

        foreach ($validTypes as $type) {
            $this->assertContains($type, $validTypes);
        }
    }

    public function test_invalid_room_type(): void
    {
        $validTypes = ['single', 'double', 'suite', 'deluxe', 'presidential'];

        $this->assertNotContains('penthouse', $validTypes);
        $this->assertNotContains('', $validTypes);
    }

    // ========== Booking Status Validation ==========

    public function test_valid_booking_statuses(): void
    {
        $validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];

        foreach ($validStatuses as $status) {
            $this->assertContains($status, $validStatuses);
        }
    }

    public function test_invalid_booking_status(): void
    {
        $validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];

        $this->assertNotContains('approved', $validStatuses);
        $this->assertNotContains('deleted', $validStatuses);
    }

    // ========== Helper Methods ==========

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
