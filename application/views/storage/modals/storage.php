<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Storage Form -->
<div class="modal fade" id="storageModal" tabindex="-1" aria-labelledby="storageModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'storageForm', 'name' => 'storageForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('storage/addEditStorage', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="StorageModalTitle">Add Storage</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary storageButtonName">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>
            </div>

            <input type="hidden" name="StorageUID" id="StorageUID" value="0" />

            <div class="d-none col-lg-12 px-5 mt-3 storageFormAlert" role="alert"></div>

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
                <div class="card-body p-2 mb-3">

                    <!-- General Details -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3">
                        <h5 class="modal-title mb-0">Basic Details</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="Name">Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" id="Name" placeholder="Enter name" name="Name" maxlength="100" required />
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="ShortName">Short Name </label>
                            <input type="text" class="form-control" id="ShortName" placeholder="Enter short name" name="ShortName" maxlength="50" required />
                        </div>
                        <div class="mb-3">
                            <label for="StorageTypeUID" class="form-label">Storage Type <span style="color:red">*</span></label>
                            <select class="form-select" id="StorageTypeUID" name="StorageTypeUID">
                                <option label="-- Select Storage Type --"></option>
                                <?php if (isset($StorageTypeInfo) && sizeof($StorageTypeInfo) > 0) {
                                    foreach ($StorageTypeInfo as $SType) { ?>
                                        <option value="<?php echo $SType->StorageTypeUID; ?>"><?php echo $SType->Name; ?></option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row d-flex">
                        <div class="col-md-3 d-flex justify-content-center align-items-center">
                            <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                <div class="dz-message needsclick text-center">
                                    <i class="upload-icon mb-3"></i>
                                    <p class="h5 needsclick mb-2">Drag and drop category here</p>
                                    <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <label for="Description" class="form-label">Description </label>
                            <div class="form-control p-0">
                                <div id="Description" name="Description" class="border-0 border-bottom ql-toolbar ql-snow"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>