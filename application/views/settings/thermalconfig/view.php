<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-printer',
                    'pageIconBg'      => '#f0fdf4',
                    'pageIconColor'   => '#16a34a',
                    'pageTitle'       => $PageTitle       ?? 'Thermal Print Config',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-primary btn-sm px-3" id="btnAddThermalConfig">
                            <i class="bx bx-plus me-1"></i>Add Config
                        </button>
                    </div>

                    <div class="card">

                        <!-- Info bar -->
                        <div class="px-3 py-2 border-bottom">
                            <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;">
                                <i class="bx bx-info-circle"></i>
                                One configuration per transaction type &nbsp;&bull;&nbsp; Max <?php echo $ThermalTypeCount; ?> records
                            </span>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive text-nowrap tablecard">
                            <table class="table trans-table MainviewTable" id="ThermalConfigTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="text-center" style="width:50px;">S.No</th>
                                        <th>Transaction Type</th>
                                        <th>Paper Width</th>
                                        <th style="white-space:normal;min-width:240px;">Receipt Elements</th>
                                        <th>Font Sizes</th>
                                        <th>Last Updated</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ThermalConfigBody" class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>
            <!-- / Content wrapper -->

            <!-- ============================================================
                 Thermal Print Config Modal
            ============================================================ -->
            <div class="modal fade" id="thermalConfigModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered" style="max-height:95vh;">
                    <div class="modal-content">

                        <div class="modal-header py-3">
                            <div>
                                <h5 class="modal-title mb-0" id="thermalModalTitle"><i class="bx bx-printer me-2 text-primary"></i>Thermal Print Settings</h5>
                                <small class="text-muted" id="thermalModalSubtitle">Add a new configuration</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger ms-auto" data-bs-dismiss="modal">
                                <i class="bx bx-x me-1"></i>Close
                            </button>
                        </div>

                        <div class="modal-body p-0 d-flex" style="min-height:540px;max-height:78vh;overflow:hidden;">

                            <input type="hidden" id="HThermalConfigUID" value="0" />

                            <!-- ═══ LEFT: Settings Form ═══ -->
                            <div class="thermal-form-panel" id="thermalFormPanel">

                                <div class="d-none thermalFormAlert alert alert-danger mx-3 mt-3 mb-0 p-2" role="alert">
                                    <span class="alert-message"></span>
                                </div>

                                <!-- Transaction Type (Add mode only) -->
                                <div id="thermalTransTypeRow" class="px-3 py-3 border-bottom bg-body-tertiary">
                                    <label class="form-label fw-semibold mb-1 small">Transaction Type <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="ThermalTransType">
                                        <option value="">-- Select Transaction Type --</option>
                                    </select>
                                </div>

                                <!-- ── Receipt Elements (2-column grid) ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Receipt Elements</div>
                                <div class="row g-0 border-top border-bottom">

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-file-blank me-1 text-secondary"></i>Terms</div><div class="thermal-setting-desc">Print terms &amp; conditions on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTerms"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-buildings me-1 text-secondary"></i>Company Details</div><div class="thermal-setting-desc">Include company's details on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowCompanyDetails" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom" id="thermalItemDescCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-list-ul me-1 text-secondary"></i>Item Description</div><div class="thermal-setting-desc">Print detailed product descriptions.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowItemDescription"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom" id="thermalTaxableAmtCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-calculator me-1 text-secondary"></i>Taxable Amount</div><div class="thermal-setting-desc">Display taxable amount above tax line.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTaxableAmount"></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom" id="thermalHSNCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-barcode me-1 text-secondary"></i>Show Item HSN/SAC</div><div class="thermal-setting-desc">Show the HSN/SAC code on the receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowHSN" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom" id="thermalTaxBreakdownCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-pie-chart-alt-2 me-1 text-secondary"></i>Tax Breakdown</div><div class="thermal-setting-desc">Show CGST / SGST / IGST split.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTaxBreakdown" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-id-card me-1 text-secondary"></i>Show GSTIN</div><div class="thermal-setting-desc">Display company GSTIN on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowGSTIN" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-phone me-1 text-secondary"></i>Show Mobile</div><div class="thermal-setting-desc">Display company mobile on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowMobile" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-money me-1 text-secondary"></i>Show Cash Received</div><div class="thermal-setting-desc">Display amount received from customer.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowCashReceived" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-image me-1 text-secondary"></i>Company Logo</div><div class="thermal-setting-desc">(B&amp;W recommended) Logo on receipt.</div></div>
                                            <div class="d-flex align-items-center gap-2 mt-1 flex-shrink-0">
                                                <div class="form-check form-switch mb-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowLogo"></div>
                                                <label class="thermal-upload-btn" for="ThermalLogoUpload" title="Upload logo"><i class="bx bx-upload"></i><input type="file" id="ThermalLogoUpload" accept="image/*" class="d-none"></label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- ── Footer ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Footer</div>
                                <div class="row g-0 border-top border-bottom">
                                    <div class="col-12 p-3">
                                        <label class="form-label small fw-semibold mb-1">Footer Message</label>
                                        <p class="text-muted" style="font-size:0.73rem;margin-bottom:6px;">Printed at the bottom of the receipt.</p>
                                        <textarea class="form-control form-control-sm" id="ThermalFooterMessage" rows="3" maxlength="500" placeholder="e.g. Thank you for your business!&#10;Visit again!"></textarea>
                                    </div>
                                </div>

                                <!-- ── QR Code Options (2-column) ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">QR Code Options</div>
                                <div class="row g-0 border-top border-bottom">
                                    <div class="col-6 thermal-toggle-cell border-end">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bxl-google me-1 text-secondary"></i>Google Reviews QR</div><div class="thermal-setting-desc">Print Google Reviews QR for customer feedback.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowGoogleReviewQR"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-qr me-1 text-secondary"></i>Payment QR</div><div class="thermal-setting-desc">Show UPI/payment QR on thermal printout.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowPaymentQR" checked></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── Branding & Printer Setup ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Branding &amp; Printer Setup</div>
                                <div class="row g-0 border-top">
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Org Name Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalOrgNameFontSize" value="16" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Address / Phone / GSTIN Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalCompanyNameFontSize" value="14" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Product Info Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalProductInfoFontSize" value="12" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3">
                                        <label class="form-label small fw-semibold mb-1">Select Printer</label>
                                        <select class="form-select form-select-sm" id="ThermalPaperWidthSelect">
                                            <option value="80mm">Thermal 80mm</option>
                                            <option value="58mm">Thermal 58mm</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                            <!-- /Left Form Panel -->

                            <!-- ═══ RIGHT: Live Preview ═══ -->
                            <div class="thermal-preview-panel" id="thermalPreviewPanel">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span class="fw-semibold small"><i class="bx bx-receipt me-1 text-secondary"></i>Receipt Preview</span>
                                    <span class="badge bg-label-secondary" id="thermalPreviewWidthBadge">80mm</span>
                                </div>
                                <div class="p-2 d-flex justify-content-center overflow-auto" style="flex:1;">
                                    <div id="thermalPreviewBox"
                                        style="font-family:'Courier New',Courier,monospace;font-size:11px;background:#fff;padding:10px;width:100%;max-width:280px;border:1px dashed #ccc;min-height:300px;line-height:1.5;">
                                        <div style="text-align:center;color:#aaa;margin-top:80px;font-size:11px;">Preview will appear here</div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Right Preview Panel -->

                        </div><!-- /modal-body -->

                        <div class="modal-footer py-3">
                            <button type="button" class="btn btn-primary" id="saveThermalConfigBtn">
                                <i class="bx bx-save me-1"></i>Save Config
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Thermal Print Config Modal -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
.thermal-form-panel {
    flex: 0 0 58%;
    overflow-y: auto;
    border-right: 1px solid var(--bs-border-color);
}
.thermal-preview-panel {
    flex: 0 0 42%;
    display: flex;
    flex-direction: column;
    background: var(--bs-tertiary-bg, #f8f9fa);
    overflow: hidden;
}
.thermal-section-header {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--bs-secondary-color, #6c757d);
}
.thermal-setting-label  { font-size: 0.82rem; font-weight: 600; color: var(--bs-body-color); margin-bottom: 2px; }
.thermal-setting-desc   { font-size: 0.72rem; color: var(--bs-secondary-color, #6c757d); line-height: 1.35; }
.thermal-toggle-cell    { background: var(--bs-body-bg); }
.thermal-toggle-cell:hover { background: var(--bs-tertiary-bg, #f8f9fa); }
.thermal-switch { width: 2.4em !important; height: 1.3em !important; cursor: pointer; flex-shrink: 0; }
.thermal-upload-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border: 1.5px dashed var(--bs-border-color);
    border-radius: 6px; cursor: pointer; color: var(--bs-secondary-color, #6c757d);
    font-size: 1rem; transition: border-color 0.15s, color 0.15s;
}
.thermal-upload-btn:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
</style>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function() {
    'use strict';

    var thermalUsedTypes = <?php echo $ThermalUsedTypes  ?? '[]'; ?>;
    var thermalAllTypes  = <?php echo $ThermalTransTypes ?? '{}'; ?>;
    var thermalLoaded    = true;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    var PAYMENT_MODULE_UIDS = [110, 111];

    function togglePaymentFields(moduleUID) {
        var isPayment = PAYMENT_MODULE_UIDS.indexOf(parseInt(moduleUID, 10)) !== -1;
        $('#thermalItemDescCell, #thermalTaxableAmtCell, #thermalHSNCell, #thermalTaxBreakdownCell')
            .toggle(!isPayment);
        if (isPayment) {
            $('#ThermalShowItemDescription, #ThermalShowTaxableAmount, #ThermalShowHSN, #ThermalShowTaxBreakdown')
                .prop('checked', false);
        }
    }

    $(document).on('change', '#ThermalTransType', function () {
        togglePaymentFields($(this).val());
        updateThermalPreview();
    });

    function updateThermalPreview() {
        var transType    = $('#ThermalTransType option:selected').text().trim() || 'Transaction';
        var footerMsg    = $('#ThermalFooterMessage').val().trim() || 'Thank you for your business!';
        var paperWidth   = $('#ThermalPaperWidthSelect').val() || '80mm';
        var orgFontSize  = Math.max(10, Math.min(28, parseInt($('#ThermalOrgNameFontSize').val()) || 16));
        var coFontSize   = Math.max(9,  Math.min(22, parseInt($('#ThermalCompanyNameFontSize').val()) || 14));
        var prodFontSize = Math.max(8,  Math.min(20, parseInt($('#ThermalProductInfoFontSize').val()) || 12));

        var showCo       = $('#ThermalShowCompanyDetails').is(':checked');
        var showGSTIN    = $('#ThermalShowGSTIN').is(':checked');
        var showMobile   = $('#ThermalShowMobile').is(':checked');
        var showHSN      = $('#ThermalShowHSN').is(':checked');
        var showTax      = $('#ThermalShowTaxBreakdown').is(':checked');
        var showTaxable  = $('#ThermalShowTaxableAmount').is(':checked');
        var showCash     = $('#ThermalShowCashReceived').is(':checked');
        var showLogo     = $('#ThermalShowLogo').is(':checked');
        var showPayQR    = $('#ThermalShowPaymentQR').is(':checked');
        var showRevQR    = $('#ThermalShowGoogleReviewQR').is(':checked');
        var showTerms    = $('#ThermalShowTerms').is(':checked');
        var showItemDesc = $('#ThermalShowItemDescription').is(':checked');

        var maxWidth = paperWidth === '58mm' ? '200px' : '270px';

        var d = '';
        var hr = '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
        var flex = function(l,r,bold,small) {
            var s = small ? 'font-size:10px;' : '';
            var b = bold  ? 'font-weight:bold;' : '';
            return '<div style="display:flex;justify-content:space-between;'+s+b+'"><span>'+l+'</span><span>'+r+'</span></div>';
        };

        if (showLogo) d += '<div style="text-align:center;margin:0 0 4px"><div style="display:inline-block;border:1px solid #ccc;padding:2px 8px;font-size:9px;color:#aaa">[ LOGO ]</div></div>';

        var orgName = <?php echo json_encode($OrgPreviewData->Name ?? ''); ?>;
        d += '<div style="text-align:center;font-weight:bold;font-size:'+orgFontSize+'px">' + escHtml(orgName || 'Store Name') + '</div>';

        if (showCo) {
            var orgAddr = [<?php
                $parts = array_filter([
                    $OrgPreviewData->Line1    ?? '',
                    $OrgPreviewData->Line2    ?? '',
                    $OrgPreviewData->CityText ?? '',
                    $OrgPreviewData->StateText ?? '',
                    $OrgPreviewData->Pincode  ?? '',
                ]);
                echo json_encode(implode(', ', $parts));
            ?>][0];
            if (orgAddr) d += '<div style="text-align:center;font-size:'+coFontSize+'px">' + escHtml(orgAddr) + '</div>';
            if (showGSTIN)  d += '<div style="text-align:center;font-size:'+coFontSize+'px">GSTIN: <?php echo addslashes($OrgPreviewData->GSTIN ?? ''); ?></div>';
            if (showMobile) d += '<div style="text-align:center;font-size:'+coFontSize+'px">Ph: <?php echo addslashes($OrgPreviewData->MobileNumber ?? ''); ?></div>';
        }

        d += hr;
        d += flex('<strong>'+escHtml(transType)+'</strong>', 'EST/0001');
        d += flex('Date:', '<?php echo date("d-m-Y"); ?>');
        d += flex('Customer:', 'Sample Customer');
        d += hr;
        d += flex('<span style="font-weight:bold">Item</span>', '<span style="font-weight:bold">Amount</span>');
        d += hr;

        var taxFontSize = Math.max(6, prodFontSize - 2);
        d += '<div style="font-weight:bold;font-size:'+prodFontSize+'px">Sample Product</div>';
        if (showItemDesc) d += '<div style="font-size:'+(prodFontSize-2)+'px;font-style:italic;color:#777">Premium quality rotavator blade - heavy duty</div>';
        if (showHSN)      d += '<div style="font-size:'+prodFontSize+'px;color:#555">HSN: 8432 90 00</div>';
        var displayPrice = showTaxable ? '₹500.00' : '₹590.00';
        d += '<div style="display:flex;justify-content:space-between;font-size:'+prodFontSize+'px"><span>2 Nos × '+displayPrice+'</span><span>₹1,180.00</span></div>';
        if (showTaxable && showTax) {
            d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic"><span>CGST 9%</span><span>₹90.00</span></div>';
            d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic"><span>SGST 9%</span><span>₹90.00</span></div>';
        }
        d += hr;

        if (showTaxable) {
            d += '<div style="display:flex;justify-content:space-between;font-size:'+prodFontSize+'px"><span>Subtotal:</span><span>₹1,000.00</span></div>';
            if (showTax) {
                d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic"><span>Total Tax:</span><span>₹180.00</span></div>';
                d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic;color:#555"><span>  CGST:</span><span>₹90.00</span></div>';
                d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic;color:#555"><span>  SGST:</span><span>₹90.00</span></div>';
            }
        }
        d += '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:'+prodFontSize+'px;border-top:1px solid #000;padding-top:3px;margin-top:3px"><span>Total:</span><span>₹1,180.00</span></div>';
        if (showCash) d += '<div style="display:flex;justify-content:space-between;font-size:'+prodFontSize+'px"><span>Cash Received:</span><span>₹1,200.00</span></div>';

        d += hr;

        if (showPayQR || showRevQR) {
            d += '<div style="display:flex;justify-content:center;gap:10px;margin:4px 0">';
            if (showPayQR) d += '<div style="text-align:center"><div style="border:1px solid #000;padding:4px;font-size:9px;display:inline-block">&#9632;&#9632;<br>&#9632;&#9632;</div><div style="font-size:9px;margin-top:2px">Pay</div></div>';
            if (showRevQR) d += '<div style="text-align:center"><div style="border:1px solid #000;padding:4px;font-size:9px;display:inline-block">&#9633;&#9633;<br>&#9633;&#9633;</div><div style="font-size:9px;margin-top:2px">Review</div></div>';
            d += '</div>';
        }

        if (showTerms) {
            d += hr;
            d += '<div style="font-size:'+(prodFontSize-2)+'px;font-style:italic;color:#555">Goods once sold will not be taken back or exchanged.</div>';
        }

        var footerLines = footerMsg.split('\n');
        footerLines.forEach(function(line) {
            if (line.trim()) d += '<div style="text-align:center;font-size:'+prodFontSize+'px">'+escHtml(line)+'</div>';
        });

        $('#thermalPreviewWidthBadge').text(paperWidth);
        $('#thermalPreviewBox').html(d).css('max-width', maxWidth);
    }

    $(document).on('input change', '#thermalConfigModal input, #thermalConfigModal select, #thermalConfigModal textarea', function() {
        updateThermalPreview();
    });

    $('#thermalConfigModal').on('shown.bs.modal', function() {
        updateThermalPreview();
    });

    function loadThermalConfigList() {
        $('#ThermalConfigBody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>');
        AjaxLoading = 0;
        $.ajax({
            url: '/settings/getThermalConfigList',
            method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function(resp) {
                AjaxLoading = 1;
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (!resp.Error) {
                    thermalUsedTypes = resp.UsedTypes || [];
                    thermalAllTypes  = resp.TransTypes || {};
                    thermalLoaded    = true;
                    $('#ThermalConfigBody').html(resp.RecordHtmlData);
                    updateAddBtnState();
                    var $sel = $('#ThermalTransType').empty().append('<option value="">-- Select Transaction Type --</option>');
                    $.each(thermalAllTypes, function(moduleUID, label) {
                        $sel.append('<option value="' + moduleUID + '">' + escHtml(label) + '</option>');
                    });
                    $('[data-bs-toggle="tooltip"]').each(function() {
                        var tt = bootstrap.Tooltip.getInstance(this); if (tt) tt.dispose();
                        new bootstrap.Tooltip(this);
                    });
                }
            }
        });
    }

    function updateAddBtnState() {
        var allKeys = Object.keys(thermalAllTypes).map(Number);
        var allUsed = allKeys.length > 0 && allKeys.every(function(uid) { return thermalUsedTypes.indexOf(uid) !== -1; });
        $('#btnAddThermalConfig').prop('disabled', allUsed).attr('title', allUsed ? 'All transaction types are already configured.' : '');
    }

    // List pre-rendered server-side — no initial AJAX needed.
    updateAddBtnState();
    var $sel = $('#ThermalTransType').empty().append('<option value="">-- Select Transaction Type --</option>');
    $.each(thermalAllTypes, function(moduleUID, label) {
        $sel.append('<option value="' + moduleUID + '">' + escHtml(label) + '</option>');
    });

    $('#btnAddThermalConfig').on('click', function() {
        resetThermalModal();
        $('#ThermalTransType option').each(function() {
            var v = parseInt($(this).val(), 10);
            if (v && thermalUsedTypes.indexOf(v) !== -1) { $(this).prop('disabled', true); }
            else { $(this).prop('disabled', false); }
        });
        $('#thermalConfigModal').modal('show');
    });

    var typeLabels = thermalAllTypes;

    $(document).on('click', '.EditThermalConfig', function() {
        var cfg = $(this).data('config');
        if (!cfg) return;

        resetThermalModal();
        $('#thermalModalSubtitle').text('Editing: ' + (cfg.ModuleName || typeLabels[cfg.ModuleUID] || cfg.ModuleUID));
        $('#HThermalConfigUID').val(cfg.ThermalConfigUID);
        $('#ThermalTransType').val(cfg.ModuleUID).prop('disabled', true);
        $('#thermalTransTypeRow').hide();
        togglePaymentFields(cfg.ModuleUID);
        $('#ThermalPaperWidthSelect').val(cfg.PaperWidth || '80mm');
        $('#ThermalFooterMessage').val(cfg.FooterMessage || '');
        $('#ThermalShowTerms').prop('checked',           cfg.ShowTerms           == 1);
        $('#ThermalShowCompanyDetails').prop('checked',  cfg.ShowCompanyDetails  == 1);
        $('#ThermalShowItemDescription').prop('checked', cfg.ShowItemDescription == 1);
        $('#ThermalShowTaxableAmount').prop('checked',   cfg.ShowTaxableAmount   == 1);
        $('#ThermalShowHSN').prop('checked',             cfg.ShowHSN             == 1);
        $('#ThermalShowTaxBreakdown').prop('checked',    cfg.ShowTaxBreakdown    == 1);
        $('#ThermalShowGSTIN').prop('checked',           cfg.ShowGSTIN           == 1);
        $('#ThermalShowMobile').prop('checked',          cfg.ShowMobile          == 1);
        $('#ThermalShowCashReceived').prop('checked',    cfg.ShowCashReceived    == 1);
        $('#ThermalShowLogo').prop('checked',            cfg.ShowLogo            == 1);
        $('#ThermalShowGoogleReviewQR').prop('checked',  cfg.ShowGoogleReviewQR  == 1);
        $('#ThermalShowPaymentQR').prop('checked',       cfg.ShowPaymentQR       == 1);
        $('#ThermalOrgNameFontSize').val(cfg.OrgNameFontSize       || 16);
        $('#ThermalCompanyNameFontSize').val(cfg.CompanyNameFontSize || 14);
        $('#ThermalProductInfoFontSize').val(cfg.ProductInfoFontSize || 12);
        $('#thermalConfigModal').modal('show');
    });

    $('#saveThermalConfigBtn').on('click', function() {
        var $btn = $(this), configUID = parseInt($('#HThermalConfigUID').val()) || 0, moduleUID = parseInt($('#ThermalTransType').val()) || 0;
        if (!configUID && !moduleUID) {
            showToastNotification('Please select a transaction type.', 'error');
            return;
        }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $.ajax({
            url: '/settings/saveThermalConfig', method: 'POST',
            data: {
                ThermalConfigUID    : configUID,
                ModuleUID           : moduleUID,
                PaperWidth          : $('#ThermalPaperWidthSelect').val() || '80mm',
                FooterMessage       : $('#ThermalFooterMessage').val(),
                ShowTerms           : $('#ThermalShowTerms').is(':checked')           ? 1 : 0,
                ShowCompanyDetails  : $('#ThermalShowCompanyDetails').is(':checked')  ? 1 : 0,
                ShowItemDescription : $('#ThermalShowItemDescription').is(':checked') ? 1 : 0,
                ShowTaxableAmount   : $('#ThermalShowTaxableAmount').is(':checked')   ? 1 : 0,
                ShowHSN             : $('#ThermalShowHSN').is(':checked')             ? 1 : 0,
                ShowTaxBreakdown    : $('#ThermalShowTaxBreakdown').is(':checked')    ? 1 : 0,
                ShowGSTIN           : $('#ThermalShowGSTIN').is(':checked')           ? 1 : 0,
                ShowMobile          : $('#ThermalShowMobile').is(':checked')          ? 1 : 0,
                ShowCashReceived    : $('#ThermalShowCashReceived').is(':checked')    ? 1 : 0,
                ShowLogo            : $('#ThermalShowLogo').is(':checked')            ? 1 : 0,
                ShowGoogleReviewQR  : $('#ThermalShowGoogleReviewQR').is(':checked')  ? 1 : 0,
                ShowPaymentQR       : $('#ThermalShowPaymentQR').is(':checked')       ? 1 : 0,
                OrgNameFontSize     : $('#ThermalOrgNameFontSize').val()     || 16,
                CompanyNameFontSize : $('#ThermalCompanyNameFontSize').val() || 14,
                ProductInfoFontSize : $('#ThermalProductInfoFontSize').val() || 12,
                [CsrfName]          : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Config');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    $('#thermalConfigModal').modal('hide');
                    thermalUsedTypes = resp.UsedTypes || [];
                    thermalAllTypes  = resp.TransTypes || thermalAllTypes;
                    thermalLoaded    = true;
                    $('#ThermalConfigBody').html(resp.RecordHtmlData);
                    updateAddBtnState();
                    var $sel = $('#ThermalTransType').empty().append('<option value="">-- Select Transaction Type --</option>');
                    $.each(thermalAllTypes, function(moduleUID, label) {
                        $sel.append('<option value="' + moduleUID + '">' + escHtml(label) + '</option>');
                    });
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Config');
                showToastNotification('Server error. Please try again.', 'error');
            }
        });
    });

    $(document).on('click', '.DeleteThermalConfig', function() {
        var uid = $(this).data('uid'), typeName = $(this).data('type');
        if (!uid) return;
        Swal.fire({
            title: 'Delete ' + typeName + ' config?', text: "You won't be able to revert this!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!', cancelButtonColor: '#3085d6',
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/settings/deleteThermalConfig', method: 'POST',
                    data: { ThermalConfigUID: uid, [CsrfName]: CsrfToken },
                    success: function(resp) {
                        CsrfToken = resp.NewCsrfToken || CsrfToken;
                        if (resp.Error) { Swal.fire({ icon:'error', title:'Error', text:resp.Message }); }
                        else { loadThermalConfigList(); Swal.fire({ icon:'success', title:'Deleted', text:resp.Message, timer:1500, showConfirmButton:false }); }
                    }
                });
            }
        });
    });

    function resetThermalModal() {
        $('#HThermalConfigUID').val('0');
        $('#ThermalTransType').val('').prop('disabled', false);
        $('#thermalTransTypeRow').show();
        togglePaymentFields(0);
        $('#thermalModalTitle').html('<i class="bx bx-printer me-2 text-primary"></i>Thermal Print Settings');
        $('#thermalModalSubtitle').text('Add a new configuration');
        $('#ThermalPaperWidthSelect').val('80mm');
        $('#ThermalFooterMessage').val('');
        $('#ThermalShowTerms,#ThermalShowItemDescription,#ThermalShowTaxableAmount,#ThermalShowLogo,#ThermalShowGoogleReviewQR').prop('checked', false);
        $('#ThermalShowCompanyDetails,#ThermalShowHSN,#ThermalShowTaxBreakdown,#ThermalShowGSTIN,#ThermalShowMobile,#ThermalShowCashReceived,#ThermalShowPaymentQR').prop('checked', true);
        $('#ThermalOrgNameFontSize').val(16);
        $('#ThermalCompanyNameFontSize').val(14);
        $('#ThermalProductInfoFontSize').val(12);
        $('.thermalFormAlert').addClass('d-none');
        updateThermalPreview();
    }

    $('#thermalConfigModal').on('hidden.bs.modal', function() { resetThermalModal(); });

});
</script>
