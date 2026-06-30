<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Partial view — vendors modal form body.
 * Variables: $FormMode ('add'|'edit'|'clone'), $FormData (object|null),
 *            $BankDetails, $BillingAddr, $ShippingAddr,
 *            $CountryInfo, $OrgCCode, $OrgCISO2, $JwtData
 */
$isEdit  = ($FormMode === 'edit');
$isClone = ($FormMode === 'clone');
$d       = $FormData;
?>

<form id="VendorModalForm" data-mode="<?php echo $FormMode; ?>" autocomplete="off" novalidate>

    <input type="hidden" name="VendorUID" id="VendorUID" value="0" />

    <div class="p-4">

        <!-- General Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">General Details</h5>
        </div>
        <div class="row">
            <div class="mb-3 col-md-2">
                <label for="VM_SalutationUID" class="form-label">Salutation</label>
                <select class="form-select" id="VM_SalutationUID" name="SalutationUID">
                    <option value="">—</option>
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_Name" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                <input class="form-control" type="text" id="VM_Name" name="Name" placeholder="Name" maxlength="100" required
                    value="<?php echo htmlspecialchars($d->Name ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_Area" class="form-label">Area</label>
                <input class="form-control" type="text" id="VM_Area" name="Area" placeholder="Area" maxlength="100"
                    value="<?php echo htmlspecialchars($d->Area ?? ''); ?>" />
            </div>
            <?php
                $activePhoneCode = (($isEdit || $isClone) && !empty($d->CountryCode)) ? $d->CountryCode : $OrgCCode;
                $activeISO2      = (($isEdit || $isClone) && !empty($d->CountryISO2))  ? $d->CountryISO2  : $OrgCISO2;
            ?>
            <div class="mb-3 col-md-4">
                <label class="form-label" for="VM_MobileNumber">Mobile Number <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text fw-semibold"><?php echo htmlspecialchars($activePhoneCode); ?></span>
                    <input type="hidden" name="CountryCode" id="VM_CountryCode" value="<?php echo htmlspecialchars($activePhoneCode); ?>" />
                    <input type="hidden" name="CountryISO2" id="VM_CountryISO2" value="<?php echo htmlspecialchars($activeISO2); ?>" />
                    <input type="number" id="VM_MobileNumber" name="MobileNumber" class="form-control"
                        placeholder="9790 000 0000" maxlength="20"
                        onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                        oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*"
                        value="<?php echo htmlspecialchars($d->MobileNumber ?? ''); ?>" />
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_EmailAddress" class="form-label">Email</label>
                <input class="form-control" type="email" id="VM_EmailAddress" name="EmailAddress" maxlength="100"
                    placeholder="Email Address"
                    value="<?php echo htmlspecialchars($d->EmailAddress ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_DebitCreditAmount" class="form-label">Opening Balance</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control" name="DebitCreditAmount" id="VM_DebitCreditAmount"
                        min="0" placeholder="Debit / Credit Amount" maxlength="6" pattern="[0-9]*"
                        onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                        oninput="this.value=this.value.slice(0,this.maxLength)"
                        value="0" />
                    <select id="VM_DebitCreditCheck" name="DebitCreditCheck" class="select2 form-select border-start ps-2">
                        <option value="Debit">To Collect</option>
                        <option value="Credit" selected>To Pay</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_PANNumber" class="form-label">PAN Number</label>
                <input class="form-control" type="text" id="VM_PANNumber" name="PANNumber" maxlength="10"
                    placeholder="PAN Number"
                    value="<?php echo htmlspecialchars($d->PANNumber ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_ContactPerson" class="form-label">Contact Person</label>
                <input class="form-control" type="text" id="VM_ContactPerson" name="ContactPerson"
                    placeholder="Contact Name" maxlength="100"
                    value="<?php echo htmlspecialchars($d->ContactPerson ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="VM_CPDateOfBirth" class="form-label">Date of Birth</label>
                <input type="text" id="VM_CPDateOfBirth" name="CPDateOfBirth"
                    class="form-control flatpickr-basic" placeholder="DD Mon YYYY"
                    value="<?php echo htmlspecialchars($d->DateOfBirth ?? ''); ?>" />
            </div>
        </div>
        <hr>

        <!-- Company Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">Company Details</h5>
        </div>
        <div class="row">
            <div class="col-md-3">
                <!-- Multi-image attachment zone (max 3 images · 3 MB total) -->
                <div id="vendAttachZone" class="prod-attach-zone" onclick="_attachZoneTrigger('Vendor', event)">
                    <div id="vendAttachEmpty" class="prod-attach-empty">
                        <i class="bx bx-image-add" id="vendAttachIcon" style="font-size:2rem;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                        <div id="vendAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop images</div>
                        <div id="vendAttachHint" style="font-size:.7rem;color:#9ca3af;margin-top:3px;">JPG, GIF or PNG · Max 3 · 3 MB total</div>
                    </div>
                </div>
                <div id="vendAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                <input type="file" id="vendAttachInput" multiple accept="image/jpeg,image/png,image/gif" style="display:none;">
                <input type="hidden" id="vendAttachDeleteUIDs" name="VendAttachDeleteUIDs" value="">
            </div>
            <div class="col-md-9">
                <div class="mb-3">
                    <label for="VM_GSTIN" class="form-label">GSTIN</label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="VM_GSTIN"
                            value="<?php echo htmlspecialchars($d->GSTIN ?? ''); ?>" />
                        <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="VM_CompanyName" class="form-label">Company Name</label>
                    <input class="form-control" type="text" id="VM_CompanyName" name="CompanyName"
                        placeholder="Company Name" maxlength="100"
                        value="<?php echo htmlspecialchars($d->CompanyName ?? ''); ?>" />
                </div>
                <div class="mb-3">
                    <label for="VM_Notes" class="form-label">Notes</label>
                    <textarea class="form-control" rows="2" name="Notes" id="VM_Notes" placeholder="Notes"><?php echo htmlspecialchars($d->Notes ?? ''); ?></textarea>
                </div>
                <div class="mb-3" id="CustomerLinkingDiv">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="CustomerLinkingCheck" id="VM_CreateCustomer" value="NewCustomer" />
                            <label class="form-check-label" for="VM_CreateCustomer">Create customer with same details</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="CustomerLinkingCheck" id="VM_ExistingCustomer" value="OldCustomer" />
                            <label class="form-check-label" for="VM_ExistingCustomer">Link Existing Customer</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="ResetCustomerLinking">Reset</button>
                    </div>
                    <div class="d-none mt-2" id="CustomerDiv">
                        <select class="select2 form-select" id="VM_Customers" name="Customers"></select>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        <!-- Bank Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">
                Bank Details
                <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addBankDetails" data-divid="appendBankDetails">
                    <i class="bx bx-plus-circle"></i>
                </a>
            </h5>
        </div>

        <!-- Bank empty state -->
        <div class="d-flex flex-column align-items-center justify-content-center py-4 <?php echo count($BankDetails) > 0 ? 'd-none' : ''; ?>" id="bankEmptyState">
            <i class="bx bx-credit-card text-muted mb-2" style="font-size:2.5rem;"></i>
            <div class="fw-semibold text-muted mb-1">No bank accounts added</div>
            <div class="text-muted small">Add vendor bank information to manage transactions</div>
        </div>

        <div class="table-responsive <?php echo count($BankDetails) > 0 ? '' : 'd-none'; ?>" id="appendBankDetails">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Type</th><th>Account / UPI</th><th>IFSC</th><th>Branch</th><th>Holder</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="bankDetailsBody">
                <?php foreach ($BankDetails as $BDet): ?>
                    <tr data-id="<?php echo $BDet->VendBankDetUID; ?>"
                        data-type="<?php echo $BDet->Type; ?>"
                        data-record='<?php echo json_encode([
                            "id"        => (int)$BDet->VendBankDetUID,
                            "type"      => $BDet->Type,
                            "accNumber" => $BDet->BankAccountNumber ?? "",
                            "ifsc"      => $BDet->BankIFSC_Code ?? "",
                            "branch"    => $BDet->BankBranchName ?? "",
                            "holder"    => $BDet->BankAccountHolderName ?? "",
                            "upiId"     => $BDet->UPI_Id ?? "",
                        ]); ?>'>
                        <td><?php echo $BDet->Type; ?></td>
                        <td><?php echo $BDet->Type === 'Bank' ? $BDet->BankAccountNumber : $BDet->UPI_Id; ?></td>
                        <td><?php echo $BDet->BankIFSC_Code; ?></td>
                        <td><?php echo $BDet->BankBranchName; ?></td>
                        <td><?php echo $BDet->BankAccountHolderName; ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary me-1 editBankDataBtn"><i class="bx bx-edit-alt"></i></button>
                            <button class="btn btn-sm btn-danger deleteBankDataBtn"><i class="bx bx-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold small text-muted text-uppercase">Billing Address</span>
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" id="addBillingAddress" data-divid="appendBillingAddress">
                            <i class="bx bx-plus-circle"></i>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToShippingBtn">
                        <i class="bx bx-copy-alt me-1"></i>Copy to Shipping
                    </button>
                </div>
                <div id="appendBillingAddress"></div>
            </div>
            <div class="mb-3 col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold small text-muted text-uppercase">Shipping Address</span>
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" id="addShippingAddress" data-divid="appendShippingAddress">
                            <i class="bx bx-plus-circle"></i>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToBillingBtn">
                        <i class="bx bx-copy-alt me-1"></i>Copy to Billing
                    </button>
                </div>
                <div id="appendShippingAddress"></div>
            </div>
        </div>

    </div><!-- /p-4 -->

</form>
