<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                <?php $FormAttribute = ['id' => 'addQuotationForm', 'name' => 'addQuotationForm', 'autocomplete' => 'off'];
                    echo form_open('quotations/addQuotation', $FormAttribute); ?>

                    <div class="card mb-3">
                        
                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0">Create Quotation</h5>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="input-group w-auto">
                                        <select id="prefix-select" name="prefix-select" class="select2 form-select form-select-sm" required>
                                        <?php foreach($PrefixData as $preData) { ?>
                                            <option value="<?php echo $preData->Name; ?>" selected><?php echo $preData->Name; ?></option>
                                        <?php } ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" id="addTransPrefixBtn" data-toggle="tooltip" title="Add Prefix">➕</button>
                                    </div>
                                    <div class="input-group input-group-sm w-auto">
                                        <span id="basic-default-password2" class="input-group-text cursor-pointer">EST/25-26/</span>
                                        <input type="number" id="quotNumber" name="quotNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="1" required />
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="/quotations" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-3 trans-right-border">
                                    <div class="mb-2">
                                        <label for="quotationType" class="form-label small fw-semibold">Type <span style="color:red">*</span></label>
                                        <select id="quotationType" name="quotationType" class="form-select form-select-sm">
                                            <option value="Regular" selected>Regular</option>
                                            <option value="Without_GST">Without GST</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="dispatchFrom" class="form-label small fw-semibold">Dispatch From</label>
                                        <select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm">
                                            <option value="" disabled selected>Select address</option>
                                            <option value="Warehouse A">Warehouse A</option>
                                            <option value="Warehouse B">Warehouse B</option>
                                            <option value="Factory">Factory</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 border-end pe-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="customerSearch" class="form-label small fw-semibold">Select Customer <span style="color:red">*</span></label>
                                            <button type="button" id="addTransCustomer" class="btn btn-sm btn-outline-primary mt-1"><i class="bx bx-plus-circle me-1"></i> Customer</button>
                                        </div>
                                        <div class="flex-grow-1">
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div id="customerAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2 mb-2">
                                        <div class="flex-fill">
                                            <label for="quotationDate" class="form-label small fw-semibold">Quotation Date <span style="color:red">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                                <input type="text" class="form-control form-control-sm" id="quotationDate" name="quotationDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d'); ?>" required />
                                            </div>
                                        </div>
                                        <div class="flex-fill">
                                            <label for="validityDays" class="form-label small fw-semibold">Validity (Days)</label>
                                            <input type="number" id="validityDays" name="validityDays" class="form-control form-control-sm" min="0" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="7" />
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label for="validityDate" class="form-label small fw-semibold">Validity Date</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="validityDate" name="validityDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d', '', null, '+7'); ?>" required />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="refernceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="refernceDetails" name="refernceDetails" class="form-control form-control-sm" placeholder="Reference, e.g. PO Number, Sales Person, Shipment No..." maxlength="100" />
                                    </div>
                                </div>
                            </div>
                            <hr/>

                            <!-- Product Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="modal-title mb-0"><i class="bx bx-cart-add me-1"></i> Product & Services Details</h5>
                                    <button class="btn btn-sm btn-outline-primary"><i class="bx bx-plus-circle me-1"></i> Product</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="card prod-header-static trans-theme p-2">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div style="width: 20%;">
                                            <select id="prodCategory" name="prodCategory" class="form-select form-select-sm">
                                                <option value="" disabled selected>Select Category</option>
                                                <option value="Warehouse A">Warehouse A</option>
                                                <option value="Warehouse B">Warehouse B</option>
                                                <option value="Factory">Factory</option>
                                            </select>
                                        </div>
                                        <div style="width: 35%;">
                                            <div class="input-group input-group-sm input-group-merge" id="searchProductGroup">
                                                <span class="input-group-text p-2"><i class="icon-base bx bx-search"></i></span>
                                                <select id="searchProductInfo" name="searchProductInfo" class="form-select form-select-sm">
                                                    <option value="" disabled selected>Search product or scan barcode</option>
                                                    <option value="Warehouse A">Warehouse A</option>
                                                    <option value="Warehouse B">Warehouse B</option>
                                                    <option value="Factory">Factory</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div style="width: 10%;">
                                            <input type="text" id="prodQuantity" name="prodQuantity" class="form-control" min="1" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" placeholder="Quantity" />
                                        </div>
                                        <div style="width: 10%;">
                                            <button class="btn btn-success w-100"><i class="bx bx-cart-add"></i> Add to Bill</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-5 p-0">
                                    <div class="table-responsive">
                                        <table class="table trans-table table-bordered table-sm table-hover align-middle">
                                            <thead class="table-light trans-table-light">
                                                <tr class="text-center">
                                                    <th>Product Name</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Price with Tax</th>
                                                    <th>Discount (%)</th>
                                                    <th>Total Amount</th>
                                                    <th>Net Total</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="text-center text-muted">
                                                    <td colspan="8">No items added yet. Start by adding a product.</td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-light trans-table-light">
                                                <tr>
                                                    <td colspan="2" class="fw-semibold">Items: <span id="itemCount">0</span></td>
                                                    <td colspan="2" class="fw-semibold">Qty: <span id="totalQty">0.000</span></td>
                                                    <td colspan="2" class="fw-semibold">Discount: <span id="totalDiscount">0.00%</span></td>
                                                    <td colspan="2" class="fw-semibold">Net Total: ₹<span id="netTotal">0.00</span></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <div class="row mt-1 m-1 p-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="col-md-3">
                                                    <label for="discountField" class="form-label fw-semibold mb-0">Apply Discount (%) to all items in the cart</label>
                                                    <input type="number" id="discountField" class="form-control form-control w-25" min="0" max="100" value="0" />
                                                </div>
                                                <div class="row">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <button id="toggleChargesBtn" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bx bx-plus-circle me-1"></i> Additional Charges
                                                        </button>
                                                    </div>
                                                    <div id="additionalChargesBox" class="mt-2 p-2 border border-secondary rounded d-none">
                                                        <div class="row g-2">
                                                            <div class="col-md-12">
                                                                <table class="table trans-table table-bordered table-sm mb-0">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>Charges</th>
                                                                            <th>Tax</th>
                                                                            <th>in (%)</th>
                                                                            <th>withoutTax in (₹)</th>
                                                                            <th>withTax in (₹)</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>Delivery / Shipping Charges</td>
                                                                            <td>
                                                                                <select class="form-select form-select-sm">
                                                                                    <option value="0">0</option>
                                                                                    <option value="5">5</option>
                                                                                    <option value="18">18</option>
                                                                                </select>
                                                                            </td>
                                                                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                                                                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                                                                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Packaging Charges</td>
                                                                            <td>
                                                                                <select class="form-select form-select-sm">
                                                                                    <option value="0">0</option>
                                                                                    <option value="5">5</option>
                                                                                    <option value="18">18</option>
                                                                                </select>
                                                                            </td>
                                                                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                                                                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                                                                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    
                                <div class="col-md-6">

                                    <div class="mb-2">
                                        <label for="quotationType" class="form-label small fw-semibold">Notes </label>
                                        <textarea class="form-control" rows="2" placeholder="Enter your notes, say thanks, or anything else"></textarea>
                                    </div>

                                    <div class="mb-2">
                                        <label for="quotationType" class="form-label small fw-semibold">Terms & Conditions </label>
                                        <textarea class="form-control" rows="2" placeholder="Enter your business terms & Condition"><?php echo "1. Goods once sold will not be taken back or exchanged\n2. All disputes are subject to Gingee jurisdiction only"; ?></textarea>
                                    </div>

                                </div>

                                <!-- Summary and Bank/Signature -->
                                <div class="col-md-6 trans-theme">
                                    <div class="row g-2">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="d-flex justify-content-end w-70 me-2">
                                                <label class="form-label small fw-semibold">Extra Discount (%)</label>
                                            </div>
                                            <div class="input-group input-group-merge w-25">
                                                <select class="form-select form-select-sm">
                                                    <option value="">%</option>
                                                    <option value="">-</option>
                                                </select>
                                                <input type="number" class="form-control form-control-sm" value="0" min="0" max="100" />
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end mt-2">
                                            <div class="d-flex justify-content-end w-75">
                                                <label class="form-label small fw-semibold">Taxable Amount</label>
                                            </div>
                                            <div class="d-flex justify-content-end w-25 me-1">
                                                <span>₹</span>
                                                <span>20.34</span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end mt-2">
                                            <div class="d-flex justify-content-end w-75">
                                                <label class="form-label small fw-semibold">Total Tax</label>
                                            </div>
                                            <div class="d-flex justify-content-end w-25 me-1">
                                                <span>₹</span>
                                                <span>20.34</span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end mt-2">
                                            <div class="d-flex justify-content-end w-75">
                                                <label class="form-label small fw-semibold">Round Off</label>
                                            </div>
                                            <div class="d-flex justify-content-end w-25 me-1">
                                                <span>₹</span>
                                                <span>0.00</span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end mt-2">
                                            <div class="d-flex justify-content-end w-75">
                                                <label class="form-label small fw-semibold">Total Amount</label>
                                            </div>
                                            <div class="d-flex justify-content-end w-25 me-1">
                                                <span>₹</span>
                                                <span>24.00</span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-end mt-2">
                                            <div class="d-flex justify-content-end w-75">
                                                <label class="form-label small fw-semibold">Total Discount</label>
                                            </div>
                                            <div class="d-flex justify-content-end w-25 me-1">
                                                <span>₹</span>
                                                <span>4.00</span>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>

                                <!-- Upload Files / Images -->
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label class="form-label small fw-semibold">Attach Files <span class="text-muted">(Max: 5)</span></label>
                                        <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                            <div class="dz-message needsclick text-center">
                                                <i class="upload-icon mb-3"></i>
                                                <p class="h5 needsclick mb-2">Drag and drop files / images here</p>
                                                <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php echo form_close(); ?>

                </div>
            </div>
            <!-- Content wrapper -->
             
            <?php $this->load->view('transactions/modals/customer'); ?>
            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/quotations.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>

<script>
const StateInfo = <?php echo json_encode($StateData); ?>;
const CityInfo = <?php echo json_encode($CityData); ?>;
$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#quotationDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#validityDate', false, 'Y-m-d', false, false, false, true, 'd-m-Y', '#quotationDate');
    
    setupTransactionValidity('#quotationDate', '#validityDays', '#validityDate');

    loadSelect2Field('#prodCategory', 'Select Category');
    searchProductInfo();

});
</script>