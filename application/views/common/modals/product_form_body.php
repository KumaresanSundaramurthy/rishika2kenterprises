<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI =& get_instance();
$CI->load->model('customers_model');

$_orgUID          = (int)($CI->pageData['JwtData']->Org->OrgUID ?? 0);
$_JwtData         = $CI->pageData['JwtData'];
$_ps              = $_JwtData->ProdSettings ?? new stdClass();
$_defProdTypeUID  = (int)($_ps->DefaultProductTypeUID  ?? 0);
$_defDiscTypeUID  = (int)($_ps->DefaultDiscountTypeUID ?? 0);
$_defProdTaxUID   = (int)($_ps->DefaultProductTaxUID   ?? 0);
$_defTaxDetailUID = (int)($_ps->DefaultTaxDetailUID    ?? 0);

$_CustomerTypeInfo = $CI->customers_model->getCustomerTypeList($_orgUID) ?? [];

$_fltStorageData = [];
if (!empty($_JwtData->GenSettings->EnableStorage)) {
    $CI->load->model('storage_model');
    $_fltStorageData = $CI->storage_model->getStorageDetails([]) ?? [];
}
?>

<?php $FormAttribute = array('id' => 'AddEditItemForm', 'name' => 'AddEditItemForm', 'class' => '', 'autocomplete' => 'off');
      echo form_open('products/addEditItem', $FormAttribute); ?>

    <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
        <div class="d-flex align-items-center gap-3">
            <div class="modal-doc-icon bg-primary bg-opacity-10">
                <i class="bx bx-package text-primary modal-doc-icon-inner"></i>
            </div>
            <div>
                <h5 class="modal-title mb-0" id="ItemModalTitle">Create Item</h5>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-sm btn-primary AddEditProductBtn"><i class="bx bx-check me-1"></i>Save</button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x me-1"></i>Close</button>
        </div>
    </div>

    <input type="hidden" name="ProductUID" id="HProductUID" value="0" />
    <input type="hidden" name="<?= $CI->security->get_csrf_token_name() ?>" value="<?= $CI->security->get_csrf_hash() ?>">

    <div class="d-none col-lg-12 px-5 mt-3 addEditFormAlert" role="alert"></div>

    <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
        <div class="card-body p-2 mb-3">

            <!-- Basic Details -->
            <div class="card-header modal-header-border-bottom p-1 mb-3">
                <h5 class="modal-title mb-0">Basic Details</h5>
            </div>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label for="ItemName" class="form-label">Product Name <span style="color:red">*</span></label>
                    <input class="form-control" type="text" id="ItemName" name="ItemName" placeholder="Enter Item Name" maxlength="100" required />
                </div>
                <div class="mb-3 col-md-6">
                    <label for="ProductType" class="form-label">Product Type <span style="color:red">*</span></label>
                    <select class="form-select" id="ProductType" name="ProductType" required>
                    </select>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="SellingPrice" class="form-label">Selling Price <span style="color:red" class="me-1">*</span>(<span id="SellingPriceTaxHelp" class="form-text text-danger">Inclusive of Taxes</span><span id="SellingPriceWTaxHelp" class="form-text text-danger d-none">Exclusive of Taxes</span>)</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span>
                        <input type="text" class="form-control" name="SellingPrice" id="SellingPrice" min="0" placeholder="Enter Selling Price"
                            onkeydown="return handleDotOnly(event)"
                            oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>"
                            pattern="^\d{1,<?= $_JwtData->GenSettings->PriceMaxLength ?>}(\.\d{0,<?= $_JwtData->GenSettings->DecimalPoints ?>})?$"
                            onpaste="handlePricePaste(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            ondrop="handlePriceDrop(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)" required />
                        <select class="form-select tax-option-select" id="SellingTaxOption" name="SellingTaxOption">
                        </select>
                    </div>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="MRP" class="form-label">MRP (Maximum Retail Price)</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span>
                        <input type="text" class="form-control" name="MRP" id="MRP" min="0" placeholder="Enter MRP"
                            onkeydown="return handleDotOnly(event)"
                            oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>"
                            pattern="^\d{1,<?= $_JwtData->GenSettings->PriceMaxLength ?>}(\.\d{0,<?= $_JwtData->GenSettings->DecimalPoints ?>})?$"
                            onpaste="handlePricePaste(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            ondrop="handlePriceDrop(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)" value="0" />
                    </div>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="PurchasePrice" class="form-label">Purchase Price</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span>
                        <input type="text" class="form-control" name="PurchasePrice" id="PurchasePrice" min="0" placeholder="Enter Purchase Price"
                            onkeydown="return handleDotOnly(event)"
                            oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>"
                            pattern="^\d{1,<?= $_JwtData->GenSettings->PriceMaxLength ?>}(\.\d{0,<?= $_JwtData->GenSettings->DecimalPoints ?>})?$"
                            onpaste="handlePricePaste(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            ondrop="handlePriceDrop(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)" />
                        <select class="form-select tax-option-select" id="PurchaseTaxOption" name="PurchaseTaxOption">
                        </select>
                    </div>
                </div>
                <div class="mb-3 col-md-6">
                    <label class="form-label" for="TaxPercentage">Tax % <span style="color:red">*</span></label>
                    <select id="TaxPercentage" name="TaxPercentage" class="select2 form-select" required>
                        <option value=""></option>
                    </select>
                </div>
                <div class="mb-3 col-md-6">
                    <label class="form-label" for="PrimaryUnit">Primary Unit <span style="color:red">*</span></label>
                    <select id="PrimaryUnit" name="PrimaryUnit" class="select2 form-select" required>
                        <option value=""></option>
                    </select>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="Category" class="form-label">Category <span style="color:red">*</span></label>
                    <select id="Category" name="Category" class="select2 form-select" required>
                        <option value=""></option>
                    </select>
                </div>
                <?php if (!empty($_JwtData->GenSettings->EnableStorage)): ?>
                <div class="mb-3 col-md-6">
                    <label for="StorageUID" class="form-label">Storage <?= !empty($_JwtData->GenSettings->MandatoryStorage) ? '<span style="color:red">*</span>' : '' ?></label>
                    <select class="form-select" id="StorageUID" name="StorageUID" <?= !empty($_JwtData->GenSettings->MandatoryStorage) ? 'required' : '' ?>>
                        <option value=""></option>
                        <?php foreach ($_fltStorageData as $_strg): ?>
                            <option value="<?= $_strg->StorageUID ?>"><?= $_strg->Name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <hr>

            <!-- Additional Information -->
            <div class="card-header modal-header-border-bottom p-1 mb-3">
                <h5 class="modal-title mb-0">Additional Information</h5>
            </div>
            <div class="row d-flex">
                <div class="col-md-3 d-flex justify-content-center align-items-center">
                    <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                        <div class="dz-message needsclick text-center">
                            <i class="upload-icon mb-3"></i>
                            <p class="h5 needsclick mb-2">Drag and drop product here</p>
                            <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="HSNCode" class="form-label">HSN / SAC</label>
                            <input type="text" class="form-control" placeholder="Enter HSN / SAC" name="HSNCode" id="HSNCode" maxlength="100" />
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="SKU" class="form-label">SKU</label>
                            <input type="text" class="form-control" placeholder="Enter SKU" name="SKU" id="SKU" maxlength="50" />
                        </div>
                        <div class="mb-3 col-md-12">
                            <label for="PartNumber" class="form-label">Barcode / Part No.</label>
                            <div class="input-group">
                                <input class="form-control w-75" type="text" id="PartNumber" name="PartNumber" placeholder="Enter Part Number" maxlength="25" />
                                <button class="btn btn-outline-secondary w-25" type="button" data-field="PartNumber" id="AutoGeneratePartNoBtn"><i class="icon-base bx bx-bxs-magic-wand me-1"></i> Auto Generate</button>
                            </div>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label for="IsSizeApplicable" class="form-label d-block">Is Size Applicable</label>
                            <label class="switch switch-primary switch-lg">
                                <input type="checkbox" id="IsSizeApplicable" name="IsSizeApplicable" class="switch-input">
                                <span class="switch-toggle-slider">
                                    <span class="switch-on"><i class="icon-base bx bx-check"></i></span>
                                    <span class="switch-off"><i class="icon-base bx bx-x"></i></span>
                                </span>
                            </label>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label for="IsBrandApplicable" class="form-label d-block">Brand Applicable</label>
                            <label class="switch switch-primary switch-lg">
                                <input type="checkbox" id="IsBrandApplicable" name="IsBrandApplicable" class="switch-input">
                                <span class="switch-toggle-slider">
                                    <span class="switch-on"><i class="icon-base bx bx-check"></i></span>
                                    <span class="switch-off"><i class="icon-base bx bx-x"></i></span>
                                </span>
                            </label>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label for="IsSerialTracked" class="form-label d-block">Serial Tracked</label>
                            <label class="switch switch-primary switch-lg">
                                <input type="checkbox" id="IsSerialTracked" name="IsSerialTracked" class="switch-input">
                                <span class="switch-toggle-slider">
                                    <span class="switch-on"><i class="icon-base bx bx-check"></i></span>
                                    <span class="switch-off"><i class="icon-base bx bx-x"></i></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mb-3 mt-2 col-md-12">
                    <label for="Description" class="form-label">Description</label>
                    <div class="form-control p-0">
                        <div id="Description" name="Description" class="border-0 border-bottom ql-toolbar ql-snow"></div>
                    </div>
                </div>
            </div>
            <hr>

            <!-- Other Information -->
            <div class="card-header modal-header-border-bottom p-1 mb-3">
                <h5 class="modal-title mb-0">Other Information</h5>
            </div>
            <div class="row">
                <div class="mb-3 col-md-4 OpeningStockDiv">
                    <label for="OpeningQuantity" class="form-label">Opening Quantity</label>
                    <input type="text" class="form-control" name="OpeningQuantity" id="OpeningQuantity" min="0" placeholder="Enter Opening Quantity"
                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                        oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)"
                        maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>" pattern="[0-9]*" value="0"
                        onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />
                    <div class="form-text text-secondary">* Quantity available in your existing inventory</div>
                </div>
                <div class="mb-3 col-md-4 OpeningStockDiv">
                    <label for="OpeningPurchasePrice" class="form-label">Opening Purchase Price (with Tax)</label>
                    <input type="text" class="form-control" name="OpeningPurchasePrice" id="OpeningPurchasePrice" min="0" placeholder="Enter Opening Purchase Price"
                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                        oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)"
                        maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>" pattern="[0-9]*" value="0"
                        onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />
                </div>
                <div class="mb-3 col-md-4 OpeningStockDiv">
                    <label for="OpeningStockValue" class="form-label">Opening Stock Value (with Tax)</label>
                    <input type="text" class="form-control" name="OpeningStockValue" id="OpeningStockValue" min="0" placeholder="Enter Opening Stock Value"
                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                        oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)"
                        maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>" pattern="[0-9]*" value="0"
                        onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />
                </div>
                <div class="mb-3 col-md-4">
                    <label for="Discount" class="form-label">Discount (<span id="discTextPercentHelp" class="form-text text-danger">Percentage (%)</span><span id="discTextAmountHelp" class="form-text text-danger d-none">Flat Amount (<?= $_JwtData->GenSettings->CurrenySymbol ?>)</span>)</label>
                    <div class="input-group input-group-merge">
                        <input class="form-control" type="text" id="Discount" name="Discount" min="0" placeholder="Enter Discount Percentage"
                            onkeydown="return handleDotOnly(event)"
                            oninput="this.value=this.value.slice(0,this.maxLength); validateDiscountInput(this, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>"
                            pattern="^\d{1,<?= $_JwtData->GenSettings->PriceMaxLength ?>}(\.\d{0,<?= $_JwtData->GenSettings->DecimalPoints ?>})?$"
                            onpaste="handleDiscountPaste(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            ondrop="handleDiscountDrop(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)" value="0" />
                        <select class="form-select w-30" id="DiscountOption" name="DiscountOption">
                        </select>
                    </div>
                </div>
                <div class="mb-3 col-md-4">
                    <label for="LowStockAlert" class="form-label">Low Stock Alert at</label>
                    <input type="text" class="form-control" name="LowStockAlert" id="LowStockAlert" min="0" placeholder="Low Stock Alert"
                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                        oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)"
                        maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>" pattern="[0-9]*"
                        onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="0" />
                </div>
                <div class="mb-3 col-md-4">
                    <label for="NotForSale" class="form-label d-block">Not For Sale</label>
                    <label class="switch switch-primary switch-lg">
                        <input type="checkbox" id="NotForSale" name="NotForSale" class="switch-input">
                        <span class="switch-toggle-slider">
                            <span class="switch-on"><i class="icon-base bx bx-check"></i></span>
                            <span class="switch-off"><i class="icon-base bx bx-x"></i></span>
                        </span>
                    </label>
                </div>
                <div class="mb-3 col-md-4">
                    <label for="IsRentable" class="form-label d-block">Is Rentable
                        <span class="badge bg-label-warning ms-1" style="font-size:.68rem;">Rental</span>
                    </label>
                    <label class="switch switch-warning switch-lg">
                        <input type="checkbox" id="IsRentable" name="IsRentable" class="switch-input">
                        <span class="switch-toggle-slider">
                            <span class="switch-on"><i class="icon-base bx bx-check"></i></span>
                            <span class="switch-off"><i class="icon-base bx bx-x"></i></span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Rental Configuration -->
            <div id="rentalConfigSection" class="d-none">
                <hr>
                <div class="card-header modal-header-border-bottom p-1 mb-3">
                    <h5 class="modal-title mb-0">
                        <i class="bx bx-time-five me-1 text-warning"></i>
                        Rental Configuration
                        <span class="text-muted small">(Rates auto-filled when creating a rental)</span>
                    </h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label fw-semibold">Security Deposit</label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_SecurityDeposit" name="rc_SecurityDeposit" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Hourly Rate</label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_HourlyRate" name="rc_HourlyRate" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Half Day Rate <small class="text-muted">(4 hrs)</small></label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_HalfDayRate" name="rc_HalfDayRate" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Full Day Rate <small class="text-muted">(8 hrs)</small></label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_FullDayRate" name="rc_FullDayRate" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Fixed Package Rate</label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_FixedPackageRate" name="rc_FixedPackageRate" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Extra Hour Rate</label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_ExtraHourRate" name="rc_ExtraHourRate" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Late Return / Hour</label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_LateReturnCharge" name="rc_LateReturnCharge" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Damage Penalty</label><div class="input-group input-group-sm"><span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span><input type="number" class="form-control" id="rc_DamagePenaltyRate" name="rc_DamagePenaltyRate" min="0" step="0.01" placeholder="0.00" value="0"></div></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Min Rental Hours</label><input type="number" class="form-control form-control-sm" id="rc_MinRentalHours" name="rc_MinRentalHours" min="1" step="1" placeholder="1" value="1"></div>
                </div>
            </div>
            <hr>

            <!-- Customer Type Pricing -->
            <div class="card-header modal-header-border-bottom p-1 mb-3">
                <h5 class="modal-title mb-0">Customer Type Pricing <span class="text-muted small">(Optional)</span></h5>
            </div>
            <div class="row mb-3">
                <div class="col-md-7">
                    <label class="form-label">Customer Type</label>
                    <select id="CustomerTypeSelect" class="form-select">
                        <option value="">-- Select Customer Type --</option>
                        <?php foreach ($_CustomerTypeInfo as $_ct): ?>
                            <option value="<?= $_ct->CustomerTypeUID ?>"><?= $_ct->TypeName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Selling Price</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><?= $_JwtData->GenSettings->CurrenySymbol ?></span>
                        <input type="text" class="form-control" name="CustomerTypePrice" id="CustomerTypePrice" min="0" placeholder="Enter Price"
                            onkeydown="return handleDotOnly(event)"
                            oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            maxLength="<?= $_JwtData->GenSettings->PriceMaxLength ?>"
                            pattern="^\d{1,<?= $_JwtData->GenSettings->PriceMaxLength ?>}(\.\d{0,<?= $_JwtData->GenSettings->DecimalPoints ?>})?$"
                            onpaste="handlePricePaste(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)"
                            ondrop="handlePriceDrop(event, <?= $_JwtData->GenSettings->PriceMaxLength ?>, <?= $_JwtData->GenSettings->DecimalPoints ?>)" />
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-success w-100" id="AddCustomerPriceBtn"><i class="bx bx-plus"></i> Add</button>
                </div>
            </div>
            <div class="table-responsive mb-3">
                <table class="table table-bordered table-sm" id="CustomerPricingTable">
                    <thead class="table-light">
                        <tr><th>#</th><th>Customer Type</th><th>Selling Price</th><th>Action</th></tr>
                    </thead>
                    <tbody id="CustomerPricingBody">
                        <tr id="CustomerPricingEmptyRow">
                            <td colspan="4" class="text-center text-muted">No rates added. Default selling price applies to all customers.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="CustomerPricingData" id="CustomerPricingData" value="[]" />

        </div>
    </div>

<?php echo form_close(); ?>

<script>
var _pfDefProdTypeUID  = <?= (int)$_defProdTypeUID ?>;
var _pfDefProductType  = ''; // resolved by DropdownCache after data loads
var _pfDefDiscTypeUID  = <?= (int)$_defDiscTypeUID ?>;
var _pfDefProdTaxUID   = <?= (int)$_defProdTaxUID ?>;
var _pfDefTaxDetailUID = <?= (int)$_defTaxDetailUID ?>;
var _pfEnableStorage   = <?= !empty($_JwtData->GenSettings->EnableStorage) ? 1 : 0 ?>;
</script>
