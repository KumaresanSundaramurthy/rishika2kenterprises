<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Sizes Form -->
<div class="modal fade" id="sizesModal" tabindex="-1" aria-labelledby="sizesModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-dialog-right modal-lg">
        <div class="modal-content h-100 d-flex flex-column">

            <?php $FormAttribute = array('id' => 'SizesForm', 'name' => 'SizesForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/sizes', $FormAttribute); ?>

            <div class="modal-header">
                <h5 class="modal-title" id="SizeModalTitle">Add Size</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <input type="hidden" name="SizeUID" id="SizeUID" value="0" />
            
            <div class="modal-body flex-grow-1 overflow-auto">
                <div class="mb-3">
                    <label class="form-label" for="SizesName">Name <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="SizesName" placeholder="Enter size name" name="SizesName" maxlength="100" required />
                </div>
                <div class="mb-3">
                    <label for="SizesDescription" class="form-label">Description </label>
                    <textarea class="form-control" rows="3" name="SizesDescription" id="SizesDescription" placeholder="Description"></textarea>
                </div>
            </div>

            <div id="sizeFormAlert" class="d-none col-lg-12 px-4" role="alert"></div>

            <div class="modal-footer border-top d-flex justify-content-end">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" id="sizeButtonName" class="btn btn-primary">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>