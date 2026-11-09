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

var _invFilter = {};

var _invCfbConfig = {
    checkClass : 'inv-category-checkbox',
    applyFn    : 'invApplyCategoryFilter',
    resetFn    : 'invResetCategoryFilter',
    uid        : 'inventory'
};

var _invItemCfgConfig = {
    checkClass : 'inv-item-checkbox',
    applyFn    : 'invApplyItemFilter',
    resetFn    : 'invResetItemFilter',
    uid        : 'inventory'
};
var _invTimelineFilter = {};
var _siDateFp = null; // flatpickr instance for Stock In record date
var _soDateFp = null; // flatpickr instance for Stock Out record date

// ── Services auth params ───────────────────────────────────────────────────────
/**
 * @param {Object} [extra]
 * @returns {Object}
 */
function _invSvcParams(extra) {
    return $.extend({
        orgId    : parseInt(JwtData.Org.OrgUID, 10),
        userId   : parseInt(JwtData.User.UserUID, 10),
        branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
    }, extra || {});
}

/**
 * @param {number} total
 * @param {number} pageNo
 * @param {number} limit
 * @returns {string}
 */

/**
 * @param {Array}  rows
 * @param {number} serialStart
 * @returns {string}
 */
function _generateInventoryHtml(rows, serialStart) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="10"><div class="d-flex flex-column align-items-center py-5">'
            + '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">'
            + '<span class="text-muted">No inventory items found</span></div></td></tr>';
    }
    var html = '';
    var sno  = serialStart || 0;
    rows.forEach(function (row) {
        sno++;
        var qty       = parseFloat(row.AvailableQuantity || 0);
        var threshold = parseFloat(row.LowStockAlertAt   || 0);
        var statusClass, statusIcon, statusLabel, qtyColor;
        if (qty <= 0) {
            statusClass = 'trans-badge-Cancelled'; statusIcon = 'bx-x-circle';    statusLabel = 'Out of Stock';
        } else if (qty <= threshold) {
            statusClass = 'trans-badge-Pending';   statusIcon = 'bx-error-circle'; statusLabel = 'Low Stock';
        } else {
            statusClass = 'trans-badge-Paid';      statusIcon = 'bx-check-circle'; statusLabel = 'In Stock';
        }
        qtyColor = qty <= 0 ? 'color:#dc2626;' : (qty <= threshold ? 'color:#d97706;' : 'color:#16a34a;');

        var snoTd = '<td class="' + (InvShowSerial ? '' : 'd-none') + ' table-serialno" style="width:44px;">'
            + '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>';

        var itemHtml = '<div class="fw-semibold">' + escHtml(row.ItemName) + '</div>';
        if (row.PartNumber) itemHtml += '<div class="text-muted" style="font-size:var(--r2k-text-70);">Part# ' + escHtml(row.PartNumber) + '</div>';
        itemHtml += '<div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size:var(--r2k-text-sm);">';
        itemHtml += row.ProductType === 'Service'
            ? '<span class="badge bg-label-info" style="font-size:var(--r2k-text-65);">Service</span>'
            : '<span class="badge bg-label-primary" style="font-size:var(--r2k-text-65);">Product</span>';
        if (row.HSNSACCode) itemHtml += '<span class="text-muted" style="font-size:var(--r2k-text-xs);">HSN: ' + escHtml(row.HSNSACCode) + '</span>';
        if (row.PartNumber) {
            itemHtml += '<span title="Barcode — ' + escHtml(row.PartNumber) + '" style="cursor:default;line-height:1;"><i class="bx bx-barcode text-primary" style="font-size:1.1rem;vertical-align:middle;"></i></span>'
                + '<span title="QR Code — ' + escHtml(row.PartNumber) + '" style="cursor:default;line-height:1;"><i class="bx bx-qr text-info" style="font-size:.95rem;vertical-align:middle;"></i></span>';
        }
        if (row.Description) {
            var descPlain = row.Description.replace(/<[^>]+>/g, '');
            var descTip   = escHtml(descPlain.length > 160 ? descPlain.substring(0, 160) + '…' : descPlain);
            itemHtml += '<i class="bx bx-info-circle text-warning inv-desc-btn"'
                + ' style="font-size:.95rem;cursor:pointer;vertical-align:middle;"'
                + ' data-name="' + escHtml(row.ItemName) + '" data-desc="' + escHtml(row.Description) + '"'
                + ' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + descTip + '" title="' + descTip + '"></i>';
        }
        itemHtml += '</div>';

        var catHtml = row.CategoryName
            ? '<span class="badge text-bg-light border" style="font-size:var(--r2k-text-70);font-weight:500;">' + escHtml(row.CategoryName) + '</span>'
            : '<span class="text-muted">—</span>';

        var qtyHtml = '<div class="fw-semibold" style="' + qtyColor + '">'
            + invSmartQty(qty)
            + (row.UnitName ? ' <span style="font-size:var(--r2k-text-sm);font-weight:400;color:#6c757d;">' + escHtml(row.UnitName) + '</span>' : '')
            + '</div>';
        if (threshold > 0) qtyHtml += '<div class="text-muted" style="font-size:var(--r2k-text-xs);">Alert at ' + invSmartQty(threshold) + '</div>';

        var statusHtml = '<span class="trans-badge ' + statusClass + '"><i class="bx ' + statusIcon + '" style="font-size:var(--r2k-text-md);"></i> ' + statusLabel + '</span>';

        var purchHtml = '<div>' + InvCurrency + ' ' + parseFloat(row.PurchasePrice || 0).toFixed(InvDecimals) + '</div>';
        if (parseFloat(row.TaxPercentage || 0) > 0) purchHtml += '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + row.TaxPercentage + '% tax</div>';

        var saleHtml = '<div class="trans-amount-main">' + InvCurrency + ' ' + parseFloat(row.SellingPrice || 0).toFixed(InvDecimals) + '</div>';
        if (parseFloat(row.TaxPercentage || 0) > 0) saleHtml += '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + row.TaxPercentage + '% tax</div>';

        var dt = _invFmtDt(row.UpdatedOn);
        var updHtml = '<div class="r2k-col-date">' + dt.formatted + '</div>';
        if (dt.ago) updHtml += '<div class="r2k-col-date-ago">' + dt.ago + '</div>';
        if (row.UpdatedByName) updHtml += '<div class="text-muted r2k-col-date-by">by ' + escHtml(row.UpdatedByName) + '</div>';

        var actHtml = '<div class="d-flex align-items-center justify-content-end gap-1">'
            + '<button class="btn btn-sm btn-success invStockInBtn px-2 py-1"'
            + ' data-uid="' + (row.ProductUID | 0) + '" data-name="' + escHtml(row.ItemName) + '"'
            + ' data-unit="' + escHtml(row.UnitName || '') + '" data-purchase-price="' + parseFloat(row.PurchasePrice || 0) + '"'
            + ' title="Stock In" style="font-size:var(--r2k-text-75);"><i class="bx bx-plus me-1"></i>In</button>'
            + '<button class="btn btn-sm btn-outline-danger invStockOutBtn px-2 py-1"'
            + ' data-uid="' + (row.ProductUID | 0) + '" data-name="' + escHtml(row.ItemName) + '"'
            + ' data-unit="' + escHtml(row.UnitName || '') + '" data-selling-price="' + parseFloat(row.SellingPrice || 0) + '"'
            + ' title="Stock Out" style="font-size:var(--r2k-text-75);"><i class="bx bx-minus me-1"></i>Out</button>';
        if (parseInt(row.IsBrandApplicable || 0) === 1) {
            actHtml += '<button class="btn btn-icon btn-sm text-secondary invVariantStockBtn"'
                + ' data-uid="' + (row.ProductUID | 0) + '" data-name="' + escHtml(row.ItemName) + '"'
                + ' title="View Variant Stock"><i class="bx bx-layer"></i></button>';
        }
        actHtml += '<button class="btn btn-icon btn-sm text-primary invTimelineBtn"'
            + ' data-uid="' + (row.ProductUID | 0) + '" data-name="' + escHtml(row.ItemName) + '"'
            + ' title="View History"><i class="bx bx-history"></i></button>'
            + '</div>';

        html += '<tr>'
            + snoTd
            + '<td>' + itemHtml + '</td>'
            + '<td>' + catHtml + '</td>'
            + '<td>' + qtyHtml + '</td>'
            + '<td>' + statusHtml + '</td>'
            + '<td>' + purchHtml + '</td>'
            + '<td>' + saleHtml + '</td>'
            + '<td>' + updHtml + '</td>'
            + '<td style="width:130px;text-align:right;">' + actHtml + '</td>'
            + '</tr>';

        if (parseInt(row.IsBrandApplicable || 0) === 1) {
            html += '<tr class="inv-variant-row d-none" data-product-uid="' + (row.ProductUID | 0) + '">'
                + '<td colspan="10" class="p-0"><div class="inv-variant-panel px-4 py-3"></div></td>'
                + '</tr>';
        }
    });
    return html;
}

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
    $wrap.html('<tr><td colspan="10"><div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2">'
        + '<div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status"></div>'
        + '<span class="text-muted" style="font-size:var(--r2k-text-82);">Loading inventory…</span>'
        + '</div></td></tr>');
    $('#invPagination').addClass('d-none');
    ajaxLoading(0);

    $.ajax({
        url: global_services_url + 'api/inventory/getPageDetails',
        method: 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data: _invSvcParams({
            RowLimit : 10,
            PageNo   : pageNo || 1,
            Filter   : JSON.stringify(_invFilter),
        }),
        success: function (r) {
            ajaxLoading(1);
            if (r.Error) {
                $wrap.html('<tr><td colspan="10" class="text-center text-danger py-3">' + (r.Message || 'Failed to load data.') + '</td></tr>');
                return;
            }
            var serialStart = (parseInt(r.PageNo, 10) - 1) * parseInt(r.RowLimit, 10);
            $wrap.html(_generateInventoryHtml(r.Data, serialStart));
            var pagHtml = generatePagination(r.TotalCount, parseInt(r.PageNo, 10), parseInt(r.RowLimit, 10));
            if (pagHtml) {
                $('#invPagination').html(pagHtml).removeClass('d-none');
            } else {
                $('#invPagination').empty().addClass('d-none');
            }
            if (r.TotalCount !== undefined) {
                $('.inv-status-tab.active .inv-tab-count').text(r.TotalCount).removeClass('d-none');
            }
            if (typeof invInitTooltips === 'function') invInitTooltips();
        },
        error: function () {
            ajaxLoading(1);
            $wrap.html('<tr><td colspan="10" class="text-center text-danger py-3">Failed to load data.</td></tr>');
            $('#invPagination').empty().addClass('d-none');
        }
    });
}

// ── Stats refresh ─────────────────────────────────────────────────────────────
function invRefreshStats() {
    $.ajax({
        url    : global_services_url + 'api/inventory/getStats',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : _invSvcParams(),
        success: function (r) {
            if (r.Error || !r.Stats) return;
            invUpdateStats(r.Stats);
        }
    });
}

// ── Stock value auto-calc ─────────────────────────────────────────────────────
function invCalcStockValue(type) {
    var prefix = type === 'in' ? 'si' : 'so';
    var qty   = parseFloat($('#' + prefix + 'Qty').val())   || 0;
    var price = parseFloat($('#' + prefix + 'Price').val()) || 0;
    $('#' + prefix + 'StockValue').val((qty * price).toFixed(InvDecimals));
}

// ── Open Stock In modal ───────────────────────────────────────────────────────
function invOpenStockIn(uid, name, unit, purchasePrice) {
    $('#siProductUID').val(uid);
    $('#siProductName').text(name);
    $('#siUnitLabel').text(unit || 'PCS');
    $('#siQty').val('');
    $('#siCategory').val('New');
    $('#siNotes').val('');
    $('#siPrice').val(purchasePrice > 0 ? purchasePrice.toFixed(InvDecimals) : '');
    $('#siPriceType').val('PurchasePrice');
    invCalcStockValue('in');

    new bootstrap.Modal(document.getElementById('stockInModal')).show();

    // Init flatpickr after modal is fully visible so position calculates correctly
    $('#stockInModal').one('shown.bs.modal', function () {
        if (_siDateFp) { _siDateFp.destroy(); _siDateFp = null; }
        _siDateFp = flatpickr('#siRecordDate', {
            dateFormat:  'Y-m-d',
            altInput:    true,
            altFormat:   (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y',
            defaultDate: new Date(),
            allowInput:  false,
            appendTo:    document.body,
        });
    });
}

// ── Open Stock Out modal ──────────────────────────────────────────────────────
function invOpenStockOut(uid, name, unit, sellingPrice) {
    $('#soProductUID').val(uid);
    $('#soProductName').text(name);
    $('#soUnitLabel').text(unit || 'PCS');
    $('#soQty').val('');
    $('#soCategory').val('Miscellaneous');
    $('#soNotes').val('');
    $('#soPrice').val(sellingPrice > 0 ? sellingPrice.toFixed(InvDecimals) : '');
    $('#soPriceType').val('SellingPrice');
    invCalcStockValue('out');

    new bootstrap.Modal(document.getElementById('stockOutModal')).show();

    // Init flatpickr after modal is fully visible so position calculates correctly
    $('#stockOutModal').one('shown.bs.modal', function () {
        if (_soDateFp) { _soDateFp.destroy(); _soDateFp = null; }
        _soDateFp = flatpickr('#soRecordDate', {
            dateFormat:  'Y-m-d',
            altInput:    true,
            altFormat:   (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y',
            defaultDate: new Date(),
            allowInput:  false,
            appendTo:    document.body,
        });
    });
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
        url        : global_services_url + 'api/inventory/stockIn',
        method     : 'POST',
        headers    : { Authorization: 'Bearer ' + JwtToken },
        contentType: 'application/json',
        data       : JSON.stringify(_invSvcParams({
            ProductUID :  parseInt($('#siProductUID').val(), 10),
            Qty        :  qty,
            AdjCategory:  $('#siCategory').val(),
            Price      :  parseFloat($('#siPrice').val()) || 0,
            PriceType  :  $('#siPriceType').val(),
            RecordDate :  $('#siRecordDate').val(),
            Notes      :  $('#siNotes').val(),
        })),
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
        url        : global_services_url + 'api/inventory/stockOut',
        method     : 'POST',
        headers    : { Authorization: 'Bearer ' + JwtToken },
        contentType: 'application/json',
        data       : JSON.stringify(_invSvcParams({
            ProductUID :  parseInt($('#soProductUID').val(), 10),
            Qty        :  qty,
            AdjCategory:  $('#soCategory').val(),
            Price      :  parseFloat($('#soPrice').val()) || 0,
            PriceType  :  $('#soPriceType').val(),
            RecordDate :  $('#soRecordDate').val(),
            Notes      :  $('#soNotes').val(),
        })),
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

    ajaxLoading(0);

    $.ajax({
        url    : global_services_url + 'api/inventory/getTimeline',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : _invSvcParams({ ProductUID: uid }),
        success: function (r) {
            ajaxLoading(1);
            $('#tlLoading').addClass('d-none');
            if (r.Error || !r.Timeline || !r.Timeline.length) {
                $('#tlEmpty').removeClass('d-none');
                return;
            }
            $('#tlTableWrap').removeClass('d-none');
            invRenderTimeline(r.Timeline);
        },
        error: function () {
            ajaxLoading(1);
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
            ? '<span class="badge" style="background:#dcfce7;color:#16a34a;font-size:var(--r2k-text-70);">▲ IN</span>'
            : '<span class="badge" style="background:#fee2e2;color:#dc2626;font-size:var(--r2k-text-70);">▼ OUT</span>';

        var moduleUID = parseInt(row.ModuleUID);
        var moduleMeta = INV_MODULE_LABELS[moduleUID] || { label: 'Unknown', color: '#64748b' };
        var sourceLabel = '<span style="color:' + moduleMeta.color + ';font-weight:600;">' + moduleMeta.label + '</span>';

        // Reference: full formatted number or manual adjustment category
        var ref = '—';
        if (moduleUID === 118) {
            var adjLabel = row.AdjUID ? 'ADJ-' + row.AdjUID : (row.AdjCategory || 'Manual');
            ref = '<span class="badge text-bg-light border" style="font-size:var(--r2k-text-70);">' + escHtml(adjLabel) + '</span>';
        } else if (row.UniqueNumber) {
            ref = escHtml(row.UniqueNumber);
        } else if (row.TransNumber) {
            ref = '#' + row.TransNumber;
        }

        var dateStr = row.EffectiveDate ? invFormatDate(row.EffectiveDate) : '—';

        // Notes — from StockLedgerTbl.Remarks (all modules), fallback sa.Notes already handled server-side
        var notes = row.Remarks ? escHtml(row.Remarks) : '—';

        // UnitCost = purchase value including tax; TaxAmount = tax portion within that
        var unitCost = parseFloat(row.UnitCost  || 0);
        var taxAmt   = parseFloat(row.TaxAmount || 0);

        // Cost Price  : base cost without tax  (UnitCost − TaxAmount)
        var costBase    = unitCost - taxAmt;
        var costBaseStr = unitCost > 0 ? (InvCurrency + ' ' + costBase.toFixed(InvDecimals)) : '—';

        // Tax Amount  : tax portion only
        var taxStr      = taxAmt > 0 ? (InvCurrency + ' ' + taxAmt.toFixed(InvDecimals)) : '—';

        // Purchase Price : full purchase value with tax  (UnitCost as stored)
        var purchaseStr = unitCost > 0 ? (InvCurrency + ' ' + unitCost.toFixed(InvDecimals)) : '—';

        // Selling Price  : sold value with tax
        var sell        = parseFloat(row.SellingPrice || 0);
        var sellStr     = sell > 0 ? (InvCurrency + ' ' + sell.toFixed(InvDecimals)) : '—';

        html += '<tr style="border-bottom:1px solid #f1f5f9;">'
            + '<td style="padding:.55rem .75rem;white-space:nowrap;">' + dateStr + '</td>'
            + '<td style="padding:.55rem .75rem;">' + sourceLabel + '</td>'
            + '<td style="padding:.55rem .75rem;">' + ref + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:center;">' + dirBadge + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;font-weight:600;' + (isIN ? 'color:#16a34a;' : 'color:#dc2626;') + '">'
            +    (isIN ? '+' : '-') + invSmartQty(row.Quantity)
            + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;">' + costBaseStr + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;">' + taxStr + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;">' + purchaseStr + '</td>'
            + '<td style="padding:.55rem .75rem;text-align:right;">' + sellStr + '</td>'
            + '<td style="padding:.55rem .75rem;color:#64748b;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + notes + '">' + notes + '</td>'
            + '</tr>';
    });
    $('#tlTableBody').html(html);
}

// ── Variant stock breakdown renderer ─────────────────────────────────────────

/**
 * Build an inline table HTML string for variant stock rows.
 * @param {Array} variants  Array from /inventory/getVariantStock
 * @returns {string}
 */
function invRenderVariantStock(variants) {
    var html = '<div style="font-size:var(--r2k-text-78);padding:.25rem 0;">'
        + '<table class="table table-sm mb-0" style="font-size:var(--r2k-text-78);">'
        + '<thead class="table-light"><tr>'
        + '<th style="font-weight:600;">Brand</th>'
        + '<th style="font-weight:600;">Size</th>'
        + '<th style="font-weight:600;">Part #</th>'
        + '<th style="font-weight:600;text-align:right;">Purchase Price</th>'
        + '<th style="font-weight:600;text-align:right;">Selling Price</th>'
        + '<th style="font-weight:600;text-align:right;">Stock</th>'
        + '</tr></thead><tbody>';

    variants.forEach(function (v) {
        var qty    = parseFloat(v.AvailableQty || 0);
        var qtyClr = qty <= 0 ? 'color:#dc2626;' : (qty < 5 ? 'color:#d97706;' : 'color:#16a34a;');
        html += '<tr>'
            + '<td>' + escHtml(v.BrandName || '—') + '</td>'
            + '<td>' + escHtml(v.SizeName  || '—') + '</td>'
            + '<td>' + escHtml(v.PartNumber || '—') + '</td>'
            + '<td style="text-align:right;">' + (InvCurrency + ' ' + parseFloat(v.PurchasePrice || 0).toFixed(InvDecimals)) + '</td>'
            + '<td style="text-align:right;">' + (InvCurrency + ' ' + parseFloat(v.SellingPrice  || 0).toFixed(InvDecimals)) + '</td>'
            + '<td style="text-align:right;font-weight:600;' + qtyClr + '">' + invSmartQty(qty) + '</td>'
            + '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
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

/**
 * @param {string} dateStr
 * @returns {{formatted: string, ago: string}}
 */
function _invFmtDt(dateStr) {
    if (!dateStr) return { formatted: '—', ago: '' };
    var d = new Date(dateStr);
    if (isNaN(d)) return { formatted: '—', ago: '' };
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var formatted = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear()
        + ', ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    var ago = timeAgo(dateStr);
    return { formatted: formatted, ago: ago };
}

// Returns qty without trailing zeros: 1.000→"1", 1.010→"1.01", 1.001→"1.001"
function invSmartQty(val) {
    var n = parseFloat(val || 0);
    return isNaN(n) ? '0' : parseFloat(n.toFixed(3)).toString();
}

// ── Category filter box ───────────────────────────────────────────────────────
function invApplyCategoryFilter() {
    var uids = [];
    $('#invCategoryFilterBox .inv-category-checkbox:checked').each(function () {
        uids.push(parseInt($(this).val()));
    });
    if (uids.length) { _invFilter['CategoryUID'] = uids; } else { delete _invFilter['CategoryUID']; }
    $('#invCatFilterIcon').css('color', uids.length ? '#0284c7' : '');
    $('#invCategoryFilterBox').hide();
    invLoadPage(1);
}

function invResetCategoryFilter() {
    $('#invCategoryFilterBox .inv-category-checkbox').prop('checked', false);
    $('#invCategoryFilterBox .ca-sel-all').prop('checked', false);
    $('#invCategoryFilterBox .ca-sel-all-label').text('Select All');
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
    $('#invCategoryFilterBox, #invItemFilterBox').hide();
    $('.trans-col-filterbox').hide();
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


function invResetStatusFilter() {
    $('input.inv-status-radio[value=""]').prop('checked', true);
    delete _invFilter['StockStatus'];
    $('#invStatusFilterIcon').css('color', '');
    $('.inv-status-tab').removeClass('active');
    $('.inv-status-tab[data-status=""]').addClass('active');
    $('#invStatusFilterBox').hide();
    invLoadPage(1);
}

// ── Item filter box ───────────────────────────────────────────────────────────
function invApplyItemFilter() {
    var uids = [];
    $('.inv-item-checkbox:checked').each(function () {
        uids.push(parseInt($(this).val(), 10));
    });
    if (uids.length) {
        _invFilter.ProductUID = uids;
    } else {
        delete _invFilter.ProductUID;
    }
    $('#invItemFilterIcon').css('color', uids.length ? '#0284c7' : '');
    $('#invItemFilterBox').hide();
    invLoadPage(1);
}

function invResetItemFilter() {
    $('.inv-item-checkbox').prop('checked', false);
    delete _invFilter.ProductUID;
    $('#invItemFilterIcon').css('color', '');
    $('#invItemFilterBox').hide();
    invLoadPage(1);
}

// ── Tooltip init (called after every page load) ───────────────────────────────
function invInitTooltips() {
    // MutationObserver in default.js handles auto-init; this is a safety call.
    $('#invTableBody [data-bs-toggle="tooltip"]').each(function () {
        if (!bootstrap.Tooltip.getInstance(this)) {
            new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
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

    // Variant stock breakdown button (per row)
    $(document).on('click', '.invVariantStockBtn', function () {
        var uid  = $(this).data('uid');
        var $row = $(this).closest('tr').next('.inv-variant-row[data-product-uid="' + uid + '"]');
        if (!$row.length) return;

        if (!$row.hasClass('d-none')) {
            $row.addClass('d-none');
            $(this).find('i').removeClass('text-primary');
            return;
        }

        var $panel = $row.find('.inv-variant-panel');
        $panel.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-secondary"></div></div>');
        $row.removeClass('d-none');
        $(this).find('i').addClass('text-primary');

        $.ajax({
            url    : global_services_url + 'api/inventory/getVariantStock',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _invSvcParams({ ProductUID: uid }),
            success: function (r) {
                if (r.Error || !r.Variants || !r.Variants.length) {
                    $panel.html('<div class="text-muted py-2" style="font-size:var(--r2k-text-md);">No variant stock data found.</div>');
                    return;
                }
                $panel.html(invRenderVariantStock(r.Variants));
            },
            error: function () {
                $panel.html('<div class="text-danger py-2" style="font-size:var(--r2k-text-md);">Failed to load variant stock.</div>');
            }
        });
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
        var $th = $(this);
        if (_invFilter['SortBy'] !== col) {
            _invFilter['SortBy']  = col;
            _invFilter['SortDir'] = 'ASC';
        } else if (_invFilter['SortDir'] === 'ASC') {
            _invFilter['SortDir'] = 'DESC';
        } else {
            delete _invFilter['SortBy'];
            delete _invFilter['SortDir'];
        }
        // Reset all headers to neutral
        $('.inv-sort-th').each(function () {
            $(this).attr('data-bs-title', 'Click for ascending order');
            var tt = bootstrap.Tooltip.getInstance(this);
            if (tt) { tt.dispose(); new bootstrap.Tooltip(this); }
        });
        $('.inv-sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
        // Update active column
        if (_invFilter['SortBy']) {
            var icon    = _invFilter['SortDir'] === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
            var tipText = _invFilter['SortDir'] === 'ASC' ? 'Click for descending order' : 'Click to remove sorting';
            $('.inv-sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon + ' text-primary');
            $th.attr('data-bs-title', tipText);
            var tt = bootstrap.Tooltip.getInstance($th[0]);
            if (tt) { tt.dispose(); new bootstrap.Tooltip($th[0]); }
        }
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
        }, 1500);
    });

    // Pagination clicks (delegated)
    $(document).on('click', '.invPagination .r2k-pag-btn', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (pg && !$(this).hasClass('disabled')) invLoadPage(pg);
    });

    // Refresh button — only run on the inventory page (guard against timeline page)
    $(document).on('click', '.pageRefresh', function () {
        if (!$('#invTable').length) return;
        invLoadPage(1);
        invRefreshStats();
    });

});
