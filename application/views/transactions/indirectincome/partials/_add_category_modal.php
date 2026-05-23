<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Add / Edit Category Modal (shared by income form and category manager) -->
<div class="modal fade" id="addIncomeCategoryModal" tabindex="-1" aria-hidden="true"
     style="backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
    <div class="modal-dialog modal-sm modal-dialog-top-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#dcfce7;">
                        <i class="bx bx-tag modal-doc-icon-inner" style="color:#16a34a;"></i>
                    </div>
                    <h6 class="modal-title mb-0" id="incCatModalTitle">Add Category</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="saveIncomeCategoryBtn">
                        <i class="bx bx-check me-1"></i><span id="incCatSaveBtnLabel">Add</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>
            <div class="modal-body py-3">
                <input type="hidden" id="incCatModalUID" value="0">
                <input type="text" class="form-control" id="newIncomeCategoryName"
                       placeholder="Category name" maxlength="100" autocomplete="off">
                <div id="incCatSaveError" class="text-danger mt-1" style="font-size:.78rem;display:none;"></div>
            </div>
        </div>
    </div>
</div>
