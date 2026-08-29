<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="variantPickerModal" tabindex="-1" aria-labelledby="variantPickerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" id="variantPickerDialog">
        <div class="modal-content">

            <!-- Header -->
            <div class="vp-modal-banner">
                <div class="vp-banner-icon">
                    <i class="bx bx-layer"></i>
                </div>
                <div class="vp-banner-text">
                    <div class="vp-banner-title" id="variantPickerModalLabel"><?php echo t('lbl_select_variant', 'Select Variant'); ?></div>
                    <div class="vp-banner-product" id="variantPickerProductName"></div>
                </div>
                <button type="button" class="vp-banner-close" id="variantPickerClose" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>

            <!-- Table (header rendered by JS; body rows rendered by JS) -->
            <div class="vp-table-wrap">
                <table class="vp-table">
                    <thead id="variantPickerTableHead"></thead>
                    <tbody id="variantPickerList"></tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="vp-footer">
                <div class="vp-footer-info">
                    <span class="vp-sel-count" id="vpSelCount"></span>
                    <span class="vp-total-label"><?php echo t('lbl_total', 'Total'); ?></span>
                    <span class="vp-total-val" id="vpTotalAmount"></span>
                </div>
                <div class="vp-footer-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="variantPickerCancel">
                        <?php echo t('btn_cancel', 'Cancel'); ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="variantPickerConfirm" disabled>
                        <i class="bx bx-cart-add me-1"></i><?php echo t('btn_add_to_bill', 'Add to Bill'); ?>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
