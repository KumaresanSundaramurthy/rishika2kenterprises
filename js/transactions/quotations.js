// ── Quotations list — module-specific JS ─────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

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
