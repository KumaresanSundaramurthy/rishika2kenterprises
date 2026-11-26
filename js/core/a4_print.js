// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Title             = '';
var _a4Header            = null;   // full response header (PartyMobile, PartyName, etc.)
var _a4PdfBase64         = null;   // PDF base64 for in-modal native PDF viewer
var _a4DownloadUid       = null;
var _a4DownloadModuleUID = null;

/* ── PDF preview helpers ─────────────────────────────────────────────────── */

/**
 * Converts a base64 string to a Blob URL for the browser's native PDF viewer.
 * @param {string} b64      - Base64-encoded PDF string
 * @param {string} mimeType - MIME type (default 'application/pdf')
 * @returns {string} Blob URL
 */
function _a4B64ToBlobUrl(b64, mimeType) {
    var byteChars = atob(b64);
    var bytes     = new Uint8Array(byteChars.length);
    for (var i = 0; i < byteChars.length; i++) {
        bytes[i] = byteChars.charCodeAt(i);
    }
    return URL.createObjectURL(new Blob([bytes], { type: mimeType || 'application/pdf' }));
}

/**
 * Renders a PDF (from base64) inside the modal stage using the browser's native PDF viewer.
 * @param {string} base64 - Base64-encoded PDF bytes
 * @returns {void}
 */
function _a4RenderPdfInStage(base64) {
    if (!base64) return;
    var blobUrl = _a4B64ToBlobUrl(base64, 'application/pdf');
    $('#a4PreviewStage').html(
        '<iframe id="a4PdfFrame" src="' + blobUrl + '" ' +
            'style="width:100%;height:780px;border:none;display:block;" ' +
            'type="application/pdf">' +
        '</iframe>'
    );
    /* Revoke after 5 s — the PDF plugin reads the bytes synchronously on load. */
    setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 5000);
}

/**
 * Fetches the transaction as a PDF from NestJS and renders it in the modal.
 * @param {number} uid       - TransUID
 * @param {number} moduleUID - ModuleUID
 * @returns {void}
 */
function _a4FetchPdfPreview(uid, moduleUID) {
    _a4SetLoading(true);
    ajaxLoading(0);
    $.ajax({
        url    : global_services_url + 'api/transactions/getTransactionPreviewPdf',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : { orgId: parseInt(JwtData.Org.OrgUID, 10), TransUID: uid, ModuleUID: moduleUID },
        success: function (data) {
            ajaxLoading(1);
            var resp = (typeof data === 'object' && data !== null) ? data : null;
            if (!resp || resp.Error) {
                _a4SetLoading(false);
                $('#a4PreviewStage').html('<div class="alert alert-danger m-4">' + (resp && resp.Message ? resp.Message : 'Error loading document.') + '</div>');
                return;
            }
            _a4Header    = resp.Header    || null;
            _a4PdfBase64 = resp.PdfBase64 || null;
            _a4Title     = (_a4Header && _a4Header.UniqueNumber) ? _a4Header.UniqueNumber : 'Document';
            var transType = (_a4Header && _a4Header.TransType) ? _a4Header.TransType : 'Document';
            $('#a4ModalTitle').text(transType + ' — ' + _a4Title);
            _a4SetLoading(false);
            _a4RenderPdfInStage(_a4PdfBase64);
        },
        error: function (jqXHR, textStatus) {
            ajaxLoading(1);
            _a4SetLoading(false);
            $('#a4PreviewStage').html('<div class="alert alert-danger m-4">Failed to load document. (' + textStatus + ')</div>');
        }
    });
}

// ── Trigger: click print icon on any transaction row ──────────────────────
$(document).on('click', '.a4PrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _a4Header            = null;
    _a4PdfBase64         = null;
    _a4DownloadUid       = uid;
    _a4DownloadModuleUID = moduleUID;

    $('#a4PrintModal').modal('show');
    _a4FetchPdfPreview(uid, moduleUID);
});

function _a4SetLoading(show) {
    if (show) {
        $('#a4PreviewStage').html(
            '<div class="d-flex justify-content-center align-items-center" style="height:200px;">' +
            '<div class="spinner-border text-secondary"></div></div>'
        );
    }
}

// ── Print button — triggers native PDF viewer print dialog ────────────────
$('#a4PrintBtn').on('click', function () {
    var frame = $('#a4PdfFrame')[0];
    if (frame && frame.contentWindow) frame.contentWindow.print();
});

// ── Download button ───────────────────────────────────────────────────────
$('#a4DownloadBtn').on('click', function () {
    if (!_a4DownloadUid || !_a4DownloadModuleUID) return;
    var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    _submitDownloadPdf(_a4DownloadUid, _a4DownloadModuleUID, size);
});

// ── WhatsApp button ───────────────────────────────────────────────────────
$('#a4WhatsappBtn').on('click', function () {
    if (!_a4Header) return;
    var mobile = (_a4Header.PartyMobile || '').replace(/[^0-9+]/g, '');
    var cc     = (_a4Header.PartyCountryCode || '91').replace(/[^0-9]/g, '');
    if (!mobile) { Swal.fire({ icon: 'info', text: t('swal_no_mobile', 'No mobile number found for this customer.') }); return; }
    var waNum = mobile.startsWith('+') ? mobile.replace('+', '') : (cc + mobile);
    var msg   = 'Dear ' + (_a4Header.PartyName || 'Customer') + ',\n\n'
              + 'Please find your ' + (_a4Header.TransType || 'document') + ' details:\n'
              + _a4Header.UniqueNumber + '\n\n'
              + 'Thank you for your business!';
    window.open('https://wa.me/' + waNum + '?text=' + encodeURIComponent(msg), '_blank');
});

// ── Email button ──────────────────────────────────────────────────────────
$('#a4EmailBtn').on('click', function () {
    if (!_a4Header) return;
    var moduleUID  = _a4DownloadModuleUID;
    var transUID   = _a4DownloadUid;
    var partyUID   = _a4Header.PartyUID   || 0;
    var partyEmail = _a4Header.PartyEmail || '';
    var transType  = _a4Header.TransType  || 'Document';

    if (!partyEmail) { Swal.fire({ icon: 'info', text: t('swal_no_email', 'No email address found for this customer.') }); return; }

    ajaxLoading(0);
    $.post(global_base_url + 'transactions/sendTransactionEmail', {
        TransUID   : transUID,
        ModuleUID  : moduleUID,
        PartyUID   : partyUID,
        PartyType  : 'C',
        [CsrfName] : CsrfToken
    }, function (resp) {
        ajaxLoading(1);
        if (resp && !resp.Error) {
            showToastNotification(transType + ' emailed to ' + partyEmail + ' successfully.', 'success');
        } else {
            Swal.fire({ icon: 'error', text: (resp && resp.Message) ? resp.Message : t('swal_email_failed', 'Failed to send email. Please try again.') });
        }
    }).fail(function () {
        ajaxLoading(1);
        Swal.fire({ icon: 'error', text: t('swal_email_failed', 'Failed to send email. Please try again.') });
    });
});

// ── Direct PDF download (from list page or A4 modal) ─────────────────────
/**
 * Downloads a transaction PDF directly from the services endpoint.
 * @param {number} uid       - TransUID
 * @param {number} moduleUID - ModuleUID
 * @param {string} [size]    - paper size, defaults to 'A4'
 * @returns {void}
 */
function _submitDownloadPdf(uid, moduleUID, size) {
    if (!uid || !moduleUID) { Swal.fire({ icon: 'warning', text: t('swal_download_missing', 'Unable to download: missing transaction data.') }); return; }
    size = size || 'A4';
    var orgUID = JwtData.Org.OrgUID;
    $.ajax({
        url       : global_services_url + 'api/transactions/downloadPdf/' + uid
                    + '?orgId=' + orgUID + '&moduleUID=' + moduleUID + '&paperSize=' + encodeURIComponent(size),
        method    : 'GET',
        headers   : { Authorization: 'Bearer ' + JwtToken },
        xhrFields : { responseType: 'blob' },
        success   : function (blob, status, xhr) {
            var filename = 'Trans_' + uid + '.pdf';
            var cd = xhr.getResponseHeader('Content-Disposition');
            if (cd) { var m = cd.match(/filename="([^"]+)"/i); if (m) filename = m[1]; }
            var blobUrl = URL.createObjectURL(blob);
            var $a = $('<a>').attr({ href: blobUrl, download: filename }).appendTo('body');
            $a[0].click();
            $a.remove();
            URL.revokeObjectURL(blobUrl);
        },
        error     : function () {
            Swal.fire({ icon: 'error', text: t('swal_pdf_error', 'Failed to generate PDF. Please try again.') });
        }
    });
}

$(document).on('click', '.downloadPdfQuotation', function () {
    _submitDownloadPdf($(this).data('uid'), $(this).data('module'));
});

$(document).on('click', '.downloadPdfTransaction', function () {
    _submitDownloadPdf($(this).data('uid'), $(this).data('module'));
});

// ── Programmatic open — called from form pages after Save & Print ─────────

/**
 * @param {number}   transUID
 * @param {number}   moduleUID
 * @param {string}   format       'a4' | 'a5' (a5 not yet supported in PDF mode; treated as A4)
 * @param {Function} afterCloseCb called when the modal is closed
 * @returns {void}
 */
function openA4PrintByUID(transUID, moduleUID, format, afterCloseCb) {
    _a4Header            = null;
    _a4PdfBase64         = null;
    _a4DownloadUid       = transUID;
    _a4DownloadModuleUID = moduleUID;

    $('#a4PrintModal').modal('show');
    _a4FetchPdfPreview(transUID, moduleUID);
    if (typeof afterCloseCb === 'function') {
        $('#a4PrintModal').one('hide.bs.modal', afterCloseCb);
    }
}
