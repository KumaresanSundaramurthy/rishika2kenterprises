<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Common Image Gallery Preview Modal ────────────────────────────────────
     Usage (JS):  openImageGallery(images, startIndex)
     images = [{url:'...', name:'...'}]
     Or single:   openImageGallery([{url:'https://...', name:'photo.jpg'}])
     Keyboard:    ← → to navigate, Esc to close
─────────────────────────────────────────────────────────────────────────── -->
<!-- z-index 2000 ensures this sits above any other open modal (Bootstrap default is 1055) -->
<!-- data-bs-backdrop="false" prevents a second backdrop from appearing over the parent modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="false" style="z-index:2000;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:min(92vw,860px);">
        <div class="modal-content border-0 shadow-lg" style="background:#0f172a;border-radius:14px;overflow:hidden;">

            <!-- Header bar -->
            <div class="d-flex align-items-center gap-2 px-3 flex-shrink-0" style="background:rgba(0,0,0,.55);height:46px;">
                <i class="bx bx-image text-white" style="font-size:1rem;flex-shrink:0;"></i>
                <span id="imgPreviewTitle" style="font-size:.88rem;font-weight:600;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;">Image Preview</span>
                <span id="imgPreviewCounter" style="font-size:.78rem;color:#94a3b8;flex-shrink:0;white-space:nowrap;"></span>
                <button type="button" data-bs-dismiss="modal" aria-label="Close"
                    style="flex-shrink:0;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:6px;color:#fff;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <i class="bx bx-x" style="font-size:1.3rem;line-height:1;"></i>
                </button>
            </div>

            <!-- Image area -->
            <div class="position-relative d-flex align-items-center justify-content-center" style="background:#0f172a;min-height:320px;max-height:78vh;">

                <!-- Prev button -->
                <button id="imgPreviewPrev" onclick="imgGalleryNav(-1)"
                    style="position:absolute;left:10px;top:50%;transform:translateY(-50%);z-index:10;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.2);color:#fff;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;">
                    <i class="bx bx-chevron-left" style="font-size:1.4rem;"></i>
                </button>

                <img id="imagePreviewTarget" src="" alt=""
                    style="max-width:100%;max-height:78vh;object-fit:contain;display:block;border-radius:4px;padding:12px;">

                <!-- Next button -->
                <button id="imgPreviewNext" onclick="imgGalleryNav(1)"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);z-index:10;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.2);color:#fff;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;">
                    <i class="bx bx-chevron-right" style="font-size:1.4rem;"></i>
                </button>

            </div>

        </div>
    </div>
</div>
