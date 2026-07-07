<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Custom Date Range Modal: force 2-month side-by-side layout ── */
#r2kCustomRangeModal .flatpickr-calendar .flatpickr-months {
    display: flex !important;
    align-items: stretch !important;
}
#r2kCustomRangeModal .flatpickr-calendar .flatpickr-month {
    flex: 1 1 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    block-size: auto !important;
    overflow: visible !important;
    padding: .4rem 0 !important;
}
#r2kCustomRangeModal .flatpickr-calendar .flatpickr-current-month {
    position: static !important;
    inline-size: auto !important;
    inset-inline-start: 0 !important;
    padding: 0 !important;
    block-size: auto !important;
}
/* Month & Year <select> — mirror .flatpickr-monthDropdown-months styling */
#r2kCustomRangeModal .r2k-mo-sel,
#r2kCustomRangeModal .r2k-yr-sel {
    appearance: menulist;
    -webkit-appearance: menulist;
    background-color: var(--bs-paper-bg);
    border: 0;
    border-radius: 0;
    color: var(--bs-heading-color);
    cursor: pointer;
    font-family: inherit;
    font-size: 1.0625rem;
    font-weight: 400;
    line-height: inherit;
    outline: none;
    padding-inline: .5ch 0;
    vertical-align: middle;
    block-size: 2.25rem;
    inline-size: auto;
}
</style>

<!-- Custom Date Range Modal — wired entirely by js/common/datefilter.js -->
<div class="modal fade" id="r2kCustomRangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:700px;">
        <div class="modal-content" style="overflow:hidden;">

            <!-- vtm-banner header — matches viewTransModal style -->
            <div class="vtm-banner flex-shrink-0"
                 style="--vtm-color:#696cff;--vtm-bg:#eeeeff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-calendar"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">Custom Date Range</div>
                            <div class="vtm-doc-meta">Select a start and end date</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" id="r2kCrClose" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body px-4 pt-3 pb-1">
                <!--
                    Flatpickr target — datefilter.js initialises a range picker on this input
                    with inline:true, showMonths:2, altInput:true so the calendar renders
                    directly below this field and the altInput shows the selected range
                    in the user's date format.
                -->
                <input type="text" id="r2kCrPicker" placeholder="Select date range" readonly>
            </div>

            <div class="modal-footer border-top py-2 px-4 justify-content-between align-items-center">
                <!-- Live formatted range preview — e.g.  01 Jul 2026 – 25 Jul 2026 -->
                <span id="r2kCrDisplay" class="text-muted" style="font-size:.82rem;"></span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            id="r2kCrCancel">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4"
                            id="r2kCrApply" disabled>Apply</button>
                </div>
            </div>

        </div>
    </div>
</div>
