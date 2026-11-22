<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Comparative P&amp;L',
                    'pageDescription' => 'Side-by-side profit &amp; loss for two periods with variance analysis',
                    'pageIcon'        => 'bx-columns',
                    'pageIconBg'      => '#eff6ff',
                    'pageIconColor'   => '#1d4ed8',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Period 1 Label</label>
                                    <input type="text" id="cmpP1Label" class="form-control form-control-sm"
                                           value="Current Period" maxlength="40" placeholder="e.g. Q1 2026">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Period 1 From</label>
                                    <input type="text" id="cmpP1From" class="form-control form-control-sm" readonly
                                           placeholder="Start date">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Period 1 To</label>
                                    <input type="text" id="cmpP1To" class="form-control form-control-sm" readonly
                                           placeholder="End date">
                                </div>
                                <div class="col-md-1 d-flex align-items-end justify-content-center pb-1">
                                    <i class="bx bx-transfer-alt fs-4 text-muted"></i>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Period 2 Label</label>
                                    <input type="text" id="cmpP2Label" class="form-control form-control-sm"
                                           value="Previous Period" maxlength="40" placeholder="e.g. Q1 2025">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Period 2 From</label>
                                    <input type="text" id="cmpP2From" class="form-control form-control-sm" readonly
                                           placeholder="Start date">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Period 2 To</label>
                                    <input type="text" id="cmpP2To" class="form-control form-control-sm" readonly
                                           placeholder="End date">
                                </div>
                            </div>
                            <div class="row g-3 mt-1 align-items-end">
                                <div class="col-md-auto">
                                    <button class="btn btn-primary btn-sm" id="cmpLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Report
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="cmpPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3 d-none" id="cmpSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-inc"><i class="bx bx-trending-up fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Income Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumIncVar">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);" id="cmpSumIncPct">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-exp"><i class="bx bx-trending-down fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Expense Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumExpVar">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);" id="cmpSumExpPct">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-net" id="cmpSumNetIcon"><i class="bx bx-money fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Period 1 Net</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumNet1">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);">P2: <span id="cmpSumNet2">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-var" id="cmpSumVarIcon"><i class="bx bx-transfer fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Net Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumNetVar">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);" id="cmpSumNetPct">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Report Area ────────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="cmpReportArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="cmpEmptyState">
                                <i class="bx bx-columns fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Enter period dates and click Load Report</span>
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

    if (typeof flatpickr !== 'undefined') {
        var now   = new Date();
        var y1Jan = new Date(now.getFullYear(), 0, 1);
        var lyJan = new Date(now.getFullYear() - 1, 0, 1);
        var lyDec = new Date(now.getFullYear() - 1, 11, 31);

        flatpickr('#cmpP1From', $.extend({}, _fpCfg, { defaultDate: y1Jan }));
        flatpickr('#cmpP1To',   $.extend({}, _fpCfg, { defaultDate: now }));
        flatpickr('#cmpP2From', $.extend({}, _fpCfg, { defaultDate: lyJan }));
        flatpickr('#cmpP2To',   $.extend({}, _fpCfg, { defaultDate: lyDec }));
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
    function _cmpFmt(n) {
        if (Math.abs(n) < 0.005) return '<span class="text-muted comp-dash">—</span>';
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number|null} pct
     * @param {boolean}     higherIsBetter
     * @returns {string}
     */
    function _cmpPct(pct, higherIsBetter) {
        if (pct === null || pct === undefined) return '<span class="text-muted">—</span>';
        var good = higherIsBetter ? pct >= 0 : pct <= 0;
        var cls  = good ? 'text-success' : 'text-danger';
        var sign = pct >= 0 ? '+' : '';
        return '<span class="' + cls + ' fw-semibold comp-pct">' + sign + pct + '%</span>';
    }

    /**
     * @param {number}  varVal
     * @param {boolean} higherIsBetter
     * @returns {string}
     */
    function _cmpVar(varVal, higherIsBetter) {
        if (Math.abs(varVal) < 0.005) return '<span class="text-muted">—</span>';
        var good = higherIsBetter ? varVal >= 0 : varVal <= 0;
        var cls  = good ? 'text-success' : 'text-danger';
        var sign = varVal >= 0 ? '+' : '−';
        return '<span class="' + cls + ' fw-semibold">' + sign + _cur + ' ' + Math.abs(varVal).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }) + '</span>';
    }

    /**
     * @param {number|null} pct
     * @param {boolean}     higherIsBetter
     * @returns {string}
     */
    function _pctLabel(pct, higherIsBetter) {
        if (pct === null || pct === undefined) return '—';
        var sign = pct >= 0 ? '+' : '';
        var good = higherIsBetter ? pct >= 0 : pct <= 0;
        return (good ? '▲ ' : '▼ ') + sign + pct + '%';
    }

    /**
     * @param {object[]} rows
     * @param {string}   p1Label
     * @param {string}   p2Label
     * @param {boolean}  higherIsBetter
     * @returns {string}
     */
    function _cmpRows(rows, p1Label, p2Label, higherIsBetter) {
        if (!rows || !rows.length) {
            return '<tr><td colspan="5" class="text-center text-muted py-3" style="font-size:var(--r2k-text-82);">No accounts</td></tr>';
        }
        var html = '';
        $.each(rows, function (i, r) {
            html += '<tr class="comp-row">'
                + '<td style="font-size:var(--r2k-text-82);">' + _esc(r.LedgerName)
                + (r.LedgerCode ? '<code style="font-size:var(--r2k-text-70);color:#7c3aed;display:block;">' + _esc(r.LedgerCode) + '</code>' : '')
                + '</td>'
                + '<td class="text-end fw-semibold comp-p1-val" style="font-size:var(--r2k-text-82);">' + _cmpFmt(r.Net1) + '</td>'
                + '<td class="text-end comp-p2-val" style="font-size:var(--r2k-text-82);">' + _cmpFmt(r.Net2) + '</td>'
                + '<td class="text-end" style="font-size:var(--r2k-text-82);">' + _cmpVar(r.Variance, higherIsBetter) + '</td>'
                + '<td class="text-center" style="font-size:var(--r2k-text-82);">' + _cmpPct(r.PctChange, higherIsBetter) + '</td>'
                + '</tr>';
        });
        return html;
    }

    /**
     * @param {object} d  NestJS getComparativePnLAjax response
     * @returns {string}
     */
    function _cmpBuildHtml(d) {
        var orgName  = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var p1Label  = _esc(d.P1Label || 'Period 1');
        var p2Label  = _esc(d.P2Label || 'Period 2');
        var t        = d.Totals || {};
        var net1Loss = (t.net1 || 0) < 0;
        var net2Loss = (t.net2 || 0) < 0;

        var html = '<div id="cmpStatement">'
            + '<div class="text-center mb-3 pl-print-header">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold" style="font-size:.92rem;">Comparative Profit &amp; Loss Statement</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-78);">' + p1Label + ': ' + _esc(d.P1From || '') + ' – ' + _esc(d.P1To || '') + ' &nbsp;|&nbsp; ' + p2Label + ': ' + _esc(d.P2From || '') + ' – ' + _esc(d.P2To || '') + '</div>'
            + '</div>';

        /* ── INCOME table ── */
        html += '<table class="table table-sm table-bordered align-middle comp-tbl mb-3">'
            + '<thead><tr class="comp-section-head comp-income-head"><th colspan="5">INCOME</th></tr>'
            + '<tr class="comp-col-head"><th>Account</th><th class="text-end" style="width:130px;">' + p1Label + '</th><th class="text-end" style="width:130px;">' + p2Label + '</th><th class="text-end" style="width:120px;">Variance</th><th class="text-center" style="width:80px;">% Change</th></tr></thead>'
            + '<tbody>' + _cmpRows(d.Income, p1Label, p2Label, true) + '</tbody>'
            + '<tfoot><tr class="comp-tfoot comp-income-tfoot">'
            + '<td class="fw-bold">TOTAL INCOME</td>'
            + '<td class="text-end fw-bold comp-p1-val">' + _cmpFmt(t.income1 || 0) + '</td>'
            + '<td class="text-end fw-bold comp-p2-val">' + _cmpFmt(t.income2 || 0) + '</td>'
            + '<td class="text-end fw-bold">' + _cmpVar(t.incVar || 0, true) + '</td>'
            + '<td class="text-center">' + _cmpPct(t.incPct, true) + '</td>'
            + '</tr></tfoot></table>';

        /* ── EXPENSES table ── */
        html += '<table class="table table-sm table-bordered align-middle comp-tbl mb-3">'
            + '<thead><tr class="comp-section-head comp-expense-head"><th colspan="5">EXPENSES</th></tr>'
            + '<tr class="comp-col-head"><th>Account</th><th class="text-end" style="width:130px;">' + p1Label + '</th><th class="text-end" style="width:130px;">' + p2Label + '</th><th class="text-end" style="width:120px;">Variance</th><th class="text-center" style="width:80px;">% Change</th></tr></thead>'
            + '<tbody>' + _cmpRows(d.Expense, p1Label, p2Label, false) + '</tbody>'
            + '<tfoot><tr class="comp-tfoot comp-expense-tfoot">'
            + '<td class="fw-bold">TOTAL EXPENSES</td>'
            + '<td class="text-end fw-bold comp-p1-val">' + _cmpFmt(t.expense1 || 0) + '</td>'
            + '<td class="text-end fw-bold comp-p2-val">' + _cmpFmt(t.expense2 || 0) + '</td>'
            + '<td class="text-end fw-bold">' + _cmpVar(t.expVar || 0, false) + '</td>'
            + '<td class="text-center">' + _cmpPct(t.expPct, false) + '</td>'
            + '</tr></tfoot></table>';

        /* ── NET row ── */
        html += '<table class="table table-sm table-bordered align-middle comp-tbl mb-0"><tbody>'
            + '<tr class="comp-net-row ' + (net1Loss ? 'comp-net-loss' : 'comp-net-profit') + '">'
            + '<td class="fw-bold" style="font-size:var(--r2k-text-90);">NET ' + (net1Loss ? 'DEFICIT' : 'SURPLUS') + ' (Profit)</td>'
            + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);width:130px;color:' + (net1Loss ? '#dc2626' : '#16a34a') + ';">' + _cmpFmt(t.net1 || 0) + '</td>'
            + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);width:130px;color:' + (net2Loss ? '#dc2626' : '#16a34a') + ';">' + _cmpFmt(t.net2 || 0) + '</td>'
            + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);width:120px;">' + _cmpVar(t.netVar || 0, true) + '</td>'
            + '<td class="text-center" style="width:80px;">' + _cmpPct(t.netPct, true) + '</td>'
            + '</tr></tbody></table>'
            + '<div class="comp-footnote mt-2 text-muted"><i class="bx bx-info-circle me-1"></i>Variance = ' + p2Label + ' minus ' + p1Label + '. Positive variance on income is favourable; positive variance on expenses is unfavourable.</div>'
            + '</div>';

        return html;
    }

    $('#cmpLoadBtn').on('click', function () {
        var p1From  = $('#cmpP1From').val();
        var p1To    = $('#cmpP1To').val();
        var p2From  = $('#cmpP2From').val();
        var p2To    = $('#cmpP2To').val();
        var p1Label = $('#cmpP1Label').val() || 'Period 1';
        var p2Label = $('#cmpP2Label').val() || 'Period 2';

        if (!p1From || !p1To || !p2From || !p2To) {
            Swal.fire({ icon: 'warning', title: 'Please fill all date fields', timer: 1800, showConfirmButton: false });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getComparativePnLAjax',
            method:   'GET',
            data:     { P1From: p1From, P1To: p1To, P2From: p2From, P2To: p2To, P1Label: p1Label, P2Label: p2Label, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }

                var t = d.Totals || {};
                $('#cmpSummaryRow').removeClass('d-none');
                $('#cmpSumIncVar').text(_fmt(t.incVar || 0)).css('color', (t.incVar || 0) >= 0 ? '#16a34a' : '#dc2626');
                $('#cmpSumIncPct').text(_pctLabel(t.incPct, true));
                $('#cmpSumExpVar').text(_fmt(t.expVar || 0)).css('color', (t.expVar || 0) <= 0 ? '#16a34a' : '#dc2626');
                $('#cmpSumExpPct').text(_pctLabel(t.expPct, false));
                $('#cmpSumNet1').text(_fmt(t.net1 || 0)).css('color', (t.net1 || 0) >= 0 ? '#16a34a' : '#dc2626');
                $('#cmpSumNet2').text(_fmt(t.net2 || 0));
                $('#cmpSumNetVar').text(_fmt(t.netVar || 0)).css('color', (t.netVar || 0) >= 0 ? '#16a34a' : '#dc2626');
                $('#cmpSumNetPct').text(_pctLabel(t.netPct, true));
                $('#cmpSumNetIcon').css({ background: (t.net1 || 0) >= 0 ? '#dcfce7' : '#fee2e2', color: (t.net1 || 0) >= 0 ? '#16a34a' : '#dc2626' });
                $('#cmpSumVarIcon').css({ background: (t.netVar || 0) >= 0 ? '#dcfce7' : '#fee2e2', color: (t.netVar || 0) >= 0 ? '#16a34a' : '#dc2626' });
                $('#cmpReportArea').html('<div class="p-3">' + _cmpBuildHtml(d) + '</div>');
                $('#cmpPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Report');
                ajaxLoading(0);
            }
        });
    });

    $('#cmpPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
