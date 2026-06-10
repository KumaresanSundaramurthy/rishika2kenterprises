// Purchase Returns module JS
'use strict';

function getPurchaseReturnsDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url           : '/purchasereturns/getPurchaseReturnsPageDetails/',
        tabCountClass : '.pr-tab-count',
        statusTabClass: '.pr-status-tab',
        errorMessage  : 'Failed to load purchase returns.',
    }, pageNo, rowLimit, filter);
}
