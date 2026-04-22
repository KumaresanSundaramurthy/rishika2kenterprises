// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Html  = null;
var _a4Title = '';
var _a4DownloadUid       = null;
var _a4DownloadModuleUID = null;

// Paper dimensions at 96dpi (px)
var _paperW = { A4: 794, A5: 559 };
var _paperH = { A4: 1123, A5: 794 };

$(document).on('click', '.a4PrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _a4Html = null;
    _a4DownloadUid       = uid;
    _a4DownloadModuleUID = moduleUID;
    $('#a4PrintModal').modal('show');
    _a4SetLoading(true);
    AjaxLoading = 0;
    $.ajax({
        url   : '/transactions/getTransactionDetail',
        method: 'GET',
        data  : { TransUID: uid, ModuleUID: moduleUID },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) {
                _a4SetLoading(false);
                $('#a4PreviewStage').html('<div class="alert alert-danger m-4">' + resp.Message + '</div>');
                return;
            }
            _a4Html  = resp.PrintHtml;
            _a4Title = (resp.Header && resp.Header.UniqueNumber) ? resp.Header.UniqueNumber : (resp.Header && resp.Header.TransType ? resp.Header.TransType : 'Document');
            $('#a4ModalTitle').text(((resp.Header && resp.Header.TransType) ? resp.Header.TransType : 'Document') + ' — ' + _a4Title);
            _a4SetLoading(false);
            _a4ShowPreview();
        },
        error: function () {
            AjaxLoading = 1;
            _a4SetLoading(false);
            $('#a4PreviewStage').html('<div class="alert alert-danger m-4">Failed to load document.</div>');
        }
    });
});

function _a4SetLoading(show) {
    if (show) {
        $('#a4PreviewStage').html(
            '<div class="d-flex justify-content-center align-items-center" style="height:200px;">' +
            '<div class="spinner-border text-secondary"></div></div>'
        );
    }
}

function _a4ShowPreview() {
    if (!_a4Html) return;

    var size  = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    var pw    = _paperW[size] || 794;
    var ph    = _paperH[size] || 1123;

    var $stage  = $('#a4PreviewStage');
    var stageW  = $stage.width() || 860;
    var scale   = Math.min(1, (stageW - 48) / pw);

    // Paper box uses min-height so it grows for multi-page content after iframe loads
    var paperHtml =
        '<div style="display:flex;justify-content:center;padding:24px 0 32px;">' +
            '<div id="a4PaperBox" style="' +
                'width:'      + pw + 'px;' +
                'min-height:' + ph + 'px;' +
                'transform:scale(' + scale + ');' +
                'transform-origin:top center;' +
                'box-shadow:0 4px 24px rgba(0,0,0,.45);' +
                'background:#fff;' +
                'flex-shrink:0;' +
            '">' +
                '<iframe id="a4PreviewFrame" ' +
                    'style="width:' + pw + 'px;height:' + ph + 'px;border:0;display:block;" ' +
                    'scrolling="no">' +
                '</iframe>' +
            '</div>' +
        '</div>';

    $stage.html(
        '<div id="a4StageWrap" style="min-height:' + (Math.round(ph * scale) + 56) + 'px;">' +
        paperHtml + '</div>'
    );

    // Load via Blob URL so the iframe document has a real URL context,
    // allowing external resources (Google Fonts) to load correctly.
    var iframe  = document.getElementById('a4PreviewFrame');
    var blob    = new Blob([_a4Html], { type: 'text/html; charset=utf-8' });
    var blobUrl = URL.createObjectURL(blob);

    iframe.onload = function () {
        URL.revokeObjectURL(blobUrl);
        iframe.onload = null;
        // Auto-expand iframe to full content height (multi-page support)
        try {
            var doc      = iframe.contentDocument || iframe.contentWindow.document;
            var contentH = Math.max(ph, doc.documentElement.scrollHeight);
            if (contentH > ph) {
                iframe.style.height = contentH + 'px';
                var box  = document.getElementById('a4PaperBox');
                var wrap = document.getElementById('a4StageWrap');
                if (box)  box.style.minHeight  = contentH + 'px';
                if (wrap) wrap.style.minHeight = (Math.round(contentH * scale) + 56) + 'px';
            }
        } catch (e) {}
    };
    iframe.src = blobUrl;
}

// Paper size toggle
$('input[name="a4PaperSize"]').on('change', function () {
    _a4ShowPreview();
});

// Re-render on modal resize (window resize while open)
$(window).on('resize', function () {
    if ($('#a4PrintModal').hasClass('show') && _a4Html) {
        _a4ShowPreview();
    }
});

// Patch @page size for print/PDF
function _a4HtmlForSize() {
    var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    return _a4Html.replace(/@page\s*\{[^}]*\}/, '@page{size:' + size + ';margin:0;}');
}

// Print button — load into hidden iframe via Blob URL and trigger browser print
$('#a4PrintBtn').on('click', function () {
    if (!_a4Html) return;
    var html    = _a4HtmlForSize();
    var blob    = new Blob([html], { type: 'text/html; charset=utf-8' });
    var blobUrl = URL.createObjectURL(blob);

    var frame = document.getElementById('a4PrintFrame');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.id = 'a4PrintFrame';
        frame.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;';
        document.body.appendChild(frame);
    }
    frame.onload = function () {
        URL.revokeObjectURL(blobUrl);
        frame.onload = null;
        frame.contentWindow.document.title = _a4Title;
        frame.contentWindow.print();
    };
    frame.src = blobUrl;
});

// Direct PDF download — bypasses the preview modal entirely
$(document).on('click', '.downloadPdfQuotation', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    if (!uid || !moduleUID) {
        Swal.fire({ icon: 'warning', text: 'Unable to download: missing transaction data.' });
        return;
    }
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/transactions/downloadA4Pdf';
    form.style.display = 'none';
    var fields = { TransUID: uid, ModuleUID: moduleUID, PaperSize: 'A4', [CsrfName]: CsrfToken };
    Object.keys(fields).forEach(function (key) {
        var input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = key;
        input.value = fields[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});

// Download button — POST to server, receive PDF file download
$('#a4DownloadBtn').on('click', function () {
    if (!_a4Html) return;
    var uid       = _a4DownloadUid;
    var moduleUID = _a4DownloadModuleUID;
    var size      = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    if (!uid || !moduleUID) {
        Swal.fire({ icon: 'warning', text: 'Transaction data not loaded yet.' });
        return;
    }
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/transactions/downloadA4Pdf';
    form.style.display = 'none';
    var fields = { TransUID: uid, ModuleUID: moduleUID, PaperSize: size, [CsrfName]: CsrfToken };
    Object.keys(fields).forEach(function (key) {
        var input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = key;
        input.value = fields[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});
