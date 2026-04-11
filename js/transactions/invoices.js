function getInvoicesDetails(pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;
    $('.inv-tab-count').text('').addClass('d-none');
    $.ajax({
        url: '/invoices/getInvoicesPageDetails/' + pageNo,
        method: 'POST',
        cache: false,
        data: {
            RowLimit:   rowLimit,
            PageNo:     pageNo,
            Filter:     filter,
            ModuleId:   ModuleId,
            [CsrfName]: CsrfToken,
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger m-2"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                var count   = response.TotalCount || 0;
                var $active = $('.inv-status-tab.active');
                $active.find('.inv-tab-count').text(count > 0 ? count : '').removeClass('d-none');
                initTooltips();
            }
        },
        error: function() {
            $(ModulePag).html('<div class="alert alert-danger m-2">Failed to load invoices.</div>');
        }
    });
}

// ── Date range helper ─────────────────────────────────────────────────────────
function getDateRange(range) {
    var today = new Date();
    var from  = '';
    var to    = formatDate(today);

    switch (range) {
        case 'today':
            from = to = formatDate(today);
            break;
        case 'yesterday':
            var y = new Date(today); y.setDate(today.getDate() - 1);
            from = to = formatDate(y);
            break;
        case 'this_week':
            var s = new Date(today); s.setDate(today.getDate() - today.getDay());
            from = formatDate(s);
            break;
        case 'last_week':
            var ls = new Date(today); ls.setDate(today.getDate() - today.getDay() - 7);
            var le = new Date(ls);    le.setDate(ls.getDate() + 6);
            from = formatDate(ls); to = formatDate(le);
            break;
        case 'last_7_days':
            var l7 = new Date(today); l7.setDate(today.getDate() - 6);
            from = formatDate(l7);
            break;
        case 'this_month':
            from = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
            break;
        case 'previous_month':
            from = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
            to   = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
            break;
        case 'last_30_days':
            var l30 = new Date(today); l30.setDate(today.getDate() - 29);
            from = formatDate(l30);
            break;
        case 'this_year':
            from = today.getFullYear() + '-01-01';
            break;
        case 'last_year':
            var ly = today.getFullYear() - 1;
            from = ly + '-01-01'; to = ly + '-12-31';
            break;
        case 'last_quarter':
            var q  = Math.floor(today.getMonth() / 3);
            var qs = new Date(today.getFullYear(), (q - 1) * 3, 1);
            var qe = new Date(today.getFullYear(), q * 3, 0);
            from = formatDate(qs); to = formatDate(qe);
            break;
        case 'fy_25_26':
            from = '2025-04-01'; to = '2026-03-31';
            break;
        default:
            from = ''; to = '';
    }
    return { from: from, to: to };
}

function formatDate(d) {
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
}

function debounce(fn, delay) {
    var t;
    return function () {
        clearTimeout(t);
        t = setTimeout(fn.bind(this, arguments), delay);
    };
}
