<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

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