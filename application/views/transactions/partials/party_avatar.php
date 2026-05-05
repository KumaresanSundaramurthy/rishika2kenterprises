<?php
/**
 * Renders a party avatar inline.
 * Call: partyAvatar($list->PartyName, $list->PartyImage ?? null, $cdnUrl)
 * Image click uses the existing .preview-image / #imagePreviewModal handler in default.js
 */
if (!function_exists('partyAvatar')) {
    function partyAvatar($name, $image, $cdnUrl) {
        $name    = (string)($name ?? '');
        $imgSrc  = !empty($image) ? rtrim($cdnUrl, '/') . '/' . ltrim($image, '/') : null;
        $words    = preg_split('/\s+/', trim($name));
        $initials = strtoupper(substr($words[0] ?? '', 0, 1));
        if (!empty($words[1])) $initials .= strtoupper(substr($words[1], 0, 1));
        if (!$initials) $initials = strtoupper(substr($name, 0, 1));

        if ($imgSrc) {
            echo '<div class="avatar avatar-sm flex-shrink-0">'
               . '<img src="' . htmlspecialchars($imgSrc) . '"'
               . ' alt="' . htmlspecialchars($name) . '"'
               . ' class="rounded-circle cursor-pointer preview-image"'
               . ' data-src="' . htmlspecialchars($imgSrc) . '"'
               . ' style="width:32px;height:32px;object-fit:cover;" />'
               . '</div>';
        } else {
            echo '<div class="avatar avatar-sm flex-shrink-0">'
               . '<span class="avatar-initial rounded-circle bg-label-primary" style="font-size:.72rem;">'
               . htmlspecialchars($initials ?: '?')
               . '</span>'
               . '</div>';
        }
    }
}
