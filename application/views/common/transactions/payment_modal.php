<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Shared Record Payment Modal
 *
 * Required variables (set by the calling view before load):
 *   $rpAccentColor  — e.g. '#0d6efd'  (invoice) | '#6f42c1' (purchase)
 *   $rpAccentBg     — e.g. '#e8f0fe'  (invoice) | '#f0ebff' (purchase)
 *   $rpPartyIcon    — e.g. 'bx-user'  (invoice) | 'bx-store' (purchase)
 *   $rpDocLabel     — e.g. 'Invoice'  (invoice) | 'Bill'     (purchase)
 *   $rpTotalIcon    — e.g. 'bx-receipt'(invoice)| 'bx-cart'  (purchase)
 *   $rpNumId        — e.g. 'rpInvNum' (invoice) | 'rpBillNum'(purchase)
 *   $rpDateId       — e.g. 'rpInvDate'(invoice) | 'rpBillDate'(purchase)
 */
$rpAccentColor = $rpAccentColor ?? '#0d6efd';
$rpAccentBg    = $rpAccentBg    ?? '#e8f0fe';
$rpPartyIcon   = $rpPartyIcon   ?? 'bx-user';
$rpDocLabel    = $rpDocLabel    ?? 'Invoice';
$rpTotalIcon   = $rpTotalIcon   ?? 'bx-receipt';
$rpBtnLabel    = $rpBtnLabel    ?? 'Record Payment';
?>
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rp-modal-content">

            <button type="button" class="btn-close rp-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>

            <div class="modal-body p-0">

                <!-- Banner -->
                <div class="rp-banner" style="background:<?php echo $rpAccentBg; ?>;border-left-color:<?php echo $rpAccentColor; ?>;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rp-banner-icon" style="background:<?php echo $rpAccentColor; ?>22;">
                            <i class="bx bx-money-withdraw" style="color:<?php echo $rpAccentColor; ?>;"></i>
                        </div>
                        <div>
                            <div class="rp-banner-title" style="color:<?php echo $rpAccentColor; ?>;">
                                <?php echo htmlspecialchars($rpBtnLabel); ?> &mdash; <span id="rpDocNum">—</span>
                            </div>
                            <div class="rp-banner-meta">
                                <span id="rpPartyRow"><i class="bx <?php echo $rpPartyIcon; ?> me-1"></i><span id="rpPartyName">—</span><span class="rp-meta-sep">|</span></span>
                                <i class="bx bx-calendar me-1"></i><span id="rpDocDate">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scrollable body -->
                <div class="rp-scroll-body">

                <!-- Summary cards -->
                <div class="rp-summary-section">
                    <div class="rp-section-header">
                        <i class="bx bx-bar-chart-alt-2 rp-section-icon"></i>
                        <span class="rp-section-label">Payment Summary</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="rp-summary-card" style="border-left-color:<?php echo $rpAccentColor; ?>;">
                                <div class="rp-summary-card-label">
                                    <i class="bx <?php echo $rpTotalIcon; ?> me-1"></i><?php echo $rpDocLabel; ?> Total
                                </div>
                                <div class="rp-summary-card-value" style="color:<?php echo $rpAccentColor; ?>;" id="rpTotalCard">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rp-summary-card rp-summary-card--paid">
                                <div class="rp-summary-card-label">
                                    <i class="bx bx-check-circle me-1"></i>Paid So Far
                                </div>
                                <div class="rp-summary-card-value rp-val-paid" id="rpPaidCard">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rp-summary-card rp-summary-card--due">
                                <div class="rp-summary-card-label">
                                    <i class="bx bx-time me-1"></i>Balance Due
                                </div>
                                <div class="rp-summary-card-value rp-val-due" id="rpBalanceCard">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment form -->
                <div class="rp-form-section">
                    <div class="rp-section-header">
                        <i class="bx bx-edit-alt rp-section-icon rp-section-icon--orange"></i>
                        <span class="rp-section-label rp-section-label--orange">Payment Details</span>
                    </div>
                    <div class="row g-3">

                        <div class="col-5">
                            <label class="rp-field-label"><span class="text-danger">*</span> Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white fw-semibold" id="rpCurrencySymbol">₹</span>
                                <input type="number" class="form-control" id="rpAmount" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-7">
                            <label class="rp-field-label">Payment Date</label>
                            <div class="input-group input-group-sm input-group-merge">
                                <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                <input type="text" class="form-control" id="rpPaymentDate" placeholder="Today" readonly>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="rp-field-label">Payment Type</label>
                            <div class="d-flex flex-wrap gap-2" id="rpPaymentTypes">
                                <div class="text-muted rp-loading"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>
                            </div>
                            <input type="hidden" id="rpPaymentTypeUID" value="">
                            <input type="hidden" id="rpIsCash" value="1">
                        </div>

                        <div class="col-12 d-none" id="rpBankRow">
                            <label class="rp-field-label">Bank Account <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="rpBankAccount">
                                <option value="">— Select bank account —</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="rp-field-label">
                                Reference ID <span class="fw-normal text-muted">(Optional)</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="rpReferenceNo"
                                placeholder="UTR, Cheque No, UPI Ref…" maxlength="100">
                        </div>

                        <div class="col-6">
                            <label class="rp-field-label">
                                Notes <span class="fw-normal text-muted">(Optional)</span>
                            </label>
                            <textarea class="form-control form-control-sm" id="rpNotes" rows="1"
                                    placeholder="Add a payment note…" maxlength="255"></textarea>
                        </div>

                        <!-- Attachments -->
                        <div class="col-12">
                            <label class="rp-field-label">
                                Attachments <span class="fw-normal text-muted">(max 3 files &bull; 3 MB each)</span>
                            </label>
                            <div id="rpAttachDropzone" class="comm-attach-dropzone dropzone needsclick dz-clickable">
                                <div class="dz-message needsclick">
                                    <i class="bx bx-cloud-upload comm-attach-icon"></i>
                                    <div class="comm-attach-hint">Drag &amp; drop or <span class="text-primary">browse</span></div>
                                    <div class="comm-attach-sub">PDF, JPG, PNG &bull; Max 3 files, 3 MB each</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                </div><!-- /.rp-scroll-body -->

                <!-- Footer -->
                <div class="rp-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnSubmitPayment">
                        <i class="bx bx-check me-1"></i> <?php echo htmlspecialchars($rpBtnLabel); ?>
                    </button>
                </div>

                <input type="hidden" id="rpTransUID"  value="">
                <input type="hidden" id="rpSubmitUrl" value="">

            </div>
        </div>
    </div>
</div>

<!-- ── Shared Payment Detail Modal ─────────────────────────────── -->
<!--
  Theme is driven by data-pdt-theme on the modal:
    data-pdt-theme="in"  → blue  (payments received)
    data-pdt-theme="out" → orange (payments out)
  Set by calling view before loading this partial.
-->
<?php
$pdtTheme       = $pdtTheme       ?? 'in';
$pdtPartyLabel  = $pdtPartyLabel  ?? 'Party';
$pdtLinkedLabel = $pdtLinkedLabel ?? 'Linked Document';
?>
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true"
     data-pdt-theme="<?php echo htmlspecialchars($pdtTheme); ?>">
    <div class="modal-dialog modal-dialog-centered pdt-dialog">
        <div class="modal-content border-0 shadow position-relative">

            <button type="button" class="btn-close pdt-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>

            <!-- Banner -->
            <div class="pdt-banner">
                <div class="d-flex align-items-center gap-3">
                    <div class="pdt-banner-icon">
                        <i class="pdt-banner-icon-el bx <?php echo $pdtTheme === 'out' ? 'bx-money-withdraw' : 'bx-receipt'; ?> fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark pdt-title" id="pdUniqueNumber">—</div>
                        <div class="text-muted pdt-date" id="pdDateLabel">—</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold pdt-amount" id="pdAmount">—</div>
                        <div id="pdModeBadge" class="mt-1"></div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- Party + Linked doc -->
                <div class="pdt-section">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="pdt-label"><?php echo htmlspecialchars($pdtPartyLabel); ?></div>
                            <div class="fw-semibold pdt-value" id="pdParty">—</div>
                            <div class="pdt-sub" id="pdPartyMobile"></div>
                        </div>
                        <div class="col-6">
                            <div class="pdt-label"><?php echo htmlspecialchars($pdtLinkedLabel); ?></div>
                            <div class="fw-semibold text-primary pdt-value" id="pdTransNumber">—</div>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div id="pdBankSection" class="pdt-section" style="display:none;">
                    <div class="pdt-label mb-2">
                        <i class="bx bx-building-house me-1"></i>Bank Details
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <div class="pdt-sub">Bank / Account Name</div>
                            <div class="fw-semibold pdt-value" id="pdBankName">—</div>
                        </div>
                        <div class="col-5">
                            <div class="pdt-sub">Account Number</div>
                            <div class="fw-semibold pdt-value pdt-mono" id="pdAccountNumber">—</div>
                        </div>
                        <div class="col-6" id="pdIfscWrap" style="display:none;">
                            <div class="pdt-sub">IFSC</div>
                            <div class="fw-semibold pdt-value" id="pdIfsc">—</div>
                        </div>
                        <div class="col-6" id="pdBranchWrap" style="display:none;">
                            <div class="pdt-sub">Branch</div>
                            <div class="fw-semibold pdt-value" id="pdBranch">—</div>
                        </div>
                    </div>
                </div>

                <!-- Reference / By / Notes -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="pdt-label">Reference No</div>
                        <div class="pdt-value" id="pdReference">—</div>
                    </div>
                    <div class="col-6">
                        <div class="pdt-label">Recorded By</div>
                        <div class="pdt-value" id="pdCreatedBy">—</div>
                    </div>
                    <div class="col-12" id="pdNotesWrap" style="display:none;">
                        <div class="pdt-label">Notes</div>
                        <div class="pdt-notes" id="pdNotes"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Payment Details Panel -->
<div id="payDetailPanel" class="pay-detail-panel">
    <div class="pay-detail-panel__header">
        <div class="d-flex justify-content-between align-items-center">
            <span class="pay-detail-panel__title">
                <i class="bx bx-credit-card me-1 text-primary"></i>
                <span id="payPanelTitle">Payments</span>
            </span>
            <button type="button" id="payPanelClose" class="pay-detail-panel__close">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>
    <div id="payDetailBody" class="pay-detail-panel__body"></div>
</div>

<script>
(function () {
    'use strict';

    var _payTypes   = [];
    var _bankAccts  = [];
    var _fpInstance = null;
    var _rpDropzone = null;
    var _currency   = '₹';

    function _rpEsc(s) { return $('<span>').text(s || '').html(); }

    window.initRecordPaymentModal = function (payTypes, bankAccts, currency) {
        _payTypes  = payTypes  || [];
        _bankAccts = bankAccts || [];
        _currency  = currency  || '₹';
        var $sel = $('#rpBankAccount').empty().append('<option value="">— Select bank account —</option>');
        $.each(_bankAccts, function (i, b) {
            $sel.append('<option value="' + b.BankAccountUID + '">' + _rpEsc(b.BankName) + ' — ' + _rpEsc(b.AccountName) + '</option>');
        });
    };

    function _renderPaymentTypes() {
        var $wrap = $('#rpPaymentTypes').empty();
        if (!_payTypes.length) {
            $wrap.html('<div class="text-muted" style="font-size:.8rem;"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>');
            return;
        }
        $.each(_payTypes, function (i, t) {
            var active = (i === 0) ? ' active' : '';
            if (i === 0) { $('#rpPaymentTypeUID').val(t.PaymentTypeUID); $('#rpIsCash').val(t.IsCash); }
            $wrap.append(
                '<button type="button" class="rp-type-pill btn btn-sm btn-outline-secondary' + active + '" ' +
                'data-uid="' + t.PaymentTypeUID + '" data-iscash="' + t.IsCash + '">' + _rpEsc(t.Name) + '</button>'
            );
        });
        _toggleBankRow();
    }

    function _toggleBankRow() {
        var isCash = parseInt($('#rpIsCash').val(), 10);
        $('#rpBankRow').toggleClass('d-none', !!isCash);
        if (!isCash && !$('#rpBankAccount').val()) {
            var def = $.grep(_bankAccts, function (b) { return b.IsDefault === 1; });
            if (def.length) { $('#rpBankAccount').val(def[0].BankAccountUID); }
        }
    }

    // Expose open-modal helper for all modules
    window.rpOpenModal = function (cfg) {
        $('#rpTransUID').val(cfg.transUID || 0);
        $('#rpSubmitUrl').val(cfg.submitUrl || '');
        $('#rpDocNum').text(cfg.docNum || '—');
        $('#rpDocDate').text(cfg.docDate || '—');
        if (cfg.partyName) {
            $('#rpPartyName').text(cfg.partyName);
            $('#rpPartyRow').show();
        } else {
            $('#rpPartyRow').hide();
        }

        var cur = _currency;
        var dec = 2;
        var fmt = function (v) { return cur + ' ' + parseFloat(v || 0).toFixed(dec); };
        $('#rpTotalCard').text(fmt(cfg.total));
        $('#rpPaidCard').text(fmt(cfg.paid));
        $('#rpBalanceCard').text(fmt(cfg.pending));
        $('#rpAmount').val(parseFloat(cfg.pending || 0).toFixed(dec)).attr('max', cfg.pending || 0);
        $('#rpCurrencySymbol').text(cur);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');

        if (_rpDropzone) { _rpDropzone.removeAllFiles(true); }
        _renderPaymentTypes();
        new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
    };

    // All jQuery-dependent event bindings are deferred until DOMContentLoaded
    // because jQuery is loaded in the footer (after this script runs).
    document.addEventListener('DOMContentLoaded', function () {

        // Init flatpickr and dropzone when modal first opens; reset date on each open
        $('#recordPaymentModal').on('shown.bs.modal', function () {
            if (!_fpInstance) {
                _fpInstance = flatpickr('#rpPaymentDate', {
                    dateFormat   : 'Y-m-d',
                    altInput     : true,
                    altFormat    : 'd M Y',
                    maxDate      : 'today',
                    disableMobile: true,
                    defaultDate  : 'today',
                    appendTo     : document.querySelector('#recordPaymentModal .modal-dialog'),
                });
            } else {
                _fpInstance.setDate(new Date(), false);
            }
            if (!_rpDropzone && typeof Dropzone !== 'undefined') {
                Dropzone.autoDiscover = false;
                _rpDropzone = new Dropzone('#rpAttachDropzone', {
                    url              : '#',
                    autoProcessQueue : false,
                    maxFiles         : 3,
                    maxFilesize      : 3,
                    acceptedFiles    : '.pdf,.jpg,.jpeg,.png',
                    parallelUploads  : 3,
                    clickable        : true,
                    previewTemplate  : '<div class="dz-preview dz-file-preview"><div class="dz-details"><div class="dz-filename"><span data-dz-name></span></div><div class="dz-size"><span data-dz-size></span></div></div><div class="dz-error-message"><span data-dz-errormessage></span></div><a class="dz-remove" href="javascript:undefined;" data-dz-remove>Remove</a></div>',
                    init: function () {
                        this.on('maxfilesexceeded', function (file) {
                            this.removeFile(file);
                            Swal.fire({ icon: 'warning', text: 'Maximum 3 attachments allowed.' });
                        });
                        this.on('error', function (file) {
                            if (file.size > 3 * 1024 * 1024) {
                                this.removeFile(file);
                                Swal.fire({ icon: 'warning', text: 'Each file must be 3 MB or smaller.' });
                            }
                        });
                    }
                });
            }
        });

        // Payment type pill toggle
        // Cap amount at pending balance on every keystroke
        $(document).on('input', '#rpAmount', function () {
            var max = parseFloat($(this).attr('max')) || 0;
            var val = parseFloat($(this).val())       || 0;
            if (max > 0 && val > max) {
                $(this).val(max.toFixed(2));
            }
        });

        $(document).on('click', '.rp-type-pill', function () {
            $('.rp-type-pill').removeClass('active btn-primary').addClass('btn-outline-secondary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
            $('#rpPaymentTypeUID').val($(this).data('uid'));
            $('#rpIsCash').val($(this).data('iscash'));
            _toggleBankRow();
        });

        // Generic submit handler — URL comes from #rpSubmitUrl
        $('#btnSubmitPayment').on('click', function () {
            var transUID       = parseInt($('#rpTransUID').val(), 10);
            var paymentTypeUID = parseInt($('#rpPaymentTypeUID').val(), 10);
            var amount         = parseFloat($('#rpAmount').val()) || 0;
            var paymentDate    = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
            var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
            var referenceNo    = $.trim($('#rpReferenceNo').val());
            var notes          = $.trim($('#rpNotes').val());
            var submitUrl      = $('#rpSubmitUrl').val();

            var maxAmount = parseFloat($('#rpAmount').attr('max')) || 0;

            if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid record.' }); return; }
            if (!paymentTypeUID) { Swal.fire({ icon: 'warning', text: 'Please select a payment type.' }); return; }
            if (amount <= 0)     { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }
            if (maxAmount > 0 && amount > maxAmount) {
                Swal.fire({ icon: 'warning', text: 'Amount cannot exceed the balance due (' + _currency + ' ' + maxAmount.toFixed(2) + ').' });
                $('#rpAmount').val(maxAmount.toFixed(2)).focus();
                return;
            }
            var isCash = parseInt($('#rpIsCash').val(), 10);
            if (!isCash && !bankAccountUID) { Swal.fire({ icon: 'warning', text: 'Please select a bank account.' }); return; }
            if (!submitUrl) { Swal.fire({ icon: 'warning', text: 'Configuration error — please refresh.' }); return; }

            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');

            var fd = new FormData();
            fd.append('TransUID',       transUID);
            fd.append('PaymentTypeUID', paymentTypeUID);
            fd.append('Amount',         amount);
            fd.append('PaymentDate',    paymentDate);
            fd.append('BankAccountUID', bankAccountUID || '');
            fd.append('ReferenceNo',    referenceNo);
            fd.append('Notes',          notes);
            fd.append('CurrentPage',    typeof PageNo    !== 'undefined' ? PageNo    : 1);
            fd.append('RowLimit',       typeof RowLimit  !== 'undefined' ? RowLimit  : 10);
            fd.append('Filter',         typeof Filter    !== 'undefined' ? JSON.stringify(Filter) : '{}');
            fd.append(CsrfName,         CsrfToken);
            if (_rpDropzone) { _rpDropzone.files.forEach(function (f) { fd.append('PaymentFiles[]', f); }); }

            $.ajax({
                url         : submitUrl,
                method      : 'POST',
                data        : fd,
                processData : false,
                contentType : false,
                success: function (resp) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                    if (resp.Error) {
                        showToastNotification(resp.Message, 'error');
                    } else {
                        var _rpModalInst = bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal'));
                        if (_rpModalInst) _rpModalInst.hide();
                        if (_rpDropzone) { _rpDropzone.removeAllFiles(true); }
                        if (resp.RecordHtmlData) {
                            $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
                            $(ModulePag).html(resp.Pagination || '');
                            $('[data-bs-toggle="tooltip"]').each(function () {
                                try { new bootstrap.Tooltip(this, { container: 'body' }); } catch (e) {}
                            });
                        }
                        if (typeof window.rpAfterSuccess === 'function') window.rpAfterSuccess(resp);
                        showToastNotification(resp.Message, 'success');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                    showToastNotification('Request failed. Try again.', 'error');
                }
            });
        });

    }); // end DOMContentLoaded

}());
</script>
