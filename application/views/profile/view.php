<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <?php $this->load->view('common/navbar_view'); ?>

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                <?php $FormAttribute = array('id' => 'profileForm', 'name' => 'profileForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('profile/updateProfile', $FormAttribute); ?>

                    <input type="hidden" name="userUid" id="HuserUid" value="<?php echo isset($userInfo->UserUID) ? $userInfo->UserUID : ''; ?>" />

                    <div class="card mb-3">
                        <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center mb-3">
                            <h5 class="modal-title">Profile Details</h5>
                            <div class="d-flex align-items-center gap-2" id="mainActionBar">
                                <button type="submit" class="btn btn-primary me-2">Update</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 d-flex justify-content-center align-items-center">
                                    <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                        <div class="dz-message needsclick text-center">
                                            <i class="upload-icon mb-3"></i>
                                            <p class="h4 needsclick mb-2">Drag and drop your image here</p>
                                            <p class="h6 text-body-secondary fw-normal mb-0">Allowed JPG, GIF or PNG of 1 MB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9 d-flex flex-column justify-content-center">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="fistName" class="form-label">First Name <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="fistName" name="fistName" placeholder="First Name" value="<?php echo isset($userInfo->UserFirstName) ? $userInfo->UserFirstName : ''; ?>" maxlength="100" required />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="lastName" class="form-label">Last Name </label>
                                            <input class="form-control" type="text" id="lastName" name="lastName" maxlength="100" placeholder="Last Name" value="<?php echo isset($userInfo->UserLastName) ? $userInfo->UserLastName : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="userName" class="form-label">User Name <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="userName" name="userName" maxlength="100" disabled placeholder="User Name" value="<?php echo isset($userInfo->UserName) ? $userInfo->UserName : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="emailAddress" class="form-label">Description <span style="color:red">*</span></label>
                                            <input class="form-control" type="email" id="emailAddress" name="emailAddress" required disabled maxlength="100" placeholder="Email Address" value="<?php echo isset($userInfo->UserEmailAddress) ? $userInfo->UserEmailAddress : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="MobileNumber">Mobile Number <span style="color:red">*</span></label>
                                            <div class="d-flex gap-2">
                                                <select id="CountryCode" name="CountryCode" class="select2 form-select">
                                                    <option label="-- Select Country Code --"></option>
                                                    <?php if (sizeof($CountryInfo) > 0) {
                                                        foreach ($CountryInfo as $Country) { ?>
                                                            <option
                                                                value="<?php echo $Country->phone[0]; ?>"
                                                                data-region="<?php echo $Country->region; ?>"
                                                                data-ccode="<?php echo $Country->iso->{'alpha-2'}; ?>"
                                                                <?php echo ($Country->phone[0] == $userInfo->UserCountryCode) ? 'selected' : ''; ?>>
                                                                <?php echo '(' . $Country->phone[0] . ') ' . $Country->name; ?>
                                                            </option>
                                                    <?php }
                                                    } ?>
                                                </select>
                                                <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" required pattern="[0-9]*" value="<?php echo isset($userInfo->UserMobileNumber) ? $userInfo->UserMobileNumber : ''; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Change Password (Optional)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="oldPassword" class="col-form-label">Old Password <span style="color:red">*</span> </label>
                                    <input type="password" class="form-control" name="oldPassword" id="oldPassword" maxlength="20" autocomplete="off" placeholder="Old Password">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="newPassword" class="col-form-label">New Password <span style="color:red">*</span> </label>
                                    <input type="password" class="form-control" name="newPassword" id="newPassword" maxlength="20" autocomplete="off" placeholder="New Password">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="confirmPassword" class="col-form-label">Confirm Password <span style="color:red">*</span> </label>
                                    <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" maxlength="20" autocomplete="off" placeholder="Confirm Password">
                                </div>
                            </div>
                        </div>
                    </div>

                <?php echo form_close(); ?>

                </div>
            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/profile.js"></script>

<script>
var imgData = '<?php echo isset($userInfo->UserImage) ? $userInfo->UserImage : ''; ?>';
$(function() {
    'use strict'

    if (hasValue(imgData)) {
        var ImageUrl = CDN_URL + imgData;
        commonSetDropzoneImageOne(ImageUrl);
    }
    
    loadCountrySelect2Field('#CountryCode', 'Select Country');

    $('#profileForm').submit(function(e) {
        e.preventDefault();

        var Ccode = $('#CountryCode').find('option:selected').data('ccode');
        var MobNum = $('#MobileNumber').val();
        var Status = validateMobileNumber(Ccode, MobNum);
        if (Status === false) {
            Swal.fire('Enter valid Phone Number', "", "danger");
            return false;
        }

        /** Password Validation */
        var oldPwd = $('#oldPassword').val().trim();
        var newPwd = $('#newPassword').val().trim();
        var confirmPwd = $('#confirmPassword').val().trim();
        var isPasswordUpdate = false;
        if (oldPwd !== '' || newPwd !== '' || confirmPwd !== '') {
            isPasswordUpdate = true;
            if (oldPwd === '' || newPwd === '' || confirmPwd === '') {
                Swal.fire('Please fill all password fields to change your password', "", "danger");
                return false;
            }
            if (newPwd !== confirmPwd) {
                Swal.fire('New Password and Confirm Password do not match', "", "danger");
                return false;
            }
            if (oldPwd === newPwd) {
                Swal.fire('Old Password and New Password cannot be the same', "", "danger");
                return false;
            }
        }

        var formData = new FormData($('#profileForm')[0]);
        formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
        formData.append('IsPasswordUpdate', isPasswordUpdate ? 1 : 0);
        if(hasValue(imgData) && myOneDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
            imgData = '';
        }
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
                imgData = 1;
            }
        }

        updateProfileForm(formData);

    });

});
</script>