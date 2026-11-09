// ── Payments Page — services migration ───────────────────────────────────────

/* ── Module-level helpers ─────────────────────────────────────────────────── */

/**
 * @param {Object} extra
 * @returns {Object}
 */
function _pmtSvcParams(extra) {
    return $.extend({
        orgId   : parseInt(JwtData.Org.OrgUID,    10),
        userId  : parseInt(JwtData.User.UserUID,  10),
        branchId: parseInt(JwtData.Org.BranchUID, 10) || 1,
    }, extra || {});
}

/**
 * @param {*} val
 * @returns {string}
 */
function _pmtEsc(val) {
    return $('<span>').text(val == null ? '' : String(val)).html();
}

/**
 * @param {string|number} val
 * @returns {string}
 */
function _pmtSmartDec(val) {
    var n   = parseFloat(val) || 0;
    var dec = (typeof decimalPlaces !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;
    return n.toFixed(dec).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '') || '0';
}

/**
 * Returns "21 Aug 2026, 10:30 AM"
 * @param {string} raw
 * @returns {string}
 */
function _pmtFmtDate(raw) {
    if (!raw) return '—';
    var d = new Date(raw);
    if (isNaN(d)) return raw;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var day = d.getDate(), mon = months[d.getMonth()], yr = d.getFullYear();
    var h = d.getHours(), m = d.getMinutes();
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return day + ' ' + mon + ' ' + yr + ', '
        + String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + ampm;
}

/**
 * @param {string} raw
 * @returns {string}
 */
function _pmtFmtAgo(raw) { return timeAgo(raw); }

/**
 * @param {string} name
 * @returns {string}
 */
function _pmtPartyAvatar(name) {
    var parts    = (name || '').trim().split(/\s+/);
    var initials = (parts[0] ? parts[0][0] : '') + (parts.length > 1 ? (parts[parts.length - 1][0] || '') : '');
    initials     = initials.toUpperCase() || '?';
    var palette  = ['#4f46e5','#0891b2','#16a34a','#dc2626','#9333ea','#ea580c','#0d9488','#b45309'];
    var color    = palette[(name || '').charCodeAt(0) % palette.length] || '#4f46e5';
    return '<div style="width:34px;height:34px;border-radius:50%;background:' + color
        + ';color:#fff;display:flex;align-items:center;justify-content:center;'
        + 'font-size:.7rem;font-weight:700;flex-shrink:0;">' + _pmtEsc(initials) + '</div>';
}

var _PMT_MODE_COLORS = {
    'cash'       : { bg: '#e8f5e9', color: '#2e7d32' },
    'upi'        : { bg: '#ede7f6', color: '#4527a0' },
    'card'       : { bg: '#e3f2fd', color: '#1565c0' },
    'net banking': { bg: '#fff8e1', color: '#f57f17' },
    'cheque'     : { bg: '#fce4ec', color: '#880e4f' },
    'emi'        : { bg: '#e0f7fa', color: '#00695c' },
    'tds'        : { bg: '#f3e5f5', color: '#6a1b9a' },
    'debit note' : { bg: '#fff3e0', color: '#e65100' },
};

/**
 * @param {string} name
 * @returns {string}
 */
function _pmtModeBadge(name) {
    var key = (name || '').toLowerCase().trim();
    var mc  = _PMT_MODE_COLORS[key] || { bg: '#f0f0f0', color: '#555' };
    return '<span class="pmt-mode-badge" style="background:' + mc.bg + ';color:' + mc.color + ';">'
        + _pmtEsc(name || '—') + '</span>';
}

/**
 * Build table row HTML from services JSON data.
 * @param {Array} rows
 * @returns {string}
 */
function _generatePaymentsHtml(rows) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="8">'
            + '<div class="d-flex flex-column align-items-center justify-content-center py-5" style="gap:10px;">'
            + '<div style="width:56px;height:56px;border-radius:50%;background:#f0f4ff;display:flex;align-items:center;justify-content:center;">'
            + '<i class="bx bx-wallet-alt" style="font-size:1.6rem;color:#4f46e5;"></i></div>'
            + '<div class="fw-semibold text-dark" style="font-size:.92rem;">No Payments Found</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-md);">Try adjusting your filters or date range.</div>'
            + '</div></td></tr>';
    }

    var cur     = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
    var orgName = window._pmtOrgName   || '';
    var orgMob  = window._pmtOrgMobile || '';
    var appUrl  = window._pmtAppUrl    || '';
    var html    = '';

    rows.forEach(function (row) {
        var isIn      = (row.PaymentDirection || 'In') === 'In';
        var typeBg    = isIn ? '#dcfce7' : '#fee2e2';
        var typeColor = isIn ? '#15803d' : '#dc2626';
        var typeLabel = isIn ? 'IN'      : 'OUT';
        var typeIcon  = isIn ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt';
        var amtClass  = isIn ? 'text-success' : 'text-danger';

        // Mobile + WA
        var countryCode = (row.PartyCountryCode || '').trim();
        var mobileNum   = (row.PartyMobile      || '').trim();
        var fullMobile  = '';
        if (mobileNum) {
            fullMobile = (countryCode && countryCode !== '+91' && countryCode !== '91')
                ? (countryCode + ' ' + mobileNum) : ('+91 ' + mobileNum);
        }
        var partyEmail = (row.PartyEmail || '').trim();
        var waNum      = fullMobile ? fullMobile.replace(/[^0-9]/g, '') : '';

        var partyIsCust      = (row.PartyType === 'C');
        var hasParty         = !!row.PartyType;
        var partyTypeBg      = partyIsCust ? '#dbeafe' : '#fef9c3';
        var partyTypeClr     = partyIsCust ? '#1d4ed8' : '#854d0e';
        var partyTypeTxt     = partyIsCust ? 'Customer' : 'Vendor';
        var partyRecipType   = partyIsCust ? 'Customer' : 'Vendor';
        var transModule      = partyIsCust ? 103 : 105;
        var transType        = partyIsCust ? 'invoice' : 'purchase';

        // Receipt
        var receiptToken = (row.ReceiptToken || '').trim();
        var receiptUrl   = receiptToken ? (appUrl + '/receipt/' + receiptToken) : '';

        // WhatsApp message
        var amtFormatted = cur + ' ' + _pmtSmartDec(row.Amount);
        var payStatus    = (parseInt(row.IsFullyPaid || 0, 10) === 1) ? 'Paid' : (isIn ? 'Partially Paid' : 'Partial');
        var receiptNum   = row.PaymentUniqueNumber || '';
        var shareMsg     = 'Hello *' + (row.PartyName || '') + '*,\n\n'
            + (isIn ? 'Thanks for your payment!\n\n' : 'Payment confirmation:\n\n')
            + (receiptNum ? '*Ref: ' + receiptNum + '*\n' : '')
            + '*Amount: ' + amtFormatted + '*\n'
            + '*Status: ' + payStatus + '*\n'
            + (receiptUrl ? '*Link:* ' + receiptUrl + '\n' : '')
            + '\nThanks,\n*' + orgName + '*'
            + (orgMob ? '\n*' + orgMob + '*' : '');
        var waUrl = waNum ? ('https://wa.me/' + waNum + '?text=' + encodeURIComponent(shareMsg)) : '';

        // Amount badges
        var oaSrc        = !!row.IsOnAccount;
        var oaAppliedUID = parseInt(row.OnAccountAppliedTransUID  || 0, 10);
        var oaSrcPmtUID  = parseInt(row.OnAccountSourcePaymentUID || 0, 10);
        var isFromOA     = oaSrcPmtUID > 0;
        var isCN         = parseInt(row.IsTransferredToCreditNote || 0, 10) === 1;
        var isOA         = oaSrc;

        var amtBadge = '';
        if (oaSrc && oaAppliedUID > 0) {
            var appliedNum = _pmtEsc(row.OnAccountAppliedTransNum || '');
            amtBadge = '<span title="Applied to ' + appliedNum + '" style="font-size:var(--r2k-text-65);font-weight:600;padding:1px 6px;border-radius:8px;background:#d1e7dd;color:#0a3622;border:1px solid #a3cfbb;cursor:default;">Applied' + (appliedNum ? ' &#8594; ' + appliedNum : '') + '</span>';
        } else if (oaSrc) {
            amtBadge = '<span style="font-size:var(--r2k-text-65);font-weight:600;padding:1px 6px;border-radius:8px;background:#fff3cd;color:#856404;border:1px solid #ffc107;">On Account</span>';
        } else if (isFromOA) {
            var srcNum = _pmtEsc(row.OnAccountSourcePaymentNum || '');
            amtBadge = '<span title="Credit from ' + srcNum + '" style="font-size:var(--r2k-text-65);font-weight:600;padding:1px 6px;border-radius:8px;background:#cfe2ff;color:#084298;border:1px solid #9ec5fe;cursor:default;">On-Account' + (srcNum ? ' &#8592; ' + srcNum : '') + '</span>';
        } else if ((row.SourceType || '') === 'CreditNote') {
            amtBadge = '<span style="font-size:var(--r2k-text-65);font-weight:600;padding:1px 6px;border-radius:8px;background:#f3e8ff;color:#6b21a8;border:1px solid #d8b4fe;">Credit Note</span>';
        } else if (parseFloat(row.ExcessAmount || 0) > 0) {
            amtBadge = '<div style="font-size:var(--r2k-text-70);color:#f59e0b;">Excess: ' + _pmtEsc(cur) + ' ' + _pmtSmartDec(row.ExcessAmount) + '</div>';
        }

        // Linked doc
        var linkedDoc;
        if (row.TransNumber) {
            linkedDoc = '<a href="javascript:void(0);" class="trans-doc-number viewTransaction"'
                + ' data-uid="' + parseInt(row.TransUID || 0, 10) + '"'
                + ' data-module="' + transModule + '"'
                + ' data-type="' + transType + '"'
                + ' data-number="' + _pmtEsc(row.TransNumber) + '"'
                + ' data-date="" data-status="">'
                + _pmtEsc(row.TransNumber) + '</a>'
                + '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + (partyIsCust ? 'Invoice' : 'Purchase') + '</div>';
        } else {
            linkedDoc = '<span class="text-muted">—</span>';
        }

        // Email context for comm buttons
        var emailCtx = JSON.stringify({
            PartyName    : row.PartyName || '',
            DocNumber    : row.TransNumber || '',
            Amount       : parseFloat(row.Amount) || 0,
            ReceiptNumber: row.PaymentUniqueNumber || '',
            PaymentMode  : row.PaymentTypeName || '',
            PaymentStatus: payStatus,
            ReceiptLink  : receiptUrl,
        });

        // Contact hover icons
        var contactIcons = '';
        if (fullMobile || partyEmail) {
            contactIcons = '<div class="inv-contact-icons">';
            if (fullMobile && waUrl) {
                contactIcons += '<a href="javascript:void(0)" class="wa inv-wa-link" title="WhatsApp" data-wa-url="' + _pmtEsc(waUrl) + '"><i class="bx bxl-whatsapp"></i></a>';
            }
            if (fullMobile) {
                contactIcons += '<button class="comm-send-single sms" title="Send SMS" data-commtype="SMS"'
                    + ' data-recipienttype="' + partyRecipType + '" data-uid="' + parseInt(row.PartyUID || 0, 10) + '"'
                    + ' data-name="' + _pmtEsc(row.PartyName || '') + '" data-mobile="' + _pmtEsc(mobileNum) + '"'
                    + ' data-email="' + _pmtEsc(partyEmail) + '" data-module-uid="110"><i class="bx bx-message-dots"></i></button>';
            }
            if (partyEmail) {
                contactIcons += '<button class="comm-send-single em" title="Send Email" data-commtype="Email"'
                    + ' data-recipienttype="' + partyRecipType + '" data-uid="' + parseInt(row.PartyUID || 0, 10) + '"'
                    + ' data-name="' + _pmtEsc(row.PartyName || '') + '" data-mobile="' + _pmtEsc(mobileNum) + '"'
                    + ' data-email="' + _pmtEsc(partyEmail) + '" data-module-uid="110"'
                    + ' data-context=\'' + _pmtEsc(emailCtx) + '\'><i class="bx bx-envelope"></i></button>';
            }
            contactIcons += '</div>';
        }

        // Action menu
        var uid = parseInt(row.PaymentUID, 10);
        var actMenu = '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
            + '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">'
            + '<li><button class="dropdown-item pmtA4Print" data-payment-uid="' + uid + '"><i class="bx bx-printer me-2 text-dark"></i>Print / Download</button></li>'
            + '<li><button class="dropdown-item pmtDownloadPdf" data-payment-uid="' + uid + '" data-num="' + _pmtEsc(row.PaymentUniqueNumber || '') + '"><i class="bx bx-download me-2 text-primary"></i>Download PDF</button></li>'
            + '<li><button class="dropdown-item pmtThermalPrint" data-payment-uid="' + uid + '"><i class="bx bx-receipt me-2 text-dark"></i>Thermal Print</button></li>';

        if (fullMobile || partyEmail) {
            actMenu += '<li><hr class="dropdown-divider my-1"></li>';
            if (fullMobile && waUrl) {
                actMenu += '<li><a class="dropdown-item pmt-wa-link" href="javascript:void(0)" data-wa-url="' + _pmtEsc(waUrl) + '" style="color:#25d366;"><i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp</a></li>'
                    + '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="' + partyRecipType + '" data-uid="' + parseInt(row.PartyUID || 0, 10) + '" data-name="' + _pmtEsc(row.PartyName || '') + '" data-mobile="' + _pmtEsc(mobileNum) + '" data-email="' + _pmtEsc(partyEmail) + '" data-module-uid="110" style="color:#0097a7;"><i class="bx bx-message-dots me-2"></i>Send SMS</button></li>';
            }
            if (partyEmail) {
                actMenu += '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="' + partyRecipType + '" data-uid="' + parseInt(row.PartyUID || 0, 10) + '" data-name="' + _pmtEsc(row.PartyName || '') + '" data-mobile="' + _pmtEsc(mobileNum) + '" data-email="' + _pmtEsc(partyEmail) + '" data-module-uid="110" data-context=\'' + _pmtEsc(emailCtx) + '\' style="color:#1565c0;"><i class="bx bx-envelope me-2"></i>Send Email</button></li>';
            }
        }

        actMenu += '<li><hr class="dropdown-divider my-1"></li>';
        if (isIn) {
            actMenu += '<li><button class="dropdown-item cancelPayment text-warning" data-payment-uid="' + uid + '" data-is-cn="' + (isCN ? '1':'0') + '" data-is-oa="' + (isOA ? '1':'0') + '"><i class="bx bx-x-circle me-2"></i>Cancel</button></li>'
                + '<li><button class="dropdown-item deletePayment text-danger" data-payment-uid="' + uid + '" data-is-cn="' + (isCN ? '1':'0') + '" data-is-oa="' + (isOA ? '1':'0') + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        } else {
            actMenu += '<li><button class="dropdown-item cancelPaymentOut text-warning" data-payment-uid="' + uid + '" data-num="' + _pmtEsc(row.PaymentUniqueNumber || '') + '" data-amount="' + _pmtEsc(_pmtSmartDec(row.Amount)) + '" data-is-cn="' + (isCN ? '1':'0') + '" data-is-oa="' + (isOA ? '1':'0') + '"><i class="bx bx-x-circle me-2"></i>Cancel</button></li>'
                + '<li><button class="dropdown-item deletePaymentOut text-danger" data-payment-uid="' + uid + '" data-amount="' + _pmtEsc(_pmtSmartDec(row.Amount)) + '" data-is-cn="' + (isCN ? '1':'0') + '" data-is-oa="' + (isOA ? '1':'0') + '"><i class="bx bx-trash me-2"></i>Delete</button></li>';
        }
        actMenu += '</ul></div>';

        var dateFormatted = _pmtFmtDate(row.CreatedOn);
        var dateAgo       = _pmtFmtAgo(row.CreatedOn);

        html += '<tr class="pmt-row"'
            + ' data-uid="'            + uid + '"'
            + ' data-direction="'      + _pmtEsc(row.PaymentDirection     || 'In') + '"'
            + ' data-unique-number="'  + _pmtEsc(row.PaymentUniqueNumber  || '')   + '"'
            + ' data-trans-uid="'      + parseInt(row.TransUID  || 0, 10)          + '"'
            + ' data-trans-module="'   + transModule                               + '"'
            + ' data-trans-type="'     + transType                                 + '"'
            + ' data-trans-number="'   + _pmtEsc(row.TransNumber          || '')   + '"'
            + ' data-amount="'         + _pmtEsc(_pmtSmartDec(row.Amount))         + '"'
            + ' data-raw-amount="'     + (parseFloat(row.Amount) || 0)             + '"'
            + ' data-payment-date="'   + _pmtEsc(row.CreatedOn            || '')   + '"'
            + ' data-payment-type="'   + _pmtEsc(row.PaymentTypeName      || '')   + '"'
            + ' data-is-cash="'        + (parseInt(row.IsCash || 0, 10) ? 1 : 0)   + '"'
            + ' data-party-uid="'      + parseInt(row.PartyUID || 0, 10)           + '"'
            + ' data-party-type="'     + _pmtEsc(row.PartyType            || 'C')  + '"'
            + ' data-party-name="'     + _pmtEsc(row.PartyName            || '')   + '"'
            + ' data-party-mobile="'   + _pmtEsc(fullMobile)                       + '"'
            + ' data-party-email="'    + _pmtEsc(partyEmail)                       + '"'
            + ' data-reference="'      + _pmtEsc(row.ReferenceNo          || '')   + '"'
            + ' data-created-by="'     + _pmtEsc(row.CreatedByName        || '')   + '"'
            + ' data-notes="'          + _pmtEsc(row.Notes                || '')   + '"'
            + ' data-bank-name="'      + _pmtEsc(row.BankName             || '')   + '"'
            + ' data-account-name="'   + _pmtEsc(row.AccountName          || '')   + '"'
            + ' data-account-number="' + _pmtEsc(row.AccountNumber        || '')   + '"'
            + ' data-ifsc="'           + _pmtEsc(row.IFSC                 || '')   + '"'
            + ' data-branch="'         + _pmtEsc(row.BranchName           || '')   + '">'

            // Date / Ref No
            + '<td class="ps-3">'
            + (row.PaymentUniqueNumber
                ? '<a href="javascript:void(0);" class="trans-doc-number viewPaymentDetail" style="font-size:var(--r2k-text-82);">' + _pmtEsc(row.PaymentUniqueNumber) + '</a>'
                : '<span class="text-muted" style="font-size:var(--r2k-text-md);">—</span>')
            + '<div class="text-muted" style="font-size:var(--r2k-text-xs);">' + dateFormatted + '</div>'
            + '</td>'

            // Type badge
            + '<td><span style="display:inline-flex;align-items:center;gap:2px;font-size:var(--r2k-text-xs);font-weight:700;padding:3px 7px;border-radius:10px;background:' + typeBg + ';color:' + typeColor + ';">'
            + '<i class="bx ' + typeIcon + '" style="font-size:var(--r2k-text-md);"></i> ' + typeLabel + '</span></td>'

            // Amount
            + '<td>'
            + '<div class="fw-semibold ' + amtClass + '" style="font-size:var(--r2k-text-88);">' + _pmtEsc(cur) + ' ' + _pmtSmartDec(row.Amount) + '</div>'
            + amtBadge
            + (row.ReferenceNo ? '<div class="text-muted" style="font-size:var(--r2k-text-xs);font-family:monospace;">' + _pmtEsc(row.ReferenceNo) + '</div>' : '')
            + '</td>'

            // Mode + Bank
            + '<td>'
            + _pmtModeBadge(row.PaymentTypeName)
            + ((!parseInt(row.IsCash || 0, 10) && row.BankName)
                ? '<div style="font-size:.76rem;font-weight:500;color:#444;margin-top:3px;">' + _pmtEsc(row.BankName) + '</div>'
                  + (row.AccountNumber ? '<div class="text-muted" style="font-size:var(--r2k-text-xs);font-family:monospace;">' + _pmtEsc(row.AccountNumber) + '</div>' : '')
                : '')
            + '</td>'

            // Party
            + '<td class="pmt-party-td">'
            + '<div class="d-flex align-items-center gap-2">'
            + _pmtPartyAvatar(row.PartyName || '')
            + '<div>'
            + '<div style="font-size:var(--r2k-text-82);font-weight:600;">' + _pmtEsc(row.PartyName || '—') + '</div>'
            + (hasParty ? '<span style="font-size:.62rem;font-weight:600;padding:1px 5px;border-radius:6px;background:' + partyTypeBg + ';color:' + partyTypeClr + ';">' + partyTypeTxt + '</span>' : '')
            + (row.PartyArea ? '<div style="font-size:var(--r2k-text-xs);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-70);"></i> ' + _pmtEsc(row.PartyArea) + '</div>' : '')
            + (fullMobile ? '<div style="font-size:var(--r2k-text-xs);color:#666;">' + _pmtEsc(fullMobile) + '</div>' : '')
            + '</div>'
            + '</div>'
            + contactIcons
            + '</td>'

            // Linked Doc
            + '<td>' + linkedDoc + '</td>'

            // Created By
            + '<td>'
            + '<div style="font-size:var(--r2k-text-82);font-weight:500;">' + _pmtEsc(row.CreatedByName || '—') + '</div>'
            + (dateAgo ? '<div style="font-size:var(--r2k-text-xs);color:#4f46e5;font-weight:500;">' + dateAgo + '</div>' : '')
            + '</td>'

            // Actions
            + '<td class="text-end pe-3">' + actMenu + '</td>'
            + '</tr>';
    });

    return html;
}

/* ── PaymentsPage class ───────────────────────────────────────────────────── */
var PaymentsPage = (function () {
    'use strict';

    /**
     * @param {object} cfg
     */
    function PaymentsPage(cfg) {
        this._sym             = cfg.sym             || '₹';
        this._dec             = cfg.dec             || 2;
        this._limit           = cfg.limit           || 10;
        this._filter          = {};
        this._pageNo          = 1;
        this._dir             = 'All';
        this._showStats       = cfg.showStats !== false;
        this._payModeFilter   = cfg.payModeFilter   || null;
        this._createdByFilter = cfg.createdByFilter || null;
        this._docTypeFilter   = cfg.docTypeFilter   || null;
        this._partyTypeFilter = cfg.partyTypeFilter || null;

        if (cfg.initStats) {
            this._renderStats(cfg.initStats);
        }
    }

    /**
     * @param {number} v
     * @returns {string}
     */
    PaymentsPage.prototype.fmt = function (v) {
        return this._sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', {
            minimumFractionDigits: this._dec,
            maximumFractionDigits: this._dec
        });
    };

    /**
     * @returns {Object}
     */
    PaymentsPage.prototype.getFilter = function () {
        var f = $.extend(
            {},
            this._filter,
            this._payModeFilter   ? this._payModeFilter.getState()   : {},
            this._createdByFilter ? this._createdByFilter.getState() : {},
            this._docTypeFilter   ? this._docTypeFilter.getState()   : {},
            this._partyTypeFilter ? this._partyTypeFilter.getState() : {}
        );
        if (this._dir !== 'All') {
            f.PaymentDirection = this._dir;
        }
        return f;
    };

    /**
     * @param {string} dir
     * @returns {void}
     */
    PaymentsPage.prototype.setDir = function (dir) {
        this._dir    = dir;
        this._pageNo = 1;
        this.loadData(1);
    };

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    PaymentsPage.prototype.loadData = function (pageNo) {
        pageNo       = pageNo || this._pageNo;
        this._pageNo = pageNo;

        var self = this;

        $('#allPmtPagination').empty().addClass('d-none');
        $('#allPaymentsTableBody').html(
            '<tr><td colspan="8" class="text-center py-4">'
            + '<span class="spinner-border spinner-border-sm text-primary me-2"></span>'
            + 'Loading…</td></tr>'
        );

        $.ajax({
            url    : global_services_url + 'api/payments/getPaymentsPageDetails',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _pmtSvcParams({
                RowLimit: self._limit,
                PageNo  : pageNo,
                Filter  : JSON.stringify(self.getFilter()),
            }),
            success: function (resp) {
                if (resp.Error) {
                    $('#allPaymentsTableBody').html(
                        '<tr><td colspan="8" class="text-center py-4 text-danger">'
                        + (resp.Message || 'Error loading data') + '</td></tr>'
                    );
                    return;
                }
                $('#allPaymentsTableBody').html(_generatePaymentsHtml(resp.Data));
                var pagHtml = generatePagination(resp.TotalCount, parseInt(resp.PageNo, 10), parseInt(resp.RowLimit, 10));
                if (pagHtml) {
                    $('#allPmtPagination').html(pagHtml).removeClass('d-none');
                } else {
                    $('#allPmtPagination').empty().addClass('d-none');
                }
                if (typeof initTooltips === 'function') initTooltips();
            },
            error: function () {
                $('#allPaymentsTableBody').html(
                    '<tr><td colspan="8" class="text-center py-4 text-danger">Request failed.</td></tr>'
                );
            }
        });

        if (self._showStats) { self.loadStats(self.getFilter()); }
    };

    /**
     * @param {Object} f
     * @returns {void}
     */
    PaymentsPage.prototype.loadStats = function (f) {
        var self = this;
        f = f || self.getFilter();
        $.ajax({
            url    : global_services_url + 'api/payments/getStats',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _pmtSvcParams({ Filter: JSON.stringify(f) }),
            success: function (resp) {
                if (!resp.Error && resp.Stats) {
                    self._renderStats(resp.Stats);
                }
            }
        });
    };

    /**
     * @param {Object} stats
     * @returns {void}
     */
    PaymentsPage.prototype._renderStats = function (stats) {
        var ci = parseFloat(stats.CashIn  || 0);
        var co = parseFloat(stats.CashOut || 0);
        var bi = parseFloat(stats.BankIn  || 0);
        var bo = parseFloat(stats.BankOut || 0);

        $('#statCashBalance').text(this.fmt(ci - co));
        $('#statBankBalance').text(this.fmt(bi - bo));
        $('#statNetBalance').text(this.fmt((ci + bi) - (co + bo)));
        $('#statCashIn').text(this.fmt(ci));
        $('#statBankIn').text(this.fmt(bi));
        $('#statTotalIn').text(this.fmt(ci + bi));
        $('#statCashOut').text(this.fmt(co));
        $('#statBankOut').text(this.fmt(bo));
        $('#statTotalOut').text(this.fmt(co + bo));
    };

    /**
     * @returns {void}
     */
    PaymentsPage.prototype.clearFilters = function () {
        this._filter  = {};
        this._dir     = 'All';
        this._pageNo  = 1;
        if (this._payModeFilter)   { this._payModeFilter.reset(); }
        if (this._createdByFilter) { this._createdByFilter.reset(); }
        if (this._docTypeFilter)   { this._docTypeFilter.reset(); }
        if (this._partyTypeFilter) { this._partyTypeFilter.reset(); }
    };

    return PaymentsPage;

}());
