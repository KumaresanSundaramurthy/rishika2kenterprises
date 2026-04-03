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

                        <!-- ── Toolbar ─────────────────────────────── -->
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 border-bottom-0">

                            <!-- Status tabs -->
                            <ul class="nav nav-pills gap-1" id="quotStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 active quot-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="badge bg-info ms-1 quot-tab-count"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Open" href="javascript:void(0);">
                                        Open <span class="badge bg-info ms-1 quot-tab-count d-none"></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Closed" href="javascript:void(0);">
                                        Closed <span class="badge bg-info ms-1 quot-tab-count d-none"></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Partial" href="javascript:void(0);">
                                        Partial <span class="badge bg-info ms-1 quot-tab-count d-none"></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-info ms-1 quot-tab-count d-none"></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="badge bg-info ms-1 quot-tab-count d-none"></span></a>
                                </li>
                            </ul>

                            <!-- Search + Date filter + Create -->
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript: void(0);" class="btn pageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                <div class="input-group input-group-sm" style="width:220px">
                                    <span class="input-group-text" id="transSearchIcon"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="searchTransactionData" placeholder="Search..." title="Quotation Number or Customer Name">
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:220px;max-height:320px;overflow-y:auto">
                                        <li><a class="dropdown-item date-option" data-range="">
                                            <i class="bx bx-list-ul me-2"></i>All Dates
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="today"><i class="bx bx-circle me-2"></i>Today</a></li>
                                        <li><a class="dropdown-item date-option" data-range="yesterday"><i class="bx bx-circle me-2"></i>Yesterday</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_week"><i class="bx bx-circle me-2"></i>This Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_week"><i class="bx bx-circle me-2"></i>Last Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_7_days"><i class="bx bx-circle me-2"></i>Last 7 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_month"><i class="bx bx-circle me-2"></i>This Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="previous_month"><i class="bx bx-circle me-2"></i>Previous Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_30_days"><i class="bx bx-circle me-2"></i>Last 30 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year"><i class="bx bx-circle me-2"></i>This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year"><i class="bx bx-circle me-2"></i>Last Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_quarter"><i class="bx bx-circle me-2"></i>Last Quarter</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                            <i class="bx bxs-star text-warning me-2"></i>FY 25-26
                                        </a></li>
                                    </ul>
                                </div>

                                <a href="/quotations/create" class="btn btn-primary btn-sm px-3">Create </a>
                            </div>
                        </div>

                        <!-- ── Table ───────────────────────────────── -->
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover MainviewTable mb-0" id="quotTable">
                                <thead class="bg-body-tertiary">
                                    <tr>
                                        <th class="table-checkbox" style="width:40px">
                                            <div class="form-check">
                                                <input class="form-check-input table-chkbox quotHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            # Quotation <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Valid Until</th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ── Pagination ──────────────────────────── -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center quotPagination" id="quotPagination">
                            <?php echo $ModPagination ? $ModPagination : ''; ?>
                        </div>

                    </div>

                    <!-- ── View Quotation Modal ──────────────────── -->
                    <div class="modal fade" id="viewQuotationModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header bg-body-secondary p-3 d-flex justify-content-between align-items-center">
                                    <h6 class="modal-title fw-semibold fs-6 text-primary mb-0" id="viewQuotModalTitle">Quotation Details</h6>
                                    <div class="gap-2">
                                        <a href="javascript:void(0);" id="viewQuotEditBtn" class="btn btn-warning btn-sm me-2">
                                            <i class="bx bx-edit me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <div class="modal-body p-0" id="viewQuotModalBody">
                                    <div class="d-flex justify-content-center align-items-center py-5">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>
                                <div class="modal-footer py-2"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/quotations.js"></script>

<script>
const  ModuleId     = 101;
const  ModuleTable  = '#quotTable';
const  ModulePag    = '.quotPagination';
const  ModuleHeader = '.quotHeaderCheck';
const  ModuleRow    = '.quotationCheck';
$(function () {
    'use strict'

    Filter['Status'] = 'All';

    // ── Status tabs (with count badge) ──────────────────
    $(document).on('click', '.quot-status-tab', function (e) {
        e.preventDefault();
        $('.quot-status-tab').removeClass('active');
        $(this).addClass('active');
        Filter.Status = $(this).data('status') || 'All';
        PageNo = 1;
        getQuotationsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        Filter.Status = $(this).data('status') || 'All';
        PageNo = 1;
        getQuotationsDetails();
    });

    // Search
    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getQuotationsDetails();
    }, 400));

    // Date filter
    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range') || '';
        var dates = getDateRange(range);
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from;
        Filter.DateTo   = dates.to;
        PageNo = 1;
        getQuotationsDetails();
    });

    // Column sorting
    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) {
            Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        } else {
            Filter.SortBy  = col;
            Filter.SortDir = 'DESC';
        }
        // Update sort icons
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        var icon = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
        PageNo = 1;
        getQuotationsDetails();
    });

    // Pagination
    $(document).on('click', '.quotPagination .page-link', function (e) {
        e.preventDefault();
        var href  = $(this).attr('href') || '';
        var match = href.match(/\/(\d+)$/);
        if (match) {
            PageNo = parseInt(match[1]);
            getQuotationsDetails();
        }
    });

    // Delete
    $(document).on('click', '.deleteQuotation', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title            : 'Delete Quotation?',
            html             : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/quotations/deleteQuotation',
                method : 'POST',
                data   : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getQuotationsDetails();
                    }
                }
            });
        });
    });

    // Duplicate
    $(document).on('click', '.duplicateQuotation', function () {
        var uid = $(this).data('uid');
        Swal.fire({
            title            : 'Duplicate Quotation?',
            text             : 'A new draft copy will be created.',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Duplicate',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/quotations/duplicateQuotation',
                method : 'POST',
                data   : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            text             : resp.Message,
                            showCancelButton : true,
                            confirmButtonText: 'Edit Now',
                            cancelButtonText : 'Stay Here',
                        }).then(function (r) {
                            if (r.isConfirmed && resp.EditURL) {
                                window.location.href = resp.EditURL;
                            } else {
                                getQuotationsDetails();
                            }
                        });
                    }
                }
            });
        });
    });

    // ── Inline status update ──────────────────────────────
    $(document).on('click', '.quot-status-update', function () {
        var uid       = $(this).data('uid');
        var newStatus = $(this).data('status');
        $.ajax({
            url    : '/quotations/updateQuotationStatus',
            method : 'POST',
            data   : { TransUID: uid, Status: newStatus, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    getQuotationsDetails();
                }
            }
        });
    });

    // ── View quotation modal ──────────────────────────────
    var _quotStatusLabel = { Pending: 'Open', Accepted: 'Closed', Rejected: 'Cancelled' };
    var _quotStatusBadge = {
        Pending: 'bg-label-warning', Accepted: 'bg-label-success',
        Partial: 'bg-label-info',    Rejected: 'bg-label-danger',
        Converted: 'bg-label-primary', Draft: 'bg-label-secondary'
    };

    $(document).on('click', '.viewQuotation', function () {
        $('#viewQuotEditBtn').addClass('d-none');
        var uid = $(this).data('uid');
        $('#viewQuotModalBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewQuotEditBtn').attr('href', '/quotations/edit/' + uid);
        $('#viewQuotModalTitle').text('Quotation Details');
        var modal = new bootstrap.Modal(document.getElementById('viewQuotationModal'));
        modal.show();
        AjaxLoading = 0;
        $.ajax({
            url    : '/quotations/getQuotationDetail',
            method : 'GET',
            data   : { TransUID: uid },
            success: function (resp) {
                AjaxLoading = 1;
                $('#viewQuotEditBtn').removeClass('d-none');
                if (resp.Error) {
                    $('#viewQuotModalBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                    return;
                }
                var h   = resp.Header;
                var sym = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? ''); ?>';
                var statusLabel = _quotStatusLabel[h.DocStatus] || h.DocStatus;
                var statusBadge = _quotStatusBadge[h.DocStatus] || 'bg-label-secondary';

                if (h.UniqueNumber) $('#viewQuotModalTitle').text('Quotation — ' + h.UniqueNumber);

                // ── Header info ──────────────────────────
                var html = '<div class="p-3 border-bottom">'
                    + '<div class="row g-2">'
                    + '<div class="col-sm-4">'
                    + '  <div class="small text-muted fw-bold">Customer</div>'
                    + '  <div class="text-primary fw-bold">' + _esc(h.PartyName) + '</div>'
                    + (h.PartyMobile ? '<div class="small text-muted">' + _esc(h.PartyCountryCode) + ' ' + _esc(h.PartyMobile) + '</div>' : '')
                    + '</div>'
                    + '<div class="col-sm-2">'
                    + '  <div class="small text-muted fw-bold">Date</div>'
                    + '  <div>' + _esc(h.TransDate) + '</div>'
                    + '</div>'
                    + '<div class="col-sm-2">'
                    + '  <div class="small text-muted fw-bold">Valid Until</div>'
                    + '  <div>' + (h.ValidityDate || '—') + '</div>'
                    + '</div>'
                    + '<div class="col-sm-2">'
                    + '  <div class="small text-muted fw-bold">Status</div>'
                    + '  <span class="badge ' + statusBadge + '">' + statusLabel + '</span>'
                    + '</div>'
                    + '<div class="col-sm-2 text-end">'
                    + '  <div class="small text-muted fw-bold">Net Amount</div>'
                    + '  <div class="fw-bold fs-6">' + sym + ' ' + _esc(h.NetAmount) + '</div>'
                    + '</div>'
                    + '</div>'
                    + (h.Reference ? '<div class="small text-muted mt-2">Ref: <span class="text-body">' + _esc(h.Reference) + '</span></div>' : '')
                    + '</div>';

                // ── Items table ──────────────────────────
                html += '<div class="table-responsive"><table class="table table-sm table-hover MainviewTable mb-0">'
                    + '<thead class="bg-body-tertiary"><tr>'
                    + '<th>#</th><th>Product</th><th class="text-end">Qty</th>'
                    + '<th class="text-end">Unit Price</th><th class="text-end">Tax %</th>'
                    + '<th class="text-end">Tax Amt</th><th class="text-end">Net Amt</th>'
                    + '</tr></thead><tbody>';

                if (resp.Items && resp.Items.length) {
                    $.each(resp.Items, function (i, item) {
                        html += '<tr>'
                            + '<td>' + (i + 1) + '</td>'
                            + '<td><div class="fw-semibold">' + _esc(item.ProductName) + '</div>'
                            + (item.PartNumber ? '<div class="small text-muted">' + _esc(item.PartNumber) + '</div>' : '')
                            + '</td>'
                            + '<td class="text-end">' + _esc(smartDecimal(item.Quantity)) + ' ' + _esc(item.PrimaryUnitName || '') + '</td>'
                            + '<td class="text-end">' + sym + ' ' + _esc(item.UnitPrice) + '</td>'
                            + '<td class="text-end">' + _esc(item.TaxPercentage) + '%</td>'
                            + '<td class="text-end">' + sym + ' ' + _esc(item.TaxAmount) + '</td>'
                            + '<td class="text-end fw-semibold">' + sym + ' ' + _esc(item.NetAmount) + '</td>'
                            + '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="7" class="text-center text-muted py-3">No items</td></tr>';
                }

                html += '</tbody></table></div>';

                // ── Totals ───────────────────────────────
                html += '<div class="d-flex justify-content-end p-3 border-top">'
                    + '<table class="table table-sm w-auto mb-0">'
                    + '<tr><td class="text-muted pe-4">Sub Total</td><td class="text-end fw-semibold">' + sym + ' ' + _esc(h.SubTotal) + '</td></tr>'
                    + (parseFloat(h.DiscountAmount) > 0 ? '<tr><td class="text-muted pe-4">Discount</td><td class="text-end text-danger">- ' + sym + ' ' + _esc(h.DiscountAmount) + '</td></tr>' : '')
                    + (parseFloat(h.TaxAmount) > 0 ? '<tr><td class="text-muted pe-4">Tax</td><td class="text-end">' + sym + ' ' + _esc(h.TaxAmount) + '</td></tr>' : '')
                    + (parseFloat(h.AdditionalCharges) > 0 ? '<tr><td class="text-muted pe-4">Charges</td><td class="text-end">' + sym + ' ' + _esc(h.AdditionalCharges) + '</td></tr>' : '')
                    + '<tr class="border-top"><td class="fw-bold pe-4">Net Amount</td><td class="text-end fw-bold">' + sym + ' ' + _esc(h.NetAmount) + '</td></tr>'
                    + '</table></div>';

                if (h.Notes) {
                    html += '<div class="px-3 pb-3"><div class="small text-muted mb-1">Notes</div><div class="small border rounded p-2 bg-body-secondary">' + _esc(h.Notes) + '</div></div>';
                }

                $('#viewQuotModalBody').html(html);
            },
            error: function () {
                AjaxLoading = 1;
                $('#viewQuotEditBtn').removeClass('d-none');
                $('#viewQuotModalBody').html('<div class="alert alert-danger m-3">Failed to load quotation.</div>');
            }
        });
    });

    function _esc(v) {
        if (v === null || v === undefined) return '—';
        return $('<span>').text(String(v)).html();
    }

    // Convert to Sales Order / Invoice
    $(document).on('click', '.convertToQuot', function () {
        var uid    = $(this).data('uid');
        var num    = $(this).data('num') || '';
        var target = $(this).data('target') || 'Invoice';
        var label  = target === 'SalesOrder' ? 'Sales Order' : 'Invoice';
        Swal.fire({
            title            : 'Convert to ' + label + '?',
            html             : num ? '<strong>' + num + '</strong> will be marked as Converted.' : '',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Convert',
            confirmButtonColor: '#28a745',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/quotations/convertQuotationToInvoice',
                method : 'POST',
                data   : { TransUID: uid, ConvertTarget: target, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getQuotationsDetails();
                        Swal.fire({ icon: 'success', text: resp.Message, timer: 500, showConfirmButton: false });
                    }
                }
            });
        });
    });

});
</script>