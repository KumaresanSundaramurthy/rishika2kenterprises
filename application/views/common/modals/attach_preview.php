<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Attachment Preview Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="attachPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height:92vh;max-height:92vh;">
        <div class="modal-content d-flex flex-column" style="height:100%;background:#1a1a2e;overflow:hidden;">
            <div class="d-flex align-items-center gap-2 px-3 flex-shrink-0" style="background:rgba(0,0,0,.6);height:44px;">
                <i class="bx bx-file text-white" style="font-size:1rem;flex-shrink:0;"></i>
                <span id="attachPreviewTitle" style="font-size:.88rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;">Preview</span>
                <button type="button" data-bs-dismiss="modal" aria-label="Close"
                    style="flex-shrink:0;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);border-radius:6px;color:#fff;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;">
                    <i class="bx bx-x" style="font-size:1.25rem;line-height:1;"></i>
                </button>
            </div>
            <div id="attachPreviewBody" class="flex-grow-1 overflow-auto" style="background:#1a1a2e;">
                <div class="text-center py-5"><span class="spinner-border text-light"></span></div>
            </div>
        </div>
    </div>
</div>
