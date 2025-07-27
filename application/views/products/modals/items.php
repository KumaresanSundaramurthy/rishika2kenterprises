<!-- Product Items Form -->
<div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content h-100 d-flex flex-column">

            <?php $FormAttribute = array('id' => 'AddEditItemForm', 'name' => 'AddEditItemForm', 'class' => '', 'autocomplete' => 'off');
                echo form_open('products/addEditItem', $FormAttribute); ?>

            <div class="modal-header modal-header-center-sticky d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title" id="ItemModalTitle">Add Item</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary AddEditProductBtn">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Discard</button>
                </div>
            </div>

            <input type="hidden" name="ProductUID" id="HProductUID" value="0" />

            <div class="d-none col-lg-12 px-5 mt-3 addEditFormAlert" role="alert"></div>
            
            <div class="modal-body flex-grow-1 overflow-auto">

                <div class="card custom-modal-cardbg mb-3">
                    <h5 class="card-header">Basic Details</h5>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label for="ItemName" class="form-label">Product Name <span style="color:red">*</span></label>
                                <input class="form-control" type="text" id="ItemName" name="ItemName" placeholder="Enter Item Name" maxlength="100" required />
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="ProductType" class="form-label">Product Type <span style="color:red">*</span></label>
                                <select class="select2 form-select" id="ProductType" name="ProductType" required>
                                    <?php if (sizeof($ProdTypeInfo) > 0) {
                                        foreach ($ProdTypeInfo as $ProdType) { ?>

                                            <option value="<?php echo $ProdType['Name']; ?>"><?php echo $ProdType['Name']; ?></option>

                                    <?php }
                                    } ?>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="SellingPrice" class="form-label">Selling Price <span style="color:red" class="me-1">*</span>(<span id="SellingPriceTaxHelp" class="form-text text-danger">Inclusive of Taxes</span><span id="SellingPriceWTaxHelp" class="form-text text-danger d-none">Exclusive of Taxes</span>)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control w-75" name="SellingPrice" id="SellingPrice" min="0" placeholder="Enter Selling Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" required />
                                    <select class="form-select w-25" id="SellingTaxOption" name="SellingTaxOption">
                                        <?php if (sizeof($ProdTaxInfo) > 0) {
                                            foreach ($ProdTaxInfo as $ProdTax) { ?>

                                                <option value="<?php echo $ProdTax['ProductTaxUID']; ?>"><?php echo $ProdTax['Name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="TaxPercentage">Tax % <span style="color:red">*</span></label>
                                <select id="TaxPercentage" name="TaxPercentage" class="select2 form-select" required>
                                    <option label="-- Select Tax Percentage --"></option>
                                    <?php if (sizeof($TaxDetInfo) > 0) {
                                        foreach ($TaxDetInfo as $TaxInfo) { ?>

                                            <option value="<?php echo $TaxInfo['TaxDetailsUID']; ?>"><?php echo $TaxInfo['TaxName']; ?></option>

                                    <?php }
                                    } ?>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="PrimaryUnit">Primary Unit <span style="color:red">*</span></label>
                                <select id="PrimaryUnit" name="PrimaryUnit" class="select2 form-select" required>
                                    <option label="-- Select Primary Unit --"></option>
                                    <?php if (sizeof($PrimaryUnitInfo) > 0) {
                                        foreach ($PrimaryUnitInfo as $PriUnitData) { ?>

                                            <option value="<?php echo $PriUnitData['PrimaryUnitUID']; ?>"><?php echo $PriUnitData['Name'] . ' (' . $PriUnitData['ShortName'] . ')'; ?></option>

                                    <?php }
                                    } ?>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="Category" class="form-label">Category <span style="color:red">*</span></label>
                                <select id="Category" name="Category" class="select2 form-select" required>
                                    <option label="-- Select Category --"></option>
                                    <?php if (sizeof($Categories) > 0) {
                                        foreach ($Categories as $Catg) { ?>
                                            <option value="<?php echo $Catg->CategoryUID; ?>"><?php echo $Catg->Name; ?></option>
                                    <?php }
                                    } ?>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="PurchasePrice" class="form-label">Purchase Price </label>
                                <div class="input-group">

                                    <input type="text" class="form-control w-75" name="PurchasePrice" id="PurchasePrice" min="0" placeholder="Enter Purchase Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" />

                                    <select class="form-select w-25" id="PurchaseTaxOption" name="PurchaseTaxOption">
                                        <?php if (sizeof($ProdTaxInfo) > 0) {
                                            foreach ($ProdTaxInfo as $ProdTax) { ?>
                                                <option value="<?php echo $ProdTax['ProductTaxUID']; ?>"><?php echo $ProdTax['Name']; ?></option>
                                        <?php }
                                        } ?>
                                    </select>

                                </div>
                            </div>
                        <?php if($JwtData->GenSettings->EnableStorage == 1) { ?>
                            <div class="mb-3 col-md-12">
                                <label for="StorageUID" class="form-label">Storage <?php echo $JwtData->GenSettings->MandatoryStorage == 1 ? '<span style="color:red">*</span>': ''; ?></label>
                                <select class="form-select" id="StorageUID" name="StorageUID" <?php echo $JwtData->GenSettings->MandatoryStorage == 1 ? 'required': ''; ?>>
                                    <option label="-- Select Storage --"></option>
                                    <?php if (sizeof($Storage) > 0) {
                                        foreach ($Storage as $strg) { ?>
                                            <option value="<?php echo $strg->StorageUID; ?>"><?php echo $strg->Name; ?></option>
                                    <?php }
                                    } ?>
                                </select>
                            </div>
                        <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="card custom-modal-cardbg mb-3">
                    <h5 class="card-header">Additional Information (Optional)</h5>
                    <div class="card-body">
                        <div class="row d-flex">
                            <!-- <div class="col-md-6 d-flex flex-column justify-content-between"> -->
                            <div class="col-md-6 d-flex flex-column">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label for="HSNCode" class="form-label">HSN/ SAC</label>
                                        <input type="text" class="form-control" placeholder="Enter HSN/ SAC" name="HSNCode" id="HSNCode" maxlength="100" />
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="Standard" class="form-label">Standard</label>
                                        <input type="text" class="form-control" placeholder="Enter Standard" name="Standard" id="Standard" maxlength="100" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label for="BrandUID" class="form-label">Brand </label>
                                        <select class="form-select" id="BrandUID" name="BrandUID">
                                            <option label="-- Select Brand --"></option>
                                            <?php if (sizeof($BrandInfo) > 0) {
                                                foreach ($BrandInfo as $Brand) { ?>

                                                    <option value="<?php echo $Brand->BrandUID; ?>"><?php echo $Brand->Name; ?></option>

                                            <?php }
                                            } ?>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="Model" class="form-label">Model</label>
                                        <input type="text" class="form-control" placeholder="Enter Model" name="Model" id="Model" maxlength="100" />
                                    </div>
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="PartNumber" class="form-label">Barcode / Part No. </label>
                                    <div class="input-group">
                                        <input class="form-control w-75" type="text" id="PartNumber" name="PartNumber" placeholder="Enter Part Number" maxlength="25" />
                                        <button class="btn btn-outline-secondary w-25" type="button" data-field="PartNumber" id="AutoGeneratePartNoBtn"><i class="icon-base bx bx-bxs-magic-wand me-1"></i> Auto Generate</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label for="IsSizeApplicable" class="form-label d-block">Is Size Applicable </label>
                                        <label class="switch switch-primary switch-lg">
                                            <input type="checkbox" id="IsSizeApplicable" name="IsSizeApplicable" class="switch-input">
                                            <span class="switch-toggle-slider">
                                                <span class="switch-on">
                                                    <i class="icon-base bx bx-check"></i>
                                                </span>
                                                <span class="switch-off">
                                                    <i class="icon-base bx bx-x"></i>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="mb-3 col-md-6 d-none" id="SizeDiv">
                                        <label for="SizeUID" class="form-label">Size <span style="color:red">*</span></label>
                                        <select class="form-select" id="PSizeUID" name="SizeUID">
                                            <option label="-- Select Size --"></option>
                                            <?php if (sizeof($BrandInfo) > 0) {
                                                foreach ($BrandInfo as $Brand) { ?>
                                                    <option value="<?php echo $Brand->BrandUID; ?>"><?php echo $Brand->Name; ?></option>
                                            <?php }
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-stretch">
                                <div class="mb-3 col-md-12">
                                    <div class="dropzone needsclick p-3 dz-clickable" id="DropzoneOneBasic">
                                        <div class="dz-message needsclick">
                                            <p class="h4 needsclick pt-4 mb-2">Drag and drop your image here</p>
                                            <p class="h6 text-body-secondary d-block fw-normal mb-3">or</p>
                                            <span class="needsclick btn btn-sm btn-label-primary" id="btnBrowse">Browse image</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="Description" class="form-label">Description </label>
                                <div class="form-control p-0">
                                    <div id="Description" name="Description" class="border-0 border-bottom ql-toolbar ql-snow"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-modal-cardbg mb-3" id="OpeningStockDiv">
                    <h5 class="card-header">Opening Stock (Optional)</h5>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="OpeningQuantity" class="form-label">Opening Quantity</label>
                                <input type="text" class="form-control" name="OpeningQuantity" id="OpeningQuantity" min="0" placeholder="Enter Opening Quantity" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" value="0" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />
                                <div id="OpeningQuantityHelp" class="form-text text-secondary">* Quantity of the product available in your existing inventory</div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="OpeningPurchasePrice" class="form-label">Opening Purchase Price (with Tax)</label>
                                <input type="text" class="form-control" name="OpeningPurchasePrice" id="OpeningPurchasePrice" min="0" placeholder="Enter Opening Purchase Price" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" value="0" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="OpeningStockValue" class="form-label">Opening Stock Value (with Tax)</label>
                                <input type="text" class="form-control" name="OpeningStockValue" id="OpeningStockValue" min="0" placeholder="Enter Opening Stock Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" value="0" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-modal-cardbg mb-3">
                    <h5 class="card-header">Other Information (Optional)</h5>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="Discount" class="form-label">Discount </label>
                                <div class="input-group">

                                    <input class="form-control w-75" type="text" id="Discount" name="Discount" min="0" placeholder="Enter Discount Percentage" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validateDiscountInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handleDiscountPaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handleDiscountDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />

                                    <select class="form-select w-25" id="DiscountOption" name="DiscountOption">
                                        <?php if (sizeof($DiscTypeInfo) > 0) {
                                            foreach ($DiscTypeInfo as $DiscType) { ?>

                                                <option value="<?php echo $DiscType['DiscountTypeUID']; ?>"><?php echo $DiscType['DisplayName']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>

                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="LowStockAlert" class="form-label">Low Stock Alert at </label>
                                <input type="text" class="form-control" name="LowStockAlert" id="LowStockAlert" min="0" placeholder="Low Stock Alert" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="0" />
                            </div>
                            <div class="mb-3 col-md-12">
                                <label for="NotForSale" class="form-label d-block">Not For Sale </label>
                                <label class="switch switch-primary switch-lg">
                                    <input type="checkbox" id="NotForSale" name="NotForSale" class="switch-input">
                                    <span class="switch-toggle-slider">
                                        <span class="switch-on">
                                            <i class="icon-base bx bx-check"></i>
                                        </span>
                                        <span class="switch-off">
                                            <i class="icon-base bx bx-x"></i>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>

            <div class="d-none col-lg-12 px-4 addEditFormAlert" role="alert"></div>

            <div class="modal-footer modal-footer-center-sticky border-top d-flex justify-content-start p-3">
                <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Discard</button>
                <button type="submit" class="btn btn-primary AddEditProductBtn">Save</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>