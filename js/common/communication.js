/**
 * communication.js -- shared Send SMS / Send Email modal logic
 */

var _commSmsQuill        = null;
var _commEmailQuill      = null;
var _commDropzone        = null;
var _commMobiles         = [];
var _commEmails          = [];
var _commTpl             = { rawSubject: '', rawBody: '', resolvedSubject: '', resolvedBody: '', showingRaw: false };
var _commPendingTpl      = null;
var _commPdfAutoAttached = false;
var _commRowData         = null;   // row data attributes from the clicked email button

function _initCommQuill() {
    if (!_commSmsQuill && document.getElementById('CommSmsEditor')) {
        _commSmsQuill = new Quill('#CommSmsEditor', {
            theme  : 'snow',
            placeholder: 'Type your SMS message here...',
            modules: { toolbar: [['bold', 'italic'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] }
        });
        _commSmsQuill.on('text-change', function () {
            var text  = _commSmsQuill.getText().trim();
            var len   = text.length;
            var parts = len === 0 ? 1 : Math.ceil(len / 160);
            $('#CommSmsMessage').val(text);
            $('#CommSmsCharCount').text(len);
            $('#CommSmsParts').text(parts);
            $('#CommSmsPartsS').text(parts > 1 ? 's' : '');
        });
    }
    if (!_commEmailQuill && document.getElementById('CommEmailEditor')) {
        _commEmailQuill = new Quill('#CommEmailEditor', {
            theme  : 'snow',
            placeholder: 'Type your email message here...',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ color: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });
        _commEmailQuill.on('text-change', function () {
            var html = _commEmailQuill.root.innerHTML;
            $('#CommEmailMessage').val(html);
            if (!_commTpl.showingRaw) { _commTpl.resolvedBody = html; }
        });

        if (_commPendingTpl) {
            _applyCommTemplate(_commPendingTpl);
            _commPendingTpl = null;
        }
    }
}

function _initCommDropzone() {
    if (_commDropzone) return;
    var el = document.getElementById('CommAttachDropzone');
    if (!el || typeof Dropzone === 'undefined') return;

    Dropzone.autoDiscover = false;

    var previewTemplate =
        '<div class="dz-preview dz-file-preview">' +
            '<div class="dz-thumbnail">' +
                '<img data-dz-thumbnail />' +
                '<span class="dz-nopreview">No Preview</span>' +
            '</div>' +
            '<div class="dz-filename"><span data-dz-name></span></div>' +
            '<div class="dz-size"><span data-dz-size></span></div>' +
            '<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>' +
            '<div class="dz-error-message"><span data-dz-errormessage></span></div>' +
            '<a class="dz-remove" href="javascript:undefined;" data-dz-remove>Remove</a>' +
        '</div>';

    _commDropzone = new Dropzone(el, {
        url              : '#',
        autoProcessQueue : false,
        maxFiles         : 3,
        maxFilesize      : 3,
        addRemoveLinks   : false,
        acceptedFiles    : '.pdf,.jpg,.jpeg,.png',
        parallelUploads  : 3,
        previewTemplate  : previewTemplate,
        init: function () {
            this.on('addedfile', function (file) {
                if (this.files.length > 3) {
                    this.removeFile(file);
                    Swal.fire({ icon: 'warning', text: 'Maximum 3 attachments allowed.' });
                }
            });
            this.on('error', function (file) {
                if (file.size > 3 * 1024 * 1024) {
                    this.removeFile(file);
                    Swal.fire({ icon: 'warning', text: 'Each file must be 3 MB or smaller.' });
                }
            });
            this.on('maxfilesexceeded', function (file) {
                this.removeFile(file);
                Swal.fire({ icon: 'warning', text: 'Maximum 3 attachments allowed.' });
            });
        }
    });
}

function _applyCommTemplate(resp) {
    _commTpl.rawSubject      = resp.RawSubject || '';
    _commTpl.rawBody         = resp.RawBody    || '';
    _commTpl.resolvedSubject = resp.Subject    || '';
    _commTpl.resolvedBody    = resp.Body       || '';
    _commTpl.showingRaw      = false;

    $('#CommEmailSubject').val(_commTpl.resolvedSubject);

    if (_commEmailQuill) {
        _commEmailQuill.root.innerHTML = _commTpl.resolvedBody || '';
        $('#CommEmailMessage').val(_commTpl.resolvedBody || '');
    }

    if (_commTpl.rawSubject !== _commTpl.resolvedSubject || _commTpl.rawBody !== _commTpl.resolvedBody) {
        $('#CommTokenToggleBtn')
            .removeClass('d-none btn-outline-warning')
            .addClass('btn-outline-secondary');
        $('#CommTokenToggleLabel').text('Show Tokens');
    }
}

function sendSMS(options) {
    openCommModal('SMS', options.recipientType || 'Vendor',
        [options.uid], [options.name], [options.mobile || ''], [options.email || ''],
        { moduleUID: options.moduleUID || 0, recordUID: options.recordUID || 0 }
    );
}

function sendEmail(options) {
    openCommModal('Email', options.recipientType || 'Vendor',
        [options.uid], [options.name], [options.mobile || ''], [options.email || ''],
        { moduleUID: options.moduleUID || 0, recordUID: options.recordUID || 0 }
    );
}

function openCommModal(commType, recipientType, uids, names, mobiles, emails, opts) {
    opts = opts || {};

    _commRowData = opts.rowData || null;
    _commMobiles = mobiles || [];
    _commEmails  = emails  || [];

    $('#CommRecipientType').val(recipientType);
    $('#CommRecipientUIDs').val(JSON.stringify(uids));
    $('#CommModuleUID').val(opts.moduleUID || 0);
    $('#CommRecordUID').val(opts.recordUID || 0);

    // To info
    $('#CommToName').text(names[0] || '\u2014');
    // Set initial contact based on active type
    var _initialContact = (activeType === 'SMS') ? String(_commMobiles[0] || '') : String(_commEmails[0] || '');
    $('#CommToContact').text(_initialContact);

    // Show/hide tabs based on available contact info
    var hasMobile = !!(String(_commMobiles[0] || '').trim());
    var hasEmail  = !!(String(_commEmails[0]  || '').trim());
    $('#CommTabSMS').toggleClass('d-none', !hasMobile);
    $('#CommTabEmail').toggleClass('d-none', !hasEmail);

    // Decide active type: prefer requested, fallback to what's available
    var activeType = commType;
    if (activeType === 'SMS'   && !hasMobile && hasEmail)  activeType = 'Email';
    if (activeType === 'Email' && !hasEmail  && hasMobile) activeType = 'SMS';
    $('#CommActiveType').val(activeType);

    // No contact warning
    var hasContact = activeType === 'SMS' ? hasMobile : hasEmail;
    if (!hasContact) {
        $('#CommNoContactWarning').removeClass('d-none');
        $('#CommNoContactText').text('No ' + (activeType === 'SMS' ? 'mobile number' : 'email address') + ' available');
    } else {
        $('#CommNoContactWarning').addClass('d-none');
    }

    // Reset editors
    _initCommQuill();
    if (_commSmsQuill)   { _commSmsQuill.setText('');   $('#CommSmsMessage').val(''); }
    if (_commEmailQuill) { _commEmailQuill.setText(''); $('#CommEmailMessage').val(''); }
    $('#CommEmailSubject').val('');
    $('#CommSmsCharCount').text('0');
    $('#CommSmsParts').text('1');
    $('#CommSmsPartsS').text('');

    // Reset attachments
    if (_commDropzone) { _commDropzone.removeAllFiles(true); }
    _commPdfAutoAttached = false;

    // Reset token toggle
    _commTpl        = { rawSubject: '', rawBody: '', resolvedSubject: '', resolvedBody: '', showingRaw: false };
    _commPendingTpl = null;
    $('#CommTokenToggleBtn').addClass('d-none').removeClass('btn-outline-warning').addClass('btn-outline-secondary');
    $('#CommTokenToggleLabel').text('Show Tokens');

    _switchCommTab(activeType);
    $('#SendCommModal').modal('show');
}

// -- Template fetch ----------------------------------------------------------
// Uses pre-loaded _rawEmailTemplate (set in view.php) for instant client-side
// token replacement. Falls back to AJAX only when the template is not pre-loaded.
function _fetchCommTemplate(moduleUID, recordUID) {
    if (!moduleUID) return;

    // If the page declared _rawEmailTemplate (any value including null), the server
    // already checked the DB — no AJAX needed.
    // undefined = old page not yet updated → fall back to AJAX for backward compat.
    if (typeof window._rawEmailTemplate !== 'undefined') {
        if (window._rawEmailTemplate &&
            (window._rawEmailTemplate.Subject || window._rawEmailTemplate.Body)) {
            var rawSubject = window._rawEmailTemplate.Subject || '';
            var rawBody    = window._rawEmailTemplate.Body    || '';
            var ctx        = _buildCommTokenContext(moduleUID, _commRowData);
            var resolved   = _resolveCommTokensJS(rawSubject, rawBody, ctx);
            var resp       = { RawSubject: rawSubject, RawBody: rawBody,
                               Subject: resolved.subject, Body: resolved.body };
            if (_commEmailQuill) { _applyCommTemplate(resp); } else { _commPendingTpl = resp; }
        }
        // null or empty template → nothing to apply, but still skip AJAX
        return;
    }

    // _rawEmailTemplate not declared → old page, fall back to AJAX
    $.ajax({
        url   : '/globally/getCommTemplate',
        method: 'POST',
        data  : { ModuleUID: moduleUID, RecordUID: recordUID || 0, Channel: 'Email', [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Found) return;
            if (_commEmailQuill) { _applyCommTemplate(resp); } else { _commPendingTpl = resp; }
        }
    });
}

// -- Client-side token context builder ---------------------------------------
// Mirrors PHP's _resolveCommTokens / _buildCommContext for all common modules.
function _buildCommTokenContext(moduleUID, rowData) {
    rowData    = rowData    || {};
    var orgCtx = (window._commOrgContext  || {});
    var gs     = (window._commGenSettings || {});
    var cur    = gs.CurrenySymbol || '₹';
    var dec    = parseInt(gs.DecimalPoints || 2);
    var fmtAmt = function (n) {
        return parseFloat(n || 0).toFixed(dec)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };
    var appBase = ((window._commAppBase || window.location.origin) + '').replace(/\/+$/, '');

    var ctx = {
        PARTY_NAME:      rowData.partyName    || '',
        DOC_NUMBER:      rowData.docNumber    || '',
        DOC_DATE:        rowData.docDate      || '',
        DOC_TYPE:        rowData.docType      || '',
        AMOUNT:          fmtAmt(rowData.netAmount),
        AMOUNT_IN_WORDS: rowData.amountWords  || '',
        CURRENCY:        cur,
        VALID_UNTIL:     rowData.validityDate || '',
        ORG_NAME:        orgCtx.OrgName       || '',
        ORG_PHONE:       orgCtx.OrgPhone      || '',
        ORG_EMAIL:       orgCtx.OrgEmail      || '',
        ORG_GSTIN:       orgCtx.OrgGSTIN      || '',
        ORG_ADDRESS:     orgCtx.OrgAddress    || '',
        // camelCase aliases
        PartyName:       rowData.partyName    || '',
        DocNumber:       rowData.docNumber    || '',
        DocDate:         rowData.docDate      || '',
        Amount:          fmtAmt(rowData.netAmount),
        AmountInWords:   rowData.amountWords  || '',
        CompanyName:     orgCtx.OrgName       || '',
        CompanyMobile:   orgCtx.OrgPhone      || '',
        CompanyEmail:    orgCtx.OrgEmail      || '',
        CompanyGSTIN:    orgCtx.OrgGSTIN      || '',
        CompanyAddress:  orgCtx.OrgAddress    || '',
    };

    // Module 103: Invoice
    if (moduleUID === 103) {
        var bal       = Math.max(0, (rowData.netAmount || 0) - (rowData.paidAmount || 0));
        var paid      = rowData.paidAmount || 0;
        var payStatus = paid > 0 && bal <= 0.01 ? 'Paid' : paid > 0 ? 'Partially Paid' : 'Pending';
        var invLink   = rowData.transToken
            ? appBase + '/invoice/' + rowData.transToken : '';
        Object.assign(ctx, {
            INVOICE_NUMBER:  rowData.docNumber    || '',
            INVOICE_DATE:    rowData.docDate      || '',
            DUE_DATE:        rowData.validityDate || '',
            PAYMENT_STATUS:  payStatus,
            PAID_AMOUNT:     fmtAmt(paid),
            BALANCE_AMOUNT:  fmtAmt(bal),
            INVOICE_LINK:    invLink,
            InvoiceNumber:   rowData.docNumber    || '',
            InvoiceDate:     rowData.docDate      || '',
            DueDate:         rowData.validityDate || '',
            PaymentStatus:   payStatus,
            PaidAmount:      fmtAmt(paid),
            BalanceAmount:   fmtAmt(bal),
            InvoiceLink:     invLink,
            CustomerName:    rowData.partyName    || '',
            BillAmount:      fmtAmt(rowData.netAmount),
        });
    }

    return ctx;
}

function _resolveCommTokensJS(subject, body, ctx) {
    var replace = function (text) {
        return text.replace(/\{\{([^}]+)\}\}/g, function (m, k) {
            return ctx.hasOwnProperty(k) ? ctx[k] : m;
        });
    };
    return { subject: replace(subject), body: replace(body) };
}

// -- PDF attachment fetch (module 110 Payments) ------------------------------
function _fetchCommPdfAttachment(moduleUID, recordUID) {
    if (!moduleUID || !recordUID) return;

    var endpoint = null;
    if (moduleUID === 110) { endpoint = '/payments/getPaymentPdfBase64'; }
    if (!endpoint) return;

    _commPdfAutoAttached = true;

    var $dz = $('#CommAttachDropzone');
    var $loader = $('<div id="CommPdfAttachLoader" class="text-center py-2" style="font-size:.78rem;color:#666;"><span class="spinner-border spinner-border-sm me-1"></span>Attaching receipt PDF...</div>');
    $dz.append($loader);

    $.ajax({
        url   : endpoint,
        method: 'POST',
        data  : { PaymentUID: recordUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
        success: function (resp) {
            $('#CommPdfAttachLoader').remove();
            if (resp.Error || !resp.Base64) { _commPdfAutoAttached = false; return; }
            _initCommDropzone();
            if (!_commDropzone) { _commPdfAutoAttached = false; return; }
            try {
                var binary = atob(resp.Base64);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                var blob = new Blob([bytes], { type: 'application/pdf' });
                var file = new File([blob], resp.Filename || 'receipt.pdf', { type: 'application/pdf' });
                _commDropzone.addFile(file);
            } catch (e) {
                _commPdfAutoAttached = false;
            }
        },
        error: function () {
            $('#CommPdfAttachLoader').remove();
            _commPdfAutoAttached = false;
        }
    });
}

// -- Token toggle ------------------------------------------------------------
$(document).on('click', '#CommTokenToggleBtn', function () {
    _commTpl.showingRaw = !_commTpl.showingRaw;

    var subject = _commTpl.showingRaw ? _commTpl.rawSubject  : _commTpl.resolvedSubject;
    var body    = _commTpl.showingRaw ? _commTpl.rawBody     : _commTpl.resolvedBody;

    $('#CommEmailSubject').val(subject);
    if (_commEmailQuill) {
        _commEmailQuill.root.innerHTML = body || '';
        $('#CommEmailMessage').val(body || '');
    }

    $('#CommTokenToggleLabel').text(_commTpl.showingRaw ? 'Show Resolved' : 'Show Tokens');
    $(this).toggleClass('btn-outline-secondary btn-outline-warning');
});

// -- Tab switching -----------------------------------------------------------
$(document).on('click', '.comm-type-tab', function () {
    var type = $(this).data('commtype');
    _switchCommTab(type);
    $('#CommActiveType').val(type);

    // Update To contact display
    var toContact = type === 'SMS' ? (_commMobiles[0] || '') : (_commEmails[0] || '');
    $('#CommToContact').text(toContact);

    var hasContact = type === 'SMS'
        ? !!(String(_commMobiles[0] || '').trim())
        : !!(String(_commEmails[0]  || '').trim());
    if (!hasContact) {
        $('#CommNoContactWarning').removeClass('d-none');
        $('#CommNoContactText').text('No ' + (type === 'SMS' ? 'mobile number' : 'email address') + ' available');
    } else {
        $('#CommNoContactWarning').addClass('d-none');
    }

    // Switching to Email: fetch template + trigger page-specific PDF fetch
    if (type === 'Email') {
        var moduleUID = parseInt($('#CommModuleUID').val()) || 0;
        var recordUID = parseInt($('#CommRecordUID').val()) || 0;
        setTimeout(_initCommDropzone, 100);
        if (moduleUID && !$('#CommEmailSubject').val() && !_commTpl.resolvedSubject) {
            _fetchCommTemplate(moduleUID, recordUID);
        }
        if (!_commPdfAutoAttached && moduleUID === 110 && recordUID > 0) {
            _fetchCommPdfAttachment(moduleUID, recordUID);
        }
        // Let page-specific handlers attach their PDF (e.g. invoices.js)
        $(document).trigger('comm:switchedToEmail', [moduleUID, recordUID]);
    }
});

function _switchCommTab(type) {
    var isSms = (type === 'SMS');
    $('.comm-type-tab').removeClass('active btn-primary btn-outline-secondary');
    $('.comm-type-tab[data-commtype="' + type + '"]').addClass('active btn-primary');
    $('.comm-type-tab[data-commtype!="' + type + '"]').addClass('btn-outline-secondary');

    // Email: show From + To side by side (col-6 each)
    // SMS:   hide From, To takes full width (col-12)
    $('#CommFromSection').toggleClass('d-none', isSms);
    $('#CommToSection').toggleClass('col-6', !isSms).toggleClass('col-12', isSms);

    // SMS: show only To + message; Email: show email fields
    $('#CommSmsFields').toggleClass('d-none', !isSms);
    $('#CommEmailFields').toggleClass('d-none', isSms);

    $('#SendCommModalTitle').text('Send ' + type);
    $('#SendCommBtnLabel').text('Send ' + type);

    // Update header banner colour
    var $hdr = $('#CommModalHeader');
    var $iconI = $('#CommHeaderIconI');
    if (isSms) {
        $iconI.attr('class', 'bx bx-message-rounded comm-header-icon-i');
        $hdr[0].style.setProperty('--vtm-color', '#198754');
        $hdr[0].style.setProperty('--vtm-bg',    '#e8f5ee');
        $hdr[0].style.setProperty('--vtm-icon-bg', 'rgba(25,135,84,.13)');
    } else {
        $iconI.attr('class', 'bx bx-envelope comm-header-icon-i');
        $hdr[0].style.setProperty('--vtm-color', '#0d6efd');
        $hdr[0].style.setProperty('--vtm-bg',    '#e8f0fe');
        $hdr[0].style.setProperty('--vtm-icon-bg', 'rgba(13,110,253,.13)');
    }

    setTimeout(_initCommQuill, 100);
}

// -- comm-send-single click --------------------------------------------------
$(document).on('click', '.comm-send-single', function () {
    var $btn          = $(this);
    var $row          = $btn.closest('tr');
    var commType      = $btn.data('commtype');
    var recipientType = $btn.data('recipienttype') || 'Vendor';
    var uid           = $btn.data('uid');
    var name          = $btn.data('name')   || '\u2014';
    var mobile        = $btn.data('mobile') || '';
    var email         = $btn.data('email')  || '';
    var moduleUID     = parseInt($btn.data('module-uid') || $row.data('trans-module') || 0);
    var recordUID     = parseInt($btn.data('trans-uid') || $row.data('uid') || 0);

    openCommModal(commType, recipientType, [uid], [name], [mobile], [email], {
        moduleUID: moduleUID,
        recordUID: recordUID,
        rowData: {
            pdfPath:      String($btn.data('pdf-path')      || ''),
            transToken:   String($btn.data('trans-token')   || ''),
            docNumber:    String($btn.data('doc-number')    || ''),
            netAmount:    parseFloat($btn.data('net-amount')   || 0),
            paidAmount:   parseFloat($btn.data('paid-amount')  || 0),
            docDate:      String($btn.data('doc-date')      || ''),
            validityDate: String($btn.data('validity-date') || ''),
            docStatus:    String($btn.data('doc-status')    || ''),
            amountWords:  String($btn.data('amount-words')  || ''),
            partyName:    String($btn.data('name')          || ''),
            docType:      String($btn.data('doc-type')      || ''),
        }
    });
});

// -- Send button -------------------------------------------------------------
$(document).on('click', '#SendCommBtn', function () {
    var type          = $('#CommActiveType').val();
    var recipientType = $('#CommRecipientType').val();
    var uids          = JSON.parse($('#CommRecipientUIDs').val() || '[]');

    var subject, message;
    if (type === 'Email') {
        subject = _commTpl.resolvedSubject || $('#CommEmailSubject').val().trim();
        message = _commTpl.resolvedBody    || ($('#CommEmailMessage').val() || (_commEmailQuill ? _commEmailQuill.root.innerHTML : ''));
    } else {
        subject = '';
        message = $('#CommSmsMessage').val() || (_commSmsQuill ? _commSmsQuill.getText().trim() : '');
    }

    if (!uids.length)                              { Swal.fire({ icon: 'warning', text: 'No recipients selected.' }); return; }
    if (!message || message === '<p><br></p>')     { Swal.fire({ icon: 'warning', text: 'Please enter a message.' }); return; }
    if (type === 'Email' && !subject)              { Swal.fire({ icon: 'warning', text: 'Please enter an email subject.' }); return; }

    var url = '/' + recipientType.toLowerCase() + 's/sendCommunication';

    $('#SendCommBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending...');

    var fd = new FormData();
    fd.append('CommType',      type);
    fd.append('RecipientType', recipientType);
    uids.forEach(function (id) { fd.append('UIDs[]', id); });
    fd.append('Message',   message);
    fd.append('Subject',   subject);
    fd.append('ModuleUID', $('#CommModuleUID').val() || 0);
    fd.append('RecordUID', $('#CommRecordUID').val() || 0);
    fd.append(CsrfName, CsrfToken);

    if (type === 'Email' && _commDropzone && _commDropzone.files.length) {
        _commDropzone.files.forEach(function (f) { fd.append('Attachments[]', f); });
    }

    $.ajax({
        url        : url,
        method     : 'POST',
        data       : fd,
        processData: false,
        contentType: false,
        success: function (resp) {
            $('#SendCommBtn').prop('disabled', false).html('<i class="bx bx-send me-1"></i><span id="SendCommBtnLabel">Send ' + type + '</span>');
            if (resp.Error) {
                Swal.fire({ icon: 'error', title: 'Failed', text: resp.Message });
            } else {
                $('#SendCommModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Sent!', text: resp.Message, timer: 2500, showConfirmButton: false });
            }
        },
        error: function () {
            $('#SendCommBtn').prop('disabled', false).html('<i class="bx bx-send me-1"></i><span id="SendCommBtnLabel">Send ' + type + '</span>');
            Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
        }
    });
});

// -- Modal shown: init Quill + Dropzone, fetch template + trigger PDF --------
$(document).on('shown.bs.modal', '#SendCommModal', function () {
    _initCommQuill();
    var type      = $('#CommActiveType').val();
    var moduleUID = parseInt($('#CommModuleUID').val()) || 0;
    var recordUID = parseInt($('#CommRecordUID').val()) || 0;

    if (type === 'Email') {
        setTimeout(_initCommDropzone, 100);
        if (moduleUID) {
            _fetchCommTemplate(moduleUID, recordUID);
        }
        if (!_commPdfAutoAttached && moduleUID === 110 && recordUID > 0) {
            _fetchCommPdfAttachment(moduleUID, recordUID);
        }
        // Let page-specific handlers attach their PDF (e.g. invoices.js)
        $(document).trigger('comm:switchedToEmail', [moduleUID, recordUID]);
    }
    // If opening on SMS tab, leave _commPdfAutoAttached = false so PDF fetch
    // fires correctly when user switches to Email tab later
});

// -- Reset on modal close ----------------------------------------------------
$(document).on('hidden.bs.modal', '#SendCommModal', function () {
    if (_commDropzone) { _commDropzone.removeAllFiles(true); }
});

// -- Keep resolvedSubject in sync with manual edits --------------------------
$(document).on('input', '#CommEmailSubject', function () {
    if (!_commTpl.showingRaw) { _commTpl.resolvedSubject = $(this).val(); }
});
