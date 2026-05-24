<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="timelineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:34px;height:34px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-history text-primary fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">Stock History</h6>
                        <div id="tlProductName" class="text-muted" style="font-size:.75rem;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">

                <!-- Loading state -->
                <div id="tlLoading" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <div class="text-muted mt-2" style="font-size:.82rem;">Loading history...</div>
                </div>

                <!-- Empty state -->
                <div id="tlEmpty" class="text-center py-5 d-none">
                    <i class="bx bx-history text-muted" style="font-size:2.5rem;"></i>
                    <div class="text-muted mt-2" style="font-size:.85rem;">No stock movements found for this item.</div>
                </div>

                <!-- Timeline table -->
                <div id="tlTableWrap" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                            <thead style="background:#f1f5f9;position:sticky;top:0;">
                                <tr>
                                    <th style="padding:.6rem .75rem;">Date</th>
                                    <th style="padding:.6rem .75rem;">Source</th>
                                    <th style="padding:.6rem .75rem;">Reference</th>
                                    <th style="padding:.6rem .75rem;text-align:center;">Movement</th>
                                    <th style="padding:.6rem .75rem;text-align:right;">Qty</th>
                                    <th style="padding:.6rem .75rem;text-align:right;">Unit Cost</th>
                                    <th style="padding:.6rem .75rem;">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="tlTableBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
                <small class="text-muted me-auto" style="font-size:.72rem;">
                    Showing last 200 movements. Purchase / Invoice / Return movements are recorded automatically.
                </small>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
