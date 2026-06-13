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
$_moduleName       = htmlspecialchars($PageTitle ?? 'This Module');
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
        <a href="<?php echo base_url('settings/prefixconfig'); ?>" class="btn btn-outline-secondary" title="Manage Prefix Configuration" target="_blank"><i class="bx bx-cog"></i></a>
    </div>
    <div class="input-group input-group-sm w-auto">
        <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($initialPrefixSeg); ?></span>
        <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo $initialNextNumber; ?>" required />
    </div>
</div>

<?php if (empty($PrefixData)): ?>
<!-- ── No-Prefix Blocking Modal ───────────────────────────────────────────── -->
<div class="modal fade" id="noPrefixBlockModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="noPrefixBlockModalLabel" aria-modal="true" role="alertdialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);">

            <!-- Gradient header -->
            <div style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:32px 28px 24px;text-align:center;">
                <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.2);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <i class="bx bx-error-circle" style="font-size:2.6rem;color:#fff;"></i>
                </div>
                <h5 id="noPrefixBlockModalLabel" style="color:#fff;font-weight:700;font-size:1.25rem;margin:0;letter-spacing:.2px;">
                    Prefix Not Configured
                </h5>
                <p style="color:rgba(255,255,255,.85);font-size:.82rem;margin:6px 0 0;">
                    Action required before you can proceed
                </p>
            </div>

            <!-- Body -->
            <div class="modal-body px-4 pt-4 pb-3">

                <!-- Module badge -->
                <div class="text-center mb-3">
                    <span style="display:inline-flex;align-items:center;gap:6px;background:#fff8e1;color:#92400e;font-size:.78rem;font-weight:600;padding:5px 14px;border-radius:20px;border:1px solid #fcd34d;">
                        <i class="bx bx-layer" style="font-size:.95rem;"></i>
                        <?php echo $_moduleName; ?>
                    </span>
                </div>

                <!-- Main message -->
                <p style="color:#374151;line-height:1.75;font-size:.9rem;text-align:center;margin-bottom:20px;">
                    No transaction prefix has been set up for <strong><?php echo $_moduleName; ?></strong>.
                    A prefix is required before any record can be created on this page.
                </p>

                <!-- Info box -->
                <div style="background:#f3f4f6;border-radius:10px;padding:14px 16px;margin-bottom:14px;">
                    <p style="font-size:.8rem;font-weight:700;color:#374151;margin:0 0 8px;letter-spacing:.3px;">
                        <i class="bx bx-info-circle me-1" style="color:#6366f1;"></i>WHAT IS A PREFIX?
                    </p>
                    <p style="font-size:.82rem;color:#6b7280;line-height:1.65;margin:0;">
                        A prefix auto-generates unique, sequential transaction numbers — for example
                        <code style="background:#e5e7eb;padding:1px 6px;border-radius:4px;font-size:.78rem;"><?php echo strtoupper(substr(preg_replace('/[^A-Za-z]/','', $_moduleName), 0, 2) ?: 'TX'); ?>-001</code>,
                        <code style="background:#e5e7eb;padding:1px 6px;border-radius:4px;font-size:.78rem;"><?php echo strtoupper(substr(preg_replace('/[^A-Za-z]/','', $_moduleName), 0, 2) ?: 'TX'); ?>-002</code>.
                        Every transaction module needs at least one prefix configured before records can be saved.
                    </p>
                </div>

                <!-- How to fix -->
                <div style="background:#fffbeb;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0;padding:11px 14px;">
                    <p style="font-size:.82rem;color:#78350f;line-height:1.6;margin:0;">
                        <i class="bx bx-wrench me-1"></i>
                        Go to <strong>Settings → Prefix Configuration</strong>, add a prefix for
                        <strong><?php echo $_moduleName; ?></strong>, then return to this page to create transactions.
                    </p>
                </div>

            </div>

            <!-- Footer — only action button, no close -->
            <div class="modal-footer justify-content-center border-0 px-4 pt-1 pb-4 gap-3">
                <a href="<?php echo base_url('settings/prefixconfig'); ?>"
                   class="btn btn-lg fw-semibold"
                   style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:10px;padding:10px 36px;letter-spacing:.3px;box-shadow:0 4px 14px rgba(245,158,11,.45);">
                    <i class="bx bx-cog me-2"></i>Go to Prefix Configuration
                </a>
                <p style="font-size:.75rem;color:#9ca3af;margin:4px 0 0;text-align:center;width:100%;">
                    You will be taken to the Settings page. Come back after adding a prefix.
                </p>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('noPrefixBlockModal');

    // The modal is rendered inside .trans-header-static (z-index:1020 sticky),
    // which creates a CSS stacking context. Children cannot exceed the parent's
    // stacking context level, so Bootstrap's backdrop (z-index:1050) would paint
    // over the modal dialog. Moving the element to <body> fixes this.
    document.body.appendChild(modalEl);

    var _npModal = new bootstrap.Modal(modalEl, {
        backdrop : 'static',
        keyboard : false
    });
    _npModal.show();

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    }, true);
});
</script>
<?php endif; ?>
