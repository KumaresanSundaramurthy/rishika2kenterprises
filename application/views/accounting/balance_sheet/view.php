<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Balance Sheet',
                    'pageDescription' => 'Assets, liabilities and equity at a point in time',
                    'pageIcon'        => 'bx-equalizer',
                    'pageIconBg'      => '#eff6ff',
                    'pageIconColor'   => '#1d4ed8',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">As Of Date</label>
                                    <input type="text" id="bsAsOfDate" class="form-control form-control-sm" readonly
                                           placeholder="Balance Sheet date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">
                                        P&amp;L From
                                        <span class="text-muted fw-normal">(for Net Profit)</span>
                                    </label>
                                    <input type="text" id="bsPnlFrom" class="form-control form-control-sm" readonly
                                           placeholder="Start of financial year">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-primary btn-sm" id="bsLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Balance Sheet
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="bsPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3 d-none" id="bsSummaryRow">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" style="background:#eff6ff;">
                                        <i class="bx bx-building fs-4" style="color:#1d4ed8;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Assets</div>
                                        <div class="fw-bold" style="font-size:1.1rem;color:#1d4ed8;" id="bsSumAssets">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" style="background:#fff7ed;">
                                        <i class="bx bx-receipt fs-4" style="color:#9a3412;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Liabilities</div>
                                        <div class="fw-bold" style="font-size:1.1rem;color:#9a3412;" id="bsSumLiab">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" id="bsSumNetIcon" style="background:#dcfce7;">
                                        <i class="bx bx-money fs-4" id="bsSumNetIco" style="color:#16a34a;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);" id="bsSumNetLabel">Net Profit</div>
                                        <div class="fw-bold" style="font-size:1.1rem;" id="bsSumNet">—</div>
                                        <div class="d-none" id="bsBalancedBadge">
                                            <span class="badge bg-success" style="font-size:var(--r2k-text-65);">Balanced âœ“</span>
                                        </div>
                                        <div class="d-none" id="bsUnbalancedBadge">
                                            <span class="badge bg-danger" style="font-size:var(--r2k-text-65);">Unbalanced âœ—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Statement Area ─────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="bsStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="bsEmptyState">
                                <i class="bx bx-equalizer fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Select dates and click Load Balance Sheet</span>
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
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#bsAsOfDate', $.extend({}, _fpCfg, { defaultDate: new Date() }));
        flatpickr('#bsPnlFrom',  $.extend({}, _fpCfg, { defaultDate: new Date(new Date().getFullYear(), 0, 1) }));
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
    function _bsFmt(n) {
        if (Math.abs(n) < 0.005) return '<span class="text-muted" style="font-size:var(--r2k-text-78);">—</span>';
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {object} grps  array of groups [{label, items[], total}]
     * @param {string} side  'asset' or 'liab'
     * @returns {string}
     */
    function _bsGroupRows(grps, side) {
        var html = '';
        var borderCol  = side === 'asset' ? '#bfdbfe' : '#fdba74';
        var groupBg    = side === 'asset' ? '#eff6ff' : '#fff7ed';
        var groupColor = side === 'asset' ? '#1d4ed8' : '#9a3412';
        $.each(grps, function (k, grp) {
            if (!grp.items || !grp.items.length) return;
            html += '<tr class="bs-group-head"><td colspan="3" style="padding:5px 12px;font-weight:600;font-size:var(--r2k-text-78);background:' + groupBg + ';color:' + groupColor + ';">'
                + _esc(grp.label) + '</td></tr>';
            $.each(grp.items, function (i, r) {
                var cb     = parseFloat(r.ClosingBalance || 0);
                var cbType = r.ClosingBalanceType || 'Debit';
                var isDr   = cbType === 'Debit';
                var isCr   = cbType === 'Credit';
                var muted  = Math.abs(cb) < 0.005;
                html += '<tr' + (muted ? ' class="text-muted"' : '') + '>'
                    + '<td style="width:16px;padding-left:20px;border-left:3px solid ' + borderCol + ';"></td>'
                    + '<td style="font-size:var(--r2k-text-82);">' + _esc(r.LedgerName);
                if (side === 'asset' && !isDr && Math.abs(cb) > 0.005) {
                    html += '<span class="badge bg-label-danger ms-1" style="font-size:.6rem;" title="Credit balance — unusual for asset">Cr</span>';
                }
                if (side === 'liab' && !isCr && Math.abs(cb) > 0.005) {
                    html += '<span class="badge bg-label-info ms-1" style="font-size:.6rem;" title="Debit balance — unusual for liability">Dr</span>';
                }
                html += '</td><td class="text-end fw-semibold" style="font-size:var(--r2k-text-82);white-space:nowrap;width:130px;'
                    + (side === 'asset' && !isDr && Math.abs(cb) > 0.005 ? 'color:#dc2626;' : '') + '">'
                    + _bsFmt(cb) + '</td></tr>';
            });
            html += '<tr class="bs-group-subtotal"><td colspan="2" style="font-weight:600;font-size:var(--r2k-text-78);padding-left:20px;">Subtotal — ' + _esc(grp.label) + '</td>'
                + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-md);white-space:nowrap;' + (grp.total < 0 ? 'color:#dc2626;' : 'color:' + groupColor + ';') + '">'
                + _bsFmt(grp.total) + '</td></tr>';
        });
        return html;
    }

    /**
     * @param {object} d  NestJS getBalanceSheetAjax response
     * @returns {string}
     */
    function _bsBuildHtml(d) {
        var orgName    = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var totalAssets = parseFloat(d.TotalAssets || 0);
        var totalLiab   = parseFloat(d.TotalLiabilities || 0);
        var netProfit   = parseFloat(d.NetProfit || 0);
        var isLoss      = netProfit < 0;
        var totalLE     = totalLiab + netProfit;
        var diff        = Math.abs(totalAssets - totalLE);
        var balanced    = diff < 0.01;

        var html = '<div class="bs-statement" id="bsStatement">'
            + '<div class="text-center mb-4 pl-print-header">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold mt-1" style="font-size:.92rem;">Balance Sheet</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-md);">As of ' + _esc(d.AsOfDate || '') + ' &nbsp;·&nbsp; P&amp;L from ' + _esc(d.PnlFrom || '') + '</div>'
            + '</div>'
            + '<div class="row g-3">';

        /* ── ASSETS column ── */
        html += '<div class="col-md-6"><table class="table table-sm table-bordered align-middle mb-0 bs-tbl h-100">'
            + '<thead><tr class="pl-section-head" style="background:#1d4ed8;color:#fff;"><th colspan="3">ASSETS</th></tr></thead><tbody>'
            + _bsGroupRows(d.AssetGroups || [], 'asset')
            + '</tbody><tfoot><tr style="background:#1d4ed8;color:#fff;font-weight:700;">'
            + '<td colspan="2" style="padding:10px 12px;">TOTAL ASSETS</td>'
            + '<td class="text-end" style="padding:10px 12px;font-size:var(--r2k-text-90);white-space:nowrap;">' + _fmt(totalAssets) + '</td>'
            + '</tr></tfoot></table></div>';

        /* ── LIABILITIES + NET PROFIT column ── */
        html += '<div class="col-md-6"><table class="table table-sm table-bordered align-middle mb-0 bs-tbl">'
            + '<thead><tr class="pl-section-head" style="background:#7c2d12;color:#fff;"><th colspan="3">LIABILITIES &amp; EQUITY</th></tr></thead><tbody>'
            + _bsGroupRows(d.LiabGroups || [], 'liab');

        /* Net profit row */
        html += '<tr style="background:#f0fdf4;">'
            + '<td style="width:16px;border-left:3px solid #86efac;"></td>'
            + '<td style="font-size:var(--r2k-text-82);font-weight:600;color:' + (isLoss ? '#dc2626' : '#16a34a') + ';">'
            + (isLoss ? 'Net Loss for Period' : 'Net Profit for Period')
            + '<div class="text-muted fw-normal" style="font-size:var(--r2k-text-70);">' + _esc(d.PnlFrom || '') + ' to ' + _esc(d.AsOfDate || '') + '</div>'
            + '</td>'
            + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-82);white-space:nowrap;color:' + (isLoss ? '#dc2626' : '#16a34a') + ';">'
            + _bsFmt(netProfit) + (isLoss ? '<div style="font-size:var(--r2k-text-70);">(Loss)</div>' : '')
            + '</td></tr>';

        html += '</tbody><tfoot><tr style="background:#7c2d12;color:#fff;font-weight:700;">'
            + '<td colspan="2" style="padding:10px 12px;">TOTAL LIABILITIES + NET PROFIT</td>'
            + '<td class="text-end" style="padding:10px 12px;font-size:var(--r2k-text-90);white-space:nowrap;">' + _fmt(totalLE) + '</td>'
            + '</tr></tfoot></table></div></div>';

        /* Balance check */
        html += '<div class="mt-3 py-3 px-4 rounded ' + (balanced ? 'bs-balanced' : 'bs-unbalanced') + '">'
            + (balanced
                ? '<i class="bx bx-check-circle me-2"></i><strong>BALANCED</strong> — Total Assets equals Total Liabilities + Net Profit'
                : '<i class="bx bx-error me-2"></i><strong>UNBALANCED</strong> — Difference of ' + _fmt(diff) + '. Check if all transactions are posted through this system.')
            + '</div></div>';

        return html;
    }

    // ── Load button ───────────────────────────────────────────────────────────
    $('#bsLoadBtn').on('click', function () {
        var asOf    = $('#bsAsOfDate').val();
        var pnlFrom = $('#bsPnlFrom').val();
        if (!asOf) {
            Swal.fire({ icon: 'warning', title: 'Please select the As Of date', timer: 1800, showConfirmButton: false });
            return;
        }
        if (!pnlFrom) pnlFrom = asOf.substring(0, 4) + '-01-01';

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getBalanceSheetAjax',
            method:   'GET',
            data:     { AsOfDate: asOf, PnlFrom: pnlFrom, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }

                var isLoss = parseFloat(d.NetProfit || 0) < 0;
                $('#bsSummaryRow').removeClass('d-none');
                $('#bsSumAssets').text(_fmt(d.TotalAssets));
                $('#bsSumLiab').text(_fmt(d.TotalLiabilities));
                $('#bsSumNet').text(_fmt(d.NetProfit)).css('color', isLoss ? '#dc2626' : '#16a34a');
                $('#bsSumNetLabel').text(isLoss ? 'Net Loss' : 'Net Profit');
                $('#bsSumNetIcon').css('background', isLoss ? '#fee2e2' : '#dcfce7');
                $('#bsSumNetIco').css('color', isLoss ? '#dc2626' : '#16a34a');
                $('#bsBalancedBadge').toggleClass('d-none', !d.IsBalanced);
                $('#bsUnbalancedBadge').toggleClass('d-none', d.IsBalanced);
                $('#bsStatementArea').html('<div class="p-4">' + _bsBuildHtml(d) + '</div>');
                $('#bsPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Balance Sheet');
                ajaxLoading(0);
            }
        });
    });

    // ── Print ─────────────────────────────────────────────────────────────────
    $('#bsPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
