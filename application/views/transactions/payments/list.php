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

function pmtModeBadge($name, $modeColors) {
    $key   = strtolower(trim($name ?? ''));
    $style = isset($modeColors[$key])
        ? 'background:' . $modeColors[$key]['bg'] . ';color:' . $modeColors[$key]['color'] . ';'
        : 'background:#f0f0f0;color:#555;';
    return '<span class="pmt-mode-badge" style="' . $style . '">' . htmlspecialchars($name ?? '—') . '</span>';
}
?>

<?php if (!empty($DataLists)): ?>
    <?php foreach ($DataLists as $row): ?>
    <?php
        // Avatar initials
        $words    = preg_split('/\s+/', trim($row->PartyName ?? ''));
        $initials = strtoupper(substr($words[0] ?? '', 0, 1));
        if (!empty($words[1])) $initials .= strtoupper(substr($words[1], 0, 1));
        $avatarColors = ['#4f46e5','#0891b2','#059669','#d97706','#db2777','#7c3aed'];
        $avatarColor  = $avatarColors[crc32($row->PartyName ?? '') % count($avatarColors)];

        // "X mins/hours ago" for created within 24 h
        $createdTs  = strtotime($row->CreatedOn);
        $diffSec    = time() - $createdTs;
        $showAgo    = ($diffSec >= 0 && $diffSec < 86400);
        $agoText    = '';
        if ($showAgo) {
            $mins = (int)floor($diffSec / 60);
            if ($mins < 60)       $agoText = $mins . 'm ago';
            else                  $agoText = (int)floor($mins / 60) . 'h ago';
        }
        $dateFormatted = date('d M Y, h:i A', $createdTs);

        // Mobile with country code
        $countryCode = trim($row->PartyCountryCode ?? '');
        $mobileNum   = trim($row->PartyMobile ?? '');
        $fullMobile  = ($countryCode && $countryCode !== '+91' && $countryCode !== '91'
                        ? $countryCode . ' ' : '+91 ') . $mobileNum;
        if (empty($mobileNum)) $fullMobile = '';
    ?>
    <tr>

        <!-- # Invoice (PaymentUniqueNumber) -->
        <td class="ps-3">
            <?php if (!empty($row->PaymentUniqueNumber)): ?>
                <span class="fw-semibold text-primary" style="font-size:.82rem;">
                    <?php echo htmlspecialchars($row->PaymentUniqueNumber); ?>
                </span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Amount -->
        <td class="ps-3">
            <div class="fw-semibold text-dark" style="font-size:.88rem;">
                <?php echo htmlspecialchars($cur); ?> <?php echo number_format((float)$row->Amount, $dec, '.', ','); ?>
            </div>
            <?php if ($row->ExcessAmount > 0): ?>
                <div style="font-size:.7rem;color:#f59e0b;">Excess: <?php echo htmlspecialchars($cur); ?> <?php echo number_format((float)$row->ExcessAmount, $dec); ?></div>
            <?php endif; ?>
        </td>

        <!-- Mode + Bank Details (merged) -->
        <td>
            <?php echo pmtModeBadge($row->PaymentTypeName, $modeColors); ?>
            <?php if (!$row->IsCash && !empty($row->BankName)): ?>
                <div style="font-size:.76rem;font-weight:500;color:#444;margin-top:4px;"><?php echo htmlspecialchars($row->BankName); ?></div>
                <?php if (!empty($row->AccountNumber)): ?>
                    <div class="text-muted" style="font-size:.68rem;font-family:monospace;"><?php echo htmlspecialchars($row->AccountNumber); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Linked Document -->
        <td>
            <?php if (!empty($row->TransNumber)): ?>
                <span class="fw-semibold text-primary" style="font-size:.82rem;"><?php echo htmlspecialchars($row->TransNumber); ?></span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Party Name + Mobile -->
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:30px;height:30px;background:<?php echo $avatarColor; ?>1a;color:<?php echo $avatarColor; ?>;font-size:.7rem;font-weight:700;">
                    <?php echo $initials ?: '?'; ?>
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:600;"><?php echo htmlspecialchars($row->PartyName ?? '—'); ?></div>
                    <?php if ($fullMobile): ?>
                        <span class="copy-mobile cursor-pointer"
                              data-mobile="<?php echo htmlspecialchars($fullMobile); ?>"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top"
                              title="Click to copy mobile number"
                              style="font-size:.72rem;color:#666;">
                            <?php echo htmlspecialchars($fullMobile); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </td>

        <!-- Created By + Date + ago -->
        <td>
            <div style="font-size:.82rem;font-weight:500;"><?php echo htmlspecialchars($row->CreatedByName ?? '—'); ?></div>
            <div class="text-muted" style="font-size:.72rem;"><?php echo $dateFormatted; ?></div>
            <?php if ($showAgo && $agoText): ?>
                <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
            <?php endif; ?>
        </td>

        <!-- Actions -->
        <td class="text-end pe-3">
            <div class="dropdown">
                <button class="trans-actions-btn" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bx bx-dots-vertical-rounded fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">
                    <li>
                        <button class="dropdown-item viewPaymentDetail"
                                data-payment-uid="<?php echo (int)$row->PaymentUID; ?>">
                            <i class="bx bx-show me-2 text-primary"></i>View Details
                        </button>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <button class="dropdown-item cancelPayment text-warning"
                                data-payment-uid="<?php echo (int)$row->PaymentUID; ?>">
                            <i class="bx bx-x-circle me-2"></i>Cancel Payment
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item deletePayment text-danger"
                                data-payment-uid="<?php echo (int)$row->PaymentUID; ?>">
                            <i class="bx bx-trash me-2"></i>Delete
                        </button>
                    </li>
                </ul>
            </div>
        </td>

    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="7" class="text-center py-5">
            <i class="bx bx-credit-card-front fs-2 text-muted d-block mb-2"></i>
            <span class="text-muted" style="font-size:.88rem;">No payment records found.</span>
        </td>
    </tr>
<?php endif; ?>
