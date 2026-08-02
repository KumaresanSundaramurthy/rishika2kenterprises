// ── Indirect Income list — module-specific JS ──────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _incSelectAllMode = false;
var _incTotalRecords  = 0;
var _incPageCount     = 0;

/**
 * @returns {void}
 */
function _incUpdateSelectAllBanner() {
    var $banner = $('#incSelectAllBanner');
    var $msg    = $('#incSelectAllMsg');
    var $link   = $('#incSelectAllLink');
    var $clear  = $('#incSelectAllClear');

    if (!_incPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_incSelectAllMode) {
        $msg.text('All ' + _incTotalRecords + ' income records are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _incPageCount + ' income records on this page are selected.');
        $clear.addClass('d-none');
        if (_incTotalRecords > _incPageCount) {
            $link.text('Select all ' + _incTotalRecords + ' income records?').removeClass('d-none');
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
function _incClearSelectAll() {
    _incSelectAllMode = false;
    $('#incSelectAllBanner').addClass('d-none');
    $('#incSelectAllLink').removeClass('d-none');
    $('#incSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultipleIncomes() {
    var postData = _incSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'IncomeUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/indirectincome/deleteMultipleIncomes',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _incClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getIncomeDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getIncomeDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/indirectincome/getPageDetails/',
        tabCountClass:  '.inc-tab-count',
        statusTabClass: '.inc-status-tab',
        errorMessage:   'Failed to load income records.',
        onSuccess:      function(resp) {
            _incTotalRecords = parseInt(resp.TotalCount) || 0;
            _incPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _incUpdateSelectAllBanner();
        },
    }, pageNo, rowLimit, filter);
}
