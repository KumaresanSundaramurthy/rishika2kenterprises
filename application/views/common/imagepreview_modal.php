<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="" id="imagePreviewTarget" class="img-fluid rounded shadow" style="max-height: 90vh; width: auto; border: 3px solid #fff;">
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .preview-image:hover { opacity: 0.8; transition: 0.3s; }
</style>