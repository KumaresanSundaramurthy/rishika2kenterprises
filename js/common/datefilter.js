/**
 * R2K Date Filter — Reusable date range utility
 * Provides: getDateRange(), formatDate(), getDateRangeLabel(), initDateFilter()
 *
 * Usage on any page:
 *   1. Include this file
 *   2. Listen to $(document).on('r2k:datechange', fn) for range changes
 *   3. Optionally call initDateFilter(options) for non-standard buttons
 */

// ── Core date helpers ────────────────────────────────────────────────────────

/**
 * @param {Date} date
 * @returns {string} YYYY-MM-DD
 */
function formatDate(date) {
    var d = String(date.getDate()).padStart(2, '0');
    var m = String(date.getMonth() + 1).padStart(2, '0');
    return date.getFullYear() + '-' + m + '-' + d;
}

/**
 * @param {string} dateStr  YYYY-MM-DD
 * @returns {string}        display string using _transListDateFormat
 */
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
              ? _transListDateFormat : 'd M Y';

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

/**
 * @param {string} range
 * @returns {{ from: string, to: string }}
 */
function getDateRange(range) {
    var today = new Date();
    var from  = '';
    var to    = formatDate(today);
    var m     = today.getMonth();
    var y     = today.getFullYear();

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
 * @param {string} range
 * @returns {string}  human-readable date range for hover preview
 */
function getDateRangeLabel(range) {
    var r = getDateRange(range);
    if (!r.from && !r.to) return '';
    if (r.from === r.to)  return formatDateDisplay(r.from);
    return formatDateDisplay(r.from) + ' – ' + formatDateDisplay(r.to);
}

// ── Global hover preview ──────────────────────────────────────────────────────
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

// ── Custom range modal state ──────────────────────────────────────────────────
var _crFp      = null;   // active flatpickr instance
var _crApplied = false;  // true between Apply click and hidden.bs.modal firing

// ── Custom range modal: year-select helpers ───────────────────────────────────
/**
 * Returns the displayed year for calendar slot idx (0 = From, 1 = To).
 * @param {object} fp   flatpickr instance
 * @param {number} idx  0 or 1
 * @returns {number}
 */
function _r2kMonthYr(fp, idx) {
    return fp.currentYear + Math.floor((fp.currentMonth + idx) / 12);
}

/**
 * Injects (or syncs) month + year <select> elements into each flatpickr month header.
 * Hides the static .cur-month span and native year spinner. Safe to call on every nav event.
 * @param {object} fp  flatpickr instance
 * @returns {void}
 */
function _r2kInjectYrSels(fp) {
    var curY   = new Date().getFullYear();
    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

    $(fp.calendarContainer).find('.flatpickr-month').each(function (idx) {
        var $m = $(this);
        var yr = _r2kMonthYr(fp, idx);
        var mo = (fp.currentMonth + idx) % 12;

        // ── Year select ──────────────────────────────────────────────────────
        $m.find('.numInputWrapper').css('display', 'none');
        var $exYr = $m.find('.r2k-yr-sel');
        if ($exYr.length) {
            $exYr.val(yr);
        } else {
            var yrHtml = '';
            for (var y = curY - 10; y <= curY + 5; y++) {
                yrHtml += '<option value="' + y + '"' + (y === yr ? ' selected' : '') + '>' + y + '</option>';
            }
            var $yrSel = $('<select class="r2k-yr-sel"></select>').html(yrHtml);
            (function (calIdx) {
                $yrSel.on('change', function () {
                    var newY     = parseInt(this.value, 10);
                    var curMonYr = _r2kMonthYr(fp, calIdx);
                    fp.changeYear(fp.currentYear + (newY - curMonYr));
                });
            })(idx);
            $m.find('.flatpickr-current-month').append($yrSel);
        }

        // ── Month select ─────────────────────────────────────────────────────
        // flatpickr ignores monthSelectorType:'dropdown' when showMonths > 1 and
        // always renders a static .cur-month span — replace it manually.
        $m.find('.cur-month').css('display', 'none');
        var $exMo = $m.find('.r2k-mo-sel');
        if ($exMo.length) {
            $exMo.val(mo);
        } else {
            var moHtml = '';
            MONTHS.forEach(function (name, i) {
                moHtml += '<option value="' + i + '"' + (i === mo ? ' selected' : '') + '>' + name + '</option>';
            });
            var $moSel = $('<select class="r2k-mo-sel"></select>').html(moHtml);
            (function (calIdx) {
                $moSel.on('change', function () {
                    var newMo       = parseInt(this.value, 10);
                    // For idx=0 navigate first calendar directly;
                    // for idx=1 back-calculate so second calendar lands on newMo.
                    var targetFirst = calIdx === 0 ? newMo : (newMo - 1 + 12) % 12;
                    var diff        = targetFirst - fp.currentMonth;
                    // Normalise to shortest path around the 12-month circle
                    if (diff > 6)  diff -= 12;
                    if (diff < -6) diff += 12;
                    if (diff !== 0) fp.changeMonth(diff, true);
                });
            })(idx);
            // Insert before numInputWrapper so DOM order is: [Month ▾] [Year ▾]
            $m.find('.numInputWrapper').before($moSel);
        }
    });
}

// ── Auto-populate #dateFilterBtn dropdown on every transaction page ───────────
$(document).ready(function () {
    var $btn = $('#dateFilterBtn');
    if (!$btn.length) return;
    var $menu = $btn.closest('.dropdown').find('ul.dropdown-menu').first();
    if (!$menu.length) return;
    if (!$menu.attr('id')) $menu.attr('id', 'dateFilterMenu');
    $menu.html(buildDateFilterHtml());

    var savedRange = (typeof r2kSavedDateRange !== 'undefined') ? r2kSavedDateRange : null;
    var savedLabel = (typeof r2kSavedDateLabel !== 'undefined') ? r2kSavedDateLabel : '';
    if (!savedRange || savedRange === '' || savedRange === 'all') {
        savedRange = 'this_month'; savedLabel = 'This Month';
    }
    if (!savedLabel || savedLabel === 'All Dates') savedLabel = 'This Month';

    var _initFrom = '', _initTo = '';

    if (savedRange.indexOf('custom|') === 0) {
        var _parts = savedRange.split('|');
        if (_parts.length === 3 && _parts[1] && _parts[2]) {
            _initFrom = _parts[1];
            _initTo   = _parts[2];
            var _cf = formatDateDisplay(_initFrom);
            var _ct = formatDateDisplay(_initTo);
            $('#dateFilterLabel').text('Custom');
            $('#dateFilterDates').text(_initFrom === _initTo ? _cf : _cf + ' – ' + _ct).show();
            $btn.addClass('r2k-date-active');
            $('.date-option').removeClass('active');
            $('.date-option[data-range="custom"]').addClass('active');
        }
    } else {
        $('.date-option').removeClass('active');
        $('.date-option[data-range="' + savedRange + '"]').addClass('active');
        if (savedLabel) $('#dateFilterLabel').text(savedLabel);

        var _initDr = getDateRange(savedRange);
        _initFrom = _initDr.from;
        _initTo   = _initDr.to;

        if (_initFrom) {
            $btn.addClass('r2k-date-active');
            if (!$('#dateFilterDates').text()) {
                var _if = formatDateDisplay(_initFrom);
                var _it = formatDateDisplay(_initTo);
                $('#dateFilterDates').text(_if === _it ? _if : _if + ' – ' + _it).show();
            }
        }
    }

    if (typeof Filter !== 'undefined') {
        Filter.DateFrom = _initFrom;
        Filter.DateTo   = _initTo;
    }
});

// ── Preference save (fire-and-forget) ────────────────────────────────────────
/**
 * @param {string} key
 * @param {string} value
 * @returns {void}
 */
function _r2kSavePref(key, value) {
    var body = new FormData();
    body.append('PreferenceKey',   String(key));
    body.append('PreferenceValue', String(value));
    if (typeof CsrfName !== 'undefined' && typeof CsrfToken !== 'undefined') {
        body.append(CsrfName, CsrfToken);
    }
    fetch('/userpreferences/save', { method: 'POST', body: body, keepalive: true })
        .catch(function () {});
}

// ── Preset range selection ────────────────────────────────────────────────────
$(document).on('click', '.date-option[data-range]', function (e) {
    var $btn = $('#dateFilterBtn');
    if (!$btn.length) return;
    var range = $(this).data('range');

    if (range === 'custom') {
        e.stopPropagation();
        var $modal = $('#r2kCustomRangeModal');
        if (!$modal.length) return;
        // Close dropdown first so it does not overlap the modal
        var _ddClose = bootstrap.Dropdown.getInstance($btn[0]);
        if (_ddClose) _ddClose.hide();
        // Store context so the Apply handler knows which button triggered the modal
        $modal
            .data('src-btn-id',   'dateFilterBtn')
            .data('src-label-id', 'dateFilterLabel')
            .data('src-dates-id', 'dateFilterDates')
            .removeData('src-on-apply');
        new bootstrap.Modal($modal[0]).show();
        return;
    }

    $('.date-option').removeClass('active');
    $(this).addClass('active');
    var _cleanLabel = $.trim($(this).clone().children('.df-date-preview').remove().end().text());
    if (_cleanLabel) $('#dateFilterLabel').text(_cleanLabel);

    var dr = (range && range !== '' && range !== 'all')
             ? getDateRange(range) : { from: '', to: '' };

    if (dr.from) {
        $btn.addClass('r2k-date-active');
        var f = formatDateDisplay(dr.from);
        var t = formatDateDisplay(dr.to);
        $('#dateFilterDates').text(dr.from === dr.to ? f : f + ' – ' + t).show();
    } else {
        $btn.removeClass('r2k-date-active');
        $('#dateFilterDates').text('').hide();
    }

    var _ddInst = bootstrap.Dropdown.getInstance($btn[0]);
    if (_ddInst) _ddInst.hide();

    var pageKey = location.pathname.split('/').filter(Boolean)[0] || '';
    if (pageKey) _r2kSavePref('df_' + pageKey, String(range));

    $(document).trigger('r2k:datechange', [{ range: range, from: dr.from, to: dr.to }]);
});

// ── Custom Range Modal — init flatpickr on open ───────────────────────────────
$(document).on('shown.bs.modal', '#r2kCustomRangeModal', function () {
    if (_crFp) { _crFp.destroy(); _crFp = null; }

    // Pre-fill dates if a custom range was previously saved
    var savedRange = (typeof r2kSavedDateRange !== 'undefined') ? r2kSavedDateRange : '';
    var _initDates = [];
    if (savedRange.indexOf('custom|') === 0) {
        var _p = savedRange.split('|');
        if (_p.length === 3 && _p[1] && _p[2]) _initDates = [_p[1], _p[2]];
    }

    var _altFmt = (typeof _transFormDateFormat !== 'undefined' && _transFormDateFormat)
                  ? _transFormDateFormat : 'd-m-Y';

    _crFp = flatpickr('#r2kCrPicker', {
        mode          : 'range',
        inline        : true,
        showMonths    : 2,
        dateFormat    : 'Y-m-d',
        altInput      : true,
        altInputClass : 'form-control',
        altFormat     : _altFmt,
        defaultDate   : _initDates.length === 2 ? _initDates : null,
        onReady           : function (_, __, fp) { _r2kInjectYrSels(fp); },
        onMonthChange     : function (_, __, fp) { _r2kInjectYrSels(fp); },
        onYearChange      : function (_, __, fp) { _r2kInjectYrSels(fp); },
        onChange          : function (selectedDates) {
            if (selectedDates.length === 2) {
                var from = formatDate(selectedDates[0]);
                var to   = formatDate(selectedDates[1]);
                var fd   = formatDateDisplay(from);
                var td   = formatDateDisplay(to);
                $('#r2kCrDisplay').text(from === to ? fd : fd + ' – ' + td);
                $('#r2kCrApply').prop('disabled', false);
            } else {
                $('#r2kCrDisplay').text('');
                $('#r2kCrApply').prop('disabled', true);
            }
        }
    });

    // Populate display + enable Apply immediately when restoring saved dates
    if (_initDates.length === 2) {
        var _fd = formatDateDisplay(_initDates[0]);
        var _td = formatDateDisplay(_initDates[1]);
        $('#r2kCrDisplay').text(_initDates[0] === _initDates[1] ? _fd : _fd + ' – ' + _td);
        $('#r2kCrApply').prop('disabled', false);
    } else {
        $('#r2kCrDisplay').text('');
        $('#r2kCrApply').prop('disabled', true);
    }
});

// ── Custom Range Modal — cleanup on close ────────────────────────────────────
$(document).on('hidden.bs.modal', '#r2kCustomRangeModal', function () {
    _crApplied = false;
    if (_crFp) { _crFp.destroy(); _crFp = null; }
    $('#r2kCrDisplay').text('');
    $('#r2kCrApply').prop('disabled', true);
});

// ── Custom Range — Apply ──────────────────────────────────────────────────────
$(document).on('click', '#r2kCrApply', function () {
    if (!_crFp || _crFp.selectedDates.length < 2) return;

    var from  = formatDate(_crFp.selectedDates[0]);
    var to    = formatDate(_crFp.selectedDates[1]);
    var f     = formatDateDisplay(from);
    var t     = formatDateDisplay(to);
    var label = from === to ? f : f + ' – ' + t;

    var $modal    = $('#r2kCustomRangeModal');
    var srcBtnId  = $modal.data('src-btn-id')   || 'dateFilterBtn';
    var labelId   = $modal.data('src-label-id') || 'dateFilterLabel';
    var datesId   = $modal.data('src-dates-id') || 'dateFilterDates';
    var onApply   = $modal.data('src-on-apply');

    // Update the trigger button
    var $srcBtn = $('#' + srcBtnId);
    $('#' + labelId).text('Custom');
    $('#' + datesId).text(label).show();
    $srcBtn.addClass('r2k-date-active');
    $srcBtn.closest('.dropdown').find('.date-option').removeClass('active');
    $srcBtn.closest('.dropdown').find('.date-option[data-range="custom"]').addClass('active');

    _crApplied = true;

    var inst = bootstrap.Modal.getInstance(document.getElementById('r2kCustomRangeModal'));
    if (inst) inst.hide();

    var _dd = bootstrap.Dropdown.getInstance($srcBtn[0]);
    if (_dd) _dd.hide();

    if (typeof onApply === 'function') {
        // Custom button (initDateFilter) — delegate to the caller's callback
        onApply(from, to);
    } else {
        // Standard #dateFilterBtn — persist + broadcast
        var _pk = location.pathname.split('/').filter(Boolean)[0] || '';
        if (_pk) _r2kSavePref('df_' + _pk, 'custom|' + from + '|' + to);
        $(document).trigger('r2k:datechange', [{ range: 'custom', from: from, to: to }]);
    }
});

// ── Custom Range — Cancel / Close ────────────────────────────────────────────
$(document).on('click', '#r2kCrCancel, #r2kCrClose', function () {
    var inst = bootstrap.Modal.getInstance(document.getElementById('r2kCustomRangeModal'));
    if (inst) inst.hide();
});

// ── initDateFilter ────────────────────────────────────────────────────────────
/**
 * Optional — only needed for non-standard date filter buttons (e.g. payments page).
 * Standard #dateFilterBtn pages do not need to call this.
 *
 * @param {object} opts
 *   btnId    {string}    id of the dropdown toggle button
 *   labelId  {string}    id of the label span inside the button
 *   datesId  {string}    id of the dates <strong> inside the button
 *   onApply  {function}  callback(from, to) called on any range selection
 */
function initDateFilter(opts) {
    opts = opts || {};
    var btnId    = opts.btnId   || 'dateFilterBtn';
    var labelId  = opts.labelId || 'dateFilterLabel';
    var datesId  = opts.datesId || labelId.replace('Label', 'Dates');
    var isCustom = (btnId !== 'dateFilterBtn');

    if (isCustom) {
        var $btn  = $('#' + btnId);
        var $wrap = $btn.closest('.dropdown');
        var $menu = $wrap.find('ul.dropdown-menu').first();
        if ($menu.length) $menu.html(buildDateFilterHtml());

        $wrap.on('click', '.date-option[data-range]', function (e) {
            var range = $(this).data('range');

            if (range === 'custom') {
                e.stopPropagation();
                var $modal = $('#r2kCustomRangeModal');
                if (!$modal.length) return;
                // Close this button's dropdown before opening modal
                var _ddC = bootstrap.Dropdown.getInstance($btn[0]);
                if (_ddC) _ddC.hide();
                $modal
                    .data('src-btn-id',   btnId)
                    .data('src-label-id', labelId)
                    .data('src-dates-id', datesId)
                    .data('src-on-apply', typeof opts.onApply === 'function' ? opts.onApply : null);
                new bootstrap.Modal($modal[0]).show();
                return;
            }

            $wrap.find('.date-option').removeClass('active');
            $(this).addClass('active');
            var _lbl = $.trim($(this).clone().children('.df-date-preview').remove().end().text());
            if (_lbl) $('#' + labelId).text(_lbl);

            var dr = getDateRange(range);
            if (dr.from) {
                $btn.addClass('r2k-date-active');
                var _f = formatDateDisplay(dr.from);
                var _t = formatDateDisplay(dr.to);
                $('#' + datesId).text(_f === _t ? _f : _f + ' – ' + _t).show();
            } else {
                $btn.removeClass('r2k-date-active');
                $('#' + datesId).text('').hide();
            }

            var _dd = bootstrap.Dropdown.getInstance($btn[0]);
            if (_dd) _dd.hide();

            if (typeof opts.onApply === 'function') opts.onApply(dr.from, dr.to);
        });

    } else {
        // Bridge legacy onApply via event
        if (typeof opts.onApply === 'function') {
            $(document).on('r2k:datechange', function (e, dr) {
                opts.onApply(dr.from, dr.to);
            });
        }
    }
}

// ── buildDateFilterHtml ───────────────────────────────────────────────────────
/**
 * Returns the full dropdown inner HTML with bold section group labels.
 * @returns {string}
 */
function buildDateFilterHtml() {
    var hdr = function (label) {
        return '<li><div style="font-size:.67rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;' +
               'color:#696cff;background:rgba(105,108,255,.07);padding:.45rem 1rem .35rem;">' + label + '</div></li>';
    };
    return [
        hdr('Daily'),
        '<li><a class="dropdown-item date-option" data-range="today">Today</a></li>',
        '<li><a class="dropdown-item date-option" data-range="yesterday">Yesterday</a></li>',
        '<li><hr class="dropdown-divider my-1"></li>',

        hdr('Weekly'),
        '<li><a class="dropdown-item date-option" data-range="this_week">This Week</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_week">Last Week</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>',
        '<li><hr class="dropdown-divider my-1"></li>',

        hdr('Monthly'),
        '<li><a class="dropdown-item date-option active" data-range="this_month">This Month</a></li>',
        '<li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_30_days">Last 30 Days</a></li>',
        '<li><hr class="dropdown-divider my-1"></li>',

        hdr('Quarterly'),
        '<li><a class="dropdown-item date-option" data-range="this_quarter">This Quarter</a></li>',
        '<li><a class="dropdown-item date-option" data-range="previous_quarter">Previous Quarter</a></li>',
        '<li><hr class="dropdown-divider my-1"></li>',

        hdr('Yearly'),
        '<li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>',
        '<li><a class="dropdown-item date-option" data-range="last_365_days">Last 365 Days</a></li>',
        '<li><hr class="dropdown-divider my-1"></li>',

        hdr('Financial Year'),
        '<li><a class="dropdown-item date-option fw-semibold" data-range="current_fy">' +
            '<i class="bx bxs-star text-warning me-1"></i>Current FY</a></li>',
        '<li><a class="dropdown-item date-option" data-range="previous_fy">Previous FY</a></li>',
        '<li><hr class="dropdown-divider my-1"></li>',

        hdr('Custom'),
        '<li><a class="dropdown-item date-option" data-range="custom">' +
            '<i class="bx bx-calendar-edit me-2"></i>Custom Range</a></li>',
    ].join('');
}
