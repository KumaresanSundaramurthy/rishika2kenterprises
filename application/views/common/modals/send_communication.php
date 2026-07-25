<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
#commAttachList .prod-attach-item { cursor: pointer; }
#commAttachList .prod-attach-item.comm-preview-active { border-color: #6366f1; background: #f5f3ff; }
</style>

<!-- ── Send Communication Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="SendCommModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">

            <!-- Header — vtm-banner style, static -->
            <div class="vtm-banner flex-shrink-0" id="CommModalHeader" style="--vtm-color:#0d6efd;--vtm-bg:#e8f0fe;--vtm-icon-bg:rgba(13,110,253,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-envelope comm-header-icon-i" id="CommHeaderIconI" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);" id="SendCommModalTitle">Send Email</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;">Compose and send your message</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scrollable body -->
            <div class="modal-body px-4 py-3 flex-grow-1" style="overflow-y:auto;">

                <!-- From (Email only) + To — row layout -->
                <div class="row g-2 mb-3">
                    <!-- From: shown for Email only -->
                    <div class="col-6" id="CommFromSection">
                        <div class="comm-from-box h-100">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-envelope-open text-primary fs-5 flex-shrink-0"></i>
                                <div class="min-w-0">
                                    <div class="comm-field-label">From</div>
                                    <div class="fw-semibold text-truncate" style="font-size:.85rem;"><?php echo htmlspecialchars(getenv('MAIL_FROM_NAME') ?: 'R2K Enterprises'); ?></div>
                                    <div class="text-muted small text-truncate"><?php echo htmlspecialchars(getenv('MAIL_FROM_EMAIL') ?: ''); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- To: always shown -->
                    <div class="col-6" id="CommToSection">
                        <div class="comm-to-box h-100">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-user-circle text-secondary fs-5 flex-shrink-0"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="comm-field-label">To</div>
                                    <div class="comm-to-name text-truncate fw-semibold" id="CommToName">&mdash;</div>
                                    <div class="comm-to-contact text-truncate text-muted small" id="CommToContact"></div>
                                </div>
                                <div id="CommNoContactWarning" class="d-none flex-shrink-0">
                                    <span class="badge bg-label-warning">
                                        <i class="bx bx-error-circle me-1"></i><span id="CommNoContactText"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type tabs — shown/hidden based on available contact -->
                <div class="d-flex gap-2 mb-3" id="CommTypeTabs">
                    <button type="button" class="btn btn-sm comm-type-tab px-3" data-commtype="SMS" id="CommTabSMS">
                        <i class="bx bx-message-rounded me-1"></i>SMS
                    </button>
                    <button type="button" class="btn btn-sm comm-type-tab px-3" data-commtype="Email" id="CommTabEmail">
                        <i class="bx bx-envelope me-1"></i>Email
                    </button>
                </div>

                <!-- SMS Fields -->
                <div id="CommSmsFields" class="d-none">
                    <label class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                    <div id="CommSmsEditor" class="comm-editor"></div>
                    <input type="hidden" id="CommSmsMessage">
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">
                            <span id="CommSmsCharCount">0</span> chars &nbsp;&middot;&nbsp;
                            <span id="CommSmsParts">1</span> SMS part<span id="CommSmsPartsS"></span>
                        </small>
                        <small class="text-muted">
                            Provider: <strong class="text-primary"><?php echo strtoupper(getenv('SMS_PROVIDER') ?: 'fast2sms'); ?></strong>
                        </small>
                    </div>
                </div>

                <!-- Email Fields -->
                <div id="CommEmailFields" class="d-none">
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold small mb-0">Subject <span class="text-danger">*</span></label>
                            <button type="button" id="CommTokenToggleBtn" class="btn btn-xs btn-outline-secondary d-none" style="font-size:.72rem;padding:2px 8px;line-height:1.4;">
                                <i class="bx bx-code-alt me-1"></i><span id="CommTokenToggleLabel">Show Tokens</span>
                            </button>
                        </div>
                        <input type="text" class="form-control form-control-sm" id="CommEmailSubject" placeholder="Enter email subject" maxlength="255" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                        <div id="CommEmailEditor" class="comm-editor comm-email-editor"></div>
                        <input type="hidden" id="CommEmailMessage">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold small">
                            Attachments <span class="text-muted fw-normal">(max 3 files &bull; 3 MB each)</span>
                        </label>
                        <!-- Smart PDF alert — shown on email tab for non-draft documents -->
                        <div id="CommPdfAttachAlert" class="d-none mb-2 rounded-2 d-flex align-items-center gap-2 px-3 py-2"
                             style="background:#eff6ff;border:1px solid #bfdbfe;cursor:pointer;user-select:none;"
                             onclick="_commClickPdfAlert()">
                            <i class="bx bxs-file-pdf" style="color:#ef4444;font-size:1.1rem;flex-shrink:0;"></i>
                            <span id="CommPdfAttachAlertText" style="font-size:.8rem;color:#1d4ed8;flex:1;line-height:1.35;"></span>
                            <span id="CommPdfAttachAlertSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm" style="color:#3b82f6;width:14px;height:14px;"></span>
                            </span>
                            <i id="CommPdfAttachAlertIcon" class="bx bx-link-external" style="color:#3b82f6;font-size:.9rem;flex-shrink:0;"></i>
                        </div>
                        <!-- Attachment drop zone (same design as transaction form pages) -->
                        <div id="commAttachZone" class="prod-attach-zone" onclick="_attachZoneTrigger('CommAttach', event)">
                            <div id="commAttachEmpty" class="prod-attach-empty">
                                <i class="bx bx-upload" id="commAttachIcon" style="font-size:1.4rem;color:#9ca3af;display:block;margin-bottom:3px;"></i>
                                <div id="commAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop files here</div>
                            </div>
                        </div>
                        <div id="commAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                        <div id="commAttachHint" style="font-size:.7rem;color:#9ca3af;margin-top:4px;"></div>
                        <input type="file" id="commAttachInput" multiple style="display:none;">
                    </div>
                </div>

            </div>

            <!-- Footer — static -->
            <div class="modal-footer border-top px-4 py-2 flex-shrink-0">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal"><?php echo t('cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="SendCommBtn">
                    <i class="bx bx-send me-1"></i><span id="SendCommBtnLabel">Send</span>
                </button>
            </div>

        </div>
    </div>
</div>

<input type="hidden" id="CommActiveType"    value="">
<input type="hidden" id="CommRecipientType" value="">
<input type="hidden" id="CommRecipientUIDs" value="">
<input type="hidden" id="CommModuleUID"     value="">
<input type="hidden" id="CommRecordUID"     value="">

<!-- ── File Preview Modal (sits above SendCommModal) ─────────────────────────
     Same stacking technique as #imagePreviewModal in imagepreview_modal.php:
     data-bs-backdrop="false"  → no second backdrop drawn over the email modal
     z-index:2000              → floats above Bootstrap's default 1055
─────────────────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="false" style="z-index:2000;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:min(92vw,860px);">
        <div class="modal-content border-0 shadow-lg" style="background:#0f172a;border-radius:14px;overflow:hidden;">

            <!-- Header bar — matches #imagePreviewModal style -->
            <div class="d-flex align-items-center gap-2 px-3 flex-shrink-0" style="background:rgba(0,0,0,.55);height:46px;">
                <i id="filePreviewIcon" class="bx bxs-file-pdf" style="font-size:1rem;color:#ef4444;flex-shrink:0;"></i>
                <span id="filePreviewTitle" style="font-size:.88rem;font-weight:600;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;">File Preview</span>
                <button type="button" data-bs-dismiss="modal" aria-label="Close"
                    style="flex-shrink:0;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:6px;color:#fff;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <i class="bx bx-x" style="font-size:1.3rem;line-height:1;"></i>
                </button>
            </div>

            <!-- File content area (iframe for PDF/docs, img for images) -->
            <div id="filePreviewContent" style="min-height:320px;max-height:78vh;overflow:hidden;background:#1e293b;"></div>

        </div>
    </div>
</div>

