// Purchase Returns module JS
'use strict';

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _prSelectAllMode = false;
var _prTotalRecords  = 0;
var _prPageCount     = 0;

/**
 * @returns {void}
 */
function _prUpdateSelectAllBanner() {
    var $banner = $('#prSelectAllBanner');
    var $msg    = $('#prSelectAllMsg');
    var $link   = $('#prSelectAllLink');
    var $clear  = $('#prSelectAllClear');

    if (!_prPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_prSelectAllMode) {
        $msg.text('All ' + _prTotalRecords + ' purchase returns are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _prPageCount + ' purchase returns on this page are selected.');
        $clear.addClass('d-none');
        if (_prTotalRecords > _prPageCount) {
            $link.text('Select all ' + _prTotalRecords + ' purchase returns?').removeClass('d-none');
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
function _prClearSelectAll() {
    _prSelectAllMode = false;
    $('#prSelectAllBanner').addClass('d-none');
    $('#prSelectAllLink').removeClass('d-none');
    $('#prSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultiplePurchaseReturns() {
    var postData = _prSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/108',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _prClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getPurchaseReturnsDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getPurchaseReturnsDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url           : '/transactions/getPageDetails/108/',
        tabCountClass : '.pr-tab-count',
        statusTabClass: '.pr-status-tab',
        errorMessage  : 'Failed to load purchase returns.',
        onSuccess     : function(resp) {
            _prTotalRecords = parseInt(resp.TotalCount) || 0;
            _prPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _prUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
