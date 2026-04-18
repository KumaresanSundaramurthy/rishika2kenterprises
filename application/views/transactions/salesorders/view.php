<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <?php $this->load->view('common/navbar_view'); ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $cntAll      = array_sum(array_column($stats, 'count'));
                    $cntConfirmed= $stats['Confirmed']['count']  ?? 0;
                    $cntFulfilled= $stats['Fulfilled']['count']  ?? 0;
                    $cntDraft    = $stats['Draft']['count']      ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtConfirmed= $stats['Confirmed']['amount'] ?? 0;
                    $amtFulfilled= $stats['Fulfilled']['amount'] ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="trans-stat-label">All Orders</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                <i class="bx bx-cart trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Confirmed">
                                <div class="trans-stat-label">Confirmed</div>
                                <div class="trans-stat-count"><?php echo number_format($cntConfirmed); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtConfirmed, $cur, $dec); ?></div>
                                <i class="bx bx-check-double trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Fulfilled">
                                <div class="trans-stat-label">Fulfilled</div>
                                <div class="trans-stat-count"><?php echo number_format($cntFulfilled); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtFulfilled, $cur, $dec); ?></div>
                                <i class="bx bx-package trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="trans-stat-label">Drafts</div>
                                <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-pencil trans-stat-icon"></i>
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
                                    <a class="nav-link so-status-tab" data-status="Confirmed" href="javascript:void(0);">
                                        Confirmed <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link so-status-tab" data-status="Fulfilled" href="javascript:void(0);">
                                        Fulfilled <span class="trans-tab-count ms-1 d-none"></span>
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
                                <a href="/salesorders/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="soTable">
                                <thead>
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
                                <tbody class="table-border-bottom-0">
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

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

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

    // ── Inline status update ────────────────────────────────
    $(document).on('click', '.so-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        $.ajax({
            url   : '/salesorders/updateSalesOrderStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                else            { getSalesOrdersDetails(); }
            }
        });
    });

    // ── View modal ──────────────────────────────────────────
    $(document).on('click', '.viewSalesOrder', function () {
        var uid = $(this).data('uid');
        $('#viewTransModal').modal('show');
        $('#viewTransModalBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewTransEditBtn').attr('href', '/salesorders/edit/' + uid);
        $.ajax({
            url   : '/salesorders/getSalesOrderDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { $('#viewTransModalBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>'); }
                else { $('#viewTransModalTitle').text('Sales Order — ' + (resp.Header.UniqueNumber || 'Details')); $('#viewTransModalBody').html(_buildSODetailHtml(resp)); }
            },
            error: function () { $('#viewTransModalBody').html('<div class="alert alert-danger m-3">Failed to load order.</div>'); }
        });
    });

    // ── A4 Print — handled by /js/transactions/a4_print.js ──

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
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getSalesOrdersDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                }
            });
        });
    });

    // ── Duplicate ────────────────────────────────────────────
    $(document).on('click', '.duplicateSalesOrder', function () {
        var uid = $(this).data('uid');
        Swal.fire({ title: 'Duplicate Sales Order?', text: 'A new draft copy will be created.', icon: 'question', showCancelButton: true, confirmButtonText: 'Duplicate' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({
                    url   : '/salesorders/duplicateSalesOrder',
                    method: 'POST',
                    data  : { TransUID: uid, [CsrfName]: CsrfToken },
                    success: function (resp) {
                        if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                        else {
                            getSalesOrdersDetails();
                            Swal.fire({ icon: 'success', text: resp.Message, showCancelButton: true, confirmButtonText: 'Edit Now', cancelButtonText: 'Stay Here' })
                                .then(function (r2) { if (r2.isConfirmed && resp.EditURL) window.location.href = resp.EditURL; });
                        }
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
    var h = resp.Header || {}, org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ', dec = h.DecimalPoints || 2, rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr><td class="text-center">' + (i+1) + '</td><td>' + _esc(item.ProductName) + '</td>' +
            '<td class="text-center">' + _esc(item.Quantity) + ' ' + _esc(item.PrimaryUnitName) + '</td>' +
            '<td class="text-end">' + cur + parseFloat(item.UnitPrice||0).toFixed(dec) + '</td>' +
            '<td class="text-end">' + cur + parseFloat(item.NetAmount||0).toFixed(dec) + '</td></tr>';
    });
    return '<div class="p-3"><div class="row mb-3"><div class="col-md-6"><strong>' + _esc(org.OrgName||'') + '</strong><br>' +
        '<small class="text-muted">' + _esc(h.UniqueNumber||'—') + ' | ' + _esc(h.TransDate||'') + '</small></div>' +
        '<div class="col-md-6 text-end"><strong>Customer:</strong> ' + _esc(h.PartyName||'—') + '</div></div>' +
        '<table class="table table-bordered table-sm"><thead class="table-light"><tr><th>#</th><th>Product</th>' +
        '<th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr></thead>' +
        '<tbody>' + rows + '</tbody><tfoot class="table-light">' +
        '<tr><td colspan="4" class="text-end fw-semibold">Sub Total</td><td class="text-end">' + cur + parseFloat(h.SubTotal||0).toFixed(dec) + '</td></tr>' +
        (parseFloat(h.TaxAmount)>0 ? '<tr><td colspan="4" class="text-end">Tax</td><td class="text-end">' + cur + parseFloat(h.TaxAmount).toFixed(dec) + '</td></tr>' : '') +
        '<tr><td colspan="4" class="text-end fw-bold">Net Amount</td><td class="text-end fw-bold">' + cur + parseFloat(h.NetAmount||0).toFixed(dec) + '</td></tr>' +
        '</tfoot></table>' + (h.Notes ? '<p class="small text-muted mt-2"><strong>Notes:</strong> ' + _esc(h.Notes) + '</p>' : '') + '</div>';
}



function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}

</script>
