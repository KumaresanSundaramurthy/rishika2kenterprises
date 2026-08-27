<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
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

                    <!-- â”€â”€ Filter Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
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

                    <!-- â”€â”€ Summary Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="row g-3 mb-3 d-none" id="bsSummaryRow">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="pl-sum-icon" style="background:#eff6ff;">
                                        <i class="bx bx-building fs-4" style="color:#1d4ed8;"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Total Assets</div>
                                        <div class="fw-bold" style="font-size:1.1rem;color:#1d4ed8;" id="bsSumAssets">â€”</div>
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
                                        <div class="text-muted" style="font-size:.75rem;">Total Liabilities</div>
                                        <div class="fw-bold" style="font-size:1.1rem;color:#9a3412;" id="bsSumLiab">â€”</div>
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
                                        <div class="text-muted" style="font-size:.75rem;" id="bsSumNetLabel">Net Profit</div>
                                        <div class="fw-bold" style="font-size:1.1rem;" id="bsSumNet">â€”</div>
                                        <div class="d-none" id="bsBalancedBadge">
                                            <span class="badge bg-success" style="font-size:.65rem;">Balanced âœ“</span>
                                        </div>
                                        <div class="d-none" id="bsUnbalancedBadge">
                                            <span class="badge bg-danger" style="font-size:.65rem;">Unbalanced âœ—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Statement Area â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card">
                        <div class="card-body p-0" id="bsStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="bsEmptyState">
                                <i class="bx bx-equalizer fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:.9rem;">Select dates and click Load Balance Sheet</span>
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
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#bsAsOfDate', Object.assign({}, _fpCfg, { defaultDate: new Date() }));
        flatpickr('#bsPnlFrom',  Object.assign({}, _fpCfg, { defaultDate: new Date(new Date().getFullYear(), 0, 1) }));
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    // â”€â”€ Load button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('bsLoadBtn').addEventListener('click', function () {
        var asOf    = document.getElementById('bsAsOfDate').value;
        var pnlFrom = document.getElementById('bsPnlFrom').value;
        if (!asOf) {
            Swal.fire({ icon: 'warning', title: 'Please select the As Of date', timer: 1800, showConfirmButton: false });
            return;
        }
        if (!pnlFrom) pnlFrom = asOf.substring(0, 4) + '-01-01';

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        ajaxLoading(1);

        var fd = new FormData();
        fd.append('AsOfDate', asOf);
        fd.append('PnlFrom',  pnlFrom);

        fetch(_baseUrl + 'accounting/getBalanceSheetAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Balance Sheet';
                ajaxLoading(0);
                if (d.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                    return;
                }

                // Summary cards
                document.getElementById('bsSummaryRow').classList.remove('d-none');
                document.getElementById('bsSumAssets').textContent = _fmt(d.TotalAssets);
                document.getElementById('bsSumLiab').textContent   = _fmt(d.TotalLiabilities);

                var netEl    = document.getElementById('bsSumNet');
                var netLabel = document.getElementById('bsSumNetLabel');
                var netIcon  = document.getElementById('bsSumNetIcon');
                var netIco   = document.getElementById('bsSumNetIco');
                var isLoss   = d.NetProfit < 0;
                netEl.textContent        = _fmt(d.NetProfit);
                netEl.style.color        = isLoss ? '#dc2626' : '#16a34a';
                netLabel.textContent     = isLoss ? 'Net Loss' : 'Net Profit';
                netIcon.style.background = isLoss ? '#fee2e2' : '#dcfce7';
                netIco.style.color       = isLoss ? '#dc2626' : '#16a34a';

                document.getElementById('bsBalancedBadge').classList.toggle('d-none',   !d.IsBalanced);
                document.getElementById('bsUnbalancedBadge').classList.toggle('d-none',  d.IsBalanced);

                // Statement
                var area = document.getElementById('bsStatementArea');
                area.innerHTML = '<div class="p-4">' + d.Html + '</div>';
                document.getElementById('bsPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Balance Sheet';
                ajaxLoading(0);
            });
    });

    // â”€â”€ Print â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('bsPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
