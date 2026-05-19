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

                    <!-- ── Page Header ── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#e0f2fe;">
                                <i class="bx bx-cog" style="color:#0284c7;"></i>
                            </div>
                            <h5 class="trans-ph-title">Settings</h5>
                        </div>
                    </div>

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

                        </div>
                        <!-- / Top Tab Content -->

                    </div>

                </div>
            </div>
            <!-- / Content wrapper -->

            <!-- Modals for thermal/banks/msgtemplates are on their own pages -->

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

</style>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function() {
    'use strict';
    $(document).on('click', '.gs-save-btn', function() {
        Swal.fire({ icon:'success', title:'Saved!', text:'Settings have been saved successfully.', timer:1800, showConfirmButton:false });
    });
});
</script>