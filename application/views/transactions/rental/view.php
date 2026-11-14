<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Machine Rentals',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <?php $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>

                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <?php if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)): ?>
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#696cff">
                        <div class="apex-stat-icon" style="background:#eef2ff"><i class="bx bx-list-ul" style="color:#696cff"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Rentals</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statTotalCount">0</span>
                                <span class="apex-stat-amount" id="statTotalRevenue"><?php echo $cur; ?> 0</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-current-location" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Currently Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statActiveCount">0</span>
                                <span class="apex-stat-amount" id="statOverdue">None overdue</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Closed" data-stat-filter="Closed" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-check-circle" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Closed / Returned</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statClosedCount">0</span>
                                <span class="apex-stat-amount" id="statDeposit"><?php echo $cur; ?> 0 deposit</span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#d97706;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-wallet" style="color:#d97706"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Outstanding Balance</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statBalance" style="color:#d97706"><?php echo $cur; ?> 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="rntSearchInput" placeholder="Rental #, customer...">
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button type="button" class="btn btn-primary" id="rntNewBtn"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_rental', 'Create Rental'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs gap-1" id="rntStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active rnt-status-tab" data-status="All"               href="javascript:void(0);">All          <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Active"            href="javascript:void(0);">Active        <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Overdue"           href="javascript:void(0);">Overdue       <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="PartiallyReturned" href="javascript:void(0);">Partial       <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Closed"            href="javascript:void(0);">Closed        <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link rnt-status-tab"        data-status="Cancelled"         href="javascript:void(0);">Cancelled     <span class="rnt-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
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
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center rntPagination" id="rntPagination">
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

<script src="/js/rental/rental.js"></script>

<script>
var RntCurrency   = '<?php echo addslashes($cur); ?>';
var RntDecimals   = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
var RntFilter     = {};
var _rntPayTypes  = [];
var _rntBankAccts = [];

$(function () {
    'use strict';

    initRecordPaymentModal([], [], RntCurrency);
    window.rpAfterSuccess = function (resp) {
        if (resp.Data) {
            var offset = ((resp.PageNo || 1) - 1) * (resp.RowLimit || RowLimit || 10);
            $('#rntTableBody').html(_buildRntRows(resp.Data, offset));
            $('.rntPagination').html(_buildRntPagination(resp.TotalCount || 0, resp.PageNo || 1, resp.RowLimit || RowLimit || 10));
            var cnt = resp.TotalCount || 0;
            $('.rnt-status-tab.active .rnt-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
        }
        if (resp.Stats) rntUpdateStats(resp.Stats);
        initTooltips();
    };

    // Initial page load — also fetches PaymentTypes and BankAccounts
    $.ajax({
        url:     global_services_url + 'api/rental/getPageData',
        method:  'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data:    { orgId: parseInt(JwtData.Org.OrgUID, 10) },
        success: function (r) {
            var offset = 0;
            $('#rntTableBody').html(_buildRntRows(r.Data || [], offset));
            $('.rntPagination').html(_buildRntPagination(r.TotalCount || 0, r.PageNo || 1, r.RowLimit || RowLimit || 10));
            var cnt = r.TotalCount || 0;
            $('.rnt-status-tab[data-status="All"] .rnt-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
            if (r.Stats) rntUpdateStats(r.Stats);
            if (!r.Error && r.PaymentTypes) {
                _rntPayTypes  = r.PaymentTypes;
                _rntBankAccts = r.BankAccounts || [];
                initRecordPaymentModal(_rntPayTypes, _rntBankAccts, RntCurrency);
            }
            initTooltips();
        },
        error: function () { rntLoadPage(1); }
    });

    // New Rental button
    $('#rntNewBtn').on('click', function () {
        rntOpenCreate();
    });

    // Status tabs
    $(document).on('click', '.rnt-status-tab', function () {
        var status = $(this).data('status') || 'All';
        $('.rnt-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        RntFilter['Status'] = status;
        rntLoadPage(1);
    });

    // Stat card clicks
    $(document).on('click', '.apex-stat-item[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
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
            submitUrl: global_services_url + 'api/rental/recordPayment',
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
            $.ajax({
                url:     global_services_url + 'api/rental/cancelRental',
                method:  'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: {
                    orgId:     parseInt(JwtData.Org.OrgUID, 10),
                    userId:    parseInt(JwtData.User.UserUID, 10),
                    RentalUID: uid,
                    Filter:    JSON.stringify(RntFilter),
                    RowLimit:  RowLimit || 10,
                },
                success: function (r) {
                    if (r.Error) {
                        Swal.fire({ icon: 'error', text: r.Message });
                    } else {
                        Swal.fire({ icon: 'success', text: r.Message, timer: 1500, showConfirmButton: false });
                        if (r.Data) {
                            var offset = 0;
                            $('#rntTableBody').html(_buildRntRows(r.Data, offset));
                            $('.rntPagination').html(_buildRntPagination(r.TotalCount || 0, r.PageNo || 1, r.RowLimit || RowLimit || 10));
                            var cnt = r.TotalCount || 0;
                            $('.rnt-status-tab.active .rnt-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                        }
                        if (r.Stats) rntUpdateStats(r.Stats);
                        initTooltips();
                    }
                }
            });
        });
    });

});
</script>
