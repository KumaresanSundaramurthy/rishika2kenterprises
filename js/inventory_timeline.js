'use strict';

var _tlFilter     = {};
var _tlProdCache  = null;
var _tlUsersCache = null;

var _tlCfbConfig = {
    checkClass : 'tl-category-checkbox',
    applyFn    : 'tlApplyCategoryFilter',
    resetFn    : 'tlResetCategoryFilter',
    uid        : 'timeline'
};

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

// ── Close all filter boxes ────────────────────────────────────────────────────
function tlCloseAllFilterBoxes() {
    $('#tlCategoryFilterBox, #tlSourceFilterBox, #tlUserFilterBox').hide();
}

// ── Category filter box ───────────────────────────────────────────────────────
function tlToggleCategoryFilter() {
    var $box = $('#tlCategoryFilterBox');
    if ($box.is(':visible')) { $box.hide(); return; }
    tlCloseAllFilterBoxes();
    var btn  = document.getElementById('tlCategoryFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 60) + 'px',
        display: 'flex',
    });
    CategoryAppend.filterBox('#tlCategoryFilterBox', _tlCfbConfig, _tlFilter.CategoryUID || []);
}

function tlApplyCategoryFilter() {
    var uids = [];
    $('#tlCategoryFilterBox .tl-category-checkbox:checked').each(function () {
        uids.push(parseInt($(this).val()));
    });
    if (uids.length) { _tlFilter['CategoryUID'] = uids; $('#tlCatFilterIcon').css('color', '#0284c7'); }
    else             { delete _tlFilter['CategoryUID']; $('#tlCatFilterIcon').css('color', ''); }
    $('#tlCategoryFilterBox').hide();
    tlLoadPage(1);
}

function tlResetCategoryFilter() {
    $('#tlCategoryFilterBox .tl-category-checkbox').prop('checked', false);
    $('#tlCategoryFilterBox .ca-sel-all').prop('checked', false);
    $('#tlCategoryFilterBox .ca-sel-all-label').text('Select All');
    delete _tlFilter['CategoryUID'];
    $('#tlCatFilterIcon').css('color', '');
    $('#tlCategoryFilterBox').hide();
    tlLoadPage(1);
}

// ── Source filter box ─────────────────────────────────────────────────────────
function tlToggleSourceFilter() {
    var $box = $('#tlSourceFilterBox');
    if ($box.is(':visible')) { $box.hide(); return; }
    tlCloseAllFilterBoxes();
    var btn  = document.getElementById('tlSourceFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 60) + 'px',
        display: 'flex',
    });
}

function tlApplySourceFilter() {
    var uids = [];
    $('.tl-source-checkbox:checked').each(function () {
        uids.push(parseInt($(this).val()));
    });
    if (uids.length) {
        _tlFilter['ModuleUID'] = uids;
        $('#tlSourceFilterIcon').css('color', '#0284c7');
    } else {
        delete _tlFilter['ModuleUID'];
        $('#tlSourceFilterIcon').css('color', '');
    }
    $('#tlSourceFilterBox').hide();
    tlLoadPage(1);
}

function tlResetSourceFilter() {
    $('.tl-source-checkbox').prop('checked', false);
    delete _tlFilter['ModuleUID'];
    $('#tlSourceFilterIcon').css('color', '');
    $('#tlSourceFilterBox').hide();
    tlLoadPage(1);
}

// ── User filter (cache-first) ─────────────────────────────────────────────────
function _tlFetchUsersCache(onSuccess, onMiss) {
    if (_tlUsersCache !== null) { onSuccess(_tlUsersCache); return; }
    if (!UpstashService.isEnabled()) { onMiss(); return; }
    UpstashService.get(UpstashService.orgKey('org_users')).then(function (data) {
        if (!Array.isArray(data) || !data.length) { onMiss(); return; }
        _tlUsersCache = data;
        onSuccess(_tlUsersCache);
    }).catch(function () { onMiss(); });
}

function _tlRenderUserList(users) {
    var $list = $('#tlUserFilterBox .catg-list');
    $list.empty();
    if (!users || users.length <= 1) {
        $('#tlUserFilterBtn').hide();
        return;
    }
    users.forEach(function (u) {
        var name = u.FullName || ((u.FirstName || '') + ' ' + (u.LastName || '')).trim() || 'User';
        $list.append(
            '<label class="catg-list-item">' +
            '<input class="form-check-input tl-user-checkbox" type="checkbox" value="' + (u.UserUID || 0) + '">' +
            '<span>' + $('<span>').text(name).html() + '</span>' +
            '</label>'
        );
    });
}

function _tlEnsureUserList() {
    if ($('#tlUserFilterBox .catg-list-item').length > 0) return;
    _tlFetchUsersCache(
        function (users) { _tlRenderUserList(users); },
        function ()      { /* cache miss — box stays empty */ }
    );
}

function tlToggleUserFilter() {
    var $box = $('#tlUserFilterBox');
    if ($box.is(':visible')) { $box.hide(); return; }
    tlCloseAllFilterBoxes();
    var btn  = document.getElementById('tlUserFilterBtn');
    var rect = btn.getBoundingClientRect();
    $box.css({
        top:  (rect.bottom + window.scrollY + 4) + 'px',
        left: Math.max(4, rect.left + window.scrollX - 80) + 'px',
        display: 'flex',
    });
    _tlEnsureUserList();
}

function tlApplyUserFilter() {
    var uids = [];
    $('.tl-user-checkbox:checked').each(function () {
        uids.push($(this).val());
    });
    if (uids.length) {
        _tlFilter['CreatedByUID'] = uids;
        $('#tlUserFilterIcon').css('color', '#0284c7');
    } else {
        delete _tlFilter['CreatedByUID'];
        $('#tlUserFilterIcon').css('color', '');
    }
    $('#tlUserFilterBox').hide();
    tlLoadPage(1);
}

function tlResetUserFilter() {
    $('.tl-user-checkbox').prop('checked', false);
    delete _tlFilter['CreatedByUID'];
    $('#tlUserFilterIcon').css('color', '');
    $('#tlUserFilterBox').hide();
    tlLoadPage(1);
}

// ── Product search (cache-first) ──────────────────────────────────────────────
function _tlFetchProdCache(onSuccess, onMiss) {
    if (_tlProdCache !== null) { onSuccess(_tlProdCache); return; }
    if (!UpstashService.isEnabled()) { onMiss(); return; }
    UpstashService.get(UpstashService.orgKey('products')).then(function (map) {
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

function _tlProductSearchTemplate(item) {
    if (!item.id) return item.text;
    var $el = $('<div style="padding:2px 0;"><div style="font-size:.85rem;font-weight:500;">' + $('<span>').text(item.text).html() + '</div></div>');
    if (item.category) {
        $el.append('<div style="font-size:.7rem;color:#6c757d;">' + $('<span>').text(item.category).html() + '</div>');
    }
    return $el;
}

function _tlInitProductSearch() {
    var prodCache = null;
    var PAGE_SIZE = 30;

    function _paginate(list, term, page) {
        var filtered = term
            ? list.filter(function (p) { return p.text.toLowerCase().includes(term.toLowerCase()); })
            : list;
        var start = (page - 1) * PAGE_SIZE;
        return { results: filtered.slice(start, start + PAGE_SIZE), pagination: { more: (start + PAGE_SIZE) < filtered.length } };
    }

    $('#tlProductSearch').select2({
        placeholder        : 'Select Item',
        allowClear         : true,
        minimumInputLength : 0,
        width              : 'resolve',
        containerCssClass  : ':el',
        dropdownParent     : $('body'),
        templateResult     : _tlProductSearchTemplate,
        ajax: {
            transport: function (params, success, failure) {
                var term = (params.data.term || '').trim();
                var page = params.data.page || 1;

                if (prodCache) {
                    success(_paginate(prodCache, term, page));
                    return;
                }

                _tlFetchProdCache(
                    function (products) {
                        prodCache = products;
                        success(_paginate(prodCache, term, page));
                    },
                    function () {
                        // AJAX fallback
                        $.ajax({
                            url : '/inventory/searchProducts',
                            type: 'POST',
                            data: { Term: term, [CsrfName]: CsrfToken },
                            success: function (data) {
                                if (data.Error) { success({ results: [] }); return; }
                                prodCache = (data.Products || []).map(function (p) {
                                    return { id: p.ProductUID, text: p.ItemName, category: p.CategoryName || '' };
                                });
                                success(_paginate(prodCache, term, page));
                            },
                            error: function () { failure(); }
                        });
                    }
                );
            },
            data: function (params) {
                return { term: params.term || '', page: params.page || 1 };
            },
            processResults: function (data) {
                return data; // already shaped by transport
            },
            cache: false
        }
    });

    $('#tlProductSearch').on('select2:open', function () {
        prodCache = null; // clear so every open fetches fresh from Upstash
    }).on('change', function () {
        _tlFilter['ProductUID'] = $(this).val() || '';
        tlLoadPage(1);
    });
}

// ── DOM ready ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    'use strict';

    // Set default filter from PHP-injected values
    _tlFilter['DateFrom'] = TlDefaultDateFrom;
    _tlFilter['DateTo']   = TlDefaultDateTo;

    var _tlDateFmt = (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y';

    // Date From picker
    flatpickr('#tlDateFrom', {
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   _tlDateFmt,
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
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   _tlDateFmt,
        defaultDate: TlDefaultDateTo,
        onChange: function (dates) {
            if (dates.length) {
                _tlFilter['DateTo'] = tlFmtDate(dates[0]);
                tlLoadPage(1);
            }
        }
    });

    // Item search (cache-first: Upstash → AJAX fallback)
    _tlInitProductSearch();

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

    // Close filter boxes when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#tlCategoryFilterBox, #tlCategoryFilterBtn, #tlSourceFilterBox, #tlSourceFilterBtn, #tlUserFilterBox, #tlUserFilterBtn').length) {
            tlCloseAllFilterBoxes();
        }
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
