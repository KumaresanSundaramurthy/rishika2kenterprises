// ── Pro Forma Invoices list — module-specific JS ──────────────────────────────
// Shared utilities (debounce, initTooltips, generatePagination, timeAgo) are in common/core files

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _pfSelectAllMode = false;
var _pfTotalRecords  = 0;
var _pfPageCount     = 0;
var _pfReqSeq        = 0;

/** @returns {void} */
function _pfUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_pfSelectAllMode, _pfTotalRecords, _pfPageCount, 'pf', 'proforma invoices');
}
/** @returns {void} */
function _pfClearSelectAll() {
    _pfSelectAllMode = false;
    clearTransSelectAllDom('pf');
}
/** @returns {void} */
function deleteMultipleProformaInvoices() {
    deleteMultipleTrans(113, _pfSelectAllMode, _pfClearSelectAll, function () {
        getProFormaInvoicesDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {string} name
 * @returns {string}
 */
function _pfPartyInitials(name) {
    var parts = String(name || '').trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return String(name || '?').substring(0, 2).toUpperCase();
}

/**
 * @param {*} s
 * @returns {string}
 */
function _pfEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _pfTimeAgo(dt) { return timeAgo(dt); }

/* Badge class + icon per status */
var _pfBadgeClass = {
    Draft: 'trans-badge-Draft', Sent: 'trans-badge-Sent', Converted: 'trans-badge-Converted',
    Expired: 'trans-badge-Expired', Cancelled: 'trans-badge-Cancelled'
};
var _pfStatusIcon = {
    Draft: 'bx-pencil', Sent: 'bx-send', Converted: 'bx-transfer-alt',
    Expired: 'bx-calendar-x', Cancelled: 'bx-x-circle'
};
var _pfTerminal = { Cancelled: true, Converted: true };

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildPfHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="9">' +
            '<div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No pro forma invoices found</span>' +
            '<a href="' + global_base_url + 'proforma/create" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Create Pro Forma Invoice</a>' +
            '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined' && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1);
    var cur = (typeof JwtData !== 'undefined' && JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? _pfEsc(JwtData.GenSettings.CurrenySymbol) : '₹';
    var dec = (typeof JwtData !== 'undefined' && JwtData.GenSettings && JwtData.GenSettings.DecimalPoints) ? parseInt(JwtData.GenSettings.DecimalPoints, 10) : 2;
    var soonMs = 3 * 24 * 60 * 60 * 1000;
    var nowMs  = Date.now();
    var html   = '';

    $.each(rows, function (i, r) {
        var status     = r.Status || 'Draft';
        var isDraft    = status === 'Draft';
        var isTerminal = !!_pfTerminal[status];
        var badgeClass = _pfBadgeClass[status] || 'trans-badge-Draft';
        var icon       = _pfStatusIcon[status]  || 'bx-pencil';

        /* Due class for Valid Until */
        var dueClass = 'trans-due-normal';
        var dueTag   = '';
        if (status === 'Sent' && r.ValidityDate) {
            var dueMs = new Date(r.ValidityDate).getTime();
            if (dueMs < nowMs) {
                dueClass = 'trans-due-overdue';
                dueTag   = '<br><span style="font-size:var(--r2k-text-xs);">Expired</span>';
            } else if (dueMs <= nowMs + soonMs) {
                dueClass = 'trans-due-soon';
            }
        }
        var isOverdue = (dueClass === 'trans-due-overdue');

        var mobile  = _pfEsc(r.MobileNumber || '');
        var cc      = _pfEsc(r.CountryCode  || '');
        var email   = _pfEsc(r.EmailAddress || '');
        var waNum   = mobile ? (cc || '91') + mobile.replace(/[^0-9]/g, '') : '';
        var hasMob  = mobile !== '';
        var hasEmail = email !== '';
        var pName   = _pfEsc(r.PartyName || 'Customer');
        var docNum  = _pfEsc(r.UniqueNumber || '');
        var waMsg   = encodeURIComponent('Hello *' + (r.PartyName || '') + '*,\n\nHere is your Pro Forma Invoice *' + (r.UniqueNumber || '') + '*.\n\nThanks');

        var hasAttach = r.AttachmentCount && parseInt(r.AttachmentCount, 10) > 0;
        var netAmt    = parseFloat(r.NetAmount || 0);

        html += '<tr class="' + (isOverdue ? 'trans-row-overdue' : '') + '">';

        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0">' +
                '<input class="form-check-input table-chkbox pfCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
                '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px">' +
                '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + (offset + i + 1) + '</span></td>';

        /* PF # */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (r.TransDate) {
                html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _pfEsc(r.TransDate) + '</div>';
            }
        } else {
            html += '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-module="' + parseInt(r.ModuleUID, 10) + '"' +
                    ' data-type="proformainvoice"' +
                    ' data-number="' + docNum + '"' +
                    ' data-date="' + _pfEsc(r.TransDate || '') + '"' +
                    ' data-status="' + _pfEsc(status) + '">' + docNum + '</a>';
            html += '<div class="d-flex align-items-center gap-2 mt-1">';
            html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _pfEsc(r.TransDate || '') + '</div>';
            if (hasAttach) {
                html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                        ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                        ' data-num="' + docNum + '"' +
                        ' data-url="/transactions/getAttachments"' +
                        ' data-module-uid="' + parseInt(r.ModuleUID, 10) + '"' +
                        ' title="' + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                        ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;"><i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="trans-amount-main">' + cur + ' ' + netAmt.toFixed(dec) + '</div>';
        }
        html += '</td>';

        /* Status badge */
        html += '<td><span class="trans-badge ' + badgeClass + '">' +
                '<i class="bx ' + icon + '" style="font-size:var(--r2k-text-md);"></i> ' + _pfEsc(status) + '</span></td>';

        /* Customer */
        var initials = _pfPartyInitials(r.PartyName || '');
        html += '<td class="inv-party-td"><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:var(--r2k-text-sm);font-weight:600;flex-shrink:0;">' + initials + '</div>';
        html += '<div><div class="trans-party-name">' + pName + '</div>';
        if (r.PartyArea) {
            html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _pfEsc(r.PartyArea) + '</div>';
        }
        if (hasMob) {
            html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? cc + ' ' : '') + mobile + '</div>';
        }
        html += '</div></div>';
        if (hasMob || hasEmail) {
            html += '<div class="inv-contact-icons">';
            if (hasMob) {
                html += '<a href="javascript:void(0)" class="wa inv-wa-link" data-wa-url="https://wa.me/' + waNum + '?text=' + waMsg + '" data-bs-toggle="tooltip" data-bs-trigger="hover" title="WhatsApp"><i class="bx bxl-whatsapp"></i></a>';
                html += '<button class="comm-send-single sms" data-commtype="SMS" data-recipienttype="Customer"' +
                        ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                        ' data-name="' + pName + '" data-mobile="' + mobile + '" data-email="' + email + '"' +
                        ' data-module-uid="' + parseInt(r.ModuleUID, 10) + '"' +
                        ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS"><i class="bx bx-message-dots"></i></button>';
            }
            if (hasEmail) {
                html += '<button class="comm-send-single em" data-commtype="Email" data-recipienttype="Customer"' +
                        ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                        ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                        ' data-name="' + pName + '" data-mobile="' + mobile + '" data-email="' + email + '"' +
                        ' data-module-uid="' + parseInt(r.ModuleUID, 10) + '"' +
                        ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email"><i class="bx bx-envelope"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Valid Until */
        html += '<td class="' + dueClass + '">';
        if (!isDraft && r.ValidityDate) {
            html += _pfEsc(r.ValidityDate) + dueTag;
        } else {
            html += '<span class="text-muted">—</span>';
        }
        html += '</td>';

        /* Last Updated */
        var updFmt = _pfEsc(r.UpdatedOn || '');
        var updAgo = r.UpdatedOn ? _pfTimeAgo(r.UpdatedOn) : '';
        html += '<td><div class="r2k-col-date">' + updFmt + '</div>';
        if (updAgo) html += '<div class="r2k-col-date-ago">' + updAgo + '</div>';
        html += '<div class="text-muted r2k-col-date-by">by ' + _pfEsc(r.UpdatedBy || '—') + '</div></td>';

        /* Actions */
        html += '<td style="width:50px"><div class="d-flex align-items-center justify-content-end gap-1">';
        if (!isTerminal) {
            html += '<a class="btn btn-icon btn-sm text-warning" href="/proforma/' + parseInt(r.TransUID, 10) + '/edit" title="Edit"><i class="bx bx-edit"></i></a>';
        }
        html += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>';
        html += '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';
        if (!isDraft) {
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + parseInt(r.ModuleUID, 10) + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + parseInt(r.ModuleUID, 10) + '"><i class="bx bx-download me-2 text-success"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + parseInt(r.ModuleUID, 10) + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
            html += '<li><hr class="dropdown-divider my-1"></li>';
        }
        if (status === 'Draft') {
            html += '<li><button class="dropdown-item pf-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + docNum + '" data-status="Sent"><i class="bx bx-send me-2 text-primary"></i>Send Pro Forma</button></li>';
        }
        if (status === 'Sent') {
            html += '<li><button class="dropdown-item convertPFToInvoice" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + docNum + '"><i class="bx bx-receipt me-2 text-success"></i>Convert to Invoice</button></li>';
            html += '<li><button class="dropdown-item pf-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + docNum + '" data-status="Expired"><i class="bx bx-calendar-x me-2 text-warning"></i>Mark as Expired</button></li>';
            html += '<li><button class="dropdown-item pf-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + docNum + '" data-status="Cancelled"><i class="bx bx-x-circle me-2 text-warning"></i>Cancel</button></li>';
        }
        if (status === 'Expired') {
            html += '<li><button class="dropdown-item pf-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-status="Sent"><i class="bx bx-refresh me-2 text-info"></i>Reactivate</button></li>';
        }
        if (!isDraft && (hasMob || hasEmail)) {
            html += '<li><hr class="dropdown-divider my-1"></li>';
            if (hasMob) {
                html += '<li><a class="dropdown-item inv-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '?text=' + waMsg + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Customer"' +
                        ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-name="' + pName + '" data-mobile="' + mobile + '" data-email="' + email + '"' +
                        ' data-module-uid="' + parseInt(r.ModuleUID, 10) + '" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (hasEmail) {
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Customer"' +
                        ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                        ' data-name="' + pName + '" data-mobile="' + mobile + '" data-email="' + email + '"' +
                        ' data-module-uid="' + parseInt(r.ModuleUID, 10) + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
        }
        if (!isDraft) {
            html += '<li><hr class="dropdown-divider my-1"></li>';
            html += '<li><button class="dropdown-item duplicateProForma" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + docNum + '"><i class="bx bx-copy me-2 text-info"></i>Duplicate</button></li>';
        }
        if (!isTerminal) {
            html += '<li><hr class="dropdown-divider my-1"></li>';
            html += '<li><button class="dropdown-item text-danger deleteProForma" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + (docNum || 'Draft') + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        }
        html += '</ul></div></div></td>';

        html += '</tr>';
    });

    return html;
}

/**
 * @param {number}   pageNo
 * @param {number}   rowLimit
 * @param {Object}   filter
 * @param {Function} [afterLoad]
 * @returns {void}
 */
function getProFormaInvoicesDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;
    var reqSeq = ++_pfReqSeq;
    $('#pfTbody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...</td></tr>');
    $('#pfPagination').addClass('d-none').empty();
    $('.pf-status-tab .trans-tab-count').text('').addClass('d-none');
    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/113/' + pageNo,
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId    : parseInt(JwtData.Org.OrgUID, 10),
            branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
            RowLimit : rowLimit,
            PageNo   : pageNo,
            Filter   : filter
        },
        success: function (resp) {
            if (reqSeq !== _pfReqSeq) return;
            ajaxLoading(1);
            var rows   = resp.Data    || [];
            var total  = parseInt(resp.TotalCount || 0,       10);
            var pg     = parseInt(resp.PageNo     || pageNo,  10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;
            $('#pfTbody').html(_buildPfHtml(rows, offset));
            var $badge = $('.pf-status-tab.active .trans-tab-count');
            if (total > 0) { $badge.text(total).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#pfPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#pfPagination').addClass('d-none').empty(); }
            if (resp.SummaryStats) updateSummaryStats(resp.SummaryStats);
            _pfTotalRecords = total;
            _pfPageCount    = $('#pfTbody .pfCheck').length;
            _pfUpdateSelectAllBanner();
            initTooltips();
            ajaxLoading(0);
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _pfReqSeq) return;
            ajaxLoading(0);
            $('#pfTbody').html('<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load pro forma invoices. Please try again.</td></tr>');
        }
    });
}

