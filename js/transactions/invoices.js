// -- Invoices list — module-specific JS --------------------------------------
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _invSelectAllMode = false;
var _invTotalRecords  = 0;
var _invPageCount     = 0;

/** @returns {void} */
function _invUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_invSelectAllMode, _invTotalRecords, _invPageCount, 'inv', 'invoices');
}
/** @returns {void} */
function _invClearSelectAll() {
    _invSelectAllMode = false;
    clearTransSelectAllDom('inv');
}
/** @returns {void} */
function deleteMultipleInvoices() {
    deleteMultipleTrans(103, _invSelectAllMode, _invClearSelectAll, function () {
        getInvoicesDetails(PageNo, RowLimit, Filter);
    });
}

// -- WhatsApp link handler ---------------------------------------------------
$(document).on('click', '.inv-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// -- Register smart PDF attach alert when comm modal opens in Email tab -------
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 103 || !recordUID) return;
    if (typeof _setupCommPdfAlert !== 'function') return;

    var rowData   = (typeof _commRowData !== 'undefined') ? _commRowData : null;
    var docNumber = rowData ? (rowData.docNumber || '') : '';
    var filename  = (docNumber || 'invoice').replace(/[^A-Za-z0-9_.\-]/g, '_') + '.pdf';

    _setupCommPdfAlert(docNumber, function (onSuccess) {
        $.ajax({
            url   : '/invoices/getInvoicePdfBase64',
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

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getInvoicesDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/103/',
        tabCountClass:  '.trans-tab-count',
        statusTabClass: '.inv-status-tab',
        errorMessage:   'Failed to load invoices.',
        onSuccess:      function (resp) {
            _invTotalRecords = parseInt(resp.TotalCount) || 0;
            _invPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _invUpdateSelectAllBanner();
            if (typeof updateSummaryStats === 'function' && resp.SummaryStats) updateSummaryStats(resp.SummaryStats);
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}

// ── Payment Details Panel ──────────────────────────────────────────────────
initTransPaymentPanel('text-primary', '#696cff');

// ── Auto-print after Save & Print from the invoice form ───────────────────────
(function () {
    var raw = null;
    try { raw = sessionStorage.getItem('r2k_pendingPrint'); } catch (e) {}
    if (!raw) return;
    try { sessionStorage.removeItem('r2k_pendingPrint'); } catch (e) {}
    var data = null;
    try { data = JSON.parse(raw); } catch (e) {}
    if (!data || !data.transUID || !data.moduleUID) return;
    $(function () {
        setTimeout(function () {
            var fmt = data.format || 'a4';
            if (fmt === 'thermal') {
                if (typeof openThermalPrintByUID === 'function') {
                    openThermalPrintByUID(data.transUID, data.moduleUID, null);
                }
            } else {
                if (typeof openA4PrintByUID === 'function') {
                    openA4PrintByUID(data.transUID, data.moduleUID, fmt, null);
                }
            }
        }, 600);
    });
}());
