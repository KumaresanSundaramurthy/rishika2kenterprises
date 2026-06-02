<?php defined('BASEPATH') or exit('No direct script access allowed');

if (empty($DataLists)) { ?>
    <tr>
        <td colspan="7" class="text-center py-5 text-muted">
            <i class="bx bx-tag-alt fs-1 d-block mb-2"></i>
            No prefix configurations added yet.
        </td>
    </tr>
<?php return; }

// Build the preview string from prefix components (PHP version of the JS preview)
function buildPrefixPreviewStr($row) {
    $sep   = $row->Separator ?? '-';
    $parts = [strtoupper($row->Name ?? '')];
    if ($row->IncludeShortName && $row->ShortName) {
        $parts[] = strtoupper($row->ShortName);
    }
    if ($row->IncludeFiscalYear) {
        $m  = (int)date('m');
        $yr = (int)date('Y');
        $fy = $m >= 4 ? $yr : $yr - 1;
        $parts[] = ($row->FiscalYearFormat ?? 'SHORT') === 'LONG'
            ? $fy . '-' . ($fy + 1)
            : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
    }
    $pad     = (int)($row->NumberPadding ?? 3);
    $parts[] = $pad > 1 ? str_pad('1', $pad, '0', STR_PAD_LEFT) : '1';
    return implode($sep, $parts);
}

$idx = 1;
foreach ($DataLists as $row):
    $isDefault  = !empty($row->IsDefault);
    $preview    = htmlspecialchars(buildPrefixPreviewStr($row));
    $moduleName = htmlspecialchars($row->ModuleName ?? '—');

    // Configuration badges
    $cfgBadges = '';
    if (!empty($row->IncludeFiscalYear)) {
        $fyLabel    = ($row->FiscalYearFormat ?? 'SHORT') === 'LONG' ? 'Full year' : 'Short year';
        $cfgBadges .= '<span class="badge bg-label-info me-1 mb-1">FY ' . $fyLabel . '</span>';
    }
    if (!empty($row->IncludeShortName) && !empty($row->ShortName)) {
        $cfgBadges .= '<span class="badge bg-label-warning me-1 mb-1">' . htmlspecialchars($row->ShortName) . '</span>';
    }
    $sepLabels  = ['-' => 'Hyphen (–)', '/' => 'Slash (/)', '|' => 'Pipe (|)', '_' => 'Underscore (_)', '.' => 'Dot (.)'];
    $cfgBadges .= '<span class="badge bg-label-secondary me-1 mb-1">Sep: ' . htmlspecialchars($sepLabels[$row->Separator] ?? $row->Separator) . '</span>';
    $padLabel   = (int)$row->NumberPadding > 1 ? (int)$row->NumberPadding . ' digits' : 'No pad';
    $cfgBadges .= '<span class="badge bg-label-secondary me-1 mb-1">Pad: ' . $padLabel . '</span>';

    // Last-updated display (UpdatedOn stored as unix int by Transactions controller)
    $updatedOnTs  = null;
    $updatedOnStr = null;
    if (!empty($row->UpdatedOn)) {
        $updatedOnTs  = is_numeric($row->UpdatedOn) ? (int)$row->UpdatedOn : strtotime($row->UpdatedOn);
        $updatedOnStr = is_numeric($row->UpdatedOn) ? date('Y-m-d H:i:s', (int)$row->UpdatedOn) : $row->UpdatedOn;
    }
    $secondsAgo = $updatedOnTs ? (time() - $updatedOnTs) : null;
    $agoText    = '';
    if ($secondsAgo !== null && $secondsAgo >= 0 && $secondsAgo < 86400) {
        if ($secondsAgo < 60)        $agoText = 'just now';
        elseif ($secondsAgo < 3600)  $agoText = (int)($secondsAgo / 60) . ' min' . ((int)($secondsAgo / 60) > 1 ? 's' : '') . ' ago';
        else                         $agoText = (int)($secondsAgo / 3600) . ' hr' . ((int)($secondsAgo / 3600) > 1 ? 's' : '') . ' ago';
    }
    $updatedByName = htmlspecialchars(trim($row->UpdatedByName ?? '—'));

    // JSON payload for Edit button (use htmlspecialchars to prevent XSS)
    $editData = htmlspecialchars(json_encode([
        'PrefixUID'         => (int)$row->PrefixUID,
        'ModuleUID'         => (int)$row->ModuleUID,
        'ModuleName'        => $row->ModuleName ?? '',
        'Name'              => $row->Name ?? '',
        'IncludeFiscalYear' => (int)($row->IncludeFiscalYear ?? 0),
        'FiscalYearFormat'  => $row->FiscalYearFormat ?? 'SHORT',
        'IncludeShortName'  => (int)($row->IncludeShortName ?? 0),
        'ShortName'         => $row->ShortName ?? '',
        'Separator'         => $row->Separator ?? '-',
        'NumberPadding'     => (int)($row->NumberPadding ?? 3),
        'IsDefault'         => (int)($row->IsDefault ?? 0),
    ]), ENT_QUOTES);
?>
<tr>
    <td class="text-center align-middle" style="width:50px"><?php echo $idx++; ?></td>

    <td class="align-middle">
        <?php if ($row->ModuleName): ?>
            <span class="badge bg-label-primary" style="font-size:.75rem;"><?php echo $moduleName; ?></span>
        <?php else: ?>
            <span class="text-muted small">—</span>
        <?php endif; ?>
    </td>

    <td class="align-middle">
        <code class="fw-bold text-primary" style="font-size:.9rem;"><?php echo $preview; ?></code>
        <?php if ($isDefault): ?>
            <span class="badge bg-success ms-1" style="font-size:.65rem;">Default</span>
        <?php endif; ?>
    </td>

    <td class="align-middle fw-semibold"><?php echo htmlspecialchars($row->Name ?? ''); ?></td>

    <td class="align-middle" style="white-space:normal;max-width:240px;">
        <?php echo $cfgBadges; ?>
    </td>

    <td class="text-center align-middle">
        <?php if ($isDefault): ?>
            <i class="bx bxs-star text-warning fs-5" title="Default prefix"
               data-bs-toggle="tooltip" data-bs-title="This is the default prefix"></i>
        <?php else: ?>
            <button type="button"
                    class="btn btn-icon text-warning SetDefaultPrefixConfig"
                    data-uid="<?php echo (int)$row->PrefixUID; ?>"
                    title="Set as default"
                    data-bs-toggle="tooltip" data-bs-title="Set as default">
                <i class="bx bx-star"></i>
            </button>
        <?php endif; ?>
    </td>

    <td class="align-middle">
        <?php if ($updatedOnStr): ?>
            <div style="font-size:.78rem;"><?php echo changeTimeZonefromDateTime($updatedOnStr, $JwtData->User->Timezone, 2); ?></div>
            <?php if ($agoText): ?>
                <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div style="font-size:.78rem;" class="text-muted">—</div>
        <?php endif; ?>
        <div class="text-muted" style="font-size:.7rem;">by <?php echo $updatedByName; ?></div>
    </td>

    <td class="text-center align-middle">
        <div class="d-flex align-items-center justify-content-center gap-1">
            <button type="button"
                    class="btn btn-icon btn-sm text-warning EditPrefixConfig"
                    data-config='<?php echo $editData; ?>'
                    title="Edit">
                <i class="bx bx-edit"></i>
            </button>
            <?php if (!$isDefault): ?>
            <button type="button"
                    class="btn btn-icon btn-sm text-danger DeletePrefixConfig"
                    data-uid="<?php echo (int)$row->PrefixUID; ?>"
                    data-name="<?php echo htmlspecialchars($row->Name ?? ''); ?>"
                    title="Delete">
                <i class="bx bx-trash"></i>
            </button>
            <?php else: ?>
            <button type="button" class="btn btn-icon btn-sm text-muted" disabled title="Default prefix cannot be deleted">
                <i class="bx bx-lock-alt"></i>
            </button>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
