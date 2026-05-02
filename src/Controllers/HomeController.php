<?php

namespace App\Controllers;

use App\Core\Response;
use App\Repositories\HotelRepository;
use App\Repositories\RoomRepository;

/**
 * Home Controller
 *
 * Handles homepage and room search for a single-hotel system
 *
 * @package App\Controllers
 */
class HomeController extends BaseController
{
    /**
     * Homepage - single hotel landing page
     */
    public function index()
    {
        $hotelRepo = new HotelRepository($this->db);
        $roomRepo = new RoomRepository($this->db);

        // Load the single hotel
        $hotel = $hotelRepo->find(1);

        // Load featured rooms (available, limit 6)
        $featuredRooms = $roomRepo->findAvailable();
        $featuredRooms = array_slice($featuredRooms, 0, 6);

        // Total available room count for stats
        $allAvailable = $roomRepo->findAvailable();

        return $this->view('pages.home', [
            'title' => $hotel ? $hotel->name . ' - Welcome' : 'Welcome',
            'hotel' => $hotel,
            'rooms' => $featuredRooms,
            'totalRooms' => count($allAvailable),
        ])->send();
    }

    /**
     * Search rooms
     */
    public function search()
    {
        $query = trim($this->request->get('q', $this->request->get('query', '')));
        if ($query === '') {
            $this->redirect('/rooms');
            exit;
        }

        $roomRepo = new RoomRepository($this->db);
        $rooms = $roomRepo->search($query);

        $this->view('pages.search', [
            'title' => 'Search Rooms',
            'query' => $query,
            'rooms' => $rooms,
        ])->send();
    }

    /**
     * About page
     */
    public function about()
    {
        $hotelRepo = new HotelRepository($this->db);
        $hotel = $hotelRepo->find(1);

        $this->view('pages.about', [
            'title' => 'About Us',
            'hotel' => $hotel,
        ])->send();
    }

    /**
     * Contact page
     */
    public function contact()
    {
        // Load hotel info for the contact page
        $hotel = $this->db->fetchOne(
            "SELECT name, address, city, state, zip_code, phone, email FROM hotels WHERE id = 1"
        );

        $this->view('pages.contact', [
            'title' => 'Contact Us',
            'hotel' => $hotel,
        ])->send();
    }

    /**
     * Process contact form submission (POST)
     */
    public function submitContact()
    {
        // CSRF validation
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/contact');
            exit;
        }

        // Collect POST data
        $name = trim($this->request->post('name') ?? '');
        $email = trim($this->request->post('email') ?? '');
        $phone = trim($this->request->post('phone') ?? '');
        $subject = trim($this->request->post('subject') ?? '');
        $message = trim($this->request->post('message') ?? '');

        // Validation
        $errors = [];

        if ($name === '') {
            $errors[] = 'Name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if ($subject === '') {
            $errors[] = 'Subject is required.';
        }

        if ($message === '' || mb_strlen($message) < 10) {
            $errors[] = 'Message is required and must be at least 10 characters.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/contact');
            exit;
        }

        // Sanitize inputs
        $name = strip_tags($name);
        $email = strip_tags($email);
        $phone = strip_tags($phone);
        $subject = strip_tags($subject);
        $message = strip_tags($message);

        // Build description for the activity log
        $description = "Contact inquiry from {$name} ({$email}). Subject: {$subject}. Message: {$message}";
        if ($phone !== '') {
            $description = "Contact inquiry from {$name} ({$email}, {$phone}). Subject: {$subject}. Message: {$message}";
        }

        // Truncate description to prevent excessively long log entries
        if (mb_strlen($description) > 1000) {
            $description = mb_substr($description, 0, 1000) . '...';
        }

        // Log the contact inquiry to activity_logs
        $userId = $this->getCurrentUserId();
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address, severity, created_at) VALUES (?, 'contact_inquiry', 'contact', ?, ?, 'info', {$this->db->now()})",
            [
                $userId,
                $description,
                $this->request->ip(),
            ]
        );

        $this->flash('success', 'Thank you for your message! We\'ll get back to you within 24 hours.');
        $this->redirect('/contact');
        exit;
    }
}
