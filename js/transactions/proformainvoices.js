// ── Pro Forma Invoices list — module-specific JS ──────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _pfSelectAllMode = false;
var _pfTotalRecords  = 0;
var _pfPageCount     = 0;

/** @returns {void} */
function _pfUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_pfSelectAllMode, _pfTotalRecords, _pfPageCount, 'pf', 'proforma invoices');
}
/** @returns {void} */
function _pfClearSelectAll() {
    _pfSelectAllMode = false;
    clearTransSelectAllDom('pf');
}
/** @returns {void} */
function deleteMultipleProformaInvoices() {
    deleteMultipleTrans(113, _pfSelectAllMode, _pfClearSelectAll, function () {
        getProFormaInvoicesDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getProFormaInvoicesDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/113/',
        tabCountClass:  '.pf-tab-count',
        statusTabClass: '.pf-status-tab',
        errorMessage:   'Failed to load pro forma invoices.',
        onSuccess:      function (resp) {
            _pfTotalRecords = parseInt(resp.TotalCount) || 0;
            _pfPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _pfUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}
