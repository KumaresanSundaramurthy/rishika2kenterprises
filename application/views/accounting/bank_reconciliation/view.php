<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => t('page_bank_recon',      'Bank Reconciliation'),
                    'pageDescription' => t('page_bank_recon_desc', 'Match your book entries against the bank statement'),
                    'pageIcon'        => 'bx-transfer-alt',
                    'pageIconBg'      => '#e0f2fe',
                    'pageIconColor'   => '#0284c7',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ───────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">
                                        <?php echo t('lbl_bank_account', 'Bank / Cash Account'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <select id="brLedgerUID" class="form-select form-select-sm">
                                        <option value="">— <?php echo t('ph_select_account', 'Select Account'); ?> —</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold"><?php echo t('lbl_from_date', 'From Date'); ?></label>
                                    <input type="text" id="brDateFrom" class="form-control form-control-sm flatpickr-input" placeholder="Select" autocomplete="off" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold"><?php echo t('lbl_to_date', 'To Date'); ?></label>
                                    <input type="text" id="brDateTo" class="form-control form-control-sm flatpickr-input" placeholder="Select" autocomplete="off" readonly>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm w-100" id="btnLoadRecon">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="brSpinner"></span>
                                        <i class="bx bx-search me-1" id="brIcon"></i>
                                        <?php echo t('btn_load', 'Load'); ?>
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-label-secondary btn-sm w-100 d-none" id="btnSaveRecon">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="brSaveSpinner"></span>
                                        <i class="bx bx-save me-1" id="brSaveIcon"></i>
                                        <?php echo t('btn_save_recon', 'Save Status'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards (hidden until loaded) ───────────── -->
                    <div id="brSummaryWrap" class="d-none mb-3">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3 h-100">
                                    <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.3px;"><?php echo t('lbl_opening_bal', 'Opening Balance'); ?></div>
                                    <div class="fw-bold mt-1" id="brStatOpening" style="font-size:1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3 h-100">
                                    <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.3px;"><?php echo t('lbl_book_balance', 'Book Closing Balance'); ?></div>
                                    <div class="fw-bold mt-1" id="brStatBook" style="font-size:1rem;color:#7c3aed;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3 h-100">
                                    <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.3px;"><?php echo t('lbl_cleared_bal', 'Cleared Balance'); ?></div>
                                    <div class="fw-bold mt-1 text-success" id="brStatCleared" style="font-size:1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card py-3 h-100">
                                    <div class="text-muted text-center" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.3px;"><?php echo t('lbl_bank_stmt_bal', 'Bank Statement Balance'); ?></div>
                                    <div class="d-flex align-items-center justify-content-center gap-2 mt-1 px-3">
                                        <span class="text-muted fw-semibold" style="font-size:var(--r2k-text-85);" id="brCurSpan">₹</span>
                                        <input type="number" id="brStmtBalance" class="form-control form-control-sm text-end" step="0.01" placeholder="0.00" style="max-width:130px;">
                                    </div>
                                    <div class="text-center mt-1" id="brDiffWrap" style="display:none;">
                                        <span id="brDiffBadge" class="badge" style="font-size:var(--r2k-text-75);"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Reconciliation Table ──────────────────────────── -->
                    <div class="card d-none" id="brResultCard">
                        <div class="card-header d-flex align-items-center justify-content-between py-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fw-semibold" id="brCardTitle" style="font-size:var(--r2k-text-88);">Transactions</span>
                                <span class="badge bg-label-secondary" id="brEntryCount"></span>
                            </div>
                            <div class="d-flex align-items-center gap-3" style="font-size:var(--r2k-text-78);">
                                <label class="d-flex align-items-center gap-1 mb-0">
                                    <input type="checkbox" id="brSelectAll" class="form-check-input">
                                    <span><?php echo t('lbl_select_all', 'Select All'); ?></span>
                                </label>
                                <span class="text-muted" id="brClearedSummary"></span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:44px;" class="text-center"><?php echo t('th_cleared', 'Cleared'); ?></th>
                                        <th style="width:100px;"><?php echo t('th_date', 'Date'); ?></th>
                                        <th style="width:140px;"><?php echo t('th_journal', 'Journal #'); ?></th>
                                        <th style="width:120px;"><?php echo t('th_reference', 'Reference'); ?></th>
                                        <th><?php echo t('th_description', 'Description'); ?></th>
                                        <th class="text-end" style="width:130px;"><?php echo t('th_debit', 'Debit (Dr)'); ?></th>
                                        <th class="text-end" style="width:130px;"><?php echo t('th_credit', 'Credit (Cr)'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="brEntriesBody"></tbody>
                            </table>
                        </div>
                        <!-- Cleared totals footer -->
                        <div class="px-3 py-2 border-top d-flex gap-4 flex-wrap" id="brTotalsBar" style="font-size:var(--r2k-text-md);">
                            <span class="text-muted"><?php echo t('lbl_cleared', 'Cleared'); ?>:
                                <strong class="text-success" id="brClDr">—</strong> Dr /
                                <strong class="text-danger" id="brClCr">—</strong> Cr
                            </span>
                            <span class="text-muted"><?php echo t('lbl_uncleared', 'Uncleared'); ?>:
                                <strong class="text-success" id="brUnclDr">—</strong> Dr /
                                <strong class="text-danger" id="brUnclCr">—</strong> Cr
                            </span>
                        </div>
                    </div>

                    <!-- ── Empty state ───────────────────────────────────── -->
                    <div id="brEmptyState" class="text-center py-5">
                        <i class="bx bx-transfer-alt d-block mx-auto mb-3" style="font-size:4rem;color:#bde0fe;"></i>
                        <p class="text-muted" style="font-size:var(--r2k-text-88);">
                            <?php echo t('msg_recon_empty', 'Select a bank account and date range, then click <strong>Load</strong>'); ?>
                        </p>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
/* global $, global_services_url, JwtData, ajaxLoading, currencySymbol, decimalPlaces, showToastNotification, showAlertMessageSwal, t, flatpickr */
(function () {
    'use strict';

    var _cur     = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _dec     = (typeof decimalPlaces  !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;
    var _dateFmt = (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y';

    /**
     * @returns {void}
     */
    function _loadLedgerOptions() {
        $.ajax({
            url:      global_services_url + 'api/accounting/getBankReconPageData',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) {
                if (r.Error || !r.Data) return;
                /* update currency symbol from server */
                if (r.Data.CurrencySymbol) {
                    _cur = r.Data.CurrencySymbol;
                    $('#brCurSpan').text(_cur);
                }
                var $sel    = $('#brLedgerUID');
                var grouped = {};
                $.each(r.Data.Ledgers || [], function (i, l) {
                    var t = l.LedgerType || 'Other';
                    if (!grouped[t]) grouped[t] = [];
                    grouped[t].push(l);
                });
                $.each(grouped, function (type, items) {
                    var $grp = $('<optgroup>').attr('label', type);
                    $.each(items, function (i, l) {
                        $grp.append($('<option>').val(l.LedgerUID)
                            .text(l.LedgerCode + ' — ' + l.LedgerName));
                    });
                    $sel.append($grp);
                });
            },
        });
    }

    // ── State ──────────────────────────────────────────────────────────────
    var _obBalance = 0, _obType = 'Debit';
    var _bookBal   = 0, _bookType = 'Debit';

    // ── Flatpickr ──────────────────────────────────────────────────────────
    var _fpFrom = flatpickr('#brDateFrom', {
        dateFormat: 'Y-m-d', altInput: true, altFormat: _dateFmt,
        static: true, position: 'below left',
        onChange: function (d, s) { if (_fpTo) _fpTo.set('minDate', s || null); }
    });
    var _fpTo = flatpickr('#brDateTo', {
        dateFormat: 'Y-m-d', altInput: true, altFormat: _dateFmt,
        static: true, position: 'below left',
        onChange: function (d, s) { if (_fpFrom) _fpFrom.set('maxDate', s || null); }
    });

    // ── Formatters ─────────────────────────────────────────────────────────
    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN',
            { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmtAbs(n) {
        return parseFloat(n || 0).toLocaleString('en-IN',
            { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    // ── Recompute cleared/uncleared totals from DOM ────────────────────────
    /** @returns {void} */
    function _recomputeTotals() {
        var clDr = 0, clCr = 0, unDr = 0, unCr = 0, clCount = 0, total = 0;

        $('#brEntriesBody tr[data-uid]').each(function () {
            var dr     = parseFloat($(this).data('dr')) || 0;
            var cr     = parseFloat($(this).data('cr')) || 0;
            var isChk  = $(this).find('.recon-chk').prop('checked');
            total++;
            if (isChk) { clDr += dr; clCr += cr; clCount++; }
            else        { unDr += dr; unCr += cr; }
        });

        $('#brClDr').text(_fmtAbs(clDr));
        $('#brClCr').text(_fmtAbs(clCr));
        $('#brUnclDr').text(_fmtAbs(unDr));
        $('#brUnclCr').text(_fmtAbs(unCr));
        $('#brClearedSummary').text(clCount + ' / ' + total + ' cleared');

        // Cleared balance = opening + cleared Dr - cleared Cr
        var clearedNet = (_obType === 'Debit')
            ? _obBalance + clDr - clCr
            : _obBalance - clDr + clCr;
        $('#brStatCleared').text(_fmt(Math.abs(clearedNet)) + ' ' + (clearedNet >= 0 ? _obType : (_obType === 'Debit' ? 'Cr' : 'Dr')));

        _computeDiff();
    }

    /** @returns {void} */
    function _computeDiff() {
        var stmtVal = parseFloat($('#brStmtBalance').val()) || 0;
        if (!stmtVal) { $('#brDiffWrap').hide(); return; }

        var clearedText = $('#brStatCleared').text();
        var clMatch = clearedText.match(/([\d,]+\.?\d*)/);
        var clearedVal = clMatch ? parseFloat(clMatch[1].replace(/,/g, '')) : 0;

        var diff = Math.abs(stmtVal - clearedVal);
        var $badge = $('#brDiffBadge');
        if (diff < 0.01) {
            $badge.removeClass('bg-label-danger').addClass('bg-label-success')
                  .html('<i class="bx bx-check me-1"></i>Balanced ✓');
        } else {
            $badge.removeClass('bg-label-success').addClass('bg-label-danger')
                  .text('Diff: ' + _cur + ' ' + _fmtAbs(diff));
        }
        $('#brDiffWrap').show();
    }

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) { return $('<span>').text(String(s || '')).html(); }

    var _refBadge = {
        'Invoice': 'bg-label-primary', 'Purchase': 'bg-label-warning',
        'Payment-In': 'bg-label-success', 'Payment-Out': 'bg-label-success',
        'Manual': 'bg-label-purple', 'Expense': 'bg-label-warning',
        'IndirectIncome': 'bg-label-success', 'Recurring': 'bg-label-info',
        'Reversal-Invoice': 'bg-label-danger', 'Reversal-Purchase': 'bg-label-danger',
        'Reversal-Manual': 'bg-label-danger',
    };

    /**
     * @param {Array} entries  NestJS getBankReconAjax Entries array
     * @returns {string}
     */
    function _brBuildEntries(entries) {
        if (!entries || !entries.length) {
            return '<tr class="r2k-empty-row"><td colspan="7">'
                + '<div class="r2k-list-empty"><div class="r2k-list-empty-inner">'
                + '<div class="r2k-empty-icon icon-info"><i class="bx bx-bank"></i></div>'
                + '<div class="r2k-empty-title">No unreconciled entries</div>'
                + '<div class="r2k-empty-sub">All transactions have been reconciled, or none match the current filter.</div>'
                + '</div></div></td></tr>';
        }
        var html = '';
        $.each(entries, function (i, en) {
            var isDr   = en.TransactionType === 'Debit';
            var amt    = parseFloat(en.Amount || 0);
            var drAmt  = isDr ? amt : 0;
            var crAmt  = isDr ? 0 : amt;
            var ref    = en.ReferenceType || '';
            var bCls   = _refBadge[ref] || 'bg-label-secondary';
            var uid    = parseInt(en.EntryUID || en.JournalEntryUID || 0, 10);
            var cleared = en.IsReconciled ? true : false;
            var desc   = _esc(en.Particulars || en.Narration || '—');

            html += '<tr data-uid="' + uid + '" data-dr="' + drAmt.toFixed(_dec) + '" data-cr="' + crAmt.toFixed(_dec) + '"'
                + (cleared ? ' class="recon-row-cleared"' : '') + '>'
                + '<td class="text-center" style="width:44px;">'
                + '<input type="checkbox" class="recon-chk form-check-input" value="' + uid + '"' + (cleared ? ' checked' : '') + '>'
                + '</td>'
                + '<td style="white-space:nowrap;font-size:var(--r2k-text-82);">' + _esc(en.JournalDate || '') + '</td>'
                + '<td><code style="font-size:var(--r2k-text-78);color:#7c3aed;">' + _esc(en.JournalNo || '') + '</code></td>'
                + '<td><span class="badge ' + bCls + '" style="font-size:var(--r2k-text-xs);">' + _esc(ref) + '</span>'
                + (en.ReferenceNo ? '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + _esc(en.ReferenceNo) + '</div>' : '')
                + '</td>'
                + '<td class="text-muted" style="font-size:var(--r2k-text-md);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + _esc(en.Narration || '') + '">'
                + desc + '</td>'
                + '<td class="text-end fw-semibold text-success" style="font-size:var(--r2k-text-82);white-space:nowrap;">'
                + (isDr ? (_cur + ' ' + amt.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec })) : '—') + '</td>'
                + '<td class="text-end fw-semibold text-danger" style="font-size:var(--r2k-text-82);white-space:nowrap;">'
                + (!isDr ? (_cur + ' ' + amt.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec })) : '—') + '</td>'
                + '</tr>';
        });
        return html;
    }

    $(function () { _loadLedgerOptions(); });

    // ── Load button ────────────────────────────────────────────────────────
    $('#btnLoadRecon').on('click', function () {
        var uid  = $('#brLedgerUID').val();
        var from = $('#brDateFrom').val();
        var to   = $('#brDateTo').val();
        if (!uid) { showToastNotification(t('alert_select_account', 'Please select a bank account.'), 'warning'); return; }

        var $btn = $(this).prop('disabled', true);
        $('#brSpinner').removeClass('d-none'); $('#brIcon').addClass('d-none');
        $('#brEmptyState').hide();
        $('#brResultCard').addClass('d-none');
        $('#brSummaryWrap').addClass('d-none');
        $('#btnSaveRecon').addClass('d-none');

        $.ajax({
            url:      global_services_url + 'api/accounting/getBankReconAjax',
            method:   'GET',
            data:     { LedgerUID: uid, DateFrom: from, DateTo: to, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (r) {
                $btn.prop('disabled', false);
                $('#brSpinner').addClass('d-none'); $('#brIcon').removeClass('d-none');

                if (r.Error) {
                    showToastNotification(r.Message, 'error');
                    $('#brEmptyState').show();
                    return;
                }

                _obBalance = parseFloat(r.OpeningBalance) || 0;
                _obType    = r.OpeningType || 'Debit';
                _bookBal   = parseFloat(r.BookBalance)   || 0;
                _bookType  = r.BookType || 'Debit';

                $('#brStatOpening').text(_fmt(_obBalance) + ' ' + _obType);
                $('#brStatBook').text(_fmt(_bookBal) + ' ' + _bookType);
                $('#brCardTitle').text(r.LedgerName || 'Transactions');
                $('#brEntryCount').text(r.EntryCount + ' ' + t('lbl_entries', 'entries'));
                $('#brStmtBalance').val('');
                $('#brDiffWrap').hide();

                $('#brEntriesBody').html(_brBuildEntries(r.Entries || []));
                _recomputeTotals();

                $('#brSummaryWrap').removeClass('d-none');
                $('#brResultCard').removeClass('d-none');
                $('#btnSaveRecon').removeClass('d-none');
                $('#brSelectAll').prop('checked', false).prop('indeterminate', false);
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#brSpinner').addClass('d-none'); $('#brIcon').removeClass('d-none');
                showToastNotification(t('toast_load_failed', 'Failed to load entries.'), 'error');
                $('#brEmptyState').show();
            }
        });
    });

    // ── Checkbox interactions ──────────────────────────────────────────────
    $(document).on('change', '.recon-chk', function () {
        var $row = $(this).closest('tr');
        $row.toggleClass('recon-row-cleared', $(this).prop('checked'));
        _recomputeTotals();

        var total   = $('#brEntriesBody .recon-chk').length;
        var checked = $('#brEntriesBody .recon-chk:checked').length;
        $('#brSelectAll')
            .prop('checked', checked === total && total > 0)
            .prop('indeterminate', checked > 0 && checked < total);
    });

    $('#brSelectAll').on('change', function () {
        var chk = $(this).prop('checked');
        $('#brEntriesBody .recon-chk').prop('checked', chk)
            .closest('tr').toggleClass('recon-row-cleared', chk);
        _recomputeTotals();
    });

    // ── Bank statement balance input ───────────────────────────────────────
    $('#brStmtBalance').on('input', _computeDiff);

    // ── Save button ────────────────────────────────────────────────────────
    $('#btnSaveRecon').on('click', function () {
        var cleared   = [];
        var uncleared = [];
        $('#brEntriesBody tr[data-uid]').each(function () {
            var uid  = parseInt($(this).data('uid'));
            var isChk = $(this).find('.recon-chk').prop('checked');
            if (isChk) cleared.push(uid); else uncleared.push(uid);
        });

        if (cleared.length === 0 && uncleared.length === 0) {
            showToastNotification(t('toast_no_entries', 'No entries to save.'), 'warning');
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $('#brSaveSpinner').removeClass('d-none'); $('#brSaveIcon').addClass('d-none');
        ajaxLoading(0);

        $.ajax({
            url:      global_services_url + 'api/accounting/saveBankRecon',
            method:   'POST',
            data:     { 'ClearedUIDs[]': cleared, 'UnclearedUIDs[]': uncleared, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
            dataType: 'json',
            success: function (resp) {
                ajaxLoading(1);
                $btn.prop('disabled', false);
                $('#brSaveSpinner').addClass('d-none'); $('#brSaveIcon').removeClass('d-none');
                if (resp.Error) {
                    showAlertMessageSwal('error', '', resp.Message);
                } else {
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                ajaxLoading(1);
                $btn.prop('disabled', false);
                $('#brSaveSpinner').addClass('d-none'); $('#brSaveIcon').removeClass('d-none');
                showAlertMessageSwal('error', '', t('toast_save_failed', 'Failed to save reconciliation status.'));
            }
        });
    });

}());
</script>
