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
                            <li class="breadcrumb-item">
                                <a href="/vendors">Vendors</a>
                            </li>
                            <li class="breadcrumb-item active">Add Vendor</li>
                        </ol>
                    </nav>

                    <?php $FormAttribute = array('id' => 'AddVendorForm', 'name' => 'AddVendorForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('vendors/addVendor', $FormAttribute); ?>

                    <div class="row">

                        <div class="col-12 col-lg-8">
                            <div class="card mb-3">
                                <h5 class="card-header">Basic Details</h5>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="Name" class="form-label">Name <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="Name" name="Name" placeholder="Name" maxlength="100" required />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="Name" class="form-label">Village Name </label>
                                            <input class="form-control" type="text" id="VillageName" name="VillageName" placeholder="Village" maxlength="100" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="MobileNumber">Mobile Number </label>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <select id="CountryCode" name="CountryCode" class="select2 form-select">
                                                        <option label="-- Select Country Code --"></option>
                                                        <?php if (sizeof($CountryInfo) > 0) {
                                                            foreach ($CountryInfo as $Country) { ?>
                                                                <option value="<?php echo $Country['phone'][0]; ?>" data-region="<?php echo $Country['region']; ?>" data-ccode="<?php echo $Country['iso']['alpha-2']; ?>" <?php echo ($Country['iso']['alpha-2'] == $JwtData->User->OrgCISO2) ? 'selected' : ''; ?>><?php echo '(' . $Country['phone'][0] . ') ' . $Country['name']; ?></option>
                                                        <?php }
                                                        } ?>

                                                    </select>
                                                </div>
                                                <div class="col-md-8 ps-2">
                                                    <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxlength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="email" class="form-label">Email </label>
                                            <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="card mb-3">
                                <h5 class="card-header">Company Details (Optional)</h5>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="mb-3 col-md-12">
                                            <label for="text" class="form-label">GSTIN</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="GSTIN" />
                                                <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                                            </div>
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="CompanyName" class="form-label">Company Name </label>
                                            <input class="form-control" type="text" id="CompanyName" name="CompanyName" placeholder="Company Name" maxlength="100" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Billing Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">

                                <div class="mb-3 col-md-12">
                                    <label for="BillAddrLine1" class="form-label">Address Line 1 </label>
                                    <input class="form-control" type="text" id="BillAddrLine1" name="BillAddrLine1" maxlength="100" placeholder="Address Line 1" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="BillAddrLine2" class="form-label">Address Line 2 </label>
                                    <input class="form-control" type="text" id="BillAddrLine2" name="BillAddrLine2" maxlength="100" placeholder="Address Line 2" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="BillAddrPincode" class="form-label">Pincode </label>
                                    <input class="form-control" type="text" id="BillAddrPincode" name="BillAddrPincode" maxlength="10" placeholder="Pincode" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="BillAddrState" class="form-label">State</label>
                                    <select class="select2 form-select" id="BillAddrState" name="BillAddrState">
                                        <option label="-- Select State --"></option>
                                        <?php if (sizeof($StateData) > 0) {
                                            foreach ($StateData as $StData) { ?>

                                                <option value="<?php echo $StData['id']; ?>" data-iso2="<?php echo $StData['iso2']; ?>"><?php echo $StData['name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="BillAddrCity" class="form-label">City</label>
                                    <select class="select2 form-select" id="BillAddrCity" name="BillAddrCity">
                                        <option label="-- Select City --"></option>
                                        <?php if (sizeof($CityData) > 0) {
                                            foreach ($CityData as $CtyData) { ?>

                                                <option value="<?php echo $CtyData['id']; ?>"><?php echo $CtyData['name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Optional Details</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-12">
                                    <label for="text" class="form-label">Opening Balance</label>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="DebitCreditCheck" id="DebitType" value="Debit" checked />
                                            <label class="form-check-label" for="DebitType">Debit (Vendor Pays you)</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="DebitCreditCheck" id="CreditType" value="Credit" />
                                            <label class="form-check-label" for="CreditType">Credit (You Pay the Vendor)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="DebitCreditAmount" id="DebitCreditAmount" min="0" placeholder="Debit / Credit Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="0" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Customer Linking</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-12">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="CustomerLinkingCheck" id="CreateCustomer" value="NewCustomer" />
                                            <label class="form-check-label" for="CreateCustomer">Create customer with same details</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="CustomerLinkingCheck" id="ExistingCustomer" value="OldCustomer" />
                                            <label class="form-check-label" for="ExistingCustomer">Link Existing Customer</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 d-none" id="CustomerDiv">
                                        <div class="input-group mb-3">
                                            <select class="select2 form-select" id="Customers" name="Customers"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion mt-3 mb-3 accordion-without-arrow" id="VendorMoreDetails">
                        <div class="card accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button type="button" class="accordion-button bg-info text-white d-flex flex-column align-items-start text-start" data-bs-toggle="collapse" data-bs-target="#accordionOne" aria-expanded="true" aria-controls="accordionOne">
                                    <span><i class="icon-base bx bx-caret-right me-1"></i> More Details ?</span>
                                    <span>Add Notes, Tags, Discount, CC Mails, Credit Limit</span>
                                </button>
                            </h2>
                            <div id="accordionOne" class="accordion-collapse collapse" data-bs-parent="#VendorMoreDetails">
                                <div class="accordion-body">

                                    <div class="row mt-3">
                                        <div class="mb-3 col-md-6">
                                            <div class="d-flex align-items-start align-items-sm-center gap-4">
                                                <img src="/images/logo/avathar_user.png" alt="user-avatar" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
                                                <div class="button-wrapper">
                                                    <label for="UploadImage" class="btn btn-primary me-2 mb-4" tabindex="0">
                                                        <span class="d-none d-sm-block">Upload new photo</span>
                                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                                        <input type="file" id="UploadImage" name="UploadImage" class="vendor-file-input" hidden onchange="fileSelect(event)" accept="image/png, image/jpg, image/jpeg" />
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
                                        <div class="mb-3 col-md-6">
                                            <label for="PANNumber" class="form-label">PAN Number </label>
                                            <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="DebitLimit" class="form-label">Debit Limit </label>
                                            <input type="number" class="form-control" name="DebitLimit" id="DebitLimit" min="0" placeholder="Debit Limit" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="Notes" class="form-label">Notes </label>
                                            <textarea class="form-control" rows="2" name="Notes" id="Notes" placeholder="Notes"></textarea>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="Tags">Tags </label>
                                            <select id="Tags" name="Tags[]" class="select2 form-select" multiple="multiple"></select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="CCEmails">CC Emails </label>
                                            <select id="CCEmails" name="CCEmails[]" class="select2 form-select" multiple="multiple"></select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-0">

                            <div id="addFormAlert" class="d-none col-lg-12 px-4 pt-4" role="alert"></div>

                            <div class="m-3">
                                <button type="submit" id="AddVendorBtn" class="btn btn-primary me-2">Save changes</button>
                                <a href="javascript: history.back();" class="btn btn-outline-secondary">Cancel</a>
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

<script src="/js/vendors.js"></script>

<script>
    var defaultImg = '<?php echo '/website/images/logo/avathar_user.png'; ?>';
    var imageChange = 0;
    var addressData = 0;
    $(function() {
        'use strict'

        $('#CountryCode').select2({
            placeholder: '-- Select Country --',
        });

        $("#Tags,#CCEmails").select2({
            tags: "true",
            placeholder: "Type and press enter..."
        });

        $('#image_reset_btn').click(function(e) {
            e.preventDefault();
            imageChange = 0;
            $('#uploadedAvatar').attr("src", CDN_URL + defaultImg);
        });

        $('input[name="CustomerLinkingCheck"]').change(function() {
            $('#CustomerDiv').addClass('d-none')
            $('#Customer').removeAttr('required');
            var selectedValue = $(this).val();
            if (selectedValue == 'OldCustomer') {
                $('#CustomerDiv').removeClass('d-none');
                $('#Customer').prop('required', true);
            }
        });

        searchCustomers('Customers');

        $('#AddVendorForm').submit(function(e) {
            e.preventDefault();

            var formData = new FormData($('#AddVendorForm')[0]);
            formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
            formData.append('imageChange', imageChange);

            var BillAddrLine1 = $('#BillAddrLine1').val();
            var BillAddrLine2 = $('#BillAddrLine2').val();
            var BillAddrPincode = $('#BillAddrPincode').val();
            var BillAddrState = $('#BillAddrState').find('option:selected').val();
            var BillAddrCity = $('#BillAddrCity').find('option:selected').val();
            if ((BillAddrLine1 != null && BillAddrLine1 != '' && BillAddrLine1 !== undefined) || (BillAddrLine2 != null && BillAddrLine2 != '' && BillAddrLine2 !== undefined) || (BillAddrPincode != null && BillAddrPincode != '' && BillAddrPincode !== undefined) || (BillAddrState != null && BillAddrState != '' && BillAddrState !== undefined) || (BillAddrCity != null && BillAddrCity != '' && BillAddrCity !== undefined)) {

                if (BillAddrCity != null && BillAddrCity != '' && BillAddrCity !== undefined) {
                    formData.append('BillAddrCityText', $('#BillAddrCity').find('option:selected').text());
                }
                if (BillAddrState != null && BillAddrState != '' && BillAddrState !== undefined) {
                    formData.append('BillAddrStateText', $('#BillAddrState').find('option:selected').text());
                }

                addressData = 1;

            }

            formData.append('addressData', addressData);

            addVendorData(formData);

        });

    });
</script>