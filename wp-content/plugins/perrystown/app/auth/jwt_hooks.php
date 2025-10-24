<?php
if (!defined('ABSPATH')) exit;

/**
 * Helper: get Bearer token from headers.
 */
function perrystown_get_bearer_token(): ?string {
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $k) {
        if (!empty($_SERVER[$k]) && preg_match('/Bearer\s+(.+)/i', $_SERVER[$k], $m)) {
            return trim($m[1]);
        }
    }
    return null;
}

/**
 * Helper: decode JWT payload (base64url) → assoc array.
 */
function perrystown_decode_jwt_payload(?string $jwt): ?array {
    if (!$jwt) return null;
    $parts = explode('.', $jwt);
    if (count($parts) < 2) return null;
    $json = base64_decode(strtr($parts[1], '-_', '+/'));
    $arr  = json_decode($json, true);
    return is_array($arr) ? $arr : null;
}

/**
 * PUBLIC helper for routes:
 * Returns true only if a Bearer token exists AND its iat >= user's latest iat.
 * Use in permission_callback: perrystown_jwt_is_fresh() && current_user_can('manage_options')
 */
function perrystown_jwt_is_fresh(): bool {
    $jwt = perrystown_get_bearer_token();
    if (!$jwt) return false;

    $payload = perrystown_decode_jwt_payload($jwt);
    if (!$payload) return false;

    // Extract issued-at and user id (cover common plugin payload shapes)
    $iat = (int) ($payload['iat'] ?? 0);
    $uid =
        (int) ($payload['data']['user']['id'] ?? 0) ?:
        (int) ($payload['user']['id'] ?? 0) ?:
        (int) ($payload['user_id'] ?? 0) ?:
        (int) ($payload['sub'] ?? 0);

    if ($iat <= 0 || $uid <= 0) return false;

    $threshold = (int) get_user_meta($uid, 'jwt_invalid_before', true);
    return ($threshold === 0) || ($iat >= $threshold);
}

/**
 * (1) Set JWT lifespan to 1 day (affects NEW tokens only).
 */
add_filter('jwt_auth_expire', function ($exp, $user) {
    return time() + DAY_IN_SECONDS; // 24h
}, 10, 2);

/**
 * (2) When issuing a NEW token, remember its iat.
 *     Any token with smaller iat is considered revoked.
 */
add_filter('jwt_auth_token_before_dispatch', function ($data, $user) {
    if (!empty($data['iat'])) {
        update_user_meta($user->ID, 'jwt_invalid_before', (int) $data['iat']);
    }
    return $data;
}, 10, 2);


// add_filter('jwt_auth_token_before_dispatch', function ($data, $user) {
//     $u = get_userdata($user->ID);
//     if ($u && is_array($u->roles)) {
//         $data['roles'] = $u->roles; 
//     }
//     return $data;
// }, 20, 2);