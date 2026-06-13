<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Purchase Bill Items Selection Modal (for Purchase Return) -->
<div class="modal fade" id="purchaseItemsModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <!-- header -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#696cff;--vtm-bg:#f0f0ff;--vtm-icon-bg:rgba(105,108,255,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-undo" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);" id="purchItemsModalTitle">Select Return Items</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;" id="purchItemsModalSubtitle">Choose items to return from this purchase bill</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0">
                <!-- Loading -->
                <div id="purchItemsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>

                <!-- Items table -->
                <div id="purchItemsTableWrap" class="d-none">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px;">
                                    <input type="checkbox" id="purchItemsSelectAll" class="form-check-input" title="Select All">
                                </th>
                                <th>Product</th>
                                <th class="text-center" style="width:110px;">Returnable Qty</th>
                                <th class="text-end" style="width:110px;">Unit Price</th>
                                <th class="text-end" style="width:110px;">Tax</th>
                                <th class="text-end" style="width:120px;">Discount</th>
                                <th class="text-end" style="width:120px;">Row Total</th>
                            </tr>
                        </thead>
                        <tbody id="purchItemsTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-top px-3 py-2 d-flex align-items-center justify-content-between flex-shrink-0" style="background:#f0f0ff;">
                <small class="text-muted"><span id="purchItemsSelectedCount">0</span> item(s) selected</small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary px-3" id="purchItemsAddToCart" disabled>
                        <i class="bx bx-cart-add me-1"></i>Add to Return
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
