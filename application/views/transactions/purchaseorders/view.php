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
                            <ul class="nav nav-pills gap-1" id="poStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 active po-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="badge bg-info ms-1 po-tab-count"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 po-status-tab" data-status="Received" href="javascript:void(0);">
                                        Received <span class="badge bg-info ms-1 po-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 po-status-tab" data-status="Closed" href="javascript:void(0);">
                                        Closed <span class="badge bg-info ms-1 po-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 po-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-info ms-1 po-tab-count d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 po-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="badge bg-info ms-1 po-tab-count d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Search + Date filter + Create -->
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn pageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                <div class="input-group input-group-sm" style="width:220px">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="searchTransactionData" placeholder="Search..." title="PO Number or Vendor Name">
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:220px;max-height:320px;overflow-y:auto">
                                        <li><a class="dropdown-item date-option" data-range=""><i class="bx bx-list-ul me-2"></i>All Dates</a></li>
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

                                <a href="/purchaseorders/create" class="btn btn-primary btn-sm px-3">Create</a>
                            </div>
                        </div>

                        <!-- ── Table ───────────────────────────────── -->
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover MainviewTable mb-0" id="poTable">
                                <thead class="bg-body-tertiary">
                                    <tr>
                                        <th class="table-checkbox" style="width:40px">
                                            <div class="form-check">
                                                <input class="form-check-input table-chkbox poHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            # PO <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Vendor</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            PO Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Expected Date</th>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center poPagination" id="poPagination">
                            <?php echo $ModPagination ? $ModPagination : ''; ?>
                        </div>

                    </div>

                    <!-- ── A4 Print Modal ────────────────────────── -->
                    <div class="modal fade" id="a4PrintModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg" style="border-radius:12px;overflow:hidden;">
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="background:#fff;">
                                    <div class="fw-semibold text-dark" style="font-size:.92rem;"><i class="bx bx-file-blank text-primary me-1"></i>Purchase Order Preview</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-check-inline mb-0 me-1">
                                            <input class="form-check-input" type="radio" name="a4PaperSize" id="psA4" value="A4" checked>
                                            <label class="form-check-label small fw-semibold" for="psA4">A4</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0 me-2">
                                            <input class="form-check-input" type="radio" name="a4PaperSize" id="psA5" value="A5">
                                            <label class="form-check-label small fw-semibold" for="psA5">A5</label>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" id="a4DownloadBtn" title="Download PDF">
                                            <i class="bx bx-download"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success px-3 py-1" id="a4PrintBtn">
                                            <i class="bx bx-printer me-1"></i>Print
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger px-3 py-1" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <div id="a4PrintPreview"
                                     style="background:#404040;overflow-y:auto;overflow-x:auto;
                                            height:82vh;display:flex;align-items:flex-start;
                                            justify-content:center;padding:24px 16px;">
                                    <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                        <div class="spinner-border text-light" role="status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── View PO Modal ─────────────────────────── -->
                    <div class="modal fade" id="viewPOModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header modal-header-border-bottom p-3 d-flex justify-content-between align-items-center">
                                    <h6 class="modal-title fw-semibold fs-6 text-primary mb-0" id="viewPOModalTitle">Purchase Order Details</h6>
                                    <div class="gap-2">
                                        <a href="javascript:void(0);" id="viewPOEditBtn" class="btn btn-warning btn-sm me-2">
                                            <i class="bx bx-edit me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <div class="modal-body p-0" id="viewPOModalBody">
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

<script src="/js/transactions/purchaseorders.js"></script>

<script>
const  ModuleId     = 104;
const  ModuleTable  = '#poTable';
const  ModulePag    = '.poPagination';
const  ModuleHeader = '.poHeaderCheck';
const  ModuleRow    = '.poCheck';

$(function () {
    'use strict'

    Filter['Status'] = 'All';

    // ── Status tabs ─────────────────────────────────────
    $(document).on('click', '.po-status-tab', function (e) {
        e.preventDefault();
        $('.po-status-tab').removeClass('active');
        $(this).addClass('active');
        Filter.Status = $(this).data('status') || 'All';
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // Search
    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchaseOrdersDetails();
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
        getPurchaseOrdersDetails();
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
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        var icon = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // Pagination
    $(document).on('click', '.poPagination .page-link', function (e) {
        e.preventDefault();
        var href  = $(this).attr('href') || '';
        var match = href.match(/\/(\d+)$/);
        if (match) {
            PageNo = parseInt(match[1]);
            getPurchaseOrdersDetails();
        }
    });

    // ── Inline status update ────────────────────────────
    $(document).on('click', '.po-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        $.ajax({
            url   : '/purchaseorders/updatePurchaseOrderStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    getPurchaseOrdersDetails();
                }
            }
        });
    });

    // ── View modal ──────────────────────────────────────
    $(document).on('click', '.viewPO', function () {
        var uid = $(this).data('uid');
        $('#viewPOModal').modal('show');
        $('#viewPOModalBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewPOEditBtn').attr('href', '/purchaseorders/edit/' + uid);
        $.ajax({
            url   : '/purchaseorders/getPurchaseOrderDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#viewPOModalBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                } else {
                    $('#viewPOModalTitle').text('Purchase Order — ' + (resp.Header.UniqueNumber || 'Details'));
                    $('#viewPOModalBody').html(_buildPODetailHtml(resp));
                }
            },
            error: function () {
                $('#viewPOModalBody').html('<div class="alert alert-danger m-3">Failed to load purchase order.</div>');
            }
        });
    });

    // ── A4 Print ─────────────────────────────────────────
    $(document).on('click', '.a4PrintPO', function () {
        var uid = $(this).data('uid');
        $('#a4PrintModal').modal('show');
        $('#a4PrintPreview').html('<div class="d-flex justify-content-center align-items-center w-100 h-100"><div class="spinner-border text-light"></div></div>');
        $.ajax({
            url   : '/purchaseorders/getPurchaseOrderDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#a4PrintPreview').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                } else {
                    var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
                    window._poLastPrintData = resp;
                    $('#a4PrintPreview').html(_buildA4Html(resp, size));
                }
            }
        });
    });

    $('input[name="a4PaperSize"]').on('change', function () {
        if (!window._poLastPrintData) return;
        var size = $(this).val();
        $('#a4PrintPreview').html(_buildA4Html(window._poLastPrintData, size));
    });

    $('#a4PrintBtn').on('click', function () {
        var frame = document.getElementById('a4PrintFrame');
        if (!frame) {
            frame = document.createElement('iframe');
            frame.id = 'a4PrintFrame';
            frame.style.display = 'none';
            document.body.appendChild(frame);
        }
        var size    = $('input[name="a4PaperSize"]:checked').val() || 'A4';
        var content = _buildA4Html(window._poLastPrintData, size, true);
        frame.contentDocument.open();
        frame.contentDocument.write(content);
        frame.contentDocument.close();
        frame.onload = function () { frame.contentWindow.print(); };
    });

    // ── Delete ───────────────────────────────────────────
    $(document).on('click', '.deletePO', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title            : 'Delete Purchase Order?',
            html             : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : '/purchaseorders/deletePurchaseOrder',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getPurchaseOrdersDetails();
                        Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false });
                    }
                }
            });
        });
    });

    // ── Duplicate ────────────────────────────────────────
    $(document).on('click', '.duplicatePO', function () {
        var uid = $(this).data('uid');
        Swal.fire({
            title            : 'Duplicate Purchase Order?',
            text             : 'A new draft copy will be created.',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Duplicate',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : '/purchaseorders/duplicatePurchaseOrder',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getPurchaseOrdersDetails();
                        Swal.fire({
                            icon : 'success',
                            text : resp.Message,
                            showCancelButton : true,
                            confirmButtonText: 'Edit Now',
                            cancelButtonText : 'Stay Here',
                        }).then(function (r) {
                            if (r.isConfirmed && resp.EditURL) {
                                window.location.href = resp.EditURL;
                            }
                        });
                    }
                }
            });
        });
    });

});

// ── Detail view HTML builder ──────────────────────────────
function _buildPODetailHtml(resp) {
    window._poLastPrintData = resp;
    var h   = resp.Header || {};
    var org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ';
    var dec = h.DecimalPoints || 2;
    var rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr>' +
            '<td class="text-center">' + (i + 1) + '</td>' +
            '<td>' + _esc(item.ProductName) + (item.PartNumber ? '<br><small class="text-muted">' + _esc(item.PartNumber) + '</small>' : '') + '</td>' +
            '<td class="text-center">' + _esc(item.Quantity) + ' ' + _esc(item.PrimaryUnitName) + '</td>' +
            '<td class="text-end">' + cur + _esc(parseFloat(item.UnitPrice).toFixed(dec)) + '</td>' +
            '<td class="text-end">' + cur + _esc(parseFloat(item.NetAmount).toFixed(dec)) + '</td>' +
            '</tr>';
    });
    return '<div class="p-3">' +
        '<div class="row mb-3">' +
            '<div class="col-md-6"><strong>' + _esc(org.OrgName || '') + '</strong><br>' +
                '<small class="text-muted">' + _esc(h.UniqueNumber || '—') + ' &nbsp;|&nbsp; ' + _esc(h.TransDate || '') + '</small></div>' +
            '<div class="col-md-6 text-end">' +
                '<strong>Vendor:</strong> ' + _esc(h.PartyName || '—') +
                (h.ValidityDate ? '<br><small class="text-muted">Expected: ' + _esc(h.ValidityDate) + '</small>' : '') +
            '</div>' +
        '</div>' +
        '<table class="table table-bordered table-sm">' +
            '<thead class="table-light"><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Net Amount</th></tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '<tfoot class="table-light">' +
                '<tr><td colspan="4" class="text-end fw-semibold">Sub Total</td><td class="text-end">' + cur + parseFloat(h.SubTotal || 0).toFixed(dec) + '</td></tr>' +
                (parseFloat(h.DiscountAmount) > 0 ? '<tr><td colspan="4" class="text-end text-danger">Discount</td><td class="text-end text-danger">- ' + cur + parseFloat(h.DiscountAmount).toFixed(dec) + '</td></tr>' : '') +
                (parseFloat(h.TaxAmount) > 0 ? '<tr><td colspan="4" class="text-end">Tax</td><td class="text-end">' + cur + parseFloat(h.TaxAmount).toFixed(dec) + '</td></tr>' : '') +
                '<tr><td colspan="4" class="text-end fw-bold">Net Amount</td><td class="text-end fw-bold">' + cur + parseFloat(h.NetAmount || 0).toFixed(dec) + '</td></tr>' +
            '</tfoot>' +
        '</table>' +
        (h.Notes ? '<p class="small text-muted mt-2"><strong>Notes:</strong> ' + _esc(h.Notes) + '</p>' : '') +
        (h.TermsConditions ? '<p class="small text-muted"><strong>Terms:</strong> ' + _esc(h.TermsConditions) + '</p>' : '') +
    '</div>';
}

function _buildA4Html(resp, size, forPrint) {
    if (!resp) return '';
    window._poLastPrintData = resp;
    var w   = size === 'A5' ? '148mm' : '210mm';
    var h   = resp.Header || {};
    var org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ';
    var dec = 2;
    var rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr>' +
            '<td style="text-align:center">' + (i + 1) + '</td>' +
            '<td>' + _esc(item.ProductName) + (item.PartNumber ? '<br><span style="font-size:.8em;color:#888">' + _esc(item.PartNumber) + '</span>' : '') + '</td>' +
            '<td style="text-align:center">' + _esc(item.Quantity) + ' ' + (_esc(item.PrimaryUnitName) || '') + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.UnitPrice || 0).toFixed(dec) + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.NetAmount || 0).toFixed(dec) + '</td>' +
            '</tr>';
    });
    var printStyles = forPrint ? '@media print { body { margin: 0; } .page { box-shadow: none; } }' : '';
    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
        '<style>body{font-family:Arial,sans-serif;font-size:12px;background:#404040}' +
        '.page{width:' + w + ';background:#fff;margin:0 auto;padding:20px;box-shadow:0 0 20px rgba(0,0,0,.5)}' +
        'table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;font-size:11px}' +
        'th{background:#f5f5f5;font-weight:bold}' + printStyles + '</style></head>' +
        '<body><div class="page">' +
        '<div style="display:flex;justify-content:space-between;margin-bottom:12px">' +
            '<div><strong style="font-size:14px">' + _esc(org.OrgName || '') + '</strong>' +
            (org.Address ? '<br><span style="color:#666">' + _esc(org.Address) + '</span>' : '') +
            (org.GSTNumber ? '<br><span style="color:#666">GSTIN: ' + _esc(org.GSTNumber) + '</span>' : '') + '</div>' +
            '<div style="text-align:right"><strong style="font-size:16px">PURCHASE ORDER</strong><br>' +
            '<span style="color:#666">' + _esc(h.UniqueNumber || '—') + '</span><br>' +
            '<span style="color:#666">Date: ' + _esc(h.TransDate || '') + '</span>' +
            (h.ValidityDate ? '<br><span style="color:#666">Expected: ' + _esc(h.ValidityDate) + '</span>' : '') +
            '</div>' +
        '</div>' +
        '<div style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:12px">' +
            '<strong>Vendor:</strong> ' + _esc(h.PartyName || '—') +
            (h.Reference ? ' &nbsp;|&nbsp; <strong>Ref:</strong> ' + _esc(h.Reference) : '') +
        '</div>' +
        '<table><thead><tr><th style="width:30px">#</th><th>Product</th><th style="width:60px;text-align:center">Qty</th><th style="width:90px;text-align:right">Unit Price</th><th style="width:90px;text-align:right">Net Amount</th></tr></thead>' +
        '<tbody>' + rows + '</tbody>' +
        '<tfoot>' +
            '<tr><td colspan="4" style="text-align:right;font-weight:bold">Sub Total</td><td style="text-align:right">' + cur + parseFloat(h.SubTotal || 0).toFixed(dec) + '</td></tr>' +
            (parseFloat(h.DiscountAmount) > 0 ? '<tr><td colspan="4" style="text-align:right;color:#c00">Discount</td><td style="text-align:right;color:#c00">- ' + cur + parseFloat(h.DiscountAmount).toFixed(dec) + '</td></tr>' : '') +
            (parseFloat(h.TaxAmount) > 0 ? '<tr><td colspan="4" style="text-align:right">Tax</td><td style="text-align:right">' + cur + parseFloat(h.TaxAmount).toFixed(dec) + '</td></tr>' : '') +
            '<tr><td colspan="4" style="text-align:right;font-weight:bold">Net Amount</td><td style="text-align:right;font-weight:bold">' + cur + parseFloat(h.NetAmount || 0).toFixed(dec) + '</td></tr>' +
        '</tfoot></table>' +
        (h.Notes ? '<p style="margin-top:12px;font-size:11px;color:#666"><strong>Notes:</strong> ' + _esc(h.Notes) + '</p>' : '') +
        (h.TermsConditions ? '<p style="font-size:11px;color:#666"><strong>Terms & Conditions:</strong> ' + _esc(h.TermsConditions) + '</p>' : '') +
    '</div></body></html>';
    return forPrint ? html : '<iframe srcdoc="' + html.replace(/"/g, '&quot;') + '" style="width:100%;height:100%;border:0;min-height:75vh"></iframe>';
}

function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}
</script>
