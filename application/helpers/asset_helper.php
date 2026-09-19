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

/**
 * Resolve a user profile image to a full URL.
 *
 * - External URLs (http/https) are returned as-is — e.g. Google OAuth profile pics.
 * - Relative paths get the CDN base prepended — e.g. manually uploaded images.
 *
 * @param string|null $image  Raw value from UsersTbl.Image column
 * @returns string            Absolute URL, or empty string when no image
 */
if (!function_exists('avatarUrl')) {
    function avatarUrl(?string $image): string {
        if (empty($image)) return '';
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }
        $cdn = getenv('FILE_UPLOAD') === 'amazonaws'
            ? getenv('CDN_URL')
            : getenv('CFLARE_R2_CDN');
        return rtrim($cdn ?: '', '/') . '/' . ltrim($image, '/');
    }
}
