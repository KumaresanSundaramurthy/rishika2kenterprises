// ── Indirect Income list — module-specific JS ──────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

function getIncomeDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/indirectincome/getPageDetails/',
        tabCountClass:  '.inc-tab-count',
        statusTabClass: '.inc-status-tab',
        errorMessage:   'Failed to load income records.',
    }, pageNo, rowLimit, filter);
}
