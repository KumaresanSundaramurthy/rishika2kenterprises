<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="ChangePasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="ChangePasswordLabel" aria-hidden="true">
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