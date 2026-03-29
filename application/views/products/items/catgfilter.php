<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Header -->
<div class="catg-filter-header">
    <span class="catg-filter-title"><i class="bx bx-layer me-1"></i> Category Filter</span>
    <div class="d-flex align-items-center gap-2">
        <?php if(sizeof($Categories) > 0): ?>
            <span class="badge"><?php echo count($Categories); ?></span>
        <?php endif; ?>
        <button type="button" class="catg-filter-close-btn" onclick="closeCategoryFilter()" title="Close">&times;</button>
    </div>
</div>

<?php if(sizeof($Categories) > 0): ?>

    <!-- Search -->
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="categorySearch" class="form-control" placeholder="Search categories...">
        </div>
    </div>

    <!-- Select All -->
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="selectAllCategories" onchange="toggleAllCategories(this)">
        <label class="small fw-semibold mb-0" for="selectAllCategories" id="selectAllLabel">Select All</label>
    </div>

    <!-- List -->
    <div id="categoryList" class="catg-list" style="max-height:180px;">
        <?php foreach ($Categories as $catg): ?>
            <label class="catg-list-item">
                <input class="form-check-input category-checkbox" type="checkbox" value="<?php echo $catg->CategoryUID; ?>">
                <span><?php echo htmlspecialchars($catg->Name); ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="applyCategoryFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="resetCategoryFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>

<?php else: ?>

    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span style="font-size:0.8rem;">No categories found</span>
    </div>

<?php endif; ?>
