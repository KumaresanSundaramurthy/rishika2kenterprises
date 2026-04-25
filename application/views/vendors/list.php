<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl     = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
$showSerial = isset($GenSettings->SerialNoDisplay) && $GenSettings->SerialNoDisplay == 1;
$currency   = $JwtData->GenSettings->CurrenySymbol ?? '₹';

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $uid    = (int)$list->TablePrimaryUID;
        $name   = htmlspecialchars($list->Name ?? '—');
        $imgSrc = !empty($list->Image) ? $cdnUrl . $list->Image : null;

        // 2-letter initials
        $_words    = preg_split('/\s+/', trim($list->Name ?? ''));
        $_initials = strtoupper(substr($_words[0] ?? '', 0, 1));
        if (!empty($_words[1])) $_initials .= strtoupper(substr($_words[1], 0, 1));
?>
    <tr>

        <!-- Checkbox -->
        <td>
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox vendorsCheck" type="checkbox" value="<?php echo $uid; ?>">
            </div>
        </td>

        <!-- S.No -->
        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Name + Avatar -->
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm me-1">
                    <?php if ($imgSrc): ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                             alt="<?php echo $name; ?>"
                             class="rounded-circle cursor-pointer preview-image"
                             data-src="<?php echo htmlspecialchars($imgSrc); ?>"
                             style="width:36px;height:36px;object-fit:cover;" />
                    <?php else: ?>
                        <span class="avatar-initial rounded-circle bg-label-warning"><?php echo $_initials; ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="fw-semibold"><?php echo $name; ?></div>
                    <?php $isActive = (int)($list->IsActive ?? 1); ?>
                    <div class="mt-1">
                        <div class="dropdown d-inline-block">
                            <span class="badge <?php echo $isActive ? 'bg-label-success' : 'bg-label-danger'; ?> cursor-pointer" style="font-size:.68rem;" data-bs-toggle="dropdown">
                                <?php echo $isActive ? 'Active' : 'In-Active'; ?>
                                <i class="bx bx-chevron-down" style="font-size:.65rem;"></i>
                            </span>
                            <ul class="dropdown-menu shadow-sm" style="min-width:150px;font-size:.82rem;">
                                <li>
                                    <button class="dropdown-item vend-status-toggle"
                                            data-uid="<?php echo $uid; ?>"
                                            data-newstatus="<?php echo $isActive ? 0 : 1; ?>">
                                        <?php if ($isActive): ?>
                                            <i class="bx bx-x-circle me-2 text-danger"></i>Mark In-Active
                                        <?php else: ?>
                                            <i class="bx bx-check-circle me-2 text-success"></i>Mark Active
                                        <?php endif; ?>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </td>

        <!-- Area -->
        <td>
            <?php if (!empty($list->Area)): ?>
                <span style="font-size:.82rem;"><?php echo htmlspecialchars($list->Area); ?></span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Mobile -->
        <td>
            <?php if (!empty($list->MobileNumber)): ?>
                <div><?php echo htmlspecialchars($list->MobileNumber); ?></div>
                <a href="https://wa.me/<?php echo htmlspecialchars($list->MobileNumber); ?>?text=Hi"
                   target="_blank" class="text-success" style="font-size:.75rem;">
                    <i class="bx bxl-whatsapp me-1"></i>WhatsApp
                </a>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- GSTIN / Company -->
        <td>
            <?php if (!empty($list->GSTIN)): ?>
                <div style="font-size:.82rem;font-family:monospace;"><?php echo htmlspecialchars($list->GSTIN); ?></div>
            <?php endif; ?>
            <?php if (!empty($list->CompanyName)): ?>
                <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars($list->CompanyName); ?></div>
            <?php else: ?>
                <?php if (empty($list->GSTIN)): ?><span class="text-muted">—</span><?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Closing Balance -->
        <td>
            <?php
                $bal     = (float)($list->ClosingBalance ?? 0);
                $balType = $list->ClosingBalanceType ?? 'Credit';
                if ($bal > 0):
                    $balClass = ($balType === 'Credit') ? 'text-danger' : 'text-success';
                    $balLabel = ($balType === 'Credit') ? 'To Pay' : 'To Collect';
            ?>
                <div class="fw-semibold <?php echo $balClass; ?>"><?php echo $currency . ' ' . number_format($bal, 2); ?></div>
                <div style="font-size:.72rem;color:#aaa;"><?php echo $balLabel; ?></div>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Updated On -->
        <td>
            <div style="font-size:.8rem;"><?php echo !empty($list->UpdatedOn) ? changeTimeZonefromDateTime($list->UpdatedOn, $JwtData->User->Timezone, 2) : '—'; ?></div>
            <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td>
            <div class="d-flex align-items-center justify-content-end gap-1">

                <a class="btn btn-icon btn-sm text-warning"
                   href="/vendors/<?php echo $uid; ?>/edit"
                   title="Edit">
                    <i class="bx bx-edit"></i>
                </a>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:170px;">

                        <li>
                            <a class="dropdown-item" href="/vendors/<?php echo $uid; ?>/clone">
                                <i class="bx bx-copy me-2 text-secondary"></i>Clone
                            </a>
                        </li>

                        <?php if (!empty($list->MobileNumber)): ?>
                        <li>
                            <a class="dropdown-item" href="https://wa.me/<?php echo htmlspecialchars($list->MobileNumber); ?>?text=Hi" target="_blank">
                                <i class="bx bxl-whatsapp me-2 text-success"></i>Send WhatsApp
                            </a>
                        </li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider my-1"></li>

                        <li>
                            <button class="dropdown-item text-danger DeleteVendor"
                                    data-vendoruid="<?php echo $uid; ?>"
                                    data-name="<?php echo $name; ?>">
                                <i class="bx bx-trash me-2"></i>Delete
                            </button>
                        </li>

                    </ul>
                </div>

            </div>
        </td>

    </tr>
<?php
    endforeach;
else:
?>
    <tr>
        <td colspan="9" style="padding:0;border:none;">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No vendors found</span>
                <a href="/vendors/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Create Vendor
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
