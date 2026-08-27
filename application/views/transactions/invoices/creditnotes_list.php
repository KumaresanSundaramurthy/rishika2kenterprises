<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$currency    = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals    = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$timezone    = $JwtData->User->Timezone ?? 'Asia/Kolkata';

if (!empty($DataLists)):
    foreach ($DataLists as $cn):
        $SerialNumber++;

        $statusMap   = ['Pending' => 'bg-label-warning', 'Applied' => 'bg-label-success', 'Refunded' => 'bg-label-secondary', 'Cancelled' => 'bg-label-danger'];
        $statusClass = $statusMap[$cn->Status ?? ''] ?? 'bg-label-secondary';
        $amt         = smartDecimal((float)($cn->Amount ?? 0));
        $isPending   = ($cn->Status ?? '') === 'Pending';

        // Created On — time ago
        $createdOn  = $cn->CreatedOn ?? null;
        $createdFmt = $createdOn ? changeTimeZonefromDateTime($createdOn, $timezone, 2) : '—';
        $secondsAgo = $createdOn ? (time() - strtotime($createdOn)) : null;
        $within24h  = $secondsAgo !== null && $secondsAgo < 86400;
        if ($within24h) {
            if ($secondsAgo < 60)       $agoText = 'just now';
            elseif ($secondsAgo < 3600) $agoText = (int)($secondsAgo / 60) . ' min' . ((int)($secondsAgo / 60) > 1 ? 's' : '') . ' ago';
            else                        $agoText = (int)($secondsAgo / 3600) . ' hr'  . ((int)($secondsAgo / 3600) > 1 ? 's' : '') . ' ago';
        }

        // Source date
        $srcDate = ($cn->SourceTransDate ?? '') ? changeTimeZonefromDateTime($cn->SourceTransDate, $timezone, 1) : '—';

        // Customer initials
        $custName = $cn->CustomerName ?? '';
        $words    = $custName ? explode(' ', trim($custName)) : [];
        $initials = $words ? strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : '')) : '';
?>
<tr>
    <td class="text-muted"><?php echo $SerialNumber; ?></td>

    <!-- CN Number -->
    <td>
        <span class="fw-semibold trans-doc-number"><?php echo htmlspecialchars($cn->CreditNoteNumber ?? '—'); ?></span>
        <?php if ($createdFmt !== '—'): ?>
        <div class="text-muted mt-1" style="font-size:.72rem;"><?php echo $createdFmt; ?></div>
        <?php endif; ?>
        <?php if ($cn->CreatorName ?? ''): ?>
        <div style="font-size:.68rem;color:#b0b7c3;">by <?php echo htmlspecialchars($cn->CreatorName); ?></div>
        <?php endif; ?>
    </td>

    <!-- Customer -->
    <td class="inv-party-td">
        <?php if ($custName): ?>
        <div class="d-flex align-items-center gap-2">
            <div class="trans-party-avatar" style="background:#696cff22;color:#696cff;font-size:.75rem;"><?php echo $initials; ?></div>
            <div>
                <div class="trans-party-name fw-semibold"><?php echo htmlspecialchars($custName); ?></div>
                <?php if ($cn->CustomerArea ?? ''): ?>
                <div style="font-size:.7rem;color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:.72rem;"></i> <?php echo htmlspecialchars($cn->CustomerArea); ?></div>
                <?php endif; ?>
                <?php if ($cn->MobileNo ?? ''): ?>
                <div style="font-size:.72rem;color:#888;"><?php echo htmlspecialchars($cn->MobileNo); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Source -->
    <td>
        <?php if (($cn->SourceTransUID ?? 0) > 0 && ($cn->SourceTransNumber ?? '')): ?>
        <a href="javascript:void(0)" class="fw-semibold trans-doc-number viewTransaction"
           data-uid="<?php echo (int)$cn->SourceTransUID; ?>"
           data-module="<?php echo (int)($cn->SourceModuleUID ?? 0); ?>"
           <?php if ($cn->SourceTransToken ?? ''): ?>data-token="<?php echo htmlspecialchars($cn->SourceTransToken); ?>"<?php endif; ?>>
            <?php echo htmlspecialchars($cn->SourceTransNumber); ?>
        </a>
        <?php else: ?>
        <span class="text-muted"><?php echo htmlspecialchars($cn->SourceTransNumber ?? '—'); ?></span>
        <?php endif; ?>
        <?php if ($srcDate !== '—'): ?>
        <div class="text-muted mt-1" style="font-size:.72rem;"><?php echo $srcDate; ?></div>
        <?php endif; ?>
    </td>

    <!-- Amount -->
    <td class="fw-semibold"><?php echo $currency . ' ' . $amt; ?></td>

    <!-- Status -->
    <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($cn->Status ?? ''); ?></span></td>

    <!-- Created On -->
    <td>
        <?php if ($createdFmt !== '—'): ?>
        <div style="font-size:.8rem;"><?php echo $createdFmt; ?></div>
        <?php if ($within24h): ?>
        <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText ?? ''; ?></div>
        <?php endif; ?>
        <?php if ($cn->CreatorName ?? ''): ?>
        <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars($cn->CreatorName); ?></div>
        <?php endif; ?>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Actions -->
    <td class="text-center">
        <?php if ($isPending): ?>
        <div class="dropdown">
            <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-dots-vertical-rounded fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">
                <li>
                    <a class="dropdown-item cn-cancel-btn" href="javascript:void(0)"
                       data-uid="<?php echo (int)$cn->CreditNoteUID; ?>"
                       data-number="<?php echo htmlspecialchars($cn->CreditNoteNumber ?? '—'); ?>"
                       data-amount="<?php echo $amt; ?>">
                        <i class="bx bx-x-circle me-2 text-warning"></i>Cancel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item cn-delete-btn" href="javascript:void(0)"
                       data-uid="<?php echo (int)$cn->CreditNoteUID; ?>"
                       data-number="<?php echo htmlspecialchars($cn->CreditNoteNumber ?? '—'); ?>"
                       data-amount="<?php echo $amt; ?>">
                        <i class="bx bx-trash me-2 text-danger"></i>Delete
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </td>
</tr>
<?php
    endforeach;
else:
?>
<tr><td colspan="8" class="text-center py-4 text-muted">No credit notes found.</td></tr>
<?php endif; ?>
