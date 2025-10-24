<?php
namespace Perrystown\App\Bookings;

if (!defined('ABSPATH')) exit;

class Booking_Controller {

    private $model;
    private $validator;

    public function __construct() {
        $this->model = new Booking_Model();
        $this->validator = new Booking_Validator();
    }

    /**
     * Create a new booking
     */
    public function create(\WP_REST_Request $request) {
        try {
            $data = $request->get_json_params();

            // Verify nonce if present
            if (isset($data['_wpnonce'])) {
                if (!$this->validator->verify_nonce($data['_wpnonce'])) {
                    return $this->error_response( $this->validator->get_errors(), 'Security validation failed', 403 );
                }
            }

            // Validate input
            if (!$this->validator->validate_create($data)) {
                return $this->error_response(
                    $this->validator->get_errors(),
                    'Validation failed',
                    422
                );
            }

            // Generate unique booking code
            $data['booked_code'] = $this->generate_booking_code();

            // Check availability
            if (!$this->model->check_availability($data['preferred_date'], $data['preferred_time'])) {
                return $this->error_response(
                    ['availability' => 'This time slot is already booked'],
                    'Time slot unavailable',
                    409
                );
            }

            // Create booking
            $booking_id = $this->model->create($data);

            if (!$booking_id) {
                return $this->error_response(
                    ['database' => 'Failed to create booking'],
                    'Database error',
                    500
                );
            }

            // Get created booking
            $booking = $this->model->get_by_id($booking_id);

           $email_sent = $this->send_confirmation_email($booking);

            return $this->success_response([
                'booking' => $booking,
                'message' => 'Booking created successfully',
                'email_sent' => $email_sent
            ], 201);

        } catch (\Exception $e) {
            return $this->error_response(
                ['exception' => $e->getMessage()],
                'An error occurred',
                500
            );
        }
    }

    /**
     * Get single booking
     */
    public function get(\WP_REST_Request $request) {
        $id = $request->get_param('id');

        $booking = $this->model->get_by_id($id);

        if (!$booking) {
            return $this->error_response(
                ['id' => 'Booking not found'],
                'Not found',
                404
            );
        }

        return $this->success_response(['booking' => $booking]);
    }

    /**
     * Get all bookings
     */
    public function get_all(\WP_REST_Request $request) {
        $page = max(1, (int) $request->get_param('page') ?: 1);
        $per_page = max(1, min(100, (int) $request->get_param('per_page') ?: 10));
        $status = $request->get_param('status');

        $offset = ($page - 1) * $per_page;

        $bookings = $this->model->get_all($per_page, $offset, $status);
        $total = $this->model->count($status);

        return $this->success_response([
            'bookings' => $bookings,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ]
        ]);
        // return $this->success_response([
        //     'message' => "Your route are correct"
        // ]);
    }

    /**
     * Update booking
     */
    public function update(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            $data = $request->get_json_params();

            // Check if booking exists
            $booking = $this->model->get_by_id($id);
            if (!$booking) {
                return $this->error_response(
                    ['id' => 'Booking not found'],
                    'Not found',
                    404
                );
            }

            // Verify nonce
            if (isset($data['_wpnonce'])) {
                if (!$this->validator->verify_nonce($data['_wpnonce'])) {
                    return $this->error_response(
                        $this->validator->get_errors(),
                        'Security validation failed',
                        403
                    );
                }
            }

            // Validate input
            if (!$this->validator->validate_update($data)) {
                return $this->error_response(
                    $this->validator->get_errors(),
                    'Validation failed',
                    422
                );
            }

            // Update booking
            $result = $this->model->update($id, $data);

            if ($result === false) {
                return $this->error_response(
                    ['database' => 'Failed to update booking'],
                    'Database error',
                    500
                );
            }

            // Get updated booking
            $updated_booking = $this->model->get_by_id($id);

            return $this->success_response([
                'booking' => $updated_booking,
                'message' => 'Booking updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->error_response(
                ['exception' => $e->getMessage()],
                'An error occurred',
                500
            );
        }
    }

    /**
     * Delete booking
     */
    public function delete(\WP_REST_Request $request) {
        $id = $request->get_param('id');

        // Check if booking exists
        $booking = $this->model->get_by_id($id);
        if (!$booking) {
            return $this->error_response(
                ['id' => 'Booking not found'],
                'Not found',
                404
            );
        }

        $result = $this->model->delete($id);

        if ($result === false) {
            return $this->error_response(
                ['database' => 'Failed to delete booking'],
                'Database error',
                500
            );
        }

        return $this->success_response([
            'message' => 'Booking deleted successfully'
        ]);
    }

    /**
     * Generate unique booking code
     */
    private function generate_booking_code() {
        do {
            $code = 'Perrystown' . strtoupper(wp_generate_password(8, false));
            $existing = $this->model->get_by_code($code);
        } while ($existing);

        return $code;
    }

    /**
     * Send confirmation email
     */
    private function send_confirmation_email($booking) {
        $to = $booking->email;
        $subject = 'Booking Confirmation - ' . $booking->booked_code;
        
        // HTML message
        $message = sprintf(
            "<html><body>
            <h2>Dear %s,</h2>
            <p>Your booking has been confirmed.</p>
            <ul>
                <li><strong>Booking Code:</strong> %s</li>
                <li><strong>Date:</strong> %s</li>
                <li><strong>Time:</strong> %s</li>
                <li><strong>Duration:</strong> %s</li>
            </ul>
            <p>Thank you for choosing Perrystown!</p>
            </body></html>",
            esc_html($booking->name),
            esc_html($booking->booked_code),
            esc_html($booking->preferred_date),
            esc_html($booking->preferred_time),
            esc_html($booking->duration)
        );

        // Set headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Perrystown Booking <mahabur1814031@gmail.com>'
        );

        // Send email with error logging
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if (!$sent) {
            error_log('Failed to send booking confirmation email to: ' . $to);
        }
        
        return $sent;
    }

    /**
     * Success response helper
     */
    private function success_response($data, $status = 200) {
        return new \WP_REST_Response([
            'success' => true,
            'data' => $data
        ], $status);
    }

    /**
     * Error response helper
     */
    private function error_response($errors, $message, $status = 400) {
        return new \WP_REST_Response([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }


    //slot management

    public function get_available_slots(\WP_REST_Request $request) {
        try {
            $date = $request->get_param('date');
            $duration = $request->get_param('duration') ?: '30min';
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $this->error_response(
                    ['date' => 'Invalid date format. Use YYYY-MM-DD'],
                    'Invalid date format',
                    400
                );
            }
            
            // Check if date is in the past
            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                return $this->error_response(
                    ['date' => 'Cannot book slots in the past'],
                    'Invalid date',
                    400
                );
            }
            
            // Validate duration
            $valid_durations = \Perrystown\App\Bookings\Includes\BookingConfig::get_durations();
            if (!in_array($duration, $valid_durations)) {
                return $this->error_response(
                    ['duration' => 'Invalid duration. Must be one of: ' . implode(', ', $valid_durations)],
                    'Invalid duration',
                    400
                );
            }
            
            // Get configuration
            $config = \Perrystown\App\Bookings\Includes\BookingConfig::get_business_hours();
            $duration_minutes = \Perrystown\App\Bookings\Includes\BookingConfig::duration_to_minutes($duration);
            
            // Generate all possible slots
            $slots = $this->generate_time_slots(
                $config['start'],
                $config['end'],
                $config['slot_duration'],
                $config['break_time']
            );
            
            // Get booked slots
            $booked_slots = $this->model->get_booked_slots($date);
            
            // Check availability for each slot
            $available_slots = [];
            foreach ($slots as $slot) {
                $is_available = $this->model->is_slot_available($date, $slot, $duration_minutes);
                
                $available_slots[] = [
                    'time' => $slot,
                    'formatted_time' => date('g:i A', strtotime($slot)),
                    'available' => $is_available,
                    'duration' => $duration
                ];
            }
            
            // Separate available and booked for easier frontend handling
            $response = [
                'date' => $date,
                'duration' => $duration,
                'slots' => $available_slots,
                'available_count' => count(array_filter($available_slots, fn($s) => $s['available'])),
                'total_count' => count($available_slots),
                'business_hours' => [
                    'start' => $config['start'],
                    'end' => $config['end'],
                    'formatted_start' => date('g:i A', strtotime($config['start'])),
                    'formatted_end' => date('g:i A', strtotime($config['end']))
                ]
            ];
            
            return $this->success_response($response);
            
        } catch (\Exception $e) {
            return $this->error_response(
                ['exception' => $e->getMessage()],
                'An error occurred',
                500
            );
        }
    }

/**
 * Generate time slots
 */
    private function generate_time_slots($start, $end, $interval, $break_time) {
        $slots = [];
        $current = strtotime($start);
        $end_time = strtotime($end);
        $break_start = strtotime($break_time['start']);
        $break_end = strtotime($break_time['end']);
        
        while ($current < $end_time) {
            // Skip break time
            if ($current >= $break_start && $current < $break_end) {
                $current = $break_end;
                continue;
            }
            
            $slots[] = date('H:i:s', $current);
            $current += ($interval * 60);
        }
        
        return $slots;
    }
}