<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Header -->
<div class="catg-filter-header">
    <span class="catg-filter-title"><i class="bx bx-package me-1"></i> Product Type</span>
    <button type="button" class="catg-filter-close-btn" onclick="closeProductTypeFilter()" title="Close">&times;</button>
</div>

<!-- List -->
<div class="catg-list" style="max-height:none; padding:8px;">
    <label class="catg-list-item">
        <input class="form-check-input ptype-checkbox" type="checkbox" value="Product" id="ptypeProduct">
        <span><i class="bx bx-cube-alt me-1 text-primary" style="font-size:0.9rem;"></i> Product</span>
    </label>
    <label class="catg-list-item">
        <input class="form-check-input ptype-checkbox" type="checkbox" value="Service" id="ptypeService">
        <span><i class="bx bx-wrench me-1 text-info" style="font-size:0.9rem;"></i> Service</span>
    </label>
</div>

<!-- Footer -->
<div class="catg-filter-footer">
    <button type="button" class="btn btn-primary" onclick="applyProductTypeFilter()"><i class="bx bx-check me-1"></i>Apply</button>
    <button type="button" class="btn btn-outline-secondary" onclick="resetProductTypeFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
</div>
