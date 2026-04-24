// ── Invoices list — module-specific JS ───────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getInvoicesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/invoices/getInvoicesPageDetails/',
        tabCountClass:  '.inv-tab-count',
        statusTabClass: '.inv-status-tab',
        errorMessage:   'Failed to load invoices.',
    }, pageNo, rowLimit, filter);
}
