<?php
namespace Perrystown\App\Faq;

if (!defined('ABSPATH')) exit;

class Faq_Model {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'faq';
    }
    
    /**
     * Get all FAQs with pagination
     */
    public function get_all($page = 1, $per_page = 10, $search = '') {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $search_query = '';
        
        if (!empty($search)) {
            $search = $wpdb->esc_like($search);
            $search_query = $wpdb->prepare(
                "WHERE question LIKE %s OR answer LIKE %s",
                "%{$search}%",
                "%{$search}%"
            );
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} {$search_query} 
                ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$search_query}");
        
        return [
            'data' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Get single FAQ by ID
     */
    public function get_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }
    
    /**
     * Create new FAQ
     */
    public function create($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'question' => sanitize_text_field($data['question']),
                'answer' => sanitize_textarea_field($data['answer']),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update existing FAQ
     */
    public function update($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'question' => sanitize_text_field($data['question']),
                'answer' => sanitize_textarea_field($data['answer']),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }
    
    /**
     * Delete FAQ
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
    }
    
    /**
     * Check if FAQ exists
     */
    public function exists($id) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
        
        return $count > 0;
    }
}