function getQuotationsDetails(pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;
    $('.quot-tab-count').text('').addClass('d-none');
    $.ajax({
        url: '/quotations/getQuotationsPageDetails/' + pageNo,
        method: 'POST',
        cache: false,
        data: {
            RowLimit:              rowLimit,
            PageNo:                pageNo,
            Filter:                filter,
            ModuleId:              ModuleId,
            [CsrfName]:            CsrfToken,
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger m-2"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                var count   = response.TotalCount || 0;
                var $active = $('.quot-status-tab.active');
                $active.find('.quot-tab-count').text(count > 0 ? count : '').removeClass('d-none');
                initTooltips();
                // Refresh stat cards if summary stats returned
                if (response.SummaryStats) {
                    updateQuotStatCards(response.SummaryStats);
                }
            }
        },
        error: function() {
            $(ModulePag).html('<div class="alert alert-danger m-2">Failed to load quotations.</div>');
        }
    });
}

function updateQuotStatCards(stats) {
    var cur = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var dec = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? (JwtData.GenSettings.DecimalPoints || 2) : 2;

    function fmt(v) {
        return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    function cnt(key) {
        return stats[key] ? (stats[key].count || 0) : 0;
    }
    function amt(key) {
        return stats[key] ? (stats[key].amount || 0) : 0;
    }

    // All = sum excluding Draft, Cancelled, Rejected
    var excludeKeys = ['Draft', 'Cancelled', 'Rejected', 'Expired'];
    var cntAll = 0, amtAll = 0;
    $.each(stats, function(k, v) {
        if (excludeKeys.indexOf(k) === -1) {
            cntAll += (v.count  || 0);
            amtAll += (v.amount || 0);
        }
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

// ── helpers ──────────────────────────────────────────────
// getDateRange(), formatDate() are in /js/common/datefilter.js

function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        if (!bootstrap.Tooltip.getInstance(el)) new bootstrap.Tooltip(el);
    });
}

function debounce(fn, delay) {
    var t;
    return function () {
        var ctx = this, args = arguments;
        clearTimeout(t);
        t = setTimeout(function () { fn.apply(ctx, args); }, delay);
    };
}
