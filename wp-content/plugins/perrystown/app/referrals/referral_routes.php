<?php
use Perrystown\App\Referral\Referral_Controller;
use Perrystown\App\Referral\Validation\Validator;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/referral_validation.php';

function perrystown_register_referral_routes() {
    $ns = 'perrystown/v1';

    // Public create
    register_rest_route($ns, '/referrals', [
        'methods'             => \WP_REST_Server::CREATABLE, // POST
        'callback'            => Validator::wrap([Referral_Controller::class, 'store'], 'store'),
        'permission_callback' => '__return_true',
    ]);

    // Protected list
    register_rest_route($ns, '/referrals', [
        'methods'             => \WP_REST_Server::READABLE, // GET
        'callback'            => Validator::wrap([Referral_Controller::class, 'index'], 'index'),
        'permission_callback' => function () { return is_user_logged_in(); },
    ]);

    // Protected show
    register_rest_route($ns, '/referrals/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::READABLE, // GET
        'callback'            => Validator::wrap([Referral_Controller::class, 'show'], 'show'),
        'permission_callback' => function () { return is_user_logged_in(); },
    ]);

    // Protected update (allow POST for multipart file replace)
    register_rest_route($ns, '/referrals/(?P<id>\d+)', [
        'methods'             => ['PUT','PATCH','POST'],
        'callback'            => Validator::wrap([Referral_Controller::class, 'update'], 'update'),
        'permission_callback' => function () { return is_user_logged_in(); },
    ]);

    // Protected delete
    register_rest_route($ns, '/referrals/(?P<id>\d+)', [
        'methods'             => \WP_REST_Server::DELETABLE, // DELETE
        'callback'            => Validator::wrap([Referral_Controller::class, 'destroy'], 'destroy'),
        'permission_callback' => function () { return is_user_logged_in(); },
    ]);
}
add_action('rest_api_init', 'perrystown_register_referral_routes');
