<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if(sizeof($Storage) > 0) { ?>

    <!-- Search Box -->
    <input type="text" id="storageSearch" class="form-control form-control-sm mb-4" placeholder="Search storage...">

    <div class="form-check mb-2">
        <label class="form-check-label w-100 d-flex align-items-center">
            <input type="checkbox" class="form-check-input me-2 my-1" id="selectAllStorage" onchange="toggleAllStorage(this)">
            <label class="form-check-label" for="selectAllStorage" id="str_selectAllLabel">Select All</label>
        </label>
    </div>

    <!-- Storage List -->
    <div id="storageList" style="max-height: 180px; overflow-y: auto;">
        
        <?php foreach ($Storage as $strg) { ?>
                <div class="form-check mb-2 my-1 list-hover-bg">
                    <label class="form-check-label w-100 d-flex align-items-center">
                        <input class="form-check-input me-2 storage-checkbox" type="checkbox" value="<?php echo $strg->StorageUID; ?>">
                        <span><?php echo $strg->Name; ?></span>
                    </label>
                </div>
        <?php } ?>
        
    </div>

    <div class="border-top pt-2 mt-2 d-flex justify-content-between gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetStorageFilter()">Reset</button>
        <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyStorageFilter()">Search</button>
        <button type="button" class="btn btn-sm btn-outline-dark w-100" onclick="closeStorageFilter();">Close</button>
    </div>

<?php } else { ?>
    
    <div class="d-flex flex-column justify-content-center align-items-center text-muted" style="height:100%;">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span>No record found</span>
    </div>

<?php } ?>