<?php

// Initialize the class
// new Perrystown_Booking_Routes();

namespace Perrystown\App\Bookings;

use Perrystown\App\Bookings\Booking_Controller;

if (!defined('ABSPATH')) exit;

class Booking_Routes {

    private $booking_controller;

    public function __construct() {
        $this->booking_controller = new Booking_Controller();
        add_action('rest_api_init', [$this, 'register_booking_routes']);
    }

    /**
     * Register booking routes
     */
    public function register_booking_routes() {
        //  error_log('🚀 Booking_Routes::mahabur your are great topha kobul koro ');
        
        // Create booking
        register_rest_route('/wp/v2', '/bookings', [
            'methods' => 'POST',
            'callback' => [$this->booking_controller, 'create'],
            'permission_callback' => [$this, 'public_permission'],
            'args' => $this->get_create_booking_args()
        ]);

        // Get all bookings
        register_rest_route('/wp/v2', '/bookings', [
            'methods' => 'GET',
            'callback' => [$this->booking_controller, 'get_all'],
            'permission_callback' => [$this, 'public_permission'],
            'args' => [
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ],
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                ],
                'status' => [
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // Get single booking
        //admin_permission
        register_rest_route('/wp/v2', '/bookings/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this->booking_controller, 'get'],
            'permission_callback' => [$this, 'public_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Update booking
        register_rest_route('/wp/v2', '/bookings/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this->booking_controller, 'update'],
            'permission_callback' => [$this, 'public_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Delete booking
        register_rest_route('/wp/v2', '/bookings/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this->booking_controller, 'delete'],
            'permission_callback' => [$this, 'public_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // Get available slots
       register_rest_route('wp/v2', '/bookings/available-slots', [
            'methods' => 'GET',
            'callback' => [$this->booking_controller, 'get_available_slots'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Date in YYYY-MM-DD format'
                ],
                'duration' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => '30min',
                    'enum' => ['30min', '60min', '90min', '120min']
                ]
            ]
        ]);
    }

    /**
     * Get create booking arguments schema
     */
    private function get_create_booking_args() {
        return [
            'name' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) <= 100;
                }
            ],
            'email' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => function($param) {
                    return is_email($param);
                }
            ],
            'phone' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'subject' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'preferred_date' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    $date = \DateTime::createFromFormat('Y-m-d', $param);
                    return $date && $date->format('Y-m-d') === $param;
                }
            ],
            'preferred_time' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'duration' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return in_array($param, ['30min', '1hour', '1.5hours', '2hours']);
                }
            ],
            'message' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field'
            ]
        ];
    }

    /**
     * Public permission callback
     */
    public function public_permission() {
        return true; // Allow public access for creating bookings
    }

    /**
     * Admin permission callback
     */
    public function admin_permission() {
        return current_user_can('manage_options');
    }

}
