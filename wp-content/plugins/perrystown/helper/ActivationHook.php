<?php

if (!defined('ABSPATH')) exit;

// ✅ Use the main file constant instead of __FILE__
register_activation_hook(PERRYSTOWN_MAIN_FILE, function () {
    \Perrystown\App\Bookings\Booking_Table::create_table();
    \Perrystown\App\Faq\Faq_Table::create_table(); 
    \Perrystown\App\Service\Service_Table::create_table();
    \Perrystown\App\Contact\Contact_Table::create_table();
    \Perrystown\App\Gallery\Gallery_Table::create_table();
    \Perrystown\App\Referral\Referral_Table::create_table();
    
    update_option('perrystown_version', PERRYSTOWN_VERSION);
    flush_rewrite_rules();
});

register_deactivation_hook(PERRYSTOWN_MAIN_FILE, function () {
    flush_rewrite_rules();
});