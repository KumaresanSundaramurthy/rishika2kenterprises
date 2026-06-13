<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Returns an asset path with a ?v= cache-busting timestamp.
 * Uses filemtime() so the version auto-updates on every deploy without manual bumping.
 *
 * Usage: _assetV('/css/apex-theme.css')
 *        → /css/apex-theme.css?v=1749823412
 */
if (!function_exists('_assetV')) {
    function _assetV($path) {
        $full = $_SERVER['DOCUMENT_ROOT'] . $path;
        $ts   = @filemtime($full) ?: '1';
        return $path . '?v=' . $ts;
    }
}
