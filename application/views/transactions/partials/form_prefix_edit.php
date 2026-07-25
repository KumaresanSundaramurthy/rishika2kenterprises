<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="d-flex align-items-center gap-1">
    <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
        <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
            <?php try {
                if (empty($PrefixData)) throw new Exception('Prefix data not loaded');
                foreach ($PrefixData as $preData) {
                    $isSelected = (int)$preData->PrefixUID === (int)$_editPrefixUID ? 'selected' : '';
                ?>
                <option value="<?php echo (int)$preData->PrefixUID; ?>"
                    data-sep="<?php echo htmlspecialchars($preData->Separator ?? '-'); ?>"
                    data-fiscal="<?php echo !empty($preData->IncludeFiscalYear) ? '1' : '0'; ?>"
                    data-fiscal-format="<?php echo htmlspecialchars($preData->FiscalYearFormat ?? 'SHORT'); ?>"
                    data-inc-short="<?php echo !empty($preData->IncludeShortName) ? '1' : '0'; ?>"
                    data-short-name="<?php echo htmlspecialchars($preData->ShortName ?? ''); ?>"
                    data-padding="<?php echo (int)($preData->NumberPadding ?? 3); ?>"
                    data-next-number="<?php echo (int)($NextNumberMap[(int)$preData->PrefixUID] ?? 1); ?>"
                    <?php echo $isSelected; ?>
                ><?php echo htmlspecialchars($preData->Name); ?></option>
            <?php }
            } catch (Exception $e) { ?>
                <option value="">Error loading prefixes</option>
            <?php } ?>
        </select>
        <?php if ($isDraftEdit): ?>
        <button type="button" class="btn btn-outline-secondary" id="addTransPrefixBtn" title="Configure Prefix"><i class="bx bx-cog"></i></button>
        <?php endif; ?>
    </div>
    <div class="input-group input-group-sm w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
        <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($editPrefixSeg); ?></span>
        <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20"
            onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
            oninput="this.value=this.value.slice(0,this.maxLength)"
            pattern="[0-9]*" value="<?php echo $editTransNumber; ?>"
            <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
    </div>
    <?php if (!$isDraftEdit): ?>
    <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$_editPrefixUID; ?>" />
    <input type="hidden" name="transNumber" value="<?php echo (int)$editTransNumber; ?>" />
    <?php endif; ?>
</div>
