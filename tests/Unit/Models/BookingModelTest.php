<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Booking;

class BookingModelTest extends TestCase
{
    public function test_booking_from_array(): void
    {
        $data = [
            'id' => 1,
            'booking_reference' => 'BK123',
            'user_id' => 1,
            'room_id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
            'base_price' => 9000,
            'total_price' => 10000,
            'status' => 'confirmed',
        ];
        $booking = Booking::fromArray($data);

        $this->assertEquals(1, $booking->id);
        $this->assertEquals('BK123', $booking->booking_reference);
        $this->assertEquals(1, $booking->user_id);
        $this->assertEquals(1, $booking->room_id);
        $this->assertEquals('2026-06-01', $booking->check_in);
        $this->assertEquals('2026-06-03', $booking->check_out);
        $this->assertEquals(10000, $booking->total_price);
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_booking_to_array(): void
    {
        $data = [
            'id' => 5,
            'booking_reference' => 'BK999',
            'user_id' => 2,
            'room_id' => 3,
            'check_in' => '2026-07-01',
            'check_out' => '2026-07-05',
            'base_price' => 40000,
            'total_price' => 47200,
            'status' => 'pending',
        ];
        $booking = Booking::fromArray($data);
        $arr = $booking->toArray();

        $this->assertIsArray($arr);
        $this->assertEquals(5, $arr['id']);
        $this->assertEquals('BK999', $arr['booking_reference']);
    }

    public function test_is_active_confirmed(): void
    {
        $booking = Booking::fromArray(['id' => 1, 'status' => 'confirmed']);
        $this->assertTrue($booking->isActive());
    }

    public function test_is_active_pending(): void
    {
        $booking = Booking::fromArray(['id' => 1, 'status' => 'pending']);
        $this->assertTrue($booking->isActive());
    }

    public function test_is_active_checked_in(): void
    {
        $booking = Booking::fromArray(['id' => 1, 'status' => 'checked_in']);
        $this->assertTrue($booking->isActive());
    }

    public function test_is_not_active_cancelled(): void
    {
        $cancelled = Booking::fromArray(['id' => 2, 'status' => 'cancelled']);
        $this->assertFalse($cancelled->isActive());
    }

    public function test_is_not_active_checked_out(): void
    {
        $checkedOut = Booking::fromArray(['id' => 3, 'status' => 'checked_out']);
        $this->assertFalse($checkedOut->isActive());
    }

    public function test_is_not_active_no_show(): void
    {
        $noShow = Booking::fromArray(['id' => 4, 'status' => 'no_show']);
        $this->assertFalse($noShow->isActive());
    }

    public function test_is_cancelled(): void
    {
        $cancelled = Booking::fromArray(['id' => 1, 'status' => 'cancelled']);
        $notCancelled = Booking::fromArray(['id' => 2, 'status' => 'confirmed']);

        $this->assertTrue($cancelled->isCancelled());
        $this->assertFalse($notCancelled->isCancelled());
    }

    public function test_is_completed(): void
    {
        $completed = Booking::fromArray(['id' => 1, 'status' => 'checked_out']);
        $notCompleted = Booking::fromArray(['id' => 2, 'status' => 'confirmed']);

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($notCompleted->isCompleted());
    }

    public function test_is_paid(): void
    {
        $paid = Booking::fromArray(['id' => 1, 'payment_status' => 'paid']);
        $unpaid = Booking::fromArray(['id' => 2, 'payment_status' => 'unpaid']);

        $this->assertTrue($paid->isPaid());
        $this->assertFalse($unpaid->isPaid());
    }

    public function test_generate_reference(): void
    {
        $ref = Booking::generateReference();

        $this->assertStringStartsWith('BK', $ref);
        $this->assertEquals(10, strlen($ref)); // BK + 8 chars from uniqid
    }

    public function test_generate_reference_unique(): void
    {
        $ref1 = Booking::generateReference();
        $ref2 = Booking::generateReference();

        $this->assertNotEquals($ref1, $ref2);
    }

    public function test_number_of_nights(): void
    {
        $booking = Booking::fromArray([
            'id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-04',
        ]);

        $this->assertEquals(3, $booking->getNumberOfNights());
    }

    public function test_number_of_nights_one_night(): void
    {
        $booking = Booking::fromArray([
            'id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-02',
        ]);

        $this->assertEquals(1, $booking->getNumberOfNights());
    }

    public function test_formatted_check_in(): void
    {
        $booking = Booking::fromArray([
            'id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
        ]);

        $this->assertEquals('Jun 01, 2026', $booking->getFormattedCheckIn());
    }

    public function test_formatted_check_out(): void
    {
        $booking = Booking::fromArray([
            'id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
        ]);

        $this->assertEquals('Jun 03, 2026', $booking->getFormattedCheckOut());
    }

    public function test_get_summary(): void
    {
        $booking = Booking::fromArray([
            'id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-04',
        ]);

        $summary = $booking->getSummary();
        $this->assertStringContainsString('Jun 01, 2026', $summary);
        $this->assertStringContainsString('Jun 04, 2026', $summary);
        $this->assertStringContainsString('3 nights', $summary);
    }

    public function test_get_summary_single_night(): void
    {
        $booking = Booking::fromArray([
            'id' => 1,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-02',
        ]);

        $summary = $booking->getSummary();
        $this->assertStringContainsString('1 night', $summary);
        $this->assertStringNotContainsString('nights', $summary);
    }

    public function test_status_badge_class(): void
    {
        $this->assertEquals('warning', Booking::fromArray(['id' => 1, 'status' => 'pending'])->getStatusBadgeClass());
        $this->assertEquals('info', Booking::fromArray(['id' => 1, 'status' => 'confirmed'])->getStatusBadgeClass());
        $this->assertEquals('primary', Booking::fromArray(['id' => 1, 'status' => 'checked_in'])->getStatusBadgeClass());
        $this->assertEquals('success', Booking::fromArray(['id' => 1, 'status' => 'checked_out'])->getStatusBadgeClass());
        $this->assertEquals('danger', Booking::fromArray(['id' => 1, 'status' => 'cancelled'])->getStatusBadgeClass());
        $this->assertEquals('dark', Booking::fromArray(['id' => 1, 'status' => 'no_show'])->getStatusBadgeClass());
        $this->assertEquals('secondary', Booking::fromArray(['id' => 1, 'status' => 'unknown'])->getStatusBadgeClass());
    }

    public function test_payment_status_badge_class(): void
    {
        $this->assertEquals('danger', Booking::fromArray(['id' => 1, 'payment_status' => 'unpaid'])->getPaymentStatusBadgeClass());
        $this->assertEquals('warning', Booking::fromArray(['id' => 1, 'payment_status' => 'partial'])->getPaymentStatusBadgeClass());
        $this->assertEquals('success', Booking::fromArray(['id' => 1, 'payment_status' => 'paid'])->getPaymentStatusBadgeClass());
        $this->assertEquals('info', Booking::fromArray(['id' => 1, 'payment_status' => 'refunded'])->getPaymentStatusBadgeClass());
        $this->assertEquals('secondary', Booking::fromArray(['id' => 1, 'payment_status' => 'unknown'])->getPaymentStatusBadgeClass());
    }

    public function test_default_status_is_pending(): void
    {
        $booking = new Booking();
        $this->assertEquals('pending', $booking->status);
    }

    public function test_default_payment_status_is_unpaid(): void
    {
        $booking = new Booking();
        $this->assertEquals('unpaid', $booking->payment_status);
    }

    public function test_default_num_guests_is_one(): void
    {
        $booking = new Booking();
        $this->assertEquals(1, $booking->num_guests);
    }
}
