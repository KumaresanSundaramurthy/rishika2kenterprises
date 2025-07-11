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

                    <?php $FormAttribute = array('id' => 'AddItemForm', 'name' => 'AddItemForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('products/addItem', $FormAttribute); ?>

                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb breadcrumb-style1">
                                <li class="breadcrumb-item">
                                    <a href="/dashboard">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="/products">Products</a>
                                </li>
                                <li class="breadcrumb-item active"><?php echo $EditData->ItemName; ?></li>
                            </ol>
                        </nav>

                        <div class="d-flex gap-2">
                            <div class="mb-3">
                                <a href="javascript: history.back();" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary AddProductBtn">Save</button>
                            </div>
                        </div>

                        <div class="d-none col-lg-12 pt-2 addFormAlert" role="alert"></div>

                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Basic Details</h5>
                        <div class="card-body">
                            <div class="row">

                                <div class="mb-3 col-md-12">
                                    <label for="ItemName" class="form-label">Product Name <span style="color:red">*</span></label>
                                    <input class="form-control" type="text" id="ItemName" name="ItemName" placeholder="Enter Item Name" maxlength="100" required value="<?php echo $EditData->ItemName; ?>" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="ProductType" class="form-label">Product Type <span style="color:red">*</span></label>
                                    <select class="select2 form-select" id="ProductType" name="ProductType" required>
                                        <?php if (sizeof($ProdTypeInfo) > 0) {
                                            foreach ($ProdTypeInfo as $ProdType) { ?>

                                                <option value="<?php echo $ProdType['Name']; ?>" <?php echo $EditData->ProductType == $ProdType['Name'] ? 'selected' : ''; ?>><?php echo $ProdType['Name']; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="SellingPrice" class="form-label">Selling Price <span style="color:red" class="me-1">*</span>(<span id="SellingPriceTaxHelp" class="form-text text-danger">Inclusive of Taxes</span><span id="SellingPriceWTaxHelp" class="form-text text-danger d-none">Exclusive of Taxes</span>)</label>
                                    <div class="input-group">

                                        <input type="text" class="form-control w-75" name="SellingPrice" id="SellingPrice" min="0" placeholder="Enter Selling Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" required value="<?php echo $EditData->SellingPrice ? smartDecimal($EditData->SellingPrice) : 0; ?>" />

                                        <select class="form-select w-25" id="SellingTaxOption" name="SellingTaxOption">
                                            <?php if (sizeof($ProdTaxInfo) > 0) {
                                                foreach ($ProdTaxInfo as $ProdTax) { ?>

                                                    <option value="<?php echo $ProdTax['ProductTaxUID']; ?>" <?php echo $EditData->SellingProductTaxUID == $ProdTax['ProductTaxUID'] ? 'selected' : ''; ?>><?php echo $ProdTax['Name']; ?></option>

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

                                                <option value="<?php echo $TaxInfo['TaxDetailsUID']; ?>" <?php echo $EditData->TaxDetailsUID == $TaxInfo['TaxDetailsUID'] ? 'selected' : ''; ?>><?php echo $TaxInfo['TaxName']; ?></option>

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

                                                <option value="<?php echo $PriUnitData['PrimaryUnitUID']; ?>" <?php echo $EditData->PrimaryUnitUID == $PriUnitData['PrimaryUnitUID'] ? 'selected' : ''; ?>><?php echo $PriUnitData['Name'] . ' (' . $PriUnitData['ShortName'] . ')'; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="Category" class="form-label">Category <span style="color:red">*</span></label>
                                    <select id="Category" name="Category" class="select2 form-select" required>
                                        <option label="-- Select Category --"></option>
                                        <?php if (sizeof($CategoriesInfo) > 0) {
                                            foreach ($CategoriesInfo as $Catg) { ?>

                                                <option value="<?php echo $Catg->CategoryUID; ?>" <?php echo $EditData->CategoryUID == $Catg->CategoryUID ? 'selected' : ''; ?>><?php echo $Catg->Name; ?></option>

                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="CompanyName" class="form-label">Purchase Price </label>
                                    <div class="input-group">

                                        <input type="text" class="form-control w-75" name="PurchasePrice" id="PurchasePrice" min="0" placeholder="Enter Purchase Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="<?php echo $EditData->PurchasePrice ? smartDecimal($EditData->PurchasePrice) : ''; ?>" />

                                        <select class="form-select w-25" id="PurchaseTaxOption" name="PurchaseTaxOption">
                                            <?php if (sizeof($ProdTaxInfo) > 0) {
                                                foreach ($ProdTaxInfo as $ProdTax) { ?>

                                                    <option value="<?php echo $ProdTax['ProductTaxUID']; ?>" <?php echo $EditData->PurchasePriceProductTaxUID == $ProdTax['ProductTaxUID'] ? 'selected' : ''; ?>><?php echo $ProdTax['Name']; ?></option>

                                            <?php }
                                            } ?>
                                        </select>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Additional Information (Optional)</h5>
                        <div class="card-body">
                            <div class="row d-flex">

                                <div class="col-md-6 d-flex flex-column">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="HSNCode" class="form-label">HSN/ SAC</label>
                                            <input type="text" class="form-control" placeholder="HSN/ SAC" name="HSNCode" id="HSNCode" maxlength="100" value="<?php echo $EditData->HSNSACCode ? $EditData->HSNSACCode : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="Standard" class="form-label">Standard</label>
                                            <input type="text" class="form-control" placeholder="Enter Standard" name="Standard" id="Standard" maxlength="100" value="<?php echo $EditData->Standard ? $EditData->Standard : ''; ?>" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="BrandUID" class="form-label">Brand </label>
                                            <select class="form-select" id="BrandUID" name="BrandUID">
                                                <option label="-- Select Brand --"></option>
                                                <?php if (sizeof($BrandInfo) > 0) {
                                                    foreach ($BrandInfo as $Brand) { ?>

                                                        <option value="<?php echo $Brand->BrandUID; ?>" <?php echo $EditData->BrandUID == $Brand->BrandUID ? 'selected' : ''; ?>><?php echo $Brand->Name; ?></option>

                                                <?php }
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="Model" class="form-label">Model</label>
                                            <input type="text" class="form-control" placeholder="Enter Model" name="Model" id="Model" maxlength="100" value="<?php echo $EditData->Model ? $EditData->Model : ''; ?>" />
                                        </div>
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <label for="PartNumber" class="form-label">Barcode / Part No. </label>
                                        <div class="input-group">
                                            <input class="form-control w-75" type="text" id="PartNumber" name="PartNumber" placeholder="Enter Part Number" maxlength="25" value="" />
                                            <button class="btn btn-outline-secondary w-25" type="button" data-field="PartNumber" id="AutoGeneratePartNoBtn"><i class="icon-base bx bx-bxs-magic-wand me-1"></i> Auto Generate</button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="IsSizeApplicable" class="form-label d-block">Is Size Applicable </label>
                                            <label class="switch switch-primary switch-lg">
                                                <input type="checkbox" id="IsSizeApplicable" name="IsSizeApplicable" class="switch-input" <?php echo $EditData->IsSizeApplicable == 1 ? 'checked' : ''; ?>>
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
                                        <div class="mb-3 col-md-6 <?php echo $EditData->IsSizeApplicable == 1 ? '' : 'd-none'; ?>" id="SizeDiv">
                                            <label for="SizeUID" class="form-label">Size <span style="color:red">*</span></label>
                                            <select class="form-select" id="SizeUID" name="SizeUID">
                                                <option label="-- Select Size --"></option>
                                                <?php if (sizeof($BrandInfo) > 0) {
                                                    foreach ($BrandInfo as $Brand) { ?>

                                                        <option value="<?php echo $Brand->BrandUID; ?>" <?php echo $EditData->IsSizeApplicable == 1 && ($EditData->SizeUID == $Brand->BrandUID) ? 'selected' : ''; ?>><?php echo $Brand->Name; ?></option>

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
                                        <div id="Description" name="Description" class="border-0 border-bottom ql-toolbar ql-snow"><?php echo $EditData->Description; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3" id="OpeningStockDiv">
                        <h5 class="card-header">Opening Stock (Optional)</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="OpeningQuantity" class="form-label">Opening Quantity</label>
                                    <input type="text" class="form-control" name="OpeningQuantity" id="OpeningQuantity" min="0" placeholder="Enter Opening Quantity" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $EditData->OpeningQuantity ? $EditData->OpeningQuantity : 0; ?>" />
                                    <div id="OpeningQuantityHelp" class="form-text text-secondary">* Quantity of the product available in your existing inventory</div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="OpeningPurchasePrice" class="form-label">Opening Purchase Price (with Tax)</label>
                                    <input type="text" class="form-control" name="OpeningPurchasePrice" id="OpeningPurchasePrice" min="0" placeholder="Enter Opening Purchase Price" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $EditData->OpeningPurchasePrice ? $EditData->OpeningPurchasePrice : 0; ?>" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="OpeningStockValue" class="form-label">Opening Stock Value (with Tax)</label>
                                    <input type="text" class="form-control" name="OpeningStockValue" id="OpeningStockValue" min="0" placeholder="Enter Opening Stock Value" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $EditData->OpeningStockValue ? $EditData->OpeningStockValue : 0; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <h5 class="card-header">Other Information (Optional)</h5>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="Discount" class="form-label">Discount </label>
                                    <div class="input-group">

                                        <input class="form-control w-75" type="text" id="Discount" name="Discount" min="0" placeholder="Enter Discount Percentage" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validateDiscountInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handleDiscountPaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handleDiscountDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="<?php echo $EditData->Discount ? smartDecimal($EditData->Discount) : ''; ?>" />

                                        <select class="form-select w-25" id="DiscountOption" name="DiscountOption">
                                            <?php if (sizeof($DiscTypeInfo) > 0) {
                                                foreach ($DiscTypeInfo as $DiscType) { ?>

                                                    <option value="<?php echo $DiscType['DiscountTypeUID']; ?>" <?php echo $EditData->DiscountTypeUID == $DiscType['DiscountTypeUID'] ? 'selected' : ''; ?>><?php echo $DiscType['DisplayName']; ?></option>

                                            <?php }
                                            } ?>
                                        </select>

                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="LowStockAlert" class="form-label">Low Stock Alert at </label>
                                    <input type="text" class="form-control" name="LowStockAlert" id="LowStockAlert" min="0" placeholder="Low Stock Alert" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="[0-9]*" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" value="<?php echo $EditData->LowStockAlertAt ? $EditData->LowStockAlertAt : 0; ?>" />
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label for="NotForSale" class="form-label d-block">Not For Sale </label>
                                    <label class="switch switch-primary">
                                        <input type="checkbox" id="NotForSale" name="NotForSale" class="switch-input" <?php echo $EditData->NotForSale == 'Yes' ? 'checked' : ''; ?>>
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

                    <div class="card mb-3">
                        <div class="card-body p-0">

                            <div class="d-none col-lg-12 px-4 pt-4 addFormAlert" role="alert"></div>

                            <div class="m-3">
                                <button type="submit" class="btn btn-primary AddProductBtn me-2">Save</button>
                                <a href="javascript: history.back();" class="btn btn-outline-secondary">Cancel</a>
                            </div>

                        </div>
                    </div>

                    <?php echo form_close(); ?>

                </div>

            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/products.js"></script>

<script>
    var defaultImg = '<?php echo '/website/images/logo/avathar_user.png'; ?>';
    var imageChange = 0;
    $(function() {
        'use strict'

        loadSelect2Field('#TaxPercentage', '-- Select Tax Percentage --');
        loadSelect2Field('#PrimaryUnit', '-- Select Primary Unit --');
        loadSelect2Field('#Category', '-- Select Category --');

        QuillEditor('.ql-toolbar', 'Enter product description...');

        $('#AddItemForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData($('#AddItemForm')[0]);
            if (myOneDropzone.files.length > 0) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }

            const Description = quill.getText().trim(); // quill.root.innerHTML;
            if ($.trim(Description) != '') {
                formData.append('Description', $('#Description .ql-editor').html());
            }

            AjaxLoading = 0;
            $('.AddProductBtn').attr('disabled', 'disabled');

            addProductData(formData);

        });

    });
</script>