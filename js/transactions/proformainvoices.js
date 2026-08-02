// ── Pro Forma Invoices list — module-specific JS ──────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _pfSelectAllMode = false;
var _pfTotalRecords  = 0;
var _pfPageCount     = 0;

/**
 * @returns {void}
 */
function _pfUpdateSelectAllBanner() {
    var $banner = $('#pfSelectAllBanner');
    var $msg    = $('#pfSelectAllMsg');
    var $link   = $('#pfSelectAllLink');
    var $clear  = $('#pfSelectAllClear');

    if (!_pfPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_pfSelectAllMode) {
        $msg.text('All ' + _pfTotalRecords + ' proforma invoices are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _pfPageCount + ' proforma invoices on this page are selected.');
        $clear.addClass('d-none');
        if (_pfTotalRecords > _pfPageCount) {
            $link.text('Select all ' + _pfTotalRecords + ' proforma invoices?').removeClass('d-none');
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
function _pfClearSelectAll() {
    _pfSelectAllMode = false;
    $('#pfSelectAllBanner').addClass('d-none');
    $('#pfSelectAllLink').removeClass('d-none');
    $('#pfSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultipleProformaInvoices() {
    var postData = _pfSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/113',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _pfClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getProFormaInvoicesDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getProFormaInvoicesDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/113/',
        tabCountClass:  '.pf-tab-count',
        statusTabClass: '.pf-status-tab',
        errorMessage:   'Failed to load pro forma invoices.',
        onSuccess:      function(resp) {
            _pfTotalRecords = parseInt(resp.TotalCount) || 0;
            _pfPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _pfUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
