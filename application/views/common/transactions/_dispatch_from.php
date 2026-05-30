<?php
/**
 * Dispatch From <select> element — no wrapper div, caller provides that.
 * Variables expected: $DispatchAddresses (array), $DispatchAddress (default)
 */
defined('BASEPATH') or exit('No direct script access allowed');
$_defUID = (int)($DispatchAddress->OrgAddressUID ?? 0);
?>
<select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm r2k-dispatch-sel" required>
    <?php foreach ($DispatchAddresses as $addr):
        $uid = (int) $addr->OrgAddressUID; ?>
    <option value="<?php echo $uid; ?>"
            data-orgname="<?php echo htmlspecialchars($addr->OrgName    ?? ''); ?>"
            data-line1="<?php echo htmlspecialchars($addr->Line1       ?? ''); ?>"
            data-line2="<?php echo htmlspecialchars($addr->Line2       ?? ''); ?>"
            data-city="<?php echo htmlspecialchars($addr->CityText     ?? ''); ?>"
            data-state="<?php echo htmlspecialchars($addr->StateText   ?? ''); ?>"
            data-pin="<?php echo htmlspecialchars($addr->Pincode       ?? ''); ?>"
            data-type="<?php echo htmlspecialchars($addr->AddressType  ?? ''); ?>"
            <?php echo $uid === $_defUID ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars(trim(implode(', ', array_filter([
            $addr->Line1, $addr->CityText, $addr->StateText, $addr->Pincode
        ])))); ?>
    </option>
    <?php endforeach; ?>
</select>
