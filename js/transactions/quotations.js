// ── Quotations list — module-specific JS ─────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// -- WhatsApp link handler ---------------------------------------------------
$(document).on('click', '.inv-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// -- Auto-attach quotation PDF when comm modal switches to Email tab ---------
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 101 || !recordUID || _commPdfAutoAttached) return;

    _commPdfAutoAttached = true;

    setTimeout(function () {
        _initCommDropzone();

        $.ajax({
            url   : '/quotations/getQuotationPdfBase64',
            method: 'POST',
            data  : { TransUID: recordUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error || !resp.Base64) { _commPdfAutoAttached = false; return; }
                if (!_commDropzone) { _commPdfAutoAttached = false; return; }
                try {
                    var binary = atob(resp.Base64);
                    var bytes  = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                    var blob = new Blob([bytes], { type: 'application/pdf' });
                    var file = new File([blob], resp.Filename || 'quotation.pdf', { type: 'application/pdf' });
                    _commDropzone.addFile(file);
                } catch (ex) {
                    _commPdfAutoAttached = false;
                }
            },
            error: function () { _commPdfAutoAttached = false; }
        });
    }, 150);
});

var _quotConfig = {
    url:            '/quotations/getQuotationsPageDetails/',
    tabCountClass:  '.quot-tab-count',
    statusTabClass: '.quot-status-tab',
    errorMessage:   'Failed to load quotations.',
    onSuccess: function (response) {
        if (response.SummaryStats) updateQuotStatCards(response.SummaryStats);
    }
};

function getQuotationsDetails(pageNo, rowLimit, filter) {
    loadTransactionList(_quotConfig, pageNo, rowLimit, filter);
}

function updateQuotStatCards(stats) {
    var cur = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var dec = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? (JwtData.GenSettings.DecimalPoints || 2) : 2;

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
