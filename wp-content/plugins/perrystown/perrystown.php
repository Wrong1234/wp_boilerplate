<?php
/**
 * Plugin Name: Perrystown
 * Description: Perrystown plugin (bookings module connected)
 * Version: 1.0.0
 * Author: Mahabur Rahman
 * Author URI: https://yourwebsite.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: perrystown
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

define('PERRYSTOWN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PERRYSTOWN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERRYSTOWN_API_KEY', 'your_api_key_here');

// ✅ Include all booking-related files
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking_routes.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking_table.php';

// ✅ On plugin activation, create all required tables
register_activation_hook(__FILE__, function () {
    \Perrystown\App\Bookings\Booking_Table::create_table();
});


// ✅ Drop all tables when plugin deactivated
register_deactivation_hook(__FILE__, function () {
    \Perrystown\App\Bookings\Booking_Table::drop_table();
});