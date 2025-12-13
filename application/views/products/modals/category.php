<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Category Form -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'categoryForm', 'name' => 'categoryForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/addCategory', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="CatgModalTitle">Create Category</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary CatgSaveButton">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Close</button>
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
                    <div class="row d-flex">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="mb-3">
                                    <label class="form-label" for="CategoryName">Name <span style="color:red">*</span></label>
                                    <input type="text" class="form-control" id="CategoryName" placeholder="Enter category name" name="CategoryName" maxlength="100" required />
                                </div>
                                <div class="mb-3">
                                    <label for="CategoryDescription" class="form-label">Description </label>
                                    <textarea class="form-control" rows="2" name="CategoryDescription" id="CategoryDescription" placeholder="Description"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex justify-content-center align-items-center">
                            <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneTwoBasic">
                                <div class="dz-message needsclick text-center">
                                    <i class="upload-icon mb-3"></i>
                                    <p class="h5 needsclick mb-2">Drag and drop category here</p>
                                    <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>

        <?php echo form_close(); ?>

        </div>
    </div>
</div>