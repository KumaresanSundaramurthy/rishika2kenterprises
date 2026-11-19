// ── Delivery Challans list — module-specific JS ───────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _dcSelectAllMode = false;
var _dcTotalRecords  = 0;
var _dcPageCount     = 0;

/** @returns {void} */
function _dcUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_dcSelectAllMode, _dcTotalRecords, _dcPageCount, 'dc', 'delivery challans');
}
/** @returns {void} */
function _dcClearSelectAll() {
    _dcSelectAllMode = false;
    clearTransSelectAllDom('dc');
}
/** @returns {void} */
function deleteMultipleDeliveryChallans() {
    deleteMultipleTrans(112, _dcSelectAllMode, _dcClearSelectAll, function () {
        getDeliveryChallansDetails(PageNo, RowLimit, Filter);
    });
}

/**
 * @param {number}   [pageNo]
 * @param {number}   [rowLimit]
 * @param {Object}   [filter]
 * @param {Function} [afterLoad]
 * @returns {void}
 */
function getDeliveryChallansDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    ajaxLoading(1);
    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/112/' + pageNo,
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : { Filter: JSON.stringify(filter), RowLimit: rowLimit, orgId: parseInt(JwtData.Org.OrgUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1, userId: parseInt(JwtData.User.UserUID, 10) },
        success: function (resp) {
            ajaxLoading(0);
            if (resp.Error) {
                $(ModuleTable + ' tbody').html(_dcEmptyRow('Failed to load delivery challans.'));
                return;
            }
            PageNo   = resp.PageNo   || pageNo;
            RowLimit = resp.RowLimit || rowLimit;
            var offset = (PageNo - 1) * RowLimit;
            $(ModuleTable + ' tbody').html(_buildDCRows(resp.Data || [], offset));
            var count = resp.TotalCount || 0;
            $('.dcPagination').html(_buildDCPagination(count, PageNo, RowLimit)).toggleClass('r2k-pag-hidden', count === 0);
            _dcTotalRecords = count;
            _dcPageCount    = $(ModuleTable + ' tbody .dcCheck').length;
            var $badge = $('.dc-status-tab.active .trans-tab-count');
            if (count > 0) { $badge.text(count).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
            if (resp.SummaryStats) { _buildDCStats(resp.SummaryStats); }
            _dcUpdateSelectAllBanner();
            initTooltips();
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            ajaxLoading(0);
            $(ModuleTable + ' tbody').html(_dcEmptyRow('Failed to load delivery challans.'));
        }
    });
}

/* ── Row / pagination / stats builders ──────────────────────────────────── */

/**
 * @param {string} str
 * @returns {string}
 */
function _dcEsc(str) {
    return $('<span>').text(str || '').html();
}

/**
 * @param {string} dateStr
 * @returns {string}
 */
function _dcFmtDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date((dateStr + '').replace(/-/g, '/').replace('T', ' '));
    if (isNaN(d.getTime())) return dateStr;
    var fmt    = _transListDateFormat || 'd M Y';
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var pad    = function (n) { return n < 10 ? '0' + n : n; };
    return fmt.replace('d', pad(d.getDate()))
              .replace('j', d.getDate())
              .replace('M', months[d.getMonth()])
              .replace('m', pad(d.getMonth() + 1))
              .replace('n', d.getMonth() + 1)
              .replace('Y', d.getFullYear())
              .replace('y', String(d.getFullYear()).slice(-2));
}

/**
 * @param {string} dateStr
 * @param {string} updatedBy
 * @returns {string}
 */
function _dcFmtUpdated(dateStr, updatedBy) {
    if (!dateStr) return '<div class="r2k-col-date">—</div><div class="text-muted r2k-col-date-by">by —</div>';
    var d = new Date((dateStr + '').replace(/-/g, '/').replace('T', ' '));
    if (isNaN(d.getTime())) return '<div class="r2k-col-date">' + _dcEsc(dateStr) + '</div>';
    var diff = Math.floor((Date.now() - d.getTime()) / 1000);
    var ago  = '';
    if (diff < 60)         { ago = 'just now'; }
    else if (diff < 3600)  { ago = Math.floor(diff / 60) + 'm ago'; }
    else if (diff < 86400) { ago = Math.floor(diff / 3600) + 'h ago'; }
    else if (diff < 2592000)  { ago = Math.floor(diff / 86400) + 'd ago'; }
    else if (diff < 31536000) { ago = Math.floor(diff / 2592000) + 'mo ago'; }
    var html = '<div class="r2k-col-date">' + _dcFmtDate(dateStr) + '</div>';
    if (ago) html += '<div class="r2k-col-date-ago">' + ago + '</div>';
    html += '<div class="text-muted r2k-col-date-by">by ' + _dcEsc(updatedBy || '—') + '</div>';
    return html;
}

/**
 * @param {string}      name
 * @param {string|null} imagePath
 * @returns {string}
 */
function _dcPartyAvatar(name, imagePath) {
    if (imagePath) {
        return '<img src="' + CDN_URL + imagePath + '" class="rounded-circle" width="32" height="32" alt="' + _dcEsc(name) + '" style="object-fit:cover;flex-shrink:0;">';
    }
    var initials = (name || '?').split(/\s+/).map(function (w) { return w.charAt(0).toUpperCase(); }).slice(0, 2).join('');
    var clrs = ['#696cff','#71dd37','#ff3e1d','#03c3ec','#ffab00','#2b2c40'];
    var bg   = clrs[(name ? name.charCodeAt(0) : 0) % clrs.length];
    return '<div style="background:' + bg + ';color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + initials + '</div>';
}

/**
 * @param {string} msg
 * @returns {string}
 */
function _dcEmptyRow(msg) {
    return '<tr class="r2k-empty-row"><td colspan="10">' +
        '<div class="r2k-list-empty"><div class="r2k-list-empty-inner">' +
        '<div class="r2k-empty-icon icon-info"><i class="bx bx-truck"></i></div>' +
        '<div class="r2k-empty-title">No delivery challans yet</div>' +
        '<div class="r2k-empty-sub">Create delivery challans to track goods dispatched to customers.</div>' +
        '<a href="' + global_base_url + 'deliverychallan/create" class="btn btn-primary px-4">' +
        '<i class="bx bx-plus me-1"></i> Create Delivery Challan</a>' +
        '</div></div></td></tr>';
}

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildDCRows(rows, offset) {
    if (!rows || !rows.length) { return _dcEmptyRow('No delivery challans found'); }

    var showSerial   = JwtData.GenSettings.SerialNoDisplay == 1;
    var cur          = JwtData.GenSettings.CurrenySymbol || '₹';
    var dec          = parseInt(JwtData.GenSettings.DecimalPoints || 2);
    var terminalSt   = ['Cancelled', 'Converted'];
    var statusBadge  = { Draft: 'trans-badge-Draft', Dispatched: 'trans-badge-Dispatched', Delivered: 'trans-badge-Delivered', 'Partially Returned': 'trans-badge-PartiallyReturned', Converted: 'trans-badge-Converted', Cancelled: 'trans-badge-Cancelled' };
    var statusIco    = { Draft: 'bx-pencil', Dispatched: 'bx-time', Delivered: 'bx-check-circle', 'Partially Returned': 'bx-adjust', Converted: 'bx-transfer', Cancelled: 'bx-x-circle' };
    var typeBadge    = { Returnable: 'badge text-bg-warning', 'Non-Returnable': 'badge text-bg-secondary', 'Job Work': 'badge text-bg-info' };
    var now          = Date.now();
    var html         = '';

    $.each(rows, function (i, r) {
        var serialNo    = offset + i + 1;
        var status      = r.Status || 'Draft';
        var isDraft     = status === 'Draft';
        var isTerminal  = terminalSt.indexOf(status) !== -1;
        var badgeCls    = statusBadge[status]  || 'trans-badge-Draft';
        var ico         = statusIco[status]    || 'bx-circle';
        var challanType = r.DocType || 'Non-Returnable';
        var typeBadgeCls = typeBadge[challanType] || 'badge text-bg-secondary';
        var isReturnType = (challanType === 'Returnable' || challanType === 'Job Work');
        var mobileNum   = $.trim(r.MobileNumber  || '');
        var countryCode = $.trim(r.CountryCode   || '');
        var partyEmail  = $.trim(r.EmailAddress  || '');
        var hasMobile   = mobileNum  !== '';
        var hasEmail    = partyEmail !== '';
        var partyName   = r.PartyName || 'Customer';
        var docNum      = r.UniqueNumber || '';
        var waNum       = hasMobile ? (countryCode || '91').replace(/[^0-9]/g, '') + mobileNum.replace(/[^0-9]/g, '') : '';
        var waMsg       = encodeURIComponent('Hello *' + partyName + '*,\n\nHere is your Delivery Challan *' + docNum + '*.\n\nThanks');
        var hasAttach   = r.AttachmentCount && parseInt(r.AttachmentCount) > 0;

        // Overdue
        var dueClass = 'trans-due-normal', dueTag = '';
        if (isReturnType && !isDraft && !isTerminal && r.ExpectedDeliveryDate) {
            var dueTs = new Date(r.ExpectedDeliveryDate.replace(/-/g, '/')).getTime();
            if (dueTs < now) {
                dueClass = 'trans-due-overdue';
                dueTag   = '<br><span style="font-size:var(--r2k-text-xs);">Overdue</span>';
            } else if (dueTs <= now + 3 * 86400000) {
                dueClass = 'trans-due-soon';
            }
        }
        var rowClass = dueClass === 'trans-due-overdue' ? 'trans-row-overdue' : (dueClass === 'trans-due-soon' ? 'trans-row-due-soon' : '');

        // Days Out
        var daysOut = null;
        if (!isDraft && isReturnType && (status === 'Dispatched' || status === 'Partially Returned') && r.TransDate) {
            daysOut = Math.floor((now - new Date(r.TransDate.replace(/-/g, '/')).getTime()) / 86400000);
        }

        // Challan number column
        var numHtml;
        if (isDraft || !r.UniqueNumber) {
            numHtml = '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (r.TransDate) { numHtml += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _dcFmtDate(r.TransDate) + '</div>'; }
        } else {
            numHtml = '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                ' data-uid="' + (r.TransUID | 0) + '" data-module="' + (r.ModuleUID | 0) + '"' +
                ' data-type="deliverychallan" data-number="' + _dcEsc(r.UniqueNumber) + '"' +
                ' data-date="' + _dcEsc(r.TransDate || '') + '" data-status="' + _dcEsc(status) + '">' +
                _dcEsc(r.UniqueNumber) + '</a>';
            numHtml += '<div class="d-flex align-items-center gap-2 mt-1">';
            numHtml += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _dcFmtDate(r.TransDate) + '</div>';
            if (hasAttach) {
                numHtml += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                    ' data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="/transactions/getAttachments" data-module-uid="' + (r.ModuleUID | 0) + '"' +
                    ' title="' + parseInt(r.AttachmentCount) + ' attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;"><i class="bx bx-paperclip"></i></button>';
            }
            numHtml += '</div>';
            if (r.CreatedBy) { numHtml += '<div style="font-size:var(--r2k-text-xs);color:#bbb;">by ' + _dcEsc(r.CreatedBy) + '</div>'; }
        }

        // Amount
        var amtHtml = (isDraft && parseFloat(r.NetAmount || 0) === 0)
            ? '<span class="text-muted">—</span>'
            : '<div class="trans-amount-main">' + cur + ' ' + parseFloat(r.NetAmount || 0).toFixed(dec) + '</div>';

        // Expected return column
        var expHtml;
        if (!isDraft && isReturnType && r.ExpectedDeliveryDate) {
            expHtml = _dcFmtDate(r.ExpectedDeliveryDate) + dueTag;
        } else {
            expHtml = '<span class="text-muted">—</span>';
        }
        if (daysOut !== null) {
            var pColor, pBg, pBorder;
            if (daysOut > 7)      { pColor = '#dc3545'; pBg = '#fde8ea'; pBorder = '#f5c2c7'; }
            else if (daysOut > 3) { pColor = '#cc5500'; pBg = '#ffe8cc'; pBorder = '#ffbb80'; }
            else                  { pColor = '#495057'; pBg = '#dee2e6'; pBorder = '#ced4da'; }
            var daysLabel = daysOut === 0 ? 'Today' : daysOut + ' day' + (daysOut > 1 ? 's' : '') + ' out';
            expHtml += '<div style="margin-top:3px;"><span style="font-size:var(--r2k-text-xs);font-weight:600;color:' + pColor + ';background:' + pBg + ';border:1px solid ' + pBorder + ';padding:1px 7px;border-radius:10px;display:inline-block;">' + daysLabel + '</span></div>';
        }

        // Customer column
        var custHtml = '<div class="d-flex align-items-center gap-2">' + _dcPartyAvatar(partyName, r.PartyImage) + '<div>';
        custHtml += '<div class="trans-party-name">' + _dcEsc(partyName) + '</div>';
        if (r.PartyArea)  { custHtml += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _dcEsc(r.PartyArea) + '</div>'; }
        if (hasMobile)    { custHtml += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (countryCode ? _dcEsc(countryCode) + ' ' : '') + _dcEsc(mobileNum) + '</div>'; }
        custHtml += '</div></div>';
        if (hasMobile || hasEmail) {
            custHtml += '<div class="inv-contact-icons">';
            if (hasMobile) {
                custHtml += '<a href="javascript:void(0)" class="wa inv-wa-link" data-wa-url="https://wa.me/' + waNum + '?text=' + waMsg + '" data-bs-toggle="tooltip" data-bs-trigger="hover" title="WhatsApp"><i class="bx bxl-whatsapp"></i></a>';
                custHtml += '<button class="comm-send-single sms" data-commtype="SMS" data-recipienttype="Customer" data-uid="' + (r.PartyUID | 0) + '" data-name="' + _dcEsc(partyName) + '" data-mobile="' + _dcEsc(mobileNum) + '" data-email="' + _dcEsc(partyEmail) + '" data-module-uid="' + (r.ModuleUID | 0) + '" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS"><i class="bx bx-message-dots"></i></button>';
            }
            if (hasEmail) {
                custHtml += '<button class="comm-send-single em" data-commtype="Email" data-recipienttype="Customer" data-uid="' + (r.PartyUID | 0) + '" data-trans-uid="' + (r.TransUID | 0) + '" data-name="' + _dcEsc(partyName) + '" data-mobile="' + _dcEsc(mobileNum) + '" data-email="' + _dcEsc(partyEmail) + '" data-module-uid="' + (r.ModuleUID | 0) + '" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email"><i class="bx bx-envelope"></i></button>';
            }
            custHtml += '</div>';
        }

        // Actions
        var actHtml = '<div class="d-flex align-items-center justify-content-end gap-1">';
        if (!isTerminal && (isDraft || status === 'Dispatched')) {
            actHtml += '<a class="btn btn-icon btn-sm text-warning" href="' + global_base_url + 'deliverychallan/' + (r.TransUID | 0) + '/edit" title="Edit"><i class="bx bx-edit"></i></a>';
        }
        actHtml += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>';
        actHtml += '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';
        if (!isDraft) {
            actHtml += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + (r.TransUID | 0) + '" data-module="' + (r.ModuleUID | 0) + '"><i class="bx bx-printer me-2 text-primary"></i>Print / Download</button></li>';
            actHtml += '<li><button class="dropdown-item downloadPdfTransaction" data-uid="' + (r.TransUID | 0) + '" data-module="' + (r.ModuleUID | 0) + '"><i class="bx bx-download me-2 text-success"></i>Download PDF</button></li>';
            actHtml += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + (r.TransUID | 0) + '" data-module="' + (r.ModuleUID | 0) + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
        }
        if (!isDraft && (hasMobile || hasEmail)) {
            actHtml += '<li><hr class="dropdown-divider my-1"></li>';
            if (hasMobile) {
                actHtml += '<li><a class="dropdown-item inv-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '?text=' + waMsg + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                actHtml += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Customer" data-uid="' + (r.PartyUID | 0) + '" data-name="' + _dcEsc(partyName) + '" data-mobile="' + _dcEsc(mobileNum) + '" data-email="' + _dcEsc(partyEmail) + '" data-module-uid="' + (r.ModuleUID | 0) + '" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (hasEmail) {
                actHtml += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Customer" data-uid="' + (r.PartyUID | 0) + '" data-trans-uid="' + (r.TransUID | 0) + '" data-name="' + _dcEsc(partyName) + '" data-mobile="' + _dcEsc(mobileNum) + '" data-email="' + _dcEsc(partyEmail) + '" data-module-uid="' + (r.ModuleUID | 0) + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
        }
        if (!isDraft) {
            actHtml += '<li><hr class="dropdown-divider my-1"></li>';
            if (status !== 'Cancelled') {
                actHtml += '<li><a class="dropdown-item" href="' + global_base_url + 'packing-list/' + (r.TransUID | 0) + '"><i class="bx bx-list-ul me-2 text-secondary"></i>Packing List</a></li>';
            }
            if ((status === 'Dispatched' || status === 'Partially Returned') && isReturnType) {
                actHtml += '<li><button class="dropdown-item dc-partial-return-btn" data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || '') + '"><i class="bx bx-adjust me-2 text-info"></i>Partial / Full Return</button></li>';
            }
            if (status === 'Dispatched' && !isReturnType) {
                actHtml += '<li><button class="dropdown-item dc-status-update" data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || '') + '" data-status="Delivered"><i class="bx bx-check-circle me-2 text-success"></i>Mark as Delivered</button></li>';
            }
            if (status === 'Delivered') {
                actHtml += '<li><button class="dropdown-item convertChallanToInvoice" data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || '') + '"><i class="bx bx-receipt me-2 text-success"></i>Convert to Invoice</button></li>';
            }
        }
        if (!isDraft) {
            actHtml += '<li><hr class="dropdown-divider my-1"></li>';
            actHtml += '<li><button class="dropdown-item duplicateDeliveryChallan" data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || '') + '"><i class="bx bx-copy me-2 text-info"></i>Clone</button></li>';
        }
        if (!isTerminal) {
            if (!isDraft) { actHtml += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (!isDraft && status !== 'Partially Returned') {
                actHtml += '<li><button class="dropdown-item text-warning dc-status-update" data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || '') + '" data-status="Cancelled"><i class="bx bx-x-circle me-2"></i>Cancel</button></li>';
            }
            actHtml += '<li><button class="dropdown-item text-danger deleteDeliveryChallan" data-uid="' + (r.TransUID | 0) + '" data-num="' + _dcEsc(r.UniqueNumber || 'Draft') + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        }
        actHtml += '</ul></div></div>';

        html += '<tr class="' + rowClass + '">';
        html += '<td style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox dcCheck" type="checkbox" value="' + (r.TransUID | 0) + '"></div></td>';
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + serialNo + '</span></td>';
        html += '<td>' + numHtml + '</td>';
        html += '<td>' + amtHtml + '</td>';
        html += '<td><span class="trans-badge ' + badgeCls + '"><i class="bx ' + ico + '" style="font-size:var(--r2k-text-md);"></i> ' + _dcEsc(status) + '</span></td>';
        html += '<td><span class="' + typeBadgeCls + '" style="font-size:var(--r2k-text-xs);">' + _dcEsc(challanType) + '</span></td>';
        html += '<td class="inv-party-td">' + custHtml + '</td>';
        html += '<td class="' + dueClass + '">' + expHtml + '</td>';
        html += '<td>' + _dcFmtUpdated(r.UpdatedOn, r.UpdatedBy) + '</td>';
        html += '<td style="width:50px">' + actHtml + '</td>';
        html += '</tr>';
    });

    return html;
}

/**
 * @param {number} total
 * @param {number} page
 * @param {number} limit
 * @returns {string}
 */
function _buildDCPagination(total, page, limit) {
    if (!total || total <= limit) return '';
    var pages = Math.ceil(total / limit);
    var from  = (page - 1) * limit + 1;
    var to    = Math.min(page * limit, total);
    var pUrl  = function (p) { return 'javascript:getDeliveryChallansDetails(' + p + ')'; };
    var html  = '<div class="col-auto"><ul class="pagination pagination-sm mb-0">';
    html += page > 1
        ? '<li class="page-item"><a class="page-link" href="' + pUrl(page - 1) + '">&laquo;</a></li>'
        : '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    var start = Math.max(1, Math.min(page - 2, pages - 4));
    var end   = Math.min(pages, start + 4);
    for (var p = start; p <= end; p++) {
        html += p === page
            ? '<li class="page-item active"><span class="page-link">' + p + '</span></li>'
            : '<li class="page-item"><a class="page-link" href="' + pUrl(p) + '">' + p + '</a></li>';
    }
    html += page < pages
        ? '<li class="page-item"><a class="page-link" href="' + pUrl(page + 1) + '">&raquo;</a></li>'
        : '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    html += '</ul></div>';
    html += '<div class="col-auto"><small class="text-muted">Showing ' + from + '–' + to + ' of ' + total + '</small></div>';
    return html;
}

/**
 * @param {Object} stats  — shape: { Dispatched: {count, amount}, Delivered: {count, amount}, ... }
 * @returns {void}
 */
function _buildDCStats(stats) {
    if (!stats) return;
    var cntAll = 0, amtAll = 0;
    $.each(stats, function (_, s) { cntAll += (s.count || 0); amtAll += (s.amount || 0); });
    var cur = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
    var dec = parseInt((JwtData.GenSettings && JwtData.GenSettings.DecimalPoints) ? JwtData.GenSettings.DecimalPoints : 2);
    var fmt = function (v) { return parseFloat(v || 0).toFixed(dec); };
    $('#dcStatAllCount').text(cntAll);
    $('#dcStatAllAmount').text(cur + ' ' + fmt(amtAll));
    $('#dcStatDispatchedCount').text((stats.Dispatched  || {}).count  || 0);
    $('#dcStatDispatchedAmount').text(cur + ' ' + fmt((stats.Dispatched || {}).amount || 0));
    $('#dcStatDeliveredCount').text((stats.Delivered || {}).count || 0);
    $('#dcStatDraftCount').text((stats.Draft || {}).count || 0);
}

$(document).on('click', '.duplicateDeliveryChallan', function () {
    var uid = $(this).data('uid'), num = $(this).data('num') || '';
    Swal.fire({
        title: t('swal_clone_challan', 'Clone Challan?'),
        html : num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Clone this delivery challan?',
        icon : 'question', showCancelButton: true,
        confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
        confirmButtonText: t('btn_yes_clone', 'Yes, Clone'), cancelButtonText: t('btn_cancel', 'Cancel')
    }).then(function (r) {
        if (!r.isConfirmed) return;
        window.location.href = global_base_url + 'deliverychallan/create?fromClone=' + uid;
    });
});

