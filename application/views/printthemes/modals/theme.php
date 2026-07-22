<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
.r2k-swatch {
    width: 28px; height: 28px; border-radius: 50%;
    border: 2px solid rgba(0,0,0,.15);
    cursor: pointer; padding: 0; flex-shrink: 0;
    transition: transform .12s, box-shadow .12s;
    outline: none;
}
.r2k-swatch:hover { transform: scale(1.18); }
.r2k-swatch-active { box-shadow: 0 0 0 2px #fff, 0 0 0 4px #444; transform: scale(1.1); }
</style>

<!-- ── Theme Create / Edit Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="themeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable"
         style="max-width:calc(100vw - 20px);width:calc(100vw - 20px);
                max-height:calc(100vh - 20px);height:calc(100vh - 20px);
                margin:10px auto;">
        <div class="modal-content" style="height:calc(100vh - 20px);">

            <div class="modal-header py-2 border-bottom">
                <h5 class="modal-title mb-0 d-flex align-items-center gap-2" id="themeModalTitle">
                    <i class="bx bx-palette"></i>
                    <span>Add Print Theme</span>
                </h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="saveThemeBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="saveThemeSpinner"></span>
                        <i class="bx bx-save me-1"></i>Save Theme
                    </button>
                </div>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0 h-100">

                    <!-- LEFT: Form -->
                    <div class="col-md-5 border-end p-3 overflow-auto h-100">
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

                        <!-- 3. Brand Color -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                3. Brand Color
                            </div>
                            <!-- Hidden: backend save compatibility -->
                            <input type="hidden" id="PrimaryColor" value="#1a3c6e">
                            <input type="hidden" id="AccentColor"  value="#3d6494">
                            <!-- Preset swatches -->
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="r2k-swatch r2k-swatch-active" data-color="#1a3c6e" style="background:#1a3c6e;" title="Navy Blue"></button>
                                <button type="button" class="r2k-swatch" data-color="#166534" style="background:#166534;" title="Forest Green"></button>
                                <button type="button" class="r2k-swatch" data-color="#0f766e" style="background:#0f766e;" title="Teal"></button>
                                <button type="button" class="r2k-swatch" data-color="#991b1b" style="background:#991b1b;" title="Dark Red"></button>
                                <button type="button" class="r2k-swatch" data-color="#4c1d95" style="background:#4c1d95;" title="Purple"></button>
                                <button type="button" class="r2k-swatch" data-color="#92400e" style="background:#92400e;" title="Amber"></button>
                                <button type="button" class="r2k-swatch" data-color="#1e3a5f" style="background:#1e3a5f;" title="Slate Blue"></button>
                                <button type="button" class="r2k-swatch" data-color="#1f2937" style="background:#1f2937;" title="Charcoal"></button>
                            </div>
                            <!-- Custom picker -->
                            <div class="d-flex align-items-center gap-2">
                                <span style="position:relative;display:inline-block;flex-shrink:0;">
                                    <input type="color" id="BrandColorPicker" value="#1a3c6e"
                                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                                    <span id="brandColorRing" style="display:inline-block;width:26px;height:26px;border-radius:50%;border:3px solid #1a3c6e;background:transparent;pointer-events:none;"></span>
                                </span>
                                <input type="text" class="form-control form-control-sm font-monospace" id="BrandColor"
                                       value="#1a3c6e" maxlength="7" placeholder="#rrggbb" style="max-width:110px;">
                                <span class="text-muted" style="font-size:.7rem;">Custom</span>
                            </div>
                        </div>

                        <!-- 4. Display Options -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                4. Display Options
                            </div>
                            <div class="card bg-body-secondary border-0">
                                <div class="card-body py-2 px-3">
                                    <?php
                                    $toggles = [
                                        'ShowLogo'         => ['Logo',          'Print org logo on document'],
                                        'ShowOrgAddress'   => ['Address',       'Organisation address in header'],
                                        'ShowGSTIN'        => ['GSTIN',         'Print GSTIN in header'],
                                        'ShowHSN'          => ['HSN Code',      'HSN/SAC in line items'],
                                        'ShowTaxBreakdown' => ['Tax Breakdown', 'Show CGST/SGST/IGST split'],
                                        'ShowPartyBalance' => ['Party Balance', 'Show customer\'s outstanding balance on document'],
                                        'ShowTime'         => ['Invoice Time',  'Print time alongside the document date'],
                                    ];
                                    foreach ($toggles as $id => [$label, $desc]):
                                    ?>
                                    <div class="form-check d-flex align-items-start gap-2 py-2 border-bottom mb-0">
                                        <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                               id="<?php echo $id; ?>" checked>
                                        <label class="form-check-label mb-0" for="<?php echo $id; ?>">
                                            <span class="fw-semibold small d-block"><?php echo $label; ?></span>
                                            <span class="text-muted d-block" style="font-size:.7rem;"><?php echo $desc; ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Font -->
                        <div class="mb-2">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">
                                5. Font
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
                                            <option value="Mulish">Mulish</option>
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

                    <!-- RIGHT: Live preview -->
                    <div class="col-md-7 p-0 d-flex flex-column h-100" style="overflow:hidden;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-white flex-shrink-0">
                            <div class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.7px;">
                                <i class="bx bx-show me-1"></i>Live Preview
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-label-primary small" id="previewThemeLabel">Sample Preview</span>
                                <span class="spinner-border text-muted opacity-50 d-none" id="previewSpinner" style="width:13px;height:13px;border-width:2px;"></span>
                            </div>
                        </div>
                        <div style="flex:1;overflow-y:auto;padding:16px 14px;background:#e5e7eb;">
                            <iframe id="livePreviewFrame"
                                    style="width:100%;min-height:700px;border:none;box-shadow:0 3px 24px rgba(0,0,0,.18);display:block;background:#fff;"
                                    sandbox="allow-same-origin"></iframe>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
