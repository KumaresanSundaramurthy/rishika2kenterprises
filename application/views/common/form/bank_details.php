<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="addEditBankDataModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

        <?php $FormAttribute = array('id' => 'AddEditBankDataForm', 'name' => 'AddEditBankDataForm', 'class' => '', 'autocomplete' => 'off');
                echo form_open('common/addEditBankInformation', $FormAttribute); ?>

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-buildings text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="bankDataModalTitle">Add Bank Details</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-primary AddEditBankDataBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <input type="hidden" id="HBankId" name="HBankId" value="">

            <div class="modal-body">
                <div class="two-col-or">

                    <div class="bank-section">
                        <div class="mb-3">
                            <label for="BankAccNumber" class="form-label">Bank Account Number <span style="color:red">*</span></label>
                            <input class="form-control" type="password" id="BankAccNumber" name="BankAccNumber" placeholder="Bank Account Number" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="50" pattern="[0-9]*" onpaste="return false;" ondrop="return false;" />
                        </div>
                        <div class="mb-3">
                            <label for="ReEntBankAccNumber" class="form-label">Re-Enter Bank Account Number <span style="color:red">*</span></label>
                            <input class="form-control" type="text" id="ReEntBankAccNumber" name="ReEntBankAccNumber" placeholder="Re-Enter Bank Account Number" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" maxLength="50" pattern="[0-9]*" />
                        </div>
                        <div class="mb-3">
                            <label for="BankIFSC_Code" class="form-label">IFSC Code <span style="color:red">*</span></label>
                            <div class="input-group">
                                <input class="form-control" type="text" id="BankIFSC_Code" name="BankIFSC_Code" maxlength="11" placeholder="e.g. HDFC0001234" style="text-transform:uppercase;" />
                                <button class="btn btn-outline-primary" type="button" id="IFSC_Fetch">Fetch</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="BankBranchName" class="form-label">Bank & Branch Name <span style="color:red">*</span></label>
                            <input class="form-control" type="text" id="BankBranchName" name="BankBranchName" maxlength="100" placeholder="Bank & Branch Number" />
                        </div>
                        <div class="mb-3">
                            <label for="BankAccHolderName" class="form-label">Account Holder's Name <span style="color:red">*</span></label>
                            <input class="form-control" type="text" id="BankAccHolderName" name="BankAccHolderName" maxlength="50" placeholder="Account Holder's Name" />
                        </div>
                    </div>

                    <div class="or-divider">
                        <span class="or-label">OR</span>
                    </div>

                    <div class="upi-section">
                        <div class="mb-3">
                            <label for="UPITransfer_Id" class="form-label">UPI ID <span style="color:red">*</span></label>
                            <input class="form-control" type="text" id="UPITransfer_Id" name="UPITransfer_Id" maxlength="100" placeholder="UPI Transfer ID" />
                        </div>
                    </div>
                </div>
                <div class="d-none addEditBankDataAlert mt-3 mb-0 alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="alert-message"></div>
                        <button type="button" class="btn-close ms-2 mt-1" aria-label="Close"></button>
                    </div>
                </div>
            </div>


            <?php echo form_close(); ?>

        </div>
    </div>
</div>