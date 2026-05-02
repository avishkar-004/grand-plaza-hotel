<?php

namespace App\Controllers;

use App\Repositories\RoomRepository;
use App\Repositories\HotelRepository;

/**
 * Room Controller
 *
 * Handles room browsing and detail pages for the hotel
 *
 * @package App\Controllers
 */
class RoomController extends BaseController
{
    private const VALID_ROOM_TYPES = ['single', 'double', 'suite', 'deluxe', 'presidential'];
    private const VALID_SORT_OPTIONS = ['price_asc', 'price_desc', 'type'];

    /**
     * Room listing page with filtering and availability checking
     */
    public function index(array $params = [])
    {
        // Load hotel context
        $hotelRepo = new HotelRepository($this->db);
        $hotel = $hotelRepo->find(1);

        // Collect and validate filter inputs
        $filters = $this->validateFilters();

        // Build parameterized query
        $sql = "SELECT r.*, h.name AS hotel_name, h.check_in_time, h.check_out_time
                FROM rooms r
                JOIN hotels h ON r.hotel_id = h.id
                WHERE r.is_deleted = 0
                  AND r.is_available = 1
                  AND r.maintenance_status = 'operational'";

        $queryParams = [];

        // Room type filter (whitelist-validated)
        if ($filters['type'] !== '') {
            $sql .= " AND r.room_type = ?";
            $queryParams[] = $filters['type'];
        }

        // Guest count filter
        if ($filters['guests'] !== '') {
            $sql .= " AND r.max_occupancy >= ?";
            $queryParams[] = (int)$filters['guests'];
        }

        // Price range filters
        if ($filters['min_price'] !== '') {
            $sql .= " AND r.base_price >= ?";
            $queryParams[] = (float)$filters['min_price'];
        }

        if ($filters['max_price'] !== '') {
            $sql .= " AND r.base_price <= ?";
            $queryParams[] = (float)$filters['max_price'];
        }

        // Date availability: exclude rooms with conflicting bookings
        if ($filters['check_in'] !== '' && $filters['check_out'] !== '') {
            $sql .= " AND r.id NOT IN (
                SELECT b.room_id FROM bookings b
                WHERE b.status NOT IN ('cancelled', 'no_show')
                  AND b.is_deleted = 0
                  AND b.check_in < ?
                  AND b.check_out > ?
            )";
            $queryParams[] = $filters['check_out'];
            $queryParams[] = $filters['check_in'];
        }

        // Sorting
        switch ($filters['sort']) {
            case 'price_desc':
                $sql .= " ORDER BY r.base_price DESC";
                break;
            case 'price_asc':
                $sql .= " ORDER BY r.base_price ASC";
                break;
            case 'type':
                $sql .= " ORDER BY r.room_type ASC, r.base_price ASC";
                break;
            default:
                $sql .= " ORDER BY r.room_type ASC, r.base_price ASC";
                break;
        }

        $sql .= " LIMIT 50";

        $rooms = $this->db->fetchAll($sql, $queryParams);

        return $this->view('pages.rooms', [
            'title'      => 'Our Rooms',
            'rooms'      => $rooms,
            'filters'    => $filters,
            'hotel'      => $hotel,
            'room_types' => self::VALID_ROOM_TYPES,
        ])->send();
    }

    /**
     * Room detail page
     */
    public function show(array $params = [])
    {
        $id = $params['id'] ?? null;

        // Validate ID is strictly numeric
        if (!$id || !ctype_digit((string)$id)) {
            $this->flash('error', 'Invalid room ID.');
            $this->redirect('/rooms');
            exit;
        }

        $roomRepo = new RoomRepository($this->db);
        $room = $roomRepo->findWithHotel((int)$id);

        if (!$room) {
            $this->flash('error', 'Room not found.');
            $this->redirect('/rooms');
            exit;
        }

        // Load hotel for check-in/check-out times
        $hotelRepo = new HotelRepository($this->db);
        $hotel = $hotelRepo->find((int)$room['hotel_id']);

        // Fetch upcoming booked date ranges (next 30 days)
        $today = date('Y-m-d');
        $bookedDates = $this->db->fetchAll(
            "SELECT check_in, check_out FROM bookings
             WHERE room_id = ?
               AND status NOT IN ('cancelled', 'no_show')
               AND is_deleted = 0
               AND check_out >= ?
             ORDER BY check_in",
            [(int)$id, $today]
        );

        // Total booking count for this room
        $totalBookings = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM bookings WHERE room_id = ? AND is_deleted = 0 AND status != 'cancelled'",
            [(int)$id]
        );

        return $this->view('pages.room_detail', [
            'title'         => ucfirst($room['room_type']) . ' Room #' . $room['room_number'],
            'room'          => $room,
            'hotel'         => $hotel,
            'booked_dates'  => $bookedDates,
            'booking_count' => (int)($totalBookings['count'] ?? 0),
        ])->send();
    }

    /**
     * Validate and sanitize all filter inputs from query string
     */
    private function validateFilters(): array
    {
        $type     = $this->request->get('type', '');
        $checkIn  = $this->request->get('check_in', '');
        $checkOut = $this->request->get('check_out', '');
        $guests   = $this->request->get('guests', '');
        $minPrice = $this->request->get('min_price', '');
        $maxPrice = $this->request->get('max_price', '');
        $sort     = $this->request->get('sort', '');

        // Whitelist room type
        if ($type !== '' && !in_array($type, self::VALID_ROOM_TYPES, true)) {
            $type = '';
        }

        // Validate dates (Y-m-d format, must be real dates)
        if ($checkIn !== '' && !$this->isValidDate($checkIn)) {
            $checkIn = '';
        }
        if ($checkOut !== '' && !$this->isValidDate($checkOut)) {
            $checkOut = '';
        }
        // check_out must be after check_in
        if ($checkIn !== '' && $checkOut !== '' && $checkOut <= $checkIn) {
            $checkOut = '';
        }

        // Guests must be a positive integer
        if ($guests !== '' && (!ctype_digit((string)$guests) || (int)$guests < 1)) {
            $guests = '';
        }

        // Prices must be positive numbers
        if ($minPrice !== '' && (!is_numeric($minPrice) || (float)$minPrice < 0)) {
            $minPrice = '';
        }
        if ($maxPrice !== '' && (!is_numeric($maxPrice) || (float)$maxPrice < 0)) {
            $maxPrice = '';
        }

        // Whitelist sort option
        if ($sort !== '' && !in_array($sort, self::VALID_SORT_OPTIONS, true)) {
            $sort = '';
        }

        return [
            'type'      => $type,
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
            'guests'    => $guests,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort'      => $sort,
        ];
    }

    /**
     * Check if a string is a valid Y-m-d date
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
