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
                    $cntIssued   = $stats['Issued']['count']    ?? 0;
                    $cntCancelled= $stats['Cancelled']['count'] ?? 0;
                    $cntDraft    = $stats['Draft']['count']     ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtIssued   = $stats['Issued']['amount']   ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="trans-stat-label">All Debit Notes</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                <i class="bx bx-note trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Issued">
                                <div class="trans-stat-label">Issued</div>
                                <div class="trans-stat-count"><?php echo number_format($cntIssued); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtIssued, $cur, $dec); ?></div>
                                <i class="bx bx-send trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-overdue" data-stat-filter="Cancelled">
                                <div class="trans-stat-label">Cancelled</div>
                                <div class="trans-stat-count"><?php echo number_format($cntCancelled); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-x-circle trans-stat-icon"></i>
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
                            <ul class="nav trans-status-tabs gap-1" id="dnStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active dn-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link dn-status-tab" data-status="Issued" href="javascript:void(0);">
                                        Issued <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link dn-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link dn-status-tab" data-status="Draft" href="javascript:void(0);">
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
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData" placeholder="Debit note # or vendor...">
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
                                        <li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_month">This Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                            <i class="bx bxs-star text-warning me-1"></i>FY 25-26
                                        </a></li>
                                    </ul>
                                </div>
                                <a href="/debitnotes/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="dnTable">
                                <thead>
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox dnHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Debit Note # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Vendor</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center dnPagination" id="dnPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <!-- ── Thermal Print Modal ─────────────────────────────── -->
                    <div class="modal fade" id="thermalPrintModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top" style="max-width:600px">
                            <div class="modal-content">
                                <div class="modal-header p-3">
                                    <h6 class="modal-title text-primary fw-bold fs-6 mb-0"><i class="bx bx-printer me-1"></i>Thermal Receipt Preview</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-2 bg-white" id="thermalPrintBody">
                                    <div class="d-flex justify-content-center py-5">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>
                                <div class="modal-footer py-2">
                                    <a href="/quotations/thermalPrintConfig" class="btn btn-outline-secondary btn-sm me-auto">
                                        <i class="bx bx-cog me-1"></i>Configure
                                    </a>
                                    <button type="button" class="btn btn-dark btn-sm" id="thermalPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
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

<script src="/js/transactions/debitnotes.js"></script>

<script>
const ModuleId     = 109;
const ModuleTable  = '#dnTable';
const ModulePag    = '.dnPagination';
const ModuleHeader = '.dnHeaderCheck';
const ModuleRow    = '.dnCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat'); $(this).addClass('active-stat');
        $('.dn-status-tab').removeClass('active');
        $('.dn-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status; PageNo = 1; getDebitNotesDetails();
    });

    $(document).on('click', '.dn-status-tab', function (e) {
        e.preventDefault();
        $('.dn-status-tab').removeClass('active'); $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status; PageNo = 1; getDebitNotesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) { e.preventDefault(); PageNo = 1; getDebitNotesDetails(); });

    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getDebitNotesDetails();
    }, 400));

    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active'); $(this).addClass('active');
        var dates = getDateRange($(this).data('range') || '');
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from; Filter.DateTo = dates.to;
        PageNo = 1; getDebitNotesDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        Filter.SortDir = (Filter.SortBy === col && Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        Filter.SortBy  = col;
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getDebitNotesDetails();
    });

    $(document).on('click', '.dnPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getDebitNotesDetails(); }
    });

    $(document).on('click', '.dn-status-update', function () {
        var uid = $(this).data('uid'), status = $(this).data('status');
        $.ajax({ url: '/debitnotes/updateDebitNoteStatus', method: 'POST', data: { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) { if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); } else { getDebitNotesDetails(); } }
        });
    });

    $(document).on('click', '.deleteDebitNote', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({ title: 'Delete Debit Note?', html: num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({ url: '/debitnotes/deleteDebitNote', method: 'POST', data: { TransUID: uid, [CsrfName]: CsrfToken },
                    success: function (resp) { if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); } else { getDebitNotesDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); } }
                });
            });
    });

    $(document).on('click', '.duplicateDebitNote', function () {
        var uid = $(this).data('uid');
        Swal.fire({ title: 'Duplicate Debit Note?', text: 'A new draft copy will be created.', icon: 'question', showCancelButton: true, confirmButtonText: 'Duplicate' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({ url: '/debitnotes/duplicateDebitNote', method: 'POST', data: { TransUID: uid, [CsrfName]: CsrfToken },
                    success: function (resp) {
                        if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                        else { getDebitNotesDetails(); Swal.fire({ icon: 'success', text: resp.Message, showCancelButton: true, confirmButtonText: 'Edit Now', cancelButtonText: 'Stay Here' }).then(function (r2) { if (r2.isConfirmed && resp.EditURL) window.location.href = resp.EditURL; }); }
                    }
                });
            });
    });

    $(document).on('change', '.dnHeaderCheck', function () { $('.dnCheck').prop('checked', $(this).is(':checked')); });
});

// ── Thermal Print ─────────────────────────────────────────────────────────
var _thermalData = null;
function _esc(v) { if (v === null || v === undefined) return '—'; return $('<span>').text(String(v)).html(); }

$(document).on('click', '.thermalPrintDebitNote', function () {
    var uid = $(this).data('uid');
    _thermalData = null;
    $('#thermalPrintBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('thermalPrintModal')).show();
    AjaxLoading = 0;
    $.ajax({
        url: '/debitnotes/getDebitNoteDetail', method: 'GET', data: { TransUID: uid },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) { $('#thermalPrintBody').html('<div class="alert alert-danger m-2">' + _esc(resp.Message) + '</div>'); return; }
            _thermalData = resp; $('#thermalPrintBody').html(_buildThermalHtml(resp, 0));
        },
        error: function () { AjaxLoading = 1; $('#thermalPrintBody').html('<div class="alert alert-danger m-2">Failed to load receipt.</div>'); }
    });
});

$('#thermalPrintBtn').on('click', function () {
    if (!_thermalData) return;
    var cfg = _thermalData.ThermalConfig, paperWidth = (cfg && cfg.PaperWidth) ? cfg.PaperWidth : '80mm';
    var win = window.open('', '_blank', 'width=400,height=700');
    win.document.write('<!DOCTYPE html><html><head><title>Thermal Receipt</title><style>* { margin:0; padding:0; box-sizing:border-box; } body { font-family:Arial,sans-serif; font-size:12px; width:' + paperWidth + '; padding:4px; } .fs-6 { font-size:0.8rem!important; } .tp-center { text-align:center; } .tp-bold { font-weight:bold; } .tp-hr { border:none; border-top:1px dashed #000; margin:4px 0; } .tp-row { display:flex; justify-content:space-between; margin:1px 0; } .tp-row-end { display:flex; justify-content:end; margin:1px 0; } .tp-item-name { font-weight:bold; margin-top:2px; } .tp-small { font-size:11px; } .tp-total { font-size:13px; font-weight:bold; border-top:1px solid #000; padding-top:3px; margin-top:3px; } .tp-footer { text-align:center; margin-top:6px; font-size:11px; } @media print { @page { margin:0; size:' + paperWidth + ' auto; } body { width:' + paperWidth + '; } }</style></head><body style="font-family:Arial,sans-serif!important;font-size:12px!important;width:' + paperWidth + ';padding:4px;">' + _buildThermalHtml(_thermalData, 1) + '</body></html>');
    win.document.close(); win.focus(); setTimeout(function () { win.print(); }, 300);
});

function _buildThermalHtml(resp, type) {
    var h = resp.Header, org = resp.OrgInfo || {}, cfg = resp.ThermalConfig || {};
    var sym = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var line1 = cfg.HeaderLine1 || org.BrandName || org.Name || '';
    var line2 = cfg.HeaderLine2 || '', line3 = cfg.HeaderLine3 || [org.CityText, org.StateText, org.Pincode].filter(Boolean).join(', ');
    var showGSTIN = cfg.ShowGSTIN !== undefined ? parseInt(cfg.ShowGSTIN) : 1;
    var showMobile = cfg.ShowMobile !== undefined ? parseInt(cfg.ShowMobile) : 1;
    var showHSN = cfg.ShowHSN !== undefined ? parseInt(cfg.ShowHSN) : 1;
    var showTaxBkd = cfg.ShowTaxBreakdown !== undefined ? parseInt(cfg.ShowTaxBreakdown) : 1;
    var footer = cfg.FooterMessage || 'Thank you for your business!';
    var html = '<div style="display:flex;align-items:center;justify-content:center;"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="60px" height="60px" alt="Logo">';
    html += '<div class="fs-6 ms-1"><div class="tp-center tp-bold">' + _esc(line1) + '</div>';
    if (line2) html += '<div class="tp-center tp-small">' + _esc(line2) + '</div>';
    if (line3) html += '<div class="tp-center tp-small">' + _esc(line3) + '</div>';
    if (showMobile && org.MobileNumber) html += '<div class="tp-center tp-small">Ph: ' + _esc(org.MobileNumber) + '</div>';
    if (showGSTIN && org.GSTIN) html += '<div class="tp-center tp-small">GSTIN: ' + _esc(org.GSTIN) + '</div>';
    html += '</div></div><hr class="tp-hr my-1">';
    html += '<div class="fs-6"><div class="d-flex justify-content-between align-items-center mb-1">';
    html += '<div class="tp-row fs-6"><span class="tp-bold">Debit Note #: </span><span class="tp-bold">' + _esc(h.UniqueNumber || '—') + '</span></div>';
    html += '<div class="tp-row"><span>Date: </span><span>' + _esc(h.TransDate) + '</span></div></div>';
    html += '<div class="d-flex justify-content-between align-items-center"><div class="tp-row"><span>Vendor: </span><span style="text-align:right;max-width:60%">' + _esc(h.PartyName) + '</span></div>';
    if (h.PartyMobile) html += '<div class="tp-row"><span>Phone: </span><span>' + _esc(h.PartyMobile) + '</span></div>';
    html += '</div></div><hr class="tp-hr my-1">';
    html += '<div class="fs-6"><div class="mb-1" style="display:flex;align-items:center;justify-content:space-between;"><div class="tp-row tp-item-name tp-bold"><span>Item </span></div><div></div></div>';
    html += '<div style="display:flex;align-items:center;justify-content:space-between;"><div class="tp-row" style="font-size:smaller;">Quantity x Price</div><div class="tp-row">Amount</div></div></div><hr class="tp-hr my-1">';
    $.each(resp.Items || [], function (i, item) {
        var lineAmt = parseFloat(item.NetAmount) || 0, hsnLine = (showHSN && item.HSNCode) ? ' [HSN:' + item.HSNCode + ']' : '';
        html += '<div class="fs-6"><div class="mb-1" style="display:flex;align-items:center;justify-content:space-between;"><div class="tp-item-name fs-6">' + _esc(item.ProductName) + _esc(hsnLine) + '</div><div></div></div>';
        html += '<div class="mb-1" style="display:flex;align-items:center;justify-content:space-between;"><div class="tp-row tp-small" style="font-size:smaller;">' + _esc(item.Quantity) + ' (' + _esc(item.PrimaryUnitName || 'PCS') + ') x ' + _esc(item.UnitPrice) + '</div><div class="fs-6">' + lineAmt.toFixed(2) + '</div></div>';
        if (showTaxBkd && parseFloat(item.TaxPercentage) > 0) {
            var cgst = parseFloat(item.CgstAmount)||0, sgst = parseFloat(item.SgstAmount)||0, igst = parseFloat(item.IgstAmount)||0;
            html += '<div style="display:flex;align-items:center;justify-content:space-between;">';
            if (cgst > 0 && sgst > 0) { html += '<div class="tp-row tp-small" style="color:#555;font-size:smaller;">CGST ' + item.CGST + '% ' + cgst.toFixed(2) + '</div><div class="tp-row tp-small" style="color:#555;font-size:smaller;">SGST ' + item.SGST + '% ' + sgst.toFixed(2) + '</div>'; }
            else if (igst > 0) { html += '<div class="tp-row tp-small" style="color:#555;font-size:smaller;">IGST ' + item.IGST + '% ' + igst.toFixed(2) + '</div>'; }
            html += '</div>';
        }
        html += '</div>';
        if (resp.Items.length > 1 && i != resp.Items.length - 1) html += '<hr class="tp-hr my-1">';
    });
    html += '<hr class="tp-hr my-1"><div class="tp-small fs-6" style="text-align:center!important;">Items/Qty: ' + (resp.Items ? resp.Items.length : 0) + ' / ' + (function(){ var q=0; $.each(resp.Items||[],function(i,it){q+=parseFloat(it.Quantity)||0;}); return q; }()) + '</div><hr class="tp-hr my-1">';
    html += '<div style="text-align:end!important;"><div class="tp-row-end fw-semibold tp-item-name"><span>Subtotal: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.SubTotal || 0).toFixed(2) + '</span></div>';
    if (parseFloat(h.DiscountAmount) > 0) html += '<div class="tp-row-end fw-semibold"><span>Discount: </span><span class="fs-6">- ' + sym + ' ' + parseFloat(h.DiscountAmount).toFixed(2) + '</span></div>';
    if (parseFloat(h.TaxAmount) > 0) {
        html += '<div class="tp-row-end fw-semibold"><span>Total Tax: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.TaxAmount).toFixed(2) + '</span></div>';
        if (showTaxBkd) {
            if (parseFloat(h.CgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  CGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.CgstAmount).toFixed(2) + '</span></div>';
            if (parseFloat(h.SgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  SGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.SgstAmount).toFixed(2) + '</span></div>';
            if (parseFloat(h.IgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  IGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.IgstAmount).toFixed(2) + '</span></div>';
        }
    }
    if (parseFloat(h.AdditionalCharges) > 0) html += '<div class="tp-row-end fw-semibold"><span>Charges: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.AdditionalCharges).toFixed(2) + '</span></div>';
    if (parseFloat(h.RoundOff || 0) !== 0) html += '<div class="tp-row-end tp-small fw-semibold"><span>Round Off: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.RoundOff).toFixed(2) + '</span></div>';
    html += '<div class="tp-total tp-row-end fw-semibold tp-item-name"><span>Total Amount: </span><span class="fs-5">' + sym + ' ' + parseFloat(h.NetAmount || 0).toFixed(2) + '</span></div></div>';
    html += '<hr class="tp-hr my-1"><div class="tp-footer" style="text-align:center!important;">' + _esc(footer) + '</div><div style="margin-bottom:8px"></div>';
    return type === 0 ? '<div style="font-family:\'Courier New\',Courier,monospace;font-size:13px;padding:8px;max-width:580px;margin:0 auto;font-weight:900;">' + html + '</div>' : html;
}
</script>
