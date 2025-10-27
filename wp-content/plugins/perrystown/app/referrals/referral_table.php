<?php
namespace Perrystown\App\Referral;

if (!defined('ABSPATH')) exit;

class Referral_Table {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'perrystown_referrals';
    }

    public static function create_table() {
        global $wpdb;
        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            -- Required
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(64)  NOT NULL,
            dob DATE NULL,
            dentist_name   VARCHAR(191) NOT NULL,
            practice       VARCHAR(191) NULL,
            dentist_phone  VARCHAR(64)  NULL,
            dentist_email  VARCHAR(191) NOT NULL,

            -- Optional extra
            notes TEXT NULL,
            file_url VARCHAR(255) NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function drop_table() {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . self::table_name());
    }
}
