<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="plSelectModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:720px;">
        <div class="modal-content" style="border:none;border-radius:10px;">

            <div class="vtm-banner" style="--vtm-color:#696cff;--vtm-bg:#f0efff;--vtm-icon-bg:rgba(105,108,255,.13);border-radius:10px 10px 0 0;">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-purchase-tag"></i></div>
                        <div>
                            <div class="vtm-doc-number">Select Price List</div>
                            <div class="vtm-doc-meta" id="plSelectMeta"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="plSelectCards" style="padding:16px;display:flex;flex-direction:column;gap:10px;max-height:60vh;overflow-y:auto;"></div>

            <div style="padding:12px 16px;border-top:1px solid #e9ecef;background:#fafafa;border-radius:0 0 10px 10px;">
                <button type="button" class="btn btn-outline-secondary w-100" id="plSelectNone" style="font-size:.85rem;">
                    Continue Without Price List
                </button>
            </div>

        </div>
    </div>
</div>
