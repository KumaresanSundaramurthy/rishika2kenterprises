<script>
var JwtToken = '<?php echo $JwtToken; ?>';
var JwtData = JSON.parse('<?php echo json_encode($JwtData); ?>');
// var CsrfName = '<?php //echo $this->security->get_csrf_token_name(); ?>';
// var CsrfToken = '<?php //echo $this->security->get_csrf_hash(); ?>';
const defaultIso2 = '<?php echo $JwtData->User->OrgCISO2 ?? 'IN'; ?>';
const defaultCCode = '+91';
var RowLimit = <?php echo isset($JwtData->GenSettings->RowLimit) ? $JwtData->GenSettings->RowLimit : 10; ?>;
var UserRoleUID = <?php echo isset($JwtData->User->RoleUID) ? $JwtData->User->RoleUID : 0; ?>;
var PageNo = 0;
var Filter = {};
var AjaxLoading = 1;
var global_base_url = '<?php echo base_url(); ?>';
var CDN_URL = '<?php echo getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN') ?>';
var defUserImg = '<?php echo '/website/images/logo/avathar_user.png'; ?>';
var myOneDropzone;
var myTwoDropzone;
var quill;
var hasRemovedStoredImage = false;
var SelectedUIDs = [];
var exportModule;
var expActionType;
var delBankDataFlag = 0;
var delBankData = [];
$(function() {
	'use strict'

    $('#ChangePasswordBtn').click(function(e) {
        e.preventDefault();
        $('#ChangePasswordModal').modal('show');
        $('#ChangePasswordModal #ResetPasswordForm').trigger('reset');
        $('#ResetPasswordSubBtn').removeAttr('disabled');
        $('#ChangePasswordAlert').html('');
        $('#ChangePasswordAlert').addClass('d-none');
    });

    $('#ResetPasswordForm').submit(function(e) {
        e.preventDefault();
        
        $('#ChangePasswordAlert').html('');
        $('#ChangePasswordAlert').addClass('d-none');

        var newPassword = $('#NewPassword').val();        
        var confirmPassword = $('#ConfirmPassword').val();
        if (newPassword !== confirmPassword) {
            inlineMessageAlert('#ChangePasswordAlert', 'danger', 'Passwords do not match!', false, false);
            $('#ChangePasswordAlert').removeClass('d-none');
            return false;
        }

        var oldPassword = $('#OldPassword').val();
        if(oldPassword === newPassword) {
            inlineMessageAlert('#ChangePasswordAlert', 'danger', 'Old & New Passwords are same!', false, false);
            $('#ChangePasswordAlert').removeClass('d-none');
            return false;
        }
        
        resetUserPassword($('#ResetPasswordForm').serializeArray());

    });

    // Initialize Dropzone for file upload - Only One will be allowed
    let dropzoneElement = document.querySelector("#DropzoneOneBasic");
    if (dropzoneElement) {
        myOneDropzone = new Dropzone(dropzoneElement, {
            url: "#",
            autoProcessQueue: false,
            previewTemplate: `
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-thumbnail">
                            <img data-dz-thumbnail>
                            <span class="dz-nopreview">No preview</span>
                            <div class="dz-success-mark"></div>
                            <div class="dz-error-mark"></div>
                            <div class="dz-error-message"><span data-dz-errormessage></span></div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                            </div>
                        </div>
                        <div class="dz-filename" data-dz-name></div>
                        <div class="dz-size" data-dz-size></div>
                    </div>
                </div>
            `,
            parallelUploads: 1,
            maxFilesize: 1, // In MB
            acceptedFiles: ".jpg,.jpeg,.png",
            addRemoveLinks: true,
            maxFiles: 1,
            init: function() {
                this.on("addedfile", function(file) {
                    if (this.files.length > 1) {
                        this.removeFile(this.files[1]); // Only allow one
                    }
                });
                this.on("error", function(file, message) {
                    if (file.size > this.options.maxFilesize * 1024 * 1024) {
                        Swal.fire({
                            icon: "error",
                            title: "File too large",
                            text: "Maximum allowed size is 1 MB.",
                        });
                        this.removeFile(file);
                    }
                });
                this.on("removedfile", function(file) {
                    if (file.isStored) {
                        hasRemovedStoredImage = true;
                    }
                });
                this.on("maxfilesexceeded", function(file) {
                    this.removeFile(file);
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Only one image is allowed.",
                    });
                });
            }
        });

        // Optional: Disable click if one file already added
        dropzoneElement.addEventListener("click", function(e) {
            if (myOneDropzone.files.length >= 1) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Only one image is allowed.",
                });
            }
        });

    }

    let dzElement = document.querySelector("#DropzoneTwoBasic");
    if (dzElement) {
        myTwoDropzone = new Dropzone(dzElement, {
            url: "#",
            autoProcessQueue: false,
            previewTemplate: `
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-thumbnail">
                            <img data-dz-thumbnail>
                            <span class="dz-nopreview">No preview</span>
                            <div class="dz-success-mark"></div>
                            <div class="dz-error-mark"></div>
                            <div class="dz-error-message"><span data-dz-errormessage></span></div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                            </div>
                        </div>
                        <div class="dz-filename" data-dz-name></div>
                        <div class="dz-size" data-dz-size></div>
                    </div>
                </div>
            `,
            parallelUploads: 1,
            maxFilesize: 1, // In MB
            acceptedFiles: ".jpg,.jpeg,.png",
            addRemoveLinks: true,
            maxFiles: 1,
            init: function() {
                this.on("addedfile", function(file) {
                    if (this.files.length > 1) {
                        this.removeFile(this.files[1]); // Only allow one
                    }
                });
                this.on("error", function(file, message) {
                    if (file.size > this.options.maxFilesize * 1024 * 1024) {
                        Swal.fire({icon: "error", title: "File too large", text: "Maximum allowed size is 1 MB."});
                        this.removeFile(file);
                    }
                });
                this.on("removedfile", function(file) {
                    if (file.isStored) {
                        hasRemovedStoredImage = true;
                    }
                });
                this.on("maxfilesexceeded", function(file) {
                    this.removeFile(file);
                    Swal.fire({icon: "error", title: "Oops...", text: "Only one image is allowed."});
                });
            }
        });

        // Optional: Disable click if one file already added
        dzElement.addEventListener("click", function(e) {
            if (myTwoDropzone.files.length >= 1) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({icon: "error", title: "Oops...", text: "Only one image is allowed."});
            }
        });

    }

});
</script>