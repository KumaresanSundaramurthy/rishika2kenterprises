<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="barcodePrintModal" tabindex="-1" aria-labelledby="barcodePrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" style="max-width:740px;">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header py-2 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx fs-4" id="bcModalIcon"></i>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold" style="font-size:.95rem;" id="bcModalTitle">Barcode</span>
                            <span class="badge bg-label-warning" style="font-size:.6rem;vertical-align:middle;padding:3px 6px;">BETA</span>
                        </div>
                        <div class="text-muted" id="barcodeProductSubtitle" style="font-size:.75rem;max-width:500px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Layout tabs -->
            <div class="border-bottom bg-body-tertiary" style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                <ul class="nav nav-tabs border-0 flex-nowrap px-3 pt-0" id="bcLayoutTabs" style="gap:0;">
                    <li class="nav-item"><a class="nav-link active px-3 py-2" href="#" data-layout="1x2" style="font-size:.82rem;white-space:nowrap;">1 × 2</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="1x4" style="font-size:.82rem;white-space:nowrap;">1 × 4</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="1x1" style="font-size:.82rem;white-space:nowrap;">1 × 1</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="1x3" style="font-size:.82rem;white-space:nowrap;">1 × 3</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="a4_8x2" style="font-size:.82rem;white-space:nowrap;">A4 (8 × 2)</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="a4_10x4" style="font-size:.82rem;white-space:nowrap;">A4 40 Labels (10 × 4)</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="a4_13x5" style="font-size:.82rem;white-space:nowrap;">A4 65 Labels (13 × 5)</a></li>
                    <li class="nav-item"><a class="nav-link px-3 py-2" href="#" data-layout="square" style="font-size:.82rem;white-space:nowrap;">Square Label</a></li>
                </ul>
            </div>

            <!-- Preview area -->
            <div class="modal-body p-3" style="background:#f4f5f7;min-height:220px;max-height:420px;overflow-y:auto;" id="bcPreviewWrap">
                <div id="bcPreviewArea" class="d-flex flex-wrap" style="gap:8px;align-items:flex-start;justify-content:flex-start;">
                    <div class="text-muted small d-flex align-items-center gap-2 p-3">
                        <i class="bx bx-loader-alt bx-spin"></i> Generating preview...
                    </div>
                </div>
                <div id="bcPreviewNote" class="text-center text-muted mt-2" style="font-size:.72rem;"></div>
            </div>

            <!-- Footer -->
            <div class="modal-footer py-2 border-top">
                <button type="button" id="btnEditBcProduct" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bx bx-edit me-1"></i>Edit
                </button>
                <button type="button" id="btnPrintBarcode" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-printer me-1"></i>Print
                </button>
            </div>

        </div>
    </div>
</div>
