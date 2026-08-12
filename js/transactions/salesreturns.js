// Sales Returns module JS

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _srSelectAllMode = false;
var _srTotalRecords  = 0;
var _srPageCount     = 0;

/** @returns {void} */
function _srUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_srSelectAllMode, _srTotalRecords, _srPageCount, 'sr', 'sales returns');
}
/** @returns {void} */
function _srClearSelectAll() {
    _srSelectAllMode = false;
    clearTransSelectAllDom('sr');
}
/** @returns {void} */
function deleteMultipleSalesReturns() {
    deleteMultipleTrans(106, _srSelectAllMode, _srClearSelectAll, function () {
        getSalesReturnsDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getSalesReturnsDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/106/',
        tabCountClass:  '.trans-tab-count',
        statusTabClass: '.sr-status-tab',
        errorMessage:   'Failed to load sales returns.',
        onSuccess:      function (resp) {
            _srTotalRecords = parseInt(resp.TotalCount) || 0;
            _srPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _srUpdateSelectAllBanner();
            if (typeof updateSummaryStats === 'function' && resp.SummaryStats) updateSummaryStats(resp.SummaryStats);
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}

// ── Payment Details Panel ─────────────────────────────────────────────────────
initTransPaymentPanel('text-success', '#198754');

