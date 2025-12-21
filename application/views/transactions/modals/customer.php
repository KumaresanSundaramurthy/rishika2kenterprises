<!-- Add Customer Form -->
<div class="modal fade" id="transCustomerModal" tabindex="-1" aria-labelledby="transCustomerModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'addEditCustomerForm', 'name' => 'addEditCustomerForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('transaction/addEditCustomerForm', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky trans-theme d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="customerModalTitle">Create Customer</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary addEditCustBtn">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>
            </div>

            <input type="hidden" name="CustomerUID" id="CustomerUID" value="0" />

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto p-2">
                <div class="card-body p-2 mb-3">

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
                            <label for="EmailAddress" class="form-label">Email</label>
                            <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" maxlength="100" placeholder="Email Address" />
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label" for="MobileNumber">Mobile Number </label>
                            <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                        </div>
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

                </div>
            </div>

        <?php echo form_close(); ?>

        </div>
    </div>
</div>