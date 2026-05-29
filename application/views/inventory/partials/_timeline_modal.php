<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="timelineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top modal-xl" style="max-width:1140px;">
        <div class="modal-content" style="overflow:hidden;">

            <div class="vtm-banner" style="--vtm-color:#0284c7;--vtm-bg:#e0f2fe;--vtm-icon-bg:rgba(2,132,199,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-history"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">Stock History</div>
                            <div class="vtm-doc-meta" id="tlProductName"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
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
                                    <th style="padding:.6rem .75rem;text-align:right;">Cost Price</th>
                                    <th style="padding:.6rem .75rem;text-align:right;">Tax Amount</th>
                                    <th style="padding:.6rem .75rem;text-align:right;">Purchase Price</th>
                                    <th style="padding:.6rem .75rem;text-align:right;">Selling Price</th>
                                    <th style="padding:.6rem .75rem;">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="tlTableBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer align-items-center" style="background:#f8fafc;border-top:1px solid #e2e8f0;min-height:44px;">
                <span class="text-muted me-auto" style="font-size:.8rem;line-height:1.4;">
                    Showing last 200 movements. Purchase / Invoice / Return movements are recorded automatically.
                </span>
            </div>

        </div>
    </div>
</div>
