<?php
/**
 * Shared Add / Edit Additional Charge Modal
 * Opened by js/common/additional_charge_form.js via $(document).trigger('acFormOpen', [opts])
 * Saved by js/common/additional_charge_form.js — fires $(document).trigger('acFormSaved', [resp]) on success
 */
defined('BASEPATH') or exit('No direct script access allowed');
?>

<!-- ================================================================
     Add / Edit Additional Charge Modal  (shared partial)
================================================================ -->
<div class="modal fade" id="additionalChargeModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header p-0">
                <div style="width:100%;padding:14px 20px;border-left:4px solid #0d6efd;background:#e7f1ff;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="background:rgba(13,110,253,.13);border-radius:10px;padding:9px 11px;flex-shrink:0;">
                                <i class="bx bx-receipt" style="font-size:1.7rem;color:#0d6efd;display:block;"></i>
                            </div>
                            <div>
                                <div id="acModalTitle" style="font-size:1.12rem;font-weight:800;color:#0d6efd;letter-spacing:.2px;line-height:1.2;">Additional Charge</div>
                                <div id="acModalSubtitle" style="font-size:.77rem;color:#6c757d;margin-top:4px;">Define a charge type for use in transactions</div>
                            </div>
                        </div>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"
                            style="background:rgba(255,255,255,.85);border:none;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.15);padding:0;flex-shrink:0;">
                            <i class="bx bx-x" style="font-size:1.2rem;color:#555;line-height:1;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body">
                <input type="hidden" id="acChargeUID" value="0">
                <input type="hidden" id="acIsSystem"  value="0">

                <!-- Name + Display Name -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label for="acName" class="form-label fw-semibold">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="acName"
                            placeholder="e.g. Express_Delivery" maxlength="100"
                            oninput="this.value=this.value.replace(/[^A-Za-z0-9_]/g,'')" />
                        <div class="form-text">Internal key — alphanumeric &amp; underscores only.</div>
                    </div>
                    <div class="col-sm-7">
                        <label for="acDisplayName" class="form-label fw-semibold">
                            Display Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="acDisplayName"
                            placeholder="e.g. Express Delivery Charge" maxlength="150" />
                        <div class="form-text">Shown to users in transaction forms.</div>
                    </div>
                </div>

                <!-- Default Tax -->
                <div class="mb-3">
                    <label for="acDefaultTaxUID" class="form-label fw-semibold">Default Tax</label>
                    <select class="form-select" id="acDefaultTaxUID">
                        <option value="">— No default tax —</option>
                    </select>
                    <div class="form-text">Pre-selected when this charge is added to a transaction. User can override.</div>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="acDescription" class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" id="acDescription" rows="2"
                        placeholder="Optional — internal note about this charge"></textarea>
                </div>

                <!-- Show By Default + Sort Order (hidden for system charges) -->
                <div id="acUserFieldsRow">
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <div class="d-flex justify-content-between align-items-center p-2 rounded border">
                                <div>
                                    <div class="fw-semibold small">Show by default in transactions</div>
                                    <div class="text-muted" style="font-size:.73rem;">Automatically add this row when creating a new transaction.</div>
                                </div>
                                <div class="form-check form-switch ms-3 mb-0">
                                    <input class="form-check-input" type="checkbox" id="acShowByDefault" role="switch">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <label for="acSortOrder" class="form-label fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="acSortOrder"
                                min="0" max="999" value="0" placeholder="0" />
                            <div class="form-text">Lower = shown first.</div>
                        </div>
                    </div>
                </div>

                <!-- System charge notice (shown instead of user fields) -->
                <div id="acSystemNotice" class="d-none">
                    <div class="alert alert-info py-2 mb-0" style="font-size:.8rem;">
                        <i class="bx bx-lock-alt me-1"></i>
                        This is a <strong>system charge</strong>. Only Description and Default Tax can be changed.
                    </div>
                </div>

            </div><!-- /modal-body -->

            <div class="modal-footer py-3" style="background:#f8f9fa;border-top:1px solid #dee2e6;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAdditionalChargeBtn">
                    <i class="bx bx-save me-1"></i>Save Charge
                </button>
            </div>

        </div>
    </div>
</div>
<!-- / Additional Charge Form Modal -->
