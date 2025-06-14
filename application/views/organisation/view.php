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

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-style1">
                            <li class="breadcrumb-item">
                                <a href="/dashboard">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active">Organisation</li>
                        </ol>
                    </nav>

                    <!-- Org Details -->
                    <?php $FormAttribute = array('id' => 'OrganisationForm', 'name' => 'OrganisationForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('organisation/updateOrganisation', $FormAttribute); ?>

                    <input type="hidden" name="OrgUID" id="OrgUID" value="<?php echo isset($EditOrgData->OrgUID) ? $EditOrgData->OrgUID : ''; ?>" />

                    <div class="card mb-4">
                        <h5 class="card-header">Organisation Details</h5>
                        <div class="card-body">
                            <div class="d-flex align-items-start align-items-sm-center gap-4">
                                <img src="<?php echo isset($EditOrgData->Logo) ? getenv('CDN_URL').$EditOrgData->Logo : '/images/logo/avathar_user.png' ?>" alt="user-avatar" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
                                <div class="button-wrapper">
                                    <label for="UploadImage" class="btn btn-primary me-2 mb-4" tabindex="0">
                                        <span class="d-none d-sm-block">Upload new photo</span>
                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                        <input type="file" id="UploadImage" name="UploadImage" class="account-file-input" hidden onchange="fileSelect(event)" accept="image/png, image/jpg, image/jpeg" />
                                    </label>
                                    <button type="button" id="image_reset_btn" class="btn btn-outline-secondary mb-4">
                                        <i class="bx bx-reset d-block d-sm-none"></i>
                                        <span class="d-none d-sm-block">Reset</span>
                                    </button>
                                    <p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 1 MB </p>
                                    <p id="image-error" class="text-danger d-none">Select only Allowed Formats</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="Name" class="form-label">Company Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="Name" name="Name" placeholder="Company Name" value="<?php echo isset($EditOrgData->Name) ? $EditOrgData->Name : ''; ?>" maxlength="100" required />
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
                                    <label class="form-label" for="MobileNumber">Mobile Number <span style="color:red">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <div class="col-md-4">
                                            <select id="CountryCode" name="CountryCode" class="select2 form-select" required>
                                                <option label="-- Select Country Code --"></option>
                                                <?php if (sizeof($CountryInfo) > 0) {
                                                    foreach ($CountryInfo as $Country) { ?>
                                                        <option value="<?php echo $Country['phone'][0]; ?>" data-region="<?php echo $Country['region']; ?>" data-ccode="<?php echo $Country['iso']['alpha-2']; ?>" <?php echo isset($EditOrgData->CountryCode) && ($EditOrgData->CountryCode == $Country['phone'][0]) ? 'selected' : ''; ?>><?php echo '(' . $Country['phone'][0] . ') ' . $Country['name']; ?></option>
                                                <?php }
                                                } ?>

                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" value="<?php echo isset($EditOrgData->MobileNumber) ? $EditOrgData->MobileNumber : ''; ?>" maxLength="20" required onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="email" class="form-label">Company Email <span style="color:red">*</span></label>
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
                                        <option label="-- Select Business Type --"></option>
                                        <?php if (sizeof($OrgBussType) > 0) {
                                            foreach ($OrgBussType as $BusType) { ?>
                                                <option value="<?php echo $BusType->OrgBussTypeUID; ?>" <?php echo $BusType->OrgBussTypeUID == (isset($EditOrgData->OrgBussTypeUID) ? $EditOrgData->OrgBussTypeUID : '') ? 'selected' : ''; ?>><?php echo $BusType->Name; ?></option>
                                        <?php }
                                        } ?>

                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="AlternateNumber" class="form-label">Alternative Contact Number </label>
                                    <input class="form-control" type="number" id="AlternateNumber" name="AlternateNumber" maxLength="20" placeholder="Alternative Contact Number" value="<?php echo isset($EditOrgData->AlternateNumber) ? $EditOrgData->AlternateNumber : ''; ?>" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="TimezoneUID" class="form-label">Timezone</label>
                                    <select class="select2 form-select" id="TimezoneUID" name="TimezoneUID">
                                        <option label="-- Select Timezone --"></option>
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

                        </div>
                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Billing Details</h5>
                        <div class="card-body">
                            <div class="row">

                                <input type="hidden" name="BillOrgAddressUID" id="BillOrgAddressUID" value="<?php echo isset($BillOrgAddrData->OrgAddressUID) ? $BillOrgAddrData->OrgAddressUID : 0; ?>" required />
                                <div class="mb-3 col-md-12">
                                    <label for="BillAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="BillAddrLine1" name="BillAddrLine1" maxlength="100" placeholder="Address Line 1" value="<?php echo isset($BillOrgAddrData->Line1) ? $BillOrgAddrData->Line1 : ''; ?>" required />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="BillAddrLine2" class="form-label">Address Line 2 </label>
                                    <input class="form-control" type="text" id="BillAddrLine2" name="BillAddrLine2" maxlength="100" placeholder="Address Line 2" value="<?php echo isset($BillOrgAddrData->Line2) ? $BillOrgAddrData->Line2 : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="BillAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="BillAddrPincode" name="BillAddrPincode" maxlength="10" placeholder="Pincode" value="<?php echo isset($BillOrgAddrData->Pincode) ? $BillOrgAddrData->Pincode : ''; ?>" required />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="BillAddrState" class="form-label">State</label>
                                    <select class="select2 form-select" id="BillAddrState" name="BillAddrState">
                                        <option label="-- Select State --"></option>
                                        <?php if (sizeof($StateData) > 0) {
                                            foreach ($StateData as $StData) { ?>

                                                <option value="<?php echo $StData['id']; ?>" data-iso2="<?php echo $StData['iso2']; ?>" <?php echo isset($BillOrgAddrData->State) && ($BillOrgAddrData->State == $StData['id']) ? 'selected' : ''; ?>><?php echo $StData['name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="BillAddrCity" class="form-label">City</label>
                                    <select class="select2 form-select" id="BillAddrCity" name="BillAddrCity">
                                        <option label="-- Select City --">Select City</option>
                                        <?php if (sizeof($CityData) > 0) {
                                            foreach ($CityData as $CtyData) { ?>

                                                <option value="<?php echo $CtyData['id']; ?>" <?php echo isset($BillOrgAddrData->City) && ($BillOrgAddrData->City == $CtyData['id']) ? 'selected' : ''; ?>><?php echo $CtyData['name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-12">
                                    <button type="button" id="CopyToShipping" class="btn btn-outline-info">Copy to Shipping</button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Shipping Details</h5>
                        <div class="card-body">
                            <div class="row">

                                <input type="hidden" name="ShipOrgAddressUID" id="ShipOrgAddressUID" value="<?php echo isset($ShipOrgAddrData->OrgAddressUID) ? $ShipOrgAddrData->OrgAddressUID : 0; ?>" required />
                                <div class="mb-3 col-md-12">
                                    <label for="ShipAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="ShipAddrLine1" name="ShipAddrLine1" maxlength="100" placeholder="Address Line 1" value="<?php echo isset($ShipOrgAddrData->Line1) ? $ShipOrgAddrData->Line1 : ''; ?>" required />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="ShipAddrLine2" class="form-label">Address Line 2 </label>
                                    <input class="form-control" type="text" id="ShipAddrLine2" name="ShipAddrLine2" maxlength="100" placeholder="Address Line 2" value="<?php echo isset($ShipOrgAddrData->Line2) ? $ShipOrgAddrData->Line2 : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="ShipAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="ShipAddrPincode" name="ShipAddrPincode" maxlength="10" placeholder="Pincode" value="<?php echo isset($ShipOrgAddrData->Pincode) ? $ShipOrgAddrData->Pincode : ''; ?>" required />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="ShipAddrState" class="form-label">State</label>
                                    <select class="select2 form-select" id="ShipAddrState" name="ShipAddrState">
                                        <option label="-- Select State --"></option>
                                        <?php if (sizeof($StateData) > 0) {
                                            foreach ($StateData as $StData) { ?>

                                                <option value="<?php echo $StData['id']; ?>" data-iso2="<?php echo $StData['iso2']; ?>" <?php echo isset($ShipOrgAddrData->State) && ($ShipOrgAddrData->State == $StData['id']) ? 'selected' : ''; ?>><?php echo $StData['name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="ShipAddrCity" class="form-label">City</label>
                                    <select class="select2 form-select" id="ShipAddrCity" name="ShipAddrCity">
                                        <option label="-- Select City --"></option>
                                        <?php if (sizeof($CityData) > 0) {
                                            foreach ($CityData as $CtyData) { ?>

                                                <option value="<?php echo $CtyData['id']; ?>" <?php echo isset($ShipOrgAddrData->City) && ($ShipOrgAddrData->City == $CtyData['id']) ? 'selected' : ''; ?>><?php echo $CtyData['name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-0">

                            <div id="updateFormAlert" class="d-none col-lg-12 px-4 pt-4" role="alert"></div>

                            <div class="m-3">
                                <button type="submit" id="OrgSubBtn" class="btn btn-primary me-2">Save changes</button>
                                <a href="/dashboard" class="btn btn-outline-secondary">Cancel</a>
                            </div>

                        </div>
                    </div>


                    <?php echo form_close(); ?>

                    <!-- / Org Details -->

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
    var imageChange = 0;
    let countryChange = 0;
    var defaultImg = '<?php echo isset($EditOrgData->Logo) ? $EditOrgData->Logo : '/website/images/logo/avathar_user.png'; ?>';
    $(function() {
        'use strict'

        $('#CountryCode').select2({
            placeholder: '-- Select Country --',
            allowClear: true,
            // templateSelection: function(data) {
            //     if (!data.id) {
            //         return data.text; // return placeholder
            //     }

            //     // Access custom label from data attribute
            //     var label = $(data.element).val();
            //     return label || data.text;
            // }
        });

        $('#TimezoneUID').select2({
            placeholder: '-- Select Timezone --',
            allowClear: true,
        });

        $('#BillAddrState,#ShipAddrState').select2({
            placeholder: '-- Select State --',
            allowClear: true,
        });

        $('#BillAddrCity,#ShipAddrCity').select2({
            placeholder: '-- Select City --',
            allowClear: true,
        });

        $('#image_reset_btn').click(function(e) {
            e.preventDefault();
            imageChange = 0;
            $('#uploadedAvatar').attr("src", CDN_URL + defaultImg);
        });

        $('#CountryCode').change(function(e) {
            e.preventDefault();
            countryChange = 1;
            $('#BillAddrState,#ShipAddrState,#BillAddrCity,#ShipAddrCity').val(null).trigger('change');
            getStateCityOfCountry($(this).find('option:selected').data('ccode'));
        });

        $('#CopyToShipping').click(function(e) {
            e.preventDefault();

            var BillLine1 = $('#BillAddrLine1').val();
            if (BillLine1 && BillLine1.trim() !== "") {
                $('#ShipAddrLine1').val(BillLine1);
            }

            var BillLine2 = $('#BillAddrLine2').val();
            if (BillLine2 && BillLine2.trim() !== "") {
                $('#ShipAddrLine2').val(BillLine2);
            }

            var BillPincode = $('#BillAddrPincode').val();
            if (BillPincode && BillPincode.trim() !== "") {
                $('#ShipAddrPincode').val(BillPincode);
            }

            var BillState = $('#BillAddrState').find('option:selected').val();
            if (BillState && BillState.trim() !== "" && BillState !== undefined) {
                $('#ShipAddrState').val(BillState).trigger('change');
            }

            var BillCity = $('#BillAddrCity').find('option:selected').val();
            if (BillCity && BillCity.trim() !== "" && BillCity !== undefined) {
                $('#ShipAddrCity').val(BillCity).trigger('change');
            }

        });

        $('#OrganisationForm').submit(function(e) {
            e.preventDefault();

            var Ccode = $('#CountryCode').find('option:selected').data('ccode');
            var MobNum = $('#MobileNumber').val();
            var Status = validateMobileNumber(Ccode, MobNum);
            if (Status === false) {
                $('#updateFormAlert').removeClass('d-none');
                inlineMessageAlert('#updateFormAlert', 'danger', 'Enter valid Phone Number', false, false);
                return false;
            }
            $('#updateFormAlert').addClass('d-none');
            var formData = new FormData($('#OrganisationForm')[0]);
            formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
            formData.append('imageChange', imageChange);
            formData.append('countryChange', countryChange);

            var BillState = $('#BillAddrState').find('option:selected').val();
            formData.append('BillAddrStateText', (BillState && BillState.trim() !== "") ? $('#BillAddrState').find('option:selected').data('iso2') : null);

            var BillCity = $('#BillAddrCity').find('option:selected').text();
            formData.append('BillAddrCityText', (BillCity && BillCity.trim() !== "") ? BillCity : null);

            var ShipState = $('#ShipAddrState').find('option:selected').val();
            formData.append('ShipAddrStateText', (ShipState && ShipState.trim() !== "") ? $('#ShipAddrState').find('option:selected').data('iso2') : null);

            var ShipCity = $('#ShipAddrCity').find('option:selected').text();
            formData.append('ShipAddrCityText', (ShipCity && ShipCity.trim() !== "") ? ShipCity : null);

            updateOrgForm(formData);

        });

    });
</script>