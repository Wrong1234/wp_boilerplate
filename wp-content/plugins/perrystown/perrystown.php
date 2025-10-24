<?php
/**
 * Plugin Name: Perrystown
 * Description: Perrystown plugin
 * Version: 1.0.0
 * Author: Rafiul Hossain
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

//jwt time validation
require_once PERRYSTOWN_PLUGIN_PATH . 'app/auth/jwt_hooks.php';



// CONTACT MODULE 
 
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_routes.php';

// register_activation_hook(__FILE__, function () {
//     \Perrystown\App\Contact\Contact_Table::create_table();
// });

// register_deactivation_hook(__FILE__, function () {
//     \Perrystown\App\Contact\Contact_Table::drop_table();
// });



// SERVICE MODULE
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_routes.php';

register_activation_hook(__FILE__, function () {
    \Perrystown\App\Service\Service_Table::create_table();
});
// register_deactivation_hook(...) // if present

//  NOT drop tables on deactivate.
// register_deactivation_hook(__FILE__, function () { /* keep service table */ });
// When a new JWT is about to be returned, mark all older tokens invalid.
