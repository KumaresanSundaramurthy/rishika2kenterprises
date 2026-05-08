<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Send Communication Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="SendCommModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content comm-modal-content">

            <!-- Header -->
            <div class="comm-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="comm-header-icon" id="CommHeaderIcon">
                        <i class="bx bx-message-rounded comm-header-icon-i" id="CommHeaderIconI"></i>
                    </div>
                    <div>
                        <div class="comm-modal-title" id="SendCommModalTitle">Send SMS</div>
                        <div class="comm-modal-subtitle">Compose and send your message</div>
                    </div>
                </div>
                <button type="button" class="comm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- From + To side by side -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="comm-from-box h-100">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-envelope-open text-primary fs-5 flex-shrink-0"></i>
                                <div class="min-w-0">
                                    <div class="comm-field-label">From</div>
                                    <div class="comm-from-name text-truncate"><?php echo htmlspecialchars(getenv('MAIL_FROM_NAME') ?: 'R2K Enterprises'); ?></div>
                                    <div class="comm-from-email text-truncate"><?php echo htmlspecialchars(getenv('MAIL_FROM_EMAIL') ?: ''); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="comm-to-box h-100">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-user-circle text-secondary fs-5 flex-shrink-0"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="comm-field-label">To</div>
                                    <div class="comm-to-name text-truncate" id="CommToName">—</div>
                                    <div class="comm-to-contact text-truncate" id="CommToContact"></div>
                                </div>
                                <div id="CommNoContactWarning" class="d-none flex-shrink-0">
                                    <span class="badge bg-label-warning comm-no-contact-badge">
                                        <i class="bx bx-error-circle me-1"></i><span id="CommNoContactText"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type toggle -->
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm comm-type-tab active px-3" data-commtype="SMS">
                        <i class="bx bx-message-rounded me-1"></i>SMS
                    </button>
                    <button type="button" class="btn btn-sm comm-type-tab px-3" data-commtype="Email">
                        <i class="bx bx-envelope me-1"></i>Email
                    </button>
                </div>

                <!-- SMS Fields -->
                <div id="CommSmsFields">
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
                    <!-- Attachments -->
                    <div class="mb-1">
                        <label class="form-label fw-semibold small">
                            Attachments <span class="text-muted fw-normal">(max 3 files &bull; 3 MB each)</span>
                        </label>
                        <div id="CommAttachDropzone" class="comm-attach-dropzone dropzone needsclick dz-clickable">
                            <div class="dz-message needsclick">
                                <i class="bx bx-cloud-upload comm-attach-icon"></i>
                                <div class="comm-attach-hint">Drag &amp; drop or <span class="text-primary">browse</span></div>
                                <div class="comm-attach-sub">PDF, JPG, PNG &bull; Max 3 MB per file</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer border-top px-4 py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="SendCommBtn">
                    <i class="bx bx-send me-1"></i><span id="SendCommBtnLabel">Send SMS</span>
                </button>
            </div>

        </div>
    </div>
</div>

<input type="hidden" id="CommActiveType"    value="SMS">
<input type="hidden" id="CommRecipientType" value="">
<input type="hidden" id="CommRecipientUIDs" value="">
<input type="hidden" id="CommModuleUID"     value="">
<input type="hidden" id="CommRecordUID"     value="">
