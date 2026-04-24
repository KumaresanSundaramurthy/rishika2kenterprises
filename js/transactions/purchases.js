// ── Purchases list — module-specific JS ──────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getPurchasesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/purchases/getPurchasesPageDetails/',
        tabCountClass:  '.purch-tab-count',
        statusTabClass: '.purch-status-tab',
        errorMessage:   'Failed to load purchase bills.',
    }, pageNo, rowLimit, filter);
}
