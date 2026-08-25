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

                <!-- Available Credits Section -->
                <div id="rpAdvanceSection" class="rp-advance-section" style="display:none;">

                    <!-- State 1: Prompt -->
                    <div id="rpAdvPrompt" class="rp-adv-prompt">
                        <i class="bx bx-wallet-alt rp-adv-prompt-icon"></i>
                        <span class="rp-adv-prompt-text">Credits (advance or on-account) may be available for this customer.</span>
                        <button type="button" class="btn btn-sm rp-adv-check-btn" id="rpAdvCheckBtn">
                            Check &amp; Apply
                        </button>
                    </div>

                    <!-- State 2: Loading -->
                    <div id="rpAdvLoading" class="rp-adv-loading" style="display:none;">
                        <i class="bx bx-loader-alt bx-spin me-1"></i> Fetching available credits…
                    </div>

                    <!-- State 3: No balance -->
                    <div id="rpAdvEmpty" class="rp-adv-empty" style="display:none;">
                        <i class="bx bx-info-circle me-1"></i> No credits available for this customer.
                    </div>

                    <!-- State 4: Radio list -->
                    <div id="rpAdvSources" style="display:none;">
                        <div class="rp-section-header mb-1">
                            <i class="bx bx-transfer rp-section-icon rp-section-icon--green"></i>
                            <span class="rp-section-label rp-section-label--green">Select Credit to Apply</span>
                        </div>
                        <div id="rpAdvRadioList" class="rp-adv-radio-list"></div>
                        <div class="rp-adv-cancel-row">
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="rpAdvCancelBtn">
                                <i class="bx bx-x me-1"></i>Don't apply credit
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Debit Note Credits Section (vendor / purchase side) -->
                <div id="rpDebitNoteSection" class="rp-advance-section" style="display:none;">

                    <!-- State 1: Prompt -->
                    <div id="rpDnPrompt" class="rp-adv-prompt">
                        <i class="bx bx-transfer-alt rp-adv-prompt-icon"></i>
                        <span class="rp-adv-prompt-text">Debit note credits may be available for this vendor.</span>
                        <button type="button" class="btn btn-sm rp-adv-check-btn" id="rpDnCheckBtn">
                            Check &amp; Apply
                        </button>
                    </div>

                    <!-- State 2: Loading -->
                    <div id="rpDnLoading" class="rp-adv-loading" style="display:none;">
                        <i class="bx bx-loader-alt bx-spin me-1"></i> Fetching debit notes…
                    </div>

                    <!-- State 3: No balance -->
                    <div id="rpDnEmpty" class="rp-adv-empty" style="display:none;">
                        <i class="bx bx-info-circle me-1"></i> No debit note credits available for this vendor.
                    </div>

                    <!-- State 4: Radio list -->
                    <div id="rpDnSources" style="display:none;">
                        <div class="rp-section-header mb-1">
                            <i class="bx bx-transfer rp-section-icon rp-section-icon--green"></i>
                            <span class="rp-section-label rp-section-label--green">Select Debit Note to Apply</span>
                        </div>
                        <div id="rpDnRadioList" class="rp-adv-radio-list"></div>
                        <div class="rp-adv-cancel-row">
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="rpDnCancelBtn">
                                <i class="bx bx-x me-1"></i>Don't apply debit note
                            </button>
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

                        <div class="col-7">
                            <label class="rp-field-label"><span class="text-danger">*</span> Amount</label>
                            <div class="input-group rp-amount-group">
                                <span class="input-group-text bg-white fw-semibold" id="rpCurrencySymbol">₹</span>
                                <input type="number" class="form-control" id="rpAmount" step="any" min="0.01" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-5">
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
                            <label class="rp-field-label">Attachments</label>
                            <div id="payAttachZone" class="prod-attach-zone" onclick="_attachZoneTrigger('Payment', event)">
                                <div id="payAttachEmpty" class="prod-attach-empty">
                                    <i class="bx bx-upload" id="payAttachIcon" style="font-size:1.4rem;color:#9ca3af;display:block;margin-bottom:3px;"></i>
                                    <div id="payAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop files here</div>
                                </div>
                            </div>
                            <div id="payAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                            <div id="payAttachHint" style="font-size:.7rem;color:#9ca3af;margin-top:4px;"></div>
                            <input type="file" id="payAttachInput" multiple style="display:none;">
                        </div>

                    </div>
                </div>

                </div><!-- /.rp-scroll-body -->

                <!-- Footer -->
                <div class="rp-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal"><?php echo t('cancel', 'Cancel'); ?></button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnSubmitPayment">
                        <i class="bx bx-check me-1"></i> <?php echo htmlspecialchars($rpBtnLabel); ?>
                    </button>
                </div>

                <input type="hidden" id="rpTransUID"                value="">
                <input type="hidden" id="rpSubmitUrl"              value="">
                <input type="hidden" id="rpPartyUID"               value="">
                <input type="hidden" id="rpAdvanceAmount"          value="">
                <input type="hidden" id="rpExcessSourcePaymentUID" value="">
                <input type="hidden" id="rpOnAccountAmount"          value="">
                <input type="hidden" id="rpOnAccountSourcePaymentUID" value="">
                <input type="hidden" id="rpVendorUID"       value="">
                <input type="hidden" id="rpDebitNoteUID"    value="">
                <input type="hidden" id="rpDebitNoteAmount" value="">

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

    var _payTypes        = [];
    var _bankAccts       = [];
    var _fpInstance      = null;
    var _currency        = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var _rpDec           = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    var _creditSources    = [];
    var _totalCredit      = 0;
    var _advanceApplied   = false;
    var _rpOrigBalanceDue = 0;
    var _dnSources        = [];
    var _dnApplied        = false;

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

    function _rpFmt(v) { return _currency + ' ' + parseFloat(v || 0).toFixed(_rpDec); }

    // Switch the advance section between named states
    function _rpAdvState(state) {
        $('#rpAdvPrompt, #rpAdvLoading, #rpAdvEmpty, #rpAdvSources').hide();
        if (state === 'prompt')  { $('#rpAdvPrompt').show(); }
        if (state === 'loading') { $('#rpAdvLoading').show(); }
        if (state === 'empty')   { $('#rpAdvEmpty').show(); }
        if (state === 'sources') { $('#rpAdvSources').show(); }
    }

    function _rpBuildRadioList() {
        var $list = $('#rpAdvRadioList').empty();
        $.each(_creditSources, function (i, s) {
            var creditAmt    = parseFloat(s.CreditAmount);
            var creditType   = s.CreditType || 'advance';
            var disabled     = creditAmt > _rpOrigBalanceDue;
            var uid          = 'rpAdvRadio_' + s.PaymentUID;
            var typeLabel    = creditType === 'on_account' ? 'On Account' : 'Advance Credit';
            var typeBadgeCls = creditType === 'on_account' ? 'rp-adv-type-badge rp-adv-type-badge--oa' : 'rp-adv-type-badge rp-adv-type-badge--adv';
            var refLabel     = s.InvoiceNumber ? _rpEsc(s.InvoiceNumber) : ('Payment #' + s.PaymentUID);
            var $row         = $(
                '<label class="rp-adv-radio-row' + (disabled ? ' rp-adv-radio-row--disabled' : '') + '" for="' + uid + '">' +
                    '<input type="radio" id="' + uid + '" name="rpAdvRadio" value="' + s.PaymentUID + '"' +
                        ' data-credit-amount="' + creditAmt + '"' +
                        ' data-credit-type="' + creditType + '"' +
                        (disabled ? ' disabled' : '') +
                    '>' +
                    '<div class="rp-adv-radio-body">' +
                        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                            '<span class="rp-adv-radio-label">' + refLabel + '</span>' +
                            '<span class="' + typeBadgeCls + '">' + typeLabel + '</span>' +
                        '</div>' +
                        '<span class="rp-adv-radio-amount">' + _rpFmt(creditAmt) + ' available</span>' +
                        (disabled ? '<span class="rp-adv-radio-badge">Exceeds balance due</span>' : '') +
                    '</div>' +
                '</label>'
            );
            $list.append($row);
        });
    }

    function _rpClearAdvanceSelection() {
        _advanceApplied = false;
        $('#rpAdvanceAmount').val('');
        $('#rpExcessSourcePaymentUID').val('');
        $('#rpOnAccountAmount').val('');
        $('#rpOnAccountSourcePaymentUID').val('');
        $('input[name="rpAdvRadio"]').prop('checked', false);
        $('#rpAmount')
            .removeAttr('readonly')
            .attr('max', _rpOrigBalanceDue)
            .val(_rpOrigBalanceDue.toFixed(_rpDec));
    }

    function _rpDnState(state) {
        $('#rpDnPrompt, #rpDnLoading, #rpDnEmpty, #rpDnSources').hide();
        if (state === 'prompt')  { $('#rpDnPrompt').show(); }
        if (state === 'loading') { $('#rpDnLoading').show(); }
        if (state === 'empty')   { $('#rpDnEmpty').show(); }
        if (state === 'sources') { $('#rpDnSources').show(); }
    }

    function _rpBuildDnList() {
        var $list = $('#rpDnRadioList').empty();
        $.each(_dnSources, function (i, dn) {
            var dnAmt    = parseFloat(dn.Amount);
            var disabled = dnAmt > _rpOrigBalanceDue;
            var uid      = 'rpDnRadio_' + dn.DebitNoteUID;
            var srcNum   = dn.SourceTransNumber || ('DN #' + dn.DebitNoteUID);
            var $row = $(
                '<label class="rp-adv-radio-row' + (disabled ? ' rp-adv-radio-row--disabled' : '') + '" for="' + uid + '">' +
                    '<input type="radio" id="' + uid + '" name="rpDnRadio" value="' + dn.DebitNoteUID + '"' +
                        ' data-dn-amount="' + dnAmt + '"' +
                        (disabled ? ' disabled' : '') +
                    '>' +
                    '<div class="rp-adv-radio-body">' +
                        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                            '<span class="rp-adv-radio-label">' + _rpEsc(srcNum) + '</span>' +
                            '<span class="rp-adv-type-badge rp-adv-type-badge--dn">Debit Note</span>' +
                        '</div>' +
                        '<span class="rp-adv-radio-amount">' + _rpFmt(dnAmt) + ' available</span>' +
                        (disabled ? '<span class="rp-adv-radio-badge">Exceeds balance due</span>' : '') +
                    '</div>' +
                '</label>'
            );
            $list.append($row);
        });
    }

    function _rpClearDnSelection() {
        _dnApplied = false;
        $('#rpDebitNoteUID').val('');
        $('#rpDebitNoteAmount').val('');
        $('input[name="rpDnRadio"]').prop('checked', false);
        $('#rpAmount')
            .removeAttr('readonly')
            .attr('max', _rpOrigBalanceDue)
            .val(_rpOrigBalanceDue.toFixed(_rpDec));
    }

    function _resetDebitNote() {
        _dnSources = [];
        _dnApplied = false;
        $('#rpDebitNoteSection').hide();
        $('#rpDebitNoteUID').val('');
        $('#rpDebitNoteAmount').val('');
        _rpDnState('prompt');
    }

    function _resetAdvance() {
        _creditSources  = [];
        _totalCredit    = 0;
        _advanceApplied = false;
        $('#rpAdvanceSection').hide();
        $('#rpAdvanceAmount').val('');
        $('#rpExcessSourcePaymentUID').val('');
        $('#rpOnAccountAmount').val('');
        $('#rpOnAccountSourcePaymentUID').val('');
        _rpAdvState('prompt');
    }

    // Expose open-modal helper for all modules
    window.rpOpenModal = function (cfg) {
        $('#rpTransUID').val(cfg.transUID   || 0);
        $('#rpSubmitUrl').val(cfg.submitUrl || '');
        $('#rpPartyUID').val(cfg.partyUID   || 0);
        $('#rpVendorUID').val(cfg.vendorUID || 0);
        $('#rpDocNum').text(cfg.docNum || '—');
        $('#rpDocDate').text(cfg.docDate || '—');
        if (cfg.partyName) {
            $('#rpPartyName').text(cfg.partyName);
            $('#rpPartyRow').show();
        } else {
            $('#rpPartyRow').hide();
        }

        _rpOrigBalanceDue = parseFloat(cfg.pending || 0);
        $('#rpTotalCard').text(_rpFmt(cfg.total));
        $('#rpPaidCard').text(_rpFmt(cfg.paid));
        $('#rpBalanceCard').text(_rpFmt(cfg.pending));
        $('#rpAmount').val(_rpOrigBalanceDue.toFixed(_rpDec)).attr('max', _rpOrigBalanceDue).removeAttr('readonly');
        $('#rpCurrencySymbol').text(_currency);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');

        _resetAdvance();
        _resetDebitNote();

        // Show advance prompt for customer invoices (no AJAX yet — user must click "Check & Apply")
        var partyUID = parseInt(cfg.partyUID, 10) || 0;
        if (partyUID > 0) {
            _rpAdvState('prompt');
            $('#rpAdvanceSection').show();
        }

        // Show debit note prompt for vendor purchases
        var vendorUID = parseInt(cfg.vendorUID, 10) || 0;
        if (vendorUID > 0) {
            _rpDnState('prompt');
            $('#rpDebitNoteSection').show();
        }

        if (typeof _attachResetState === 'function') { _attachResetState('Payment'); }
        _renderPaymentTypes();
        new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
    };

    // All jQuery-dependent event bindings are deferred until DOMContentLoaded
    // because jQuery is loaded in the footer (after this script runs).
    document.addEventListener('DOMContentLoaded', function () {

        // Reset advance/debit-note state when modal is fully hidden
        $('#recordPaymentModal').on('hidden.bs.modal', function () {
            _resetAdvance();
            _resetDebitNote();
        });

        // Init flatpickr and dropzone when modal first opens; reset date on each open
        $('#recordPaymentModal').on('shown.bs.modal', function () {
            if (!_fpInstance) {
                _fpInstance = flatpickr('#rpPaymentDate', {
                    dateFormat   : 'Y-m-d',
                    altInput     : true,
                    altFormat    : _transFormDateFormat,
                    maxDate      : 'today',
                    disableMobile: true,
                    defaultDate  : 'today',
                });
            } else {
                _fpInstance.setDate(new Date(), false);
            }
            if (typeof _attachInit === 'function') { _attachInit('Payment'); }
        });

        // Available credits: "Check & Apply" — use cached data on second click, AJAX only on first
        $(document).on('click', '#rpAdvCheckBtn', function () {
            var partyUID = parseInt($('#rpPartyUID').val(), 10);
            if (!partyUID) return;
            if (_creditSources.length > 0) {
                _rpBuildRadioList();
                _rpAdvState('sources');
                return;
            }
            _rpAdvState('loading');
            $.ajax({
                url      : '/payments/getCustomerExcessBalance',
                method   : 'GET',
                data     : { PartyUID: partyUID },
                dataType : 'json',
                success  : function (resp) {
                    if (resp.Error || !resp.Sources || !resp.Sources.length || parseFloat(resp.TotalCredit || 0) <= 0) {
                        _rpAdvState('empty');
                        return;
                    }
                    _creditSources = resp.Sources;
                    _totalCredit   = parseFloat(resp.TotalCredit);
                    _rpBuildRadioList();
                    _rpAdvState('sources');
                },
                error: function () { _rpAdvState('empty'); }
            });
        });

        // Credit selection — branch on type (advance vs on-account)
        $(document).on('change', 'input[name="rpAdvRadio"]', function () {
            var creditAmt  = parseFloat($(this).data('credit-amount')) || 0;
            var creditType = $(this).data('credit-type') || 'advance';
            var srcUID     = parseInt($(this).val(), 10) || 0;
            var newMax     = Math.max(0, Math.round((_rpOrigBalanceDue - creditAmt) * Math.pow(10, _rpDec)) / Math.pow(10, _rpDec));

            _advanceApplied = true;

            // Clear both credit slots before setting the selected one
            $('#rpAdvanceAmount').val('');
            $('#rpExcessSourcePaymentUID').val('');
            $('#rpOnAccountAmount').val('');
            $('#rpOnAccountSourcePaymentUID').val('');

            if (creditType === 'on_account') {
                $('#rpOnAccountAmount').val(creditAmt.toFixed(_rpDec));
                $('#rpOnAccountSourcePaymentUID').val(srcUID);
            } else {
                $('#rpAdvanceAmount').val(creditAmt.toFixed(_rpDec));
                $('#rpExcessSourcePaymentUID').val(srcUID);
            }

            var $amt = $('#rpAmount').attr('max', newMax).val(newMax.toFixed(_rpDec));
            if (newMax === 0) {
                $amt.attr('readonly', true).val('0');
            } else {
                $amt.removeAttr('readonly');
            }
        });

        // Guard: readonly Amount field must stay 0 even if user tries to type
        $(document).on('input', '#rpAmount', function () {
            if ($(this).is('[readonly]')) { $(this).val('0'); }
        });

        // Advance credit: "Don't apply advance" — back to prompt, clear selection
        $(document).on('click', '#rpAdvCancelBtn', function () {
            _rpClearAdvanceSelection();
            _rpAdvState('prompt');
        });

        // Debit note: "Check & Apply" — fetch from backend (cached after first load)
        $(document).on('click', '#rpDnCheckBtn', function () {
            var vendorUID = parseInt($('#rpVendorUID').val(), 10);
            if (!vendorUID) return;
            if (_dnSources.length > 0) {
                _rpBuildDnList();
                _rpDnState('sources');
                return;
            }
            _rpDnState('loading');
            $.ajax({
                url      : '/purchases/getVendorDebitNotes',
                method   : 'GET',
                data     : { VendorUID: vendorUID },
                dataType : 'json',
                success  : function (resp) {
                    if (resp.Error || !resp.Data || !resp.Data.length) {
                        _rpDnState('empty');
                        return;
                    }
                    _dnSources = resp.Data;
                    _rpBuildDnList();
                    _rpDnState('sources');
                },
                error: function () { _rpDnState('empty'); }
            });
        });

        // Debit note radio selection
        $(document).on('change', 'input[name="rpDnRadio"]', function () {
            var dnAmt  = parseFloat($(this).data('dn-amount')) || 0;
            var dnUID  = parseInt($(this).val(), 10) || 0;
            var newMax = Math.max(0, Math.round((_rpOrigBalanceDue - dnAmt) * Math.pow(10, _rpDec)) / Math.pow(10, _rpDec));
            _dnApplied = true;
            $('#rpDebitNoteUID').val(dnUID);
            $('#rpDebitNoteAmount').val(dnAmt.toFixed(_rpDec));
            var $amt = $('#rpAmount').attr('max', newMax).val(newMax.toFixed(_rpDec));
            if (newMax === 0) {
                $amt.attr('readonly', true).val('0');
            } else {
                $amt.removeAttr('readonly');
            }
        });

        // Debit note: "Don't apply" — back to prompt, clear selection
        $(document).on('click', '#rpDnCancelBtn', function () {
            _rpClearDnSelection();
            _rpDnState('prompt');
        });

        // Payment type pill toggle
        // On blur: cap to pending max then format with smartDecimal
        $(document).on('blur', '#rpAmount', function () {
            var max = parseFloat($(this).attr('max')) || 0;
            var val = parseFloat($(this).val())       || 0;
            if (max > 0 && val > max) { val = max; }
            if (val > 0) {
                var formatted = (typeof smartDecimal === 'function')
                    ? smartDecimal(val, _rpDec, true)
                    : val.toFixed(_rpDec);
                $(this).val(formatted);
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
            var transUID               = parseInt($('#rpTransUID').val(), 10);
            var paymentTypeUID         = parseInt($('#rpPaymentTypeUID').val(), 10);
            var amount                 = parseFloat($('#rpAmount').val()) || 0;
            var advanceAmount          = _advanceApplied ? (parseFloat($('#rpAdvanceAmount').val()) || 0) : 0;
            var excessSourcePaymentUID = _advanceApplied ? (parseInt($('#rpExcessSourcePaymentUID').val(), 10) || 0) : 0;
            var onAccountAmount        = _advanceApplied ? (parseFloat($('#rpOnAccountAmount').val()) || 0) : 0;
            var onAccountSourceUID     = _advanceApplied ? (parseInt($('#rpOnAccountSourcePaymentUID').val(), 10) || 0) : 0;
            var debitNoteUID           = _dnApplied ? (parseInt($('#rpDebitNoteUID').val(), 10) || 0) : 0;
            var debitNoteAmount        = _dnApplied ? (parseFloat($('#rpDebitNoteAmount').val()) || 0) : 0;
            var paymentDate            = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
            var bankAccountUID         = parseInt($('#rpBankAccount').val(), 10) || 0;
            var referenceNo            = $.trim($('#rpReferenceNo').val());
            var notes                  = $.trim($('#rpNotes').val());
            var submitUrl              = $('#rpSubmitUrl').val();
            var maxAmount              = parseFloat($('#rpAmount').attr('max')) || 0;

            if (!transUID) { Swal.fire({ icon: 'warning', text: 'Invalid record.' }); return; }

            if (amount <= 0 && advanceAmount <= 0 && onAccountAmount <= 0 && debitNoteAmount <= 0) {
                Swal.fire({ icon: 'warning', text: 'Enter a payment amount or select a credit to apply.' });
                return;
            }
            if (amount > 0 && !paymentTypeUID) {
                Swal.fire({ icon: 'warning', text: 'Please select a payment type.' });
                return;
            }
            if (amount > 0 && maxAmount > 0 && amount > maxAmount) {
                Swal.fire({ icon: 'warning', text: 'Amount cannot exceed the balance due (' + _currency + ' ' + maxAmount.toFixed(_rpDec) + ').' });
                $('#rpAmount').val(maxAmount.toFixed(_rpDec)).focus();
                return;
            }
            if (_advanceApplied && advanceAmount <= 0 && onAccountAmount <= 0) {
                Swal.fire({ icon: 'warning', text: 'Please select a credit source to apply.' });
                return;
            }
            if (_advanceApplied && advanceAmount > 0 && !excessSourcePaymentUID) {
                Swal.fire({ icon: 'warning', text: 'Please select the source payment for the advance credit.' });
                return;
            }
            if (_advanceApplied && onAccountAmount > 0 && !onAccountSourceUID) {
                Swal.fire({ icon: 'warning', text: 'Please select the source payment for the on-account credit.' });
                return;
            }
            var isCash = parseInt($('#rpIsCash').val(), 10);
            if (amount > 0 && !isCash && !bankAccountUID) { Swal.fire({ icon: 'warning', text: 'Please select a bank account.' }); return; }
            if (!submitUrl) { Swal.fire({ icon: 'warning', text: 'Configuration error — please refresh.' }); return; }

            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');

            var fd = new FormData();
            fd.append('TransUID',                 transUID);
            fd.append('PaymentTypeUID',           paymentTypeUID || 0);
            fd.append('Amount',                   amount);
            fd.append('AdvanceAmount',            advanceAmount);
            fd.append('ExcessSourcePaymentUID',   excessSourcePaymentUID || 0);
            fd.append('OnAccountAmount',          onAccountAmount);
            fd.append('OnAccountSourcePaymentUID', onAccountSourceUID || 0);
            fd.append('DebitNoteUID',             debitNoteUID    || 0);
            fd.append('DebitNoteAmount',          debitNoteAmount || 0);
            fd.append('PaymentDate',              paymentDate);
            fd.append('BankAccountUID',           bankAccountUID || '');
            fd.append('ReferenceNo',              referenceNo);
            fd.append('Notes',                    notes);
            fd.append(CsrfName, CsrfToken);
            (_attachState && _attachState['Payment'] ? (_attachState['Payment'].newFiles || []) : []).forEach(function (f) { fd.append('PaymentFiles[]', f, f.name); });

            if (typeof window.rpBeforeSend === 'function') { window.rpBeforeSend(fd); }

            $.ajax({
                url         : submitUrl,
                method      : 'POST',
                data        : fd,
                processData : false,
                contentType : false,
                success: function (resp) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                    if (resp.Error) {
                        if (resp.ErrorCode === 1001) {
                            // Credit already consumed by another user — clear cache, back to prompt
                            _creditSources = [];
                            _rpClearAdvanceSelection();
                            _rpAdvState('prompt');
                            showToastNotification(resp.Message, 'error');
                        } else if (resp.ErrorCode === 1002) {
                            // Invoice already fully paid by another user
                            Swal.fire({ icon: 'info', title: 'Already Paid', text: resp.Message });
                        } else {
                            showToastNotification(resp.Message, 'error');
                        }
                    } else {
                        var _rpModalInst = bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal'));
                        if (_rpModalInst) _rpModalInst.hide();
                        if (typeof _attachResetState === 'function') { _attachResetState('Payment'); }
                        showToastNotification(resp.Message, 'success');
                        hideUIBlock();
                        ajaxLoading(0);
                        if (typeof window.rpAfterSuccess === 'function') window.rpAfterSuccess(resp);
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
