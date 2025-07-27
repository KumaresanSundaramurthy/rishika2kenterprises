<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Sizes Form -->
<div class="modal fade" id="sizesModal" tabindex="-1" aria-labelledby="sizesModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content h-100 d-flex flex-column">

            <?php $FormAttribute = array('id' => 'SizesForm', 'name' => 'SizesForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/sizes', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="SizeModalTitle">Add Item</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary sizeButtonName">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Discard</button>
                </div>
            </div>

            <input type="hidden" name="SizeUID" id="HSizeUID" value="0" />
            <div class="d-none col-lg-12 px-5 mt-3 sizeFormAlert" role="alert"></div>
            
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

            <div class="d-none col-lg-12 px-4 sizeFormAlert" role="alert"></div>

            <div class="modal-footer modal-footer-center-sticky border-top d-flex justify-content-start p-3">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" class="btn btn-primary sizeButtonName">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>