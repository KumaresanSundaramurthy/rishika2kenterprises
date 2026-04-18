// ── A4 Print — Reusable across all transaction pages ──────────────────────

var _a4Data = null;

$(document).on('click', '.a4PrintTransaction', function () {
    var uid   = $(this).data('uid');
    var url   = $(this).data('url');
    var label = $(this).data('label') || 'Document';
    _a4Data = null;
    $('#a4ModalTitle').text(label + ' Preview');
    $('#a4PrintModal').modal('show');
    $('#a4PrintPreview').html('<div class="d-flex justify-content-center align-items-center w-100 h-100"><div class="spinner-border text-light"></div></div>');
    $.ajax({
        url   : url,
        method: 'POST',
        data  : { TransUID: uid, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error) {
                $('#a4PrintPreview').html('<div class="alert alert-danger m-3">' + _a4Esc(resp.Message) + '</div>');
                return;
            }
            _a4Data = resp;
            _a4Data._label = label;
            $('#a4PrintPreview').html(_buildA4Html(resp, $('input[name="a4PaperSize"]:checked').val() || 'A4'));
        },
        error: function () {
            $('#a4PrintPreview').html('<div class="alert alert-danger m-3">Failed to load document.</div>');
        }
    });
});

$('input[name="a4PaperSize"]').on('change', function () {
    if (_a4Data) $('#a4PrintPreview').html(_buildA4Html(_a4Data, $(this).val()));
});

$('#a4PrintBtn').on('click', function () {
    if (!_a4Data) return;
    var frame = document.getElementById('a4PrintFrame');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.id = 'a4PrintFrame';
        frame.style.display = 'none';
        document.body.appendChild(frame);
    }
    var content = _buildA4Html(_a4Data, $('input[name="a4PaperSize"]:checked').val() || 'A4', true);
    frame.contentDocument.open();
    frame.contentDocument.write(content);
    frame.contentDocument.close();
    frame.onload = function () { frame.contentWindow.print(); };
});

$('#a4DownloadBtn').on('click', function () {
    if (!_a4Data) return;
    var h      = _a4Data.Header || {};
    var num    = h.UniqueNumber || ('Doc_' + Date.now());
    var html   = _buildA4Html(_a4Data, $('input[name="a4PaperSize"]:checked').val() || 'A4', true);
    var blob   = new Blob([html], { type: 'text/html' });
    var url    = URL.createObjectURL(blob);
    var frame  = document.getElementById('_pdfDownloadFrame');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.id = '_pdfDownloadFrame';
        frame.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;';
        document.body.appendChild(frame);
    }
    frame.src = url;
    frame.onload = function () {
        frame.contentWindow.document.title = num;
        frame.contentWindow.print();
        setTimeout(function () { URL.revokeObjectURL(url); }, 3000);
    };
});

// ── Builder ────────────────────────────────────────────────────────────────

function _buildA4Html(resp, size, forPrint) {
    if (!resp) return '';
    var w   = size === 'A5' ? '148mm' : '210mm';
    var h   = resp.Header  || {};
    var org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ';
    var dec = 2;
    var label = (resp._label || h.TransType || 'Document').toUpperCase();

    var partyLabel = (label === 'PURCHASE ORDER' || label === 'PURCHASE BILL') ? 'Vendor' : 'Customer';

    var rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr>' +
            '<td style="text-align:center">' + (i + 1) + '</td>' +
            '<td>' + _a4Esc(item.ProductName) +
                (item.PartNumber ? '<br><span style="font-size:.8em;color:#888">' + _a4Esc(item.PartNumber) + '</span>' : '') +
            '</td>' +
            '<td style="text-align:center">' + _a4Esc(item.Quantity) + ' ' + (_a4Esc(item.PrimaryUnitName) || '') + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.UnitPrice  || 0).toFixed(dec) + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.NetAmount  || 0).toFixed(dec) + '</td>' +
        '</tr>';
    });

    var ps = forPrint ? '@media print{body{margin:0}.page{box-shadow:none}}' : '';

    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' +
        'body{font-family:Arial,sans-serif;font-size:12px;background:#404040}' +
        '.page{width:' + w + ';background:#fff;margin:0 auto;padding:20px;box-shadow:0 0 20px rgba(0,0,0,.5)}' +
        'table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;font-size:11px}' +
        'th{background:#f5f5f5;font-weight:bold}' + ps +
        '</style></head><body><div class="page">' +

        '<div style="display:flex;justify-content:space-between;margin-bottom:12px">' +
            '<div><strong style="font-size:14px">' + _a4Esc(org.OrgName || '') + '</strong>' +
            (org.Address   ? '<br><span style="color:#666">' + _a4Esc(org.Address) + '</span>' : '') +
            (org.GSTNumber ? '<br><span style="color:#666">GSTIN: ' + _a4Esc(org.GSTNumber) + '</span>' : '') +
            '</div>' +
            '<div style="text-align:right"><strong style="font-size:16px">' + label + '</strong><br>' +
            '<span style="color:#666">' + _a4Esc(h.UniqueNumber || '—') + '</span><br>' +
            '<span style="color:#666">Date: ' + _a4Esc(h.TransDate || '') + '</span>' +
            (h.ValidityDate ? '<br><span style="color:#666">Due/Valid: ' + _a4Esc(h.ValidityDate) + '</span>' : '') +
            '</div>' +
        '</div>' +

        '<div style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:12px">' +
            '<strong>' + partyLabel + ':</strong> ' + _a4Esc(h.PartyName || '—') +
            (h.Reference ? ' &nbsp;|&nbsp; <strong>Ref:</strong> ' + _a4Esc(h.Reference) : '') +
        '</div>' +

        '<table><thead><tr>' +
            '<th style="width:30px">#</th><th>Product</th>' +
            '<th style="width:60px;text-align:center">Qty</th>' +
            '<th style="width:90px;text-align:right">Unit Price</th>' +
            '<th style="width:90px;text-align:right">Amount</th>' +
        '</tr></thead>' +
        '<tbody>' + rows + '</tbody>' +
        '<tfoot>' +
            '<tr><td colspan="4" style="text-align:right;font-weight:bold">Sub Total</td><td style="text-align:right">' + cur + parseFloat(h.SubTotal || 0).toFixed(dec) + '</td></tr>' +
            (parseFloat(h.DiscountAmount) > 0 ? '<tr><td colspan="4" style="text-align:right;color:#c00">Discount</td><td style="text-align:right;color:#c00">- ' + cur + parseFloat(h.DiscountAmount).toFixed(dec) + '</td></tr>' : '') +
            (parseFloat(h.TaxAmount)      > 0 ? '<tr><td colspan="4" style="text-align:right">Tax</td><td style="text-align:right">' + cur + parseFloat(h.TaxAmount).toFixed(dec) + '</td></tr>' : '') +
            '<tr><td colspan="4" style="text-align:right;font-weight:bold">Net Amount</td><td style="text-align:right;font-weight:bold">' + cur + parseFloat(h.NetAmount || 0).toFixed(dec) + '</td></tr>' +
        '</tfoot></table>' +

        (h.Notes          ? '<p style="margin-top:12px;font-size:11px;color:#666"><strong>Notes:</strong> ' + _a4Esc(h.Notes) + '</p>' : '') +
        (h.TermsConditions ? '<p style="font-size:11px;color:#666"><strong>Terms & Conditions:</strong> ' + _a4Esc(h.TermsConditions) + '</p>' : '') +

    '</div></body></html>';

    return forPrint ? html : '<iframe srcdoc="' + html.replace(/"/g, '&quot;') + '" style="width:100%;height:100%;border:0;min-height:75vh"></iframe>';
}

function _a4Esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}
