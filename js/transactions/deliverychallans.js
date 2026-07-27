// ── Delivery Challans list — module-specific JS ───────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

function getDeliveryChallansDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/112/',
        tabCountClass:  '.dc-tab-count',
        statusTabClass: '.dc-status-tab',
        errorMessage:   'Failed to load delivery challans.',
        onSuccess:      function(resp) { if (typeof afterLoad === 'function') afterLoad(resp); },
    }, pageNo, rowLimit, filter);
}

$(document).on('click', '.duplicateDeliveryChallan', function () {
    var uid = $(this).data('uid'), num = $(this).data('num') || '';
    Swal.fire({
        title: t('swal_clone_challan', 'Clone Challan?'),
        html : num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Clone this delivery challan?',
        icon : 'question', showCancelButton: true,
        confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
        confirmButtonText: t('btn_yes_clone', 'Yes, Clone'), cancelButtonText: t('btn_cancel', 'Cancel')
    }).then(function (r) {
        if (!r.isConfirmed) return;
        window.location.href = '/deliverychallan/create?fromClone=' + uid;
    });
});
