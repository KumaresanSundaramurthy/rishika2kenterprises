// ── Expenses list — module-specific JS ────────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _expSelectAllMode = false;
var _expTotalRecords  = 0;
var _expPageCount     = 0;

/**
 * @returns {void}
 */
function _expUpdateSelectAllBanner() {
    var $banner = $('#expSelectAllBanner');
    var $msg    = $('#expSelectAllMsg');
    var $link   = $('#expSelectAllLink');
    var $clear  = $('#expSelectAllClear');

    if (!_expPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_expSelectAllMode) {
        $msg.text('All ' + _expTotalRecords + ' expenses are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _expPageCount + ' expenses on this page are selected.');
        $clear.addClass('d-none');
        if (_expTotalRecords > _expPageCount) {
            $link.text('Select all ' + _expTotalRecords + ' expenses?').removeClass('d-none');
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
function _expClearSelectAll() {
    _expSelectAllMode = false;
    $('#expSelectAllBanner').addClass('d-none');
    $('#expSelectAllLink').removeClass('d-none');
    $('#expSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultipleExpenses() {
    var postData = _expSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'ExpenseUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/expenses/deleteMultipleExpenses',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _expClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getExpensesDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getExpensesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/expenses/getPageDetails/',
        tabCountClass:  '.exp-tab-count',
        statusTabClass: '.exp-status-tab',
        errorMessage:   'Failed to load expenses.',
        onSuccess:      function(resp) {
            _expTotalRecords = parseInt(resp.TotalCount) || 0;
            _expPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _expUpdateSelectAllBanner();
        },
    }, pageNo, rowLimit, filter);
}
