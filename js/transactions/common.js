// ── Transaction pages — shared utilities ──────────────────────────────────────
// Requires: jQuery, Bootstrap, datefilter.js (getDateRange, formatDate),
//           and page globals: PageNo, RowLimit, Filter, ModuleId,
//           ModuleTable, ModulePag, CsrfName, CsrfToken

function initTooltips() {
    // MutationObserver in default.js auto-inits newly added tooltip elements,
    // so this is a no-op safety call for pages that invoke it explicitly after AJAX.
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        if (!bootstrap.Tooltip.getInstance(el)) {
            new bootstrap.Tooltip(el, { container: 'body', trigger: 'hover' });
        }
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

/**
 * Generic transaction list AJAX loader.
 *
 * config = {
 *   url            {string}   base URL — pageNo is appended  e.g. '/quotations/getQuotationsPageDetails/'
 *   tabCountClass  {string}   badge selector                 e.g. '.quot-tab-count'
 *   statusTabClass {string}   status tab selector            e.g. '.quot-status-tab'
 *   errorMessage   {string}   message shown on AJAX failure
 *   onSuccess      {function} optional extra callback(response)
 * }
 */
function loadTransactionList(config, pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;
    $(config.tabCountClass).text('').addClass('d-none');
    $.ajax({
        url:    config.url + pageNo,
        method: 'POST',
        cache:  false,
        data: {
            RowLimit:   rowLimit,
            PageNo:     pageNo,
            Filter:     filter,
            ModuleId:   ModuleId,
            [CsrfName]: CsrfToken,
        },
        success: function (response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger m-2"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                var count   = response.TotalCount || 0;
                var $active = $(config.statusTabClass + '.active');
                var $badge  = $active.find(config.tabCountClass);
                if (count > 0) {
                    $badge.text(count).removeClass('d-none');
                } else {
                    $badge.text('').addClass('d-none');
                }
                initTooltips();
                $(window).trigger('scroll');
                if (config.onSuccess) config.onSuccess(response);
            }
        },
        error: function () {
            $(ModulePag).html('<div class="alert alert-danger m-2">' + config.errorMessage + '</div>');
        }
    });
}

// Note: Cancel and Delete confirmations are handled by each page's own
// -status-update and delete* click handlers (defined in each view's <script>).
