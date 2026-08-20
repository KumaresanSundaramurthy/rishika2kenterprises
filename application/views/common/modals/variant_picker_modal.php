<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="variantPickerModal" tabindex="-1" aria-labelledby="variantPickerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" id="variantPickerModalLabel">
                        <i class="bx bx-layer me-1"></i>
                        <?php echo t('lbl_select_variant', 'Select Variant'); ?>
                    </h5>
                    <p class="vp-product-name mb-0" id="variantPickerProductName"></p>
                </div>
                <button type="button" class="btn-close" id="variantPickerClose" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="vp-list" id="variantPickerList"></div>
            </div>
            <div class="modal-footer py-2">
                <div class="me-auto" id="variantPickerSelectedLabel"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="variantPickerCancel">
                    <?php echo t('btn_cancel', 'Cancel'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="variantPickerConfirm" disabled>
                    <i class="bx bx-check me-1"></i><?php echo t('btn_select_variant', 'Select Variant'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
