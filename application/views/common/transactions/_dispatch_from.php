<?php
/**
 * Dispatch From custom dropdown — no wrapper div, caller provides that.
 * Variables expected: $DispatchAddresses (array), $DispatchAddress (default)
 */
defined('BASEPATH') or exit('No direct script access allowed');
$_defUID = (int)($DispatchAddress->OrgAddressUID ?? 0);

// Find the default address object
$_defAddr = null;
foreach ($DispatchAddresses as $_a) {
    if ((int)$_a->OrgAddressUID === $_defUID) { $_defAddr = $_a; break; }
}
if (!$_defAddr && !empty($DispatchAddresses)) $_defAddr = $DispatchAddresses[0];

// Build trigger display text for default selection
$_trigParts = array_filter([
    $_defAddr->Line1 ?? '',
    implode(', ', array_filter([$_defAddr->CityText ?? '', $_defAddr->StateText ?? ''])),
    $_defAddr->Pincode ?? '',
]);
$_trigText = htmlspecialchars(implode(' · ', $_trigParts)) ?: 'Select Address';
?>
<div class="r2k-dispatch-from">
    <input type="hidden" id="dispatchFrom" name="dispatchFrom"
           value="<?php echo $_defAddr ? (int)$_defAddr->OrgAddressUID : ''; ?>" required>
    <button type="button" class="r2k-dispatch-btn" title="<?php echo $_trigText; ?>">
        <span class="r2k-dispatch-val"><?php echo $_trigText; ?></span>
    </button>
    <div class="r2k-dispatch-menu p-1">
        <?php foreach ($DispatchAddresses as $_addr):
            $_uid      = (int)$_addr->OrgAddressUID;
            $_orgName  = htmlspecialchars($_addr->OrgName     ?? '');
            $_line1    = htmlspecialchars($_addr->Line1       ?? '');
            $_line2    = htmlspecialchars($_addr->Line2       ?? '');
            $_city     = htmlspecialchars($_addr->CityText    ?? '');
            $_state    = htmlspecialchars($_addr->StateText   ?? '');
            $_pin      = htmlspecialchars($_addr->Pincode     ?? '');
            $_type     = htmlspecialchars($_addr->AddressType ?? '');
            $_cityState = implode(', ', array_filter([$_city, trim($_state . ($_pin ? ' ' . $_pin : ''))]));
            $_isSel    = $_uid === (int)($_defAddr->OrgAddressUID ?? 0);
            $_optParts = array_filter([
                $_addr->Line1 ?? '',
                implode(', ', array_filter([$_addr->CityText ?? '', $_addr->StateText ?? ''])),
                $_addr->Pincode ?? '',
            ]);
            $_optTrig  = htmlspecialchars(implode(' · ', $_optParts));
        ?>
        <div class="r2k-dispatch-item<?php echo $_isSel ? ' selected' : ''; ?>"
             data-uid="<?php echo $_uid; ?>"
             data-trig="<?php echo $_optTrig; ?>">
            <div class="r2k-dispatch-check">
                <i class="bx <?php echo $_isSel ? 'bx-check-circle' : 'bx-circle'; ?>"></i>
            </div>
            <div class="r2k-dispatch-info">
                <?php if ($_orgName): ?><div class="r2k-dispatch-org"><?php echo $_orgName; ?></div><?php endif; ?>
                <?php if ($_line1): ?><div class="r2k-dispatch-line"><?php echo $_line1; ?></div><?php endif; ?>
                <?php if ($_line2): ?><div class="r2k-dispatch-line"><?php echo $_line2; ?></div><?php endif; ?>
                <?php if ($_cityState): ?><div class="r2k-dispatch-city"><?php echo $_cityState; ?></div><?php endif; ?>
                <?php if ($_type): ?><span class="r2k-dispatch-badge"><?php echo $_type; ?></span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
