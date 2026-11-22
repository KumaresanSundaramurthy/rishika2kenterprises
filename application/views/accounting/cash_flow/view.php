<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Cash Flow Statement',
                    'pageDescription' => 'Direct-method view of cash inflows and outflows for a period',
                    'pageIcon'        => 'bx-transfer-alt',
                    'pageIconBg'      => '#ecfdf5',
                    'pageIconColor'   => '#059669',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">From Date</label>
                                    <input type="text" id="cfDateFrom" class="form-control form-control-sm" readonly
                                           placeholder="Start date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">To Date</label>
                                    <input type="text" id="cfDateTo" class="form-control form-control-sm" readonly
                                           placeholder="End date">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-success btn-sm" id="cfLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Statement
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="cfPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3 d-none" id="cfSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-open"><i class="bx bx-wallet fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Opening Cash &amp; Bank</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cfSumOpen">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-in"><i class="bx bx-trending-up fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Inflows</div>
                                        <div class="fw-bold text-success" style="font-size:1rem;" id="cfSumIn">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-out"><i class="bx bx-trending-down fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Outflows</div>
                                        <div class="fw-bold text-danger" style="font-size:1rem;" id="cfSumOut">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-close" id="cfSumCloseIcon"><i class="bx bx-wallet-alt fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Closing Cash &amp; Bank</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cfSumClose">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Statement Area ─────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="cfStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="cfEmptyState">
                                <i class="bx bx-transfer-alt fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Select dates and click Load Statement</span>
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

    var _fpCfg = { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y' };
    var _fpFrom, _fpTo;
    if (typeof flatpickr !== 'undefined') {
        _fpFrom = flatpickr('#cfDateFrom', $.extend({}, _fpCfg, {
            defaultDate: new Date(new Date().getFullYear(), 0, 1),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#cfDateTo', $.extend({}, _fpCfg, {
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
     * @param {number}  n
     * @param {boolean} showSign
     * @returns {string}
     */
    function _cfFmt(n, showSign) {
        if (Math.abs(n) < 0.005) return '<span class="text-muted">—</span>';
        var prefix = showSign ? (n >= 0 ? '+' : '−') : '';
        return prefix + _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n || 0).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    var _refLabels = {
        'Invoice': 'Sales / Invoice Receipts', 'Payment-In': 'Customer Payment Receipts',
        'Return-In': 'Sales Return Receipts', 'Purchase': 'Purchase / Bill Payments',
        'Payment-Out': 'Vendor Payment Outflows', 'Return-Out': 'Purchase Return Outflows',
        'Recurring': 'Recurring Journal Entries', 'Manual': 'Manual Journal Entries',
        'Bank Recon': 'Bank Reconciliation Adjustments',
    };

    /**
     * @param {object} d  NestJS getCashFlowAjax response
     * @returns {string}
     */
    function _cfBuildHtml(d) {
        var orgName = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var accounts = d.Accounts || [];
        var grandNetChange = parseFloat(d.GrandPeriodDr || 0) - parseFloat(d.GrandPeriodCr || 0);
        var netPos = grandNetChange >= 0;

        var html = '<div class="cf-statement pl-print-header" id="cfStatement">'
            + '<div class="text-center mb-4">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold mt-1" style="font-size:.92rem;">Cash Flow Statement (Direct Method)</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-md);">Period: ' + _esc(d.DateFrom || '') + ' to ' + _esc(d.DateTo || '') + '</div>'
            + '</div>';

        if (!accounts.length) {
            html += '<div class="text-center text-muted py-4"><i class="bx bx-wallet fs-3 d-block mb-2"></i>No Cash or Bank accounts found.</div>';
        } else {
            $.each(accounts, function (i, acc) {
                var inflows  = acc.Inflows  || {};
                var outflows = acc.Outflows || {};
                var isCash   = acc.LedgerType === 'Cash';
                var openBal  = parseFloat(acc.OpeningBal || 0);
                var closeBal = parseFloat(acc.ClosingBal || 0);
                var netChange= parseFloat(acc.NetChange || 0);

                html += '<div class="cf-account-block mb-4">'
                    + '<div class="cf-account-header d-flex align-items-center justify-content-between">'
                    + '<div class="d-flex align-items-center gap-2"><i class="bx ' + (isCash ? 'bx-money' : 'bx-bank') + ' fs-5"></i>'
                    + '<span class="fw-semibold">' + _esc(acc.LedgerName) + '</span>'
                    + '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-65);">' + _esc(acc.LedgerType) + '</span></div>'
                    + '<div class="text-end"><div class="text-muted" style="font-size:var(--r2k-text-sm);">Net Change</div>'
                    + '<div class="fw-bold ' + (netChange >= 0 ? 'text-success' : 'text-danger') + '" style="font-size:var(--r2k-text-88);">' + _cfFmt(netChange, true) + '</div>'
                    + '</div></div>'
                    + '<table class="table table-sm align-middle mb-0 cf-tbl"><tbody>'
                    /* Opening balance */
                    + '<tr class="cf-balance-row"><td class="fw-semibold" style="font-size:var(--r2k-text-82);">Opening Balance</td><td></td>'
                    + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-82);">' + _cfFmt(openBal, false)
                    + (Math.abs(openBal) > 0.005 ? '<span class="ms-1 text-muted" style="font-size:var(--r2k-text-xs);">' + (openBal >= 0 ? 'Dr' : 'Cr') + '</span>' : '')
                    + '</td></tr>';

                /* Inflows section */
                var inKeys = Object.keys(inflows).sort(function (a, b) { return inflows[b] - inflows[a]; });
                if (inKeys.length) {
                    var totalIn = 0;
                    html += '<tr class="cf-section-head cf-inflow-head"><td colspan="3" class="fw-semibold" style="font-size:.76rem;">INFLOWS (Money Received)</td></tr>';
                    $.each(inKeys, function (j, refType) {
                        var amt = parseFloat(inflows[refType] || 0);
                        totalIn += amt;
                        var label = _refLabels[refType] || _esc(refType);
                        html += '<tr class="cf-entry-row cf-entry-in">'
                            + '<td style="width:16px;border-left:3px solid #bbf7d0;"></td>'
                            + '<td style="font-size:var(--r2k-text-82);padding-left:12px;">' + label + '</td>'
                            + '<td class="text-end text-success fw-semibold" style="font-size:var(--r2k-text-82);">' + _cfFmt(amt, false) + '</td></tr>';
                    });
                    html += '<tr class="cf-subtotal cf-inflow-subtotal"><td colspan="2" class="fw-semibold" style="font-size:var(--r2k-text-78);">Total Inflows</td>'
                        + '<td class="text-end fw-bold text-success" style="font-size:var(--r2k-text-82);">' + _cfFmt(totalIn, false) + '</td></tr>';
                }

                /* Outflows section */
                var outKeys = Object.keys(outflows).sort(function (a, b) { return outflows[b] - outflows[a]; });
                if (outKeys.length) {
                    var totalOut = 0;
                    html += '<tr class="cf-section-head cf-outflow-head"><td colspan="3" class="fw-semibold" style="font-size:.76rem;">OUTFLOWS (Money Paid)</td></tr>';
                    $.each(outKeys, function (j, refType) {
                        var amt = parseFloat(outflows[refType] || 0);
                        totalOut += amt;
                        var label = _refLabels[refType] || _esc(refType);
                        html += '<tr class="cf-entry-row cf-entry-out">'
                            + '<td style="width:16px;border-left:3px solid #fecaca;"></td>'
                            + '<td style="font-size:var(--r2k-text-82);padding-left:12px;">' + label + '</td>'
                            + '<td class="text-end text-danger fw-semibold" style="font-size:var(--r2k-text-82);">(' + _cfFmt(amt, false) + ')</td></tr>';
                    });
                    html += '<tr class="cf-subtotal cf-outflow-subtotal"><td colspan="2" class="fw-semibold" style="font-size:var(--r2k-text-78);">Total Outflows</td>'
                        + '<td class="text-end fw-bold text-danger" style="font-size:var(--r2k-text-82);">(' + _cfFmt(totalOut, false) + ')</td></tr>';
                }

                /* Closing balance */
                html += '<tr class="cf-balance-row cf-closing-row"><td class="fw-bold" style="font-size:var(--r2k-text-82);">Closing Balance</td><td></td>'
                    + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);color:' + (closeBal >= 0 ? '#059669' : '#dc2626') + ';">'
                    + _cfFmt(closeBal, false)
                    + (Math.abs(closeBal) > 0.005 ? '<span class="ms-1 text-muted" style="font-size:var(--r2k-text-xs);">' + (closeBal >= 0 ? 'Dr' : 'Cr') + '</span>' : '')
                    + '</td></tr>'
                    + '</tbody></table></div>';
            });

            /* Consolidated summary (if >1 account) */
            if (accounts.length > 1) {
                var grandOpen  = parseFloat(d.GrandOpeningBal || 0);
                var grandDr    = parseFloat(d.GrandPeriodDr || 0);
                var grandCr    = parseFloat(d.GrandPeriodCr || 0);
                var grandClose = parseFloat(d.GrandClosingBal || 0);
                html += '<div class="cf-consolidated mt-2"><div class="cf-consolidated-head">CONSOLIDATED SUMMARY — All Cash &amp; Bank</div>'
                    + '<table class="table table-sm align-middle mb-0 cf-tbl"><tbody>'
                    + '<tr><td style="font-size:var(--r2k-text-82);">Total Opening Balance</td><td class="text-end fw-semibold" style="font-size:var(--r2k-text-82);">' + _cfFmt(grandOpen, false) + '</td></tr>'
                    + '<tr><td style="font-size:var(--r2k-text-82);color:#059669;">+ Total Inflows</td><td class="text-end fw-bold text-success" style="font-size:var(--r2k-text-82);">' + _cfFmt(grandDr, false) + '</td></tr>'
                    + '<tr><td style="font-size:var(--r2k-text-82);color:#dc2626;">− Total Outflows</td><td class="text-end fw-bold text-danger" style="font-size:var(--r2k-text-82);">(' + _cfFmt(grandCr, false) + ')</td></tr>'
                    + '<tr class="cf-grand-net ' + (netPos ? 'cf-net-pos' : 'cf-net-neg') + '"><td class="fw-bold" style="font-size:var(--r2k-text-85);">Net Change in Cash &amp; Bank</td>'
                    + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);">' + _cfFmt(grandNetChange, true) + '</td></tr>'
                    + '<tr class="cf-grand-closing"><td class="fw-bold" style="font-size:var(--r2k-text-85);">Total Closing Balance</td>'
                    + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);color:' + (grandClose >= 0 ? '#059669' : '#dc2626') + ';">' + _cfFmt(grandClose, false) + '</td></tr>'
                    + '</tbody></table></div>';
            }
        }

        html += '</div>';
        return html;
    }

    $('#cfLoadBtn').on('click', function () {
        var from = $('#cfDateFrom').val();
        var to   = $('#cfDateTo').val();
        if (!from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please select both dates', timer: 1800, showConfirmButton: false });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getCashFlowAjax',
            method:   'GET',
            data:     { DateFrom: from, DateTo: to, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }

                var netPos = (parseFloat(d.NetChange || 0)) >= 0;
                $('#cfSummaryRow').removeClass('d-none');
                $('#cfSumOpen').text(_fmt(d.GrandOpeningBal));
                $('#cfSumIn').text(_fmt(d.GrandPeriodDr));
                $('#cfSumOut').text(_fmt(d.GrandPeriodCr));
                $('#cfSumClose').text(_fmt(d.GrandClosingBal));
                $('#cfSumCloseIcon').css({ background: netPos ? '#dcfce7' : '#fee2e2', color: netPos ? '#059669' : '#dc2626' });
                $('#cfStatementArea').html('<div class="p-3">' + _cfBuildHtml(d) + '</div>');
                $('#cfPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Statement');
                ajaxLoading(0);
            }
        });
    });

    $('#cfPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
