<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Custom Date Range Modal ── */

/* 1. Outer calendar fills the modal body */
#r2kCustomRangeModal .flatpickr-calendar {
    width: 100% !important;
    max-width: 100% !important;
    box-shadow: none !important;
}

/* 2. Inner container: two months side by side via flex */
#r2kCustomRangeModal .flatpickr-innerContainer {
    display: flex !important;
    width: 100% !important;
    overflow: visible !important;
}

/* 3. Each month's container takes exactly half */
#r2kCustomRangeModal .flatpickr-rContainer {
    flex: 1 1 0% !important;
    min-width: 0 !important;
    width: auto !important;
    overflow: visible !important;
}

/* 4. Day grid and weekday row fill their half */
#r2kCustomRangeModal .flatpickr-weekdays,
#r2kCustomRangeModal .flatpickr-weekdaycontainer,
#r2kCustomRangeModal .flatpickr-days {
    width: 100% !important;
}
#r2kCustomRangeModal .dayContainer {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
}

/* 5. Day cells and weekday labels fill 1/7 of their container */
#r2kCustomRangeModal .flatpickr-day {
    max-width: calc(100% / 7) !important;
    flex: 1 0 calc(100% / 7) !important;
}
#r2kCustomRangeModal span.flatpickr-weekday {
    flex: 1 !important;
    max-width: calc(100% / 7) !important;
}

/* 6. Month header row */
#r2kCustomRangeModal .flatpickr-calendar .flatpickr-months {
    display: flex !important;
    align-items: stretch !important;
}
/* 7. Each month header: tall enough for selects, no overflow into date grid */
#r2kCustomRangeModal .flatpickr-calendar .flatpickr-month {
    flex: 1 1 0% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 48px !important;
    height: auto !important;
    overflow: visible !important;
    padding: .4rem 0 !important;
}
/* 8. Month + year selects in a flex row, not absolutely positioned */
#r2kCustomRangeModal .flatpickr-calendar .flatpickr-current-month {
    position: static !important;
    width: auto !important;
    left: auto !important;
    padding: 0 !important;
    height: auto !important;
    display: flex !important;
    align-items: center !important;
    gap: 2px !important;
}

/* 9. Month & Year <select> styling */
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
    line-height: 1.4;
    outline: none;
    padding: 0 .25rem 0 .5ch;
    height: auto;
    width: auto;
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
                            id="r2kCrCancel"><?php echo t('cancel', 'Cancel'); ?></button>
                    <button type="button" class="btn btn-primary btn-sm px-4"
                            id="r2kCrApply" disabled>Apply</button>
                </div>
            </div>

        </div>
    </div>
</div>
