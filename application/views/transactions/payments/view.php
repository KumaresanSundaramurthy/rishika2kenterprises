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
                <div class="container-xxl flex-grow-1">

                    <?php
                    $cur   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec   = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $stats = $BalanceStats ?? (object)['CashIn' => 0, 'CashOut' => 0, 'BankIn' => 0, 'BankOut' => 0];

                    $cashIn  = (float)($stats->CashIn  ?? 0);
                    $cashOut = (float)($stats->CashOut ?? 0);
                    $bankIn  = (float)($stats->BankIn  ?? 0);
                    $bankOut = (float)($stats->BankOut ?? 0);

                    function allPmtFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>


                    <!-- ── Stats Bar (apex-stats-strip = visibility controlled by StatsDefaultOpen setting) ── -->
                    <?php if ($JwtData->TransSettings->ShowTransactionStats ?? 1): ?>
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
                    <?php endif; ?>
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
                            <a href="javascript:void(0);" id="allPmtDocTypeFilter" class="apex-filter-btn" title="Filter by Document Type"><i class="bx bx-file me-1"></i>Doc Type</a>
                            <a href="javascript:void(0);" id="allPmtPartyTypeFilter" class="apex-filter-btn" title="Filter by Party Type"><i class="bx bx-group me-1"></i>Party Type</a>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="allPmtCreatedByFilter" class="apex-filter-btn" title="Filter by Created By"><i class="bx bx-user me-1"></i>Created By</a>
                            <?php endif; ?>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="allPmtStatusTabs">
                                <li class="nav-item"><a class="nav-link allpmt-status-tab active" data-status=""          href="javascript:void(0);">All       <span class="trans-tab-count ms-1<?php echo $ModAllCount > 0 ? '' : ' d-none'; ?>" id="allPmtTabCountActive"><?php echo $ModAllCount > 0 ? number_format($ModAllCount) : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link allpmt-dir-pill"          data-dir="In"           href="javascript:void(0);"><i class="bx bx-up-arrow-alt text-success"></i> In</a></li>
                                <li class="nav-item"><a class="nav-link allpmt-dir-pill"          data-dir="Out"          href="javascript:void(0);"><i class="bx bx-down-arrow-alt text-danger"></i> Out</a></li>
                                <li class="nav-item"><a class="nav-link allpmt-status-tab"        data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 d-none" id="allPmtTabCountCancelled"></span></a></li>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable last-col-sticky mb-0" id="allPaymentsTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3 col-sortable cursor-pointer user-select-none" style="width:160px;" data-sort="Date" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Date / Ref No <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i></th>
                                        <th class="col-sortable cursor-pointer user-select-none" style="width:70px;" data-sort="Type" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Type <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Type"></i></th>
                                        <th class="col-sortable cursor-pointer user-select-none" style="width:140px;" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i></th>
                                        <th style="width:160px;">Mode / Bank</th>
                                        <th class="col-sortable cursor-pointer user-select-none" style="width:200px;" data-sort="Party" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Party <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Party"></i></th>
                                        <th style="width:140px;">Linked Doc</th>
                                        <th style="width:150px;">Created By</th>
                                        <th style="width:70px;" class="text-center pe-3 pmt-sticky-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="allPaymentsTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="allPmtPagination">
                            <?php echo $ModPagination; ?>
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
<?php $this->load->view('common/filter_panels/col_filter_box', [
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
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'allPmtCreatedByFilterBox',
        'triggerId'  => 'allPmtCreatedByFilter',
        'checkClass' => 'allpmt-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'allPmtDocTypeFilterBox',
        'triggerId'  => 'allPmtDocTypeFilter',
        'title'      => 'Document Type',
        'icon'       => 'bx-file',
        'filterKey'  => 'DocTypeModuleUIDs',
        'checkClass' => 'allpmt-doctype-chk',
        'items'      => [
            ['value' => '103', 'label' => 'Invoice'],
            ['value' => '105', 'label' => 'Purchase'],
            ['value' => '106', 'label' => 'Sales Return'],
            ['value' => '108', 'label' => 'Purchase Return'],
            ['value' => '114', 'label' => 'Expense'],
            ['value' => '115', 'label' => 'Income'],
            ['value' => '110', 'label' => 'Standalone'],
        ],
    ],
]); ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'allPmtPartyTypeFilterBox',
        'triggerId'  => 'allPmtPartyTypeFilter',
        'title'      => 'Party Type',
        'icon'       => 'bx-group',
        'filterKey'  => 'PartyTypes',
        'checkClass' => 'allpmt-partytype-chk',
        'items'      => [
            ['value' => 'C', 'label' => 'Customer'],
            ['value' => 'S', 'label' => 'Vendor'],
        ],
    ],
]); ?>

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
// ── Date filter globals — must be defined before datefilter.js DOM-ready fires ──
var r2kSavedDateRange = '<?php echo addslashes($SavedDateRange ?? 'this_month'); ?>';
var r2kSavedDateLabel = '<?php echo addslashes($SavedDateLabel ?? 'This Month'); ?>';

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

var allPmtDocTypeFilter = new TransColFilter({
    boxId       : 'allPmtDocTypeFilterBox',
    triggerId   : 'allPmtDocTypeFilter',
    filterKey   : 'DocTypeModuleUIDs',
    activeClass : 'has-filter',
    onApply     : function () { _allPmtPage.loadData(1); }
});

var allPmtPartyTypeFilter = new TransColFilter({
    boxId       : 'allPmtPartyTypeFilterBox',
    triggerId   : 'allPmtPartyTypeFilter',
    filterKey   : 'PartyTypes',
    activeClass : 'has-filter',
    onApply     : function () { _allPmtPage.loadData(1); }
});

_allPmtPage = new PaymentsPage({
    sym             : '<?php echo addslashes($cur); ?>',
    dec             : <?php echo $dec; ?>,
    limit           : <?php echo (int)($JwtData->GenSettings->RowLimit ?? 10); ?>,
    initStats       : <?php echo json_encode($BalanceStats ?? (object)['CashIn'=>0,'CashOut'=>0,'BankIn'=>0,'BankOut'=>0]); ?>,
    showStats       : <?php echo json_encode(($JwtData->TransSettings->ShowTransactionStats ?? 1) == 1); ?>,
    payModeFilter   : allPmtPayModeFilter,
    createdByFilter : allPmtCreatedByFilter,
    docTypeFilter   : allPmtDocTypeFilter,
    partyTypeFilter : allPmtPartyTypeFilter,
});

// Seed date filter from controller-side saved preference
_allPmtPage._filter.DateFrom = '<?php echo addslashes($InitDateFrom ?? ''); ?>';
_allPmtPage._filter.DateTo   = '<?php echo addslashes($InitDateTo   ?? ''); ?>';

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

    // ── Date filter ──────────────────────────────────────────────────────────
    $(document).on('r2k:datechange', function (e, dr) {
        _allPmtPage._filter.DateFrom = dr.from;
        _allPmtPage._filter.DateTo   = dr.to;
        _allPmtPage.loadData(1);
    });

    // ── Column sort ───────────────────────────────────────────────────────────
    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        var $th = $(this);
        if (_allPmtPage._filter.SortBy !== col) {
            _allPmtPage._filter.SortBy  = col;
            _allPmtPage._filter.SortDir = 'ASC';
        } else if (_allPmtPage._filter.SortDir === 'ASC') {
            _allPmtPage._filter.SortDir = 'DESC';
        } else {
            delete _allPmtPage._filter.SortBy;
            delete _allPmtPage._filter.SortDir;
        }
        $('.col-sortable').each(function () {
            $(this).attr('data-bs-title', 'Click for ascending order');
            var tt = bootstrap.Tooltip.getInstance(this);
            if (tt) { tt.dispose(); new bootstrap.Tooltip(this); }
        });
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        if (_allPmtPage._filter.SortBy) {
            var icon    = _allPmtPage._filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
            var tipText = _allPmtPage._filter.SortDir === 'ASC' ? 'Click for descending order' : 'Click to remove sorting';
            $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
            $th.attr('data-bs-title', tipText);
            var tt2 = bootstrap.Tooltip.getInstance($th[0]);
            if (tt2) { tt2.dispose(); new bootstrap.Tooltip($th[0]); }
        }
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

    // ── Handle optional dir=out URL param ───────────────────────────────────
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
