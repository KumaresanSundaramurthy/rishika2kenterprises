'use strict';

var _tlFilter = {};

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

// ── DOM ready ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    'use strict';

    // Set default filter from PHP-injected values
    _tlFilter['DateFrom'] = TlDefaultDateFrom;
    _tlFilter['DateTo']   = TlDefaultDateTo;

    // Date From picker
    flatpickr('#tlDateFrom', {
        dateFormat:  'd-m-Y',
        defaultDate: TlDefaultDateFrom,
        onChange: function (dates) {
            if (dates.length) {
                _tlFilter['DateFrom'] = tlFmtDate(dates[0]);
                tlLoadPage(1);
            }
        }
    });

    // Date To picker
    flatpickr('#tlDateTo', {
        dateFormat:  'd-m-Y',
        defaultDate: TlDefaultDateTo,
        onChange: function (dates) {
            if (dates.length) {
                _tlFilter['DateTo'] = tlFmtDate(dates[0]);
                tlLoadPage(1);
            }
        }
    });

    // Item search (Select2 AJAX)
    $('#tlProductSearch').select2({
        placeholder:        'Select Item',
        allowClear:         true,
        minimumInputLength: 0,
        dropdownParent:     $('body'),
        ajax: {
            url:   '/inventory/searchProducts',
            type:  'POST',
            delay: 300,
            data: function (params) {
                return { Term: params.term || '', [CsrfName]: CsrfToken };
            },
            processResults: function (data) {
                if (data.Error) return { results: [] };
                return {
                    results: (data.Products || []).map(function (p) {
                        return { id: p.ProductUID, text: p.ItemName, category: p.CategoryName || '' };
                    })
                };
            },
        },
        templateResult: function (item) {
            if (!item.id) return item.text;
            var $el = $('<div style="padding:2px 0;"><div style="font-size:.85rem;font-weight:500;">' + $('<span>').text(item.text).html() + '</div></div>');
            if (item.category) {
                $el.append('<div style="font-size:.7rem;color:#6c757d;">' + $('<span>').text(item.category).html() + '</div>');
            }
            return $el;
        },
    });

    $('#tlProductSearch').on('change', function () {
        _tlFilter['ProductUID'] = $(this).val() || '';
        tlLoadPage(1);
    });

    // Movement type filter
    $('#tlMovementFilter').on('change', function () {
        _tlFilter['MovementType'] = $(this).val();
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

    // Edit manual adjustment
    $(document).on('click', '.tlEditAdjBtn', function () {
        var movement = $(this).data('movement');
        var isIN     = movement === 'IN';
        var uid      = $(this).data('adj-uid');
        var name     = $(this).data('product-name');
        var qty      = parseFloat($(this).data('qty'))  || 0;
        var cost     = parseFloat($(this).data('cost')) || 0;
        var category = $(this).data('category') || '';
        var notes    = $(this).data('notes')    || '';
        var date     = $(this).data('date')     || '';
        var unit     = $(this).data('unit')     || 'PCS';

        if (isIN) {
            $('#siProductUID').val($(this).data('product-uid'));
            $('#siProductName').text(name);
            $('#siUnitLabel').text(unit);
            $('#siQty').val(qty);
            $('#siPrice').val(cost > 0 ? cost.toFixed(2) : '');
            $('#siCategory').val(category || 'New');
            $('#siNotes').val(notes);
            $('#siRecordDate').val(date);
            $('#siPriceType').val('PurchasePrice');
            $('#siSubmitBtn').data('adj-uid', uid).text('Update');
            new bootstrap.Modal(document.getElementById('stockInModal')).show();
        } else {
            $('#soProductUID').val($(this).data('product-uid'));
            $('#soProductName').text(name);
            $('#soUnitLabel').text(unit);
            $('#soQty').val(qty);
            $('#soPrice').val(cost > 0 ? cost.toFixed(2) : '');
            $('#soCategory').val(category || 'Miscellaneous');
            $('#soNotes').val(notes);
            $('#soRecordDate').val(date);
            $('#soPriceType').val('SellingPrice');
            $('#soSubmitBtn').data('adj-uid', uid).text('Update');
            new bootstrap.Modal(document.getElementById('stockOutModal')).show();
        }
    });

});
