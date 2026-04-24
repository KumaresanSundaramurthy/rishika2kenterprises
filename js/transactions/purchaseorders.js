// ── Purchase Orders list — module-specific JS ─────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getPurchaseOrdersDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/purchaseorders/getPurchaseOrdersPageDetails/',
        tabCountClass:  '.po-tab-count',
        statusTabClass: '.po-status-tab',
        errorMessage:   'Failed to load purchase orders.',
    }, pageNo, rowLimit, filter);
}
