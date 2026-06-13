<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-buildings',
                    'pageIconBg'      => '#eff6ff',
                    'pageIconColor'   => '#2563eb',
                    'pageTitle'       => $PageTitle       ?? 'Bank Accounts',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card">

                        <!-- Action bar -->
                        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom gap-2 flex-wrap">
                            <span class="text-muted small">Manage your organisation's bank accounts used in transactions and payments.</span>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm px-3" id="btnTransferFunds">
                                    <i class="bx bx-transfer me-1"></i>Transfer Funds
                                </button>
                                <button class="btn btn-primary btn-sm px-3" id="btnAddBank">
                                    <i class="bx bx-plus me-1"></i>Add Bank Account
                                </button>
                            </div>
                        </div>

                        <!-- Info Note -->
                        <div class="px-3 pt-3 pb-2">
                            <div class="alert alert-warning d-flex gap-2 mb-0 py-2 px-3" style="font-size:.82rem;border-left:3px solid #f59e0b;background:#fffbeb;border-radius:6px;">
                                <i class="bx bx-info-circle mt-1 flex-shrink-0" style="color:#d97706;font-size:1rem;"></i>
                                <div>
                                    <div class="fw-semibold mb-1" style="color:#92400e;">Before you edit a bank account, please read this:</div>
                                    <ul class="mb-0 ps-3" style="color:#78350f;line-height:1.7;">
                                        <li>Use <strong>Edit</strong> only to fix mistakes like a wrong account number, IFSC, or name — not to replace it with a different bank.</li>
                                        <li>Changing bank details on an existing account will affect its opening balance and past transaction records.</li>
                                        <li>For a new bank, always click <strong>Add Bank Account</strong> — never overwrite an existing one.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Cards container -->
                        <div id="bankCardsContainer">
                            <div class="text-center py-5 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                            </div>
                        </div>

                    </div>

                </div>
            </div>
            <!-- / Content wrapper -->

            <!-- ============================================================
                 Bank Account Modal (Add / Edit)
            ============================================================ -->
            <div class="modal fade" id="bankDetailModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">

                        <div class="modal-header pb-3">
                            <h5 class="modal-title" id="bankModalTitle">
                                <i class="bx bxs-credit-card me-1 text-primary"></i>Add Bank Account
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <hr class="my-0">

                        <div class="modal-body">
                            <input type="hidden" id="bankUID" value="0">

                            <div class="row g-3">

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Account Holder Name <span class="text-danger">*</span></label>
                                    <input type="text" id="bm_AccountName" class="form-control"
                                        placeholder="e.g. RISHIKA 2K ENTERPRISES" maxlength="100" autocomplete="off"/>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Account No <span class="text-danger">*</span></label>
                                    <input type="password" id="bm_AccountNumber" class="form-control"
                                        placeholder="Bank Account No." maxlength="50" autocomplete="new-password"/>
                                </div>
                                <div class="col-md-6" id="confirmAccWrap">
                                    <label class="form-label fw-semibold small">Confirm Bank Account No <span class="text-danger">*</span></label>
                                    <input type="text" id="bm_ConfirmAccountNumber" class="form-control"
                                        placeholder="Confirm Bank Account No." maxlength="50" autocomplete="new-password"/>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">IFSC Code <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" id="bm_IFSC" class="form-control text-uppercase"
                                            placeholder="IFSC Code" maxlength="20" autocomplete="off"/>
                                        <button class="btn btn-outline-secondary" type="button" id="fetchBankDetailsBtn"
                                                title="Auto-fill bank details from IFSC">
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

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Branch Name</label>
                                    <input type="text" id="bm_BranchName" class="form-control"
                                        placeholder="Bank Branch Name" maxlength="100" autocomplete="off"/>
                                </div>

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
                                    <div class="input-group">
                                        <input type="text" id="bm_UPIId" class="form-control"
                                            placeholder="yourname@okhdfc" maxlength="100" autocomplete="off"/>
                                        <button class="btn btn-outline-secondary" type="button" id="verifyUPIBtn"
                                                title="Verify UPI ID">
                                            <span id="verifyUPISpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                            <span id="verifyUPIBtnText">Verify</span>
                                        </button>
                                    </div>
                                    <div class="form-text">e.g. komalakumar2329-1@okhdfcbank</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">UPI Number <small class="text-muted fw-normal">(Optional)</small></label>
                                    <input type="text" id="bm_UPINumber" class="form-control"
                                        placeholder="Linked mobile number" maxlength="50" autocomplete="off"/>
                                </div>

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
                        <hr class="my-0">

                        <div class="modal-footer pt-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveBankBtn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" id="saveBankSpinner"></span>
                                Save &amp; Update
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Bank Account Modal -->

            <!-- ============================================================
                 Transfer Funds Modal
            ============================================================ -->
            <div class="modal fade" id="transferFundsModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
                    <div class="modal-content">

                        <div class="modal-header py-3">
                            <div>
                                <h5 class="modal-title mb-0"><i class="bx bx-transfer me-2 text-primary"></i>Transfer Funds</h5>
                                <small class="text-muted">Move money between your bank accounts</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <div class="d-none transferFormAlert alert alert-danger mb-3 p-2" role="alert">
                                <span class="alert-message"></span>
                            </div>

                            <div class="row g-3">

                                <div class="col-12">
                                    <label class="form-label">From Account <span class="text-danger">*</span></label>
                                    <select class="form-select" id="TransferFromBank">
                                        <option value="">— Select Account —</option>
                                    </select>
                                </div>

                                <div class="col-12 text-center">
                                    <i class="bx bx-down-arrow-alt text-muted" style="font-size:1.5rem;"></i>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">To Account <span class="text-danger">*</span></label>
                                    <select class="form-select" id="TransferToBank">
                                        <option value="">— Select Account —</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="TransferAmount" placeholder="0.00" step="0.01" min="0.01" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="TransferDate" value="<?php echo date('Y-m-d'); ?>" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Reference No <span class="text-muted small">(optional)</span></label>
                                    <input type="text" class="form-control" id="TransferReferenceNo" placeholder="UTR / Cheque No" maxlength="100" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
                                    <textarea class="form-control" id="TransferNotes" rows="2" maxlength="500" placeholder="Optional remarks"></textarea>
                                </div>

                            </div>

                        </div>

                        <div class="modal-footer py-3">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveTransferBtn">
                                <i class="bx bx-transfer me-1"></i>Transfer
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Transfer Funds Modal -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
.bank-icon-cash, .bank-icon-normal {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 8px; font-size: 1.1rem; flex-shrink: 0;
}
.bank-icon-cash   { background: rgba(var(--bs-warning-rgb),.12); color: var(--bs-warning); }
.bank-icon-normal { background: rgba(var(--bs-primary-rgb),.1);  color: var(--bs-primary); }
</style>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function() {
    'use strict';

    var banksList = [];

    function loadBankList() {
        $('#bankCardsContainer').html(
            '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>'
        );
        AjaxLoading = 0;
        $.ajax({
            url: '/settings/getBankList', method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function(resp) {
                AjaxLoading = 1;
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (!resp.Error) {
                    $('#bankCardsContainer').html(resp.RecordHtmlData);
                    cacheBanksForTransfer();
                }
            }
        });
    }

    function cacheBanksForTransfer() {
        banksList = [];
        $('#bankCardsContainer [id^="bankCard_"]').each(function() {
            var uid    = $(this).attr('id').replace('bankCard_', '');
            var name   = $(this).find('.fw-bold').first().text().trim();
            var isCash = $(this).find('.bx-money').length > 0;
            banksList.push({ uid: uid, name: name, isCash: isCash });
        });
    }

    loadBankList();

    /* ── Show Balance toggle ─────────────────────────────────────────── */
    $(document).on('click', '.bank-balance-row', function() {
        var $row  = $(this);
        var $val  = $row.find('.bank-balance-val');
        var uid   = $row.data('uid');

        if ($row.data('loaded')) {
            // Toggle visibility on repeat clicks
            $val.css('text-decoration', $val.css('text-decoration') === 'none' ? 'line-through' : 'none');
            if ($val.css('text-decoration') !== 'none') {
                $val.css('color', '#aaa').text('₹ ••••••');
            } else {
                $val.css('color', $row.data('color')).text($row.data('display'));
            }
            return;
        }

        $val.css('text-decoration', 'none').css('color', '#999').html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/settings/getBankBalance', method: 'POST',
            data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) {
                    $val.text('Error').css('color', '#dc3545');
                    return;
                }
                var bal    = parseFloat(resp.Balance);
                var isNeg  = bal < 0;
                var color  = isNeg ? '#dc3545' : '#28a745';
                var sign   = isNeg ? '' : '+';
                var fmt    = '₹ ' + sign + bal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $val.text(fmt).css({ 'color': color, 'text-decoration': 'none' });
                $row.data('loaded', true).data('display', fmt).data('color', color);
            },
            error: function() {
                $val.text('Failed').css('color', '#dc3545');
            }
        });
    });

    $(document).on('click', '#btnAddBank, #btnAddBankEmpty', function() {
        resetBankForm();
        $('#bankModalTitle').html('<i class="bx bxs-credit-card me-1 text-primary"></i>Add Bank Account');
        $('#confirmAccWrap').show();
        $('#bankDetailModal').modal('show');
    });

    $(document).on('click', '.editBankBtn', function() {
        var uid = $(this).data('uid');
        resetBankForm();
        $('#bankModalTitle').html('<i class="bx bx-edit me-1 text-primary"></i>Edit Bank Account');
        $('#confirmAccWrap').hide();
        $('#bankUID').val(uid);
        $.ajax({
            url: '/settings/getBankDetail', method: 'POST',
            data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                var d = resp.Data;
                $('#bm_AccountName').val(d.AccountName || '');
                $('#bm_AccountNumber').val(d.AccountNumber || '');
                $('#bm_IFSC').val(d.IFSC || '');
                $('#bm_BankName').val(d.BankName || '');
                $('#bm_BranchName').val(d.BranchName || '');
                $('#bm_UPIId').val(d.UPIId || '');
                $('#bm_UPINumber').val(d.UPINumber || '');
                $('#bm_IsDefault').prop('checked', parseInt(d.IsDefault) === 1);
                $('#bankDetailModal').modal('show');
            },
            error: function() { Swal.fire('Error', 'Failed to load bank details.', 'error'); }
        });
    });

    $(document).on('click', '#saveBankBtn', function() {
        var uid           = parseInt($('#bankUID').val()) || 0;
        var accountName   = $.trim($('#bm_AccountName').val());
        var accountNumber = $.trim($('#bm_AccountNumber').val());
        var confirmNo     = $.trim($('#bm_ConfirmAccountNumber').val());
        var ifsc          = $.trim($('#bm_IFSC').val()).toUpperCase();
        var bankName      = $.trim($('#bm_BankName').val());
        var branchName    = $.trim($('#bm_BranchName').val());
        var upiId         = $.trim($('#bm_UPIId').val());
        var upiNumber     = $.trim($('#bm_UPINumber').val());
        var isDefault     = $('#bm_IsDefault').is(':checked') ? 1 : 0;

        if (!accountName)   return bankFieldError('#bm_AccountName',   'Account holder name is required.');
        if (!accountNumber) return bankFieldError('#bm_AccountNumber', 'Account number is required.');
        if (uid <= 0 && !confirmNo) return bankFieldError('#bm_ConfirmAccountNumber', 'Please confirm the account number.');
        if (uid <= 0 && accountNumber !== confirmNo) return bankFieldError('#bm_ConfirmAccountNumber', 'Account numbers do not match.');
        if (!bankName)      return bankFieldError('#bm_BankName',      'Bank name is required.');

        var $btn = $('#saveBankBtn').prop('disabled', true);
        $('#saveBankSpinner').removeClass('d-none');

        $.ajax({
            url: '/settings/saveBankDetail', method: 'POST',
            data: {
                BankAccountUID       : uid,
                AccountName          : accountName,
                AccountNumber        : accountNumber,
                ConfirmAccountNumber : confirmNo,
                IFSC                 : ifsc,
                BankName             : bankName,
                BranchName           : branchName,
                UPIId                : upiId,
                UPINumber            : upiNumber,
                IsDefault            : isDefault,
                [CsrfName]           : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#saveBankSpinner').addClass('d-none');
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                $('#bankDetailModal').modal('hide');
                Swal.fire({ icon:'success', title:'Saved', text:resp.Message, timer:2000, showConfirmButton:false });
                loadBankList();
            },
            error: function() {
                $btn.prop('disabled', false);
                $('#saveBankSpinner').addClass('d-none');
                Swal.fire('Error', 'Server error. Please try again.', 'error');
            }
        });
    });

    $(document).on('click', '.setDefaultBankBtn', function() {
        var uid  = $(this).data('uid');
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/settings/setDefaultBank', method: 'POST',
            data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                loadBankList();
            },
            error: function() { $btn.prop('disabled', false); Swal.fire('Error', 'Server error.', 'error'); }
        });
    });

    $(document).on('click', '.deleteBankBtn', function() {
        var uid  = $(this).data('uid');
        var name = $(this).data('name');
        Swal.fire({
            title: 'Delete Bank Account?',
            html : '<span class="text-muted small">' + name + '</span>',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Delete', confirmButtonColor: '#dc3545',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '/settings/deleteBankDetail', method: 'POST',
                data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
                success: function(resp) {
                    CsrfToken = resp.NewCsrfToken || CsrfToken;
                    if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                    Swal.fire({ icon:'success', title:'Deleted', text:resp.Message, timer:1800, showConfirmButton:false });
                    loadBankList();
                },
                error: function() { Swal.fire('Error', 'Server error.', 'error'); }
            });
        });
    });

    $(document).on('click', '#fetchBankDetailsBtn', function() {
        var ifsc = $.trim($('#bm_IFSC').val()).toUpperCase();
        if (ifsc.length < 11) return Swal.fire('Info', 'Please enter a valid 11-character IFSC code.', 'info');
        $('#fetchBankSpinner').removeClass('d-none');
        $('#fetchBankBtnText').text('');
        $.ajax({
            url: 'https://ifsc.razorpay.com/' + ifsc, method: 'GET',
            success: function(data) {
                $('#fetchBankSpinner').addClass('d-none');
                $('#fetchBankBtnText').text('Fetch');
                if (data && data.BANK) {
                    $('#bm_BankName').val(data.BANK || '');
                    $('#bm_BranchName').val(data.BRANCH || '');
                    Swal.fire({ icon:'success', title:'Bank details fetched', text: data.BANK + ', ' + data.BRANCH, timer:2500, showConfirmButton:false });
                } else {
                    Swal.fire('Not found', 'Could not find bank details for this IFSC.', 'warning');
                }
            },
            error: function() {
                $('#fetchBankSpinner').addClass('d-none');
                $('#fetchBankBtnText').text('Fetch');
                Swal.fire('Error', 'Failed to fetch IFSC details. Please fill in manually.', 'error');
            }
        });
    });

    $(document).on('input', '#bm_IFSC', function() { $(this).val($(this).val().toUpperCase()); });

    $(document).on('input', '#bm_ConfirmAccountNumber', function() {
        var accNo   = $('#bm_AccountNumber').val();
        var confirm = $(this).val();
        if (confirm.length === 0) {
            $(this).removeClass('is-invalid is-valid');
            $(this).next('.invalid-feedback').remove();
            return;
        }
        if (accNo !== confirm) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Account numbers do not match.</div>');
            }
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    $(document).on('click', '#verifyUPIBtn', function() {
        var upi = $.trim($('#bm_UPIId').val());
        if (!upi) return Swal.fire('Info', 'Please enter a UPI ID first.', 'info');
        $('#verifyUPISpinner').removeClass('d-none');
        $('#verifyUPIBtnText').text('');
        setTimeout(function() {
            $('#verifyUPISpinner').addClass('d-none');
            $('#verifyUPIBtnText').text('Verify');
            Swal.fire({ icon:'info', text:'UPI verification requires payment gateway integration.', timer:2200, showConfirmButton:false });
        }, 800);
    });

    $(document).on('input', '#bankDetailModal .form-control', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });

    function resetBankForm() {
        $('#bankUID').val(0);
        $('#bm_AccountName,#bm_AccountNumber,#bm_ConfirmAccountNumber,#bm_IFSC,#bm_BankName,#bm_BranchName,#bm_UPIId,#bm_UPINumber').val('');
        $('#bm_IsDefault').prop('checked', false);
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#confirmAccWrap').show();
    }

    function bankFieldError(selector, msg) {
        $(selector).addClass('is-invalid').focus();
        if (!$(selector).next('.invalid-feedback').length) {
            $(selector).after('<div class="invalid-feedback">' + msg + '</div>');
        }
        return false;
    }

    $('#bankDetailModal').on('hidden.bs.modal', function() { resetBankForm(); });

    $('#btnTransferFunds').on('click', function() {
        resetTransferModal();
        var $from = $('#TransferFromBank'), $to = $('#TransferToBank');
        $from.find('option:not(:first)').remove();
        $to.find('option:not(:first)').remove();
        banksList.forEach(function(b) {
            var opt = '<option value="' + b.uid + '">' + (b.isCash ? '💵 ' : '🏦 ') + b.name + '</option>';
            $from.append(opt); $to.append(opt);
        });
        $('#transferFundsModal').modal('show');
    });

    $('#saveTransferBtn').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Transferring...');
        $.ajax({
            url: '/settings/transferFunds', method: 'POST',
            data: {
                FromBankUID  : $('#TransferFromBank').val(),
                ToBankUID    : $('#TransferToBank').val(),
                Amount       : $('#TransferAmount').val(),
                TransferDate : $('#TransferDate').val(),
                ReferenceNo  : $('#TransferReferenceNo').val().trim(),
                Notes        : $('#TransferNotes').val().trim(),
                [CsrfName]   : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i>Transfer');
                if (resp.Error) {
                    $('.transferFormAlert .alert-message').text(resp.Message);
                    $('.transferFormAlert').removeClass('d-none');
                } else {
                    $('#transferFundsModal').modal('hide');
                    Swal.fire({ icon:'success', title:'Done', text:resp.Message, timer:1500, showConfirmButton:false });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i>Transfer');
                Swal.fire({ icon:'error', text:'Server error. Please try again.' });
            }
        });
    });

    function resetTransferModal() {
        $('#TransferFromBank,#TransferToBank').val('');
        $('#TransferAmount,#TransferReferenceNo,#TransferNotes').val('');
        $('#TransferDate').val('<?php echo date("Y-m-d"); ?>');
        $('.transferFormAlert').addClass('d-none');
    }

    $('#transferFundsModal').on('hidden.bs.modal', function() { resetTransferModal(); });

});
</script>
