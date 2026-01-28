<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Content wrapper -->
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y pt-2">

            <?php $FormAttribute = ['id' => 'AddCustomerForm', 'name' => 'AddCustomerForm', 'autocomplete' => 'off'];
                    echo form_open('customers/addCustomer', $FormAttribute); ?>

                    <div class="card mb-3">
                        
                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <h5 class="modal-title mb-0" id="CustomerModalTitle">Create Customer</h5>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary AddEditCustomerBtn">Save</button>
                                <a href="javascript: history.back();" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <!-- General Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">General Details</h5>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="Name" class="form-label">Customer Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="Name" name="Name" placeholder="Name" maxlength="100" required />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="Area" class="form-label">Area</label>
                                    <input class="form-control" type="text" id="Area" name="Area" placeholder="Area" maxlength="100" />
                                </div>
                                <div class="mb-3 col-md-4">
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
                                                        <?php echo ($Country->iso->{'alpha-2'} == $JwtData->User->OrgCISO2) ? 'selected' : ''; ?>>
                                                        <?php echo '(' . $Country->phone[0] . ') ' . $Country->name; ?>
                                                    </option>
                                            <?php }
                                            } ?>
                                        </select>
                                        <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                    </div>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="EmailAddress" class="form-label">Email</label>
                                    <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="DebitCreditAmount" class="form-label">Opening Balance</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="DebitCreditAmount" id="DebitCreditAmount" min="0" placeholder="Debit / Credit Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="0" />
                                        <select id="DebitCreditCheck" name="DebitCreditCheck" class="select2 form-select border-start ps-2">
                                            <option value="Debit">To Collect</option>
                                            <option value="Credit">To Pay</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="PANNumber" class="form-label">PAN Number</label>
                                    <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="ContactPerson" class="form-label">Contact Person</label>
                                    <input class="form-control" type="text" id="ContactPerson" name="ContactPerson" placeholder="Contact Name" maxlength="100" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="CPDateOfBirth" class="form-label">Date of Birth</label>
                                    <input type="text" id="CPDateOfBirth" name="CPDateOfBirth" class="form-control flatpickr-basic" placeholder="YYYY-MM-DD" />
                                </div>
                            </div>
                            <hr>

                            <!-- Company Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">Company Details</h5>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="GSTIN" class="form-label">GSTIN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="GSTIN" />
                                        <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="CompanyName" class="form-label">Company Name</label>
                                    <input class="form-control" type="text" id="CompanyName" name="CompanyName" placeholder="Company Name" maxlength="100" />
                                </div>
                            </div>
                            <hr>

                            <!-- Bank Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">
                                    Bank Details
                                    <a href="javascript: void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addBankDetails" data-divid="appendBankDetails"><i class="bx bx-plus-circle me-1"></i> Bank Accounts</a>
                                </h5>
                            </div>
                            <div class="table-responsive d-none" id="appendBankDetails">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Account / UPI</th>
                                            <th>IFSC</th>
                                            <th>Branch</th>
                                            <th>Holder</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bankDetailsBody"></tbody>
                                </table>
                            </div>
                            <hr id="bankDivider" class="d-none">

                            <!-- Address Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">Address Details</h5>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center p-0 mb-2">
                                        <h5 class="mb-2">
                                            Billing Address
                                            <a href="javascript: void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addBillingAddress" data-divid="appendBillingAddress"><i class="bx bx-plus-circle me-1"></i> Billing Address</a>
                                        </h5>
                                        <div class="ms-auto d-flex align-items-center">
                                            <a href="javascript: void(0)" class="btn btn-sm btn-outline-primary ms-1 d-none" id="addrCopyToShipping"><i class="bx bx-copy-alt me-1"></i> Copy to Shipping</a>
                                            <button type="button" id="deleteBillingAddress" class="btn btn-outline-danger btn-sm ms-2 d-none"><i class="bx bx-trash"></i> </button>
                                        </div>
                                    </div>
                                    <div id="appendBillingAddress" class="d-none"></div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center p-0 mb-2">
                                        <h5 class="mb-2">
                                            Shipping Address
                                            <a href="javascript: void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addShippingAddress" data-divid="appendShippingAddress"><i class="bx bx-plus-circle me-1"></i> Shipping Address</a>
                                        </h5>
                                        <button type="button" id="deleteShippingAddress" class="btn btn-outline-danger btn-sm d-none"><i class="bx bx-trash"></i> </button>
                                    </div>
                                    <div id="appendShippingAddress" class="d-none"></div>
                                </div>
                            </div>
                            <hr id="AddressDivider" class="d-none">

                            <!-- More Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">Other Details</h5>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3 d-flex justify-content-center align-items-center">
                                    <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                        <div class="dz-message needsclick text-center">
                                            <i class="upload-icon mb-3"></i>
                                            <p class="h5 needsclick mb-2">Drag and drop your photo here</p>
                                            <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="mb-3 col-md-4">
                                            <label for="DiscountPercent" class="form-label">Discount (%)</label>
                                            <input class="form-control" type="number" id="DiscountPercent" name="DiscountPercent" min="0" max="100" maxLength="3" placeholder="Discount (%)" onkeyup="changeHandler(this)" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="0" />
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label for="CreditPeriod" class="form-label">Credit Period</label>
                                            <input type="number" class="form-control" name="CreditPeriod" id="CreditPeriod" min="0" placeholder="Credit Period" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" value="30" pattern="[0-9]*" />
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label for="CreditLimit" class="form-label">Credit Limit</label>
                                            <input type="number" class="form-control" name="CreditLimit" id="CreditLimit" min="0" placeholder="Credit Limit" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="0" />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="Notes" class="form-label">Notes</label>
                                            <textarea class="form-control" rows="2" name="Notes" id="Notes" placeholder="Notes"></textarea>
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label class="form-label" for="Tags">Tags</label>
                                            <select id="Tags" name="Tags[]" class="select2 form-select" multiple="multiple"></select>
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label class="form-label" for="CCEmails">CC Emails</label>
                                            <select id="CCEmails" name="CCEmails[]" class="select2 form-select" multiple="multiple"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php echo form_close(); ?>

                </div>
            </div>
            <!-- Content wrapper -->
             
            <?php $this->load->view('common/form/bank_details'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/customers.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/address.js"></script>

<script>
const StateInfo = <?php echo json_encode($StateData); ?>;
const CityInfo = <?php echo json_encode($CityData); ?>;
$(function() {
    'use strict'

    initializeFlatPickr('#CPDateOfBirth');
    
    loadCountrySelect2Field('#CountryCode', 'Select Country');
    
    initializeSelect2Tags('#Tags', 'Type and press enter...');
    initializeSelect2Tags('#CCEmails', 'Type and press enter...');

    $('#AddCustomerForm').submit(function(e) {
        e.preventDefault();

        getScrollableOnSubmitForm(this);

        // Disable enter key specifically on inputs
        $(this).find('input, select').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                return false;
            }
        });

        // Validate mobile number
        const mobileValue = $('#MobileNumber').val();
        const countryCode = ($('#CountryCode').val() || '91').replace('+', '');
        const mobileValidation = validateMobileNumber(mobileValue, countryCode);
        if (!mobileValidation.isValid) {
            showAlertMessageSwal('error', '', mobileValidation.message);
            return false;
        }

        // Validate PAN number
        const panValue = $('#PANNumber').val();
        const panValidation = validatePANNumber(panValue);
        if (!panValidation.isValid) {
            showAlertMessageSwal('error', '', panValidation.message);
            return false;
        }
        
        // Validate GSTIN
        const gstinValue = $('#GSTIN').val();
        const gstinValidation = validateGSTIN(gstinValue);
        if (!gstinValidation.isValid) {
            showAlertMessageSwal('error', '', gstinValidation.message);
            return false;
        }

        var formData = new FormData($('#AddCustomerForm')[0]);
        formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }

        const bankRecords = getBankRecordsFromTable();
        const validation = validateBankRecords(bankRecords);
        if (!validation.ok) {
            showAlertMessageSwal('error', '', validation.msg);
            return;
        }
        formData.append('BankDetailsJSON', JSON.stringify(bankRecords));
        formData.append('BankDetailsCount', String(bankRecords.length));

        var BillAddrLine1 = $('#BillAddrLine1').val();
        if (hasValue(BillAddrLine1)) {
            var city = $('#BillAddrCity').find('option:selected').val();
            if (hasValue(city) && $.isNumeric(city)) formData.append('BillAddrCityText', $('#BillAddrCity').find('option:selected').text());
            var state = $('#BillAddrState').find('option:selected').val();
            if (hasValue(state) && $.isNumeric(state)) formData.append('BillAddrStateText', $('#BillAddrState').find('option:selected').text());
        }

        var ShipAddrLine1 = $('#ShipAddrLine1').val();
        if (hasValue(ShipAddrLine1)) {
            var city = $('#ShipAddrCity').find('option:selected').val();
            if (hasValue(city) && $.isNumeric(city)) formData.append('ShipAddrCityText', $('#ShipAddrCity').find('option:selected').text());
            var state = $('#ShipAddrState').find('option:selected').val();
            if (hasValue(state) && $.isNumeric(state)) formData.append('ShipAddrStateText', $('#ShipAddrState').find('option:selected').text());
        }

        formData.append('transCustomer', 0);

        addCustomerData(formData);

    });

});
</script>