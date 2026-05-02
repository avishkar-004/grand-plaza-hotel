<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\BookingRepository;
use App\Repositories\HotelRepository;
use App\Repositories\RoomRepository;

class AdminController extends BaseController
{
    /**
     * Admin dashboard - hotel operations overview
     */
    public function dashboard(array $params = []): void
    {
        $this->requireAdmin();

        $hotelRepo = new HotelRepository($this->db);
        $hotel = $hotelRepo->find(1);

        // Room statistics
        $roomStats = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_rooms,
                SUM(CASE WHEN is_available = 1 AND maintenance_status = 'operational' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN is_available = 0 OR maintenance_status != 'operational' THEN 1 ELSE 0 END) as unavailable,
                SUM(CASE WHEN maintenance_status = 'maintenance' THEN 1 ELSE 0 END) as in_maintenance
            FROM rooms WHERE is_deleted = 0",
            []
        ) ?: [];

        // Booking statistics
        $bookingRepo = new BookingRepository($this->db);
        $bookingStats = $bookingRepo->getStatistics();

        // Today's check-ins
        $todayCheckIns = $this->db->fetchAll(
            "SELECT b.*, u.full_name, u.email, r.room_number, r.room_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN rooms r ON b.room_id = r.id
            WHERE b.check_in = {$this->db->today()}
            AND b.status IN ('confirmed','pending')
            AND b.is_deleted = 0",
            []
        );

        // Today's check-outs
        $todayCheckOuts = $this->db->fetchAll(
            "SELECT b.*, u.full_name, r.room_number, r.room_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN rooms r ON b.room_id = r.id
            WHERE b.check_out = {$this->db->today()}
            AND b.status = 'checked_in'
            AND b.is_deleted = 0",
            []
        );

        // User count
        $userRepo = new UserRepository($this->db);
        $userCount = $userRepo->count();

        // Occupancy rate
        $occupiedResult = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT b.room_id) as occupied
            FROM bookings b
            WHERE b.status IN ('confirmed','checked_in')
            AND b.is_deleted = 0
            AND b.check_in <= {$this->db->today()}
            AND b.check_out > {$this->db->today()}",
            []
        );
        $occupiedRooms = (int)($occupiedResult['occupied'] ?? 0);
        $totalAvailable = (int)($roomStats['available'] ?? 0);
        $occupancyRate = $totalAvailable > 0
            ? round(($occupiedRooms / $totalAvailable) * 100, 1)
            : 0;

        // Recent activity (last 15)
        $recentActivity = $this->db->fetchAll(
            "SELECT al.*, u.username
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 15",
            []
        );

        // Security events in last 24 hours
        $securityResult = $this->db->fetchOne(
            "SELECT COUNT(*) as count
            FROM activity_logs
            WHERE is_security_event = 1
            AND created_at >= {$this->db->dateSub('days', 1)}",
            []
        );
        $securityEventsCount = (int)($securityResult['count'] ?? 0);

        $this->view('admin.dashboard', [
            'title' => 'Admin Dashboard',
            'hotel' => $hotel,
            'roomStats' => $roomStats,
            'bookingStats' => $bookingStats,
            'todayCheckIns' => $todayCheckIns,
            'todayCheckOuts' => $todayCheckOuts,
            'userCount' => $userCount,
            'occupancyRate' => $occupancyRate,
            'occupiedRooms' => $occupiedRooms,
            'recentActivity' => $recentActivity,
            'securityEventsCount' => $securityEventsCount,
        ])->send();
    }

    /**
     * Room management listing with filters
     */
    public function rooms(array $params = []): void
    {
        $this->requireAdmin();

        $typeFilter = trim($this->request->get('type', ''));
        $statusFilter = trim($this->request->get('status', ''));

        // Get distinct room types for filter dropdown
        $roomTypeRows = $this->db->fetchAll(
            "SELECT DISTINCT room_type FROM rooms WHERE is_deleted = 0 ORDER BY room_type",
            []
        );
        $roomTypes = array_column($roomTypeRows, 'room_type');

        // Validate type filter against actual values
        if ($typeFilter !== '' && !in_array($typeFilter, $roomTypes, true)) {
            $typeFilter = '';
        }

        // Validate status filter
        $allowedStatuses = ['available', 'unavailable', 'maintenance'];
        if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = '';
        }

        $sql = "SELECT r.*, h.name as hotel_name
                FROM rooms r
                JOIN hotels h ON r.hotel_id = h.id
                WHERE r.is_deleted = 0";
        $sqlParams = [];

        if ($typeFilter !== '') {
            $sql .= " AND r.room_type = ?";
            $sqlParams[] = $typeFilter;
        }

        if ($statusFilter === 'available') {
            $sql .= " AND r.is_available = 1 AND r.maintenance_status = 'operational'";
        } elseif ($statusFilter === 'unavailable') {
            $sql .= " AND (r.is_available = 0 OR r.maintenance_status != 'operational')";
        } elseif ($statusFilter === 'maintenance') {
            $sql .= " AND r.maintenance_status = 'maintenance'";
        }

        $sql .= " ORDER BY r.room_number";

        $rooms = $this->db->fetchAll($sql, $sqlParams);

        $this->view('admin.rooms', [
            'title' => 'Room Management',
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
        ])->send();
    }

    /**
     * Booking management listing with filters
     */
    public function bookings(array $params = []): void
    {
        $this->requireAdmin();

        $statusFilter = trim($this->request->get('status', ''));
        $dateFrom = trim($this->request->get('date_from', ''));
        $dateTo = trim($this->request->get('date_to', ''));
        $search = trim($this->request->get('search', ''));

        $allowedStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
        if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = '';
        }

        // Validate date formats
        if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }

        $sql = "SELECT b.*, u.username, u.full_name, u.email, r.room_number, r.room_type
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN rooms r ON b.room_id = r.id
                WHERE b.is_deleted = 0";
        $sqlParams = [];

        if ($statusFilter !== '') {
            $sql .= " AND b.status = ?";
            $sqlParams[] = $statusFilter;
        }

        if ($dateFrom !== '') {
            $sql .= " AND b.check_in >= ?";
            $sqlParams[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND b.check_out <= ?";
            $sqlParams[] = $dateTo;
        }

        if ($search !== '') {
            $sql .= " AND (b.booking_reference LIKE ? OR u.full_name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $sqlParams[] = $searchTerm;
            $sqlParams[] = $searchTerm;
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT 100";

        $bookings = $this->db->fetchAll($sql, $sqlParams);

        $pagination = $this->paginate($bookings, 15);

        $this->view('admin.bookings', [
            'title' => 'Booking Management',
            'bookings' => $pagination['items'],
            'pagination' => $pagination,
            'statusFilter' => $statusFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
            'allowedStatuses' => $allowedStatuses,
        ])->send();
    }

    /**
     * User management listing - never selects password column
     */
    public function users(array $params = []): void
    {
        $this->requireAdmin();

        $search = trim($this->request->get('search', ''));

        $columns = "id, username, email, full_name, phone, role, failed_login_attempts,
                    locked_until, last_login, last_login_ip, is_active, created_at";

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $sql = "SELECT {$columns}
                    FROM users
                    WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?)
                    AND is_deleted = 0
                    ORDER BY created_at DESC
                    LIMIT 100";
            $users = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
        } else {
            $sql = "SELECT {$columns}
                    FROM users
                    WHERE is_deleted = 0
                    ORDER BY created_at DESC
                    LIMIT 100";
            $users = $this->db->fetchAll($sql, []);
        }

        $pagination = $this->paginate($users, 20);

        $this->view('admin.users', [
            'title' => 'User Management',
            'users' => $pagination['items'],
            'pagination' => $pagination,
            'search' => $search,
        ])->send();
    }

    /**
     * Hotel settings form
     */
    public function settings(array $params = []): void
    {
        $this->requireAdmin();

        $hotelRepo = new HotelRepository($this->db);
        $hotel = $hotelRepo->find(1);

        $this->view('admin.settings', [
            'title' => 'Hotel Settings',
            'hotel' => $hotel,
        ])->send();
    }

    /**
     * Process hotel settings update (POST)
     */
    public function updateSettings(array $params = []): void
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/settings');
            return;
        }

        $name = trim(strip_tags($this->request->post('name', '')));
        $description = trim(strip_tags($this->request->post('description', '')));
        $address = trim(strip_tags($this->request->post('address', '')));
        $city = trim(strip_tags($this->request->post('city', '')));
        $state = trim(strip_tags($this->request->post('state', '')));
        $phone = trim(strip_tags($this->request->post('phone', '')));
        $email = trim(strip_tags($this->request->post('email', '')));
        $checkInTime = trim(strip_tags($this->request->post('check_in_time', '')));
        $checkOutTime = trim(strip_tags($this->request->post('check_out_time', '')));

        // Validation
        if ($name === '') {
            $this->flash('error', 'Hotel name is required.');
            $this->redirect('/admin/settings');
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid email address.');
            $this->redirect('/admin/settings');
            return;
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'phone' => $phone,
            'email' => $email,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ];

        $hotelRepo = new HotelRepository($this->db);
        $hotelRepo->update(1, $data);

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'update_settings',
                'hotel',
                1,
                'Admin updated hotel settings',
                $this->request->ip(),
                $this->request->userAgent(),
                'info',
                0,
            ]
        );

        $this->flash('success', 'Hotel settings updated successfully.');
        $this->redirect('/admin/settings');
    }

    /**
     * Show add room form
     */
    public function addRoomForm(array $params = []): void
    {
        $this->requireAdmin();

        $roomTypes = ['single', 'double', 'deluxe', 'suite', 'presidential'];
        $bedTypes = ['Single', 'Double', 'Queen', 'King', 'King + Twin'];

        $this->view('admin.room_form', [
            'title' => 'Add New Room',
            'room_types' => $roomTypes,
            'bed_types' => $bedTypes,
        ])->send();
    }

    /**
     * Process add room (POST)
     */
    public function addRoom(array $params = []): void
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        $allowedRoomTypes = ['single', 'double', 'deluxe', 'suite', 'presidential'];

        // Collect POST data
        $roomNumber = trim(strip_tags($this->request->post('room_number', '')));
        $roomType = trim(strip_tags($this->request->post('room_type', '')));
        $floorNumber = trim($this->request->post('floor_number', ''));
        $description = trim(strip_tags($this->request->post('description', '')));
        $basePrice = trim($this->request->post('base_price', ''));
        $weekendPrice = trim($this->request->post('weekend_price', ''));
        $peakSeasonPrice = trim($this->request->post('peak_season_price', ''));
        $maxOccupancy = trim($this->request->post('max_occupancy', ''));
        $numBeds = trim($this->request->post('num_beds', ''));
        $bedType = trim(strip_tags($this->request->post('bed_type', '')));
        $amenities = trim(strip_tags($this->request->post('amenities', '')));
        $squareFeet = trim($this->request->post('square_feet', ''));
        $viewType = trim(strip_tags($this->request->post('view_type', '')));

        // Validation
        if ($roomNumber === '' || !ctype_alnum($roomNumber)) {
            $this->flash('error', 'Room number is required and must be alphanumeric.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        if (!in_array($roomType, $allowedRoomTypes, true)) {
            $this->flash('error', 'Invalid room type selected.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        if (!is_numeric($floorNumber) || (int)$floorNumber < 1 || (int)$floorNumber > 50) {
            $this->flash('error', 'Floor number must be between 1 and 50.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        if (!is_numeric($basePrice) || (float)$basePrice <= 0) {
            $this->flash('error', 'Base price must be a positive number.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        if (!is_numeric($maxOccupancy) || (int)$maxOccupancy < 1 || (int)$maxOccupancy > 20) {
            $this->flash('error', 'Max occupancy must be between 1 and 20.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        if (!is_numeric($numBeds) || (int)$numBeds < 1 || (int)$numBeds > 10) {
            $this->flash('error', 'Number of beds must be between 1 and 10.');
            $this->redirect('/admin/rooms/add');
            return;
        }

        // Convert amenities from comma-separated to JSON array
        $amenitiesJson = null;
        if ($amenities !== '') {
            $amenitiesArray = array_map('trim', explode(',', $amenities));
            $amenitiesArray = array_filter($amenitiesArray, fn($a) => $a !== '');
            $amenitiesJson = json_encode(array_values($amenitiesArray));
        }

        $data = [
            'hotel_id' => 1,
            'room_number' => $roomNumber,
            'room_type' => $roomType,
            'floor_number' => (int)$floorNumber,
            'description' => $description !== '' ? $description : null,
            'base_price' => (float)$basePrice,
            'weekend_price' => ($weekendPrice !== '' && is_numeric($weekendPrice)) ? (float)$weekendPrice : null,
            'peak_season_price' => ($peakSeasonPrice !== '' && is_numeric($peakSeasonPrice)) ? (float)$peakSeasonPrice : null,
            'max_occupancy' => (int)$maxOccupancy,
            'num_beds' => (int)$numBeds,
            'bed_type' => $bedType !== '' ? $bedType : null,
            'amenities' => $amenitiesJson,
            'square_feet' => ($squareFeet !== '' && is_numeric($squareFeet)) ? (int)$squareFeet : null,
            'view_type' => $viewType !== '' ? $viewType : null,
            'is_available' => 1,
            'maintenance_status' => 'operational',
            'created_by' => $this->getCurrentUserId(),
        ];

        $roomRepo = new RoomRepository($this->db);
        $newRoomId = $roomRepo->create($data);

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'add_room',
                'room',
                $newRoomId,
                'Admin added room ' . $roomNumber,
                $this->request->ip(),
                $this->request->userAgent(),
                'info',
                0,
            ]
        );

        $this->flash('success', 'Room ' . $roomNumber . ' added successfully.');
        $this->redirect('/admin/rooms');
    }

    /**
     * Show edit room form
     */
    public function editRoomForm(array $params = []): void
    {
        $this->requireAdmin();

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid room ID.');
            $this->redirect('/admin/rooms');
            return;
        }

        $roomRepo = new RoomRepository($this->db);
        $room = $roomRepo->find((int)$id);

        if (!$room) {
            $this->flash('error', 'Room not found.');
            $this->redirect('/admin/rooms');
            return;
        }

        $roomTypes = ['single', 'double', 'deluxe', 'suite', 'presidential'];
        $bedTypes = ['Single', 'Double', 'Queen', 'King', 'King + Twin'];

        $this->view('admin.room_form', [
            'title' => 'Edit Room #' . $room->room_number,
            'room' => $room,
            'room_types' => $roomTypes,
            'bed_types' => $bedTypes,
        ])->send();
    }

    /**
     * Process edit room (POST)
     */
    public function editRoom(array $params = []): void
    {
        $this->requireAdmin();

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid room ID.');
            $this->redirect('/admin/rooms');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        $allowedRoomTypes = ['single', 'double', 'deluxe', 'suite', 'presidential'];

        // Collect POST data
        $roomNumber = trim(strip_tags($this->request->post('room_number', '')));
        $roomType = trim(strip_tags($this->request->post('room_type', '')));
        $floorNumber = trim($this->request->post('floor_number', ''));
        $description = trim(strip_tags($this->request->post('description', '')));
        $basePrice = trim($this->request->post('base_price', ''));
        $weekendPrice = trim($this->request->post('weekend_price', ''));
        $peakSeasonPrice = trim($this->request->post('peak_season_price', ''));
        $maxOccupancy = trim($this->request->post('max_occupancy', ''));
        $numBeds = trim($this->request->post('num_beds', ''));
        $bedType = trim(strip_tags($this->request->post('bed_type', '')));
        $amenities = trim(strip_tags($this->request->post('amenities', '')));
        $squareFeet = trim($this->request->post('square_feet', ''));
        $viewType = trim(strip_tags($this->request->post('view_type', '')));

        // Validation
        if ($roomNumber === '' || !ctype_alnum($roomNumber)) {
            $this->flash('error', 'Room number is required and must be alphanumeric.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        if (!in_array($roomType, $allowedRoomTypes, true)) {
            $this->flash('error', 'Invalid room type selected.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        if (!is_numeric($floorNumber) || (int)$floorNumber < 1 || (int)$floorNumber > 50) {
            $this->flash('error', 'Floor number must be between 1 and 50.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        if (!is_numeric($basePrice) || (float)$basePrice <= 0) {
            $this->flash('error', 'Base price must be a positive number.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        if (!is_numeric($maxOccupancy) || (int)$maxOccupancy < 1 || (int)$maxOccupancy > 20) {
            $this->flash('error', 'Max occupancy must be between 1 and 20.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        if (!is_numeric($numBeds) || (int)$numBeds < 1 || (int)$numBeds > 10) {
            $this->flash('error', 'Number of beds must be between 1 and 10.');
            $this->redirect('/admin/rooms/edit/' . (int)$id);
            return;
        }

        // Convert amenities from comma-separated to JSON array
        $amenitiesJson = null;
        if ($amenities !== '') {
            $amenitiesArray = array_map('trim', explode(',', $amenities));
            $amenitiesArray = array_filter($amenitiesArray, fn($a) => $a !== '');
            $amenitiesJson = json_encode(array_values($amenitiesArray));
        }

        $data = [
            'room_number' => $roomNumber,
            'room_type' => $roomType,
            'floor_number' => (int)$floorNumber,
            'description' => $description !== '' ? $description : null,
            'base_price' => (float)$basePrice,
            'weekend_price' => ($weekendPrice !== '' && is_numeric($weekendPrice)) ? (float)$weekendPrice : null,
            'peak_season_price' => ($peakSeasonPrice !== '' && is_numeric($peakSeasonPrice)) ? (float)$peakSeasonPrice : null,
            'max_occupancy' => (int)$maxOccupancy,
            'num_beds' => (int)$numBeds,
            'bed_type' => $bedType !== '' ? $bedType : null,
            'amenities' => $amenitiesJson,
            'square_feet' => ($squareFeet !== '' && is_numeric($squareFeet)) ? (int)$squareFeet : null,
            'view_type' => $viewType !== '' ? $viewType : null,
        ];

        $roomRepo = new RoomRepository($this->db);
        $roomRepo->update((int)$id, $data);

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'edit_room',
                'room',
                (int)$id,
                'Admin edited room ' . $roomNumber,
                $this->request->ip(),
                $this->request->userAgent(),
                'info',
                0,
            ]
        );

        $this->flash('success', 'Room ' . $roomNumber . ' updated successfully.');
        $this->redirect('/admin/rooms');
    }

    /**
     * Toggle room availability (POST)
     */
    public function toggleRoom(array $params = []): void
    {
        $this->requireAdmin();

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid room ID.');
            $this->redirect('/admin/rooms');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/rooms');
            return;
        }

        $roomRepo = new RoomRepository($this->db);
        $room = $roomRepo->find((int)$id);

        if (!$room) {
            $this->flash('error', 'Room not found.');
            $this->redirect('/admin/rooms');
            return;
        }

        $newStatus = !$room->is_available;
        $roomRepo->updateAvailability((int)$id, $newStatus);

        $statusLabel = $newStatus ? 'enabled' : 'disabled';

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'toggle_room',
                'room',
                (int)$id,
                'Admin ' . $statusLabel . ' room ' . $room->room_number,
                $this->request->ip(),
                $this->request->userAgent(),
                'info',
                0,
            ]
        );

        $this->flash('success', 'Room ' . $room->room_number . ' has been ' . $statusLabel . '.');
        $this->redirect('/admin/rooms');
    }

    /**
     * Activity logs viewer with filters
     */
    public function logs(array $params = []): void
    {
        $this->requireAdmin();

        $severity = trim($this->request->get('severity', ''));
        $securityOnly = trim($this->request->get('security_only', ''));
        $dateFrom = trim($this->request->get('date_from', ''));
        $dateTo = trim($this->request->get('date_to', ''));

        $allowedSeverities = ['info', 'warning', 'error', 'critical'];
        if ($severity !== '' && !in_array($severity, $allowedSeverities, true)) {
            $severity = '';
        }

        // Validate date formats
        if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }

        $sql = "SELECT al.*, u.username
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $sqlParams = [];

        if ($severity !== '') {
            $sql .= " AND al.severity = ?";
            $sqlParams[] = $severity;
        }

        if ($securityOnly === '1') {
            $sql .= " AND al.is_security_event = 1";
        }

        if ($dateFrom !== '') {
            $sql .= " AND al.created_at >= ?";
            $sqlParams[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $sql .= " AND al.created_at <= ?";
            $sqlParams[] = $dateTo . ' 23:59:59';
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT 200";

        $logs = $this->db->fetchAll($sql, $sqlParams);

        $pagination = $this->paginate($logs, 25);

        $this->view('admin.logs', [
            'title' => 'Activity Logs',
            'logs' => $pagination['items'],
            'pagination' => $pagination,
            'severity' => $severity,
            'securityOnly' => $securityOnly,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'allowedSeverities' => $allowedSeverities,
        ])->send();
    }

    /**
     * View single booking with full details
     */
    public function viewBooking(array $params = []): void
    {
        $this->requireAdmin();

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid booking ID.');
            $this->redirect('/admin/bookings');
            return;
        }

        $id = (int)$id;

        $booking = $this->db->fetchOne(
            "SELECT b.*, u.username, u.full_name, u.email, u.phone,
                    r.room_number, r.room_type, r.floor_number,
                    h.name as hotel_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN rooms r ON b.room_id = r.id
            JOIN hotels h ON r.hotel_id = h.id
            WHERE b.id = ? AND b.is_deleted = 0",
            [$id]
        );

        if (!$booking) {
            $this->flash('error', 'Booking not found.');
            $this->redirect('/admin/bookings');
            return;
        }

        $this->view('admin.booking_detail', [
            'title' => 'Booking #' . $this->esc($booking['booking_reference'] ?? ''),
            'booking' => $booking,
        ])->send();
    }

    /**
     * Update booking status (POST)
     */
    public function updateBookingStatus(array $params = []): void
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/bookings');
            return;
        }

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid booking ID.');
            $this->redirect('/admin/bookings');
            return;
        }

        $id = (int)$id;
        $newStatus = trim($this->request->post('new_status', ''));
        $allowedStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $this->flash('error', 'Invalid status value.');
            $this->redirect('/admin/booking/' . $id);
            return;
        }

        $bookingRepo = new BookingRepository($this->db);

        if (!$bookingRepo->exists($id)) {
            $this->flash('error', 'Booking not found.');
            $this->redirect('/admin/bookings');
            return;
        }

        if ($newStatus === 'cancelled') {
            $bookingRepo->cancel($id, $this->getCurrentUserId(), 'Cancelled by admin');
        } else {
            $bookingRepo->updateStatus($id, $newStatus);
        }

        // If checked_out, also mark unpaid bookings as paid
        if ($newStatus === 'checked_out') {
            $booking = $this->db->fetchOne(
                "SELECT payment_status FROM bookings WHERE id = ? AND is_deleted = 0",
                [$id]
            );
            if ($booking && ($booking['payment_status'] ?? '') === 'unpaid') {
                $bookingRepo->updatePaymentStatus($id, 'paid');
            }
        }

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'update_booking_status',
                'booking',
                $id,
                'Admin updated booking status to ' . $newStatus,
                $this->request->ip(),
                $this->request->userAgent(),
                'info',
                0,
            ]
        );

        $this->flash('success', 'Booking status updated to ' . ucwords(str_replace('_', ' ', $newStatus)) . '.');
        $this->redirect('/admin/booking/' . $id);
    }

    /**
     * Update booking payment status (POST)
     */
    public function updatePaymentStatus(array $params = []): void
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/bookings');
            return;
        }

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid booking ID.');
            $this->redirect('/admin/bookings');
            return;
        }

        $id = (int)$id;
        $newPaymentStatus = trim($this->request->post('new_payment_status', ''));
        $allowedPaymentStatuses = ['unpaid', 'partial', 'paid', 'refunded'];

        if (!in_array($newPaymentStatus, $allowedPaymentStatuses, true)) {
            $this->flash('error', 'Invalid payment status value.');
            $this->redirect('/admin/booking/' . $id);
            return;
        }

        $bookingRepo = new BookingRepository($this->db);

        if (!$bookingRepo->exists($id)) {
            $this->flash('error', 'Booking not found.');
            $this->redirect('/admin/bookings');
            return;
        }

        $bookingRepo->updatePaymentStatus($id, $newPaymentStatus);

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'update_payment_status',
                'booking',
                $id,
                'Admin updated payment status to ' . $newPaymentStatus,
                $this->request->ip(),
                $this->request->userAgent(),
                'info',
                0,
            ]
        );

        $this->flash('success', 'Payment status updated to ' . ucwords($newPaymentStatus) . '.');
        $this->redirect('/admin/booking/' . $id);
    }

    /**
     * Toggle user active/inactive status (POST)
     */
    public function toggleUserStatus(array $params = []): void
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/users');
            return;
        }

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid user ID.');
            $this->redirect('/admin/users');
            return;
        }

        $id = (int)$id;

        // Prevent admin from deactivating themselves
        if ($id === $this->getCurrentUserId()) {
            $this->flash('error', 'You cannot deactivate your own account.');
            $this->redirect('/admin/users');
            return;
        }

        $userRow = $this->db->fetchOne(
            "SELECT id, is_active FROM users WHERE id = ? AND is_deleted = 0",
            [$id]
        );

        if (!$userRow) {
            $this->flash('error', 'User not found.');
            $this->redirect('/admin/users');
            return;
        }

        $newActiveStatus = empty($userRow['is_active']) ? 1 : 0;

        $userRepo = new UserRepository($this->db);
        $userRepo->update($id, ['is_active' => $newActiveStatus]);

        // Log the admin action
        $actionDesc = $newActiveStatus ? 'activated' : 'deactivated';
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'toggle_user_status',
                'user',
                $id,
                'Admin ' . $actionDesc . ' user account',
                $this->request->ip(),
                $this->request->userAgent(),
                'warning',
                1,
            ]
        );

        $this->flash('success', 'User account ' . $actionDesc . ' successfully.');
        $this->redirect('/admin/users');
    }

    /**
     * Change user role (POST)
     */
    public function changeUserRole(array $params = []): void
    {
        $this->requireAdmin();

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/users');
            return;
        }

        $id = $params['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->flash('error', 'Invalid user ID.');
            $this->redirect('/admin/users');
            return;
        }

        $id = (int)$id;

        // Prevent admin from changing their own role
        if ($id === $this->getCurrentUserId()) {
            $this->flash('error', 'You cannot change your own role.');
            $this->redirect('/admin/users');
            return;
        }

        $newRole = trim($this->request->post('new_role', ''));
        $allowedRoles = ['admin', 'user'];

        if (!in_array($newRole, $allowedRoles, true)) {
            $this->flash('error', 'Invalid role value.');
            $this->redirect('/admin/users');
            return;
        }

        $userRow = $this->db->fetchOne(
            "SELECT id FROM users WHERE id = ? AND is_deleted = 0",
            [$id]
        );

        if (!$userRow) {
            $this->flash('error', 'User not found.');
            $this->redirect('/admin/users');
            return;
        }

        $userRepo = new UserRepository($this->db);
        $userRepo->update($id, ['role' => $newRole]);

        // Log the admin action
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, severity, is_security_event, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$this->db->now()})",
            [
                $this->getCurrentUserId(),
                'change_user_role',
                'user',
                $id,
                'Admin changed user role to ' . $newRole,
                $this->request->ip(),
                $this->request->userAgent(),
                'warning',
                1,
            ]
        );

        $this->flash('success', 'User role changed to ' . ucfirst($newRole) . ' successfully.');
        $this->redirect('/admin/users');
    }
}
