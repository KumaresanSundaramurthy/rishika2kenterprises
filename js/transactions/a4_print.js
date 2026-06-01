// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Html        = null;   // raw HTML with __COPY_LABEL__ placeholder
var _a4Title       = '';
var _a4Header      = null;   // full response header (PartyMobile, PartyName etc.)
var _a4DownloadUid       = null;
var _a4DownloadModuleUID = null;

// Paper dimensions at 96dpi (px)
// A4 portrait: 794 × 1123  (210mm × 297mm)
// A5 portrait: 559 × 794   (148mm × 210mm) — content scaled via zoom:0.704
var _paperW = { A4: 794, A5: 559 };
var _paperH = { A4: 1123, A5: 794 };

// Copy label map
var _copyLabels = {
    customer  : 'ORIGINAL FOR RECIPIENT',
    transport : 'DUPLICATE FOR TRANSPORTER',
    supplier  : 'TRIPLICATE FOR SUPPLIER'
};

// Apply selected copy label to the raw HTML
function _a4ApplyLabel(copyType) {
    if (!_a4Html) return '';
    var label = _copyLabels[copyType] || 'ORIGINAL FOR RECIPIENT';
    return _a4Html.replace(/__COPY_LABEL__/g, label);
}

// Return currently checked copy types in order
function _a4SelectedCopies() {
    var sel = [];
    $('.a4-copy-check:checked').each(function () { sel.push($(this).val()); });
    return sel.length ? sel : ['customer'];
}

// ── Trigger: click print button on any transaction row ────────────────────
$(document).on('click', '.a4PrintTransaction', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    _a4Html              = null;
    _a4Header            = null;
    _a4DownloadUid       = uid;
    _a4DownloadModuleUID = moduleUID;

    // Reset checkboxes — default Customer only
    $('.a4-copy-check').prop('checked', false);
    $('#copyCustomer').prop('checked', true);

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
            _a4Html   = resp.PrintHtml;
            _a4Header = resp.Header || null;
            _a4Title  = (_a4Header && _a4Header.UniqueNumber) ? _a4Header.UniqueNumber : 'Document';
            var transType = (_a4Header && _a4Header.TransType) ? _a4Header.TransType : 'Document';
            $('#a4ModalTitle').text(transType + ' — ' + _a4Title);
            $('#a4ModalSubtitle').text((_a4Header && _a4Header.PartyName) ? _a4Header.PartyName : 'Print / Download');
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

// ── Preview: stacks all selected copies. Add/remove pages without AJAX. ──

// A5 landscape: same width as A4 (794px) but shorter height (559px).
// Content fills full width — no scaling needed.
// _paperW/H are already set correctly at top of file.

function _a4GetDimensions() {
    var size  = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    var pw    = _paperW[size] || 794;
    var ph    = _paperH[size] || 1123;
    var sw    = $('#a4PreviewStage').width() || 860;
    var scale = Math.min(1, (sw - 48) / pw);
    return { pw: pw, ph: ph, scale: scale, size: size };
}

// zoom:0.704 reflows layout to fit A5 width (559px) — same as browser native A5 print preview
function _a4InjectA5Zoom(html) {
    var css = '<style>html,body{zoom:0.704;}</style>';
    return html.indexOf('</head>') !== -1
        ? html.replace('</head>', css + '</head>')
        : css + html;
}

function _a4LoadFrame(iframe, copy, ph, size) {
    var html = _a4ApplyLabel(copy);
    if (size === 'A5') {
        html = _a4InjectA5Zoom(html);
        html = html.replace(/@page\s*\{[^}]*\}/, '@page{size:A5;margin:0;}');
    }
    var blob    = new Blob([html], { type: 'text/html; charset=utf-8' });
    var blobUrl = URL.createObjectURL(blob);
    iframe.onload = function () {
        URL.revokeObjectURL(blobUrl);
        iframe.onload = null;
        try {
            var doc      = iframe.contentDocument || iframe.contentWindow.document;
            var contentH = Math.max(ph, doc.documentElement.scrollHeight);
            if (contentH > ph) {
                iframe.style.height = contentH + 'px';
                var box = iframe.parentElement;
                if (box) box.style.minHeight = contentH + 'px';
            }
        } catch (e) {}
    };
    iframe.src = blobUrl;
}

// Initial full render (called after AJAX or paper size change)
function _a4ShowPreview() {
    if (!_a4Html) return;
    var d = _a4GetDimensions();

    // Full rebuild needed (paper size changed or first load)
    $('#a4PreviewStage').html('<div id="a4StageWrap" style="padding:16px 0 32px;"></div>');

    _a4SelectedCopies().forEach(function (copy) {
        _a4AddCopyPage(copy, d, false);
    });
}

// Add a single copy page to the stage — instant, no AJAX
function _a4AddCopyPage(copy, d, animate) {
    if (!_a4Html) return;
    if (!d) d = _a4GetDimensions();
    var $wrap = $('#a4StageWrap');
    if (!$wrap.length) return;

    // Don't add duplicate
    if ($wrap.find('.a4-copy-page[data-copy="' + copy + '"]').length) return;

    var $page = $(
        '<div class="a4-copy-page" data-copy="' + copy + '" style="display:flex;justify-content:center;margin-top:20px;">' +
            '<div class="a4-paper-box" style="' +
                'width:' + d.pw + 'px;' +
                'min-height:' + d.ph + 'px;' +
                'transform:scale(' + d.scale + ');' +
                'transform-origin:top center;' +
                'box-shadow:0 4px 24px rgba(0,0,0,.45);' +
                'background:#fff;flex-shrink:0;' +
            '">' +
                '<iframe class="a4-copy-frame" style="width:' + d.pw + 'px;height:' + d.ph + 'px;border:0;display:block;" scrolling="no"></iframe>' +
            '</div>' +
        '</div>'
    );

    // Enforce order: customer first, transport second, supplier third
    var order = ['customer', 'transport', 'supplier'];
    var myIdx = order.indexOf(copy);
    var inserted = false;
    $wrap.find('.a4-copy-page').each(function () {
        var existIdx = order.indexOf($(this).data('copy'));
        if (myIdx < existIdx) {
            $(this).before($page);
            inserted = true;
            return false;
        }
    });
    if (!inserted) $wrap.append($page);

    // Remove top margin from first page
    $wrap.find('.a4-copy-page:first').css('margin-top', '0');
    $wrap.find('.a4-copy-page').not(':first').css('margin-top', '20px');

    // Load iframe content — no AJAX, just DOM/blob
    _a4LoadFrame($page.find('.a4-copy-frame')[0], copy, d.ph, d.size);
}

// Remove a copy page instantly
function _a4RemoveCopyPage(copy) {
    $('#a4StageWrap .a4-copy-page[data-copy="' + copy + '"]').remove();
    $('#a4StageWrap .a4-copy-page:first').css('margin-top', '0');
}

// Checkbox change — add or remove page instantly, no AJAX
$(document).on('change', '.a4-copy-check', function () {
    if (!_a4Html) return;
    var copy    = $(this).val();
    var checked = $(this).is(':checked');
    if (checked) {
        _a4AddCopyPage(copy, null, true);
    } else {
        _a4RemoveCopyPage(copy);
    }
});

// Paper size toggle — full rebuild since dimensions change
$('input[name="a4PaperSize"]').on('change', _a4ShowPreview);

// Re-render on window resize while modal is open
$(window).on('resize', function () {
    if ($('#a4PrintModal').hasClass('show') && _a4Html) _a4ShowPreview();
});

// Build combined HTML for all selected copies (each copy = separate page)
function _a4BuildPrintHtml() {
    var size   = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    var isA5   = size === 'A5';
    var copies = _a4SelectedCopies();

    function _applyAndScale(copy) {
        var html = _a4ApplyLabel(copy)
                     .replace(/@page\s*\{[^}]*\}/, '@page{size:' + (isA5 ? 'A5' : 'A4') + ';margin:0;}');
        return isA5 ? _a4InjectA5Zoom(html) : html;
    }

    if (copies.length === 1) {
        return _applyAndScale(copies[0]);
    }
    // Multiple copies — concatenate body content with page breaks
    var parts = copies.map(function (copy, idx) {
        var html      = _applyAndScale(copy);
        var bodyMatch = html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
        var content   = bodyMatch ? bodyMatch[1] : html;
        var pb = (idx < copies.length - 1) ? '<div style="page-break-after:always;"></div>' : '';
        return content + pb;
    });
    var shell = _applyAndScale(copies[0]);
    return shell.replace(/<body([^>]*)>([\s\S]*)<\/body>/i, '<body$1>' + parts.join('') + '</body>');
}

// ── Print button ──────────────────────────────────────────────────────────
$('#a4PrintBtn').on('click', function () {
    if (!_a4Html) return;
    var html    = _a4BuildPrintHtml();
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

// ── Download button ───────────────────────────────────────────────────────
$('#a4DownloadBtn').on('click', function () {
    if (!_a4DownloadUid || !_a4DownloadModuleUID) return;
    var size  = $('input[name="a4PaperSize"]:checked').val() || 'A4';
    var form  = document.createElement('form');
    form.method = 'POST';
    form.action = '/transactions/downloadA4Pdf';
    form.style.display = 'none';
    var fields = { TransUID: _a4DownloadUid, ModuleUID: _a4DownloadModuleUID, PaperSize: size, [CsrfName]: CsrfToken };
    Object.keys(fields).forEach(function (k) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
        form.appendChild(inp);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});

// ── WhatsApp button ───────────────────────────────────────────────────────
$('#a4WhatsappBtn').on('click', function () {
    if (!_a4Header) return;
    var mobile = (_a4Header.PartyMobile || '').replace(/[^0-9+]/g, '');
    var cc     = (_a4Header.PartyCountryCode || '91').replace(/[^0-9]/g, '');
    if (!mobile) { Swal.fire({ icon: 'info', text: 'No mobile number found for this customer.' }); return; }
    var waNum = mobile.startsWith('+') ? mobile.replace('+','') : (cc + mobile);
    var msg   = 'Dear ' + (_a4Header.PartyName || 'Customer') + ',\n\n'
              + 'Please find your ' + (_a4Header.TransType || 'document') + ' details:\n'
              + _a4Header.UniqueNumber + '\n\n'
              + 'Thank you for your business!';
    window.open('https://wa.me/' + waNum + '?text=' + encodeURIComponent(msg), '_blank');
});

// ── Email button ──────────────────────────────────────────────────────────
$('#a4EmailBtn').on('click', function () {
    if (!_a4Header) return;
    var orgUID     = typeof _currentOrgUID !== 'undefined' ? _currentOrgUID : 0;
    var moduleUID  = _a4DownloadModuleUID;
    var transUID   = _a4DownloadUid;
    var partyUID   = _a4Header.PartyUID   || 0;
    var partyName  = _a4Header.PartyName  || '';
    var partyEmail = _a4Header.PartyEmail || '';
    var mobile     = _a4Header.PartyMobile || '';
    var docNum     = _a4Header.UniqueNumber || '';
    var transType  = _a4Header.TransType || 'Document';

    if (!partyEmail) { Swal.fire({ icon: 'info', text: 'No email address found for this customer.' }); return; }

    AjaxLoading = 0;
    $.post('/transactions/sendTransactionEmail', {
        TransUID   : transUID,
        ModuleUID  : moduleUID,
        PartyUID   : partyUID,
        PartyType  : 'C',
        [CsrfName] : CsrfToken
    }, function (resp) {
        AjaxLoading = 1;
        if (resp && !resp.Error) {
            showToastNotification(transType + ' emailed to ' + partyEmail + ' successfully.', 'success');
        } else {
            Swal.fire({ icon: 'error', text: (resp && resp.Message) ? resp.Message : 'Failed to send email.' });
        }
    }).fail(function () {
        AjaxLoading = 1;
        Swal.fire({ icon: 'error', text: 'Failed to send email. Please try again.' });
    });
});

// ── Direct PDF download (from list page) ─────────────────────────────────
$(document).on('click', '.downloadPdfQuotation', function () {
    var uid       = $(this).data('uid');
    var moduleUID = $(this).data('module');
    if (!uid || !moduleUID) { Swal.fire({ icon: 'warning', text: 'Unable to download: missing transaction data.' }); return; }
    var form = document.createElement('form');
    form.method = 'POST'; form.action = '/transactions/downloadA4Pdf'; form.style.display = 'none';
    var fields = { TransUID: uid, ModuleUID: moduleUID, PaperSize: 'A4', [CsrfName]: CsrfToken };
    Object.keys(fields).forEach(function (k) {
        var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
        form.appendChild(inp);
    });
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
});
