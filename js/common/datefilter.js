/**
 * R2K Date Filter — Reusable date range utility
 * Provides: getDateRange(), formatDate(), getDateRangeLabel(), initDateFilter()
 *
 * Usage on any page:
 *   1. Include this file
 *   2. Call initDateFilter(options) — see below
 */

// ── Core date helpers ────────────────────────────────────────────────────────

function formatDate(date) {
    var d = String(date.getDate()).padStart(2, '0');
    var m = String(date.getMonth() + 1).padStart(2, '0');
    return date.getFullYear() + '-' + m + '-' + d;
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return parts[2] + ' ' + months[parseInt(parts[1]) - 1] + ' ' + parts[0];
}

function getDateRange(range) {
    var today = new Date();
    var from  = '';
    var to    = formatDate(today);
    var m     = today.getMonth();
    var y     = today.getFullYear();

    // Indian fiscal year starts April 1
    function getFYStart(yr) { return yr + '-04-01'; }
    function getFYEnd(yr)   { return (yr + 1) + '-03-31'; }
    function currentFYYear() { return m >= 3 ? y : y - 1; }

    switch (range) {
        case 'today':
            from = to = formatDate(today);
            break;
        case 'yesterday':
            var yest = new Date(today); yest.setDate(today.getDate() - 1);
            from = to = formatDate(yest);
            break;
        case 'this_week':
            var ws = new Date(today); ws.setDate(today.getDate() - today.getDay());
            from = formatDate(ws);
            break;
        case 'last_week':
            var lws = new Date(today); lws.setDate(today.getDate() - today.getDay() - 7);
            var lwe = new Date(lws);   lwe.setDate(lws.getDate() + 6);
            from = formatDate(lws); to = formatDate(lwe);
            break;
        case 'last_7_days':
            var l7 = new Date(today); l7.setDate(today.getDate() - 6);
            from = formatDate(l7);
            break;
        case 'this_month':
            from = formatDate(new Date(y, m, 1));
            break;
        case 'previous_month':
            from = formatDate(new Date(y, m - 1, 1));
            to   = formatDate(new Date(y, m, 0));
            break;
        case 'last_30_days':
            var l30 = new Date(today); l30.setDate(today.getDate() - 29);
            from = formatDate(l30);
            break;
        case 'this_quarter':
            var qStart = Math.floor(m / 3) * 3;
            from = formatDate(new Date(y, qStart, 1));
            to   = formatDate(new Date(y, qStart + 3, 0));
            break;
        case 'previous_quarter':
            var pqStart = (Math.floor(m / 3) - 1) * 3;
            var pqYear  = pqStart < 0 ? y - 1 : y;
            pqStart     = ((pqStart % 12) + 12) % 12;
            from = formatDate(new Date(pqYear, pqStart, 1));
            to   = formatDate(new Date(pqYear, pqStart + 3, 0));
            break;
        case 'this_year':
            from = y + '-01-01';
            break;
        case 'last_year':
            from = (y - 1) + '-01-01'; to = (y - 1) + '-12-31';
            break;
        case 'last_365_days':
            var l365 = new Date(today); l365.setDate(today.getDate() - 364);
            from = formatDate(l365);
            break;
        case 'current_fy':
            var cfy = currentFYYear();
            from = getFYStart(cfy); to = getFYEnd(cfy);
            break;
        case 'previous_fy':
            var pfy = currentFYYear() - 1;
            from = getFYStart(pfy); to = getFYEnd(pfy);
            break;
        case 'fy_25_26':
            from = '2025-04-01'; to = '2026-03-31';
            break;
        default:
            from = ''; to = '';
    }
    return { from: from, to: to };
}

/**
 * Returns a human-readable date range string for a given range key.
 * Used for hover preview on dropdown items.
 */
function getDateRangeLabel(range) {
    var r = getDateRange(range);
    if (!r.from && !r.to) return '';
    if (r.from === r.to)  return formatDateDisplay(r.from);
    return formatDateDisplay(r.from) + ' – ' + formatDateDisplay(r.to);
}

// ── initDateFilter ────────────────────────────────────────────────────────────
/**
 * Initialises the date filter dropdown on a page.
 *
 * @param {object} opts
 *   btnId        {string}   id of the dropdown toggle button          (default: 'dateFilterBtn')
 *   labelId      {string}   id of the span showing selected label     (default: 'dateFilterLabel')
 *   fromId       {string}   id of hidden from-date input (custom)     (default: 'customDateFrom')
 *   toId         {string}   id of hidden to-date input   (custom)     (default: 'customDateTo')
 *   onApply      {function} callback(from, to, label) when a range is selected
 */
function initDateFilter(opts) {
    opts = opts || {};
    var btnId   = opts.btnId   || 'dateFilterBtn';
    var labelId = opts.labelId || 'dateFilterLabel';
    var fromId  = opts.fromId  || 'customDateFrom';
    var toId    = opts.toId    || 'customDateTo';
    var onApply = opts.onApply || function () {};

    // ── Hover preview ────────────────────────────────────────────────────────
    $(document).on('mouseenter', '.date-option[data-range]', function () {
        var range = $(this).data('range');
        if (!range || range === 'custom') return;
        var label = getDateRangeLabel(range);
        if (!label) return;
        var $preview = $(this).find('.df-date-preview');
        if (!$preview.length) {
            $(this).append('<span class="df-date-preview"></span>');
            $preview = $(this).find('.df-date-preview');
        }
        $preview.text(label);
    }).on('mouseleave', '.date-option[data-range]', function () {
        $(this).find('.df-date-preview').remove();
    });

    // ── Option click ─────────────────────────────────────────────────────────
    $(document).on('click', '.date-option[data-range]', function (e) {
        e.stopPropagation();
        var range = $(this).data('range');

        if (range === 'custom') {
            // Show inline custom picker — don't close dropdown
            $('#r2k-custom-date-picker').toggleClass('d-none');
            return;
        }

        // Close custom picker if open
        $('#r2k-custom-date-picker').addClass('d-none');

        var dates = getDateRange(range);
        var label = $.trim($(this).clone().children('.df-date-preview').remove().end().text());

        $('.date-option').removeClass('active');
        $(this).addClass('active');
        $('#' + labelId).text(label);

        // Close dropdown
        var $btn = $('#' + btnId);
        var dd   = bootstrap.Dropdown.getInstance($btn[0]);
        if (dd) dd.hide();

        onApply(dates.from, dates.to, label);
    });

    // ── Custom date apply ────────────────────────────────────────────────────
    $(document).on('click', '#r2k-custom-apply', function () {
        var from  = $('#' + fromId).val();
        var to    = $('#' + toId).val();
        if (!from || !to) { alert('Please select both From and To dates.'); return; }
        if (from > to)    { alert('From date cannot be after To date.'); return; }

        var label = formatDateDisplay(from) + ' – ' + formatDateDisplay(to);
        $('#' + labelId).text(label);
        $('.date-option').removeClass('active');
        $('.date-option[data-range="custom"]').addClass('active');

        var $btn = $('#' + btnId);
        var dd   = bootstrap.Dropdown.getInstance($btn[0]);
        if (dd) dd.hide();
        $('#r2k-custom-date-picker').addClass('d-none');

        onApply(from, to, label);
    });

    // ── Custom date clear ────────────────────────────────────────────────────
    $(document).on('click', '#r2k-custom-clear', function () {
        $('#' + fromId).val('');
        $('#' + toId).val('');
        $('#r2k-custom-date-picker').addClass('d-none');
        $('#' + labelId).text('All Dates');
        $('.date-option').removeClass('active');
        $('.date-option[data-range=""]').addClass('active');

        var $btn = $('#' + btnId);
        var dd   = bootstrap.Dropdown.getInstance($btn[0]);
        if (dd) dd.hide();

        onApply('', '', 'All Dates');
    });
}

// ── buildDateFilterHtml ───────────────────────────────────────────────────────
/**
 * Returns the full dropdown <ul> inner HTML for the date filter.
 * Paste the output inside your <ul class="dropdown-menu ..."> element.
 *
 * @param {string} fromId  id for the custom from-date input
 * @param {string} toId    id for the custom to-date input
 */
function buildDateFilterHtml(fromId, toId) {
    fromId = fromId || 'customDateFrom';
    toId   = toId   || 'customDateTo';
    return [
        '<li><a class="dropdown-item date-option active" data-range=""><i class="bx bx-list-ul me-2"></i>All Dates</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option" data-range="today">Today</a></li>',
        '<li><a class="dropdown-item date-option" data-range="yesterday">Yesterday</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option" data-range="this_week">This Week</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_week">Last Week</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option" data-range="this_month">This Month</a></li>',
        '<li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_30_days">Last 30 Days</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option" data-range="this_quarter">This Quarter</a></li>',
        '<li><a class="dropdown-item date-option" data-range="previous_quarter">Previous Quarter</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_365_days">Last 365 Days</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option fw-semibold" data-range="current_fy"><i class="bx bxs-star text-warning me-1"></i>Current FY</a></li>',
        '<li><a class="dropdown-item date-option" data-range="previous_fy">Previous FY</a></li>',
        '<li><hr class="dropdown-divider"></li>',
        '<li><a class="dropdown-item date-option" data-range="custom"><i class="bx bx-calendar-edit me-2"></i>Custom Range</a></li>',
        '<li id="r2k-custom-date-picker" class="d-none px-2 py-2" style="min-width:220px;">',
        '  <div class="mb-1"><label style="font-size:.75rem;color:#666;">From</label>',
        '  <input type="date" id="' + fromId + '" class="form-control form-control-sm"></div>',
        '  <div class="mb-2"><label style="font-size:.75rem;color:#666;">To</label>',
        '  <input type="date" id="' + toId + '" class="form-control form-control-sm"></div>',
        '  <div class="d-flex gap-2">',
        '    <button id="r2k-custom-apply" class="btn btn-primary btn-sm flex-grow-1">Apply</button>',
        '    <button id="r2k-custom-clear" class="btn btn-outline-secondary btn-sm">Clear</button>',
        '  </div>',
        '</li>',
    ].join('');
}
