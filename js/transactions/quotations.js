// ── Quotations list — module-specific JS ─────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// -- WhatsApp link handler ---------------------------------------------------
$(document).on('click', '.inv-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// -- Register smart PDF attach alert when comm modal opens in Email tab -------
// Priority: direct CDN fetch (if PdfPath in row data) → AJAX fallback
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 101 || !recordUID) return;
    if (typeof _setupCommPdfAlert !== 'function') return;

    var rowData   = (typeof _commRowData !== 'undefined') ? _commRowData : null;
    var pdfPath   = rowData ? (rowData.pdfPath || '') : '';
    var cdnBase   = (typeof _r2CdnBase    !== 'undefined') ? (_r2CdnBase   || '') : '';
    var docNumber = rowData ? (rowData.docNumber || '') : '';
    var filename  = (docNumber || 'quotation').replace(/[^A-Za-z0-9_.\-]/g, '_') + '.pdf';

    _setupCommPdfAlert(docNumber, function (onSuccess) {
        if (pdfPath && cdnBase) {
            var cdnUrl     = cdnBase.replace(/\/+$/, '') + '/' + pdfPath.replace(/^\/+/, '');
            var controller = new AbortController();
            var cdnTimeout = setTimeout(function () { controller.abort(); }, 3000);
            fetch(cdnUrl, { signal: controller.signal })
                .then(function (r) {
                    clearTimeout(cdnTimeout);
                    if (!r.ok) throw new Error('CDN ' + r.status);
                    return r.blob();
                })
                .then(function (blob) {
                    onSuccess(new File([blob], filename, { type: 'application/pdf' }));
                })
                .catch(function () {
                    clearTimeout(cdnTimeout);
                    _quotFetchPdfAjax(recordUID, moduleUID, filename, onSuccess);
                });
        } else {
            _quotFetchPdfAjax(recordUID, moduleUID, filename, onSuccess);
        }
    });
});

/**
 * @param {number}   recordUID
 * @param {number}   moduleUID
 * @param {string}   filename
 * @param {Function} onSuccess
 * @returns {void}
 */
function _quotFetchPdfAjax(recordUID, moduleUID, filename, onSuccess) {
    $.ajax({
        url   : '/transactions/getTransactionPdfBase64',
        method: 'POST',
        data  : { TransUID: recordUID, ModuleUID: moduleUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Base64) { if (onSuccess) onSuccess(null); return; }
            try {
                var binary = atob(resp.Base64);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                var blob = new Blob([bytes], { type: 'application/pdf' });
                if (onSuccess) onSuccess(new File([blob], resp.Filename || filename, { type: 'application/pdf' }));
            } catch (ex) {
                if (onSuccess) onSuccess(null);
            }
        },
        error: function () { if (onSuccess) onSuccess(null); }
    });
}

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _quotSelectAllMode = false;
var _quotTotalRecords  = 0;
var _quotPageCount     = 0;

/** @returns {void} */
function _quotUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_quotSelectAllMode, _quotTotalRecords, _quotPageCount, 'quot', 'quotations');
}
/** @returns {void} */
function _quotClearSelectAll() {
    _quotSelectAllMode = false;
    clearTransSelectAllDom('quot');
}
/** @returns {void} */
function deleteMultipleQuotations() {
    deleteMultipleTrans(101, _quotSelectAllMode, _quotClearSelectAll, function () {
        getQuotationsDetails(PageNo, RowLimit, Filter);
    });
}

var _quotConfig = {
    url:            '/transactions/getPageDetails/101/',
    tabCountClass:  '.trans-tab-count',
    statusTabClass: '.quot-status-tab',
    errorMessage:   'Failed to load quotations.',
    onSuccess: function (response) {
        _quotTotalRecords = parseInt(response.TotalCount) || 0;
        _quotPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
        _quotUpdateSelectAllBanner();
        if (response.SummaryStats) updateQuotStatCards(response.SummaryStats);
    }
};

/**
 * @param {number}        pageNo
 * @param {number}        rowLimit
 * @param {Object}        filter
 * @param {Function}      [afterLoad]
 * @returns {void}
 */
function getQuotationsDetails(pageNo, rowLimit, filter, afterLoad) {
    if (typeof afterLoad === 'function') {
        var _cfg = $.extend({}, _quotConfig, {
            onSuccess: function (resp) {
                if (_quotConfig.onSuccess) _quotConfig.onSuccess(resp);
                afterLoad(resp);
            }
        });
        loadTransactionList(_cfg, pageNo, rowLimit, filter);
    } else {
        loadTransactionList(_quotConfig, pageNo, rowLimit, filter);
    }
}

/**
 * @param {Object} stats
 * @returns {void}
 */
function updateQuotStatCards(stats) {
    if (!stats || Object.keys(stats).length === 0) return;
    var cur = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var dec = 2;

    function fmt(v) {
        return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    function cnt(key) { return stats[key] ? (stats[key].count  || 0) : 0; }
    function amt(key) { return stats[key] ? (stats[key].amount || 0) : 0; }

    var excludeKeys = ['Draft', 'Cancelled', 'Rejected', 'Expired'];
    var cntAll = 0, amtAll = 0;
    $.each(stats, function (k, v) {
        if (excludeKeys.indexOf(k) === -1) { cntAll += (v.count || 0); amtAll += (v.amount || 0); }
    });

    $('[data-stat-filter="All"] .trans-stat-count').text(cntAll.toLocaleString('en-IN'));
    $('[data-stat-filter="All"] .trans-stat-amount').text(fmt(amtAll));
    $('[data-stat-filter="Open"] .trans-stat-count').text(cnt('Pending').toLocaleString('en-IN'));
    $('[data-stat-filter="Open"] .trans-stat-amount').text(fmt(amt('Pending')));
    $('[data-stat-filter="Accepted"] .trans-stat-count').text(cnt('Accepted').toLocaleString('en-IN'));
    $('[data-stat-filter="Accepted"] .trans-stat-amount').text(fmt(amt('Accepted')));
    $('[data-stat-filter="Converted"] .trans-stat-count').text(cnt('Converted').toLocaleString('en-IN'));
    $('[data-stat-filter="Converted"] .trans-stat-amount').text(fmt(amt('Converted')));
    $('[data-stat-filter="Draft"] .trans-stat-count').text(cnt('Draft').toLocaleString('en-IN'));
}

