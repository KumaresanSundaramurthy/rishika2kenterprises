// ── Sales Orders list — module-specific JS ───────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _soSelectAllMode = false;
var _soTotalRecords  = 0;
var _soPageCount     = 0;

/**
 * @returns {void}
 */
function _soUpdateSelectAllBanner() {
    var $banner = $('#soSelectAllBanner');
    var $msg    = $('#soSelectAllMsg');
    var $link   = $('#soSelectAllLink');
    var $clear  = $('#soSelectAllClear');

    if (!_soPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_soSelectAllMode) {
        $msg.text('All ' + _soTotalRecords + ' sales orders are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _soPageCount + ' sales orders on this page are selected.');
        $clear.addClass('d-none');
        if (_soTotalRecords > _soPageCount) {
            $link.text('Select all ' + _soTotalRecords + ' sales orders?').removeClass('d-none');
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
function _soClearSelectAll() {
    _soSelectAllMode = false;
    $('#soSelectAllBanner').addClass('d-none');
    $('#soSelectAllLink').removeClass('d-none');
    $('#soSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultipleSalesOrders() {
    var postData = _soSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/102',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _soClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getSalesOrdersDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getSalesOrdersDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/102/',
        tabCountClass:  '.so-tab-count',
        statusTabClass: '.so-status-tab',
        errorMessage:   'Failed to load sales orders.',
        onSuccess:      function(resp) {
            _soTotalRecords = parseInt(resp.TotalCount) || 0;
            _soPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _soUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
