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
                            <ul class="nav nav-pills gap-1" id="cnStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 active cn-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="badge bg-info ms-1 cn-tab-count"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 cn-status-tab" data-status="Issued" href="javascript:void(0);">
                                        Issued <span class="badge bg-info ms-1 cn-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 cn-status-tab" data-status="Applied" href="javascript:void(0);">
                                        Applied <span class="badge bg-info ms-1 cn-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 cn-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-info ms-1 cn-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 cn-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="badge bg-info ms-1 cn-tab-count d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn pageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                <div class="input-group input-group-sm" style="width:220px">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="searchTransactionData" placeholder="Search..." title="Credit Note Number or Customer Name">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:220px;max-height:320px;overflow-y:auto">
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
                                        <li><a class="dropdown-item date-option" data-range="this_quarter">This Quarter</a></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>
                                    </ul>
                                </div>
                                <a href="/creditnotes/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="cnTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px"><input class="form-check-input" type="checkbox" id="checkAllCN"></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">#</th>
                                        <th>Credit Note No.</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="cnTableBody">
                                    <?php
                                    $SerialNumber = 0;
                                    $this->load->view('transactions/creditnotes/list', ['DataLists' => $DataLists, 'SerialNumber' => &$SerialNumber]);
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 py-2" id="cnPaginationWrap">
                            <div class="text-muted small" id="cnPaginationInfo"></div>
                            <nav><ul class="pagination pagination-sm mb-0" id="cnPagination"></ul></nav>
                        </div>

                    </div>
                </div>
                <?php $this->load->view('common/footer_view'); ?>
            </div>
        </div>
    </div>
</div>

<!-- View Credit Note Modal -->
<div class="modal fade" id="viewCNModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Credit Note Details</h5>
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="cnModalPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewCNModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- A4 Print Modal -->
<div class="modal fade" id="a4PrintCNModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Credit Note</h5>
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <select id="cnPrintSizeSelect" class="form-select form-select-sm" style="width:100px">
                        <option value="A4">A4</option>
                        <option value="A5">A5</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="cnDoPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="a4PrintCNModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer_desc'); ?>

<script src="/js/transactions/creditnotes.js"></script>
<script src="/js/transactions/transactions.js"></script>

<script>
const ModuleId    = 107;
const ModuleTable = '#cnTable';

var _cnCurrentPage   = 1;
var _cnCurrentStatus = 'All';
var _cnCurrentSearch = '';
var _cnCurrentRange  = '';
var _cnCurrentUID    = 0;

function getCreditNotesDetails(page, status, search, dateRange) {
    page      = page      || 1;
    status    = status    || 'All';
    search    = search    || '';
    dateRange = dateRange || '';

    _cnCurrentPage   = page;
    _cnCurrentStatus = status;
    _cnCurrentSearch = search;
    _cnCurrentRange  = dateRange;

    $.ajax({
        url   : '/creditnotes/getCreditNotesPageDetails',
        method: 'GET',
        data  : { page: page, status: status, search: search, dateRange: dateRange },
        success: function(resp) {
            if (resp.Error) return;
            var $tbody = $('#cnTableBody');
            $tbody.html(resp.Html || '');
            _renderCNPagination(resp.Pagination);
            _updateCNTabCounts(resp.StatusCounts);
        }
    });
}

function _renderCNPagination(p) {
    if (!p) return;
    var info = 'Showing ' + p.from + ' to ' + p.to + ' of ' + p.total + ' entries';
    $('#cnPaginationInfo').text(info);
    var $ul = $('#cnPagination').empty();
    for (var i = 1; i <= p.lastPage; i++) {
        var active = (i === p.currentPage) ? 'active' : '';
        $ul.append('<li class="page-item ' + active + '"><a class="page-link cn-page-link" href="javascript:void(0);" data-page="' + i + '">' + i + '</a></li>');
    }
}

function _updateCNTabCounts(counts) {
    if (!counts) return;
    $('.cn-tab-count').each(function() {
        var $b = $(this);
        var tab = $b.closest('.cn-status-tab').data('status') || 'All';
        var c = counts[tab] !== undefined ? counts[tab] : '';
        if (c !== '') { $b.text(c).removeClass('d-none'); } else { $b.addClass('d-none'); }
    });
    var allBadge = $('.cn-status-tab[data-status="All"] .cn-tab-count');
    if (counts['All'] !== undefined) allBadge.text(counts['All']).removeClass('d-none');
}

function _buildCNDetailHtml(resp) {
    var currency = resp.Currency || '';
    var d = resp.Data || {};
    var items = resp.Items || [];
    var html = '<div class="row mb-3"><div class="col-md-6"><h6 class="fw-bold">' + (d.UniqueNumber || 'Draft') + '</h6>' +
        '<div class="small text-muted">Date: ' + (d.TransDateDisplay || '—') + '</div>' +
        '<div class="small text-muted">Customer: ' + (d.PartyName || '—') + '</div>' +
        '<div class="small text-muted">Reference: ' + (d.Reference || '—') + '</div>' +
        '</div><div class="col-md-6 text-end">' +
        '<span class="badge bg-label-primary fs-6">' + (d.DocStatus || '') + '</span>' +
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

    getCreditNotesDetails(1, 'All', '', '');

    // Status tabs
    $(document).on('click', '.cn-status-tab', function() {
        $('.cn-status-tab').removeClass('active');
        $(this).addClass('active');
        getCreditNotesDetails(1, $(this).data('status'), _cnCurrentSearch, _cnCurrentRange);
    });

    // Search
    var _cnSearchTimer;
    $(document).on('input', '#searchTransactionData', function() {
        clearTimeout(_cnSearchTimer);
        var val = $.trim($(this).val());
        _cnSearchTimer = setTimeout(function() {
            getCreditNotesDetails(1, _cnCurrentStatus, val, _cnCurrentRange);
        }, 400);
    });

    // Date filter
    $(document).on('click', '.date-option', function() {
        var range = $(this).data('range');
        var label = $(this).text().trim();
        $('#dateFilterLabel').text(label || 'All Dates');
        getCreditNotesDetails(1, _cnCurrentStatus, _cnCurrentSearch, range);
    });

    // Pagination
    $(document).on('click', '.cn-page-link', function() {
        getCreditNotesDetails($(this).data('page'), _cnCurrentStatus, _cnCurrentSearch, _cnCurrentRange);
    });

    // Refresh
    $(document).on('click', '.pageRefresh', function() {
        getCreditNotesDetails(_cnCurrentPage, _cnCurrentStatus, _cnCurrentSearch, _cnCurrentRange);
    });

    // Check all
    $(document).on('change', '#checkAllCN', function() {
        $('.cnCheck').prop('checked', $(this).is(':checked'));
    });

    // Status update
    $(document).on('click', '.cn-status-update', function() {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        Swal.fire({
            title            : 'Update Status',
            text             : 'Change status to "' + status + '"?',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Yes, Update',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/creditnotes/updateCreditNoteStatus', { TransUID: uid, Status: status, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                getCreditNotesDetails(_cnCurrentPage, _cnCurrentStatus, _cnCurrentSearch, _cnCurrentRange);
            });
        });
    });

    // View modal
    $(document).on('click', '.viewCreditNote', function() {
        _cnCurrentUID = $(this).data('uid');
        $('#viewCNModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewCNModal').modal('show');
        $.get('/creditnotes/getCreditNoteDetail', { TransUID: _cnCurrentUID }, function(resp) {
            if (resp.Error) { $('#viewCNModalBody').html('<div class="alert alert-danger">' + resp.Message + '</div>'); return; }
            $('#viewCNModalBody').html(_buildCNDetailHtml(resp));
        });
    });

    // Print A4
    $(document).on('click', '.a4PrintCreditNote', function() {
        _cnCurrentUID = $(this).data('uid');
        $('#a4PrintCNModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#a4PrintCNModal').modal('show');
        $.get('/creditnotes/getCreditNoteDetail', { TransUID: _cnCurrentUID }, function(resp) {
            if (resp.Error) { $('#a4PrintCNModalBody').html('<div class="alert alert-danger">' + resp.Message + '</div>'); return; }
            $('#a4PrintCNModalBody').html(_buildCNDetailHtml(resp));
        });
    });

    $(document).on('click', '#cnModalPrintBtn, #cnDoPrintBtn', function() {
        window.print();
    });

    // Duplicate
    $(document).on('click', '.duplicateCreditNote', function() {
        var uid = $(this).data('uid');
        Swal.fire({
            title            : 'Duplicate Credit Note?',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Yes, Duplicate',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/creditnotes/duplicateCreditNote', { TransUID: uid, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                Swal.fire({ icon: 'success', title: 'Duplicated', timer: 2000, timerProgressBar: true });
                getCreditNotesDetails(_cnCurrentPage, _cnCurrentStatus, _cnCurrentSearch, _cnCurrentRange);
            });
        });
    });

    // Delete
    $(document).on('click', '.deleteCreditNote', function() {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || 'this credit note';
        Swal.fire({
            title            : 'Delete Credit Note?',
            text             : 'Delete "' + num + '"? This cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#d33',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/creditnotes/deleteCreditNote', { TransUID: uid, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                Swal.fire({ icon: 'success', title: 'Deleted', timer: 2000, timerProgressBar: true });
                getCreditNotesDetails(_cnCurrentPage, _cnCurrentStatus, _cnCurrentSearch, _cnCurrentRange);
            });
        });
    });

});
</script>
