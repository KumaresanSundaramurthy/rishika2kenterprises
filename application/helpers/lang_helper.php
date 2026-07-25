<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('t')) {
    /**
     * @param string $key
     * @param string $fallback
     * @return string
     */
    function t(string $key, string $fallback = ''): string {
        $CI   = &get_instance();
        $line = $CI->lang->line($key);
        if ($line !== FALSE && $line !== '') {
            return $line;
        }
        return $fallback !== '' ? $fallback : $key;
    }
}
