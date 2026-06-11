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
    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var dy = parseInt(parts[2], 10);
    var mo = parseInt(parts[1], 10) - 1;
    var yr = parseInt(parts[0], 10);
    if (isNaN(dy) || mo < 0 || mo > 11) return dateStr;

    var fmt = (typeof _transListDateFormat !== 'undefined' && _transListDateFormat)
              ? _transListDateFormat
              : 'd M Y';

    var tokens = {
        'd': String(dy).padStart(2, '0'),
        'j': String(dy),
        'm': String(mo + 1).padStart(2, '0'),
        'n': String(mo + 1),
        'Y': String(yr),
        'y': String(yr).slice(-2),
        'F': monthsFull[mo],
        'M': months[mo]
    };
    return fmt.replace(/[djmnYyFM]/g, function (tok) {
        return tokens[tok] !== undefined ? tokens[tok] : tok;
    });
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

// ── Global hover preview (works on every page — no initDateFilter call needed) ──
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

// ── Auto-populate every #dateFilterBtn dropdown from buildDateFilterHtml ──────
// Runs on every transaction page — replaces any hardcoded <li> items in PHP
// with the single standard list maintained here. Pages that call initDateFilter()
// explicitly will overwrite this with their own fromId/toId; that is intentional.
$(document).ready(function () {
    var $btn = $('#dateFilterBtn');
    if (!$btn.length) return;
    var $menu = $btn.closest('.dropdown').find('ul.dropdown-menu').first();
    if (!$menu.length) return;
    if (!$menu.attr('id')) $menu.attr('id', 'dateFilterMenu');
    $menu.html(buildDateFilterHtml('customDateFrom', 'customDateTo'));

    // Apply the saved preference active state from PHP (r2kSavedDateRange defined in trans_footer_script.php)
    var savedRange = (typeof r2kSavedDateRange !== 'undefined') ? r2kSavedDateRange : null;
    var savedLabel = (typeof r2kSavedDateLabel !== 'undefined') ? r2kSavedDateLabel : '';
    if (savedRange !== null) {
        $('.date-option').removeClass('active');
        $('.date-option[data-range="' + savedRange + '"]').addClass('active');
        if (savedLabel) $('#dateFilterLabel').text(savedLabel);
    }

    // Seed the global Filter object so tab / sort / search AJAX calls carry the saved date range.
    // Without this, clicking a status tab after page load sends no DateFrom/DateTo to the server.
    if (savedRange && savedRange !== '' && savedRange !== 'all' && typeof Filter !== 'undefined') {
        var _savedDr = getDateRange(savedRange);
        Filter.DateFrom = _savedDr.from;
        Filter.DateTo   = _savedDr.to;
    }
});

// ── Date range selection — single global handler, fires r2k:datechange event ──
// All per-page code should listen to $(document).on('r2k:datechange', fn)
// instead of registering their own .date-option click handlers.
var _r2kAllDatesSkipConfirm = false;

$(document).on('click', '.date-option[data-range]', function (e) {
    var $btn = $('#dateFilterBtn');
    if (!$btn.length) return;
    var range = $(this).data('range');

    // Toggle custom date picker panel; all other logic is in the Apply/Clear handlers
    if (range === 'custom') {
        e.stopPropagation();
        $('#r2k-custom-date-picker').toggleClass('d-none');
        return;
    }

    // Close custom picker when any standard range is selected
    $('#r2k-custom-date-picker').addClass('d-none');

    // Confirm before removing the date filter — loading all records can be slow
    if ((range === '' || range === 'all') && !_r2kAllDatesSkipConfirm) {
        e.stopImmediatePropagation();
        var $el = $(this);
        Swal.fire({
            icon             : 'warning',
            title            : 'Load All Records?',
            html             : 'Loading data <strong>without a date filter</strong> may be slow and return a large number of records.<br><br>Are you sure you want to proceed?',
            showCancelButton : true,
            confirmButtonText: 'Proceed',
            cancelButtonText : 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor : '#6c757d',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            _r2kAllDatesSkipConfirm = true;
            $el.trigger('click');
            _r2kAllDatesSkipConfirm = false;
        });
        return;
    }

    // Active item + clean label (strip hover-preview span before reading text)
    $('.date-option').removeClass('active');
    $(this).addClass('active');
    var _cleanLabel = $.trim($(this).clone().children('.df-date-preview').remove().end().text());
    if (_cleanLabel) $('#dateFilterLabel').text(_cleanLabel);

    // Compute dates once
    var dr = (range && range !== '' && range !== 'all') ? getDateRange(range) : { from: '', to: '' };

    // Button state + bold date display
    if (dr.from) {
        $btn.addClass('r2k-date-active');
        var f = formatDateDisplay(dr.from);
        var t = formatDateDisplay(dr.to);
        $('#dateFilterDates').text(dr.from === dr.to ? f : f + ' – ' + t).show();
    } else {
        $btn.removeClass('r2k-date-active');
        $('#dateFilterDates').text('').hide();
    }

    // Close dropdown (data-bs-auto-close="outside" keeps it open on inner clicks)
    var _ddInst = bootstrap.Dropdown.getInstance($btn[0]);
    if (_ddInst) _ddInst.hide();

    // Persist preference
    var pageKey = location.pathname.split('/').filter(Boolean)[0] || '';
    if (pageKey) {
        var _prefData = { PreferenceKey: 'df_' + pageKey, PreferenceValue: String(range) };
        if (typeof CsrfName !== 'undefined') _prefData[CsrfName] = CsrfToken;
        $.post('/userpreferences/save', _prefData);
    }

    // Broadcast to page-level listeners
    $(document).trigger('r2k:datechange', [{ range: range, from: dr.from, to: dr.to }]);
});

// ── Custom range Apply ────────────────────────────────────────────────────────
$(document).on('click', '#r2k-custom-apply', function () {
    var $picker = $('#r2k-custom-date-picker');
    var $inputs = $picker.find('input[type="date"]');
    var from    = $inputs.eq(0).val();
    var to      = $inputs.eq(1).val();
    if (!from || !to) { alert('Please select both From and To dates.'); return; }
    if (from > to)    { alert('From date cannot be after To date.'); return; }

    var f     = formatDateDisplay(from);
    var t     = formatDateDisplay(to);
    var label = from === to ? f : f + ' – ' + t;
    $('#dateFilterLabel').text('Custom');
    $('#dateFilterDates').text(label).show();
    $('#dateFilterBtn').addClass('r2k-date-active');
    $('.date-option').removeClass('active');
    $('.date-option[data-range="custom"]').addClass('active');
    $picker.addClass('d-none');

    var _ddInst = bootstrap.Dropdown.getInstance($('#dateFilterBtn')[0]);
    if (_ddInst) _ddInst.hide();

    $(document).trigger('r2k:datechange', [{ range: 'custom', from: from, to: to }]);
});

// ── Custom range Clear ────────────────────────────────────────────────────────
$(document).on('click', '#r2k-custom-clear', function () {
    var $picker = $('#r2k-custom-date-picker');
    $picker.find('input[type="date"]').val('');
    $picker.addClass('d-none');
    $('#dateFilterLabel').text('All Dates');
    $('#dateFilterDates').text('').hide();
    $('#dateFilterBtn').removeClass('r2k-date-active');
    $('.date-option').removeClass('active');
    $('.date-option[data-range=""]').addClass('active');

    var _ddInst = bootstrap.Dropdown.getInstance($('#dateFilterBtn')[0]);
    if (_ddInst) _ddInst.hide();

    $(document).trigger('r2k:datechange', [{ range: '', from: '', to: '' }]);
});

// ── initDateFilter ────────────────────────────────────────────────────────────
/**
 * Optional helper — only needed when the page uses non-default custom picker
 * input IDs, or has a legacy onApply callback.
 *
 * All display logic, active state, and preference saving are handled globally
 * above. Per-page code should listen to the r2k:datechange event:
 *
 *   $(document).on('r2k:datechange', function (e, dr) {
 *       Filter.DateFrom = dr.from;
 *       Filter.DateTo   = dr.to;
 *       PageNo = 1;
 *       loadMyData();
 *   });
 *
 * @param {object} opts
 *   fromId   {string}   id of the from-date input for custom range (default: 'customDateFrom')
 *   toId     {string}   id of the to-date input for custom range   (default: 'customDateTo')
 *   onApply  {function} legacy callback(from, to) — prefer r2k:datechange instead
 */
function initDateFilter(opts) {
    opts = opts || {};
    var fromId = opts.fromId || 'customDateFrom';
    var toId   = opts.toId   || 'customDateTo';

    // Rebuild menu only when non-default input IDs are needed
    if (fromId !== 'customDateFrom' || toId !== 'customDateTo') {
        var $menu = $('#dateFilterBtn').closest('.dropdown').find('ul.dropdown-menu').first();
        if ($menu.length) $menu.html(buildDateFilterHtml(fromId, toId));
    }

    // Legacy onApply — bridged via event for backward compatibility
    if (typeof opts.onApply === 'function') {
        $(document).on('r2k:datechange', function (e, dr) {
            opts.onApply(dr.from, dr.to);
        });
    }
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
