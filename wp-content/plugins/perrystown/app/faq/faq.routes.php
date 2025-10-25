<?php
namespace Perrystown\App\Faq;

if (!defined('ABSPATH')) exit;

class Faq_Routes {
    
    private $controller;
    private $namespace = '/wp/v2';
    private $base = 'faqs';
    
    public function __construct() {
        $this->controller = new Faq_Controller();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // GET /faqs - Get all FAQs
        register_rest_route($this->namespace, '/' . $this->base, [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this->controller, 'get_all'],
            'permission_callback' => '__return_true',
            'args' => [
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ],
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                ],
                'search' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // GET /faqs/{id} - Get single FAQ
        register_rest_route($this->namespace, '/' . $this->base . '/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this->controller, 'get'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // POST /faqs - Create FAQ
        register_rest_route('/wp/v2', '/faqs', [
            'methods' => 'POST',
            'callback' => [$this->controller, 'create'],
            // 'permission_callback' => [$this, 'create_permission'],
              'permission_callback' => '__return_true',
            'args' => [
                'question' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'answer' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field'
                ]
            ]
        ]);
        
        // PUT/PATCH /faqs/{id} - Update FAQ
        register_rest_route($this->namespace, '/' . $this->base . '/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => [$this->controller, 'update'],
            // 'permission_callback' => [$this, 'update_permission'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'question' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'answer' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field'
                ]
            ]
        ]);
        
        // DELETE /faqs/{id} - Delete FAQ
        register_rest_route($this->namespace, '/' . $this->base . '/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::DELETABLE,
            'callback' => [$this->controller, 'delete'],
            // 'permission_callback' => [$this, 'delete_permission'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Permission callback for CREATE requests
     */
    public function create_permission() {
        return current_user_can('edit_posts');
    }
    
    /**
     * Permission callback for UPDATE requests
     */
    public function update_permission() {
        return current_user_can('edit_posts');
    }
    
    /**
     * Permission callback for DELETE requests
     */
    public function delete_permission() {
        return current_user_can('delete_posts');
    }
}