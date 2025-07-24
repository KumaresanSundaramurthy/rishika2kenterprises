<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Storage Form -->
<div class="modal fade" id="storageModal" tabindex="-1" aria-labelledby="storageModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-sm-down modal-lg" role="document">
        <div class="modal-content d-flex flex-column">

            <?php $FormAttribute = array('id' => 'storageForm', 'name' => 'storageForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('storage/addStorage', $FormAttribute); ?>

            <div class="modal-header">
                <h5 class="modal-title" id="StorageModalTitle">Add Storage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <input type="hidden" name="StorageUID" id="StorageUID" value="0" />

            <div class="modal-body flex-grow-1 overflow-auto">

                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="Name">Name <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="Name" placeholder="Enter name" name="Name" maxlength="100" required />
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="ShortName">Short Name </label>
                        <input type="text" class="form-control" id="ShortName" placeholder="Enter short name" name="ShortName" maxlength="50" required />
                    </div>
                </div>

                <div class="mb-3">
                    <label for="StorageTypeUID" class="form-label">Storage Type <span style="color:red">*</span></label>
                    <select class="form-select" id="StorageTypeUID" name="StorageTypeUID">
                        <option label="-- Select Storage Type --"></option>
                        <?php if (isset($StorageTypeInfo) && sizeof($StorageTypeInfo) > 0) {
                            foreach ($StorageTypeInfo as $SType) { ?>

                                <option value="<?php echo $SType['StorageTypeUID']; ?>"><?php echo $SType['Name']; ?></option>

                        <?php }
                        } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="Description" class="form-label">Description </label>
                    <div class="form-control p-0">
                        <div id="Description" name="Description" class="border-0 border-bottom ql-toolbar ql-snow"></div>
                    </div>
                </div>

                <div class="mb-3 dropzone needsclick p-3 dz-clickable" id="DropzoneOneBasic">
                    <div class="dz-message needsclick">
                        <p class="h4 needsclick pt-4 mb-2">Drag and drop your image here</p>
                        <p class="h6 text-body-secondary d-block fw-normal mb-3">or</p>
                        <span class="needsclick btn btn-sm btn-label-primary" id="btnBrowse">Browse image</span>
                    </div>
                </div>

            </div>

            <div id="storageFormAlert" class="d-none col-lg-12 px-4 pt-4" role="alert"></div>

            <div class="modal-footer border-top d-flex justify-content-end">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" class="btn btn-primary" id="StorageSaveButton">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>