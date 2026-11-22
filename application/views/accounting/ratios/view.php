<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Financial Ratios',
                    'pageDescription' => 'Liquidity, profitability, activity and leverage ratios at a glance',
                    'pageIcon'        => 'bx-analyse',
                    'pageIconBg'      => '#f5f3ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Balance Sheet As Of</label>
                                    <input type="text" id="ratioAsOf" class="form-control form-control-sm" readonly
                                           placeholder="Balance sheet date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">P&amp;L From</label>
                                    <input type="text" id="ratioPnlFrom" class="form-control form-control-sm" readonly
                                           placeholder="Income statement from">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">P&amp;L To</label>
                                    <input type="text" id="ratioPnlTo" class="form-control form-control-sm" readonly
                                           placeholder="Income statement to">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-sm btn-primary" id="ratioLoadBtn"
                                            style="background:#7c3aed;border-color:#7c3aed;">
                                        <i class="bx bx-play me-1"></i>Compute Ratios
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="ratioPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Balance sheet date determines assets/liabilities snapshot.
                                    P&amp;L period is used for income-based ratios (margin, ROA, ROE, DRO, DPO).
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- ── Dashboard Area ─────────────────────────────────── -->
                    <div id="ratioDashArea">
                        <div class="d-flex flex-column align-items-center py-5 text-muted" id="ratioEmptyState">
                            <i class="bx bx-analyse fs-1 mb-2" style="color:#ccc;"></i>
                            <span style="font-size:var(--r2k-text-90);">Select dates and click Compute Ratios</span>
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
        flatpickr('#ratioAsOf',    $.extend({}, _fpCfg, { defaultDate: now }));
        flatpickr('#ratioPnlFrom', $.extend({}, _fpCfg, { defaultDate: y1Jan }));
        flatpickr('#ratioPnlTo',   $.extend({}, _fpCfg, { defaultDate: now }));
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
    function _fmtN(n) {
        return _cur + ' ' + Math.abs(n || 0).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number|null} v
     * @param {number}      dp
     * @param {string}      suffix
     * @returns {string}
     */
    function _ratioVal(v, dp, suffix) {
        dp = dp !== undefined ? dp : 2;
        suffix = suffix || '';
        if (v === null || v === undefined) return '<span class="ratio-na">N/A</span>';
        return '<span class="ratio-number">' + parseFloat(v).toFixed(dp) + suffix + '</span>';
    }

    /**
     * @param {number|null} v
     * @param {number}      good
     * @param {number}      warn
     * @param {boolean}     higherIsBetter
     * @returns {string}
     */
    function _ratioBadge(v, good, warn, higherIsBetter) {
        if (v === null || v === undefined) return '';
        higherIsBetter = (higherIsBetter !== false);
        var cls, lbl;
        if (higherIsBetter) {
            cls = v >= good ? 'ratio-badge-good' : (v >= warn ? 'ratio-badge-warn' : 'ratio-badge-bad');
            lbl = v >= good ? 'Strong' : (v >= warn ? 'Adequate' : 'Weak');
        } else {
            cls = v <= good ? 'ratio-badge-good' : (v <= warn ? 'ratio-badge-warn' : 'ratio-badge-bad');
            lbl = v <= good ? 'Good' : (v <= warn ? 'Moderate' : 'High');
        }
        return '<span class="ratio-badge ' + cls + '">' + lbl + '</span>';
    }

    /**
     * @param {object} r  NestJS getRatiosAjax response (Ratios object at top level)
     * @returns {string}
     */
    function _ratiosBuildHtml(r) {
        var orgName  = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var eqColor  = (r.equity || 0) >= 0 ? '#059669' : '#dc2626';
        var npColor  = (r.netProfit || 0) >= 0 ? '#059669' : '#dc2626';
        var asOf     = _esc(r.AsOfDate || '');
        var pnlFrom  = _esc(r.PnlFrom || '');
        var pnlTo    = _esc(r.PnlTo || '');

        var html = '<div id="ratioDashboard">'
            + '<div class="text-center mb-4 pl-print-header">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold" style="font-size:.92rem;">Financial Ratios Dashboard</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-78);">Balance Sheet: ' + asOf + ' &nbsp;·&nbsp; P&amp;L: ' + pnlFrom + ' – ' + pnlTo + '</div>'
            + '</div>'
            + '<div class="row g-3 mb-4"><div class="col-12"><div class="row g-2">';

        /* ── Summary KPI cards ── */
        var kpis = [
            { label: 'Total Assets',      val: _fmtN(r.totalAssets),  icon: 'bx-building-house', col: '#1d4ed8' },
            { label: 'Total Liabilities', val: _fmtN(r.totalLiab),    icon: 'bx-credit-card',    col: '#dc2626' },
            { label: 'Equity',            val: _fmtN(r.equity),       icon: 'bx-dollar-circle',  col: eqColor  },
            { label: 'Net Profit',        val: _fmtN(r.netProfit),    icon: 'bx-trending-up',    col: npColor  },
        ];
        $.each(kpis, function (i, k) {
            html += '<div class="col-md-3"><div class="card ratio-summary-card"><div class="card-body p-3 d-flex align-items-center gap-2">'
                + '<i class="bx ' + k.icon + ' fs-3" style="color:' + k.col + ';"></i>'
                + '<div><div class="text-muted" style="font-size:var(--r2k-text-sm);">' + k.label + '</div>'
                + '<div class="fw-bold" style="font-size:var(--r2k-text-90);color:' + k.col + ';">' + k.val + '</div>'
                + '</div></div></div></div>';
        });
        html += '</div></div>';

        /* helper: ratio table card */
        /**
         * @param {string}   title
         * @param {string}   headCls
         * @param {string}   icon
         * @param {Array}    rows
         * @returns {string}
         */
        function _ratioCard(title, headCls, icon, rows) {
            var t = '<div class="col-md-6"><div class="card h-100"><div class="card-header ratio-group-head ' + headCls + '"><i class="bx ' + icon + ' me-2"></i>' + title + '</div>'
                + '<div class="card-body p-0"><table class="table table-sm ratio-tbl mb-0">'
                + '<thead><tr class="ratio-col-head"><th>Ratio</th><th class="text-end">Value</th><th class="text-center">Signal</th><th class="text-muted ratio-bench-col">Benchmark</th><th class="ratio-explain-col">Explanation</th></tr></thead><tbody>';
            $.each(rows, function (i, row) {
                t += '<tr><td class="ratio-name">' + row.name + '</td>'
                    + '<td class="text-end">' + row.val + '</td>'
                    + '<td class="text-center">' + row.badge + '</td>'
                    + '<td class="ratio-bench">' + row.bench + '</td>'
                    + '<td class="ratio-explain">' + row.explain + '</td></tr>';
            });
            t += '</tbody></table></div></div></div>';
            return t;
        }

        html += _ratioCard('Liquidity Ratios', 'ratio-liquidity-head', 'bx-water', [
            { name: 'Current Ratio',  val: _ratioVal(r.currentRatio),         badge: _ratioBadge(r.currentRatio, 2.0, 1.0),         bench: '≥ 2.0',   explain: 'Assets ÷ Liabilities. >2 = strong buffer.' },
            { name: 'Quick Ratio',    val: _ratioVal(r.quickRatio),           badge: _ratioBadge(r.quickRatio, 1.0, 0.5),           bench: '≥ 1.0',   explain: '(Cash+Receivables) ÷ Liabilities. Excludes stock.' },
            { name: 'Cash Ratio',     val: _ratioVal(r.cashRatio),            badge: _ratioBadge(r.cashRatio, 0.5, 0.2),            bench: '≥ 0.5',   explain: 'Cash &amp; Bank ÷ Liabilities. Most conservative.' },
        ]);
        html += _ratioCard('Profitability Ratios', 'ratio-profit-head', 'bx-line-chart', [
            { name: 'Net Profit Margin',       val: _ratioVal(r.netProfitMargin, 1, '%'), badge: _ratioBadge(r.netProfitMargin, 10.0, 5.0),  bench: '≥ 10%', explain: 'Net Profit ÷ Revenue × 100.' },
            { name: 'Return on Assets (ROA)',  val: _ratioVal(r.roa, 1, '%'),            badge: _ratioBadge(r.roa, 5.0, 2.0),               bench: '≥ 5%',  explain: 'Net Profit ÷ Total Assets × 100.' },
            { name: 'Return on Equity (ROE)',  val: _ratioVal(r.roe, 1, '%'),            badge: _ratioBadge(r.roe, 15.0, 8.0),              bench: '≥ 15%', explain: 'Net Profit ÷ Equity × 100.' },
        ]);
        html += _ratioCard('Activity Ratios', 'ratio-activity-head', 'bx-refresh', [
            { name: 'Receivables Days (DRO)', val: _ratioVal(r.receivablesDays, 0, ' days'), badge: _ratioBadge(r.receivablesDays, 30.0, 60.0, false), bench: '≤ 30 days', explain: 'Avg days to collect from customers.' },
            { name: 'Payables Days (DPO)',    val: _ratioVal(r.payablesDays, 0, ' days'),    badge: _ratioBadge(r.payablesDays, 45.0, 30.0, false),   bench: '≥ 30 days', explain: 'Avg days before paying vendors.' },
        ]);
        html += _ratioCard('Leverage Ratios', 'ratio-leverage-head', 'bx-buildings', [
            { name: 'Debt to Equity', val: _ratioVal(r.debtToEquity),          badge: _ratioBadge(r.debtToEquity, 1.0, 2.0, false),  bench: '≤ 1.0',   explain: 'Total Liabilities ÷ Equity. Lower = less leveraged.' },
            { name: 'Equity Ratio',   val: _ratioVal(r.equityRatio, 1, '%'),   badge: _ratioBadge(r.equityRatio, 50.0, 30.0),        bench: '≥ 50%',   explain: 'Equity ÷ Total Assets × 100.' },
        ]);

        html += '</div>'
            + '<div class="text-muted mb-3 ratio-footnote"><i class="bx bx-info-circle me-1"></i>Benchmarks are general industry guidelines. Actual targets vary by sector and business model. Receivables and Payables ratios use annualised P&amp;L income/expense figures.</div>'
            + '</div>';

        return html;
    }

    $('#ratioLoadBtn').on('click', function () {
        var asOf = $('#ratioAsOf').val();
        var from = $('#ratioPnlFrom').val();
        var to   = $('#ratioPnlTo').val();
        if (!asOf || !from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please fill all date fields', timer: 1800, showConfirmButton: false });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Computing...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getRatiosAjax',
            method:   'GET',
            data:     { AsOfDate: asOf, PnlFrom: from, PnlTo: to, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                $('#ratioDashArea').html(_ratiosBuildHtml(d));
                $('#ratioPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Compute Ratios');
                ajaxLoading(0);
            }
        });
    });

    $('#ratioPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
