<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Header -->
<div class="catg-filter-header">
    <span class="catg-filter-title"><i class="bx bx-toggle-right me-1"></i> Status Filter</span>
    <button type="button" class="catg-filter-close-btn" onclick="closeStatusFilter()" title="Close">&times;</button>
</div>

<!-- List -->
<div class="catg-list" style="max-height:none; padding:8px;">
    <label class="catg-list-item">
        <input class="form-check-input status-checkbox" type="checkbox" value="1" id="statusActive">
        <span><i class="bx bx-check-circle me-1 text-success" style="font-size:0.9rem;"></i> Active</span>
    </label>
    <label class="catg-list-item">
        <input class="form-check-input status-checkbox" type="checkbox" value="0" id="statusInActive">
        <span><i class="bx bx-x-circle me-1 text-danger" style="font-size:0.9rem;"></i> In-Active</span>
    </label>
</div>

<!-- Footer -->
<div class="catg-filter-footer">
    <button type="button" class="btn btn-primary" onclick="applyStatusFilter()"><i class="bx bx-check me-1"></i>Apply</button>
    <button type="button" class="btn btn-outline-secondary" onclick="resetStatusFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
</div>
