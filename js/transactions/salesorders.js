// ── Sales Orders list — module-specific JS ───────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _soSelectAllMode = false;
var _soTotalRecords  = 0;
var _soPageCount     = 0;

/** @returns {void} */
function _soUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_soSelectAllMode, _soTotalRecords, _soPageCount, 'so', 'sales orders');
}
/** @returns {void} */
function _soClearSelectAll() {
    _soSelectAllMode = false;
    clearTransSelectAllDom('so');
}
/** @returns {void} */
function deleteMultipleSalesOrders() {
    deleteMultipleTrans(102, _soSelectAllMode, _soClearSelectAll, function () {
        getSalesOrdersDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getSalesOrdersDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/102/',
        tabCountClass:  '.so-tab-count',
        statusTabClass: '.so-status-tab',
        errorMessage:   'Failed to load sales orders.',
        onSuccess:      function (resp) {
            _soTotalRecords = parseInt(resp.TotalCount) || 0;
            _soPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _soUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}

