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
                                        <div class="text-muted" style="font-size:.75rem;">Income Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumIncVar">—</div>
                                        <div class="text-muted" style="font-size:.72rem;" id="cmpSumIncPct">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-exp"><i class="bx bx-trending-down fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Expense Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumExpVar">—</div>
                                        <div class="text-muted" style="font-size:.72rem;" id="cmpSumExpPct">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-net" id="cmpSumNetIcon"><i class="bx bx-money fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Period 1 Net</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumNet1">—</div>
                                        <div class="text-muted" style="font-size:.72rem;">P2: <span id="cmpSumNet2">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="comp-sum-icon comp-icon-var" id="cmpSumVarIcon"><i class="bx bx-transfer fs-4"></i></div>
                                    <div>
                                        <div class="text-muted" style="font-size:.75rem;">Net Variance</div>
                                        <div class="fw-bold" style="font-size:1rem;" id="cmpSumNetVar">—</div>
                                        <div class="text-muted" style="font-size:.72rem;" id="cmpSumNetPct">—</div>
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
                                <span style="font-size:.9rem;">Enter period dates and click Load Report</span>
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

    if (typeof flatpickr !== 'undefined') {
        var now   = new Date();
        var y1Jan = new Date(now.getFullYear(), 0, 1);
        var lyJan = new Date(now.getFullYear() - 1, 0, 1);
        var lyDec = new Date(now.getFullYear() - 1, 11, 31);

        flatpickr('#cmpP1From', Object.assign({}, _fpCfg, { defaultDate: y1Jan }));
        flatpickr('#cmpP1To',   Object.assign({}, _fpCfg, { defaultDate: now }));
        flatpickr('#cmpP2From', Object.assign({}, _fpCfg, { defaultDate: lyJan }));
        flatpickr('#cmpP2To',   Object.assign({}, _fpCfg, { defaultDate: lyDec }));
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
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

    document.getElementById('cmpLoadBtn').addEventListener('click', function () {
        var p1From  = document.getElementById('cmpP1From').value;
        var p1To    = document.getElementById('cmpP1To').value;
        var p2From  = document.getElementById('cmpP2From').value;
        var p2To    = document.getElementById('cmpP2To').value;
        var p1Label = document.getElementById('cmpP1Label').value || 'Period 1';
        var p2Label = document.getElementById('cmpP2Label').value || 'Period 2';

        if (!p1From || !p1To || !p2From || !p2To) {
            Swal.fire({ icon: 'warning', title: 'Please fill all date fields', timer: 1800, showConfirmButton: false });
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        ajaxLoading(1);

        var fd = new FormData();
        fd.append('P1From',  p1From);
        fd.append('P1To',    p1To);
        fd.append('P2From',  p2From);
        fd.append('P2To',    p2To);
        fd.append('P1Label', p1Label);
        fd.append('P2Label', p2Label);

        fetch(_baseUrl + 'accounting/getComparativePnLAjax', { method: 'POST', body: fd })
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
                document.getElementById('cmpSummaryRow').classList.remove('d-none');

                var incEl  = document.getElementById('cmpSumIncVar');
                var expEl  = document.getElementById('cmpSumExpVar');
                var netEl  = document.getElementById('cmpSumNetVar');
                var n1El   = document.getElementById('cmpSumNet1');
                var n2El   = document.getElementById('cmpSumNet2');

                incEl.textContent  = _fmt(t.incVar);
                incEl.style.color  = t.incVar >= 0 ? '#16a34a' : '#dc2626';
                document.getElementById('cmpSumIncPct').textContent = _pctLabel(t.incPct, true);

                expEl.textContent  = _fmt(t.expVar);
                expEl.style.color  = t.expVar <= 0 ? '#16a34a' : '#dc2626';
                document.getElementById('cmpSumExpPct').textContent = _pctLabel(t.expPct, false);

                n1El.textContent   = _fmt(t.net1);
                n1El.style.color   = t.net1 >= 0 ? '#16a34a' : '#dc2626';
                n2El.textContent   = _fmt(t.net2);

                netEl.textContent  = _fmt(t.netVar);
                netEl.style.color  = t.netVar >= 0 ? '#16a34a' : '#dc2626';
                document.getElementById('cmpSumNetPct').textContent = _pctLabel(t.netPct, true);

                var netIcon = document.getElementById('cmpSumNetIcon');
                var varIcon = document.getElementById('cmpSumVarIcon');
                netIcon.style.background = t.net1 >= 0 ? '#dcfce7' : '#fee2e2';
                netIcon.style.color      = t.net1 >= 0 ? '#16a34a' : '#dc2626';
                varIcon.style.background = t.netVar >= 0 ? '#dcfce7' : '#fee2e2';
                varIcon.style.color      = t.netVar >= 0 ? '#16a34a' : '#dc2626';

                document.getElementById('cmpReportArea').innerHTML = '<div class="p-3">' + d.Html + '</div>';
                document.getElementById('cmpPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Report';
                ajaxLoading(0);
            });
    });

    document.getElementById('cmpPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_scripts'); ?>
