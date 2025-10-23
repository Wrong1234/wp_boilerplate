<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

class Perrystown_Booking_Routes {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('wp/v2', '/bookings', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_bookings'),
        ));
    }

    public function get_bookings($request) {
        // Example response — replace with real database logic
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Booking route working!',
        ));
    }
}

// Initialize the class
new Perrystown_Booking_Routes();
