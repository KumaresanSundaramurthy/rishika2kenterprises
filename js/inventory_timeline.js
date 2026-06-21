'use strict';

var _tlFilter      = {};
var _tlProdCache   = null;
var _tlItemsLoaded = false;

// ── Load page ─────────────────────────────────────────────────────────────────
function tlLoadPage(pageNo) {
    var $wrap = $('#tlTableBody');
    $wrap.html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');

    $.ajax({
        url: '/inventory/timeline/getPageDetails/' + (pageNo || 1),
        method: 'POST',
        data: {
            RowLimit: 10,
            Filter:   JSON.stringify(_tlFilter),
            [CsrfName]: CsrfToken,
        },
        success: function (r) {
            if (r.Error) {
                Swal.fire({ icon: 'error', text: r.Message });
                return;
            }
            $wrap.html(r.RecordHtmlData);
            $('.tlPagination').html(r.Pagination);
            if (r.TotalCount !== undefined) {
                $('#tlTotalCount').text(Number(r.TotalCount).toLocaleString() + ' records');
            }
        },
        error: function () {
            $wrap.html('<tr><td colspan="9" class="text-center text-danger py-3">Failed to load data.</td></tr>');
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
                $list.html('<div class="text-center py-3 text-muted" style="font-size:.8rem;">No items found</div>');
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
            // Upstash miss — fall back to AJAX
            $.ajax({
                url : '/inventory/searchProducts',
                type: 'POST',
                data: { Term: '', [CsrfName]: CsrfToken },
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
                        $list.html('<div class="text-center py-3 text-muted" style="font-size:.8rem;">No items found</div>');
                    }
                },
                error: function () {
                    $list.html('<div class="text-center py-3 text-danger" style="font-size:.8rem;">Failed to load items</div>');
                }
            });
        }
    );
}

// ── DOM ready ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    'use strict';

    // Set default filter from PHP-injected values
    _tlFilter['DateFrom'] = TlDefaultDateFrom;
    _tlFilter['DateTo']   = TlDefaultDateTo;

    var _tlDateFmt = (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y';

    // Date range picker — two months side by side, appended to body to avoid clipping
    flatpickr('#tlDateRange', {
        mode:        'range',
        showMonths:  2,
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   _tlDateFmt,
        defaultDate: [TlDefaultDateFrom, TlDefaultDateTo],
        appendTo:    document.body,
        onChange: function (dates) {
            if (dates.length === 2) {
                _tlFilter['DateFrom'] = tlFmtDate(dates[0]);
                _tlFilter['DateTo']   = tlFmtDate(dates[1]);
                tlLoadPage(1);
            }
        }
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
        if (_tlFilter['SortBy'] === col) {
            _tlFilter['SortDir'] = (_tlFilter['SortDir'] === 'ASC') ? 'DESC' : 'ASC';
        } else {
            _tlFilter['SortBy']  = col;
            _tlFilter['SortDir'] = 'ASC';
        }
        $('.tl-sortable .tl-sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort').css('opacity', '.5');
        var $icon = $(this).find('.tl-sort-icon');
        $icon.removeClass('bx-sort').addClass(_tlFilter['SortDir'] === 'ASC' ? 'bx-sort-up' : 'bx-sort-down').css('opacity', '1');
        tlLoadPage(1);
    });

    // Pagination
    $(document).on('click', '.tlPagination .page-link', function (e) {
        e.preventDefault();
        var pg = $(this).data('page');
        if (pg) tlLoadPage(pg);
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
            url: '/inventory/updateLedgerRemarks',
            method: 'POST',
            data: {
                LedgerUID:  ledgerUID,
                Remarks:    $('#tlEditAdjNotes').val(),
                [CsrfName]: CsrfToken,
            },
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
