<?php
/**
 * Plugin Name: Perrystown
 * Description: Perrystown plugin (bookings & FAQ modules)
 * Version: 1.0.1
 * Author: Rafiul Hossain
 * Author URI: https://yourwebsite.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: perrystown
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// ✅ SMTP Configuration - Use constants from wp-config.php for security
add_action('phpmailer_init', function($phpmailer) {
    // Only configure if constants are defined in wp-config.php
    if (defined('PERRYSTOWN_SMTP_HOST') && defined('PERRYSTOWN_SMTP_USER') && defined('PERRYSTOWN_SMTP_PASS')) {
        error_log('PHPMailer Init Hook Triggered');
        
        $phpmailer->isSMTP();
        $phpmailer->Host       = PERRYSTOWN_SMTP_HOST;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = PERRYSTOWN_SMTP_PORT ?? 587;
        $phpmailer->Username   = PERRYSTOWN_SMTP_USER;
        $phpmailer->Password   = PERRYSTOWN_SMTP_PASS;
        $phpmailer->SMTPSecure = PERRYSTOWN_SMTP_SECURE ?? 'tls';
        $phpmailer->From       = PERRYSTOWN_SMTP_FROM ?? PERRYSTOWN_SMTP_USER;
        $phpmailer->FromName   = PERRYSTOWN_SMTP_FROM_NAME ?? 'Perrystown';
        
        // Enable debug in development only
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $phpmailer->SMTPDebug = 2;
            $phpmailer->Debugoutput = function($str, $level) {
                error_log("SMTP Debug level $level: $str");
            };
        }
        
        error_log('SMTP configured with host: ' . $phpmailer->Host);
    }
});

// ✅ Define plugin constants
define('PERRYSTOWN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PERRYSTOWN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERRYSTOWN_VERSION', '1.0.1');

// ======================
// Bookings Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.migration.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.model.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.validator.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.routes.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/includes/BookingConfig.php';

// ======================
// FAQ Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.migration.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.model.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.validator.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.routes.php';

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

// ✅ On plugin deactivation: keep data (no drops)
register_deactivation_hook(__FILE__, function () {
    // Intentionally no table drops to preserve data
});

// ======================
// Auth / JWT
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/auth/jwt_hooks.php';

// ======================
// Contact Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_routes.php';

register_activation_hook(__FILE__, function () {
    \Perrystown\App\Contact\Contact_Table::create_table();
});

// If you ever decide to drop on deactivate, add a hook—but current policy is to keep data.
// register_deactivation_hook(__FILE__, function () {
//     \Perrystown\App\Contact\Contact_Table::drop_table();
// });

// ======================
// Service Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_routes.php';

register_activation_hook(__FILE__, function () {
    \Perrystown\App\Service\Service_Table::create_table();
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
