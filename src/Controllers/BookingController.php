<?php

namespace App\Controllers;

use App\Repositories\RoomRepository;
use App\Repositories\BookingRepository;
use App\Models\Booking;

/**
 * Booking Controller
 *
 * Handles room booking creation, listing, and cancellation
 * for the Grand Plaza Hotel & Resort.
 *
 * @package App\Controllers
 */
class BookingController extends BaseController
{
    /**
     * Show booking form for a specific room
     */
    public function bookingForm(array $params = [])
    {
        $this->requireLogin();

        $roomId = $params['roomId'] ?? null;

        // Validate room ID is strictly numeric
        if (!$roomId || !ctype_digit((string)$roomId)) {
            $this->flash('error', 'Invalid room ID.');
            $this->redirect('/rooms');
            exit;
        }

        $roomRepo = new RoomRepository($this->db);
        $room = $roomRepo->findWithHotel((int)$roomId);

        if (!$room || empty($room['is_available'])) {
            $this->flash('error', 'Room not found or not available.');
            $this->redirect('/rooms');
            exit;
        }

        // Get optional pre-filled dates from query string (from room search page)
        $prefilledCheckIn = $this->request->get('check_in', '');
        $prefilledCheckOut = $this->request->get('check_out', '');

        // Validate prefilled dates — discard if invalid
        if ($prefilledCheckIn !== '' && !$this->isValidDate($prefilledCheckIn)) {
            $prefilledCheckIn = '';
        }
        if ($prefilledCheckOut !== '' && !$this->isValidDate($prefilledCheckOut)) {
            $prefilledCheckOut = '';
        }

        // Load hotel check-in/check-out times
        $hotelTimes = $this->db->fetchOne(
            "SELECT check_in_time, check_out_time FROM hotels WHERE id = ? AND is_deleted = 0",
            [$room['hotel_id']]
        );

        return $this->view('pages.booking_form', [
            'title' => 'Book Room - ' . $this->esc($room['hotel_name']),
            'room' => $room,
            'hotel_times' => $hotelTimes ?: ['check_in_time' => '14:00', 'check_out_time' => '11:00'],
            'prefilled_dates' => [
                'check_in' => $prefilledCheckIn,
                'check_out' => $prefilledCheckOut,
            ],
        ])->send();
    }

    /**
     * Process booking creation (POST)
     */
    public function createBooking(array $params = [])
    {
        $this->requireLogin();

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->back();
            exit;
        }

        // Collect POST data
        $roomId = $this->request->post('room_id');
        $checkIn = $this->request->post('check_in');
        $checkOut = $this->request->post('check_out');
        $numGuests = $this->request->post('num_guests');
        $specialRequests = $this->request->post('special_requests');

        // --- Input Validation ---

        // room_id must be numeric
        if (!$roomId || !ctype_digit((string)$roomId)) {
            $this->flash('error', 'Invalid room selection.');
            $this->back();
            exit;
        }

        // Dates must be valid Y-m-d format
        if (!$this->isValidDate($checkIn) || !$this->isValidDate($checkOut)) {
            $this->flash('error', 'Invalid date format. Please use YYYY-MM-DD.');
            $this->back();
            exit;
        }

        // check_in must be today or future
        $today = date('Y-m-d');
        if ($checkIn < $today) {
            $this->flash('error', 'Check-in date cannot be in the past.');
            $this->back();
            exit;
        }

        // check_out must be after check_in
        if ($checkOut <= $checkIn) {
            $this->flash('error', 'Check-out date must be after check-in date.');
            $this->back();
            exit;
        }

        // Maximum stay: 30 days
        $checkInDate = new \DateTime($checkIn);
        $checkOutDate = new \DateTime($checkOut);
        $nights = (int)$checkInDate->diff($checkOutDate)->days;

        if ($nights > 30) {
            $this->flash('error', 'Maximum stay is 30 nights. Please shorten your reservation.');
            $this->back();
            exit;
        }

        // num_guests must be a positive integer
        if (!$numGuests || !ctype_digit((string)$numGuests) || (int)$numGuests < 1) {
            $this->flash('error', 'Number of guests must be at least 1.');
            $this->back();
            exit;
        }

        // Sanitize special_requests: strip tags to prevent stored XSS, enforce max length
        if ($specialRequests !== null && $specialRequests !== '') {
            $specialRequests = strip_tags($specialRequests);
            if (mb_strlen($specialRequests) > 500) {
                $this->flash('error', 'Special requests must be 500 characters or fewer.');
                $this->back();
                exit;
            }
        } else {
            $specialRequests = null;
        }

        $roomId = (int)$roomId;
        $numGuests = (int)$numGuests;

        // Load room and verify availability
        $roomRepo = new RoomRepository($this->db);
        $room = $roomRepo->findWithHotel($roomId);

        if (!$room || empty($room['is_available'])) {
            $this->flash('error', 'Room not found or no longer available.');
            $this->redirect('/rooms');
            exit;
        }

        // Verify guest count within room capacity
        if ($numGuests > (int)$room['max_occupancy']) {
            $this->flash('error', 'Number of guests exceeds room capacity of ' . (int)$room['max_occupancy'] . '.');
            $this->back();
            exit;
        }

        // Check for date conflicts
        $bookingRepo = new BookingRepository($this->db);
        $conflicts = $bookingRepo->findByRoomAndDateRange($roomId, $checkIn, $checkOut);

        if (!empty($conflicts)) {
            $this->flash('error', 'Room not available for selected dates. Please choose different dates.');
            $this->back();
            exit;
        }

        // --- Server-side price calculation (NEVER trust client) ---
        $pricePerNight = (float)$room['base_price'];
        $baseTotal = round($pricePerNight * $nights, 2);
        // GST: 18% for rooms >= ₹7,500/night, 12% for rooms below
        $gstRate = ($pricePerNight >= 7500) ? 0.18 : 0.12;
        $taxAmount = round($baseTotal * $gstRate, 2);
        $totalPrice = round($baseTotal + $taxAmount, 2);

        // Generate booking reference
        $reference = Booking::generateReference();
        $userId = $this->getCurrentUserId();

        // Create booking
        $bookingId = $bookingRepo->create([
            'booking_reference' => $reference,
            'user_id' => $userId,
            'room_id' => $roomId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'booking_date' => date('Y-m-d H:i:s'),
            'num_guests' => $numGuests,
            'special_requests' => $specialRequests,
            'base_price' => $baseTotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_price' => $totalPrice,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId,
        ]);

        // Log the booking action to activity_logs
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, severity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $userId,
                'booking_created',
                'booking',
                $bookingId,
                "Booking {$reference} created for room {$roomId}",
                $this->request->ip(),
                'info',
            ]
        );

        $this->flash('success', 'Booking confirmed! Your reference number is <strong>' . $this->esc($reference) . '</strong>.');
        $this->redirect('/booking/' . $bookingId . '/confirmation');
        exit;
    }

    /**
     * List current user's bookings, separated into upcoming/past/cancelled
     */
    public function myBookings(array $params = [])
    {
        $this->requireLogin();

        $bookingRepo = new BookingRepository($this->db);

        // IDOR prevention: findByUser filters by the authenticated user's ID
        $bookings = $bookingRepo->findByUser($this->getCurrentUserId());

        $today = date('Y-m-d');
        $upcoming = [];
        $past = [];
        $cancelled = [];

        foreach ($bookings as $b) {
            if (($b['status'] ?? '') === 'cancelled') {
                $cancelled[] = $b;
            } elseif (($b['status'] ?? '') === 'checked_out' || ($b['check_out'] ?? '') < $today) {
                $past[] = $b;
            } else {
                $upcoming[] = $b;
            }
        }

        return $this->view('pages.bookings', [
            'title' => 'My Bookings',
            'upcoming_bookings' => $upcoming,
            'past_bookings' => $past,
            'cancelled_bookings' => $cancelled,
        ])->send();
    }

    /**
     * Cancel a booking (POST)
     */
    public function cancelBooking(array $params = [])
    {
        $this->requireLogin();

        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/bookings');
            exit;
        }

        $bookingId = $params['id'] ?? null;

        // Validate booking ID is numeric
        if (!$bookingId || !ctype_digit((string)$bookingId)) {
            $this->flash('error', 'Invalid booking ID.');
            $this->redirect('/bookings');
            exit;
        }

        $bookingId = (int)$bookingId;
        $bookingRepo = new BookingRepository($this->db);
        $booking = $bookingRepo->find($bookingId);

        if (!$booking) {
            $this->flash('error', 'Booking not found.');
            $this->redirect('/bookings');
            exit;
        }

        // IDOR prevention: verify the booking belongs to the current user
        // Admin override: admins may cancel any booking
        $currentUser = $this->getCurrentUser();
        $isAdmin = ($currentUser['role'] ?? '') === 'admin';

        if ((int)$booking->user_id !== $this->getCurrentUserId() && !$isAdmin) {
            $this->flash('error', 'Unauthorized action.');
            $this->redirect('/bookings');
            exit;
        }

        // Verify booking is in a cancellable state
        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            $this->flash('error', 'This booking cannot be cancelled.');
            $this->redirect('/bookings');
            exit;
        }

        $userId = $this->getCurrentUserId();

        // Get optional cancellation reason from POST
        $reason = $this->request->post('cancellation_reason');
        if ($reason !== null && $reason !== '') {
            $reason = strip_tags($reason);
            $reason = mb_substr($reason, 0, 500);
        } else {
            $reason = 'Cancelled by user';
        }

        // Cancel the booking
        $bookingRepo->cancel($bookingId, $userId, $reason);

        // Log the cancellation to activity_logs
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, severity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $userId,
                'booking_cancelled',
                'booking',
                $bookingId,
                "Booking {$booking->booking_reference} cancelled by " . ($isAdmin ? 'admin' : 'user'),
                $this->request->ip(),
                'info',
            ]
        );

        $this->flash('success', 'Booking <strong>' . $this->esc($booking->booking_reference) . '</strong> has been cancelled.');
        $this->redirect('/bookings');
        exit;
    }

    /**
     * Show booking confirmation page
     */
    public function confirmation(array $params = [])
    {
        $this->requireLogin();

        $id = $params['id'] ?? null;

        // Validate booking ID is numeric
        if (!$id || !ctype_digit((string)$id)) {
            $this->flash('error', 'Invalid booking ID.');
            $this->redirect('/bookings');
            exit;
        }

        $id = (int)$id;

        // Load booking with room and hotel details
        $booking = $this->db->fetchOne(
            "SELECT b.*, r.room_number, r.room_type, r.floor_number, r.bed_type, h.name as hotel_name, h.address, h.city, h.phone, h.check_in_time, h.check_out_time
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN hotels h ON r.hotel_id = h.id
            WHERE b.id = ? AND b.is_deleted = 0",
            [$id]
        );

        if (!$booking) {
            $this->flash('error', 'Booking not found.');
            $this->redirect('/bookings');
            exit;
        }

        // IDOR check: verify booking belongs to current user (admin bypass)
        $currentUser = $this->getCurrentUser();
        $isAdmin = ($currentUser['role'] ?? '') === 'admin';

        if ((int)$booking['user_id'] !== $this->getCurrentUserId() && !$isAdmin) {
            $this->flash('error', 'Unauthorized access.');
            $this->redirect('/bookings');
            exit;
        }

        // Calculate number of nights
        $checkInDate = new \DateTime($booking['check_in']);
        $checkOutDate = new \DateTime($booking['check_out']);
        $nights = (int)$checkInDate->diff($checkOutDate)->days;

        return $this->view('pages.booking_confirmation', [
            'title' => 'Booking Confirmation - ' . $this->esc($booking['booking_reference']),
            'booking' => $booking,
            'nights' => $nights,
        ])->send();
    }

    /**
     * Validate a date string in Y-m-d format
     */
    private function isValidDate(?string $date): bool
    {
        if (!$date) {
            return false;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $date);

        return $dt && $dt->format('Y-m-d') === $date;
    }
}
