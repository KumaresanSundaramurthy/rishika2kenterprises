<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$cur = $JwtData->GenSettings->CurrenySymbol ?? '₹';
$dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

// Payment type → badge colour
$modeColors = [
    'cash'        => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
    'upi'         => ['bg' => '#ede7f6', 'color' => '#4527a0'],
    'card'        => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
    'net banking' => ['bg' => '#fff8e1', 'color' => '#f57f17'],
    'cheque'      => ['bg' => '#fce4ec', 'color' => '#880e4f'],
    'emi'         => ['bg' => '#e0f7fa', 'color' => '#00695c'],
    'tds'         => ['bg' => '#f3e5f5', 'color' => '#6a1b9a'],
];

function pmtModeBadge($name, $code, $modeColors) {
    $key = strtolower(trim($name ?? ''));
    $style = isset($modeColors[$key])
        ? 'background:' . $modeColors[$key]['bg'] . ';color:' . $modeColors[$key]['color'] . ';'
        : 'background:#f0f0f0;color:#555;';
    return '<span class="pmt-mode-badge" style="' . $style . '">' . htmlspecialchars($name ?? '—') . '</span>';
}
?>

<?php if (!empty($DataLists)): ?>
    <?php foreach ($DataLists as $row): ?>
    <?php
        $initials = '';
        $words = preg_split('/\s+/', trim($row->PartyName ?? ''));
        $initials .= strtoupper(substr($words[0] ?? '', 0, 1));
        if (!empty($words[1])) $initials .= strtoupper(substr($words[1], 0, 1));
        $avatarColors = ['#4f46e5','#0891b2','#059669','#d97706','#db2777','#7c3aed'];
        $avatarColor  = $avatarColors[crc32($row->PartyName ?? '') % count($avatarColors)];
    ?>
    <tr>

        <!-- Amount -->
        <td class="ps-3">
            <div class="fw-semibold text-dark" style="font-size:.88rem;">
                <?php echo htmlspecialchars($cur); ?> <?php echo number_format((float)$row->Amount, $dec, '.', ','); ?>
            </div>
            <?php if ($row->ExcessAmount > 0): ?>
                <div style="font-size:.7rem;color:#f59e0b;">Excess: <?php echo htmlspecialchars($cur); ?> <?php echo number_format((float)$row->ExcessAmount, $dec); ?></div>
            <?php endif; ?>
        </td>

        <!-- Mode -->
        <td>
            <?php echo pmtModeBadge($row->PaymentTypeName, $row->PaymentTypeCode ?? '', $modeColors); ?>
        </td>

        <!-- Linked Documents -->
        <td>
            <?php if (!empty($row->TransNumber)): ?>
                <span class="fw-semibold text-primary" style="font-size:.82rem;"><?php echo htmlspecialchars($row->TransNumber); ?></span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Party Name -->
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:30px;height:30px;background:<?php echo $avatarColor; ?>1a;color:<?php echo $avatarColor; ?>;font-size:.7rem;font-weight:700;">
                    <?php echo $initials ?: '?'; ?>
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:600;"><?php echo htmlspecialchars($row->PartyName ?? '—'); ?></div>
                    <?php if (!empty($row->PartyMobile)): ?>
                        <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($row->PartyMobile); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </td>

        <!-- Date / Created Time -->
        <td>
            <div style="font-size:.82rem;font-weight:500;"><?php echo !empty($row->TransDate) ? date('d-m-Y', strtotime($row->TransDate)) : '—'; ?></div>
            <div class="text-muted" style="font-size:.72rem;"><?php echo date('d M y, h:i A', strtotime($row->CreatedOn)); ?></div>
        </td>

        <!-- Bank Details -->
        <td>
            <?php if (!empty($row->BankName)): ?>
                <div style="font-size:.82rem;font-weight:500;"><?php echo htmlspecialchars($row->BankName); ?></div>
                <?php if (!empty($row->AccountNumber)): ?>
                    <div class="text-muted" style="font-size:.7rem;font-family:monospace;">A/C NO: <?php echo htmlspecialchars($row->AccountNumber); ?></div>
                <?php endif; ?>
            <?php elseif ($row->IsCash): ?>
                <span class="pmt-mode-badge" style="background:#e8f5e9;color:#2e7d32;">Cash</span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Created By -->
        <td>
            <div style="font-size:.8rem;"><?php echo htmlspecialchars($row->CreatedByName ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td class="text-end pe-3">
            <div class="d-flex align-items-center justify-content-end gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary viewPaymentDetail"
                        data-payment-uid="<?php echo (int)$row->PaymentUID; ?>"
                        title="View" style="font-size:.75rem;padding:2px 10px;">
                    <i class="bx bx-show me-1"></i>View
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger deletePayment"
                        data-payment-uid="<?php echo (int)$row->PaymentUID; ?>"
                        title="Delete">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        </td>

    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="8" class="text-center py-5">
            <i class="bx bx-credit-card-front fs-2 text-muted d-block mb-2"></i>
            <span class="text-muted" style="font-size:.88rem;">No payment records found.</span>
        </td>
    </tr>
<?php endif; ?>
