<?php defined('BASEPATH') or exit('No direct script access allowed');
// Shared prefix select + transaction number block for all CREATE (add) forms.
// Requires: $PrefixData, $NextNumberMap — passed by controller via pageData.
$defaultPrefixConfig = null;
if (!empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ($_pd->IsDefault == 1) { $defaultPrefixConfig = $_pd; break; }
    }
    if (!$defaultPrefixConfig) $defaultPrefixConfig = $PrefixData[0];
}
if (!function_exists('buildTransInitialPrefixSegment')) {
    function buildTransInitialPrefixSegment($cfg) {
        if (!$cfg) return '';
        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
        if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) {
            $parts[] = strtoupper($cfg->ShortName);
        }
        if (!empty($cfg->IncludeFiscalYear)) {
            $m  = (int)date('m');
            $yr = (int)date('Y');
            $fy = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        return implode($sep, $parts) . $sep;
    }
}
$initialPrefixSeg  = buildTransInitialPrefixSegment($defaultPrefixConfig);
$initialNextNumber = ($defaultPrefixConfig && isset($NextNumberMap[(int)$defaultPrefixConfig->PrefixUID]))
                     ? (int)$NextNumberMap[(int)$defaultPrefixConfig->PrefixUID] : 1;
?>
<div class="d-flex align-items-center gap-1">
    <div class="input-group w-auto">
        <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" required>
        <?php if (!empty($PrefixData)) {
            foreach ($PrefixData as $preData) {
                $isSelected = $preData->IsDefault == 1 ? 'selected' : ''; ?>
            <option value="<?php echo (int)$preData->PrefixUID; ?>"
                data-sep="<?php echo htmlspecialchars($preData->Separator ?? '-'); ?>"
                data-fiscal="<?php echo !empty($preData->IncludeFiscalYear) ? '1' : '0'; ?>"
                data-fiscal-format="<?php echo htmlspecialchars($preData->FiscalYearFormat ?? 'SHORT'); ?>"
                data-inc-short="<?php echo !empty($preData->IncludeShortName) ? '1' : '0'; ?>"
                data-short-name="<?php echo htmlspecialchars($preData->ShortName ?? ''); ?>"
                data-padding="<?php echo (int)($preData->NumberPadding ?? 3); ?>"
                data-next-number="<?php echo (int)($NextNumberMap[(int)$preData->PrefixUID] ?? 1); ?>"
                <?php echo $isSelected; ?>><?php echo htmlspecialchars($preData->Name); ?></option>
        <?php } } else { ?>
            <option value="">No prefixes configured</option>
        <?php } ?>
        </select>
        <button type="button" class="btn btn-outline-secondary" id="addTransPrefixBtn" title="Configure Prefix"><i class="bx bx-cog"></i></button>
    </div>
    <div class="input-group input-group-sm w-auto">
        <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($initialPrefixSeg); ?></span>
        <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo $initialNextNumber; ?>" required />
    </div>
</div>
