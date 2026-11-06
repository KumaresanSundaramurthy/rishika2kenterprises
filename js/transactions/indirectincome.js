// ── Indirect Income list — module-specific JS ──────────────────────────────────
// Shared utilities (debounce, initTooltips, ajaxLoading) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _incSelectAllMode = false;
var _incTotalRecords  = 0;
var _incPageCount     = 0;

// ── Status display maps ──────────────────────────────────────────────────────
var _incBadgeClass = {
    Pending:   'trans-badge-Pending',
    Partial:   'trans-badge-Partial',
    Received:  'trans-badge-Paid',
    Cancelled: 'trans-badge-Cancelled',
};
var _incStatusIcon = {
    Pending:   'bx-time',
    Partial:   'bx-adjust',
    Received:  'bx-check-circle',
    Cancelled: 'bx-x-circle',
};

// ── HTML helpers ─────────────────────────────────────────────────────────────

/**
 * @param {*} str
 * @returns {string}
 */
function _incEsc(str) {
    return $('<div>').text(str == null ? '' : String(str)).html();
}

/**
 * @param {*} str
 * @returns {string}
 */
function _incAttr(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/**
 * Format a YYYY-MM-DD date string using the user's ListDateFormat.
 * @param {string|null} str
 * @returns {string}
 */
function _incFmtDate(str) {
    if (!str) return '—';
    var parts = String(str).split('-');
    if (parts.length < 3) return str;
    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var d   = parseInt(parts[2], 10);
    var m   = parseInt(parts[1], 10) - 1;
    var y   = parts[0];
    var fmt = (typeof _transListDateFormat !== 'undefined' && _transListDateFormat) ? _transListDateFormat : 'd M Y';
    var tokens = { 'd': String(d).padStart(2,'0'), 'j': String(d), 'm': String(m+1).padStart(2,'0'), 'n': String(m+1), 'Y': y, 'y': y.slice(-2), 'F': monthsFull[m], 'M': months[m] };
    return fmt.replace(/[djmnYyFM]/g, function (tok) { return tokens[tok] !== undefined ? tokens[tok] : tok; });
}

/**
 * Parse a "YYYY-MM-DD HH:MM:SS" datetime string and return display info.
 * @param {string|null} str
 * @returns {{ formatted: string, w24: boolean, ago: string|null }}
 */
function _incFmtDt(str) {
    if (!str) return { formatted: '—', w24: false, ago: null };
    var parsed = new Date(str.replace(' ', 'T') + 'Z');
    if (isNaN(parsed.getTime())) return { formatted: str, w24: false, ago: null };

    var dy = parsed.getUTCDate(), mo = parsed.getUTCMonth(), yr = parsed.getUTCFullYear();
    var hr = parsed.getUTCHours(), min = parsed.getUTCMinutes(), sec = parsed.getUTCSeconds();
    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var ampm = hr >= 12 ? 'PM' : 'AM';
    var h12  = hr % 12 || 12;
    var fmt  = (typeof _transListDateTimeFormat !== 'undefined' && _transListDateTimeFormat) ? _transListDateTimeFormat : 'd M Y H:i';
    var t = { 'd': String(dy).padStart(2,'0'), 'j': String(dy), 'm': String(mo+1).padStart(2,'0'), 'n': String(mo+1), 'Y': String(yr), 'y': String(yr).slice(-2), 'F': monthsFull[mo], 'M': months[mo], 'H': String(hr).padStart(2,'0'), 'G': String(hr), 'h': String(h12).padStart(2,'0'), 'g': String(h12), 'i': String(min).padStart(2,'0'), 's': String(sec).padStart(2,'0'), 'A': ampm, 'a': ampm.toLowerCase() };
    var formatted = fmt.replace(/[djmnYyFMHGhgisAa]/g, function (tok) { return t[tok] !== undefined ? t[tok] : tok; });

    var secsAgo = Math.floor((Date.now() - parsed.getTime()) / 1000);
    var w24 = secsAgo >= 0 && secsAgo < 86400;
    var ago = null;
    if (w24) {
        if (secsAgo < 60) { ago = 'just now'; }
        else if (secsAgo < 3600) { var mm = Math.floor(secsAgo / 60); ago = mm + ' min' + (mm > 1 ? 's' : '') + ' ago'; }
        else { var hh = Math.floor(secsAgo / 3600); ago = hh + ' hr' + (hh > 1 ? 's' : '') + ' ago'; }
    }
    return { formatted: formatted, w24: w24, ago: ago };
}

// ── Pagination builder ───────────────────────────────────────────────────────

/**
 * @param {number} total
 * @param {number} page
 * @param {number} limit
 * @returns {string}
 */
// ── Row HTML generator ───────────────────────────────────────────────────────

/**
 * Build table row HTML for the income list from services JSON data.
 * @param {Array}  data         Array of income record objects
 * @param {number} serialStart  Zero-based serial offset
 * @returns {string}
 */
function _generateIncomeHtml(data, serialStart) {
    if (!data || data.length === 0) {
        return '<tr><td colspan="9">' +
               '<div class="d-flex flex-column align-items-center py-5">' +
               '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
               '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No income records found</span>' +
               '<button type="button" class="btn btn-primary btn-sm px-4" id="addIncomeBtn"><i class="bx bx-plus me-1"></i>Add Income</button>' +
               '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined' && JwtData.GenSettings && parseInt(JwtData.GenSettings.SerialNoDisplay, 10) === 1);
    var sym  = (typeof incCurSymbol !== 'undefined') ? incCurSymbol : '₹';
    var dec  = (typeof incDecPoints !== 'undefined') ? incDecPoints : 2;
    var html = '';

    $.each(data, function (i, r) {
        var sno        = serialStart + i + 1;
        var uid        = parseInt(r.IncomeUID, 10);
        var status     = r.DocStatus || 'Pending';
        var badge      = _incBadgeClass[status] || 'trans-badge-Pending';
        var icon       = _incStatusIcon[status]  || 'bx-circle';
        var netAmt     = parseFloat(r.NetAmount  || 0);
        var paidAmt    = parseFloat(r.PaidAmount || 0);
        var pendingAmt = Math.max(0, Math.round((netAmt - paidAmt) * 100) / 100);
        var showPending = (status === 'Pending' || status === 'Partial') && netAmt > 0;
        var incNum     = r.IncomeNumber || '';
        var incDate    = _incFmtDate(r.IncomeDate || '');
        var dtObj      = _incFmtDt(r.UpdatedOn || '');
        var amtFmt     = sym + ' ' + parseFloat(netAmt).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        var rawAmtFmt  = sym + ' ' + parseFloat(r.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });

        var row = '<tr>';
        row += '<td style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox incCheck" type="checkbox" value="' + uid + '"></div></td>';
        row += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>';

        // ── Income number + date ─────────────────────────────────────────────
        row += '<td>';
        if (!incNum) {
            row += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>—</span>';
        } else {
            row += '<a href="javascript:void(0);" class="trans-doc-number fw-semibold incViewDetail"'
                 + ' data-uid="' + uid + '"'
                 + ' data-num="' + _incAttr(incNum) + '"'
                 + ' data-date="' + _incAttr(incDate) + '"'
                 + ' data-amount="' + _incAttr(rawAmtFmt) + '"'
                 + ' data-status="' + _incAttr(status) + '"'
                 + ' data-badge="' + _incAttr(badge) + '"'
                 + ' data-icon="' + _incAttr(icon) + '">'
                 + _incEsc(incNum) + '</a>';
        }
        row += '<div class="d-flex align-items-center gap-2 mt-1">';
        if (r.IncomeDate) row += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _incEsc(incDate) + '</div>';
        if (parseInt(r.AttachCount, 10) > 0) {
            row += '<button type="button" class="btn btn-link p-0 transAttachBtn"'
                 + ' data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"'
                 + ' data-module-uid="115" data-color="#059669"'
                 + ' title="' + parseInt(r.AttachCount, 10) + ' attachment(s)"'
                 + ' style="font-size:var(--r2k-text-82);line-height:1;color:#059669;"><i class="bx bx-paperclip"></i></button>';
        }
        row += '</div>';
        var createdBy = (r.CreatedByName || '').trim();
        if (createdBy) row += '<div style="font-size:var(--r2k-text-xs);color:#bbb;">by ' + _incEsc(createdBy) + '</div>';
        row += '</td>';

        // ── Amount ───────────────────────────────────────────────────────────
        row += '<td>';
        row += '<div class="trans-amount-main">' + _incEsc(amtFmt) + '</div>';
        if (status === 'Partial' && pendingAmt > 0) {
            row += '<div style="font-size:var(--r2k-text-70);color:#dc3545;margin-top:2px;">Bal ' + sym + ' ' + pendingAmt.toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + '</div>';
        }
        row += '</td>';

        // ── Category / Notes ─────────────────────────────────────────────────
        row += '<td>';
        if (r.CategoryName) {
            row += '<span class="badge text-bg-light border" style="font-size:var(--r2k-text-sm);font-weight:500;">' + _incEsc(r.CategoryName) + '</span>';
        } else {
            row += '<span class="text-muted">—</span>';
        }
        if (r.Notes) row += '<div class="text-muted" style="font-size:var(--r2k-text-sm);margin-top:3px;">' + _incEsc(r.Notes) + '</div>';
        row += '</td>';

        // ── Status badge ─────────────────────────────────────────────────────
        row += '<td><span class="trans-badge ' + badge + '"><i class="bx ' + icon + '" style="font-size:var(--r2k-text-md);"></i> ' + _incEsc(status) + '</span></td>';

        // ── Payment mode ─────────────────────────────────────────────────────
        var payCount     = parseInt(r.PaymentCount || 0, 10);
        var payModes     = payCount > 0 ? String(r.PaymentModes || '').split(',') : [];
        var firstMode    = payModes.length ? (payModes[0] || '').trim() : '';
        var extraCnt     = Math.max(0, payCount - 1);
        var bankName     = (r.PayBankName      || '').trim();
        var accNum       = (r.PayAccountNumber || '').trim();
        var isCashOnly   = payCount > 0 && !bankName;
        var hasPayAttach = parseInt(r.PaymentAttachmentCount || 0, 10) > 0;

        row += '<td>';
        if (payCount > 0 && firstMode) {
            row += '<div class="pay-mode-cell' + (payCount > 1 ? ' pay-mode-clickable' : '') + '"'
                 + (payCount > 1 ? ' data-trans-uid="' + uid + '" data-trans-num="' + _incAttr(incNum) + '" style="cursor:pointer;"' : '') + '>';
            row += '<div class="d-flex align-items-center gap-1 flex-wrap">';
            row += '<span class="badge bg-label-success" style="font-size:var(--r2k-text-xs);"><i class="bx bx-credit-card me-1"></i>' + _incEsc(firstMode) + '</span>';
            if (extraCnt > 0) row += '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-xs);">+' + extraCnt + '</span>';
            if (hasPayAttach) {
                row += '<button type="button" class="btn btn-link p-0 transPayAttachBtn"'
                     + ' data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"'
                     + ' data-url="' + global_services_url + 'api/indirectincome/getPaymentAttachments"'
                     + ' title="' + parseInt(r.PaymentAttachmentCount, 10) + ' payment attachment(s)"'
                     + ' style="font-size:var(--r2k-text-82);line-height:1;color:#059669;"><i class="bx bx-paperclip"></i></button>';
            }
            row += '</div>';
            if (!isCashOnly && (bankName || accNum)) {
                row += '<div style="font-size:var(--r2k-text-xs);color:#6c757d;margin-top:3px;line-height:1.4;">';
                if (bankName) row += '<div><i class="bx bx-building-house me-1" style="font-size:var(--r2k-text-70);"></i>' + _incEsc(bankName) + '</div>';
                if (accNum)   row += '<div style="font-family:monospace;letter-spacing:.03em;">' + _incEsc(accNum) + '</div>';
                row += '</div>';
            }
            row += '</div>';
        } else {
            row += '<span class="text-muted" style="font-size:var(--r2k-text-78);">—</span>';
        }
        row += '</td>';

        // ── Last Updated ─────────────────────────────────────────────────────
        row += '<td>';
        row += '<div class="r2k-col-date">' + dtObj.formatted + '</div>';
        if (dtObj.w24 && dtObj.ago) row += '<div class="r2k-col-date-ago">' + dtObj.ago + '</div>';
        row += '<div class="text-muted r2k-col-date-by">by ' + _incEsc(r.UpdatedBy || '—') + '</div>';
        row += '</td>';

        // ── Actions ──────────────────────────────────────────────────────────
        row += '<td style="width:50px"><div class="d-flex align-items-center justify-content-end gap-1">';
        if (showPending) {
            row += '<button class="btn inc-pay-quick-btn incMarkReceived"'
                 + ' data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"'
                 + ' data-date="' + _incAttr(incDate) + '"'
                 + ' data-total="' + netAmt + '" data-paid="' + paidAmt + '" data-pending="' + pendingAmt + '"'
                 + ' title="Record Payment">' + _incEsc(sym) + '</button>';
        }
        if (status !== 'Cancelled') {
            row += '<button class="btn btn-icon btn-sm text-warning incEdit" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit"></i></button>';
        }
        row += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>';
        row += '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';
        if (showPending) {
            row += '<li><button class="dropdown-item incMarkReceived" data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '" data-date="' + _incAttr(incDate) + '" data-total="' + netAmt + '" data-paid="' + paidAmt + '" data-pending="' + pendingAmt + '"><i class="bx bx-wallet me-2 text-success"></i>Record Payment</button></li>';
            row += '<li><button class="dropdown-item incCancel" data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"><i class="bx bx-x-circle me-2 text-warning"></i>Cancel Income</button></li>';
            row += '<li><hr class="dropdown-divider my-1"></li>';
            row += '<li><button class="dropdown-item text-danger incDelete" data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        } else if (status === 'Received') {
            row += '<li><button class="dropdown-item incCancel" data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"><i class="bx bx-x-circle me-2 text-warning"></i>Cancel Income</button></li>';
            row += '<li><hr class="dropdown-divider my-1"></li>';
            row += '<li><button class="dropdown-item text-danger incDelete" data-uid="' + uid + '" data-num="' + _incAttr(incNum) + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        } else if (status === 'Cancelled') {
            row += '<li><button class="dropdown-item text-muted" disabled><i class="bx bx-lock-alt me-2"></i>Cancelled — No Actions</button></li>';
        }
        row += '</ul></div>';
        row += '</div></td>';
        row += '</tr>';
        html += row;
    });
    return html;
}

// ── Select-all banner ────────────────────────────────────────────────────────

/**
 * @returns {void}
 */
function _incUpdateSelectAllBanner() {
    var $banner = $('#incSelectAllBanner');
    var $msg    = $('#incSelectAllMsg');
    var $link   = $('#incSelectAllLink');
    var $clear  = $('#incSelectAllClear');

    if (!_incPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }
    if (_incSelectAllMode) {
        $msg.text('All ' + _incTotalRecords + ' income records are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _incPageCount + ' income records on this page are selected.');
        $clear.addClass('d-none');
        if (_incTotalRecords > _incPageCount) {
            $link.text('Select all ' + _incTotalRecords + ' income records?').removeClass('d-none');
        } else {
            $link.addClass('d-none');
            $banner.addClass('d-none');
            return;
        }
    }
    $banner.removeClass('d-none');
}

/**
 * @returns {void}
 */
function _incClearSelectAll() {
    _incSelectAllMode = false;
    $('#incSelectAllBanner').addClass('d-none');
    $('#incSelectAllLink').removeClass('d-none');
    $('#incSelectAllClear').addClass('d-none');
}

// ── Main list loader ─────────────────────────────────────────────────────────

/**
 * @param {number} [pageNo]
 * @param {number} [rowLimit]
 * @param {object} [filter]
 * @returns {void}
 */
function getIncomeDetails(pageNo, rowLimit, filter) {
    pageNo   = pageNo   || PageNo || 1;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var serialStart = (pageNo - 1) * rowLimit;

    $(ModuleTable + ' tbody').html(
        '<tr><td colspan="9">' +
        '<div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2">' +
        '<div class="spinner-border text-success" style="width:2rem;height:2rem;" role="status"><span class="visually-hidden">Loading…</span></div>' +
        '<span class="text-muted" style="font-size:var(--r2k-text-sm);">Loading income records…</span>' +
        '</div></td></tr>'
    );
    $('#incPagination').addClass('d-none');
    ajaxLoading(0);

    $.ajax({
        url    : global_services_url + 'api/indirectincome/getPageDetails',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            RowLimit : rowLimit,
            PageNo   : pageNo,
            Filter   : JSON.stringify(filter),
            orgId    : parseInt(JwtData.Org.OrgUID, 10),
            userId   : parseInt(JwtData.User.UserUID, 10),
            branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
        },
        success: function (resp) {
            ajaxLoading(1);
            if (resp.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load.') + '</strong></div>');
                return;
            }
            var pagHtml = generatePagination(parseInt(resp.TotalCount) || 0, pageNo, rowLimit);
            if (pagHtml) {
                $(ModulePag).html(pagHtml).removeClass('d-none');
            } else {
                $(ModulePag).empty().addClass('d-none');
            }
            $(ModuleTable + ' tbody').html(_generateIncomeHtml(resp.Data || [], serialStart));

            _incTotalRecords = parseInt(resp.TotalCount) || 0;
            _incPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;

            var cnt = _incTotalRecords;
            $('.inc-status-tab.active .trans-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);

            _incUpdateSelectAllBanner();
            if (resp.SummaryStats && typeof window._updateStatCards === 'function') window._updateStatCards(resp.SummaryStats);
            initTooltips();
            $(window).trigger('scroll');
        },
        error: function () {
            ajaxLoading(1);
            $(ModulePag).empty().addClass('d-none');
        },
    });
}

// ── Bulk delete ──────────────────────────────────────────────────────────────

/**
 * @returns {void}
 */
function deleteMultipleIncomes() {
    var _base = { orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 };
    var postData = _incSelectAllMode
        ? $.extend({ SelectAll: 1, Filter: JSON.stringify(Filter) }, _base)
        : $.extend({ 'IncomeUIDs[]': SelectedUIDs }, _base);

    $.ajax({
        url    : global_services_url + 'api/indirectincome/deleteMultipleIncomes',
        method : 'POST',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : postData,
        success: function (resp) {
            if (resp.Error) {
                showAlertMessageSwal('error', '', resp.Message);
            } else {
                showToastNotification(resp.Message, 'success');
                SelectedUIDs = [];
                _incClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getIncomeDetails(PageNo, RowLimit, Filter);
            }
        },
        error: function () {
            showToastNotification('Failed to delete income records.', 'error');
        },
    });
}
