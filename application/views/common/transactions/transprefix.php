<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="transPrefixModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

        <?php $FormAttribute = array('id' => 'addTransPrefixForm', 'name' => 'addTransPrefixForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('common/addTransPrefixFormData', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title">Add Prefix Details</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-icon-square" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x fs-4"></i>
                    </button>
                </div>
            </div>

            <input type="hidden" id="preModuleUID" name="preModuleUID" value="<?php echo $JwtData->ModuleUID; ?>">

            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label for="transPrefixName" class="form-label">Prefix Name <span style="color:red">*</span></label>
                        <input class="form-control text-uppercase" type="text" id="transPrefixName" name="transPrefixName" placeholder="Prefix Name..." oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="7" />
                    </div>
                    <div class="col-md-6">
                        <label for="prefixPreview" class="form-label">Preview </label>
                        <input class="form-control bg-light fw-semibold" type="text" id="prefixPreview" readonly />
                    </div>
                </div>
            </div>

            <hr class="mt-0 mb-0 border-top" />

            <div class="modal-footer p-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>

        <?php echo form_close(); ?>

        </div>
    </div>
</div>