<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if(sizeof($Categories) > 0) { ?>

    <!-- Search Box -->
    <div class="d-flex align-items-center mb-2">
        <input type="text" id="categorySearch" class="form-control form-control-sm me-2" style="width:95%;" placeholder="Search category...">
        <button type="button" class="btn btn-outline-success" data-toggle="tooltip" title="Load All Data" onclick="refreshSearchCateg(this)">
            <i class="bx bx-refresh"></i>
        </button>
    </div>

    <div class="form-check mb-2">
        <label class="form-check-label w-100 d-flex align-items-center">
            <input type="checkbox" class="form-check-input me-2 my-1" id="selectAllCategories" onchange="toggleAllCategories(this)">
            <label class="form-check-label" for="selectAllCategories" id="selectAllLabel">Select All</label>
        </label>
    </div>

    <!-- Category List -->
    <div id="categoryList" style="max-height: 180px; overflow-y: auto;">
        <?php foreach ($Categories as $catg) { ?>
                <div class="form-check mb-2 my-1 list-hover-bg">
                    <label class="form-check-label w-100 d-flex align-items-center">
                        <input class="form-check-input me-2 category-checkbox" type="checkbox" value="<?php echo $catg->CategoryUID; ?>">
                        <span><?php echo $catg->Name; ?></span>
                    </label>
                </div>
        <?php } ?>
    </div>

    <div class="border-top pt-2 mt-2 d-flex justify-content-between gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetCategoryFilter()">Reset</button>
        <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyCategoryFilter()">Search</button>
        <button type="button" class="btn btn-sm btn-outline-dark w-100" onclick="closeCategoryFilter();">Close</button>
    </div>

<?php } else { ?>
    
    <div class="d-flex flex-column justify-content-center align-items-center text-muted" style="height:100%;">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span>No record found</span>
    </div>

<?php } ?>