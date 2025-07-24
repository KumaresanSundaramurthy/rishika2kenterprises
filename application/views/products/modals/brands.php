<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Brands Form -->
<div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-dialog-right modal-lg">
        <div class="modal-content h-100 d-flex flex-column">

            <?php $FormAttribute = array('id' => 'BrandsForm', 'name' => 'BrandsForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/brands', $FormAttribute); ?>

            <div class="modal-header">
                <h5 class="modal-title" id="BrandsModalTitle">Add Brands</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <input type="hidden" name="BrandUID" id="BrandUID" value="0" />
            
            <div class="modal-body flex-grow-1 overflow-auto">
                <div class="mb-3">
                    <label class="form-label" for="BrandsName">Name <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="BrandsName" placeholder="Enter brand name" name="BrandsName" maxlength="100" required />
                </div>
                <div class="mb-3">
                    <label for="BrandsDescription" class="form-label">Description </label>
                    <textarea class="form-control" rows="3" name="BrandsDescription" id="BrandsDescription" placeholder="Description"></textarea>
                </div>
            </div>

            <div id="brandFormAlert" class="d-none col-lg-12 px-6" role="alert"></div>

            <div class="modal-footer border-top d-flex justify-content-end">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" id="brandButtonName" class="btn btn-primary">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>