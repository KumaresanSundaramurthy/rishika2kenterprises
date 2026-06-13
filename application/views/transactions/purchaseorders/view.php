<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">

                <!-- ── Apex Page Header ──────────────────────────────────── -->
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-cart',
                    'pageIconBg'      => '#ffedd5',
                    'pageIconColor'   => '#f97316',
                    'pageTitle'       => $PageTitle       ?? 'Purchase Orders',
                    'pageDescription' => $PageDescription ?? 'Create and manage purchase orders sent to suppliers',
                ]); ?>

                <!-- ── Apex Stats Strip ──────────────────────────────────── -->
                <?php
                $poStats   = $POStats ?? [];
                $allCount  = array_sum(array_column($poStats, 'count'));
                $allAmount = array_sum(array_column($poStats, 'amount'));
                $cur       = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec       = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

                $statsItems = [
                    ['label' => 'All Purchase Orders', 'status' => 'All',       'icon' => 'bx-file-blank',   'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $allCount,                              'amount' => $allAmount],
                    ['label' => 'Received',            'status' => 'Received',  'icon' => 'bx-download',     'iconBg' => '#e0f5f2', 'iconColor' => '#17a2b8', 'count' => $poStats['Received']['count']  ?? 0,    'amount' => $poStats['Received']['amount']  ?? 0],
                    ['label' => 'Closed',              'status' => 'Closed',    'icon' => 'bx-check-circle', 'iconBg' => '#d1e7dd', 'iconColor' => '#198754', 'count' => $poStats['Closed']['count']    ?? 0,    'amount' => $poStats['Closed']['amount']    ?? 0],
                    ['label' => 'Cancelled',           'status' => 'Cancelled', 'icon' => 'bx-x-circle',     'iconBg' => '#f8d7da', 'iconColor' => '#dc3545', 'count' => $poStats['Cancelled']['count'] ?? 0,    'amount' => $poStats['Cancelled']['amount'] ?? 0],
                    ['label' => 'Drafts',              'status' => 'Draft',     'icon' => 'bx-edit',         'iconBg' => '#f1f3f5', 'iconColor' => '#6c757d', 'count' => $poStats['Draft']['count']     ?? 0,    'amount' => $poStats['Draft']['amount']     ?? 0],
                ];
                ?>
                <div class="apex-stats-strip">
                    <?php foreach ($statsItems as $stat): ?>
                    <div class="apex-stat-item <?php echo $stat['status'] === 'All' ? 'active' : ''; ?>" data-status="<?php echo $stat['status']; ?>" style="--stat-color:<?php echo $stat['iconColor']; ?>">
                        <div class="apex-stat-icon" style="background:<?php echo $stat['iconBg']; ?>;">
                            <i class="bx <?php echo $stat['icon']; ?>" style="color:<?php echo $stat['iconColor']; ?>;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label"><?php echo $stat['label']; ?></div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $stat['count']; ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format($stat['amount'], $dec); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="container-xxl flex-grow-1 py-3">

                    <div class="card">

                        <!-- ── Apex Filter Row ───────────────────────────── -->
                        <div class="apex-filter-row">

                            <button class="apex-filter-btn" id="poVendorFilterBtn">
                                <i class="bx bx-store"></i>
                                <span id="poVendorFilterLabel">All Vendors</span>
                                <i class="bx bx-chevron-down"></i>
                            </button>

                            <div class="dropdown">
                                <button class="apex-filter-btn" id="poStatusFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-filter-alt"></i>
                                    <span id="poStatusFilterLabel">All Status</span>
                                    <i class="bx bx-chevron-down"></i>
                                </button>
                                <ul class="dropdown-menu shadow-sm" style="font-size:.82rem;min-width:160px;">
                                    <li><button class="dropdown-item po-status-filter-opt" data-status="All">All Status</button></li>
                                    <li><button class="dropdown-item po-status-filter-opt" data-status="Received">Received</button></li>
                                    <li><button class="dropdown-item po-status-filter-opt" data-status="Closed">Closed</button></li>
                                    <li><button class="dropdown-item po-status-filter-opt" data-status="Cancelled">Cancelled</button></li>
                                    <li><button class="dropdown-item po-status-filter-opt" data-status="Draft">Drafts</button></li>
                                </ul>
                            </div>

                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <button class="apex-filter-btn" id="poUserFilterBtn">
                                <i class="bx bx-user"></i>
                                <span id="poUserFilterLabel">All Users</span>
                                <i class="bx bx-chevron-down"></i>
                            </button>
                            <?php endif; ?>

                            <!-- Data search -->
                            <div class="apex-search-wrap" style="flex-shrink:0;">
                                <i class="bx bx-search apex-search-icon"></i>
                                <input type="text" id="poSearchInput" class="apex-search-input" placeholder="Search PO #, vendor..." style="width:190px;">
                                <i class="bx bx-x apex-search-clear d-none"></i>
                            </div>

                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>

                            <div class="apex-filter-spacer"></div>

                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh">
                                <i class="bx bx-refresh"></i>
                            </a>

                            <?php $this->load->view('common/partials/export_btn'); ?>

                            <a href="/purchaseorders/create" class="btn btn-sm btn-primary">
                                <i class="bx bx-plus me-1"></i>New Purchase Order
                            </a>

                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="poStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active po-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1 po-tab-count"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link po-status-tab" data-status="Received" href="javascript:void(0);">Received <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link po-status-tab" data-status="Closed" href="javascript:void(0);">Closed <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link po-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link po-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- ── Table ───────────────────────────────────── -->
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover MainviewTable mb-0" id="poTable">
                                <thead class="r2k-thead bg-body-tertiary">
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
                                        <th>
                                            Vendor
                                            <a href="javascript:void(0);" id="poPartyFilterTrigger" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Vendor"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            PO Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Expected Date</th>
                                        <th>
                                            Last Updated
                                            <?php if (count($OrgUsers ?? []) > 1): ?>
                                            <a href="javascript:void(0);" id="poCreatedByFilter" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by User"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                            <?php endif; ?>
                                        </th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ── Pagination ──────────────────────────────── -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center poPagination" id="poPagination">
                            <?php echo $ModPagination ? $ModPagination : ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>

            </div>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'poCreatedByFilterBox',
        'triggerId'  => 'poCreatedByFilter',
        'checkClass' => 'po-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'poPartyFilterBox',
        'title' => 'Filter by Vendor',
        'icon'  => 'bx-store',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
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
    initExport({ moduleUID: 104, getFilters: function () { return Filter; } });

    var tfb = null; // TransFilterBar not used for PO (all options disabled)

    var poCreatedByFilter = (document.getElementById('poCreatedByFilterBox'))
        ? new TransColFilter({
            boxId     : 'poCreatedByFilterBox',
            triggerId : 'poCreatedByFilter',
            filterKey : 'UpdatedByUIDs',
            onApply   : function () { PageNo = 1; getPurchaseOrdersDetails(); }
        })
        : null;

    var poPartyFilter = new TransPartyColFilter({
        boxId     : 'poPartyFilterBox',
        triggerId : 'poPartyFilterTrigger',
        partyType : 'vendor',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getPurchaseOrdersDetails(); }
    });

    var _origGetPurchaseOrdersDetails = getPurchaseOrdersDetails;
    getPurchaseOrdersDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            poCreatedByFilter ? poCreatedByFilter.getState() : {},
            poPartyFilter     ? poPartyFilter.getState()     : {}
        );
        _origGetPurchaseOrdersDetails(pageNo, rowLimit, f);
    };

    // ── Status tabs ─────────────────────────────────────────────────────────
    $(document).on('click', '.po-status-tab', function (e) {
        e.preventDefault();
        $('.po-status-tab').removeClass('active');
        $(this).addClass('active');
        Filter.Status = $(this).data('status') || 'All';
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // ── Apex: keep stat strip + status btn in sync with active tab ──────────
    $(document).on('click', '.po-status-tab', function () {
        var s = $(this).data('status') || 'All';
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-status="' + s + '"]').addClass('active');
        var lbl = s === 'All' ? 'All Status' : s === 'Draft' ? 'Drafts' : s;
        $('#poStatusFilterLabel').text(lbl);
        $('#poStatusFilterBtn').toggleClass('has-filter', s !== 'All');
    });

    // ── Apex: click stat item → activate corresponding tab ──────────────────
    $(document).on('click', '.apex-stat-item', function () {
        $('.po-status-tab[data-status="' + $(this).data('status') + '"]').trigger('click');
    });

    // ── Apex: status dropdown in filter row → trigger tab ───────────────────
    $(document).on('click', '.po-status-filter-opt', function () {
        $('.po-status-tab[data-status="' + $(this).data('status') + '"]').trigger('click');
    });

    // ── Apex: vendor filter btn → trigger existing popup ────────────────────
    $('#poVendorFilterBtn').on('click', function (e) {
        e.preventDefault();
        $('#poPartyFilterTrigger').trigger('click');
    });

    // ── Apex: user filter btn → trigger existing popup ───────────────────────
    $('#poUserFilterBtn').on('click', function (e) {
        e.preventDefault();
        $('#poCreatedByFilter').trigger('click');
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // Data search (filter row)
    $('#poSearchInput').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchaseOrdersDetails();
    }, 500));

    // Date filter
    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
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

    // ── Inline status update ─────────────────────────────────────────────────
    $(document).on('click', '.po-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        if ($(this).data('_confirmed')) { $(this).removeData('_confirmed'); return; }
        if (status === 'Cancelled') {
            var num = $(this).data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this purchase order';
            var $btn = $(this);
            Swal.fire({ title: 'Cancel Purchase Order?', html: 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It' }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        $.ajax({
            url   : '/purchaseorders/updatePurchaseOrderStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                else            { getPurchaseOrdersDetails(); }
            }
        });
    });

    // ── A4 Print ─────────────────────────────────────────────────────────────
    $(document).on('click', '.a4PrintPO', function () {
        var uid = $(this).data('uid');
        $('#a4ModalTitle').text('Purchase Order Preview');
        $('#a4PrintModal').modal('show');
        $("#a4PreviewStage").html('<div class="d-flex justify-content-center align-items-center w-100 h-100"><div class="spinner-border text-light"></div></div>');
        $.ajax({
            url   : '/purchaseorders/getPurchaseOrderDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $("#a4PreviewStage").html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                } else {
                    var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
                    window._poLastPrintData = resp;
                    $("#a4PreviewStage").html(_buildA4Html(resp, size));
                }
            }
        });
    });

    $('input[name="a4PaperSize"]').on('change', function () {
        if (!window._poLastPrintData) return;
        $("#a4PreviewStage").html(_buildA4Html(window._poLastPrintData, $(this).val()));
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

    // ── Delete ────────────────────────────────────────────────────────────────
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
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getPurchaseOrdersDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                }
            });
        });
    });

});

// ── Detail view HTML builder ──────────────────────────────────────────────
function _buildPODetailHtml(resp) {
    window._poLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Vendor',
        typeIcon    : 'bx-purchase-tag-alt',
        typeColor   : '#0f766e',
        typeBg      : '#e0f5f2',
        hasPayments : false,
        validLabel  : 'Expected Delivery',
    });
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
        '<table><thead class="r2k-thead"><tr><th style="width:30px">#</th><th>Product</th><th style="width:60px;text-align:center">Qty</th><th style="width:90px;text-align:right">Unit Price</th><th style="width:90px;text-align:right">Net Amount</th></tr></thead>' +
        '<tbody class="r2k-tbody">' + rows + '</tbody>' +
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
</script>
