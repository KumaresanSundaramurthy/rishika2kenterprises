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
    var $el    = $('#' + key);
    var wrapId = 'vendorGroup_' + key;

    if (!$el.closest('.vendor-search-group').length) {
        $el.wrap('<div class="input-group input-group-sm input-group-merge vendor-search-group" id="' + wrapId + '"></div>');
        $('<span class="input-group-text p-2"><i class="icon-base bx bx-search"></i></span>').insertBefore($el);
    }

    $el.select2({
        placeholder: 'Search Vendor by Name, Mobile, GSTIN, Company.',
        minimumInputLength: 0,
        allowClear: true,
        dropdownParent: $('#' + wrapId),
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (d) {
            if (d.loading || !d.name) return d.text;

            // Balance HTML (right side)
            var balHtml = '';
            if (d.balance !== undefined && d.balance !== null) {
                var isZero   = (d.balance === 0 || d.balance === '0');
                var isCredit = !isZero && (d.balanceType === 'Credit');
                var balColor = isZero ? '#6c757d' : (isCredit ? '#dc3545' : '#198754');
                var balLabel = isZero ? 'No Balance' : (isCredit ? 'To Pay' : 'To Collect');
                var balAmt   = parseFloat(d.balance || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                balHtml = '<div style="text-align:right;white-space:nowrap;flex-shrink:0;">' +
                    '<div style="font-weight:600;color:' + balColor + ';font-size:.85rem;">' + (genSettings.CurrenySymbol || '₹') + ' ' + balAmt + '</div>' +
                    '<div style="font-size:.72rem;color:' + balColor + ';">' + balLabel + '</div>' +
                '</div>';
            }

            // Left side: name + optional company name
            var companyHtml = d.companyName
                ? '<div style="font-size:.75rem;color:#6c757d;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + d.companyName + '</div>'
                : '';

            return $(
                '<div style="display:flex;align-items:center;gap:8px;">' +
                    '<div style="flex:1;min-width:0;">' +
                        '<div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + d.name + '</div>' +
                        companyHtml +
                    '</div>' +
                    balHtml +
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
