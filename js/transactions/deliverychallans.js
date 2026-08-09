// ── Delivery Challans list — module-specific JS ───────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _dcSelectAllMode = false;
var _dcTotalRecords  = 0;
var _dcPageCount     = 0;

/** @returns {void} */
function _dcUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_dcSelectAllMode, _dcTotalRecords, _dcPageCount, 'dc', 'delivery challans');
}
/** @returns {void} */
function _dcClearSelectAll() {
    _dcSelectAllMode = false;
    clearTransSelectAllDom('dc');
}
/** @returns {void} */
function deleteMultipleDeliveryChallans() {
    deleteMultipleTrans(112, _dcSelectAllMode, _dcClearSelectAll, function () {
        getDeliveryChallansDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getDeliveryChallansDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/112/',
        tabCountClass:  '.dc-tab-count',
        statusTabClass: '.dc-status-tab',
        errorMessage:   'Failed to load delivery challans.',
        onSuccess:      function (resp) {
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
