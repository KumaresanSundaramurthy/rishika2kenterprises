<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Category Form -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-dialog-right modal-lg">
        <div class="modal-content h-100 d-flex flex-column">

            <?php $FormAttribute = array('id' => 'categoryForm', 'name' => 'categoryForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/addCategory', $FormAttribute); ?>

            <div class="modal-header">
                <h5 class="modal-title" id="CatgModalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <input type="hidden" name="CategoryUID" id="CategoryUID" value="0" />

            <div class="modal-body flex-grow-1 overflow-auto">
                <div class="mb-3">
                    <label class="form-label" for="CategoryName">Name <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="CategoryName" placeholder="Enter category name" name="CategoryName" maxlength="100" required />
                </div>
                <div class="mb-3">
                    <label for="CategoryDescription" class="form-label">Description </label>
                    <textarea class="form-control" rows="2" name="CategoryDescription" id="CategoryDescription" placeholder="Description"></textarea>
                </div>

                <div class="mb-3 dropzone needsclick p-3 dz-clickable" id="DropzoneOneBasic">
                    <div class="dz-message needsclick">
                        <p class="h4 needsclick pt-4 mb-2">Drag and drop your image here</p>
                        <p class="h6 text-body-secondary d-block fw-normal mb-3">or</p>
                        <span class="needsclick btn btn-sm btn-label-primary" id="btnBrowse">Browse image</span>
                    </div>
                </div>

            </div>

            <div id="catgFormAlert" class="d-none col-lg-12 px-4 pt-4" role="alert"></div>

            <div class="modal-footer border-top d-flex justify-content-end">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" class="btn btn-primary" id="CatgSaveButton">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>