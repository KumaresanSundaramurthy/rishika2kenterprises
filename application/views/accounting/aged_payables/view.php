<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Aged Payables',
                    'pageDescription' => 'Vendor outstanding balances bucketed by overdue age',
                    'pageIcon'        => 'bx-credit-card',
                    'pageIconBg'      => '#fef3c7',
                    'pageIconColor'   => '#92400e',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">As Of Date</label>
                                    <input type="text" id="apAsOfDate" class="form-control form-control-sm" readonly
                                           placeholder="Select date">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-warning btn-sm" id="apLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Report
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="apPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3 d-none" id="apSummaryRow">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="aged-sum-icon" style="background:#fef3c7;color:#92400e;">
                                        <i class="bx bx-dollar-circle fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Total Outstanding</div>
                                        <div class="fw-bold" style="font-size:1rem;color:#92400e;" id="apSumTotal">—</div>
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
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">Current (0â€“30 days)</div>
                                        <div class="fw-bold aged-val-current" style="font-size:1rem;" id="apSum0to30">—</div>
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
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">31â€“90 days</div>
                                        <div class="fw-bold aged-val-warn" style="font-size:1rem;" id="apSumMid">—</div>
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
                                        <div class="text-muted" style="font-size:var(--r2k-text-75);">90+ days overdue</div>
                                        <div class="fw-bold aged-val-overdue" style="font-size:1rem;" id="apSum90plus">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Statement Area ─────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="apStatementArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="apEmptyState">
                                <i class="bx bx-credit-card fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Select a date and click Load Report</span>
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
        flatpickr('#apAsOfDate', Object.assign({}, _fpCfg, { defaultDate: new Date() }));
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    $('#apLoadBtn').on('click', function () {
        var asOf = $('#apAsOfDate').val();
        if (!asOf) {
            Swal.fire({ icon: 'warning', title: 'Please select the As Of date', timer: 1800, showConfirmButton: false });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      _baseUrl + 'accounting/getAgedPayablesAjax',
            method:   'POST',
            data:     { AsOfDate: asOf },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }

                var t = d.Totals;
                $('#apSummaryRow').removeClass('d-none');
                $('#apSumTotal').text(_fmt(t.outstanding));
                $('#apSum0to30').text(_fmt(t['0to30']));
                $('#apSumMid').text(_fmt(t['31to60'] + t['61to90']));
                $('#apSum90plus').text(_fmt(t['90plus']));
                $('#apStatementArea').html('<div class="p-3">' + d.Html + '</div>');
                $('#apPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Report');
                ajaxLoading(0);
            }
        });
    });

    $('#apPrintBtn').on('click', function () { window.print(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
