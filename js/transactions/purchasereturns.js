// Purchase Returns module JS
'use strict';

function getPurchaseReturnsDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url           : '/transactions/getPageDetails/108/',
        tabCountClass : '.pr-tab-count',
        statusTabClass: '.pr-status-tab',
        errorMessage  : 'Failed to load purchase returns.',
        onSuccess     : function(resp) { if (typeof afterLoad === 'function') afterLoad(resp); },
    }, pageNo, rowLimit, filter);
}
