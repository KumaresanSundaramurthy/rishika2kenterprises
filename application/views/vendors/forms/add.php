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

                <?php $FormAttribute = ['id' => 'AddVendorForm', 'name' => 'AddVendorForm', 'autocomplete' => 'off'];
                    echo form_open('vendors/addVendor', $FormAttribute); ?>

                    <div class="card mb-3">
                        
                        <div class="card-header bg-body-tertiary card-header-form-static modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <h5 class="modal-title mb-0" id="VendorModalTitle">Create Vendor</h5>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary AddEditVendorBtn">Save</button>
                                <a href="javascript: history.back();" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="d-none addEditFormAlert alert alert-danger alert-dismissible fade show p-3 m-3 mb-0" role="alert">
                            <span class="alert-message"></span>
                            <button type="button" class="btn-close" aria-label="Close"></button>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <!-- General Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">General Details</h5>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="Name" class="form-label">Vendor Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="Name" name="Name" placeholder="Name" maxlength="100" required />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="Area" class="form-label">Area</label>
                                    <input class="form-control" type="text" id="Area" name="Area" placeholder="Area" maxlength="100" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="EmailAddress" class="form-label">Email</label>
                                    <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" />
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
                                                        <?php echo ($Country->iso->{'alpha-2'} == $JwtData->User->OrgCISO2) ? 'selected' : ''; ?>>
                                                        <?php echo '(' . $Country->phone[0] . ') ' . $Country->name; ?>
                                                    </option>
                                            <?php }
                                            } ?>
                                        </select>
                                        <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
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
                                <div class="col-md-3 d-flex justify-content-center align-items-center">
                                    <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                        <div class="dz-message needsclick text-center">
                                            <i class="upload-icon mb-3"></i>
                                            <p class="h5 needsclick mb-2">Drag and drop logo here</p>
                                            <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="mb-3 col-md-12">
                                        <label for="GSTIN" class="form-label">GSTIN</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="GSTIN" />
                                            <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                                        </div>
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <label for="CompanyName" class="form-label">Company Name</label>
                                        <input class="form-control" type="text" id="CompanyName" name="CompanyName" placeholder="Company Name" maxlength="100" />
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <div class="col-md-12 mb-3 d-flex align-items-center gap-3 flex-wrap">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="CustomerLinkingCheck" id="CreateCustomer" value="NewCustomer" />
                                                <label class="form-check-label" for="CreateCustomer">Create customer with same details</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="CustomerLinkingCheck" id="ExistingCustomer" value="OldCustomer" />
                                                <label class="form-check-label" for="ExistingCustomer">Link Existing Customer</label>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="ResetCustomerLinking">Reset</button>
                                        </div>
                                        <div class="col-md-12 d-none" id="CustomerDiv">
                                            <div class="input-group mb-3">
                                                <select class="select2 form-select" id="Customers" name="Customers"></select>
                                            </div>
                                        </div>
                                    </div>
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

                        </div>
                    </div>

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

<script src="/js/vendors.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/address.js"></script>

<script>
const StateInfo = <?php echo json_encode($StateData); ?>;
const CityInfo = <?php echo json_encode($CityData); ?>;
$(function() {
    'use strict'

    initializeFlatPickr('#CPDateOfBirth');

    loadSelect2Field('#CountryCode', '-- Select Country --');

    initializeSelect2Tags('#Tags', 'Type and press enter...');
    initializeSelect2Tags('#CCEmails', 'Type and press enter...');
    
    searchCustomers('Customers');

    $('#AddVendorForm').submit(function(e) {
        e.preventDefault();

        getScrollableOnSubmitForm(this);
        $('.addEditFormAlert').addClass('d-none');

        var formData = new FormData($('#AddVendorForm')[0]);
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
            $('.addEditFormAlert').removeClass('d-none');
            $('.addEditFormAlert').find('.alert-message').text(validation.msg);
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

        addVendorData(formData);

    });

});
</script>