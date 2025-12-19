<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if(sizeof($StorageTypeInfo) > 0) { ?>

    <!-- Storage Type List -->
    <div id="storageTypeList" style="max-height: 180px; overflow-y: auto;">
        <?php foreach ($StorageTypeInfo as $ptype) { ?>
                <div class="form-check mb-2 my-1 list-hover-bg">
                    <label class="form-check-label w-100 d-flex align-items-center">
                        <input class="form-check-input me-2 storagetype-checkbox" type="checkbox" value="<?php echo $ptype->StorageTypeUID; ?>">
                        <span><?php echo $ptype->Name; ?></span>
                    </label>
                </div>
        <?php } ?>
    </div>

    <div class="border-top pt-2 mt-2 d-flex justify-content-between gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetStorageTypeFilter()">Reset</button>
        <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyStorageTypeFilter()">Search</button>
        <button type="button" class="btn btn-sm btn-outline-dark w-100" onclick="closeStorageTypeFilter();">Close</button>
    </div>

<?php } else { ?>
    
    <div class="d-flex flex-column justify-content-center align-items-center text-muted" style="height:100%;">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span>No record found</span>
    </div>

<?php } ?>