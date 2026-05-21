// ── Expenses list — module-specific JS ────────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

function getExpensesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/expenses/getPageDetails/',
        tabCountClass:  '.exp-tab-count',
        statusTabClass: '.exp-status-tab',
        errorMessage:   'Failed to load expenses.',
    }, pageNo, rowLimit, filter);
}
