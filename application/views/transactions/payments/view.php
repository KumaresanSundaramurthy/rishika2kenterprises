<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $summary      = $MethodSummary ?? [];
                    $bankAccounts = $BankAccounts ?? [];
                    $totals   = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $received = (float)($totals->TotalReceived ?? 0);
                    $paid     = (float)($totals->TotalPaid ?? 0);
                    $net      = $received - $paid;

                    function pmtFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#dcfce7;">
                                <i class="bx bx-credit-card-alt" style="color:#22c55e;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Payments In'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- ── Balance Summary ─────────────────────────────────── -->
                    <div class="trans-stats-section mb-4">
                        <div class="d-flex gap-3 flex-wrap" id="pmtSummaryCards">

                        <?php if (!empty($summary)): ?>
                            <?php foreach ($summary as $row): ?>
                            <?php
                                $balance  = (float)($row->TotalReceived ?? 0);
                                $label    = htmlspecialchars($row->AccountLabel ?? 'Cash');
                                $bankName = htmlspecialchars($row->BankName ?? '');
                            ?>
                            <div class="card border-0 shadow-sm pmt-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                <div class="card-body py-3 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php if ($row->IsCash): ?>
                                            <i class="bx bx-money fs-5 text-success"></i>
                                        <?php else: ?>
                                            <i class="bx bx-building-house fs-5 text-primary"></i>
                                        <?php endif; ?>
                                        <span class="fw-semibold" style="font-size:.82rem;"><?php echo $label; ?></span>
                                        <i class="bx bx-up-arrow-alt text-success ms-auto fs-5"></i>
                                    </div>
                                    <?php if ($bankName): ?>
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo $bankName; ?></div>
                                    <?php endif; ?>
                                    <div class="fw-bold mt-1 text-dark" style="font-size:1.05rem;">
                                        <?php echo pmtFmt($balance, $cur, $dec); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- No payments yet — show all bank accounts with ₹0 -->
                            <div class="card border-0 shadow-sm pmt-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                <div class="card-body py-3 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bx bx-money fs-5 text-success"></i>
                                        <span class="fw-semibold" style="font-size:.82rem;">Cash</span>
                                        <i class="bx bx-minus text-muted ms-auto fs-5"></i>
                                    </div>
                                    <div class="fw-bold mt-1 text-muted" style="font-size:1.05rem;"><?php echo pmtFmt(0, $cur, $dec); ?></div>
                                </div>
                            </div>
                            <?php foreach ($bankAccounts as $ba): ?>
                                <?php if ($ba->IsCash) continue; ?>
                                <div class="card border-0 shadow-sm pmt-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bx bx-building-house fs-5 text-primary"></i>
                                            <span class="fw-semibold" style="font-size:.82rem;"><?php echo htmlspecialchars($ba->AccountName); ?></span>
                                            <i class="bx bx-minus text-muted ms-auto fs-5"></i>
                                        </div>
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($ba->BankName); ?></div>
                                        <div class="fw-bold mt-1 text-muted" style="font-size:1.05rem;"><?php echo pmtFmt(0, $cur, $dec); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Tabs + Toolbar -->
                        <div class="trans-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="pmtStatusTabs">
                                <li class="nav-item">
                                    <a class="nav-link pmt-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-primary ms-1" id="pmtTabCountSuccess"><?php echo number_format($ModAllCount); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pmt-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1" id="pmtTabCountCancelled">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <div class="input-group input-group-sm" style="width:200px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="pmtSearch" placeholder="Search payment, party, amount…">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="pmtDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="pmtDateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" id="pmtDateFilterMenu"
                                        style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu shadow-sm" style="font-size:.82rem;">
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtFilterSales"><i class="bx bx-upload me-2 text-success"></i>Sales (Received)</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtFilterPurchases"><i class="bx bx-download me-2 text-danger"></i>Purchases (Paid)</a></li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtClearFilter"><i class="bx bx-x me-2 text-muted"></i>Clear Filters</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="paymentsTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:130px;">Amount</th>
                                        <th style="width:150px;">Mode / Bank</th>
                                        <th style="width:130px;">Linked Document</th>
                                        <th>Party Name</th>
                                        <th style="width:160px;">Created By</th>
                                        <th style="width:120px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer with totals -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.8rem;">
                                    Net Balance &nbsp;<strong class="text-dark" id="pmtFooterNet"><?php echo pmtFmt($net, $cur, $dec); ?></strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    You Received: &nbsp;<strong class="text-success" id="pmtFooterReceived"><?php echo pmtFmt($received, $cur, $dec); ?></strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    You Gave: &nbsp;<strong class="text-danger" id="pmtFooterPaid"><?php echo pmtFmt($paid, $cur, $dec); ?></strong>
                                </span>
                            </div>
                            <div id="pmtPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">Total: <strong id="pmtTotalCount"><?php echo number_format($ModAllCount); ?></strong></span>
                                <?php echo $ModPagination; ?>
                            </div>
                        </div>

                    </div>

                </div>
                <?php $this->load->view('common/footer_desc'); ?>
            </div>
        </div>

        <?php $this->load->view('common/transactions/print_modals'); ?>
        <?php $this->load->view('common/modals/send_communication'); ?>

    </div>
</div>

<?php
$pdtTheme       = 'in';
$pdtPartyLabel  = 'Party';
$pdtLinkedLabel = 'Linked Document';
$this->load->view('common/transactions/payment_modal');
?>

<?php $this->load->view('common/footer'); ?>

<link rel="stylesheet" href="/assets/vendor/css/transactions.css">
<link rel="stylesheet" href="/css/transactions-theme.css">
<script type="text/javascript" src="/js/common/datefilter.js"></script>
<script type="text/javascript" src="/js/transactions/attachments.js"></script>
<script type="text/javascript" src="/js/transactions/viewmodal.js"></script>
<script type="text/javascript" src="/js/transactions/a4_print.js"></script>
<script type="text/javascript" src="/js/transactions/thermal_print.js"></script>
<script type="text/javascript" src="/js/common/communication.js"></script>

<script>
$('#viewTransEditBtn').data('hide-edit', true);
const PmtModuleId = <?php echo (int) $JwtData->ModuleUID; ?>;
var PmtFilter  = {};
var PmtPageNo  = 1;
var PmtLimit   = 10;

$(function () {
    'use strict';

    // Tab switch
    $(document).on('click', '.pmt-tab', function (e) {
        e.preventDefault();
        $('.pmt-tab').removeClass('active');
        $(this).addClass('active');
        PmtFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // Pagination
    $(document).on('click', '#pmtPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) getPaymentsDetails(parseInt(m[1]));
    });

    // Search
    var pmtSearchTimer;
    $('#pmtSearch').on('input', function () {
        clearTimeout(pmtSearchTimer);
        var v = $.trim($(this).val());
        pmtSearchTimer = setTimeout(function () {
            PmtFilter.Search = v;
            PmtPageNo = 1;
            getPaymentsDetails(1);
        }, 400);
    });

    // Date filter — dropdown with range presets + custom flatpickr pickers
    $('#pmtDateFilterMenu').html(buildDateFilterHtml('pmtCustomDateFrom', 'pmtCustomDateTo'));

    initDateFilter({
        btnId  : 'pmtDateFilterBtn',
        labelId: 'pmtDateFilterLabel',
        fromId : 'pmtCustomDateFrom',
        toId   : 'pmtCustomDateTo',
        onApply: function (from, to) {
            PmtFilter.DateFrom = from;
            PmtFilter.DateTo   = to;
            PmtPageNo = 1;
            getPaymentsDetails(1);
        }
    });

    // Attach flatpickr to the custom range inputs once they are rendered in the dropdown
    $(document).on('shown.bs.dropdown', '#pmtDateFilterBtn', function () {
        if (!$('#pmtCustomDateFrom').data('fpInit')) {
            flatpickr('#pmtCustomDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#pmtCustomDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#pmtCustomDateFrom').data('fpInit', true);
        }
    });

    // Actions dropdown filters
    $('#pmtFilterSales').on('click', function () {
        PmtFilter.PartyType = 'C';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    $('#pmtFilterPurchases').on('click', function () {
        PmtFilter.PartyType = 'V';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    $('#pmtClearFilter').on('click', function () {
        PmtFilter = {};
        $('#pmtSearch').val('');
        $('#pmtDateFilterLabel').text('All Dates');
        $('.date-option').removeClass('active');
        $('.date-option[data-range=""]').addClass('active');
        $('.pmt-tab').removeClass('active');
        $('.pmt-tab[data-tab="Success"]').addClass('active');
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // ── Payment A4 Print ─────────────────────────────────────────────────────
    $(document).on('click', '.pmtA4Print', function () {
        var paymentUID = $(this).data('payment-uid');
        _pmtLoadPrintData(paymentUID, 'a4', function (resp) {
            if (!resp.PrintHtml) {
                $('#a4PrintModal').modal('hide');
                showToastNotification('No print template is configured for Payments. Please set one up in Settings > Print Themes.', 'error');
                return;
            }
            _a4Html  = resp.PrintHtml;
            _a4Title = resp.Payment.UniqueNumber || ('PMT-' + paymentUID);
            _a4DownloadUid       = paymentUID;
            _a4DownloadModuleUID = 0;
            $('#a4ModalTitle').text('Payment Receipt — ' + _a4Title);
            _a4SetLoading(false);
            _a4ShowPreview();
        });
        _a4Html = null;
        $('#a4PrintModal').modal('show');
        _a4SetLoading(true);
    });

    // ── Payment Download PDF (direct) ────────────────────────────────────────
    $(document).on('click', '.pmtDownloadPdf', function () {
        var paymentUID = $(this).data('payment-uid');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/payments/downloadPaymentPdf';
        form.style.display = 'none';
        var fields = { PaymentUID: paymentUID, PaperSize: 'A4', [CsrfName]: CsrfToken };
        Object.keys(fields).forEach(function (k) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // Override download button for payments (POST to payments/downloadPaymentPdf)
    $('#a4DownloadBtn').off('click.pmt').on('click.pmt', function () {
        if (!_a4Html || !_a4DownloadUid) return;
        var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/payments/downloadPaymentPdf';
        form.style.display = 'none';
        var fields = { PaymentUID: _a4DownloadUid, PaperSize: size, [CsrfName]: CsrfToken };
        Object.keys(fields).forEach(function (k) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // ── Payment Thermal Print — handled by thermal_print.js ─────────────────

    // View payment detail — reads from data-* on the <tr>, zero AJAX
    $(document).on('click', '.viewPaymentDetail', function () {
        var $row = $(this).closest('tr.pmt-row');
        var sym  = '<?php echo addslashes($cur); ?>';
        var dec  = <?php echo $dec; ?>;
        var fmt  = function (v) {
            return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        };

        // Banner
        $('#pdUniqueNumber').text($row.data('unique-number') || '—');
        var dateStr = ($row.data('payment-date') || '').toString().slice(0, 10);
        if (dateStr) {
            var p = dateStr.split('-'), mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            dateStr = p[2] + ' ' + mo[parseInt(p[1], 10) - 1] + ' ' + p[0];
        }
        $('#pdDateLabel').text(dateStr || '—');
        $('#pdAmount').text(fmt($row.data('raw-amount')));

        // Mode badge
        var modeMap = { 'cash':'#e8f5e9|#2e7d32','upi':'#ede7f6|#4527a0','card':'#e3f2fd|#1565c0','net banking':'#fff8e1|#f57f17','cheque':'#fce4ec|#880e4f','emi':'#e0f7fa|#00695c','tds':'#f3e5f5|#6a1b9a' };
        var modeKey = ($row.data('payment-type') || '').toLowerCase().trim();
        var mc = modeMap[modeKey] ? modeMap[modeKey].split('|') : ['#f0f0f0','#555'];
        $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + ($row.data('payment-type') || '—') + '</span>');

        // Party
        var mobile = $row.data('party-mobile') || '';
        $('#pdParty').text($row.data('party-name') || '—');
        $('#pdPartyMobile').text(mobile).toggle(!!mobile);
        $('#pdTransNumber').text($row.data('trans-number') || '—');

        // Bank
        var bankName = $row.data('bank-name') || '';
        if (bankName && !$row.data('is-cash')) {
            var acctName = $row.data('account-name') || '';
            $('#pdBankName').text(bankName + (acctName ? ' (' + acctName + ')' : ''));
            $('#pdAccountNumber').text($row.data('account-number') || '—');
            var ifsc = $row.data('ifsc') || '', branch = $row.data('branch') || '';
            $('#pdIfsc').text(ifsc);   $('#pdIfscWrap').toggle(!!ifsc);
            $('#pdBranch').text(branch); $('#pdBranchWrap').toggle(!!branch);
            $('#pdBankSection').show();
        } else {
            $('#pdBankSection').hide();
        }

        // Reference / By / Notes
        $('#pdReference').text($row.data('reference') || '—');
        $('#pdCreatedBy').text($row.data('created-by') || '—');
        var notes = $row.data('notes') || '';
        $('#pdNotes').text(notes);
        $('#pdNotesWrap').toggle(!!notes);

        $('#paymentDetailModal').modal('show');
    });

    // Cancel payment
    $(document).on('click', '.cancelPayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Cancel Payment?',
            text : 'This payment will be marked as cancelled and the invoice balance will be restored.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel it',
            confirmButtonColor: '#f59e0b',
        }).then(function (result) {
            if (result.isConfirmed) doPaymentRemove(paymentUID, $row);
        });
    });

    // Delete payment
    $(document).on('click', '.deletePayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text : 'This will permanently remove the payment record and restore the invoice balance.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (result.isConfirmed) doPaymentRemove(paymentUID, $row);
        });
    });

});

function getPaymentsDetails(pageNo, append) {
    pageNo = pageNo || 1;
    PmtPageNo = pageNo;
    $.ajax({
        url    : '/payments/getPaymentsPageDetails/' + pageNo,
        method : 'POST',
        data   : { RowLimit: PmtLimit, Filter: PmtFilter, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (!resp.Error) {
                $('#paymentsTableBody').html(resp.RecordHtmlData);
                $('#pmtPagination').html(
                    '<span class="text-muted" style="font-size:.78rem;">Total: <strong id="pmtTotalCount">' +
                    Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                    (resp.Pagination || '')
                );
                if (resp.Totals) {
                    var sym = '<?php echo addslashes($cur); ?>';
                    var dec = <?php echo $dec; ?>;
                    var fmt = function(v) { return sym + ' ' + parseFloat(v).toLocaleString('en-IN', {minimumFractionDigits: dec, maximumFractionDigits: dec}); };
                    $('#pmtFooterReceived').text(fmt(resp.Totals.TotalReceived || 0));
                    $('#pmtFooterPaid').text(fmt(resp.Totals.TotalPaid || 0));
                    $('#pmtFooterNet').text(fmt((resp.Totals.TotalReceived || 0) - (resp.Totals.TotalPaid || 0)));
                }
            }
        }
    });
}

// ── WhatsApp: always open a fresh window to clear previous pre-filled text ──
$(document).on('click', '.pmt-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (!url) return;
    var win = window.open('about:blank', '_blank');
    win.location.href = url;
});

// Shared helper: call deletePayment endpoint and handle response
function doPaymentRemove(paymentUID, $row) {
    var sym = '<?php echo addslashes($cur); ?>';
    var dec = <?php echo $dec; ?>;
    var fmt = function (v) {
        return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    };
    $.ajax({
        url   : '/payments/deletePayment',
        method: 'POST',
        data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (!resp.Error) {
                $row.fadeOut(300, function () { $(this).remove(); });
                if (resp.NewBalanceAmount !== undefined) {
                    var statusColor = resp.NewStatus === 'Unpaid' ? 'secondary' : 'warning';
                    Swal.fire({
                        icon : 'success',
                        title: 'Done',
                        html : 'Transaction balance updated.<br>' +
                                '<strong>Remaining Balance:</strong> ' + fmt(resp.NewBalanceAmount) +
                                ' &nbsp;|&nbsp; Status: <span class="badge bg-label-' + statusColor + '">' +
                                resp.NewStatus + '</span>',
                        timer: 3000,
                        showConfirmButton: false,
                    });
                }
            } else {
                Swal.fire('Error', resp.Message, 'error');
            }
        }
    });
}
</script>
