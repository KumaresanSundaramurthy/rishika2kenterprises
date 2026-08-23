// ── List page AJAX functions ──────────────────────────────────────────────

/**
 * @param {string} tableSelector
 * @param {string} paginationSelector
 * @returns {void}
 */
function showTabSpinner(tableSelector, paginationSelector) {
    var cols = $(tableSelector + ' thead tr:first th:visible').length || 6;
    $(tableSelector + ' tbody').html(
        '<tr><td colspan="' + cols + '" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
        '<span class="text-muted" style="font-size:.85rem;">Loading...</span>' +
        '</td></tr>'
    );
    if (paginationSelector) $(paginationSelector).empty();
}

function _smartDecimal(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return '0';
    return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
}

// ── Select-all (Pattern 3) state ──────────────────────────────────────────
var _custSelectAllMode = false;
var _custTotalRecords  = 0;
var _custPageCount     = 0;
var _custReqSeq        = 0;

/**
 * @returns {void}
 */
function _custUpdateSelectAllBanner() {
    var $banner = $('#custSelectAllBanner');
    var $msg    = $('#custSelectAllMsg');
    var $link   = $('#custSelectAllLink');
    var $clear  = $('#custSelectAllClear');

    if (!_custPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_custSelectAllMode) {
        $msg.text('All ' + _custTotalRecords + ' customers are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _custPageCount + ' customers on this page are selected.');
        $clear.addClass('d-none');
        if (_custTotalRecords > _custPageCount) {
            $link.text('Select all ' + _custTotalRecords + ' customers?').removeClass('d-none');
        } else {
            $link.addClass('d-none');
            $banner.addClass('d-none');
            return;
        }
    }
    $banner.removeClass('d-none');
}

/**
 * @returns {void}
 */
function _custClearSelectAll() {
    _custSelectAllMode = false;
    $('#custSelectAllBanner').addClass('d-none');
    $('#custSelectAllLink').removeClass('d-none');
    $('#custSelectAllClear').addClass('d-none');
}

function getCustomersDetails(PageNo, RowLimit, Filter, onDone) {
    var reqSeq = ++_custReqSeq;
    ajaxLoading(0);
    showTabSpinner(ModuleTable, ModulePag);
    $.ajax({
        url: '/customers/getCustomersPageDetails/' + PageNo,
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (reqSeq !== _custReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                $('#custStickyPagination .CustomersPagination').html(response.Pagination);
                _custTotalRecords = parseInt(response.TotalCount) || 0;
                _custPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
                var cnt = _custTotalRecords;
                $('.cust-tab .trans-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                $(window).trigger('scroll');
            }
            executeTablePagnCommonFunc(response, false);
            if (typeof onDone === 'function') onDone();
        },
        error: function () {
            if (reqSeq !== _custReqSeq) return;
            ajaxLoading(1);
        },
    });
}

function deleteCustomer(DeleteId) {
    $.ajax({
        url: '/customers/deleteCustomerData',
        method: 'POST',
        data: { CustomerUID: DeleteId, ModuleId: ModuleId },
        cache: false,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function deleteMultipleCustomers() {
    var postData = _custSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), ModuleId: ModuleId, [CsrfName]: CsrfToken }
        : { CustomerUIDs: SelectedUIDs, ModuleId: ModuleId, [CsrfName]: CsrfToken };
    $.ajax({
        url: '/customers/deleteBulkCustomers',
        method: 'POST',
        cache: false,
        data: postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _custClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

function toggleCustomerStatus(CustomerUID, IsActive) {
    $.ajax({
        url: '/customers/toggleCustomerStatus',
        method: 'POST',
        cache: false,
        data: { CustomerUID: CustomerUID, IsActive: IsActive, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function updateCustomerStats(stats) {
    if (!stats) return;
    var s = stats;
    $('.cust-stat-total').text(Number(s.TotalCount || 0).toLocaleString());
    $('.cust-stat-active').text(Number(s.ActiveCount || 0).toLocaleString());
    $('.cust-stat-month').text(Number(s.MonthCount || 0).toLocaleString());
    $('.cust-stat-fy').text(Number(s.FYCount || 0).toLocaleString());
    $('.cust-stat-lastmonth').text(Number(s.LastMonthCount || 0).toLocaleString());
}

// ── Page-level callback: refresh list after any save ─────────────────────
/**
 * @param {Object} response
 * @returns {void}
 */
function _custPageSaveSuccess(response) {
    hideUIBlock();
    ajaxLoading(0);
    var msg = response ? response.Message : '';
    if (msg) showToastNotification(msg, 'success');
    getCustomersDetails(PageNo, RowLimit, Filter);
}

// ── Customer list image → open gallery from data-images (no AJAX) ────────────
$(document).on('click', '.cust-list-img', function(e) {
    e.stopPropagation();
    var raw = $(this).data('images');
    try {
        var imgs = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (imgs && imgs.length) { openImageGallery(imgs, 0); return; }
    } catch(err) {}
    var src = this.src;
    if (src) openImageGallery([{ url: src, name: '' }], 0);
});

// ── Open modal triggers ───────────────────────────────────────────────────
$(document).on('click', '.cust-edit-btn', function () {
    CustomerForm.open('edit', $(this).data('uid'), { onSaveSuccess: _custPageSaveSuccess });
});

$(document).on('click', '.cust-clone-btn', function () {
    CustomerForm.open('clone', $(this).data('uid'), { onSaveSuccess: _custPageSaveSuccess });
});

// ── Communication: single send ────────────────────────────────────────────
$(document).on('click', '.comm-send-single', function () {
    var $btn = $(this);
    openCommModal(
        $btn.data('commtype'),
        $btn.data('recipienttype'),
        [$btn.data('uid')],
        [$btn.data('name') || ''],
        [$btn.data('mobile') || ''],
        [$btn.data('email') || '']
    );
});

// ── Communication: bulk show/hide ─────────────────────────────────────────
function _updateBulkCommOptions() {
    var checked = $('.customerCheck:checked').length > 0;
    $('#BulkSmsOption').toggleClass('d-none', !checked);
    $('#BulkEmailOption').toggleClass('d-none', !checked);
}

$(document).on('change', '.customerCheck', function () {
    _updateBulkCommOptions();
});

$(document).on('click', '#btnBulkSms', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.customerCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('SMS', 'Customer', uids, names, mobiles, emails);
});

$(document).on('click', '#btnBulkEmail', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.customerCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('Email', 'Customer', uids, names, mobiles, emails);
});


