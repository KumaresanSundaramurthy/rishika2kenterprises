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
                    <h4 class="fw-bold py-1 mb-4"><span class="text-muted fw-light">Masters/ Customers /</span> <?php echo $EditData->Name; ?></h4>

                    <?php $FormAttribute = array('id' => 'EditCustomerForm', 'name' => 'EditCustomerForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('customers/editCustomer', $FormAttribute); ?>

                    <div class="card mb-3">
                        <h5 class="card-header">Basic Details</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-12">
                                    <label for="Name" class="form-label">Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="Name" name="Name" placeholder="Name" maxlength="100" value="<?php echo isset($EditData->Name) ? $EditData->Name : ''; ?>" required />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="MobileNumber">Mobile Number </label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <select id="CountryCode" name="CountryCode" class="select2 form-select">
                                                <option label="-- Select Country Code --"></option>
                                                <?php if (sizeof($CountryInfo) > 0) {
                                                    foreach ($CountryInfo as $Country) { ?>
                                                        <option value="<?php echo $Country['phone'][0]; ?>" data-region="<?php echo $Country['region']; ?>" data-ccode="<?php echo $Country['iso']['alpha-2']; ?>" <?php echo ($Country['iso']['alpha-2'] == $JwtData->User->OrgCISO2) ? 'selected' : ''; ?> <?php echo (isset($EditData->CountryCode) && $EditData->CountryCode == $Country['phone'][0]) ? 'selected' : ''; ?>><?php echo '(' . $Country['phone'][0] . ') ' . $Country['name']; ?></option>
                                                <?php }
                                                } ?>

                                            </select>
                                        </div>
                                        <div class="col-md-8 ps-2">
                                            <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxlength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="<?php echo isset($EditData->MobileNumber) ? $EditData->MobileNumber : ''; ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="email" class="form-label">Email </label>
                                    <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" value="<?php echo isset($EditData->EmailAddress) ? $EditData->EmailAddress : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Company Details (Optional)</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-12">
                                    <label for="text" class="form-label">GSTIN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="GSTIN" value="<?php echo isset($EditData->GSTIN) ? $EditData->GSTIN : ''; ?>" />
                                        <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="CompanyName" class="form-label">Company Name </label>
                                    <input class="form-control" type="text" id="CompanyName" name="CompanyName" placeholder="Company Name" maxlength="100" value="<?php echo isset($EditData->CompanyName) ? $EditData->CompanyName : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-2">Billing Address</h6>
                    <button type="button" class="btn btn-info mb-3" id="addBillingAddress" data-divid="appendBillingAddress"><i class="bx bx-plus-circle me-1"></i> Billing Address</button>
                    <div id="appendBillingAddress" class="d-none"></div>

                    <h6 class="mb-2">Shipping Address</h6>
                    <button type="button" class="btn btn-info mb-3" id="addShippingAddress" data-divid="appendShippingAddress"><i class="bx bx-plus-circle me-1"></i> Shipping Address</button>
                    <div id="appendShippingAddress" class="d-none"></div>

                    <div class="card mb-3">
                        <h5 class="card-header">Optional Details</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-12">
                                    <label for="text" class="form-label">Opening Balance</label>
                                    <!-- <div class="input-group input-group-merge"> -->
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="DebitCreditCheck" id="DebitType" value="Debit" <?php echo isset($EditData->DebitCreditType) && $EditData->DebitCreditType == "Debit" ? 'checked' : ''; ?> />
                                                <label class="form-check-label" for="DebitType">Debit (Customer Pays you)</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="DebitCreditCheck" id="CreditType" value="Credit" <?php echo isset($EditData->DebitCreditType) && $EditData->DebitCreditType == "Credit" ? 'checked' : ''; ?> />
                                                <label class="form-check-label" for="CreditType">Credit (You Pay the Customer)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" name="DebitCreditAmount" id="DebitCreditAmount" min="0" placeholder="Debit / Credit Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="<?php echo isset($EditData->DebitCreditAmount) ? $EditData->DebitCreditAmount : '0'; ?>" />
                                            </div>
                                        </div>
                                    <!-- </div> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion mt-3 mb-3 accordion-without-arrow" id="AccountMoreDetails">
                        <div class="card accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button type="button" class="accordion-button bg-info text-white d-flex flex-column align-items-start text-start" data-bs-toggle="collapse" data-bs-target="#accordionOne" aria-expanded="true" aria-controls="accordionOne">
                                    <span><i class="icon-base bx bx-caret-right me-1"></i> More Details ?</span>
                                    <span>Add Notes, Tags, Discount, CC Mails, Credit Limit</span>
                                </button>
                            </h2>
                            <div id="accordionOne" class="accordion-collapse collapse" data-bs-parent="#AccountMoreDetails">
                                <div class="accordion-body">

                                    <div class="row mt-3">
                                        <div class="mb-3 col-md-6">
                                            <div class="d-flex align-items-start align-items-sm-center gap-4">
                                                <img src="<?php echo getenv('CDN_URL') . '/website/images/logo/avathar_user.png'; ?>" alt="user-avatar" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
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
                                        <div class="mb-3 col-md-6">
                                            <label for="PANNumber" class="form-label">PAN Number </label>
                                            <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" value="<?php echo isset($EditData->PANNumber) ? $EditData->PANNumber : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="DiscountPercent" class="form-label">Discount (%) </label>
                                            <input class="form-control" type="number" id="DiscountPercent" name="DiscountPercent" min="0" max="100" maxLength="3" placeholder="Discount (%)" onkeyup="changeHandler(this)" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo isset($EditData->DiscountPercent) ? $EditData->DiscountPercent : '0'; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="CreditLimit" class="form-label">Credit Limit </label>
                                            <input type="number" class="form-control" name="CreditLimit" id="CreditLimit" min="0" placeholder="Credit Limit" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="<?php echo isset($EditData->CreditLimit) ? $EditData->CreditLimit : '0'; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="Notes" class="form-label">Notes </label>
                                            <textarea class="form-control" rows="2" name="Notes" id="Notes" placeholder="Notes"><?php echo isset($EditData->Notes) ? $EditData->Notes : ''; ?></textarea>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="Tags">Tags </label>
                                            <select id="Tags" name="Tags" class="select2 form-select" multiple="multiple"></select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label" for="CCEmails">CC Emails </label>
                                            <select id="CCEmails" name="CCEmails" class="select2 form-select" multiple="multiple"></select>
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
                                <button type="submit" id="OrgSubBtn" class="btn btn-primary me-2">Save changes</button>
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

<script src="/js/customers.js"></script>

<script>
    var CDN_URL = '<?php echo getenv('CDN_URL'); ?>';
    var defaultImg = '<?php echo '/website/images/logo/avathar_user.png'; ?>';
    var imageChange = 0;
    $(function() {
        'use strict'

        $('#CountryCode').select2({
            placeholder: '-- Select Country --',
            selectOnClose: true,
        });

        $("#Tags,#CCEmails").select2({
            tags: "true",
            placeholder: "Type and press enter...",
            allowClear: true
        });

        $('#image_reset_btn').click(function(e) {
            e.preventDefault();
            imageChange = 0;
            $('#uploadedAvatar').attr("src", CDN_URL + defaultImg);
        });

        $('#AddCustomerForm').submit(function(e) {
            e.preventDefault();
            var formData = new FormData($('#AddCustomerForm')[0]);
            formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
            addCustomerData(formData);
        });

        $('#addBillingAddress').click(function(e) {
            e.preventDefault();

            var DivId = $(this).data('divid');
            $('#'+DivId).addClass('d-none').html('');
            
            var formData = new FormData();
            formData.append('AddressType', 1);
            formData.append('CountryCode', $('#CountryCode').find('option:selected').data('ccode'));
            showAddressInfo(formData, 'addBillingAddress', DivId);

        });

        $('#addShippingAddress').click(function(e) {
            e.preventDefault();

            var DivId = $(this).data('divid');
            $('#'+DivId).addClass('d-none').html('');
            
            var formData = new FormData();
            formData.append('AddressType', 2);
            formData.append('CountryCode', $('#CountryCode').find('option:selected').data('ccode'));
            showAddressInfo(formData, 'addShippingAddress', DivId);

        });

        $(document).on('click', '#deleteBillingAddress', function(e) {
            e.preventDefault();
            $('#appendBillingAddress').addClass('d-none').html(' ');
            $('#addBillingAddress').removeClass('d-none');
        });

        $(document).on('click', '#deleteShippingAddress', function(e) {
            e.preventDefault();
            $('#appendShippingAddress').addClass('d-none').html(' ');
            $('#addShippingAddress').removeClass('d-none');
        });

    });
</script>