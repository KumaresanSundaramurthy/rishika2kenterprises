<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <?php $this->load->view('common/navbar_view'); ?>
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="card">

                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 border-bottom-0">
                            <ul class="nav nav-pills gap-1" id="prStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 active pr-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="badge bg-info ms-1 pr-tab-count"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 pr-status-tab" data-status="Approved" href="javascript:void(0);">
                                        Approved <span class="badge bg-info ms-1 pr-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 pr-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-info ms-1 pr-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 pr-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="badge bg-info ms-1 pr-tab-count d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn pageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                <div class="input-group input-group-sm" style="width:220px">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="searchTransactionData" placeholder="Search..." title="Return Number or Vendor Name">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:220px;max-height:320px;overflow-y:auto">
                                        <li><a class="dropdown-item date-option" data-range="">All Dates</a></li>
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
                                        <li><a class="dropdown-item date-option" data-range="this_quarter">This Quarter</a></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>
                                    </ul>
                                </div>
                                <a href="/purchasereturns/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="prTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px"><input class="form-check-input" type="checkbox" id="checkAllPR"></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">#</th>
                                        <th>Return No.</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Vendor</th>
                                        <th>Return Date</th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="prTableBody">
                                    <?php
                                    $SerialNumber = 0;
                                    $this->load->view('transactions/purchasereturns/list', ['DataLists' => $DataLists, 'SerialNumber' => &$SerialNumber]);
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 py-2" id="prPaginationWrap">
                            <div class="text-muted small" id="prPaginationInfo"></div>
                            <nav><ul class="pagination pagination-sm mb-0" id="prPagination"></ul></nav>
                        </div>

                    </div>
                </div>
                <?php $this->load->view('common/footer_view'); ?>
            </div>
        </div>
    </div>
</div>

<!-- View Purchase Return Modal -->
<div class="modal fade" id="viewPRModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purchase Return Details</h5>
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="prModalPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPRModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- A4 Print Modal -->
<div class="modal fade" id="a4PrintPRModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Purchase Return</h5>
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <select id="prPrintSizeSelect" class="form-select form-select-sm" style="width:100px">
                        <option value="A4">A4</option>
                        <option value="A5">A5</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="prDoPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="a4PrintPRModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer_desc'); ?>

<script src="/js/transactions/purchasereturns.js"></script>
<script src="/js/transactions/transactions.js"></script>

<script>
const ModuleId    = 108;
const ModuleTable = '#prTable';

var _prCurrentPage   = 1;
var _prCurrentStatus = 'All';
var _prCurrentSearch = '';
var _prCurrentRange  = '';
var _prCurrentUID    = 0;

function getPurchaseReturnsDetails(page, status, search, dateRange) {
    page      = page      || 1;
    status    = status    || 'All';
    search    = search    || '';
    dateRange = dateRange || '';

    _prCurrentPage   = page;
    _prCurrentStatus = status;
    _prCurrentSearch = search;
    _prCurrentRange  = dateRange;

    $.ajax({
        url   : '/purchasereturns/getPurchaseReturnsPageDetails',
        method: 'GET',
        data  : { page: page, status: status, search: search, dateRange: dateRange },
        success: function(resp) {
            if (resp.Error) return;
            $('#prTableBody').html(resp.Html || '');
            _renderPRPagination(resp.Pagination);
            _updatePRTabCounts(resp.StatusCounts);
        }
    });
}

function _renderPRPagination(p) {
    if (!p) return;
    $('#prPaginationInfo').text('Showing ' + p.from + ' to ' + p.to + ' of ' + p.total + ' entries');
    var $ul = $('#prPagination').empty();
    for (var i = 1; i <= p.lastPage; i++) {
        var active = (i === p.currentPage) ? 'active' : '';
        $ul.append('<li class="page-item ' + active + '"><a class="page-link pr-page-link" href="javascript:void(0);" data-page="' + i + '">' + i + '</a></li>');
    }
}

function _updatePRTabCounts(counts) {
    if (!counts) return;
    $('.pr-tab-count').each(function() {
        var $b = $(this);
        var tab = $b.closest('.pr-status-tab').data('status') || 'All';
        var c = counts[tab] !== undefined ? counts[tab] : '';
        if (c !== '') { $b.text(c).removeClass('d-none'); } else { $b.addClass('d-none'); }
    });
    var allBadge = $('.pr-status-tab[data-status="All"] .pr-tab-count');
    if (counts['All'] !== undefined) allBadge.text(counts['All']).removeClass('d-none');
}

function _buildPRDetailHtml(resp) {
    var currency = resp.Currency || '';
    var d = resp.Data || {};
    var items = resp.Items || [];
    var html = '<div class="row mb-3"><div class="col-md-6"><h6 class="fw-bold">' + (d.UniqueNumber || 'Draft') + '</h6>' +
        '<div class="small text-muted">Date: ' + (d.TransDateDisplay || '—') + '</div>' +
        '<div class="small text-muted">Vendor: ' + (d.PartyName || '—') + '</div>' +
        '<div class="small text-muted">Reference: ' + (d.Reference || '—') + '</div>' +
        '</div><div class="col-md-6 text-end">' +
        '<span class="badge bg-label-success fs-6">' + (d.DocStatus || '') + '</span>' +
        '</div></div>';
    html += '<div class="table-responsive"><table class="table table-sm table-bordered">' +
        '<thead class="table-light"><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Tax</th><th class="text-end">Total</th></tr></thead><tbody>';
    $.each(items, function(i, item) {
        html += '<tr><td>' + (i+1) + '</td><td>' + (item.ProductName||'') + '</td><td>' + (item.Quantity||0) + '</td><td>' + currency + ' ' + (item.UnitPrice||0) + '</td><td>' + (item.TaxPercentage||0) + '%</td><td class="text-end">' + currency + ' ' + (item.NetAmount||0) + '</td></tr>';
    });
    html += '</tbody></table></div>';
    html += '<div class="row"><div class="col-md-6 offset-md-6"><table class="table table-sm">' +
        '<tr><td>Sub Total</td><td class="text-end">' + currency + ' ' + (d.SubTotal||0) + '</td></tr>' +
        '<tr><td>Discount</td><td class="text-end">' + currency + ' ' + (d.DiscountAmount||0) + '</td></tr>' +
        '<tr><td>Tax</td><td class="text-end">' + currency + ' ' + (d.TaxAmount||0) + '</td></tr>' +
        '<tr class="fw-bold"><td>Total</td><td class="text-end">' + currency + ' ' + (d.NetAmount||0) + '</td></tr>' +
        '</table></div></div>';
    if (d.Notes) html += '<div class="mt-2 small"><strong>Notes:</strong> ' + d.Notes + '</div>';
    return html;
}

$(function() {
    'use strict'

    getPurchaseReturnsDetails(1, 'All', '', '');

    $(document).on('click', '.pr-status-tab', function() {
        $('.pr-status-tab').removeClass('active');
        $(this).addClass('active');
        getPurchaseReturnsDetails(1, $(this).data('status'), _prCurrentSearch, _prCurrentRange);
    });

    var _prSearchTimer;
    $(document).on('input', '#searchTransactionData', function() {
        clearTimeout(_prSearchTimer);
        var val = $.trim($(this).val());
        _prSearchTimer = setTimeout(function() {
            getPurchaseReturnsDetails(1, _prCurrentStatus, val, _prCurrentRange);
        }, 400);
    });

    $(document).on('click', '.date-option', function() {
        var range = $(this).data('range');
        var label = $(this).text().trim();
        $('#dateFilterLabel').text(label || 'All Dates');
        getPurchaseReturnsDetails(1, _prCurrentStatus, _prCurrentSearch, range);
    });

    $(document).on('click', '.pr-page-link', function() {
        getPurchaseReturnsDetails($(this).data('page'), _prCurrentStatus, _prCurrentSearch, _prCurrentRange);
    });

    $(document).on('click', '.pageRefresh', function() {
        getPurchaseReturnsDetails(_prCurrentPage, _prCurrentStatus, _prCurrentSearch, _prCurrentRange);
    });

    $(document).on('change', '#checkAllPR', function() {
        $('.prCheck').prop('checked', $(this).is(':checked'));
    });

    $(document).on('click', '.pr-status-update', function() {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        Swal.fire({
            title: 'Update Status', text: 'Change to "' + status + '"?', icon: 'question',
            showCancelButton: true, confirmButtonText: 'Yes, Update',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/purchasereturns/updatePurchaseReturnStatus', { TransUID: uid, Status: status, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                getPurchaseReturnsDetails(_prCurrentPage, _prCurrentStatus, _prCurrentSearch, _prCurrentRange);
            });
        });
    });

    $(document).on('click', '.viewPurchaseReturn', function() {
        _prCurrentUID = $(this).data('uid');
        $('#viewPRModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewPRModal').modal('show');
        $.get('/purchasereturns/getPurchaseReturnDetail', { TransUID: _prCurrentUID }, function(resp) {
            if (resp.Error) { $('#viewPRModalBody').html('<div class="alert alert-danger">' + resp.Message + '</div>'); return; }
            $('#viewPRModalBody').html(_buildPRDetailHtml(resp));
        });
    });

    $(document).on('click', '.a4PrintPurchaseReturn', function() {
        _prCurrentUID = $(this).data('uid');
        $('#a4PrintPRModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#a4PrintPRModal').modal('show');
        $.get('/purchasereturns/getPurchaseReturnDetail', { TransUID: _prCurrentUID }, function(resp) {
            if (resp.Error) { $('#a4PrintPRModalBody').html('<div class="alert alert-danger">' + resp.Message + '</div>'); return; }
            $('#a4PrintPRModalBody').html(_buildPRDetailHtml(resp));
        });
    });

    $(document).on('click', '#prModalPrintBtn, #prDoPrintBtn', function() { window.print(); });

    $(document).on('click', '.duplicatePurchaseReturn', function() {
        var uid = $(this).data('uid');
        Swal.fire({ title: 'Duplicate Purchase Return?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Duplicate' })
        .then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/purchasereturns/duplicatePurchaseReturn', { TransUID: uid, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                Swal.fire({ icon: 'success', title: 'Duplicated', timer: 2000, timerProgressBar: true });
                getPurchaseReturnsDetails(_prCurrentPage, _prCurrentStatus, _prCurrentSearch, _prCurrentRange);
            });
        });
    });

    $(document).on('click', '.deletePurchaseReturn', function() {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || 'this purchase return';
        Swal.fire({ title: 'Delete Purchase Return?', text: 'Delete "' + num + '"?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, Delete', confirmButtonColor: '#d33' })
        .then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/purchasereturns/deletePurchaseReturn', { TransUID: uid, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                Swal.fire({ icon: 'success', title: 'Deleted', timer: 2000, timerProgressBar: true });
                getPurchaseReturnsDetails(_prCurrentPage, _prCurrentStatus, _prCurrentSearch, _prCurrentRange);
            });
        });
    });

});
</script>
