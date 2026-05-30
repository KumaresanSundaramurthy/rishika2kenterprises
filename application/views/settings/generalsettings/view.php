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
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Settings'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
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

                                            <a class="nav-link gs-tab-link px-4 py-3" id="tab-product-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-product"
                                                role="tab" aria-controls="tab-product" aria-selected="false"
                                                href="javascript:void(0);">
                                                <i class="bx bx-box me-2"></i>Product Settings
                                            </a>

                                        </div>
                                    </div>
                                    <!-- / Left Side -->

                                    <!-- Right Side: Sub-Tab Content -->
                                    <div class="col-md-9">
                                        <div class="tab-content p-4" id="genSettingsTabContent">

                                            <!-- Sub-Tab 1: General -->
                                            <?php
                                            $gs              = $GenSettings ?? new stdClass();
                                            $gsCurrency      = htmlspecialchars($gs->CurrenySymbol   ?? '₹');
                                            $gsDecimal       = (int)($gs->DecimalPoints  ?? 2);
                                            $gsSerial        = !empty($gs->SerialNoDisplay);
                                            $gsRowLimit      = (int)($gs->RowLimit       ?? 10);
                                            $gsFYMonth       = (int)($gs->FYStartMonth   ?? 4);
                                            $gsEnableStorage = !empty($gs->EnableStorage);
                                            $gsMandatory     = !empty($gs->MandatoryStorage);
                                            $gsQtyMax        = (int)($gs->QtyMaxLength   ?? 6);
                                            $gsPriceMax      = (int)($gs->PriceMaxLength ?? 12);
                                            ?>
                                            <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-tab">

                                                <h6 class="fw-semibold mb-1">General Preferences</h6>
                                                <p class="text-muted small mb-4">Configure your application's basic display and regional preferences.</p>

                                                <!-- ── Display & Regional ── -->
                                                <p class="text-muted fw-semibold mb-2" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Display &amp; Regional</p>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Currency Symbol <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="gs_CurrenySymbol" name="CurrenySymbol"
                                                               placeholder="e.g. ₹" value="<?php echo $gsCurrency; ?>" maxlength="1" />
                                                        <div class="form-text">Single character representing your country's currency (e.g. ₹ India, $ USA, € Europe).</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Decimal Places</label>
                                                        <select class="form-select" id="gs_DecimalPoints" name="DecimalPoints">
                                                            <option value="0" <?php echo $gsDecimal === 0 ? 'selected' : ''; ?>>0 — No decimals (e.g. 100)</option>
                                                            <option value="2" <?php echo $gsDecimal === 2 ? 'selected' : ''; ?>>2 — Standard (e.g. 100.00)</option>
                                                            <option value="3" <?php echo $gsDecimal === 3 ? 'selected' : ''; ?>>3 — High precision (e.g. 100.000)</option>
                                                        </select>
                                                        <div class="form-text">Controls how prices, quantities, and totals are displayed throughout the app.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Rows Per Page</label>
                                                        <select class="form-select" id="gs_RowLimit" name="RowLimit">
                                                            <option value="10"  <?php echo $gsRowLimit === 10  ? 'selected' : ''; ?>>10</option>
                                                            <option value="25"  <?php echo $gsRowLimit === 25  ? 'selected' : ''; ?>>25</option>
                                                            <option value="50"  <?php echo $gsRowLimit === 50  ? 'selected' : ''; ?>>50</option>
                                                            <option value="100" <?php echo $gsRowLimit === 100 ? 'selected' : ''; ?>>100</option>
                                                        </select>
                                                        <div class="form-text">Default number of records shown per page in all list/table views.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Financial Year Start Month</label>
                                                        <select class="form-select" id="gs_FYStartMonth" name="FYStartMonth">
                                                            <?php
                                                            $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                                                                       7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                                                            foreach ($months as $num => $name):
                                                            ?>
                                                            <option value="<?php echo $num; ?>" <?php echo $gsFYMonth === $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Transaction sequence numbers (INV-001, PO-001 etc.) reset to 1 when the new FY begins.</div>
                                                    </div>
                                                </div>

                                                <!-- ── Input Limits ── -->
                                                <p class="text-muted fw-semibold mb-2" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Input Limits</p>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Quantity Max Length</label>
                                                        <input type="number" class="form-control" id="gs_QtyMaxLength" name="QtyMaxLength"
                                                               min="1" max="15" value="<?php echo $gsQtyMax; ?>" />
                                                        <div class="form-text">Maximum number of digits allowed when entering a product quantity (e.g. 6 allows up to 999999). Includes decimal digits.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Price Max Length</label>
                                                        <input type="number" class="form-control" id="gs_PriceMaxLength" name="PriceMaxLength"
                                                               min="1" max="20" value="<?php echo $gsPriceMax; ?>" />
                                                        <div class="form-text">Maximum number of digits allowed when entering a price or amount (e.g. 12 allows up to ₹99,99,99,999.99). Includes decimal digits.</div>
                                                    </div>
                                                </div>

                                                <!-- ── Toggles ── -->
                                                <p class="text-muted fw-semibold mb-2" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Features</p>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                   id="gs_SerialNoDisplay" name="SerialNoDisplay"
                                                                   <?php echo $gsSerial ? 'checked' : ''; ?>>
                                                            <label class="form-check-label fw-semibold" for="gs_SerialNoDisplay">Show Serial Number in Lists</label>
                                                        </div>
                                                        <div class="form-text ms-4 ps-2">Displays a row number (#1, #2…) as the first column in all list and table views for easy reference.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                   id="gs_EnableStorage" name="EnableStorage"
                                                                   <?php echo $gsEnableStorage ? 'checked' : ''; ?>>
                                                            <label class="form-check-label fw-semibold" for="gs_EnableStorage">Enable Storage / Warehouse</label>
                                                        </div>
                                                        <div class="form-text ms-4 ps-2">Activates warehouse or storage location tracking. When enabled, products can be assigned to specific storage locations and transactions can specify the source/destination store.</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                   id="gs_MandatoryStorage" name="MandatoryStorage"
                                                                   <?php echo $gsMandatory ? 'checked' : ''; ?>>
                                                            <label class="form-check-label fw-semibold" for="gs_MandatoryStorage">Make Storage Selection Mandatory</label>
                                                        </div>
                                                        <div class="form-text ms-4 ps-2">When enabled, users must select a storage location before saving a transaction. Requires "Enable Storage" to be turned on. Ensures every stock movement is traceable to a specific location.</div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary" id="btnSaveGeneralSettings">
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="gsSpinner"></span>
                                                        Save Changes
                                                    </button>
                                                </div>
                                                <div id="gsAlert" class="mt-3 d-none"></div>

                                            </div>
                                            <!-- / Sub-Tab 1 -->

                                            <!-- Sub-Tab 2: Product Settings -->
                                            <?php
                                            $ps              = $ProdSettings   ?? new stdClass();
                                            $psProductType   = (int)($ps->DefaultProductTypeUID  ?? 0);
                                            $psDiscountType  = (int)($ps->DefaultDiscountTypeUID ?? 0);
                                            $psProductTax    = (int)($ps->DefaultProductTaxUID   ?? 0);
                                            $psTaxDetail     = (int)($ps->DefaultTaxDetailUID    ?? 0);
                                            ?>
                                            <div class="tab-pane fade" id="tab-product" role="tabpanel" aria-labelledby="tab-product-tab">

                                                <h6 class="fw-semibold mb-1">Product Settings</h6>
                                                <p class="text-muted small mb-4">Set the default selections that pre-fill the product creation form. Users can override these per product.</p>

                                                <div class="row g-3">

                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Product Type</label>
                                                        <select class="form-select" id="ps_ProductType" name="DefaultProductTypeUID">
                                                            <option value="">— Select —</option>
                                                            <?php foreach ($ProdTypeInfo as $pt): ?>
                                                            <option value="<?php echo (int)$pt->ProductTypeUID; ?>"
                                                                <?php echo $psProductType === (int)$pt->ProductTypeUID ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($pt->Name); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Whether a new product defaults to a physical "Product" or an intangible "Service". This controls whether stock tracking and inventory movement apply to the item.</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Discount Type</label>
                                                        <select class="form-select" id="ps_DiscountType" name="DefaultDiscountTypeUID">
                                                            <option value="">— Select —</option>
                                                            <?php foreach ($DiscTypeInfo as $dt): ?>
                                                            <option value="<?php echo (int)$dt->DiscountTypeUID; ?>"
                                                                <?php echo $psDiscountType === (int)$dt->DiscountTypeUID ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($dt->Name); ?> (<?php echo htmlspecialchars($dt->Symbol); ?>)
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">The discount calculation method pre-selected on the product form — Percentage (%) deducts a share of the price; Amount (₹) deducts a fixed value.</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Product Tax Type</label>
                                                        <select class="form-select" id="ps_ProductTax" name="DefaultProductTaxUID">
                                                            <option value="">— Select —</option>
                                                            <?php foreach ($ProdTaxInfo as $ptx): ?>
                                                            <option value="<?php echo (int)$ptx->ProductTaxUID; ?>"
                                                                <?php echo $psProductTax === (int)$ptx->ProductTaxUID ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($ptx->Name); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Controls how the selling price is interpreted — "With Tax" means the entered price includes GST; "Without Tax" means GST is added on top during invoicing.</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Default Tax Percentage</label>
                                                        <select class="form-select" id="ps_TaxDetail" name="DefaultTaxDetailUID">
                                                            <option value="">— Select —</option>
                                                            <?php foreach ($TaxDetInfo as $td): ?>
                                                            <option value="<?php echo (int)$td->TaxDetailsUID; ?>"
                                                                <?php echo $psTaxDetail === (int)$td->TaxDetailsUID ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars(smartDecimal($td->Percentage)); ?>% — <?php echo htmlspecialchars($td->TaxName); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">The GST slab pre-selected when creating a new product (e.g. 0%, 5%, 12%, 18%, 28%). The split into CGST+SGST or IGST is determined automatically based on the transaction's place of supply.</div>
                                                    </div>

                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary" id="btnSaveProductSettings">
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="psSpinner"></span>
                                                        Save Changes
                                                    </button>
                                                </div>
                                                <div id="psAlert" class="mt-3 d-none"></div>

                                            </div>
                                            <!-- / Sub-Tab 4: Product Settings -->

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
.gs-nav-pills .gs-tab-link { border-radius: 0; color: var(--bs-body-color); font-size: 0.875rem; border-left: 3px solid transparent; transition: background 0.15s, border-color 0.15s; text-align: left; justify-content: flex-start; }
.gs-nav-pills .gs-tab-link:hover  { background-color: var(--bs-gray-100); }
.gs-nav-pills .gs-tab-link.active { background-color: rgba(var(--bs-primary-rgb), 0.08); color: var(--bs-primary); border-left-color: var(--bs-primary); font-weight: 600; }

</style>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

$(document).ready(function () {
    'use strict';

    $('#btnSaveGeneralSettings').on('click', function () {
        var $btn     = $(this);
        var $spinner = $('#gsSpinner');
        var $alert   = $('#gsAlert');

        // Collect values
        var currency  = $('#gs_CurrenySymbol').val().trim();
        if (!currency) {
            $alert.removeClass('d-none alert-success').addClass('alert alert-danger').text('Currency symbol is required.');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $alert.addClass('d-none');

        $.ajax({
            url    : '/settings/updateGeneralSettings',
            method : 'POST',
            data   : {
                CurrenySymbol  : currency,
                DecimalPoints  : $('#gs_DecimalPoints').val(),
                RowLimit       : $('#gs_RowLimit').val(),
                FYStartMonth   : $('#gs_FYStartMonth').val(),
                SerialNoDisplay: $('#gs_SerialNoDisplay').is(':checked') ? 1 : 0,
                [CsrfName]     : CsrfToken,
            },
            success: function (resp) {
                if (resp.Error) {
                    $alert.removeClass('d-none alert-success').addClass('alert alert-danger').text(resp.Message);
                } else {
                    $alert.removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.Message);
                }
            },
            error: function () {
                $alert.removeClass('d-none alert-success').addClass('alert alert-danger').text('Request failed. Please try again.');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    // ── Product Settings save ─────────────────────────────────────────────────
    $('#btnSaveProductSettings').on('click', function () {
        var $btn     = $(this);
        var $spinner = $('#psSpinner');
        var $alert   = $('#psAlert');

        if (!$('#ps_ProductType').val() || !$('#ps_DiscountType').val() ||
            !$('#ps_ProductTax').val()  || !$('#ps_TaxDetail').val()) {
            $alert.removeClass('d-none alert-success').addClass('alert alert-danger').text('Please select all four fields.');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $alert.addClass('d-none');

        $.ajax({
            url    : '/settings/updateProductSettings',
            method : 'POST',
            data   : {
                DefaultProductTypeUID  : $('#ps_ProductType').val(),
                DefaultDiscountTypeUID : $('#ps_DiscountType').val(),
                DefaultProductTaxUID   : $('#ps_ProductTax').val(),
                DefaultTaxDetailUID    : $('#ps_TaxDetail').val(),
                [CsrfName]             : CsrfToken,
            },
            success: function (resp) {
                if (resp.Error) {
                    $alert.removeClass('d-none alert-success').addClass('alert alert-danger').text(resp.Message);
                } else {
                    $alert.removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.Message);
                }
            },
            error: function () {
                $alert.removeClass('d-none alert-success').addClass('alert alert-danger').text('Request failed. Please try again.');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

});
</script>