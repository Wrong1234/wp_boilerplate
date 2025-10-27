<?php
/**
 * Contact Routes 
 */
use Perrystown\App\Contact\Contact_Controller;
use Perrystown\App\Contact\Validation\Validator;

if ( ! defined('ABSPATH') ) exit;

require_once __DIR__ . '/contact_validation.php';

function perrystown_register_contact_routes() {
    $ns = 'perrystown/v1';

    // List
    register_rest_route($ns, '/contacts', [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => Validator::wrap([Contact_Controller::class, 'index'], 'index'),
        'permission_callback' => '__return_true',
    ]);

    // Create
    register_rest_route($ns, '/contacts', [
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => Validator::wrap([Contact_Controller::class, 'store'], 'store'),
        'permission_callback' => '__return_true',
    ]);

    // Show
    register_rest_route($ns, '/contacts/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => Validator::wrap([Contact_Controller::class, 'show'], 'show'),
        'permission_callback' => '__return_true',
    ]);

    // Update (PUT/PATCH)
    register_rest_route($ns, '/contacts/(?P<id>\d+)', [
        'methods'             => [ 'PUT', 'PATCH' ],
        'callback'            => Validator::wrap([Contact_Controller::class, 'update'], 'update'),
        'permission_callback' => '__return_true',
    ]);

    // Delete
    register_rest_route($ns, '/contacts/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::DELETABLE,
        'callback'            => Validator::wrap([Contact_Controller::class, 'destroy'], 'destroy'),
        'permission_callback' => '__return_true',
    ]);
}

add_action('rest_api_init', 'perrystown_register_contact_routes');
