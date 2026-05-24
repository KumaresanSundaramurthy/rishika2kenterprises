<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content">

            <div class="modal-header" style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:34px;height:34px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-plus-circle text-success fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-success">Stock In</h6>
                        <div id="siProductName" class="text-muted" style="font-size:.75rem;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="siProductUID">

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
                                <input type="number" class="form-control" id="siQty" min="0.001" step="0.001"
                                       placeholder="0" oninput="invCalcStockValue('in')">
                                <span class="input-group-text" id="siUnitLabel" style="min-width:50px;justify-content:center;">PCS</span>
                            </div>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Record Date</label>
                            <input type="date" class="form-control form-control-sm" id="siRecordDate"
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Category (Reason)</label>
                        <select class="form-select form-select-sm" id="siCategory">
                            <option value="New">New</option>
                            <option value="Return">Return</option>
                            <option value="Miscellaneous" selected>Miscellaneous</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <textarea class="form-control form-control-sm" id="siNotes" rows="2"
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
                    <div class="row g-3 align-items-end">
                        <div class="col-5">
                            <label class="form-label mb-1" style="font-size:.8rem;">Price</label>
                            <input type="number" class="form-control form-control-sm" id="siPrice" min="0" step="0.01"
                                   placeholder="0.00" oninput="invCalcStockValue('in')">
                            <div class="text-muted mt-1" style="font-size:.68rem;">Includes tax</div>
                        </div>
                        <div class="col-4">
                            <select class="form-select form-select-sm" id="siPriceType" onchange="invCalcStockValue('in')">
                                <option value="PurchasePrice" selected>Purchase Price</option>
                                <option value="SellingPrice">Selling Price</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-1" style="font-size:.8rem;">Stock In Value</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="siStockValue"
                                   readonly placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="justify-content:space-between;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="siSubmitBtn" style="min-width:130px;">
                    <i class="bx bx-plus me-1"></i>Add Quantity
                </button>
            </div>

        </div>
    </div>
</div>
