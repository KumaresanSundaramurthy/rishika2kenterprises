<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Payments',
                    'pageDescription' => $PageDescription ?? 'Track payments received and made',
                ]); ?>
                <div class="container-xxl flex-grow-1 py-3">

                    <?php
                    $cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $totals  = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $stats   = $BalanceStats ?? (object)['CashIn' => 0, 'CashOut' => 0, 'BankIn' => 0, 'BankOut' => 0];

                    $cashIn  = (float)($stats->CashIn  ?? 0);
                    $cashOut = (float)($stats->CashOut ?? 0);
                    $bankIn  = (float)($stats->BankIn  ?? 0);
                    $bankOut = (float)($stats->BankOut ?? 0);

                    function allPmtFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>


                    <!-- ── Stats Bar (apex-stats-strip = visibility controlled by StatsDefaultOpen setting) ── -->
                    <div class="apex-stats-strip mb-3" style="border-radius:.5rem;border:0;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                        <!-- Current Balance -->
                        <div class="d-flex align-items-center gap-3 px-4 border-end" style="flex:1;min-width:0;padding-top:14px;padding-bottom:14px;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:36px;height:36px;background:#f0f4ff;">
                                <i class="bx bx-wallet" style="color:#4f46e5;font-size:1.05rem;"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="text-muted" style="font-size:.69rem;text-transform:uppercase;letter-spacing:.05em;">Current Balance</div>
                                <div class="fw-bold" style="font-size:.95rem;color:#4f46e5;" id="statNetBalance">
                                    <?php echo allPmtFmt(($cashIn+$bankIn)-($cashOut+$bankOut),$cur,$dec); ?>
                                </div>
                            </div>
                            <div class="ms-auto text-end flex-shrink-0" style="font-size:.73rem;">
                                <div class="text-muted mb-1"><i class="bx bx-money me-1 text-success"></i>Cash&nbsp;<span class="fw-semibold text-body" id="statCashBalance"><?php echo allPmtFmt($cashIn-$cashOut,$cur,$dec); ?></span></div>
                                <div class="text-muted"><i class="bx bx-building-house me-1 text-primary"></i>Bank&nbsp;<span class="fw-semibold text-body" id="statBankBalance"><?php echo allPmtFmt($bankIn-$bankOut,$cur,$dec); ?></span></div>
                            </div>
                        </div>
                        <!-- Money In -->
                        <div class="d-flex align-items-center gap-3 px-4 border-end" style="flex:1;min-width:0;padding-top:14px;padding-bottom:14px;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:36px;height:36px;background:#dcfce7;">
                                <i class="bx bx-log-in-circle text-success" style="font-size:1.05rem;"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="text-muted" style="font-size:.69rem;text-transform:uppercase;letter-spacing:.05em;"><i class="bx bx-up-arrow-alt text-success"></i>&nbsp;Money In</div>
                                <div class="fw-bold text-success" style="font-size:.95rem;" id="statTotalIn">
                                    <?php echo allPmtFmt($cashIn+$bankIn,$cur,$dec); ?>
                                </div>
                            </div>
                            <div class="ms-auto text-end flex-shrink-0" style="font-size:.73rem;">
                                <div class="text-muted mb-1"><i class="bx bx-money me-1 text-success"></i>Cash&nbsp;<span class="fw-semibold text-success" id="statCashIn"><?php echo allPmtFmt($cashIn,$cur,$dec); ?></span></div>
                                <div class="text-muted"><i class="bx bx-building-house me-1 text-primary"></i>Bank&nbsp;<span class="fw-semibold text-success" id="statBankIn"><?php echo allPmtFmt($bankIn,$cur,$dec); ?></span></div>
                            </div>
                        </div>
                        <!-- Money Out -->
                        <div class="d-flex align-items-center gap-3 px-4" style="flex:1;min-width:0;padding-top:14px;padding-bottom:14px;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:36px;height:36px;background:#fee2e2;">
                                <i class="bx bx-log-out-circle text-danger" style="font-size:1.05rem;"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="text-muted" style="font-size:.69rem;text-transform:uppercase;letter-spacing:.05em;"><i class="bx bx-down-arrow-alt text-danger"></i>&nbsp;Money Out</div>
                                <div class="fw-bold text-danger" style="font-size:.95rem;" id="statTotalOut">
                                    <?php echo allPmtFmt($cashOut+$bankOut,$cur,$dec); ?>
                                </div>
                            </div>
                            <div class="ms-auto text-end flex-shrink-0" style="font-size:.73rem;">
                                <div class="text-muted mb-1"><i class="bx bx-money me-1 text-success"></i>Cash&nbsp;<span class="fw-semibold text-danger" id="statCashOut"><?php echo allPmtFmt($cashOut,$cur,$dec); ?></span></div>
                                <div class="text-muted"><i class="bx bx-building-house me-1 text-primary"></i>Bank&nbsp;<span class="fw-semibold text-danger" id="statBankOut"><?php echo allPmtFmt($bankOut,$cur,$dec); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <!-- /.stats -->

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="allPmtSearch" placeholder="Party, ref, amount…">
                            </div>
                            <a href="javascript:void(0);" id="allPmtModeFilter" class="apex-filter-btn" title="Filter by Payment Mode"><i class="bx bx-credit-card me-1"></i>Pay Mode</a>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="allPmtCreatedByFilter" class="apex-filter-btn" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <div class="dropdown">
                                <button class="apex-filter-btn dropdown-toggle" type="button" id="allPmtDateBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-calendar"></i><span id="allPmtDateLabel" class="ms-1">This Month</span><strong id="allPmtDateDates" class="r2k-df-dates" style="display:none;"></strong>
                                </button>
                                <ul class="dropdown-menu shadow" id="allPmtDateMenu" style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <button class="btn btn-sm btn-outline-secondary" id="allPmtClearBtn" title="Clear all filters">
                                <i class="bx bx-x me-1"></i>Clear
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="allPmtStatusTabs">
                                <li class="nav-item"><a class="nav-link allpmt-status-tab active" data-status=""          href="javascript:void(0);">All       <span class="trans-tab-count ms-1" id="allPmtTabCountActive"><?php echo number_format($ModAllCount); ?></span></a></li>
                                <li class="nav-item"><a class="nav-link allpmt-dir-pill"          data-dir="In"           href="javascript:void(0);"><i class="bx bx-up-arrow-alt text-success"></i> In</a></li>
                                <li class="nav-item"><a class="nav-link allpmt-dir-pill"          data-dir="Out"          href="javascript:void(0);"><i class="bx bx-down-arrow-alt text-danger"></i> Out</a></li>
                                <li class="nav-item"><a class="nav-link allpmt-status-tab"        data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1" id="allPmtTabCountCancelled">0</span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="allPaymentsTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:160px;">Date / Ref No</th>
                                        <th style="width:70px;">Type</th>
                                        <th style="width:140px;">Amount</th>
                                        <th style="width:160px;">Mode / Bank</th>
                                        <th>Party</th>
                                        <th style="width:140px;">Linked Doc</th>
                                        <th style="width:170px;">Created By</th>
                                        <th style="width:80px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="allPaymentsTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer totals + pagination -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <span class="text-muted" style="font-size:.8rem;">
                                    In: &nbsp;<strong class="text-success" id="allPmtFooterIn">
                                        <?php echo allPmtFmt((float)($totals->TotalReceived ?? 0), $cur, $dec); ?>
                                    </strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    Out: &nbsp;<strong class="text-danger" id="allPmtFooterOut">
                                        <?php echo allPmtFmt((float)($totals->TotalPaid ?? 0), $cur, $dec); ?>
                                    </strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    Net: &nbsp;<strong class="text-dark" id="allPmtFooterNet">
                                        <?php echo allPmtFmt((float)($totals->TotalReceived ?? 0) - (float)($totals->TotalPaid ?? 0), $cur, $dec); ?>
                                    </strong>
                                </span>
                            </div>
                            <div id="allPmtPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">
                                    Total: <strong id="allPmtTotalCount"><?php echo number_format($ModAllCount); ?></strong>
                                </span>
                                <?php echo $ModPagination; ?>
                            </div>
                        </div>

                    </div><!-- /.card -->

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

<!-- ── Column filter boxes ──────────────────────────────────────────────── -->
<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'allPmtModeFilterBox',
        'triggerId'  => 'allPmtModeFilter',
        'title'      => 'Payment Mode',
        'icon'       => 'bx-credit-card',
        'filterKey'  => 'PaymentMode',
        'checkClass' => 'allpmt-mode-chk',
        'items'      => array_map(function($t) {
            return ['value' => $t->Name, 'label' => $t->Name];
        }, $PaymentTypes ?? []),
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'allPmtCreatedByFilterBox',
        'triggerId'  => 'allPmtCreatedByFilter',
        'checkClass' => 'allpmt-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<!-- ── Styles + Scripts ─────────────────────────────────────────────────── -->
<link rel="stylesheet" href="/assets/vendor/css/transactions.css">
<link rel="stylesheet" href="/css/transactions-theme.css">
<script src="/js/common/datefilter.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/payments_page.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/thermal_print.js"></script>
<script src="/js/common/communication.js"></script>

<script>
$('#viewTransEditBtn').data('hide-edit', true);

// ── Bootstrap PaymentsPage ───────────────────────────────────────────────
var _allPmtPage;

var allPmtPayModeFilter = new TransColFilter({
    boxId       : 'allPmtModeFilterBox',
    triggerId   : 'allPmtModeFilter',
    filterKey   : 'PaymentMode',
    activeClass : 'has-filter',
    onApply     : function () { _allPmtPage.loadData(1); }
});

var allPmtCreatedByFilter = (document.getElementById('allPmtCreatedByFilterBox'))
    ? new TransColFilter({
        boxId       : 'allPmtCreatedByFilterBox',
        triggerId   : 'allPmtCreatedByFilter',
        filterKey   : 'UpdatedByUIDs',
        activeClass : 'has-filter',
        onApply     : function () { _allPmtPage.loadData(1); }
    })
    : null;

_allPmtPage = new PaymentsPage({
    sym            : '<?php echo addslashes($cur); ?>',
    dec            : <?php echo $dec; ?>,
    limit          : <?php echo (int)($JwtData->GenSettings->RowLimit ?? 10); ?>,
    initStats      : <?php echo json_encode($BalanceStats ?? (object)['CashIn'=>0,'CashOut'=>0,'BankIn'=>0,'BankOut'=>0]); ?>,
    payModeFilter  : allPmtPayModeFilter,
    createdByFilter: allPmtCreatedByFilter,
});

$(function () {
    'use strict';

    var sym = '<?php echo addslashes($cur); ?>';
    var dec = <?php echo $dec; ?>;

    // Init payment modal
    initRecordPaymentModal(
        <?php echo json_encode($PaymentTypes ?? []); ?>,
        <?php echo json_encode($BankAccounts ?? []); ?>,
        '<?php echo addslashes($cur); ?>'
    );

    // ── Status tab (Active / Cancelled) ─────────────────────────────────────
    $(document).on('click', '.allpmt-status-tab', function (e) {
        e.preventDefault();
        $('.allpmt-status-tab').removeClass('active');
        $(this).addClass('active');
        _allPmtPage._filter.Status = $(this).data('status') || '';
        _allPmtPage.loadData(1);
    });

    // ── Direction pill (All / In / Out) ─────────────────────────────────────
    $(document).on('click', '.allpmt-dir-pill', function (e) {
        e.preventDefault();
        $('.allpmt-dir-pill').removeClass('active').addClass('text-muted');
        $(this).addClass('active').removeClass('text-muted');
        _allPmtPage.setDir($(this).data('dir'));
    });

    // ── Pagination ───────────────────────────────────────────────────────────
    $(document).on('click', '#allPmtPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) { _allPmtPage.loadData(parseInt(m[1], 10)); }
    });

    // ── Search ───────────────────────────────────────────────────────────────
    var _searchTimer;
    $('#allPmtSearch').on('input', function () {
        clearTimeout(_searchTimer);
        var v = $.trim($(this).val());
        _searchTimer = setTimeout(function () {
            _allPmtPage._filter.Search = v;
            _allPmtPage.loadData(1);
        }, 1500);
    });

    // ── Date filter — defaults to This Month ─────────────────────────────────
    initDateFilter({
        btnId  : 'allPmtDateBtn',
        labelId: 'allPmtDateLabel',
        fromId : 'allPmtDateFrom',
        toId   : 'allPmtDateTo',
        onApply: function (from, to) {
            _allPmtPage._filter.DateFrom = from;
            _allPmtPage._filter.DateTo   = to;
            _allPmtPage.loadData(1);
        }
    });
    // Seed filter with This Month so initial load is date-scoped
    var _pmtInitDr = getDateRange('this_month');
    _allPmtPage._filter.DateFrom = _pmtInitDr.from;
    _allPmtPage._filter.DateTo   = _pmtInitDr.to;
    $('#allPmtDateBtn').addClass('r2k-date-active');
    $('#allPmtDateDates').text(formatDateDisplay(_pmtInitDr.from) + ' – ' + formatDateDisplay(_pmtInitDr.to)).show();
    $(document).on('shown.bs.dropdown', '#allPmtDateBtn', function () {
        if (!$('#allPmtDateFrom').data('fpInit')) {
            flatpickr('#allPmtDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#allPmtDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#allPmtDateFrom').data('fpInit', true);
        }
    });

    // ── Clear all filters — date resets to This Month ───────────────────────
    $('#allPmtClearBtn').on('click', function () {
        _allPmtPage.clearFilters();
        $('#allPmtSearch').val('');
        var _clrDr = getDateRange('this_month');
        _allPmtPage._filter.DateFrom = _clrDr.from;
        _allPmtPage._filter.DateTo   = _clrDr.to;
        $('#allPmtDateLabel').text('This Month');
        $('#allPmtDateDates').text(formatDateDisplay(_clrDr.from) + ' – ' + formatDateDisplay(_clrDr.to)).show();
        $('#allPmtDateBtn').addClass('r2k-date-active');
        $('#allPmtDateMenu').find('.date-option').removeClass('active');
        $('#allPmtDateMenu').find('.date-option[data-range="this_month"]').addClass('active');
        $('.allpmt-status-tab').removeClass('active').addClass('text-muted');
        $('.allpmt-status-tab[data-status=""]').addClass('active').removeClass('text-muted');
        $('.allpmt-dir-pill').removeClass('active').addClass('text-muted');
        _allPmtPage.loadData(1);
    });

    // ── View payment detail (reads from data-* on <tr>) ─────────────────────
    $(document).on('click', '.viewPaymentDetail', function () {
        var $row = $(this).closest('tr.pmt-row');
        var fmt  = function (v) {
            return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', {
                minimumFractionDigits: dec, maximumFractionDigits: dec
            });
        };

        var dir = ($row.data('direction') || 'In');
        var $modal = $('#paymentDetailModal');
        $modal.attr('data-pdt-theme', dir === 'Out' ? 'out' : 'in');

        $('#pdUniqueNumber').text($row.data('unique-number') || '—');
        var dateStr = ($row.data('payment-date') || '').toString().slice(0, 10);
        if (dateStr) {
            var p = dateStr.split('-'), mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            dateStr = p[2] + ' ' + mo[parseInt(p[1], 10) - 1] + ' ' + p[0];
        }
        $('#pdDateLabel').text(dateStr || '—');
        $('#pdAmount').text(fmt($row.data('raw-amount')));

        var modeMap = { 'cash':'#e8f5e9|#2e7d32','upi':'#ede7f6|#4527a0','card':'#e3f2fd|#1565c0','net banking':'#fff8e1|#f57f17','cheque':'#fce4ec|#880e4f','emi':'#e0f7fa|#00695c','tds':'#f3e5f5|#6a1b9a' };
        var modeKey = ($row.data('payment-type') || '').toLowerCase().trim();
        var mc = modeMap[modeKey] ? modeMap[modeKey].split('|') : ['#f0f0f0','#555'];
        $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + ($row.data('payment-type') || '—') + '</span>');

        var mobile = $row.data('party-mobile') || '';
        $('#pdParty').text($row.data('party-name') || '—');
        $('#pdPartyMobile').text(mobile).toggle(!!mobile);
        $('#pdTransNumber').text($row.data('trans-number') || '—');

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

        $('#pdReference').text($row.data('reference') || '—');
        $('#pdCreatedBy').text($row.data('created-by') || '—');
        var notes = $row.data('notes') || '';
        $('#pdNotes').text(notes);
        $('#pdNotesWrap').toggle(!!notes);

        $modal.modal('show');
    });

    // ── Cancel payment (In direction) ────────────────────────────────────────
    $(document).on('click', '.cancelPayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Cancel Payment?',
            text : 'This payment will be marked as cancelled and the linked document balance will be restored.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel it',
            confirmButtonColor: '#f59e0b',
        }).then(function (result) {
            if (result.isConfirmed) { _doPaymentCancel(paymentUID, $row); }
        });
    });

    // ── Cancel payment (Out direction) ───────────────────────────────────────
    $(document).on('click', '.cancelPaymentOut', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Cancel Payment?',
            text : 'This payment will be cancelled and the linked document balance restored.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel it',
            confirmButtonColor: '#f59e0b',
        }).then(function (result) {
            if (result.isConfirmed) { _doPaymentCancel(paymentUID, $row); }
        });
    });

    // ── Delete payment ───────────────────────────────────────────────────────
    $(document).on('click', '.deletePayment, .deletePaymentOut', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text : 'This will permanently remove the payment record.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (result.isConfirmed) { _doPaymentCancel(paymentUID, $row); }
        });
    });

    // ── A4 Print ─────────────────────────────────────────────────────────────
    $(document).on('click', '.pmtA4Print', function () {
        var paymentUID = $(this).data('payment-uid');
        _pmtLoadPrintData(paymentUID, 'a4', function (resp) {
            if (!resp.PrintHtml) {
                $('#a4PrintModal').modal('hide');
                showToastNotification('No print template configured for Payments.', 'error');
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

    // ── Download PDF ─────────────────────────────────────────────────────────
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

    // ── WhatsApp link helper ─────────────────────────────────────────────────
    $(document).on('click', '.pmt-wa-link', function (e) {
        e.preventDefault();
        var url = $(this).data('wa-url');
        if (!url) return;
        var win = window.open('about:blank', '_blank');
        win.location.href = url;
    });

    // ── Pre-apply dir=out if URL param present ───────────────────────────────
    (function () {
        var params = new URLSearchParams(window.location.search);
        if ((params.get('dir') || '').toLowerCase() === 'out') {
            $('.allpmt-dir-pill[data-dir="Out"]').trigger('click');
        }
    }());

});

// ── Shared helper: cancel / delete a payment ─────────────────────────────
function _doPaymentCancel(paymentUID, $row) {
    $.ajax({
        url   : '/payments/deletePayment',
        method: 'POST',
        data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (!resp.Error) {
                $row.fadeOut(300, function () { $(this).remove(); });
                // Refresh stats to reflect the change
                _allPmtPage.loadStats();
                Swal.fire({ icon: 'success', text: resp.Message, timer: 1800, showConfirmButton: false });
            } else {
                Swal.fire('Error', resp.Message, 'error');
            }
        }
    });
}
</script>
