<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="stockOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content">

            <div class="vtm-banner" style="--vtm-color:#dc3545;--vtm-bg:#fee2e2;--vtm-icon-bg:rgba(220,53,69,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-minus-circle"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">Stock Out</div>
                            <div id="soProductName" class="vtm-doc-meta"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body">
                <input type="hidden" id="soProductUID">

                <!-- Quantity info -->
                <div class="p-3 rounded mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="fw-semibold mb-2" style="font-size:.8rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;">
                        Quantity Information
                    </div>
                    <div class="row g-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">
                                <span class="text-danger me-1">*</span>Quantity
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="soQty" min="0.001" step="0.001"
                                       placeholder="0" oninput="invCalcStockValue('out')">
                                <span class="input-group-text" id="soUnitLabel" style="min-width:50px;justify-content:center;">PCS</span>
                            </div>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Record Date</label>
                            <input type="text" class="form-control form-control-sm" id="soRecordDate"
                                   placeholder="Select date" readonly>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Category (Reason)</label>
                        <select class="form-select form-select-sm" id="soCategory">
                            <option value="Miscellaneous" selected>Miscellaneous</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Expired">Expired</option>
                            <option value="Lost">Lost</option>
                            <option value="Recalled">Recalled</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <textarea class="form-control form-control-sm" id="soNotes" rows="2"
                                  placeholder="Add notes to help remember details..."></textarea>
                    </div>
                </div>

                <!-- Price details (optional) -->
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold" style="font-size:.8rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;">
                            Price Details
                        </div>
                        <span class="badge text-bg-secondary" style="font-size:.65rem;">OPTIONAL</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-5">
                            <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Price</label>
                            <input type="number" class="form-control form-control-sm" id="soPrice" min="0" step="0.01"
                                   placeholder="0.00" oninput="invCalcStockValue('out')">
                            <div class="text-muted mt-1" style="font-size:.68rem;">Includes tax</div>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Price Type</label>
                            <select class="form-select form-select-sm" id="soPriceType" onchange="invCalcStockValue('out')">
                                <option value="SellingPrice" selected>Selling Price</option>
                                <option value="PurchasePrice">Purchase Price</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Stock Out Value</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="soStockValue"
                                   readonly placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="justify-content:space-between;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="soSubmitBtn" style="min-width:140px;">
                    <i class="bx bx-minus me-1"></i>Remove Quantity
                </button>
            </div>

        </div>
    </div>
</div>
