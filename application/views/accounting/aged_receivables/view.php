<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Aged Receivables',
                    'pageDescription' => 'Customer outstanding balances bucketed by overdue age',
                    'pageIcon'        => 'bx-receipt',
                    'pageIconBg'      => '#dbeafe',
                    'pageIconColor'   => '#1d4ed8',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- â”€â”€ Filter Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">As Of Date</label>
                                    <input type="text" id="arAsOfDate" class="form-control form-control-sm" readonly
                                           placeholder="Select date">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-primary btn-sm" id="arLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Report
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="arPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Summary Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="row g-3 mb-3 d-none" id="arSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="aged-sum-icon aged-icon-total">
                                        <i class="bx bx-dollar-circle fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Total Outstanding</div>
                                        <div class="fw-bold aged-val-total" style="font-size:1rem;" id="arSumTotal">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="aged-sum-icon aged-icon-current">
                                        <i class="bx bx-time fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Current (0â€“30 days)</div>
                                        <div class="fw-bold aged-val-current" style="font-size:1rem;" id="arSum0to30">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="aged-sum-icon aged-icon-warn">
                                        <i class="bx bx-alarm-exclamation fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">31â€“90 days</div>
                                        <div class="fw-bold aged-val-warn" style="font-size:1rem;" id="arSumMid">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="aged-sum-icon aged-icon-overdue">
                                        <i class="bx bx-error-circle fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">90+ days overdue</div>
                                        <div class="fw-bold aged-val-overdue" style="font-size:1rem;" id="arSum90plus">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Statement Area â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card">
                        <div class="card-body p-0" id="arStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="arEmptyState">
                                <i class="bx bx-receipt fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:.9rem;">Select a date and click Load Report</span>
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

    var _fpCfg = { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat || 'd M Y' };
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#arAsOfDate', Object.assign({}, _fpCfg, { defaultDate: new Date() }));
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    document.getElementById('arLoadBtn').addEventListener('click', function () {
        var asOf = document.getElementById('arAsOfDate').value;
        if (!asOf) {
            Swal.fire({ icon: 'warning', title: 'Please select the As Of date', timer: 1800, showConfirmButton: false });
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        ajaxLoading(1);

        var fd = new FormData();
        fd.append('AsOfDate', asOf);

        fetch(_baseUrl + 'accounting/getAgedReceivablesAjax', { method: 'POST', body: fd })
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
                document.getElementById('arSummaryRow').classList.remove('d-none');
                document.getElementById('arSumTotal').textContent  = _fmt(t.outstanding);
                document.getElementById('arSum0to30').textContent  = _fmt(t['0to30']);
                document.getElementById('arSumMid').textContent    = _fmt(t['31to60'] + t['61to90']);
                document.getElementById('arSum90plus').textContent = _fmt(t['90plus']);

                document.getElementById('arStatementArea').innerHTML = '<div class="p-3">' + d.Html + '</div>';
                document.getElementById('arPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Report';
                ajaxLoading(0);
            });
    });

    document.getElementById('arPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
