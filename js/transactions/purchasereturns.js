// ── Purchase Returns list — module-specific JS ────────────────────────────────

var _prSelectAllMode = false;
var _prTotalRecords  = 0;
var _prPageCount     = 0;
var _prReqSeq        = 0;

/** @returns {void} */
function _prUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_prSelectAllMode, _prTotalRecords, _prPageCount, 'pr', 'purchasereturns');
}
/** @returns {void} */
function _prClearSelectAll() {
    _prSelectAllMode = false;
    clearTransSelectAllDom('pr');
}
/** @returns {void} */
function deleteMultiplePurchaseReturns() {
    deleteMultipleTrans(108, _prSelectAllMode, _prClearSelectAll, function () {
        getPurchaseReturnsDetails(PageNo, RowLimit, Filter);
    });
}

var _prStatusBadge = {
    Draft     : 'trans-badge-Draft',
    Approved  : 'trans-badge-Confirmed',
    Paid      : 'trans-badge-Paid',
    Cancelled : 'trans-badge-Cancelled',
};
var _prStatusIcon = {
    Draft     : 'bx-pencil',
    Approved  : 'bx-check-double',
    Paid      : 'bx-check-circle',
    Cancelled : 'bx-x-circle',
};
var _prTerminal = ['Paid', 'Cancelled'];
var _prTransitions = {
    Draft    : [{ db: 'Approved',  label: 'Approve Return' }],
    Approved : [{ db: 'Cancelled', label: 'Cancel Return'  }],
};

/**
 * @param {string} name
 * @returns {string}
 */
function _prPartyInitials(name) {
    if (!name) return '?';
    var parts = name.trim().split(/\s+/);
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();
}

/**
 * @param {string} s
 * @returns {string}
 */
function _prEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _prTimeAgo(dt) { return timeAgo(dt); }

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildPrHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="10"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No purchase returns found</span>' +
            '<a href="' + global_base_url + 'purchasereturns/create" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Record Purchase Return</a>' +
            '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined') && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var cur        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;
    var prModUID   = 108;
    var html       = '';

    $.each(rows, function (i, r) {
        var serial      = offset + i + 1;
        var status      = r.Status || 'Draft';
        var isDraft     = status === 'Draft';
        var isCancelled = status === 'Cancelled';
        var isTerminal  = _prTerminal.indexOf(status) !== -1;

        var netAmt     = parseFloat(r.NetAmount  || 0);
        var paidAmt    = parseFloat(r.PaidAmount || 0);
        var pendingAmt = Math.max(0, Math.round((netAmt - paidAmt) * 100) / 100);
        var showPending = !isDraft && pendingAmt > 0 && !isCancelled && status !== 'Rejected';

        /* Refund status badge */
        var refundBadge = '';
        if (!isDraft) {
            if (paidAmt <= 0) {
                refundBadge = '<span class="badge bg-label-warning" style="font-size:var(--r2k-text-xs);">Pending</span>';
            } else if (pendingAmt <= 0.01) {
                refundBadge = '<span class="badge bg-label-success" style="font-size:var(--r2k-text-xs);">Settled</span>';
            } else {
                refundBadge = '<span class="badge bg-label-info" style="font-size:var(--r2k-text-xs);">Partial</span>';
            }
        }

        /* Status badge */
        var badgeClass = _prStatusBadge[status]  || 'trans-badge-Draft';
        var badgeIcon  = _prStatusIcon[status]   || 'bx-help-circle';
        var transitions = (!isTerminal && _prTransitions[status]) ? _prTransitions[status] : [];
        var statusBadgeHtml;

        if (transitions.length) {
            statusBadgeHtml = '<div class="dropdown d-inline-block">' +
                '<button class="trans-status-badge ' + badgeClass + ' dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
                '<i class="bx ' + badgeIcon + ' me-1"></i>' + _prEsc(status) + '</button>' +
                '<ul class="dropdown-menu r2k-action-menu">';
            $.each(transitions, function (_, t) {
                statusBadgeHtml += '<li><button class="dropdown-item pr-status-update"' +
                    ' data-uid="'    + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="'    + _prEsc(r.UniqueNumber || 'Draft') + '"' +
                    ' data-refund="' + paidAmt + '"' +
                    ' data-status="' + _prEsc(t.db) + '">' +
                    _prEsc(t.label) + '</button></li>';
            });
            statusBadgeHtml += '</ul></div>';
        } else {
            statusBadgeHtml = '<span class="trans-status-badge ' + badgeClass + '">' +
                '<i class="bx ' + badgeIcon + ' me-1"></i>' + _prEsc(status) + '</span>';
        }

        var transDateFmt   = r.TransDate  ? formatDateDisplay(r.TransDate)  : '';
        var updatedDateFmt = r.UpdatedOn  ? formatDateDisplay(r.UpdatedOn.substring(0, 10)) : '';
        var updatedAgo     = _prTimeAgo(r.UpdatedOn);
        var updatedBy      = r.UpdatedBy  || '—';
        var createdBy      = r.CreatedBy  || '';

        var partyName  = r.PartyName    || '';
        var mobile     = r.MobileNumber || '';
        var cc         = r.CountryCode  || '';
        var email      = r.EmailAddress || '';
        var area       = r.PartyArea    || '';
        var initials   = _prPartyInitials(partyName);
        var hasAttach  = parseInt(r.AttachmentCount || 0, 10) > 0;

        html += '<tr>';

        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0">' +
            '<input class="form-check-input table-chkbox prCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
            '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px">' +
            '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</span></td>';

        /* Return Number */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (transDateFmt) { html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _prEsc(transDateFmt) + '</div>'; }
        } else {
            html += '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                ' data-uid="'    + parseInt(r.TransUID, 10) + '"' +
                ' data-module="' + prModUID + '"' +
                ' data-type="purchasereturn"' +
                ' data-number="' + _prEsc(r.UniqueNumber) + '"' +
                ' data-date="'   + _prEsc(r.TransDate || '') + '"' +
                ' data-status="' + _prEsc(status) + '">' +
                _prEsc(r.UniqueNumber) + '</a>';
            html += '<div class="d-flex align-items-center gap-2 mt-1">';
            html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _prEsc(transDateFmt) + '</div>';
            if (hasAttach) {
                html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                    ' data-uid="'        + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="'        + _prEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="/transactions/getAttachments"' +
                    ' data-module-uid="' + prModUID + '"' +
                    ' title="'           + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#6f42c1;">' +
                    '<i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
            if (createdBy) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#bbb;">by ' + _prEsc(createdBy) + '</div>';
            }
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="trans-amount-main">' + _prEsc(cur + ' ' + netAmt.toFixed(dec)) + '</div>';
            if (showPending) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#d33;font-weight:500;">Bal ' + _prEsc(cur + ' ' + pendingAmt.toFixed(dec)) + '</div>';
            }
        }
        html += '</td>';

        /* Refund Status */
        html += '<td>' + refundBadge + '</td>';

        /* Status Badge */
        html += '<td>' + statusBadgeHtml + '</td>';

        /* Vendor */
        html += '<td><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + _prEsc(initials) + '</div>';
        html += '<div><div class="trans-party-name">' + _prEsc(partyName) + '</div>';
        if (area) { html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _prEsc(area) + '</div>'; }
        if (mobile) { html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? _prEsc(cc) + ' ' : '') + _prEsc(mobile) + '</div>'; }
        html += '</div></div></td>';

        /* Date */
        html += '<td><div class="r2k-col-date">' + _prEsc(transDateFmt) + '</div></td>';

        /* Last Updated */
        html += '<td><div class="r2k-col-date">' + _prEsc(updatedDateFmt) + '</div>';
        if (updatedAgo) { html += '<div class="r2k-col-date-ago">' + _prEsc(updatedAgo) + '</div>'; }
        html += '<div class="text-muted r2k-col-date-by">by ' + _prEsc(updatedBy) + '</div></td>';

        /* Actions */
        html += '<td style="width:110px"><div class="d-flex align-items-center justify-content-end gap-1">';

        if (showPending && status === 'Approved') {
            html += '<button type="button" class="btn inv-pay-quick-btn prReceivePayment"' +
                ' data-uid="'     + parseInt(r.TransUID, 10) + '"' +
                ' data-num="'     + _prEsc(r.UniqueNumber || '') + '"' +
                ' data-date="'    + _prEsc(transDateFmt) + '"' +
                ' data-party="'   + _prEsc(partyName) + '"' +
                ' data-total="'   + netAmt + '"' +
                ' data-paid="'    + paidAmt + '"' +
                ' data-pending="' + pendingAmt + '"' +
                ' data-bs-toggle="tooltip" data-bs-trigger="hover"' +
                ' title="Receive Refund — ' + _prEsc(cur + ' ' + pendingAmt.toFixed(dec)) + ' pending">' +
                _prEsc(cur) + '</button>';
        }

        if (!isCancelled) {
            html += '<a class="btn btn-icon btn-sm text-warning inv-row-action" href="/purchasereturns/edit/' + parseInt(r.TransUID, 10) + '"' +
                ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit"><i class="bx bx-edit"></i></a>';
        }

        html += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="bx bx-dots-vertical-rounded fs-5"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';

        if (showPending && status === 'Approved') {
            html += '<li><button class="dropdown-item prReceivePayment"' +
                ' data-uid="'     + parseInt(r.TransUID, 10) + '"' +
                ' data-num="'     + _prEsc(r.UniqueNumber || '') + '"' +
                ' data-date="'    + _prEsc(transDateFmt) + '"' +
                ' data-party="'   + _prEsc(partyName) + '"' +
                ' data-total="'   + netAmt + '"' +
                ' data-paid="'    + paidAmt + '"' +
                ' data-pending="' + pendingAmt + '">' +
                '<i class="bx bx-money-withdraw me-2 text-success"></i>Receive Refund</button></li>';

            html += '<li><button class="dropdown-item prApplyDebit"' +
                ' data-uid="'       + parseInt(r.TransUID, 10) + '"' +
                ' data-num="'       + _prEsc(r.UniqueNumber || '') + '"' +
                ' data-party="'     + _prEsc(partyName) + '"' +
                ' data-partyuid="'  + parseInt(r.PartyUID, 10) + '"' +
                ' data-balance="'   + pendingAmt + '">' +
                '<i class="bx bx-transfer me-2 text-info"></i>Apply Debit Note</button></li>';

            html += '<li><hr class="dropdown-divider my-1"></li>';
        }

        if (!isDraft) {
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + prModUID + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + prModUID + '"><i class="bx bx-download me-2 text-success"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + prModUID + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
        }

        if (!isCancelled) {
            html += '<li><hr class="dropdown-divider my-1"></li>';
            if (!isDraft) {
                html += '<li><button class="dropdown-item text-warning pr-status-update"' +
                    ' data-uid="'    + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="'    + _prEsc(r.UniqueNumber || 'Draft') + '"' +
                    ' data-refund="' + paidAmt + '"' +
                    ' data-status="Cancelled"><i class="bx bx-x-circle me-2"></i>Cancel Return</button></li>';
            }
            html += '<li><button class="dropdown-item text-danger deletePurchaseReturn"' +
                ' data-uid="'    + parseInt(r.TransUID, 10) + '"' +
                ' data-num="'    + _prEsc(r.UniqueNumber || 'Draft') + '"' +
                ' data-refund="' + paidAmt + '">' +
                '<i class="bx bx-trash me-2"></i>Delete</button></li>';
        }

        html += '</ul></div></div></td>';
        html += '</tr>';
    });

    return html;
}

/**
 * @param {number}   [pageNo]
 * @param {number}   [rowLimit]
 * @param {Object}   [filter]
 * @param {Function} [afterLoad]
 * @returns {void}
 */
function getPurchaseReturnsDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_prReqSeq;

    $('#prTbody').html(
        '<tr><td colspan="10" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
    $('#prPagination').addClass('d-none').empty();
    $('.pr-status-tab .trans-tab-count').text('').addClass('d-none');

    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/108/' + pageNo,
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId    : parseInt(JwtData.Org.OrgUID, 10),
            branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
            RowLimit : rowLimit,
            PageNo   : pageNo,
            Filter   : filter,
        },
        success: function (resp) {
            if (reqSeq !== _prReqSeq) return;
            ajaxLoading(1);
            if (resp.Error) {
                $('#prTbody').html('');
                $('#prPagination').html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load purchase returns.') + '</strong></div>').removeClass('d-none');
                return;
            }

            var rows   = resp.Data       || [];
            var total  = parseInt(resp.TotalCount || 0, 10);
            var pg     = parseInt(resp.PageNo     || pageNo,   10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;

            $('#prTbody').html(_buildPrHtml(rows, offset));

            var $activeBadge = $('.pr-status-tab.active .trans-tab-count');
            if (total > 0) { $activeBadge.text(total).removeClass('d-none'); }
            else           { $activeBadge.text('').addClass('d-none'); }

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#prPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#prPagination').addClass('d-none').empty(); }

            if (resp.SummaryStats) { updatePrStatCards(resp.SummaryStats); }

            _prTotalRecords = total;
            _prPageCount    = $('#prTbody .prCheck').length;
            _prUpdateSelectAllBanner();
            initTooltips();
            $(window).trigger('scroll');
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _prReqSeq) return;
            ajaxLoading(1);
            $('#prPagination').html('<div class="alert alert-danger m-2">Failed to load purchase returns.</div>').removeClass('d-none');
        },
    });
}

/**
 * @param {Object} stats
 * @returns {void}
 */
function updatePrStatCards(stats) {
    if (!stats || !Object.keys(stats).length) return;
    var cur = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;

    /** @param {string} key @returns {number} */
    function cnt(key) { return stats[key] ? (stats[key].count  || 0) : 0; }
    /** @param {string} key @returns {number} */
    function amt(key) { return stats[key] ? (stats[key].amount || 0) : 0; }
    /** @param {number} v @returns {string} */
    function fmt(v) { return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }

    var activePrStatuses = ['Approved', 'Paid'];
    var cntAll = 0, amtAll = 0;
    $.each(activePrStatuses, function (_, k) {
        cntAll += cnt(k); amtAll += amt(k);
    });

    var cntPending = cnt('Approved');
    var amtPending = amt('Approved');

    $('[data-status="All"]      .apex-stat-count').text(cntAll.toLocaleString('en-IN'));
    $('[data-status="All"]      .apex-stat-amount').text(fmt(amtAll));
    $('[data-status="PRPending"] .apex-stat-count').text(cntPending.toLocaleString('en-IN'));
    $('[data-status="PRPending"] .apex-stat-amount').text(fmt(amtPending));
    $('[data-status="Paid"]     .apex-stat-count').text(cnt('Paid').toLocaleString('en-IN'));
    $('[data-status="Paid"]     .apex-stat-amount').text(fmt(amt('Paid')));
    $('[data-status="Draft"]    .apex-stat-count').text(cnt('Draft').toLocaleString('en-IN'));
}
