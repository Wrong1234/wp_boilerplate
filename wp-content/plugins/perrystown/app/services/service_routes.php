<?php

/**
 * Service Routes — clean (validation in service_validation.php)
 */

use Perrystown\App\Service\Service_Controller;
use Perrystown\App\Service\Validation\Validator;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/service_validation.php';

function perrystown_register_service_routes()
{
    $ns = 'perrystown/v1';

    // List
    register_rest_route($ns, '/services', [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => Validator::wrap([Service_Controller::class, 'index'], 'index'),
        'permission_callback' => '__return_true',
    ]);

    // Create (name + image required via file OR URL)
    register_rest_route($ns, '/services', [
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => Validator::wrap([Service_Controller::class, 'store'], 'store'),
        'permission_callback' => function (\WP_REST_Request $request) {
            return is_user_logged_in();
        },

    ]);

    // Show
    register_rest_route($ns, '/services/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => Validator::wrap([Service_Controller::class, 'show'], 'show'),
        'permission_callback' => '__return_true',
    ]);

    // Update (partial)
    register_rest_route($ns, '/services/(?P<id>\d+)', [
        'methods'             => ['PUT', 'PATCH', 'POST'],
        'callback'            => Validator::wrap([Service_Controller::class, 'update'], 'update'),
        'permission_callback' => '__return_true',
    ]);

    // Delete
    register_rest_route($ns, '/services/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::DELETABLE,
        'callback'            => Validator::wrap([Service_Controller::class, 'destroy'], 'destroy'),
        'permission_callback' => '__return_true',
    ]);
}

add_action('rest_api_init', 'perrystown_register_service_routes');
