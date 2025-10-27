<?php
namespace Perrystown\App\Bookings;

if (!defined('ABSPATH')) exit;

class Booking_Table {

    // ✅ Create booking table
    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'bookings';

        $sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booked_code varchar(50) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT NULL,
            subject varchar(100) NOT NULL,
            preferred_date date NOT NULL,
            preferred_time time NOT NULL,
            message text DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            duration varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    // ✅ Drop table on deactivation
    public static function drop_table() {
        global $wpdb;

        $tables = [
            "{$wpdb->prefix}bookings"
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}
