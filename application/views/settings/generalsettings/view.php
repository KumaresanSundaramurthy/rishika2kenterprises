<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <!-- Content wrapper -->
            <div class="content-wrapper">

                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card">

                        <!-- ============================================================
                             TOP-LEVEL HORIZONTAL TABS
                        ============================================================ -->
                        <div class="card-header border-bottom px-0 pt-0 pb-0">
                            <ul class="nav nav-tabs gs-top-tabs px-3 pt-3" id="gsTopTab" role="tablist">

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link gs-top-link active" id="toptab-general-settings-tab"
                                        data-bs-toggle="tab" data-bs-target="#toptab-general-settings"
                                        role="tab" aria-controls="toptab-general-settings" aria-selected="true"
                                        href="javascript:void(0);">
                                        <i class="bx bx-cog me-1"></i> General
                                    </a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link gs-top-link" id="toptab-transaction-settings-tab"
                                        data-bs-toggle="tab" data-bs-target="#toptab-transaction-settings"
                                        role="tab" aria-controls="toptab-transaction-settings" aria-selected="false"
                                        href="javascript:void(0);">
                                        <i class="bx bx-transfer me-1"></i> Transaction
                                    </a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link gs-top-link" id="toptab-thermal-config-tab"
                                        data-bs-toggle="tab" data-bs-target="#toptab-thermal-config"
                                        role="tab" aria-controls="toptab-thermal-config" aria-selected="false"
                                        href="javascript:void(0);">
                                        <i class="bx bx-printer me-1"></i> Thermal Print Config
                                    </a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link gs-top-link" id="toptab-banks-tab"
                                        data-bs-toggle="tab" data-bs-target="#toptab-banks"
                                        role="tab" aria-controls="toptab-banks" aria-selected="false"
                                        href="javascript:void(0);">
                                        <i class="bx bx-buildings me-1"></i> Banks
                                    </a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link gs-top-link" id="toptab-msgtemplates-tab"
                                        data-bs-toggle="tab" data-bs-target="#toptab-msgtemplates"
                                        role="tab" aria-controls="toptab-msgtemplates" aria-selected="false"
                                        href="javascript:void(0);">
                                        <i class="bx bx-message-square-edit me-1"></i> Message Templates
                                    </a>
                                </li>

                            </ul>
                        </div>
                        <!-- / Top Tabs Header -->

                        <!-- TOP TAB CONTENT -->
                        <div class="tab-content p-0" id="gsTopTabContent">

                            <!-- ============================================================
                                 TOP TAB 1: General Settings
                            ============================================================ -->
                            <div class="tab-pane fade show active" id="toptab-general-settings" role="tabpanel" aria-labelledby="toptab-general-settings-tab">

                                <div class="row g-0">

                                    <!-- Left Side: Vertical Sub-Tabs -->
                                    <div class="col-md-3 border-end">
                                        <div class="nav flex-column nav-pills gs-nav-pills py-3" id="genSettingsTab" role="tablist" aria-orientation="vertical">

                                            <a class="nav-link gs-tab-link active px-4 py-3" id="tab-general-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-general"
                                                role="tab" aria-controls="tab-general" aria-selected="true"
                                                href="javascript:void(0);">
                                                <i class="bx bx-slider-alt me-2"></i>General
                                            </a>

                                            <a class="nav-link gs-tab-link px-4 py-3" id="tab-invoice-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-invoice"
                                                role="tab" aria-controls="tab-invoice" aria-selected="false"
                                                href="javascript:void(0);">
                                                <i class="bx bx-receipt me-2"></i>Invoice Settings
                                            </a>

                                            <a class="nav-link gs-tab-link px-4 py-3" id="tab-inventory-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-inventory"
                                                role="tab" aria-controls="tab-inventory" aria-selected="false"
                                                href="javascript:void(0);">
                                                <i class="bx bx-package me-2"></i>Inventory
                                            </a>

                                        </div>
                                    </div>
                                    <!-- / Left Side -->

                                    <!-- Right Side: Sub-Tab Content -->
                                    <div class="col-md-9">
                                        <div class="tab-content p-4" id="genSettingsTabContent">

                                            <!-- Sub-Tab 1: General -->
                                            <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-tab">

                                                <h6 class="fw-semibold mb-1">General Preferences</h6>
                                                <p class="text-muted small mb-4">Configure your application's basic display and regional preferences.</p>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Date Format</label>
                                                        <select class="form-select" id="DateFormat" name="DateFormat">
                                                            <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                                            <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                                            <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Currency Symbol</label>
                                                        <input type="text" class="form-control" id="CurrencySymbol" name="CurrencySymbol" placeholder="e.g. ₹" value="₹" maxlength="5" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Decimal Places</label>
                                                        <select class="form-select" id="DecimalPlaces" name="DecimalPlaces">
                                                            <option value="0">0</option>
                                                            <option value="2" selected>2</option>
                                                            <option value="3">3</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Language</label>
                                                        <select class="form-select" id="Language" name="Language">
                                                            <option value="en" selected>English</option>
                                                            <option value="ta">Tamil</option>
                                                            <option value="hi">Hindi</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="SerialNoDisplay" name="SerialNoDisplay" checked>
                                                            <label class="form-check-label" for="SerialNoDisplay">Show Serial Number in Lists</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="DarkModeDefault" name="DarkModeDefault">
                                                            <label class="form-check-label" for="DarkModeDefault">Default to Dark Mode</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary gs-save-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-label-secondary">Reset</button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab 1 -->

                                            <!-- Sub-Tab 2: Invoice Settings -->
                                            <div class="tab-pane fade" id="tab-invoice" role="tabpanel" aria-labelledby="tab-invoice-tab">

                                                <h6 class="fw-semibold mb-1">Invoice Settings</h6>
                                                <p class="badge bg-label-secondary d-inline-flex align-items-center gap-1 mb-4" style="font-size:.78rem;font-weight:500;">
                                                    <i class="bx bx-info-circle"></i>
                                                    Manage invoice numbering, defaults, and display preferences.
                                                </p>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Invoice Prefix</label>
                                                        <input type="text" class="form-control" id="InvoicePrefix" name="InvoicePrefix" placeholder="e.g. INV-" value="INV-" maxlength="10" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Starting Number</label>
                                                        <input type="number" class="form-control" id="InvoiceStartNo" name="InvoiceStartNo" placeholder="e.g. 1001" value="1001" min="1" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Due Days</label>
                                                        <input type="number" class="form-control" id="DefaultDueDays" name="DefaultDueDays" placeholder="e.g. 30" value="30" min="0" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Tax Type</label>
                                                        <select class="form-select" id="DefaultTaxType" name="DefaultTaxType">
                                                            <option value="GST" selected>GST (CGST + SGST)</option>
                                                            <option value="IGST">IGST</option>
                                                            <option value="None">None</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Default Terms &amp; Conditions</label>
                                                        <textarea class="form-control" id="DefaultTerms" name="DefaultTerms" rows="3" placeholder="Enter default terms and conditions for invoices...">Goods once sold will not be taken back or exchanged.</textarea>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="ShowSignature" name="ShowSignature" checked>
                                                            <label class="form-check-label" for="ShowSignature">Show Signature on Invoice</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="ShowBankDetails" name="ShowBankDetails" checked>
                                                            <label class="form-check-label" for="ShowBankDetails">Show Bank Details on Invoice</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary gs-save-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-label-secondary">Reset</button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab 2 -->

                                            <!-- Sub-Tab 3: Inventory -->
                                            <div class="tab-pane fade" id="tab-inventory" role="tabpanel" aria-labelledby="tab-inventory-tab">

                                                <h6 class="fw-semibold mb-1">Inventory Settings</h6>
                                                <p class="text-muted small mb-4">Control stock tracking, storage, and low-stock alert preferences.</p>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Low Stock Alert Quantity</label>
                                                        <input type="number" class="form-control" id="LowStockDefault" name="LowStockDefault" placeholder="e.g. 5" value="5" min="0" />
                                                        <div class="form-text">Alert triggers when stock falls below this quantity.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Unit of Measurement</label>
                                                        <select class="form-select" id="DefaultUOM" name="DefaultUOM">
                                                            <option value="Nos" selected>Nos (Numbers)</option>
                                                            <option value="Kg">Kg</option>
                                                            <option value="Ltr">Ltr</option>
                                                            <option value="Mtr">Mtr</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="EnableStorage" name="EnableStorage" checked>
                                                            <label class="form-check-label" for="EnableStorage">Enable Storage / Warehouse Management</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="EnableLowStockAlert" name="EnableLowStockAlert" checked>
                                                            <label class="form-check-label" for="EnableLowStockAlert">Enable Low Stock Alerts</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="TrackNegativeStock" name="TrackNegativeStock">
                                                            <label class="form-check-label" for="TrackNegativeStock">Allow Negative Stock</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="EnableOpeningStock" name="EnableOpeningStock" checked>
                                                            <label class="form-check-label" for="EnableOpeningStock">Enable Opening Stock Entry for New Items</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary gs-save-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-label-secondary">Reset</button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab 3 -->

                                        </div>
                                    </div>
                                    <!-- / Right Side -->

                                </div>

                            </div>
                            <!-- / TOP TAB 1 -->

                            <!-- ============================================================
                                 TOP TAB 2: Transaction Settings
                            ============================================================ -->
                            <div class="tab-pane fade" id="toptab-transaction-settings" role="tabpanel" aria-labelledby="toptab-transaction-settings-tab">

                                <div class="row g-0">

                                    <!-- Left Side: Vertical Sub-Tabs -->
                                    <div class="col-md-3 border-end">
                                        <div class="nav flex-column nav-pills gs-nav-pills py-3" id="txnSettingsTab" role="tablist" aria-orientation="vertical">

                                            <a class="nav-link gs-tab-link active px-4 py-3" id="tab-quotation-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-quotation"
                                                role="tab" aria-controls="tab-quotation" aria-selected="true"
                                                href="javascript:void(0);">
                                                <i class="bx bx-file me-2"></i>Quotation
                                            </a>

                                            <a class="nav-link gs-tab-link px-4 py-3" id="tab-salesorder-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-salesorder"
                                                role="tab" aria-controls="tab-salesorder" aria-selected="false"
                                                href="javascript:void(0);">
                                                <i class="bx bx-cart me-2"></i>Sales Order
                                            </a>

                                            <a class="nav-link gs-tab-link px-4 py-3" id="tab-purchase-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-purchase"
                                                role="tab" aria-controls="tab-purchase" aria-selected="false"
                                                href="javascript:void(0);">
                                                <i class="bx bx-purchase-tag me-2"></i>Purchase
                                            </a>

                                        </div>
                                    </div>
                                    <!-- / Left Side -->

                                    <!-- Right Side: Sub-Tab Content -->
                                    <div class="col-md-9">
                                        <div class="tab-content p-4" id="txnSettingsTabContent">

                                            <!-- Sub-Tab 1: Quotation -->
                                            <div class="tab-pane fade show active" id="tab-quotation" role="tabpanel" aria-labelledby="tab-quotation-tab">

                                                <h6 class="fw-semibold mb-1">Quotation Settings</h6>
                                                <p class="text-muted small mb-4">Configure prefix, validity, and defaults for quotations.</p>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Quotation Prefix</label>
                                                        <input type="text" class="form-control" id="QuotationPrefix" name="QuotationPrefix" placeholder="e.g. QUO-" value="QUO-" maxlength="10" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Starting Number</label>
                                                        <input type="number" class="form-control" id="QuotationStartNo" name="QuotationStartNo" placeholder="e.g. 1001" value="1001" min="1" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Validity (Days)</label>
                                                        <input type="number" class="form-control" id="QuotationValidity" name="QuotationValidity" placeholder="e.g. 15" value="15" min="1" />
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="QuotationAutoConvert" name="QuotationAutoConvert">
                                                            <label class="form-check-label" for="QuotationAutoConvert">Allow Auto-Convert to Invoice</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary gs-save-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-label-secondary">Reset</button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab 1 -->

                                            <!-- Sub-Tab 2: Sales Order -->
                                            <div class="tab-pane fade" id="tab-salesorder" role="tabpanel" aria-labelledby="tab-salesorder-tab">

                                                <h6 class="fw-semibold mb-1">Sales Order Settings</h6>
                                                <p class="text-muted small mb-4">Configure prefix and defaults for sales orders.</p>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Sales Order Prefix</label>
                                                        <input type="text" class="form-control" id="SOPrefix" name="SOPrefix" placeholder="e.g. SO-" value="SO-" maxlength="10" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Starting Number</label>
                                                        <input type="number" class="form-control" id="SOStartNo" name="SOStartNo" placeholder="e.g. 1001" value="1001" min="1" />
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="SOAutoConvert" name="SOAutoConvert">
                                                            <label class="form-check-label" for="SOAutoConvert">Allow Auto-Convert to Invoice</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary gs-save-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-label-secondary">Reset</button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab 2 -->

                                            <!-- Sub-Tab 3: Purchase -->
                                            <div class="tab-pane fade" id="tab-purchase" role="tabpanel" aria-labelledby="tab-purchase-tab">

                                                <h6 class="fw-semibold mb-1">Purchase Settings</h6>
                                                <p class="text-muted small mb-4">Configure prefix and defaults for purchase bills.</p>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Purchase Bill Prefix</label>
                                                        <input type="text" class="form-control" id="PurchasePrefix" name="PurchasePrefix" placeholder="e.g. PUR-" value="PUR-" maxlength="10" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Starting Number</label>
                                                        <input type="number" class="form-control" id="PurchaseStartNo" name="PurchaseStartNo" placeholder="e.g. 1001" value="1001" min="1" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Payment Due Days</label>
                                                        <input type="number" class="form-control" id="PurchaseDueDays" name="PurchaseDueDays" placeholder="e.g. 30" value="30" min="0" />
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="PurchaseUpdateStock" name="PurchaseUpdateStock" checked>
                                                            <label class="form-check-label" for="PurchaseUpdateStock">Auto-Update Stock on Purchase</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary gs-save-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-label-secondary">Reset</button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab 3 -->

                                        </div>
                                    </div>
                                    <!-- / Right Side -->

                                </div>

                            </div>
                            <!-- / TOP TAB 2 -->

                            <!-- ============================================================
                                 TOP TAB 3: Thermal Print Config
                            ============================================================ -->
                            <div class="tab-pane fade" id="toptab-thermal-config" role="tabpanel" aria-labelledby="toptab-thermal-config-tab">

                                <!-- Action bar -->
                                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom">
                                    <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;">
                                        <i class="bx bx-info-circle"></i>
                                        One configuration per transaction type &nbsp;&bull;&nbsp; Max <?php echo $ThermalTypeCount; ?> records
                                    </span>
                                    <button class="btn btn-primary btn-sm px-3" id="btnAddThermalConfig">
                                        <i class="bx bx-plus me-1"></i>Add Config
                                    </button>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive text-nowrap tablecard">
                                    <table class="table trans-table MainviewTable" id="ThermalConfigTable">
                                        <thead class="r2k-thead">
                                            <tr>
                                                <th class="text-center" style="width:50px;">S.No</th>
                                                <th>Transaction Type</th>
                                                <th>Paper Width</th>
                                                <th style="white-space:normal;min-width:240px;">Receipt Elements</th>
                                                <th>Font Sizes</th>
                                                <th>Last Updated</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ThermalConfigBody" class="r2k-tbody table-border-bottom-0">
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                            <!-- / TOP TAB 3 -->

                            <!-- ============================================================
                                 TOP TAB 4: Banks
                            ============================================================ -->
                            <div class="tab-pane fade" id="toptab-banks" role="tabpanel" aria-labelledby="toptab-banks-tab">

                                <!-- Action bar -->
                                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom gap-2 flex-wrap">
                                    <span class="text-muted small">Manage your organisation's bank accounts used in transactions and payments.</span>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm px-3" id="btnTransferFunds">
                                            <i class="bx bx-transfer me-1"></i>Transfer Funds
                                        </button>
                                        <button class="btn btn-primary btn-sm px-3" id="btnAddBank">
                                            <i class="bx bx-plus me-1"></i>Add Bank Account
                                        </button>
                                    </div>
                                </div>

                                <!-- Cards container -->
                                <div id="bankCardsContainer">
                                    <div class="text-center py-5 text-muted">
                                        <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                                    </div>
                                </div>

                            </div>
                            <!-- / TOP TAB 4: Banks -->

                            <!-- ============================================================
                                 TOP TAB 5: Message Templates
                            ============================================================ -->
                            <div class="tab-pane fade" id="toptab-msgtemplates" role="tabpanel" aria-labelledby="toptab-msgtemplates-tab">

                                <!-- Action bar -->
                                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom gap-2 flex-wrap">
                                    <span class="text-muted small">Create Email, WhatsApp &amp; SMS templates per transaction type. Use <code>{{tokens}}</code> to auto-fill real data when sending.</span>
                                    <button class="btn btn-primary btn-sm px-3" id="btnAddMsgTemplate">
                                        <i class="bx bx-plus me-1"></i>Add Template
                                    </button>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive">
                                    <table class="table trans-table MainviewTable mb-0" id="MsgTemplateTable">
                                        <thead class="r2k-thead">
                                            <tr>
                                                <th style="width:130px;">Channel</th>
                                                <th>Template Preview</th>
                                                <th style="width:130px;">Last Updated</th>
                                                <th class="text-center" style="width:90px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="MsgTemplateBody" class="r2k-tbody table-border-bottom-0">
                                            <tr><td colspan="4" class="text-center py-4 text-muted">
                                                <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                                            </td></tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                            <!-- / TOP TAB 5: Message Templates -->

                        </div>
                        <!-- / Top Tab Content -->

                    </div>

                </div>
            </div>
            <!-- / Content wrapper -->

            <!-- ============================================================
                 Thermal Print Config Modal
            ============================================================ -->
            <div class="modal fade" id="thermalConfigModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered" style="max-height:95vh;">
                    <div class="modal-content">

                        <div class="modal-header py-3">
                            <div>
                                <h5 class="modal-title mb-0" id="thermalModalTitle"><i class="bx bx-printer me-2 text-primary"></i>Thermal Print Settings</h5>
                                <small class="text-muted" id="thermalModalSubtitle">Add a new configuration</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger ms-auto" data-bs-dismiss="modal">
                                <i class="bx bx-x me-1"></i>Close
                            </button>
                        </div>

                        <div class="modal-body p-0 d-flex" style="min-height:540px;max-height:78vh;overflow:hidden;">

                            <input type="hidden" id="HThermalConfigUID" value="0" />

                            <!-- ═══ LEFT: Settings Form ═══ -->
                            <div class="thermal-form-panel" id="thermalFormPanel">

                                <div class="d-none thermalFormAlert alert alert-danger mx-3 mt-3 mb-0 p-2" role="alert">
                                    <span class="alert-message"></span>
                                </div>

                                <!-- Transaction Type (Add mode only) -->
                                <div id="thermalTransTypeRow" class="px-3 py-3 border-bottom bg-body-tertiary">
                                    <label class="form-label fw-semibold mb-1 small">Transaction Type <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="ThermalTransType">
                                        <option value="">-- Select Transaction Type --</option>
                                    </select>
                                </div>

                                <!-- ── Receipt Elements (2-column grid) ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Receipt Elements</div>
                                <div class="row g-0 border-top border-bottom">

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-file-blank me-1 text-secondary"></i>Terms</div><div class="thermal-setting-desc">Print terms &amp; conditions on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTerms"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-buildings me-1 text-secondary"></i>Company Details</div><div class="thermal-setting-desc">Include company's details on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowCompanyDetails" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom" id="thermalItemDescCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-list-ul me-1 text-secondary"></i>Item Description</div><div class="thermal-setting-desc">Print detailed product descriptions.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowItemDescription"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom" id="thermalTaxableAmtCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-calculator me-1 text-secondary"></i>Taxable Amount</div><div class="thermal-setting-desc">Display taxable amount above tax line.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTaxableAmount"></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom" id="thermalHSNCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-barcode me-1 text-secondary"></i>Show Item HSN/SAC</div><div class="thermal-setting-desc">Show the HSN/SAC code on the receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowHSN" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom" id="thermalTaxBreakdownCell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-pie-chart-alt-2 me-1 text-secondary"></i>Tax Breakdown</div><div class="thermal-setting-desc">Show CGST / SGST / IGST split.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTaxBreakdown" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-id-card me-1 text-secondary"></i>Show GSTIN</div><div class="thermal-setting-desc">Display company GSTIN on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowGSTIN" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-phone me-1 text-secondary"></i>Show Mobile</div><div class="thermal-setting-desc">Display company mobile on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowMobile" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-money me-1 text-secondary"></i>Show Cash Received</div><div class="thermal-setting-desc">Display amount received from customer.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowCashReceived" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-image me-1 text-secondary"></i>Company Logo</div><div class="thermal-setting-desc">(B&amp;W recommended) Logo on receipt.</div></div>
                                            <div class="d-flex align-items-center gap-2 mt-1 flex-shrink-0">
                                                <div class="form-check form-switch mb-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowLogo"></div>
                                                <label class="thermal-upload-btn" for="ThermalLogoUpload" title="Upload logo"><i class="bx bx-upload"></i><input type="file" id="ThermalLogoUpload" accept="image/*" class="d-none"></label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- ── Footer ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Footer</div>
                                <div class="row g-0 border-top border-bottom">
                                    <div class="col-12 p-3">
                                        <label class="form-label small fw-semibold mb-1">Footer Message</label>
                                        <p class="text-muted" style="font-size:0.73rem;margin-bottom:6px;">Printed at the bottom of the receipt.</p>
                                        <textarea class="form-control form-control-sm" id="ThermalFooterMessage" rows="3" maxlength="500" placeholder="e.g. Thank you for your business!&#10;Visit again!"></textarea>
                                    </div>
                                </div>

                                <!-- ── QR Code Options (2-column) ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">QR Code Options</div>
                                <div class="row g-0 border-top border-bottom">
                                    <div class="col-6 thermal-toggle-cell border-end">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bxl-google me-1 text-secondary"></i>Google Reviews QR</div><div class="thermal-setting-desc">Print Google Reviews QR for customer feedback.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowGoogleReviewQR"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label"><i class="bx bx-qr me-1 text-secondary"></i>Payment QR</div><div class="thermal-setting-desc">Show UPI/payment QR on thermal printout.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowPaymentQR" checked></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── Branding & Printer Setup ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Branding &amp; Printer Setup</div>
                                <div class="row g-0 border-top">
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Org Name Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalOrgNameFontSize" value="16" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Address / Phone / GSTIN Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalCompanyNameFontSize" value="14" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Product Info Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalProductInfoFontSize" value="12" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3">
                                        <label class="form-label small fw-semibold mb-1">Select Printer</label>
                                        <select class="form-select form-select-sm" id="ThermalPaperWidthSelect">
                                            <option value="80mm">Thermal 80mm</option>
                                            <option value="58mm">Thermal 58mm</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                            <!-- /Left Form Panel -->

                            <!-- ═══ RIGHT: Live Preview ═══ -->
                            <div class="thermal-preview-panel" id="thermalPreviewPanel">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span class="fw-semibold small"><i class="bx bx-receipt me-1 text-secondary"></i>Receipt Preview</span>
                                    <span class="badge bg-label-secondary" id="thermalPreviewWidthBadge">80mm</span>
                                </div>
                                <div class="p-2 d-flex justify-content-center overflow-auto" style="flex:1;">
                                    <div id="thermalPreviewBox"
                                        style="font-family:'Courier New',Courier,monospace;font-size:11px;background:#fff;padding:10px;width:100%;max-width:280px;border:1px dashed #ccc;min-height:300px;line-height:1.5;">
                                        <div style="text-align:center;color:#aaa;margin-top:80px;font-size:11px;">Preview will appear here</div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Right Preview Panel -->

                        </div><!-- /modal-body -->

                        <div class="modal-footer py-3">
                            <button type="button" class="btn btn-primary" id="saveThermalConfigBtn">
                                <i class="bx bx-save me-1"></i>Save Config
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Thermal Print Config Modal -->

            <!-- ============================================================
                 Bank Account Modal (Add / Edit)
            ============================================================ -->
            <div class="modal fade" id="bankDetailModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">

                        <div class="modal-header pb-3">
                            <h5 class="modal-title" id="bankModalTitle">
                                <i class="bx bxs-credit-card me-1 text-primary"></i>Add Bank Account
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <hr class="my-0">

                        <div class="modal-body">
                            <input type="hidden" id="bankUID" value="0">

                            <div class="row g-3">

                                <!-- Account Holder Name -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Account Holder Name <span class="text-danger">*</span></label>
                                    <input type="text" id="bm_AccountName" class="form-control"
                                        placeholder="e.g. RISHIKA 2K ENTERPRISES" maxlength="100" autocomplete="off"/>
                                </div>

                                <!-- Account No + Confirm -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Account No <span class="text-danger">*</span></label>
                                    <input type="password" id="bm_AccountNumber" class="form-control"
                                        placeholder="Bank Account No." maxlength="50" autocomplete="new-password"/>
                                </div>
                                <div class="col-md-6" id="confirmAccWrap">
                                    <label class="form-label fw-semibold small">Confirm Bank Account No <span class="text-danger">*</span></label>
                                    <input type="text" id="bm_ConfirmAccountNumber" class="form-control"
                                        placeholder="Confirm Bank Account No." maxlength="50" autocomplete="new-password"/>
                                </div>

                                <!-- IFSC input-group + Bank Name -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">IFSC Code <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" id="bm_IFSC" class="form-control text-uppercase"
                                            placeholder="IFSC Code" maxlength="20" autocomplete="off"/>
                                        <button class="btn btn-outline-secondary" type="button" id="fetchBankDetailsBtn"
                                                title="Auto-fill bank details from IFSC">
                                            <span id="fetchBankSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                            <span id="fetchBankBtnText">Fetch</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" id="bm_BankName" class="form-control"
                                        placeholder="e.g. HDFC Bank, SBI..." maxlength="100" autocomplete="off"/>
                                </div>

                                <!-- Branch Name -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Branch Name</label>
                                    <input type="text" id="bm_BranchName" class="form-control"
                                        placeholder="Bank Branch Name" maxlength="100" autocomplete="off"/>
                                </div>

                                <!-- UPI section divider -->
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <hr class="flex-grow-1 mb-0">
                                        <small class="text-muted fw-semibold text-uppercase px-2" style="white-space:nowrap;">UPI Details <span class="badge bg-label-secondary ms-1">Optional</span></small>
                                        <hr class="flex-grow-1 mb-0">
                                    </div>
                                    <p class="text-muted mt-1 mb-0" style="font-size:0.78rem;">Link a UPI ID to generate dynamic QR codes on invoices and bills.</p>
                                </div>

                                <!-- UPI ID input-group + UPI Number -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">UPI ID <small class="text-muted fw-normal">(Optional)</small></label>
                                    <div class="input-group">
                                        <input type="text" id="bm_UPIId" class="form-control"
                                            placeholder="yourname@okhdfc" maxlength="100" autocomplete="off"/>
                                        <button class="btn btn-outline-secondary" type="button" id="verifyUPIBtn"
                                                title="Verify UPI ID">
                                            <span id="verifyUPISpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                            <span id="verifyUPIBtnText">Verify</span>
                                        </button>
                                    </div>
                                    <div class="form-text">e.g. komalakumar2329-1@okhdfcbank</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">UPI Number <small class="text-muted fw-normal">(Optional)</small></label>
                                    <input type="text" id="bm_UPINumber" class="form-control"
                                        placeholder="Linked mobile number" maxlength="50" autocomplete="off"/>
                                </div>

                                <!-- Set as Default -->
                                <div class="col-md-12">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="bm_IsDefault" value="1">
                                        <label class="form-check-label fw-semibold small" for="bm_IsDefault">
                                            Set as Default Bank Account
                                            <span class="text-muted fw-normal ms-1">— this account will be pre-selected on all transactions</span>
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <hr class="my-0">

                        <div class="modal-footer pt-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveBankBtn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" id="saveBankSpinner"></span>
                                Save &amp; Update
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Bank Account Modal -->

            <!-- ============================================================
                 Transfer Funds Modal
            ============================================================ -->
            <div class="modal fade" id="transferFundsModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
                    <div class="modal-content">

                        <div class="modal-header py-3">
                            <div>
                                <h5 class="modal-title mb-0"><i class="bx bx-transfer me-2 text-primary"></i>Transfer Funds</h5>
                                <small class="text-muted">Move money between your bank accounts</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <div class="d-none transferFormAlert alert alert-danger mb-3 p-2" role="alert">
                                <span class="alert-message"></span>
                            </div>

                            <div class="row g-3">

                                <div class="col-12">
                                    <label class="form-label">From Account <span class="text-danger">*</span></label>
                                    <select class="form-select" id="TransferFromBank">
                                        <option value="">— Select Account —</option>
                                    </select>
                                </div>

                                <div class="col-12 text-center">
                                    <i class="bx bx-down-arrow-alt text-muted" style="font-size:1.5rem;"></i>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">To Account <span class="text-danger">*</span></label>
                                    <select class="form-select" id="TransferToBank">
                                        <option value="">— Select Account —</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="TransferAmount" placeholder="0.00" step="0.01" min="0.01" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="TransferDate" value="<?php echo date('Y-m-d'); ?>" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Reference No <span class="text-muted small">(optional)</span></label>
                                    <input type="text" class="form-control" id="TransferReferenceNo" placeholder="UTR / Cheque No" maxlength="100" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
                                    <textarea class="form-control" id="TransferNotes" rows="2" maxlength="500" placeholder="Optional remarks"></textarea>
                                </div>

                            </div>

                        </div>

                        <div class="modal-footer py-3">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveTransferBtn">
                                <i class="bx bx-transfer me-1"></i>Transfer
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Transfer Funds Modal -->

            <!-- ============================================================
                 Message Template Modal
            ============================================================ -->
            <div class="modal fade" id="msgTemplateModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-3">
                            <h5 class="modal-title" id="msgTemplateModalTitle"><i class="bx bx-message-square-edit me-2 text-primary"></i>Message Template</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <hr class="my-0">
                        <div class="modal-body p-0">
                            <div class="d-flex" style="min-height:520px;">

                                <!-- Left: Form -->
                                <div class="p-4" style="flex:0 0 50%;border-right:1px solid var(--bs-border-color);overflow-y:auto;">
                                    <input type="hidden" id="msgTemplateUID" value="0">

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Transaction Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="msgModuleUID">
                                                <option value="">&mdash; Select &mdash;</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Channel <span class="text-danger">*</span></label>
                                            <select class="form-select" id="msgChannel">
                                                <option value="WhatsApp">WhatsApp</option>
                                                <option value="SMS">SMS</option>
                                                <option value="Email">Email</option>
                                            </select>
                                        </div>
                                        <div class="col-12 d-none" id="msgSubjectWrap">
                                            <label class="form-label fw-semibold">Email Subject</label>
                                            <input type="text" class="form-control" id="msgSubject" placeholder="e.g. Payment Receipt - {{DOC_NUMBER}}" maxlength="255">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Template Body <span class="text-danger">*</span></label>
                                            <!-- Email: Quill rich text editor -->
                                            <div id="msgBodyEditorWrap">
                                                <div id="msgBodyEditor" style="min-height:200px;font-size:.85rem;"></div>
                                                <input type="hidden" id="msgBody">
                                            </div>
                                            <!-- WhatsApp / SMS: plain textarea -->
                                            <div id="msgBodyTextareaWrap" class="d-none">
                                                <textarea class="form-control font-monospace" id="msgBodyPlain" rows="10"
                                                    placeholder="Type your message here. Click a token below to insert it."
                                                    style="font-size:.82rem;resize:vertical;"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" style="font-size:.78rem;">Available Tokens <span class="text-muted">(click to insert at cursor)</span></label>
                                            <div id="msgTokenList" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Live Preview -->
                                <div class="p-4" style="flex:0 0 50%;background:var(--bs-tertiary-bg,#f8f9fa);overflow-y:auto;">
                                    <div class="fw-semibold mb-3" style="font-size:.82rem;color:#888;text-transform:uppercase;letter-spacing:.4px;">
                                        <i class="bx bx-show me-1"></i>Live Preview
                                    </div>
                                    <div id="msgPreviewBox"
                                         style="background:#fff;border:1px solid var(--bs-border-color);border-radius:8px;padding:16px;min-height:200px;font-size:.85rem;line-height:1.6;">
                                        <span class="text-muted fst-italic">Preview will appear here as you type...</span>
                                    </div>
                                    <div class="mt-3 p-3" style="background:#fffde7;border-radius:6px;font-size:.76rem;color:#666;">
                                        <strong>Note:</strong> <code>*bold*</code> formatting works in WhatsApp. Tokens shown with sample data in preview.
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer py-3">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="btnSaveMsgTemplate">
                                <i class="bx bx-save me-1"></i>Save Template
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- / Message Template Modal -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
/* ── Top-level horizontal tabs ─────────────────────────── */
.gs-top-tabs { border-bottom: none; }
.gs-top-tabs .gs-top-link {
    color: var(--bs-body-color); border-radius: 0;
    border-bottom: 3px solid transparent;
    padding: 0.65rem 1.1rem; font-size: 0.9rem;
    transition: color 0.15s, border-color 0.15s;
}
.gs-top-tabs .gs-top-link:hover  { color: var(--bs-primary); border-bottom-color: var(--bs-primary); background: transparent; }
.gs-top-tabs .gs-top-link.active { color: var(--bs-primary); border-bottom: 3px solid var(--bs-primary); background: transparent; font-weight: 600; }

/* ── Left vertical sub-tabs ────────────────────────────── */
.gs-nav-pills .gs-tab-link { border-radius: 0; color: var(--bs-body-color); font-size: 0.875rem; border-left: 3px solid transparent; transition: background 0.15s, border-color 0.15s; }
.gs-nav-pills .gs-tab-link:hover  { background-color: var(--bs-gray-100); }
.gs-nav-pills .gs-tab-link.active { background-color: rgba(var(--bs-primary-rgb), 0.08); color: var(--bs-primary); border-left-color: var(--bs-primary); font-weight: 600; }

/* ── Thermal modal two-panel layout ────────────────────── */
.thermal-form-panel {
    flex: 0 0 58%;
    overflow-y: auto;
    border-right: 1px solid var(--bs-border-color);
}
.thermal-preview-panel {
    flex: 0 0 42%;
    display: flex;
    flex-direction: column;
    background: var(--bs-tertiary-bg, #f8f9fa);
    overflow: hidden;
}

/* Section headers */
.thermal-section-header {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--bs-secondary-color, #6c757d);
}
/* Toggle cell labels */
.thermal-setting-label  { font-size: 0.82rem; font-weight: 600; color: var(--bs-body-color); margin-bottom: 2px; }
.thermal-setting-desc   { font-size: 0.72rem; color: var(--bs-secondary-color, #6c757d); line-height: 1.35; }
.thermal-toggle-cell    { background: var(--bs-body-bg); }
.thermal-toggle-cell:hover { background: var(--bs-tertiary-bg, #f8f9fa); }

/* Swipe-sized switch */
.thermal-switch { width: 2.4em !important; height: 1.3em !important; cursor: pointer; flex-shrink: 0; }

/* Logo upload mini button */
.thermal-upload-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border: 1.5px dashed var(--bs-border-color);
    border-radius: 6px; cursor: pointer; color: var(--bs-secondary-color, #6c757d);
    font-size: 1rem; transition: border-color 0.15s, color 0.15s;
}
.thermal-upload-btn:hover { border-color: var(--bs-primary); color: var(--bs-primary); }

/* ── Banks tab ─────────────────────────────────────────────── */
.bank-icon-cash, .bank-icon-normal {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 8px; font-size: 1.1rem; flex-shrink: 0;
}
.bank-icon-cash   { background: rgba(var(--bs-warning-rgb),.12); color: var(--bs-warning); }
.bank-icon-normal { background: rgba(var(--bs-primary-rgb),.1);  color: var(--bs-primary); }
/* Quill editor in modal */
#msgBodyEditor { border: 1px solid var(--bs-border-color); border-top: none; border-radius: 0 0 6px 6px; background:#fff; }
.ql-toolbar { border: 1px solid var(--bs-border-color) !important; border-radius: 6px 6px 0 0 !important; }
.ql-container { font-size: .85rem !important; }
</style>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function() {
    'use strict';

    // ── General / Invoice / Inventory save (placeholder) ──────────────────
    $(document).on('click', '.gs-save-btn', function() {
        Swal.fire({ icon:'success', title:'Saved!', text:'Settings have been saved successfully.', timer:1800, showConfirmButton:false });
    });

    // ── Thermal Print Config ───────────────────────────────────────────────
    var thermalUsedTypes  = [];
    var thermalAllTypes   = {}; // { ModuleUID: Name } from DB
    var thermalLoaded     = false;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Show/hide payment-irrelevant fields based on ModuleUID ──────────────
    var PAYMENT_MODULE_UIDS = [110, 111];

    function togglePaymentFields(moduleUID) {
        var isPayment = PAYMENT_MODULE_UIDS.indexOf(parseInt(moduleUID, 10)) !== -1;
        $('#thermalItemDescCell, #thermalTaxableAmtCell, #thermalHSNCell, #thermalTaxBreakdownCell')
            .toggle(!isPayment);
        if (isPayment) {
            $('#ThermalShowItemDescription, #ThermalShowTaxableAmount, #ThermalShowHSN, #ThermalShowTaxBreakdown')
                .prop('checked', false);
        }
    }

    $(document).on('change', '#ThermalTransType', function () {
        togglePaymentFields($(this).val());
        updateThermalPreview();
    });

    // ── Live preview builder ───────────────────────────────────────────────
    function updateThermalPreview() {
        var transType    = $('#ThermalTransType option:selected').text().trim() || 'Transaction';
        var footerMsg    = $('#ThermalFooterMessage').val().trim() || 'Thank you for your business!';
        var paperWidth   = $('#ThermalPaperWidthSelect').val() || '80mm';
        var orgFontSize  = Math.max(10, Math.min(28, parseInt($('#ThermalOrgNameFontSize').val()) || 16));
        var coFontSize   = Math.max(9,  Math.min(22, parseInt($('#ThermalCompanyNameFontSize').val()) || 14));
        var prodFontSize = Math.max(8,  Math.min(20, parseInt($('#ThermalProductInfoFontSize').val()) || 12));

        var showCo       = $('#ThermalShowCompanyDetails').is(':checked');
        var showGSTIN    = $('#ThermalShowGSTIN').is(':checked');
        var showMobile   = $('#ThermalShowMobile').is(':checked');
        var showHSN      = $('#ThermalShowHSN').is(':checked');
        var showTax      = $('#ThermalShowTaxBreakdown').is(':checked');
        var showTaxable  = $('#ThermalShowTaxableAmount').is(':checked');
        var showCash     = $('#ThermalShowCashReceived').is(':checked');
        var showLogo     = $('#ThermalShowLogo').is(':checked');
        var showPayQR    = $('#ThermalShowPaymentQR').is(':checked');
        var showRevQR    = $('#ThermalShowGoogleReviewQR').is(':checked');
        var showTerms    = $('#ThermalShowTerms').is(':checked');
        var showItemDesc = $('#ThermalShowItemDescription').is(':checked');

        var maxWidth     = paperWidth === '58mm' ? '200px' : '270px';

        var d = ''; // receipt HTML
        var hr = '<hr style="border:none;border-top:1px dashed #000;margin:4px 0">';
        var flex = function(l,r,bold,small) {
            var s = small ? 'font-size:10px;' : '';
            var b = bold  ? 'font-weight:bold;' : '';
            return '<div style="display:flex;justify-content:space-between;'+s+b+'"><span>'+l+'</span><span>'+r+'</span></div>';
        };

        // Logo placeholder
        if (showLogo) d += '<div style="text-align:center;margin:0 0 4px"><div style="display:inline-block;border:1px solid #ccc;padding:2px 8px;font-size:9px;color:#aaa">[ LOGO ]</div></div>';

        // Org name header
        var orgName = <?php echo json_encode($OrgPreviewData->Name ?? ''); ?>;
        d += '<div style="text-align:center;font-weight:bold;font-size:'+orgFontSize+'px">' + escHtml(orgName || 'Store Name') + '</div>';

        // Company details
        if (showCo) {
            var orgAddr = [<?php
                $parts = array_filter([
                    $OrgPreviewData->Line1    ?? '',
                    $OrgPreviewData->Line2    ?? '',
                    $OrgPreviewData->CityText ?? '',
                    $OrgPreviewData->StateText ?? '',
                    $OrgPreviewData->Pincode  ?? '',
                ]);
                echo json_encode(implode(', ', $parts));
            ?>][0];
            if (orgAddr) d += '<div style="text-align:center;font-size:'+coFontSize+'px">' + escHtml(orgAddr) + '</div>';
            if (showGSTIN)  d += '<div style="text-align:center;font-size:'+coFontSize+'px">GSTIN: <?php echo addslashes($OrgPreviewData->GSTIN ?? ''); ?></div>';
            if (showMobile) d += '<div style="text-align:center;font-size:'+coFontSize+'px">Ph: <?php echo addslashes($OrgPreviewData->MobileNumber ?? ''); ?></div>';
        }

        d += hr;
        d += flex('<strong>'+escHtml(transType)+'</strong>', 'EST/0001');
        d += flex('Date:', '<?php echo date("d-m-Y"); ?>');
        d += flex('Customer:', 'Sample Customer');
        d += hr;
        d += flex('<span style="font-weight:bold">Item</span>', '<span style="font-weight:bold">Amount</span>');
        d += hr;

        // Sample item
        var taxFontSize = Math.max(6, prodFontSize - 2);
        d += '<div style="font-weight:bold;font-size:'+prodFontSize+'px">Sample Product</div>';
        if (showItemDesc) d += '<div style="font-size:'+(prodFontSize-2)+'px;font-style:italic;color:#777">Premium quality rotavator blade - heavy duty</div>';
        if (showHSN)      d += '<div style="font-size:'+prodFontSize+'px;color:#555">HSN: 8432 90 00</div>';
        var displayPrice = showTaxable ? '\u20b9500.00' : '\u20b9590.00';
        d += '<div style="display:flex;justify-content:space-between;font-size:'+prodFontSize+'px"><span>2 Nos \u00d7 '+displayPrice+'</span><span>\u20b91,180.00</span></div>';
        if (showTaxable && showTax) {
            d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic"><span>CGST 9%</span><span>\u20b990.00</span></div>';
            d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic"><span>SGST 9%</span><span>\u20b990.00</span></div>';
        }
        d += hr;

        // Totals
        if (showTaxable) {
            if (showTaxable) d += '<div style="display:flex;justify-content:space-between;font-size:'+prodFontSize+'px"><span>Subtotal:</span><span>\u20b91,000.00</span></div>';
            if (showTax)     d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic"><span>Total Tax:</span><span>\u20b9180.00</span></div>';
            if (showTax && showTax) {
                d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic;color:#555"><span>  CGST:</span><span>\u20b990.00</span></div>';
                d += '<div style="display:flex;justify-content:space-between;font-size:'+taxFontSize+'px;font-style:italic;color:#555"><span>  SGST:</span><span>\u20b990.00</span></div>';
            }
        }
        d += '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:'+prodFontSize+'px;border-top:1px solid #000;padding-top:3px;margin-top:3px"><span>Total:</span><span>\u20b91,180.00</span></div>';
        if (showCash) d += '<div style="display:flex;justify-content:space-between;font-size:'+prodFontSize+'px"><span>Cash Received:</span><span>\u20b91,200.00</span></div>';

        d += hr;

        // QR codes
        if (showPayQR || showRevQR) {
            d += '<div style="display:flex;justify-content:center;gap:10px;margin:4px 0">';
            if (showPayQR) d += '<div style="text-align:center"><div style="border:1px solid #000;padding:4px;font-size:9px;display:inline-block">&#9632;&#9632;<br>&#9632;&#9632;</div><div style="font-size:9px;margin-top:2px">Pay</div></div>';
            if (showRevQR) d += '<div style="text-align:center"><div style="border:1px solid #000;padding:4px;font-size:9px;display:inline-block">&#9633;&#9633;<br>&#9633;&#9633;</div><div style="font-size:9px;margin-top:2px">Review</div></div>';
            d += '</div>';
        }

        // Terms
        if (showTerms) {
            d += hr;
            d += '<div style="font-size:'+(prodFontSize-2)+'px;font-style:italic;color:#555">Goods once sold will not be taken back or exchanged.</div>';
        }

        // Footer
        var footerLines = footerMsg.split('\n');
        footerLines.forEach(function(line) {
            if (line.trim()) d += '<div style="text-align:center;font-size:'+prodFontSize+'px">'+escHtml(line)+'</div>';
        });

        // Apply
        $('#thermalPreviewWidthBadge').text(paperWidth);
        $('#thermalPreviewBox').html(d).css('max-width', maxWidth);
    }

    // Bind all form controls inside the modal for live preview
    $(document).on('input change', '#thermalConfigModal input, #thermalConfigModal select, #thermalConfigModal textarea', function() {
        updateThermalPreview();
    });

    // Show preview when modal is opened
    $('#thermalConfigModal').on('shown.bs.modal', function() {
        updateThermalPreview();
    });

    // ── Load list ──────────────────────────────────────────────────────────
    function loadThermalConfigList() {
        $('#ThermalConfigBody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>');
        AjaxLoading = 0;
        $.ajax({
            url: '/settings/getThermalConfigList',
            method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function(resp) {
                AjaxLoading = 1;
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (!resp.Error) {
                    thermalUsedTypes = resp.UsedTypes || [];
                    thermalAllTypes  = resp.TransTypes || {};
                    thermalLoaded    = true;
                    $('#ThermalConfigBody').html(resp.RecordHtmlData);
                    updateAddBtnState();
                    // Rebuild dropdown options from DB — value = ModuleUID
                    var $sel = $('#ThermalTransType').empty().append('<option value="">-- Select Transaction Type --</option>');
                    $.each(thermalAllTypes, function(moduleUID, label) {
                        $sel.append('<option value="' + moduleUID + '">' + escHtml(label) + '</option>');
                    });
                    $('[data-bs-toggle="tooltip"]').each(function() {
                        var tt = bootstrap.Tooltip.getInstance(this); if (tt) tt.dispose();
                        new bootstrap.Tooltip(this);
                    });
                }
            }
        });
    }

    function updateAddBtnState() {
        var allKeys = Object.keys(thermalAllTypes).map(Number);
        var allUsed = allKeys.length > 0 && allKeys.every(function(uid) { return thermalUsedTypes.indexOf(uid) !== -1; });
        $('#btnAddThermalConfig').prop('disabled', allUsed).attr('title', allUsed ? 'All transaction types are already configured.' : '');
    }

    $('#toptab-thermal-config-tab').on('shown.bs.tab', function() {
        if (!thermalLoaded) loadThermalConfigList();
    });

    // ── Add ────────────────────────────────────────────────────────────────
    $('#btnAddThermalConfig').on('click', function() {
        resetThermalModal();
        $('#ThermalTransType option').each(function() {
            var v = parseInt($(this).val(), 10);
            if (v && thermalUsedTypes.indexOf(v) !== -1) { $(this).prop('disabled', true); }
            else { $(this).prop('disabled', false); }
        });
        $('#thermalConfigModal').modal('show');
    });

    // ── Edit ───────────────────────────────────────────────────────────────
    var typeLabels = thermalAllTypes; // { ModuleUID: Name }

    $(document).on('click', '.EditThermalConfig', function() {
        var cfg = $(this).data('config');
        if (!cfg) return;

        resetThermalModal();
        $('#thermalModalSubtitle').text('Editing: ' + (cfg.ModuleName || typeLabels[cfg.ModuleUID] || cfg.ModuleUID));
        $('#HThermalConfigUID').val(cfg.ThermalConfigUID);
        $('#ThermalTransType').val(cfg.ModuleUID).prop('disabled', true);
        $('#thermalTransTypeRow').hide();
        togglePaymentFields(cfg.ModuleUID);
        $('#ThermalPaperWidthSelect').val(cfg.PaperWidth || '80mm');
        $('#ThermalFooterMessage').val(cfg.FooterMessage || '');
        $('#ThermalShowTerms').prop('checked',           cfg.ShowTerms           == 1);
        $('#ThermalShowCompanyDetails').prop('checked',  cfg.ShowCompanyDetails  == 1);
        $('#ThermalShowItemDescription').prop('checked', cfg.ShowItemDescription == 1);
        $('#ThermalShowTaxableAmount').prop('checked',   cfg.ShowTaxableAmount   == 1);
        $('#ThermalShowHSN').prop('checked',             cfg.ShowHSN             == 1);
        $('#ThermalShowTaxBreakdown').prop('checked',    cfg.ShowTaxBreakdown    == 1);
        $('#ThermalShowGSTIN').prop('checked',           cfg.ShowGSTIN           == 1);
        $('#ThermalShowMobile').prop('checked',          cfg.ShowMobile          == 1);
        $('#ThermalShowCashReceived').prop('checked',    cfg.ShowCashReceived    == 1);
        $('#ThermalShowLogo').prop('checked',            cfg.ShowLogo            == 1);
        $('#ThermalShowGoogleReviewQR').prop('checked',  cfg.ShowGoogleReviewQR  == 1);
        $('#ThermalShowPaymentQR').prop('checked',       cfg.ShowPaymentQR       == 1);
        $('#ThermalOrgNameFontSize').val(cfg.OrgNameFontSize       || 16);
        $('#ThermalCompanyNameFontSize').val(cfg.CompanyNameFontSize || 14);
        $('#ThermalProductInfoFontSize').val(cfg.ProductInfoFontSize || 12);
        $('#thermalConfigModal').modal('show');
    });

    // ── Save ───────────────────────────────────────────────────────────────
    $('#saveThermalConfigBtn').on('click', function() {
        var $btn = $(this), configUID = parseInt($('#HThermalConfigUID').val()) || 0, moduleUID = parseInt($('#ThermalTransType').val()) || 0;
        if (!configUID && !moduleUID) {
            showToastNotification('Please select a transaction type.', 'error');
            return;
        }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $.ajax({
            url: '/settings/saveThermalConfig', method: 'POST',
            data: {
                ThermalConfigUID    : configUID,
                ModuleUID           : moduleUID,
                PaperWidth          : $('#ThermalPaperWidthSelect').val() || '80mm',
                FooterMessage       : $('#ThermalFooterMessage').val(),
                ShowTerms           : $('#ThermalShowTerms').is(':checked')           ? 1 : 0,
                ShowCompanyDetails  : $('#ThermalShowCompanyDetails').is(':checked')  ? 1 : 0,
                ShowItemDescription : $('#ThermalShowItemDescription').is(':checked') ? 1 : 0,
                ShowTaxableAmount   : $('#ThermalShowTaxableAmount').is(':checked')   ? 1 : 0,
                ShowHSN             : $('#ThermalShowHSN').is(':checked')             ? 1 : 0,
                ShowTaxBreakdown    : $('#ThermalShowTaxBreakdown').is(':checked')    ? 1 : 0,
                ShowGSTIN           : $('#ThermalShowGSTIN').is(':checked')           ? 1 : 0,
                ShowMobile          : $('#ThermalShowMobile').is(':checked')          ? 1 : 0,
                ShowCashReceived    : $('#ThermalShowCashReceived').is(':checked')    ? 1 : 0,
                ShowLogo            : $('#ThermalShowLogo').is(':checked')            ? 1 : 0,
                ShowGoogleReviewQR  : $('#ThermalShowGoogleReviewQR').is(':checked')  ? 1 : 0,
                ShowPaymentQR       : $('#ThermalShowPaymentQR').is(':checked')       ? 1 : 0,
                OrgNameFontSize     : $('#ThermalOrgNameFontSize').val()     || 16,
                CompanyNameFontSize : $('#ThermalCompanyNameFontSize').val() || 14,
                ProductInfoFontSize : $('#ThermalProductInfoFontSize').val() || 12,
                [CsrfName]          : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Config');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    $('#thermalConfigModal').modal('hide');
                    thermalUsedTypes = resp.UsedTypes || [];
                    thermalAllTypes  = resp.TransTypes || thermalAllTypes;
                    thermalLoaded    = true;
                    $('#ThermalConfigBody').html(resp.RecordHtmlData);
                    updateAddBtnState();
                    var $sel = $('#ThermalTransType').empty().append('<option value="">-- Select Transaction Type --</option>');
                    $.each(thermalAllTypes, function(moduleUID, label) {
                        $sel.append('<option value="' + moduleUID + '">' + escHtml(label) + '</option>');
                    });
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Config');
                showToastNotification('Server error. Please try again.', 'error');
            }
        });
    });

    // ── Delete ─────────────────────────────────────────────────────────────
    $(document).on('click', '.DeleteThermalConfig', function() {
        var uid = $(this).data('uid'), typeName = $(this).data('type');
        if (!uid) return;
        Swal.fire({
            title: 'Delete ' + typeName + ' config?', text: "You won't be able to revert this!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!', cancelButtonColor: '#3085d6',
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/settings/deleteThermalConfig', method: 'POST',
                    data: { ThermalConfigUID: uid, [CsrfName]: CsrfToken },
                    success: function(resp) {
                        CsrfToken = resp.NewCsrfToken || CsrfToken;
                        if (resp.Error) { Swal.fire({ icon:'error', title:'Error', text:resp.Message }); }
                        else { thermalLoaded = false; loadThermalConfigList(); Swal.fire({ icon:'success', title:'Deleted', text:resp.Message, timer:1500, showConfirmButton:false }); }
                    }
                });
            }
        });
    });

    // ── Reset ──────────────────────────────────────────────────────────────
    function resetThermalModal() {
        $('#HThermalConfigUID').val('0');
        $('#ThermalTransType').val('').prop('disabled', false);
        $('#thermalTransTypeRow').show();
        togglePaymentFields(0);
        $('#thermalModalTitle').html('<i class="bx bx-printer me-2 text-primary"></i>Thermal Print Settings');
        $('#thermalModalSubtitle').text('Add a new configuration');
        $('#ThermalPaperWidthSelect').val('80mm');
        $('#ThermalFooterMessage').val('');
        $('#ThermalShowTerms,#ThermalShowItemDescription,#ThermalShowTaxableAmount,#ThermalShowLogo,#ThermalShowGoogleReviewQR').prop('checked', false);
        $('#ThermalShowCompanyDetails,#ThermalShowHSN,#ThermalShowTaxBreakdown,#ThermalShowGSTIN,#ThermalShowMobile,#ThermalShowCashReceived,#ThermalShowPaymentQR').prop('checked', true);
        $('#ThermalOrgNameFontSize').val(16);
        $('#ThermalCompanyNameFontSize').val(14);
        $('#ThermalProductInfoFontSize').val(12);
        $('.thermalFormAlert').addClass('d-none');
        updateThermalPreview();
    }

    $('#thermalConfigModal').on('hidden.bs.modal', function() { resetThermalModal(); });

    // ── Banks ──────────────────────────────────────────────────────────────
    var banksLoaded = false;
    var banksList   = [];

    function loadBankList() {
        $('#bankCardsContainer').html(
            '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>'
        );
        AjaxLoading = 0;
        $.ajax({
            url: '/settings/getBankList', method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function(resp) {
                AjaxLoading = 1;
                CsrfToken   = resp.NewCsrfToken || CsrfToken;
                banksLoaded = true;
                if (!resp.Error) {
                    $('#bankCardsContainer').html(resp.RecordHtmlData);
                    cacheBanksForTransfer();
                }
            }
        });
    }

    function cacheBanksForTransfer() {
        banksList = [];
        $('#bankCardsContainer [id^="bankCard_"]').each(function() {
            var uid    = $(this).attr('id').replace('bankCard_', '');
            var name   = $(this).find('.fw-bold').first().text().trim();
            var isCash = $(this).find('.bx-money').length > 0;
            banksList.push({ uid: uid, name: name, isCash: isCash });
        });
    }

    $('#toptab-banks-tab').on('shown.bs.tab', function() {
        if (!banksLoaded) loadBankList();
    });

    // Open modal: Add
    $(document).on('click', '#btnAddBank, #btnAddBankEmpty', function() {
        resetBankForm();
        $('#bankModalTitle').html('<i class="bx bxs-credit-card me-1 text-primary"></i>Add Bank Account');
        $('#confirmAccWrap').show();
        $('#bankDetailModal').modal('show');
    });

    // Open modal: Edit
    $(document).on('click', '.editBankBtn', function() {
        var uid = $(this).data('uid');
        resetBankForm();
        $('#bankModalTitle').html('<i class="bx bx-edit me-1 text-primary"></i>Edit Bank Account');
        $('#confirmAccWrap').hide();
        $('#bankUID').val(uid);
        $.ajax({
            url: '/settings/getBankDetail', method: 'POST',
            data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                var d = resp.Data;
                $('#bm_AccountName').val(d.AccountName || '');
                $('#bm_AccountNumber').val(d.AccountNumber || '');
                $('#bm_IFSC').val(d.IFSC || '');
                $('#bm_BankName').val(d.BankName || '');
                $('#bm_BranchName').val(d.BranchName || '');
                $('#bm_UPIId').val(d.UPIId || '');
                $('#bm_UPINumber').val(d.UPINumber || '');
                $('#bm_IsDefault').prop('checked', parseInt(d.IsDefault) === 1);
                $('#bankDetailModal').modal('show');
            },
            error: function() { Swal.fire('Error', 'Failed to load bank details.', 'error'); }
        });
    });

    // Save
    $(document).on('click', '#saveBankBtn', function() {
        var uid           = parseInt($('#bankUID').val()) || 0;
        var accountName   = $.trim($('#bm_AccountName').val());
        var accountNumber = $.trim($('#bm_AccountNumber').val());
        var confirmNo     = $.trim($('#bm_ConfirmAccountNumber').val());
        var ifsc          = $.trim($('#bm_IFSC').val()).toUpperCase();
        var bankName      = $.trim($('#bm_BankName').val());
        var branchName    = $.trim($('#bm_BranchName').val());
        var upiId         = $.trim($('#bm_UPIId').val());
        var upiNumber     = $.trim($('#bm_UPINumber').val());
        var isDefault     = $('#bm_IsDefault').is(':checked') ? 1 : 0;

        if (!accountName)   return bankFieldError('#bm_AccountName',   'Account holder name is required.');
        if (!accountNumber) return bankFieldError('#bm_AccountNumber', 'Account number is required.');
        if (uid <= 0 && !confirmNo) return bankFieldError('#bm_ConfirmAccountNumber', 'Please confirm the account number.');
        if (uid <= 0 && accountNumber !== confirmNo) return bankFieldError('#bm_ConfirmAccountNumber', 'Account numbers do not match.');
        if (!bankName)      return bankFieldError('#bm_BankName',      'Bank name is required.');

        var $btn = $('#saveBankBtn').prop('disabled', true);
        $('#saveBankSpinner').removeClass('d-none');

        $.ajax({
            url: '/settings/saveBankDetail', method: 'POST',
            data: {
                BankAccountUID       : uid,
                AccountName          : accountName,
                AccountNumber        : accountNumber,
                ConfirmAccountNumber : confirmNo,
                IFSC                 : ifsc,
                BankName             : bankName,
                BranchName           : branchName,
                UPIId                : upiId,
                UPINumber            : upiNumber,
                IsDefault            : isDefault,
                [CsrfName]           : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                $('#saveBankSpinner').addClass('d-none');
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                $('#bankDetailModal').modal('hide');
                Swal.fire({ icon:'success', title:'Saved', text:resp.Message, timer:2000, showConfirmButton:false });
                banksLoaded = false; loadBankList();
            },
            error: function() {
                $btn.prop('disabled', false);
                $('#saveBankSpinner').addClass('d-none');
                Swal.fire('Error', 'Server error. Please try again.', 'error');
            }
        });
    });

    // Set Default
    $(document).on('click', '.setDefaultBankBtn', function() {
        var uid  = $(this).data('uid');
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/settings/setDefaultBank', method: 'POST',
            data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false);
                if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                banksLoaded = false; loadBankList();
            },
            error: function() { $btn.prop('disabled', false); Swal.fire('Error', 'Server error.', 'error'); }
        });
    });

    // Delete
    $(document).on('click', '.deleteBankBtn', function() {
        var uid  = $(this).data('uid');
        var name = $(this).data('name');
        Swal.fire({
            title: 'Delete Bank Account?',
            html : '<span class="text-muted small">' + name + '</span>',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Delete', confirmButtonColor: '#dc3545',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '/settings/deleteBankDetail', method: 'POST',
                data: { BankAccountUID: uid, [CsrfName]: CsrfToken },
                success: function(resp) {
                    CsrfToken = resp.NewCsrfToken || CsrfToken;
                    if (resp.Error) return Swal.fire('Error', resp.Message, 'error');
                    Swal.fire({ icon:'success', title:'Deleted', text:resp.Message, timer:1800, showConfirmButton:false });
                    banksLoaded = false; loadBankList();
                },
                error: function() { Swal.fire('Error', 'Server error.', 'error'); }
            });
        });
    });

    // IFSC Fetch
    $(document).on('click', '#fetchBankDetailsBtn', function() {
        var ifsc = $.trim($('#bm_IFSC').val()).toUpperCase();
        if (ifsc.length < 11) return Swal.fire('Info', 'Please enter a valid 11-character IFSC code.', 'info');
        $('#fetchBankSpinner').removeClass('d-none');
        $('#fetchBankBtnText').text('');
        $.ajax({
            url: 'https://ifsc.razorpay.com/' + ifsc, method: 'GET',
            success: function(data) {
                $('#fetchBankSpinner').addClass('d-none');
                $('#fetchBankBtnText').text('Fetch');
                if (data && data.BANK) {
                    $('#bm_BankName').val(data.BANK || '');
                    $('#bm_BranchName').val(data.BRANCH || '');
                    Swal.fire({ icon:'success', title:'Bank details fetched', text: data.BANK + ', ' + data.BRANCH, timer:2500, showConfirmButton:false });
                } else {
                    Swal.fire('Not found', 'Could not find bank details for this IFSC.', 'warning');
                }
            },
            error: function() {
                $('#fetchBankSpinner').addClass('d-none');
                $('#fetchBankBtnText').text('Fetch');
                Swal.fire('Error', 'Failed to fetch IFSC details. Please fill in manually.', 'error');
            }
        });
    });

    // Auto-uppercase IFSC
    $(document).on('input', '#bm_IFSC', function() { $(this).val($(this).val().toUpperCase()); });

    // Real-time account number match check
    $(document).on('input', '#bm_ConfirmAccountNumber', function() {
        var accNo   = $('#bm_AccountNumber').val();
        var confirm = $(this).val();
        if (confirm.length === 0) {
            $(this).removeClass('is-invalid is-valid');
            $(this).next('.invalid-feedback').remove();
            return;
        }
        if (accNo !== confirm) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Account numbers do not match.</div>');
            }
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Verify UPI
    $(document).on('click', '#verifyUPIBtn', function() {
        var upi = $.trim($('#bm_UPIId').val());
        if (!upi) return Swal.fire('Info', 'Please enter a UPI ID first.', 'info');
        $('#verifyUPISpinner').removeClass('d-none');
        $('#verifyUPIBtnText').text('');
        setTimeout(function() {
            $('#verifyUPISpinner').addClass('d-none');
            $('#verifyUPIBtnText').text('Verify');
            Swal.fire({ icon:'info', text:'UPI verification requires payment gateway integration.', timer:2200, showConfirmButton:false });
        }, 800);
    });

    // Clear validation on input
    $(document).on('input', '#bankDetailModal .form-control', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });

    function resetBankForm() {
        $('#bankUID').val(0);
        $('#bm_AccountName,#bm_AccountNumber,#bm_ConfirmAccountNumber,#bm_IFSC,#bm_BankName,#bm_BranchName,#bm_UPIId,#bm_UPINumber').val('');
        $('#bm_IsDefault').prop('checked', false);
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#confirmAccWrap').show();
    }

    function bankFieldError(selector, msg) {
        $(selector).addClass('is-invalid').focus();
        if (!$(selector).next('.invalid-feedback').length) {
            $(selector).after('<div class="invalid-feedback">' + msg + '</div>');
        }
        return false;
    }

    $('#bankDetailModal').on('hidden.bs.modal', function() { resetBankForm(); });

    // Transfer Funds
    $('#btnTransferFunds').on('click', function() {
        resetTransferModal();
        var $from = $('#TransferFromBank'), $to = $('#TransferToBank');
        $from.find('option:not(:first)').remove();
        $to.find('option:not(:first)').remove();
        banksList.forEach(function(b) {
            var opt = '<option value="' + b.uid + '">' + (b.isCash ? '💵 ' : '🏦 ') + b.name + '</option>';
            $from.append(opt); $to.append(opt);
        });
        $('#transferFundsModal').modal('show');
    });

    $('#saveTransferBtn').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Transferring...');
        $.ajax({
            url: '/settings/transferFunds', method: 'POST',
            data: {
                FromBankUID  : $('#TransferFromBank').val(),
                ToBankUID    : $('#TransferToBank').val(),
                Amount       : $('#TransferAmount').val(),
                TransferDate : $('#TransferDate').val(),
                ReferenceNo  : $('#TransferReferenceNo').val().trim(),
                Notes        : $('#TransferNotes').val().trim(),
                [CsrfName]   : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i>Transfer');
                if (resp.Error) {
                    $('.transferFormAlert .alert-message').text(resp.Message);
                    $('.transferFormAlert').removeClass('d-none');
                } else {
                    $('#transferFundsModal').modal('hide');
                    Swal.fire({ icon:'success', title:'Done', text:resp.Message, timer:1500, showConfirmButton:false });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i>Transfer');
                Swal.fire({ icon:'error', text:'Server error. Please try again.' });
            }
        });
    });

    function resetTransferModal() {
        $('#TransferFromBank,#TransferToBank').val('');
        $('#TransferAmount,#TransferReferenceNo,#TransferNotes').val('');
        $('#TransferDate').val('<?php echo date("Y-m-d"); ?>');
        $('.transferFormAlert').addClass('d-none');
    }

    $('#transferFundsModal').on('hidden.bs.modal', function() { resetTransferModal(); });

    // ── Message Templates ────────────────────────────────────────────────────
    // ── Message Templates ─────────────────────────────────────────────────────
    var msgTplLoaded = false;
    var msgTokens    = {};
    var msgModules   = {};
    var quillEditor      = null;
    var _pendingEditBody = null; // holds body to set after Quill initialises

    // Init Quill once modal is shown (only once), then apply any pending edit body
    $('#msgTemplateModal').on('shown.bs.modal', function() {
        if (!quillEditor) {
            quillEditor = new Quill('#msgBodyEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold','italic','underline','strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['link'],
                        ['clean']
                    ]
                }
            });
            quillEditor.on('text-change', function() {
                $('#msgBody').val(quillEditor.root.innerHTML);
                updateMsgPreview();
            });
        }
        // Apply pending body (set before Quill was ready)
        if (_pendingEditBody !== null) {
            quillEditor.root.innerHTML = _pendingEditBody;
            $('#msgBody').val(_pendingEditBody);
            _pendingEditBody = null;
            updateMsgPreview();
        }
    });

    function getMsgBody() {
        return $('#msgChannel').val() === 'Email' ? (quillEditor ? quillEditor.root.innerHTML : '') : $('#msgBodyPlain').val();
    }

    function setMsgBody(ch, val) {
        if (ch === 'Email') {
            if (quillEditor) {
                quillEditor.root.innerHTML = val || '';
                $('#msgBody').val(val || '');
            } else {
                // Quill not ready yet — store for after shown.bs.modal fires
                _pendingEditBody = val || '';
            }
        } else {
            $('#msgBodyPlain').val(val || '');
        }
    }

    function switchBodyEditor(ch) {
        if (ch === 'Email') {
            $('#msgBodyEditorWrap').removeClass('d-none');
            $('#msgBodyTextareaWrap').addClass('d-none');
        } else {
            $('#msgBodyEditorWrap').addClass('d-none');
            $('#msgBodyTextareaWrap').removeClass('d-none');
        }
    }

    $('#toptab-msgtemplates-tab').on('shown.bs.tab', function() {
        if (!msgTplLoaded) loadMsgTemplates();
    });

    function loadMsgTemplates() {
        $('#MsgTemplateBody').html('<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>');
        AjaxLoading = 0;
        $.ajax({
            url: '/settings/getMsgTemplateList', method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function(resp) {
                AjaxLoading = 1;
                if (!resp.Error) {
                    $('#MsgTemplateBody').html(resp.RecordHtmlData);
                    msgTokens  = resp.Tokens  || {};
                    msgModules = resp.Modules || {};
                    msgTplLoaded = true;
                    buildModuleOptions();
                }
            }, error: function() { AjaxLoading = 1; }
        });
    }

    function buildModuleOptions() {
        var html = '<option value="">— Select Transaction Type —</option>';
        $.each(msgModules, function(uid, name) {
            html += '<option value="' + uid + '">' + name + '</option>';
        });
        $('#msgModuleUID').html(html);
    }

    function buildTokenBadges(channel) {
        var html = '';
        $.each(msgTokens, function(token, desc) {
            html += '<span class="badge bg-label-secondary me-1 mb-1 msg-token-badge" '
                  + 'style="cursor:pointer;font-size:.72rem;" '
                  + 'data-token="' + token + '" title="' + desc + '">' + token + '</span>';
        });
        $('#msgTokenList').html(html);
    }

    // Click token badge → insert at cursor
    $(document).on('click', '.msg-token-badge', function() {
        var token = $(this).data('token');
        var ch    = $('#msgChannel').val();
        if (ch === 'Email' && quillEditor) {
            var range = quillEditor.getSelection(true);
            quillEditor.insertText(range ? range.index : quillEditor.getLength(), token);
            updateMsgPreview();
        } else {
            var ta    = document.getElementById('msgBodyPlain');
            var start = ta.selectionStart;
            var end   = ta.selectionEnd;
            ta.value  = ta.value.substring(0, start) + token + ta.value.substring(end);
            ta.selectionStart = ta.selectionEnd = start + token.length;
            ta.focus();
            updateMsgPreview();
        }
    });

    function updateMsgPreview() {
        var body    = getMsgBody();
        var channel = $('#msgChannel').val();
        var samples = {
            '{{PARTY_NAME}}'     : 'Venkatesh Paalapattu',
            '{{DOC_NUMBER}}'     : 'INV-0042',
            '{{DOC_DATE}}'       : '01 May 2026',
            '{{DOC_TYPE}}'       : 'Invoice',
            '{{AMOUNT}}'         : '₹ 1,200.00',
            '{{CURRENCY}}'       : '₹',
            '{{RECEIPT_NUMBER}}' : 'PREC-3259',
            '{{PAYMENT_MODE}}'   : 'UPI',
            '{{PAYMENT_STATUS}}' : 'Paid',
            '{{RECEIPT_LINK}}'   : 'https://yourdomain.com/receipt/aB3xK9mNpQ',
            '{{ORG_NAME}}'       : 'R2K Automobiles',
            '{{ORG_PHONE}}'      : '9789612478',
            '{{ORG_EMAIL}}'      : 'info@r2k.com',
            '{{ORG_ADDRESS}}'    : 'Chennai, Tamil Nadu',
            '{{ORG_GSTIN}}'      : '33AABCR1234F1Z5',
            '{{VALID_UNTIL}}'    : '15 May 2026',
            '{{BALANCE_AMOUNT}}' : '₹ 500.00',
        };
        var preview = body;
        $.each(samples, function(k, v) { preview = preview.split(k).join(v); });

        if (channel === 'Email') {
            var subj = $('#msgSubject').val();
            $.each(samples, function(k, v) { subj = subj.split(k).join(v); });
            $('#msgPreviewBox').html(
                '<div style="font-size:.78rem;color:#888;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #eee;"><strong>Subject:</strong> ' + $('<span>').text(subj).html() + '</div>'
                + '<div style="font-size:.85rem;">' + preview + '</div>'
            );
        } else {
            $('#msgPreviewBox').html('<div style="white-space:pre-wrap;font-size:.85rem;">' + $('<span>').text(preview).html() + '</div>');
        }
    }

    $('#msgBodyPlain, #msgSubject').on('input', updateMsgPreview);

    $('#msgChannel').on('change', function() {
        var ch = $(this).val();
        $('#msgSubjectWrap').toggleClass('d-none', ch !== 'Email');
        switchBodyEditor(ch);
        buildTokenBadges(ch);
        updateMsgPreview();
    });

    $(document).on('click', '#btnAddMsgTemplate', function() {
        resetMsgForm();
        $('#msgTemplateModalTitle').text('Add Message Template');
        $('#msgTemplateModal').modal('show');
    });

    $(document).on('click', '.AddMsgTemplate', function() {
        resetMsgForm();
        $('#msgModuleUID').val($(this).data('module-uid'));
        var ch = $(this).data('channel');
        $('#msgChannel').val(ch).trigger('change');
        $('#msgTemplateModalTitle').text('Add Template — ' + $(this).data('module-name') + ' / ' + ch);
        $('#msgTemplateModal').modal('show');
    });

    $(document).on('click', '.EditMsgTemplate', function() {
        var uid = $(this).data('uid');
        resetMsgForm();
        $('#msgTemplateModalTitle').text('Edit Template');
        $('#msgModuleUID, #msgChannel, #msgSubject').prop('disabled', true);
        $('#msgBodyPlain').prop('disabled', true);
        $('#btnSaveMsgTemplate').prop('disabled', true);
        $('#msgTemplateModal').modal('show');
        AjaxLoading = 0;
        $.ajax({
            url   : '/settings/getMsgTemplateDetail',
            method: 'GET',
            data  : { TemplateUID: uid },
            success: function(resp) {
                AjaxLoading = 1;
                $('#msgModuleUID, #msgChannel, #msgSubject').prop('disabled', false);
                $('#msgBodyPlain').prop('disabled', false);
                $('#btnSaveMsgTemplate').prop('disabled', false);
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                    $('#msgTemplateModal').modal('hide');
                    return;
                }
                var d = resp.Data;
                $('#msgTemplateUID').val(d.TemplateUID);
                $('#msgModuleUID').val(d.ModuleUID);
                $('#msgChannel').val(d.Channel).trigger('change');
                $('#msgSubject').val(d.Subject || '');
                $('#msgTemplateModalTitle').text('Edit Template ' + String.fromCharCode(8212) + ' ' + (d.ModuleName || '') + ' / ' + d.Channel);
                $('#msgTemplateModalTitle').text('Edit Template â€” ' + (d.ModuleName || '') + ' / ' + d.Channel);
                if(d.Channel === 'Email') {
                    setMsgBody('Email', d.Body || '');
                } else {
                    $('#msgBodyPlain').val(d.Body || '');
                }
                updateMsgPreview();
            },
            error: function() {
                AjaxLoading = 1;
                $('#msgModuleUID, #msgChannel, #msgSubject').prop('disabled', false);
                $('#msgBodyPlain').prop('disabled', false);
                $('#btnSaveMsgTemplate').prop('disabled', false);
                Swal.fire({ icon: 'error', text: 'Failed to load template.' });
                $('#msgTemplateModal').modal('hide');
            }
        });

    });

    function resetMsgForm() {
        _pendingEditBody = null;
        $('#msgTemplateUID').val(0);
        $('#msgModuleUID').val('');
        $('#msgSubject').val('');
        $('#msgBodyPlain').val('');
        if (quillEditor) quillEditor.root.innerHTML = '';
        $('#msgBody').val('');
        $('#msgPreviewBox').html('<span class="text-muted fst-italic">Preview will appear here as you type...</span>');
        $('#msgChannel').val('WhatsApp').trigger('change');
    }

    $('#btnSaveMsgTemplate').on('click', function() {
        var body = getMsgBody();
        if (!body.trim() || body === '<p><br></p>') {
            Swal.fire({ icon: 'warning', text: 'Template body is required.' });
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        AjaxLoading = 0;
        $.ajax({
            url: '/settings/saveMsgTemplate', method: 'POST',
            data: {
                TemplateUID : $('#msgTemplateUID').val(),
                ModuleUID   : $('#msgModuleUID').val(),
                Channel     : $('#msgChannel').val(),
                Subject     : $('#msgSubject').val(),
                Body        : body,
                [CsrfName]  : CsrfToken
            },
            success: function(resp) {
                AjaxLoading = 1;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Template');
                if (!resp.Error) {
                    $('#MsgTemplateBody').html(resp.RecordHtmlData);
                    $('#msgTemplateModal').modal('hide');
                    Swal.fire({ icon:'success', text: resp.Message, timer:1500, showConfirmButton:false });
                } else {
                    Swal.fire({ icon:'error', text: resp.Message });
                }
            },
            error: function() {
                AjaxLoading = 1;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Template');
                Swal.fire({ icon:'error', text:'Server error.' });
            }
        });
    });

    $(document).on('click', '.DeleteMsgTemplate', function() {
        var uid   = $(this).data('uid');
        var label = $(this).data('label');
        Swal.fire({
            title: 'Delete Template?',
            text : 'Delete "' + label + '" template?',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33'
        }).then(function(r) {
            if (!r.isConfirmed) return;
            AjaxLoading = 0;
            $.ajax({
                url: '/settings/deleteMsgTemplate', method: 'POST',
                data: { TemplateUID: uid, [CsrfName]: CsrfToken },
                success: function(resp) {
                    AjaxLoading = 1;
                    if (!resp.Error) {
                        msgTplLoaded = false;
                        loadMsgTemplates();
                        Swal.fire({ icon:'success', text: resp.Message, timer:1500, showConfirmButton:false });
                    } else {
                        Swal.fire({ icon:'error', text: resp.Message });
                    }
                }, error: function() { AjaxLoading = 1; }
            });
        });
    });

});
</script>