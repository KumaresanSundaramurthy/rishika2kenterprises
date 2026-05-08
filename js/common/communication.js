/**
 * communication.js — shared Send SMS / Send Email modal logic
 *
 * Public API:
 *   openCommModal(commType, recipientType, uids, names, mobiles, emails, opts)
 *   sendSMS(options)
 *   sendEmail(options)
 *
 * opts = {
 *   moduleUID   : number   — module id (110 = Payments, etc.)
 *   recordUID   : number   — the specific record id (PaymentUID, etc.) for DB token resolution
 * }
 */

// ── Quill instances ───────────────────────────────────────────────────────────
var _commSmsQuill   = null;
var _commEmailQuill = null;
var _commDropzone   = null;

// ── Store mobiles/emails for tab switching ────────────────────────────────────
var _commMobiles = [];
var _commEmails  = [];

// ── Template raw/resolved store ──────────────────────────────────────────────
var _commTpl = { rawSubject: '', rawBody: '', resolvedSubject: '', resolvedBody: '', showingRaw: false };

// ── Pending template payload (set before modal show, applied after Quill ready)
var _commPendingTpl = null;

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
            // Keep resolvedBody in sync with manual edits (only when not viewing raw tokens)
            if (!_commTpl.showingRaw) { _commTpl.resolvedBody = html; }
        });

        // If a template was fetched before Quill was ready, apply it now
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
    _commDropzone = new Dropzone(el, {
        url              : '#',
        autoProcessQueue : false,
        maxFiles         : 3,
        maxFilesize      : 3,
        addRemoveLinks   : true,
        acceptedFiles    : '.pdf,.jpg,.jpeg,.png',
        parallelUploads  : 3,
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

// ── Apply a fetched template payload to the editor ───────────────────────────
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

    // Show toggle button only when tokens were actually replaced
    if (_commTpl.rawSubject !== _commTpl.resolvedSubject || _commTpl.rawBody !== _commTpl.resolvedBody) {
        $('#CommTokenToggleBtn')
            .removeClass('d-none btn-outline-warning')
            .addClass('btn-outline-secondary');
        $('#CommTokenToggleLabel').text('Show Tokens');
    }
}

// ── Public: sendSMS ───────────────────────────────────────────────────────────
function sendSMS(options) {
    openCommModal('SMS', options.recipientType || 'Vendor',
        [options.uid], [options.name], [options.mobile || ''], [options.email || ''],
        { moduleUID: options.moduleUID || 0, recordUID: options.recordUID || 0 }
    );
}

// ── Public: sendEmail ─────────────────────────────────────────────────────────
function sendEmail(options) {
    openCommModal('Email', options.recipientType || 'Vendor',
        [options.uid], [options.name], [options.mobile || ''], [options.email || ''],
        { moduleUID: options.moduleUID || 0, recordUID: options.recordUID || 0 }
    );
}

// ── Public: openCommModal ─────────────────────────────────────────────────────
function openCommModal(commType, recipientType, uids, names, mobiles, emails, opts) {
    opts = opts || {};

    _commMobiles = mobiles || [];
    _commEmails  = emails  || [];

    $('#CommActiveType').val(commType);
    $('#CommRecipientType').val(recipientType);
    $('#CommRecipientUIDs').val(JSON.stringify(uids));
    $('#CommModuleUID').val(opts.moduleUID || 0);
    $('#CommRecordUID').val(opts.recordUID || 0);

    // To info
    $('#CommToName').text(names[0] || '—');
    var toContact = commType === 'SMS' ? (_commMobiles[0] || '') : (_commEmails[0] || '');
    $('#CommToContact').text(toContact);

    // No contact warning
    var hasContact = commType === 'SMS'
        ? !!(_commMobiles[0] && _commMobiles[0].trim())
        : !!(_commEmails[0]  && _commEmails[0].trim());
    if (!hasContact) {
        $('#CommNoContactWarning').removeClass('d-none');
        $('#CommNoContactText').text('No ' + (commType === 'SMS' ? 'mobile number' : 'email address') + ' available');
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

    // Reset token toggle state
    _commTpl        = { rawSubject: '', rawBody: '', resolvedSubject: '', resolvedBody: '', showingRaw: false };
    _commPendingTpl = null;
    $('#CommTokenToggleBtn').addClass('d-none').removeClass('btn-outline-warning').addClass('btn-outline-secondary');
    $('#CommTokenToggleLabel').text('Show Tokens');

    _switchCommTab(commType);
    $('#SendCommModal').modal('show');
    // Template fetch happens in shown.bs.modal after Quill is fully initialised
}

// ── Template fetch from server ────────────────────────────────────────────────
// Passes ModuleUID + RecordUID so the server fetches live DB data for token replacement.
function _fetchCommTemplate(moduleUID, recordUID) {
    if (!moduleUID) return;
    $.ajax({
        url   : '/globally/getCommTemplate',
        method: 'POST',
        data  : { ModuleUID: moduleUID, RecordUID: recordUID || 0, Channel: 'Email', [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Found) return;
            if (_commEmailQuill) {
                // Quill is ready — apply immediately
                _applyCommTemplate(resp);
            } else {
                // Quill not ready yet — store and apply once _initCommQuill runs
                _commPendingTpl = resp;
            }
        }
    });
}

// ── Token toggle ──────────────────────────────────────────────────────────────
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

// ── Tab switching ─────────────────────────────────────────────────────────────
$(document).on('click', '.comm-type-tab', function () {
    var type = $(this).data('commtype');
    _switchCommTab(type);
    $('#CommActiveType').val(type);

    // Update To contact
    var toContact = type === 'SMS' ? (_commMobiles[0] || '') : (_commEmails[0] || '');
    $('#CommToContact').text(toContact);

    var hasContact = type === 'SMS'
        ? !!(_commMobiles[0] && _commMobiles[0].trim())
        : !!(_commEmails[0]  && _commEmails[0].trim());
    if (!hasContact) {
        $('#CommNoContactWarning').removeClass('d-none');
        $('#CommNoContactText').text('No ' + (type === 'SMS' ? 'mobile number' : 'email address') + ' available');
    } else {
        $('#CommNoContactWarning').addClass('d-none');
    }

    // Auto-fetch template on switch to Email (only if not already loaded)
    if (type === 'Email') {
        var moduleUID = parseInt($('#CommModuleUID').val()) || 0;
        var recordUID = parseInt($('#CommRecordUID').val()) || 0;
        if (moduleUID && !$('#CommEmailSubject').val() && !_commTpl.resolvedSubject) {
            _fetchCommTemplate(moduleUID, recordUID);
        }
        setTimeout(_initCommDropzone, 100);
    }
});

function _switchCommTab(type) {
    var isSms = (type === 'SMS');
    $('.comm-type-tab').removeClass('active btn-primary btn-outline-secondary');
    $('.comm-type-tab[data-commtype="' + type + '"]').addClass('active btn-primary');
    $('.comm-type-tab[data-commtype!="' + type + '"]').addClass('btn-outline-secondary');
    $('#CommSmsFields').toggleClass('d-none', !isSms);
    $('#CommEmailFields').toggleClass('d-none', isSms);
    $('#SendCommModalTitle').text('Send ' + type);
    $('#SendCommBtnLabel').text('Send ' + type);

    var $icon  = $('#CommHeaderIcon');
    var $iconI = $('#CommHeaderIconI');
    if (isSms) {
        $icon.removeClass('email').addClass('sms');
        $iconI.attr('class', 'bx bx-message-rounded comm-header-icon-i');
    } else {
        $icon.removeClass('sms').addClass('email');
        $iconI.attr('class', 'bx bx-envelope comm-header-icon-i');
    }

    setTimeout(_initCommQuill, 100);
}

// ── comm-send-single click ────────────────────────────────────────────────────
$(document).on('click', '.comm-send-single', function () {
    var $btn          = $(this);
    var $row          = $btn.closest('tr');
    var commType      = $btn.data('commtype');
    var recipientType = $btn.data('recipienttype') || 'Vendor';
    var uid           = $btn.data('uid');
    var name          = $btn.data('name')   || '—';
    var mobile        = $btn.data('mobile') || '';
    var email         = $btn.data('email')  || '';
    var moduleUID     = parseInt($btn.data('module-uid') || $row.data('trans-module') || 0);
    // recordUID = PaymentUID from the row — used server-side to fetch live DB data for tokens
    var recordUID     = parseInt($row.data('uid') || 0);

    openCommModal(commType, recipientType, [uid], [name], [mobile], [email],
        { moduleUID: moduleUID, recordUID: recordUID }
    );
});

// ── Send button ───────────────────────────────────────────────────────────────
$(document).on('click', '#SendCommBtn', function () {
    var type          = $('#CommActiveType').val();
    var recipientType = $('#CommRecipientType').val();
    var uids          = JSON.parse($('#CommRecipientUIDs').val() || '[]');

    // Always send resolved content — never raw tokens
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
    fd.append('Message', message);
    fd.append('Subject', subject);
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

// ── Modal shown: init Quill + Dropzone, then fetch template ──────────────────
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
    }
});

// ── Reset on modal close ──────────────────────────────────────────────────────
$(document).on('hidden.bs.modal', '#SendCommModal', function () {
    if (_commDropzone) { _commDropzone.removeAllFiles(true); }
});

// ── Keep resolvedSubject in sync with manual subject edits ────────────────────
$(document).on('input', '#CommEmailSubject', function () {
    if (!_commTpl.showingRaw) { _commTpl.resolvedSubject = $(this).val(); }
});
