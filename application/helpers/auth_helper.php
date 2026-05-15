<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Returns TRUE if the current request carries a valid, non-expired JWT session.
 * Safe to call from any controller or hook — uses get_instance() internally.
 */
function is_authenticated() {
    $CI       =& get_instance();
    $cookieName = getenv('JWT_COOKIE_NAME');
    $encoded    = get_cookie($cookieName);

    if (empty($encoded)) {
        return false;
    }

    try {
        $jwt = JWT::decode($encoded, new Key(getenv('JWT_KEY'), 'HS256'));
        if (empty($jwt->key)) {
            return false;
        }
        $redis = $CI->redisservice->getCache($jwt->key);
        return $redis->Error === false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Redirects to the login page if the user is not authenticated.
 * Use at the top of any controller method that needs auth without the Middleware hook.
 */
function require_auth($redirectTo = 'portal') {
    if (!is_authenticated()) {
        redirect($redirectTo, 'refresh');
    }
}
