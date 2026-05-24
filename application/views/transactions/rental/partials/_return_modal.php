<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="rntReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
        <div class="modal-content">

            <div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:34px;height:34px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-undo text-primary fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">Record Machine Return</h6>
                        <div id="rtnItemLabel" class="text-muted" style="font-size:.75rem;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3">

                <input type="hidden" id="rtnRentalUID">
                <input type="hidden" id="rtnRentalItemUID">

                <!-- Booking info banner -->
                <div id="rtnInfoBanner" class="alert alert-light border py-2 px-3 mb-3" style="font-size:.82rem;">
                    <div class="row g-1">
                        <div class="col-6"><span class="text-muted">Rental #: </span><strong id="rtnRentalNum">—</strong></div>
                        <div class="col-6"><span class="text-muted">Type: </span><strong id="rtnRentalType">—</strong></div>
                        <div class="col-6"><span class="text-muted">Booked: </span><span id="rtnStartDate">—</span></div>
                        <div class="col-6"><span class="text-muted">Due: </span><span id="rtnDueDate" style="color:#dc2626;">—</span></div>
                    </div>
                </div>

                <!-- Return details -->
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Qty Returned <span class="text-danger">*</span></label>
                        <input type="number" id="rtnReturnedQty" class="form-control form-control-sm" value="1" min="1" step="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Qty Damaged (if any)</label>
                        <input type="number" id="rtnDamagedQty" class="form-control form-control-sm" value="0" min="0" step="1">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label form-label-sm">Actual Return Date & Time <span class="text-danger">*</span></label>
                    <input type="text" id="rtnActualReturnDateTime" class="form-control form-control-sm" placeholder="Select actual return date/time" readonly>
                </div>

                <div class="mb-2">
                    <label class="form-label form-label-sm">Actual Hours Used</label>
                    <input type="number" id="rtnActualHours" class="form-control form-control-sm" value="0" min="0" step="0.5" placeholder="e.g. 4.5">
                    <div class="text-muted" style="font-size:.7rem;margin-top:3px;">Auto-calculated when return time is set</div>
                </div>

                <!-- Extra Charges -->
                <div class="card mt-3 mb-2" style="border-color:#fee2e2;">
                    <div class="card-header py-2" style="background:#fff8f8;border-bottom-color:#fee2e2;">
                        <h6 class="mb-0" style="font-size:.82rem;color:#dc2626;"><i class="bx bx-calculator me-1"></i>Extra Charges (if any)</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label form-label-sm">Extra Hour Charge</label>
                                <input type="number" id="rtnExtraHourCharge" class="form-control form-control-sm rtnChargeInput" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label form-label-sm">Late Return Charge</label>
                                <input type="number" id="rtnLateCharge" class="form-control form-control-sm rtnChargeInput" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label form-label-sm">Damage Charge</label>
                                <input type="number" id="rtnDamageCharge" class="form-control form-control-sm rtnChargeInput" value="0" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="d-flex justify-content-between align-items-center p-2 rounded mb-2" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <span class="text-muted" style="font-size:.82rem;">Total Extra Charges</span>
                    <span id="rtnTotalExtraCharges" style="font-weight:700;color:#dc2626;font-size:.9rem;">₹ 0.00</span>
                </div>

                <!-- Notes -->
                <div>
                    <label class="form-label form-label-sm">Return Notes</label>
                    <textarea id="rtnReturnNotes" class="form-control form-control-sm" rows="2" placeholder="Condition on return, damage notes..."></textarea>
                </div>

            </div><!-- /modal-body -->

            <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="rtnSubmitBtn">
                    <i class="bx bx-undo me-1"></i>Record Return
                </button>
            </div>

        </div>
    </div>
</div>
