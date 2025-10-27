<?php


if (!defined('ABSPATH')) exit;

// ✅ Include all booking-related files
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.migration.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.model.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.validator.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/booking.routes.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/bookings/includes/BookingConfig.php';

// ✅ Include all FAQ-related files
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.migration.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.model.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.validator.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/faq/faq.routes.php';


// CONTACT MODULE 
 
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/contacts/contact_routes.php';


// SERVICE MODULE
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/services/service_routes.php';

//jwt time validation
require_once PERRYSTOWN_PLUGIN_PATH . 'app/auth/jwt_hooks.php';

//gallary
require_once PERRYSTOWN_PLUGIN_PATH . 'app/galleries/gallery_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/galleries/gallery_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/galleries/gallery_routes.php';

// ======================
// Referrals Module
// ======================
require_once PERRYSTOWN_PLUGIN_PATH . 'app/referrals/referral_table.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/referrals/referral_controller.php';
require_once PERRYSTOWN_PLUGIN_PATH . 'app/referrals/referral_routes.php';
