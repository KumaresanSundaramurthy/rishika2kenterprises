// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Html  = null;
var _a4Title = '';

// Paper dimensions at 96dpi (px)
var _paperW = { A4: 794, A5: 559 };
var _paperH = { A4: 1123, A5: 794 };

$(document).on('click', '.a4PrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _a4Html = null;
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

    var $stage   = $('#a4PreviewStage');
    var stageW   = $stage.width() || 860;
    var scale    = Math.min(1, (stageW - 48) / pw);   // 24px padding each side
    var scaledH  = Math.round(ph * scale);

    // Paper wrapper holds the iframe at natural size, then gets scaled
    var paperHtml =
        '<div style="' +
            'display:flex;justify-content:center;' +
            'padding:24px 0 32px;' +
        '">' +
            '<div style="' +
                'width:' + pw + 'px;' +
                'height:' + ph + 'px;' +
                'transform:scale(' + scale + ');' +
                'transform-origin:top center;' +
                'box-shadow:0 4px 24px rgba(0,0,0,.45);' +
                'background:#fff;' +
                'flex-shrink:0;' +
                'overflow:hidden;' +
            '" id="a4PaperBox">' +
                '<iframe id="a4PreviewFrame" ' +
                    'style="width:' + pw + 'px;height:' + ph + 'px;border:0;display:block;" ' +
                    'scrolling="no">' +
                '</iframe>' +
            '</div>' +
        '</div>';

    // Outer wrapper height must account for scaling shrinkage
    $stage.html(
        '<div style="min-height:' + (scaledH + 56) + 'px;">' + paperHtml + '</div>'
    );

    // Write HTML into iframe
    var iframe = document.getElementById('a4PreviewFrame');
    var doc    = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(_a4Html);
    doc.close();
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

// Returns _a4Html with @page size patched to the currently selected paper size
function _a4HtmlForSize() {
    var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    return _a4Html.replace(/@page\s*\{[^}]*\}/, '@page{size:' + size + ';margin:0;}');
}

// Print button — write HTML into hidden iframe and trigger print
$('#a4PrintBtn').on('click', function () {
    if (!_a4Html) return;
    var frame = document.getElementById('a4PrintFrame');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.id = 'a4PrintFrame';
        frame.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;';
        document.body.appendChild(frame);
    }
    var printHtml = _a4HtmlForSize();
    frame.contentDocument.open();
    frame.contentDocument.write(printHtml);
    frame.contentDocument.close();
    frame.onload = function () {
        frame.contentWindow.document.title = _a4Title;
        frame.contentWindow.print();
    };
});

// Download button
$('#a4DownloadBtn').on('click', function () {
    if (!_a4Html) return;
    var blob  = new Blob([_a4HtmlForSize()], { type: 'text/html' });
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
