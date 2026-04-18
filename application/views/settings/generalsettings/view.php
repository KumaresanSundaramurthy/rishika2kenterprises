<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
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
                                        <i class="bx bx-cog me-1"></i> General Settings
                                    </a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link gs-top-link" id="toptab-transaction-settings-tab"
                                        data-bs-toggle="tab" data-bs-target="#toptab-transaction-settings"
                                        role="tab" aria-controls="toptab-transaction-settings" aria-selected="false"
                                        href="javascript:void(0);">
                                        <i class="bx bx-transfer me-1"></i> Transaction Settings
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

                            </ul>
                        </div>
                        <!-- / Top Tabs Header -->

                        <!-- TOP TAB CONTENT -->
                        <div class="tab-content" id="gsTopTabContent">

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
                                                <p class="text-muted small mb-4">Manage invoice numbering, defaults, and display preferences.</p>

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
                                    <span class="text-muted small">One configuration per transaction type. Max <?php echo count(['Quotation','Invoice','SalesOrder','PurchaseOrder','Purchase','SalesReturn','PurchaseReturn','CreditNote','DebitNote']); ?> records.</span>
                                    <button class="btn btn-primary btn-sm px-3" id="btnAddThermalConfig">
                                        <i class="bx bx-plus me-1"></i>Add Config
                                    </button>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive text-nowrap tablecard">
                                    <table class="table table-hover" id="ThermalConfigTable">
                                        <thead class="bg-body-tertiary">
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
                                        <tbody id="ThermalConfigBody" class="table-border-bottom-0">
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
                                <h5 class="modal-title mb-0" id="thermalModalTitle">Thermal Print Settings</h5>
                                <small class="text-muted" id="thermalModalSubtitle">Add a new configuration</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                        <option value="Quotation">Quotation</option>
                                        <option value="Invoice">Invoice</option>
                                        <option value="SalesOrder">Sales Order</option>
                                        <option value="PurchaseOrder">Purchase Order</option>
                                        <option value="Purchase">Purchase</option>
                                        <option value="SalesReturn">Sales Return</option>
                                        <option value="PurchaseReturn">Purchase Return</option>
                                        <option value="CreditNote">Credit Note</option>
                                        <option value="DebitNote">Debit Note</option>
                                    </select>
                                </div>

                                <!-- ── Receipt Elements (2-column grid) ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Receipt Elements</div>
                                <div class="row g-0 border-top border-bottom">

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Terms</div><div class="thermal-setting-desc">Print terms &amp; conditions on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTerms"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Company Details</div><div class="thermal-setting-desc">Include company's details on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowCompanyDetails" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Item Description</div><div class="thermal-setting-desc">Print detailed product descriptions.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowItemDescription"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Taxable Amount</div><div class="thermal-setting-desc">Display taxable amount above tax line.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTaxableAmount"></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Show Item HSN/SAC</div><div class="thermal-setting-desc">Show the HSN/SAC code on the receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowHSN" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Tax Breakdown</div><div class="thermal-setting-desc">Show CGST / SGST / IGST split.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowTaxBreakdown" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Show GSTIN</div><div class="thermal-setting-desc">Display company GSTIN on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowGSTIN" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Show Mobile</div><div class="thermal-setting-desc">Display company mobile on receipt.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowMobile" checked></div>
                                        </div>
                                    </div>

                                    <div class="col-6 thermal-toggle-cell border-end">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Show Cash Received</div><div class="thermal-setting-desc">Display amount received from customer.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowCashReceived" checked></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Company Logo</div><div class="thermal-setting-desc">(B&amp;W recommended) Logo on receipt.</div></div>
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
                                            <div><div class="thermal-setting-label">Google Reviews QR</div><div class="thermal-setting-desc">Print Google Reviews QR for customer feedback.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowGoogleReviewQR"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 thermal-toggle-cell">
                                        <div class="d-flex justify-content-between align-items-start gap-2 p-3">
                                            <div><div class="thermal-setting-label">Payment QR</div><div class="thermal-setting-desc">Show UPI/payment QR on thermal printout.</div></div>
                                            <div class="form-check form-switch mb-0 mt-1 flex-shrink-0"><input class="form-check-input thermal-switch" type="checkbox" role="switch" id="ThermalShowPaymentQR" checked></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── Branding & Printer Setup ── -->
                                <div class="thermal-section-header px-3 pt-3 pb-1">Branding &amp; Printer Setup</div>
                                <div class="row g-0 border-top">
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Org Name Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalOrgNameFontSize" value="22" min="8" max="40" />
                                    </div>
                                    <div class="col-3 p-3 border-end">
                                        <label class="form-label small fw-semibold mb-1">Address / Phone / GSTIN Font Size</label>
                                        <input type="number" class="form-control form-control-sm text-center" id="ThermalCompanyNameFontSize" value="18" min="8" max="40" />
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
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveThermalConfigBtn">
                                <i class="bx bx-save me-1"></i>Save Config
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Thermal Print Config Modal -->

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
</style>

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
    var thermalUsedTypes = [];
    var thermalLoaded    = false;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Live preview builder ───────────────────────────────────────────────
    function updateThermalPreview() {
        var transType    = $('#ThermalTransType option:selected').text().trim() || 'Transaction';
        var footerMsg    = $('#ThermalFooterMessage').val().trim() || 'Thank you for your business!';
        var paperWidth   = $('#ThermalPaperWidthSelect').val() || '80mm';
        var orgFontSize  = Math.max(10, Math.min(28, parseInt($('#ThermalOrgNameFontSize').val()) || 22));
        var coFontSize   = Math.max(9,  Math.min(22, parseInt($('#ThermalCompanyNameFontSize').val()) || 18));
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
        $('#ThermalConfigBody').html(
            '<tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>'
        );
        $.ajax({
            url: '/settings/getThermalConfigList', method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (!resp.Error) {
                    thermalUsedTypes = resp.UsedTypes || [];
                    thermalLoaded    = true;
                    $('#ThermalConfigBody').html(resp.RecordHtmlData);
                    updateAddBtnState();
                    $('[data-bs-toggle="tooltip"]').each(function() {
                        var tt = bootstrap.Tooltip.getInstance(this); if (tt) tt.dispose();
                        new bootstrap.Tooltip(this);
                    });
                }
            }
        });
    }

    function updateAddBtnState() {
        var allTypes = ['Quotation','Invoice','SalesOrder','PurchaseOrder','Purchase','SalesReturn','PurchaseReturn','CreditNote','DebitNote'];
        var allUsed  = allTypes.every(function(t) { return thermalUsedTypes.indexOf(t) !== -1; });
        $('#btnAddThermalConfig').prop('disabled', allUsed).attr('title', allUsed ? 'All transaction types are already configured.' : '');
    }

    $('#toptab-thermal-config-tab').on('shown.bs.tab', function() {
        if (!thermalLoaded) loadThermalConfigList();
    });

    // ── Add ────────────────────────────────────────────────────────────────
    $('#btnAddThermalConfig').on('click', function() {
        resetThermalModal();
        $('#ThermalTransType option').each(function() {
            var v = $(this).val();
            if (v && thermalUsedTypes.indexOf(v) !== -1) { $(this).prop('disabled', true); }
            else { $(this).prop('disabled', false); }
        });
        $('#thermalConfigModal').modal('show');
    });

    // ── Edit ───────────────────────────────────────────────────────────────
    var typeLabels = { Quotation:'Quotation', Invoice:'Invoice', SalesOrder:'Sales Order', PurchaseOrder:'Purchase Order',
        Purchase:'Purchase', SalesReturn:'Sales Return', PurchaseReturn:'Purchase Return', CreditNote:'Credit Note', DebitNote:'Debit Note' };

    $(document).on('click', '.EditThermalConfig', function() {
        var cfg = $(this).data('config');
        if (!cfg) return;

        resetThermalModal();
        $('#thermalModalSubtitle').text('Editing: ' + (typeLabels[cfg.TransactionType] || cfg.TransactionType));
        $('#HThermalConfigUID').val(cfg.ThermalConfigUID);
        $('#ThermalTransType').val(cfg.TransactionType).prop('disabled', true);
        $('#thermalTransTypeRow').hide();
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
        $('#ThermalOrgNameFontSize').val(cfg.OrgNameFontSize       || 22);
        $('#ThermalCompanyNameFontSize').val(cfg.CompanyNameFontSize || 18);
        $('#ThermalProductInfoFontSize').val(cfg.ProductInfoFontSize || 12);
        $('#thermalConfigModal').modal('show');
    });

    // ── Save ───────────────────────────────────────────────────────────────
    $('#saveThermalConfigBtn').on('click', function() {
        var $btn = $(this), configUID = parseInt($('#HThermalConfigUID').val()) || 0, transType = $('#ThermalTransType').val();
        if (!configUID && !transType) {
            $('.thermalFormAlert .alert-message').text('Please select a transaction type.');
            $('.thermalFormAlert').removeClass('d-none'); return;
        }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $('.thermalFormAlert').addClass('d-none');
        $.ajax({
            url: '/settings/saveThermalConfig', method: 'POST',
            data: {
                ThermalConfigUID    : configUID,
                TransactionType     : transType,
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
                OrgNameFontSize     : $('#ThermalOrgNameFontSize').val()     || 22,
                CompanyNameFontSize : $('#ThermalCompanyNameFontSize').val() || 18,
                ProductInfoFontSize : $('#ThermalProductInfoFontSize').val() || 12,
                [CsrfName]          : CsrfToken,
            },
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Config');
                if (resp.Error) {
                    $('.thermalFormAlert .alert-message').text(resp.Message);
                    $('.thermalFormAlert').removeClass('d-none');
                } else {
                    $('#thermalConfigModal').modal('hide');
                    thermalLoaded = false; loadThermalConfigList();
                    Swal.fire({ icon:'success', title:'Saved', text:resp.Message, timer:1500, showConfirmButton:false });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Config');
                Swal.fire({ icon:'error', text:'Server error. Please try again.' });
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
        $('#thermalModalTitle').text('Thermal Print Settings');
        $('#thermalModalSubtitle').text('Add a new configuration');
        $('#ThermalPaperWidthSelect').val('80mm');
        $('#ThermalFooterMessage').val('');
        $('#ThermalShowTerms,#ThermalShowItemDescription,#ThermalShowTaxableAmount,#ThermalShowLogo,#ThermalShowGoogleReviewQR').prop('checked', false);
        $('#ThermalShowCompanyDetails,#ThermalShowHSN,#ThermalShowTaxBreakdown,#ThermalShowGSTIN,#ThermalShowMobile,#ThermalShowCashReceived,#ThermalShowPaymentQR').prop('checked', true);
        $('#ThermalOrgNameFontSize').val(22);
        $('#ThermalCompanyNameFontSize').val(18);
        $('#ThermalProductInfoFontSize').val(12);
        $('.thermalFormAlert').addClass('d-none');
        updateThermalPreview();
    }

    $('#thermalConfigModal').on('hidden.bs.modal', function() { resetThermalModal(); });

});
</script>
