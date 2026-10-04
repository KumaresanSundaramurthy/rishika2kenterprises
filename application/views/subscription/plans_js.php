<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
/* Shadow on modal header when the body is scrolled */
$('#planChangeModal').on('shown.bs.modal', function() {
    var $body   = $(this).find('.modal-body');
    var $header = $('#pcm-header');
    if (!$body.length || !$header.length) return;
    $body.on('scroll', function() {
        $header.css('box-shadow', $(this).scrollTop() > 4 ? '0 4px 12px rgba(0,0,0,.10)' : 'none');
    });
});
</script>
