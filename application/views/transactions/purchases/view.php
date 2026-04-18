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
                    $cntPending  = ($stats['Received']['count'] ?? 0) + ($stats['Partial']['count'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']    ?? 0;
                    $cntDraft    = $stats['Draft']['count']   ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtPending  = ($stats['Received']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                    $amtPaid     = $stats['Paid']['amount']   ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="trans-stat-label">All Purchases</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                <i class="bx bx-package trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Received">
                                <div class="trans-stat-label">Pending Payment</div>
                                <div class="trans-stat-count"><?php echo number_format($cntPending); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtPending, $cur, $dec); ?></div>
                                <i class="bx bx-time-five trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Paid">
                                <div class="trans-stat-label">Paid</div>
                                <div class="trans-stat-count"><?php echo number_format($cntPaid); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtPaid, $cur, $dec); ?></div>
                                <i class="bx bx-check-circle trans-stat-icon"></i>
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

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="purchStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active purch-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Received" href="javascript:void(0);">
                                        Received <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Partial" href="javascript:void(0);">
                                        Partial <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Paid" href="javascript:void(0);">
                                        Paid <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData"
                                           placeholder="Bill # or vendor..." title="Search purchases">
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
                                <a href="/purchases/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Record Bill
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="purchTable">
                                <thead>
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox purchHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Bill # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Vendor</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Due Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center purchPagination" id="purchPagination">
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
<script src="/js/transactions/purchases.js"></script>

<script>
const ModuleId     = 105;
const ModuleTable  = '#purchTable';
const ModulePag    = '.purchPagination';
const ModuleHeader = '.purchHeaderCheck';
const ModuleRow    = '.purchCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    // ── Stat card click → filter ────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.purch-status-tab').removeClass('active');
        $('.purch-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.purch-status-tab', function (e) {
        e.preventDefault();
        $('.purch-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getPurchasesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getPurchasesDetails();
    });

    // Search
    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchasesDetails();
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
        getPurchasesDetails();
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
        getPurchasesDetails();
    });

    // Pagination
    $(document).on('click', '.purchPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getPurchasesDetails(); }
    });

    // ── Inline status update ────────────────────────────────
    $(document).on('click', '.purch-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        $.ajax({
            url   : '/purchases/updatePurchaseStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                else            { getPurchasesDetails(); }
            }
        });
    });

    // ── View modal ──────────────────────────────────────────
    $(document).on('click', '.viewPurchase', function () {
        var uid = $(this).data('uid');
        $('#viewTransModal').modal('show');
        $('#viewTransModalBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewTransEditBtn').attr('href', '/purchases/edit/' + uid);
        $.ajax({
            url   : '/purchases/getPurchaseDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#viewTransModalBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                } else {
                    $('#viewTransModalTitle').text('Purchase Bill — ' + (resp.Header.UniqueNumber || 'Details'));
                    $('#viewTransModalBody').html(_buildPurchDetailHtml(resp));
                }
            },
            error: function () {
                $('#viewTransModalBody').html('<div class="alert alert-danger m-3">Failed to load purchase bill.</div>');
            }
        });
    });

    // ── A4 Print ─────────────────────────────────────────────
    $(document).on('click', '.a4PrintPurchase', function () {
        var uid = $(this).data('uid');
        $('#a4ModalTitle').text('Purchase Bill Preview');
        $('#a4PrintModal').modal('show');
        $('#a4PrintPreview').html('<div class="d-flex justify-content-center align-items-center w-100 h-100"><div class="spinner-border text-light"></div></div>');
        $.ajax({
            url   : '/purchases/getPurchaseDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#a4PrintPreview').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                } else {
                    var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
                    window._purchLastPrintData = resp;
                    $('#a4PrintPreview').html(_buildA4Html(resp, size));
                }
            }
        });
    });
    $('input[name="a4PaperSize"]').on('change', function () {
        if (window._purchLastPrintData) $('#a4PrintPreview').html(_buildA4Html(window._purchLastPrintData, $(this).val()));
    });
    $('#a4PrintBtn').on('click', function () {
        var frame = document.getElementById('a4PrintFrame');
        if (!frame) { frame = document.createElement('iframe'); frame.id = 'a4PrintFrame'; frame.style.display = 'none'; document.body.appendChild(frame); }
        var size    = $('input[name="a4PaperSize"]:checked').val() || 'A4';
        var content = _buildA4Html(window._purchLastPrintData, size, true);
        frame.contentDocument.open(); frame.contentDocument.write(content); frame.contentDocument.close();
        frame.onload = function () { frame.contentWindow.print(); };
    });

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deletePurchase', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Purchase Bill?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/purchases/deletePurchase',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getPurchasesDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                }
            });
        });
    });

    // ── Duplicate ────────────────────────────────────────────
    $(document).on('click', '.duplicatePurchase', function () {
        var uid = $(this).data('uid');
        Swal.fire({
            title: 'Duplicate Purchase Bill?', text: 'A new draft copy will be created.',
            icon : 'question', showCancelButton: true, confirmButtonText: 'Duplicate',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/purchases/duplicatePurchase',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else {
                        getPurchasesDetails();
                        Swal.fire({ icon: 'success', text: resp.Message, showCancelButton: true, confirmButtonText: 'Edit Now', cancelButtonText: 'Stay Here' })
                            .then(function (r2) { if (r2.isConfirmed && resp.EditURL) window.location.href = resp.EditURL; });
                    }
                }
            });
        });
    });

    // Header checkbox
    $(document).on('change', '.purchHeaderCheck', function () {
        $('.purchCheck').prop('checked', $(this).is(':checked'));
    });

});

// ── Detail view HTML builder ──────────────────────────────
function _buildPurchDetailHtml(resp) {
    window._purchLastPrintData = resp;
    var h = resp.Header || {}, org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ', dec = h.DecimalPoints || 2;
    var rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr>' +
            '<td class="text-center">' + (i + 1) + '</td>' +
            '<td>' + _esc(item.ProductName) + (item.PartNumber ? '<br><small class="text-muted">' + _esc(item.PartNumber) + '</small>' : '') + '</td>' +
            '<td class="text-center">' + _esc(item.Quantity) + ' ' + _esc(item.PrimaryUnitName) + '</td>' +
            '<td class="text-end">' + cur + parseFloat(item.UnitPrice || 0).toFixed(dec) + '</td>' +
            '<td class="text-end">' + cur + parseFloat(item.NetAmount || 0).toFixed(dec) + '</td>' +
            '</tr>';
    });
    return '<div class="p-3">' +
        '<div class="row mb-3">' +
            '<div class="col-md-6"><strong>' + _esc(org.OrgName || '') + '</strong><br>' +
                '<small class="text-muted">' + _esc(h.UniqueNumber || '—') + ' &nbsp;|&nbsp; ' + _esc(h.TransDate || '') + '</small></div>' +
            '<div class="col-md-6 text-end"><strong>Vendor:</strong> ' + _esc(h.PartyName || '—') +
                (h.ValidityDate ? '<br><small class="text-muted">Due: ' + _esc(h.ValidityDate) + '</small>' : '') + '</div>' +
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
    window._purchLastPrintData = resp;
    var w = size === 'A5' ? '148mm' : '210mm';
    var h = resp.Header || {}, org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ', dec = 2;
    var rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr><td style="text-align:center">' + (i + 1) + '</td>' +
            '<td>' + _esc(item.ProductName) + (item.PartNumber ? '<br><span style="font-size:.8em;color:#888">' + _esc(item.PartNumber) + '</span>' : '') + '</td>' +
            '<td style="text-align:center">' + _esc(item.Quantity) + ' ' + (_esc(item.PrimaryUnitName) || '') + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.UnitPrice || 0).toFixed(dec) + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.NetAmount || 0).toFixed(dec) + '</td></tr>';
    });
    var ps = forPrint ? '@media print{body{margin:0}.page{box-shadow:none}}' : '';
    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' +
        'body{font-family:Arial,sans-serif;font-size:12px;background:#404040}' +
        '.page{width:' + w + ';background:#fff;margin:0 auto;padding:20px;box-shadow:0 0 20px rgba(0,0,0,.5)}' +
        'table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;font-size:11px}' +
        'th{background:#f5f5f5;font-weight:bold}' + ps + '</style></head>' +
        '<body><div class="page">' +
        '<div style="display:flex;justify-content:space-between;margin-bottom:12px">' +
            '<div><strong style="font-size:14px">' + _esc(org.OrgName || '') + '</strong>' +
            (org.Address ? '<br><span style="color:#666">' + _esc(org.Address) + '</span>' : '') +
            (org.GSTNumber ? '<br><span style="color:#666">GSTIN: ' + _esc(org.GSTNumber) + '</span>' : '') + '</div>' +
            '<div style="text-align:right"><strong style="font-size:16px">PURCHASE BILL</strong><br>' +
            '<span style="color:#666">' + _esc(h.UniqueNumber || '—') + '</span><br>' +
            '<span style="color:#666">Date: ' + _esc(h.TransDate || '') + '</span>' +
            (h.ValidityDate ? '<br><span style="color:#666">Due: ' + _esc(h.ValidityDate) + '</span>' : '') +
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
