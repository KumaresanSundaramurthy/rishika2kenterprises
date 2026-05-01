<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Send Communication Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="SendCommModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;overflow:hidden;">

            <!-- Header — common theme -->
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div id="CommHeaderIcon" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#e8f0fe;">
                        <i class="bx bx-message-rounded fs-4 text-primary" id="CommHeaderIconI"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.95rem;" id="SendCommModalTitle">Send SMS</div>
                        <div class="text-muted" style="font-size:.75rem;">Compose and send your message</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- From -->
                <div class="mb-3 p-3 rounded-3" style="background:#f0f4ff;border:1px solid #d0dcff;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-envelope-open text-primary fs-5"></i>
                        <div>
                            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">From</div>
                            <div class="fw-semibold" style="font-size:.85rem;"><?php echo htmlspecialchars(getenv('MAIL_FROM_NAME') ?: 'R2K Enterprises'); ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars(getenv('MAIL_FROM_EMAIL') ?: ''); ?></div>
                        </div>
                    </div>
                </div>

                <!-- To -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8f9fa;border:1px solid #e9ecef;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-user-circle text-secondary fs-5"></i>
                        <div class="flex-grow-1">
                            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">To</div>
                            <div class="fw-semibold" style="font-size:.85rem;" id="CommToName">—</div>
                            <div class="text-muted" style="font-size:.75rem;" id="CommToContact"></div>
                        </div>
                        <div id="CommNoContactWarning" class="d-none">
                            <span class="badge bg-label-warning" style="font-size:.72rem;">
                                <i class="bx bx-error-circle me-1"></i><span id="CommNoContactText"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Type toggle -->
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm comm-type-tab active px-3" data-commtype="SMS" style="border-radius:20px;font-size:.8rem;">
                        <i class="bx bx-message-rounded me-1"></i>SMS
                    </button>
                    <button type="button" class="btn btn-sm comm-type-tab px-3" data-commtype="Email" style="border-radius:20px;font-size:.8rem;">
                        <i class="bx bx-envelope me-1"></i>Email
                    </button>
                </div>

                <!-- SMS Fields -->
                <div id="CommSmsFields">
                    <label class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                    <div id="CommSmsEditor" style="min-height:130px;border:1px solid #dee2e6;border-radius:6px;font-size:.88rem;"></div>
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
                        <label class="form-label fw-semibold small">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="CommEmailSubject" placeholder="Enter email subject" maxlength="255" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                        <div id="CommEmailEditor" style="min-height:180px;border:1px solid #dee2e6;border-radius:6px;font-size:.88rem;"></div>
                        <input type="hidden" id="CommEmailMessage">
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
