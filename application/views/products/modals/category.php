<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Category Form -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-lg" id="categoryModalDialog">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'categoryForm', 'name' => 'categoryForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/addCategory', $FormAttribute); ?>

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-category text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="CatgModalTitle">Create Category</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-primary CatgSaveButton"><i class="bx bx-check me-1"></i>Save</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x me-1"></i>Close</button>
                </div>
            </div>

            <input type="hidden" name="CategoryUID" id="CategoryUID" value="0" />

            <div class="d-none col-lg-12 px-5 mt-3 catgFormAlert" role="alert"></div>

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
                <div class="card-body p-2 mb-3">

                    <!-- General Details -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3">
                        <h5 class="modal-title mb-0">Basic Details</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-12">
                            <label class="form-label" for="CategoryName">Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" id="CategoryName" placeholder="Enter category name" name="CategoryName" maxlength="100" required />
                        </div>
                        <div class="mb-3 col-12">
                            <label for="CategoryDescription" class="form-label">Description</label>
                            <textarea class="form-control" rows="3" name="CategoryDescription" id="CategoryDescription" placeholder="Description"></textarea>
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151;">Images <span class="text-muted fw-normal">(max 3 · 3 MB total)</span></label>
                            <!-- Drop zone — always visible, click or drag to add images -->
                            <div id="catgAttachZone" class="prod-attach-zone" onclick="catgAttachTrigger(event)">
                                <div id="catgAttachEmpty" class="prod-attach-empty">
                                    <i class="bx bx-image-add" id="catgAttachIcon" style="font-size:2rem;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                                    <div id="catgAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop images</div>
                                    <div id="catgAttachHint" style="font-size:.7rem;color:#9ca3af;margin-top:3px;">JPG, GIF or PNG · Max 3 · 3 MB total</div>
                                </div>
                            </div>
                            <!-- Preview list — outside drop zone so thumbnail clicks don't trigger file input -->
                            <div id="catgAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                            <input type="file" id="catgAttachInput" multiple accept="image/jpeg,image/png,image/gif" style="display:none;">
                            <input type="hidden" id="catgAttachDeleteUIDs" name="CatgAttachDeleteUIDs" value="">
                        </div>
                    </div>
                    
                </div>
            </div>

        <?php echo form_close(); ?>

        </div>
    </div>
</div>