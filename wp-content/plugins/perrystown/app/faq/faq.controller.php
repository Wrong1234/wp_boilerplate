<?php
namespace Perrystown\App\Faq;

if (!defined('ABSPATH')) exit;

class Faq_Controller {
    
    private $model;
    private $validator;
    
    public function __construct() {
        $this->model = new Faq_Model();
        $this->validator = new Faq_Validator();
    }
    
    /**
     * Get all FAQs
     */
    public function get_all(\WP_REST_Request $request) {
        try {
            $page = max(1, (int) $request->get_param('page') ?: 1);
            $per_page = max(1, min(100, (int) $request->get_param('per_page') ?: 10));
            $search = $request->get_param('search') ?: '';
            
            $offset = ($page - 1) * $per_page;
            
            $result = $this->model->get_all($page, $per_page, $search);
            
            return $this->success_response([
                'faqs' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'per_page' => $result['per_page'],
                    'total_pages' => $result['total_pages']
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->error_response(
                ['exception' => $e->getMessage()],
                'An error occurred',
                500
            );
        }
    }
    
    /**
     * Get single FAQ
     */
    public function get(\WP_REST_Request $request) {
        $id = $request->get_param('id');
        
        $faq = $this->model->get_by_id($id);
        
        if (!$faq) {
            return $this->error_response(
                ['id' => 'FAQ not found'],
                'Not found',
                404
            );
        }
        
        return $this->success_response(['faq' => $faq]);
    }
    
    /**
     * Create new FAQ
     */
    public function create(\WP_REST_Request $request) {
        try {
            $data = $request->get_json_params();
            
            // Verify nonce if present
            if (isset($data['_wpnonce'])) {
                if (!$this->validator->verify_nonce($data['_wpnonce'])) {
                    return $this->error_response(
                        $this->validator->get_errors(),
                        'Security validation failed',
                        403
                    );
                }
            }
            
            // Validate data
            if (!$this->validator->validate_create($data)) {
                return $this->error_response(
                    $this->validator->get_errors(),
                    'Validation failed',
                    422
                );
            }
            
            // Create FAQ
            $id = $this->model->create($data);
            
            if (!$id) {
                return $this->error_response(
                    ['database' => 'Failed to create FAQ'],
                    'Database error',
                    500
                );
            }
            
            // Get created FAQ
            $faq = $this->model->get_by_id($id);
            
            return $this->success_response([
                'faq' => $faq,
                'message' => 'FAQ created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            return $this->error_response(
                ['exception' => $e->getMessage()],
                'An error occurred',
                500
            );
        }
    }
    
    /**
     * Update FAQ
     */
    public function update(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            $data = $request->get_json_params();
            
            // Check if FAQ exists
            $faq = $this->model->get_by_id($id);
            if (!$faq) {
                return $this->error_response(
                    ['id' => 'FAQ not found'],
                    'Not found',
                    404
                );
            }
            
            // Verify nonce if present
            if (isset($data['_wpnonce'])) {
                if (!$this->validator->verify_nonce($data['_wpnonce'])) {
                    return $this->error_response(
                        $this->validator->get_errors(),
                        'Security validation failed',
                        403
                    );
                }
            }
            
            // Validate data
            if (!$this->validator->validate_update($data)) {
                return $this->error_response(
                    $this->validator->get_errors(),
                    'Validation failed',
                    422
                );
            }
            
            // Update FAQ
            $result = $this->model->update($id, $data);
            
            if ($result === false) {
                return $this->error_response(
                    ['database' => 'Failed to update FAQ'],
                    'Database error',
                    500
                );
            }
            
            // Get updated FAQ
            $updated_faq = $this->model->get_by_id($id);
            
            return $this->success_response([
                'faq' => $updated_faq,
                'message' => 'FAQ updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->error_response(
                ['exception' => $e->getMessage()],
                'An error occurred',
                500
            );
        }
    }
    
    /**
     * Delete FAQ
     */
    public function delete(\WP_REST_Request $request) {
        $id = $request->get_param('id');
        
        // Check if FAQ exists
        $faq = $this->model->get_by_id($id);
        if (!$faq) {
            return $this->error_response(
                ['id' => 'FAQ not found'],
                'Not found',
                404
            );
        }
        
        $result = $this->model->delete($id);
        
        if ($result === false) {
            return $this->error_response(
                ['database' => 'Failed to delete FAQ'],
                'Database error',
                500
            );
        }
        
        return $this->success_response([
            'message' => 'FAQ deleted successfully'
        ]);
    }
    
    /**
     * Success response helper
     */
    private function success_response($data, $status = 200) {
        return new \WP_REST_Response([
            'success' => true,
            'data' => $data
        ], $status);
    }
    
    /**
     * Error response helper
     */
    private function error_response($errors, $message, $status = 400) {
        return new \WP_REST_Response([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}