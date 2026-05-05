// ── Purchases list — module-specific JS ──────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getPurchasesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/purchases/getPurchasesPageDetails/',
        tabCountClass:  '.purch-tab-count',
        statusTabClass: '.purch-status-tab',
        errorMessage:   'Failed to load purchase bills.',
    }, pageNo, rowLimit, filter);
}

function searchVendors(key) {
    $('#' + key).select2({
        placeholder: 'Search Vendor by Name, Mobile, GSTIN, Company.',
        minimumInputLength: 0,
        allowClear: true,
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (d) {
            if (d.loading || !d.name) return d.text;
            var area = d.area
                ? '<span style="color:#6c757d;font-size:.78rem;">' + d.area + '</span>'
                : '<span style="color:transparent;font-size:.78rem;">-</span>';
            return $(
                '<div style="display:flex;align-items:center;gap:6px;">' +
                    '<div style="flex:1;min-width:0;">' +
                        '<div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + d.name + '</div>' +
                        '<div>' + area + '</div>' +
                    '</div>' +
                '</div>'
            );
        },
        templateSelection: function (d) {
            if (!d.id) return d.text;
            if (d.name) return d.area ? d.name + ' (' + d.area + ')' : d.name;
            return d.text;
        },
        ajax: {
            url: '/transactions/searchVendors',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, type: 'public' };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists };
            },
            cache: true
        }
    }).on('select2:close', function () {
        AjaxLoading = 1;
    });
}
