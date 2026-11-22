<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Profit &amp; Loss',
                    'pageDescription' => 'Income and expense summary for a selected period',
                    'pageIcon'        => 'bx-trending-up',
                    'pageIconBg'      => '#dcfce7',
                    'pageIconColor'   => '#16a34a',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">From Date</label>
                                    <input type="text" id="plDateFrom" class="form-control form-control-sm" readonly
                                           placeholder="Select start date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">To Date</label>
                                    <input type="text" id="plDateTo" class="form-control form-control-sm" readonly
                                           placeholder="Select end date">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-success btn-sm" id="plLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load P&amp;L
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="plPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3 d-none" id="plSummaryRow">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" style="background:#dcfce7;">
                                        <i class="bx bx-trending-up fs-4" style="color:#16a34a;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Income</div>
                                        <div class="fw-bold text-success" style="font-size:1.1rem;" id="plSumIncome">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" style="background:#fee2e2;">
                                        <i class="bx bx-trending-down fs-4" style="color:#dc2626;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Expenses</div>
                                        <div class="fw-bold text-danger" style="font-size:1.1rem;" id="plSumExpense">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" id="plSumNetIcon" style="background:#dcfce7;">
                                        <i class="bx bx-money fs-4" id="plSumNetIco" style="color:#16a34a;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);" id="plSumNetLabel">Net Profit</div>
                                        <div class="fw-bold" style="font-size:1.1rem;" id="plSumNet">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Statement Area ─────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="plStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="plEmptyState">
                                <i class="bx bx-bar-chart-alt-2 fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Select a date range and click Load P&amp;L</span>
                            </div>
                        </div>
                    </div>

                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<script>
/* global $, global_services_url, JwtData, ajaxLoading, currencySymbol, decimalPlaces, Swal, flatpickr, _transFormDateFormat */
(function () {
    'use strict';

    var _cur = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _dec = (typeof decimalPlaces  !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;

    // ── Flatpickr ─────────────────────────────────────────────────────────────
    var _fpCfg = { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y' };
    var _fpFrom, _fpTo;
    if (typeof flatpickr !== 'undefined') {
        _fpFrom = flatpickr('#plDateFrom', $.extend({}, _fpCfg, {
            defaultDate: new Date(new Date().getFullYear(), 0, 1),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#plDateTo', $.extend({}, _fpCfg, {
            defaultDate: new Date(),
            onChange: function (d) { if (_fpFrom && d[0]) _fpFrom.set('maxDate', d[0]); }
        }));
    }

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) { return $('<span>').text(String(s || '')).html(); }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _plFmt(n) {
        if (Math.abs(n) < 0.005) return '<span class="text-muted">—</span>';
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {object} d  NestJS getPandLAjax response
     * @returns {string}
     */
    function _plBuildHtml(d) {
        var orgName = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var html = '<div class="pl-statement" id="plStatement">'
            + '<div class="text-center mb-4 pl-print-header">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold mt-1" style="font-size:.92rem;">Profit &amp; Loss Statement</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-md);">Period: ' + _esc(d.DateFrom || '') + ' to ' + _esc(d.DateTo || '') + '</div>'
            + '</div>';

        /* ── INCOME table ── */
        html += '<table class="table table-sm table-bordered align-middle mb-0 pl-tbl" id="plPrintTable">'
            + '<thead><tr class="pl-section-head income-head"><th colspan="3">INCOME</th></tr>'
            + '<tr class="pl-col-head"><th style="width:60px;">Code</th><th>Account</th><th class="text-end" style="width:150px;">Amount</th></tr></thead><tbody>';
        if (d.Income && d.Income.length) {
            $.each(d.Income, function (i, r) {
                var net = parseFloat(r.NetAmount || 0);
                var muted = Math.abs(net) < 0.005;
                html += '<tr' + (muted ? ' class="text-muted"' : '') + '>'
                    + '<td><code style="font-size:var(--r2k-text-75);color:#7c3aed;">' + _esc(r.LedgerCode || '') + '</code></td>'
                    + '<td>' + _esc(r.LedgerName) + '</td>'
                    + '<td class="text-end fw-semibold ' + (net < 0 ? 'text-danger' : 'text-success') + '">'
                    + _plFmt(net) + (net < 0 ? '<small class="ms-1" style="font-size:var(--r2k-text-70);">(Dr)</small>' : '')
                    + '</td></tr>';
            });
        } else {
            html += '<tr><td colspan="3" class="text-center text-muted py-3" style="font-size:var(--r2k-text-82);">No income accounts found</td></tr>';
        }
        var totalIncome = parseFloat(d.TotalIncome || 0);
        html += '</tbody><tfoot><tr class="pl-subtotal income-subtotal">'
            + '<td colspan="2" class="fw-semibold">TOTAL INCOME</td>'
            + '<td class="text-end fw-bold text-success">' + _fmt(Math.max(0, totalIncome)) + '</td>'
            + '</tr></tfoot></table><div style="height:16px;"></div>';

        /* ── EXPENSES table ── */
        html += '<table class="table table-sm table-bordered align-middle mb-0 pl-tbl">'
            + '<thead><tr class="pl-section-head expense-head"><th colspan="3">EXPENSES</th></tr>'
            + '<tr class="pl-col-head"><th style="width:60px;">Code</th><th>Account</th><th class="text-end" style="width:150px;">Amount</th></tr></thead><tbody>';
        if (d.Expense && d.Expense.length) {
            $.each(d.Expense, function (i, r) {
                var net = parseFloat(r.NetAmount || 0);
                var muted = Math.abs(net) < 0.005;
                html += '<tr' + (muted ? ' class="text-muted"' : '') + '>'
                    + '<td><code style="font-size:var(--r2k-text-75);color:#7c3aed;">' + _esc(r.LedgerCode || '') + '</code></td>'
                    + '<td>' + _esc(r.LedgerName) + '</td>'
                    + '<td class="text-end fw-semibold ' + (net < 0 ? 'text-success' : 'text-danger') + '">'
                    + _plFmt(net) + (net < 0 ? '<small class="ms-1" style="font-size:var(--r2k-text-70);">(Cr)</small>' : '')
                    + '</td></tr>';
            });
        } else {
            html += '<tr><td colspan="3" class="text-center text-muted py-3" style="font-size:var(--r2k-text-82);">No expense accounts found</td></tr>';
        }
        var totalExpense = parseFloat(d.TotalExpense || 0);
        html += '</tbody><tfoot><tr class="pl-subtotal expense-subtotal">'
            + '<td colspan="2" class="fw-semibold">TOTAL EXPENSES</td>'
            + '<td class="text-end fw-bold text-danger">' + _fmt(Math.max(0, totalExpense)) + '</td>'
            + '</tr></tfoot></table><div style="height:16px;"></div>';

        /* ── NET PROFIT/LOSS row ── */
        var net = parseFloat(d.NetProfit || 0);
        var isLoss = net < 0;
        html += '<table class="table table-sm table-bordered align-middle mb-0 pl-tbl"><tbody>'
            + '<tr class="pl-net ' + (isLoss ? 'pl-net-loss' : 'pl-net-profit') + '">'
            + '<td class="fw-bold" style="font-size:.95rem;padding:12px 16px;">'
            + (isLoss ? 'NET LOSS' : 'NET PROFIT')
            + '<div class="text-muted fw-normal" style="font-size:var(--r2k-text-sm);margin-top:2px;">Income ' + (totalIncome >= totalExpense ? '−' : '+') + ' Expenses</div>'
            + '</td>'
            + '<td class="text-end fw-bold" style="font-size:1rem;padding:12px 16px;width:150px;' + (isLoss ? 'color:#dc2626;' : 'color:#16a34a;') + '">'
            + _fmt(net) + (isLoss ? '<div style="font-size:var(--r2k-text-sm);font-weight:400;">(Loss)</div>' : '')
            + '</td></tr></tbody></table></div>';

        return html;
    }

    // ── Load button ───────────────────────────────────────────────────────────
    $('#plLoadBtn').on('click', function () {
        var from = $('#plDateFrom').val();
        var to   = $('#plDateTo').val();
        if (!from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please select both dates', timer: 1800, showConfirmButton: false });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getPandLAjax',
            method:   'GET',
            data:     { DateFrom: from, DateTo: to, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }

                var isLoss = parseFloat(d.NetProfit || 0) < 0;
                $('#plSummaryRow').removeClass('d-none');
                $('#plSumIncome').text(_fmt(d.TotalIncome));
                $('#plSumExpense').text(_fmt(d.TotalExpense));
                $('#plSumNet').text(_fmt(d.NetProfit)).css('color', isLoss ? '#dc2626' : '#16a34a');
                $('#plSumNetLabel').text(isLoss ? 'Net Loss' : 'Net Profit');
                $('#plSumNetIcon').css('background', isLoss ? '#fee2e2' : '#dcfce7');
                $('#plSumNetIco').css('color', isLoss ? '#dc2626' : '#16a34a').attr('class', isLoss ? 'bx bx-trending-down fs-4' : 'bx bx-trending-up fs-4');
                $('#plStatementArea').html('<div class="p-4">' + _plBuildHtml(d) + '</div>');
                $('#plPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load P&amp;L');
                ajaxLoading(0);
            }
        });
    });

    // ── Print ─────────────────────────────────────────────────────────────────
    $('#plPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
