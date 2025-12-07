<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="addEditBankDataModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

        <?php $FormAttribute = array('id' => 'AddEditBankDataForm', 'name' => 'AddEditBankDataForm', 'class' => '', 'autocomplete' => 'off');
                echo form_open('common/addEditBankInformation', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="bankDataModalTitle">Add Bank Details</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-icon-square" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x fs-4"></i>
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
                            <input class="form-control" type="text" id="BankIFSC_Code" name="BankIFSC_Code" maxlength="50" placeholder="Bank IFSC Code" />
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

            <hr class="mt-0 mb-0 border-top" />
            <div class="modal-footer p-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary AddEditBankDataBtn">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>