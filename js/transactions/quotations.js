// ── Quotations list — module-specific JS ─────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// -- WhatsApp link handler ---------------------------------------------------
$(document).on('click', '.inv-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// -- Register smart PDF attach alert when comm modal opens in Email tab -------
// Priority: direct CDN fetch (if PdfPath in row data) → AJAX fallback
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 101 || !recordUID) return;
    if (typeof _setupCommPdfAlert !== 'function') return;

    var rowData   = (typeof _commRowData !== 'undefined') ? _commRowData : null;
    var pdfPath   = rowData ? (rowData.pdfPath || '') : '';
    var cdnBase   = (typeof _r2CdnBase    !== 'undefined') ? (_r2CdnBase   || '') : '';
    var docNumber = rowData ? (rowData.docNumber || '') : '';
    var filename  = (docNumber || 'quotation').replace(/[^A-Za-z0-9_.\-]/g, '_') + '.pdf';

    _setupCommPdfAlert(docNumber, function (onSuccess) {
        if (pdfPath && cdnBase) {
            var cdnUrl     = cdnBase.replace(/\/+$/, '') + '/' + pdfPath.replace(/^\/+/, '');
            var controller = new AbortController();
            var cdnTimeout = setTimeout(function () { controller.abort(); }, 3000);
            fetch(cdnUrl, { signal: controller.signal })
                .then(function (r) {
                    clearTimeout(cdnTimeout);
                    if (!r.ok) throw new Error('CDN ' + r.status);
                    return r.blob();
                })
                .then(function (blob) {
                    onSuccess(new File([blob], filename, { type: 'application/pdf' }));
                })
                .catch(function () {
                    clearTimeout(cdnTimeout);
                    _quotFetchPdfAjax(recordUID, moduleUID, filename, onSuccess);
                });
        } else {
            _quotFetchPdfAjax(recordUID, moduleUID, filename, onSuccess);
        }
    });
});

/**
 * @param {number}   recordUID
 * @param {number}   moduleUID
 * @param {string}   filename
 * @param {Function} onSuccess
 * @returns {void}
 */
function _quotFetchPdfAjax(recordUID, moduleUID, filename, onSuccess) {
    $.ajax({
        url   : global_base_url + 'transactions/getTransactionPdfBase64',
        method: 'POST',
        data  : { TransUID: recordUID, ModuleUID: moduleUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Base64) { if (onSuccess) onSuccess(null); return; }
            try {
                var binary = atob(resp.Base64);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                var blob = new Blob([bytes], { type: 'application/pdf' });
                if (onSuccess) onSuccess(new File([blob], resp.Filename || filename, { type: 'application/pdf' }));
            } catch (ex) {
                if (onSuccess) onSuccess(null);
            }
        },
        error: function () { if (onSuccess) onSuccess(null); }
    });
}

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _quotSelectAllMode = false;
var _quotTotalRecords  = 0;
var _quotPageCount     = 0;
var _quotReqSeq        = 0;

/** @returns {void} */
function _quotUpdateSelectAllBanner() {
    updateTransSelectAllBanner(_quotSelectAllMode, _quotTotalRecords, _quotPageCount, 'quot', 'quotations');
}
/** @returns {void} */
function _quotClearSelectAll() {
    _quotSelectAllMode = false;
    clearTransSelectAllDom('quot');
}
/** @returns {void} */
function deleteMultipleQuotations() {
    deleteMultipleTrans(101, _quotSelectAllMode, _quotClearSelectAll, function () {
        getQuotationsDetails(PageNo, RowLimit, Filter);
    });
}

/** Status badge + icon config for quotations. */
var _quotStatusCfg = {
    'Draft':     { badge: 'trans-badge-Draft',     icon: 'bx-pencil' },
    'Pending':   { badge: 'trans-badge-Pending',   icon: 'bx-send' },
    'Accepted':  { badge: 'trans-badge-Accepted',  icon: 'bx-check-circle' },
    'Converted': { badge: 'trans-badge-Converted', icon: 'bx-transfer-alt' },
    'Cancelled': { badge: 'trans-badge-Cancelled', icon: 'bx-x-circle' },
};
var _quotTerminal = ['Cancelled', 'Converted'];

/**
 * Build an initials avatar from a party name.
 * @param {string} name
 * @returns {string}
 */
function _quotPartyInitials(name) {
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
function _quotEsc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _quotTimeAgo(dt) { return timeAgo(dt); }

/**
 * Build HTML rows from services Data array.
 * @param {Array}  rows
 * @param {number} offset  zero-based offset for serial numbers
 * @returns {string}
 */
function _buildQuotHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="9"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No quotations found</span>' +
            '<a href="' + global_base_url + 'quotations/create" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>New</a>' +
            '</div></td></tr>';
    }

    var showSerial = (typeof JwtData !== 'undefined') && JwtData.GenSettings && JwtData.GenSettings.SerialNoDisplay == 1;
    var cur        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec        = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;
    var quotModUID = 101;
    var now        = new Date();
    var html       = '';

    $.each(rows, function (i, r) {
        var serial     = offset + i + 1;
        var status     = r.Status || 'Draft';
        if (status === 'Rejected') status = 'Cancelled';
        var isDraft    = status === 'Draft';
        var isTerminal = _quotTerminal.indexOf(status) !== -1;
        var cfg        = _quotStatusCfg[status] || { badge: 'trans-badge-Draft', icon: 'bx-circle' };

        /* ── Validity / due-date display ── */
        var dueClass     = '';
        var validityHtml = '';
        var showValidity = !isDraft && !isTerminal && r.ValidityDate;
        if (showValidity) {
            var dueTs    = new Date(r.ValidityDate).getTime();
            var todayTs  = new Date(new Date().toDateString()).getTime();
            var diffDays = Math.round((dueTs - todayTs) / 86400000);
            validityHtml += '<div style="font-size:var(--r2k-text-xs);color:#6c757d;">Due: ' + _quotEsc(formatDateDisplay(r.ValidityDate)) + '</div>';
            if (diffDays < 0) {
                dueClass      = 'trans-due-overdue';
                var absDays   = Math.abs(diffDays);
                validityHtml += '<div style="font-size:var(--r2k-text-xs);color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:var(--r2k-text-sm);"></i> since ' + absDays + ' day' + (absDays !== 1 ? 's' : '') + '</div>';
                validityHtml += '<div style="margin-top:2px;"><span class="badge bg-label-danger" style="font-size:var(--r2k-text-65);">Expired</span></div>';
            } else if (diffDays === 0) {
                dueClass      = 'trans-due-soon';
                validityHtml += '<div style="font-size:var(--r2k-text-xs);color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:var(--r2k-text-sm);"></i> expires today</div>';
            } else if (diffDays <= 7) {
                dueClass      = 'trans-due-soon';
                validityHtml += '<div style="font-size:var(--r2k-text-xs);color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:var(--r2k-text-sm);"></i> in ' + diffDays + ' day' + (diffDays !== 1 ? 's' : '') + '</div>';
            } else {
                validityHtml += '<div style="font-size:var(--r2k-text-xs);color:#6c757d;margin-top:3px;"><i class="bx bx-time-five" style="font-size:var(--r2k-text-sm);"></i> in ' + diffDays + ' days</div>';
            }
        } else if (!isDraft && r.ValidityDate) {
            validityHtml = '<div style="font-size:var(--r2k-text-xs);color:#6c757d;">Due: ' + _quotEsc(formatDateDisplay(r.ValidityDate)) + '</div>';
        }
        var isOverdue = dueClass === 'trans-due-overdue';

        /* ── Party info ── */
        var partyName = r.PartyName    || '';
        var mobile    = r.MobileNumber || '';
        var cc        = r.CountryCode  || '';
        var email     = r.EmailAddress || '';
        var area      = r.PartyArea    || '';
        var waNum     = mobile ? (cc || '91').replace(/[^0-9]/g,'') + mobile.replace(/[^0-9]/g,'') : '';
        var initials  = _quotPartyInitials(partyName);

        /* ── Amount ── */
        var netAmt    = parseFloat(r.NetAmount || 0);
        var amtStr    = cur + ' ' + netAmt.toFixed(dec);

        /* ── Dates ── */
        var transDateFmt   = r.TransDate    ? formatDateDisplay(r.TransDate)    : '';
        var validityFmt    = r.ValidityDate ? formatDateDisplay(r.ValidityDate) : '';
        var updatedDateFmt = r.UpdatedOn    ? formatDateDisplay(r.UpdatedOn.substring(0, 10)) : '';
        var updatedAgo     = _quotTimeAgo(r.UpdatedOn);
        var updatedBy      = r.UpdatedBy || '—';
        var createdBy      = r.CreatedBy || '';

        /* ── Attachments ── */
        var hasAttach = parseInt(r.AttachmentCount || 0, 10) > 0;

        /* ── Row ── */
        html += '<tr class="' + (isOverdue ? 'trans-row-overdue' : '') + '">';

        /* Checkbox */
        html += '<td style="width:36px"><div class="form-check mb-0">' +
            '<input class="form-check-input table-chkbox quotationCheck" type="checkbox" value="' + parseInt(r.TransUID, 10) + '">' +
            '</div></td>';

        /* Serial */
        html += '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno" style="width:44px">' +
            '<span class="text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</span></td>';

        /* Quotation # */
        html += '<td>';
        if (isDraft || !r.UniqueNumber) {
            html += '<span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:var(--r2k-text-md);"></i>Draft</span>';
            if (transDateFmt) { html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _quotEsc(transDateFmt) + '</div>'; }
        } else {
            html += '<a href="javascript:void(0)" class="trans-doc-number viewTransaction"' +
                ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                ' data-module="' + quotModUID + '"' +
                ' data-type="quotation"' +
                ' data-number="' + _quotEsc(r.UniqueNumber) + '"' +
                ' data-date="' + _quotEsc(r.TransDate || '') + '"' +
                ' data-status="' + _quotEsc(status) + '">' +
                _quotEsc(r.UniqueNumber) + '</a>';
            html += '<div class="d-flex align-items-center gap-2 mt-1">';
            html += '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + _quotEsc(transDateFmt) + '</div>';
            if (hasAttach) {
                html += '<button type="button" class="btn btn-link p-0 transAttachBtn"' +
                    ' data-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-num="' + _quotEsc(r.UniqueNumber || '') + '"' +
                    ' data-url="/transactions/getAttachments"' +
                    ' data-module-uid="' + quotModUID + '"' +
                    ' title="' + parseInt(r.AttachmentCount, 10) + ' attachment(s)"' +
                    ' style="font-size:var(--r2k-text-82);line-height:1;color:#0d6efd;">' +
                    '<i class="bx bx-paperclip"></i></button>';
            }
            html += '</div>';
            if (createdBy) { html += '<div style="font-size:var(--r2k-text-xs);color:#bbb;">by ' + _quotEsc(createdBy) + '</div>'; }
        }
        html += '</td>';

        /* Amount */
        html += '<td>';
        if (isDraft && netAmt === 0) {
            html += '<span class="text-muted">—</span>';
        } else {
            html += '<div class="trans-amount-main">' + _quotEsc(amtStr) + '</div>';
        }
        html += '</td>';

        /* Status */
        html += '<td class="quot-col-status"><span class="trans-badge ' + cfg.badge + '">' +
            '<i class="bx ' + cfg.icon + '" style="font-size:var(--r2k-text-md);"></i> ' +
            _quotEsc(status) + '</span></td>';

        /* Customer */
        html += '<td class="inv-party-td"><div class="d-flex align-items-center gap-2">';
        html += '<div class="trans-party-avatar" style="background:var(--r2k-avatar-bg);color:var(--r2k-avatar-fg);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0;">' + _quotEsc(initials) + '</div>';
        html += '<div><div class="trans-party-name">' + _quotEsc(partyName) + '</div>';
        if (area)   { html += '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + _quotEsc(area) + '</div>'; }
        if (mobile) { html += '<div class="trans-party-mobile" style="font-size:var(--r2k-text-sm);color:#666;margin-top:1px;">' + (cc ? _quotEsc(cc) + ' ' : '') + _quotEsc(mobile) + '</div>'; }
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
                    ' data-name="' + _quotEsc(partyName) + '"' +
                    ' data-mobile="' + _quotEsc(mobile) + '"' +
                    ' data-email="' + _quotEsc(email) + '"' +
                    ' data-module-uid="' + quotModUID + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS">' +
                    '<i class="bx bx-message-dots"></i></button>';
            }
            if (email) {
                html += '<button class="comm-send-single em"' +
                    ' data-commtype="Email" data-recipienttype="Customer"' +
                    ' data-uid="' + parseInt(r.PartyUID, 10) + '"' +
                    ' data-trans-uid="' + parseInt(r.TransUID, 10) + '"' +
                    ' data-name="' + _quotEsc(partyName) + '"' +
                    ' data-mobile="' + _quotEsc(mobile) + '"' +
                    ' data-email="' + _quotEsc(email) + '"' +
                    ' data-module-uid="' + quotModUID + '"' +
                    ' data-doc-type="Quotation"' +
                    ' data-doc-number="' + _quotEsc(r.UniqueNumber || '') + '"' +
                    ' data-net-amount="' + netAmt + '"' +
                    ' data-paid-amount="' + parseFloat(r.PaidAmount || 0) + '"' +
                    ' data-doc-date="' + _quotEsc(transDateFmt) + '"' +
                    ' data-validity-date="' + _quotEsc(validityFmt) + '"' +
                    ' data-doc-status="' + _quotEsc(status) + '"' +
                    ' data-trans-token="' + _quotEsc(r.TransToken || '') + '"' +
                    ' data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email">' +
                    '<i class="bx bx-envelope"></i></button>';
            }
            html += '</div>';
        }
        html += '</td>';

        /* Valid Until */
        html += '<td class="quot-col-valid-until">';
        if (validityHtml) {
            html += validityHtml;
        } else {
            html += '<span class="text-muted">—</span>';
        }
        html += '</td>';

        /* Last Updated */
        html += '<td><div class="r2k-col-date">' + _quotEsc(updatedDateFmt) + '</div>';
        if (updatedAgo) { html += '<div class="r2k-col-date-ago">' + _quotEsc(updatedAgo) + '</div>'; }
        html += '<div class="text-muted r2k-col-date-by">by ' + _quotEsc(updatedBy) + '</div></td>';

        /* Actions */
        html += '<td style="width:50px"><div class="d-flex align-items-center justify-content-end gap-1">';
        if (!isTerminal) {
            html += '<a class="btn btn-icon btn-sm text-warning" href="/quotations/edit/' + parseInt(r.TransUID, 10) + '" title="Edit"><i class="bx bx-edit"></i></a>';
        }
        html += '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="bx bx-dots-vertical-rounded fs-5"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">';

        var hasSep = false;
        if (!isDraft) {
            html += '<li><button class="dropdown-item a4PrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + quotModUID + '"><i class="bx bx-printer me-2 text-dark"></i>Print / Download</button></li>';
            html += '<li><button class="dropdown-item downloadPdfQuotation" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + quotModUID + '" data-num="' + _quotEsc(r.UniqueNumber || '') + '"><i class="bx bx-download me-2 text-primary"></i>Download PDF</button></li>';
            html += '<li><button class="dropdown-item thermalPrintTransaction" data-uid="' + parseInt(r.TransUID, 10) + '" data-module="' + quotModUID + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';
            hasSep = true;
        }

        if (!isDraft && (mobile || email)) {
            if (hasSep) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (mobile) {
                html += '<li><a class="dropdown-item inv-wa-link" href="javascript:void(0)" data-wa-url="https://wa.me/' + waNum + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>';
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Customer" data-uid="' + parseInt(r.PartyUID, 10) + '" data-name="' + _quotEsc(partyName) + '" data-mobile="' + _quotEsc(mobile) + '" data-email="' + _quotEsc(email) + '" data-module-uid="' + quotModUID + '" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (email) {
                html += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Customer" data-uid="' + parseInt(r.PartyUID, 10) + '" data-trans-uid="' + parseInt(r.TransUID, 10) + '" data-name="' + _quotEsc(partyName) + '" data-mobile="' + _quotEsc(mobile) + '" data-email="' + _quotEsc(email) + '" data-module-uid="' + quotModUID + '" style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
            hasSep = true;
        }

        if (!isTerminal && !isDraft) {
            if (hasSep) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            if (status === 'Accepted') {
                html += '<li><button class="dropdown-item quot-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-status="Converted" data-target="Invoice" style="color:#0891b2;"><i class="bx bx-transfer-alt me-2"></i>Convert to Invoice</button></li>';
                html += '<li><button class="dropdown-item quot-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-status="Converted" data-target="SalesOrder" style="color:#7c3aed;"><i class="bx bx-transfer-alt me-2"></i>Convert to Sales Order</button></li>';
                html += '<li><hr class="dropdown-divider my-1"></li>';
            }
            html += '<li><button class="dropdown-item text-warning quot-status-update" data-uid="' + parseInt(r.TransUID, 10) + '" data-status="Cancelled" data-num="' + _quotEsc(r.UniqueNumber || '') + '"><i class="bx bx-x-circle me-2"></i>Cancel Quotation</button></li>';
            hasSep = true;
        }

        if (!isTerminal) {
            if (hasSep) { html += '<li><hr class="dropdown-divider my-1"></li>'; }
            html += '<li><button class="dropdown-item text-danger deleteQuotation" data-uid="' + parseInt(r.TransUID, 10) + '" data-num="' + _quotEsc(r.UniqueNumber || 'Draft') + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        }

        html += '</ul></div></div></td>';
        html += '</tr>';
    });

    return html;
}

/**
 * Load a page of quotations from the services endpoint.
 * @param {number}   [pageNo]
 * @param {number}   [rowLimit]
 * @param {Object}   [filter]
 * @param {Function} [afterLoad]
 * @returns {void}
 */
function getQuotationsDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter   || Filter;

    var reqSeq = ++_quotReqSeq;

    $(ModuleTable + ' tbody').html(
        '<tr><td colspan="9" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>' +
        '</td></tr>'
    );
    $('#quotPagination').addClass('d-none').empty();
    $('.quot-status-tab .trans-tab-count').text('').addClass('d-none');

    $.ajax({
        url    : global_services_url + 'api/transactions/getPageDetails/101/' + pageNo,
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
            if (reqSeq !== _quotReqSeq) return;
            ajaxLoading(1);
            if (resp.Error) {
                $(ModuleTable + ' tbody').html('');
                $('#quotPagination').html('<div class="alert alert-danger m-2"><strong>' + (resp.Message || 'Failed to load quotations.') + '</strong></div>').removeClass('d-none');
                return;
            }

            var rows   = resp.Data       || [];
            var total  = parseInt(resp.TotalCount || 0, 10);
            var pg     = parseInt(resp.PageNo     || pageNo,   10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;

            $(ModuleTable + ' tbody').html(_buildQuotHtml(rows, offset));

            var $activeBadge = $('.quot-status-tab.active .trans-tab-count');
            if (total > 0) { $activeBadge.text(total).removeClass('d-none'); }
            else           { $activeBadge.text('').addClass('d-none'); }

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#quotPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#quotPagination').addClass('d-none').empty(); }

            if (resp.SummaryStats) { updateQuotStatCards(resp.SummaryStats); }

            _quotTotalRecords = total;
            _quotPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _quotUpdateSelectAllBanner();
            initTooltips();
            $(window).trigger('scroll');
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error: function () {
            if (reqSeq !== _quotReqSeq) return;
            ajaxLoading(1);
            $('#quotPagination').html('<div class="alert alert-danger m-2">Failed to load quotations.</div>').removeClass('d-none');
        },
    });
}

/**
 * Update stat-strip cards from a SummaryStats response object.
 * @param {Object} stats
 * @returns {void}
 */
function updateQuotStatCards(stats) {
    if (!stats || !Object.keys(stats).length) return;
    var cur = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (JwtData.GenSettings.CurrenySymbol || '₹') : '₹';
    var dec = (typeof JwtData !== 'undefined') && JwtData.GenSettings ? (parseInt(JwtData.GenSettings.DecimalPoints, 10) || 2) : 2;

    /** @param {string} key @returns {number} */
    function cnt(key) { return stats[key] ? (stats[key].count  || 0) : 0; }
    /** @param {string} key @returns {number} */
    function amt(key) { return stats[key] ? (stats[key].amount || 0) : 0; }

    function fmt(v) { return cur + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }

    var excludeKeys = ['Draft', 'Cancelled', 'Rejected', 'Expired'];
    var cntAll = 0, amtAll = 0;
    $.each(stats, function (k, v) {
        if (excludeKeys.indexOf(k) === -1) { cntAll += (v.count || 0); amtAll += (v.amount || 0); }
    });

    $('[data-stat-filter="All"]       .apex-stat-count').text(cntAll.toLocaleString('en-IN'));
    $('[data-stat-filter="All"]       .apex-stat-amount').text(fmt(amtAll));
    $('[data-stat-filter="Open"]      .apex-stat-count').text(cnt('Pending').toLocaleString('en-IN'));
    $('[data-stat-filter="Open"]      .apex-stat-amount').text(fmt(amt('Pending')));
    $('[data-stat-filter="Accepted"]  .apex-stat-count').text(cnt('Accepted').toLocaleString('en-IN'));
    $('[data-stat-filter="Accepted"]  .apex-stat-amount').text(fmt(amt('Accepted')));
    $('[data-stat-filter="Converted"] .apex-stat-count').text(cnt('Converted').toLocaleString('en-IN'));
    $('[data-stat-filter="Converted"] .apex-stat-amount').text(fmt(amt('Converted')));
    $('[data-stat-filter="Draft"]     .apex-stat-count').text(cnt('Draft').toLocaleString('en-IN'));
}

