<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
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

                    <!-- â”€â”€ Filter Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
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

                    <!-- â”€â”€ Summary Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="row g-3 mb-3 d-none" id="cfSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-open"><i class="bx bx-wallet fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Opening Cash &amp; Bank</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cfSumOpen">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-in"><i class="bx bx-trending-up fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Total Inflows</div>
                                        <div class="fw-bold text-success" style="font-size:1rem;" id="cfSumIn">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-out"><i class="bx bx-trending-down fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Total Outflows</div>
                                        <div class="fw-bold text-danger" style="font-size:1rem;" id="cfSumOut">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="cf-sum-icon cf-icon-close" id="cfSumCloseIcon"><i class="bx bx-wallet-alt fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Closing Cash &amp; Bank</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cfSumClose">â€”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Statement Area â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card">
                        <div class="card-body p-0" id="cfStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="cfEmptyState">
                                <i class="bx bx-transfer-alt fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:.9rem;">Select dates and click Load Statement</span>
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
    var _fpFrom, _fpTo;
    if (typeof flatpickr !== 'undefined') {
        _fpFrom = flatpickr('#cfDateFrom', Object.assign({}, _fpCfg, {
            defaultDate: new Date(new Date().getFullYear(), 0, 1),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#cfDateTo', Object.assign({}, _fpCfg, {
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

    document.getElementById('cfLoadBtn').addEventListener('click', function () {
        var from = document.getElementById('cfDateFrom').value;
        var to   = document.getElementById('cfDateTo').value;
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
        fd.append('DateTo', to);

        fetch(_baseUrl + 'accounting/getCashFlowAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Statement';
                ajaxLoading(0);
                if (d.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                    return;
                }

                document.getElementById('cfSummaryRow').classList.remove('d-none');
                document.getElementById('cfSumOpen').textContent  = _fmt(d.GrandOpeningBal);
                document.getElementById('cfSumIn').textContent    = _fmt(d.GrandPeriodDr);
                document.getElementById('cfSumOut').textContent   = _fmt(d.GrandPeriodCr);
                document.getElementById('cfSumClose').textContent = _fmt(d.GrandClosingBal);

                var closeIcon = document.getElementById('cfSumCloseIcon');
                var netPos    = d.NetChange >= 0;
                closeIcon.style.background = netPos ? '#dcfce7' : '#fee2e2';
                closeIcon.style.color      = netPos ? '#059669' : '#dc2626';

                document.getElementById('cfStatementArea').innerHTML = '<div class="p-3">' + d.Html + '</div>';
                document.getElementById('cfPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Statement';
                ajaxLoading(0);
            });
    });

    document.getElementById('cfPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
