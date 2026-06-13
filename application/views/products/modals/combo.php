<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Combo / Composite Product Form -->
<div class="modal fade" id="comboItemModal" tabindex="-1" aria-labelledby="comboItemModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $ComboFormAttr = array('id' => 'AddEditComboForm', 'name' => 'AddEditComboForm', 'class' => '', 'autocomplete' => 'off');
                echo form_open('products/addComboItem', $ComboFormAttr); ?>

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-warning bg-opacity-10">
                        <i class="bx bx-git-merge text-warning modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="ComboModalTitle">Add Combo Item</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-primary AddEditComboBtn"><i class="bx bx-check me-1"></i>Save</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x me-1"></i>Close</button>
                </div>
            </div>

            <input type="hidden" name="ComboUID" id="HComboUID" value="0" />
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

            <div class="d-none col-lg-12 px-5 mt-3 comboFormAlert" role="alert"></div>

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
                <div class="card-body p-2 mb-3">

                    <!-- Basic Details -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3">
                        <h5 class="modal-title mb-0">Basic Details</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="ComboName" class="form-label">Combo Name <span style="color:red">*</span></label>
                            <input class="form-control" type="text" id="ComboName" name="ComboName" placeholder="Enter Combo Name" maxlength="150" required />
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ComboSellingPrice" class="form-label">Selling Price <span style="color:red">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                <input type="text" class="form-control" name="ComboSellingPrice" id="ComboSellingPrice" min="0" placeholder="Enter Selling Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" required />
                            </div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ComboMRP" class="form-label">MRP (Maximum Retail Price)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                <input type="text" class="form-control" name="ComboMRP" id="ComboMRP" min="0" placeholder="Enter MRP" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                            </div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ComboTaxPercentage" class="form-label">Tax %</label>
                            <select id="ComboTaxPercentage" name="ComboTaxPercentage" class="select2 form-select">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ComboPrimaryUnit" class="form-label">Primary Unit</label>
                            <select id="ComboPrimaryUnit" name="ComboPrimaryUnit" class="select2 form-select">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="ComboDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="ComboDescription" name="ComboDescription" rows="2" placeholder="Enter combo description (optional)" maxlength="500"></textarea>
                        </div>
                    </div>

                    <!-- Component Items -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3 mt-2">
                        <h5 class="modal-title mb-0">Component Items <span class="text-danger small">(Minimum 2 items required)</span></h5>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Search Item</label>
                            <select id="ComboItemSearch" class="form-select" style="width:100%">
                                <option value="">-- Search & Select Item --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="text" class="form-control" id="ComboItemQty" placeholder="Qty"
                                onkeydown="return handleDotOnly(event)"
                                oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?= (int)($JwtData->GenSettings->PriceMaxLength ?? 10) ?>, <?= (int)($JwtData->GenSettings->DecimalPoints ?? 2) ?>)"
                                maxlength="<?= (int)($JwtData->GenSettings->PriceMaxLength ?? 10) ?>"
                                onpaste="handlePricePaste(event, <?= (int)($JwtData->GenSettings->PriceMaxLength ?? 10) ?>, <?= (int)($JwtData->GenSettings->DecimalPoints ?? 2) ?>)"
                                ondrop="handlePriceDrop(event, <?= (int)($JwtData->GenSettings->PriceMaxLength ?? 10) ?>, <?= (int)($JwtData->GenSettings->DecimalPoints ?? 2) ?>)"
                                value="1" />
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-success w-100" id="AddComboComponentBtn">
                                <i class="bx bx-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm" id="ComboComponentsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="ComboComponentsBody">
                                <tr id="ComboComponentEmptyRow">
                                    <td colspan="4" class="text-center text-muted">No items added yet. Add at least 2 items.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="ComboComponentsData" id="ComboComponentsData" value="[]" />

                </div>
            </div>

        <?php echo form_close(); ?>
        </div>
    </div>
</div>
<script>
var _comboQtyMaxLen = <?= (int)($JwtData->GenSettings->PriceMaxLength ?? 10) ?>;
var _comboQtyDecimals = <?= (int)($JwtData->GenSettings->DecimalPoints ?? 2) ?>;
</script>
