<?php
namespace Perrystown\App\Faq;

if (!defined('ABSPATH')) exit;

class Faq_Table {

    // ✅ Create FAQ table
    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'faq';

        $sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question varchar(255) NOT NULL,
            answer text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY question_idx (question(100)),
            FULLTEXT KEY search_idx (question, answer)
        ) $charset_collate;";

        dbDelta($sql);
    }

    // ✅ Drop table on deactivation
    public static function drop_table() {
        global $wpdb;

        $tables = [
            "{$wpdb->prefix}faq"
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}