<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="ChangePasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="ChangePasswordLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ChangePasswordLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <?php $FormAttribute = array('id' => 'ResetPasswordForm', 'name' => 'ResetPasswordForm', 'class' => 'd-flex flex-column justify-content-end h-100', 'autocomplete' => 'off');
            echo form_open('login/resetPassword', $FormAttribute); ?>

            <div class="modal-body">
                <input type="hidden" name="UserUID" id="UserUID" value="<?php echo $JwtData->User->UserUID; ?>" />
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="OldPassword" class="col-form-label">Old Password <span style="color:red">*</span> </label>
                    </div>
                    <div class="col-8">
                        <input type="password" class="form-control" name="OldPassword" id="OldPassword" required maxlength="20" autocomplete="off" placeholder="Old Password">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="NewPassword" class="col-form-label">New Password <span style="color:red">*</span> </label>
                    </div>
                    <div class="col-8">
                        <input type="password" class="form-control" name="NewPassword" id="NewPassword" required maxlength="20" autocomplete="off" placeholder="New Password">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="ConfirmPassword" class="col-form-label">Confirm Password <span style="color:red">*</span> </label>
                    </div>
                    <div class="col-8">
                        <input type="password" class="form-control" name="ConfirmPassword" id="ConfirmPassword" required maxlength="20" autocomplete="off" placeholder="Confirm Password">
                    </div>
                </div>
                <div id="ChangePasswordAlert" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="ResetPasswordSubBtn" class="btn btn-primary">Reset</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>

<!-- Page Selection Modal -->
<div class="modal fade" id="selectPagesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="selectPagesModal">
    <div class="modal-dialog"> <!-- modal-dialog-centered -->
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-5 mb-5">
                <div class="text-primary mb-3">
                    <i class="bx bx-select-multiple bx-lg"></i>
                </div>
                <h4 class="mb-3">Bulk Selection</h4>
                <p class="mb-4 px-3">
                    Do you want to select items <strong>only on this page</strong> or <strong>across all pages</strong>?
                </p>

                <div class="d-grid gap-2 col-10 mx-auto">
                    <button type="button" class="btn btn-outline-primary btn-lg" id="selectThisPageBtn">
                        <i class="bx bx-grid-small me-2"></i> This Page Only
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="selectAllPagesBtn">
                        <i class="bx bx-layer me-2"></i> All Pages
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-lg" id="clearSelectAllClose">
                        <i class="bx bx-reset me-2"></i> Clear & Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Unselect Items Modal -->
<div class="modal fade" id="unSelectPagesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="unSelectPagesModal">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-5">
                <div class="text-danger mb-3">
                    <i class="bx bx-unlink bx-lg"></i>
                </div>
                <h4 class="mb-3">Cancel Bulk Selection</h4>
                <p class="mb-4 px-3">
                    Do you want to unselect items <strong>only on this page</strong> or <strong>across all pages</strong>?
                </p>
                <div class="d-grid gap-2 col-10 mx-auto">
                    <button type="button" class="btn btn-outline-primary btn-lg" id="unselectThisPageBtn">
                        <i class="bx bx-grid-small me-2"></i> This Page Only
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="unselectAllPagesBtn">
                        <i class="bx bx-layer me-2"></i> All Pages
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Export modal -->
<div class="modal fade" id="exportPagesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exportPagesModal">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-5">
                <div class="text-danger mb-3">
                    <i class="bx bx-unlink bx-lg"></i>
                </div>
                <h4 class="mb-3">Export Bulk Selection</h4>
                <p class="mb-4 px-3">
                    Do you want to select items <span id="exportThisPageCnt"><strong>only on this page</strong>,</span> <span id="exportSelectedItemsCnt"><strong>only selected items</strong>, </span> or <strong>across all pages</strong>?
                </p>
                <div class="d-grid gap-2 col-10 mx-auto">
                    <button type="button" class="btn btn-outline-warning btn-lg" id="exportSelectedItemsBtn">
                        <i class="bx bx-check-square me-2"></i> Selected Items Only
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-lg" id="exportThisPageBtn">
                        <i class="bx bx-grid-small me-2"></i> This Page Only
                    </button>
                    <button type="button" class="btn btn-outline-success btn-lg" id="exportAllPagesBtn">
                        <i class="bx bx-layer me-2"></i> All Pages
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-lg" id="clearExportClose">
                        <i class="bx bx-reset me-2"></i> Clear & Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>