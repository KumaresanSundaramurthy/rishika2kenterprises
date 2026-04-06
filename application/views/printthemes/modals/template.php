<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Template Create / Edit Modal ──────────────────────────────────────── -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header py-2 border-bottom">
                <h6 class="modal-title mb-0" id="templateModalTitle">
                    <i class="bx bx-file me-1"></i>Add Template
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3">
                <input type="hidden" id="TemplateModalUID" value="0">

                <div class="row g-3">

                    <!-- Template Name -->
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold mb-1">Template Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="TemplateName"
                            maxlength="100" placeholder="e.g. Classic Invoice">
                    </div>

                    <!-- Template Key -->
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">
                            Template Key
                            <span class="text-muted fw-normal ms-1" style="font-size:.7rem;">(auto-generated)</span>
                        </label>
                        <input type="text" class="form-control form-control-sm font-monospace" id="TemplateKey"
                            maxlength="60" placeholder="classic_invoice"
                            style="text-transform:lowercase;">
                        <div class="form-text" id="tplKeyHint" style="font-size:.7rem;">
                            Lowercase letters, numbers, underscores only.
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Description</label>
                        <textarea class="form-control form-control-sm" id="TplDescription"
                            rows="2" maxlength="300"
                            placeholder="Short description of this template's style…"></textarea>
                    </div>

                    <!-- Category + Sort Order -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Category</label>
                        <select class="form-select form-select-sm" id="TplCategory">
                            <option value="general">General</option>
                            <option value="gst">GST</option>
                            <option value="minimal">Minimal</option>
                            <option value="formal">Formal</option>
                            <option value="modern">Modern</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Sort Order</label>
                        <input type="number" class="form-control form-control-sm" id="TplSortOrder"
                            value="0" min="0" max="999" step="1"
                            placeholder="0">
                    </div>

                    <!-- Preview Image -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Preview Image URL</label>
                        <input type="text" class="form-control form-control-sm" id="TplPreviewImageUrl"
                            maxlength="500" placeholder="https://…/template-preview.png">
                        <div class="mt-2" id="tplPreviewImgWrapper" style="display:none;">
                            <img id="tplPreviewImg"
                                src="" alt="Template preview"
                                style="max-width:100%;max-height:220px;border:1px solid #ddd;border-radius:6px;
                                        box-shadow:0 1px 6px rgba(0,0,0,.1);object-fit:contain;">
                        </div>
                    </div>

                    <!-- HTML Content -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">
                            HTML Content
                            <span class="text-muted fw-normal ms-1" style="font-size:.7rem;">
                                Use <code>{{PLACEHOLDER}}</code> tokens
                            </span>
                        </label>
                        <div class="mb-1" style="font-size:.7rem;color:#888;line-height:1.6;">
                            Available tokens:
                            <code>{{ORG_NAME}}</code> <code>{{ORG_ADDRESS}}</code> <code>{{ORG_GSTIN}}</code>
                            <code>{{DOC_NUMBER}}</code> <code>{{DOC_DATE}}</code> <code>{{DUE_DATE}}</code>
                            <code>{{CUSTOMER_NAME}}</code> <code>{{CUSTOMER_ADDRESS}}</code>
                            <code>{{ITEMS_TABLE}}</code> <code>{{SUBTOTAL}}</code>
                            <code>{{TAX_TOTAL}}</code> <code>{{GRAND_TOTAL}}</code>
                            <code>{{FOOTER_TEXT}}</code> <code>{{PRIMARY_COLOR}}</code>
                            <code>{{ACCENT_COLOR}}</code> <code>{{FONT_FAMILY}}</code>
                            <code>{{FONT_SIZE}}</code> <code>{{ORG_LOGO}}</code>
                        </div>
                        <textarea class="form-control font-monospace" id="TplHtmlContent"
                            rows="14" style="font-size:.72rem;resize:vertical;"
                            placeholder="Paste full HTML template here with {{PLACEHOLDER}} tokens…"></textarea>
                    </div>

                </div><!-- /row -->
            </div>

            <div class="modal-footer py-2 border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="saveTplBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="saveTplSpinner"></span>
                    Save Template
                </button>
            </div>

        </div>
    </div>
</div>

