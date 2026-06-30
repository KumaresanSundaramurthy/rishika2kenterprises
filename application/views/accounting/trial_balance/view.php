<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $FinancialYears */ $FinancialYears = $FinancialYears ?? [];
/** @var int   $DefaultFY */      $DefaultFY      = $DefaultFY      ?? (int)date('Y');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Trial Balance',
                    'pageDescription' => 'Summary of all ledger account balances for a financial year',
                    'pageIcon'        => 'bx-bar-chart-alt-2',
                    'pageIconBg'      => '#ede9ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <div class="container-xxl flex-grow-1 py-3">

                    <!-- ── Filter Card ──────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Financial Year</label>
                                    <select id="tbFY" class="form-select form-select-sm">
                                        <?php foreach ($FinancialYears as $fy): ?>
                                        <option value="<?php echo (int)$fy; ?>" <?php echo ((int)$fy === $DefaultFY) ? 'selected' : ''; ?>>
                                            FY <?php echo (int)$fy; ?>–<?php echo ((int)$fy + 1); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php if (empty($FinancialYears)): ?>
                                        <option value="<?php echo date('Y'); ?>">FY <?php echo date('Y'); ?>–<?php echo (date('Y')+1); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm w-100" id="btnViewTB">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="tbSpinner"></span>
                                        <i class="bx bx-search me-1" id="tbIcon"></i>Generate
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-sm w-100 d-none" id="btnTBPrint">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                                <div class="col-md-5 text-end" id="tbBalanceAlert" style="display:none!important;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary Stats ────────────────────────────────── -->
                    <div id="tbStatsWrap" class="d-none mb-3">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Total Accounts</div>
                                    <div class="fw-bold mt-1" id="tbStatCount" style="font-size:1.2rem;color:#7c3aed;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Total Debits</div>
                                    <div class="fw-bold mt-1 text-success" id="tbStatDr" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Total Credits</div>
                                    <div class="fw-bold mt-1 text-danger" id="tbStatCr" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Status</div>
                                    <div class="fw-bold mt-1" id="tbStatStatus" style="font-size:1rem;">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Trial Balance Table ──────────────────────────── -->
                    <div class="card d-none" id="tbResultCard">
                        <div class="card-header d-flex align-items-center justify-content-between py-2">
                            <span class="fw-semibold" id="tbCardTitle" style="font-size:.88rem;">Trial Balance</span>
                            <span class="text-muted" id="tbCardPeriod" style="font-size:.76rem;"></span>
                        </div>
                        <div class="table-responsive" id="tbTableWrap"></div>
                    </div>

                    <!-- ── Empty state ──────────────────────────────────── -->
                    <div id="tbEmptyState" class="text-center py-5">
                        <i class="bx bx-bar-chart-alt-2 d-block mx-auto mb-3" style="font-size:4rem;color:#d0d0e8;"></i>
                        <p class="text-muted" style="font-size:.88rem;">Select a financial year and click <strong>Generate</strong></p>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
(function () {
    'use strict';

    var _cur = '<?php echo addslashes($cur); ?>';

    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function _load() {
        var fy = $('#tbFY').val();
        var $btn = $('#btnViewTB').prop('disabled', true);
        $('#tbSpinner').removeClass('d-none'); $('#tbIcon').addClass('d-none');
        $('#tbEmptyState').hide();
        $('#tbResultCard').addClass('d-none');
        $('#tbStatsWrap').addClass('d-none');

        $.post('/accounting/getTrialBalanceAjax', { FinancialYear: fy, [CsrfName]: CsrfToken }, function (r) {
            CsrfToken = r.NewCsrfToken || CsrfToken;
            $btn.prop('disabled', false);
            $('#tbSpinner').addClass('d-none'); $('#tbIcon').removeClass('d-none');

            if (r.Error) { showToastNotification(r.Message, 'error'); $('#tbEmptyState').show(); return; }

            // Stats
            $('#tbStatCount').text(r.RowCount || 0);
            $('#tbStatDr').text(_fmt(r.GrandDebit));
            $('#tbStatCr').text(_fmt(r.GrandCredit));
            if (r.IsBalanced) {
                $('#tbStatStatus').html('<span class="text-success"><i class="bx bx-check-circle me-1"></i>Balanced</span>');
            } else {
                $('#tbStatStatus').html('<span class="text-danger"><i class="bx bx-error me-1"></i>Unbalanced</span>');
            }
            $('#tbStatsWrap').removeClass('d-none');

            // Card
            var fyVal = parseInt(fy);
            $('#tbCardTitle').text('Trial Balance — FY ' + fyVal + '–' + (fyVal + 1));
            $('#tbCardPeriod').text('April ' + fyVal + ' to March ' + (fyVal + 1));
            $('#tbTableWrap').html(r.Html);
            $('#tbResultCard').removeClass('d-none');
            $('#btnTBPrint').removeClass('d-none');
        }).fail(function () {
            $btn.prop('disabled', false);
            $('#tbSpinner').addClass('d-none'); $('#tbIcon').removeClass('d-none');
            showToastNotification('Request failed.', 'error');
            $('#tbEmptyState').show();
        });
    }

    $('#btnViewTB').on('click', _load);

    // Auto-load on page ready
    _load();

    // Print
    $('#btnTBPrint').on('click', function () {
        var win = window.open('', '_blank');
        var fy  = parseInt($('#tbFY').val());
        win.document.write('<html><head><title>Trial Balance</title>');
        win.document.write('<link rel="stylesheet" href="/assets/vendor/css/core.css">');
        win.document.write('</head><body style="padding:24px;">');
        win.document.write('<h4>Trial Balance — FY ' + fy + '–' + (fy+1) + '</h4>');
        win.document.write($('#tbTableWrap').html());
        win.document.write('</body></html>');
        win.document.close(); win.print();
    });

}());
</script>
