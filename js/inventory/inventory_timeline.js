'use strict';

var _tlFilter      = {};
var _tlProdCache   = null;
var _tlItemsLoaded = false;

/* Module labels are shared from inventory.js (INV_MODULE_LABELS) */
var TL_MODULE_TYPE_MAP = {
    103: 'invoice', 105: 'purchase', 106: 'salesreturn',
    107: 'creditnote', 108: 'purchasereturn',
};

/**
 * @param {number} total
 * @param {number} pageNo
 * @param {number} limit
 * @returns {string}
 */

/**
 * @param {Array} rows
 * @returns {string}
 */
function _generateTimelineHtml(rows) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="9"><div class="d-flex flex-column align-items-center py-5">'
            + '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">'
            + '<span class="text-muted">No stock movements found for this period</span></div></td></tr>';
    }
    var html = '';
    rows.forEach(function (row) {
        var moduleUID = parseInt(row.ModuleUID, 10);
        var isIN      = row.MovementType === 'IN';
        var qty       = parseFloat(row.Quantity || 0);
        var cost      = (row.SellingPrice !== null && row.SellingPrice !== undefined)
                       ? parseFloat(row.SellingPrice) : parseFloat(row.UnitCost || 0);
        var modMeta   = INV_MODULE_LABELS[moduleUID] || { label: 'Unknown', color: '#64748b' };
        var txType    = TL_MODULE_TYPE_MAP[moduleUID] || null;
        var txUID     = parseInt(row.TransactionUID || 0, 10);
        var canView   = txType !== null && txUID > 0;

        var effDate = moduleUID === 118 && row.AdjDate
            ? invFormatDate(row.AdjDate)
            : (row.TransDate ? invFormatDate(row.TransDate) : invFormatDate(row.CreatedOn));

        var refNum = moduleUID === 118
            ? (row.AdjUID ? 'ADJ-' + row.AdjUID : '')
            : (row.UniqueNumber || '');

        var createdOnDisplay = '';
        if (row.CreatedOn) {
            var cd = new Date(row.CreatedOn);
            if (!isNaN(cd)) {
                createdOnDisplay = String(cd.getDate()).padStart(2, '0') + '-'
                    + String(cd.getMonth() + 1).padStart(2, '0') + '-'
                    + cd.getFullYear() + ' '
                    + String(cd.getHours()).padStart(2, '0') + ':' + String(cd.getMinutes()).padStart(2, '0');
            }
        }

        // Item cell
        var itemHtml = '<div class="fw-semibold">' + escHtml(row.ItemName) + '</div>';
        if (row.SnapItemName && row.SnapItemName !== row.ItemName) {
            itemHtml += '<div class="text-muted" style="font-size:var(--r2k-text-xs);"><i class="bx bx-history me-1" style="color:#94a3b8;"></i>At movement: ' + escHtml(row.SnapItemName) + '</div>';
        }
        if (row.PartNumber) itemHtml += '<div class="text-muted" style="font-size:var(--r2k-text-70);">Part# ' + escHtml(row.PartNumber) + '</div>';
        itemHtml += '<div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size:var(--r2k-text-sm);">';
        itemHtml += row.ProductType === 'Service'
            ? '<span class="badge bg-label-info" style="font-size:var(--r2k-text-65);">Service</span>'
            : '<span class="badge bg-label-primary" style="font-size:var(--r2k-text-65);">Product</span>';
        if (row.HSNSACCode) itemHtml += '<span class="text-muted" style="font-size:var(--r2k-text-xs);"><strong>HSN:</strong> ' + escHtml(row.HSNSACCode) + '</span>';
        if (row.PartNumber) {
            itemHtml += '<span title="Barcode — ' + escHtml(row.PartNumber) + '" style="cursor:default;line-height:1;"><i class="bx bx-barcode text-primary" style="font-size:1.1rem;vertical-align:middle;"></i></span>'
                + '<span title="QR Code — ' + escHtml(row.PartNumber) + '" style="cursor:default;line-height:1;"><i class="bx bx-qr text-info" style="font-size:.95rem;vertical-align:middle;"></i></span>';
        }
        if (row.Description) {
            var descPlain = row.Description.replace(/<[^>]+>/g, '');
            var descTip   = escHtml(descPlain.length > 160 ? descPlain.substring(0, 160) + '…' : descPlain);
            itemHtml += '<i class="bx bx-info-circle text-warning" style="cursor:pointer;vertical-align:middle;"'
                + ' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + descTip + '" title="' + descTip + '"></i>';
        }
        itemHtml += '</div>';

        // In / Out / Price
        var inHtml = isIN
            ? '<span class="fw-bold" style="color:#16a34a;">' + invSmartQty(qty) + '</span> <span style="font-size:var(--r2k-text-78);color:#6c757d;">' + escHtml(row.UnitName || 'PCS') + '</span>'
            : '<span class="text-muted">—</span>';
        var outHtml = !isIN
            ? '<span class="fw-bold" style="color:#dc2626;">' + invSmartQty(qty) + '</span> <span style="font-size:var(--r2k-text-78);color:#6c757d;">' + escHtml(row.UnitName || 'PCS') + '</span>'
            : '<span class="text-muted">—</span>';
        var priceHtml = cost > 0
            ? '<span style="font-weight:600;">' + TlCurrency + ' ' + parseFloat(cost).toFixed(TlDecimals) + '</span>'
            : '<span class="text-muted">—</span>';

        // Source
        var sourceHtml;
        if (refNum && canView) {
            sourceHtml = '<a href="javascript:void(0);" class="viewTransaction"'
                + ' data-uid="' + txUID + '" data-module="' + moduleUID + '" data-type="' + txType + '"'
                + ' data-number="' + escHtml(refNum) + '" data-date="' + escHtml(row.TransDate || '') + '"'
                + ' data-status="' + escHtml(row.DocStatus || '') + '"'
                + ' style="font-weight:600;color:' + modMeta.color + ';text-decoration:none;" title="Click to view details"'
                + ' onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">'
                + escHtml(refNum) + '</a>';
        } else if (refNum) {
            sourceHtml = '<div style="font-weight:600;color:' + modMeta.color + ';">' + escHtml(refNum) + '</div>';
        } else {
            sourceHtml = '<div style="font-weight:600;color:' + modMeta.color + ';">' + escHtml(modMeta.label) + '</div>';
        }
        sourceHtml += '<div style="font-size:var(--r2k-text-sm);color:#6c757d;">' + escHtml(modMeta.label) + '</div>';

        // Category / Remarks / Date
        var catHtml = row.CategoryName
            ? '<span class="badge text-bg-light border" style="font-size:var(--r2k-text-70);font-weight:500;">' + escHtml(row.CategoryName) + '</span>'
            : '<span class="text-muted">—</span>';
        var remarks = row.Remarks || '';
        var remarksHtml = remarks
            ? '<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escHtml(remarks) + '">' + escHtml(remarks) + '</div>'
            : '<span class="text-muted">—</span>';
        var byName  = (row.CreatedByName || '').trim();
        var dateHtml = '<div>' + effDate + '</div>'
            + (byName ? '<div style="font-size:var(--r2k-text-70);color:#6c757d;">by ' + escHtml(byName) + '</div>' : '');

        // Actions
        var actHtml = '<button class="btn btn-icon btn-sm tlEditAdjBtn"'
            + ' data-ledger-uid="' + (parseInt(row.LedgerUID, 10) || 0) + '"'
            + ' data-product-name="' + escHtml(row.ItemName) + '"'
            + ' data-qty="' + qty + '" data-unit="' + escHtml(row.UnitName || '') + '"'
            + ' data-notes="' + escHtml(remarks) + '" data-created-on="' + escHtml(createdOnDisplay) + '"'
            + ' title="Edit Remarks"><i class="bx bx-edit"></i></button>';

        html += '<tr>'
            + '<td>' + itemHtml + '</td>'
            + '<td style="text-align:right;">' + inHtml + '</td>'
            + '<td style="text-align:right;">' + outHtml + '</td>'
            + '<td style="text-align:right;">' + priceHtml + '</td>'
            + '<td>' + sourceHtml + '</td>'
            + '<td>' + catHtml + '</td>'
            + '<td style="max-width:160px;">' + remarksHtml + '</td>'
            + '<td>' + dateHtml + '</td>'
            + '<td>' + actHtml + '</td>'
            + '</tr>';
    });
    return html;
}

// ── Load page ─────────────────────────────────────────────────────────────────
function tlLoadPage(pageNo) {
    var $wrap = $('#tlTableBody');
    $wrap.html('<tr><td colspan="9"><div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2">'
        + '<div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status"></div>'
        + '<span class="text-muted" style="font-size:var(--r2k-text-82);">Loading movements…</span>'
        + '</div></td></tr>');
    $('#tlPagination').addClass('d-none');
    ajaxLoading(0);

    $.ajax({
        url    : global_services_url + 'api/inventory/timeline/getPageDetails',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : _invSvcParams({
            RowLimit : 10,
            PageNo   : pageNo || 1,
            Filter   : JSON.stringify(_tlFilter),
        }),
        success: function (r) {
            ajaxLoading(1);
            if (r.Error) {
                $wrap.html('<tr><td colspan="9" class="text-center text-danger py-3">' + (r.Message || 'Failed to load.') + '</td></tr>');
                return;
            }
            $wrap.html(_generateTimelineHtml(r.Data));
            var pagHtml = generatePagination(r.TotalCount, parseInt(r.PageNo, 10), parseInt(r.RowLimit, 10));
            if (pagHtml) {
                $('#tlPagination').html(pagHtml).removeClass('d-none');
            } else {
                $('#tlPagination').empty().addClass('d-none');
            }
            if (r.TotalCount !== undefined) {
                $('#tlTotalCount').text(Number(r.TotalCount).toLocaleString() + ' records');
            }
        },
        error: function () {
            ajaxLoading(1);
            $wrap.html('<tr><td colspan="9" class="text-center text-danger py-3">Failed to load data.</td></tr>');
            $('#tlPagination').empty().addClass('d-none');
        }
    });
}

// ── Format date object to Y-m-d ───────────────────────────────────────────────
function tlFmtDate(d) {
    var y   = d.getFullYear();
    var m   = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
}


// ── Product search (cache-first) ──────────────────────────────────────────────
function _tlFetchProdCache(onSuccess, onMiss) {
    if (_tlProdCache !== null) { onSuccess(_tlProdCache); return; }
    if (!UpstashService.isEnabled()) { onMiss(); return; }
    UpstashService.hgetall(UpstashService.orgKey('products')).then(function (map) {
        if (!map) { onMiss(); return; }
        var keys = Object.keys(map || {});
        if (!keys.length) { onMiss(); return; }
        _tlProdCache = keys.map(function (uid) {
            var p = map[uid];
            return { id: parseInt(uid, 10), text: p.ItemName || '', category: p.CategoryName || '' };
        }).sort(function (a, b) { return a.text.localeCompare(b.text); });
        onSuccess(_tlProdCache);
    }).catch(function () { onMiss(); });
}

// ── Item filter box — populate from Upstash cache on first open ───────────────
function _tlLoadItemsIntoBox() {
    if (_tlItemsLoaded) return;
    var $list = $('#tlItemList');
    $list.html('<div class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

    _tlFetchProdCache(
        function (products) {
            _tlItemsLoaded = true;
            if (!products || !products.length) {
                $list.html('<div class="text-center py-3 text-muted" style="font-size:var(--r2k-text-md);">No items found</div>');
                return;
            }
            $list.empty();
            products.forEach(function (p) {
                $list.append(
                    '<label class="catg-list-item">' +
                    '<input class="form-check-input tl-item-chk" type="checkbox" value="' + p.id + '">' +
                    '<span>' + $('<span>').text(p.text).html() + '</span>' +
                    '</label>'
                );
            });
        },
        function () {
            // Upstash miss — fall back to services
            $.ajax({
                url    : global_services_url + 'api/inventory/searchProducts',
                method : 'GET',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : _invSvcParams({ Term: '' }),
                success: function (data) {
                    _tlItemsLoaded = true;
                    $list.empty();
                    if (!data.Error && data.Products && data.Products.length) {
                        data.Products.forEach(function (p) {
                            $list.append(
                                '<label class="catg-list-item">' +
                                '<input class="form-check-input tl-item-chk" type="checkbox" value="' + p.ProductUID + '">' +
                                '<span>' + $('<span>').text(p.ItemName).html() + '</span>' +
                                '</label>'
                            );
                        });
                    } else {
                        $list.html('<div class="text-center py-3 text-muted" style="font-size:var(--r2k-text-md);">No items found</div>');
                    }
                },
                error: function () {
                    $list.html('<div class="text-center py-3 text-danger" style="font-size:var(--r2k-text-md);">Failed to load items</div>');
                }
            });
        }
    );
}

// ── DOM ready ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    'use strict';

    // Set default filter from PHP-injected values (current month)
    _tlFilter['DateFrom'] = TlDefaultDateFrom;
    _tlFilter['DateTo']   = TlDefaultDateTo;

    tlLoadPage(1);

    // Date range changes from the shared date filter button
    $(document).on('r2k:datechange', function (e, data) {
        _tlFilter['DateFrom'] = data.from || '';
        _tlFilter['DateTo']   = data.to   || '';
        tlLoadPage(1);
    });

    // Populate item filter box on first open
    $(document).on('click', '#tlItemFilterBtn', function () {
        _tlLoadItemsIntoBox();
    });

    // Movement type filter
    $('#tlMovementFilter').on('change', function () {
        _tlFilter['MovementType'] = $(this).val();
        tlLoadPage(1);
    });

    // Sortable column headers
    $(document).on('click', '.tl-sortable', function () {
        var col = $(this).data('sort');
        if (_tlFilter['SortBy'] !== col) {
            _tlFilter['SortBy']  = col;
            _tlFilter['SortDir'] = 'ASC';
        } else if (_tlFilter['SortDir'] === 'ASC') {
            _tlFilter['SortDir'] = 'DESC';
        } else {
            delete _tlFilter['SortBy'];
            delete _tlFilter['SortDir'];
        }
        $('.tl-sortable .tl-sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2').css('opacity', '.5');
        if (_tlFilter['SortBy']) {
            var $icon = $(this).find('.tl-sort-icon');
            $icon.removeClass('bx-sort-alt-2').addClass(_tlFilter['SortDir'] === 'ASC' ? 'bx-sort-up' : 'bx-sort-down').addClass('text-primary').css('opacity', '1');
        }
        tlLoadPage(1);
    });

    // Pagination
    $(document).on('click', '#tlPagination .r2k-pag-btn', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (pg && !$(this).hasClass('disabled')) tlLoadPage(pg);
    });

    // Refresh
    $(document).on('click', '.pageRefresh', function () {
        tlLoadPage(1);
    });

    // Edit remarks — all rows
    $(document).on('click', '.tlEditAdjBtn', function () {
        var $btn      = $(this);
        var ledgerUID = $btn.data('ledger-uid');
        var name      = $btn.data('product-name');
        var qty       = parseFloat($btn.data('qty')) || 0;
        var unit      = $btn.data('unit')       || '';
        var notes     = $btn.data('notes')      || '';
        var createdOn = $btn.data('created-on') || '';

        $('#tlEditLedgerUID').val(ledgerUID);
        $('#tlEditAdjProductName').text(name + (unit ? ' · ' + unit : ''));
        $('#tlEditAdjNotes').val(notes);
        $('#tlEditAdjQtyDisplay').val(qty > 0 ? qty + (unit ? ' ' + unit : '') : '');
        $('#tlEditAdjDateDisplay').val(createdOn);

        new bootstrap.Modal(document.getElementById('tlEditAdjModal')).show();
    });

    // Save — only Remarks is sent
    $('#tlEditAdjSaveBtn').on('click', function () {
        var ledgerUID = $('#tlEditLedgerUID').val();
        if (!ledgerUID) {
            Swal.fire({ icon: 'warning', text: 'Invalid record.' });
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url        : global_services_url + 'api/inventory/updateLedgerRemarks',
            method     : 'POST',
            headers    : { Authorization: 'Bearer ' + JwtToken },
            contentType: 'application/json',
            data       : JSON.stringify(_invSvcParams({
                LedgerUID: parseInt(ledgerUID, 10),
                Remarks  : $('#tlEditAdjNotes').val(),
            })),
            success: function (r) {
                if (r.Error) {
                    Swal.fire({ icon: 'error', text: r.Message });
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('tlEditAdjModal')).hide();
                    Swal.fire({ icon: 'success', title: 'Updated', text: r.Message, timer: 1800, showConfirmButton: false });
                    tlLoadPage(1);
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-edit me-1"></i>Save Changes');
            }
        });
    });

});
