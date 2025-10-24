<?php
namespace Perrystown\App\Bookings\Includes;

class BookingConfig {
    
    /**
     * Get business hours configuration
     */
    public static function get_business_hours() {
        return [
            'start' => '09:00',  // Business starts at 9 AM
            'end' => '21:00',    // Business ends at 9 PM
            'slot_duration' => 30, // 30 minutes per slot
            'break_time' => [
                'start' => '13:00',
                'end' => '14:00'
            ]
        ];
    }
    
    /**
     * Get available durations
     */
    public static function get_durations() {
        return ['30min', '60min', '90min', '120min'];
    }
    
    /**
     * Get duration in minutes
     */
    public static function duration_to_minutes($duration) {
        $map = [
            '30min' => 30,
            '60min' => 60,
            '90min' => 90,
            '120min' => 120
        ];
        return $map[$duration] ?? 30;
    }
}