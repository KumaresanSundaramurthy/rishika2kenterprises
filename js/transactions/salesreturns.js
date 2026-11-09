// Sales Returns module JS

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _srSelectAllMode = false;
var _srTotalRecords  = 0;
var _srPageCount     = 0;
var _srReqSeq        = 0;

/** @returns {void} */
function _srUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_srSelectAllMode, _srTotalRecords, _srPageCount, 'sr', 'sales returns');
}
/** @returns {void} */
function _srClearSelectAll() {
    _srSelectAllMode = false;
    clearTransSelectAllDom('sr');
}
/** @returns {void} */
function deleteMultipleSalesReturns() {
    deleteMultipleTrans(106, _srSelectAllMode, _srClearSelectAll, function () {
        getSalesReturnsDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {string} name
 * @returns {string}
 */
function _srPartyInitials(name) {
    if (!name) return '?';
    var parts = name.trim().split(/\s+/);
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();
}

/**
 * @param {*} s
 * @returns {string}
 */
function _srEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _srTimeAgo(dt) { return timeAgo(dt); }

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildSrHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="9"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No sales returns found</span>' +
            '<a href="/salesreturns/create" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Create Sales Return</a>' +
            '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined') && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var cur        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;
    var srModUID   = 106;
    var html       = '';

    $.each(rows, function (i, r) {
        var serial      = offset + i + 1;
        var status      = r.Status || 'Draft';
        var isDraft     = status === 'Draft';
        var isCancelled = status === 'Cancelled';
        var isTerminal  = isCancelled;

        var netAmt     = parseFloat(r.NetAmount  || 0);
        var paidAmt    = parseFloat(r.PaidAmount || 0);
        var pendingAmt = Math.max(0, Math.round((netAmt - paidAmt) * 100) / 100);
        var showPending = !isDraft && pendingAmt > 0 && !isCancelled && status !== 'Rejected';

        /* Payment status badge */
        var payBadge = '';
        if (!isDraft) {
            if (paidAmt <= 0) {
                payBadge = '<span class="badge bg-label-warning" style="font-size:var(--r2k-text-xs);">Pending</span>';
            } else if (pendingAmt <= 0.01) {
                payBadge = '<span class="badge bg-label-success" style="font-size:var(--r2k-text-xs);">Paid</span>';
            } else {
                payBadge = '<span class="badge bg-label-info" style="font-size:var(--r2k-text-xs);">Partially Paid</span>';
            }
        }

        var transDateFmt   = r.TransDate ? formatDateDisplay(r.TransDate) : '';
        var updatedDateFmt = r.UpdatedOn ? formatDateDisplay(r.UpdatedOn.substring(0, 10)) : '';
        var updatedAgo     = _srTimeAgo(r.UpdatedOn);
        var updatedBy      = r.UpdatedBy || '—';

        var partyName  = r.PartyName    || '';
        var mobile     = r.MobileNumber || '';
        var cc         = r.CountryCode  || '';
        var email      = r.EmailAddress || '';
        var area       = r.PartyArea    || '';
        var waNum      = mobile ? (cc || '91').replace(/[^0-9]/g, '') + mobile.replace(/[^0-9]/g, '') : '';
        var initials   = _srPartyInitials(partyName);
        var hasAttach  = parseInt(r.AttachmentCount || 0, 10) > 0;
        var createdBy  = r.CreatedBy || '';

        /* Payment mode */
        var payCount     = parseInt(r.PaymentCount || 0, 10);
        var payModes     = payCount > 0 && r.PaymentModes ? r.PaymentModes.split(',') : [];
        var firstMode    = payModes.length ? payModes[0].trim() : '';
        var extraCnt     = Math.max(0, payCount - 1);
        var payBankName  = _srEsc((r.PayBankName || '').trim());
        var payAccNum    = _srEsc((r.PayAccountNumber || '').trim());
        var isCashOnly   = payCount > 0 && !payBankName;
        var hasPayAttach = parseInt(r.PaymentAttachmentCount || 0, 10) > 0;

        var creditApplied = (parseFloat(r.CancelCreditApplied || 0) > 0) ? 1 : 0;
        var cashRefunded  = parseFloat(r.CancelCasrRefunded || 0);

        html += '<tr>';

        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0">' +
            '<input class="form-check-input table-chkbox srCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
            '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px">' +
            '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</span></td>';

        /* Return # */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (transDateFmt) { html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _srEsc(transDateFmt) + '</div>'; }
        } else {
            html += '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-module="' + srModUID + '"' +
                ' data-type="salesreturn"' +
                ' data-number="' + _srEsc(r.UniqueNumber) + '"' +
                ' data-date="' + _srEsc(r.TransDate || '') + '"' +
                ' data-status="' + _srEsc(status) + '">' +
                _srEsc(r.UniqueNumber) + '</a>';
            html += '<div class="d-flex align-items-center gap-2 mt-1">';
            html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _srEsc(transDateFmt) + '</div>';
            if (hasAttach) {
                html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _srEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="/transactions/getAttachments"' +
                    ' data-module-uid="' + srModUID + '"' +
                    ' title="' + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;">' +
                    '<i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
            if (createdBy) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#bbb;">by ' + _srEsc(createdBy) + '</div>';
            }
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="trans-amount-main">' + _srEsc(cur + ' ' + netAmt.toFixed(dec)) + '</div>';
            if (showPending) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#d33;font-weight:500;">Bal ' + _srEsc(cur + ' ' + pendingAmt.toFixed(dec)) + '</div>';
            }
        }
        html += '</td>';

        /* Payment Status */
        html += '<td>' + payBadge;
        if (showPending) {
            var daysPending = Math.floor((Date.now() - new Date(r.TransDate).getTime()) / 86400000);
            html += '<div style="font-size:var(--r2k-text-xs);color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:var(--r2k-text-sm);"></i> since ' + daysPending + ' day' + (daysPending !== 1 ? 's' : '') + '</div>';
        }
        html += '</td>';

        /* Payment Mode */
        html += '<td>';
        if (payCount > 0 && firstMode) {
            html += '<div class="pay-mode-cell' + (payCount > 1 ? ' pay-mode-clickable' : '') + '"' +
                (payCount > 1 ? ' data-trans-uid="' + parseInt(r.TransUID, 10) + '" data-trans-num="' + _srEsc(r.UniqueNumber || '') + '" style="cursor:pointer;"' : '') + '>';
            html += '<div class="d-flex align-items-center gap-1 flex-wrap">';
            html += '<span class="badge bg-label-primary" style="font-size:var(--r2k-text-xs);"><i class="bx bx-credit-card me-1"></i>' + _srEsc(firstMode) + '</span>';
            if (extraCnt > 0) {
                html += '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-xs);">+' + extraCnt + '</span>';
            }
            if (hasPayAttach) {
                html += '<button type="button" class="btn btn-link p-0 transPayAttachBtn"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _srEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="' + global_services_url + 'api/salesreturns/getPaymentAttachments"' +
                    ' title="' + parseInt(r.PaymentAttachmentCount, 10) + ' payment attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;"><i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
            if (!isCashOnly && (payBankName || payAccNum)) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#6c757d;margin-top:3px;line-height:1.4;">';
                if (payBankName) { html += '<div><i class="bx bx-building-house me-1" style="font-size:var(--r2k-text-70);"></i>' + payBankName + '</div>'; }
                if (payAccNum)   { html += '<div style="font-family:monospace;letter-spacing:.03em;">' + payAccNum + '</div>'; }
                html += '</div>';
            }
            html += '</div>';
        } else {
            html += '<span class="text-muted" style="font-size:var(--r2k-text-78);">—</span>';
        }
        html += '</td>';

        /* Customer */
        html += '<td class="inv-party-td"><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + _srEsc(initials) + '</div>';
        html += '<div><div class="trans-party-name">' + _srEsc(partyName) + '</div>';
        if (area)   { html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _srEsc(area) + '</div>'; }
        if (mobile) { html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? _srEsc(cc) + ' ' : '') + _srEsc(mobile) + '</div>'; }
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
                    ' data-name="' + _srEsc(partyName) + '"' +
                    ' data-mobile="' + _srEsc(mobile) + '"' +
                    ' data-email="' + _srEsc(email) + '"' +
                    ' data-module-uid="' + srModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS">' +
                    '<i class="bx bx-message-dots"></i></button>';
            }
            if (email) {
                html += '<button class="comm-send-single em"' +
                    ' data-commtype="Email" data-recipienttype="Customer"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _srEsc(partyName) + '"' +
                    ' data-mobile="' + _srEsc(mobile) + '"' +
                    ' data-email="' + _srEsc(email) + '"' +
                    ' data-module-uid="' + srModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email">' +
                    '<i class="bx bx-envelope"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Last Updated */
        html += '<td><div class="r2k-col-date">' + _srEsc(updatedDateFmt) + '</div>';
        if (updatedAgo) { html += '<div class="r2k-col-date-ago">' + _srEsc(updatedAgo) + '</div>'; }
        html += '<div class="text-muted r2k-col-date-by">by ' + _srEsc(updatedBy) + '</div></td>';

        /* Actions */
        html += '<td style="width:110px"><div class="d-flex align-items-center justify-content-end gap-1">';

        if (showPending) {
            html += '<button type="button" class="btn inv-pay-quick-btn srReceivePayment"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _srEsc(r.UniqueNumber || '') + '"' +
                ' data-date="' + _srEsc(transDateFmt) + '"' +
                ' data-party="' + _srEsc(partyName) + '"' +
                ' data-total="' + netAmt + '"' +
                ' data-paid="' + paidAmt + '"' +
                ' data-pending="' + pendingAmt + '"' +
                ' title="Refund Payment — ' + _srEsc(cur + ' ' + pendingAmt.toFixed(dec)) + ' pending">' +
                _srEsc(cur) + '</button>';
        }

        if (!isTerminal) {
            html += '<a class="btn btn-icon btn-sm text-warning inv-row-action" href="/salesreturns/edit/' + parseInt(r.TransUID, 10) + '"' +
                ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit"><i class="bx bx-edit"></i></a>';
        }

        html += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="bx bx-dots-vertical-rounded fs-5"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';

        var ddHasItems = false;

        if (!isDraft) {
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + srModUID + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + srModUID + '"><i class="bx bx-download me-2 text-success"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + srModUID + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
            ddHasItems = true;
        }

        if (showPending) {
            if (ddHasItems) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            html += '<li><button class="dropdown-item srReceivePayment"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _srEsc(r.UniqueNumber || '') + '"' +
                ' data-date="' + _srEsc(transDateFmt) + '"' +
                ' data-party="' + _srEsc(partyName) + '"' +
                ' data-total="' + netAmt + '"' +
                ' data-paid="' + paidAmt + '"' +
                ' data-pending="' + pendingAmt + '">' +
                '<i class="bx bx-transfer me-2 text-success"></i>Refund Payment</button></li>';
            html += '<li><button class="dropdown-item srApplyCredit"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _srEsc(r.UniqueNumber || '') + '"' +
                ' data-party="' + _srEsc(partyName) + '"' +
                ' data-partyuid="' + parseInt(r.PartyUID, 10) + '"' +
                ' data-balance="' + pendingAmt + '">' +
                '<i class="bx bx-credit-card me-2 text-primary"></i>Apply Credit to Invoice</button></li>';
            ddHasItems = true;
        }

        if (!isDraft && (mobile || email)) {
            if (ddHasItems) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (mobile) {
                html += '<li><a class="dropdown-item inv-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Customer"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-name="' + _srEsc(partyName) + '"' +
                    ' data-mobile="' + _srEsc(mobile) + '" data-email="' + _srEsc(email) + '"' +
                    ' data-module-uid="' + srModUID + '" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (email) {
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Customer"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _srEsc(partyName) + '" data-mobile="' + _srEsc(mobile) + '" data-email="' + _srEsc(email) + '"' +
                    ' data-module-uid="' + srModUID + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
            ddHasItems = true;
        }

        if (!isCancelled) {
            if (ddHasItems) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (!isDraft) {
                html += '<li><button class="dropdown-item text-warning sr-status-update"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _srEsc(r.UniqueNumber || '') + '"' +
                    ' data-paid="' + paidAmt + '"' +
                    ' data-refund="' + cashRefunded + '"' +
                    ' data-credit-applied="' + creditApplied + '"' +
                    ' data-status="Cancelled">' +
                    '<i class="bx bx-x-circle me-2"></i>Cancel</button></li>';
            }
            html += '<li><button class="dropdown-item text-danger deleteSalesReturn"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _srEsc(r.UniqueNumber || 'Draft') + '">' +
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
function getSalesReturnsDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_srReqSeq;

    $('#srTbody').html(
        '<tr><td colspan="9" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
    $('#srPagination').addClass('d-none').empty();
    $('.sr-status-tab .trans-tab-count').text('').addClass('d-none');

    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/106/' + pageNo,
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
            if (reqSeq !== _srReqSeq) return;
            ajaxLoading(1);
            if (resp.Error) {
                $('#srTbody').html('');
                $('#srPagination').html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load sales returns.') + '</strong></div>').removeClass('d-none');
                return;
            }

            var rows   = resp.Data       || [];
            var total  = parseInt(resp.TotalCount || 0, 10);
            var pg     = parseInt(resp.PageNo     || pageNo,   10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;

            $('#srTbody').html(_buildSrHtml(rows, offset));

            var $activeBadge = $('.sr-status-tab.active .trans-tab-count');
            if (total > 0) { $activeBadge.text(total).removeClass('d-none'); }
            else           { $activeBadge.text('').addClass('d-none'); }

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#srPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#srPagination').addClass('d-none').empty(); }

            if (resp.SummaryStats && typeof updateSummaryStats === 'function') { updateSummaryStats(resp.SummaryStats); }

            _srTotalRecords = total;
            _srPageCount    = $('#srTbody .srCheck').length;
            _srUpdateSelectAllBanner();
            initTooltips();
            $(window).trigger('scroll');
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _srReqSeq) return;
            ajaxLoading(1);
            $('#srPagination').html('<div class="alert alert-danger m-2">Failed to load sales returns.</div>').removeClass('d-none');
        },
    });
}

// ── Payment Details Panel ─────────────────────────────────────────────────────
initTransPaymentPanel('text-success', '#198754');

