<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <a href="/quotations" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Quotations
                        </a>
                        <h5 class="mb-0">Thermal Print Configuration</h5>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-xl-8 col-lg-10">

                            <div class="row g-4">

                                <!-- Config Form -->
                                <div class="col-md-7">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="bx bx-printer me-2 text-primary"></i>POS Printer Settings</h6>
                                        </div>
                                        <div class="card-body">

                                            <?php
                                            $cfg = $ThermalConfig;
                                            function tcVal($cfg, $field, $default = '') {
                                                return $cfg ? htmlspecialchars($cfg->$field ?? $default) : $default;
                                            }
                                            function tcCheck($cfg, $field, $default = 1) {
                                                if (!$cfg) return $default ? 'checked' : '';
                                                return ($cfg->$field ?? $default) ? 'checked' : '';
                                            }
                                            ?>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Paper Width</label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="PaperWidth" id="pw80" value="80mm" <?php echo tcVal($cfg, 'PaperWidth', '80mm') === '80mm' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="pw80">80mm (Standard POS)</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="PaperWidth" id="pw58" value="58mm" <?php echo tcVal($cfg, 'PaperWidth', '80mm') === '58mm' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="pw58">58mm (Mini POS)</label>
                                                    </div>
                                                </div>
                                                <div class="form-text text-muted">Match this to your physical printer paper width.</div>
                                            </div>

                                            <hr/>

                                            <div class="mb-3">
                                                <label for="HeaderLine1" class="form-label fw-semibold">Header Line 1 <span class="text-muted fw-normal">(Store / Brand Name)</span></label>
                                                <input type="text" class="form-control" id="HeaderLine1" name="HeaderLine1" maxlength="100"
                                                       placeholder="Auto-filled from org name if empty"
                                                       value="<?php echo tcVal($cfg, 'HeaderLine1'); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="HeaderLine2" class="form-label fw-semibold">Header Line 2 <span class="text-muted fw-normal">(Tagline / Address Line)</span></label>
                                                <input type="text" class="form-control" id="HeaderLine2" name="HeaderLine2" maxlength="100"
                                                       placeholder="Optional"
                                                       value="<?php echo tcVal($cfg, 'HeaderLine2'); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="HeaderLine3" class="form-label fw-semibold">Header Line 3 <span class="text-muted fw-normal">(City / State / PIN)</span></label>
                                                <input type="text" class="form-control" id="HeaderLine3" name="HeaderLine3" maxlength="100"
                                                       placeholder="Auto-filled from org address if empty"
                                                       value="<?php echo tcVal($cfg, 'HeaderLine3'); ?>">
                                            </div>

                                            <hr/>

                                            <label class="form-label fw-semibold">Display Options</label>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="ShowGSTIN" name="ShowGSTIN" value="1" <?php echo tcCheck($cfg, 'ShowGSTIN', 1); ?>>
                                                        <label class="form-check-label" for="ShowGSTIN">Show GSTIN</label>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="ShowMobile" name="ShowMobile" value="1" <?php echo tcCheck($cfg, 'ShowMobile', 1); ?>>
                                                        <label class="form-check-label" for="ShowMobile">Show Mobile</label>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="ShowHSN" name="ShowHSN" value="1" <?php echo tcCheck($cfg, 'ShowHSN', 1); ?>>
                                                        <label class="form-check-label" for="ShowHSN">Show HSN Code</label>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="ShowTaxBreakdown" name="ShowTaxBreakdown" value="1" <?php echo tcCheck($cfg, 'ShowTaxBreakdown', 1); ?>>
                                                        <label class="form-check-label" for="ShowTaxBreakdown">Show Tax Breakdown</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="FooterMessage" class="form-label fw-semibold">Footer Message</label>
                                                <input type="text" class="form-control" id="FooterMessage" name="FooterMessage" maxlength="200"
                                                       placeholder="e.g. Thank you for your business!"
                                                       value="<?php echo tcVal($cfg, 'FooterMessage', 'Thank you for your business!'); ?>">
                                            </div>

                                            <div class="d-flex gap-2 mt-3">
                                                <button type="button" class="btn btn-primary" id="saveThermalConfigBtn">
                                                    <i class="bx bx-save me-1"></i>Save Settings
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" id="previewThermalBtn">
                                                    <i class="bx bx-show me-1"></i>Preview Receipt
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Live Preview -->
                                <div class="col-md-5">
                                    <div class="card h-100">
                                        <div class="card-header d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0"><i class="bx bx-receipt me-1 text-secondary"></i>Receipt Preview</h6>
                                            <span class="badge bg-label-secondary" id="paperWidthBadge"><?php echo tcVal($cfg, 'PaperWidth', '80mm'); ?></span>
                                        </div>
                                        <div class="card-body p-2 bg-light d-flex justify-content-center">
                                            <div id="thermalPreviewBox" style="font-family:'Courier New',Courier,monospace;font-size:11px;background:#fff;padding:8px;width:100%;max-width:300px;border:1px dashed #ccc;min-height:260px;">
                                                <?php
                                                $h1 = !empty($cfg->HeaderLine1) ? htmlspecialchars($cfg->HeaderLine1) : '<em class="text-muted">Store Name</em>';
                                                $h2 = !empty($cfg->HeaderLine2) ? htmlspecialchars($cfg->HeaderLine2) : '';
                                                $h3 = !empty($cfg->HeaderLine3) ? htmlspecialchars($cfg->HeaderLine3) : '';
                                                $fm = !empty($cfg->FooterMessage) ? htmlspecialchars($cfg->FooterMessage) : 'Thank you for your business!';
                                                ?>
                                                <div style="text-align:center;font-weight:bold;font-size:13px"><?php echo $h1; ?></div>
                                                <?php if ($h2): ?><div style="text-align:center;font-size:11px"><?php echo $h2; ?></div><?php endif; ?>
                                                <?php if ($h3): ?><div style="text-align:center;font-size:11px"><?php echo $h3; ?></div><?php endif; ?>
                                                <hr style="border:none;border-top:1px dashed #000;margin:4px 0">
                                                <div style="display:flex;justify-content:space-between"><span style="font-weight:bold">Quotation</span><span>EST/001</span></div>
                                                <div style="display:flex;justify-content:space-between"><span>Date:</span><span><?php echo date('d M Y'); ?></span></div>
                                                <div style="display:flex;justify-content:space-between"><span>Customer:</span><span>Sample Customer</span></div>
                                                <hr style="border:none;border-top:1px dashed #000;margin:4px 0">
                                                <div style="display:flex;justify-content:space-between;font-weight:bold"><span>Item</span><span>Amount</span></div>
                                                <hr style="border:none;border-top:1px dashed #000;margin:4px 0">
                                                <div style="font-weight:bold">Sample Product</div>
                                                <div style="display:flex;justify-content:space-between;font-size:11px"><span>2(PCS) x 500.00</span><span>1000.00</span></div>
                                                <div style="display:flex;justify-content:space-between;font-size:11px;color:#555"><span>CGST 9% 90.00</span><span>SGST 9% 90.00</span></div>
                                                <hr style="border:none;border-top:1px dashed #000;margin:4px 0">
                                                <div style="display:flex;justify-content:space-between"><span>Subtotal:</span><span>&#8377;1000.00</span></div>
                                                <div style="display:flex;justify-content:space-between"><span>Total Tax:</span><span>&#8377;180.00</span></div>
                                                <div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px solid #000;padding-top:2px;margin-top:2px"><span>Total Amount:</span><span>&#8377;1,180.00</span></div>
                                                <hr style="border:none;border-top:1px dashed #000;margin:4px 0">
                                                <div style="text-align:center;font-size:11px"><?php echo $fm; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /row -->

                            <!-- How to connect section -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-info-circle me-2 text-info"></i>How to Connect Your POS Printer</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="d-flex gap-2">
                                                <div class="badge bg-label-primary rounded-pill" style="height:24px;min-width:24px;display:flex;align-items:center;justify-content:center">1</div>
                                                <div>
                                                    <div class="fw-semibold small">USB Thermal Printer</div>
                                                    <div class="text-muted small">Connect via USB. When you click Print, your browser will show the print dialog — select your thermal printer from the list.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex gap-2">
                                                <div class="badge bg-label-success rounded-pill" style="height:24px;min-width:24px;display:flex;align-items:center;justify-content:center">2</div>
                                                <div>
                                                    <div class="fw-semibold small">Set as Default Printer</div>
                                                    <div class="text-muted small">Go to Windows Settings → Printers & Scanners → set your thermal printer as default so it auto-selects on print.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex gap-2">
                                                <div class="badge bg-label-warning rounded-pill" style="height:24px;min-width:24px;display:flex;align-items:center;justify-content:center">3</div>
                                                <div>
                                                    <div class="fw-semibold small">Browser Print Settings</div>
                                                    <div class="text-muted small">In the print dialog set Margins → None, disable headers/footers, and set paper size to match your roll width (58mm or 80mm).</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /col -->
                    </div><!-- /row -->

                </div>
            </div>
        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
$(function () {
    'use strict'

    // Paper width badge live update
    $('input[name="PaperWidth"]').on('change', function () {
        $('#paperWidthBadge').text($(this).val());
    });

    // Save
    $('#saveThermalConfigBtn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var showGSTIN   = $('#ShowGSTIN').is(':checked') ? 1 : 0;
        var showMobile  = $('#ShowMobile').is(':checked') ? 1 : 0;
        var showHSN     = $('#ShowHSN').is(':checked') ? 1 : 0;
        var showTax     = $('#ShowTaxBreakdown').is(':checked') ? 1 : 0;

        $.ajax({
            url   : '/quotations/saveThermalPrintConfig',
            method: 'POST',
            data  : {
                PaperWidth      : $('input[name="PaperWidth"]:checked').val() || '80mm',
                HeaderLine1     : $('#HeaderLine1').val(),
                HeaderLine2     : $('#HeaderLine2').val(),
                HeaderLine3     : $('#HeaderLine3').val(),
                ShowGSTIN       : showGSTIN,
                ShowMobile      : showMobile,
                ShowHSN         : showHSN,
                ShowTaxBreakdown: showTax,
                FooterMessage   : $('#FooterMessage').val(),
                [CsrfName]      : CsrfToken,
            },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.Message });
                } else {
                    Swal.fire({ icon: 'success', title: 'Saved', text: resp.Message, timer: 1500, showConfirmButton: false });
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
                Swal.fire({ icon: 'error', text: 'Server error. Please try again.' });
            }
        });
    });

    // Live preview update when fields change
    function updatePreviewHeader() {
        var h1 = $('#HeaderLine1').val().trim() || '<em style="color:#aaa">Store Name</em>';
        var h2 = $('#HeaderLine2').val().trim();
        var h3 = $('#HeaderLine3').val().trim();
        var fm = $('#FooterMessage').val().trim() || 'Thank you for your business!';
        var box = $('#thermalPreviewBox');

        box.find('div:first').html('<span style="font-weight:bold;font-size:13px">' + h1 + '</span>');
        box.find('div:nth-child(2)').html(h2 || '');
        box.find('div:nth-child(3)').html(h3 || '');
        box.find('.preview-footer').text(fm);
    }

    // Simple preview button — print sample
    $('#previewThermalBtn').on('click', function () {
        var paperWidth = $('input[name="PaperWidth"]:checked').val() || '80mm';
        var h1 = $('#HeaderLine1').val().trim() || 'Your Store Name';
        var h2 = $('#HeaderLine2').val().trim();
        var h3 = $('#HeaderLine3').val().trim();
        var fm = $('#FooterMessage').val().trim() || 'Thank you for your business!';
        var showGSTIN  = $('#ShowGSTIN').is(':checked');
        var showTax    = $('#ShowTaxBreakdown').is(':checked');

        var html = '<div style="text-align:center;font-weight:bold;font-size:14px">' + h1 + '</div>';
        if (h2) html += '<div style="text-align:center;font-size:12px">' + h2 + '</div>';
        if (h3) html += '<div style="text-align:center;font-size:12px">' + h3 + '</div>';
        html += '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
        html += '<div style="display:flex;justify-content:space-between"><strong>Quotation</strong><span>EST/001</span></div>';
        html += '<div style="display:flex;justify-content:space-between"><span>Date:</span><span><?php echo date('d M Y'); ?></span></div>';
        html += '<div style="display:flex;justify-content:space-between"><span>Customer:</span><span>Sample Customer</span></div>';
        html += '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
        html += '<div style="font-weight:bold">Sample Product</div>';
        html += '<div style="display:flex;justify-content:space-between;font-size:11px"><span>2(PCS) x 500.00</span><span>1000.00</span></div>';
        if (showTax) html += '<div style="display:flex;justify-content:space-between;font-size:11px;color:#555"><span>CGST 9% 90.00</span><span>SGST 9% 90.00</span></div>';
        html += '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
        html += '<div style="display:flex;justify-content:space-between"><span>Subtotal:</span><span>&#8377;1000.00</span></div>';
        if (showTax) html += '<div style="display:flex;justify-content:space-between"><span>Total Tax:</span><span>&#8377;180.00</span></div>';
        html += '<div style="display:flex;justify-content:space-between;font-weight:bold;border-top:1px solid #000;padding-top:2px;margin-top:2px"><span>Total Amount:</span><span>&#8377;1,180.00</span></div>';
        html += '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
        html += '<div style="text-align:center;font-size:11px">' + fm + '</div>';

        var win = window.open('', '_blank', 'width=400,height=700');
        win.document.write(
            '<!DOCTYPE html><html><head><title>Thermal Preview</title>' +
            '<style>* { margin:0; padding:0; box-sizing:border-box; } body { font-family:"Courier New",Courier,monospace; font-size:12px; width:' + paperWidth + '; padding:4px; } @media print { @page { margin:0; size:' + paperWidth + ' auto; } body { width:' + paperWidth + '; } }</style>' +
            '</head><body>' + html + '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function () { win.print(); }, 300);
    });

});
</script>
