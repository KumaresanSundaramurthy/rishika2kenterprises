<?php defined('BASEPATH') or exit('No direct script access allowed');

$_posOrgStateCode = $JwtData->Org->StateCode  ?? '';
$_posOrgStateName = $JwtData->Org->StateName  ?? '';
$_posInvType      = strtolower($JwtData->TransSettings->DefaultTransactionType ?? 'regular') === 'without_tax' ? 'Without_GST' : 'Regular';
$_posCur          = $JwtData->GenSettings->CurrenySymbol ?? '₹';
?>
<?php $this->load->view('common/transactions/header'); ?>

<!-- POS-only CSS — loaded before body paint -->
<link rel="stylesheet" href="<?php echo _assetV('/css/pos-billing.css'); ?>">

<div id="posBillingPage">

    <!-- ── Loading overlay ─────────────────────────────────────────────── -->
    <div id="posOverlay"><i class="bx bx-loader-alt bx-spin"></i></div>

    <!-- ── Top header bar ──────────────────────────────────────────────── -->
    <div id="posHeaderBar">

        <div class="pos-brand">
            <i class="bx bx-barcode-reader"></i> POS Billing
        </div>

        <div class="pos-hdr-sep"></div>

        <!-- Invoice Prefix + Number -->
        <div class="pos-hdr-field">
            <label>Prefix</label>
            <select id="posPrefixSelect">
                <?php foreach ($PrefixData as $px): ?>
                <option value="<?php echo (int)$px->PrefixUID; ?>"
                    <?php echo ($px->IsDefault ?? 0) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($px->Name ?? ''); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pos-hdr-field">
            <label>#</label>
            <input type="text" id="posTransNumber"
                   value="<?php echo htmlspecialchars($NextInvNumber ?? ''); ?>"
                   placeholder="Auto">
        </div>

        <div class="pos-hdr-sep"></div>

        <!-- Date -->
        <div class="pos-hdr-field">
            <label>Date</label>
            <div class="pos-hdr-date-wrap">
                <input type="text" id="posDate" readonly
                       value="<?php echo date('Y-m-d'); ?>">
                <i class="bx bx-calendar"></i>
            </div>
        </div>

        <div class="pos-hdr-spacer"></div>

        <!-- Branch -->
        <div class="pos-hdr-field">
            <label>Branch</label>
            <span class="pos-branch-name">
                <?php echo htmlspecialchars($JwtData->Org->BranchName ?? 'Main'); ?>
            </span>
        </div>

        <div class="pos-hdr-sep"></div>

        <!-- Close button — rightmost -->
        <a href="/invoices" class="pos-back-btn" title="Back to Invoices">
            <i class="bx bx-x"></i> Close
        </a>

    </div><!-- /#posHeaderBar -->

    <!-- ── Billing screen tabs ───────────────────────────────────────────── -->
    <div id="posScreenTabs">
        <div class="pos-tab active" data-idx="0">
            <i class="bx bx-receipt"></i>
            <span class="pos-tab-label">Bill 1</span>
        </div>
        <div class="pos-tab-new" id="posHoldNew" title="Hold this bill and start a new one [Ctrl+H]">
            <i class="bx bx-plus"></i> Hold &amp; New <span class="pos-tab-shortcut">Ctrl+H</span>
        </div>
    </div>

    <!-- ── Two-panel body ──────────────────────────────────────────────── -->
    <div id="posBody">

        <!-- ── LEFT: Action bar + search + items table ───────────────────── -->
        <div id="posLeftPanel">

            <!-- Action bar -->
            <div id="posActionBar">
                <div class="pos-cust-wrap pos-cust-full">
                    <i class="bx bx-user pos-cust-icon"></i>
                    <input type="text" id="posCustomerSearch" placeholder="Select Customer" autocomplete="off">
                    <input type="hidden" id="posCustomerUID" value="0">
                    <span id="posCustClear" class="pos-cust-clear d-none" title="Clear">×</span>
                    <div id="posCustDropdown"></div>
                </div>
            </div>

            <!-- Category + Search row -->
            <div id="posSearchWrap">
                <select id="posCatFilter">
                    <option value="">All Categories</option>
                </select>
                <div class="pos-search-box">
                    <i class="bx bx-search"></i>
                    <input type="text" id="posProductSearch"
                           placeholder="Search by Item / Serial no. / HSN code / SKU / Custom Field / Category or Scan Barcode"
                           autocomplete="off">
                    <!-- Search results dropdown (shown on input/focus) -->
                    <div id="posSearchDropdown"></div>
                </div>
            </div>

            <!-- Items table -->
            <div id="posItemsWrap">
                <table id="posItemsTable">
                    <thead>
                        <tr>
                            <th class="pit-no">NO</th>
                            <th class="pit-items">ITEMS</th>
                            <th class="pit-code">ITEM CODE</th>
                            <th class="pit-mrp">MRP</th>
                            <th class="pit-sp">SP (<?php echo htmlspecialchars($_posCur); ?>)</th>
                            <th class="pit-disc">DISC (%)</th>
                            <th class="pit-qty">QUANTITY</th>
                            <th class="pit-amt">AMOUNT (<?php echo htmlspecialchars($_posCur); ?>)</th>
                            <th class="pit-del"></th>
                        </tr>
                    </thead>
                    <tbody id="posCartBody"></tbody>
                </table>
                <!-- Empty state (shown when no items) -->
                <div id="posCartEmpty">
                    <i class="bx bx-cart-alt"></i>
                    <span>No items added. Search a product above to add.</span>
                </div>
            </div>

            <!-- Footer count bar -->
            <div id="posCartFooter">
                <span class="pcf-count">Total Items: <strong id="posTotalItems">0</strong></span>
                <span class="pcf-count">Total Qty: <strong id="posTotalQty">0</strong></span>
                <button id="posClearCart">
                    <i class="bx bx-trash me-1"></i>Clear All Items
                </button>
            </div>

        </div><!-- /#posLeftPanel -->

        <!-- ── RIGHT: Totals + Payment + Customer ────────────────────────── -->
        <div id="posRightPanel">

            <!-- Top quick-action buttons (disabled until cart has items) -->
            <div id="posRightTopActions">
                <button class="pos-rta-btn" id="posToggleDiscBtn" disabled>
                    <i class="bx bx-purchase-tag-alt"></i> Add Discount
                </button>
                <button class="pos-rta-btn" id="posAddChargeBtn" disabled>
                    <i class="bx bx-plus-circle"></i> Add Additional Charge
                </button>
            </div>

            <!-- Bill details -->
            <div class="pos-right-section" id="posBillDetails">
                <div class="prs-title">Bill details</div>
                <div class="prs-row">
                    <span>Sub Total</span>
                    <span id="posSubtotal"><?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                </div>
                <div class="prs-row">
                    <span class="prs-tax-wrap">Tax
                        <button type="button" id="posTaxInfoBtn" class="pos-tax-info-btn d-none" title="View tax breakup">
                            <i class="bx bx-info-circle"></i>
                        </button>
                    </span>
                    <span id="posTaxAmt"><?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                </div>
                <!-- Applied charge rows — built dynamically by _posRenderTotals -->
                <div id="posChargeAppliedList"></div>
                <!-- Applied discount row — shown when a discount is active -->
                <div class="prs-row prs-disc-applied-row d-none" id="posDiscAppliedRow">
                    <div class="prs-disc-left">
                        <span id="posDiscAppliedLabel" class="prs-disc-applied-label">Discount</span>
                        <button type="button" class="pos-disc-remove-btn" id="posDiscRemove" title="Remove discount">&times;</button>
                    </div>
                    <span id="posDiscAmt" class="prs-disc-amt">− <?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                </div>
                <!-- Hidden discount data inputs (read by _posRenderTotals and _posSaveCurrentScreen) -->
                <input type="hidden" id="posDiscValue" value="0">
                <input type="hidden" id="posDiscType" value="Percentage">
                <div class="prs-row d-none" id="posRoundOffRow">
                    <span>Round Off</span>
                    <span id="posRoundOffAmt"></span>
                </div>
                <div class="prs-row prs-total">
                    <span>Total Amount</span>
                    <span id="posGrandTotal"><?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                </div>
            </div>

            <!-- Payment mode (with split support) -->
            <div class="pos-right-section" id="posReceivedSection">
                <!-- Header: title + "Mark as fully paid" toggle -->
                <div class="prs-pay-header">
                    <span class="prs-title-sm">Payment</span>
                    <label class="pos-fully-paid-label">
                        <input type="checkbox" id="posIsFullyPaid" checked>
                        <span>Mark as fully paid</span>
                    </label>
                </div>
                <!-- Hidden template — cloned per row by JS -->
                <select id="posPayTypeTemplate" style="display:none">
                    <option value="0">— Type —</option>
                    <?php foreach ($PaymentTypes as $pt): ?>
                    <option value="<?php echo (int)$pt->PaymentTypeUID; ?>">
                        <?php echo htmlspecialchars($pt->Name ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <!-- Payment rows built by JS -->
                <div id="posPaymentRows"></div>
                <!-- Add split payment -->
                <button type="button" id="posAddSplitBtn" class="pos-add-split-btn">
                    <i class="bx bx-plus-circle"></i> Add Split Payment
                </button>
                <!-- Running totals -->
                <div class="pos-pay-totals">
                    <div class="pos-pay-tot-row">
                        <span>Total Paid</span>
                        <span id="posTotalPaid"><?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                    </div>
                    <div class="pos-pay-tot-row d-none" id="posPayBalRow">
                        <span>Balance Due</span>
                        <span id="posPayBalance"><?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                    </div>
                </div>
            </div>

            <!-- Save strip (disabled until cart has items) -->
            <div id="posSaveStrip">
                <button id="posSavePrint" disabled>
                    <i class="bx bx-printer"></i> Save &amp; Print
                </button>
                <button id="posSaveBill" disabled>
                    <i class="bx bx-save"></i> Save Bill
                </button>
            </div>

        </div><!-- /#posRightPanel -->

    </div><!-- /#posBody -->

    <!-- ── Tax Breakup modal ─────────────────────────────────────────────────── -->
    <div id="posTaxModal" class="pos-modal-overlay d-none" role="dialog" aria-modal="true">
        <div class="pos-modal-box pos-tax-modal-box">
            <div class="pos-modal-title">Tax Breakup</div>
            <div class="pos-tax-modal-body">
                <table class="pos-tax-break-table">
                    <thead>
                        <tr>
                            <th>Tax Rate</th>
                            <th>Taxable Amount</th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>Total Tax</th>
                        </tr>
                    </thead>
                    <tbody id="posTaxBreakBody"></tbody>
                    <tfoot id="posTaxBreakFoot"></tfoot>
                </table>
            </div>
            <div class="pos-modal-footer">
                <button type="button" id="posTaxModalClose" class="pos-modal-btn pos-modal-btn-apply">Close</button>
            </div>
        </div>
    </div>

    <!-- ── Additional Charges modal ─────────────────────────────────────────── -->
    <div id="posChargeModal" class="pos-modal-overlay d-none" role="dialog" aria-modal="true">
        <div class="pos-modal-box pos-charge-modal-box">
            <div class="pos-modal-title">Additional Charges</div>
            <div id="posChargeSection" class="pos-charge-modal-body">
                <table class="poc-table d-none" id="posChargeTable">
                    <thead>
                        <tr>
                            <th class="poc-th-name">CHARGE</th>
                            <th class="poc-th-tax">TAX</th>
                            <th class="poc-th-pct">IN (%)</th>
                            <th class="poc-th-wot">W/O TAX (<?php echo htmlspecialchars($_posCur); ?>)</th>
                            <th class="poc-th-wt">WITH TAX (<?php echo htmlspecialchars($_posCur); ?>)</th>
                            <th class="poc-th-del"></th>
                        </tr>
                    </thead>
                    <tbody id="posChargeBody"></tbody>
                </table>
                <div id="posChargeEmpty" class="poc-empty-state d-none">
                    <i class="bx bx-receipt"></i>
                    <span>No charges configured</span>
                </div>
                <div id="posChargeSectionTotalRow" class="poc-section-total d-none">
                    <span>Total Additional Charges</span>
                    <span id="posChargeSectionTotal"><?php echo htmlspecialchars($_posCur); ?> 0.00</span>
                </div>
            </div>
            <div class="pos-modal-footer">
                <button type="button" id="posChargeModalCancel" class="pos-modal-btn pos-modal-btn-cancel">Cancel</button>
                <button type="button" id="posChargeModalApply"  class="pos-modal-btn pos-modal-btn-apply">Apply</button>
            </div>
        </div>
    </div>

    <!-- ── Discount modal ─────────────────────────────────────────────────── -->
    <div id="posDiscModal" class="pos-modal-overlay d-none" role="dialog" aria-modal="true">
        <div class="pos-modal-box">
            <div class="pos-modal-title">Add Discount</div>
            <div class="pos-modal-body">
                <div class="pos-disc-modal-field">
                    <input type="number" id="posDiscModalValue" min="0" step="0.01" placeholder="0" autocomplete="off">
                    <select id="posDiscModalType">
                        <option value="Percentage">%</option>
                        <option value="Fixed"><?php echo htmlspecialchars($_posCur); ?></option>
                    </select>
                </div>
            </div>
            <div class="pos-modal-footer">
                <button type="button" id="posDiscModalCancel" class="pos-modal-btn pos-modal-btn-cancel">Cancel</button>
                <button type="button" id="posDiscModalApply" class="pos-modal-btn pos-modal-btn-apply">Apply</button>
            </div>
        </div>
    </div>

</div><!-- /#posBillingPage -->

<?php $this->load->view('common/transactions/footer'); ?>

<script>
/* POS config — injected by PHP, read by pos-billing.js */
window._posCfg = <?php echo json_encode([
    'orgStateCode'   => $_posOrgStateCode,
    'orgStateName'   => $_posOrgStateName,
    'defaultInvType' => $_posInvType,
    'currency'       => $_posCur,
    'decimals'       => 2,
    'fpFormat'       => 'Y-m-d',
    'fpAltFormat'    => $JwtData->GenSettings->ListDateFormat ?? 'd M Y',
]); ?>;

/* Pre-defined additional charges + tax options for the charges table */
window._posChargesData = <?php echo json_encode(array_values($AdditionalCharges ?? [])); ?>;
window._posTaxOpts     = <?php echo json_encode(array_values($TaxList ?? [])); ?>;
</script>

<script src="<?php echo _assetV('/js/transactions/forms/pos-billing.js'); ?>"></script>
