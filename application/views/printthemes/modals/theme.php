<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Theme Create / Edit Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="themeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header py-2 border-bottom">
                <h6 class="modal-title mb-0" id="themeModalTitle">
                    <i class="bx bx-palette me-1"></i>Add Print Theme
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0" style="min-height:520px;">

                    <!-- LEFT: Form -->
                    <div class="col-md-5 border-end p-3 overflow-auto" style="max-height:80vh;">
                        <input type="hidden" id="ThemeConfigUID" value="0">
                        <input type="hidden" id="TemplateUID" value="0">

                        <!-- 1. Transaction Type -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                1. Transaction Type
                            </div>
                            <select class="form-select form-select-sm" id="TransactionType">
                                <option value="">— Select type —</option>
                                <?php foreach ($TransactionTypes as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-warning small d-none" id="typeUsedNote">
                                <i class="bx bx-error-circle me-1"></i>A theme already exists for this type.
                            </div>
                        </div>

                        <!-- 2. Choose Template (carousel from DB) -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                2. Choose Template
                            </div>
                            <div class="text-muted small mb-2">
                                Selected: <strong id="selectedTplName">None selected</strong>
                                <span class="text-muted ms-1">(click once to select, click again to preview)</span>
                            </div>

                            <!-- Carousel -->
                            <div style="position:relative;">
                                <button type="button" id="tplCarouselPrev"
                                        style="position:absolute;left:-12px;top:50%;transform:translateY(-50%);z-index:2;
                                               width:26px;height:26px;border-radius:50%;border:1px solid #ccc;
                                               background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.15);
                                               display:flex;align-items:center;justify-content:center;padding:0;cursor:pointer;">
                                    <i class="bx bx-chevron-left" style="font-size:1rem;color:#555;"></i>
                                </button>

                                <div id="tplCarouselTrack"
                                     style="display:flex;gap:8px;overflow:hidden;scroll-behavior:smooth;padding:2px 4px;">
                                    <!-- Items rendered by JS _renderCarousel() -->
                                </div>

                                <button type="button" id="tplCarouselNext"
                                        style="position:absolute;right:-12px;top:50%;transform:translateY(-50%);z-index:2;
                                               width:26px;height:26px;border-radius:50%;border:1px solid #ccc;
                                               background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.15);
                                               display:flex;align-items:center;justify-content:center;padding:0;cursor:pointer;">
                                    <i class="bx bx-chevron-right" style="font-size:1rem;color:#555;"></i>
                                </button>
                            </div>

                            <!-- Full-preview overlay (shown on double-click) -->
                            <div id="tplPreviewOverlay"
                                 style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;
                                        align-items:center;justify-content:center;">
                                <div style="background:#fff;border-radius:10px;padding:14px;max-width:680px;width:94%;
                                            max-height:90vh;overflow-y:auto;position:relative;
                                            box-shadow:0 8px 40px rgba(0,0,0,.4);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <div class="fw-bold" id="tplPreviewLabel">Template Preview</div>
                                        <button type="button" id="tplPreviewClose"
                                                style="background:none;border:none;font-size:1.5rem;line-height:1;cursor:pointer;color:#888;">&times;</button>
                                    </div>
                                    <div id="tplPreviewBox" style="border:1px solid #ddd;border-radius:4px;overflow:hidden;"></div>
                                    <div class="text-end mt-2">
                                        <button type="button" id="tplPreviewSelect" class="btn btn-primary btn-sm">
                                            <i class="bx bx-check me-1"></i>Use this template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Brand Colors -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                3. Brand Colors
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Primary Color</label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" id="PrimaryColorPicker" value="#1a3c6e" style="width:42px;padding:2px;">
                                        <input type="text"  class="form-control" id="PrimaryColor" value="#1a3c6e" maxlength="7" placeholder="#1a3c6e">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Accent Color</label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" id="AccentColorPicker" value="#f59e0b" style="width:42px;padding:2px;">
                                        <input type="text"  class="form-control" id="AccentColor" value="#f59e0b" maxlength="7" placeholder="#f59e0b">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Display Options -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                4. Display Options
                            </div>
                            <div class="card bg-body-secondary border-0">
                                <div class="card-body py-1 px-3">
                                    <?php
                                    $toggles = [
                                        'ShowLogo'         => ['Logo',          'Print org logo on document'],
                                        'ShowOrgAddress'   => ['Address',       'Organisation address in header'],
                                        'ShowGSTIN'        => ['GSTIN',         'Print GSTIN in header'],
                                        'ShowHSN'          => ['HSN Code',      'HSN/SAC in line items'],
                                        'ShowTaxBreakdown' => ['Tax Breakdown', 'Show CGST/SGST/IGST split'],
                                    ];
                                    foreach ($toggles as $id => [$label, $desc]):
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <div>
                                            <div class="fw-semibold small"><?php echo $label; ?></div>
                                            <div class="text-muted" style="font-size:.7rem;"><?php echo $desc; ?></div>
                                        </div>
                                        <div class="form-check form-switch mb-0 ms-2">
                                            <input class="form-check-input" type="checkbox" id="<?php echo $id; ?>" checked>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Footer Message -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                5. Footer Message
                            </div>
                            <input type="text" class="form-control form-control-sm" id="FooterText"
                                   maxlength="200" value="Thank you for your business!"
                                   placeholder="Footer message on printed document">
                        </div>

                        <!-- 6. Font -->
                        <div class="mb-2">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                6. Font
                            </div>
                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label small fw-semibold mb-1">Font Family</label>
                                    <select class="form-select form-select-sm" id="FontFamily">
                                        <optgroup label="── System Fonts (Print-safe)">
                                            <option value="Arial" selected>Arial</option>
                                            <option value="Helvetica">Helvetica</option>
                                            <option value="Verdana">Verdana</option>
                                            <option value="Tahoma">Tahoma</option>
                                            <option value="Trebuchet MS">Trebuchet MS</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Palatino Linotype">Palatino Linotype</option>
                                            <option value="Calibri">Calibri</option>
                                        </optgroup>
                                        <optgroup label="── Google Fonts">
                                            <option value="Roboto">Roboto</option>
                                            <option value="Open Sans">Open Sans</option>
                                            <option value="Lato">Lato</option>
                                            <option value="Nunito">Nunito</option>
                                            <option value="Poppins">Poppins</option>
                                            <option value="Montserrat">Montserrat</option>
                                            <option value="Source Sans Pro">Source Sans Pro</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold mb-1">Size (px)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="FontSizePx"
                                               value="11" min="8" max="20" step="1">
                                        <span class="input-group-text">px</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 px-3 py-2 border rounded bg-white" id="fontPreviewText"
                                 style="font-family:Arial;font-size:11px;color:#333;transition:all .2s;">
                                The quick brown fox — Sample Invoice Text 12345
                            </div>
                        </div>

                    </div><!-- /left col -->

                    <!-- RIGHT: Template preview image -->
                    <div class="col-md-7 p-3 bg-body-tertiary d-flex flex-column" style="max-height:80vh;overflow-y:auto;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.7px;">
                                <i class="bx bx-show me-1"></i>Template Preview
                            </div>
                            <span class="badge bg-label-primary" id="previewThemeLabel"></span>
                        </div>
                        <div id="tplLargePreviewBox" style="flex:1;display:flex;align-items:flex-start;justify-content:center;overflow:auto;">
                            <div id="tplLargePreviewImg" style="max-width:100%;text-align:center;">
                                <div class="text-muted text-center py-5" id="tplNoPreviewMsg">
                                    <i class="bx bx-image fs-1 d-block mb-2 opacity-50"></i>
                                    Select a template to see its preview
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer py-2 border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="saveThemeBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="saveThemeSpinner"></span>
                    Save Theme
                </button>
            </div>

        </div>
    </div>
</div>

