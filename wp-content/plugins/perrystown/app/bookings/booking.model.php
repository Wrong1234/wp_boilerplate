<?php
namespace Perrystown\App\Bookings;

if (!defined('ABSPATH')) exit;

class Booking_Model {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bookings';
    }

    /**
     * Create a new booking
     */
    public function create($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'booked_code' => $data['booked_code'],
                'name' => sanitize_text_field($data['name']),
                'email' => sanitize_email($data['email']),
                'phone' => sanitize_text_field($data['phone'] ?? ''),
                'subject' => sanitize_text_field($data['subject']),
                'preferred_date' => sanitize_text_field($data['preferred_date']),
                'preferred_time' => sanitize_text_field($data['preferred_time']),
                'message' => sanitize_textarea_field($data['message'] ?? ''),
                'status' => sanitize_text_field($data['status'] ?? 'pending'),
                'duration' => sanitize_text_field($data['duration']),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get booking by ID
     */
    public function get_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
        );
    }

    /**
     * Get booking by code
     */
    public function get_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE booked_code = %s", $code)
        );
    }

    /**
     * Get all bookings with pagination
     */
    public function get_all($limit = 10, $offset = 0, $status = null) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }

        $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $limit, $offset)
        );
    }

    /**
     * Update booking
     */
    public function update($id, $data) {
        global $wpdb;
        
        $update_data = [];
        $format = [];

        $allowed_fields = ['name', 'email', 'phone', 'subject', 'preferred_date', 
                          'preferred_time', 'message', 'status', 'duration'];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = '%s';
            }
        }

        $update_data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        return $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $id],
            $format,
            ['%d']
        );
    }

    /**
     * Delete booking
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Count total bookings
     */
    public function count($status = null) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where}");
    }

    /**
     * Check if booking exists for date/time
     */
    public function check_availability($date, $time) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                WHERE preferred_date = %s AND preferred_time = %s AND status != 'cancelled'",
                $date, $time
            )
        );

        return (int) $count === 0;
    }






    // slot management

    public function get_booked_slots($date) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookings';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT preferred_time, duration 
            FROM $table 
            WHERE preferred_date = %s 
            AND status != 'cancelled'
            ORDER BY preferred_time ASC",
            $date
        ));
        
        return $results;
}

/**
 * Check if a time slot is available considering duration
 */
    public function is_slot_available($date, $time, $duration_minutes) {
        global $wpdb;
        $table = $wpdb->prefix . 'bookings';
        
        // Convert time to timestamp for calculation
        $requested_start = strtotime("$date $time");
        $requested_end = $requested_start + ($duration_minutes * 60);
        
        // Get all bookings for this date
        $bookings = $this->get_booked_slots($date);
        
        foreach ($bookings as $booking) {
            $booked_start = strtotime("$date {$booking->preferred_time}");
            $booked_duration = \Perrystown\App\Bookings\Includes\BookingConfig::duration_to_minutes($booking->duration);
            $booked_end = $booked_start + ($booked_duration * 60);
            
            // Check if there's any overlap
            if (($requested_start < $booked_end) && ($requested_end > $booked_start)) {
                return false;
            }
        }
        
        return true;
    }
}