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

                    <!-- ── A4 / A5 Print Modal ──────────────────────── -->
                    <div class="modal fade" id="a4PrintModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header modal-header-border-bottom p-3">
                                    <h6 class="modal-title fw-bold text-primary fs-6 mb-0"><i class="bx bx-file me-1"></i>Print Quotation</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <!-- Paper size selector -->
                                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom bg-body-secondary">
                                        <span class="small fw-semibold text-muted">Paper Size:</span>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="a4PaperSize" id="psA4" value="A4" checked>
                                            <label class="form-check-label small" for="psA4">A4 (210 × 297 mm)</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="a4PaperSize" id="psA5" value="A5">
                                            <label class="form-check-label small" for="psA5">A5 (148 × 210 mm)</label>
                                        </div>
                                    </div>
                                    <!-- Preview area -->
                                    <div id="a4PrintPreview" class="p-3" style="background:#e8e8e8; min-height:300px;">
                                        <div class="d-flex justify-content-center py-5">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer py-2">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="a4PrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Thermal Print Modal ──────────────────────── -->
                    <div class="modal fade" id="thermalPrintModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top" style="max-width: 600px">
                            <div class="modal-content">
                                <div class="modal-header modal-header-border-bottom p-3">
                                    <h6 class="modal-title text-primary fw-bold fs-6 mb-0"><i class="bx bx-printer me-1"></i>Thermal Receipt Preview</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-2 bg-white" id="thermalPrintBody">
                                    <div class="d-flex justify-content-center py-5">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>
                                <div class="modal-footer py-2">
                                    <a href="/quotations/thermalPrintConfig" class="btn btn-outline-secondary btn-sm me-auto" title="Configure printer">
                                        <i class="bx bx-cog me-1"></i>Configure
                                    </a>
                                    <!-- <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button> -->
                                    <button type="button" class="btn btn-dark btn-sm" id="thermalPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── View Quotation Modal ──────────────────── -->
                    <div class="modal fade" id="viewQuotationModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header modal-header-border-bottom p-3 d-flex justify-content-between align-items-center">
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

    // ── Thermal Print ─────────────────────────────────────────────────────────
    var _thermalData = null;

    $(document).on('click', '.thermalPrintQuotation', function () {
        var uid = $(this).data('uid');
        _thermalData = null;
        $('#thermalPrintBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        new bootstrap.Modal(document.getElementById('thermalPrintModal')).show();
        AjaxLoading = 0;
        $.ajax({
            url   : '/quotations/getQuotationDetail',
            method: 'GET',
            data  : { TransUID: uid },
            success: function (resp) {
                AjaxLoading = 1;
                if (resp.Error) {
                    $('#thermalPrintBody').html('<div class="alert alert-danger m-2">' + _esc(resp.Message) + '</div>');
                    return;
                }
                _thermalData = resp;
                $('#thermalPrintBody').html(_buildThermalHtml(resp, 0));
            },
            error: function () {
                AjaxLoading = 1;
                $('#thermalPrintBody').html('<div class="alert alert-danger m-2">Failed to load receipt.</div>');
            }
        });
    });

    $('#thermalPrintBtn').on('click', function () {
        if (!_thermalData) return;
        var cfg = _thermalData.ThermalConfig;
        var paperWidth = (cfg && cfg.PaperWidth) ? cfg.PaperWidth : '80mm';
        var receiptHtml = _buildThermalHtml(_thermalData, 1);
        var win = window.open('', '_blank', 'width=400,height=700');
        win.document.write(
            '<!DOCTYPE html><html><head><title>Thermal Receipt</title>' +
            '<style>' +
            '  * { margin:0; padding:0; box-sizing:border-box; }' +
            '  body { font-family: Arial, Helvetica, sans-serif; font-size:12px; width:' + paperWidth + '; padding:4px; }' +
            '  .fs-6 { font-size: 0.8rem !important; }' +
            '  .tp-center { text-align: center; }' +
            '  .tp-bold { font-weight: bold; }' +
            '  .tp-hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }' +
            '  .tp-row { display: flex; justify-content: space-between; margin: 1px 0; }' +
            '  .tp-row-end { display: flex; justify-content: end; margin: 1px 0; }' +
            '  .tp-item-name { font-weight: bold; margin-top: 2px; }' +
            '  .tp-small { font-size:11px; }' +
            '  .tp-total { font-size:13px; font-weight:bold; border-top:1px solid #000; padding-top:3px; margin-top:3px; }' +
            '  .tp-footer { text-align:center; margin-top:6px; font-size:11px; }' +
            '  @media print { @page { margin:0; size:' + paperWidth + ' auto; } body { width:' + paperWidth + '; } }' +
            '</style></head><body style="font-family: Arial, Helvetica, sans-serif !important; font-size: 12px !important; width:' + paperWidth + '; padding:4px;">' +
            receiptHtml +
            '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function () { win.print(); }, 300);
    });

    function _buildThermalHtml(resp, type) {
        var h   = resp.Header;
        var org = resp.OrgInfo  || {};
        var cfg = resp.ThermalConfig || {};
        var sym = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

        var line1 = cfg.HeaderLine1 || org.BrandName || org.Name || '';
        var line2 = cfg.HeaderLine2 || '';
        var line3 = cfg.HeaderLine3 || [org.CityText, org.StateText, org.Pincode].filter(Boolean).join(', ');

        var showGSTIN   = cfg.ShowGSTIN   !== undefined ? parseInt(cfg.ShowGSTIN)   : 1;
        var showMobile  = cfg.ShowMobile  !== undefined ? parseInt(cfg.ShowMobile)  : 1;
        var showHSN     = cfg.ShowHSN     !== undefined ? parseInt(cfg.ShowHSN)     : 1;
        var showTaxBkd  = cfg.ShowTaxBreakdown !== undefined ? parseInt(cfg.ShowTaxBreakdown) : 1;
        var footer      = cfg.FooterMessage || 'Thank you for your business!';

        var html = '';

        // Header
        html += '<div style="display: flex; align-items: center; justify-content: center;"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="60px;" height="60px;" alt="Rishika 2K Enterprises">';
        html += '<div class="fs-6 ms-1"><div class="tp-center tp-bold">' + _esc(line1) + '</div>';
        if (line2) html += '<div class="tp-center tp-small">' + _esc(line2) + '</div>';
        if (line3) html += '<div class="tp-center tp-small">' + _esc(line3) + '</div>';
        if (showMobile && org.MobileNumber) html += '<div class="tp-center tp-small">Ph: ' + _esc(org.MobileNumber) + '</div>';
        if (showGSTIN && org.GSTIN) html += '<div class="tp-center tp-small">GSTIN: ' + _esc(org.GSTIN) + '</div></div></div>';

        html += '<hr class="tp-hr my-1">';

        // Bill info
        html += '<div class="fs-6">';
            html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="tp-row fs-6"><span class="tp-bold">Quotation: </span><span class="tp-bold">' + _esc(h.UniqueNumber || '—') + '</span></div>';
                html += '<div class="tp-row"><span>Date: </span><span>' + _esc(h.TransDate) + '</span></div>';
            html += '</div>';
            html += '<div class="d-flex justify-content-between align-items-center">';
                html += '<div class="tp-row"><span>Customer: </span><span style="text-align:right;max-width:60%">' + _esc(h.PartyName) + '</span></div>';
                if (h.PartyMobile) html += '<div class="tp-row"><span>Phone: </span><span>' + _esc(h.PartyMobile) + '</span></div>';
            html += '</div>';
        html += '</div>';

        html += '<hr class="tp-hr my-1">';
        html += '<div class="fs-6">';
            html += '<div class="mb-1" style="display: flex; align-items: center; justify-content: space-between;">';
                html += '<div class="tp-row tp-item-name tp-bold"><span>Item </span></div><div></div>';
            html += '</div>';
            html += '<div style="display: flex; align-items: center; justify-content: space-between;">';
                html += '<div class="tp-row" style="font-size: smaller;">Quantity x Price</div>';
                html += '<div class="tp-row">Amount</div>';
            html += '</div>';
        html += '</div>';
        html += '<hr class="tp-hr my-1">';

        // Items
        $.each(resp.Items || [], function (i, item) {

            html += '<div class="fs-6">';
            var lineAmt = parseFloat(item.NetAmount) || 0;
            var hsnLine = (showHSN && item.HSNCode) ? ' [HSN:' + item.HSNCode + ']' : '';

            html += '<div class="mb-1" style="display: flex; align-items: center; justify-content: space-between;">';
                html += '<div class="tp-item-name fs-6">' + _esc(item.ProductName) + _esc(hsnLine) + '</div>';
                html += '<div></div>';
            html += '</div>';

            html += '<div class="mb-1" style="display: flex; align-items: center; justify-content: space-between;">';
                html += '<div class="tp-row tp-small" style="font-size: smaller;">'+ _esc(item.Quantity) + ' (' + _esc(item.PrimaryUnitName || ' PCS') + ') x ' + _esc(item.UnitPrice) + '</div>';
                html += '<div class="fs-6">' + lineAmt.toFixed(2) + '</div>';
            html += '</div>';

            if (showTaxBkd && parseFloat(item.TaxPercentage) > 0) {
                html += '<div style="display: flex; align-items: center; justify-content: space-between;">';
                var cgst = parseFloat(item.CgstAmount) || 0;
                var sgst = parseFloat(item.SgstAmount) || 0;
                var igst = parseFloat(item.IgstAmount) || 0;
                if (cgst > 0 && sgst > 0) {
                    html += '<div class="tp-row tp-small" style="color: #555; font-size: smaller;">' + 'CGST ' + item.CGST + '% ' + cgst.toFixed(2) + '</div>';
                    html += '<div class="tp-row tp-small" style="color: #555; font-size: smaller;">' + 'SGST ' + item.SGST + '% ' + sgst.toFixed(2) + '</div>';
                } else if (igst > 0) {
                    html += '<div class="tp-row tp-small" style="color: #555; font-size: smaller;">' + 'IGST ' + item.IGST + '%' + igst.toFixed(2) + '</div>';
                }
                html += '</div>';
            }
            html += '</div>';

            if(resp.Items.length > 1 && i != resp.Items.length - 1) {
                html += '<hr class="tp-hr my-1">';
            }

        });

        html += '<hr class="tp-hr my-1">';

        // Items / qty summary
        html += '<div class="tp-small fs-6" style="text-align: center !important;">Items/Qty: ' + (resp.Items ? resp.Items.length : 0) + ' / ' + (function(){var q=0; $.each(resp.Items||[],function(i,it){q+=parseFloat(it.Quantity)||0;}); return q;}()) + '</div>';
        html += '<hr class="tp-hr my-1">';

        // Totals
        html += '<div style="text-align: end !important;">';
            html += '<div class="tp-row-end fw-semibold tp-item-name"><span>Subtotal: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.SubTotal || 0).toFixed(2) + '</span></div>';
            if (parseFloat(h.DiscountAmount) > 0) {
                html += '<div class="tp-row-end fw-semibold"><span>Discount: </span><span class="fs-6">- ' + sym + ' ' + parseFloat(h.DiscountAmount).toFixed(2) + '</span></div>';
            }
            if (parseFloat(h.TaxAmount) > 0) {
                html += '<div class="tp-row-end fw-semibold"><span>Total Tax: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.TaxAmount).toFixed(2) + '</span></div>';
                if (showTaxBkd) {
                    if (parseFloat(h.CgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  CGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.CgstAmount).toFixed(2) + '</span></div>';
                    if (parseFloat(h.SgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  SGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.SgstAmount).toFixed(2) + '</span></div>';
                    if (parseFloat(h.IgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  IGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.IgstAmount).toFixed(2) + '</span></div>';
                }
            }
            if (parseFloat(h.AdditionalCharges) > 0) {
                html += '<div class="tp-row-end fw-semibold"><span>Charges: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.AdditionalCharges).toFixed(2) + '</span></div>';
            }
            if (parseFloat(h.RoundOff || 0) !== 0) {
                html += '<div class="tp-row-end tp-small fw-semibold"><span>Round Off: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.RoundOff).toFixed(2) + '</span></div>';
            }

            html += '<div class="tp-total tp-row-end fw-semibold tp-item-name"><span>Total Amount: </span><span class="fs-5">' + sym + ' ' + parseFloat(h.NetAmount || 0).toFixed(2) + '</span></div>';
        html += '</div>';

        // Footer
        html += '<hr class="tp-hr my-1">';
        html += '<div class="tp-footer" style="text-align: center !important;">' + _esc(footer) + '</div>';
        html += '<div style="margin-bottom:8px"></div>';

        // Wrap for modal preview
        if(type === 0) {
            return '<div style="font-family:\'Courier New\',Courier,monospace; font-size:13px; padding:8px; max-width: 580px; margin:0 auto; font-weight: 900;">' + html + '</div>';
        } else {
            return html;
        }

    }

    // ── A4 / A5 Print ────────────────────────────────────────────────────────
    var _a4Data = null;

    function _renderA4Preview() {
        if (!_a4Data) return;
        var size       = $('input[name="a4PaperSize"]:checked').val() || 'A4';
        var pageW      = size === 'A5' ? '148mm' : '210mm';
        var pageH      = size === 'A5' ? '210mm' : '297mm';
        var scale      = size === 'A5' ? 0.72 : 1;
        var thm        = _a4Data.PrintTheme || {};
        var fontFamily = thm.FontFamily || 'Arial';
        var fontSizePt = ((parseInt(thm.FontSizePx) || 11) * scale * 0.75).toFixed(1) + 'pt';
        // Inject Google Font link into preview page if needed
        var systemFonts = ['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
        if ($.inArray(fontFamily, systemFonts) === -1) {
            var linkId = 'gfont-' + fontFamily.replace(/\s+/g, '-');
            if (!$('#' + linkId).length) {
                $('<link>', { id: linkId, rel: 'stylesheet',
                    href: 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fontFamily) + ':wght@400;600;700&display=swap'
                }).appendTo('head');
            }
        }
        var html = '<div style="background:#fff; width:' + pageW + '; min-height:' + pageH + '; margin:0 auto; padding:14mm 12mm; box-shadow:0 2px 12px rgba(0,0,0,.18); font-size:' + fontSizePt + '; font-family:"' + fontFamily + '",Arial,Helvetica,sans-serif; box-sizing:border-box;">'
                 + _buildA4Html(_a4Data, size)
                 + '</div>';
        $('#a4PrintPreview').html(html);
    }

    $(document).on('click', '.a4PrintQuotation', function () {
        var uid = $(this).data('uid');
        _a4Data = null;
        $('#a4PrintPreview').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('input[name="a4PaperSize"][value="A4"]').prop('checked', true);
        new bootstrap.Modal(document.getElementById('a4PrintModal')).show();
        AjaxLoading = 0;
        $.ajax({
            url   : '/quotations/getQuotationDetail',
            method: 'GET',
            data  : { TransUID: uid },
            success: function (resp) {
                AjaxLoading = 1;
                if (resp.Error) {
                    $('#a4PrintPreview').html('<div class="alert alert-danger m-3">' + _esc(resp.Message) + '</div>');
                    return;
                }
                _a4Data = resp;
                _renderA4Preview();
            },
            error: function () {
                AjaxLoading = 1;
                $('#a4PrintPreview').html('<div class="alert alert-danger m-3">Failed to load quotation.</div>');
            }
        });
    });

    // Re-render preview when paper size changes
    $(document).on('change', 'input[name="a4PaperSize"]', function () {
        _renderA4Preview();
    });

    $('#a4PrintBtn').on('click', function () {
        if (!_a4Data) return;
        var size       = $('input[name="a4PaperSize"]:checked').val() || 'A4';
        var thm        = _a4Data.PrintTheme || {};
        var fontFamily = thm.FontFamily || 'Arial';
        var fontSizePt = ((parseInt(thm.FontSizePx) || 11) * 0.75).toFixed(1) + 'pt';
        var body       = _buildA4Html(_a4Data, size);
        var systemFonts = ['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
        var gfontImport = '';
        if ($.inArray(fontFamily, systemFonts) === -1) {
            gfontImport = '@import url("https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fontFamily) + ':wght@400;600;700&display=swap");';
        }
        var win = window.open('', '_blank', 'width=900,height=700');
        win.document.write(
            '<!DOCTYPE html><html><head><title>Quotation</title>' +
            '<style>' +
            gfontImport +
            '  * { margin:0; padding:0; box-sizing:border-box; }' +
            '  body { font-family:"' + fontFamily + '",Arial,Helvetica,sans-serif; font-size:' + fontSizePt + '; color:#222; }' +
            '  @media print { @page { size:' + size + ' portrait; margin:12mm; } body { margin:0; } .no-print { display:none; } }' +
            '  table { border-collapse:collapse; width:100%; }' +
            '  th { background:#f3f4f6; font-size:9pt; font-weight:600; padding:5px 6px; border:1px solid #ddd; text-align:left; }' +
            '  td { padding:5px 6px; border:1px solid #ddd; font-size:9.5pt; vertical-align:top; }' +
            '  .text-end { text-align:right; }' +
            '  .text-center { text-align:center; }' +
            '  .fw-bold { font-weight:700; }' +
            '  .text-muted { color:#666; }' +
            '  .bg-light { background:#f8f9fa; }' +
            '</style>' +
            '</head><body>' +
            '<div style="padding:0;">' + body + '</div>' +
            '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function () { win.print(); }, 350);
    });

    // ══════════════════════════════════════════════════════════════════════
    // A4/A5 Professional Invoice Theme Renderers
    // ══════════════════════════════════════════════════════════════════════

    function _amountToWords(amount) {
        var ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                    'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                    'Seventeen','Eighteen','Nineteen'];
        var tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        function grp(n) {
            if (!n) return '';
            if (n < 20) return ones[n] + ' ';
            if (n < 100) return tens[Math.floor(n/10)] + (n%10 ? ' '+ones[n%10] : '') + ' ';
            return ones[Math.floor(n/100)] + ' Hundred ' + grp(n%100);
        }
        var num = Math.round(parseFloat(amount) || 0);
        if (!num) return 'Zero Only';
        var r = '';
        if (num >= 10000000) { r += grp(Math.floor(num/10000000)) + 'Crore ';  num %= 10000000; }
        if (num >= 100000)   { r += grp(Math.floor(num/100000))   + 'Lakh ';   num %= 100000; }
        if (num >= 1000)     { r += grp(Math.floor(num/1000))     + 'Thousand '; num %= 1000; }
        r += grp(num);
        return r.trim() + ' Only';
    }

    function _isInterState(items) {
        var inter = false;
        $.each(items || [], function(i, it) {
            if (parseFloat(it.IgstAmount) > 0) { inter = true; return false; }
        });
        return inter;
    }

    // ── Professional GST items table (CGST/SGST or IGST columns) ─────────
    function _gstItemsTable(items, sym, fmt, thm, hBg, hFg) {
        var showHSN = !thm || parseInt(thm.ShowHSN) !== 0;
        var showTax = !thm || parseInt(thm.ShowTaxBreakdown) !== 0;
        var inter   = _isInterState(items);
        hBg = hBg || ((thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e');
        hFg = hFg || '#fff';

        var ths = 'padding:5px 4px;font-size:7.5pt;font-weight:700;border:1px solid rgba(0,0,0,.12);background:' + hBg + ';color:' + hFg + ';white-space:nowrap;';
        var tdr = 'padding:4px 4px;font-size:8.5pt;border:1px solid #e0e0e0;vertical-align:middle;';

        var html = '<table style="width:100%;border-collapse:collapse;margin-bottom:0;">';
        html += '<thead><tr>';
        html += '<th style="' + ths + 'text-align:center;width:26px;">S.No</th>';
        html += '<th style="' + ths + 'text-align:left;">Item Description</th>';
        if (showHSN) html += '<th style="' + ths + 'text-align:center;width:50px;">HSN/SAC</th>';
        html += '<th style="' + ths + 'text-align:center;width:48px;">Qty</th>';
        html += '<th style="' + ths + 'text-align:right;width:65px;">Rate</th>';
        html += '<th style="' + ths + 'text-align:right;width:52px;">Disc.</th>';
        html += '<th style="' + ths + 'text-align:right;width:65px;">Taxable</th>';
        if (showTax) {
            if (inter) {
                html += '<th style="' + ths + 'text-align:center;width:34px;">IGST%</th>';
                html += '<th style="' + ths + 'text-align:right;width:58px;">IGST Amt</th>';
            } else {
                html += '<th style="' + ths + 'text-align:center;width:34px;">CGST%</th>';
                html += '<th style="' + ths + 'text-align:right;width:55px;">CGST Amt</th>';
                html += '<th style="' + ths + 'text-align:center;width:34px;">SGST%</th>';
                html += '<th style="' + ths + 'text-align:right;width:55px;">SGST Amt</th>';
            }
        } else {
            html += '<th style="' + ths + 'text-align:center;width:40px;">Tax%</th>';
            html += '<th style="' + ths + 'text-align:right;width:58px;">Tax Amt</th>';
        }
        html += '<th style="' + ths + 'text-align:right;width:70px;">Amount</th>';
        html += '</tr></thead><tbody>';

        $.each(items || [], function(i, item) {
            var bg   = i%2 ? '#f9fafb' : '#ffffff';
            var cgst = parseFloat(item.CgstAmount) || 0;
            var sgst = parseFloat(item.SgstAmount) || 0;
            var igst = parseFloat(item.IgstAmount) || 0;
            var disc = parseFloat(item.DiscountAmount) || 0;
            var td   = tdr + 'background:' + bg + ';';

            html += '<tr>';
            html += '<td style="' + td + 'text-align:center;">' + (i+1) + '</td>';
            html += '<td style="' + td + 'text-align:left;"><div style="font-weight:600;">' + _esc(item.ProductName) + '</div>';
            if (item.PartNumber) html += '<div style="font-size:7pt;color:#888;">Part#: ' + _esc(item.PartNumber) + '</div>';
            html += '</td>';
            if (showHSN) html += '<td style="' + td + 'text-align:center;font-size:7.5pt;color:#666;">' + _esc(item.HSNCode || '—') + '</td>';
            html += '<td style="' + td + 'text-align:center;">' + fmt(item.Quantity) + '<div style="font-size:7pt;color:#999;">' + _esc(item.PrimaryUnitName||'') + '</div></td>';
            html += '<td style="' + td + 'text-align:right;">' + fmt(item.UnitPrice) + '</td>';
            html += '<td style="' + td + 'text-align:right;">' + (disc > 0 ? fmt(disc) : '—') + '</td>';
            html += '<td style="' + td + 'text-align:right;">' + fmt(item.TaxableAmount) + '</td>';
            if (showTax) {
                if (inter) {
                    html += '<td style="' + td + 'text-align:center;">' + (parseFloat(item.IGST)||0) + '%</td>';
                    html += '<td style="' + td + 'text-align:right;">' + fmt(igst) + '</td>';
                } else {
                    html += '<td style="' + td + 'text-align:center;">' + (parseFloat(item.CGST)||0) + '%</td>';
                    html += '<td style="' + td + 'text-align:right;">' + fmt(cgst) + '</td>';
                    html += '<td style="' + td + 'text-align:center;">' + (parseFloat(item.SGST)||0) + '%</td>';
                    html += '<td style="' + td + 'text-align:right;">' + fmt(sgst) + '</td>';
                }
            } else {
                html += '<td style="' + td + 'text-align:center;">' + (parseFloat(item.TaxPercentage)||0) + '%</td>';
                html += '<td style="' + td + 'text-align:right;">' + fmt(item.TaxAmount) + '</td>';
            }
            html += '<td style="' + td + 'text-align:right;font-weight:700;">' + fmt(item.NetAmount) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    }

    // ── Totals table + amount in words ────────────────────────────────────
    function _totalsSection(h, sym, fmt, primary, items) {
        var html = '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;">';
        html += '<tr>';
        // Left: Amount in words + tax summary
        html += '<td style="border:none;vertical-align:top;padding-right:12px;">';
        html += '<div style="font-size:8pt;font-weight:700;color:#555;margin-bottom:4px;">Amount in Words:</div>';
        html += '<div style="font-size:8.5pt;font-weight:600;color:#222;border:1px solid #ddd;background:#f9f9f9;padding:5px 8px;border-radius:2px;">' + _amountToWords(h.NetAmount) + '</div>';
        html += '</td>';
        // Right: numeric totals
        html += '<td style="border:none;vertical-align:top;width:240px;">';
        html += '<table style="width:100%;border-collapse:collapse;">';
        function totRow(lbl, val, bold, color) {
            return '<tr><td style="padding:3px 8px;font-size:8.5pt;color:#555;border:1px solid #e8e8e8;">' + lbl + '</td>'
                 + '<td style="padding:3px 8px;font-size:8.5pt;text-align:right;white-space:nowrap;border:1px solid #e8e8e8;' + (bold?'font-weight:700;':'') + (color?'color:'+color+';':'') + '">' + sym + ' ' + val + '</td></tr>';
        }
        html += totRow('Sub Total', fmt(h.SubTotal));
        if (parseFloat(h.DiscountAmount) > 0) html += totRow('Discount', '− ' + fmt(h.DiscountAmount), false, '#c00');
        if (parseFloat(h.CgstAmount)  > 0) html += totRow('CGST',     fmt(h.CgstAmount));
        if (parseFloat(h.SgstAmount)  > 0) html += totRow('SGST',     fmt(h.SgstAmount));
        if (parseFloat(h.IgstAmount)  > 0) html += totRow('IGST',     fmt(h.IgstAmount));
        if (parseFloat(h.AdditionalCharges) > 0) html += totRow('Additional Charges', fmt(h.AdditionalCharges));
        if (parseFloat(h.RoundOff||0) !== 0) html += totRow('Round Off', fmt(h.RoundOff));
        html += '<tr style="background:' + primary + ';color:#fff;">'
              + '<td style="padding:6px 8px;font-size:10pt;font-weight:700;border:1px solid ' + primary + ';">Grand Total</td>'
              + '<td style="padding:6px 8px;font-size:10pt;font-weight:700;text-align:right;white-space:nowrap;border:1px solid ' + primary + ';">' + sym + ' ' + fmt(h.NetAmount) + '</td></tr>';
        html += '</table>';
        html += '</td></tr></table>';
        return html;
    }

    // ── Notes / Terms block ───────────────────────────────────────────────
    function _notesTermsBlock(h) {
        if (!h.Notes && !h.TermsConditions) return '';
        var html = '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;margin-bottom:8px;"><tr>';
        if (h.Notes) {
            html += '<td style="padding:6px 10px;vertical-align:top;font-size:8.5pt;' + (h.TermsConditions ? 'width:50%;border-right:1px solid #ddd;' : '') + '">'
                  + '<div style="font-weight:700;margin-bottom:3px;color:#333;">Notes</div>'
                  + '<div style="color:#555;">' + _esc(h.Notes) + '</div></td>';
        }
        if (h.TermsConditions) {
            html += '<td style="padding:6px 10px;vertical-align:top;font-size:8.5pt;">'
                  + '<div style="font-weight:700;margin-bottom:3px;color:#333;">Terms &amp; Conditions</div>'
                  + '<div style="color:#555;">' + _esc(h.TermsConditions) + '</div></td>';
        }
        html += '</tr></table>';
        return html;
    }

    // ── Signature block ───────────────────────────────────────────────────
    function _signatureBlock(orgName, primary) {
        return '<table style="width:100%;border-collapse:collapse;margin-top:6px;"><tr>'
             + '<td style="border:none;vertical-align:bottom;font-size:8pt;color:#777;">This is a computer generated document.</td>'
             + '<td style="border:none;width:180px;text-align:center;vertical-align:bottom;">'
             + '<div style="border-top:1px solid #555;padding-top:4px;font-size:8pt;font-weight:600;color:#333;">Authorised Signatory</div>'
             + '<div style="font-size:7.5pt;color:#666;">For ' + _esc(orgName) + '</div>'
             + '</td></tr></table>';
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 1 — CLASSIC (Luxury-style: bordered, clean, traditional)
    // ══════════════════════════════════════════════════════════════════════
    function _themeClassic(resp, sym, fmt, thm) {
        var h       = resp.Header  || {};
        var org     = resp.OrgInfo || {};
        var primary = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent  = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo) !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN) !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '';

        // Outer border frame
        html += '<div style="border:2px solid ' + primary + ';padding:10px;">';

        // Top header: logo+org | document title
        html += '<table style="width:100%;border-collapse:collapse;margin-bottom:0;border-bottom:2px solid ' + accent + ';padding-bottom:8px;"><tr>';
        html += '<td style="border:none;vertical-align:top;">';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="48" height="48" alt="Logo" style="float:left;margin-right:10px;">';
        html += '<div style="font-size:14pt;font-weight:800;color:' + primary + ';">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showAddr) {
            if (org.Line1) html += '<div style="font-size:8pt;color:#444;">' + _esc(org.Line1) + (org.Line2 ? ', ' + _esc(org.Line2) : '') + '</div>';
            if (org.CityText) html += '<div style="font-size:8pt;color:#444;">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + (org.Pincode ? ' – ' + _esc(org.Pincode) : '') + '</div>';
            if (org.MobileNumber) html += '<div style="font-size:8pt;color:#444;">Ph: ' + _esc(org.MobileNumber) + (org.EmailAddress ? '  |  ' + _esc(org.EmailAddress) : '') + '</div>';
        }
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8pt;color:#444;">GSTIN: <strong>' + _esc(org.GSTIN) + '</strong></div>';
        html += '</td>';
        html += '<td style="border:none;text-align:right;vertical-align:top;min-width:180px;">';
        html += '<div style="font-size:18pt;font-weight:800;color:' + primary + ';letter-spacing:2px;border-bottom:2px solid ' + accent + ';display:inline-block;padding-bottom:2px;">QUOTATION</div>';
        html += '<table style="margin-left:auto;border-collapse:collapse;margin-top:6px;border:1px solid #ddd;">';
        if (h.UniqueNumber) html += '<tr><td style="padding:3px 8px;font-size:8pt;color:#777;border-bottom:1px solid #eee;">Quotation No.</td><td style="padding:3px 8px;font-size:8.5pt;font-weight:700;border-bottom:1px solid #eee;">' + _esc(h.UniqueNumber) + '</td></tr>';
        html += '<tr><td style="padding:3px 8px;font-size:8pt;color:#777;border-bottom:1px solid #eee;">Date</td><td style="padding:3px 8px;font-size:8.5pt;border-bottom:1px solid #eee;">' + _esc(h.TransDate) + '</td></tr>';
        if (h.ValidityDate) html += '<tr><td style="padding:3px 8px;font-size:8pt;color:#777;">Valid Until</td><td style="padding:3px 8px;font-size:8.5pt;">' + _esc(h.ValidityDate) + '</td></tr>';
        html += '</table></td></tr></table>';

        // Bill To
        html += '<table style="width:100%;border-collapse:collapse;margin:8px 0;"><tr>';
        html += '<td style="width:50%;vertical-align:top;border:1px solid #ddd;padding:0;">';
        html += '<div style="background:#f3f4f6;font-size:7.5pt;font-weight:700;padding:4px 8px;border-bottom:1px solid #ddd;color:' + primary + ';letter-spacing:.5px;text-transform:uppercase;">Bill To</div>';
        html += '<div style="padding:6px 8px;">';
        html += '<div style="font-size:11pt;font-weight:700;color:#222;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) html += '<div style="font-size:8pt;color:#555;">' + _esc(h.PartyCountryCode||'') + ' ' + _esc(h.PartyMobile) + '</div>';
        if (h.Reference) html += '<div style="font-size:8pt;color:#777;margin-top:3px;">Ref: ' + _esc(h.Reference) + '</div>';
        html += '</div></td>';
        html += '<td style="border:none;"></td></tr></table>';

        // Items table
        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');

        // Totals
        html += _totalsSection(h, sym, fmt, primary, resp.Items);
        html += _notesTermsBlock(h);
        html += _signatureBlock(org.BrandName || org.Name || '', primary);

        html += '</div>'; // end outer border frame
        html += '<div style="text-align:center;font-size:8pt;color:#888;margin-top:8px;border-top:2px solid ' + accent + ';padding-top:5px;">' + _esc(footer) + '</div>';
        return html;
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 2 — MODERN (Stylish: bold brand header, two-tone)
    // ══════════════════════════════════════════════════════════════════════
    function _themeModern(resp, sym, fmt, thm) {
        var h       = resp.Header  || {};
        var org     = resp.OrgInfo || {};
        var primary = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent  = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo) !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN) !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '';

        // Full-width primary header band
        html += '<div style="background:' + primary + ';padding:14px 16px;margin-bottom:0;">';
        html += '<div style="display:flex;justify-content:space-between;align-items:center;">';
        html += '<div style="display:flex;align-items:center;gap:12px;">';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="50" height="50" alt="Logo" style="background:#fff;border-radius:6px;padding:3px;">';
        html += '<div>';
        html += '<div style="font-size:17pt;font-weight:800;color:#fff;letter-spacing:.5px;">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showAddr && org.CityText) html += '<div style="font-size:8pt;color:rgba(255,255,255,.8);">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + '</div>';
        if (showAddr && org.MobileNumber) html += '<div style="font-size:8pt;color:rgba(255,255,255,.8);">Ph: ' + _esc(org.MobileNumber) + (org.EmailAddress ? ' | ' + _esc(org.EmailAddress) : '') + '</div>';
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8pt;color:rgba(255,255,255,.8);">GSTIN: ' + _esc(org.GSTIN) + '</div>';
        html += '</div></div>';
        html += '<div style="text-align:right;">';
        html += '<div style="font-size:22pt;font-weight:900;color:' + accent + ';letter-spacing:3px;">QUOTATION</div>';
        if (h.UniqueNumber) html += '<div style="font-size:10pt;font-weight:700;color:#fff;">' + _esc(h.UniqueNumber) + '</div>';
        html += '<div style="font-size:8pt;color:rgba(255,255,255,.8);">Date: ' + _esc(h.TransDate) + '</div>';
        if (h.ValidityDate) html += '<div style="font-size:8pt;color:rgba(255,255,255,.8);">Valid Until: ' + _esc(h.ValidityDate) + '</div>';
        html += '</div></div></div>';
        // Accent stripe
        html += '<div style="background:' + accent + ';height:4px;margin-bottom:10px;"></div>';

        // Address + Bill To row
        html += '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;"><tr>';
        if (showAddr && (org.Line1 || org.CityText)) {
            html += '<td style="border:none;width:50%;vertical-align:top;padding-right:10px;">';
            html += '<div style="font-size:7.5pt;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;">From</div>';
            if (org.Line1) html += '<div style="font-size:8.5pt;color:#444;">' + _esc(org.Line1) + (org.Line2 ? ', ' + _esc(org.Line2) : '') + '</div>';
            if (org.CityText) html += '<div style="font-size:8.5pt;color:#444;">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + (org.Pincode ? ' – ' + _esc(org.Pincode) : '') + '</div>';
            html += '</td>';
        }
        html += '<td style="border:none;vertical-align:top;">';
        html += '<div style="font-size:7.5pt;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;">Bill To</div>';
        html += '<div style="font-size:11pt;font-weight:700;color:#222;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) html += '<div style="font-size:8.5pt;color:#555;">' + _esc(h.PartyCountryCode||'') + ' ' + _esc(h.PartyMobile) + '</div>';
        if (h.Reference) html += '<div style="font-size:8pt;color:#888;margin-top:2px;">Ref: ' + _esc(h.Reference) + '</div>';
        html += '</td></tr></table>';

        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');
        html += _totalsSection(h, sym, fmt, primary, resp.Items);
        html += _notesTermsBlock(h);
        html += _signatureBlock(org.BrandName || org.Name || '', primary);
        html += '<div style="margin-top:10px;border-top:4px solid ' + accent + ';padding-top:6px;font-size:8pt;color:#666;text-align:center;">' + _esc(footer) + '</div>';
        return html;
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 3 — MINIMAL (Clean, spacious, PayPal-style)
    // ══════════════════════════════════════════════════════════════════════
    function _themeMinimal(resp, sym, fmt, thm) {
        var h       = resp.Header  || {};
        var org     = resp.OrgInfo || {};
        var primary = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent  = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo) !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN) !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '';

        // Minimal top bar (just a thin colored line)
        html += '<div style="border-top:3px solid ' + primary + ';padding-top:14px;">';

        // Header: logo+name left, doc info right
        html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">';
        html += '<div>';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="44" height="44" alt="Logo" style="display:block;margin-bottom:6px;">';
        html += '<div style="font-size:16pt;font-weight:800;color:' + primary + ';">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showAddr) {
            if (org.Line1) html += '<div style="font-size:8.5pt;color:#888;margin-top:2px;">' + _esc(org.Line1) + (org.Line2 ? ', ' + _esc(org.Line2) : '') + '</div>';
            if (org.CityText) html += '<div style="font-size:8.5pt;color:#888;">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + (org.Pincode ? ' ' + _esc(org.Pincode) : '') + '</div>';
            if (org.MobileNumber) html += '<div style="font-size:8.5pt;color:#888;">' + _esc(org.MobileNumber) + (org.EmailAddress ? '  •  ' + _esc(org.EmailAddress) : '') + '</div>';
        }
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8.5pt;color:#888;">GSTIN: ' + _esc(org.GSTIN) + '</div>';
        html += '</div>';
        html += '<div style="text-align:right;">';
        html += '<div style="font-size:22pt;font-weight:300;letter-spacing:4px;color:' + primary + ';text-transform:uppercase;">Quotation</div>';
        if (h.UniqueNumber) html += '<div style="font-size:10pt;color:#333;font-weight:600;margin-top:4px;">' + _esc(h.UniqueNumber) + '</div>';
        html += '<table style="margin-left:auto;margin-top:4px;border-collapse:collapse;">';
        html += '<tr><td style="font-size:8pt;color:#aaa;padding:2px 8px;border:none;text-align:right;">Date</td><td style="font-size:8.5pt;color:#333;padding:2px 0;border:none;">' + _esc(h.TransDate) + '</td></tr>';
        if (h.ValidityDate) html += '<tr><td style="font-size:8pt;color:#aaa;padding:2px 8px;border:none;text-align:right;">Valid Until</td><td style="font-size:8.5pt;color:#333;padding:2px 0;border:none;">' + _esc(h.ValidityDate) + '</td></tr>';
        html += '</table></div></div>';

        html += '<div style="border-bottom:1px solid #ddd;margin-bottom:12px;"></div>';

        // Bill To
        html += '<div style="margin-bottom:12px;">';
        html += '<div style="font-size:7.5pt;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:4px;">Bill To</div>';
        html += '<div style="font-size:11pt;font-weight:600;color:#222;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) html += '<div style="font-size:8.5pt;color:#888;">' + _esc(h.PartyCountryCode||'') + ' ' + _esc(h.PartyMobile) + '</div>';
        if (h.Reference) html += '<div style="font-size:8pt;color:#aaa;margin-top:2px;">Ref: ' + _esc(h.Reference) + '</div>';
        html += '</div>';

        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');
        html += _totalsSection(h, sym, fmt, primary, resp.Items);
        html += _notesTermsBlock(h);
        html += _signatureBlock(org.BrandName || org.Name || '', primary);

        html += '</div>';
        html += '<div style="border-top:1px solid #ddd;padding-top:8px;margin-top:10px;font-size:8pt;color:#aaa;text-align:center;letter-spacing:.5px;">' + _esc(footer) + '</div>';
        return html;
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 4 — BOLD (Flipkart-style: dark band + accent corner)
    // ══════════════════════════════════════════════════════════════════════
    function _themeBold(resp, sym, fmt, thm) {
        var h       = resp.Header  || {};
        var org     = resp.OrgInfo || {};
        var primary = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent  = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo) !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN) !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '';

        // Dark header: left (logo+org) + right (accent panel for doc title)
        html += '<div style="display:flex;margin-bottom:0;">';
        // Left dark band
        html += '<div style="background:' + primary + ';flex:1;padding:14px 16px;display:flex;align-items:center;gap:12px;">';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="50" height="50" alt="Logo" style="background:#fff;border-radius:6px;padding:3px;flex-shrink:0;">';
        html += '<div style="color:#fff;">';
        html += '<div style="font-size:16pt;font-weight:800;">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showAddr) {
            var addrLine = [org.Line1, org.CityText, org.StateText].filter(Boolean).join(', ');
            if (addrLine) html += '<div style="font-size:8pt;opacity:.8;margin-top:2px;">' + _esc(addrLine) + (org.Pincode ? ' – ' + _esc(org.Pincode) : '') + '</div>';
            if (org.MobileNumber) html += '<div style="font-size:8pt;opacity:.8;">Ph: ' + _esc(org.MobileNumber) + (org.EmailAddress ? ' | ' + _esc(org.EmailAddress) : '') + '</div>';
        }
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8pt;opacity:.8;">GSTIN: ' + _esc(org.GSTIN) + '</div>';
        html += '</div></div>';
        // Right accent panel
        html += '<div style="background:' + accent + ';padding:14px 18px;text-align:right;min-width:185px;display:flex;flex-direction:column;justify-content:center;">';
        html += '<div style="font-size:20pt;font-weight:900;color:#fff;letter-spacing:2px;line-height:1.1;">QUOTATION</div>';
        if (h.UniqueNumber) html += '<div style="font-size:10pt;font-weight:700;color:#fff;">' + _esc(h.UniqueNumber) + '</div>';
        html += '<div style="font-size:8pt;color:rgba(255,255,255,.9);margin-top:2px;">Date: ' + _esc(h.TransDate) + '</div>';
        if (h.ValidityDate) html += '<div style="font-size:8pt;color:rgba(255,255,255,.9);">Valid: ' + _esc(h.ValidityDate) + '</div>';
        html += '</div></div>';

        // Bill To strip below header
        html += '<div style="background:#f5f5f5;padding:7px 14px;border-bottom:3px solid ' + accent + ';margin-bottom:10px;display:flex;align-items:center;gap:16px;">';
        html += '<span style="font-size:7.5pt;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.8px;flex-shrink:0;">Bill To</span>';
        html += '<span style="font-size:11pt;font-weight:700;color:#222;">' + _esc(h.PartyName || '—') + '</span>';
        if (h.PartyMobile) html += '<span style="font-size:8.5pt;color:#666;">' + _esc(h.PartyCountryCode||'') + ' ' + _esc(h.PartyMobile) + '</span>';
        if (h.Reference) html += '<span style="font-size:8pt;color:#999;margin-left:auto;">Ref: ' + _esc(h.Reference) + '</span>';
        html += '</div>';

        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');
        html += _totalsSection(h, sym, fmt, primary, resp.Items);
        html += _notesTermsBlock(h);
        html += _signatureBlock(org.BrandName || org.Name || '', primary);
        html += '<div style="background:' + primary + ';color:#fff;text-align:center;padding:7px;font-size:8pt;margin-top:8px;">' + _esc(footer) + '</div>';
        return html;
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 5 — EXECUTIVE (Tally-style: formal, side accent bar, detailed)
    // ══════════════════════════════════════════════════════════════════════
    function _themeExecutive(resp, sym, fmt, thm) {
        var h       = resp.Header  || {};
        var org     = resp.OrgInfo || {};
        var primary = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent  = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo) !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN) !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '';

        // Outer wrapper with left accent bar
        html += '<div style="border-left:5px solid ' + primary + ';padding-left:14px;">';

        // Header
        html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid ' + primary + ';padding-bottom:10px;margin-bottom:10px;">';
        html += '<div style="display:flex;align-items:flex-start;gap:10px;">';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="52" height="52" alt="Logo">';
        html += '<div>';
        html += '<div style="font-size:15pt;font-weight:700;color:' + primary + ';border-bottom:2px solid ' + accent + ';display:inline-block;padding-bottom:2px;margin-bottom:4px;">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showAddr) {
            if (org.Line1) html += '<div style="font-size:8.5pt;color:#444;">' + _esc(org.Line1) + (org.Line2 ? ', ' + _esc(org.Line2) : '') + '</div>';
            if (org.CityText) html += '<div style="font-size:8.5pt;color:#444;">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + (org.Pincode ? ' – ' + _esc(org.Pincode) : '') + '</div>';
            if (org.MobileNumber) html += '<div style="font-size:8.5pt;color:#444;">Tel: ' + _esc(org.MobileNumber) + (org.EmailAddress ? '  |  ' + _esc(org.EmailAddress) : '') + '</div>';
        }
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8.5pt;color:#444;">GSTIN: <strong>' + _esc(org.GSTIN) + '</strong></div>';
        html += '</div></div>';
        html += '<div style="text-align:right;">';
        html += '<div style="font-size:18pt;font-weight:700;color:' + primary + ';font-variant:small-caps;letter-spacing:2px;">Quotation</div>';
        html += '<table style="margin-left:auto;border:1px solid #ddd;border-collapse:collapse;margin-top:4px;">';
        if (h.UniqueNumber) html += '<tr><td style="padding:3px 8px;font-size:8pt;color:#777;border-bottom:1px solid #eee;">Quotation No.</td><td style="padding:3px 8px;font-size:9pt;font-weight:700;border-bottom:1px solid #eee;">' + _esc(h.UniqueNumber) + '</td></tr>';
        html += '<tr><td style="padding:3px 8px;font-size:8pt;color:#777;border-bottom:1px solid #eee;">Date</td><td style="padding:3px 8px;font-size:9pt;border-bottom:1px solid #eee;">' + _esc(h.TransDate) + '</td></tr>';
        if (h.ValidityDate) html += '<tr><td style="padding:3px 8px;font-size:8pt;color:#777;">Valid Until</td><td style="padding:3px 8px;font-size:9pt;">' + _esc(h.ValidityDate) + '</td></tr>';
        html += '</table></div></div>';

        // Bill To box
        html += '<div style="border:1px solid #ddd;background:#fafafa;padding:8px 12px;margin-bottom:12px;display:inline-block;min-width:45%;">';
        html += '<div style="font-size:7.5pt;font-weight:700;color:' + primary + ';text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;">Bill To</div>';
        html += '<div style="font-size:11pt;font-weight:700;color:#222;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) html += '<div style="font-size:8.5pt;color:#555;">' + _esc(h.PartyCountryCode||'') + ' ' + _esc(h.PartyMobile) + '</div>';
        if (h.Reference) html += '<div style="font-size:8pt;color:#888;margin-top:2px;">Ref: ' + _esc(h.Reference) + '</div>';
        html += '</div>';

        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');
        html += _totalsSection(h, sym, fmt, primary, resp.Items);
        html += _notesTermsBlock(h);
        html += _signatureBlock(org.BrandName || org.Name || '', primary);

        html += '</div>'; // close accent bar wrapper
        html += '<div style="border-top:3px solid ' + accent + ';padding-top:6px;margin-top:10px;font-size:8pt;color:#666;text-align:center;">' + _esc(footer) + '</div>';
        return html;
    }

    // ── HSN/SAC tax summary table (used by swipe_formal) ─────────────────
    function _hsnTaxSummary(items, sym, fmt, primary) {
        var groups = {};
        $.each(items || [], function(i, it) {
            var hsn     = it.HSNSACCode || '-';
            var taxable = parseFloat(it.TaxableAmount || 0);
            var igst    = parseFloat(it.IgstAmount  || 0);
            var cgst    = parseFloat(it.CgstAmount  || 0);
            var sgst    = parseFloat(it.SgstAmount  || 0);
            var tax     = igst + cgst + sgst;
            var rate    = parseFloat(it.IgstRate || 0) || (parseFloat(it.CgstRate || 0) + parseFloat(it.SgstRate || 0));
            if (!groups[hsn]) groups[hsn] = { taxable: 0, tax: 0, rate: rate };
            groups[hsn].taxable += taxable;
            groups[hsn].tax     += tax;
        });
        var ths = 'padding:4px 6px;font-size:7.5pt;font-weight:700;background:' + primary + ';color:#fff;border:1px solid rgba(0,0,0,.1);';
        var td  = 'padding:4px 6px;font-size:8pt;border:1px solid #ddd;';
        var html = '<table style="width:100%;border-collapse:collapse;margin:0;">';
        html += '<thead><tr>'
              + '<th style="' + ths + '">HSN/SAC</th>'
              + '<th style="' + ths + 'text-align:right;">Taxable Value</th>'
              + '<th style="' + ths + 'text-align:center;">Rate</th>'
              + '<th style="' + ths + 'text-align:right;">Total Tax Amount</th>'
              + '</tr></thead><tbody>';
        var totTaxable = 0, totTax = 0;
        $.each(groups, function(hsn, g) {
            totTaxable += g.taxable; totTax += g.tax;
            html += '<tr>'
                  + '<td style="' + td + '">' + _esc(hsn) + '</td>'
                  + '<td style="' + td + 'text-align:right;">' + sym + ' ' + fmt(g.taxable) + '</td>'
                  + '<td style="' + td + 'text-align:center;">' + fmt(g.rate) + '%</td>'
                  + '<td style="' + td + 'text-align:right;">' + sym + ' ' + fmt(g.tax) + '</td>'
                  + '</tr>';
        });
        html += '<tr style="font-weight:700;background:#f5f5f5;">'
              + '<td style="' + td + '">TOTAL</td>'
              + '<td style="' + td + 'text-align:right;">' + sym + ' ' + fmt(totTaxable) + '</td>'
              + '<td style="' + td + '"></td>'
              + '<td style="' + td + 'text-align:right;">' + sym + ' ' + fmt(totTax) + '</td>'
              + '</tr>';
        html += '</tbody></table>';
        html += '<div style="text-align:right;padding:4px 8px;font-size:8pt;color:#27ae60;font-weight:700;">&#10003; Amount Paid</div>';
        return html;
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 6 — SWIPE CLEAN (Amazon/Swipe style: no border, 3-col customer, bank footer)
    // ══════════════════════════════════════════════════════════════════════
    function _themeSwipeClean(resp, sym, fmt, thm) {
        var h         = resp.Header  || {};
        var org       = resp.OrgInfo || {};
        var primary   = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent    = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo)      !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN)     !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '';

        // Header
        html += '<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:10px;">';
        html += '<div style="font-size:13pt;font-weight:700;color:' + primary + ';letter-spacing:.8px;">TAX INVOICE</div>';
        html += '<div style="font-size:8pt;color:#888;font-style:italic;">ORIGINAL FOR RECIPIENT</div>';
        html += '</div>';

        // Company + Logo
        html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #e0e0e0;padding-bottom:10px;margin-bottom:10px;">';
        html += '<div style="flex:1;">';
        html += '<div style="font-size:17pt;font-weight:800;color:#1a1a1a;margin-bottom:3px;">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">GSTIN ' + _esc(org.GSTIN) + '</div>';
        if (showAddr) {
            if (org.Line1) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">' + _esc(org.Line1) + (org.Line2 ? ', ' + _esc(org.Line2) : '') + '</div>';
            if (org.CityText) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + (org.Pincode ? ' - ' + _esc(org.Pincode) : '') + '</div>';
            if (org.MobileNumber) html += '<div style="font-size:8.5pt;color:#555;">Mobile ' + _esc(org.MobileNumber) + (org.EmailAddress ? ' &nbsp; Email ' + _esc(org.EmailAddress) : '') + '</div>';
        }
        html += '</div>';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="64" height="64" alt="Logo" style="border-radius:6px;margin-left:14px;">';
        html += '</div>';

        // Invoice meta row
        html += '<div style="display:flex;gap:28px;font-size:8.5pt;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #eee;">';
        html += '<div><span style="font-weight:600;color:#333;">Invoice #:</span> ' + _esc(h.UniqueNumber || '—') + '</div>';
        html += '<div><span style="font-weight:600;color:#333;">Invoice Date:</span> ' + _esc(h.TransDate || '') + '</div>';
        if (h.ValidityDate) html += '<div><span style="font-weight:600;color:#333;">Due Date:</span> ' + _esc(h.ValidityDate) + '</div>';
        html += '</div>';

        // Customer 3-column table
        html += '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;border:1px solid #e8e8e8;font-size:8.5pt;">';
        html += '<tr>';
        html += '<td style="width:33%;border-right:1px solid #e8e8e8;vertical-align:top;padding:7px 9px;">';
        html += '<div style="font-weight:700;font-size:7.5pt;color:#888;margin-bottom:4px;">Customer Details:</div>';
        html += '<div style="font-weight:700;font-size:10pt;margin-bottom:2px;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) html += '<div style="color:#555;">Ph: ' + _esc(h.PartyCountryCode || '') + ' ' + _esc(h.PartyMobile) + '</div>';
        html += '</td>';
        html += '<td style="width:33%;border-right:1px solid #e8e8e8;vertical-align:top;padding:7px 9px;">';
        html += '<div style="font-weight:700;font-size:7.5pt;color:#888;margin-bottom:4px;">Billing address:</div>';
        html += '<div style="color:#444;line-height:1.5;">' + _esc(h.BillingAddress || h.PartyName || '—') + '</div>';
        html += '</td>';
        html += '<td style="vertical-align:top;padding:7px 9px;">';
        html += '<div style="font-weight:700;font-size:7.5pt;color:#888;margin-bottom:4px;">Shipping address:</div>';
        html += '<div style="color:#444;line-height:1.5;">' + _esc(h.ShippingAddress || h.BillingAddress || h.PartyName || '—') + '</div>';
        html += '</td></tr></table>';

        if (h.PlaceOfSupply) html += '<div style="font-size:8.5pt;margin-bottom:10px;"><strong>Place of Supply:</strong> ' + _esc(h.PlaceOfSupply) + '</div>';

        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');
        html += _totalsSection(h, sym, fmt, primary, resp.Items);

        // Amount in words
        html += '<div style="font-size:8pt;color:#555;margin-bottom:12px;">';
        html += '<strong>Total amount (in words):</strong> ' + _amountToWords(parseFloat(h.GrandTotal || 0));
        html += '</div>';

        // Bank / UPI / Signature 3-column
        html += '<table style="width:100%;border-collapse:collapse;margin-top:10px;border-top:1px solid #ddd;font-size:8pt;">';
        html += '<tr>';
        html += '<td style="width:33%;border-right:1px solid #eee;vertical-align:top;padding:8px 6px 6px 0;">';
        html += '<div style="font-weight:700;margin-bottom:6px;">Pay using UPI:</div>';
        if (org.UPIId) html += '<div style="color:#555;">' + _esc(org.UPIId) + '</div>';
        html += '</td>';
        html += '<td style="width:33%;border-right:1px solid #eee;vertical-align:top;padding:8px;">';
        html += '<div style="font-weight:700;margin-bottom:6px;">Bank Details:</div>';
        if (org.BankName)    html += '<div><span style="color:#888;">Bank:</span> '       + _esc(org.BankName)    + '</div>';
        if (org.AccountNo)   html += '<div><span style="color:#888;">Account #:</span> ' + _esc(org.AccountNo)   + '</div>';
        if (org.IFSCCode)    html += '<div><span style="color:#888;">IFSC:</span> '      + _esc(org.IFSCCode)    + '</div>';
        if (org.BranchName)  html += '<div><span style="color:#888;">Branch:</span> '   + _esc(org.BranchName)  + '</div>';
        html += '</td>';
        html += '<td style="vertical-align:bottom;padding:8px 0 6px 8px;text-align:right;">';
        html += '<div style="font-size:8pt;color:#666;margin-bottom:28px;">For ' + _esc(org.BrandName || org.Name || '') + '</div>';
        html += '<div style="display:inline-block;border-top:1px solid #555;padding-top:4px;font-size:8.5pt;font-weight:600;text-align:center;min-width:130px;">Authorised Signatory</div>';
        html += '</td></tr></table>';

        html += _notesTermsBlock(h);
        html += '<div style="margin-top:10px;border-top:2px solid ' + accent + ';padding-top:6px;text-align:center;font-size:8pt;color:#888;">' + _esc(footer) + '</div>';
        html += '<div style="margin-top:4px;font-size:7.5pt;color:#aaa;text-align:center;">This is a computer generated document. &nbsp; Page 1 / 1</div>';
        return html;
    }

    // ══════════════════════════════════════════════════════════════════════
    // THEME 7 — SWIPE FORMAL (Tata/Swipe style: outer border, header band, HSN tax summary)
    // ══════════════════════════════════════════════════════════════════════
    function _themeSwipeFormal(resp, sym, fmt, thm) {
        var h         = resp.Header  || {};
        var org       = resp.OrgInfo || {};
        var primary   = (thm && thm.PrimaryColor) ? thm.PrimaryColor : '#1a3c6e';
        var accent    = (thm && thm.AccentColor)  ? thm.AccentColor  : '#f59e0b';
        var showLogo  = !thm || parseInt(thm.ShowLogo)      !== 0;
        var showAddr  = !thm || parseInt(thm.ShowOrgAddress) !== 0;
        var showGSTIN = !thm || parseInt(thm.ShowGSTIN)     !== 0;
        var footer    = (thm && thm.FooterText) ? thm.FooterText : 'Thank you for your business!';
        var html = '<div style="border:1.5px solid #bbb;">';

        // Header band
        html += '<div style="background:#f8f9fa;border-bottom:1px solid #ccc;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;">';
        html += '<div style="font-size:13pt;font-weight:700;letter-spacing:1.5px;color:#222;">TAX INVOICE</div>';
        html += '<div style="font-size:8pt;color:#888;font-style:italic;">ORIGINAL FOR RECIPIENT</div>';
        html += '</div>';

        // Company (left) + Invoice meta (right)
        html += '<table style="width:100%;border-collapse:collapse;border-bottom:1px solid #ccc;">';
        html += '<tr>';
        html += '<td style="width:55%;border-right:1px solid #ccc;vertical-align:top;padding:10px 12px;">';
        html += '<div style="display:flex;align-items:flex-start;gap:10px;">';
        if (showLogo) html += '<img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="52" height="52" alt="Logo" style="border-radius:4px;flex-shrink:0;">';
        html += '<div>';
        html += '<div style="font-size:14pt;font-weight:800;color:#1a1a1a;margin-bottom:3px;">' + _esc(org.BrandName || org.Name || '') + '</div>';
        if (showGSTIN && org.GSTIN) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">GSTIN ' + _esc(org.GSTIN) + '</div>';
        if (showAddr) {
            if (org.Line1) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">' + _esc(org.Line1) + (org.Line2 ? ', ' + _esc(org.Line2) : '') + '</div>';
            if (org.CityText) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">' + _esc(org.CityText) + (org.StateText ? ', ' + _esc(org.StateText) : '') + (org.Pincode ? ' - ' + _esc(org.Pincode) : '') + '</div>';
            if (org.MobileNumber) html += '<div style="font-size:8.5pt;color:#555;margin-bottom:1px;">Mobile ' + _esc(org.MobileNumber) + '</div>';
            if (org.EmailAddress) html += '<div style="font-size:8.5pt;color:#555;">Email ' + _esc(org.EmailAddress) + '</div>';
        }
        html += '</div></div></td>';
        // Invoice meta
        html += '<td style="vertical-align:top;padding:10px 12px;">';
        html += '<table style="width:100%;border-collapse:collapse;font-size:8.5pt;">';
        html += '<tr><td style="color:#888;padding:3px 6px 3px 0;border-bottom:1px solid #eee;">Invoice #:</td><td style="font-weight:700;padding:3px 0;border-bottom:1px solid #eee;">' + _esc(h.UniqueNumber || '—') + '</td></tr>';
        html += '<tr><td style="color:#888;padding:3px 6px 3px 0;border-bottom:1px solid #eee;">Invoice Date:</td><td style="padding:3px 0;border-bottom:1px solid #eee;">' + _esc(h.TransDate || '') + '</td></tr>';
        if (h.PlaceOfSupply) html += '<tr><td style="color:#888;padding:3px 6px 3px 0;border-bottom:1px solid #eee;">Place of Supply:</td><td style="font-weight:700;color:' + primary + ';padding:3px 0;border-bottom:1px solid #eee;">' + _esc(h.PlaceOfSupply) + '</td></tr>';
        if (h.ValidityDate) html += '<tr><td style="color:#888;padding:3px 6px 3px 0;">Due Date:</td><td style="padding:3px 0;">' + _esc(h.ValidityDate) + '</td></tr>';
        html += '</table></td></tr></table>';

        // Customer (left) + Shipping (right)
        html += '<table style="width:100%;border-collapse:collapse;border-bottom:1px solid #ccc;font-size:8.5pt;">';
        html += '<tr>';
        html += '<td style="width:50%;border-right:1px solid #ccc;vertical-align:top;padding:8px 12px;">';
        html += '<div style="font-weight:700;font-size:7.5pt;color:#888;margin-bottom:4px;">Customer Details:</div>';
        html += '<div style="font-weight:700;font-size:10pt;margin-bottom:2px;">' + _esc(h.PartyName || '—') + '</div>';
        if (h.PartyMobile) html += '<div style="color:#555;margin-bottom:6px;">Ph: ' + _esc(h.PartyCountryCode || '') + ' ' + _esc(h.PartyMobile) + '</div>';
        html += '<div style="font-weight:700;font-size:7.5pt;color:#888;margin-bottom:3px;">Billing address:</div>';
        html += '<div style="color:#444;line-height:1.5;">' + _esc(h.BillingAddress || h.PartyName || '—') + '</div>';
        html += '</td>';
        html += '<td style="vertical-align:top;padding:8px 12px;">';
        html += '<div style="font-weight:700;font-size:7.5pt;color:#888;margin-bottom:4px;">Shipping address:</div>';
        html += '<div style="color:#444;line-height:1.5;">' + _esc(h.ShippingAddress || h.BillingAddress || h.PartyName || '—') + '</div>';
        html += '</td></tr></table>';

        html += _gstItemsTable(resp.Items, sym, fmt, thm, primary, '#fff');
        html += _totalsSection(h, sym, fmt, primary, resp.Items);

        // Amount in words
        html += '<div style="border-bottom:1px solid #ccc;padding:6px 12px;font-size:8.5pt;color:#555;">';
        html += '<strong>Total amount (in words):</strong> ' + _amountToWords(parseFloat(h.GrandTotal || 0));
        html += '</div>';

        // HSN/SAC tax summary
        html += '<div style="border-bottom:1px solid #ccc;">' + _hsnTaxSummary(resp.Items, sym, fmt, primary) + '</div>';

        // Bank / UPI / Signature
        html += '<table style="width:100%;border-collapse:collapse;font-size:8pt;">';
        html += '<tr>';
        html += '<td style="width:34%;border-right:1px solid #ccc;vertical-align:top;padding:8px 12px;">';
        html += '<div style="font-weight:700;margin-bottom:6px;">Bank Details:</div>';
        if (org.BankName)    html += '<div><span style="color:#888;">Bank:</span> '        + _esc(org.BankName)   + '</div>';
        if (org.AccountNo)   html += '<div><span style="color:#888;">Account #:</span> '  + _esc(org.AccountNo)  + '</div>';
        if (org.IFSCCode)    html += '<div><span style="color:#888;">IFSC:</span> '       + _esc(org.IFSCCode)   + '</div>';
        if (org.BranchName)  html += '<div><span style="color:#888;">Branch:</span> '    + _esc(org.BranchName) + '</div>';
        html += '</td>';
        html += '<td style="width:33%;border-right:1px solid #ccc;vertical-align:top;padding:8px 12px;text-align:center;">';
        html += '<div style="font-weight:700;margin-bottom:6px;">Pay using UPI:</div>';
        if (org.UPIId) html += '<div style="font-size:8.5pt;color:#555;">' + _esc(org.UPIId) + '</div>';
        html += '</td>';
        html += '<td style="vertical-align:bottom;padding:8px 12px;text-align:right;">';
        html += '<div style="font-size:8pt;color:#666;margin-bottom:28px;">For ' + _esc(org.BrandName || org.Name || '') + '</div>';
        html += '<div style="display:inline-block;border-top:1px solid #555;padding-top:4px;font-size:8.5pt;font-weight:600;text-align:center;min-width:130px;">Authorised Signatory</div>';
        html += '<div style="font-size:7.5pt;color:#888;margin-top:2px;">This is a computer generated document.</div>';
        html += '</td></tr></table>';

        html += _notesTermsBlock(h);
        html += '</div>'; // close outer border
        html += '<div style="margin-top:8px;text-align:center;font-size:8pt;color:#888;">' + _esc(footer) + ' &nbsp;|&nbsp; Page 1 / 1</div>';
        return html;
    }

    // ── Token-template renderer (DB-driven HTML with {{PLACEHOLDER}} tokens) ─
    function _renderTokenTemplate(html, resp, sym, fmt, thm) {
        var h    = resp.Header  || {};
        var org  = resp.OrgInfo || {};
        var items = resp.Items  || [];

        // Build items table rows
        var itemRows = '';
        $.each(items, function (i, it) {
            itemRows += '<tr>'
                + '<td style="padding:4px 6px;">' + (i + 1) + '</td>'
                + '<td style="padding:4px 6px;">' + (it.ItemName || '') + (it.HSNSACCode ? '<br><small style="color:#888;">HSN: ' + it.HSNSACCode + '</small>' : '') + '</td>'
                + '<td style="padding:4px 6px;text-align:center;">' + fmt(it.Quantity) + ' ' + (it.UnitName || '') + '</td>'
                + '<td style="padding:4px 6px;text-align:right;">' + sym + ' ' + fmt(it.UnitPrice) + '</td>'
                + '<td style="padding:4px 6px;text-align:right;">' + fmt(it.TaxPercentage || 0) + '%</td>'
                + '<td style="padding:4px 6px;text-align:right;">' + sym + ' ' + fmt(it.TotalAmount) + '</td>'
                + '</tr>';
        });
        var itemsTable = '<table style="width:100%;border-collapse:collapse;font-size:inherit;">'
            + '<thead><tr style="background:' + (thm.PrimaryColor || '#1a3c6e') + ';color:#fff;">'
            + '<th style="padding:5px 6px;text-align:left;">#</th>'
            + '<th style="padding:5px 6px;text-align:left;">Item</th>'
            + '<th style="padding:5px 6px;text-align:center;">Qty</th>'
            + '<th style="padding:5px 6px;text-align:right;">Rate</th>'
            + '<th style="padding:5px 6px;text-align:right;">Tax</th>'
            + '<th style="padding:5px 6px;text-align:right;">Amount</th>'
            + '</tr></thead><tbody>' + itemRows + '</tbody></table>';

        // Format org address lines
        var addrParts = [org.Address1, org.Address2, org.City, org.State, org.PinCode].filter(Boolean);
        var orgAddress = addrParts.join(', ');
        var custAddrParts = [h.BillingAddress1, h.BillingAddress2, h.BillingCity, h.BillingState, h.BillingPinCode].filter(Boolean);
        var custAddress = custAddrParts.join(', ');

        // Org logo HTML
        var orgLogoHtml = (org.LogoUrl && thm.ShowLogo == 1)
            ? '<img src="' + org.LogoUrl + '" style="max-height:60px;max-width:160px;" alt="Logo">'
            : '';

        var subTotal   = fmt(h.SubTotal   || 0);
        var taxTotal   = fmt(h.TaxTotal   || 0);
        var grandTotal = fmt(h.GrandTotal || 0);

        var map = {
            '{{ORG_NAME}}':          org.OrgName        || '',
            '{{ORG_ADDRESS}}':       orgAddress,
            '{{ORG_GSTIN}}':         (thm.ShowGSTIN == 1 ? (org.GSTIN || '') : ''),
            '{{ORG_LOGO}}':          orgLogoHtml,
            '{{DOC_NUMBER}}':        h.DocNumber         || '',
            '{{DOC_DATE}}':          h.DocDate           || '',
            '{{DUE_DATE}}':          h.DueDate           || '',
            '{{CUSTOMER_NAME}}':     h.CustomerName      || '',
            '{{CUSTOMER_ADDRESS}}':  custAddress,
            '{{ITEMS_TABLE}}':       itemsTable,
            '{{SUBTOTAL}}':          sym + ' ' + subTotal,
            '{{TAX_TOTAL}}':         sym + ' ' + taxTotal,
            '{{GRAND_TOTAL}}':       sym + ' ' + grandTotal,
            '{{FOOTER_TEXT}}':       thm.FooterText      || '',
            '{{PRIMARY_COLOR}}':     thm.PrimaryColor    || '#1a3c6e',
            '{{ACCENT_COLOR}}':      thm.AccentColor     || '#f59e0b',
            '{{FONT_FAMILY}}':       thm.FontFamily      || 'Arial',
            '{{FONT_SIZE}}':         (parseInt(thm.FontSizePx) || 11) + 'px',
            '{{CURRENCY_SYMBOL}}':   sym,
        };

        var result = html;
        $.each(map, function (token, value) {
            result = result.split(token).join(value);
        });
        return result;
    }

    // ── Main dispatcher ───────────────────────────────────────────────────
    function _buildA4Html(resp, size) {
        var sym = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
        var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
        function fmt(v) { return parseFloat(v || 0).toFixed(dec); }
        var thm        = resp.PrintTheme || {};
        var key        = (thm.ThemeKey || 'classic').toLowerCase();
        var fontFamily = thm.FontFamily || 'Arial';
        var fontSizePx = parseInt(thm.FontSizePx) || 11;

        // If a DB template HTML exists, use token-replacement renderer
        if (thm.TemplateHtmlContent && thm.TemplateHtmlContent.trim().length > 0) {
            return _renderTokenTemplate(thm.TemplateHtmlContent, resp, sym, fmt, thm);
        }

        // Fallback: built-in JS renderers
        var inner;
        switch (key) {
            case 'modern':       inner = _themeModern(resp,      sym, fmt, thm); break;
            case 'minimal':      inner = _themeMinimal(resp,     sym, fmt, thm); break;
            case 'bold':         inner = _themeBold(resp,        sym, fmt, thm); break;
            case 'executive':    inner = _themeExecutive(resp,   sym, fmt, thm); break;
            case 'swipe_clean':  inner = _themeSwipeClean(resp,  sym, fmt, thm); break;
            case 'swipe_formal': inner = _themeSwipeFormal(resp, sym, fmt, thm); break;
            default:             inner = _themeClassic(resp,     sym, fmt, thm); break;
        }
        return '<div style="font-family:\'' + fontFamily + '\',Arial,Helvetica,sans-serif; font-size:' + fontSizePx + 'px;">' + inner + '</div>';
    }

});
</script>