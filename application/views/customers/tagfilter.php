<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Header -->
<div class="catg-filter-header">
    <span class="catg-filter-title"><i class="bx bx-purchase-tag me-1"></i> Tag Filter</span>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($Tags)): ?>
            <span class="badge"><?php echo count($Tags); ?></span>
        <?php endif; ?>
        <button type="button" class="catg-filter-close-btn" onclick="closeCustTagFilter()" title="Close">&times;</button>
    </div>
</div>

<?php if (!empty($Tags)): ?>

    <!-- Search -->
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="custTagSearch" class="form-control" placeholder="Search tags...">
        </div>
    </div>

    <!-- Select All -->
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="selectAllCustTags" onchange="toggleAllCustTags(this)">
        <label class="small fw-semibold mb-0" for="selectAllCustTags" id="custTagSelectAllLabel">Select All</label>
    </div>

    <!-- List -->
    <div id="custTagList" class="catg-list" style="max-height:180px;">
        <?php foreach ($Tags as $tag): ?>
            <label class="catg-list-item">
                <input class="form-check-input cust-tag-chk" type="checkbox" value="<?php echo htmlspecialchars($tag); ?>">
                <span><?php echo htmlspecialchars($tag); ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="applyCustTagFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="resetCustTagFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>

<?php else: ?>

    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span style="font-size:0.8rem;">No tags found</span>
    </div>

<?php endif; ?>
