<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $cntAll      = array_sum(array_column($stats, 'count'));
                    $cntConfirmed= $stats['Pending']['count']   ?? 0;
                    $cntDraft    = $stats['Draft']['count']      ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtConfirmed= $stats['Pending']['amount']   ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#cffafe;">
                                <i class="bx bx-package" style="color:#06b6d4;"></i>
                            </div>
                            <h5 class="trans-ph-title"><?php echo htmlspecialchars($PageTitle ?? 'Sales Orders'); ?></h5>
                        </div>
                        <a href="/salesorders/create" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i>New Sales Order
                        </a>
                    </div>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-cart"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Orders</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Pending">
                                <div class="tsc-icon-wrap"><i class="bx bx-time"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Pending</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntConfirmed); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtConfirmed, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="tsc-icon-wrap"><i class="bx bx-pencil"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Drafts</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <ul class="nav trans-status-tabs gap-1" id="soStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active so-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link so-status-tab" data-status="Pending" href="javascript:void(0);">
                                        Pending <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link so-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link so-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData" placeholder="Order # or customer...">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:210px;max-height:300px;overflow-y:auto;font-size:.82rem;">
                                        <li><a class="dropdown-item date-option" data-range=""><i class="bx bx-list-ul me-2"></i>All Dates</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="today">Today</a></li>
                                        <li><a class="dropdown-item date-option" data-range="yesterday">Yesterday</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_week">This Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_week">Last Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_month">This Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_30_days">Last 30 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                            <i class="bx bxs-star text-warning me-1"></i>FY 25-26
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="soTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox soHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Order # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Expected Delivery <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center soPagination" id="soPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="soStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center soPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/salesorders.js"></script>

<script>
const ModuleId     = 102;
const ModuleTable  = '#soTable';
const ModulePag    = '.soPagination';
const ModuleHeader = '.soHeaderCheck';
const ModuleRow    = '.soCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    // ── Sticky pagination ──
    var $soStaticPag = $('#soPagination');
    var $soStickyPag = $('#soStickyPagination');
    function _syncSoSticky() { $soStickyPag.find('.soPagination').html($soStaticPag.html()); }
    function _toggleSoSticky() {
        if (!$soStaticPag.length) return;
        var r = $soStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $soStickyPag.stop(true,true).fadeOut(150); }
        else { _syncSoSticky(); $soStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleSoSticky);
    _toggleSoSticky();

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.so-status-tab').removeClass('active');
        $('.so-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.so-status-tab', function (e) {
        e.preventDefault();
        $('.so-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getSalesOrdersDetails();
    }, 400));

    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range') || '';
        var dates = getDateRange(range);
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from;
        Filter.DateTo   = dates.to;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) {
            Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        } else {
            Filter.SortBy  = col;
            Filter.SortDir = 'DESC';
        }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.soPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getSalesOrdersDetails(); }
    });

    // helper: sync active tab status into Filter then build POST data
    function _actionPostData(extra) {
        Filter.Status = $('.so-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    // helper: render list response returned by cancel/delete directly into the table
    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        $('.so-status-tab.active .trans-tab-count').text(count > 0 ? count : '').removeClass('d-none');
        initTooltips();
    }

    // ── Inline status update (Cancel from 3-dot or status badge) ──
    $(document).on('click', '.so-status-update', function () {
        var $btn   = $(this);
        var uid    = $btn.data('uid');
        var status = $btn.data('status');

        if (status === 'Cancelled' && !$btn.data('_confirmed')) {
            var num = $btn.data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this sales order';
            Swal.fire({
                title: 'Cancel Sales Order?',
                html : 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.',
                icon : 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $btn.data('_confirmed', true).trigger('click');
            });
            return;
        }

        $btn.removeData('_confirmed');
        $.ajax({
            url   : '/salesorders/updateSalesOrderStatus',
            method: 'POST',
            data  : _actionPostData({ TransUID: uid, Status: status }),
            success: function (resp) {
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                    return;
                }
                _renderListResponse(resp);
                showToastNotification('Sales order cancelled.', 'success');
            }
        });
    });

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deleteSalesOrder', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Sales Order?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/deleteSalesOrder',
                method: 'POST',
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    _renderListResponse(resp);
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false });
                }
            });
        });
    });

    // ── Convert to Invoice ───────────────────────────────────
    $(document).on('click', '.convertSOToInvoice', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Invoice?',
            html : num ? 'Convert <strong>' + num + '</strong> to an Invoice?' : 'Convert this sales order to an Invoice?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/convertSalesOrderToInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });

    // ── Convert to Delivery Challan ──────────────────────────
    $(document).on('click', '.convertSOToChallan', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Delivery Challan?',
            html : num ? 'Convert <strong>' + num + '</strong> to a Delivery Challan?' : 'Convert this sales order to a Delivery Challan?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/convertSalesOrderToDeliveryChallan',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });


    $(document).on('change', '.soHeaderCheck', function () {
        $('.soCheck').prop('checked', $(this).is(':checked'));
    });

});

function _buildSODetailHtml(resp) {
    window._soLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-store-alt',
        typeColor   : '#d97706',
        typeBg      : '#fff8e1',
        hasPayments : false,
        validLabel  : 'Expected Delivery',
    });
}

</script>
