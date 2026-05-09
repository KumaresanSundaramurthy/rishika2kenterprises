<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Footer -->
<div class="card" style="border-radius: 0;">
    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
        <div class="mb-2 mb-md-0">
            ©
            <script>
                document.write(new Date().getFullYear());
            </script>
            , made with ❤️ by <?php echo strtoupper(getSiteConfiguration()->ShortName); ?>
        </div>
    </div>
</div>
<!-- / Footer -->

<!-- ── Attachment Preview Modal (shared across all transaction form pages) ── -->
<div class="modal fade" id="attachPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height:92vh;">
        <div class="modal-content d-flex flex-column" style="height:100%;background:#1a1a2e;overflow:hidden;">
            <div class="d-flex align-items-center gap-2 px-3 py-2 flex-shrink-0" style="background:rgba(0,0,0,.5);">
                <i class="bx bx-file text-white" style="font-size:1rem;flex-shrink:0;"></i>
                <span id="attachPreviewTitle" style="font-size:.88rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;">Preview</span>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close" style="flex-shrink:0;"></button>
            </div>
            <div id="attachPreviewContent" class="flex-grow-1 overflow-auto">
                <div class="text-center py-5"><span class="spinner-border text-light"></span></div>
            </div>
        </div>
    </div>
</div>