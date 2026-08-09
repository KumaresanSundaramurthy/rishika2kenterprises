// Purchase Returns module JS

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _prSelectAllMode = false;
var _prTotalRecords  = 0;
var _prPageCount     = 0;

/** @returns {void} */
function _prUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_prSelectAllMode, _prTotalRecords, _prPageCount, 'pr', 'purchase returns');
}
/** @returns {void} */
function _prClearSelectAll() {
    _prSelectAllMode = false;
    clearTransSelectAllDom('pr');
}
/** @returns {void} */
function deleteMultiplePurchaseReturns() {
    deleteMultipleTrans(108, _prSelectAllMode, _prClearSelectAll, function () {
        getPurchaseReturnsDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getPurchaseReturnsDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url           : '/transactions/getPageDetails/108/',
        tabCountClass : '.pr-tab-count',
        statusTabClass: '.pr-status-tab',
        errorMessage  : 'Failed to load purchase returns.',
        onSuccess     : function (resp) {
            _prTotalRecords = parseInt(resp.TotalCount) || 0;
            _prPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _prUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
