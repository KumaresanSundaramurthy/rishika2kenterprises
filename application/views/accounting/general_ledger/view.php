<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $AllLedgers */ $AllLedgers = $AllLedgers ?? [];
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'General Ledger',
                    'pageDescription' => 'View account-wise transaction history with running balance',
                    'pageIcon'        => 'bx-book-open',
                    'pageIconBg'      => '#ede9ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <div class="container-xxl flex-grow-1 py-3">

                    <!-- ── Filter Card ───────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Ledger Account <span class="text-danger">*</span></label>
                                    <select id="glLedgerUID" class="form-select form-select-sm">
                                        <option value="">— Select Account —</option>
                                        <?php
                                        $grouped = [];
                                        foreach ($AllLedgers as $l) {
                                            $grouped[$l->LedgerType][] = $l;
                                        }
                                        foreach ($grouped as $type => $items):
                                        ?>
                                        <optgroup label="<?php echo htmlspecialchars($type); ?>">
                                            <?php foreach ($items as $l): ?>
                                            <option value="<?php echo (int)$l->LedgerUID; ?>"
                                                data-ob="<?php echo (float)$l->OpeningBalance; ?>"
                                                data-obtype="<?php echo htmlspecialchars($l->OpeningBalanceType ?? 'Debit'); ?>">
                                                <?php echo htmlspecialchars($l->LedgerCode . ' — ' . $l->LedgerName); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">From Date</label>
                                    <input type="text" id="glDateFrom" class="form-control form-control-sm" placeholder="Select date">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">To Date</label>
                                    <input type="text" id="glDateTo" class="form-control form-control-sm" placeholder="Select date">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm w-100" id="btnViewLedger">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="glSpinner"></span>
                                        <i class="bx bx-search me-1" id="glIcon"></i>View Ledger
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-sm w-100 d-none" id="btnGlPrint">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Stats Strip (hidden until ledger loaded) ─────── -->
                    <div id="glStatsWrap" class="d-none mb-3">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Opening Balance</div>
                                    <div class="fw-bold mt-1" id="glStatOpening" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Total Debits</div>
                                    <div class="fw-bold mt-1 text-success" id="glStatDebit" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Total Credits</div>
                                    <div class="fw-bold mt-1 text-danger" id="glStatCredit" style="font-size:1.1rem;">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center py-3">
                                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Closing Balance</div>
                                    <div class="fw-bold mt-1" id="glStatClosing" style="font-size:1.1rem;color:#7c3aed;">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Statement Table ───────────────────────────────── -->
                    <div class="card" id="glResultCard" style="display:none;">
                        <div class="card-header d-flex align-items-center justify-content-between py-2">
                            <span class="fw-semibold" id="glCardTitle" style="font-size:.88rem;">Ledger Statement</span>
                            <span class="text-muted" id="glCardPeriod" style="font-size:.76rem;"></span>
                        </div>
                        <div class="table-responsive" id="glStatementWrap">
                        </div>
                    </div>

                    <!-- ── Empty state (before search) ──────────────────── -->
                    <div id="glEmptyState" class="text-center py-5">
                        <i class="bx bx-book-open d-block mx-auto mb-3" style="font-size:4rem;color:#d0d0e8;"></i>
                        <p class="text-muted" style="font-size:.88rem;">Select a ledger account and date range, then click <strong>View Ledger</strong></p>
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

    var _cur     = '<?php echo addslashes($cur); ?>';
    var _dateFmt = (typeof _transListDateFormat !== 'undefined') ? _transListDateFormat : 'd-m-Y';

    // Init date pickers
    if (typeof transDatePickr === 'function') {
        transDatePickr('#glDateFrom', false, 'Y-m-d', false, false, false, true, _dateFmt);
        transDatePickr('#glDateTo',   false, 'Y-m-d', false, false, true,  true, _dateFmt);
    }

    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    $('#btnViewLedger').on('click', function () {
        var uid      = $('#glLedgerUID').val();
        var dateFrom = $('#glDateFrom').val();
        var dateTo   = $('#glDateTo').val();

        if (!uid) { showToastNotification('Please select a ledger account.', 'warning'); return; }

        var $btn = $(this).prop('disabled', true);
        $('#glSpinner').removeClass('d-none'); $('#glIcon').addClass('d-none');
        $('#glEmptyState').hide();
        $('#glResultCard').hide();
        $('#glStatsWrap').addClass('d-none');

        $.post('/accounting/getLedgerStatementAjax', {
            LedgerUID: uid,
            DateFrom : dateFrom,
            DateTo   : dateTo,
            [CsrfName]: CsrfToken
        }, function (r) {
            CsrfToken = r.NewCsrfToken || CsrfToken;
            $btn.prop('disabled', false);
            $('#glSpinner').addClass('d-none'); $('#glIcon').removeClass('d-none');

            if (r.Error) { showToastNotification(r.Message, 'error'); $('#glEmptyState').show(); return; }

            // Stats
            var obLabel = _fmt(r.OpeningBalance) + ' ' + (r.OpeningType || '');
            var clLabel = _fmt(r.ClosingBalance) + ' ' + (r.ClosingType || '');
            $('#glStatOpening').text(obLabel);
            $('#glStatDebit').text(_fmt(r.TotalDebit));
            $('#glStatCredit').text(_fmt(r.TotalCredit));
            $('#glStatClosing').text(clLabel);
            $('#glStatsWrap').removeClass('d-none');

            // Card header
            $('#glCardTitle').text(r.LedgerName || 'Ledger Statement');
            var period = '';
            if (dateFrom || dateTo) period = (dateFrom || '…') + '  →  ' + (dateTo || '…');
            $('#glCardPeriod').text(period);

            // Statement
            $('#glStatementWrap').html(r.Html);
            $('#glResultCard').show();
            $('#btnGlPrint').removeClass('d-none');
        }).fail(function () {
            $btn.prop('disabled', false);
            $('#glSpinner').addClass('d-none'); $('#glIcon').removeClass('d-none');
            showToastNotification('Request failed.', 'error');
            $('#glEmptyState').show();
        });
    });

    // Print
    $('#btnGlPrint').on('click', function () {
        var $tbl = $('#glStatementWrap').clone();
        var win  = window.open('', '_blank');
        win.document.write('<html><head><title>General Ledger</title>');
        win.document.write('<link rel="stylesheet" href="/assets/vendor/css/core.css">');
        win.document.write('</head><body style="padding:24px;">');
        win.document.write('<h4>' + $('#glCardTitle').text() + '</h4>');
        win.document.write('<p style="font-size:.8rem;color:#666;">' + $('#glCardPeriod').text() + '</p>');
        win.document.write($tbl.html());
        win.document.write('</body></html>');
        win.document.close();
        win.print();
    });

}());
</script>
