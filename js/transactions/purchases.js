// ── Purchases list — module-specific JS ──────────────────────────────────────

var _purchSelectAllMode = false;
var _purchTotalRecords  = 0;
var _purchPageCount     = 0;
var _purchReqSeq        = 0;

/** @returns {void} */
function _purchUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_purchSelectAllMode, _purchTotalRecords, _purchPageCount, 'purch', 'purchases');
}
/** @returns {void} */
function _purchClearSelectAll() {
    _purchSelectAllMode = false;
    clearTransSelectAllDom('purch');
}
/** @returns {void} */
function deleteMultiplePurchases() {
    deleteMultipleTrans(105, _purchSelectAllMode, _purchClearSelectAll, function () {
        getPurchasesDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {string} name
 * @returns {string}
 */
function _purchPartyInitials(name) {
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
function _purchEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _purchTimeAgo(dt) { return timeAgo(dt); }

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildPurchHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="9"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No purchase bills found</span>' +
            '<a href="' + global_base_url + 'purchases/create" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Record Purchase Bill</a>' +
            '</div></td></tr>';
    }

    var showSerial   = (typeof JwtData !== 'undefined') && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var cur          = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec          = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;
    var purchModUID  = 105;
    var html         = '';

    $.each(rows, function (i, r) {
        var serial      = offset + i + 1;
        var status      = r.Status || 'Draft';
        var isDraft     = status === 'Draft';
        var isCancelled = status === 'Cancelled';

        var netAmt     = parseFloat(r.NetAmount  || 0);
        var paidAmt    = parseFloat(r.PaidAmount || 0);
        var pendingAmt = Math.max(0, Math.round((netAmt - paidAmt) * 100) / 100);
        var showPending = !isDraft && pendingAmt > 0 && !isCancelled;

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
        var updatedAgo     = _purchTimeAgo(r.UpdatedOn);
        var updatedBy      = r.UpdatedBy || '—';

        var partyName  = r.PartyName    || '';
        var mobile     = r.MobileNumber || '';
        var cc         = r.CountryCode  || '';
        var email      = r.EmailAddress || '';
        var area       = r.PartyArea    || '';
        var waNum      = mobile ? (cc || '91').replace(/[^0-9]/g, '') + mobile.replace(/[^0-9]/g, '') : '';
        var initials   = _purchPartyInitials(partyName);
        var hasAttach  = parseInt(r.AttachmentCount || 0, 10) > 0;
        var createdBy  = r.CreatedBy || '';

        /* Payment mode */
        var payCount = parseInt(r.PaymentCount || 0, 10);
        var payModes = payCount > 0 && r.PaymentModes ? r.PaymentModes.split(',') : [];
        var firstMode = payModes.length ? payModes[0].trim() : '';
        var extraCnt  = Math.max(0, payCount - 1);
        var payBankName = _purchEsc((r.PayBankName || '').trim());
        var payAccNum   = _purchEsc((r.PayAccountNumber || '').trim());
        var isCashOnly  = payCount > 0 && !payBankName;
        var isDnMode    = firstMode === 'Debit Note';
        var debitApplied = r.PaymentModes && r.PaymentModes.indexOf('Debit Note') !== -1 ? '1' : '0';

        html += '<tr>';

        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0">' +
            '<input class="form-check-input table-chkbox purchCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
            '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px">' +
            '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</span></td>';

        /* Bill Number */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (transDateFmt) { html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _purchEsc(transDateFmt) + '</div>'; }
        } else {
            html += '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-module="' + purchModUID + '"' +
                ' data-type="purcrase"' +
                ' data-number="' + _purchEsc(r.UniqueNumber) + '"' +
                ' data-date="' + _purchEsc(r.TransDate || '') + '"' +
                ' data-status="' + _purchEsc(status) + '">' +
                _purchEsc(r.UniqueNumber) + '</a>';
            html += '<div class="d-flex align-items-center gap-2 mt-1">';
            html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _purchEsc(transDateFmt) + '</div>';
            if (hasAttach) {
                html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _purchEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="/transactions/getAttachments"' +
                    ' data-module-uid="' + purchModUID + '"' +
                    ' title="' + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#6f42c1;">' +
                    '<i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
            if (createdBy) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#bbb;">by ' + _purchEsc(createdBy) + '</div>';
            }
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="trans-amount-main">' + _purchEsc(cur + ' ' + netAmt.toFixed(dec)) + '</div>';
            if (showPending) {
                html += '<div style="font-size:var(--r2k-text-xs);color:#d33;font-weight:500;">Bal ' + _purchEsc(cur + ' ' + pendingAmt.toFixed(dec)) + '</div>';
            }
        }
        html += '</td>';

        /* Payment Status */
        html += '<td>' + payBadge;
        if ((paidAmt <= 0 || pendingAmt > 0.01) && !isDraft) {
            var daysPending = Math.floor((Date.now() - new Date(r.TransDate).getTime()) / 86400000);
            html += '<div style="font-size:var(--r2k-text-xs);color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:var(--r2k-text-sm);"></i> since ' + daysPending + ' day' + (daysPending !== 1 ? 's' : '') + '</div>';
        }
        html += '</td>';

        /* Payment Mode */
        html += '<td>';
        if (payCount > 0 && firstMode) {
            var modeBadge = isDnMode ? 'bg-label-warning' : 'bg-label-primary';
            var modeIcon  = isDnMode ? 'bx-transfer'      : 'bx-credit-card';
            html += '<div class="pay-mode-cell' + (payCount > 1 ? ' pay-mode-clickable' : '') + '"' +
                (payCount > 1 ? ' data-trans-uid="' + parseInt(r.TransUID, 10) + '" data-trans-num="' + _purchEsc(r.UniqueNumber || '') + '" style="cursor:pointer;"' : '') + '>';
            html += '<div class="d-flex align-items-center gap-1 flex-wrap">';
            html += '<span class="badge ' + modeBadge + '" style="font-size:var(--r2k-text-xs);"><i class="bx ' + modeIcon + ' me-1"></i>' + _purchEsc(firstMode) + '</span>';
            if (extraCnt > 0) {
                html += '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-xs);">+' + extraCnt + '</span>';
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

        /* Vendor */
        html += '<td class="purcr-party-td"><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + _purchEsc(initials) + '</div>';
        html += '<div><div class="trans-party-name">' + _purchEsc(partyName) + '</div>';
        if (area) { html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _purchEsc(area) + '</div>'; }
        if (mobile) { html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? _purchEsc(cc) + ' ' : '') + _purchEsc(mobile) + '</div>'; }
        html += '</div></div>';
        if (mobile || email) {
            html += '<div class="inv-contact-icons">';
            if (mobile) {
                html += '<a href="javascript:void(0)" class="wa purcr-wa-link"' +
                    ' data-wa-url="https://wa.me/' + waNum + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="WhatsApp">' +
                    '<i class="bx bxl-whatsapp"></i></a>';
                html += '<button class="comm-send-single sms"' +
                    ' data-commtype="SMS" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _purchEsc(partyName) + '"' +
                    ' data-mobile="' + _purchEsc(mobile) + '"' +
                    ' data-email="' + _purchEsc(email) + '"' +
                    ' data-module-uid="' + purchModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS">' +
                    '<i class="bx bx-message-dots"></i></button>';
            }
            if (email) {
                html += '<button class="comm-send-single em"' +
                    ' data-commtype="Email" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _purchEsc(partyName) + '"' +
                    ' data-mobile="' + _purchEsc(mobile) + '"' +
                    ' data-email="' + _purchEsc(email) + '"' +
                    ' data-module-uid="' + purchModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email">' +
                    '<i class="bx bx-envelope"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Last Updated */
        html += '<td><div class="r2k-col-date">' + _purchEsc(updatedDateFmt) + '</div>';
        if (updatedAgo) { html += '<div class="r2k-col-date-ago">' + _purchEsc(updatedAgo) + '</div>'; }
        html += '<div class="text-muted r2k-col-date-by">by ' + _purchEsc(updatedBy) + '</div></td>';

        /* Actions */
        html += '<td style="width:110px"><div class="d-flex align-items-center justify-content-end gap-1">';

        if (showPending) {
            html += '<button type="button" class="btn inv-pay-quick-btn purchReceivePayment"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _purchEsc(r.UniqueNumber || '') + '"' +
                ' data-date="' + _purchEsc(transDateFmt) + '"' +
                ' data-party="' + _purchEsc(partyName) + '"' +
                ' data-vendor-uid="' + parseInt(r.PartyUID, 10) + '"' +
                ' data-total="' + netAmt + '"' +
                ' data-paid="' + paidAmt + '"' +
                ' data-pending="' + pendingAmt + '"' +
                ' data-bs-toggle="tooltip" data-bs-trigger="hover"' +
                ' title="Issue Payment — ' + _purchEsc(cur + ' ' + pendingAmt.toFixed(dec)) + ' pending">' +
                _purchEsc(cur) + '</button>';
        }

        if (!isCancelled) {
            html += '<a class="btn btn-icon btn-sm text-warning inv-row-action" href="/purchases/edit/' + parseInt(r.TransUID, 10) + '"' +
                ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit"><i class="bx bx-edit"></i></a>';
        }

        html += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="bx bx-dots-vertical-rounded fs-5"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';

        if (showPending) {
            html += '<li><button class="dropdown-item purchReceivePayment"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _purchEsc(r.UniqueNumber || '') + '"' +
                ' data-date="' + _purchEsc(transDateFmt) + '"' +
                ' data-party="' + _purchEsc(partyName) + '"' +
                ' data-vendor-uid="' + parseInt(r.PartyUID, 10) + '"' +
                ' data-total="' + netAmt + '"' +
                ' data-paid="' + paidAmt + '"' +
                ' data-pending="' + pendingAmt + '">' +
                '<i class="bx bx-money-withdraw me-2 text-success"></i>Issue Payment</button></li>';
        }

        if (!isDraft) {
            if (showPending) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + purchModUID + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + purchModUID + '"><i class="bx bx-download me-2 text-success"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + purchModUID + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
        }

        if (!isCancelled && !isDraft && (mobile || email)) {
            html += '<li><hr class="dropdown-divider my-1"></li>';
            if (mobile) {
                html += '<li><a class="dropdown-item purcr-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _purchEsc(partyName) + '" data-mobile="' + _purchEsc(mobile) + '" data-email="' + _purchEsc(email) + '"' +
                    ' data-module-uid="' + purchModUID + '" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (email) {
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Vendor"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _purchEsc(partyName) + '" data-mobile="' + _purchEsc(mobile) + '" data-email="' + _purchEsc(email) + '"' +
                    ' data-module-uid="' + purchModUID + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
        }

        if (!isCancelled) {
            if (!isDraft || showPending) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (!isDraft) {
                html += '<li><button class="dropdown-item text-warning purch-status-update"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _purchEsc(r.UniqueNumber || 'Draft') + '"' +
                    ' data-paid="' + paidAmt + '"' +
                    ' data-debit-applied="' + debitApplied + '"' +
                    ' data-status="Cancelled"><i class="bx bx-x-circle me-2"></i>Cancel Bill</button></li>';
            }
            html += '<li><button class="dropdown-item duplicatePurchase"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _purchEsc(r.UniqueNumber || 'Draft') + '">' +
                '<i class="bx bx-copy me-2 text-info"></i>Duplicate</button></li>';
            html += '<li><button class="dropdown-item text-danger deletePurchase"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-num="' + _purchEsc(r.UniqueNumber || 'Draft') + '"' +
                ' data-paid="' + paidAmt + '"' +
                ' data-debit-applied="' + debitApplied + '">' +
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
function getPurchasesDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_purchReqSeq;

    $('#purchTbody').html(
        '<tr><td colspan="9" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
    $('#purchPagination').addClass('d-none').empty();
    $('.purch-status-tab .trans-tab-count').text('').addClass('d-none');

    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/105/' + pageNo,
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
            if (reqSeq !== _purchReqSeq) return;
            ajaxLoading(1);
            if (resp.Error) {
                $('#purchTbody').html('');
                $('#purchPagination').html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load purchases.') + '</strong></div>').removeClass('d-none');
                return;
            }

            var rows   = resp.Data       || [];
            var total  = parseInt(resp.TotalCount || 0, 10);
            var pg     = parseInt(resp.PageNo     || pageNo,   10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;

            $('#purchTbody').html(_buildPurchHtml(rows, offset));

            var $activeBadge = $('.purch-status-tab.active .trans-tab-count');
            if (total > 0) { $activeBadge.text(total).removeClass('d-none'); }
            else           { $activeBadge.text('').addClass('d-none'); }

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#purchPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#purchPagination').addClass('d-none').empty(); }

            if (resp.SummaryStats) { updatePurchStatCards(resp.SummaryStats); }

            _purchTotalRecords = total;
            _purchPageCount    = $('#purchTbody .purchCheck').length;
            _purchUpdateSelectAllBanner();
            initTooltips();
            $(window).trigger('scroll');
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _purchReqSeq) return;
            ajaxLoading(1);
            $('#purchPagination').html('<div class="alert alert-danger m-2">Failed to load purchase bills.</div>').removeClass('d-none');
        },
    });
}

/**
 * @param {Object} stats
 * @returns {void}
 */
function updatePurchStatCards(stats) {
    if (!stats || !Object.keys(stats).length) return;
    var cur = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;

    /** @param {string} key @returns {number} */
    function cnt(key) { return stats[key] ? (stats[key].count  || 0) : 0; }
    /** @param {string} key @returns {number} */
    function amt(key) { return stats[key] ? (stats[key].amount || 0) : 0; }
    /** @param {number} v @returns {string} */
    function fmt(v) { return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }

    var activePurchStatuses = ['Received', 'Partial', 'Paid'];
    var cntAll = 0, amtAll = 0;
    $.each(activePurchStatuses, function (_, k) {
        cntAll += cnt(k); amtAll += amt(k);
    });

    var cntPending = cnt('Received') + cnt('Partial');
    var amtPending = amt('Received') + amt('Partial');

    $('[data-status="All"]     .apex-stat-count').text(cntAll.toLocaleString('en-IN'));
    $('[data-status="All"]     .apex-stat-amount').text(fmt(amtAll));
    $('[data-status="Pending"] .apex-stat-count').text(cntPending.toLocaleString('en-IN'));
    $('[data-status="Pending"] .apex-stat-amount').text(fmt(amtPending));
    $('[data-status="Paid"]    .apex-stat-count').text(cnt('Paid').toLocaleString('en-IN'));
    $('[data-status="Paid"]    .apex-stat-amount').text(fmt(amt('Paid')));
    $('[data-status="Draft"]   .apex-stat-count').text(cnt('Draft').toLocaleString('en-IN'));
}

// ── WhatsApp link handler ─────────────────────────────────────────────────────
$(document).on('click', '.purcr-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// ── Register smart PDF attach alert when comm modal opens in Email tab ────────
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 105 || !recordUID) return;
    if (typeof _setupCommPdfAlert !== 'function') return;

    var rowData   = (typeof _commRowData !== 'undefined') ? _commRowData : null;
    var docNumber = rowData ? (rowData.docNumber || '') : '';
    var filename  = (docNumber || 'purchase').replace(/[^A-Za-z0-9_.\-]/g, '_') + '.pdf';

    _setupCommPdfAlert(docNumber, function (onSuccess) {
        $.ajax({
            url   : global_base_url + 'purchases/getPurchasePdfBase64',
            method: 'POST',
            data  : { TransUID: recordUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error || !resp.Base64) { onSuccess(null); return; }
                try {
                    var binary = atob(resp.Base64);
                    var bytes  = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                    var blob = new Blob([bytes], { type: 'application/pdf' });
                    onSuccess(new File([blob], resp.Filename || filename, { type: 'application/pdf' }));
                } catch (ex) {
                    onSuccess(null);
                }
            },
            error: function () { onSuccess(null); }
        });
    });
});

// ── Duplicate purchase bill ───────────────────────────────────────────────────
$(document).on('click', '.duplicatePurchase', /**
 * @param {jQuery.Event} e
 * @returns {void}
 */
function () {
    var uid = $(this).data('uid');
    var num = $(this).data('num') || '';
    Swal.fire({
        title             : 'Duplicate Purchase Bill?',
        html              : num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Duplicate this purchase bill?',
        icon              : 'question',
        showCancelButton  : true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor : '#6c757d',
        confirmButtonText : 'Yes, Duplicate',
        cancelButtonText  : 'Cancel',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        ajaxLoading(1);
        $.ajax({
            url    : global_services_url + 'api/purchases/duplicatePurchase',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : {
                orgId   : parseInt(JwtData.Org.OrgUID, 10),
                userId  : parseInt(JwtData.User.UserUID, 10),
                branchId: parseInt(JwtData.Org.BranchUID, 10) || 1,
                TransUID: uid,
            },
            success: function (resp) {
                ajaxLoading(0);
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                window.location.href = resp.EditURL;
            },
            error: function () { ajaxLoading(0); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); },
        });
    });
});

// ── Payment Details Panel ─────────────────────────────────────────────────────
initTransPaymentPanel('', '#6f42c1');
