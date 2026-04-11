/**
 * Banks Settings Page — AJAX interactions
 * Handles: Add / Edit / Delete / Set Default bank accounts
 */
$(function () {
    'use strict';

    /* ── Open modal: Add ─────────────────────────────────────────── */
    $(document).on('click', '#btnAddBank, #btnAddBankEmpty', function () {
        resetBankForm();
        $('#bankModalTitle').html('<i class="bx bx-plus-circle me-1 text-primary"></i>Add Bank Account');
        $('#confirmAccWrap').show();
        $('#bankModal').modal('show');
    });

    /* ── Open modal: Edit ────────────────────────────────────────── */
    $(document).on('click', '.editBankBtn', function () {
        var uid = $(this).data('uid');

        resetBankForm();
        $('#bankModalTitle').html('<i class="bx bx-edit me-1 text-primary"></i>Edit Bank Account');
        $('#confirmAccWrap').hide();   // no confirm field on edit
        $('#bankUID').val(uid);

        $.ajax({
            url    : '/payments/getBankDetails',
            method : 'POST',
            data   : { BankAccountUID: uid },
            success: function (resp) {
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
                $('#bankModal').modal('show');
            },
            error: function () {
                Swal.fire('Error', 'Failed to load bank details.', 'error');
            }
        });
    });

    /* ── Save (Add / Edit) ───────────────────────────────────────── */
    $(document).on('click', '#saveBankBtn', function () {
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

        if (!accountName)   return showFieldError('#bm_AccountName',   'Account holder name is required.');
        if (!accountNumber) return showFieldError('#bm_AccountNumber', 'Account number is required.');
        if (uid <= 0 && accountNumber !== confirmNo) return showFieldError('#bm_ConfirmAccountNumber', 'Account numbers do not match.');
        if (!bankName)      return showFieldError('#bm_BankName',      'Bank name is required.');

        var $btn = $('#saveBankBtn').prop('disabled', true);
        $('#saveBankSpinner').removeClass('d-none');

        $.ajax({
            url   : '/payments/saveBankAccount',
            method: 'POST',
            data  : {
                BankAccountUID        : uid,
                AccountName           : accountName,
                AccountNumber         : accountNumber,
                ConfirmAccountNumber  : confirmNo,
                IFSC                  : ifsc,
                BankName              : bankName,
                BranchName            : branchName,
                UPIId                 : upiId,
                UPINumber             : upiNumber,
                IsDefault             : isDefault,
                [CsrfName]            : CsrfToken,
            },
            success: function (resp) {
                $btn.prop('disabled', false);
                $('#saveBankSpinner').addClass('d-none');
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                $('#bankModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Saved', text: resp.Message, timer: 2000, showConfirmButton: false });
                reloadBankList();
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#saveBankSpinner').addClass('d-none');
                Swal.fire('Error', 'Server error. Please try again.', 'error');
            }
        });
    });

    /* ── Set Default ─────────────────────────────────────────────── */
    $(document).on('click', '.setDefaultBankBtn', function () {
        var uid  = $(this).data('uid');
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url   : '/payments/setDefaultBank',
            method: 'POST',
            data  : { BankAccountUID: uid },
            success: function (resp) {
                $btn.prop('disabled', false);
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                reloadBankList();
            },
            error: function () {
                $btn.prop('disabled', false);
                Swal.fire('Error', 'Server error.', 'error');
            }
        });
    });

    /* ── Delete ──────────────────────────────────────────────────── */
    $(document).on('click', '.deleteBankBtn', function () {
        var uid  = $(this).data('uid');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Delete Bank Account?',
            html : '<span class="text-muted small">' + name + '</span>',
            icon : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonText : 'Cancel',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url   : '/payments/deleteBankAccount',
                method: 'POST',
                data  : { BankAccountUID: uid },
                success: function (resp) {
                    if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                    Swal.fire({ icon: 'success', title: 'Deleted', text: resp.Message, timer: 1800, showConfirmButton: false });
                    reloadBankList();
                },
                error: function () {
                    Swal.fire('Error', 'Server error.', 'error');
                }
            });
        });
    });

    /* ── IFSC Fetch ──────────────────────────────────────────────── */
    $(document).on('click', '#fetchBankDetailsBtn', function () {
        var ifsc = $.trim($('#bm_IFSC').val()).toUpperCase();
        if (ifsc.length < 11) return Swal.fire('Info', 'Please enter a valid 11-character IFSC code.', 'info');

        $('#fetchBankSpinner').removeClass('d-none');
        $('#fetchBankBtnText').text('');

        $.ajax({
            url    : 'https://ifsc.razorpay.com/' + ifsc,
            method : 'GET',
            success: function (data) {
                $('#fetchBankSpinner').addClass('d-none');
                $('#fetchBankBtnText').text('Fetch');
                if (data && data.BANK) {
                    $('#bm_BankName').val(data.BANK || '');
                    $('#bm_BranchName').val(data.BRANCH || '');
                    Swal.fire({ icon: 'success', title: 'Bank details fetched', text: data.BANK + ', ' + data.BRANCH, timer: 2500, showConfirmButton: false });
                } else {
                    Swal.fire('Not found', 'Could not find bank details for this IFSC.', 'warning');
                }
            },
            error: function () {
                $('#fetchBankSpinner').addClass('d-none');
                $('#fetchBankBtnText').text('Fetch');
                Swal.fire('Error', 'Failed to fetch IFSC details. Please fill in manually.', 'error');
            }
        });
    });

    /* ── Auto-uppercase IFSC ─────────────────────────────────────── */
    $(document).on('input', '#bm_IFSC', function () {
        $(this).val($(this).val().toUpperCase());
    });

    /* ── Helpers ─────────────────────────────────────────────────── */
    function resetBankForm() {
        $('#bankUID').val(0);
        $('#bm_AccountName, #bm_AccountNumber, #bm_ConfirmAccountNumber, #bm_IFSC, #bm_BankName, #bm_BranchName, #bm_UPIId, #bm_UPINumber').val('');
        $('#bm_IsDefault').prop('checked', false);
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#confirmAccWrap').show();
    }

    function showFieldError(selector, msg) {
        $(selector).addClass('is-invalid').focus();
        if (!$(selector).next('.invalid-feedback').length) {
            $(selector).after('<div class="invalid-feedback">' + msg + '</div>');
        }
        return false;
    }

    function reloadBankList() {
        $.ajax({
            url   : '/payments/getBanksList',
            method: 'POST',
            success: function (resp) {
                if (!resp.Error && resp.Data !== undefined) {
                    // Build simple cards reload via server partial
                    location.reload();
                }
            }
        });
    }

    /* ── Clear validation on input ───────────────────────────────── */
    $(document).on('input', '#bankModal .form-control', function () {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });

});
