<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'General Ledger',
                    'pageDescription' => 'View account-wise transaction history with running balance',
                    'pageIcon'        => 'bx-book-open',
                    'pageIconBg'      => '#ede9ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ───────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Ledger Account <span class="text-danger">*</span></label>
                                    <select id="glLedgerUID" class="form-select form-select-sm">
                                        <option value="">— Select Account —</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">From Date</label>
                                    <input type="text" id="glDateFrom" class="form-control form-control-sm" placeholder="Select date">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">To Date</label>
                                    <input type="text" id="glDateTo" class="form-control form-control-sm" placeholder="Select date">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm w-100" id="btnViewLedger">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="glSpinner"></span>
                                        <i class="bx bx-search me-1" id="glIcon"></i>View Ledger
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-sm w-100 d-none" id="btnGlPrint">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Stats Strip (hidden until ledger loaded) ─────── -->
                    <div id="glStatsWrap" class="d-none mb-3">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Opening Balance</div>
                                    <div class="fw-bold mt-1" id="glStatOpening" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Total Debits</div>
                                    <div class="fw-bold mt-1 text-success" id="glStatDebit" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Total Credits</div>
                                    <div class="fw-bold mt-1 text-danger" id="glStatCredit" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Closing Balance</div>
                                    <div class="fw-bold mt-1" id="glStatClosing" style="font-size:1.1rem;color:#7c3aed;">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Statement Table ───────────────────────────────── -->
                    <div class="card" id="glResultCard" style="display:none;">
                        <div class="card-header d-flex align-items-center justify-content-between py-2">
                            <span class="fw-semibold" id="glCardTitle" style="font-size:var(--r2k-text-88);">Ledger Statement</span>
                            <span class="text-muted" id="glCardPeriod" style="font-size:.76rem;"></span>
                        </div>
                        <div class="table-responsive" id="glStatementWrap">
                        </div>
                    </div>

                    <!-- ── Empty state (before search) ──────────────────── -->
                    <div id="glEmptyState" class="text-center py-5">
                        <i class="bx bx-book-open d-block mx-auto mb-3" style="font-size:4rem;color:#d0d0e8;"></i>
                        <p class="text-muted" style="font-size:var(--r2k-text-88);">Select a ledger account and date range, then click <strong>View Ledger</strong></p>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
/* global $, global_services_url, JwtData, currencySymbol, showToastNotification, transDatePickr */
(function () {
    'use strict';

    var _cur     = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _dateFmt = (typeof _transListDateFormat !== 'undefined') ? _transListDateFormat : 'd-m-Y';

    /**
     * @returns {void}
     */
    function _loadLedgerOptions() {
        $.ajax({
            url:      global_services_url + 'api/accounting/getGeneralLedgerPageData',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) {
                if (r.Error || !r.Data) return;
                var $sel    = $('#glLedgerUID');
                var grouped = {};
                $.each(r.Data || [], function (i, l) {
                    var t = l.LedgerType || 'Other';
                    if (!grouped[t]) grouped[t] = [];
                    grouped[t].push(l);
                });
                $.each(grouped, function (type, items) {
                    var $grp = $('<optgroup>').attr('label', type);
                    $.each(items, function (i, l) {
                        $grp.append($('<option>').val(l.LedgerUID)
                            .attr('data-ob', l.OpeningBalance || 0)
                            .attr('data-obtype', l.OpeningBalanceType || 'Debit')
                            .text(l.LedgerCode + ' — ' + l.LedgerName));
                    });
                    $sel.append($grp);
                });
            },
        });
    }

    // Init date pickers
    if (typeof transDatePickr === 'function') {
        transDatePickr('#glDateFrom', false, 'Y-m-d', false, false, false, true, _dateFmt);
        transDatePickr('#glDateTo',   false, 'Y-m-d', false, false, true,  true, _dateFmt);
    }

    /**
     * @param {string|number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) { return $('<span>').text(String(s || '')).html(); }

    /**
     * @param {object} d  NestJS getLedgerStatementAjax response
     * @returns {string}
     */
    function _glBuildHtml(d) {
        var lines = d.Lines || [];
        var html = '<table class="table table-sm table-bordered align-middle mb-0" style="font-size:var(--r2k-text-82);">'
            + '<thead class="r2k-thead"><tr>'
            + '<th style="width:100px;">Date</th>'
            + '<th style="width:120px;">Journal #</th>'
            + '<th style="width:110px;">Reference</th>'
            + '<th>Particulars</th>'
            + '<th class="text-end" style="width:120px;">Debit</th>'
            + '<th class="text-end" style="width:120px;">Credit</th>'
            + '<th class="text-end" style="width:130px;">Balance</th>'
            + '</tr></thead><tbody>';

        // Opening balance row
        html += '<tr style="background:#f8f5ff;">'
            + '<td colspan="4" class="fw-semibold" style="color:#7c3aed;">Opening Balance'
            + (d.DateFrom ? ' <span class="text-muted fw-normal" style="font-size:var(--r2k-text-75);">as of ' + _esc(d.DateFrom) + '</span>' : '')
            + '</td>'
            + '<td class="text-end">—</td>'
            + '<td class="text-end">—</td>'
            + '<td class="text-end fw-semibold" style="color:#7c3aed;">' + _fmt(d.OpeningBalance)
            + '<div style="font-size:var(--r2k-text-xs);color:#8b8bcc;">' + _esc(d.OpeningType || '') + '</div></td>'
            + '</tr>';

        if (!lines.length) {
            html += '<tr><td colspan="7" class="text-center text-muted py-4">No transactions in this period.</td></tr>';
        } else {
            $.each(lines, function (i, ln) {
                var isDr        = ln.TransactionType === 'Debit';
                var balClass    = (ln.RunningBalanceType || 'Debit') === 'Debit' ? 'text-success' : 'text-danger';
                var particulars = ln.Particulars || ln.Narration || '';
                var showNarr    = ln.Narration && ln.Narration !== ln.Particulars;
                html += '<tr>'
                    + '<td style="white-space:nowrap;">' + _esc(ln.JournalDate || '') + '</td>'
                    + '<td><code style="font-size:var(--r2k-text-75);color:#7c3aed;">' + _esc(ln.JournalNo || '') + '</code></td>'
                    + '<td class="text-muted" style="font-size:.76rem;">' + _esc(ln.ReferenceType || '')
                    + (ln.ReferenceNo ? '<span class="d-block">' + _esc(ln.ReferenceNo) + '</span>' : '') + '</td>'
                    + '<td><div>' + _esc(particulars) + '</div>'
                    + (showNarr ? '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _esc(ln.Narration) + '</div>' : '')
                    + '</td>'
                    + '<td class="text-end text-success fw-semibold">' + (isDr  ? _fmt(ln.Amount) : '—') + '</td>'
                    + '<td class="text-end text-danger fw-semibold">'  + (!isDr ? _fmt(ln.Amount) : '—') + '</td>'
                    + '<td class="text-end fw-semibold ' + balClass + '">' + _fmt(ln.RunningBalance)
                    + '<div style="font-size:var(--r2k-text-xs);font-weight:400;opacity:.75;">' + _esc(ln.RunningBalanceType || '') + '</div></td>'
                    + '</tr>';
            });
        }

        // Totals row
        html += '<tr style="background:#f8f5ff;font-weight:600;">'
            + '<td colspan="4" class="text-end" style="color:#566a7f;font-size:var(--r2k-text-md);">Period Totals</td>'
            + '<td class="text-end text-success">' + _fmt(d.TotalDebit)  + '</td>'
            + '<td class="text-end text-danger">'  + _fmt(d.TotalCredit) + '</td>'
            + '<td class="text-end" style="color:#7c3aed;">' + _fmt(d.ClosingBalance)
            + '<div style="font-size:var(--r2k-text-xs);font-weight:400;color:#8b8bcc;">' + _esc(d.ClosingType || '') + '</div></td>'
            + '</tr></tbody></table>';

        return html;
    }

    $('#btnViewLedger').on('click', function () {
        var uid      = $('#glLedgerUID').val();
        var dateFrom = $('#glDateFrom').val();
        var dateTo   = $('#glDateTo').val();

        if (!uid) { showToastNotification('Please select a ledger account.', 'warning'); return; }

        var $btn = $(this).prop('disabled', true);
        $('#glSpinner').removeClass('d-none'); $('#glIcon').addClass('d-none');
        $('#glEmptyState').hide();
        $('#glResultCard').hide();
        $('#glStatsWrap').addClass('d-none');

        $.ajax({
            url:      global_services_url + 'api/accounting/getLedgerStatementAjax',
            method:   'GET',
            data:     { LedgerUID: uid, DateFrom: dateFrom, DateTo: dateTo, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) {
                $btn.prop('disabled', false);
                $('#glSpinner').addClass('d-none'); $('#glIcon').removeClass('d-none');

                if (r.Error) { showToastNotification(r.Message, 'error'); $('#glEmptyState').show(); return; }

                // Stats
                var obLabel = _fmt(r.OpeningBalance) + ' ' + (r.OpeningType || '');
                var clLabel = _fmt(r.ClosingBalance) + ' ' + (r.ClosingType || '');
                $('#glStatOpening').text(obLabel);
                $('#glStatDebit').text(_fmt(r.TotalDebit));
                $('#glStatCredit').text(_fmt(r.TotalCredit));
                $('#glStatClosing').text(clLabel);
                $('#glStatsWrap').removeClass('d-none');

                // Card header
                $('#glCardTitle').text(r.LedgerName || 'Ledger Statement');
                var period = '';
                if (dateFrom || dateTo) period = (dateFrom || '…') + '  →  ' + (dateTo || '…');
                $('#glCardPeriod').text(period);

                // Statement
                $('#glStatementWrap').html(_glBuildHtml(r));
                $('#glResultCard').show();
                $('#btnGlPrint').removeClass('d-none');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#glSpinner').addClass('d-none'); $('#glIcon').removeClass('d-none');
                showToastNotification('Request failed.', 'error');
                $('#glEmptyState').show();
            },
        });
    });

    $(function () { _loadLedgerOptions(); });

    // Print
    $('#btnGlPrint').on('click', function () {
        var $tbl = $('#glStatementWrap').clone();
        var win  = window.open('', '_blank');
        win.document.write('<html><head><title>General Ledger</title>');
        win.document.write('<link rel="stylesheet" href="/assets/vendor/css/core.css">');
        win.document.write('</head><body style="padding:24px;">');
        win.document.write('<h4>' + $('#glCardTitle').text() + '</h4>');
        win.document.write('<p style="font-size:var(--r2k-text-md);color:#666;">' + $('#glCardPeriod').text() + '</p>');
        win.document.write($tbl.html());
        win.document.write('</body></html>');
        win.document.close();
        win.print();
    });

}());
</script>
