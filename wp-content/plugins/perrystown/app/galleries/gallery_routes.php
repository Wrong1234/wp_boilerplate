<?php

use Perrystown\App\Gallery\Gallery_Controller;
use Perrystown\App\Gallery\Validation\Validator;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/gallery_validation.php';

function perrystown_register_gallery_routes()
{
    $ns = 'perrystown/v1';

    // List
    register_rest_route($ns, '/galleries', [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => Validator::wrap([Gallery_Controller::class, 'index'], 'index'),
        'permission_callback' => '__return_true',
    ]);

    // Create (logged-in users only)
    register_rest_route($ns, '/galleries', [
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => Validator::wrap([Gallery_Controller::class, 'store'], 'store'),
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    // Show
    register_rest_route($ns, '/galleries/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => Validator::wrap([Gallery_Controller::class, 'show'], 'show'),
        'permission_callback' => function () {
            return is_user_logged_in();
        }, // if you want public, change to __return_true
    ]);

    // Update (PUT/PATCH/POST; POST allows multipart file upload)
    register_rest_route($ns, '/galleries/(?P<id>\d+)', [
        'methods'             => ['PUT', 'PATCH', 'POST'], // POST allows file upload
        'callback'            => Validator::wrap([Gallery_Controller::class, 'update'], 'update'),
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);


    // Delete
    register_rest_route($ns, '/galleries/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::DELETABLE,
        'callback'            => Validator::wrap([Gallery_Controller::class, 'destroy'], 'destroy'),
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);
}
add_action('rest_api_init', 'perrystown_register_gallery_routes');
