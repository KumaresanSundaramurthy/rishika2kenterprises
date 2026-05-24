<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade modal-select2-search" id="rntCreateModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon" style="background:#ede9fe;">
                        <i class="bx bx-cycling modal-doc-icon-inner" style="color:#7c3aed;"></i>
                    </div>
                    <h5 class="modal-title mb-0">New Rental Booking</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="rntCreateSaveBtn">
                        <i class="bx bx-check me-1"></i>Create Rental
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-3">
                <div class="row g-3">

                    <!-- Left: Form -->
                    <div class="col-lg-8">

                        <!-- Customer -->
                        <div class="card mb-3">
                            <div class="card-header py-2"><h6 class="mb-0">Customer</h6></div>
                            <div class="card-body">
                                <select id="rntCustomerUID" name="CustomerUID" class="form-select form-select-sm" style="width:100%;"></select>
                            </div>
                        </div>

                        <!-- Rental Period -->
                        <div class="card mb-3">
                            <div class="card-header py-2"><h6 class="mb-0">Rental Period</h6></div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm">Start Date & Time <span class="text-danger">*</span></label>
                                        <input type="text" id="rntStartDateTime" class="form-control form-control-sm" placeholder="Select start date/time" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label form-label-sm">Expected Return Date & Time <span class="text-danger">*</span></label>
                                        <input type="text" id="rntReturnDueDateTime" class="form-control form-control-sm" placeholder="Select return date/time" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Machines -->
                        <div class="card mb-3">
                            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">Machines / Equipment</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="rntAddMachineBtn">
                                    <i class="bx bx-plus me-1"></i>Add Machine
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="font-size:.82rem;">
                                        <thead style="background:#f8fafc;">
                                            <tr>
                                                <th style="padding:.5rem .75rem;">Machine</th>
                                                <th style="padding:.5rem .75rem;width:70px;">Qty</th>
                                                <th style="padding:.5rem .75rem;width:120px;">Rental Type</th>
                                                <th style="padding:.5rem .75rem;width:110px;text-align:right;">Rate (per unit)</th>
                                                <th style="padding:.5rem .75rem;width:110px;text-align:right;">Charge</th>
                                                <th style="padding:.5rem .75rem;width:36px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="rntMachineRows">
                                            <tr id="rntNoMachinesRow">
                                                <td colspan="6" class="text-center text-muted py-4" style="font-size:.85rem;">
                                                    <i class="bx bx-cycling fs-4 d-block mb-2"></i>Click "Add Machine" to add rentable equipment
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="card mb-0">
                            <div class="card-header py-2"><h6 class="mb-0">Notes</h6></div>
                            <div class="card-body">
                                <textarea id="rntNotes" class="form-control form-control-sm" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                        </div>

                    </div><!-- /col-lg-8 -->

                    <!-- Right: Summary -->
                    <div class="col-lg-4">

                        <!-- Totals -->
                        <div class="card mb-3" style="position:sticky;top:0;">
                            <div class="card-header py-2"><h6 class="mb-0">Booking Summary</h6></div>
                            <div class="card-body">

                                <div class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted" style="font-size:.82rem;">Rental Charge</span>
                                    <span id="rntSummaryRentalCharge" style="font-size:.82rem;font-weight:600;">₹ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between py-2">
                                    <span class="fw-semibold">Grand Total</span>
                                    <span id="rntSummaryGrandTotal" style="font-weight:700;font-size:1rem;color:#7c3aed;">₹ 0.00</span>
                                </div>

                                <hr class="my-2">

                                <!-- Deposit -->
                                <div class="mb-2">
                                    <label class="form-label form-label-sm mb-1">Security Deposit Collected</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?></span>
                                        <input type="number" id="rntDepositCollected" class="form-control" value="0" min="0" step="0.01">
                                    </div>
                                    <div id="rntDepositSuggestion" class="text-muted mt-1" style="font-size:.72rem;"></div>
                                </div>

                                <!-- Deposit Payment Type -->
                                <div id="rntDepositPayWrap" class="d-none">
                                    <div class="mb-2">
                                        <label class="form-label form-label-sm mb-1">Payment Method for Deposit</label>
                                        <select id="rntDepositPayType" class="form-select form-select-sm">
                                            <option value="">— Select method —</option>
                                        </select>
                                    </div>
                                    <div id="rntDepositBankWrap" class="mb-2 d-none">
                                        <label class="form-label form-label-sm mb-1">Bank Account</label>
                                        <select id="rntDepositBankUID" class="form-select form-select-sm">
                                            <option value="">— Select bank —</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="my-2">

                                <div class="d-flex justify-content-between">
                                    <span class="text-muted" style="font-size:.82rem;">Balance After Deposit</span>
                                    <span id="rntSummaryBalance" style="font-size:.82rem;font-weight:600;color:#dc2626;">₹ 0.00</span>
                                </div>

                            </div>
                        </div>

                    </div><!-- /col-lg-4 -->

                </div><!-- /row -->
            </div><!-- /modal-body -->

        </div>
    </div>
</div>
