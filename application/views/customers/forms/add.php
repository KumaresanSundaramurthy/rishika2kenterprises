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

                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb breadcrumb-style1">
                                <li class="breadcrumb-item">
                                    <a href="/dashboard">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="/customers">Customers</a>
                                </li>
                                <li class="breadcrumb-item active">Add Customer</li>
                            </ol>
                        </nav>

                        <div class="d-flex gap-2">
                            <div class="mb-3">
                                <a href="javascript: history.back();" class="btn btn-outline-secondary me-2">Discard</a>
                                <button type="submit" class="btn btn-primary AddCustomerBtn">Save</button>
                            </div>
                        </div>

                        <div class="d-none col-lg-12 pt-2 addFormAlert" role="alert"></div>

                    </div>

                    <?php $FormAttribute = array('id' => 'AddCustomerForm', 'name' => 'AddCustomerForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('customers/addCustomer', $FormAttribute); ?>

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
                                            <input class="form-check-input" type="radio" name="DebitCreditCheck" id="DebitType" value="Debit" checked />
                                            <label class="form-check-label" for="DebitType">Debit (Customer Pays you)</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="DebitCreditCheck" id="CreditType" value="Credit" />
                                            <label class="form-check-label" for="CreditType">Credit (You Pay the Customer)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="DebitCreditAmount" id="DebitCreditAmount" min="0" placeholder="Debit / Credit Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="0" />
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

                                        <!-- Left Column - Centered Vertically -->
                                        <div class="mb-3 col-md-6 d-flex justify-content-center align-items-center">
                                            <div class="d-flex gap-3 align-items-center">

                                                <!-- Dropzone Box -->
                                                <div id="DropzoneOneBasic"
                                                    class="dropzone needsclick p-2 dz-clickable d-flex flex-column justify-content-center align-items-center text-center"
                                                    style="height: 250px; border: 2px dashed #ccc;">
                                                    <div class="dz-message needsclick">
                                                        <p class="h6 pt-4 mb-2">Drag and drop your image here</p>
                                                        <p class="h6 text-body-secondary fw-normal mb-3">or</p>
                                                        <span class="btn btn-sm btn-label-primary" id="btnBrowse">Browse image</span>
                                                    </div>
                                                </div>

                                                <!-- Controls next to Dropzone -->
                                                <div class="d-flex flex-column align-items-start justify-content-center" style="min-height: 220px;">
                                                    <button type="button" id="image_reset_btn" class="btn btn-outline-warning mb-2 w-100">
                                                        <i class="bx bx-reset d-block d-sm-none"></i>
                                                        <span class="d-none d-sm-block">Reset</span>
                                                    </button>
                                                    <p class="text-muted mb-1 small">Allowed JPG, GIF or PNG</p>
                                                    <p class="text-muted mb-0 small">Max size of 1 MB</p>
                                                    <p id="image-error" class="text-danger d-none mt-1 small">Select only Allowed Formats</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column -->
                                        <div class="mb-3 col-md-6 d-flex flex-column justify-content-end">
                                            <div class="mt-auto">
                                                <label for="PANNumber" class="form-label">PAN Number</label>
                                                <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" />
                                            </div>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="DiscountPercent" class="form-label">Discount (%) </label>
                                            <input class="form-control" type="number" id="DiscountPercent" name="DiscountPercent" min="0" max="100" maxLength="3" placeholder="Discount (%)" onkeyup="changeHandler(this)" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="CreditLimit" class="form-label">Credit Limit </label>
                                            <input type="number" class="form-control" name="CreditLimit" id="CreditLimit" min="0" placeholder="Credit Limit" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" />
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
                            <div class="d-none col-lg-12 pt-2 addFormAlert" role="alert"></div>
                            <div class="m-3">
                                <button type="submit" class="btn btn-primary AddCustomerBtn me-2">Save</button>
                                <a href="javascript: history.back();" class="btn btn-outline-secondary">Discard</a>
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
$(function() {
    'use strict'

    select2CountryCode();
    select2TagEmail();
    
    $('#image_reset_btn').click(function(e) {
        e.preventDefault();
        myOneDropzone.removeAllFiles();
    });

    $('#AddCustomerForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#AddCustomerForm')[0]);
        formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }

        var BillAddrLine1 = $('#BillAddrLine1').val();
        if (BillAddrLine1 != null && BillAddrLine1 != '' && BillAddrLine1 !== undefined) {
            var BillAddrCity = $('#BillAddrCity').find('option:selected').val();
            if (BillAddrCity != null && BillAddrCity != '' && BillAddrCity !== undefined) {
                formData.append('BillAddrCityText', $('#BillAddrCity').find('option:selected').text());
            }
            var BillAddrState = $('#BillAddrState').find('option:selected').val();
            if (BillAddrState != null && BillAddrState != '' && BillAddrState !== undefined) {
                formData.append('BillAddrStateText', $('#BillAddrState').find('option:selected').text());
            }
        }

        var ShipAddrLine1 = $('#ShipAddrLine1').val();
        if (ShipAddrLine1 != null && ShipAddrLine1 != '' && ShipAddrLine1 !== undefined) {
            var ShipAddrCity = $('#ShipAddrCity').find('option:selected').val();
            if (ShipAddrCity != null && ShipAddrCity != '' && ShipAddrCity !== undefined) {
                formData.append('ShipAddrCityText', $('#ShipAddrCity').find('option:selected').text());
            }
            var ShipAddrState = $('#ShipAddrState').find('option:selected').val();
            if (ShipAddrState != null && ShipAddrState != '' && ShipAddrState !== undefined) {
                formData.append('ShipAddrStateText', $('#ShipAddrState').find('option:selected').text());
            }
        }

        addCustomerData(formData);

    });

});
</script>