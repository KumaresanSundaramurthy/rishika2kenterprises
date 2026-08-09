<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
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
                            <span style="font-size:.9rem;">Select dates and click Compute Ratios</span>
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

    var _fpCfg = { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat || 'd M Y' };
    if (typeof flatpickr !== 'undefined') {
        var now   = new Date();
        var y1Jan = new Date(now.getFullYear(), 0, 1);
        flatpickr('#ratioAsOf',    Object.assign({}, _fpCfg, { defaultDate: now }));
        flatpickr('#ratioPnlFrom', Object.assign({}, _fpCfg, { defaultDate: y1Jan }));
        flatpickr('#ratioPnlTo',   Object.assign({}, _fpCfg, { defaultDate: now }));
    }

    document.getElementById('ratioLoadBtn').addEventListener('click', function () {
        var asOf  = document.getElementById('ratioAsOf').value;
        var from  = document.getElementById('ratioPnlFrom').value;
        var to    = document.getElementById('ratioPnlTo').value;
        if (!asOf || !from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please fill all date fields', timer: 1800, showConfirmButton: false });
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Computing...';
        ajaxLoading(1);

        var fd = new FormData();
        fd.append('AsOfDate', asOf);
        fd.append('PnlFrom',  from);
        fd.append('PnlTo',    to);

        fetch(_baseUrl + 'accounting/getRatiosAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Compute Ratios';
                ajaxLoading(0);
                if (d.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                    return;
                }
                document.getElementById('ratioDashArea').innerHTML = d.Html;
                document.getElementById('ratioPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Compute Ratios';
                ajaxLoading(0);
            });
    });

    document.getElementById('ratioPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_scripts'); ?>
