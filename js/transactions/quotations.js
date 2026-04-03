function getQuotationsDetails(pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

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
                $(ModulePag).html(response.pagination);
                $(ModuleTable + ' tbody').html(response.dataList);
                var count = response.ResultCount || 0;
                $('#quotTotalBadge').text(count > 0 ? count : '');
                initTooltips();
            }
        },
        error: function() {
            $(ModulePag).html('<div class="alert alert-danger m-2">Failed to load quotations.</div>');
        }
    });
}

// ── helpers ──────────────────────────────────────────────
function getDateRange(range) {
    var today  = new Date();
    var from   = '';
    var to     = formatDate(today);

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
            var q = Math.floor(today.getMonth() / 3);
            from = formatDate(new Date(today.getFullYear(), (q - 1) * 3, 1));
            to   = formatDate(new Date(today.getFullYear(), q * 3, 0));
            break;
        case 'fy_25_26':
            from = '2025-04-01'; to = '2026-03-31';
            break;
        default:
            from = ''; to = '';
    }
    return { from: from, to: to };
}

function formatDate(date) {
    var d = String(date.getDate()).padStart(2, '0');
    var m = String(date.getMonth() + 1).padStart(2, '0');
    return date.getFullYear() + '-' + m + '-' + d;
}

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

function showFormError(message) {
    Swal.fire({ icon: 'error', title: 'Validation Error', text: message });
}

function setFormLoading(isLoading) {
    var $btns = $('#addQuotationForm button[type="submit"]');
    if (isLoading) {
        $btns.prop('disabled', true);
        $btns.filter('[value="save"]').html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
    } else {
        $btns.prop('disabled', false);
        $btns.filter('[value="save"]').text('Save');
        $btns.filter('[value="draft"]').text('Save as Draft');
    }
}
