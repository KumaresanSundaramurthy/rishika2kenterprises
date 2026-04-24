<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="bx bx-barcode me-2 text-primary"></i>Barcode &amp; QR Code Config</h5>
                            <div class="text-muted" style="font-size:.8rem;">Configure how barcodes and QR codes are generated and printed for your products.</div>
                        </div>
                        <a href="/products" class="btn btn-outline-secondary btn-sm"><i class="bx bx-package me-1"></i>Products</a>
                    </div>

                    <div class="card">
                        <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between py-2 px-4">
                            <span class="fw-semibold" style="font-size:.88rem;"><i class="bx bx-cog me-1 text-secondary"></i>Print Label Configuration</span>
                            <button class="btn btn-primary btn-sm px-4" id="btnSaveBarcodeConfig"><i class="bx bx-save me-1"></i>Save Changes</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-body-tertiary">
                                    <tr>
                                        <th style="width:180px;">Type</th>
                                        <th style="width:80px;" class="text-center">Enabled</th>
                                        <th>Settings</th>
                                        <th style="width:140px;" class="text-center">Preview</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <!-- Row 1: Barcode -->
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:36px;height:36px;background:#e8f0fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i class="bx bx-barcode text-primary" style="font-size:1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold" style="font-size:.85rem;">Barcode</div>
                                                    <div class="text-muted" style="font-size:.72rem;">Linear barcode (CODE128)</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" id="barcodeEnabled" <?php echo $BarcodeConfig->IsEnabled ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <!-- Barcode generation settings -->
                                            <div class="d-flex flex-wrap align-items-center gap-3">
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Format</label>
                                                    <select class="form-select form-select-sm" id="barcodeFormat" style="width:110px;">
                                                        <option value="CODE128" <?php echo $BarcodeConfig->Format === 'CODE128' ? 'selected' : ''; ?>>CODE128</option>
                                                        <option value="CODE39"  <?php echo $BarcodeConfig->Format === 'CODE39'  ? 'selected' : ''; ?>>CODE39</option>
                                                        <option value="EAN13"   <?php echo $BarcodeConfig->Format === 'EAN13'   ? 'selected' : ''; ?>>EAN-13</option>
                                                        <option value="UPC"     <?php echo $BarcodeConfig->Format === 'UPC'     ? 'selected' : ''; ?>>UPC</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Bar Width</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator" id="barcodeWidth" value="<?php echo (int)$BarcodeConfig->Width; ?>" min="1" max="4" style="width:70px;">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Height (px)</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator" id="barcodeHeight" value="<?php echo (int)$BarcodeConfig->Height; ?>" min="30" max="150" style="width:80px;">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Font Size</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator" id="barcodeFontSize" value="<?php echo (int)$BarcodeConfig->FontSize; ?>" min="8" max="20" style="width:70px;">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Show Value</label>
                                                    <div class="form-check form-switch mt-1">
                                                        <input class="form-check-input" type="checkbox" id="barcodeShowValue" <?php echo $BarcodeConfig->ShowValue ? 'checked' : ''; ?>>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Color</label>
                                                    <input type="color" class="form-control form-control-sm form-control-color" id="barcodeColor" value="<?php echo htmlspecialchars($BarcodeConfig->LineColor); ?>" style="width:46px;height:32px;padding:2px;">
                                                </div>
                                            </div>
                                            <!-- Label fields -->
                                            <div class="mt-3 pt-2 border-top">
                                                <div class="mb-2" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#6c757d;">Fields to Display on Label</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_ItemName" value="ItemName">
                                                        <label class="form-check-label" for="bcF_ItemName" style="font-size:.78rem;">Item Name</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_PartNumber" value="PartNumber">
                                                        <label class="form-check-label" for="bcF_PartNumber" style="font-size:.78rem;">Part Number</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_SellingPrice" value="SellingPrice">
                                                        <label class="form-check-label" for="bcF_SellingPrice" style="font-size:.78rem;">Selling Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_MRP" value="MRP">
                                                        <label class="form-check-label" for="bcF_MRP" style="font-size:.78rem;">MRP</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_PurchasePrice" value="PurchasePrice">
                                                        <label class="form-check-label" for="bcF_PurchasePrice" style="font-size:.78rem;">Purchase Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_CategoryName" value="CategoryName">
                                                        <label class="form-check-label" for="bcF_CategoryName" style="font-size:.78rem;">Category</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input bc-label-field" type="checkbox" id="bcF_HSNSACCode" value="HSNSACCode">
                                                        <label class="form-check-label" for="bcF_HSNSACCode" style="font-size:.78rem;">HSN/SAC</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <svg id="barcodePreviewSvg" style="max-width:130px;"></svg>
                                        </td>
                                    </tr>

                                    <!-- Row 2: QR Code -->
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:36px;height:36px;background:#e0f5fb;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i class="bx bx-qr text-info" style="font-size:1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold" style="font-size:.85rem;">QR Code</div>
                                                    <div class="text-muted" style="font-size:.72rem;">2D matrix code (QR)</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" id="qrEnabled" <?php echo $QRConfig->IsEnabled ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <!-- QR generation settings -->
                                            <div class="d-flex flex-wrap align-items-center gap-3">
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Size (px)</label>
                                                    <input type="number" class="form-control form-control-sm stop-incre-indicator" id="qrSize" value="<?php echo (int)$QRConfig->Size; ?>" min="60" max="300" style="width:80px;">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Error Level</label>
                                                    <select class="form-select form-select-sm" id="qrErrorLevel" style="width:90px;">
                                                        <option value="L" <?php echo $QRConfig->ErrorLevel === 'L' ? 'selected' : ''; ?>>L (7%)</option>
                                                        <option value="M" <?php echo $QRConfig->ErrorLevel === 'M' ? 'selected' : ''; ?>>M (15%)</option>
                                                        <option value="Q" <?php echo $QRConfig->ErrorLevel === 'Q' ? 'selected' : ''; ?>>Q (25%)</option>
                                                        <option value="H" <?php echo $QRConfig->ErrorLevel === 'H' ? 'selected' : ''; ?>>H (30%)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Dark Color</label>
                                                    <input type="color" class="form-control form-control-sm form-control-color" id="qrDarkColor" value="<?php echo htmlspecialchars($QRConfig->DarkColor); ?>" style="width:46px;height:32px;padding:2px;">
                                                </div>
                                                <div>
                                                    <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;">Light Color</label>
                                                    <input type="color" class="form-control form-control-sm form-control-color" id="qrLightColor" value="<?php echo htmlspecialchars($QRConfig->LightColor); ?>" style="width:46px;height:32px;padding:2px;">
                                                </div>
                                            </div>
                                            <!-- Label fields -->
                                            <div class="mt-3 pt-2 border-top">
                                                <div class="mb-2" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#6c757d;">Fields to Display on Label</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_ItemName" value="ItemName">
                                                        <label class="form-check-label" for="qrF_ItemName" style="font-size:.78rem;">Item Name</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_PartNumber" value="PartNumber">
                                                        <label class="form-check-label" for="qrF_PartNumber" style="font-size:.78rem;">Part Number</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_SellingPrice" value="SellingPrice">
                                                        <label class="form-check-label" for="qrF_SellingPrice" style="font-size:.78rem;">Selling Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_MRP" value="MRP">
                                                        <label class="form-check-label" for="qrF_MRP" style="font-size:.78rem;">MRP</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_PurchasePrice" value="PurchasePrice">
                                                        <label class="form-check-label" for="qrF_PurchasePrice" style="font-size:.78rem;">Purchase Price</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_CategoryName" value="CategoryName">
                                                        <label class="form-check-label" for="qrF_CategoryName" style="font-size:.78rem;">Category</label>
                                                    </div>
                                                    <div class="form-check form-check-inline me-0">
                                                        <input class="form-check-input qr-label-field" type="checkbox" id="qrF_HSNSACCode" value="HSNSACCode">
                                                        <label class="form-check-label" for="qrF_HSNSACCode" style="font-size:.78rem;">HSN/SAC</label>
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

    // Live preview updates on setting change
    $('#barcodeFormat, #barcodeWidth, #barcodeHeight, #barcodeFontSize, #barcodeShowValue, #barcodeColor').on('change input', function () {
        renderBarcodePreview();
    });
    $('#qrSize, #qrErrorLevel, #qrDarkColor, #qrLightColor').on('change input', function () {
        renderQRPreview();
    });

    // Load saved field config from localStorage
    _loadLabelFieldConfig();

    $('#btnSaveBarcodeConfig').on('click', function () {
        // Collect barcode fields
        var bcFields = [];
        $('.bc-label-field:checked').each(function () { bcFields.push($(this).val()); });

        // Collect QR fields
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
            format       : $('#barcodeFormat').val()    || 'CODE128',
            width        : parseInt($('#barcodeWidth').val())    || 2,
            height       : parseInt($('#barcodeHeight').val())   || 60,
            displayValue : $('#barcodeShowValue').is(':checked'),
            fontSize     : parseInt($('#barcodeFontSize').val()) || 11,
            margin       : 4,
            lineColor    : $('#barcodeColor').val()     || '#000000',
            background   : '#ffffff',
        });
        $svg.css('max-width', '130px');
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
        barcode : ['ItemName', 'PartNumber', 'SellingPrice'],
        qrcode  : ['ItemName', 'PartNumber', 'SellingPrice', 'MRP']
    };
    try {
        var cfg = JSON.parse(localStorage.getItem('r2k_bc_label_config') || '{}');
        var bcFields = (cfg.barcode && cfg.barcode.length) ? cfg.barcode : DEFAULTS.barcode;
        var qrFields = (cfg.qrcode  && cfg.qrcode.length)  ? cfg.qrcode  : DEFAULTS.qrcode;

        $('.bc-label-field').each(function () {
            $(this).prop('checked', bcFields.indexOf($(this).val()) !== -1);
        });
        $('.qr-label-field').each(function () {
            $(this).prop('checked', qrFields.indexOf($(this).val()) !== -1);
        });
    } catch (e) {
        // Use defaults: check them
        $('.bc-label-field').each(function () {
            $(this).prop('checked', DEFAULTS.barcode.indexOf($(this).val()) !== -1);
        });
        $('.qr-label-field').each(function () {
            $(this).prop('checked', DEFAULTS.qrcode.indexOf($(this).val()) !== -1);
        });
    }
}
</script>
