// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Html  = null;
var _a4Title = '';

$(document).on('click', '.a4PrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _a4Html = null;
    $('#a4PrintModal').modal('show');
    $('#a4PrintPreview').html('<div class="d-flex justify-content-center align-items-center w-100 h-100"><div class="spinner-border text-light"></div></div>');
    AjaxLoading = 0;
    $.ajax({
        url   : '/transactions/getTransactionDetail',
        method: 'GET',
        data  : { TransUID: uid, ModuleUID: moduleUID },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) {
                $('#a4PrintPreview').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                return;
            }
            _a4Html  = resp.PrintHtml;
            _a4Title = (resp.Header.UniqueNumber || resp.Header.TransType || 'Document');
            $('#a4ModalTitle').text((resp.Header.TransType || 'Document') + ' Preview');
            _a4ShowPreview();
        },
        error: function () {
            AjaxLoading = 1;
            $('#a4PrintPreview').html('<div class="alert alert-danger m-3">Failed to load document.</div>');
        }
    });
});

function _a4ShowPreview() {
    if (!_a4Html) return;
    $('#a4PrintPreview').html(
        '<iframe srcdoc="' + _a4Html.replace(/"/g, '&quot;') + '" style="width:100%;height:100%;border:0;min-height:75vh"></iframe>'
    );
}

// Paper size toggle — re-request with size param
$('input[name="a4PaperSize"]').on('change', function () {
    _a4ShowPreview();
});

// Print button — write HTML into hidden iframe and trigger print
$('#a4PrintBtn').on('click', function () {
    if (!_a4Html) return;
    var frame = document.getElementById('a4PrintFrame');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.id = 'a4PrintFrame';
        frame.style.display = 'none';
        document.body.appendChild(frame);
    }
    frame.contentDocument.open();
    frame.contentDocument.write(_a4Html);
    frame.contentDocument.close();
    frame.onload = function () {
        frame.contentWindow.document.title = _a4Title;
        frame.contentWindow.print();
    };
});

// Download button — same as print but via blob URL
$('#a4DownloadBtn').on('click', function () {
    if (!_a4Html) return;
    var blob  = new Blob([_a4Html], { type: 'text/html' });
    var url   = URL.createObjectURL(blob);
    var frame = document.getElementById('_pdfDownloadFrame');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.id = '_pdfDownloadFrame';
        frame.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;';
        document.body.appendChild(frame);
    }
    frame.src = url;
    frame.onload = function () {
        frame.contentWindow.document.title = _a4Title;
        frame.contentWindow.print();
        setTimeout(function () { URL.revokeObjectURL(url); }, 3000);
    };
});
