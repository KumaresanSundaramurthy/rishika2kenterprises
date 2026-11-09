// ── Purchase Orders list — module-specific JS ─────────────────────────────────

var _poSelectAllMode = false;
var _poTotalRecords  = 0;
var _poPageCount     = 0;
var _poReqSeq        = 0;

/** @returns {void} */
function _poUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_poSelectAllMode, _poTotalRecords, _poPageCount, 'po', 'purchase orders');
}
/** @returns {void} */
function _poClearSelectAll() {
    _poSelectAllMode = false;
    clearTransSelectAllDom('po');
}
/** @returns {void} */
function deleteMultiplePurchaseOrders() {
    deleteMultipleTrans(104, _poSelectAllMode, _poClearSelectAll, function () {
        getPurchaseOrdersDetails(PageNo, RowLimit, Filter);
    });
}

var _poStatusBadge = {
    'Draft':     'bg-label-secondary',
    'Received':  'bg-label-info',
    'Closed':    'bg-label-success',
    'Cancelled': 'bg-label-danger',
};
var _poTerminal = ['Closed', 'Cancelled'];
var _poTransitions = {
    'Draft':    [{ db: 'Received',  label: 'Receive PO' }],
    'Received': [{ db: 'Closed',    label: 'Close' }, { db: 'Cancelled', label: 'Cancel' }],
};

/**
 * @param {string} name
 * @returns {string}
 */
function _poPartyInitials(name) {
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
function _poEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _poTimeAgo(dt) { return timeAgo(dt); }

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildPoHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="10"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No purchase orders found</span>' +
            '<a href="' + global_base_url + 'purchaseorders/create" class="btn btn-primary btn-sm px-3"><i class="bx bx-plus me-1"></i>New</a>' +
            '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined') && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var cur        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;
    var poModUID   = 104;
    var now        = new Date();
    var html       = '';

    $.each(rows, function (i, r) {
        var serial     = offset + i + 1;
        var status     = r.Status || 'Draft';
        var isDraft    = status === 'Draft';
        var isClosed   = status === 'Closed';
        var isCancelled = status === 'Cancelled';
        var isTerminal = _poTerminal.indexOf(status) !== -1;
        var badge      = _poStatusBadge[status] || 'bg-label-secondary';
        var transitions = _poTransitions[status] || [];

        var netAmt  = parseFloat(r.NetAmount || 0);
        var amtStr  = cur + ' ' + netAmt.toFixed(dec);

        var transDateFmt   = r.TransDate    ? formatDateDisplay(r.TransDate)    : '';
        var validityFmt    = r.ValidityDate ? formatDateDisplay(r.ValidityDate) : '';
        var updatedDateFmt = r.UpdatedOn    ? formatDateDisplay(r.UpdatedOn.substring(0, 10)) : '';
        var updatedAgo     = _poTimeAgo(r.UpdatedOn);
        var updatedBy      = r.UpdatedBy || '—';

        /* Expected date label */
        var edLabel = '';
        var edClass = '';
        if (!isDraft && r.ValidityDate) {
            var valTs   = new Date(r.ValidityDate).getTime();
            var todayTs = new Date(new Date().toDateString()).getTime();
            var diffMs  = valTs - todayTs;
            var diffDays = Math.round(diffMs / 86400000);
            if (isClosed) {
                edLabel = 'Completed'; edClass = 'text-success';
            } else if (isCancelled) {
                edLabel = 'Cancelled'; edClass = 'text-danger';
            } else if (diffDays < 0) {
                edLabel = 'Overdue'; edClass = 'text-danger';
            } else if (diffDays === 0) {
                edLabel = 'Today'; edClass = 'text-warning';
            } else {
                edLabel = 'in ' + diffDays + ' day' + (diffDays > 1 ? 's' : '');
                edClass = 'text-primary';
            }
        }

        var partyName  = r.PartyName      || '';
        var mobile     = r.MobileNumber   || '';
        var cc         = r.CountryCode    || '';
        var email      = r.EmailAddress   || '';
        var area       = r.PartyArea      || '';
        var waNum      = mobile ? (cc || '91').replace(/[^0-9]/g, '') + mobile.replace(/[^0-9]/g, '') : '';
        var initials   = _poPartyInitials(partyName);
        var hasAttach  = parseInt(r.AttachmentCount || 0, 10) > 0;
        var createdBy  = r.CreatedBy || '';

        html += '<tr>';

        /* Checkbox */
        html += '<td style="width:40px"><div class="form-check">' +
            '<input class="form-check-input table-chkbox poCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
            '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:50px">' +
            '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</span></td>';

        /* PO Number */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="text-muted fst-italic" style="font-size:var(--r2k-text-82);">Draft</span>';
        } else {
            html += '<a href="javascript:void(0)" class="fw-semibold text-primary text-decoration-underline viewTransaction d-block lr-sm"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-module="' + poModUID + '"' +
                ' data-type="purcraseorder"' +
                ' data-number="' + _poEsc(r.UniqueNumber) + '"' +
                ' data-date="' + _poEsc(r.TransDate || '') + '"' +
                ' data-status="' + _poEsc(status) + '">' +
                _poEsc(r.UniqueNumber) + '</a>';
        }
        html += '<div class="d-flex align-items-center gap-2">';
        html += '<div class="apex-doc-meta">' + _poEsc(transDateFmt) + '</div>';
        if (!isDraft && hasAttach) {
            html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _poEsc(r.UniqueNumber || '') + '"' +
                ' data-url="/transactions/getAttachments"' +
                ' data-module-uid="' + poModUID + '"' +
                ' title="' + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;">' +
                '<i class="bx bx-paperclip"></i></button>';
        }
        html += '</div>';
        if (createdBy) {
            html += '<div class="apex-doc-meta">by ' + _poEsc(createdBy) + '</div>';
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="text-dark fw-semibold">' + _poEsc(amtStr) + '</div>';
        }
        html += '</td>';

        /* Status badge */
        html += '<td>';
        if (transitions.length) {
            html += '<div class="dropdown">' +
                '<span class="badge ' + badge + ' cursor-pointer" data-bs-toggle="dropdown"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-current="' + _poEsc(status) + '">' +
                _poEsc(status) + ' <i class="bx bx-chevron-down" style="font-size:var(--r2k-text-70);vertical-align:middle"></i>' +
                '</span>' +
                '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';
            $.each(transitions, function (_, t) {
                html += '<li><button class="dropdown-item po-status-update"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _poEsc(r.UniqueNumber || '') + '"' +
                    ' data-status="' + _poEsc(t.db) + '">' +
                    _poEsc(t.label) + '</button></li>';
            });
            html += '</ul></div>';
        } else {
            html += '<span class="badge ' + badge + '">' + _poEsc(status) + '</span>';
        }
        html += '</td>';

        /* Vendor */
        html += '<td class="inv-party-td"><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + _poEsc(initials) + '</div>';
        html += '<div><div class="trans-party-name">' + _poEsc(partyName) + '</div>';
        if (area) {
            html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _poEsc(area) + '</div>';
        }
        if (mobile) {
            html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? _poEsc(cc) + ' ' : '') + _poEsc(mobile) + '</div>';
        }
        html += '</div></div>';
        if (mobile || email) {
            html += '<div class="inv-contact-icons">';
            if (mobile) {
                html += '<a href="javascript:void(0)" class="wa inv-wa-link"' +
                    ' data-wa-url="https://wa.me/' + waNum + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="WhatsApp">' +
                    '<i class="bx bxl-whatsapp"></i></a>';
                html += '<button class="comm-send-single sms"' +
                    ' data-commtype="SMS" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-name="' + _poEsc(partyName) + '"' +
                    ' data-mobile="' + _poEsc(mobile) + '"' +
                    ' data-email="' + _poEsc(email) + '"' +
                    ' data-module-uid="' + poModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS">' +
                    '<i class="bx bx-message-dots"></i></button>';
            }
            if (email) {
                html += '<button class="comm-send-single em"' +
                    ' data-commtype="Email" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _poEsc(partyName) + '"' +
                    ' data-mobile="' + _poEsc(mobile) + '"' +
                    ' data-email="' + _poEsc(email) + '"' +
                    ' data-module-uid="' + poModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email">' +
                    '<i class="bx bx-envelope"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* PO Date */
        html += '<td>' + _poEsc(transDateFmt) + '</td>';

        /* Expected Date */
        html += '<td>';
        if (!isDraft && r.ValidityDate) {
            html += '<div style="font-size:var(--r2k-text-82);">' + _poEsc(validityFmt) + '</div>';
            if (edLabel) {
                html += '<div class="apex-ed-label ' + edClass + '">' + edLabel + '</div>';
            }
        } else {
            html += '<span class="text-muted">—</span>';
        }
        html += '</td>';

        /* Last Updated */
        html += '<td><div class="r2k-col-date">' + _poEsc(updatedDateFmt) + '</div>';
        if (updatedAgo) { html += '<div class="r2k-col-date-ago">' + _poEsc(updatedAgo) + '</div>'; }
        html += '<div class="text-muted r2k-col-date-by">by ' + _poEsc(updatedBy) + '</div></td>';

        /* Actions */
        html += '<td style="width:50px"><div class="d-flex align-items-center justify-content-end gap-1">';
        if (!isTerminal) {
            html += '<a class="btn btn-icon btn-sm text-warning" href="/purchaseorders/edit/' + parseInt(r.TransUID, 10) + '" title="Edit"><i class="bx bx-edit fs-6"></i></a>';
        }
        html += '<div class="dropdown"><button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="bx bx-dots-vertical-rounded fs-5 text-muted"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';

        var hasPrinted = false;
        if (!isDraft) {
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + poModUID + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + poModUID + '"><i class="bx bx-download me-2 text-success"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + poModUID + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
            hasPrinted = true;
        }

        if (!isDraft && (mobile || email)) {
            if (hasPrinted) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (mobile) {
                html += '<li><a class="dropdown-item inv-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-name="' + _poEsc(partyName) + '"' +
                    ' data-mobile="' + _poEsc(mobile) + '" data-email="' + _poEsc(email) + '" data-module-uid="' + poModUID + '"' +
                    ' style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (email) {
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _poEsc(partyName) + '" data-mobile="' + _poEsc(mobile) + '" data-email="' + _poEsc(email) + '"' +
                    ' data-module-uid="' + poModUID + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
            hasPrinted = true;
        }

        if (!isTerminal) {
            if (hasPrinted) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (!isDraft) {
                html += '<li><button class="dropdown-item text-warning po-status-update"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _poEsc(r.UniqueNumber || '') + '"' +
                    ' data-status="Cancelled"><i class="bx bx-x-circle me-2"></i>Cancel</button></li>';
            }
            html += '<li><button class="dropdown-item text-danger deletePO"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _poEsc(r.UniqueNumber || 'Draft') + '">' +
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
function getPurchaseOrdersDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_poReqSeq;

    $('#poTbody').html(
        '<tr><td colspan="10" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
    $('#poPagination').addClass('d-none').empty();
    $('.po-status-tab .trans-tab-count').text('').addClass('d-none');

    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/104/' + pageNo,
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
            if (reqSeq !== _poReqSeq) return;
            ajaxLoading(1);
            if (resp.Error) {
                $('#poTbody').html('');
                $('#poPagination').html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load purchase orders.') + '</strong></div>').removeClass('d-none');
                return;
            }

            var rows   = resp.Data       || [];
            var total  = parseInt(resp.TotalCount || 0, 10);
            var pg     = parseInt(resp.PageNo     || pageNo,   10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;

            $('#poTbody').html(_buildPoHtml(rows, offset));

            var $activeBadge = $('.po-status-tab.active .trans-tab-count');
            if (total > 0) { $activeBadge.text(total).removeClass('d-none'); }
            else           { $activeBadge.text('').addClass('d-none'); }

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#poPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#poPagination').addClass('d-none').empty(); }

            if (resp.SummaryStats) { updatePoStatCards(resp.SummaryStats); }

            _poTotalRecords = total;
            _poPageCount    = $('#poTbody .poCheck').length;
            _poUpdateSelectAllBanner();
            initTooltips();
            $(window).trigger('scroll');
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _poReqSeq) return;
            ajaxLoading(1);
            $('#poPagination').html('<div class="alert alert-danger m-2">Failed to load purchase orders.</div>').removeClass('d-none');
        },
    });
}

/**
 * @param {Object} stats
 * @returns {void}
 */
function updatePoStatCards(stats) {
    if (!stats || !Object.keys(stats).length) return;
    var cur = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;

    /** @param {string} key @returns {number} */
    function cnt(key) { return stats[key] ? (stats[key].count  || 0) : 0; }
    /** @param {string} key @returns {number} */
    function amt(key) { return stats[key] ? (stats[key].amount || 0) : 0; }
    /** @param {number} v @returns {string} */
    function fmt(v) { return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }

    var cntAll = 0, amtAll = 0;
    $.each(stats, function (k, v) {
        cntAll += (v.count || 0); amtAll += (v.amount || 0);
    });

    $('[data-status="All"]       .apex-stat-count').text(cntAll.toLocaleString('en-IN'));
    $('[data-status="All"]       .apex-stat-amount').text(fmt(amtAll));
    $('[data-status="Received"]  .apex-stat-count').text(cnt('Received').toLocaleString('en-IN'));
    $('[data-status="Received"]  .apex-stat-amount').text(fmt(amt('Received')));
    $('[data-status="Closed"]    .apex-stat-count').text(cnt('Closed').toLocaleString('en-IN'));
    $('[data-status="Closed"]    .apex-stat-amount').text(fmt(amt('Closed')));
    $('[data-status="Cancelled"] .apex-stat-count').text(cnt('Cancelled').toLocaleString('en-IN'));
    $('[data-status="Cancelled"] .apex-stat-amount').text(fmt(amt('Cancelled')));
    $('[data-status="Draft"]     .apex-stat-count').text(cnt('Draft').toLocaleString('en-IN'));
}
