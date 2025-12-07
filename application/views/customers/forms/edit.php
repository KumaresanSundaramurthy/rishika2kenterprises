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

                <?php $FormAttribute = array('id' => 'EditCustomerForm', 'name' => 'EditCustomerForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('customers/editCustomer', $FormAttribute); ?>

                    <div class="card mb-3">
                        
                        <div class="card-header bg-body-tertiary card-header-form-static modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <h5 class="modal-title mb-0" id="CustomerModalTitle">Update Customer</h5>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary AddEditCustomerBtn">Update</button>
                                <a href="javascript: history.back();" class="btn btn-label-danger">Cancel</a>
                            </div>
                        </div>

                        <div class="d-none addEditFormAlert alert alert-danger alert-dismissible fade show p-3 m-3 mb-0" role="alert">
                            <span class="alert-message"></span>
                            <button type="button" class="btn-close" aria-label="Close"></button>
                        </div>
                    
                        <input type="hidden" name="CustomerUID" id="CustomerUID" value="<?php echo isset($EditData->CustomerUID) ? $EditData->CustomerUID : ''; ?>" />

                        <div class="card-body card-body-form-static p-4">

                            <!-- General Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">General Details</h5>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="Name" class="form-label">Customer Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="Name" name="Name" placeholder="Name" maxlength="100" required value="<?php echo isset($EditData->Name) ? $EditData->Name : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="Area" class="form-label">Area</label>
                                    <input class="form-control" type="text" id="Area" name="Area" placeholder="Area" maxlength="100" value="<?php echo isset($EditData->Area) ? $EditData->Area : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="EmailAddress" class="form-label">Email</label>
                                    <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" value="<?php echo isset($EditData->EmailAddress) ? $EditData->EmailAddress : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="MobileNumber">Mobile Number <span style="color:red">*</span></label>
                                    <div class="d-flex gap-2">
                                        <select id="CountryCode" name="CountryCode" class="select2 form-select">
                                            <option label="-- Select Country Code --"></option>
                                            <?php if (sizeof($CountryInfo) > 0) {
                                                foreach ($CountryInfo as $Country) { ?>
                                                    <option
                                                        value="<?php echo $Country['phone'][0]; ?>"
                                                        data-region="<?php echo $Country['region']; ?>"
                                                        data-ccode="<?php echo $Country['iso']['alpha-2']; ?>"
                                                        <?php echo (isset($EditData->CountryCode) && $EditData->CountryCode == $Country['phone'][0]) ? 'selected' : ($Country['iso']['alpha-2'] == $JwtData->User->OrgCISO2 ? 'selected' : ''); ?>>
                                                        <?php echo '(' . $Country['phone'][0] . ') ' . $Country['name']; ?>
                                                    </option>
                                            <?php }
                                            } ?>
                                        </select>
                                        <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo isset($EditData->MobileNumber) ? $EditData->MobileNumber : ''; ?>" />
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="DebitCreditAmount" class="form-label">Opening Balance</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="DebitCreditAmount" id="DebitCreditAmount" min="0" placeholder="Debit / Credit Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="<?php echo isset($EditData->DebitCreditAmount) ? smartDecimal($EditData->DebitCreditAmount) : '0'; ?>" />
                                        <select id="DebitCreditCheck" name="DebitCreditCheck" class="select2 form-select border-start ps-2">
                                            <option value="Debit" <?php echo $EditData->DebitCreditType == "Debit" ? 'selected' : ''; ?>>To Collect</option>
                                            <option value="Credit" <?php echo $EditData->DebitCreditType == "Credit" ? 'selected' : ''; ?>>To Pay</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="PANNumber" class="form-label">PAN Number</label>
                                    <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" value="<?php echo isset($EditData->PANNumber) ? $EditData->PANNumber : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="ContactPerson" class="form-label">Contact Person</label>
                                    <input class="form-control" type="text" id="ContactPerson" name="ContactPerson" placeholder="Contact Name" maxlength="100" value="<?php echo isset($EditData->ContactPerson) ? $EditData->ContactPerson : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="CPDateOfBirth" class="form-label">Date of Birth</label>
                                    <input type="text" id="CPDateOfBirth" name="CPDateOfBirth" class="form-control flatpickr-basic" placeholder="YYYY-MM-DD" value="<?php echo isset($EditData->DateOfBirth) ? $EditData->DateOfBirth : ''; ?>" />
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
                                        <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="GSTIN" value="<?php echo isset($EditData->GSTIN) ? $EditData->GSTIN : ''; ?>" />
                                        <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="CompanyName" class="form-label">Company Name</label>
                                    <input class="form-control" type="text" id="CompanyName" name="CompanyName" placeholder="Company Name" maxlength="100" value="<?php echo isset($EditData->CompanyName) ? $EditData->CompanyName : ''; ?>" />
                                </div>
                            </div>
                            <hr>

                            <!-- Bank Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">
                                    Bank Details
                                    <button type="button" class="btn btn-warning ms-1" id="addBankDetails" data-divid="appendBankDetails">
                                        <i class="bx bx-plus-circle me-1"></i>
                                    </button>
                                </h5>
                            </div>
                            <div class="table-responsive <?php echo count($BankDetails) > 0 ? '' : 'd-none'; ?>" id="appendBankDetails">
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
                                    <tbody id="bankDetailsBody">
                                    <?php if(count($BankDetails) > 0) {
                                        foreach($BankDetails as $BDet) { ?>
                                        <tr data-id="<?php echo $BDet->CustBankDetUID; ?>" data-type="<?php echo $BDet->Type; ?>" data-record='<?php echo json_encode([
                                                "id"       => (int) $BDet->CustBankDetUID,
                                                "type"     => $BDet->Type,
                                                "accNumber"=> $BDet->BankAccountNumber ?? "",
                                                "ifsc"     => $BDet->BankIFSC_Code ?? "",
                                                "branch"   => $BDet->BankBranchName ?? "",
                                                "holder"   => $BDet->BankAccountHolderName ?? "",
                                                "upiId"    => $BDet->UPI_Id ?? ""
                                            ]); ?>'>
                                            <td><?php echo $BDet->Type; ?></td>
                                            <td><?php echo $BDet->Type == 'Bank' ? $BDet->BankAccountNumber : $BDet->UPI_Id; ?></td>
                                            <td><?php echo $BDet->BankIFSC_Code; ?></td>
                                            <td><?php echo $BDet->BankBranchName; ?></td>
                                            <td><?php echo $BDet->BankAccountHolderName; ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary me-1 editBankDataBtn"><i class="bx bx-edit-alt"></i></button>
                                                <button class="btn btn-sm btn-danger deleteBankDataBtn"><i class="bx bx-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                            <hr id="bankDivider" class="<?php echo count($BankDetails) > 0 ? '' : 'd-none'; ?>">

                            <!-- Address Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0">Address Details</h5>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <div class="card-header modal-header-center-sticky d-flex align-items-center p-0 mb-2">
                                        <h5 class="mb-2">
                                            Billing Address
                                            <button type="button" class="btn btn-warning ms-1" id="addBillingAddress" data-divid="appendBillingAddress">
                                                <i class="bx bx-plus-circle me-1"></i>
                                            </button>
                                        </h5>
                                        <div class="ms-auto d-flex align-items-center">
                                            <button type="button" class="btn btn-primary btn-sm d-none" id="addrCopyToShipping">
                                                <i class="bx bx-copy-alt me-1"></i> Copy to Shipping
                                            </button>
                                        </div>
                                    </div>
                                    <div id="appendBillingAddress" class="d-none"></div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <div class="card-header modal-header-center-sticky p-0 mb-2">
                                        <h5 class="mb-2">
                                            Shipping Address
                                            <button type="button" class="btn btn-warning ms-1 d-none" id="addShippingAddress" data-divid="appendShippingAddress">
                                                <i class="bx bx-plus-circle me-1"></i>
                                            </button>
                                        </h5>
                                    </div>
                                    <div id="appendShippingAddress" class="d-none"></div>
                                </div>
                            </div>
                            <hr id="AddressDivider" class="d-none">

                            <!-- More Details -->
                            <div class="accordion mt-3 mb-3 accordion-without-arrow" id="AccountMoreDetails">
                                <div class="card accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button type="button" class="accordion-button bg-warning text-white d-flex flex-column align-items-start text-start" data-bs-toggle="collapse" data-bs-target="#accordionOne" aria-expanded="true" aria-controls="accordionOne">
                                            <span><i class="icon-base bx bx-caret-right me-1"></i> More Details ?</span>
                                            <span>Add Notes, Tags, Discount, CC Mails, Credit Limit</span>
                                        </button>
                                    </h2>
                                    <div id="accordionOne" class="accordion-collapse collapse" data-bs-parent="#AccountMoreDetails">
                                        <div class="accordion-body">
                                            <div class="row mt-3">
                                                <div class="col-md-3 d-flex justify-content-center align-items-center">
                                                    <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                                        <div class="dz-message needsclick text-center">
                                                            <i class="upload-icon mb-3"></i>
                                                            <p class="h5 needsclick mb-2">Drag and drop your logo here</p>
                                                            <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-4">
                                                            <label for="DiscountPercent" class="form-label">Discount (%)</label>
                                                            <input class="form-control" type="number" id="DiscountPercent" name="DiscountPercent" min="0" max="100" maxLength="3" placeholder="Discount (%)" onkeyup="changeHandler(this)" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo isset($EditData->DiscountPercent) ? smartDecimal($EditData->DiscountPercent) : '0'; ?>" />
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label for="CreditPeriod" class="form-label">Credit Period</label>
                                                            <input type="number" class="form-control" name="CreditPeriod" id="CreditPeriod" min="0" placeholder="Credit Period" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="<?php echo isset($EditData->CreditPeriod) ? $EditData->CreditPeriod : 30; ?>" />
                                                        </div>
                                                        <div class="mb-3 col-md-4">
                                                            <label for="CreditLimit" class="form-label">Credit Limit</label>
                                                            <input type="number" class="form-control" name="CreditLimit" id="CreditLimit" min="0" placeholder="Credit Limit" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="<?php echo isset($EditData->CreditLimit) ? smartDecimal($EditData->CreditLimit) : '0'; ?>" />
                                                        </div>
                                                        <div class="mb-3 col-md-12">
                                                            <label for="Notes" class="form-label">Notes</label>
                                                            <textarea class="form-control" rows="2" name="Notes" id="Notes" placeholder="Notes"><?php echo isset($EditData->Notes) ? $EditData->Notes : ''; ?></textarea>
                                                        </div>
                                                        <div class="mb-3 col-md-12">
                                                            <label class="form-label" for="Tags">Tags</label>
                                                            <select id="Tags" name="Tags[]" class="select2 form-select" multiple="multiple">
                                                                <?php if (isset($EditData->Tags) && !empty($EditData->Tags)) {
                                                                    foreach (explode(',', $EditData->Tags) as $Tags) { ?>
                                                                        <option value="<?php echo $Tags; ?>" selected><?php echo $Tags; ?></option>
                                                                <?php } } ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3 col-md-12">
                                                            <label class="form-label" for="CCEmails">CC Emails</label>
                                                            <select id="CCEmails" name="CCEmails[]" class="select2 form-select" multiple="multiple">
                                                                <?php if (isset($EditData->CCEmails) && !empty($EditData->CCEmails)) {
                                                                    foreach (explode(',', $EditData->CCEmails) as $CCEmails) { ?>
                                                                        <option value="<?php echo $CCEmails; ?>" selected><?php echo $CCEmails; ?></option>
                                                                <?php } } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

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

<script src="/js/customers.js"></script>

<script>
const StateInfo = <?php echo json_encode($StateData); ?>;
const CityInfo = <?php echo json_encode($CityData); ?>;
var imgData = '<?php echo isset($EditData->Image) ? $EditData->Image : ''; ?>';
$(function() {
    'use strict'

    <?php if (!empty($BillingAddr)) { ?>
        creationBilngAddrActions();
        const billingData = <?php echo json_encode($BillingAddr); ?>;
            $('#BillAddressUID').val(billingData.CustAddressUID || 0);
            $('#BillAddrLine1').val(billingData.Line1 || '');
            $('#BillAddrLine2').val(billingData.Line2 || '');
            $('#BillAddrPincode').val(billingData.Pincode || '');
            $('#BillAddrState').val(billingData.State || '').trigger('change');
            $('#BillAddrCity').val(billingData.City || '').trigger('change');
    <?php } if (!empty($ShippingAddr)) { ?>
        creationShipAddrActions();
        const shippingData = <?php echo json_encode($ShippingAddr); ?>;
            $('#ShipAddressUID').val(shippingData.CustAddressUID || 0);
            $('#ShipAddrLine1').val(shippingData.Line1 || '');
            $('#ShipAddrLine2').val(shippingData.Line2 || '');
            $('#ShipAddrPincode').val(shippingData.Pincode || '');
            $('#ShipAddrState').val(shippingData.State || '').trigger('change');
            $('#ShipAddrCity').val(shippingData.City || '').trigger('change');
    <?php } ?>

    initializeFlatPickr('#CPDateOfBirth');

    loadSelect2Field('#CountryCode', '-- Select Country --');

    initializeSelect2Tags('#Tags', 'Type and press enter...');
    initializeSelect2Tags('#CCEmails', 'Type and press enter...');

    if (hasValue(imgData)) {
        commonSetDropzoneImageOne(CDN_URL + imgData);
    }

    $('#EditCustomerForm').submit(function(e) {
        e.preventDefault();

        $('.addEditFormAlert').addClass('d-none');

        var formData = new FormData($('#EditCustomerForm')[0]);
        formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
        if(hasValue(imgData) && myOneDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
        }
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
            if (hasValue(city)) formData.append('BillAddrCityText', $('#BillAddrCity').find('option:selected').text());

            var state = $('#BillAddrState').find('option:selected').val();
            if (hasValue(state)) formData.append('BillAddrStateText', $('#BillAddrState').find('option:selected').text());

        }

        var ShipAddrLine1 = $('#ShipAddrLine1').val();
        if (hasValue(ShipAddrLine1)) {

            var city = $('#ShipAddrCity').find('option:selected').val();
            if (hasValue(city)) formData.append('ShipAddrCityText', $('#ShipAddrCity').find('option:selected').text());
            
            var state = $('#ShipAddrState').find('option:selected').val();
            if (hasValue(state)) formData.append('ShipAddrStateText', $('#ShipAddrState').find('option:selected').text());

        }

        editCustomerData(formData);

    });

});
</script>