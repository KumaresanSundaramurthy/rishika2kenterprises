<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $CashBankLedgers */ $CashBankLedgers = $CashBankLedgers ?? [];
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
$dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Day Book',
                    'pageDescription' => 'Chronological journal entry view for any date range',
                    'pageIcon'        => 'bx-book',
                    'pageIconBg'      => '#f0fdf4',
                    'pageIconColor'   => '#15803d',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- â”€â”€ Filter Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">From Date</label>
                                    <input type="text" id="dbDateFrom" class="form-control form-control-sm" readonly
                                           placeholder="From">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">To Date</label>
                                    <input type="text" id="dbDateTo" class="form-control form-control-sm" readonly
                                           placeholder="To">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Account Filter</label>
                                    <select id="dbAccountFilter" class="form-select form-select-sm">
                                        <option value="all">All Accounts (Day Book)</option>
                                        <option value="cashbank">Cash &amp; Bank Only (Cash Book)</option>
                                        <?php if (!empty($CashBankLedgers)): ?>
                                        <optgroup label="Specific Account">
                                            <?php foreach ($CashBankLedgers as $l): ?>
                                            <option value="ledger:<?php echo (int)$l->LedgerUID; ?>">
                                                <?php echo htmlspecialchars($l->LedgerType . ' â€” ' . $l->LedgerName); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-success btn-sm" id="dbLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Day Book
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="dbPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- â”€â”€ Summary bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="d-none mb-3" id="dbSummaryBar">
                        <div class="d-flex gap-3 flex-wrap align-items-center">
                            <span class="badge bg-label-success" style="font-size:.8rem;">
                                <i class="bx bx-trending-up me-1"></i>Total Dr: <strong id="dbSumDr">â€”</strong>
                            </span>
                            <span class="badge bg-label-danger" style="font-size:.8rem;">
                                <i class="bx bx-trending-down me-1"></i>Total Cr: <strong id="dbSumCr">â€”</strong>
                            </span>
                            <span class="text-muted" style="font-size:.78rem;" id="dbDayCount"></span>
                        </div>
                    </div>

                    <!-- â”€â”€ Entries Area â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                    <div class="card">
                        <div class="card-body p-0" id="dbEntriesArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="dbEmptyState">
                                <i class="bx bx-book fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:.9rem;">Select dates and click Load Day Book</span>
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
        _fpFrom = flatpickr('#dbDateFrom', Object.assign({}, _fpCfg, {
            defaultDate: new Date(),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); }
        }));
        _fpTo = flatpickr('#dbDateTo', Object.assign({}, _fpCfg, {
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

    document.getElementById('dbLoadBtn').addEventListener('click', function () {
        var from   = document.getElementById('dbDateFrom').value;
        var to     = document.getElementById('dbDateTo').value;
        var filter = document.getElementById('dbAccountFilter').value;

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
        fd.append('CashBankOnly', filter === 'cashbank' ? '1' : '0');
        fd.append('LedgerUID',    filter.startsWith('ledger:') ? filter.split(':')[1] : '0');

        fetch(_baseUrl + 'accounting/getDayBookAjax', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Day Book';
                ajaxLoading(0);
                if (d.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                    return;
                }

                document.getElementById('dbSummaryBar').classList.remove('d-none');
                document.getElementById('dbSumDr').textContent = _fmt(d.GrandDr);
                document.getElementById('dbSumCr').textContent = _fmt(d.GrandCr);
                document.getElementById('dbDayCount').textContent = d.DayCount + ' day(s)';

                document.getElementById('dbEntriesArea').innerHTML = d.Html || '<div class="text-center text-muted py-4">No entries found for this period.</div>';
                document.getElementById('dbPrintBtn').classList.remove('d-none');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-play me-1"></i>Load Day Book';
                ajaxLoading(0);
            });
    });

    document.getElementById('dbPrintBtn').addEventListener('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
