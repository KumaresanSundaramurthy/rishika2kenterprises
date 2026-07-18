// ── Pro Forma Invoices list — module-specific JS ──────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

function getProFormaInvoicesDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/proforma/getPageDetails/',
        tabCountClass:  '.pf-tab-count',
        statusTabClass: '.pf-status-tab',
        errorMessage:   'Failed to load pro forma invoices.',
        onSuccess:      function(resp) { if (typeof afterLoad === 'function') afterLoad(resp); },
    }, pageNo, rowLimit, filter);
}
