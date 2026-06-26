<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Render a party name element with the global hover-card trigger.
 * The popup JS/CSS is defined globally in footer_script.php + apex-theme.css.
 * Call this once per list row wherever a customer/vendor name is displayed.
 *
 * @param string $name    Full party name
 * @param string $mobile  Mobile number (digits only, no country code)
 * @param string $code    Country dial code e.g. "+91"
 * @param string $area    Area / location string (optional)
 * @param string $img     CDN URL of party photo (optional)
 */
if (!function_exists('r2k_party_name')) {
    function r2k_party_name(string $name, string $mobile = '', string $code = '', string $area = '', string $img = ''): string {
        $attr  = ' class="chc-trigger" style="cursor:default;"';
        $attr .= ' data-name="'   . htmlspecialchars($name,   ENT_QUOTES) . '"';
        $attr .= ' data-mobile="' . htmlspecialchars($mobile, ENT_QUOTES) . '"';
        $attr .= ' data-code="'   . htmlspecialchars($code,   ENT_QUOTES) . '"';
        if ($area)  $attr .= ' data-area="'  . htmlspecialchars($area,  ENT_QUOTES) . '"';
        if ($img)   $attr .= ' data-img="'   . htmlspecialchars($img,   ENT_QUOTES) . '"';
        return '<span' . $attr . '>' . htmlspecialchars($name, ENT_QUOTES) . '</span>';
    }
}

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
    function format_datedisplay($getDate, $format = null, $default = '', $timezone = null, $adjustDays = 0) {
        // When no format supplied, read ListDateFormat from JWT TransSettings via CI instance
        if ($format === null) {
            try {
                $CI     = &get_instance();
                $format = $CI->pageData['JwtData']->GenSettings->ListDateFormat ?? 'd-m-Y';
            } catch (Exception $_) {
                $format = 'd-m-Y';
            }
        }
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