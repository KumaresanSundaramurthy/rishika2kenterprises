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
                                                <i class="bx bx-box me-2"></i>Product
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

                                                <!-- ── Date & DateTime Formats ── -->
                                                <?php
                                                $formDateFormat     = $gs->FormDateFormat      ?? 'd-m-Y';
                                                $listDateFormat     = $gs->ListDateFormat      ?? 'd-m-Y';
                                                $printDateFormat    = $gs->PrintDateFormat     ?? 'd-m-Y';
                                                $formDtFormat       = $gs->FormDateTimeFormat  ?? 'd-m-Y H:i';
                                                $listDtFormat       = $gs->ListDateTimeFormat  ?? 'd-m-Y H:i';
                                                $printDtFormat      = $gs->PrintDateTimeFormat ?? 'd-m-Y H:i';
                                                $dateFormatOptions = [
                                                    'd-m-Y' => '01-06-2026  (DD-MM-YYYY)',
                                                    'd/m/Y' => '01/06/2026  (DD/MM/YYYY)',
                                                    'Y-m-d' => '2026-06-01  (YYYY-MM-DD)',
                                                    'Y/m/d' => '2026/06/01  (YYYY/MM/DD)',
                                                    'd.m.Y' => '01.06.2026  (DD.MM.YYYY)',
                                                    'm/d/Y' => '06/01/2026  (MM/DD/YYYY)',
                                                    'd M Y' => '01 Jun 2026 (DD Mon YYYY)',
                                                ];
                                                $dtFormatOptions = [
                                                    'd-m-Y H:i'   => '01-06-2026 14:30  (24hr)',
                                                    'd/m/Y H:i'   => '01/06/2026 14:30  (24hr)',
                                                    'Y-m-d H:i'   => '2026-06-01 14:30  (24hr)',
                                                    'd M Y H:i'   => '01 Jun 2026 14:30 (24hr)',
                                                    'd-m-Y h:i A' => '01-06-2026 02:30 PM (12hr)',
                                                    'd/m/Y h:i A' => '01/06/2026 02:30 PM (12hr)',
                                                    'Y-m-d h:i A' => '2026-06-01 02:30 PM (12hr)',
                                                    'd M Y h:i A' => '01 Jun 2026 02:30 PM (12hr)',
                                                ];
                                                ?>
                                                <p class="text-muted fw-semibold mb-2 mt-1" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Date Formats</p>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Form Page</label>
                                                        <select class="form-select" id="FormDateFormat" name="FormDateFormat">
                                                            <?php foreach ($dateFormatOptions as $val => $lbl): ?>
                                                            <option value="<?php echo $val; ?>" <?php echo $formDateFormat === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Used in Create / Edit form date pickers.</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">List Page</label>
                                                        <select class="form-select" id="ListDateFormat" name="ListDateFormat">
                                                            <?php foreach ($dateFormatOptions as $val => $lbl): ?>
                                                            <option value="<?php echo $val; ?>" <?php echo $listDateFormat === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Used in list page date columns.</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Print / PDF</label>
                                                        <select class="form-select" id="PrintDateFormat" name="PrintDateFormat">
                                                            <?php foreach ($dateFormatOptions as $val => $lbl): ?>
                                                            <option value="<?php echo $val; ?>" <?php echo $printDateFormat === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Used in print templates &amp; PDF exports.</div>
                                                    </div>
                                                </div>

                                                <p class="text-muted fw-semibold mb-2" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Date &amp; Time Formats</p>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Form Page</label>
                                                        <select class="form-select" id="FormDateTimeFormat" name="FormDateTimeFormat">
                                                            <?php foreach ($dtFormatOptions as $val => $lbl): ?>
                                                            <option value="<?php echo $val; ?>" <?php echo $formDtFormat === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Used for datetime fields in forms (e.g. created on).</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">List Page</label>
                                                        <select class="form-select" id="ListDateTimeFormat" name="ListDateTimeFormat">
                                                            <?php foreach ($dtFormatOptions as $val => $lbl): ?>
                                                            <option value="<?php echo $val; ?>" <?php echo $listDtFormat === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Used for datetime columns in list pages.</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Print / PDF</label>
                                                        <select class="form-select" id="PrintDateTimeFormat" name="PrintDateTimeFormat">
                                                            <?php foreach ($dtFormatOptions as $val => $lbl): ?>
                                                            <option value="<?php echo $val; ?>" <?php echo $printDtFormat === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Used for datetime in print templates &amp; PDFs.</div>
                                                    </div>
                                                </div>

                                                <!-- ── Input Limits ── -->
                                                <?php $gsMaxShip = (int)($gs->MaxShippingAddr ?? 3); ?>
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
                                                    <div class="col-md-6">
                                                        <label class="form-label">Max Shipping Addresses</label>
                                                        <input type="number" class="form-control" id="gs_MaxShippingAddr" name="MaxShippingAddr"
                                                               min="1" max="5" value="<?php echo $gsMaxShip; ?>" />
                                                        <div class="form-text">Maximum number of shipping addresses allowed per organisation (1–5).</div>
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

                                            <a class="nav-link gs-tab-link active px-4 py-3" id="tab-txn-general-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-txn-general"
                                                role="tab" aria-controls="tab-txn-general" aria-selected="true"
                                                href="javascript:void(0);">
                                                <i class="bx bx-slider-alt me-2"></i>General
                                            </a>

                                            <a class="nav-link gs-tab-link px-4 py-3" id="tab-invoice-settings-tab"
                                                data-bs-toggle="pill" data-bs-target="#tab-invoice-settings"
                                                role="tab" aria-controls="tab-invoice-settings" aria-selected="false"
                                                href="javascript:void(0);">
                                                <i class="bx bx-receipt me-2"></i>Invoice
                                            </a>

                                        </div>
                                    </div>
                                    <!-- / Left Side -->

                                    <!-- Right Side: Sub-Tab Content -->
                                    <div class="col-md-9">
                                        <div class="tab-content p-4" id="txnSettingsTabContent">

                                            <!-- Sub-Tab: General (T&C) -->
                                            <?php
                                            $tgs = $TransGenSettings ?? new stdClass();
                                            $tgsTerms       = htmlspecialchars($tgs->TermsAndConditions ?? '', ENT_QUOTES);
                                            $tgsHideNav     = !empty($tgs->HideNavOnTransForm) ? (int)$tgs->HideNavOnTransForm : 0;
                                            ?>
                                            <div class="tab-pane fade show active" id="tab-txn-general" role="tabpanel" aria-labelledby="tab-txn-general-tab">

                                                <h6 class="fw-semibold mb-1">Transaction General Settings</h6>
                                                <p class="text-muted small mb-4">Configure default content that applies across all transaction types.</p>

                                                <div class="row g-3">

                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Transaction Form Navigation</label>
                                                        <div class="d-flex align-items-center gap-3 p-3 border rounded">
                                                            <div class="form-check form-switch mb-0">
                                                                <input class="form-check-input" type="checkbox"
                                                                       id="txn_HideNavOnTransForm" name="HideNavOnTransForm"
                                                                       value="1" <?php echo $tgsHideNav ? 'checked' : ''; ?>>
                                                            </div>
                                                            <div>
                                                                <label class="form-check-label fw-semibold mb-0" for="txn_HideNavOnTransForm">
                                                                    Hide sidebar &amp; show Back button
                                                                </label>
                                                                <div class="form-text mt-0">When enabled, hides the sidebar navigation and adds a Back button on all transaction create / edit pages.</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold" for="txn_TermsAndConditions">Terms &amp; Conditions</label>
                                                        <textarea class="form-control" id="txn_TermsAndConditions" name="TermsAndConditions"
                                                                  rows="8" placeholder="Enter your default terms and conditions here. This will appear on all transaction documents (invoices, quotations, etc.)."
                                                                  ><?php echo $tgsTerms; ?></textarea>
                                                        <div class="form-text">This text will be printed on all transaction documents unless overridden at the document level.</div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary" id="btnSaveTxnGeneral">
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="tgSpinner"></span>
                                                        Save Changes
                                                    </button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab: General -->

                                            <!-- Sub-Tab: Invoice -->
                                            <?php
                                            $ts = $TransSettings ?? new stdClass();
                                            $invoiceCancelAction = $ts->InvoiceCancelAction ?? 'ask';
                                            ?>
                                            <div class="tab-pane fade" id="tab-invoice-settings" role="tabpanel" aria-labelledby="tab-invoice-settings-tab">

                                                <h6 class="fw-semibold mb-1">Invoice</h6>
                                                <p class="text-muted small mb-4">Configure default behaviours for invoice operations.</p>

                                                <div class="row g-4">
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Cancelling / Deleting Invoice <span class="text-danger">*</span></label>
                                                        <p class="text-muted small mb-3">When a <strong>paid or partially paid</strong> invoice is cancelled or deleted, define what should happen to the amount already received from the customer.</p>

                                                        <div class="row g-3">

                                                            <div class="col-md-6">
                                                                <div class="border rounded p-3 h-100 <?php echo $invoiceCancelAction === 'ask' ? 'border-primary bg-label-primary' : ''; ?>" style="cursor:pointer;" onclick="selectCancelAction('ask')">
                                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                                        <input class="form-check-input mt-0" type="radio" name="InvoiceCancelAction" id="ica_ask" value="ask" <?php echo $invoiceCancelAction === 'ask' ? 'checked' : ''; ?>>
                                                                        <label class="fw-semibold mb-0" for="ica_ask" style="cursor:pointer;">Always Ask</label>
                                                                    </div>
                                                                    <p class="text-muted small mb-0">Show a prompt every time so the user can decide on the spot — convert to credit note, issue a refund, or cancel without any action. Best for teams that handle each case individually.</p>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <div class="border rounded p-3 h-100 <?php echo $invoiceCancelAction === 'credit_note' ? 'border-primary bg-label-primary' : ''; ?>" style="cursor:pointer;" onclick="selectCancelAction('credit_note')">
                                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                                        <input class="form-check-input mt-0" type="radio" name="InvoiceCancelAction" id="ica_credit_note" value="credit_note" <?php echo $invoiceCancelAction === 'credit_note' ? 'checked' : ''; ?>>
                                                                        <label class="fw-semibold mb-0" for="ica_credit_note" style="cursor:pointer;">Convert to Credit Note</label>
                                                                    </div>
                                                                    <p class="text-muted small mb-0">Automatically convert the paid amount into a credit note for the customer. The credit note can later be applied against a future invoice or refunded. Ideal for businesses that frequently re-invoice the same customer.</p>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <div class="border rounded p-3 h-100 <?php echo $invoiceCancelAction === 'refund' ? 'border-primary bg-label-primary' : ''; ?>" style="cursor:pointer;" onclick="selectCancelAction('refund')">
                                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                                        <input class="form-check-input mt-0" type="radio" name="InvoiceCancelAction" id="ica_refund" value="refund" <?php echo $invoiceCancelAction === 'refund' ? 'checked' : ''; ?>>
                                                                        <label class="fw-semibold mb-0" for="ica_refund" style="cursor:pointer;">Mark as Refund</label>
                                                                    </div>
                                                                    <p class="text-muted small mb-0">Automatically mark the paid amount as a refund due to the customer. This records the liability to return the money. The actual payment back to the customer must be processed separately (bank transfer / cash).</p>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <div class="border rounded p-3 h-100 <?php echo $invoiceCancelAction === 'cancel_only' ? 'border-primary bg-label-primary' : ''; ?>" style="cursor:pointer;" onclick="selectCancelAction('cancel_only')">
                                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                                        <input class="form-check-input mt-0" type="radio" name="InvoiceCancelAction" id="ica_cancel_only" value="cancel_only" <?php echo $invoiceCancelAction === 'cancel_only' ? 'checked' : ''; ?>>
                                                                        <label class="fw-semibold mb-0" for="ica_cancel_only" style="cursor:pointer;">Cancel Only</label>
                                                                    </div>
                                                                    <p class="text-muted small mb-0">Simply cancel the invoice without any automatic action on the received payment. The customer balance will naturally show a credit. Use this if your business handles payment adjustments manually outside the system.</p>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary" id="btnSaveTransactionSettings">
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="tsSpinner"></span>
                                                        Save Changes
                                                    </button>
                                                </div>

                                            </div>
                                            <!-- / Sub-Tab: Invoice -->

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

        var currency = $('#gs_CurrenySymbol').val().trim();
        if (!currency) {
            showToastNotification('Currency symbol is required.', 'error');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        $.ajax({
            url    : '/settings/updateGeneralSettings',
            method : 'POST',
            data   : {
                CurrenySymbol   : currency,
                DecimalPoints   : $('#gs_DecimalPoints').val(),
                RowLimit        : $('#gs_RowLimit').val(),
                FYStartMonth    : $('#gs_FYStartMonth').val(),
                SerialNoDisplay : $('#gs_SerialNoDisplay').is(':checked') ? 1 : 0,
                MaxShippingAddr : $('#gs_MaxShippingAddr').val(),
                FormDateFormat      : $('#FormDateFormat').val(),
                ListDateFormat      : $('#ListDateFormat').val(),
                PrintDateFormat     : $('#PrintDateFormat').val(),
                FormDateTimeFormat  : $('#FormDateTimeFormat').val(),
                ListDateTimeFormat  : $('#ListDateTimeFormat').val(),
                PrintDateTimeFormat : $('#PrintDateTimeFormat').val(),
                [CsrfName]      : CsrfToken,
            },
            success: function (resp) {
                showToastNotification(resp.Message, resp.Error ? 'error' : 'success');
            },
            error: function () {
                showToastNotification('Request failed. Please try again.', 'error');
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

        if (!$('#ps_ProductType').val() || !$('#ps_DiscountType').val() ||
            !$('#ps_ProductTax').val()  || !$('#ps_TaxDetail').val()) {
            showToastNotification('Please select all four fields.', 'error');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

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
                showToastNotification(resp.Message, resp.Error ? 'error' : 'success');
            },
            error: function () {
                showToastNotification('Request failed. Please try again.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    // ── Save Transaction General Settings (T&C) ──────────────────────────────
    $('#btnSaveTxnGeneral').on('click', function () {
        var $btn     = $(this);
        var $spinner = $('#tgSpinner');

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        $.ajax({
            url    : '/settings/updateTransactionGeneralSettings',
            method : 'POST',
            data   : {
                TermsAndConditions : $('#txn_TermsAndConditions').val(),
                HideNavOnTransForm : $('#txn_HideNavOnTransForm').is(':checked') ? 1 : 0,
                [CsrfName]         : CsrfToken,
            },
            success: function (resp) {
                showToastNotification(resp.Message, resp.Error ? 'error' : 'success');
            },
            error: function () {
                showToastNotification('Request failed. Please try again.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    // ── Invoice cancel action card selection ──────────────────────────────────
    window.selectCancelAction = function (value) {
        $('input[name="InvoiceCancelAction"]').val([value]);
        $('input[name="InvoiceCancelAction"]').closest('.border').each(function () {
            $(this).removeClass('border-primary bg-label-primary');
        });
        $('input[name="InvoiceCancelAction"][value="' + value + '"]')
            .closest('.border').addClass('border-primary bg-label-primary');
    };

    // ── Save Transaction Settings ─────────────────────────────────────────────
    // ── Transaction General Settings (date formats) save ─────────────────────

    $('#btnSaveTransactionSettings').on('click', function () {
        var $btn     = $(this);
        var $spinner = $('#tsSpinner');
        var action   = $('input[name="InvoiceCancelAction"]:checked').val();

        if (!action) {
            showToastNotification('Please select a cancellation action.', 'error');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        $.ajax({
            url    : '/settings/updateTransactionSettings',
            method : 'POST',
            data   : {
                InvoiceCancelAction : action,
                [CsrfName]          : CsrfToken,
            },
            success: function (resp) {
                showToastNotification(resp.Message, resp.Error ? 'error' : 'success');
            },
            error: function () {
                showToastNotification('Request failed. Please try again.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

});
</script>