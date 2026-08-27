<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
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

                    <!-- â”€â”€ Filter Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
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

                    <!-- â”€â”€ Summary Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="row g-3 mb-3 d-none" id="plSummaryRow">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" style="background:#dcfce7;">
                                        <i class="bx bx-trending-up fs-4" style="color:#16a34a;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Total Income</div>
                                        <div class="fw-bold text-success" style="font-size:1.1rem;" id="plSumIncome">â€”</div>
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
                                        <div class="text-muted" style="font-size:.75rem;">Total Expenses</div>
                                        <div class="fw-bold text-danger" style="font-size:1.1rem;" id="plSumExpense">â€”</div>
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
                                        <div class="text-muted" style="font-size:.75rem;" id="plSumNetLabel">Net Profit</div>
                                        <div class="fw-bold" style="font-size:1.1rem;" id="plSumNet">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Statement Area â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card">
                        <div class="card-body p-0" id="plStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="plEmptyState">
                                <i class="bx bx-bar-chart-alt-2 fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:.9rem;">Select a date range and click Load P&amp;L</span>
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
    var _dec     = 2;

    // â”€â”€ Flatpickr â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var _fpCfg = { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat || 'd M Y' };
    var _fpFrom, _fpTo;
    if (typeof flatpickr !== 'undefined') {
        _fpFrom = flatpickr('#plDateFrom', Object.assign({}, _fpCfg, {
            defaultDate: new Date(new Date().getFullYear(), 0, 1),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#plDateTo', Object.assign({}, _fpCfg, {
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

    // â”€â”€ Load button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('plLoadBtn').addEventListener('click', function () {
        var from = document.getElementById('plDateFrom').value;
        var to   = document.getElementById('plDateTo').value;
        if (!from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please select both dates', timer: 1800, showConfirmButton: false });
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        ajaxLoading(1);

        var fd = new FormData();
        fd.append('DateFrom', from);
        fd.append('DateTo',   to);

        fetch(_baseUrl + 'accounting/getPandLAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load P&amp;L';
                ajaxLoading(0);
                if (d.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                    return;
                }

                // Update summary cards
                document.getElementById('plSummaryRow').classList.remove('d-none');
                document.getElementById('plSumIncome').textContent  = _fmt(d.TotalIncome);
                document.getElementById('plSumExpense').textContent = _fmt(d.TotalExpense);

                var netEl    = document.getElementById('plSumNet');
                var netLabel = document.getElementById('plSumNetLabel');
                var netIcon  = document.getElementById('plSumNetIcon');
                var netIco   = document.getElementById('plSumNetIco');
                var isLoss   = d.NetProfit < 0;
                netEl.textContent      = _fmt(d.NetProfit);
                netEl.style.color      = isLoss ? '#dc2626' : '#16a34a';
                netLabel.textContent   = isLoss ? 'Net Loss' : 'Net Profit';
                netIcon.style.background = isLoss ? '#fee2e2' : '#dcfce7';
                netIco.style.color     = isLoss ? '#dc2626' : '#16a34a';
                netIco.className       = isLoss ? 'bx bx-trending-down fs-4' : 'bx bx-trending-up fs-4';

                // Render statement
                var area = document.getElementById('plStatementArea');
                area.innerHTML = '<div class="p-4">' + d.Html + '</div>';

                document.getElementById('plPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load P&amp;L';
                ajaxLoading(0);
            });
    });

    // â”€â”€ Print â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('plPrintBtn').addEventListener('click', function () {
        window.print();
    });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
