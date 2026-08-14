<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Credit Note Detail Modal — informational; JS populates via AJAX on badge click -->
<div class="modal fade" id="creditNoteDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="margin-top:60px;">
        <div class="modal-content">

            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#5c2d91;--vtm-bg:#f3e8ff;--vtm-icon-bg:rgba(92,45,145,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-receipt"></i></div>
                        <div>
                            <div class="vtm-doc-number">Credit Notes</div>
                            <div class="vtm-doc-meta">Pending credit notes available for this customer</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0">

                <div id="cnDetailLoading" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm me-2" style="color:#c084fc;"></div>
                    <span class="text-muted" style="font-size:.85rem;">Loading credit note details...</span>
                </div>

                <div id="cnDetailContent" class="d-none px-3 py-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                            <thead style="background:#f3e8ff;">
                                <tr>
                                    <th style="color:#5c2d91;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;">Credit Note No</th>
                                    <th style="color:#5c2d91;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;">Date</th>
                                    <th style="color:#5c2d91;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="cnDetailBody">
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer py-2 justify-content-between border-top" style="border-color:#c084fc !important;background:#faf5ff;">
                <div style="font-size:.82rem;color:#5c2d91;" id="cnDetailSummary"></div>
                <button type="button" class="btn btn-sm fw-semibold" style="border:1px solid #c084fc;color:#5c2d91;" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
