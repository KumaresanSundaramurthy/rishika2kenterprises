<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="dropdown">
    <button class="r2k-dd-btn<?php echo (!empty($SavedDateRange) && $SavedDateRange !== 'all') ? ' r2k-date-active' : ''; ?>" type="button" id="dateFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <i class="bx bx-calendar"></i>
        <span id="dateFilterLabel"><?php echo htmlspecialchars((!empty($SavedDateLabel) && $SavedDateLabel !== 'All Dates') ? $SavedDateLabel : 'This Month'); ?></span><?php if (!empty($SavedDateFromDisplay ?? '')): ?> <strong id="dateFilterDates" class="r2k-df-dates"><?php echo ($SavedDateFromDisplay === $SavedDateToDisplay) ? htmlspecialchars($SavedDateFromDisplay) : htmlspecialchars($SavedDateFromDisplay) . ' – ' . htmlspecialchars($SavedDateToDisplay); ?></strong><?php else: ?><strong id="dateFilterDates" class="r2k-df-dates" style="display:none;"></strong><?php endif; ?>
        <i class="bx bx-chevron-down" style="font-size:.75rem;"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow" id="dateFilterMenu" style="width:240px;max-height:420px;overflow-y:auto;font-size:.82rem;z-index:9999;"></ul>
</div>
