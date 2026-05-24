'use strict';

// ── Module source labels for timeline ────────────────────────────────────────
var INV_MODULE_LABELS = {
    103: { label: 'Invoice',         color: '#dc2626' },
    105: { label: 'Purchase',        color: '#16a34a' },
    106: { label: 'Sales Return',    color: '#0891b2' },
    107: { label: 'Credit Note',     color: '#0891b2' },
    108: { label: 'Purchase Return', color: '#d97706' },
    118: { label: 'Manual Adj.',     color: '#7c3aed' },
};

var _invFilter        = {};
var _invTimelineFilter = {};

// ── Export helpers ────────────────────────────────────────────────────────────
function invExport(type) {
    var url = '/inventory/export?Type=' + encodeURIComponent(type);
    if (!$.isEmptyObject(_invFilter)) {
        url += '&Filter=' + encodeURIComponent(JSON.stringify(_invFilter));
    }
    if (type === 'Print') {
        printPreviewRecords(url, function () {});
    } else {
        window.location.href = url;
    }
}

function invExportTimeline(type) {
    var url = '/inventory/exportTimeline?Type=' + encodeURIComponent(type);
    if (typeof _tlFilter !== 'undefined' && !$.isEmptyObject(_tlFilter)) {
        url += '&Filter=' + encodeURIComponent(JSON.stringify(_tlFilter));
    } else if (!$.isEmptyObject(_invTimelineFilter)) {
        url += '&Filter=' + encodeURIComponent(JSON.stringify(_invTimelineFilter));
    }
    if (type === 'Print') {
        printPreviewRecords(url, function () {});
    } else {
        window.location.href = url;
    }
}

// ── Pagination / list refresh ─────────────────────────────────────────────────
function invLoadPage(pageNo) {
    var $wrap = $('#invTableBody');
    $wrap.html('<tr><td colspan="10" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');

    $.ajax({
        url: '/inventory/getPageDetails/' + (pageNo || 1),
        method: 'POST',
        data: {
            RowLimit: 10,
            Filter:   JSON.stringify(_invFilter),
            [CsrfName]: CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
                return;
            }
            $wrap.html(r.RecordHtmlData);
            $('.invPagination').html(r.Pagination);
            if (r.TotalCount !== undefined) {
                var $activeTab = $('.inv-status-tab.active .inv-tab-count');
                $activeTab.text(r.TotalCount).removeClass('d-none');
            }
            // Re-init tooltips on freshly rendered rows
            if (typeof invInitTooltips === 'function') invInitTooltips();
        },
        error: function () {
            $wrap.html('<tr><td colspan="10" class="text-center text-danger py-3">Failed to load data.</td></tr>');
        }
    });
}

// ── Stats refresh ─────────────────────────────────────────────────────────────
function invRefreshStats() {
    $.post('/inventory/getStats', { [CsrfName]: CsrfToken }, function (r) {
        if (r.Error || !r.Stats) return;
        var s = r.Stats;
        $('#statPositiveCount').text(Number(s.positiveCount || 0).toLocaleString() + ' Items');
        $('#statPositiveQty').text(Number(s.positiveQty || 0).toFixed(2) + ' Qty');
        $('#statLowCount').text(Number(s.lowStockCount || 0).toLocaleString() + ' Items');
        $('#statLowQty').text(Number(s.lowStockQty || 0).toFixed(2) + ' Qty');
        $('#statSaleValue').text(InvCurrency + ' ' + Number(s.saleValue || 0).toLocaleString('en-IN', { minimumFractionDigits: InvDecimals, maximumFractionDigits: InvDecimals }));
        $('#statPurchaseValue').text(InvCurrency + ' ' + Number(s.purchaseValue || 0).toLocaleString('en-IN', { minimumFractionDigits: InvDecimals, maximumFractionDigits: InvDecimals }));
    });
}

// ── Stock value auto-calc ─────────────────────────────────────────────────────
function invCalcStockValue(type) {
    var prefix = type === 'in' ? 'si' : 'so';
    var qty   = parseFloat($('#' + prefix + 'Qty').val())   || 0;
    var price = parseFloat($('#' + prefix + 'Price').val()) || 0;
    $('#' + prefix + 'StockValue').val((qty * price).toFixed(2));
}

// ── Open Stock In modal ───────────────────────────────────────────────────────
function invOpenStockIn(uid, name, unit, purchasePrice) {
    $('#siProductUID').val(uid);
    $('#siProductName').text(name);
    $('#siUnitLabel').text(unit || 'PCS');
    $('#siQty').val('');
    $('#siRecordDate').val(new Date().toISOString().split('T')[0]);
    $('#siCategory').val('New');
    $('#siNotes').val('');
    $('#siPrice').val(purchasePrice > 0 ? purchasePrice.toFixed(2) : '');
    $('#siPriceType').val('PurchasePrice');
    invCalcStockValue('in');
    new bootstrap.Modal(document.getElementById('stockInModal')).show();
}

// ── Open Stock Out modal ──────────────────────────────────────────────────────
function invOpenStockOut(uid, name, unit, sellingPrice) {
    $('#soProductUID').val(uid);
    $('#soProductName').text(name);
    $('#soUnitLabel').text(unit || 'PCS');
    $('#soQty').val('');
    $('#soRecordDate').val(new Date().toISOString().split('T')[0]);
    $('#soCategory').val('Miscellaneous');
    $('#soNotes').val('');
    $('#soPrice').val(sellingPrice > 0 ? sellingPrice.toFixed(2) : '');
    $('#soPriceType').val('SellingPrice');
    invCalcStockValue('out');
    new bootstrap.Modal(document.getElementById('stockOutModal')).show();
}

// ── Stock In submit ───────────────────────────────────────────────────────────
function invSubmitStockIn() {
    var qty = parseFloat($('#siQty').val());
    if (!$('#siProductUID').val() || isNaN(qty) || qty <= 0) {
        Swal.fire({ icon: 'warning', text: 'Please enter a valid quantity.' });
        return;
    }

    var $btn = $('#siSubmitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    $.ajax({
        url: '/inventory/stockIn',
        method: 'POST',
        data: {
            ProductUID:   $('#siProductUID').val(),
            Qty:          qty,
            AdjCategory:  $('#siCategory').val(),
            Price:        $('#siPrice').val() || 0,
            PriceType:    $('#siPriceType').val(),
            RecordDate:   $('#siRecordDate').val(),
            Notes:        $('#siNotes').val(),
            [CsrfName]: CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
            } else {
                bootstrap.Modal.getInstance(document.getElementById('stockInModal')).hide();
                Swal.fire({ icon: 'success', title: 'Stock Added', text: r.Message, timer: 1800, showConfirmButton: false });
                invLoadPage(1);
                if (r.Stats) invUpdateStats(r.Stats);
            }
        },
        error: function () {
            Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i>Add Quantity');
        }
    });
}

// ── Stock Out submit ──────────────────────────────────────────────────────────
function invSubmitStockOut() {
    var qty = parseFloat($('#soQty').val());
    if (!$('#soProductUID').val() || isNaN(qty) || qty <= 0) {
        Swal.fire({ icon: 'warning', text: 'Please enter a valid quantity.' });
        return;
    }

    var $btn = $('#soSubmitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    $.ajax({
        url: '/inventory/stockOut',
        method: 'POST',
        data: {
            ProductUID:   $('#soProductUID').val(),
            Qty:          qty,
            AdjCategory:  $('#soCategory').val(),
            Price:        $('#soPrice').val() || 0,
            PriceType:    $('#soPriceType').val(),
            RecordDate:   $('#soRecordDate').val(),
            Notes:        $('#soNotes').val(),
            [CsrfName]: CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
            } else {
                bootstrap.Modal.getInstance(document.getElementById('stockOutModal')).hide();
                Swal.fire({ icon: 'success', title: 'Stock Removed', text: r.Message, timer: 1800, showConfirmButton: false });
                invLoadPage(1);
                if (r.Stats) invUpdateStats(r.Stats);
            }
        },
        error: function () {
            Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-minus me-1"></i>Remove Quantity');
        }
    });
}

// ── Update stat cards from response ──────────────────────────────────────────
function invUpdateStats(s) {
    if (!s) return;
    $('#statPositiveCount').text(Number(s.positiveCount || 0).toLocaleString() + ' Items');
    $('#statPositiveQty').text(Number(s.positiveQty || 0).toFixed(2) + ' Qty');
    $('#statLowCount').text(Number(s.lowStockCount || 0).toLocaleString() + ' Items');
    $('#statLowQty').text(Number(s.lowStockQty || 0).toFixed(2) + ' Qty');
    var fmt = function (v) {
        return InvCurrency + ' ' + Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: InvDecimals, maximumFractionDigits: InvDecimals });
    };
    $('#statSaleValue').text(fmt(s.saleValue));
    $('#statPurchaseValue').text(fmt(s.purchaseValue));
}

// ── Open Timeline modal ───────────────────────────────────────────────────────
function invOpenTimeline(uid, name) {
    $('#tlProductName').text(name);
    $('#tlLoading').removeClass('d-none');
    $('#tlEmpty').addClass('d-none');
    $('#tlTableWrap').addClass('d-none');
    $('#tlTableBody').html('');

    new bootstrap.Modal(document.getElementById('timelineModal')).show();

    $.ajax({
        url: '/inventory/getTimeline',
        method: 'POST',
        data: { ProductUID: uid, [CsrfName]: CsrfToken },
        success: function (r) {
            $('#tlLoading').addClass('d-none');
            if (r.Error || !r.Timeline || !r.Timeline.length) {
                $('#tlEmpty').removeClass('d-none');
                return;
            }
            $('#tlTableWrap').removeClass('d-none');
            invRenderTimeline(r.Timeline);
        },
        error: function () {
            $('#tlLoading').addClass('d-none');
            $('#tlEmpty').removeClass('d-none');
        }
    });
}

// ── Render timeline rows ──────────────────────────────────────────────────────
function invRenderTimeline(rows) {
    var html = '';
    rows.forEach(function (row) {
        var isIN = row.MovementType === 'IN';
        var dirBadge = isIN
            ? '<span class="badge" style="background:#dcfce7;color:#16a34a;font-size:.7rem;">▲ IN</span>'
            : '<span class="badge" style="background:#fee2e2;color:#dc2626;font-size:.7rem;">▼ OUT</span>';

        var moduleUID = parseInt(row.ModuleUID);
        var moduleMeta = INV_MODULE_LABELS[moduleUID] || { label: 'Unknown', color: '#64748b' };
        var sourceLabel = '<span style="color:' + moduleMeta.color + ';font-weight:600;">' + moduleMeta.label + '</span>';

        // Reference: transaction number or manual category
        var ref = '—';
        if (moduleUID === 118) {
            // Manual adjustment
            var catLabel = row.AdjCategory || 'Manual';
            ref = '<span class="badge text-bg-light border" style="font-size:.7rem;">' + escHtml(catLabel) + '</span>';
        } else if (row.TransNumber) {
            ref = '#' + row.TransNumber;
        }

        // Date display
        var dateStr = '—';
        if (moduleUID === 118 && row.AdjDate) {
            dateStr = invFormatDate(row.AdjDate);
        } else if (row.TransDate) {
            dateStr = invFormatDate(row.TransDate);
        } else if (row.MovementDate) {
            dateStr = invFormatDate(row.MovementDate);
        }

        // Notes
        var notes = (moduleUID === 118 && row.AdjNotes) ? escHtml(row.AdjNotes) : '—';

        // Unit cost
        var cost = parseFloat(row.UnitCost || 0);
        var costStr = cost > 0 ? (InvCurrency + ' ' + cost.toFixed(InvDecimals)) : '—';

        html += '<tr style="border-bottom:1px solid #f1f5f9;">'
            + '<td style="padding:.55rem .75rem;white-space:nowrap;">' + dateStr + '</td>'
            + '<td style="padding:.55rem .75rem;">' + sourceLabel + '</td>'
            + '<td style="padding:.55rem .75rem;">' + ref + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:center;">' + dirBadge + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;font-weight:600;' + (isIN ? 'color:#16a34a;' : 'color:#dc2626;') + '">'
            +    (isIN ? '+' : '-') + parseFloat(row.Quantity).toFixed(3)
            + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;">' + costStr + '</td>'
            + '<td style="padding:.55rem .75rem;color:#64748b;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + notes + '">' + notes + '</td>'
            + '</tr>';
    });
    $('#tlTableBody').html(html);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function invFormatDate(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

// ── Category filter box ───────────────────────────────────────────────────────
function invToggleCategoryFilter() {
    var $box = $('#invCategoryFilterBox');
    if ($box.is(':visible')) {
        $box.hide();
        return;
    }
    $('#invStatusFilterBox').hide();
    var btn = document.getElementById('invCategoryFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 60) + 'px',
        display: 'flex',
    });
    $('#invCategorySearch').val('').trigger('input');
}

function invFilterCategoryList(term) {
    var t = term.toLowerCase();
    $('#invCategoryList .catg-list-item').each(function () {
        var name = $(this).find('span').text().toLowerCase();
        $(this).toggle(name.indexOf(t) !== -1);
    });
}

function invToggleAllCategories(main) {
    $('#invCategoryList .inv-category-checkbox:visible').prop('checked', main.checked);
}

function invApplyCategoryFilter() {
    var uids = [];
    $('#invCategoryList .inv-category-checkbox:checked').each(function () {
        uids.push(parseInt($(this).val()));
    });
    if (uids.length) {
        _invFilter['CategoryUID'] = uids;
    } else {
        delete _invFilter['CategoryUID'];
    }
    var $icon = $('#invCatFilterIcon');
    if (uids.length) {
        $icon.css('color', '#0284c7');
    } else {
        $icon.css('color', '');
    }
    $('#invCategoryFilterBox').hide();
    invLoadPage(1);
}

function invResetCategoryFilter() {
    $('#invCategoryList .inv-category-checkbox').prop('checked', false);
    $('#invSelectAllCategories').prop('checked', false);
    delete _invFilter['CategoryUID'];
    $('#invCatFilterIcon').css('color', '');
    $('#invCategoryFilterBox').hide();
    invLoadPage(1);
}

// ── Status filter box ─────────────────────────────────────────────────────────
function invToggleStatusFilter() {
    var $box = $('#invStatusFilterBox');
    if ($box.is(':visible')) {
        $box.hide();
        return;
    }
    $('#invCategoryFilterBox').hide();
    var btn = document.getElementById('invStatusFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 80) + 'px',
        display: 'flex',
    });
    // Pre-select current value
    var cur = _invFilter['StockStatus'] || '';
    $box.find('.inv-status-radio[value="' + cur + '"]').prop('checked', true);
}

function invApplyStatusFilter() {
    var val = $('input.inv-status-radio:checked').val() || '';
    if (val) {
        _invFilter['StockStatus'] = val;
    } else {
        delete _invFilter['StockStatus'];
    }
    // Update icon color and sync status tabs
    if (val) {
        $('#invStatusFilterIcon').css('color', '#0284c7');
        $('.inv-status-tab').removeClass('active');
        $('.inv-status-tab[data-status="' + val + '"]').addClass('active');
    } else {
        $('#invStatusFilterIcon').css('color', '');
        $('.inv-status-tab').removeClass('active');
        $('.inv-status-tab[data-status=""]').addClass('active');
    }
    $('#invStatusFilterBox').hide();
    invLoadPage(1);
}

// ── Item Type filter box ──────────────────────────────────────────────────────
function invToggleProdTypeFilter() {
    var $box = $('#invProdTypeFilterBox');
    if ($box.is(':visible')) {
        $box.hide();
        return;
    }
    $('#invCategoryFilterBox').hide();
    $('#invStatusFilterBox').hide();
    var btn = document.getElementById('invProdTypeFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 40) + 'px',
        display: 'flex',
    });
    // Pre-tick current selections
    var cur = _invFilter['ProductType'] || [];
    $box.find('.inv-prodtype-checkbox').each(function () {
        $(this).prop('checked', cur.indexOf($(this).val()) !== -1);
    });
}

function invApplyProdTypeFilter() {
    var types = [];
    $('.inv-prodtype-checkbox:checked').each(function () {
        types.push($(this).val());
    });
    if (types.length) {
        _invFilter['ProductType'] = types;
    } else {
        delete _invFilter['ProductType'];
    }
    $('#invProdTypeFilterIcon').css('color', types.length ? '#0284c7' : '');
    $('#invProdTypeFilterBox').hide();
    invLoadPage(1);
}

function invResetProdTypeFilter() {
    $('.inv-prodtype-checkbox').prop('checked', false);
    delete _invFilter['ProductType'];
    $('#invProdTypeFilterIcon').css('color', '');
    $('#invProdTypeFilterBox').hide();
    invLoadPage(1);
}

function invResetStatusFilter() {
    $('input.inv-status-radio[value=""]').prop('checked', true);
    delete _invFilter['StockStatus'];
    $('#invStatusFilterIcon').css('color', '');
    $('.inv-status-tab').removeClass('active');
    $('.inv-status-tab[data-status=""]').addClass('active');
    $('#invStatusFilterBox').hide();
    invLoadPage(1);
}

// ── Updated By filter box ─────────────────────────────────────────────────────
var _invOrgUsers = null;

function invToggleUpdatedByFilter() {
    var $box = $('#invUpdatedByFilterBox');
    if ($box.is(':visible')) {
        $box.hide();
        return;
    }
    $('#invCategoryFilterBox').hide();
    $('#invStatusFilterBox').hide();
    $('#invProdTypeFilterBox').hide();
    var btn = document.getElementById('invUpdatedByFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 80) + 'px',
        display: 'flex',
    });
    invRenderUserFilterList();
}

function invRenderUserFilterList() {
    var $list = $('#invUpdatedByList');
    if (_invOrgUsers !== null) {
        invBuildUserCheckboxes($list);
        return;
    }
    $list.html('<div class="text-muted small text-center py-2">Loading...</div>');
    $.ajax({
        url: '/users/getOrgUsers',
        method: 'POST',
        data: { [CsrfName]: CsrfToken },
        success: function (r) {
            if (!r.Error && r.Users) {
                _invOrgUsers = r.Users;
                invBuildUserCheckboxes($list);
            } else {
                $list.html('<div class="text-muted small text-center py-2">No users found.</div>');
            }
        },
        error: function () {
            $list.html('<div class="text-danger small text-center py-2">Failed to load users.</div>');
        }
    });
}

function invBuildUserCheckboxes($list) {
    var cur = _invFilter['UpdatedBy'] || [];
    var html = '';
    $.each(_invOrgUsers, function (i, u) {
        var checked = cur.indexOf(String(u.UserUID)) !== -1 ? ' checked' : '';
        html += '<label class="catg-list-item">' +
            '<input class="form-check-input inv-updatedby-checkbox" type="checkbox" value="' + u.UserUID + '"' + checked + '>' +
            '<span>' + $('<span>').text(u.FullName || (u.FirstName + ' ' + u.LastName)).html() + '</span>' +
            '</label>';
    });
    $list.html(html || '<div class="text-muted small text-center py-2">No users found.</div>');
}

function invApplyUpdatedByFilter() {
    var uids = [];
    $('.inv-updatedby-checkbox:checked').each(function () {
        uids.push($(this).val());
    });
    if (uids.length) {
        _invFilter['UpdatedBy'] = uids;
    } else {
        delete _invFilter['UpdatedBy'];
    }
    $('#invUpdatedByFilterIcon').css('color', uids.length ? '#0284c7' : '');
    $('#invUpdatedByFilterBox').hide();
    invLoadPage(1);
}

function invResetUpdatedByFilter() {
    $('.inv-updatedby-checkbox').prop('checked', false);
    delete _invFilter['UpdatedBy'];
    $('#invUpdatedByFilterIcon').css('color', '');
    $('#invUpdatedByFilterBox').hide();
    invLoadPage(1);
}

// ── Tooltip init (called after every page load) ───────────────────────────────
function invInitTooltips() {
    $('#invTableBody [data-bs-toggle="tooltip"]').each(function () {
        if (!bootstrap.Tooltip.getInstance(this)) {
            new bootstrap.Tooltip(this, { trigger: 'hover', html: false });
        }
    });
}

// ── DOM ready ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    'use strict';

    // Stock In button (per row)
    $(document).on('click', '.invStockInBtn', function () {
        invOpenStockIn(
            $(this).data('uid'),
            $(this).data('name'),
            $(this).data('unit'),
            parseFloat($(this).data('purchase-price')) || 0
        );
    });

    // Stock Out button (per row)
    $(document).on('click', '.invStockOutBtn', function () {
        invOpenStockOut(
            $(this).data('uid'),
            $(this).data('name'),
            $(this).data('unit'),
            parseFloat($(this).data('selling-price')) || 0
        );
    });

    // Timeline button (per row)
    $(document).on('click', '.invTimelineBtn', function () {
        invOpenTimeline($(this).data('uid'), $(this).data('name'));
    });

    // Modal submit buttons
    $('#siSubmitBtn').on('click', invSubmitStockIn);
    $('#soSubmitBtn').on('click', invSubmitStockOut);

    // Column sort
    $(document).on('click', '.inv-sort-th', function () {
        var col = $(this).data('sort');
        if (_invFilter['SortBy'] === col) {
            _invFilter['SortDir'] = (_invFilter['SortDir'] === 'ASC') ? 'DESC' : 'ASC';
        } else {
            _invFilter['SortBy']  = col;
            _invFilter['SortDir'] = 'ASC';
        }
        // Update all sort icons
        $('.inv-sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort');
        var $icon = $('.inv-sort-icon[data-col="' + col + '"]');
        $icon.removeClass('bx-sort').addClass(_invFilter['SortDir'] === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        invLoadPage(1);
    });

    // Status filter tabs
    $(document).on('click', '.inv-status-tab', function () {
        $('.inv-status-tab').removeClass('active');
        $(this).addClass('active');
        var st = $(this).data('status') || '';
        _invFilter['StockStatus'] = st;
        // Sync status filter box icon
        $('#invStatusFilterIcon').css('color', st ? '#0284c7' : '');
        invLoadPage(1);
    });

    // Close filter boxes on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#invCategoryFilterBox, #invCategoryFilterBtn').length) {
            $('#invCategoryFilterBox').hide();
        }
        if (!$(e.target).closest('#invStatusFilterBox, #invStatusFilterBtn').length) {
            $('#invStatusFilterBox').hide();
        }
        if (!$(e.target).closest('#invProdTypeFilterBox, #invProdTypeFilterBtn').length) {
            $('#invProdTypeFilterBox').hide();
        }
        if (!$(e.target).closest('#invUpdatedByFilterBox, #invUpdatedByFilterBtn').length) {
            $('#invUpdatedByFilterBox').hide();
        }
    });

    // Description icon — open modal
    $(document).on('click', '.inv-desc-btn', function () {
        var name = $(this).data('name') || 'Description';
        var desc = $(this).data('desc') || '';
        $('#invDescModalTitle').text(name);
        $('#invDescModalBody').html(desc);
        new bootstrap.Modal(document.getElementById('invDescModal')).show();
    });

    invInitTooltips();

    // Search
    var _searchTimer;
    $('#invSearchInput').on('input', function () {
        clearTimeout(_searchTimer);
        var val = $(this).val().trim();
        _searchTimer = setTimeout(function () {
            _invFilter['Search'] = val;
            invLoadPage(1);
        }, 400);
    });

    // Pagination clicks (delegated)
    $(document).on('click', '.invPagination .page-link', function (e) {
        e.preventDefault();
        var pg = $(this).data('page');
        if (pg) invLoadPage(pg);
    });

    // Refresh button
    $(document).on('click', '.pageRefresh', function () {
        invLoadPage(1);
        invRefreshStats();
    });

});
