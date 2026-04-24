<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="barcodePrintModal" tabindex="-1" aria-labelledby="barcodePrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" style="max-width:480px;">
        <div class="modal-content">

            <div class="modal-header py-2 px-4 border-bottom">
                <div>
                    <h6 class="modal-title fw-semibold mb-0" id="barcodePrintModalLabel">
                        <i class="bx bx-barcode me-1 text-primary" id="bcModalIcon"></i>
                        <span id="bcModalTitle">Print Barcode</span>
                    </h6>
                    <div class="text-muted mt-1" id="barcodeProductSubtitle" style="font-size:.78rem;"></div>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <!-- Barcode section -->
                <div id="bcSection" class="border rounded p-3 text-center mb-3">
                    <div class="mb-2" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;">Barcode Preview</div>
                    <svg id="barcodeSvg" style="max-width:100%;"></svg>
                </div>

                <!-- QR Code section -->
                <div id="qrSection" class="border rounded p-3 text-center mb-3 d-none">
                    <div class="mb-2" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;">QR Code Preview</div>
                    <div id="qrcodeDiv" class="d-inline-block"></div>
                </div>

                <!-- Print copies selector -->
                <div class="border-top pt-3">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size:.75rem;font-weight:600;color:#6c757d;white-space:nowrap;">Print Copies:</span>
                        <div class="btn-group btn-group-sm flex-grow-1" role="group">
                            <input type="radio" class="btn-check" name="printCopies" id="pc1" value="1" checked autocomplete="off">
                            <label class="btn btn-outline-primary" for="pc1">1</label>
                            <input type="radio" class="btn-check" name="printCopies" id="pc2" value="2" autocomplete="off">
                            <label class="btn btn-outline-primary" for="pc2">2</label>
                            <input type="radio" class="btn-check" name="printCopies" id="pc4" value="4" autocomplete="off">
                            <label class="btn btn-outline-primary" for="pc4">4</label>
                            <input type="radio" class="btn-check" name="printCopies" id="pc8" value="8" autocomplete="off">
                            <label class="btn btn-outline-primary" for="pc8">8</label>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnPrintBarcode" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-printer me-1"></i><span id="bcPrintBtnText">Print</span>
                </button>
            </div>

        </div>
    </div>
</div>
