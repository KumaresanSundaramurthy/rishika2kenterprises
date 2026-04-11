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
                            <ul class="nav nav-pills gap-1" id="dnStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 active dn-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="badge bg-info ms-1 dn-tab-count"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 dn-status-tab" data-status="Issued" href="javascript:void(0);">
                                        Issued <span class="badge bg-info ms-1 dn-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 dn-status-tab" data-status="Applied" href="javascript:void(0);">
                                        Applied <span class="badge bg-info ms-1 dn-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 dn-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-info ms-1 dn-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 dn-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="badge bg-info ms-1 dn-tab-count d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn pageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                <div class="input-group input-group-sm" style="width:220px">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="searchTransactionData" placeholder="Search..." title="Debit Note Number or Vendor Name">
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
                                <a href="/debitnotes/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="dnTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px"><input class="form-check-input" type="checkbox" id="checkAllDN"></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">#</th>
                                        <th>Debit Note No.</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Vendor</th>
                                        <th>Date</th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="dnTableBody">
                                    <?php
                                    $SerialNumber = 0;
                                    $this->load->view('transactions/debitnotes/list', ['DataLists' => $DataLists, 'SerialNumber' => &$SerialNumber]);
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 py-2" id="dnPaginationWrap">
                            <div class="text-muted small" id="dnPaginationInfo"></div>
                            <nav><ul class="pagination pagination-sm mb-0" id="dnPagination"></ul></nav>
                        </div>

                    </div>
                </div>
                <?php $this->load->view('common/footer_view'); ?>
            </div>
        </div>
    </div>
</div>

<!-- View Debit Note Modal -->
<div class="modal fade" id="viewDNModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Debit Note Details</h5>
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="dnModalPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewDNModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- A4 Print Modal -->
<div class="modal fade" id="a4PrintDNModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Debit Note</h5>
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <select id="dnPrintSizeSelect" class="form-select form-select-sm" style="width:100px">
                        <option value="A4">A4</option>
                        <option value="A5">A5</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="dnDoPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="a4PrintDNModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer_desc'); ?>

<script src="/js/transactions/debitnotes.js"></script>
<script src="/js/transactions/transactions.js"></script>

<script>
const ModuleId    = 109;
const ModuleTable = '#dnTable';

var _dnCurrentPage   = 1;
var _dnCurrentStatus = 'All';
var _dnCurrentSearch = '';
var _dnCurrentRange  = '';
var _dnCurrentUID    = 0;

function getDebitNotesDetails(page, status, search, dateRange) {
    page      = page      || 1;
    status    = status    || 'All';
    search    = search    || '';
    dateRange = dateRange || '';

    _dnCurrentPage   = page;
    _dnCurrentStatus = status;
    _dnCurrentSearch = search;
    _dnCurrentRange  = dateRange;

    $.ajax({
        url   : '/debitnotes/getDebitNotesPageDetails',
        method: 'GET',
        data  : { page: page, status: status, search: search, dateRange: dateRange },
        success: function(resp) {
            if (resp.Error) return;
            $('#dnTableBody').html(resp.Html || '');
            _renderDNPagination(resp.Pagination);
            _updateDNTabCounts(resp.StatusCounts);
        }
    });
}

function _renderDNPagination(p) {
    if (!p) return;
    $('#dnPaginationInfo').text('Showing ' + p.from + ' to ' + p.to + ' of ' + p.total + ' entries');
    var $ul = $('#dnPagination').empty();
    for (var i = 1; i <= p.lastPage; i++) {
        var active = (i === p.currentPage) ? 'active' : '';
        $ul.append('<li class="page-item ' + active + '"><a class="page-link dn-page-link" href="javascript:void(0);" data-page="' + i + '">' + i + '</a></li>');
    }
}

function _updateDNTabCounts(counts) {
    if (!counts) return;
    $('.dn-tab-count').each(function() {
        var $b = $(this);
        var tab = $b.closest('.dn-status-tab').data('status') || 'All';
        var c = counts[tab] !== undefined ? counts[tab] : '';
        if (c !== '') { $b.text(c).removeClass('d-none'); } else { $b.addClass('d-none'); }
    });
    var allBadge = $('.dn-status-tab[data-status="All"] .dn-tab-count');
    if (counts['All'] !== undefined) allBadge.text(counts['All']).removeClass('d-none');
}

function _buildDNDetailHtml(resp) {
    var currency = resp.Currency || '';
    var d = resp.Data || {};
    var items = resp.Items || [];
    var html = '<div class="row mb-3"><div class="col-md-6"><h6 class="fw-bold">' + (d.UniqueNumber || 'Draft') + '</h6>' +
        '<div class="small text-muted">Date: ' + (d.TransDateDisplay || '—') + '</div>' +
        '<div class="small text-muted">Vendor: ' + (d.PartyName || '—') + '</div>' +
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

    getDebitNotesDetails(1, 'All', '', '');

    $(document).on('click', '.dn-status-tab', function() {
        $('.dn-status-tab').removeClass('active');
        $(this).addClass('active');
        getDebitNotesDetails(1, $(this).data('status'), _dnCurrentSearch, _dnCurrentRange);
    });

    var _dnSearchTimer;
    $(document).on('input', '#searchTransactionData', function() {
        clearTimeout(_dnSearchTimer);
        var val = $.trim($(this).val());
        _dnSearchTimer = setTimeout(function() {
            getDebitNotesDetails(1, _dnCurrentStatus, val, _dnCurrentRange);
        }, 400);
    });

    $(document).on('click', '.date-option', function() {
        var range = $(this).data('range');
        var label = $(this).text().trim();
        $('#dateFilterLabel').text(label || 'All Dates');
        getDebitNotesDetails(1, _dnCurrentStatus, _dnCurrentSearch, range);
    });

    $(document).on('click', '.dn-page-link', function() {
        getDebitNotesDetails($(this).data('page'), _dnCurrentStatus, _dnCurrentSearch, _dnCurrentRange);
    });

    $(document).on('click', '.pageRefresh', function() {
        getDebitNotesDetails(_dnCurrentPage, _dnCurrentStatus, _dnCurrentSearch, _dnCurrentRange);
    });

    $(document).on('change', '#checkAllDN', function() {
        $('.dnCheck').prop('checked', $(this).is(':checked'));
    });

    $(document).on('click', '.dn-status-update', function() {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        Swal.fire({ title: 'Update Status', text: 'Change to "' + status + '"?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Update' })
        .then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/debitnotes/updateDebitNoteStatus', { TransUID: uid, Status: status, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                getDebitNotesDetails(_dnCurrentPage, _dnCurrentStatus, _dnCurrentSearch, _dnCurrentRange);
            });
        });
    });

    $(document).on('click', '.viewDebitNote', function() {
        _dnCurrentUID = $(this).data('uid');
        $('#viewDNModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewDNModal').modal('show');
        $.get('/debitnotes/getDebitNoteDetail', { TransUID: _dnCurrentUID }, function(resp) {
            if (resp.Error) { $('#viewDNModalBody').html('<div class="alert alert-danger">' + resp.Message + '</div>'); return; }
            $('#viewDNModalBody').html(_buildDNDetailHtml(resp));
        });
    });

    $(document).on('click', '.a4PrintDebitNote', function() {
        _dnCurrentUID = $(this).data('uid');
        $('#a4PrintDNModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#a4PrintDNModal').modal('show');
        $.get('/debitnotes/getDebitNoteDetail', { TransUID: _dnCurrentUID }, function(resp) {
            if (resp.Error) { $('#a4PrintDNModalBody').html('<div class="alert alert-danger">' + resp.Message + '</div>'); return; }
            $('#a4PrintDNModalBody').html(_buildDNDetailHtml(resp));
        });
    });

    $(document).on('click', '#dnModalPrintBtn, #dnDoPrintBtn', function() { window.print(); });

    $(document).on('click', '.duplicateDebitNote', function() {
        var uid = $(this).data('uid');
        Swal.fire({ title: 'Duplicate Debit Note?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Duplicate' })
        .then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/debitnotes/duplicateDebitNote', { TransUID: uid, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                Swal.fire({ icon: 'success', title: 'Duplicated', timer: 2000, timerProgressBar: true });
                getDebitNotesDetails(_dnCurrentPage, _dnCurrentStatus, _dnCurrentSearch, _dnCurrentRange);
            });
        });
    });

    $(document).on('click', '.deleteDebitNote', function() {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || 'this debit note';
        Swal.fire({ title: 'Delete Debit Note?', text: 'Delete "' + num + '"?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, Delete', confirmButtonColor: '#d33' })
        .then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/debitnotes/deleteDebitNote', { TransUID: uid, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' }, function(resp) {
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                Swal.fire({ icon: 'success', title: 'Deleted', timer: 2000, timerProgressBar: true });
                getDebitNotesDetails(_dnCurrentPage, _dnCurrentStatus, _dnCurrentSearch, _dnCurrentRange);
            });
        });
    });

});
</script>
