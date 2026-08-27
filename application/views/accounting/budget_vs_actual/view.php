<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
$dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
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

                    <!-- â”€â”€ Filter Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
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

                    <!-- â”€â”€ Summary Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="row g-3 mb-3 d-none" id="bvaSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-income"><i class="bx bx-trending-up fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Budget Income</div>
                                        <div class="fw-bold text-success" style="font-size:1rem;" id="bvaSumBudgetInc">â€”</div>
                                        <div class="text-muted" style="font-size:.72rem;">Actual: <span id="bvaSumActualInc">â€”</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-expense"><i class="bx bx-trending-down fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Budget Expense</div>
                                        <div class="fw-bold text-danger" style="font-size:1rem;" id="bvaSumBudgetExp">â€”</div>
                                        <div class="text-muted" style="font-size:.72rem;">Actual: <span id="bvaSumActualExp">â€”</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-net" id="bvaSumNetIcon"><i class="bx bx-money fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Budget Net Profit</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="bvaSumBudgetNet">â€”</div>
                                        <div class="text-muted" style="font-size:.72rem;">Actual: <span id="bvaSumActualNet">â€”</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="bva-sum-icon bva-icon-var" id="bvaSumVarIcon"><i class="bx bx-transfer fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Net Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="bvaSumNetVar">â€”</div>
                                        <div class="text-muted" style="font-size:.72rem;" id="bvaSumVarLabel">vs Budget</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Report Area â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card">
                        <div class="card-body p-0" id="bvaReportArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="bvaEmptyState">
                                <i class="bx bx-bar-chart fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:.9rem;">Select FY and dates, then click Load Report</span>
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
(function () {
    'use strict';

    var _baseUrl = '<?php echo base_url(); ?>';
    var _cur     = '<?php echo $cur; ?>';
    var _dec     = <?php echo $dec; ?>;

    var _fpCfg = { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat || 'd M Y' };
    var _fpFrom, _fpTo;
    if (typeof flatpickr !== 'undefined') {
        _fpFrom = flatpickr('#bvaDateFrom', Object.assign({}, _fpCfg, {
            defaultDate: new Date(new Date().getFullYear(), 0, 1),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#bvaDateTo', Object.assign({}, _fpCfg, {
            defaultDate: new Date(),
            onChange: function (d) { if (_fpFrom && d[0]) _fpFrom.set('maxDate', d[0]); }
        }));
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    document.getElementById('bvaLoadBtn').addEventListener('click', function () {
        var fy   = document.getElementById('bvaFY').value;
        var from = document.getElementById('bvaDateFrom').value;
        var to   = document.getElementById('bvaDateTo').value;
        if (!from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please select both dates', timer: 1800, showConfirmButton: false });
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        ajaxLoading(1);

        var fd = new FormData();
        fd.append('FY', fy);
        fd.append('DateFrom', from);
        fd.append('DateTo', to);

        fetch(_baseUrl + 'accounting/getBudgetVActualAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Report';
                ajaxLoading(0);
                if (d.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                    return;
                }

                var t = d.Totals;
                document.getElementById('bvaSummaryRow').classList.remove('d-none');
                document.getElementById('bvaSumBudgetInc').textContent  = _fmt(t.budgetIncome);
                document.getElementById('bvaSumActualInc').textContent  = _fmt(t.actualIncome);
                document.getElementById('bvaSumBudgetExp').textContent  = _fmt(t.budgetExpense);
                document.getElementById('bvaSumActualExp').textContent  = _fmt(t.actualExpense);
                document.getElementById('bvaSumBudgetNet').textContent  = _fmt(t.budgetNet);
                document.getElementById('bvaSumActualNet').textContent  = _fmt(t.actualNet);
                document.getElementById('bvaSumNetVar').textContent     = _fmt(t.netVariance);

                var isNeg = t.netVariance < 0;
                var varIcon  = document.getElementById('bvaSumVarIcon');
                var netIcon  = document.getElementById('bvaSumNetIcon');
                varIcon.style.background  = isNeg ? '#fee2e2' : '#dcfce7';
                varIcon.style.color       = isNeg ? '#dc2626' : '#16a34a';
                netIcon.style.background  = t.budgetNet >= 0 ? '#dcfce7' : '#fee2e2';
                netIcon.style.color       = t.budgetNet >= 0 ? '#16a34a' : '#dc2626';
                document.getElementById('bvaSumNetVar').style.color = isNeg ? '#dc2626' : '#16a34a';

                document.getElementById('bvaReportArea').innerHTML = '<div class="p-3">' + d.Html + '</div>';
                document.getElementById('bvaPrintBtn').classList.remove('d-none');

                // Wire inline budget editing
                _wireInlineEdit();
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Report';
                ajaxLoading(0);
            });
    });

    /**
     * @returns {void}
     */
    function _wireInlineEdit() {
        var cells = document.querySelectorAll('.bva-budget-cell[data-ledger]');
        cells.forEach(function (cell) {
            cell.addEventListener('click', function () {
                if (cell.querySelector('input')) return; // already editing
                var current = parseFloat(cell.dataset.value || '0');
                var input = document.createElement('input');
                input.type = 'number';
                input.min  = '0';
                input.step = '0.01';
                input.value = current.toFixed(_dec);
                input.className = 'bva-budget-input';
                cell.textContent = '';
                cell.appendChild(input);
                input.focus();
                input.select();

                /**
                 * @returns {void}
                 */
                function _save() {
                    var newVal = parseFloat(input.value) || 0;
                    var ledger = cell.dataset.ledger;
                    var fy     = cell.dataset.fy;
                    var fd = new FormData();
                    fd.append('LedgerUID', ledger);
                    fd.append('FY', fy);
                    fd.append('Amount', newVal);
                    fetch(_baseUrl + 'accounting/saveBudgetAmount', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (res.Error) {
                                Swal.fire({ icon: 'error', title: 'Save failed', text: res.Message });
                                cell.textContent = _cur + ' ' + current.toFixed(_dec);
                                cell.dataset.value = current;
                            } else {
                                cell.textContent   = newVal > 0 ? (_cur + ' ' + newVal.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec })) : 'â€”';
                                cell.dataset.value = newVal;
                            }
                        })
                        .catch(function () {
                            cell.textContent = _cur + ' ' + current.toFixed(_dec);
                        });
                }

                input.addEventListener('blur',  function () { _save(); });
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter')  { input.blur(); }
                    if (e.key === 'Escape') { cell.textContent = current > 0 ? (_cur + ' ' + current.toFixed(_dec)) : 'â€”'; }
                });
            });
        });
    }

    document.getElementById('bvaPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
