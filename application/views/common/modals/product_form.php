<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="ProductFormModal" tabindex="-1" aria-labelledby="ProductFormModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">
            <?php $this->load->view('common/modals/product_form_body'); ?>
        </div>
    </div>
</div>
