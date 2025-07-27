<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Brands Form -->
<div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content h-100 d-flex flex-column">

            <?php $FormAttribute = array('id' => 'BrandsForm', 'name' => 'BrandsForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/brands', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="BrandsModalTitle">Add Brands</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary brandButtonName">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Discard</button>
                </div>
            </div>

            <input type="hidden" name="BrandUID" id="HBrandUID" value="0" />
            <div class="d-none col-lg-12 px-5 mt-3 brandFormAlert" role="alert"></div>
            
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

            <div class="d-none col-lg-12 px-4 brandFormAlert" role="alert"></div>

            <div class="modal-footer modal-footer-center-sticky border-top d-flex justify-content-start p-3">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" class="btn btn-primary brandButtonName">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>