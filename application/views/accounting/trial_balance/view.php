<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Trial Balance',
                    'pageDescription' => 'Summary of all ledger account balances for a financial year',
                    'pageIcon'        => 'bx-bar-chart-alt-2',
                    'pageIconBg'      => '#ede9ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ──────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Financial Year</label>
                                    <select id="tbFY" class="form-select form-select-sm">
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm w-100" id="btnViewTB">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="tbSpinner"></span>
                                        <i class="bx bx-search me-1" id="tbIcon"></i>Generate
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-sm w-100 d-none" id="btnTBPrint">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                                <div class="col-md-5 text-end" id="tbBalanceAlert" style="display:none!important;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Stats ────────────────────────────────── -->
                    <div id="tbStatsWrap" class="d-none mb-3">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Total Accounts</div>
                                    <div class="fw-bold mt-1" id="tbStatCount" style="font-size:1.2rem;color:#7c3aed;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Total Debits</div>
                                    <div class="fw-bold mt-1 text-success" id="tbStatDr" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Total Credits</div>
                                    <div class="fw-bold mt-1 text-danger" id="tbStatCr" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;letter-spacing:.3px;">Status</div>
                                    <div class="fw-bold mt-1" id="tbStatStatus" style="font-size:1rem;">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Trial Balance Table ──────────────────────────── -->
                    <div class="card d-none" id="tbResultCard">
                        <div class="card-header d-flex align-items-center justify-content-between py-2">
                            <span class="fw-semibold" id="tbCardTitle" style="font-size:var(--r2k-text-88);">Trial Balance</span>
                            <span class="text-muted" id="tbCardPeriod" style="font-size:.76rem;"></span>
                        </div>
                        <div class="table-responsive" id="tbTableWrap"></div>
                    </div>

                    <!-- ── Empty state ──────────────────────────────────── -->
                    <div id="tbEmptyState" class="text-center py-5">
                        <i class="bx bx-bar-chart-alt-2 d-block mx-auto mb-3" style="font-size:4rem;color:#d0d0e8;"></i>
                        <p class="text-muted" style="font-size:var(--r2k-text-88);">Select a financial year and click <strong>Generate</strong></p>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
/* global $, global_services_url, JwtData, currencySymbol, showToastNotification */
(function () {
    'use strict';

    var _cur = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';

    var _typeBadge = {
        'Asset':    'bg-label-primary',   'Liability': 'bg-label-danger',
        'Income':   'bg-label-success',   'Expense':   'bg-label-warning',
        'Customer': 'bg-label-info',      'Vendor':    'bg-label-secondary',
        'Employee': 'bg-label-secondary', 'Bank':      'bg-label-primary',
        'Cash':     'bg-label-success',
    };
    var _typeOrder = ['Asset', 'Liability', 'Income', 'Expense', 'Customer', 'Vendor', 'Employee', 'Bank', 'Cash'];

    /**
     * @param {string|number} n
     * @returns {string}
     */
    function _tbFmt(n) {
        var v = Math.abs(parseFloat(n) || 0);
        if (v < 0.005) return '—';
        return _cur + ' ' + v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * @param {string|number} n
     * @returns {string}
     */
    function _tbFmtAlways(n) {
        return _cur + ' ' + Math.abs(parseFloat(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) { return $('<span>').text(String(s || '')).html(); }

    /**
     * @param {object} d  NestJS getTrialBalanceAjax response
     * @returns {string}
     */
    function _tbBuildHtml(d) {
        var rows = d.Rows || [];
        var grouped = {};
        $.each(rows, function (i, row) {
            var t = row.LedgerType || 'Other';
            if (!grouped[t]) grouped[t] = [];
            grouped[t].push(row);
        });
        var ordered = [];
        $.each(_typeOrder, function (i, t) { if (grouped[t]) ordered.push(t); });
        $.each(Object.keys(grouped), function (i, t) { if (ordered.indexOf(t) < 0) ordered.push(t); });

        var html = '<table class="table table-sm table-bordered align-middle mb-0" style="font-size:var(--r2k-text-82);" id="tbPrintTable">'
            + '<thead class="r2k-thead"><tr>'
            + '<th style="width:44px;">#</th>'
            + '<th style="width:120px;">Code</th>'
            + '<th>Account Name</th>'
            + '<th style="width:90px;">Type</th>'
            + '<th class="text-end" style="width:130px;">Opening (Dr)</th>'
            + '<th class="text-end" style="width:130px;">Opening (Cr)</th>'
            + '<th class="text-end" style="width:130px;">Period Debit</th>'
            + '<th class="text-end" style="width:130px;">Period Credit</th>'
            + '<th class="text-end" style="width:130px;">Closing (Dr)</th>'
            + '<th class="text-end" style="width:130px;">Closing (Cr)</th>'
            + '</tr></thead><tbody>';

        var serial = 0;
        $.each(ordered, function (i, type) {
            var items = grouped[type] || [];
            if (!items.length) return;
            var badge = _typeBadge[type] || 'bg-label-secondary';
            html += '<tr style="background:#f8f5ff;">'
                + '<td colspan="10" style="padding:6px 12px;">'
                + '<span class="badge ' + badge + '" style="font-size:var(--r2k-text-sm);">' + _esc(type) + '</span>'
                + '<span class="text-muted ms-2" style="font-size:var(--r2k-text-sm);">'
                + items.length + ' account' + (items.length > 1 ? 's' : '') + '</span></td></tr>';

            $.each(items, function (j, r) {
                serial++;
                var ob   = parseFloat(r.OpeningBalance) || 0;
                var obDr = r.OpeningBalanceType === 'Debit'  ? ob : 0;
                var obCr = r.OpeningBalanceType === 'Credit' ? ob : 0;
                var cbv  = parseFloat(r.ClosingBalance) || 0;
                var cbDr = r.ClosingBalanceType === 'Debit'  ? cbv : 0;
                var cbCr = r.ClosingBalanceType === 'Credit' ? cbv : 0;
                var dr   = parseFloat(r.PeriodDebit)  || 0;
                var cr   = parseFloat(r.PeriodCredit) || 0;
                var isSys = (r.LedgerCode || '').indexOf('SYS-') === 0;
                var noAct = (dr + cr + ob) < 0.005;
                html += '<tr' + (noAct ? ' class="text-muted"' : '') + '>'
                    + '<td style="font-size:var(--r2k-text-75);color:#a4afc5;">' + serial + '</td>'
                    + '<td><code style="font-size:var(--r2k-text-75);color:#7c3aed;">' + _esc(r.LedgerCode || '') + '</code></td>'
                    + '<td><span class="fw-semibold">' + _esc(r.LedgerName || '') + '</span>'
                    + (isSys ? '<span class="badge bg-label-secondary ms-1" style="font-size:.6rem;">System</span>' : '')
                    + '</td>'
                    + '<td><span class="badge ' + badge + '" style="font-size:var(--r2k-text-xs);">' + _esc(type) + '</span></td>'
                    + '<td class="text-end text-success">' + _tbFmt(obDr) + '</td>'
                    + '<td class="text-end text-danger">'  + _tbFmt(obCr) + '</td>'
                    + '<td class="text-end text-success fw-semibold">' + _tbFmt(dr) + '</td>'
                    + '<td class="text-end text-danger fw-semibold">'  + _tbFmt(cr) + '</td>'
                    + '<td class="text-end text-success">' + _tbFmt(cbDr) + '</td>'
                    + '<td class="text-end text-danger">'  + _tbFmt(cbCr) + '</td>'
                    + '</tr>';
            });
        });

        html += '</tbody><tfoot>'
            + '<tr style="background:#ede9ff;font-weight:700;font-size:.84rem;">'
            + '<td colspan="4" class="text-end" style="color:#7c3aed;">Grand Totals</td>'
            + '<td class="text-end text-success">' + _tbFmtAlways(d.TotalObDr)   + '</td>'
            + '<td class="text-end text-danger">'  + _tbFmtAlways(d.TotalObCr)   + '</td>'
            + '<td class="text-end text-success">' + _tbFmtAlways(d.GrandDebit)  + '</td>'
            + '<td class="text-end text-danger">'  + _tbFmtAlways(d.GrandCredit) + '</td>'
            + '<td colspan="2"></td></tr>';

        var diff = Math.abs((parseFloat(d.GrandDebit) || 0) - (parseFloat(d.GrandCredit) || 0));
        var isBalanced = diff < 0.01;
        html += '<tr class="' + (isBalanced ? 'table-success' : 'table-danger') + '">'
            + '<td colspan="10" class="text-center py-2" style="font-size:var(--r2k-text-82);font-weight:600;">';
        html += isBalanced
            ? '<i class="bx bx-check-circle me-1 text-success"></i>Trial Balance is <strong>BALANCED</strong> — Total Debit equals Total Credit'
            : '<i class="bx bx-error me-1 text-danger"></i>Trial Balance is <strong>UNBALANCED</strong> — Difference: ' + _tbFmtAlways(diff);
        html += '</td></tr></tfoot></table>';
        return html;
    }

    /**
     * @param {object} r
     * @returns {void}
     */
    function _loadFYOptions() {
        $.ajax({
            url:      global_services_url + 'api/accounting/getTrialBalancePageData',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) {
                if (r.Error || !r.Data) { _load(); return; }
                var $sel  = $('#tbFY');
                var years = r.Data.FinancialYears || [];
                var defFY = r.Data.DefaultFY || 0;
                $sel.empty();
                $.each(years, function (i, fy) {
                    var yr = parseInt(fy, 10);
                    $sel.append($('<option>').val(yr).text('FY ' + yr + '–' + (yr + 1))
                        .prop('selected', yr === defFY));
                });
                if (!years.length) {
                    var curY = new Date().getFullYear();
                    $sel.append($('<option>').val(curY).text('FY ' + curY + '–' + (curY + 1)));
                }
                _load();
            },
            error: function () { _load(); },
        });
    }

    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * @returns {void}
     */
    function _load() {
        var fy = $('#tbFY').val();
        var $btn = $('#btnViewTB').prop('disabled', true);
        $('#tbSpinner').removeClass('d-none'); $('#tbIcon').addClass('d-none');
        $('#tbEmptyState').hide();
        $('#tbResultCard').addClass('d-none');
        $('#tbStatsWrap').addClass('d-none');

        $.ajax({
            url:      global_services_url + 'api/accounting/getTrialBalanceAjax',
            method:   'GET',
            data:     { FinancialYear: fy, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (r) {
                $btn.prop('disabled', false);
                $('#tbSpinner').addClass('d-none'); $('#tbIcon').removeClass('d-none');

                if (r.Error) { showToastNotification(r.Message, 'error'); $('#tbEmptyState').show(); return; }

                $('#tbStatCount').text(r.RowCount || 0);
                $('#tbStatDr').text(_fmt(r.GrandDebit));
                $('#tbStatCr').text(_fmt(r.GrandCredit));
                if (r.IsBalanced) {
                    $('#tbStatStatus').html('<span class="text-success"><i class="bx bx-check-circle me-1"></i>Balanced</span>');
                } else {
                    $('#tbStatStatus').html('<span class="text-danger"><i class="bx bx-error me-1"></i>Unbalanced</span>');
                }
                $('#tbStatsWrap').removeClass('d-none');

                var fyVal = parseInt(fy);
                $('#tbCardTitle').text('Trial Balance — FY ' + fyVal + '–' + (fyVal + 1));
                $('#tbCardPeriod').text('April ' + fyVal + ' to March ' + (fyVal + 1));
                $('#tbTableWrap').html(_tbBuildHtml(r));
                $('#tbResultCard').removeClass('d-none');
                $('#btnTBPrint').removeClass('d-none');
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#tbSpinner').addClass('d-none'); $('#tbIcon').removeClass('d-none');
                showToastNotification('Request failed.', 'error');
                $('#tbEmptyState').show();
            }
        });
    }

    $('#btnViewTB').on('click', _load);

    // Auto-load on page ready — fetch FY options first, then load
    _loadFYOptions();

    // Print
    $('#btnTBPrint').on('click', function () {
        var win = window.open('', '_blank');
        var fy  = parseInt($('#tbFY').val());
        win.document.write('<html><head><title>Trial Balance</title>');
        win.document.write('<link rel="stylesheet" href="/assets/vendor/css/core.css">');
        win.document.write('</head><body style="padding:24px;">');
        win.document.write('<h4>Trial Balance — FY ' + fy + '–' + (fy+1) + '</h4>');
        win.document.write($('#tbTableWrap').html());
        win.document.write('</body></html>');
        win.document.close(); win.print();
    });

}());
</script>
