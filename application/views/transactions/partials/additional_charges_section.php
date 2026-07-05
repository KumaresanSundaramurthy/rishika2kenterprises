<?php
defined('BASEPATH') or exit('No direct script access allowed');

$isEdit       = !empty($IsEditMode);
$allCharges   = $AdditionalCharges   ?? [];
$savedCharges = $TransactionCharges  ?? [];

if ($isEdit && !empty($savedCharges)) {
    $initialRows = $savedCharges;
    $isFromSaved = true;
} else {
    $initialRows = array_values($allCharges);
    $isFromSaved = false;
}

// Auto-open only in edit mode when saved charges exist; always collapsed on create
$sectionOpen = $isEdit && !empty($savedCharges);

// Helper: read a property from either a stdClass object or an associative array
$get = fn($r, $k, $d = '') => is_array($r) ? ($r[$k] ?? $d) : ($r->$k ?? $d);

$hasRows = !empty($initialRows);
?>

<div id="additionalChargesWrapper" style="flex:1;margin-left:24px;display:flex;flex-direction:column;align-items:flex-end;">

    <button type="button" id="btnToggleCharges"
        class="btn btn-sm btn-outline-secondary px-3 mt-2"
        style="font-size:.82rem;">
        <i class="bx bx-receipt me-1"></i>Additional Charges
        <i class="bx <?php echo $sectionOpen ? 'bx-chevron-up' : 'bx-chevron-down'; ?> ms-1" id="acToggleChevron"></i>
    </button>

    <div id="additionalChargesSection"
        style="width:100%;<?php echo $sectionOpen ? '' : 'display:none;'; ?>border:1px solid #dee2e6;border-radius:6px;padding:10px 12px;margin-top:8px;background:#fff;">

        <table class="table table-bordered table-sm table-hover align-middle trans-table w-100 mb-0<?php echo $hasRows ? '' : ' d-none'; ?>" id="additionalChargesTable" style="table-layout:fixed;">
            <thead class="trans-table-light">
                <tr>
                    <th style="width:31%;">Charge</th>
                    <th style="width:11%;text-align:center;">Tax</th>
                    <th style="width:13%;text-align:right;">In (%)</th>
                    <th style="width:17%;text-align:right;">Without Tax (₹)</th>
                    <th style="width:17%;text-align:right;">With Tax (₹)</th>
                    <th style="width:5%;"></th>
                </tr>
            </thead>
            <tbody id="additionalChargesBody">
            <?php foreach ($initialRows as $row):
                $chargeUID   = (int)$get($row, 'ChargeUID', 0);
                $name        = $isFromSaved ? $get($row, 'ChargeName')        : $get($row, 'Name');
                $displayName = $isFromSaved ? $get($row, 'ChargeDisplayName') : $get($row, 'DisplayName');
                $inPct       = $isFromSaved ? (float)$get($row, 'InPercent', 0)        : 0;
                $woTax       = $isFromSaved ? (float)$get($row, 'WithoutTaxAmount', 0) : 0;
                $taxUID      = (int)$get($row, $isFromSaved ? 'TaxUID' : 'DefaultTaxUID', 0);
                $withTax     = $isFromSaved ? (float)$get($row, 'WithTaxAmount', 0)    : 0;
                $sortOrder   = (int)$get($row, 'SortOrder', 0);
            ?>
                <?php
                $driver = $inPct > 0 ? 'pct' : ($woTax > 0 ? 'wot' : ($withTax > 0 ? 'wt' : 'pct'));
                ?>
                <tr class="ac-charge-row"
                    data-charge-uid="<?php echo $chargeUID; ?>"
                    data-name="<?php echo htmlspecialchars($name); ?>"
                    data-display-name="<?php echo htmlspecialchars($displayName); ?>"
                    data-tax-uid="<?php echo $taxUID; ?>"
                    data-sort-order="<?php echo $sortOrder; ?>"
                    data-driver="<?php echo $driver; ?>"
                    >
                    <td style="overflow:hidden;">
                        <span class="fw-semibold" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($displayName); ?> (+)</span>
                    </td>
                    <td class="text-center">
                        <select class="form-select form-select-sm ac-tax-select"
                            data-charge-uid="<?php echo $chargeUID; ?>">
                            <option value="0" data-pct="0">0</option>
                        </select>
                    </td>
                    <td class="text-end">
                        <input type="number" class="form-control form-control-sm text-end ac-pct-input"
                            min="0" max="100" step="any"
                            value="<?php echo $inPct > 0 ? $inPct : '0'; ?>"
                            data-charge-uid="<?php echo $chargeUID; ?>">
                    </td>
                    <td class="text-end">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="border-right:0;"><?php echo htmlspecialchars($CurrencySymbol ?? '₹'); ?></span>
                            <input type="number" class="form-control form-control-sm text-end ac-wot-input"
                                min="0" step="0.01"
                                value="<?php echo $woTax > 0 ? $woTax : '0'; ?>"
                                data-charge-uid="<?php echo $chargeUID; ?>">
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="border-right:0;"><?php echo htmlspecialchars($CurrencySymbol ?? '₹'); ?></span>
                            <input type="number" class="form-control form-control-sm text-end ac-wt-input"
                                min="0" step="0.01"
                                value="<?php echo $withTax > 0 ? $withTax : '0'; ?>"
                                data-charge-uid="<?php echo $chargeUID; ?>">
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-icon btn-sm text-danger ac-remove-btn p-0"
                            title="Remove charge" data-charge-uid="<?php echo $chargeUID; ?>">
                            <i class="bx bx-trash fs-5"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div id="acEmptyState" class="<?php echo $hasRows ? 'd-none' : ''; ?> text-center py-3">
            <i class="bx bx-receipt" style="font-size:2rem;color:#d0d5dd;display:block;margin-bottom:6px;"></i>
            <span style="font-size:.8rem;color:#adb5bd;">No charges added — click <strong>+ Add Charge</strong> to include one</span>
        </div>

        <hr id="acSeparator" class="<?php echo $hasRows ? '' : 'd-none'; ?>" style="margin:8px 0;border-color:#e9ecef;">

        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnViewAllCharges"
                class="btn btn-sm btn-outline-secondary px-3"
                style="font-size:.78rem;">
                <i class="bx bx-list-ul me-1"></i>View All
            </button>
            <button type="button" id="btnAddCharge"
                class="btn btn-sm btn-outline-primary px-3"
                style="font-size:.78rem;">
                <i class="bx bx-plus me-1"></i>Add Charge
            </button>
        </div>

    </div>

</div>
