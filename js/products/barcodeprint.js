/* ── Barcode / QR Code print ──────────────────────────────────────────── */

var _bcData   = {};
var _bcMode   = 'barcode'; // 'barcode' | 'qrcode'
var _DEFAULT_LOGO = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';

var _BC_FIELD_LABELS = {
    ItemName      : 'Item Name',
    PartNumber    : 'Part#',
    SellingPrice  : 'Price',
    MRP           : 'MRP',
    PurchasePrice : 'Purchase Price',
    CategoryName  : 'Category',
    HSNSACCode    : 'HSN/SAC'
};

var _BC_DEFAULTS = {
    barcode : ['ItemName', 'PartNumber', 'SellingPrice'],
    qrcode  : ['ItemName', 'PartNumber', 'SellingPrice', 'MRP']
};

/* ── Field config (localStorage) ────────────────────────────────────── */
function _getLabelFields(mode) {
    try {
        var cfg = JSON.parse(localStorage.getItem('r2k_bc_label_config') || '{}');
        if (cfg[mode] && cfg[mode].length) return cfg[mode];
    } catch (e) {}
    return _BC_DEFAULTS[mode];
}

/* ── Open modal ─────────────────────────────────────────────────────── */
$(document).on('click', '.BarcodeOnlyBtn, .QROnlyBtn', function () {
    var $btn = $(this);
    _bcMode = $btn.hasClass('QROnlyBtn') ? 'qrcode' : 'barcode';

    _bcData = {
        partNumber    : $btn.data('partnumber')    || '',
        itemName      : $btn.data('itemname')      || '',
        price         : $btn.data('price')         || '',
        mrp           : $btn.data('mrp')           || '',
        purchasePrice : $btn.data('purchaseprice') || '',
        category      : $btn.data('category')      || '',
        hsnCode       : $btn.data('hsncode')       || ''
    };

    $('#pc1').prop('checked', true);
    $('#barcodeProductSubtitle').text(
        _bcData.itemName + (_bcData.partNumber ? '  ·  Part# ' + _bcData.partNumber : '')
    );

    if (_bcMode === 'barcode') {
        $('#bcModalIcon').attr('class', 'bx bx-barcode me-1 text-primary');
        $('#bcModalTitle').text('Print Barcode');
        $('#bcPrintBtnText').text('Print Barcode');
        $('#bcSection').removeClass('d-none');
        $('#qrSection').addClass('d-none');
        _renderBarcode();
    } else {
        $('#bcModalIcon').attr('class', 'bx bx-qr me-1 text-info');
        $('#bcModalTitle').text('Print QR Code');
        $('#bcPrintBtnText').text('Print QR Code');
        $('#bcSection').addClass('d-none');
        $('#qrSection').removeClass('d-none');
        _renderQR();
    }

    $('#barcodePrintModal').modal('show');
});

/* ── Render barcode ─────────────────────────────────────────────────── */
function _renderBarcode() {
    var $svg = $('#barcodeSvg');
    $svg.empty().removeAttr('style class').attr('id', 'barcodeSvg');
    try {
        JsBarcode('#barcodeSvg', _bcData.partNumber || 'NO-PART', {
            format       : 'CODE128',
            width        : 2,
            height       : 55,
            displayValue : true,
            fontSize     : 11,
            margin       : 6,
            lineColor    : '#222',
            background   : '#ffffff',
        });
        $svg.css('max-width', '100%');
    } catch (e) {
        $svg.replaceWith('<div id="barcodeSvg" class="text-danger small py-3">Cannot generate barcode for this value.</div>');
    }
}

/* ── Render QR code ─────────────────────────────────────────────────── */
function _renderQR() {
    var $div = $('#qrcodeDiv');
    $div.empty();
    new QRCode($div[0], {
        text         : _bcData.partNumber || _bcData.itemName || 'NO-DATA',
        width        : 110,
        height       : 110,
        colorDark    : '#000000',
        colorLight   : '#ffffff',
        correctLevel : QRCode.CorrectLevel.H,
    });
    setTimeout(function () {
        _overlayLogoOnQR($div[0], _getOrgLogoUrl());
    }, 100);
}

/* ── Print ──────────────────────────────────────────────────────────── */
$(document).on('click', '#btnPrintBarcode', function () {
    var copies = parseInt($('input[name="printCopies"]:checked').val()) || 1;
    if (_bcMode === 'barcode') {
        _printBarcode(copies);
    } else {
        _printQR(copies);
    }
});

function _printBarcode(copies) {
    var barcodeEl  = document.getElementById('barcodeSvg');
    var svgContent = barcodeEl ? barcodeEl.outerHTML : '';
    var fields     = _getLabelFields('barcode');
    var fieldHtml  = _buildFieldHtml(fields, 'barcode');

    var labels = '';
    for (var i = 0; i < copies; i++) {
        labels += '<div class="lbl">';
        labels += '<div class="lbl-code">' + svgContent + '</div>';
        if (fieldHtml) labels += '<div class="lbl-fields">' + fieldHtml + '</div>';
        labels += '</div>';
    }
    _openPrintWindow(labels, 'barcode');
}

function _printQR(copies) {
    var qrDiv   = document.getElementById('qrcodeDiv');
    var qrImgEl = qrDiv ? (qrDiv.querySelector('canvas') || qrDiv.querySelector('img')) : null;
    var qrSrc   = '';
    if (qrImgEl) {
        qrSrc = (qrImgEl.tagName === 'CANVAS') ? qrImgEl.toDataURL('image/png') : qrImgEl.src;
    }
    var fields   = _getLabelFields('qrcode');
    var fieldHtml = _buildFieldHtml(fields, 'qrcode');

    var labels = '';
    for (var i = 0; i < copies; i++) {
        labels += '<div class="lbl">';
        if (qrSrc) labels += '<div class="lbl-code"><img src="' + qrSrc + '" width="100" height="100"/></div>';
        if (fieldHtml) labels += '<div class="lbl-fields">' + fieldHtml + '</div>';
        labels += '</div>';
    }
    _openPrintWindow(labels, 'qrcode');
}

/* ── Build configured field text lines ─────────────────────────────── */
function _buildFieldHtml(fields, mode) {
    var dataMap = {
        ItemName      : _bcData.itemName,
        PartNumber    : _bcData.partNumber,
        SellingPrice  : _bcData.price,
        MRP           : _bcData.mrp,
        PurchasePrice : _bcData.purchasePrice,
        CategoryName  : _bcData.category,
        HSNSACCode    : _bcData.hsnCode
    };
    var html = '';
    for (var i = 0; i < fields.length; i++) {
        var key = fields[i];
        var val = dataMap[key];
        if (!val) continue;
        var isName = (key === 'ItemName');
        html += '<div class="lbl-f' + (isName ? ' lbl-name' : '') + '">'
             + (isName ? '' : '<span class="lbl-key">' + _bcEsc(_BC_FIELD_LABELS[key] || key) + ': </span>')
             + _bcEsc(val) + '</div>';
    }
    return html;
}

/* ── Open print window ──────────────────────────────────────────────── */
function _openPrintWindow(labels, mode) {
    var win = window.open('', '_blank', 'width=800,height=600');
    var isQR = (mode === 'qrcode');
    win.document.write('<!DOCTYPE html><html><head><title>Print Label</title><style>');
    win.document.write('* { margin:0; padding:0; box-sizing:border-box; }');
    win.document.write('body { font-family: Arial, sans-serif; background:#fff; padding:8px; }');
    win.document.write('.grid { display:flex; flex-wrap:wrap; gap:10px; }');
    win.document.write('.lbl { border:1px solid #bbb; border-radius:4px; padding:8px 10px; width:' + (isQR ? '140px' : '210px') + '; text-align:center; page-break-inside:avoid; }');
    win.document.write('.lbl-code { margin-bottom:4px; }');
    win.document.write('.lbl-code svg { max-width:100%; height:auto; display:block; margin:0 auto; }');
    win.document.write('.lbl-code img { display:block; margin:0 auto; }');
    win.document.write('.lbl-fields { margin-top:4px; border-top:1px solid #eee; padding-top:3px; }');
    win.document.write('.lbl-f { font-size:9px; color:#333; line-height:1.4; }');
    win.document.write('.lbl-name { font-size:10px; font-weight:bold; }');
    win.document.write('.lbl-key { color:#888; }');
    win.document.write('@media print { body { padding:0; } }');
    win.document.write('</style></head><body>');
    win.document.write('<div class="grid">' + labels + '</div>');
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function () { win.print(); }, 500);
}

/* ── Overlay org logo on QR canvas ─────────────────────────────────── */
function _overlayLogoOnQR(containerEl, logoUrl) {
    var canvas  = containerEl.querySelector('canvas');
    var visImg  = containerEl.querySelector('img');
    if (!canvas) return;
    var ctx    = canvas.getContext('2d');
    var cw     = canvas.width;
    var ch     = canvas.height;
    var lSize  = Math.round(cw * 0.22);
    var lx     = Math.round((cw - lSize) / 2);
    var ly     = Math.round((ch - lSize) / 2);
    var pad    = 3;
    var logo   = new Image();
    logo.crossOrigin = 'anonymous';
    logo.onload = function () {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(lx - pad, ly - pad, lSize + pad * 2, lSize + pad * 2);
        ctx.drawImage(logo, lx, ly, lSize, lSize);
        if (visImg) visImg.src = canvas.toDataURL('image/png');
    };
    logo.onerror = function () {};
    logo.src = logoUrl;
}

/* ── Resolve org logo URL ───────────────────────────────────────────── */
function _getOrgLogoUrl() {
    var DEFAULT = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
    try {
        var logo = JwtData && JwtData.User && JwtData.User.OrgLogo;
        if (logo) return (CDN_URL || '') + logo;
    } catch (e) {}
    return DEFAULT;
}

function _bcEsc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
