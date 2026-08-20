<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="brandStockModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-grid-alt text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="modal-title mb-0 fw-bold">Variant Stock</h5>
                        <div class="bsm-product-name text-muted text-truncate" id="bsmProductName"></div>
                    </div>
                </div>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Summary strip -->
            <div class="bsm-summary px-4 py-3">
                <div class="d-flex align-items-end justify-content-between gap-3">
                    <div>
                        <div class="bsm-summary-label">Total Available</div>
                        <div class="bsm-total-avail" id="bsmTotalAvail">—</div>
                        <div class="bsm-unit-label" id="bsmUnit"></div>
                    </div>
                    <div class="text-end">
                        <div class="bsm-summary-label">Total Opening</div>
                        <div class="bsm-total-open" id="bsmTotalOpen">—</div>
                    </div>
                </div>
                <div class="bsm-total-bar-wrap mt-2">
                    <div class="bsm-total-bar" id="bsmTotalBar" style="--bsm-pct:0%"></div>
                </div>
            </div>

            <!-- Variant list -->
            <div class="modal-body p-0">
                <div id="bsmList" class="bsm-list">
                    <div class="bsm-loading">
                        <i class="bx bx-loader-alt bx-spin me-2"></i>Loading variant stock…
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
