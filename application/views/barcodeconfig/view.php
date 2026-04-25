<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<style>
.bc-page-subtitle       { font-size: .8rem; }
.bc-card-header-text    { font-size: .88rem; }
.bc-type-icon           { width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0; }
.bc-type-icon-barcode   { background: #e8f0fe; }
.bc-type-icon-qr        { background: #e0f5fb; }
.bc-type-icon i         { font-size: 1.2rem; }
.bc-type-name           { font-size: .85rem; }
.bc-type-desc           { font-size: .72rem; }
.bc-field-label         { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.bc-check-label         { font-size: .78rem; }
.bc-section-title       { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.bc-select-format       { width: 110px; }
.bc-input-barwidth      { width: 70px; }
.bc-input-height        { width: 80px; }
.bc-input-fontsize      { width: 70px; }
.bc-input-color         { width: 46px; height: 32px; padding: 2px; }
.bc-input-qrsize        { width: 80px; }
.bc-select-errlevel     { width: 90px; }
.bc-col-type            { width: 180px; }
.bc-col-enabled         { width: 80px; }
.bc-col-preview         { width: 140px; }
.bc-preview-svg         { max-width: 130px; }
</style>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="bx bx-barcode me-2 text-primary"></i>Barcode &amp; QR Code Config</h5>
                            <div class="text-muted bc-page-subtitle">Configure how barcodes and QR codes are generated and printed for your products.</div>
                        </div>
                        <a href="/products" class="btn btn-outline-secondary btn-sm"><i class="bx bx-package me-1"></i>Products</a>
                    </div>

                    <div class="card">
                        <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between py-2 px-4">
                            <span class="fw-semibold bc-card-header-text"><i class="bx bx-cog me-1 text-secondary"></i>Print Label Configuration</span>
                            <button class="btn btn-primary btn-sm px-4" id="btnSaveBarcodeConfig"><i class="bx bx-save me-1"></i>Save Changes</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-body-tertiary">
                                    <tr>
                                        <th class="bc-col-type">Type</th>
                                        <th class="bc-col-enabled text-center">Enabled</th>
                                        <th>Settings</th>
                                        <th class="bc-col-preview text-center">Preview</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <!-- Row 1: Barcode -->
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bc-type-icon bc-type-icon-barcode d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-barcode text-primary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold bc-type-name">Barcode</div>
                                                    <div class="text-muted bc-type-desc">Linear barcode (CODE128)</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" id="barcodeEnabled" <?php echo !empty($BarcodeConfig->IsEnabled) ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex flex-wrap align-items-center gap-3">
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Format</label>
                                                    <select class="form-select form-select-sm bc-select-format" id="barcodeFormat">
                                                        <option value="CODE128" <?php echo ($BarcodeConfig->Format ?? 'CODE128') === 'CODE128' ? 'selected' : ''; ?>>CODE128</option>
                                                        <option value="CODE39"  <?php echo ($BarcodeConfig->Format ?? '') === 'CODE39'  ? 'selected' : ''; ?>>CODE39</option>
                                                        <option value="EAN13"   <?php echo ($BarcodeConfig->Format ?? '') === 'EAN13'   ? 'selected' : ''; ?>>EAN-13</option>
                                                        <option value="UPC"     <?php echo ($BarcodeConfig->Format ?? '') === 'UPC'     ? 'selected' : ''; ?>>UPC</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Bar Width</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator bc-input-barwidth" id="barcodeWidth" value="<?php echo (int)($BarcodeConfig->BarWidth ?? $BarcodeConfig->Width ?? 2); ?>" min="1" max="4">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Height (px)</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator bc-input-height" id="barcodeHeight" value="<?php echo (int)($BarcodeConfig->Height ?? 60); ?>" min="30" max="150">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Font Size</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator bc-input-fontsize" id="barcodeFontSize" value="<?php echo (int)($BarcodeConfig->FontSize ?? 11); ?>" min="8" max="20">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Show Value</label>
                                                    <div class="form-check form-switch mt-1">
                                                        <input class="form-check-input" type="checkbox" id="barcodeShowValue" <?php echo !empty($BarcodeConfig->ShowValue) ? 'checked' : ''; ?>>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Color</label>
                                                    <input type="color" class="form-control form-control-sm form-control-color bc-input-color" id="barcodeColor" value="<?php echo htmlspecialchars($BarcodeConfig->LineColor ?? '#000000'); ?>">
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-2 border-top">
                                                <div class="mb-2 bc-section-title text-muted">Fields to Display on Label</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_ItemName" value="ItemName">
                                                        <label class="form-check-label bc-check-label" for="bcF_ItemName">Item Name</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_PartNumber" value="PartNumber">
                                                        <label class="form-check-label bc-check-label" for="bcF_PartNumber">Part Number</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_SellingPrice" value="SellingPrice">
                                                        <label class="form-check-label bc-check-label" for="bcF_SellingPrice">Selling Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_MRP" value="MRP">
                                                        <label class="form-check-label bc-check-label" for="bcF_MRP">MRP</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_PurchasePrice" value="PurchasePrice">
                                                        <label class="form-check-label bc-check-label" for="bcF_PurchasePrice">Purchase Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_CategoryName" value="CategoryName">
                                                        <label class="form-check-label bc-check-label" for="bcF_CategoryName">Category</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_HSNSACCode" value="HSNSACCode">
                                                        <label class="form-check-label bc-check-label" for="bcF_HSNSACCode">HSN/SAC</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_PackedOn" value="PackedOn">
                                                        <label class="form-check-label bc-check-label" for="bcF_PackedOn">Packed On Date</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <svg id="barcodePreviewSvg" class="bc-preview-svg"></svg>
                                        </td>
                                    </tr>

                                    <!-- Row 2: QR Code -->
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bc-type-icon bc-type-icon-qr d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-qr text-info"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold bc-type-name">QR Code</div>
                                                    <div class="text-muted bc-type-desc">2D matrix code (QR)</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" id="qrEnabled" <?php echo !empty($QRConfig->IsEnabled) ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex flex-wrap align-items-center gap-3">
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Size (px)</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator bc-input-qrsize" id="qrSize" value="<?php echo (int)($QRConfig->Size ?? 100); ?>" min="60" max="300">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Error Level</label>
                                                    <select class="form-select form-select-sm bc-select-errlevel" id="qrErrorLevel">
                                                        <option value="L" <?php echo ($QRConfig->ErrorLevel ?? '') === 'L' ? 'selected' : ''; ?>>L (7%)</option>
                                                        <option value="M" <?php echo ($QRConfig->ErrorLevel ?? 'M') === 'M' ? 'selected' : ''; ?>>M (15%)</option>
                                                        <option value="Q" <?php echo ($QRConfig->ErrorLevel ?? '') === 'Q' ? 'selected' : ''; ?>>Q (25%)</option>
                                                        <option value="H" <?php echo ($QRConfig->ErrorLevel ?? '') === 'H' ? 'selected' : ''; ?>>H (30%)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Dark Color</label>
                                                    <input type="color" class="form-control form-control-sm form-control-color bc-input-color" id="qrDarkColor" value="<?php echo htmlspecialchars($QRConfig->DarkColor ?? '#000000'); ?>">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1 bc-field-label">Light Color</label>
                                                    <input type="color" class="form-control form-control-sm form-control-color bc-input-color" id="qrLightColor" value="<?php echo htmlspecialchars($QRConfig->LightColor ?? '#ffffff'); ?>">
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-2 border-top">
                                                <div class="mb-2 bc-section-title text-muted">Fields to Display on Label</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_ItemName" value="ItemName">
                                                        <label class="form-check-label bc-check-label" for="qrF_ItemName">Item Name</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_PartNumber" value="PartNumber">
                                                        <label class="form-check-label bc-check-label" for="qrF_PartNumber">Part Number</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_SellingPrice" value="SellingPrice">
                                                        <label class="form-check-label bc-check-label" for="qrF_SellingPrice">Selling Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_MRP" value="MRP">
                                                        <label class="form-check-label bc-check-label" for="qrF_MRP">MRP</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_PurchasePrice" value="PurchasePrice">
                                                        <label class="form-check-label bc-check-label" for="qrF_PurchasePrice">Purchase Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_CategoryName" value="CategoryName">
                                                        <label class="form-check-label bc-check-label" for="qrF_CategoryName">Category</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_HSNSACCode" value="HSNSACCode">
                                                        <label class="form-check-label bc-check-label" for="qrF_HSNSACCode">HSN/SAC</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_PackedOn" value="PackedOn">
                                                        <label class="form-check-label bc-check-label" for="qrF_PackedOn">Packed On Date</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div id="qrPreviewDiv" class="d-inline-block"></div>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
var _qrPreviewInstance = null;
var _previewValue      = 'SAMPLE-PART-001';

$(function () {
    'use strict';

    renderBarcodePreview();
    renderQRPreview();

    $('#barcodeFormat, #barcodeWidth, #barcodeHeight, #barcodeFontSize, #barcodeShowValue, #barcodeColor').on('change input', function () {
        renderBarcodePreview();
    });
    $('#qrSize, #qrErrorLevel, #qrDarkColor, #qrLightColor').on('change input', function () {
        renderQRPreview();
    });

    _loadLabelFieldConfig();

    $('#btnSaveBarcodeConfig').on('click', function () {
        var bcFields = [];
        $('.bc-label-field:checked').each(function () { bcFields.push($(this).val()); });

        var qrFields = [];
        $('.qr-label-field:checked').each(function () { qrFields.push($(this).val()); });

        try {
            localStorage.setItem('r2k_bc_label_config', JSON.stringify({ barcode: bcFields, qrcode: qrFields }));
        } catch (e) {}

        Swal.fire({ icon: 'success', title: 'Saved', text: 'Label field configuration saved.', timer: 1500, showConfirmButton: false });
    });

});

function renderBarcodePreview() {
    var $svg = $('#barcodePreviewSvg');
    $svg.empty().removeAttr('style').attr('id', 'barcodePreviewSvg');
    try {
        JsBarcode('#barcodePreviewSvg', _previewValue, {
            format       : $('#barcodeFormat').val()             || 'CODE128',
            width        : parseInt($('#barcodeWidth').val())    || 2,
            height       : parseInt($('#barcodeHeight').val())   || 60,
            displayValue : $('#barcodeShowValue').is(':checked'),
            fontSize     : parseInt($('#barcodeFontSize').val()) || 11,
            margin       : 4,
            lineColor    : $('#barcodeColor').val()              || '#000000',
            background   : '#ffffff',
        });
        $svg.addClass('bc-preview-svg');
    } catch (e) {
        $svg.after('<div class="text-danger small">Preview error</div>');
    }
}

function renderQRPreview() {
    var $div = $('#qrPreviewDiv');
    $div.empty();
    _qrPreviewInstance = null;
    var errMap = { L: QRCode.CorrectLevel.L, M: QRCode.CorrectLevel.M, Q: QRCode.CorrectLevel.Q, H: QRCode.CorrectLevel.H };
    var size   = parseInt($('#qrSize').val()) || 100;
    var dispSz = Math.min(size, 90);
    _qrPreviewInstance = new QRCode($div[0], {
        text         : _previewValue,
        width        : dispSz,
        height       : dispSz,
        colorDark    : $('#qrDarkColor').val()  || '#000000',
        colorLight   : $('#qrLightColor').val() || '#ffffff',
        correctLevel : errMap[$('#qrErrorLevel').val()] || QRCode.CorrectLevel.H,
    });
    setTimeout(function () { _overlayLogoOnQR($div[0], _getOrgLogoUrl()); }, 100);
}

function _overlayLogoOnQR(containerEl, logoUrl) {
    var canvas = containerEl.querySelector('canvas');
    var visImg = containerEl.querySelector('img');
    if (!canvas) return;
    var ctx   = canvas.getContext('2d');
    var cw    = canvas.width;
    var ch    = canvas.height;
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
        if (visImg) visImg.src = canvas.toDataURL('image/png');
    };
    logo.onerror = function () {};
    logo.src = logoUrl;
}

function _getOrgLogoUrl() {
    var DEFAULT = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
    try {
        var logo = JwtData && JwtData.User && JwtData.User.OrgLogo;
        if (logo) return (CDN_URL || '') + logo;
    } catch (e) {}
    return DEFAULT;
}

function _loadLabelFieldConfig() {
    var DEFAULTS = {
        barcode : ['ItemName', 'MRP', 'PackedOn'],
        qrcode  : ['ItemName', 'PartNumber', 'SellingPrice', 'MRP']
    };
    try {
        var cfg      = JSON.parse(localStorage.getItem('r2k_bc_label_config') || '{}');
        var bcFields = (cfg.barcode && cfg.barcode.length) ? cfg.barcode : DEFAULTS.barcode;
        var qrFields = (cfg.qrcode  && cfg.qrcode.length)  ? cfg.qrcode  : DEFAULTS.qrcode;
        $('.bc-label-field').each(function () { $(this).prop('checked', bcFields.indexOf($(this).val()) !== -1); });
        $('.qr-label-field').each(function () { $(this).prop('checked', qrFields.indexOf($(this).val()) !== -1); });
    } catch (e) {
        $('.bc-label-field').each(function () { $(this).prop('checked', DEFAULTS.barcode.indexOf($(this).val()) !== -1); });
        $('.qr-label-field').each(function () { $(this).prop('checked', DEFAULTS.qrcode.indexOf($(this).val()) !== -1); });
    }
}
</script>
