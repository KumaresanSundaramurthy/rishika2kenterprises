<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <?php $this->load->view('common/navbar_view'); ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Page Header -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="fw-bold mb-1"><i class="bx bx-bank me-2 text-primary"></i>Bank Accounts</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">Manage your organisation's bank accounts used in transactions and payments.</p>
                        </div>
                        <button class="btn btn-primary" id="btnAddBank">
                            <i class="bx bx-plus me-1"></i>Add Bank Account
                        </button>
                    </div>

                    <!-- Bank Cards List -->
                    <div id="bankCardsContainer">
                        <?php $this->load->view('banks/list', ['BanksList' => $BanksList]); ?>
                    </div>

                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Add / Edit Bank Modal ──────────────────────────────────────── -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="bankModalTitle">
                    <i class="bx bx-plus-circle me-1 text-primary"></i>Add Bank Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="bankUID" value="0">

                <div class="row g-3">

                    <!-- Account Holder Name -->
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small">Account Holder Name <span class="text-danger">*</span></label>
                        <input type="text" id="bm_AccountName" class="form-control"
                            placeholder="e.g. RISHIKA 2K ENTERPRISES" maxlength="100" autocomplete="off"/>
                    </div>

                    <!-- Account No + Confirm -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Account No <span class="text-danger">*</span></label>
                        <input type="text" id="bm_AccountNumber" class="form-control"
                            placeholder="Bank Account No." maxlength="50" autocomplete="new-password"/>
                    </div>
                    <div class="col-md-6" id="confirmAccWrap">
                        <label class="form-label fw-semibold small">Confirm Bank Account No <span class="text-danger">*</span></label>
                        <input type="text" id="bm_ConfirmAccountNumber" class="form-control"
                            placeholder="Confirm Bank Account No." maxlength="50" autocomplete="new-password"/>
                    </div>

                    <!-- IFSC + Bank Name -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">IFSC Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="bm_IFSC" class="form-control text-uppercase"
                                placeholder="IFSC Code" maxlength="20" autocomplete="off"/>
                            <button class="btn btn-outline-secondary" type="button" id="fetchBankDetailsBtn" title="Auto-fill bank details from IFSC">
                                <span id="fetchBankSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                <span id="fetchBankBtnText">Fetch</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" id="bm_BankName" class="form-control"
                            placeholder="e.g. HDFC Bank, SBI..." maxlength="100" autocomplete="off"/>
                    </div>

                    <!-- Branch Name -->
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small">Branch Name</label>
                        <input type="text" id="bm_BranchName" class="form-control"
                            placeholder="Bank Branch Name" maxlength="100" autocomplete="off"/>
                    </div>

                    <!-- Divider: UPI section -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-center gap-2">
                            <hr class="flex-grow-1 mb-0">
                            <small class="text-muted fw-semibold text-uppercase px-2" style="white-space:nowrap;">UPI Details <span class="badge bg-label-secondary ms-1">Optional</span></small>
                            <hr class="flex-grow-1 mb-0">
                        </div>
                        <p class="text-muted mt-1 mb-0" style="font-size:0.78rem;">Link a UPI ID to generate dynamic QR codes on invoices and bills.</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">UPI ID <small class="text-muted fw-normal">(Optional)</small></label>
                        <input type="text" id="bm_UPIId" class="form-control"
                            placeholder="yourname@okhdfc" maxlength="100" autocomplete="off"/>
                        <div class="form-text">e.g. komalakumar2329-1@okhdfcbank</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">UPI Number <small class="text-muted fw-normal">(Optional)</small></label>
                        <input type="text" id="bm_UPINumber" class="form-control"
                            placeholder="Linked mobile number" maxlength="50" autocomplete="off"/>
                    </div>

                    <!-- Set as Default -->
                    <div class="col-md-12">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="bm_IsDefault" value="1">
                            <label class="form-check-label fw-semibold small" for="bm_IsDefault">
                                Set as Default Bank Account
                                <span class="text-muted fw-normal ms-1">— this account will be pre-selected on all transactions</span>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBankBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="saveBankSpinner"></span>
                    Save &amp; Update
                </button>
            </div>

        </div>
    </div>
</div>

<script src="/js/banks.js"></script>

<?php $this->load->view('common/footer'); ?>
