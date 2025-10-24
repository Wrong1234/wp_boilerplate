<?php
namespace Perrystown\App\Contact;

if (!defined('ABSPATH')) exit;

class Contact_Table {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'perrystown_contacts';
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(64) NOT NULL,
            message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function drop_table() {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . self::table_name());
    }
}
