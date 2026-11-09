// ── Sales Orders list — module-specific JS ───────────────────────────────────
// Shared utilities (debounce, initTooltips) are in common.js
// Date helpers (formatDateDisplay) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _soSelectAllMode = false;
var _soTotalRecords  = 0;
var _soPageCount     = 0;
var _soReqSeq        = 0;

/** @returns {void} */
function _soUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_soSelectAllMode, _soTotalRecords, _soPageCount, 'so', 'sales orders');
}
/** @returns {void} */
function _soClearSelectAll() {
    _soSelectAllMode = false;
    clearTransSelectAllDom('so');
}
/** @returns {void} */
function deleteMultipleSalesOrders() {
    deleteMultipleTrans(102, _soSelectAllMode, _soClearSelectAll, function () {
        getSalesOrdersDetails(PageNo, RowLimit, Filter);
    });
}

/** Status badge + icon config for sales orders. */
var _soStatusCfg = {
    'Draft':     { badge: 'trans-badge-Draft',     icon: 'bx-pencil' },
    'Pending':   { badge: 'trans-badge-Pending',   icon: 'bx-time' },
    'Completed': { badge: 'trans-badge-Completed', icon: 'bx-check-double' },
    'Cancelled': { badge: 'trans-badge-Cancelled', icon: 'bx-x-circle' },
    'Converted': { badge: 'trans-badge-Converted', icon: 'bx-transfer' },
};
var _soTerminal = ['Cancelled', 'Converted'];

/**
 * Build an initials avatar from a party name.
 * @param {string} name
 * @returns {string}
 */
function _soPartyInitials(name) {
    if (!name) return '?';
    var parts = name.trim().split(/\s+/);
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();
}

/**
 * Escape a string for safe HTML output.
 * @param {string} s
 * @returns {string}
 */
function _soEsc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _soTimeAgo(dt) { return timeAgo(dt); }

/**
 * Build HTML rows from services Data array.
 * @param {Array}  rows
 * @param {number} offset  zero-based offset for serial numbers
 * @returns {string}
 */
function _buildSoHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="9"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No sales orders found</span>' +
            '<a href="' + global_base_url + 'salesorders/create" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>New</a>' +
            '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined') && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var cur        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;
    var soModUID   = 102;
    var now        = new Date();
    var soonMs     = 3 * 24 * 3600 * 1000;
    var html       = '';

    $.each(rows, function (i, r) {
        var serial     = offset + i + 1;
        var status     = r.Status || 'Draft';
        var isDraft    = status === 'Draft';
        var isTerminal = _soTerminal.indexOf(status) !== -1;
        var cfg        = _soStatusCfg[status] || { badge: 'trans-badge-Draft', icon: 'bx-circle' };

        /* ── Due-date class ── */
        var dueClass = 'trans-due-normal';
        var dueTag   = '';
        if (!isDraft && !isTerminal && r.ValidityDate) {
            var dueTs = new Date(r.ValidityDate).getTime();
            if (dueTs < now.getTime()) {
                dueClass = 'trans-due-overdue';
                dueTag   = '<br><span style="font-size:var(--r2k-text-xs);">Overdue</span>';
            } else if (dueTs <= now.getTime() + soonMs) {
                dueClass = 'trans-due-soon';
            }
        }
        var isOverdue = dueClass === 'trans-due-overdue';

        /* ── Party info ── */
        var partyName  = r.PartyName  || '';
        var mobile     = r.MobileNumber || '';
        var cc         = r.CountryCode  || '';
        var email      = r.EmailAddress || '';
        var area       = r.PartyArea    || '';
        var waNum      = mobile ? (cc || '91').replace(/[^0-9]/g,'') + mobile.replace(/[^0-9]/g,'') : '';
        var initials   = _soPartyInitials(partyName);

        /* ── Amount ── */
        var netAmt     = parseFloat(r.NetAmount || 0);
        var amtStr     = cur + ' ' + netAmt.toFixed(dec);

        /* ── Dates ── */
        var transDateFmt    = r.TransDate    ? formatDateDisplay(r.TransDate)    : '';
        var validityFmt     = r.ValidityDate ? formatDateDisplay(r.ValidityDate) : '';
        var updatedDateFmt  = r.UpdatedOn    ? formatDateDisplay(r.UpdatedOn.substring(0, 10)) : '';
        var updatedAgo      = _soTimeAgo(r.UpdatedOn);
        var updatedBy       = r.UpdatedBy || '—';

        /* ── Attachments ── */
        var hasAttach = parseInt(r.AttachmentCount || 0, 10) > 0;

        /* ── Row ── */
        html += '<tr class="' + (isOverdue ? 'trans-row-overdue' : '') + '">';

        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0">' +
            '<input class="form-check-input table-chkbox soCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
            '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px">' +
            '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</span></td>';

        /* Order # */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (transDateFmt) { html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _soEsc(transDateFmt) + '</div>'; }
        } else {
            html += '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-module="' + soModUID + '"' +
                ' data-type="salesorder"' +
                ' data-number="' + _soEsc(r.UniqueNumber) + '"' +
                ' data-date="' + _soEsc(r.TransDate || '') + '"' +
                ' data-status="' + _soEsc(status) + '">' +
                _soEsc(r.UniqueNumber) + '</a>';
            html += '<div class="d-flex align-items-center gap-2 mt-1">';
            html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _soEsc(transDateFmt) + '</div>';
            if (hasAttach) {
                html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _soEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="/transactions/getAttachments"' +
                    ' data-module-uid="' + soModUID + '"' +
                    ' title="' + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;">' +
                    '<i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="trans-amount-main">' + _soEsc(amtStr) + '</div>';
        }
        html += '</td>';

        /* Status */
        html += '<td><span class="trans-badge ' + cfg.badge + '">' +
            '<i class="bx ' + cfg.icon + '" style="font-size:var(--r2k-text-md);"></i> ' +
            _soEsc(status) + '</span></td>';

        /* Customer */
        html += '<td class="inv-party-td"><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + _soEsc(initials) + '</div>';
        html += '<div><div class="trans-party-name">' + _soEsc(partyName) + '</div>';
        if (area) { html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _soEsc(area) + '</div>'; }
        if (mobile) { html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? _soEsc(cc) + ' ' : '') + _soEsc(mobile) + '</div>'; }
        html += '</div></div>';
        if (mobile || email) {
            html += '<div class="inv-contact-icons">';
            if (mobile) {
                html += '<a href="javascript:void(0)" class="wa inv-wa-link"' +
                    ' data-wa-url="https://wa.me/' + waNum + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="WhatsApp">' +
                    '<i class="bx bxl-whatsapp"></i></a>';
                html += '<button class="comm-send-single sms"' +
                    ' data-commtype="SMS" data-recipienttype="Customer"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-name="' + _soEsc(partyName) + '"' +
                    ' data-mobile="' + _soEsc(mobile) + '"' +
                    ' data-email="' + _soEsc(email) + '"' +
                    ' data-module-uid="' + soModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS">' +
                    '<i class="bx bx-message-dots"></i></button>';
            }
            if (email) {
                html += '<button class="comm-send-single em"' +
                    ' data-commtype="Email" data-recipienttype="Customer"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _soEsc(partyName) + '"' +
                    ' data-mobile="' + _soEsc(mobile) + '"' +
                    ' data-email="' + _soEsc(email) + '"' +
                    ' data-module-uid="' + soModUID + '"' +
                    ' data-doc-type="Sales Order"' +
                    ' data-doc-number="' + _soEsc(r.UniqueNumber || '') + '"' +
                    ' data-net-amount="' + netAmt + '"' +
                    ' data-paid-amount="' + parseFloat(r.PaidAmount || 0) + '"' +
                    ' data-doc-date="' + _soEsc(transDateFmt) + '"' +
                    ' data-validity-date="' + _soEsc(validityFmt) + '"' +
                    ' data-doc-status="' + _soEsc(status) + '"' +
                    ' data-trans-token="' + _soEsc(r.TransToken || '') + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email">' +
                    '<i class="bx bx-envelope"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Expected Delivery */
        html += '<td class="' + dueClass + '">';
        if (!isDraft && r.ValidityDate) {
            html += _soEsc(validityFmt) + dueTag;
        } else {
            html += '<span class="text-muted">—</span>';
        }
        html += '</td>';

        /* Last Updated */
        html += '<td><div class="r2k-col-date">' + _soEsc(updatedDateFmt) + '</div>';
        if (updatedAgo) { html += '<div class="r2k-col-date-ago">' + _soEsc(updatedAgo) + '</div>'; }
        html += '<div class="text-muted r2k-col-date-by">by ' + _soEsc(updatedBy) + '</div></td>';

        /* Actions */
        html += '<td style="width:50px"><div class="d-flex align-items-center justify-content-end gap-1">';
        if (!isTerminal) {
            html += '<a class="btn btn-icon btn-sm text-warning" href="/salesorders/edit/' + parseInt(r.TransUID, 10) + '" title="Edit"><i class="bx bx-edit"></i></a>';
        }
        html += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="bx bx-dots-vertical-rounded fs-5"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';

        var hasPrinted = false;
        if (!isDraft) {
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + soModUID + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + soModUID + '"><i class="bx bxs-file-pdf me-2 text-danger"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + soModUID + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
            hasPrinted = true;
        }

        if (!isDraft && (mobile || email)) {
            if (hasPrinted) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (mobile) {
                html += '<li><a class="dropdown-item inv-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Customer" data-uid="' + parseInt(r.PartyUID, 10) + '" data-name="' + _soEsc(partyName) + '" data-mobile="' + _soEsc(mobile) + '" data-email="' + _soEsc(email) + '" data-module-uid="' + soModUID + '" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (email) {
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Customer" data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '" data-name="' + _soEsc(partyName) + '" data-mobile="' + _soEsc(mobile) + '" data-email="' + _soEsc(email) + '" data-module-uid="' + soModUID + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
            hasPrinted = true;
        }

        if (status === 'Pending') {
            if (hasPrinted) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            html += '<li><button class="dropdown-item convertSOToInvoice" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + _soEsc(r.UniqueNumber || '') + '"><i class="bx bx-receipt me-2 text-success"></i>Convert to Invoice</button></li>';
            html += '<li><button class="dropdown-item convertSOToChallan" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + _soEsc(r.UniqueNumber || '') + '"><i class="bx bx-package me-2 text-info"></i>Convert to Delivery Challan</button></li>';
            hasPrinted = true;
        }

        if (!isTerminal) {
            if (hasPrinted) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (!isDraft) {
                html += '<li><button class="dropdown-item text-warning so-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + _soEsc(r.UniqueNumber || '') + '" data-status="Cancelled"><i class="bx bx-x-circle me-2"></i>Cancel</button></li>';
            }
            html += '<li><button class="dropdown-item text-danger deleteSalesOrder" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + _soEsc(r.UniqueNumber || 'Draft') + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        }

        html += '</ul></div></div></td>';
        html += '</tr>';
    });

    return html;
}

/**
 * Load a page of sales orders from the services endpoint.
 * @param {number}   [pageNo]
 * @param {number}   [rowLimit]
 * @param {Object}   [filter]
 * @param {Function} [afterLoad]
 * @returns {void}
 */
function getSalesOrdersDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_soReqSeq;

    $(ModuleTable + ' tbody').html(
        '<tr><td colspan="9" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
    $('#soPagination').addClass('d-none').empty();
    $('.so-status-tab .trans-tab-count').text('').addClass('d-none');

    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/102/' + pageNo,
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
            if (reqSeq !== _soReqSeq) return;
            ajaxLoading(1);
            if (resp.Error) {
                $(ModuleTable + ' tbody').html('');
                $('#soPagination').html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load sales orders.') + '</strong></div>').removeClass('d-none');
                return;
            }

            var rows      = resp.Data       || [];
            var total     = parseInt(resp.TotalCount || 0, 10);
            var pg        = parseInt(resp.PageNo     || pageNo,   10);
            var rl        = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset    = (pg - 1) * rl;

            $(ModuleTable + ' tbody').html(_buildSoHtml(rows, offset));

            var $activeBadge = $('.so-status-tab.active .trans-tab-count');
            if (total > 0) { $activeBadge.text(total).removeClass('d-none'); }
            else           { $activeBadge.text('').addClass('d-none'); }

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#soPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#soPagination').addClass('d-none').empty(); }

            if (resp.SummaryStats) { updateSoStatCards(resp.SummaryStats); }

            _soTotalRecords = total;
            _soPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _soUpdateSelectAllBanner();
            initTooltips();
            $(window).trigger('scroll');
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _soReqSeq) return;
            ajaxLoading(1);
            $('#soPagination').html('<div class="alert alert-danger m-2">Failed to load sales orders.</div>').removeClass('d-none');
        },
    });
}

/**
 * Update stat-strip cards from a SummaryStats response object.
 * @param {Object} stats
 * @returns {void}
 */
function updateSoStatCards(stats) {
    if (!stats || !Object.keys(stats).length) return;
    var cur = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;

    /** @param {string} key @returns {number} */
    function cnt(key) { return stats[key] ? (stats[key].count  || 0) : 0; }
    /** @param {string} key @returns {number} */
    function amt(key) { return stats[key] ? (stats[key].amount || 0) : 0; }

    var excludeKeys = ['Cancelled', 'Converted', 'Draft'];
    var cntAll = 0, amtAll = 0;
    $.each(stats, function (k, v) {
        if (excludeKeys.indexOf(k) === -1) { cntAll += (v.count || 0); amtAll += (v.amount || 0); }
    });

    function fmt(v) { return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }

    $('[data-stat-filter="All"]       .apex-stat-count').text(cntAll.toLocaleString('en-IN'));
    $('[data-stat-filter="All"]       .apex-stat-amount').text(fmt(amtAll));
    $('[data-stat-filter="Pending"]   .apex-stat-count').text(cnt('Pending').toLocaleString('en-IN'));
    $('[data-stat-filter="Pending"]   .apex-stat-amount').text(fmt(amt('Pending')));
    $('[data-stat-filter="Completed"] .apex-stat-count').text(cnt('Completed').toLocaleString('en-IN'));
    $('[data-stat-filter="Completed"] .apex-stat-amount').text(fmt(amt('Completed')));
    $('[data-stat-filter="Draft"]     .apex-stat-count').text(cnt('Draft').toLocaleString('en-IN'));
}

