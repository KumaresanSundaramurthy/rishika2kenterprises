/**
 * communication.js — shared Send SMS / Send Email modal logic
 * Accessible globally across all pages.
 *
 * Public API:
 *   sendSMS(options)   — open modal pre-set to SMS
 *   sendEmail(options) — open modal pre-set to Email
 *   openCommModal(commType, recipientType, uids, names, mobiles, emails)
 *
 * options = {
 *   recipientType : 'Vendor' | 'Customer',
 *   uid           : number,
 *   name          : string,
 *   mobile        : string,   (for SMS)
 *   email         : string,   (for Email)
 *   fromName      : string,   (org name)
 *   fromContact   : string,   (org phone / email)
 * }
 */

// ── Quill instances ──────────────────────────────────────────────────────────
var _commSmsQuill   = null;
var _commEmailQuill = null;

function _initCommQuill() {
    if (!_commSmsQuill && document.getElementById('CommSmsEditor')) {
        _commSmsQuill = new Quill('#CommSmsEditor', {
            theme  : 'snow',
            placeholder: 'Type your SMS message here...',
            modules: {
                toolbar: [
                    ['bold', 'italic'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['clean']
                ]
            }
        });
        _commSmsQuill.on('text-change', function () {
            var text = _commSmsQuill.getText().trim();
            $('#CommSmsMessage').val(text);
            var len   = text.length;
            var parts = len === 0 ? 1 : Math.ceil(len / 160);
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
            $('#CommEmailMessage').val(_commEmailQuill.root.innerHTML);
        });
    }
}

// ── Public: sendSMS ──────────────────────────────────────────────────────────
function sendSMS(options) {
    openCommModal('SMS', options.recipientType || 'Vendor',
        [options.uid], [options.name], [options.mobile], [options.email || '']
    );
}

// ── Public: sendEmail ────────────────────────────────────────────────────────
function sendEmail(options) {
    openCommModal('Email', options.recipientType || 'Vendor',
        [options.uid], [options.name], [options.mobile || ''], [options.email]
    );
}

// ── Public: openCommModal ────────────────────────────────────────────────────
function openCommModal(commType, recipientType, uids, names, mobiles, emails) {

    $('#CommActiveType').val(commType);
    $('#CommRecipientType').val(recipientType);
    $('#CommRecipientUIDs').val(JSON.stringify(uids));

    // From info — now static in modal from .env (MAIL_FROM_NAME / MAIL_FROM_EMAIL)

    // To info
    $('#CommToName').text(names[0] || '—');
    var toContact = commType === 'SMS' ? (mobiles[0] || '') : (emails[0] || '');
    $('#CommToContact').text(toContact);

    // No contact warning
    var hasContact = commType === 'SMS' ? !!(mobiles[0] && mobiles[0].trim()) : !!(emails[0] && emails[0].trim());
    if (!hasContact) {
        var field = commType === 'SMS' ? 'mobile number' : 'email address';
        $('#CommNoContactWarning').removeClass('d-none');
        $('#CommNoContactText').text('No ' + field + ' available');
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

    _switchCommTab(commType);
    $('#SendCommModal').modal('show');
}

// ── Tab switching ────────────────────────────────────────────────────────────
$(document).on('click', '.comm-type-tab', function () {
    var type = $(this).data('commtype');
    _switchCommTab(type);
    $('#CommActiveType').val(type);

    // Update To contact display
    var uids    = JSON.parse($('#CommRecipientUIDs').val() || '[]');
    // contact is stored in the button's data — re-read from hidden inputs not available here
    // so just update the label
    $('#SendCommModalTitle').text('Send ' + type);
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
    // Update header icon
    if (isSms) {
        $('#CommHeaderIconI').attr('class', 'bx bx-message-rounded fs-4 text-primary');
        $('#CommHeaderIcon').css('background', '#e8f0fe');
    } else {
        $('#CommHeaderIconI').attr('class', 'bx bx-envelope fs-4 text-success');
        $('#CommHeaderIcon').css('background', '#e8f5e9');
    }
    // Init quill if not yet
    setTimeout(_initCommQuill, 100);
}

// ── comm-send-single click (single row button) ───────────────────────────────
$(document).on('click', '.comm-send-single', function () {
    var commType      = $(this).data('commtype');
    var recipientType = $(this).data('recipienttype') || 'Vendor';
    var uid           = $(this).data('uid');
    var name          = $(this).data('name')   || '—';
    var mobile        = $(this).data('mobile') || '';
    var email         = $(this).data('email')  || '';

    // From info — static in modal from .env
    openCommModal(
        commType, recipientType,
        [uid], [name], [mobile], [email]
    );
});

// ── Send button ──────────────────────────────────────────────────────────────
$(document).on('click', '#SendCommBtn', function () {
    var type          = $('#CommActiveType').val();
    var recipientType = $('#CommRecipientType').val();
    var uids          = JSON.parse($('#CommRecipientUIDs').val() || '[]');
    var subject       = $('#CommEmailSubject').val().trim();

    // Get message from hidden input (synced from Quill)
    var message = type === 'SMS'
        ? ($('#CommSmsMessage').val() || (_commSmsQuill ? _commSmsQuill.getText().trim() : ''))
        : ($('#CommEmailMessage').val() || (_commEmailQuill ? _commEmailQuill.root.innerHTML : ''));

    if (!uids.length) {
        Swal.fire({ icon: 'warning', text: 'No recipients selected.' }); return;
    }
    if (!message || message === '<p><br></p>') {
        Swal.fire({ icon: 'warning', text: 'Please enter a message.' }); return;
    }
    if (type === 'Email' && !subject) {
        Swal.fire({ icon: 'warning', text: 'Please enter an email subject.' }); return;
    }

    var url = '/' + recipientType.toLowerCase() + 's/sendCommunication';

    $('#SendCommBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending...');

    $.ajax({
        url   : url,
        method: 'POST',
        data  : {
            CommType     : type,
            RecipientType: recipientType,
            UIDs         : uids,
            Message      : message,
            Subject      : subject,
            [CsrfName]   : CsrfToken
        },
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

// ── Re-init Quill when modal opens ───────────────────────────────────────────
$(document).on('shown.bs.modal', '#SendCommModal', function () {
    _initCommQuill();
});
