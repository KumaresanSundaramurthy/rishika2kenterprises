<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Returns the current Indian financial year (Apr–Mar cycle).
 *
 * @param string $format  'long'  → '2026-27'  (for DB storage, display, filtering)
 *                        'code'  → '2627'      (for invoice number prefix R2K-2627-001)
 * @return string
 */
if (!function_exists('billing_fy')) {
    function billing_fy(string $format = 'long'): string {
        $month   = (int)date('n');
        $year    = (int)date('Y');
        $fyStart = $month >= 4 ? $year : $year - 1;
        $fyEnd   = $fyStart + 1;

        if ($format === 'code') {
            return substr((string)$fyStart, 2, 2) . substr((string)$fyEnd, 2, 2);
        }
        return $fyStart . '-' . substr((string)$fyEnd, 2, 2);
    }
}
