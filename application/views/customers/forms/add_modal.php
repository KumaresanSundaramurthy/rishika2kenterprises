<!-- Customers Form -->
<div class="modal fade" id="addEditCustomerModal" tabindex="-1" aria-labelledby="customersModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'AddCustomerForm', 'name' => 'AddCustomerForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('customers/addCustomer', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="CustomerModalTitle">Add Customer</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary AddEditCustomerBtn">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Discard</button>
                </div>
            </div>

            <input type="hidden" name="CustomerUID" id="HCustomerUID" value="0" />

            <div class="d-none col-lg-12 px-5 mt-3 addEditFormAlert" role="alert"></div>

            <div class="modal-body flex-grow-1">
                
                <div class="card-header modal-header-center-sticky p-1 mb-3">
                    <h5 class="modal-title">General Details</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="Name" class="form-label">Customer Name <span style="color:red">*</span></label>
                        <input class="form-control" type="text" id="Name" name="Name" placeholder="Name" maxlength="100" required />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="Name" class="form-label">Area </label>
                        <input class="form-control" type="text" id="Area" name="Area" placeholder="Area" maxlength="100" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="email" class="form-label">Email </label>
                        <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" />
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="MobileNumber">Mobile Number <span style="color:red">*</span></label>
                        <div class="d-flex gap-2">
                            <select id="CountryCode" name="CountryCode" class="select2 form-select">
                                <option label="-- Select Country Code --"></option>
                                <?php if (sizeof($CountryInfo) > 0) {
                                    foreach ($CountryInfo as $Country) { ?>
                                        <option value="<?php echo $Country['phone'][0]; ?>" data-region="<?php echo $Country['region']; ?>" data-ccode="<?php echo $Country['iso']['alpha-2']; ?>" <?php echo ($Country['iso']['alpha-2'] == $JwtData->User->OrgCISO2) ? 'selected' : ''; ?>><?php echo '(' . $Country['phone'][0] . ') ' . $Country['name']; ?></option>
                                <?php }
                                } ?>

                            </select>
                            <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="text" class="form-label">Opening Balance</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" name="DebitCreditAmount" id="DebitCreditAmount" min="0" placeholder="Debit / Credit Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" value="0" />
                            <select id="DebitCreditCheck" name="DebitCreditCheck" class="select2 form-select">
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
                        <label for="ContactPerson" class="form-label">Contact Person </label>
                        <input class="form-control" type="text" id="ContactPerson" name="ContactPerson" placeholder="Contact Name" maxlength="100" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="CPDateOfBirth" class="form-label">Date of Birth </label>
                        <input type="text" id="CPDateOfBirth" name="CPDateOfBirth" class="form-control flatpickr-basic" placeholder="YYYY-MM-DD" />
                    </div>
                </div>
                <hr>

                <div class="card-header modal-header-center-sticky p-1 mb-3">
                    <h5 class="modal-title">Company Details</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="text" class="form-label">GSTIN</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="GSTIN" />
                            <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="CompanyName" class="form-label">Company Name </label>
                        <input class="form-control" type="text" id="CompanyName" name="CompanyName" placeholder="Company Name" maxlength="100" />
                    </div>
                    
                </div>
                <hr>

                <div class="card-header modal-header-center-sticky p-1 mb-3">
                    <h5 class="modal-title">Bank Details</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="BankAccNumber" class="form-label">Bank Account Number </label>
                        <input class="form-control" type="text" id="BankAccNumber" name="BankAccNumber" maxlength="50" placeholder="Bank Account Number" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="BankIFSC_Code" class="form-label">IFSC Code </label>
                        <input class="form-control" type="text" id="BankIFSC_Code" name="BankIFSC_Code" maxlength="50" placeholder="Bank IFSC Code" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="BankBranchName" class="form-label">Bank & Branch Name </label>
                        <input class="form-control" type="text" id="BankBranchName" name="BankBranchName" maxlength="100" placeholder="Bank & Branch Number" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="BankAccHolderName" class="form-label">Account Holder's Name </label>
                        <input class="form-control" type="text" id="BankAccHolderName" name="BankAccHolderName" maxlength="50" placeholder="Account Holder's Name" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="UPITransfer_Id" class="form-label">UPI ID </label>
                        <input class="form-control" type="text" id="UPITransfer_Id" name="UPITransfer_Id" maxlength="100" placeholder="UPI Transfer ID" />
                    </div>
                </div>
                <hr>

                <div class="card-header modal-header-center-sticky p-1 mb-3">
                    <h5 class="modal-title">Address Details</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <div class="card-header modal-header-center-sticky d-flex align-items-center">
                            <h5 class="mb-2">Billing Address <button type="button" class="btn btn-warning ms-1" id="addBillingAddress" data-divid="appendBillingAddress">
                                    <i class="bx bx-plus-circle me-1"></i>
                                </button></h5>
                            <div class="ms-auto d-flex align-items-center">
                                <button type="button" class="btn btn-primary btn-sm d-none" id="addrCopyToShipping">
                                <i class="bx bx-copy-alt me-1"></i> Copy to Shipping
                                </button>
                            </div>
                        </div>
                        <div id="appendBillingAddress" class="d-none"></div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <div class="card-header modal-header-center-sticky">
                            <h5 class="mb-2">Shipping Address <button type="button" class="btn btn-warning ms-1 d-none" id="addShippingAddress" data-divid="appendShippingAddress"><i class="bx bx-plus-circle me-1"></i> </button></h5>
                        </div>
                        <div id="appendShippingAddress" class="d-none"></div>
                    </div>
                </div>
                <hr>

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
                                                <label for="DiscountPercent" class="form-label">Discount (%) </label>
                                                <input class="form-control" type="number" id="DiscountPercent" name="DiscountPercent" min="0" max="100" maxLength="3" placeholder="Discount (%)" onkeyup="changeHandler(this)" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label for="CreditPeriod" class="form-label">Credit Period </label>
                                                <input type="number" class="form-control" name="CreditPeriod" id="CreditPeriod" min="0" placeholder="Credit Period" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" />
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label for="CreditLimit" class="form-label">Credit Limit </label>
                                                <input type="number" class="form-control" name="CreditLimit" id="CreditLimit" min="0" placeholder="Credit Limit" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="6" pattern="[0-9]*" />
                                            </div>
                                            <div class="mb-3 col-md-12">
                                                <label for="Notes" class="form-label">Notes </label>
                                                <textarea class="form-control" rows="2" name="Notes" id="Notes" placeholder="Notes"></textarea>
                                            </div>
                                            <div class="mb-3 col-md-12">
                                                <label class="form-label" for="Tags">Tags </label>
                                                <select id="Tags" name="Tags[]" class="select2 form-select" multiple="multiple"></select>
                                            </div>
                                            <div class="mb-3 col-md-12">
                                                <label class="form-label" for="CCEmails">CC Emails </label>
                                                <select id="CCEmails" name="CCEmails[]" class="select2 form-select" multiple="multiple"></select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-none col-lg-12 px-4 addEditFormAlert" role="alert"></div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>