// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Title             = '';
var _a4Header            = null;
var _a4Pdfs              = { customer: null, transport: null, supplier: null };
var _a4DownloadUid       = null;
var _a4DownloadModuleUID = null;
var _a4BlobUrls          = [];

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
 * Revokes all stored blob URLs to free memory.
 * @returns {void}
 */
function _a4RevokeBlobUrls() {
    $.each(_a4BlobUrls, function (_, url) { URL.revokeObjectURL(url); });
    _a4BlobUrls = [];
}

/**
 * Clears the preview stage and shows an initial loading spinner.
 * @returns {void}
 */
function _a4InitStage() {
    _a4RevokeBlobUrls();
    $('#a4PreviewStage').html(
        '<div class="d-flex justify-content-center align-items-center" style="height:200px;"><div class="spinner-border text-secondary"></div></div>'
    );
}

/**
 * Fetches one copy PDF from NestJS; if already cached, rebuilds the merged preview immediately.
 * On success, stores the base64 and triggers _a4RebuildPreview to merge all checked copies.
 * @param {number} uid       - TransUID
 * @param {number} moduleUID - ModuleUID
 * @param {string} copy      - 'customer' | 'transport' | 'supplier'
 * @returns {void}
 */
function _a4FetchCopyPdf(uid, moduleUID, copy) {
    if (copy !== 'customer') { _a4InitStage(); } // spinner inside modal stage
    if (_a4Pdfs[copy]) { setTimeout(function () { _a4RebuildPreview(); }, 0); return; }
    $.ajax({
        url    : global_services_url + 'api/transactions/getTransactionPreviewPdf',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : { orgId: parseInt(JwtData.Org.OrgUID, 10), TransUID: uid, ModuleUID: moduleUID, copy: copy },
        success: function (data) {
            var resp = (typeof data === 'object' && data !== null) ? data : null;
            if (!resp || resp.Error) {
                $('#a4PreviewStage').html('<div class="alert alert-danger m-3">' + (resp && resp.Message ? resp.Message : 'Error loading.') + '</div>');
                return;
            }
            if (!_a4Header) _a4Header = resp.Header || null;
            _a4Pdfs[copy] = resp.PdfBase64 || null;
            if (copy === 'customer' && _a4Header) {
                _a4Title = _a4Header.UniqueNumber || 'Document';
                $('#a4ModalTitle').text((_a4Header.TransType || 'Document') + ' — ' + _a4Title);
            }
            _a4RebuildPreview();
        },
        error: function (jqXHR, textStatus) {
            $('#a4PreviewStage').html('<div class="alert alert-danger m-3">Failed to load. (' + textStatus + ')</div>');
        }
    });
}

/**
 * Merges all checked+loaded copy PDFs into one and renders a single iframe in the preview stage.
 * Single copy skips pdf-lib (direct blob URL). Multiple copies are merged with pdf-lib.
 * @returns {void}
 */
function _a4RebuildPreview() {
    var order   = ['customer', 'transport', 'supplier'];
    var ids     = { customer: '#copyCustomer', transport: '#copyTransport', supplier: '#copySupplier' };
    var base64s = [];
    $.each(order, function (_, copy) {
        if ($(ids[copy]).is(':checked') && _a4Pdfs[copy]) base64s.push(_a4Pdfs[copy]);
    });
    if (!base64s.length) {
        $('#a4PreviewStage').html('<div class="alert alert-info m-3">Select at least one copy above.</div>');
        return;
    }
    _a4RevokeBlobUrls();
    if (base64s.length === 1) {
        var blobUrl = _a4B64ToBlobUrl(base64s[0], 'application/pdf');
        _a4BlobUrls.push(blobUrl);
        $('#a4PreviewStage').html(
            '<iframe id="a4PdfFrame" src="' + blobUrl + '" style="width:100%;height:780px;border:none;display:block;" type="application/pdf"></iframe>'
        );
        return;
    }
    _a4LoadPdfLib(function () {
        PDFLib.PDFDocument.create().then(function (merged) {
            var tasks = $.map(base64s, function (b64) {
                var binary = atob(b64);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                return PDFLib.PDFDocument.load(bytes, { ignoreEncryption: true }).then(function (doc) {
                    return merged.copyPages(doc, doc.getPageIndices()).then(function (pages) {
                        $.each(pages, function (_, page) { merged.addPage(page); });
                    });
                });
            });
            Promise.all(tasks).then(function () { return merged.save(); }).then(function (mergedBytes) {
                var blob    = new Blob([mergedBytes], { type: 'application/pdf' });
                var blobUrl = URL.createObjectURL(blob);
                _a4BlobUrls.push(blobUrl);
                $('#a4PreviewStage').html(
                    '<iframe id="a4PdfFrame" src="' + blobUrl + '" style="width:100%;height:780px;border:none;display:block;" type="application/pdf"></iframe>'
                );
            });
        });
    });
}

// ── Trigger: click print icon on any transaction row ──────────────────────
$(document).on('click', '.a4PrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _a4Header            = null;
    _a4Pdfs              = { customer: null, transport: null, supplier: null };
    _a4DownloadUid       = uid;
    _a4DownloadModuleUID = moduleUID;

    $('.a4-copy-check').prop('checked', false);
    $('#copyCustomer').prop('checked', true);

    $('#a4PrintModal').modal('show');
    _a4LoadPdfLib(function () {}); // pre-warm pdf-lib while customer PDF loads
    _a4InitStage();
    _a4FetchCopyPdf(uid, moduleUID, 'customer');
});

// ── Print button — single copy: direct print; multiple copies: merge then print ───
$('#a4PrintBtn').on('click', function () {
    var base64s = [];
    if ($('#copyCustomer').is(':checked')  && _a4Pdfs.customer)  base64s.push(_a4Pdfs.customer);
    if ($('#copyTransport').is(':checked') && _a4Pdfs.transport) base64s.push(_a4Pdfs.transport);
    if ($('#copySupplier').is(':checked')  && _a4Pdfs.supplier)  base64s.push(_a4Pdfs.supplier);
    if (!base64s.length) return;
    if (base64s.length === 1) {
        var $frame = $('#a4PreviewStage iframe:visible').first();
        if ($frame.length && $frame[0].contentWindow) $frame[0].contentWindow.print();
        return;
    }
    _a4MergeAndPrint(base64s);
});

// ── Copy checkboxes — lazy-load on check, re-merge on uncheck ────────────
$(document).on('change', '.a4-copy-check', function () {
    var $cb     = $(this);
    var checked = $cb.is(':checked');

    if (!checked) {
        if ($('.a4-copy-check:checked').length === 0) {
            $cb.prop('checked', true);
            return;
        }
        _a4InitStage(); // spinner inside modal stage — setTimeout lets browser paint it first
        setTimeout(function () { _a4RebuildPreview(); }, 0);
        return;
    }

    _a4FetchCopyPdf(_a4DownloadUid, _a4DownloadModuleUID, $cb.val());
});

// ── Modal cleanup — revoke blob URLs when modal closes ───────────────────
$('#a4PrintModal').on('hidden.bs.modal', function () {
    _a4RevokeBlobUrls();
    _a4Pdfs   = { customer: null, transport: null, supplier: null };
    _a4Header = null;
});

/**
 * Lazy-loads pdf-lib from CDN then fires callback.
 * @param {Function} callback - Called once PDFLib is available on window
 * @returns {void}
 */
function _a4LoadPdfLib(callback) {
    if (window.PDFLib) { callback(); return; }
    var $s = $('<script>').attr('src', 'https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js');
    $s.on('load', callback);
    $('head').append($s);
}

/**
 * Merges multiple base64 PDFs with pdf-lib and opens a single print dialog.
 * @param {string[]} base64Array - Base64-encoded PDF strings to merge in order
 * @returns {void}
 */
function _a4MergeAndPrint(base64Array) {
    _a4LoadPdfLib(function () {
        PDFLib.PDFDocument.create().then(function (merged) {
            var tasks = $.map(base64Array, function (b64) {
                var binary = atob(b64);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                return PDFLib.PDFDocument.load(bytes, { ignoreEncryption: true }).then(function (doc) {
                    return merged.copyPages(doc, doc.getPageIndices()).then(function (pages) {
                        $.each(pages, function (_, page) { merged.addPage(page); });
                    });
                });
            });
            Promise.all(tasks).then(function () { return merged.save(); }).then(function (mergedBytes) {
                var blob = new Blob([mergedBytes], { type: 'application/pdf' });
                var url  = URL.createObjectURL(blob);
                var $fr  = $('<iframe>').css({ position: 'fixed', top: '-9999px', left: '-9999px', width: '1px', height: '1px' })
                    .attr({ src: url, type: 'application/pdf' }).appendTo('body');
                $fr[0].onload = function () {
                    $fr[0].contentWindow.print();
                    setTimeout(function () { URL.revokeObjectURL(url); $fr.remove(); }, 5000);
                };
            });
        });
    });
}

// ── Download button — respects checked copy options ──────────────────────
$('#a4DownloadBtn').on('click', function () {
    var order   = ['customer', 'transport', 'supplier'];
    var ids     = { customer: '#copyCustomer', transport: '#copyTransport', supplier: '#copySupplier' };
    var base64s = [];
    $.each(order, function (_, copy) {
        if ($(ids[copy]).is(':checked') && _a4Pdfs[copy]) base64s.push(_a4Pdfs[copy]);
    });

    /* No copies loaded yet (edge case) — fall back to backend download, Customer only */
    if (!base64s.length) {
        if (!_a4DownloadUid || !_a4DownloadModuleUID) return;
        _submitDownloadPdf(_a4DownloadUid, _a4DownloadModuleUID, $('input[name="a4PaperSize"]:checked').val() || 'A4');
        return;
    }

    var filename = (_a4Title || 'Document').replace(/[^a-zA-Z0-9_\-\.]/g, '_') + '.pdf';

    if (base64s.length === 1) {
        var binary = atob(base64s[0]);
        var bytes  = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
        var blob   = new Blob([bytes], { type: 'application/pdf' });
        var url    = URL.createObjectURL(blob);
        $('<a>').attr({ href: url, download: filename }).appendTo('body')[0].click();
        URL.revokeObjectURL(url);
        return;
    }

    _a4LoadPdfLib(function () {
        PDFLib.PDFDocument.create().then(function (merged) {
            var tasks = $.map(base64s, function (b64) {
                var binary = atob(b64);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                return PDFLib.PDFDocument.load(bytes, { ignoreEncryption: true }).then(function (doc) {
                    return merged.copyPages(doc, doc.getPageIndices()).then(function (pages) {
                        $.each(pages, function (_, page) { merged.addPage(page); });
                    });
                });
            });
            Promise.all(tasks).then(function () { return merged.save(); }).then(function (mergedBytes) {
                var blob = new Blob([mergedBytes], { type: 'application/pdf' });
                var url  = URL.createObjectURL(blob);
                $('<a>').attr({ href: url, download: filename }).appendTo('body')[0].click();
                URL.revokeObjectURL(url);
            });
        });
    });
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
    _a4Pdfs              = { customer: null, transport: null, supplier: null };
    _a4DownloadUid       = transUID;
    _a4DownloadModuleUID = moduleUID;

    $('.a4-copy-check').prop('checked', false);
    $('#copyCustomer').prop('checked', true);

    $('#a4PrintModal').modal('show');
    _a4LoadPdfLib(function () {}); // pre-warm pdf-lib while customer PDF loads
    _a4InitStage();
    _a4FetchCopyPdf(transUID, moduleUID, 'customer');
    if (typeof afterCloseCb === 'function') {
        $('#a4PrintModal').one('hide.bs.modal', afterCloseCb);
    }
}
