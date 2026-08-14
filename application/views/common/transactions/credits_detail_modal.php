<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Credits Detail Modal — informational; JS populates via AJAX on badge click -->
<div class="modal fade" id="creditsDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="margin-top:60px;">
        <div class="modal-content">

            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#856404;--vtm-bg:#fff8e1;--vtm-icon-bg:rgba(133,100,4,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-wallet"></i></div>
                        <div>
                            <div class="vtm-doc-number">Customer Credits</div>
                            <div class="vtm-doc-meta">On Account &amp; Advance payments available</div>
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

                <div id="credDetailLoading" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                    <span class="text-muted" style="font-size:.85rem;">Loading credit details...</span>
                </div>

                <div id="credDetailContent" class="d-none">

                    <div class="px-3 pt-3 pb-2">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bx bx-wallet" style="color:#856404;font-size:.95rem;"></i>
                            <span class="fw-semibold" style="font-size:.83rem;color:#856404;">On Account Credits</span>
                            <span class="badge ms-1" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;font-weight:700;" id="credOaTotal">— 0.00</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                                <thead style="background:#fff3cd;">
                                    <tr>
                                        <th style="color:#856404;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;">Invoice No</th>
                                        <th style="color:#856404;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;">Date</th>
                                        <th style="color:#856404;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;" class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="credDetailOaBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="mx-3 my-0"/>

                    <div class="px-3 pt-2 pb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bx bx-coin-stack" style="color:#856404;font-size:.95rem;"></i>
                            <span class="fw-semibold" style="font-size:.83rem;color:#856404;">Advance Payments</span>
                            <span class="badge ms-1" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;font-weight:700;" id="credAdvTotal">— 0.00</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                                <thead style="background:#fff3cd;">
                                    <tr>
                                        <th style="color:#856404;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;">Invoice No</th>
                                        <th style="color:#856404;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;">Date</th>
                                        <th style="color:#856404;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.04em;white-space:nowrap;" class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="credDetailAdvBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer py-2 justify-content-between border-top" style="border-color:#ffc107 !important;background:#fffdf0;">
                <div style="font-size:.82rem;color:#856404;" id="credDetailSummary"></div>
                <button type="button" class="btn btn-sm btn-outline-warning fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
