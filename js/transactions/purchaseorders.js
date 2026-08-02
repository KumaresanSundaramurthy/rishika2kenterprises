// ── Purchase Orders list — module-specific JS ─────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _poSelectAllMode = false;
var _poTotalRecords  = 0;
var _poPageCount     = 0;

/**
 * @returns {void}
 */
function _poUpdateSelectAllBanner() {
    var $banner = $('#poSelectAllBanner');
    var $msg    = $('#poSelectAllMsg');
    var $link   = $('#poSelectAllLink');
    var $clear  = $('#poSelectAllClear');

    if (!_poPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_poSelectAllMode) {
        $msg.text('All ' + _poTotalRecords + ' purchase orders are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _poPageCount + ' purchase orders on this page are selected.');
        $clear.addClass('d-none');
        if (_poTotalRecords > _poPageCount) {
            $link.text('Select all ' + _poTotalRecords + ' purchase orders?').removeClass('d-none');
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
function _poClearSelectAll() {
    _poSelectAllMode = false;
    $('#poSelectAllBanner').addClass('d-none');
    $('#poSelectAllLink').removeClass('d-none');
    $('#poSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultiplePurchaseOrders() {
    var postData = _poSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/104',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _poClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getPurchaseOrdersDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getPurchaseOrdersDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/104/',
        tabCountClass:  '.po-tab-count',
        statusTabClass: '.po-status-tab',
        errorMessage:   'Failed to load purchase orders.',
        onSuccess:      function(resp) {
            _poTotalRecords = parseInt(resp.TotalCount) || 0;
            _poPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _poUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
