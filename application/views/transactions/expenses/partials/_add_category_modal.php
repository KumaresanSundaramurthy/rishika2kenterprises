<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Add Category Modal (nested over expense modal) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true"
     style="backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
    <div class="modal-dialog modal-sm modal-dialog-top-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#dcfce7;">
                        <i class="bx bx-tag modal-doc-icon-inner" style="color:#16a34a;"></i>
                    </div>
                    <h6 class="modal-title mb-0">Add Category</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="saveCategoryBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>
            <div class="modal-body py-3">
                <input type="text" class="form-control" id="newCategoryName" placeholder="Category name" maxlength="100">
            </div>
        </div>
    </div>
</div>
