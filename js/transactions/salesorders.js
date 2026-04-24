// ── Sales Orders list — module-specific JS ───────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getSalesOrdersDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/salesorders/getSalesOrdersPageDetails/',
        tabCountClass:  '.so-tab-count',
        statusTabClass: '.so-status-tab',
        errorMessage:   'Failed to load sales orders.',
    }, pageNo, rowLimit, filter);
}
