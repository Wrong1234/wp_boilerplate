<?php
namespace Perrystown\App\Bookings;

if (!defined('ABSPATH')) exit;

class Booking_Validator {
    
    private $errors = [];

    /**
     * Validate booking creation data
     */
    public function validate_create($data) {
        $this->errors = [];

        // Name validation
        if (empty($data['name'])) {
            $this->errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $this->errors['name'] = 'Name must be at least 2 characters';
        } elseif (strlen($data['name']) > 100) {
            $this->errors['name'] = 'Name must not exceed 100 characters';
        }

        // Email validation
        if (empty($data['email'])) {
            $this->errors['email'] = 'Email is required';
        } elseif (!is_email($data['email'])) {
            $this->errors['email'] = 'Invalid email format';
        }

        // Phone validation (optional but validated if provided)
        if (!empty($data['phone'])) {
            if (!preg_match('/^[\d\s\+\-\(\)]+$/', $data['phone'])) {
                $this->errors['phone'] = 'Invalid phone number format';
            } elseif (strlen($data['phone']) > 20) {
                $this->errors['phone'] = 'Phone number must not exceed 20 characters';
            }
        }

        // Subject validation
        if (empty($data['subject'])) {
            $this->errors['subject'] = 'Subject is required';
        } elseif (strlen($data['subject']) > 100) {
            $this->errors['subject'] = 'Subject must not exceed 100 characters';
        }

        // Date validation
        if (empty($data['preferred_date'])) {
            $this->errors['preferred_date'] = 'Preferred date is required';
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $data['preferred_date']);
            if (!$date || $date->format('Y-m-d') !== $data['preferred_date']) {
                $this->errors['preferred_date'] = 'Invalid date format (YYYY-MM-DD required)';
            } else {
                $today = new \DateTime('today');
                if ($date < $today) {
                    $this->errors['preferred_date'] = 'Date cannot be in the past';
                }
            }
        }

        // Time validation
        if (empty($data['preferred_time'])) {
            $this->errors['preferred_time'] = 'Preferred time is required';
        } else {
            $time = \DateTime::createFromFormat('H:i:s', $data['preferred_time']);
            if (!$time) {
                // Try H:i format
                $time = \DateTime::createFromFormat('H:i', $data['preferred_time']);
                if (!$time) {
                    $this->errors['preferred_time'] = 'Invalid time format (HH:MM required)';
                }
            }
        }

        // Duration validation
        if (empty($data['duration'])) {
            $this->errors['duration'] = 'Duration is required';
        } elseif (!in_array($data['duration'], ['30min', '1hour', '1.5hours', '2hours'])) {
            $this->errors['duration'] = 'Invalid duration value';
        }

        // Message validation (optional)
        if (!empty($data['message']) && strlen($data['message']) > 1000) {
            $this->errors['message'] = 'Message must not exceed 1000 characters';
        }

        // Status validation (if provided)
        if (isset($data['status'])) {
            $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
            if (!in_array($data['status'], $valid_statuses)) {
                $this->errors['status'] = 'Invalid status value';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate booking update data
     */
    public function validate_update($data) {
        $this->errors = [];

        // Only validate fields that are being updated
        if (isset($data['name'])) {
            if (strlen($data['name']) < 2) {
                $this->errors['name'] = 'Name must be at least 2 characters';
            } elseif (strlen($data['name']) > 100) {
                $this->errors['name'] = 'Name must not exceed 100 characters';
            }
        }

        if (isset($data['email'])) {
            if (!is_email($data['email'])) {
                $this->errors['email'] = 'Invalid email format';
            }
        }

        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^[\d\s\+\-\(\)]+$/', $data['phone'])) {
                $this->errors['phone'] = 'Invalid phone number format';
            }
        }

        if (isset($data['preferred_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['preferred_date']);
            if (!$date || $date->format('Y-m-d') !== $data['preferred_date']) {
                $this->errors['preferred_date'] = 'Invalid date format';
            }
        }

        if (isset($data['status'])) {
            $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
            if (!in_array($data['status'], $valid_statuses)) {
                $this->errors['status'] = 'Invalid status value';
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Check nonce for security
     */
    public function verify_nonce($nonce, $action = 'booking_action') {
        if (!wp_verify_nonce($nonce, $action)) {
            $this->errors['security'] = 'Security validation failed';
            return false;
        }
        return true;
    }
}