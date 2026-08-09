// ── Purchase Orders list — module-specific JS ─────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _poSelectAllMode = false;
var _poTotalRecords  = 0;
var _poPageCount     = 0;

/** @returns {void} */
function _poUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_poSelectAllMode, _poTotalRecords, _poPageCount, 'po', 'purchase orders');
}
/** @returns {void} */
function _poClearSelectAll() {
    _poSelectAllMode = false;
    clearTransSelectAllDom('po');
}
/** @returns {void} */
function deleteMultiplePurchaseOrders() {
    deleteMultipleTrans(104, _poSelectAllMode, _poClearSelectAll, function () {
        getPurchaseOrdersDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getPurchaseOrdersDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/104/',
        tabCountClass:  '.po-tab-count',
        statusTabClass: '.po-status-tab',
        errorMessage:   'Failed to load purchase orders.',
        onSuccess:      function (resp) {
            _poTotalRecords = parseInt(resp.TotalCount) || 0;
            _poPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _poUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
