<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Category Add Modal — shared across transaction pages -->
<div class="modal fade" id="CategoryFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-category text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="CategoryFormModalTitle">Create Category</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="CategoryFormSaveBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <div class="modal-body p-3">
                <form id="CFCategoryForm" autocomplete="off" novalidate>
                    <input type="hidden" name="CategoryUID" id="CF_CategoryUID" value="0" />

                    <div class="mb-3">
                        <label class="form-label" for="CF_CategoryName">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="CF_CategoryName" name="CategoryName"
                               placeholder="Enter category name" maxlength="100" required />
                    </div>
                    <div class="mb-3">
                        <label for="CF_CategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="CategoryDescription"
                                  id="CF_CategoryDescription" placeholder="Description"></textarea>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
