<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats        = $Stats         ?? null;
                    $paymentTypes = $PaymentTypes  ?? [];
                    $bankAccounts = $BankAccounts  ?? [];
                    $cur  = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec  = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

                    function rntFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ─────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#ede9fe;">
                                <i class="bx bx-cycling" style="color:#7c3aed;font-size:1.3rem;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Machine Rentals'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-primary" id="rntNewBtn">
                                <i class="bx bx-plus me-1"></i>New Rental
                            </button>
                        </div>
                    </div>

                    <!-- ── Stat Cards ──────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">

                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-list-ul"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Total Rentals</div>
                                    <div class="trans-stat-count" id="statTotalCount"><?php echo number_format((int)($stats->totalCount ?? 0)); ?></div>
                                    <div class="trans-stat-amount" id="statTotalRevenue"><?php echo rntFmt($stats->totalRevenue ?? 0, $cur, $dec); ?></div>
                                </div>
                            </a>

                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Active">
                                <div class="tsc-icon-wrap"><i class="bx bx-current-location"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Currently Active</div>
                                    <div class="trans-stat-count" id="statActiveCount"><?php echo number_format((int)($stats->activeCount ?? 0)); ?></div>
                                    <?php $ovd = (int)($stats->overdueCount ?? 0); ?>
                                    <div class="trans-stat-amount" id="statOverdue" style="color:<?php echo $ovd > 0 ? '#dc2626' : 'inherit'; ?>">
                                        <?php echo $ovd > 0 ? $ovd . ' overdue' : 'None overdue'; ?>
                                    </div>
                                </div>
                            </a>

                            <a href="javascript:void(0);" class="trans-stat-card" data-stat-filter="Closed">
                                <div class="tsc-icon-wrap"><i class="bx bx-check-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Closed / Returned</div>
                                    <div class="trans-stat-count" id="statClosedCount"><?php echo number_format((int)($stats->closedCount ?? 0)); ?></div>
                                    <div class="trans-stat-amount" id="statDeposit"><?php echo rntFmt($stats->totalDeposit ?? 0, $cur, $dec); ?> deposit</div>
                                </div>
                            </a>

                            <div class="trans-stat-card" style="cursor:default;">
                                <div class="tsc-icon-wrap" style="background:#fef3c7;"><i class="bx bx-wallet" style="color:#d97706;"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Outstanding Balance</div>
                                    <div class="trans-stat-count" id="statBalance" style="color:#d97706;"><?php echo rntFmt($stats->totalBalance ?? 0, $cur, $dec); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <ul class="nav trans-status-tabs gap-1" id="rntStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active rnt-status-tab" data-status="All"               href="javascript:void(0);">All          <span class="rnt-tab-count trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Active"            href="javascript:void(0);">Active        <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Overdue"           href="javascript:void(0);">Overdue       <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="PartiallyReturned" href="javascript:void(0);">Partial       <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Closed"            href="javascript:void(0);">Closed        <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Cancelled"         href="javascript:void(0);">Cancelled     <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>

                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh"><i class="bx bx-refresh fs-5"></i></a>
                                <div class="input-group input-group-sm" style="width:220px;">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="rntSearchInput" placeholder="Rental #, customer...">
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="rntTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="rntHeaderCheck"></div></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th>Rental # / Date</th>
                                        <th>Customer</th>
                                        <th>Machines</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Total</th>
                                        <th style="text-align:right;">Paid</th>
                                        <th style="text-align:right;">Balance</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="rntTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center rntPagination" id="rntPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('transactions/rental/partials/_create_modal'); ?>
<?php $this->load->view('transactions/rental/partials/_return_modal'); ?>

<?php
$rpAccentColor = '#7c3aed';
$rpAccentBg    = '#ede9fe';
$rpPartyIcon   = 'bx-user';
$rpDocLabel    = 'Rental';
$rpTotalIcon   = 'bx-cycling';
$rpBtnLabel    = 'Record Payment';
$this->load->view('common/transactions/payment_modal');
?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/rental.js"></script>

<script>
var RntCurrency  = '<?php echo addslashes($cur); ?>';
var RntDecimals  = <?php echo (int)$dec; ?>;
var RntFilter    = {};
var _rntPayTypes = <?php echo json_encode(array_map(function($t) {
    return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->PaymentTypeName, 'IsCash' => (int)$t->IsCash];
}, $paymentTypes)); ?>;
var _rntBankAccts = <?php echo json_encode(array_values(array_map(function($b) {
    return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
}, $bankAccounts))); ?>;

$(function () {
    'use strict';

    // Init shared payment modal
    initRecordPaymentModal(
        <?php echo json_encode(array_map(function($t) {
            return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->PaymentTypeName, 'IsCash' => (int)$t->IsCash];
        }, $paymentTypes)); ?>,
        <?php echo json_encode(array_values(array_map(function($b) {
            return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
        }, $bankAccounts))); ?>,
        RntCurrency
    );
    window.rpAfterSuccess = function (resp) {
        if (resp.Stats) rntUpdateStats(resp.Stats);
        if (resp.RecordHtmlData) {
            $('#rntTableBody').html(resp.RecordHtmlData);
            $('.rntPagination').html(resp.Pagination);
        }
    };

    // New Rental button
    $('#rntNewBtn').on('click', function () {
        rntOpenCreate();
    });

    // Status tabs
    $(document).on('click', '.rnt-status-tab', function () {
        $('.rnt-status-tab').removeClass('active');
        $(this).addClass('active');
        RntFilter['Status'] = $(this).data('status') || 'All';
        rntLoadPage(1);
    });

    // Stat card clicks
    $(document).on('click', '.trans-stat-card[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.rnt-status-tab').removeClass('active');
        $('.rnt-status-tab[data-status="' + status + '"]').addClass('active');
        RntFilter['Status'] = status;
        rntLoadPage(1);
    });

    // Search (debounced)
    var _searchTimer;
    $('#rntSearchInput').on('input', function () {
        clearTimeout(_searchTimer);
        var val = $(this).val().trim();
        _searchTimer = setTimeout(function () {
            RntFilter['Search'] = val;
            rntLoadPage(1);
        }, 400);
    });

    // Pagination
    $(document).on('click', '.rntPagination .page-link', function (e) {
        e.preventDefault();
        var pg = $(this).data('page');
        if (pg) rntLoadPage(pg);
    });

    // Refresh button
    $(document).on('click', '.pageRefresh', function () {
        rntLoadPage(1);
    });

    // Row actions (delegated)
    $(document).on('click', '.rntRecordPayBtn', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num');
        var date    = $(this).data('date');
        var cust    = $(this).data('customer');
        var total   = parseFloat($(this).data('total')   || 0);
        var paid    = parseFloat($(this).data('paid')    || 0);
        var balance = parseFloat($(this).data('balance') || 0);
        rpOpenModal({
            transUID:  uid,
            submitUrl: '/rental/recordPayment',
            docNum:    num,
            docDate:   date,
            partyName: cust,
            total:     total,
            paid:      paid,
            pending:   balance,
        });
    });

    $(document).on('click', '.rntReturnBtn', function () {
        rntOpenReturn(
            $(this).data('uid'),
            $(this).data('num'),
            $(this).data('item-uid'),
            $(this).data('item-name'),
            $(this).data('item-status')
        );
    });

    $(document).on('click', '.rntCancelBtn', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num');
        Swal.fire({
            title: 'Cancel ' + num + '?',
            text:  'This rental will be marked as Cancelled.',
            icon:  'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel',
            cancelButtonText:  'No',
            confirmButtonColor: '#dc2626',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post('/rental/cancelRental', { RentalUID: uid, [CsrfName]: CsrfToken }, function (r) {
                if (r.Error) {
                    Swal.fire({ icon: 'error', text: r.Message });
                } else {
                    Swal.fire({ icon: 'success', text: r.Message, timer: 1500, showConfirmButton: false });
                    if (r.RecordHtmlData) { $('#rntTableBody').html(r.RecordHtmlData); $('.rntPagination').html(r.Pagination); }
                    if (r.Stats) rntUpdateStats(r.Stats);
                }
            });
        });
    });

});
</script>
