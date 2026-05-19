// ── Pro Forma Invoices list — module-specific JS ──────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

function getProFormaInvoicesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/proforma/getPageDetails/',
        tabCountClass:  '.pf-tab-count',
        statusTabClass: '.pf-status-tab',
        errorMessage:   'Failed to load pro forma invoices.',
    }, pageNo, rowLimit, filter);
}
