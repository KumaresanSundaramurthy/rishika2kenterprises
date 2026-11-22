<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Budget vs Actual',
                    'pageDescription' => 'Compare planned budgets against actual income and expenses',
                    'pageIcon'        => 'bx-bar-chart',
                    'pageIconBg'      => '#fef9c3',
                    'pageIconColor'   => '#a16207',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Financial Year</label>
                                    <select id="bvaFY" class="form-select form-select-sm">
                                        <?php
                                        $currentYear = (int)date('Y');
                                        for ($y = $currentYear + 1; $y >= $currentYear - 3; $y--):
                                        ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y === $currentYear ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>â€“<?php echo ($y + 1); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Actuals From</label>
                                    <input type="text" id="bvaDateFrom" class="form-control form-control-sm" readonly
                                           placeholder="From date">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Actuals To</label>
                                    <input type="text" id="bvaDateTo" class="form-control form-control-sm" readonly
                                           placeholder="To date">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-warning btn-sm" id="bvaLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Report
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="bvaPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Click any budget cell in the report to set or update the annual budget for that account.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3 d-none" id="bvaSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-income"><i class="bx bx-trending-up fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Budget Income</div>
                                        <div class="fw-bold text-success" style="font-size:1rem;" id="bvaSumBudgetInc">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);">Actual: <span id="bvaSumActualInc">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-expense"><i class="bx bx-trending-down fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Budget Expense</div>
                                        <div class="fw-bold text-danger" style="font-size:1rem;" id="bvaSumBudgetExp">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);">Actual: <span id="bvaSumActualExp">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-net" id="bvaSumNetIcon"><i class="bx bx-money fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Budget Net Profit</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="bvaSumBudgetNet">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);">Actual: <span id="bvaSumActualNet">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-var" id="bvaSumVarIcon"><i class="bx bx-transfer fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Net Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="bvaSumNetVar">—</div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-sm);" id="bvaSumVarLabel">vs Budget</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Report Area ────────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="bvaReportArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="bvaEmptyState">
                                <i class="bx bx-bar-chart fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Select FY and dates, then click Load Report</span>
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
        _fpFrom = flatpickr('#bvaDateFrom', $.extend({}, _fpCfg, {
            defaultDate: new Date(new Date().getFullYear(), 0, 1),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#bvaDateTo', $.extend({}, _fpCfg, {
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
        return _cur + ' ' + Math.abs(n || 0).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number|null} n
     * @returns {string}
     */
    function _bvaFmt(n) {
        if (n === null || n === undefined || Math.abs(n) < 0.005) return '<span class="text-muted bva-dash">—</span>';
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number|null} pct
     * @param {string}      type  'income' or 'expense'
     * @returns {string}
     */
    function _bvaPctBadge(pct, type) {
        if (pct === null || pct === undefined) return '<span class="text-muted" style="font-size:var(--r2k-text-sm);">—</span>';
        var cls;
        if (type === 'income') {
            cls = pct >= 100 ? 'bg-success' : (pct >= 75 ? 'bg-primary' : 'bg-warning');
        } else {
            cls = pct <= 100 ? 'bg-success' : (pct <= 115 ? 'bg-warning' : 'bg-danger');
        }
        return '<span class="badge ' + cls + ' bva-pct-badge">' + pct + '%</span>';
    }

    /**
     * @param {number} pct
     * @param {string} type
     * @returns {string}
     */
    function _bvaBar(pct, type) {
        var capped = Math.min(150, Math.abs(pct));
        var color;
        if (type === 'income') {
            color = pct >= 100 ? '#22c55e' : (pct >= 75 ? '#3b82f6' : '#f59e0b');
        } else {
            color = pct <= 100 ? '#22c55e' : (pct <= 115 ? '#f59e0b' : '#ef4444');
        }
        return '<div class="bva-mini-bar-wrap"><div class="bva-mini-bar" style="width:' + (capped / 1.5).toFixed(1) + '%;background:' + color + ';"></div></div>';
    }

    /**
     * @param {object[]} rows
     * @param {number}   fy
     * @param {string}   type  'income' or 'expense'
     * @returns {string}
     */
    function _bvaRows(rows, fy, type) {
        if (!rows || !rows.length) {
            return '<tr><td colspan="6" class="text-center text-muted py-3" style="font-size:var(--r2k-text-82);">No accounts</td></tr>';
        }
        var html = '';
        $.each(rows, function (i, r) {
            var bud = parseFloat(r.Budgeted || 0);
            var act = parseFloat(r.Actual || 0);
            var varV = parseFloat(r.Variance || 0);
            var isIncome = (type === 'income');
            var varGood = isIncome ? (varV >= 0) : (varV >= 0);
            var varCls  = varGood ? 'text-success' : 'text-danger';
            /* expense sign: positive = saved (shown as −), negative = over (shown as +) */
            var varSign = isIncome
                ? (varV >= 0 ? '+' : '−')
                : (varV >= 0 ? '−' : '+');
            var varHtml = Math.abs(varV) < 0.005 ? '<span class="text-muted">—</span>'
                : '<span class="' + varCls + ' fw-semibold">' + varSign + _cur + ' ' + Math.abs(varV).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }) + '</span>';

            html += '<tr>'
                + '<td style="font-size:var(--r2k-text-82);">' + _esc(r.LedgerName)
                + (r.LedgerCode ? '<code style="font-size:var(--r2k-text-70);color:#7c3aed;display:block;">' + _esc(r.LedgerCode) + '</code>' : '')
                + '</td>'
                + '<td class="text-end bva-budget-cell" style="font-size:var(--r2k-text-82);cursor:pointer;" data-ledger="' + parseInt(r.LedgerUID || 0, 10) + '" data-fy="' + fy + '" data-value="' + bud.toFixed(_dec) + '" title="Click to set budget">'
                + (Math.abs(bud) > 0.005 ? _bvaFmt(bud) : '<span class="text-muted">— Set —</span>')
                + '</td>'
                + '<td class="text-end fw-semibold ' + (isIncome ? 'text-success' : 'text-danger') + '" style="font-size:var(--r2k-text-82);">' + _bvaFmt(act) + '</td>'
                + '<td class="text-end" style="font-size:var(--r2k-text-82);">' + varHtml + '</td>'
                + '<td class="text-center" style="font-size:var(--r2k-text-82);">' + _bvaPctBadge(r.Pct !== undefined ? r.Pct : null, type) + '</td>'
                + '<td>' + (r.Pct !== null && r.Pct !== undefined ? _bvaBar(r.Pct, type) : '') + '</td>'
                + '</tr>';
        });
        return html;
    }

    /**
     * @param {object} d  NestJS getBudgetVActualAjax response
     * @returns {string}
     */
    function _bvaBuildHtml(d) {
        var orgName = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var fy = parseInt(d.FY || 0, 10);
        var t  = d.Totals || {};

        /* Totals */
        var budInc = parseFloat(t.budgetIncome || 0);
        var actInc = parseFloat(t.actualIncome || 0);
        var budExp = parseFloat(t.budgetExpense || 0);
        var actExp = parseFloat(t.actualExpense || 0);
        var budNet = parseFloat(t.budgetNet || 0);
        var actNet = parseFloat(t.actualNet || 0);
        var netVar = parseFloat(t.netVariance || 0);
        var netIsLoss   = actNet < 0;
        var netBudLoss  = budNet < 0;
        var netIsPos    = netVar >= 0;

        var incVar  = actInc - budInc;
        var incVarCls = incVar >= 0 ? 'text-success' : 'text-danger';
        var incPct  = budInc > 0.005 ? parseFloat((actInc / budInc * 100).toFixed(1)) : null;
        var expVar  = budExp - actExp;
        var expVarCls = expVar >= 0 ? 'text-success' : 'text-danger';
        var expPct  = budExp > 0.005 ? parseFloat((actExp / budExp * 100).toFixed(1)) : null;

        var html = '<div id="bvaReport">'
            + '<div class="text-center mb-3 pl-print-header">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold" style="font-size:.92rem;">Budget vs Actual</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-md);">FY ' + fy + '–' + (fy + 1) + ' &nbsp;·&nbsp; Actuals: ' + _esc(d.DateFrom || '') + ' to ' + _esc(d.DateTo || '') + '</div>'
            + '</div>';

        /* ── INCOME table ── */
        html += '<table class="table table-sm table-bordered align-middle bva-tbl mb-3">'
            + '<thead><tr class="bva-section-head bva-income-head"><th colspan="6">INCOME</th></tr>'
            + '<tr class="bva-col-head"><th>Account</th>'
            + '<th class="text-end bva-col-budget" style="width:140px;">Budget (FY ' + fy + ')<div class="text-muted fw-normal" style="font-size:var(--r2k-text-65);">Click to edit</div></th>'
            + '<th class="text-end" style="width:130px;">Actual</th><th class="text-end" style="width:120px;">Variance</th><th class="text-center" style="width:80px;">%</th><th style="width:100px;"></th></tr></thead>'
            + '<tbody>' + _bvaRows(d.Income || [], fy, 'income') + '</tbody>'
            + '<tfoot><tr class="bva-tfoot bva-income-tfoot">'
            + '<td class="fw-bold">TOTAL INCOME</td>'
            + '<td class="text-end fw-bold">' + _bvaFmt(budInc) + '</td>'
            + '<td class="text-end fw-bold text-success">' + _bvaFmt(actInc) + '</td>'
            + '<td class="text-end fw-bold ' + incVarCls + '">' + (Math.abs(incVar) > 0.005 ? (incVar >= 0 ? '+' : '−') + _cur + ' ' + Math.abs(incVar).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }) : '—') + '</td>'
            + '<td class="text-center">' + _bvaPctBadge(incPct, 'income') + '</td><td></td>'
            + '</tr></tfoot></table>';

        /* ── EXPENSES table ── */
        html += '<table class="table table-sm table-bordered align-middle bva-tbl mb-3">'
            + '<thead><tr class="bva-section-head bva-expense-head"><th colspan="6">EXPENSES</th></tr>'
            + '<tr class="bva-col-head"><th>Account</th>'
            + '<th class="text-end bva-col-budget" style="width:140px;">Budget (FY ' + fy + ')<div class="text-muted fw-normal" style="font-size:var(--r2k-text-65);">Click to edit</div></th>'
            + '<th class="text-end" style="width:130px;">Actual</th><th class="text-end" style="width:120px;">Variance</th><th class="text-center" style="width:80px;">%</th><th style="width:100px;"></th></tr></thead>'
            + '<tbody>' + _bvaRows(d.Expense || [], fy, 'expense') + '</tbody>'
            + '<tfoot><tr class="bva-tfoot bva-expense-tfoot">'
            + '<td class="fw-bold">TOTAL EXPENSES</td>'
            + '<td class="text-end fw-bold">' + _bvaFmt(budExp) + '</td>'
            + '<td class="text-end fw-bold text-danger">' + _bvaFmt(actExp) + '</td>'
            + '<td class="text-end fw-bold ' + expVarCls + '">' + (Math.abs(expVar) > 0.005 ? (expVar >= 0 ? '−' : '+') + _cur + ' ' + Math.abs(expVar).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }) : '—') + '</td>'
            + '<td class="text-center">' + _bvaPctBadge(expPct, 'expense') + '</td><td></td>'
            + '</tr></tfoot></table>';

        /* ── NET row ── */
        html += '<table class="table table-sm table-bordered align-middle bva-tbl mb-0"><tbody>'
            + '<tr class="bva-net-row ' + (netIsLoss ? 'bva-net-loss' : 'bva-net-profit') + '">'
            + '<td class="fw-bold" style="font-size:var(--r2k-text-90);">' + (netIsLoss ? 'NET DEFICIT (Budget: ' + (netBudLoss ? 'Deficit' : 'Profit') + ')' : 'NET SURPLUS (Profit)') + '</td>'
            + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);width:140px;color:' + (netBudLoss ? '#dc2626' : '#16a34a') + ';">' + _bvaFmt(budNet) + '</td>'
            + '<td class="text-end fw-bold" style="font-size:var(--r2k-text-88);width:130px;color:' + (netIsLoss ? '#dc2626' : '#16a34a') + ';">' + _bvaFmt(actNet) + '</td>'
            + '<td class="text-end fw-bold ' + (netIsPos ? 'text-success' : 'text-danger') + '" style="font-size:var(--r2k-text-88);width:120px;">'
            + (Math.abs(netVar) > 0.005 ? (netIsPos ? '+' : '−') + _cur + ' ' + Math.abs(netVar).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }) : '—') + '</td>'
            + '<td colspan="2"></td></tr></tbody></table></div>';

        return html;
    }

    $('#bvaLoadBtn').on('click', function () {
        var fy   = $('#bvaFY').val();
        var from = $('#bvaDateFrom').val();
        var to   = $('#bvaDateTo').val();
        if (!from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please select both dates', timer: 1800, showConfirmButton: false });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getBudgetVActualAjax',
            method:   'GET',
            data:     { FY: fy, DateFrom: from, DateTo: to, orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }

                var t     = d.Totals || {};
                var isNeg = (t.netVariance || 0) < 0;
                $('#bvaSummaryRow').removeClass('d-none');
                $('#bvaSumBudgetInc').text(_fmt(t.budgetIncome || 0));
                $('#bvaSumActualInc').text(_fmt(t.actualIncome || 0));
                $('#bvaSumBudgetExp').text(_fmt(t.budgetExpense || 0));
                $('#bvaSumActualExp').text(_fmt(t.actualExpense || 0));
                $('#bvaSumBudgetNet').text(_fmt(t.budgetNet || 0));
                $('#bvaSumActualNet').text(_fmt(t.actualNet || 0));
                $('#bvaSumNetVar').text(_fmt(t.netVariance || 0)).css('color', isNeg ? '#dc2626' : '#16a34a');
                $('#bvaSumVarIcon').css({ background: isNeg ? '#fee2e2' : '#dcfce7', color: isNeg ? '#dc2626' : '#16a34a' });
                $('#bvaSumNetIcon').css({ background: (t.budgetNet || 0) >= 0 ? '#dcfce7' : '#fee2e2', color: (t.budgetNet || 0) >= 0 ? '#16a34a' : '#dc2626' });
                $('#bvaReportArea').html('<div class="p-3">' + _bvaBuildHtml(d) + '</div>');
                $('#bvaPrintBtn').removeClass('d-none');

                _wireInlineEdit();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Report');
                ajaxLoading(0);
            }
        });
    });

    /**
     * @returns {void}
     */
    function _wireInlineEdit() {
        $('.bva-budget-cell[data-ledger]').off('click').on('click', function () {
            var $cell = $(this);
            if ($cell.find('input').length) return;
            var current = parseFloat($cell.data('value') || '0');
            var $input  = $('<input>').attr({ type: 'number', min: '0', step: '0.01' }).val(current.toFixed(_dec)).addClass('bva-budget-input');
            $cell.text('').append($input);
            $input[0].focus();
            $input[0].select();

            /**
             * @returns {void}
             */
            function _save() {
                var newVal = parseFloat($input.val()) || 0;
                $.ajax({
                    url:      global_services_url + 'api/accounting/saveBudgetAmount',
                    method:   'POST',
                    data:     { LedgerUID: $cell.data('ledger'), FY: $cell.data('fy'), Amount: newVal, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
                    dataType: 'json',
                    success: function (res) {
                        if (res.Error) {
                            Swal.fire({ icon: 'error', title: 'Save failed', text: res.Message });
                            $cell.text(_cur + ' ' + current.toFixed(_dec)).data('value', current);
                        } else {
                            $cell.text(newVal > 0 ? (_cur + ' ' + newVal.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec })) : '—').data('value', newVal);
                        }
                    },
                    error: function () {
                        $cell.text(_cur + ' ' + current.toFixed(_dec));
                    }
                });
            }

            $input.on('blur', function () { _save(); });
            $input.on('keydown', function (e) {
                if (e.key === 'Enter')  { $input[0].blur(); }
                if (e.key === 'Escape') { $cell.text(current > 0 ? (_cur + ' ' + current.toFixed(_dec)) : '—'); }
            });
        });
    }

    $('#bvaPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
