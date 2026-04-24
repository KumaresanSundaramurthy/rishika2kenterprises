<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('smart_dec_amount')) {
    /**
     * Format a decimal amount with 2 decimal places, or 3 if the 3rd decimal digit is non-zero.
     * E.g. 118.985 → "118.985", 118.500 → "118.50", 118.000 → "118.00"
     */
    function smart_dec_amount($value) {
        if ($value === null || $value === '') return '0.00';
        $str    = rtrim(number_format((float)$value, 3, '.', ''), '');
        $dotPos = strpos($str, '.');
        if ($dotPos !== false && strlen($str) >= $dotPos + 4 && $str[$dotPos + 3] !== '0') {
            return number_format((float)$value, 3, '.', '');
        }
        return number_format((float)$value, 2, '.', '');
    }
}

if (!function_exists('format_datedisplay')) {
    function format_datedisplay($getDate, $format = 'd-m-Y', $default = '', $timezone = null, $adjustDays = 0) {
        if (empty($getDate)) {
            return $default;
        }
        try {
            if (is_numeric($getDate)) {
                $dt = new DateTime('@' . $getDate);
            } else {
                $dt = new DateTime($getDate);
            }
            if ($timezone) {
                $dt->setTimezone(new DateTimeZone($timezone));
            }
            if (!empty($adjustDays) && is_numeric($adjustDays)) {
                $dt->modify(($adjustDays >= 0 ? '+' : '') . $adjustDays . ' days');
            }
            return $dt->format($format);
        } catch (Exception $e) {
            return $default;
        }
    }
}