<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Thermal Receipt Print Modal ──────────────────────────────────────── -->
<div class="modal fade" id="thermalPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" style="max-width:600px">
        <div class="modal-content">
            <!-- Banner header — same vtm-banner pattern as viewTransModal -->
            <div id="thermalPrintHeader" class="vtm-banner flex-shrink-0" style="--vtm-color:#696cff;--vtm-bg:#e8f0fe;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-printer" id="thermalPrintHeaderIcon" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);" id="thermalPrintHeaderTitle">Thermal Receipt Preview</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;" id="thermalPrintHeaderMeta"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <span id="thermalPrintHeaderBadge"></span>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-body p-2 bg-white" id="thermalPrintBody">
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-dark btn-sm d-none" id="thermalPrintBtn">
                    <i class="bx bx-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── A4 / Document Print Modal ────────────────────────────────────────── -->
<div class="modal fade" id="a4PrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-top">
        <div class="modal-content border-0 shadow-lg" style="border-radius:10px;overflow:hidden;">

            <!-- Toolbar -->
            <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom bg-white" style="min-height:52px;">
                <div class="fw-semibold text-truncate me-3" style="font-size:.88rem;max-width:340px;">
                    <i class="bx bx-file-blank text-primary me-1"></i>
                    <span id="a4ModalTitle">Document Preview</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <!-- Paper size -->
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="a4PaperSize" id="psA4" value="A4" checked>
                        <label class="btn btn-outline-secondary px-3" for="psA4">A4</label>
                        <input type="radio" class="btn-check" name="a4PaperSize" id="psA5" value="A5">
                        <label class="btn btn-outline-secondary px-3" for="psA5">A5</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="a4DownloadBtn" title="Download / Print to PDF">
                        <i class="bx bx-download"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-success px-3" id="a4PrintBtn">
                        <i class="bx bx-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-sm btn-danger px-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>

            <!-- Preview stage — dark background, scrollable -->
            <div id="a4PreviewStage"
                 style="background:#3c3c3c;overflow-y:auto;max-height:82vh;min-height:200px;">
                <div class="d-flex justify-content-center align-items-center" style="height:200px;">
                    <div class="spinner-border text-secondary"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── View Transaction Detail Modal ────────────────────────────────────── -->
<div class="modal fade" id="viewTransModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <a href="javascript:void(0);" id="viewTransEditBtn" style="display:none;" aria-hidden="true"></a>
            <!-- Banner header — populated instantly before modal shows -->
            <div id="viewTransModalHeader" class="vtm-banner d-none"></div>
            <!-- Body — shows loader until data arrives -->
            <div class="modal-body p-0" id="viewTransModalBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Transaction Attachment Viewer Modal ──────────────────────────────── -->
<div class="modal fade" id="transAttachModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="overflow:hidden;">
            <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal"
                style="top:14px;right:16px;z-index:10;background-color:rgba(255,255,255,.85);border-radius:50%;padding:6px;box-shadow:0 1px 4px rgba(0,0,0,.15);"
                aria-label="Close"></button>
            <div class="modal-body p-0">
                <div id="transAttachModalBanner" style="padding:14px 20px;">
                    <div class="d-flex align-items-center gap-3">
                        <div id="transAttachModalIconWrap" style="border-radius:10px;padding:9px 11px;">
                            <i class="bx bx-paperclip" style="font-size:1.7rem;display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:1rem;font-weight:800;" id="transAttachModalTitle">Attachments</div>
                            <div style="font-size:.77rem;color:#6c757d;margin-top:3px;">Click any file to preview</div>
                        </div>
                    </div>
                </div>
                <div style="padding:16px 20px;" id="transAttachGallery">
                    <div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/modals/attach_preview'); ?>
