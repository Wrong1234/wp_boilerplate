<?php
namespace Perrystown\App\Faq;

if (!defined('ABSPATH')) exit;

class Faq_Validator {
    
    private $errors = [];
    
    /**
     * Validate FAQ creation data
     */
    public function validate_create($data) {
        $this->errors = [];
        
        // Validate question
        if (empty($data['question'])) {
            $this->errors['question'] = 'Question is required';
        } elseif (strlen($data['question']) < 10) {
            $this->errors['question'] = 'Question must be at least 10 characters long';
        } elseif (strlen($data['question']) > 100) {
            $this->errors['question'] = 'Question must not exceed 100 characters';
        }
        
        // Validate answer
        if (empty($data['answer'])) {
            $this->errors['answer'] = 'Answer is required';
        } elseif (strlen($data['answer']) < 20) {
            $this->errors['answer'] = 'Answer must be at least 20 characters long';
        } elseif (strlen($data['answer']) > 200) {
            $this->errors['answer'] = 'Answer must not exceed 200 characters';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate FAQ update data
     */
    public function validate_update($data) {
        return $this->validate_create($data);
    }
    
    /**
     * Validate ID parameter
     */
    public function validate_id($id) {
        $this->errors = [];
        
        if (empty($id)) {
            $this->errors['id'] = 'ID is required';
            return false;
        }
        
        if (!is_numeric($id) || $id <= 0) {
            $this->errors['id'] = 'Invalid ID format';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate pagination parameters
     */
    public function validate_pagination($page, $per_page) {
        $this->errors = [];
        
        if (!is_numeric($page) || $page < 1) {
            $this->errors['page'] = 'Page must be a positive number';
        }
        
        if (!is_numeric($per_page) || $per_page < 1 || $per_page > 100) {
            $this->errors['per_page'] = 'Per page must be between 1 and 100';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Verify WordPress nonce
     */
    public function verify_nonce($nonce, $action = 'faq_nonce_action') {
        if (!wp_verify_nonce($nonce, $action)) {
            $this->errors['security'] = 'Security verification failed';
            return false;
        }
        return true;
    }
    
    /**
     * Get validation errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function get_first_error() {
        if (empty($this->errors)) {
            return null;
        }
        
        return reset($this->errors);
    }
}