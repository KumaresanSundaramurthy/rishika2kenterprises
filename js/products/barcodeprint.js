/* ── Barcode / QR Code label print ───────────────────────────────────────
   Layout tabs: 1×1, 1×2, 1×3, 1×4, A4 8×2, A4 10×4, A4 13×5, Square
   Field config: stored in localStorage['r2k_bc_label_config']
   Org logo: overlaid on QR centre from JwtData.User.OrgLogo
   ─────────────────────────────────────────────────────────────────────── */

/* ── Layout definitions ─────────────────────────────────────────────── */
var BC_LAYOUTS = [
    { key: '1x2',     label: '1 × 2',                 cols: 2, count: 2,  previewMax: 2  },
    { key: '1x4',     label: '1 × 4',                 cols: 4, count: 4,  previewMax: 4  },
    { key: '1x1',     label: '1 × 1',                 cols: 1, count: 1,  previewMax: 1  },
    { key: '1x3',     label: '1 × 3',                 cols: 3, count: 3,  previewMax: 3  },
    { key: 'a4_8x2',  label: 'A4 (8 × 2)',            cols: 2, count: 16, previewMax: 8  },
    { key: 'a4_10x4', label: 'A4 40 Labels (10 × 4)', cols: 4, count: 40, previewMax: 12 },
    { key: 'a4_13x5', label: 'A4 65 Labels (13 × 5)', cols: 5, count: 65, previewMax: 15 },
    { key: 'square',  label: 'Square Label',           cols: 2, count: 2,  previewMax: 2, square: true },
];

/* ── State ──────────────────────────────────────────────────────────── */
var _bcMode    = 'barcode';
var _bcData    = {};
var _bcLayout  = BC_LAYOUTS[0];
var _bcSvgHtml = '';   // cached barcode SVG outerHTML (preview height)
var _bcQRSrc   = '';   // cached QR canvas dataURL with logo
var _DEFAULT_LOGO = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';

/* ── Field metadata ─────────────────────────────────────────────────── */
var _BC_FIELD_LABELS = {
    ItemName      : 'Item Name',
    PartNumber    : 'Part#',
    SellingPrice  : 'Price',
    MRP           : 'MRP',
    PurchasePrice : 'Purchase Price',
    CategoryName  : 'Category',
    HSNSACCode    : 'HSN/SAC',
    PackedOn      : 'PKD ON'
};

var _BC_DEFAULTS = {
    barcode : ['ItemName', 'MRP', 'PackedOn'],
    qrcode  : ['ItemName', 'PartNumber', 'SellingPrice', 'MRP']
};

/* ── Helpers ────────────────────────────────────────────────────────── */
function _getLabelFields(mode) {
    try {
        var cfg = JSON.parse(localStorage.getItem('r2k_bc_label_config') || '{}');
        if (cfg[mode] && cfg[mode].length) return cfg[mode];
    } catch (e) {}
    return _BC_DEFAULTS[mode];
}

function _getFieldValue(key) {
    if (key === 'PackedOn') {
        var d = new Date();
        var M = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
        return d.getDate() + '-' + M[d.getMonth()] + '-' + String(d.getFullYear()).slice(-2);
    }
    return ({
        ItemName      : _bcData.itemName,
        PartNumber    : _bcData.partNumber,
        SellingPrice  : _bcData.price,
        MRP           : _bcData.mrp,
        PurchasePrice : _bcData.purchasePrice,
        CategoryName  : _bcData.category,
        HSNSACCode    : _bcData.hsnCode
    })[key] || '';
}

function _getOrgLogoUrl() {
    var DEF = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
    try {
        var logo = JwtData && JwtData.User && JwtData.User.OrgLogo;
        if (logo) return (CDN_URL || '') + logo;
    } catch (e) {}
    return DEF;
}

function _bcEsc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Open modal ─────────────────────────────────────────────────────── */
$(document).on('click', '.BarcodeOnlyBtn, .QROnlyBtn', function () {
    var $b = $(this);
    _bcMode   = $b.hasClass('QROnlyBtn') ? 'qrcode' : 'barcode';
    _bcSvgHtml = '';
    _bcQRSrc   = '';

    _bcData = {
        uid          : $b.data('uid')           || 0,
        partNumber   : $b.data('partnumber')    || '',
        itemName     : $b.data('itemname')      || '',
        price        : $b.data('price')         || '',
        mrp          : $b.data('mrp')           || '',
        purchasePrice: $b.data('purchaseprice') || '',
        category     : $b.data('category')      || '',
        hsnCode      : $b.data('hsncode')       || ''
    };

    // Header
    if (_bcMode === 'barcode') {
        $('#bcModalIcon').attr('class', 'bx bx-barcode fs-4 text-primary');
        $('#bcModalTitle').text('Barcode');
    } else {
        $('#bcModalIcon').attr('class', 'bx bx-qr fs-4 text-info');
        $('#bcModalTitle').text('QR Code');
    }
    $('#barcodeProductSubtitle').text(
        _bcData.itemName + (_bcData.partNumber ? '  ·  Part# ' + _bcData.partNumber : '')
    );
    $('#btnEditBcProduct').data('uid', _bcData.uid);

    // Reset to 1×2 tab
    $('#bcLayoutTabs .nav-link').removeClass('active');
    $('#bcLayoutTabs .nav-link[data-layout="1x2"]').addClass('active');
    _bcLayout = BC_LAYOUTS[0];

    // Show loading
    $('#bcPreviewArea').html('<div class="text-muted small d-flex align-items-center gap-2 p-3"><i class="bx bx-loader-alt bx-spin"></i> Generating preview...</div>');
    $('#bcPreviewNote').text('');

    $('#barcodePrintModal').modal('show');

    // Generate code asset → render preview
    _generateCodeAsset(function () { _renderPreview(); });
});

/* ── Layout tab click ───────────────────────────────────────────────── */
$(document).on('click', '#bcLayoutTabs .nav-link', function (e) {
    e.preventDefault();
    $(this).closest('ul').find('.nav-link').removeClass('active');
    $(this).addClass('active');
    var key = $(this).data('layout');
    _bcLayout = BC_LAYOUTS.filter(function (l) { return l.key === key; })[0] || BC_LAYOUTS[0];
    _renderPreview();
});

/* ── Edit button ────────────────────────────────────────────────────── */
$(document).on('click', '#btnEditBcProduct', function () {
    var uid = $(this).data('uid');
    $('#barcodePrintModal').modal('hide');
    setTimeout(function () {
        $('[data-uid="' + uid + '"].EditProduct').first().trigger('click');
    }, 350);
});

/* ── Generate code asset (SVG or QR dataURL) ────────────────────────── */
function _generateCodeAsset(done) {
    if (_bcMode === 'barcode') {
        _bcSvgHtml = _makeBarcodeHtml(40);
        done();
    } else {
        _makeQRDataURL(function (src) { _bcQRSrc = src; done(); });
    }
}

/* ── Build barcode SVG HTML at given height ─────────────────────────── */
function _makeBarcodeHtml(h) {
    var id  = 'bc_tmp_' + Date.now();
    var el  = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    el.setAttribute('id', id);
    el.style.position = 'absolute';
    el.style.left     = '-9999px';
    document.body.appendChild(el);
    try {
        JsBarcode('#' + id, _bcData.partNumber || 'NO-PART', {
            format       : 'CODE128',
            width        : 1.8,
            height       : h,
            displayValue : true,
            fontSize     : 9,
            margin       : 3,
            lineColor    : '#111',
            background   : '#ffffff',
        });
    } catch (e) {}
    var html = el.outerHTML;
    document.body.removeChild(el);
    return html;
}

/* ── Generate QR dataURL with logo overlay ──────────────────────────── */
function _makeQRDataURL(done) {
    var wrap = document.createElement('div');
    wrap.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
    document.body.appendChild(wrap);
    new QRCode(wrap, {
        text         : _bcData.partNumber || _bcData.itemName || 'NO-DATA',
        width        : 110, height: 110,
        colorDark    : '#000000', colorLight: '#ffffff',
        correctLevel : QRCode.CorrectLevel.H,
    });
    setTimeout(function () {
        _overlayLogoOnQR(wrap, _getOrgLogoUrl(), function (dataUrl) {
            document.body.removeChild(wrap);
            done(dataUrl);
        });
    }, 160);
}

/* ── Render live preview ─────────────────────────────────────────────── */
function _renderPreview() {
    var layout    = _bcLayout;
    var n         = Math.min(layout.count, layout.previewMax);
    var fields    = _getLabelFields(_bcMode);

    // Label width: fit cols inside ~680px modal content area
    var areaW  = 660;
    var labelW = Math.max(80, Math.floor((areaW - layout.cols * 10) / layout.cols));
    labelW     = Math.min(labelW, 195);
    var compact = labelW < 110;

    // Build field rows HTML
    var fieldRows = _buildFieldRowsHtml(fields, compact);

    // Build code HTML
    var codeHtml = '';
    if (_bcMode === 'barcode') {
        // Inject max-width so the SVG scales down to fit the label card in the modal preview
        var previewSvg = _bcSvgHtml.replace(/^<svg /, '<svg style="max-width:100%;height:auto;display:block;" ');
        codeHtml = '<div style="width:100%;overflow:hidden;margin:2px auto;">' + previewSvg + '</div>';
    } else {
        var qSz = Math.min(Math.round(labelW * 0.55), 70);
        codeHtml = '<img src="' + _bcQRSrc + '" style="width:' + qSz + 'px;height:' + qSz + 'px;display:block;margin:3px auto;">';
    }

    // Build N label divs
    var lbls = '';
    for (var i = 0; i < n; i++) {
        var h = layout.square ? labelW + 'px' : 'auto';
        lbls += '<div style="'
            + 'width:' + labelW + 'px;'
            + (layout.square ? 'height:' + h + ';' : '')
            + 'border:1px solid #d0d5dd;border-radius:4px;'
            + 'padding:6px 8px;text-align:center;background:#fff;'
            + 'display:flex;flex-direction:column;align-items:center;justify-content:center;'
            + '">';
        lbls += fieldRows;
        lbls += codeHtml;
        lbls += '</div>';
    }

    var note = '';
    if (layout.count > layout.previewMax) {
        note = (layout.count - layout.previewMax) + ' more labels will appear on the printed sheet.';
    }

    $('#bcPreviewArea').html(lbls);
    $('#bcPreviewNote').text(note);
}

/* ── Build field rows for label ─────────────────────────────────────── */
function _buildFieldRowsHtml(fields, compact) {
    var nameSz = compact ? '8px' : '9.5px';
    var valSz  = compact ? '7px' : '8.5px';
    var html   = '';
    for (var i = 0; i < fields.length; i++) {
        var k   = fields[i];
        var val = _getFieldValue(k);
        if (!val) continue;
        var isName = k === 'ItemName';
        html += '<div style="font-size:' + (isName ? nameSz : valSz) + ';'
              + (isName ? 'font-weight:700;color:#111;' : 'color:#444;')
              + 'line-height:1.35;margin-bottom:1px;width:100%;text-align:center;">';
        if (!isName) html += '<span style="color:#999;">' + _bcEsc(_BC_FIELD_LABELS[k] || k) + ' </span>';
        html += _bcEsc(val) + '</div>';
    }
    return html;
}

/* ── Print ──────────────────────────────────────────────────────────── */
$(document).on('click', '#btnPrintBarcode', function () {
    var layout = _bcLayout;
    var fields = _getLabelFields(_bcMode);
    var count  = layout.count;

    if (_bcMode === 'barcode') {
        // Regenerate at full print height
        var pSvg = _makeBarcodeHtml(55);
        _doPrint(_buildPrintLabels(fields, count, pSvg, null), layout);
    } else {
        var qImg = '<img src="' + _bcQRSrc + '" style="width:90px;height:90px;display:block;margin:2px auto;">';
        _doPrint(_buildPrintLabels(fields, count, null, qImg), layout);
    }
});

/* ── Build print label HTML ──────────────────────────────────────────── */
function _buildPrintLabels(fields, count, svgHtml, imgHtml) {
    var fHtml = '';
    for (var i = 0; i < fields.length; i++) {
        var k   = fields[i];
        var val = _getFieldValue(k);
        if (!val) continue;
        var isName = k === 'ItemName';
        fHtml += '<div class="lbl-f' + (isName ? ' lbl-n' : '') + '">';
        if (!isName) fHtml += '<span class="lbl-k">' + _bcEsc(_BC_FIELD_LABELS[k] || k) + ' </span>';
        fHtml += _bcEsc(val) + '</div>';
    }
    var codeHtml = '<div class="lbl-c">' + (svgHtml || imgHtml || '') + '</div>';

    var out = '';
    for (var j = 0; j < count; j++) {
        out += '<div class="lbl">' + fHtml + codeHtml + '</div>';
    }
    return out;
}

/* ── Open print window ──────────────────────────────────────────────── */
function _doPrint(labelsHtml, layout) {
    var win = window.open('', '_blank', 'width=960,height=720');
    var sq  = layout.square;
    win.document.write('<!DOCTYPE html><html><head><title>Print Labels</title><style>');
    win.document.write('*{margin:0;padding:0;box-sizing:border-box;}');
    win.document.write('body{font-family:Arial,sans-serif;background:#fff;padding:4mm;}');
    win.document.write('.grid{display:grid;grid-template-columns:repeat(' + layout.cols + ',1fr);gap:3mm;}');
    win.document.write('.lbl{border:1px solid #ccc;border-radius:2px;padding:4px 6px;text-align:center;page-break-inside:avoid;'
        + (sq ? 'aspect-ratio:1/1;display:flex;flex-direction:column;align-items:center;justify-content:center;' : '') + '}');
    win.document.write('.lbl-f{font-size:8px;color:#444;line-height:1.4;}');
    win.document.write('.lbl-n{font-size:9.5px;font-weight:700;color:#111;}');
    win.document.write('.lbl-k{color:#999;}');
    win.document.write('.lbl-c{margin-top:3px;}');
    win.document.write('.lbl-c svg{max-width:100%;height:auto;display:block;margin:0 auto;}');
    win.document.write('.lbl-c img{display:block;margin:0 auto;max-width:100%;}');
    win.document.write('@media print{body{padding:0;}@page{margin:5mm;}}');
    win.document.write('</style></head><body>');
    win.document.write('<div class="grid">' + labelsHtml + '</div>');
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function () { win.print(); }, 600);
}

/* ── Logo overlay on QR canvas (with callback) ──────────────────────── */
function _overlayLogoOnQR(containerEl, logoUrl, done) {
    var canvas = containerEl.querySelector('canvas');
    var visImg = containerEl.querySelector('img');
    if (!canvas) { if (done) done(visImg ? visImg.src : ''); return; }

    var ctx   = canvas.getContext('2d');
    var cw    = canvas.width, ch = canvas.height;
    var lSize = Math.round(cw * 0.22);
    var lx    = Math.round((cw - lSize) / 2);
    var ly    = Math.round((ch - lSize) / 2);
    var pad   = 3;

    var logo  = new Image();
    logo.crossOrigin = 'anonymous';
    logo.onload = function () {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(lx - pad, ly - pad, lSize + pad * 2, lSize + pad * 2);
        ctx.drawImage(logo, lx, ly, lSize, lSize);
        var dataUrl = canvas.toDataURL('image/png');
        if (visImg) visImg.src = dataUrl;
        if (done) done(dataUrl);
    };
    logo.onerror = function () { if (done) done(visImg ? visImg.src : ''); };
    logo.src = logoUrl;
}
