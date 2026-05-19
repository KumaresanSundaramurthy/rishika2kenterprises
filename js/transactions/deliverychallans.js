// ── Delivery Challans list — module-specific JS ───────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

function getDeliveryChallansDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/deliverychallan/getPageDetails/',
        tabCountClass:  '.dc-tab-count',
        statusTabClass: '.dc-status-tab',
        errorMessage:   'Failed to load delivery challans.',
    }, pageNo, rowLimit, filter);
}
