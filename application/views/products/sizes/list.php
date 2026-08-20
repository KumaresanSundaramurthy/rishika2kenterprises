<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
/**
 * Build a compact dimension summary string for the list.
 * Returns e.g. "10 × 5 × 2 cm", "Ø 50 mm", "0.5 kg", or "" if nothing set.
 */
function _sizeDimSummary(object $row): string {
    $uom = htmlspecialchars($row->DimensionUOM ?? '');
    $wuom = htmlspecialchars($row->WeightUOM ?? '');
    $parts = [];

    // L × W × H
    $lwh = array_filter([
        isset($row->Length)    && $row->Length    > 0 ? rtrim(rtrim(number_format((float)$row->Length, 2), '0'), '.') : null,
        isset($row->Width)     && $row->Width     > 0 ? rtrim(rtrim(number_format((float)$row->Width,  2), '0'), '.') : null,
        isset($row->Height)    && $row->Height    > 0 ? rtrim(rtrim(number_format((float)$row->Height, 2), '0'), '.') : null,
    ]);
    if (count($lwh)) $parts[] = implode(' × ', $lwh) . ($uom ? ' ' . $uom : '');

    // Depth
    if (!empty($row->Depth) && $row->Depth > 0)
        $parts[] = 'D ' . rtrim(rtrim(number_format((float)$row->Depth, 2), '0'), '.') . ($uom ? ' ' . $uom : '');

    // Diameter
    if (!empty($row->Diameter) && $row->Diameter > 0)
        $parts[] = 'Ø ' . rtrim(rtrim(number_format((float)$row->Diameter, 2), '0'), '.') . ($uom ? ' ' . $uom : '');

    // Thickness
    if (!empty($row->Thickness) && $row->Thickness > 0)
        $parts[] = 'T ' . rtrim(rtrim(number_format((float)$row->Thickness, 2), '0'), '.') . ($uom ? ' ' . $uom : '');

    // Weight
    if (!empty($row->Weight) && $row->Weight > 0)
        $parts[] = rtrim(rtrim(number_format((float)$row->Weight, 3), '0'), '.') . ($wuom ? ' ' . $wuom : '');

    return implode(' · ', $parts);
}

if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {
        $sno      = $StartFrom + $i + 1;
        $_words   = preg_split('/\s+/', trim($row->SizeName));
        $_initial = strtoupper(substr($_words[0], 0, 1));
        if (isset($_words[1]) && $_words[1] !== '') $_initial .= strtoupper(substr($_words[1], 0, 1));

        $dimSummary = _sizeDimSummary($row);
?>
        <tr>
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input sizeCheck" type="checkbox" value="<?php echo htmlspecialchars($row->SizeUID); ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded bg-label-warning"><?php echo $_initial; ?></span>
                    </div>
                    <div>
                        <span class="fw-medium"><?php echo htmlspecialchars($row->SizeName); ?></span>
                        <?php if (!empty($row->SizeCode)): ?>
                            <div class="text-muted r2k-item-subtext"><?php echo htmlspecialchars($row->SizeCode); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($dimSummary !== ''): ?>
                    <span class="text-muted r2k-dim-text"><?php echo $dimSummary; ?></span>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php $prodCount = (int)($row->ProductCount ?? 0); ?>
                <?php if ($prodCount > 0): ?>
                    <span class="badge bg-label-primary"><?php echo $prodCount; ?> item<?php echo $prodCount > 1 ? 's' : ''; ?></span>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($row->UpdatedOn)): ?>
                    <?php $updatedTs = viewPageDateTimeFormat($row->UpdatedOn, $JwtData->User->Timezone ?? 'UTC', 2); ?>
                    <div class="r2k-col-date"><?php echo $updatedTs->formatted; ?></div>
                    <?php if ($updatedTs->ago): ?>
                    <div class="r2k-col-date-ago"><?php echo $updatedTs->ago; ?></div>
                    <?php endif; ?>
                    <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($row->UpdatedBy ?? '—'); ?></div>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center align-middle">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editSize"
                       data-uid="<?php echo htmlspecialchars($row->SizeUID); ?>"
                       data-name="<?php echo base64_encode($row->SizeName); ?>"
                       data-code="<?php echo base64_encode($row->SizeCode ?? ''); ?>"
                       data-description="<?php echo base64_encode($row->Description ?? ''); ?>"
                       data-length="<?php echo (float)($row->Length ?? 0); ?>"
                       data-width="<?php echo (float)($row->Width ?? 0); ?>"
                       data-height="<?php echo (float)($row->Height ?? 0); ?>"
                       data-depth="<?php echo (float)($row->Depth ?? 0); ?>"
                       data-diameter="<?php echo (float)($row->Diameter ?? 0); ?>"
                       data-thickness="<?php echo (float)($row->Thickness ?? 0); ?>"
                       data-weight="<?php echo (float)($row->Weight ?? 0); ?>"
                       data-dimensionuom="<?php echo htmlspecialchars($row->DimensionUOM ?? ''); ?>"
                       data-weightuom="<?php echo htmlspecialchars($row->WeightUOM ?? ''); ?>"
                       title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>
                    <div class="dropdown">
                        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm r2k-actions-menu">
                            <li>
                                <button class="dropdown-item text-danger DeleteSize"
                                        data-sizeuid="<?php echo htmlspecialchars($row->SizeUID); ?>"
                                        data-productcount="<?php echo (int)($row->ProductCount ?? 0); ?>"
                                        data-sizename="<?php echo htmlspecialchars($row->SizeName); ?>">
                                    <i class="bx bx-trash me-2"></i>Delete
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
<?php }
} else { ?>
    <tr>
        <td colspan="7">
            <div class="r2k-list-empty">
                <div class="r2k-list-empty-inner">
                    <div class="r2k-list-empty-img">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" />
                    </div>
                    <div class="r2k-list-empty-action">
                        <span class="mb-2">Add a Size Now</span>
                        <a href="javascript:void(0);" class="btn btn-primary px-3 addSize">
                            <i class="bx bx-plus"></i> Create Size
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<?php } ?>
