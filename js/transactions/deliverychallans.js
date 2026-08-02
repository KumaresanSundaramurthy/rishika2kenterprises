// ── Delivery Challans list — module-specific JS ───────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _dcSelectAllMode = false;
var _dcTotalRecords  = 0;
var _dcPageCount     = 0;

/**
 * @returns {void}
 */
function _dcUpdateSelectAllBanner() {
    var $banner = $('#dcSelectAllBanner');
    var $msg    = $('#dcSelectAllMsg');
    var $link   = $('#dcSelectAllLink');
    var $clear  = $('#dcSelectAllClear');

    if (!_dcPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_dcSelectAllMode) {
        $msg.text('All ' + _dcTotalRecords + ' delivery challans are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _dcPageCount + ' delivery challans on this page are selected.');
        $clear.addClass('d-none');
        if (_dcTotalRecords > _dcPageCount) {
            $link.text('Select all ' + _dcTotalRecords + ' delivery challans?').removeClass('d-none');
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
function _dcClearSelectAll() {
    _dcSelectAllMode = false;
    $('#dcSelectAllBanner').addClass('d-none');
    $('#dcSelectAllLink').removeClass('d-none');
    $('#dcSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultipleDeliveryChallans() {
    var postData = _dcSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/112',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _dcClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getDeliveryChallansDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function getDeliveryChallansDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/112/',
        tabCountClass:  '.dc-tab-count',
        statusTabClass: '.dc-status-tab',
        errorMessage:   'Failed to load delivery challans.',
        onSuccess:      function(resp) {
            _dcTotalRecords = parseInt(resp.TotalCount) || 0;
            _dcPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _dcUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}

$(document).on('click', '.duplicateDeliveryChallan', function () {
    var uid = $(this).data('uid'), num = $(this).data('num') || '';
    Swal.fire({
        title: t('swal_clone_challan', 'Clone Challan?'),
        html : num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Clone this delivery challan?',
        icon : 'question', showCancelButton: true,
        confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
        confirmButtonText: t('btn_yes_clone', 'Yes, Clone'), cancelButtonText: t('btn_cancel', 'Cancel')
    }).then(function (r) {
        if (!r.isConfirmed) return;
        window.location.href = '/deliverychallan/create?fromClone=' + uid;
    });
});
