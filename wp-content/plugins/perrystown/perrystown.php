<?php
/**
 * Plugin Name: Perrystown
 * Description: Perrystown plugin 
 * Version: 1.0.0
 * Author: Mahabur Rahman
 * Author URI: https://yourwebsite.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: perrystown
 */

if (!defined('ABSPATH')) exit; // Prevent direct access


// ✅ Define plugin constants
define('PERRYSTOWN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PERRYSTOWN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERRYSTOWN_VERSION', '1.0.1');


// ✅ Initialize Routes (keep as in your current pattern)
add_action('rest_api_init', function() {
    new \Perrystown\App\Bookings\Booking_Routes();
    new \Perrystown\App\Faq\Faq_Routes();
});

// ✅ On plugin activation, create required tables
register_activation_hook(__FILE__, function () {
    \Perrystown\App\Bookings\Booking_Table::create_table();
    \Perrystown\App\Faq\Faq_Table::create_table(); // ✅ namespace fixed

    // Set plugin version
    update_option('perrystown_version', PERRYSTOWN_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
});

// ======================
// Auth / JWT
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/auth/jwt_hooks.php';

define('PERRYSTOWN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PERRYSTOWN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERRYSTOWN_VERSION', '1.0.0');
define('PERRYSTOWN_MAIN_FILE', __FILE__); // Pass main file reference

// ✅ SMTP Configuration - Use constants from wp-config.php
require_once PERRYSTOWN_PLUGIN_PATH . 'helper/mail.php';


// ✅ Load files
require_once PERRYSTOWN_PLUGIN_PATH . 'helper/AllLoadingFiles.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'helper/ActivationHook.php';

// ✅ Initialize all REST API routes
add_action('rest_api_init', function() {
    // Booking Routes
    $booking_routes = new \Perrystown\App\Bookings\Booking_Routes();
    $booking_routes->register_booking_routes();

    // FAQ Routes
    $faq_routes = new \Perrystown\App\Faq\Faq_Routes();
    $faq_routes->register_routes();
});

// ======================
// Gallery Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/galleries/gallery_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/galleries/gallery_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/galleries/gallery_routes.php';

register_activation_hook(__FILE__, function () {
    \Perrystown\App\Gallery\Gallery_Table::create_table();
});

// ======================
// Referrals Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/referrals/referral_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/referrals/referral_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/referrals/referral_routes.php';

register_activation_hook(__FILE__, function () {
    \Perrystown\App\Referral\Referral_Table::create_table();
});
