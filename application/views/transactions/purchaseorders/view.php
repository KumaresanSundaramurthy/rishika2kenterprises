<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#ffedd5;">
                                <i class="bx bx-cart" style="color:#f97316;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Purchase Orders'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/purchaseorders/create" class="btn btn-primary me-1">
                                <i class="bx bx-plus me-1"></i>New Purchase Order
                            </a>
                        </div>
                    </div>

                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-tabs">
                                <ul class="nav trans-status-tabs" id="poStatusTabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active po-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1 po-tab-count"><?php echo $ModAllCount; ?></span></a></li>
                                    <li class="nav-item"><a class="nav-link po-status-tab" data-status="Received" href="javascript:void(0);">Received <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link po-status-tab" data-status="Closed" href="javascript:void(0);">Closed <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link po-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link po-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 po-tab-count d-none"></span></a></li>
                                </ul>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="javascript:void(0);" class="r2k-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                                <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                                <?php $this->load->view('common/transactions/filter_bar', [
                                    'FilterBarConfig' => [
                                        'paymentStatus' => false,
                                        'paymentMode'   => false,
                                        'party'         => false,
                                        'lastUpdated'   => false,
                                        'PaymentTypes'  => [],
                                        'OrgUsers'      => $OrgUsers ?? [],
                                    ],
                                ]); ?>
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="searchTransactionData" placeholder="PO # or vendor...">
                                    <i class="bx bx-x r2k-clear d-none"></i>
                                </div>
                            </div>
                        </div>

                        <!-- ── Table ───────────────────────────────── -->
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

                        <!-- ── Pagination ──────────────────────────── -->
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

    // ── Filter bar ──────────────────────────────────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getPurchaseOrdersDetails(); } })
        : null;

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
            tfb               ? tfb.getState()               : {},
            poCreatedByFilter ? poCreatedByFilter.getState() : {},
            poPartyFilter     ? poPartyFilter.getState()     : {}
        );
        _origGetPurchaseOrdersDetails(pageNo, rowLimit, f);
    };

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
    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchaseOrdersDetails();
    }, 1500));

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

    // ── Inline status update ────────────────────────────
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
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    getPurchaseOrdersDetails();
                }
            }
        });
    });

    // View modal — handled by /js/transactions/viewmodal.js (.viewTransaction)

    // ── A4 Print ─────────────────────────────────────────
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
        var size = $(this).val();
        $("#a4PreviewStage").html(_buildA4Html(window._poLastPrintData, size));
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


});

// ── Detail view HTML builder ──────────────────────────────
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
