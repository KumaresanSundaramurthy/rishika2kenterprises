<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Purchase Price History',
                    'pageDescription' => 'Track purchase price changes per product and vendor',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="card">
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-filters d-flex align-items-center gap-2 flex-wrap">
                                <div class="r2k-search-wrap">
                                    <span class="r2k-search-icon"><i class="bx bx-search"></i></span>
                                    <input type="text" id="pplSearchInput" class="r2k-search-input"
                                           placeholder="Search product, vendor, SKU…">
                                </div>
                                <!-- Source filter -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary apex-filter-btn dropdown-toggle" type="button"
                                            id="pplSourceFilter" data-bs-toggle="dropdown">
                                        <i class="bx bx-filter-alt me-1"></i>Source
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item ppl-source-opt" data-val="" href="#">All</a></li>
                                        <li><a class="dropdown-item ppl-source-opt" data-val="1" href="#">Auto</a></li>
                                        <li><a class="dropdown-item ppl-source-opt" data-val="2" href="#">Manual</a></li>
                                    </ul>
                                </div>
                                <!-- Direction filter -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary apex-filter-btn dropdown-toggle" type="button"
                                            id="pplDirFilter" data-bs-toggle="dropdown">
                                        <i class="bx bx-sort-alt-2 me-1"></i>Direction
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item ppl-dir-opt" data-val="" href="#">All</a></li>
                                        <li><a class="dropdown-item ppl-dir-opt" data-val="0" href="#">First Entry</a></li>
                                        <li><a class="dropdown-item ppl-dir-opt" data-val="1" href="#">Price Up</a></li>
                                        <li><a class="dropdown-item ppl-dir-opt" data-val="2" href="#">Price Down</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="#" class="r2k-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                                <button type="button" class="btn btn-sm btn-primary" id="btnAddPriceLog">
                                    <i class="bx bx-plus me-1"></i>Add Manual Entry
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="pplTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="r2k-sl-col">#</th>
                                        <th>Product</th>
                                        <th>Vendor</th>
                                        <th>Date</th>
                                        <th class="text-end">Purchase Price</th>
                                        <th class="text-end">Selling Price</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-center">Change</th>
                                        <th class="text-center">Source</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="pplTableBody">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-3 my-2 justify-content-between align-items-center" id="pplPagination">
                            <?php echo $ModPagination ?? ''; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ── Manual Entry Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="pplManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pplManualModalTitle">Add Manual Price Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pplLogUID" value="0">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                        <select class="form-select select2-product" id="pplProductSelect" style="width:100%;">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                        <select class="form-select select2-vendor" id="pplVendorSelect" style="width:100%;">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Entry Date <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pplEntryDate" placeholder="Select date" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Qty</label>
                        <input type="number" class="form-control" id="pplQty" min="0" step="any" placeholder="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Purchase Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="pplPurchasePrice" min="0" step="any" placeholder="0.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Selling Price</label>
                        <input type="number" class="form-control" id="pplSellingPrice" min="0" step="any" placeholder="auto-filled" readonly>
                    </div>
                    <div class="col-12" id="pplBackdatedNotice" style="display:none;">
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="bx bx-calendar-exclamation me-1"></i>
                            This date is between existing entries. Notes are required to explain this backdated record.
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" id="pplRemarksLabel">Notes</label>
                        <textarea class="form-control" id="pplRemarks" rows="2" placeholder="Optional notes…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="pplSaveBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="pplSaveSpinner"></span>
                    Save Entry
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Remarks Viewer Modal ───────────────────────────────────────────────── -->
<div class="modal fade" id="pplRemarkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Entry Notes</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="pplRemarkText" class="mb-0" style="white-space:pre-wrap;"></p>
            </div>
        </div>
    </div>
</div>

<script>
var _pplFilter = {
    Search:    '',
    Source:    '',
    Direction: '',
};
var _pplIsBackdated = false;

function _pplLoad(pageNo) {
    pageNo = pageNo || 1;
    $('#pplPagination').empty();
    ajaxLoading(0);
    $('#pplTableBody').html(
        '<tr><td colspan="10" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary"></span></td></tr>'
    );
    $.ajax({
        url    : '/purchasepricehistory/getPageDetails/' + pageNo,
        method : 'POST',
        data   : { RowLimit: _transRowLimit || 10, Filter: _pplFilter, [CsrfName]: CsrfToken },
        success: function (r) {
            ajaxLoading(1);
            if (r.Error) {
                $('#pplTableBody').html('');
                $('#pplPagination').html('<div class="alert alert-danger m-2">' + r.Message + '</div>');
            } else {
                $('#pplTableBody').html(r.RecordHtmlData);
                $('#pplPagination').html(r.Pagination);
                initTooltips();
            }
        },
        error: function () {
            ajaxLoading(1);
            $('#pplPagination').html('<div class="alert alert-danger m-2">Failed to load data.</div>');
        }
    });
}

// ── Search ────────────────────────────────────────────────────────────────
var _pplSearchTimer;
$('#pplSearchInput').on('input', function () {
    clearTimeout(_pplSearchTimer);
    var v = $(this).val();
    _pplSearchTimer = setTimeout(function () {
        _pplFilter.Search = v;
        _pplLoad(1);
    }, 350);
});

// ── Source filter ─────────────────────────────────────────────────────────
$(document).on('click', '.ppl-source-opt', function (e) {
    e.preventDefault();
    _pplFilter.Source = $(this).data('val');
    var lbl = $(this).text();
    $('#pplSourceFilter').html('<i class="bx bx-filter-alt me-1"></i>' + lbl);
    $('#pplSourceFilter').toggleClass('has-filter', _pplFilter.Source !== '');
    _pplLoad(1);
});

// ── Direction filter ──────────────────────────────────────────────────────
$(document).on('click', '.ppl-dir-opt', function (e) {
    e.preventDefault();
    _pplFilter.Direction = $(this).data('val');
    var lbl = $(this).text();
    $('#pplDirFilter').html('<i class="bx bx-sort-alt-2 me-1"></i>' + lbl);
    $('#pplDirFilter').toggleClass('has-filter', _pplFilter.Direction !== '');
    _pplLoad(1);
});

// ── Pagination ────────────────────────────────────────────────────────────
$(document).on('click', '.pplPagBtn', function (e) {
    e.preventDefault();
    _pplLoad($(this).data('page'));
});

// ── Refresh ───────────────────────────────────────────────────────────────
$(document).on('click', '.PageRefresh', function (e) {
    e.preventDefault();
    _pplLoad(1);
});

// ── Remarks viewer ────────────────────────────────────────────────────────
$(document).on('click', '.ppl-remark-btn', function () {
    $('#pplRemarkText').text($(this).data('remark'));
    $('#pplRemarkModal').modal('show');
});

// ── Manual modal: open for add ────────────────────────────────────────────
$('#btnAddPriceLog').on('click', function () {
    $('#pplManualModalTitle').text('Add Manual Price Entry');
    $('#pplLogUID').val(0);
    $('#pplProductSelect').val(null).trigger('change');
    $('#pplVendorSelect').val(null).trigger('change');
    $('#pplEntryDate').val(flatpickr.formatDate(new Date(), _transFormDateFormat || 'd-m-Y'));
    $('#pplQty, #pplPurchasePrice, #pplSellingPrice').val('');
    $('#pplRemarks').val('');
    $('#pplBackdatedNotice').hide();
    $('#pplRemarksLabel').text('Notes');
    $('#pplRemarks').prop('required', false);
    _pplIsBackdated = false;
    $('#pplManualModal').modal('show');
});

// ── Manual modal: open for edit ───────────────────────────────────────────
$(document).on('click', '.ppl-edit-btn', function () {
    var logUID = $(this).data('log-uid');
    ajaxLoading(0);
    $.ajax({
        url    : '/purchasepricehistory/getEntry/' + logUID,
        method : 'POST',
        data   : { [CsrfName]: CsrfToken },
        success: function (r) {
            ajaxLoading(1);
            if (r.Error) { showToastNotification(r.Message, 'error'); return; }
            var e = r.Entry;
            $('#pplManualModalTitle').text('Edit Manual Price Entry');
            $('#pplLogUID').val(e.PriceLogUID);
            // Populate product select
            if ($('#pplProductSelect').find('option[value="' + e.ProductUID + '"]').length === 0) {
                $('#pplProductSelect').append(new Option(e.ProductName, e.ProductUID, true, true));
            } else {
                $('#pplProductSelect').val(e.ProductUID).trigger('change');
            }
            // Populate vendor select
            if ($('#pplVendorSelect').find('option[value="' + e.VendorUID + '"]').length === 0) {
                $('#pplVendorSelect').append(new Option(e.VendorName, e.VendorUID, true, true));
            } else {
                $('#pplVendorSelect').val(e.VendorUID).trigger('change');
            }
            if (window._pplDatePicker) {
                _pplDatePicker.setDate(e.EntryDate, true, 'Y-m-d');
            }
            $('#pplPurchasePrice').val(e.PurchasePrice);
            $('#pplSellingPrice').val(e.SellingPrice);
            $('#pplQty').val(e.Qty);
            $('#pplRemarks').val(e.Remarks || '');
            $('#pplBackdatedNotice').hide();
            _pplIsBackdated = false;
            $('#pplManualModal').modal('show');
        },
        error: function () { ajaxLoading(1); showToastNotification('Failed to load entry.', 'error'); }
    });
});

// ── Product selected: auto-fill selling price ─────────────────────────────
$('#pplProductSelect').on('select2:select', function () {
    var pid = $(this).val();
    if (!pid) return;
    $.ajax({
        url    : '/purchasepricehistory/getProductInfo',
        method : 'POST',
        data   : { ProductUID: pid, [CsrfName]: CsrfToken },
        success: function (r) {
            if (!r.Error) {
                $('#pplSellingPrice').val(r.SellingPrice);
            }
        }
    });
    _pplCheckBackdated();
});

$('#pplVendorSelect').on('select2:select', function () { _pplCheckBackdated(); });

// ── Date change: check backdated ──────────────────────────────────────────
function _pplCheckBackdated() {
    var pid    = $('#pplProductSelect').val();
    var vid    = $('#pplVendorSelect').val();
    var date   = _pplDatePicker ? _pplDatePicker.input.value : $('#pplEntryDate').val();
    var logUID = parseInt($('#pplLogUID').val()) || 0;
    if (!pid || !vid || !date) return;
    $.ajax({
        url    : '/purchasepricehistory/checkBackdated',
        method : 'POST',
        data   : { ProductUID: pid, VendorUID: vid, EntryDate: date, PriceLogUID: logUID, [CsrfName]: CsrfToken },
        success: function (r) {
            _pplIsBackdated = !r.Error && r.IsBackdated;
            $('#pplBackdatedNotice').toggle(_pplIsBackdated);
            $('#pplRemarksLabel').text(_pplIsBackdated ? 'Notes (required for backdated entry)' : 'Notes');
            $('#pplRemarks').prop('required', _pplIsBackdated);
        }
    });
}

// ── Flatpickr for entry date ──────────────────────────────────────────────
var _pplDatePicker;
$(function () {
    _pplDatePicker = flatpickr('#pplEntryDate', {
        dateFormat   : 'Y-m-d',
        altInput     : true,
        altFormat    : _transFormDateFormat || 'd-m-Y',
        defaultDate  : 'today',
        static       : true,
        position     : 'below left',
        onChange     : function () { _pplCheckBackdated(); },
    });

    // Select2: product  (POST /inventory/searchProducts, returns {Products:[{ProductUID,ItemName}]})
    $('#pplProductSelect').select2({
        placeholder     : 'Search product…',
        allowClear      : true,
        dropdownParent  : $('#pplManualModal'),
        ajax            : {
            url            : '/inventory/searchProducts',
            type           : 'POST',
            delay          : 250,
            data           : function (p) { return { Term: p.term || '', [CsrfName]: CsrfToken }; },
            processResults : function (d) {
                return { results: (d.Products || []).map(function (r) { return { id: r.ProductUID, text: r.ItemName }; }) };
            },
        },
    });

    // Select2: vendor  (GET /transactions/searchVendors?term=, returns {Lists:[{id,text}]})
    $('#pplVendorSelect').select2({
        placeholder     : 'Search vendor…',
        allowClear      : true,
        dropdownParent  : $('#pplManualModal'),
        ajax            : {
            url            : '/transactions/searchVendors',
            type           : 'GET',
            delay          : 250,
            data           : function (p) { return { term: p.term || '' }; },
            processResults : function (d) { return { results: d.Lists || [] }; },
        },
    });
});

// ── Save ──────────────────────────────────────────────────────────────────
$('#pplSaveBtn').on('click', function () {
    var pid = $('#pplProductSelect').val();
    var vid = $('#pplVendorSelect').val();
    var pp  = parseFloat($('#pplPurchasePrice').val());
    var remarks = $.trim($('#pplRemarks').val());

    if (!pid) { showToastNotification('Please select a product.', 'warning'); return; }
    if (!vid) { showToastNotification('Please select a vendor.',  'warning'); return; }
    if (!(pp > 0)) { showToastNotification('Purchase price must be greater than zero.', 'warning'); return; }
    if (_pplIsBackdated && !remarks) { showToastNotification('Notes are required for backdated entries.', 'warning'); $('#pplRemarks').focus(); return; }

    var $btn = $(this);
    $btn.prop('disabled', true);
    $('#pplSaveSpinner').removeClass('d-none');

    $.ajax({
        url    : '/purchasepricehistory/save',
        method : 'POST',
        data   : {
            PriceLogUID   : $('#pplLogUID').val(),
            ProductUID    : pid,
            VendorUID     : vid,
            EntryDate     : _pplDatePicker ? _pplDatePicker.input.value : '',
            PurchasePrice : pp,
            SellingPrice  : parseFloat($('#pplSellingPrice').val()) || 0,
            Qty           : parseFloat($('#pplQty').val()) || 0,
            Remarks       : remarks,
            Filter        : JSON.stringify(_pplFilter),
            [CsrfName]    : CsrfToken,
        },
        success: function (r) {
            $btn.prop('disabled', false);
            $('#pplSaveSpinner').addClass('d-none');
            if (r.Error) {
                if (r.IsBackdated) {
                    _pplIsBackdated = true;
                    $('#pplBackdatedNotice').show();
                    $('#pplRemarksLabel').text('Notes (required for backdated entry)');
                    $('#pplRemarks').prop('required', true).focus();
                } else {
                    showToastNotification(r.Message, 'error');
                }
                return;
            }
            $('#pplManualModal').modal('hide');
            showToastNotification(r.Message, 'success');
            $('#pplTableBody').html(r.RecordHtmlData);
            $('#pplPagination').html(r.Pagination);
            initTooltips();
        },
        error: function () {
            $btn.prop('disabled', false);
            $('#pplSaveSpinner').addClass('d-none');
            showToastNotification('Failed to save entry.', 'error');
        }
    });
});
</script>
<?php $this->load->view('common/footer_desc'); ?>
