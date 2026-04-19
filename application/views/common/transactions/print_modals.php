<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Thermal Receipt Print Modal ──────────────────────────────────────── -->
<div class="modal fade" id="thermalPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" style="max-width:600px">
        <div class="modal-content">
            <div class="modal-header p-3">
                <h6 class="modal-title text-primary fw-bold fs-6 mb-0">
                    <i class="bx bx-printer me-1"></i>Thermal Receipt Preview
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            <div class="modal-header p-3 d-flex justify-content-between align-items-center">
                <h6 class="modal-title fw-semibold text-primary mb-0" id="viewTransModalTitle">Transaction Details</h6>
                <div class="gap-2">
                    <a href="javascript:void(0);" id="viewTransEditBtn" class="btn btn-warning btn-sm me-2">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
            <div class="modal-body p-0" id="viewTransModalBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer py-2"></div>
        </div>
    </div>
</div>
