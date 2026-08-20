<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="brandPickerModal" tabindex="-1" aria-labelledby="brandPickerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" id="brandPickerModalLabel">
                        <i class="bx bx-purchase-tag-alt me-1"></i>
                        <?php echo t('lbl_select_brand', 'Select Brand'); ?>
                    </h5>
                    <p class="bp-product-name mb-0" id="brandPickerProductName"></p>
                </div>
                <button type="button" class="btn-close" id="brandPickerClose" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" id="brandPickerSearch" class="form-control"
                           placeholder="<?php echo t('lbl_search_brands', 'Search brands…'); ?>" autocomplete="off">
                    <span class="input-group-text text-muted small" id="brandPickerCount"></span>
                </div>
                <div id="brandPickerList" class="bp-list"></div>
            </div>
            <div class="modal-footer py-2">
                <div class="me-auto" id="brandPickerSelectedLabel"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="brandPickerCancel">
                    <?php echo t('btn_cancel', 'Cancel'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="brandPickerConfirm" disabled>
                    <i class="bx bx-check me-1"></i><?php echo t('btn_select_brand', 'Select Brand'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
