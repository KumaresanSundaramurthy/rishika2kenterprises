<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Partial view — customers modal form body.
 * Variables: $FormMode ('add'|'edit'|'clone'), $FormData (object|null),
 *            $BankDetails, $BillingAddr, $ShippingAddr,
 *            $CustomerTypeList, $CountryInfo, $JwtData
 */
$isEdit  = ($FormMode === 'edit');
$isClone = ($FormMode === 'clone');
$d       = $FormData; // shorthand, null for add
?>

<form id="CustomerModalForm" data-mode="<?php echo $FormMode; ?>" autocomplete="off" novalidate>

    <?php if ($isEdit): ?>
    <input type="hidden" name="CustomerUID" id="CustomerUID" value="<?php echo (int)($d->CustomerUID ?? 0); ?>" />
    <?php endif; ?>

    <div class="p-4">

        <!-- General Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">General Details</h5>
        </div>
        <div class="row">
            <div class="mb-3 col-md-4">
                <label for="CM_Name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                <input class="form-control" type="text" id="CM_Name" name="Name" placeholder="Name" maxlength="100" required
                    value="<?php echo htmlspecialchars($d->Name ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_Area" class="form-label">Area</label>
                <input class="form-control" type="text" id="CM_Area" name="Area" placeholder="Area" maxlength="100"
                    value="<?php echo htmlspecialchars($d->Area ?? ''); ?>" />
            </div>
            <?php
                $orgISO2      = $JwtData->User->OrgCISO2 ?? 'IN';
                $defPhoneCode = '+91';
                foreach ($CountryInfo as $_c) {
                    if ($_c->iso->{'alpha-2'} == $orgISO2) { $defPhoneCode = $_c->phone[0]; break; }
                }
                $activePhoneCode = (($isEdit || $isClone) && !empty($d->CountryCode)) ? $d->CountryCode : $defPhoneCode;
                $activeISO2 = $orgISO2;
                foreach ($CountryInfo as $_c) {
                    if ($_c->phone[0] == $activePhoneCode) { $activeISO2 = $_c->iso->{'alpha-2'}; break; }
                }
            ?>
            <div class="mb-3 col-md-4">
                <label class="form-label" for="CM_MobileNumber">Mobile Number <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text fw-semibold"><?php echo htmlspecialchars($activePhoneCode); ?></span>
                    <input type="hidden" name="CountryCode" id="CM_CountryCode" value="<?php echo htmlspecialchars($activePhoneCode); ?>" />
                    <input type="hidden" name="CountryISO2" id="CM_CountryISO2" value="<?php echo htmlspecialchars($activeISO2); ?>" />
                    <input type="number" id="CM_MobileNumber" name="MobileNumber" class="form-control"
                        placeholder="9790 000 0000" maxlength="20"
                        onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                        oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*"
                        value="<?php echo htmlspecialchars($d->MobileNumber ?? ''); ?>" />
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_EmailAddress" class="form-label">Email</label>
                <input class="form-control" type="email" id="CM_EmailAddress" name="EmailAddress" maxlength="100"
                    placeholder="Email Address"
                    value="<?php echo htmlspecialchars($d->EmailAddress ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_DebitCreditAmount" class="form-label">Opening Balance</label>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control" name="DebitCreditAmount" id="CM_DebitCreditAmount"
                        min="0" placeholder="Debit / Credit Amount" maxlength="6" pattern="[0-9]*"
                        onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                        oninput="this.value=this.value.slice(0,this.maxLength)"
                        value="<?php echo $isEdit ? smartDecimal($d->DebitCreditAmount ?? 0) : '0'; ?>" />
                    <select id="CM_DebitCreditCheck" name="DebitCreditCheck" class="select2 form-select border-start ps-2">
                        <option value="Debit" <?php echo (!$isEdit || ($d->DebitCreditType ?? '') === 'Debit') ? 'selected' : ''; ?>>To Collect</option>
                        <option value="Credit" <?php echo ($isEdit && ($d->DebitCreditType ?? '') === 'Credit') ? 'selected' : ''; ?>>To Pay</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_PANNumber" class="form-label">PAN Number</label>
                <input class="form-control" type="text" id="CM_PANNumber" name="PANNumber" maxlength="10"
                    placeholder="PAN Number"
                    value="<?php echo htmlspecialchars($d->PANNumber ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_ContactPerson" class="form-label">Contact Person</label>
                <input class="form-control" type="text" id="CM_ContactPerson" name="ContactPerson"
                    placeholder="Contact Name" maxlength="100"
                    value="<?php echo htmlspecialchars($d->ContactPerson ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_CPDateOfBirth" class="form-label">Date of Birth</label>
                <input type="text" id="CM_CPDateOfBirth" name="CPDateOfBirth"
                    class="form-control flatpickr-basic" placeholder="YYYY-MM-DD"
                    value="<?php echo htmlspecialchars($d->DateOfBirth ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_CustomerTypeUID" class="form-label">Customer Type <span class="text-danger">*</span></label>
                <select id="CM_CustomerTypeUID" name="CustomerTypeUID" class="form-select" required>
                    <option value="">-- Select Customer Type --</option>
                    <?php foreach ($CustomerTypeList as $ct): ?>
                        <option value="<?php echo $ct->CustomerTypeUID; ?>"
                            <?php echo ($isEdit || $isClone)
                                ? (isset($d->CustomerTypeUID) && $d->CustomerTypeUID == $ct->CustomerTypeUID ? 'selected' : '')
                                : ($ct->IsDefault ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($ct->TypeName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <hr>

        <!-- Company Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">Company Details</h5>
        </div>
        <div class="row">
            <div class="mb-3 col-md-4">
                <label for="CM_GSTIN" class="form-label">GSTIN</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="GSTIN" name="GSTIN" id="CM_GSTIN"
                        value="<?php echo htmlspecialchars($d->GSTIN ?? ''); ?>" />
                    <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_CompanyName" class="form-label">Company Name</label>
                <input class="form-control" type="text" id="CM_CompanyName" name="CompanyName"
                    placeholder="Company Name" maxlength="100"
                    value="<?php echo htmlspecialchars($d->CompanyName ?? ''); ?>" />
            </div>
        </div>
        <hr>

        <!-- Bank Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">
                Bank Details
                <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addBankDetails" data-divid="appendBankDetails">
                    <i class="bx bx-plus-circle me-1"></i> Bank Accounts
                </a>
            </h5>
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
                    <tr data-id="<?php echo $BDet->CustBankDetUID; ?>"
                        data-type="<?php echo $BDet->Type; ?>"
                        data-record='<?php echo json_encode([
                            "id"        => (int)$BDet->CustBankDetUID,
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
                <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center p-0 mb-2">
                    <h5 class="mb-2">
                        Billing Address
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addBillingAddress" data-divid="appendBillingAddress">
                            <i class="bx bx-plus-circle me-1"></i> Billing Address
                        </a>
                    </h5>
                    <div class="ms-auto d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary ms-1 d-none" id="addrCopyToShipping">
                            <i class="bx bx-copy-alt me-1"></i> Copy to Shipping
                        </a>
                        <button type="button" id="deleteBillingAddress" class="btn btn-outline-danger btn-sm ms-2 d-none"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
                <div id="appendBillingAddress" class="d-none"></div>
            </div>
            <div class="mb-3 col-md-6">
                <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center p-0 mb-2">
                    <h5 class="mb-2">
                        Shipping Address
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addShippingAddress" data-divid="appendShippingAddress">
                            <i class="bx bx-plus-circle me-1"></i> Shipping Address
                        </a>
                    </h5>
                    <button type="button" id="deleteShippingAddress" class="btn btn-outline-danger btn-sm d-none"><i class="bx bx-trash"></i></button>
                </div>
                <div id="appendShippingAddress" class="d-none"></div>
            </div>
        </div>
        <hr id="AddressDivider" class="d-none">

        <!-- Other Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">Other Details</h5>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="dropzone dropzone-main-form needsclick dz-clickable w-100" id="DropzoneOneBasic" style="min-height:160px;">
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
                        <label for="CM_DiscountPercent" class="form-label">Discount (%)</label>
                        <input class="form-control" type="number" id="CM_DiscountPercent" name="DiscountPercent"
                            min="0" max="100" maxlength="3" placeholder="Discount (%)"
                            onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                            oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*"
                            value="<?php echo $isEdit ? smartDecimal($d->DiscountPercent ?? 0) : '0'; ?>" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="CM_CreditPeriod" class="form-label">Credit Period</label>
                        <input type="number" class="form-control" name="CreditPeriod" id="CM_CreditPeriod"
                            min="0" placeholder="Credit Period" maxlength="6" pattern="[0-9]*"
                            onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                            oninput="this.value=this.value.slice(0,this.maxLength)"
                            value="<?php echo $isEdit ? ($d->CreditPeriod ?? 30) : '30'; ?>" />
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="CM_CreditLimit" class="form-label">Credit Limit</label>
                        <input type="number" class="form-control" name="CreditLimit" id="CM_CreditLimit"
                            min="0" placeholder="Credit Limit" maxlength="6" pattern="[0-9]*"
                            onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                            oninput="this.value=this.value.slice(0,this.maxLength)"
                            value="<?php echo $isEdit ? smartDecimal($d->CreditLimit ?? 0) : '0'; ?>" />
                    </div>
                    <div class="mb-3 col-md-12">
                        <label for="CM_Notes" class="form-label">Notes</label>
                        <textarea class="form-control" rows="2" name="Notes" id="CM_Notes" placeholder="Notes"><?php echo htmlspecialchars($d->Notes ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3 col-md-12">
                        <label class="form-label" for="CM_Tags">Tags</label>
                        <select id="CM_Tags" name="Tags[]" class="select2 form-select" multiple="multiple">
                            <?php if ($isEdit && !empty($d->Tags)):
                                foreach (explode(',', $d->Tags) as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>" selected><?php echo htmlspecialchars($tag); ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="mb-3 col-md-12">
                        <label class="form-label" for="CM_CCEmails">CC Emails</label>
                        <select id="CM_CCEmails" name="CCEmails[]" class="select2 form-select" multiple="multiple">
                            <?php if ($isEdit && !empty($d->CCEmails)):
                                foreach (explode(',', $d->CCEmails) as $email): ?>
                                <option value="<?php echo htmlspecialchars($email); ?>" selected><?php echo htmlspecialchars($email); ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /p-4 -->

</form>
