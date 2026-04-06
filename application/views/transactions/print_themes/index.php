<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Page Title -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <a href="/quotations" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back
                            </a>
                            <h5 class="mb-0"><i class="bx bx-palette me-2 text-primary"></i>Print Themes</h5>
                        </div>
                        <?php if (count($UsedTypes) < count($TransactionTypes)): ?>
                        <button class="btn btn-primary btn-sm px-3" id="addThemeBtn">
                            <i class="bx bx-plus me-1"></i>Add Theme
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($Configs)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-palette" style="font-size:3rem; color:#c0cfe0;"></i>
                            <div class="mt-2 text-muted">No print themes configured yet.</div>
                            <div class="small text-muted mb-3">Set up a theme for each transaction type to customise your A4 print layout.</div>
                            <button class="btn btn-primary btn-sm px-4" id="addThemeBtnEmpty">
                                <i class="bx bx-plus me-1"></i>Add First Theme
                            </button>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="row g-3" id="themeCardContainer">
                        <?php foreach ($Configs as $cfg): ?>
                        <?php
                            $themeInfo = $Themes[$cfg->ThemeKey] ?? ['label' => $cfg->ThemeKey, 'desc' => ''];
                            $typeLabel = $TransactionTypes[$cfg->TransactionType] ?? $cfg->TransactionType;
                            $primary   = htmlspecialchars($cfg->PrimaryColor ?? '#1a3c6e');
                            $accent    = htmlspecialchars($cfg->AccentColor  ?? '#f59e0b');
                        ?>
                        <div class="col-xl-4 col-lg-6 col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <!-- Mini layout thumbnail -->
                                <div style="padding:10px 10px 0; background:#f4f5f7; border-radius:8px 8px 0 0;">
                                    <div class="theme-thumb" data-key="<?php echo htmlspecialchars($cfg->ThemeKey); ?>" data-primary="<?php echo $primary; ?>" data-accent="<?php echo $accent; ?>" style="width:100%; height:110px; background:#fff; border:1px solid #e0e0e0; border-radius:4px; overflow:hidden; font-size:0; position:relative;"></div>
                                </div>
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-label-primary mb-1"><?php echo htmlspecialchars($typeLabel); ?></span>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($themeInfo['label']); ?> Theme</div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($themeInfo['desc']); ?></div>
                                        </div>
                                        <div class="d-flex gap-1 mt-1">
                                            <div title="Primary" style="width:18px;height:18px;border-radius:50%;background:<?php echo $primary; ?>;border:1px solid #ddd;"></div>
                                            <div title="Accent"  style="width:18px;height:18px;border-radius:50%;background:<?php echo $accent; ?>;border:1px solid #ddd;"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <?php if ($cfg->ShowLogo):         ?><span class="badge bg-label-secondary" style="font-size:.65rem;">Logo</span><?php endif; ?>
                                        <?php if ($cfg->ShowOrgAddress):   ?><span class="badge bg-label-secondary" style="font-size:.65rem;">Address</span><?php endif; ?>
                                        <?php if ($cfg->ShowGSTIN):        ?><span class="badge bg-label-secondary" style="font-size:.65rem;">GSTIN</span><?php endif; ?>
                                        <?php if ($cfg->ShowHSN):          ?><span class="badge bg-label-secondary" style="font-size:.65rem;">HSN</span><?php endif; ?>
                                        <?php if ($cfg->ShowTaxBreakdown): ?><span class="badge bg-label-secondary" style="font-size:.65rem;">Tax Split</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-top pt-2 pb-2 px-3 d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-primary editThemeBtn" data-uid="<?php echo (int) $cfg->ThemeConfigUID; ?>"><i class="bx bx-edit me-1"></i>Edit</button>
                                    <button class="btn btn-sm btn-outline-danger deleteThemeBtn"
                                            data-uid="<?php echo (int) $cfg->ThemeConfigUID; ?>"
                                            data-label="<?php echo htmlspecialchars($typeLabel . ' — ' . $themeInfo['label']); ?>">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ── Add / Edit Theme Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="themeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h6 class="modal-title fw-bold text-primary mb-0" id="themeModalTitle">
                    <i class="bx bx-palette me-1"></i>Add Print Theme
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">

                    <!-- LEFT: Template Picker + settings ──────────────── -->
                    <div class="col-lg-5 border-end p-4" style="overflow-y:auto; max-height:82vh;">
                        <input type="hidden" id="ThemeConfigUID" value="0">

                        <!-- Step 1: Transaction Type -->
                        <div class="mb-4">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">1. Transaction Type</div>
                            <select class="form-select" id="TransactionType">
                                <option value="">— Select type —</option>
                                <?php foreach ($TransactionTypes as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-warning small d-none" id="typeUsedNote">
                                <i class="bx bx-error-circle me-1"></i>A theme already exists for this type.
                            </div>
                        </div>

                        <!-- Step 2: Template Style -->
                        <div class="mb-4">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">2. Choose Template</div>

                            <!-- Carousel wrapper -->
                            <div style="position:relative;">
                                <!-- Left arrow -->
                                <button type="button" id="carouselPrev"
                                        style="position:absolute;left:-14px;top:50%;transform:translateY(-50%);z-index:2;
                                               width:28px;height:28px;border-radius:50%;border:1px solid #ccc;
                                               background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.18);
                                               display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;">
                                    <i class="bx bx-chevron-left" style="font-size:1.1rem;color:#555;"></i>
                                </button>

                                <!-- Scrollable strip -->
                                <div id="templateCarouselTrack"
                                     style="display:flex;gap:10px;overflow:hidden;scroll-behavior:smooth;padding:4px 2px;">
                                    <?php foreach ($Themes as $key => $info): ?>
                                    <div class="template-carousel-item flex-shrink-0"
                                         data-key="<?php echo htmlspecialchars($key); ?>"
                                         style="width:130px;cursor:pointer;border-radius:6px;overflow:hidden;
                                                border:2px solid <?php echo $key === 'classic' ? '#0d6efd' : '#dee2e6'; ?>;
                                                transition:border-color .15s,box-shadow .15s;">
                                        <input type="radio" name="ThemeKey"
                                               value="<?php echo htmlspecialchars($key); ?>"
                                               class="d-none"
                                               <?php echo $key === 'classic' ? 'checked' : ''; ?>>
                                        <!-- CSS mini-thumbnail -->
                                        <div class="theme-modal-thumb"
                                             data-key="<?php echo htmlspecialchars($key); ?>"
                                             style="height:90px;background:#f8f8f8;pointer-events:none;"></div>
                                        <!-- Label -->
                                        <div style="background:#fff;padding:4px 6px;border-top:1px solid #eee;">
                                            <div style="font-size:.72rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                <?php echo htmlspecialchars($info['label']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Right arrow -->
                                <button type="button" id="carouselNext"
                                        style="position:absolute;right:-14px;top:50%;transform:translateY(-50%);z-index:2;
                                               width:28px;height:28px;border-radius:50%;border:1px solid #ccc;
                                               background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.18);
                                               display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0;">
                                    <i class="bx bx-chevron-right" style="font-size:1.1rem;color:#555;"></i>
                                </button>
                            </div>

                            <!-- Full-preview modal (click on thumbnail) -->
                            <div id="tplPreviewOverlay"
                                 style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;
                                        align-items:center;justify-content:center;">
                                <div style="background:#fff;border-radius:10px;padding:14px;max-width:600px;width:92%;
                                            max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.35);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <div style="font-weight:700;font-size:.9rem;" id="tplPreviewLabel">Template Preview</div>
                                        <button type="button" id="tplPreviewClose"
                                                style="background:none;border:none;font-size:1.4rem;line-height:1;cursor:pointer;color:#888;">&times;</button>
                                    </div>
                                    <div id="tplPreviewBox" style="border:1px solid #ddd;border-radius:4px;overflow:hidden;"></div>
                                    <div style="margin-top:10px;text-align:right;">
                                        <button type="button" id="tplPreviewSelect" class="btn btn-primary btn-sm">
                                            <i class="bx bx-check me-1"></i>Use this template
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Step 3: Colors -->
                        <div class="mb-4">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">3. Brand Colors</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Primary Color</label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" id="PrimaryColorPicker" value="#1a3c6e" style="width:42px; padding:2px;">
                                        <input type="text" class="form-control" id="PrimaryColor" value="#1a3c6e" maxlength="7" placeholder="#1a3c6e">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Accent Color</label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color" id="AccentColorPicker" value="#f59e0b" style="width:42px; padding:2px;">
                                        <input type="text" class="form-control" id="AccentColor" value="#f59e0b" maxlength="7" placeholder="#f59e0b">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Display Options -->
                        <div class="mb-4">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">4. Display Options</div>
                            <div class="card bg-body-secondary border-0">
                                <div class="card-body py-1 px-3">
                                    <?php
                                    $toggles = [
                                        'ShowLogo'         => ['Logo',         'Print org logo on document'],
                                        'ShowOrgAddress'   => ['Address',      'Organisation address in header'],
                                        'ShowGSTIN'        => ['GSTIN',        'Print GSTIN in header'],
                                        'ShowHSN'          => ['HSN Code',     'HSN/SAC in line items'],
                                        'ShowTaxBreakdown' => ['Tax Breakdown','Show CGST/SGST/IGST split'],
                                    ];
                                    foreach ($toggles as $id => [$label, $desc]):
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom last-no-border">
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

                        <!-- Step 5: Footer -->
                        <div class="mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">5. Footer Message</div>
                            <input type="text" class="form-control form-control-sm" id="FooterText" maxlength="200"
                                   value="Thank you for your business!" placeholder="Footer message on printed document">
                        </div>

                        <!-- Step 6: Font -->
                        <div class="mb-2">
                            <div class="fw-bold text-uppercase text-muted small mb-2" style="letter-spacing:.8px;">6. Font</div>
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
                                            <option value="Noto Sans">Noto Sans</option>
                                            <option value="Poppins">Poppins</option>
                                            <option value="Raleway">Raleway</option>
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
                                    <div class="form-text" style="font-size:.65rem;">8 – 20 px</div>
                                </div>
                            </div>
                            <!-- Live font preview -->
                            <div class="mt-2 px-3 py-2 border rounded bg-white" id="fontPreviewText" style="font-family:Arial; font-size:11px; color:#333; transition:all .2s;">
                                The quick brown fox — Sample Invoice Text 12345
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Live Preview ──────────────────────────── -->
                    <div class="col-lg-7 bg-body-secondary p-3 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold text-uppercase text-muted small" style="letter-spacing:.8px;">Template Preview</div>
                            <span class="badge bg-label-secondary small" id="previewThemeLabel">Classic</span>
                        </div>
                        <!-- Scaled paper preview -->
                        <div style="flex:1; overflow-y:auto; display:flex; align-items:flex-start; justify-content:center;">
                            <div id="themePreviewWrap" style="width:100%; max-width:560px;">
                                <div id="themePreviewBox" style="background:#fff; box-shadow:0 2px 16px rgba(0,0,0,.15); transform-origin:top center; font-family:Arial,Helvetica,sans-serif; font-size:10pt; color:#222; overflow:hidden;"></div>
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

<?php $this->load->view('common/transactions/footer'); ?>

<script>
(function () {
    'use strict';

    var _usedTypes  = <?php echo json_encode($UsedTypes); ?>;
    var _themeModal = new bootstrap.Modal(document.getElementById('themeModal'));

    // ── Build CSS mini-thumbnail for a theme ──────────────────────────
    function _buildThumb(key, primary, accent, container, h) {
        h = h || 80;
        var p = primary || '#1a3c6e';
        var a = accent  || '#f59e0b';
        var el = $(container);
        var w  = el.width() || 120;

        // Common elements helper
        function bar(bg, height, mt) {
            return '<div style="height:' + height + 'px;background:' + bg + ';' + (mt ? 'margin-top:' + mt + 'px;' : '') + '"></div>';
        }
        function lines(n, bg, ht) {
            var s = '';
            for (var i = 0; i < n; i++) s += '<div style="height:' + (ht||4) + 'px;background:' + bg + ';margin-bottom:2px;border-radius:1px;"></div>';
            return s;
        }
        function cell(w, bg) {
            return '<div style="flex:' + w + ';height:6px;background:' + bg + ';margin:0 1px;"></div>';
        }
        function tableRow(light, bdrBg) {
            return '<div style="display:flex;padding:2px 0;border-bottom:1px solid ' + (bdrBg||'#eee') + ';">'
                + cell(0.3, '#999') + cell(1.5, light ? '#aaa' : '#ccc') + cell(0.5, light ? '#aaa' : '#ccc') + cell(0.5, light ? '#aaa' : '#ccc') + cell(0.7, light ? '#aaa' : '#ccc')
                + '</div>';
        }

        var html = '';

        if (key === 'classic') {
            html = '<div style="border:2px solid ' + p + ';padding:3px;height:' + h + 'px;overflow:hidden;">'
                 + '<div style="border-bottom:2px solid ' + a + ';padding-bottom:3px;margin-bottom:3px;display:flex;justify-content:space-between;align-items:center;">'
                 +   '<div><div style="width:30px;height:5px;background:' + p + ';margin-bottom:2px;border-radius:1px;"></div>'
                 +   lines(2, '#ccc', 3) + '</div>'
                 +   '<div style="text-align:right;"><div style="font-size:6px;font-weight:900;color:' + p + ';letter-spacing:1px;">QUOTATION</div>'
                 +   '<div style="width:28px;height:3px;background:#ddd;margin-left:auto;margin-top:2px;border-radius:1px;"></div></div>'
                 + '</div>'
                 + '<div style="border:1px solid #ddd;padding:2px 3px;margin-bottom:3px;width:45%;">'
                 +   '<div style="font-size:5px;color:#888;font-weight:700;">BILL TO</div>'
                 +   '<div style="width:40px;height:4px;background:#333;border-radius:1px;margin-top:1px;"></div></div>'
                 + bar(p, 7) + tableRow(false) + tableRow(true) + tableRow(false)
                 + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:40%;background:' + p + ';height:8px;border-radius:1px;"></div></div>'
                 + '</div>';
        } else if (key === 'modern') {
            html = '<div style="height:' + h + 'px;overflow:hidden;">'
                 + '<div style="background:' + p + ';padding:4px 5px;display:flex;justify-content:space-between;align-items:center;">'
                 +   '<div style="display:flex;align-items:center;gap:3px;">'
                 +     '<div style="width:12px;height:12px;background:#fff;border-radius:2px;flex-shrink:0;opacity:.9;"></div>'
                 +     '<div><div style="width:28px;height:5px;background:#fff;border-radius:1px;margin-bottom:1px;"></div>'
                 +          '<div style="width:20px;height:3px;background:rgba(255,255,255,.6);border-radius:1px;"></div></div>'
                 +   '</div>'
                 +   '<div style="font-size:7px;font-weight:900;color:' + a + ';letter-spacing:1px;">QUOT.</div>'
                 + '</div>'
                 + '<div style="height:3px;background:' + a + ';"></div>'
                 + '<div style="padding:2px 4px;margin-bottom:2px;display:flex;gap:8px;">'
                 +   '<div>' + lines(3, '#ddd', 3) + '</div>'
                 +   '<div><div style="width:32px;height:5px;background:#333;border-radius:1px;margin-bottom:2px;"></div>'
                 +        lines(2, '#ccc', 3) + '</div>'
                 + '</div>'
                 + bar(p, 7) + tableRow(false) + tableRow(true) + tableRow(false)
                 + '<div style="display:flex;justify-content:flex-end;margin-top:2px;padding:0 3px;"><div style="width:38%;background:' + p + ';height:8px;border-radius:1px;"></div></div>'
                 + '<div style="margin-top:3px;border-top:3px solid ' + a + ';text-align:center;padding-top:2px;"><div style="width:50px;height:3px;background:#ccc;border-radius:1px;margin:0 auto;"></div></div>'
                 + '</div>';
        } else if (key === 'minimal') {
            html = '<div style="border-top:3px solid ' + p + ';padding:3px;height:' + h + 'px;overflow:hidden;">'
                 + '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:3px;">'
                 +   '<div><div style="width:28px;height:6px;background:' + p + ';border-radius:1px;margin-bottom:2px;"></div>'
                 +        lines(2, '#ddd', 3) + '</div>'
                 +   '<div style="text-align:right;"><div style="font-size:7px;font-weight:300;letter-spacing:2px;color:' + p + ';">Quotation</div>'
                 +        '<div style="width:22px;height:3px;background:#ddd;border-radius:1px;margin-left:auto;margin-top:2px;"></div></div>'
                 + '</div>'
                 + '<div style="border-bottom:1px solid #ddd;margin-bottom:3px;"></div>'
                 + '<div style="margin-bottom:3px;"><div style="width:36px;height:5px;background:#333;border-radius:1px;"></div>'
                 + lines(1, '#ddd', 3) + '</div>'
                 + bar(p, 6) + tableRow(false, '#ddd') + tableRow(true, '#ddd') + tableRow(false, '#ddd')
                 + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:38%;background:' + p + ';height:7px;border-radius:1px;"></div></div>'
                 + '<div style="border-top:1px solid #ddd;margin-top:3px;padding-top:2px;text-align:center;"><div style="width:40px;height:3px;background:#ddd;border-radius:1px;margin:0 auto;"></div></div>'
                 + '</div>';
        } else if (key === 'bold') {
            html = '<div style="height:' + h + 'px;overflow:hidden;">'
                 + '<div style="display:flex;margin-bottom:0;">'
                 +   '<div style="background:' + p + ';flex:1;padding:4px 5px;display:flex;align-items:center;gap:3px;">'
                 +     '<div style="width:12px;height:12px;background:#fff;border-radius:2px;flex-shrink:0;opacity:.9;"></div>'
                 +     '<div><div style="width:28px;height:5px;background:#fff;border-radius:1px;margin-bottom:1px;"></div>'
                 +          '<div style="width:18px;height:3px;background:rgba(255,255,255,.6);border-radius:1px;"></div></div>'
                 +   '</div>'
                 +   '<div style="background:' + a + ';width:36px;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:3px;">'
                 +     '<div style="font-size:5px;font-weight:900;color:#fff;letter-spacing:.5px;text-align:center;writing-mode:horizontal-tb;">QUOT.</div>'
                 +     '<div style="width:24px;height:3px;background:rgba(255,255,255,.8);border-radius:1px;margin-top:2px;"></div>'
                 +   '</div>'
                 + '</div>'
                 + '<div style="background:#f5f5f5;padding:2px 4px;border-bottom:2px solid ' + a + ';margin-bottom:2px;display:flex;align-items:center;gap:4px;">'
                 +   '<div style="width:8px;height:3px;background:#888;border-radius:1px;"></div>'
                 +   '<div style="width:32px;height:5px;background:#333;border-radius:1px;"></div>'
                 + '</div>'
                 + bar(p, 6) + tableRow(false) + tableRow(true) + tableRow(false)
                 + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:38%;background:' + p + ';height:8px;border-radius:1px;"></div></div>'
                 + '<div style="background:' + p + ';margin-top:3px;height:8px;"></div>'
                 + '</div>';
        } else if (key === 'swipe_clean') {
            // Clean: no border, big company name, horizontal meta strip, 3-col customer table
            html = '<div style="height:' + h + 'px;overflow:hidden;padding:3px 4px;">'
                 + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">'
                 +   '<div style="font-size:7px;font-weight:800;color:#1a1a1a;letter-spacing:.5px;">TAX INVOICE</div>'
                 +   '<div style="font-size:5px;color:#aaa;font-style:italic;">ORIGINAL FOR RECIPIENT</div>'
                 + '</div>'
                 + '<div style="border-bottom:1px solid #ddd;padding-bottom:3px;margin-bottom:3px;display:flex;justify-content:space-between;">'
                 +   '<div><div style="width:36px;height:5px;background:#1a1a1a;border-radius:1px;margin-bottom:2px;"></div>'
                 +        lines(2,'#bbb',2) + '</div>'
                 +   '<div style="width:12px;height:12px;background:#eee;border-radius:2px;flex-shrink:0;"></div>'
                 + '</div>'
                 + '<div style="display:flex;gap:2px;margin-bottom:3px;">'
                 +   '<div style="width:50px;height:3px;background:#ddd;border-radius:1px;"></div>'
                 +   '<div style="width:35px;height:3px;background:#ddd;border-radius:1px;"></div>'
                 + '</div>'
                 + '<div style="display:flex;border:1px solid #e8e8e8;margin-bottom:3px;">'
                 +   '<div style="flex:1;border-right:1px solid #e8e8e8;padding:2px 3px;">' + lines(3,'#ccc',2) + '</div>'
                 +   '<div style="flex:1;border-right:1px solid #e8e8e8;padding:2px 3px;">' + lines(3,'#ddd',2) + '</div>'
                 +   '<div style="flex:1;padding:2px 3px;">'                                                    + lines(3,'#ddd',2) + '</div>'
                 + '</div>'
                 + bar(p, 5) + tableRow(false) + tableRow(true)
                 + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:36%;background:' + p + ';height:6px;border-radius:1px;"></div></div>'
                 + '<div style="margin-top:3px;border-top:2px solid ' + a + ';"></div>'
                 + '</div>';
        } else if (key === 'swipe_formal') {
            // Formal: outer border, header band, grid layout
            html = '<div style="border:1.5px solid #bbb;height:' + h + 'px;overflow:hidden;">'
                 + '<div style="background:#f8f9fa;border-bottom:1px solid #ccc;padding:2px 4px;display:flex;justify-content:space-between;align-items:center;">'
                 +   '<div style="font-size:6px;font-weight:700;letter-spacing:1px;color:#222;">TAX INVOICE</div>'
                 +   '<div style="font-size:5px;color:#aaa;font-style:italic;">ORIGINAL</div>'
                 + '</div>'
                 + '<div style="display:flex;border-bottom:1px solid #ccc;">'
                 +   '<div style="flex:1.2;border-right:1px solid #ccc;padding:2px 3px;">'
                 +     '<div style="display:flex;align-items:flex-start;gap:2px;">'
                 +       '<div style="width:9px;height:9px;background:#eee;border-radius:1px;flex-shrink:0;"></div>'
                 +       '<div><div style="width:28px;height:4px;background:#1a1a1a;border-radius:1px;margin-bottom:1px;"></div>'
                 +            lines(2,'#bbb',2) + '</div>'
                 +     '</div></div>'
                 +   '<div style="flex:1;padding:2px 3px;">' + lines(4,'#ddd',2) + '</div>'
                 + '</div>'
                 + '<div style="display:flex;border-bottom:1px solid #ccc;">'
                 +   '<div style="flex:1;border-right:1px solid #ccc;padding:2px 3px;">' + lines(4,'#ccc',2) + '</div>'
                 +   '<div style="flex:1;padding:2px 3px;">'                             + lines(3,'#ddd',2) + '</div>'
                 + '</div>'
                 + bar(p, 5) + tableRow(false) + tableRow(true)
                 + '<div style="display:flex;justify-content:flex-end;padding:0 2px;margin-top:1px;"><div style="width:36%;background:' + p + ';height:5px;border-radius:1px;"></div></div>'
                 + '<div style="display:flex;border-top:1px solid #ddd;margin-top:2px;padding:1px 2px;gap:2px;">'
                 +   '<div style="flex:1;">' + lines(2,'#ddd',2) + '</div>'
                 +   '<div style="flex:1;">' + lines(1,'#ddd',2) + '</div>'
                 + '</div>'
                 + '</div>';
        } else { // executive
            html = '<div style="border-left:4px solid ' + p + ';padding-left:4px;height:' + h + 'px;overflow:hidden;">'
                 + '<div style="border-bottom:2px solid ' + p + ';padding-bottom:3px;margin-bottom:3px;display:flex;justify-content:space-between;align-items:flex-start;">'
                 +   '<div style="display:flex;align-items:flex-start;gap:3px;">'
                 +     '<div style="width:12px;height:12px;background:#eee;border-radius:1px;flex-shrink:0;"></div>'
                 +     '<div><div style="width:28px;height:5px;background:' + p + ';border-bottom:2px solid ' + a + ';margin-bottom:2px;border-radius:1px;"></div>'
                 +          lines(2, '#ccc', 3) + '</div>'
                 +   '</div>'
                 +   '<div style="text-align:right;"><div style="font-size:6px;font-weight:700;color:' + p + ';letter-spacing:1px;">Quotation</div>'
                 +     '<div style="border:1px solid #ddd;padding:1px 3px;margin-top:1px;">'
                 +       lines(2, '#ddd', 2)
                 +     '</div></div>'
                 + '</div>'
                 + '<div style="border:1px solid #ddd;background:#fafafa;padding:2px 3px;margin-bottom:3px;display:inline-block;width:45%;">'
                 +   '<div style="width:8px;height:3px;background:' + p + ';margin-bottom:1px;border-radius:1px;"></div>'
                 +   '<div style="width:32px;height:5px;background:#333;border-radius:1px;"></div>'
                 + '</div>'
                 + bar(p, 6) + tableRow(false) + tableRow(true) + tableRow(false)
                 + '<div style="display:flex;justify-content:flex-end;margin-top:2px;"><div style="width:38%;background:' + p + ';height:7px;border-radius:1px;"></div></div>'
                 + '<div style="border-top:3px solid ' + a + ';margin-top:4px;"></div>'
                 + '</div>';
        }

        el.html(html);
    }

    // ── Build large modal preview ─────────────────────────────────────
    function _buildModalPreview() {
        var key     = $('input[name="ThemeKey"]:checked').val() || 'classic';
        var primary = $('#PrimaryColor').val()  || '#1a3c6e';
        var accent  = $('#AccentColor').val()   || '#f59e0b';
        var orgName = 'Your Company Name';
        var p = primary, a = accent;

        var themeLabels = {
            classic: 'Classic', modern: 'Modern', minimal: 'Minimal', bold: 'Bold', executive: 'Executive'
        };
        $('#previewThemeLabel').text(themeLabels[key] || key);

        // Rebuild the thumbnail grids with updated colors
        $('.theme-modal-thumb').each(function() {
            _buildThumb($(this).data('key'), p, a, this, 80);
        });

        // Large preview panel
        var box = $('#themePreviewBox');
        var sampleOrg   = { name: orgName, gstin: '29XXXXX1234X1Z1', phone: '9876543210', addr: 'No.12, Main Road, Chennai, Tamil Nadu - 600001' };
        var sampleItems = [
            { name: 'Rotavator Blade',    hsn: '84322900', qty: '2 Nos', rate: '4,500.00', disc: '—',      taxable: '9,000.00', cgst: '9%', cgstAmt: '810.00', sgst: '9%', sgstAmt: '810.00', amount: '10,620.00' },
            { name: 'Gear Assembly Kit',  hsn: '84831000', qty: '1 Set', rate: '3,200.00', disc: '160.00', taxable: '3,040.00', cgst: '9%', cgstAmt: '273.60', sgst: '9%', sgstAmt: '273.60', amount: '3,587.20' },
        ];

        var ths = 'padding:4px 5px;font-size:7pt;font-weight:700;background:' + p + ';color:#fff;border:1px solid rgba(0,0,0,.1);white-space:nowrap;';
        var td  = 'padding:4px 5px;font-size:8pt;border:1px solid #ddd;';

        function itemsTable() {
            var h = '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;">';
            h += '<thead><tr>';
            h += '<th style="' + ths + 'text-align:center;width:26px;">#</th>';
            h += '<th style="' + ths + 'text-align:left;">Item Description</th>';
            h += '<th style="' + ths + 'text-align:center;width:48px;">HSN/SAC</th>';
            h += '<th style="' + ths + 'text-align:center;width:44px;">Qty</th>';
            h += '<th style="' + ths + 'text-align:right;width:62px;">Rate</th>';
            h += '<th style="' + ths + 'text-align:right;width:50px;">Disc.</th>';
            h += '<th style="' + ths + 'text-align:right;width:62px;">Taxable</th>';
            h += '<th style="' + ths + 'text-align:center;width:32px;">CGST%</th>';
            h += '<th style="' + ths + 'text-align:right;width:55px;">CGST Amt</th>';
            h += '<th style="' + ths + 'text-align:center;width:32px;">SGST%</th>';
            h += '<th style="' + ths + 'text-align:right;width:55px;">SGST Amt</th>';
            h += '<th style="' + ths + 'text-align:right;width:68px;">Amount</th>';
            h += '</tr></thead><tbody>';
            $.each(sampleItems, function(i, item) {
                var bg = i%2 ? '#f9fafb' : '#fff';
                h += '<tr>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:center;">' + (i+1) + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';font-weight:600;">' + item.name + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:center;font-size:7pt;color:#666;">' + item.hsn + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:center;">' + item.qty + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:right;">' + item.rate + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:right;">' + item.disc + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:right;">' + item.taxable + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:center;">' + item.cgst + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:right;">' + item.cgstAmt + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:center;">' + item.sgst + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:right;">' + item.sgstAmt + '</td>'
                   + '<td style="' + td + 'background:' + bg + ';text-align:right;font-weight:700;">' + item.amount + '</td>'
                   + '</tr>';
            });
            h += '</tbody></table>';
            return h;
        }

        function totals() {
            function tr(l, v, bold, color) {
                return '<tr><td style="padding:3px 8px;font-size:8pt;color:#666;border:1px solid #eee;">' + l + '</td>'
                     + '<td style="padding:3px 8px;font-size:8pt;text-align:right;border:1px solid #eee;' + (bold?'font-weight:700;':'') + (color?'color:'+color+';':'') + '">₹ ' + v + '</td></tr>';
            }
            return '<table style="width:auto;min-width:200px;margin-left:auto;border-collapse:collapse;margin-bottom:6px;">'
                + tr('Sub Total','12,040.00') + tr('Discount','160.00',false,'#c00') + tr('CGST','1,083.60') + tr('SGST','1,083.60')
                + '<tr style="background:' + p + ';color:#fff;"><td style="padding:5px 8px;font-weight:700;font-size:9pt;">Grand Total</td>'
                + '<td style="padding:5px 8px;font-weight:700;font-size:9pt;text-align:right;">₹ 14,207.20</td></tr>'
                + '</table>';
        }

        function amtWords() {
            return '<div style="font-size:7.5pt;color:#555;margin-bottom:8px;padding:4px 8px;background:#f9f9f9;border:1px solid #ddd;"><strong>Amount in Words:</strong> Fourteen Thousand Two Hundred Seven Only</div>';
        }

        function sig() {
            return '<div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:6px;border-top:1px solid #ddd;padding-top:5px;">'
                 + '<div style="font-size:7pt;color:#999;">Computer generated document.</div>'
                 + '<div style="text-align:center;width:140px;">'
                 +   '<div style="border-top:1px solid #555;padding-top:3px;font-size:7.5pt;font-weight:600;color:#333;">Authorised Signatory</div>'
                 +   '<div style="font-size:7pt;color:#888;">For ' + orgName + '</div>'
                 + '</div></div>';
        }

        function footer(txt) {
            return '<div style="margin-top:6px;padding-top:5px;font-size:7.5pt;color:#888;text-align:center;">' + (txt || 'Thank you for your business!') + '</div>';
        }

        var footerTxt  = $('#FooterText').val() || 'Thank you for your business!';
        var fontFamily = $('#FontFamily').val() || 'Arial';
        var fontSizePx = parseInt($('#FontSizePx').val()) || 11;
        box.css({ 'font-family': "'" + fontFamily + "', Arial, Helvetica, sans-serif", 'font-size': fontSizePx + 'px' });
        var html = '';

        if (key === 'classic') {
            html = '<div style="border:2px solid ' + p + ';padding:10px;">'
                 + '<table style="width:100%;border-collapse:collapse;border-bottom:2px solid ' + a + ';padding-bottom:6px;margin-bottom:6px;"><tr>'
                 +   '<td style="border:none;vertical-align:top;"><div style="font-size:12pt;font-weight:800;color:' + p + ';">' + orgName + '</div>'
                 +   '<div style="font-size:8pt;color:#555;">' + sampleOrg.addr + '</div>'
                 +   '<div style="font-size:8pt;color:#555;">Ph: ' + sampleOrg.phone + ' | GSTIN: ' + sampleOrg.gstin + '</div></td>'
                 +   '<td style="border:none;text-align:right;vertical-align:top;white-space:nowrap;">'
                 +   '<div style="font-size:16pt;font-weight:800;color:' + p + ';letter-spacing:2px;">QUOTATION</div>'
                 +   '<table style="margin-left:auto;border:1px solid #ddd;border-collapse:collapse;font-size:8pt;"><tr><td style="padding:2px 6px;border-bottom:1px solid #eee;color:#888;">No.</td><td style="padding:2px 6px;border-bottom:1px solid #eee;font-weight:700;">QT-2025-001</td></tr>'
                 +   '<tr><td style="padding:2px 6px;color:#888;">Date</td><td style="padding:2px 6px;">05 Apr 2025</td></tr></table>'
                 +   '</td></tr></table>'
                 + '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;"><tr>'
                 +   '<td style="width:50%;border:1px solid #ddd;padding:0;"><div style="background:#f3f4f6;font-size:7pt;font-weight:700;padding:3px 6px;border-bottom:1px solid #ddd;color:' + p + ';text-transform:uppercase;">Bill To</div>'
                 +   '<div style="padding:5px 6px;"><div style="font-size:9.5pt;font-weight:700;">Sample Trading Co.</div><div style="font-size:8pt;color:#555;">+91 9876543210</div></div></td>'
                 +   '<td style="border:none;"></td></tr></table>'
                 + itemsTable() + amtWords() + totals() + sig()
                 + '</div>'
                 + '<div style="text-align:center;font-size:8pt;color:#888;margin-top:6px;border-top:2px solid ' + a + ';padding-top:4px;">' + footerTxt + '</div>';

        } else if (key === 'modern') {
            html = '<div style="background:' + p + ';padding:12px 14px;display:flex;justify-content:space-between;align-items:center;">'
                 + '<div style="display:flex;align-items:center;gap:10px;color:#fff;">'
                 +   '<div style="width:36px;height:36px;background:#fff;border-radius:5px;opacity:.9;flex-shrink:0;"></div>'
                 +   '<div><div style="font-size:13pt;font-weight:800;">' + orgName + '</div>'
                 +   '<div style="font-size:8pt;opacity:.8;">Chennai, Tamil Nadu | GSTIN: ' + sampleOrg.gstin + '</div></div>'
                 + '</div>'
                 + '<div style="text-align:right;"><div style="font-size:18pt;font-weight:900;color:' + a + ';letter-spacing:2px;">QUOTATION</div>'
                 +   '<div style="font-size:9pt;font-weight:700;color:#fff;">QT-2025-001</div>'
                 +   '<div style="font-size:8pt;color:rgba(255,255,255,.8);">Date: 05 Apr 2025</div></div>'
                 + '</div>'
                 + '<div style="background:' + a + ';height:3px;margin-bottom:8px;"></div>'
                 + '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;"><tr>'
                 +   '<td style="border:none;width:50%;vertical-align:top;"><div style="font-size:7pt;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:2px;">From</div>'
                 +   '<div style="font-size:8pt;color:#555;">' + sampleOrg.addr + '</div></td>'
                 +   '<td style="border:none;vertical-align:top;"><div style="font-size:7pt;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:2px;">Bill To</div>'
                 +   '<div style="font-size:9.5pt;font-weight:700;">Sample Trading Co.</div>'
                 +   '<div style="font-size:8pt;color:#555;">+91 9876543210</div></td></tr></table>'
                 + itemsTable() + amtWords() + totals() + sig()
                 + '<div style="margin-top:8px;border-top:3px solid ' + a + ';padding-top:5px;text-align:center;font-size:8pt;color:#888;">' + footerTxt + '</div>';

        } else if (key === 'minimal') {
            html = '<div style="border-top:3px solid ' + p + ';padding-top:12px;">'
                 + '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">'
                 +   '<div><div style="font-size:14pt;font-weight:800;color:' + p + ';">' + orgName + '</div>'
                 +   '<div style="font-size:8pt;color:#999;margin-top:2px;">' + sampleOrg.addr + '</div>'
                 +   '<div style="font-size:8pt;color:#999;">GSTIN: ' + sampleOrg.gstin + '</div></div>'
                 +   '<div style="text-align:right;">'
                 +   '<div style="font-size:18pt;font-weight:300;letter-spacing:4px;color:' + p + ';">Quotation</div>'
                 +   '<div style="font-size:9pt;font-weight:600;color:#333;margin-top:3px;">QT-2025-001</div>'
                 +   '<div style="font-size:8pt;color:#aaa;">Date: 05 Apr 2025</div></div>'
                 + '</div>'
                 + '<div style="border-bottom:1px solid #ddd;margin-bottom:10px;"></div>'
                 + '<div style="margin-bottom:10px;"><div style="font-size:7pt;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:3px;">Bill To</div>'
                 +   '<div style="font-size:10pt;font-weight:600;color:#222;">Sample Trading Co.</div>'
                 +   '<div style="font-size:8pt;color:#888;">+91 9876543210</div></div>'
                 + itemsTable() + amtWords() + totals() + sig()
                 + '</div>'
                 + '<div style="border-top:1px solid #ddd;padding-top:6px;margin-top:8px;text-align:center;font-size:8pt;color:#bbb;">' + footerTxt + '</div>';

        } else if (key === 'bold') {
            html = '<div style="display:flex;">'
                 + '<div style="background:' + p + ';flex:1;padding:12px 14px;display:flex;align-items:center;gap:10px;">'
                 +   '<div style="width:40px;height:40px;background:#fff;border-radius:5px;flex-shrink:0;opacity:.9;"></div>'
                 +   '<div style="color:#fff;"><div style="font-size:14pt;font-weight:800;">' + orgName + '</div>'
                 +   '<div style="font-size:8pt;opacity:.8;">Chennai | Ph: ' + sampleOrg.phone + '</div>'
                 +   '<div style="font-size:8pt;opacity:.8;">GSTIN: ' + sampleOrg.gstin + '</div></div>'
                 + '</div>'
                 + '<div style="background:' + a + ';padding:12px 14px;text-align:right;min-width:160px;display:flex;flex-direction:column;justify-content:center;">'
                 +   '<div style="font-size:16pt;font-weight:900;color:#fff;letter-spacing:1px;">QUOTATION</div>'
                 +   '<div style="font-size:9pt;font-weight:700;color:#fff;">QT-2025-001</div>'
                 +   '<div style="font-size:8pt;color:rgba(255,255,255,.9);">05 Apr 2025</div>'
                 + '</div></div>'
                 + '<div style="background:#f5f5f5;padding:6px 12px;border-bottom:3px solid ' + a + ';margin-bottom:8px;display:flex;align-items:center;gap:12px;">'
                 +   '<span style="font-size:7.5pt;font-weight:700;color:#888;text-transform:uppercase;">Bill To:</span>'
                 +   '<span style="font-size:10pt;font-weight:700;">Sample Trading Co.</span>'
                 +   '<span style="font-size:8.5pt;color:#666;">+91 9876543210</span>'
                 + '</div>'
                 + itemsTable() + amtWords() + totals() + sig()
                 + '<div style="background:' + p + ';color:#fff;text-align:center;padding:6px;font-size:8pt;margin-top:6px;">' + footerTxt + '</div>';

        } else { // executive
            html = '<div style="border-left:5px solid ' + p + ';padding-left:12px;">'
                 + '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid ' + p + ';padding-bottom:8px;margin-bottom:8px;">'
                 +   '<div style="display:flex;align-items:flex-start;gap:8px;">'
                 +     '<div style="width:38px;height:38px;background:#eee;border-radius:2px;flex-shrink:0;"></div>'
                 +     '<div><div style="font-size:13pt;font-weight:700;color:' + p + ';border-bottom:2px solid ' + a + ';display:inline-block;padding-bottom:1px;margin-bottom:3px;">' + orgName + '</div>'
                 +     '<div style="font-size:8pt;color:#555;">' + sampleOrg.addr + '</div>'
                 +     '<div style="font-size:8pt;color:#555;">GSTIN: ' + sampleOrg.gstin + '</div></div>'
                 +   '</div>'
                 +   '<div style="text-align:right;"><div style="font-size:15pt;font-weight:700;color:' + p + ';font-variant:small-caps;letter-spacing:2px;">Quotation</div>'
                 +   '<table style="margin-left:auto;border:1px solid #ddd;border-collapse:collapse;margin-top:3px;font-size:8pt;"><tr><td style="padding:2px 6px;border-bottom:1px solid #eee;color:#888;">No.</td><td style="padding:2px 6px;border-bottom:1px solid #eee;font-weight:700;">QT-2025-001</td></tr>'
                 +   '<tr><td style="padding:2px 6px;color:#888;">Date</td><td style="padding:2px 6px;">05 Apr 2025</td></tr></table></div>'
                 + '</div>'
                 + '<div style="border:1px solid #ddd;background:#fafafa;padding:6px 10px;margin-bottom:8px;display:inline-block;min-width:42%;">'
                 +   '<div style="font-size:7pt;font-weight:700;color:' + p + ';text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Bill To</div>'
                 +   '<div style="font-size:10pt;font-weight:700;">Sample Trading Co.</div>'
                 +   '<div style="font-size:8pt;color:#555;">+91 9876543210</div>'
                 + '</div>'
                 + itemsTable() + amtWords() + totals() + sig()
                 + '</div>'
                 + '<div style="border-top:3px solid ' + a + ';padding-top:5px;margin-top:8px;text-align:center;font-size:8pt;color:#888;">' + footerTxt + '</div>';
        }

        box.html(html);
    }

    // ── Debounce helper ───────────────────────────────────────────────
    function _debounce(fn, ms) { var t; return function() { clearTimeout(t); t = setTimeout(fn, ms); }; }

    // ── Render all page-level thumbnails ──────────────────────────────
    function _renderPageThumbs() {
        $('.theme-thumb').each(function() {
            var key = $(this).data('key');
            var p   = $(this).data('primary') || '#1a3c6e';
            var a   = $(this).data('accent')  || '#f59e0b';
            _buildThumb(key, p, a, this, 110);
        });
    }

    // ── Modal open helpers ────────────────────────────────────────────
    function _openAddModal() {
        $('#ThemeConfigUID').val(0);
        $('#themeModalTitle').html('<i class="bx bx-palette me-1"></i>Add Print Theme');
        $('#TransactionType').val('').prop('disabled', false);
        _filterTypeOptions();
        $('#PrimaryColor').val('#1a3c6e'); $('#PrimaryColorPicker').val('#1a3c6e');
        $('#AccentColor').val('#f59e0b');  $('#AccentColorPicker').val('#f59e0b');
        $('#FooterText').val('Thank you for your business!');
        $('#ShowLogo, #ShowOrgAddress, #ShowGSTIN, #ShowHSN, #ShowTaxBreakdown').prop('checked', true);
        $('#FontFamily').val('Arial');
        $('#FontSizePx').val(11);
        $('#typeUsedNote').addClass('d-none');
        _themeModal.show();
        setTimeout(function() {
            $('.theme-modal-thumb').each(function() { _buildThumb($(this).data('key'), '#1a3c6e', '#f59e0b', this, 90); });
            _selectCarouselItem('classic');
            _updateFontPreview();
            _buildModalPreview();
        }, 150);
    }

    function _filterTypeOptions() {
        var editingUID = parseInt($('#ThemeConfigUID').val()) > 0;
        var current    = editingUID ? $('#TransactionType').val() : null;
        $('#TransactionType option[value!=""]').each(function() {
            var v = $(this).val();
            $(this).prop('disabled', $.inArray(v, _usedTypes) !== -1 && v !== current);
        });
    }

    // ── Event bindings ────────────────────────────────────────────────
    $('#addThemeBtn, #addThemeBtnEmpty').on('click', function() {
        _usedTypes = <?php echo json_encode($UsedTypes); ?>;
        _openAddModal();
    });

    // ── Template carousel ─────────────────────────────────────────────
    var _tplPreviewKey = null; // key of the template currently shown in full-preview overlay

    function _selectCarouselItem(key) {
        $('.template-carousel-item').css({ 'border-color': '#dee2e6', 'box-shadow': 'none' });
        $('.template-carousel-item[data-key="' + key + '"]')
            .css({ 'border-color': '#0d6efd', 'box-shadow': '0 0 0 3px rgba(13,110,253,.2)' });
        $('input[name="ThemeKey"][value="' + key + '"]').prop('checked', true);
        _buildModalPreview();
        // Scroll that item into view
        var track = document.getElementById('templateCarouselTrack');
        var item  = track.querySelector('.template-carousel-item[data-key="' + key + '"]');
        if (item) item.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    // Click on a carousel item = select it; second click = open full preview
    $(document).on('click', '.template-carousel-item', function() {
        var key = $(this).data('key');
        var alreadySelected = $('input[name="ThemeKey"]:checked').val() === key;
        _selectCarouselItem(key);
        if (alreadySelected) {
            // Open full-size preview overlay
            _tplPreviewKey = key;
            var label = $(this).find('div:last div:first').text().trim();
            $('#tplPreviewLabel').text(label + ' — Full Preview');
            // Build preview HTML using the current _buildModalPreview output
            var prev = $('#themePreviewBox').html();
            $('#tplPreviewBox').html(prev);
            $('#tplPreviewOverlay').css('display', 'flex');
        }
    });

    $('#tplPreviewClose').on('click', function() {
        $('#tplPreviewOverlay').hide();
    });
    $('#tplPreviewOverlay').on('click', function(e) {
        if ($(e.target).is('#tplPreviewOverlay')) $('#tplPreviewOverlay').hide();
    });
    $('#tplPreviewSelect').on('click', function() {
        if (_tplPreviewKey) _selectCarouselItem(_tplPreviewKey);
        $('#tplPreviewOverlay').hide();
    });

    // Carousel prev / next scroll
    $('#carouselPrev').on('click', function() {
        var track = document.getElementById('templateCarouselTrack');
        track.scrollBy({ left: -150, behavior: 'smooth' });
    });
    $('#carouselNext').on('click', function() {
        var track = document.getElementById('templateCarouselTrack');
        track.scrollBy({ left: 150, behavior: 'smooth' });
    });

    // Color pickers
    $('#PrimaryColorPicker').on('input', function() { $('#PrimaryColor').val($(this).val()); _buildModalPreview(); });
    $('#AccentColorPicker').on('input',  function() { $('#AccentColor').val($(this).val());  _buildModalPreview(); });
    $('#PrimaryColor').on('input', _debounce(function() {
        if (/^#[0-9a-fA-F]{6}$/.test($(this).val())) { $('#PrimaryColorPicker').val($(this).val()); _buildModalPreview(); }
    }, 300));
    $('#AccentColor').on('input', _debounce(function() {
        if (/^#[0-9a-fA-F]{6}$/.test($(this).val())) { $('#AccentColorPicker').val($(this).val()); _buildModalPreview(); }
    }, 300));
    $('#FooterText').on('input', _debounce(function() { _buildModalPreview(); }, 400));

    // Font live preview
    function _updateFontPreview() {
        var font = $('#FontFamily').val() || 'Arial';
        var size = parseInt($('#FontSizePx').val()) || 11;
        var el   = $('#fontPreviewText');
        el.css({ 'font-family': "'" + font + "', sans-serif", 'font-size': size + 'px' });
        // Load Google Font into the page if not a system font
        var systemFonts = ['Arial','Helvetica','Verdana','Tahoma','Trebuchet MS','Times New Roman','Georgia','Palatino Linotype','Calibri'];
        if ($.inArray(font, systemFonts) === -1) {
            var linkId = 'gfont-' + font.replace(/\s+/g, '-');
            if (!$('#' + linkId).length) {
                $('<link>', { id: linkId, rel: 'stylesheet',
                    href: 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(font) + ':wght@400;600;700&display=swap'
                }).appendTo('head');
            }
        }
        _buildModalPreview();
    }
    $('#FontFamily').on('change', _updateFontPreview);
    $('#FontSizePx').on('input', _debounce(_updateFontPreview, 300));

    // Edit
    $(document).on('click', '.editThemeBtn', function() {
        var uid = $(this).data('uid');
        $.ajax({
            url: '/print-themes/getThemeData', method: 'GET', data: { ThemeConfigUID: uid },
            success: function(resp) {
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                var d = resp.Data;
                $('#ThemeConfigUID').val(d.ThemeConfigUID);
                $('#themeModalTitle').html('<i class="bx bx-edit me-1"></i>Edit Print Theme');
                $('#TransactionType').val(d.TransactionType).prop('disabled', true);
                $('#typeUsedNote').addClass('d-none');

                $('#PrimaryColor').val(d.PrimaryColor); $('#PrimaryColorPicker').val(d.PrimaryColor);
                $('#AccentColor').val(d.AccentColor);   $('#AccentColorPicker').val(d.AccentColor);
                $('#ShowLogo').prop('checked',         d.ShowLogo == 1);
                $('#ShowOrgAddress').prop('checked',   d.ShowOrgAddress == 1);
                $('#ShowGSTIN').prop('checked',        d.ShowGSTIN == 1);
                $('#ShowHSN').prop('checked',          d.ShowHSN == 1);
                $('#ShowTaxBreakdown').prop('checked', d.ShowTaxBreakdown == 1);
                $('#FooterText').val(d.FooterText || '');
                $('#FontFamily').val(d.FontFamily || 'Arial');
                $('#FontSizePx').val(d.FontSizePx || 11);

                _themeModal.show();
                setTimeout(function() {
                    $('.theme-modal-thumb').each(function() { _buildThumb($(this).data('key'), d.PrimaryColor, d.AccentColor, this, 90); });
                    _selectCarouselItem(d.ThemeKey || 'classic');
                    _updateFontPreview();
                    _buildModalPreview();
                }, 150);
            }
        });
    });

    // Save
    $('#saveThemeBtn').on('click', function() {
        var transType = $('#TransactionType').val();
        if (!transType) { Swal.fire({ icon: 'warning', text: 'Please select a transaction type.' }); return; }
        var themeKey  = $('input[name="ThemeKey"]:checked').val();
        if (!themeKey)  { Swal.fire({ icon: 'warning', text: 'Please select a theme.' }); return; }

        $('#saveThemeSpinner').removeClass('d-none');
        $('#saveThemeBtn').prop('disabled', true);

        var formData = new FormData();
        formData.append('ThemeConfigUID',  $('#ThemeConfigUID').val());
        formData.append('TransactionType', transType);
        formData.append('ThemeKey',        themeKey);
        formData.append('PrimaryColor',    $('#PrimaryColor').val());
        formData.append('AccentColor',     $('#AccentColor').val());
        formData.append('ShowLogo',        $('#ShowLogo').is(':checked') ? 1 : 0);
        formData.append('ShowOrgAddress',  $('#ShowOrgAddress').is(':checked') ? 1 : 0);
        formData.append('ShowGSTIN',       $('#ShowGSTIN').is(':checked') ? 1 : 0);
        formData.append('ShowHSN',         $('#ShowHSN').is(':checked') ? 1 : 0);
        formData.append('ShowTaxBreakdown', $('#ShowTaxBreakdown').is(':checked') ? 1 : 0);
        formData.append('FooterText',      $('#FooterText').val());
        formData.append('FontFamily',      $('#FontFamily').val());
        formData.append('FontSizePx',      $('#FontSizePx').val());
        formData.append(CsrfName, CsrfToken);

        $.ajax({
            url: '/print-themes/save', method: 'POST', data: formData,
            processData: false, contentType: false,
            success: function(resp) {
                $('#saveThemeSpinner').addClass('d-none');
                $('#saveThemeBtn').prop('disabled', false);
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    _themeModal.hide();
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false })
                        .then(function() { location.reload(); });
                }
            },
            error: function() {
                $('#saveThemeSpinner').addClass('d-none');
                $('#saveThemeBtn').prop('disabled', false);
                Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
            }
        });
    });

    // Delete
    $(document).on('click', '.deleteThemeBtn', function() {
        var uid   = $(this).data('uid');
        var label = $(this).data('label') || 'this theme';
        Swal.fire({
            title: 'Remove Theme?', html: 'Remove <strong>' + label + '</strong>?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Remove', confirmButtonColor: '#d33',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '/print-themes/delete', method: 'POST',
                data: { ThemeConfigUID: uid, [CsrfName]: CsrfToken },
                success: function(resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else {
                        Swal.fire({ icon: 'success', text: resp.Message, timer: 1200, showConfirmButton: false })
                            .then(function() { location.reload(); });
                    }
                }
            });
        });
    });

    // Init page-level thumbnails on load
    _renderPageThumbs();

}());
</script>
