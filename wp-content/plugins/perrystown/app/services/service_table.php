<?php
// ============================================
// 1. SERVICE TABLE (service_table.php)
// ============================================

namespace Perrystown\App\Service;

if (!defined('ABSPATH')) exit;

class Service_Table
{
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'perrystown_services';
    }

    public static function create_table()
    {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            title VARCHAR(191) NULL,
            rate VARCHAR(50)NOT NULL,
            description LONGTEXT NULL,
            image VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function drop_table()
    {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . self::table_name());
    }
}