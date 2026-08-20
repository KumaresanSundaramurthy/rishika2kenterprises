// ── Transaction pages — shared utilities ──────────────────────────────────────
// Requires: jQuery, Bootstrap, datefilter.js (getDateRange, formatDate),
//           and page globals: PageNo, RowLimit, Filter, ModuleId,
//           ModuleTable, ModulePag, CsrfName, CsrfToken

/** @returns {void} */
function initTooltips() {
    // MutationObserver in default.js auto-inits newly added tooltip elements,
    // so this is a no-op safety call for pages that invoke it explicitly after AJAX.
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        if (!bootstrap.Tooltip.getInstance(el)) {
            new bootstrap.Tooltip(el, { container: 'body', trigger: 'hover' });
        }
    });
}

/**
 * @param {Function} fn
 * @param {number}   delay
 * @returns {Function}
 */
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
 * @param {Object}        config                  - Loader configuration
 * @param {string}        config.url              - Base URL; pageNo is appended
 * @param {string}        config.tabCountClass    - Count badge selector, e.g. '.quot-tab-count'
 * @param {string}        config.statusTabClass   - Status tab selector, e.g. '.quot-status-tab'
 * @param {string}        config.errorMessage     - User-facing message on AJAX failure
 * @param {Function}      [config.onSuccess]      - Called with the raw response on success
 * @param {number}        [pageNo]
 * @param {number}        [rowLimit]
 * @param {Object}        [filter]
 * @returns {void}
 */
var _listReqSeq = 0; // increments with every request; callbacks discard stale responses

function loadTransactionList(config, pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_listReqSeq;

    $(config.tabCountClass).text('').addClass('d-none');
    $(ModulePag).empty();
    ajaxLoading(0);
    $(ModuleTable + ' tbody').html(
        '<tr><td colspan="99" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
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
            if (reqSeq !== _listReqSeq) return;
            ajaxLoading(1);
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
            if (reqSeq !== _listReqSeq) return;
            ajaxLoading(1);
            $(ModulePag).html('<div class="alert alert-danger m-2">' + config.errorMessage + '</div>');
        }
    });
}

/**
 * @deprecated Use _pushTabUrl (now in default.js). Kept as alias for transaction pages.
 * @param {string} status
 * @param {string} search
 * @returns {void}
 */
function _updateTransTabUrl(status, search) { _pushTabUrl(status, search); }

/**
 * Shows/hides #transFilterNotice whenever a filter, search, or date selection
 * changes. No-op on pages that have no #transFilterNotice element.
 * @returns {void}
 */
function _updateFilterNotice() {
    var $notice = $('#transFilterNotice');
    if (!$notice.length) return;
    var hasSearch     = $.trim($('#searchTransactionData').val()).length > 0;
    var hasFilterBtn  = $('.apex-filter-btn.has-filter').length > 0;
    var hasDateFilter = $('#dateFilterBtn').hasClass('r2k-date-active');
    $notice[hasSearch || hasFilterBtn || hasDateFilter ? 'addClass' : 'removeClass']('show');
}

$(document).on('click', '.apex-filter-btn, .cn-status-panel-opt', function () {
    setTimeout(_updateFilterNotice, 60);
});
$(document).on('input', '#searchTransactionData', _updateFilterNotice);

$(function () {
    var dfBtn = document.getElementById('dateFilterBtn');
    if (dfBtn && typeof MutationObserver !== 'undefined') {
        new MutationObserver(_updateFilterNotice).observe(dfBtn, { attributes: true, attributeFilter: ['class'] });
    }
    _updateFilterNotice();
});

$(document).on('click', '.notice-dismiss', function () {
    $(this).closest('.apex-filter-active-notice').removeClass('show');
});

// Note: Cancel and Delete confirmations are handled by each page's own
// -status-update and delete* click handlers (defined in each view's <script>).

/**
 * Shows only the filter buttons relevant to the active tab; hides all others.
 * Each page defines its own filterMap and allFilterEls, then delegates here.
 * @param {string}   status        - Active tab status key (e.g. 'All', 'Paid')
 * @param {Object}   filterMap     - Map of status → string[] of element IDs to show (no #)
 * @param {string[]} allFilterEls  - Every controllable filter element ID (no #)
 * @param {Function} [afterCb]     - Optional fn called after 50ms (e.g. blur-edge refresh)
 * @returns {void}
 */
function _applyTabFilters(status, filterMap, allFilterEls, afterCb) {
    var show = filterMap[status] || filterMap['All'];
    allFilterEls.forEach(function (id) {
        var $el = $('#' + id);
        if ($el.length) { $el.toggleClass('d-none', show.indexOf(id) === -1); }
    });
    if (afterCb) { setTimeout(afterCb, 50); }
}

// ── Select-all shared helpers ─────────────────────────────────────────────────

/**
 * Updates the select-all banner text and visibility for a transaction list page.
 * @param {boolean} selectAllMode
 * @param {number}  totalRecords
 * @param {number}  pageCount
 * @param {string}  prefix   DOM ID prefix, e.g. 'inv', 'purch', 'so'
 * @param {string}  label    Plural noun for the module, e.g. 'invoices', 'purchases'
 * @returns {void}
 */
function updateTransSelectAllBanner(selectAllMode, totalRecords, pageCount, prefix, label) {
    var $banner = $('#' + prefix + 'SelectAllBanner');
    var $msg    = $('#' + prefix + 'SelectAllMsg');
    var $link   = $('#' + prefix + 'SelectAllLink');
    var $clear  = $('#' + prefix + 'SelectAllClear');

    if (!pageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }
    if (selectAllMode) {
        $msg.text('All ' + totalRecords + ' ' + label + ' are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + pageCount + ' ' + label + ' on this page are selected.');
        $clear.addClass('d-none');
        if (totalRecords > pageCount) {
            $link.text('Select all ' + totalRecords + ' ' + label + '?').removeClass('d-none');
        } else {
            $link.addClass('d-none');
            $banner.addClass('d-none');
            return;
        }
    }
    $banner.removeClass('d-none');
}

/**
 * Hides the select-all banner and resets its link/clear buttons.
 * Does NOT reset the caller's mode variable — do that before calling.
 * @param {string} prefix  DOM ID prefix, e.g. 'inv', 'purch', 'so'
 * @returns {void}
 */
function clearTransSelectAllDom(prefix) {
    $('#' + prefix + 'SelectAllBanner').addClass('d-none');
    $('#' + prefix + 'SelectAllLink').removeClass('d-none');
    $('#' + prefix + 'SelectAllClear').addClass('d-none');
}

/**
 * Generic bulk-delete handler for transaction list pages.
 * @param {number}   moduleUID     Module UID for the delete endpoint (e.g. 103)
 * @param {boolean}  selectAllMode Whether "select all records" mode is active
 * @param {Function} onClear       Called on success to reset select-all state
 * @param {Function} onRefresh     Called after onClear to reload the list
 * @returns {void}
 */
function deleteMultipleTrans(moduleUID, selectAllMode, onClear, onRefresh) {
    var postData = selectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/' + moduleUID,
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                if (typeof onClear   === 'function') onClear();
                hideUIBlock();
                ajaxLoading(0);
                if (typeof onRefresh === 'function') onRefresh();
            }
        }
    });
}

// ── Payment panel shared helpers ──────────────────────────────────────────────

/**
 * Wires up the floating payment-details panel used on invoice/purchase/return list pages.
 * Call once from the module JS file at global scope.
 * @param {string} spinnerCls  Bootstrap text-colour class for the loading spinner (e.g. 'text-primary', '')
 * @param {string} accentHex   CSS hex colour for amount text and the view-payments link (e.g. '#696cff')
 * @returns {void}
 */
function initTransPaymentPanel(spinnerCls, accentHex) {
    var $panel = $('#payDetailPanel');
    if (!$panel.length) return;
    var $body   = $('#payDetailBody');
    var $title  = $('#payPanelTitle');
    var openUID = null;

    function openPanel($trigger) {
        var transUID = $trigger.data('trans-uid');
        var transNum = $trigger.data('trans-num') || '';
        var rect     = $trigger[0].getBoundingClientRect();
        var panelW   = 290;
        var left     = rect.left;
        var top      = rect.bottom + 6;
        if (left + panelW + 16 > window.innerWidth) left = window.innerWidth - panelW - 16;
        $title.text(transNum ? 'Payments — ' + transNum : 'Payments');
        $body.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm' + (spinnerCls ? ' ' + spinnerCls : '') + '"></span></div>');
        $panel.css({ top: top, left: left }).show();
        openUID = transUID;
        ajaxLoading(0);
        $.ajax({
            url    : '/payments/getPaymentsByTransaction',
            type   : 'GET',
            data   : { TransUID: transUID },
            success: function (resp) {
                ajaxLoading(1);
                if (resp && !resp.Error && resp.Payments && resp.Payments.length) {
                    $body.html(_buildPaymentPanelHtml(resp.Payments, accentHex));
                } else {
                    $body.html('<p class="text-muted mb-0" style="font-size:.8rem;">' + t('toast_no_payments', 'No payments found.') + '</p>');
                }
            },
            error: function () {
                ajaxLoading(1);
                $body.html('<p class="text-danger mb-0" style="font-size:.8rem;">' + t('toast_payments_failed', 'Failed to load payments.') + '</p>');
            }
        });
    }

    function closePanel() { $panel.hide(); openUID = null; }

    $(document).on('click', '.pay-mode-clickable', function (e) {
        if ($(e.target).closest('.transPayAttachBtn').length) return;
        e.stopPropagation();
        var transUID = $(this).data('trans-uid');
        if (openUID === transUID) { closePanel(); return; }
        openPanel($(this));
    });
    $(document).on('click', '#payPanelClose', function (e) { e.stopPropagation(); closePanel(); });
    $(document).on('click', function (e) {
        if ($panel.is(':visible') && !$(e.target).closest('#payDetailPanel, .pay-mode-clickable').length) closePanel();
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') closePanel(); });
}

/**
 * Builds the HTML for one or more payment entries inside the payment panel.
 * @param {Array}  payments   Array of payment objects from the API
 * @param {string} accentHex  CSS hex colour for amount text and the view-payments link
 * @returns {string}
 */
function _buildPaymentPanelHtml(payments, accentHex) {
    var html = '';
    payments.forEach(function (p, i) {
        if (i > 0) html += '<hr style="margin:8px 0;border-color:#f0f0f0;">';
        var amt  = parseFloat(p.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var sourceType = p.SourceType || '';
        var mode = p.PaymentTypeName || (sourceType === 'CreditNote' ? t('lbl_credit_note', 'Credit Note') : (sourceType === 'OnAccount' ? t('lbl_on_account', 'On Account') : '—'));
        var ref  = p.ReferenceNo || '';
        var date = '';
        if (p.CreatedOn) {
            var d = new Date(p.CreatedOn.replace(' ', 'T'));
            date  = ('0' + d.getDate()).slice(-2) + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + d.getFullYear();
        }
        html += '<div class="d-flex justify-content-between align-items-start gap-2">';
        html += '  <div style="min-width:0;">';
        html += '    <div style="font-size:.83rem;font-weight:600;color:' + accentHex + ';">&#8377;' + amt + '</div>';
        html += '    <div style="font-size:.75rem;color:#566a7f;">' + mode + '</div>';
        if (date || ref) {
            html += '  <div style="font-size:.72rem;color:#aaa;margin-top:1px;">';
            if (date) html += date;
            if (date && ref) html += '&nbsp;&nbsp;';
            if (ref)  html += ref;
            html += '  </div>';
        }
        html += '  </div>';
        html += '  <a href="/payments" class="btn btn-icon btn-sm" style="color:' + accentHex + ';flex-shrink:0;" title="' + t('vm_view_payments', 'View Payments') + '"><i class="bx bx-show fs-6"></i></a>';
        html += '</div>';
    });
    return html;
}
