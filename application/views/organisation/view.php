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
                    <div class="card mb-4">
                        <h5 class="card-header">Organisation Details</h5>

                        <!-- Org Details -->
                        <div class="card-body">
                            <div class="d-flex align-items-start align-items-sm-center gap-4">
                                <img src="../assets/img/avatars/1.png" alt="user-avatar" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
                                <div class="button-wrapper">
                                    <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                                        <span class="d-none d-sm-block">Upload new photo</span>
                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                        <input type="file" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg" />
                                    </label>
                                    <button type="button" class="btn btn-outline-secondary account-image-reset mb-4">
                                        <i class="bx bx-reset d-block d-sm-none"></i>
                                        <span class="d-none d-sm-block">Reset</span>
                                    </button>
                                    <p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 800K</p>
                                </div>
                            </div>
                        </div>
                        <hr class="my-0" />
                        <div class="card-body">

                            <?php $FormAttribute = array('id' => 'OrganisationForm', 'name' => 'OrganisationForm', 'class' => '', 'autocomplete' => 'off');
                            echo form_open('organisation/updateOrganisation', $FormAttribute); ?>

                            <input type="hidden" name="OrgUID" id="OrgUID" value="<?php echo isset($EditOrgData->OrgUID) ? $EditOrgData->OrgUID : ''; ?>" />
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="Name" class="form-label">Company Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="Name" name="Name" autofocus placeholder="Company Name" value="<?php echo isset($EditOrgData->Name) ? $EditOrgData->Name : ''; ?>" maxlength="100" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="BrandName" class="form-label">Trade / Brand Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="BrandName" name="BrandName" required maxlength="100" placeholder="Trade / Brand Name" value="<?php echo isset($EditOrgData->BrandName) ? $EditOrgData->BrandName : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="Description" class="form-label">Description</label>
                                    <textarea class="form-control" rows="2" name="Description" id="Description" placeholder="Description"><?php echo isset($EditOrgData->ShortDescription) ? $EditOrgData->ShortDescription : ''; ?></textarea>
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label class="form-label" for="MobileNumber">Mobile Number</label>
                                    <div class="input-group input-group-merge">
                                        <div class="col-md-4">
                                            <select id="CountryCode" name="CountryCode" class="select2 form-select" required>
                                                <option>Select</option>
                                                <?php if (sizeof($CountryInfo) > 0) {
                                                    foreach ($CountryInfo as $Country) { ?>
                                                        <option value="<?php echo $Country['phone'][0]; ?>" data-region="<?php echo $Country['region']; ?>" data-ccode="<?php echo $Country['iso']['alpha-2']; ?>" <?php echo isset($EditOrgData->CountryCode) && ($EditOrgData->CountryCode == $Country['phone'][0]) ? 'selected' : ''; ?>><?php echo '(' . $Country['phone'][0] . ') ' . $Country['name']; ?></option>
                                                <?php }
                                                } ?>

                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" value="<?php echo isset($EditOrgData->MobileNumber) ? $EditOrgData->MobileNumber : ''; ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="email" class="form-label">Company Email</label>
                                    <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" required disabled maxlength="100" placeholder="Email Address" value="<?php echo isset($EditOrgData->EmailAddress) ? $EditOrgData->EmailAddress : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="text" class="form-label">GSTIN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="GSTIN" aria-label="GSTIN" aria-describedby="GSTIN_Fetch" name="GSTIN" id="GSTIN" value="<?php echo isset($EditOrgData->GSTIN) ? $EditOrgData->GSTIN : ''; ?>" />
                                        <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="OrgBussTypeUID">Business Type <span style="color:red">*</span></label>
                                    <select id="OrgBussTypeUID" name="OrgBussTypeUID" class="select2 form-select" required>
                                        <option>Select</option>
                                        <?php if (sizeof($OrgBussType) > 0) {
                                            foreach ($OrgBussType as $BusType) { ?>
                                                <option value="<?php echo $BusType->OrgBussTypeUID; ?>" <?php echo $BusType->OrgBussTypeUID == (isset($EditOrgData->OrgBussTypeUID) ? $EditOrgData->OrgBussTypeUID : '') ? 'selected' : ''; ?>><?php echo $BusType->Name; ?></option>
                                        <?php }
                                        } ?>

                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="AlternateNumber" class="form-label">Alternative Contact Number <span style="color:red">*</span></label>
                                    <input class="form-control" type="number" id="AlternateNumber" name="AlternateNumber" maxlength="30" placeholder="Alternative Contact Number" value="<?php echo isset($EditOrgData->AlternateNumber) ? $EditOrgData->AlternateNumber : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="TimezoneUID" class="form-label">Timezone</label>
                                    <select class="select2 form-select js-example-basic-single" id="TimezoneUID" name="TimezoneUID">
                                        <option>Select Timezone</option>
                                        <?php if (sizeof($TimezoneInfo) > 0) {
                                            foreach ($TimezoneInfo as $Tzinfo) { ?>

                                                <option value="<?php echo $Tzinfo->TimezoneUID; ?>" data-ccode="<?php echo $Tzinfo->CountryCode; ?>" <?php echo isset($EditOrgData->TimezoneUID) && ($EditOrgData->TimezoneUID == $Tzinfo->TimezoneUID) ? 'selected' : ''; ?>><?php echo '(' . $Tzinfo->GmtOffset . ') ' . $Tzinfo->Timezone; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="PANNumber" class="form-label">PAN Number </label>
                                    <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" value="<?php echo isset($EditOrgData->PANNumber) ? $EditOrgData->PANNumber : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="Website" class="form-label">Website </label>
                                    <input class="form-control" type="text" id="Website" name="Website" maxlength="255" placeholder="Website" value="<?php echo isset($EditOrgData->Website) ? $EditOrgData->Website : ''; ?>" />
                                </div>
                            </div>

                            <div id="updateFormAlert" class="d-none col-lg-12" role="alert"></div>

                            <div class="mt-2">
                                <button type="submit" id="OrgSubBtn" class="btn btn-primary me-2">Save changes</button>
                                <a href="/dashboard" class="btn btn-outline-secondary">Cancel</a>
                            </div>

                            <?php echo form_close(); ?>

                        </div>
                        <!-- / Org Details -->

                    </div>
                </div>

            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/organisation.js"></script>

<script>
    $(function() {
        'use strict'

        $('#CountryCode').select2({
            placeholder: 'Select Country',
            selectOnClose: true,
            templateSelection: function(data) {
                if (!data.id) {
                    return data.text; // return placeholder
                }

                // Access custom label from data attribute
                var label = $(data.element).val();
                return label || data.text;
            }
        });

        $('#TimezoneUID').select2({
            placeholder: 'Select Timezone',
            selectOnClose: true,
        });

        $('#OrganisationForm').submit(function(e) {
            e.preventDefault();
            var Ccode = $('#CountryCode').find('option:selected').data('ccode');
            var MobNum = $('#MobileNumber').val();
            var Status = validateMobileNumber(Ccode, MobNum);
            if(Status === false) {
                $('#updateFormAlert').removeClass('d-none');
                inlineMessageAlert('#updateFormAlert', 'danger', 'Enter valid Phone Number', false, false);
                return false;
            }
            $('#updateFormAlert').addClass('d-none');
            updateOrgForm($('#OrganisationForm').serializeArray());
        });

    });
</script>