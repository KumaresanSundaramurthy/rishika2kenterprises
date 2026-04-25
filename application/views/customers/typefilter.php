<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Header -->
<div class="catg-filter-header">
    <span class="catg-filter-title"><i class="bx bx-user-pin me-1"></i> Customer Type</span>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($CustomerTypeList)): ?>
            <span class="badge"><?php echo count($CustomerTypeList); ?></span>
        <?php endif; ?>
        <button type="button" class="catg-filter-close-btn" onclick="closeCustTypeFilter()" title="Close">&times;</button>
    </div>
</div>

<?php if (!empty($CustomerTypeList)): ?>

    <!-- Search -->
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="custTypeSearch" class="form-control" placeholder="Search types...">
        </div>
    </div>

    <!-- Select All -->
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="selectAllCustTypes" onchange="toggleAllCustTypes(this)">
        <label class="small fw-semibold mb-0" for="selectAllCustTypes" id="custTypeSelectAllLabel">Select All</label>
    </div>

    <!-- List -->
    <div id="custTypeList" class="catg-list" style="max-height:180px;">
        <?php foreach ($CustomerTypeList as $type): ?>
            <label class="catg-list-item">
                <input class="form-check-input cust-type-chk" type="checkbox" value="<?php echo (int)$type->CustomerTypeUID; ?>">
                <span><?php echo htmlspecialchars($type->TypeName); ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="applyCustTypeFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="resetCustTypeFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>

<?php else: ?>

    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span style="font-size:0.8rem;">No customer types found</span>
    </div>

<?php endif; ?>
