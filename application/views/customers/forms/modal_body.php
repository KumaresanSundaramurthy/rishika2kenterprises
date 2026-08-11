<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Partial view — customers modal form body.
 * Variables: $FormMode ('add'|'edit'|'clone'), $FormData (object|null),
 *            $BankDetails, $BillingAddr, $ShippingAddr,
 *            $CustomerTypeList, $CustomerGroupList, $CountryInfo, $JwtData
 */
$isEdit  = ($FormMode === 'edit');
$isClone = ($FormMode === 'clone');
$d       = $FormData; // shorthand, null for add
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
?>

<form id="CustomerModalForm" data-mode="<?php echo $FormMode; ?>" autocomplete="off" novalidate>

    <?php if ($isEdit): ?>
    <input type="hidden" name="CustomerUID" id="CustomerUID" value="<?php echo (int)($d->CustomerUID ?? 0); ?>" />
    <?php endif; ?>

    <?php if (!$isEdit && !$isClone): ?>
    <div class="gstin-hint-strip" id="gstinPrefillHint">
        <div class="gstin-hint-icon-wrap">
            <i class="bx bx-id-card gstin-hint-icon"></i>
        </div>
        <div class="gstin-hint-body">
            <div class="gstin-hint-title">Autofill from GSTIN</div>
            <div class="gstin-hint-sub">Have the customer's GSTIN? Enter it below and click <strong>Fetch</strong> — business name, address &amp; tax category get filled automatically.</div>
        </div>
        <a href="javascript:void(0);" class="gstin-hint-cta" id="gstinPrefillBtn">
            Enter GSTIN <i class="bx bx-chevron-right ms-1"></i>
        </a>
    </div>
    <?php endif; ?>

    <div class="p-4">

        <!-- General Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">General Details</h5>
        </div>
        <div class="row">
            <div class="mb-3 col-md-2">
                <label for="CM_SalutationUID" class="form-label">Salutation</label>
                <select class="form-select" id="CM_SalutationUID" name="SalutationUID">
                    <option value="">—</option>
                </select>
            </div>
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
            <div class="mb-3 col-md-2">
                <label for="CM_CustomerNumber" class="form-label">Customer Number</label>
                <input class="form-control cust-num-field" type="text" id="CM_CustomerNumber" name="CustomerNumber"
                    readonly tabindex="-1"
                    placeholder="Auto-generated"
                    value="<?php echo htmlspecialchars($d->CustomerNumber ?? ''); ?>" />
            </div>
            <?php
                $activePhoneCode = (($isEdit || $isClone) && !empty($d->CountryCode)) ? $d->CountryCode : $OrgCCode;
                $activeISO2      = (($isEdit || $isClone) && !empty($d->CountryISO2))  ? $d->CountryISO2  : $OrgCISO2;
            ?>
            <div class="mb-3 col-md-4">
                <label class="form-label" for="CM_MobileNumber">Mobile Number </label>
                <div class="input-group position-relative">
                    <button type="button" class="btn btn-outline-secondary fw-semibold flex-shrink-0 r2k-cc-btn"
                            id="CM_MobileCCBtn" tabindex="-1">
                        <?php echo htmlspecialchars($activePhoneCode); ?>
                    </button>
                    <div id="CM_CCDropdown" class="r2k-cc-dropdown">
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control form-control-sm" id="CM_CCSearch"
                                   placeholder="Search country..." autocomplete="off">
                        </div>
                        <div id="CM_CCList" class="r2k-cc-list"></div>
                    </div>
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
                    <span class="input-group-text"><?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?></span>
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
                <input class="form-control text-uppercase" type="text" id="CM_PANNumber" name="PANNumber" maxlength="10"
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
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_GroupUID" class="form-label">Customer Group
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary px-2 py-0 ms-1"
                       data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo t('create_customer_group', 'Create Customer Group'); ?>"
                       onclick="CustomerGroupForm.open('add', null, { hideMembers: true, onSaveSuccess: function(r){ if (typeof CustomerForm !== 'undefined' && CustomerForm.clearGroupsCache) CustomerForm.clearGroupsCache(); if(r.GroupUID){ var opt = new Option(r.GroupName || r.GroupUID, r.GroupUID, true, true); $('#CM_GroupUID').append(opt).trigger('change'); } } })">+ New</a>
                </label>
                <select id="CM_GroupUID" name="GroupUID" class="form-select">
                    <option value="">— No Group —</option>
                </select>
            </div>
            <div class="col-md-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="CM_AllowPortalAccess"
                           name="AllowPortalAccess" value="1"
                           <?php echo ($isEdit && !empty($d->AllowPortalAccess)) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="CM_AllowPortalAccess">
                        Allow Customer Portal Access
                    </label>
                    <div class="form-text">When enabled, this customer can log in to the portal using their registered email address.</div>
                </div>
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
                    <input type="text" class="form-control text-uppercase" placeholder="GSTIN" name="GSTIN" id="CM_GSTIN"
                        value="<?php echo htmlspecialchars($d->GSTIN ?? ''); ?>" />
                    <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button>
                </div>
                <div id="CM_GSTINValidatedMsg" class="gstin-validated-msg<?php echo ($isEdit && (int)($d->GSTINValidated ?? 0) === 1) ? ' show' : ''; ?>">
                    <i class="bx bx-check-circle"></i> GSTIN is validated
                </div>
                <input type="hidden" name="GSTINValidated" id="CM_GSTINValidated" value="<?php echo ($isEdit) ? (int)($d->GSTINValidated ?? 0) : '0'; ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_CompanyName" class="form-label">Company Name</label>
                <input class="form-control" type="text" id="CM_CompanyName" name="CompanyName"
                    placeholder="Company Name" maxlength="100"
                    value="<?php echo htmlspecialchars($d->CompanyName ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_WorkPhone" class="form-label">Work Phone <span class="r2k-field-hint">(Validated by mobile country code. Optional)</span></label>
                <input type="number" class="form-control" id="CM_WorkPhone" name="WorkPhone"
                    placeholder="Work phone number"
                    onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                    oninput="this.value=this.value.slice(0,15)"
                    value="<?php echo htmlspecialchars($d->WorkPhone ?? ''); ?>" />
            </div>
            <div class="mb-3 col-md-4">
                <label for="CM_LandlineNumber" class="form-label">Landline Number <span class="r2k-field-hint">(Digits only, max 15. Optional)</span></label>
                <input type="text" class="form-control" id="CM_LandlineNumber" name="LandlineNumber"
                    placeholder="Landline number" maxlength="15"
                    onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,15)"
                    value="<?php echo htmlspecialchars($d->LandlineNumber ?? ''); ?>" />
            </div>
        </div>
        <hr>

        <!-- Bank Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">
                Bank Details
                <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning ms-1" id="addBankDetails" data-divid="appendBankDetails"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo t('btn_add_bank', 'Add Bank Account'); ?>">
                    <i class="bx bx-plus-circle me-1"></i> Bank Accounts
                </a>
            </h5>
        </div>
        <!-- Bank empty state -->
        <div class="d-flex flex-column align-items-center justify-content-center py-4 <?php echo count($BankDetails) > 0 ? 'd-none' : ''; ?>" id="bankEmptyState">
            <i class="bx bx-credit-card text-muted mb-2" style="font-size:2.5rem;"></i>
            <div class="fw-semibold text-muted mb-1">No bank accounts added</div>
            <div class="text-muted small">Add customer bank information to manage transactions</div>
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
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold small text-muted text-uppercase">Billing Address</span>
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" id="addBillingAddress" data-divid="appendBillingAddress"
                           data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo t('add_billing_addr', 'Add Billing Address'); ?>">
                            <i class="bx bx-plus-circle"></i>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToShippingBtn">
                        <i class="bx bx-copy-alt me-1"></i>Copy to Shipping
                    </button>
                </div>
                <div id="appendBillingAddress">
                    <div class="r2k-addr-empty">
                        <i class="bx bx-map-alt r2k-addr-empty-icon"></i>
                        <div class="r2k-addr-empty-text">No Billing Address added</div>
                    </div>
                </div>
            </div>
            <div class="mb-3 col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold small text-muted text-uppercase">Shipping Address</span>
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" id="addShippingAddress" data-divid="appendShippingAddress"
                           data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo t('add_shipping_addr', 'Add Shipping Address'); ?>">
                            <i class="bx bx-plus-circle"></i>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToBillingBtn">
                        <i class="bx bx-copy-alt me-1"></i>Copy to Billing
                    </button>
                </div>
                <div id="appendShippingAddress">
                    <div class="r2k-addr-empty">
                        <i class="bx bx-map-alt r2k-addr-empty-icon"></i>
                        <div class="r2k-addr-empty-text">No Shipping Address added</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Other Details -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">Other Details</h5>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <!-- Multi-image attachment zone (max 3 images · 3 MB total) -->
                <div id="custAttachZone" class="prod-attach-zone" onclick="_attachZoneTrigger('Customer', event)">
                    <div id="custAttachEmpty" class="prod-attach-empty">
                        <i class="bx bx-image-add" id="custAttachIcon" style="font-size:2rem;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                        <div id="custAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop images</div>
                        <div id="custAttachHint" style="font-size:.7rem;color:#9ca3af;margin-top:3px;">JPG, GIF or PNG · Max 3 · 3 MB total</div>
                    </div>
                </div>
                <div id="custAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                <input type="file" id="custAttachInput" multiple accept="image/jpeg,image/png,image/gif" style="display:none;">
                <input type="hidden" id="custAttachDeleteUIDs" name="CustAttachDeleteUIDs" value="">
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="CM_DiscountPercent" class="form-label">Discount (%)</label>
                        <input class="form-control" type="text" inputmode="decimal" id="CM_DiscountPercent" name="DiscountPercent"
                            placeholder="Discount (%)"
                            onkeypress="return (event.charCode===8||event.charCode===0||(event.charCode>=48&&event.charCode<=57)||(event.charCode===46&&this.value.indexOf('.')===-1))"
                            oninput="var p=this.value.split('.');if(p.length>1&&p[1].length><?php echo $dec; ?>)this.value=p[0]+'.'+p[1].slice(0,<?php echo $dec; ?>);var v=parseFloat(this.value);if(!isNaN(v)&&v>100)this.value='100';"
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
