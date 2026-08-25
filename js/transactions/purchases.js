// ── Purchases list — module-specific JS ──────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _purchSelectAllMode = false;
var _purchTotalRecords  = 0;
var _purchPageCount     = 0;

/** @returns {void} */
function _purchUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_purchSelectAllMode, _purchTotalRecords, _purchPageCount, 'purch', 'purchases');
}
/** @returns {void} */
function _purchClearSelectAll() {
    _purchSelectAllMode = false;
    clearTransSelectAllDom('purch');
}
/** @returns {void} */
function deleteMultiplePurchases() {
    deleteMultipleTrans(105, _purchSelectAllMode, _purchClearSelectAll, function () {
        getPurchasesDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getPurchasesDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/105/',
        tabCountClass:  '.trans-tab-count',
        statusTabClass: '.purch-status-tab',
        errorMessage:   'Failed to load purchase bills.',
        onSuccess:      function (resp) {
            _purchTotalRecords = parseInt(resp.TotalCount) || 0;
            _purchPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _purchUpdateSelectAllBanner();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}

// ── WhatsApp link handler ─────────────────────────────────────────────────────
$(document).on('click', '.purch-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// ── Register smart PDF attach alert when comm modal opens in Email tab ────────
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 105 || !recordUID) return;
    if (typeof _setupCommPdfAlert !== 'function') return;

    var rowData   = (typeof _commRowData !== 'undefined') ? _commRowData : null;
    var docNumber = rowData ? (rowData.docNumber || '') : '';
    var filename  = (docNumber || 'purchase').replace(/[^A-Za-z0-9_.\-]/g, '_') + '.pdf';

    _setupCommPdfAlert(docNumber, function (onSuccess) {
        $.ajax({
            url   : '/purchases/getPurchasePdfBase64',
            method: 'POST',
            data  : { TransUID: recordUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error || !resp.Base64) { onSuccess(null); return; }
                try {
                    var binary = atob(resp.Base64);
                    var bytes  = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                    var blob = new Blob([bytes], { type: 'application/pdf' });
                    onSuccess(new File([blob], resp.Filename || filename, { type: 'application/pdf' }));
                } catch (ex) {
                    onSuccess(null);
                }
            },
            error: function () { onSuccess(null); }
        });
    });
});

// ── Payment Details Panel ─────────────────────────────────────────────────────
initTransPaymentPanel('', '#6f42c1');

