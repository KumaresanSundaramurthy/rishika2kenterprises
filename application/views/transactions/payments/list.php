<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$cur = $JwtData->GenSettings->CurrenySymbol ?? '₹';
$dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

// Org info for message formatting
$orgName   = $OrgInfo->BrandName ?? $OrgInfo->Name ?? '';
$orgMobile = $OrgInfo->MobileNumber ?? '';
$appUrl    = rtrim(getenv('HTTP_HOST_URL') ?: '', '/');

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

<style>
/* Contact icon strip — hidden by default, shown on row hover */
.pmt-row .pmt-contact-icons {
    display: none;
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    align-items: center;
    gap: 4px;
    padding: 0 8px;
    background: linear-gradient(to right, transparent, #f0f4ff 30%);
}
.pmt-row:hover .pmt-contact-icons { display: flex; }
.pmt-contact-icons a,
.pmt-contact-icons button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.3rem;
    text-decoration: none;
    transition: transform .12s;
    flex-shrink: 0;
    border: none;
    cursor: pointer;
    background: transparent;
    padding: 0;
}
.pmt-contact-icons a:hover,
.pmt-contact-icons button:hover { transform: scale(1.18); }
.pmt-contact-icons a.wa,
.pmt-contact-icons button.wa  { color: #25d366; background: rgba(37,211,102,0.15); }
.pmt-contact-icons a.sms,
.pmt-contact-icons button.sms { color: #0097a7; background: rgba(0,151,167,0.15); }
.pmt-contact-icons a.em,
.pmt-contact-icons button.em  { color: #1565c0; background: rgba(21,101,192,0.15); }
.pmt-party-td { position: relative; }
</style>

<?php if (!empty($DataLists)): ?>
    <?php foreach ($DataLists as $row): ?>
    <?php
        $words    = preg_split('/\s+/', trim($row->PartyName ?? ''));
        $initials = strtoupper(substr($words[0] ?? '', 0, 1));
        if (!empty($words[1])) $initials .= strtoupper(substr($words[1], 0, 1));
        $avatarColors = ['#4f46e5','#0891b2','#059669','#d97706','#db2777','#7c3aed'];
        $avatarColor  = $avatarColors[crc32($row->PartyName ?? '') % count($avatarColors)];

        $createdTs     = strtotime($row->CreatedOn);
        $diffSec       = time() - $createdTs;
        $showAgo       = ($diffSec >= 0 && $diffSec < 86400);
        $agoText       = '';
        if ($showAgo) {
            $mins = (int)floor($diffSec / 60);
            $agoText = $mins < 60 ? $mins . 'm ago' : (int)floor($mins / 60) . 'h ago';
        }
        $dateFormatted = date('d M Y, h:i A', $createdTs);

        $countryCode = trim($row->PartyCountryCode ?? '');
        $mobileNum   = trim($row->PartyMobile ?? '');
        $fullMobile  = ($countryCode && $countryCode !== '+91' && $countryCode !== '91'
                        ? $countryCode . ' ' : '+91 ') . $mobileNum;
        if (empty($mobileNum)) $fullMobile = '';

        $partyEmail  = trim($row->PartyEmail ?? '');
        $waNum       = $fullMobile ? preg_replace('/[^0-9]/', '', $fullMobile) : '';
        $smsNum      = $fullMobile ? preg_replace('/[^0-9+]/', '', $fullMobile) : '';

        $transType   = ($row->PartyType === 'C') ? 'invoice' : 'purchase';
        $transModule = ($row->PartyType === 'C') ? 103 : 105;
        $receiptToken = trim($row->ReceiptToken ?? '');
        $receiptUrl   = $receiptToken ? $appUrl . '/receipt/' . $receiptToken : '';

        // Format the WhatsApp / SMS message
        $partyFirstName = trim(explode(' ', trim($row->PartyName ?? ''))[0]);
        $amtFormatted   = $cur . ' ' . number_format((float)$row->Amount, $dec, '.', ',');
        $payStatus      = $row->IsFullyPaid ? 'Paid' : 'Partially Paid';
        $receiptNum     = $row->PaymentUniqueNumber ?? '';

        $shareMsg = "Hello *{$row->PartyName}*,\n\n"
                  . "Thanks for your business!\n\n"
                  . ($receiptNum  ? "*Receipt: {$receiptNum}*\n" : '')
                  . "*Total: {$amtFormatted}*\n"
                  . "*Payment Status: {$payStatus}*\n"
                  . ($receiptUrl  ? "*Link:* {$receiptUrl}\n" : '')
                  . "\nThanks\n"
                  . "*{$orgName}*\n"
                  . ($orgMobile   ? "*{$orgMobile}*" : '');
    ?>
    <tr class="pmt-row"
        data-uid="<?php echo (int)$row->PaymentUID; ?>"
        data-unique-number="<?php echo htmlspecialchars($row->PaymentUniqueNumber ?? ''); ?>"
        data-trans-uid="<?php echo (int)($row->TransUID ?? 0); ?>"
        data-trans-module="<?php echo $transModule; ?>"
        data-trans-type="<?php echo $transType; ?>"
        data-trans-number="<?php echo htmlspecialchars($row->TransNumber ?? ''); ?>"
        data-amount="<?php echo number_format((float)$row->Amount, $dec, '.', ','); ?>"
        data-raw-amount="<?php echo (float)$row->Amount; ?>"
        data-payment-date="<?php echo htmlspecialchars($row->PaymentDate ?? $row->CreatedOn ?? ''); ?>"
        data-payment-type="<?php echo htmlspecialchars($row->PaymentTypeName ?? ''); ?>"
        data-is-cash="<?php echo (int)$row->IsCash; ?>"
        data-party-uid="<?php echo (int)($row->PartyUID ?? 0); ?>"
        data-party-type="<?php echo htmlspecialchars($row->PartyType ?? 'C'); ?>"
        data-party-name="<?php echo htmlspecialchars($row->PartyName ?? ''); ?>"
        data-party-mobile="<?php echo htmlspecialchars($fullMobile); ?>"
        data-party-email="<?php echo htmlspecialchars($partyEmail); ?>"
        data-reference="<?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?>"
        data-created-by="<?php echo htmlspecialchars($row->CreatedByName ?? ''); ?>"
        data-notes="<?php echo htmlspecialchars($row->Notes ?? ''); ?>"
        data-bank-name="<?php echo htmlspecialchars($row->BankName ?? ''); ?>"
        data-account-name="<?php echo htmlspecialchars($row->AccountName ?? ''); ?>"
        data-account-number="<?php echo htmlspecialchars($row->AccountNumber ?? ''); ?>"
        data-ifsc="<?php echo htmlspecialchars($row->IFSC ?? ''); ?>"
        data-branch="<?php echo htmlspecialchars($row->BranchName ?? ''); ?>">

        <!-- Ref No -->
        <td class="ps-3">
            <?php if (!empty($row->PaymentUniqueNumber)): ?>
                <a href="javascript:void(0);" class="trans-doc-number viewPaymentDetail">
                    <?php echo htmlspecialchars($row->PaymentUniqueNumber); ?>
                </a>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Amount -->
        <td class="ps-3">
            <div class="fw-semibold text-dark" style="font-size:.88rem;">
                <?php echo htmlspecialchars($cur); ?> <?php echo number_format((float)$row->Amount, $dec, '.', ','); ?>
            </div>
            <?php if (!empty($row->IsOnAccount)): ?>
                <div style="margin-top:3px;">
                    <span style="font-size:.68rem;font-weight:600;padding:2px 7px;border-radius:10px;background:#fff3cd;color:#856404;border:1px solid #ffc107;">
                        On Account
                    </span>
                </div>
            <?php elseif ($row->ExcessAmount > 0): ?>
                <div style="font-size:.7rem;color:#f59e0b;">Excess: <?php echo htmlspecialchars($cur); ?> <?php echo number_format((float)$row->ExcessAmount, $dec); ?></div>
            <?php endif; ?>
        </td>

        <!-- Mode + Bank -->
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
                <a href="javascript:void(0);" class="trans-doc-number viewTransaction"
                   data-uid="<?php echo (int)($row->TransUID ?? 0); ?>"
                   data-module="<?php echo $transModule; ?>"
                   data-type="<?php echo $transType; ?>"
                   data-number="<?php echo htmlspecialchars($row->TransNumber); ?>"
                   data-date=""
                   data-status="">
                    <?php echo htmlspecialchars($row->TransNumber); ?>
                </a>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Party Name + Contact icons (hover overlay) -->
        <td class="pmt-party-td">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:30px;height:30px;background:<?php echo $avatarColor; ?>1a;color:<?php echo $avatarColor; ?>;font-size:.7rem;font-weight:700;">
                    <?php echo $initials ?: '?'; ?>
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:600;"><?php echo htmlspecialchars($row->PartyName ?? '—'); ?></div>
                    <?php if (!empty($row->PartyArea)): ?>
                    <div style="font-size:.7rem;color:#888;margin-top:1px;">
                        <i class="bx bx-map" style="font-size:.72rem;"></i> <?php echo htmlspecialchars($row->PartyArea); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($fullMobile): ?>
                    <div style="font-size:.72rem;color:#666;margin-top:1px;"><?php echo htmlspecialchars($fullMobile); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Icon strip: shown on row hover, absolutely overlaid on right -->
            <?php if ($fullMobile || $partyEmail): ?>
            <div class="pmt-contact-icons">
                <?php if ($fullMobile): ?>
                <a href="javascript:void(0)" data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=<?php echo rawurlencode($shareMsg); ?>" target="_blank" class="wa pmt-wa-link" title="WhatsApp"><i class="bx bxl-whatsapp"></i></a>
                <button class="comm-send-single sms" title="Send SMS"
                    data-commtype="SMS"
                    data-recipienttype="<?php echo $row->PartyType === 'C' ? 'Customer' : 'Vendor'; ?>"
                    data-uid="<?php echo (int)($row->PartyUID ?? 0); ?>"
                    data-name="<?php echo htmlspecialchars($row->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo (int)$PmtModuleUID; ?>">
                    <i class="bx bx-message-dots"></i>
                </button>
                <?php endif; ?>
                <?php if ($partyEmail): ?>
                <button class="comm-send-single em" title="Send Email"
                    data-commtype="Email"
                    data-recipienttype="<?php echo $row->PartyType === 'C' ? 'Customer' : 'Vendor'; ?>"
                    data-uid="<?php echo (int)($row->PartyUID ?? 0); ?>"
                    data-name="<?php echo htmlspecialchars($row->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo (int)$PmtModuleUID; ?>"
                    data-context='<?php echo htmlspecialchars(json_encode([
                        'PartyName'     => $row->PartyName ?? '',
                        'DocNumber'     => $row->TransNumber ?? '',
                        'Amount'        => (float)$row->Amount,
                        'ReceiptNumber' => $row->PaymentUniqueNumber ?? '',
                        'PaymentMode'   => $row->PaymentTypeName ?? '',
                        'PaymentStatus' => $row->IsFullyPaid ? 'Paid' : 'Partially Paid',
                        'ReceiptLink'   => $receiptUrl,
                    ]), ENT_QUOTES); ?>'>
                    <i class="bx bx-envelope"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <!-- Created By + Date -->
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
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:180px;">
                    <li>
                        <button class="dropdown-item pmtA4Print"
                                data-payment-uid="<?php echo (int)$row->PaymentUID; ?>">
                            <i class="bx bx-printer me-2 text-dark"></i>Print / Download
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item pmtDownloadPdf"
                                data-payment-uid="<?php echo (int)$row->PaymentUID; ?>"
                                data-num="<?php echo htmlspecialchars($row->PaymentUniqueNumber ?? ''); ?>">
                            <i class="bx bx-download me-2 text-primary"></i>Download PDF
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item pmtThermalPrint"
                                data-payment-uid="<?php echo (int)$row->PaymentUID; ?>">
                            <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                        </button>
                    </li>

                    <?php if ($fullMobile || $partyEmail): ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <?php if ($fullMobile): ?>
                    <li>
                        <a class="dropdown-item pmt-wa-link"
                           href="javascript:void(0)"
                           data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=<?php echo rawurlencode($shareMsg); ?>"
                           style="color:#25d366;">
                            <i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp
                        </a>
                    </li>
                    <li>
                        <button class="dropdown-item comm-send-single"
                                data-commtype="SMS"
                                data-recipienttype="<?php echo $row->PartyType === 'C' ? 'Customer' : 'Vendor'; ?>"
                                data-uid="<?php echo (int)($row->PartyUID ?? 0); ?>"
                                data-name="<?php echo htmlspecialchars($row->PartyName ?? ''); ?>"
                                data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                data-module-uid="<?php echo (int)$PmtModuleUID; ?>"
                                style="color:#0097a7;">
                            <i class="bx bx-message-dots me-2"></i>Send SMS
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if ($partyEmail): ?>
                    <li>
                        <button class="dropdown-item comm-send-single"
                                data-commtype="Email"
                                data-recipienttype="<?php echo $row->PartyType === 'C' ? 'Customer' : 'Vendor'; ?>"
                                data-uid="<?php echo (int)($row->PartyUID ?? 0); ?>"
                                data-name="<?php echo htmlspecialchars($row->PartyName ?? ''); ?>"
                                data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                data-module-uid="<?php echo (int)$PmtModuleUID; ?>"
                                data-context='<?php echo htmlspecialchars(json_encode([
                                    'PartyName'     => $row->PartyName ?? '',
                                    'DocNumber'     => $row->TransNumber ?? '',
                                    'Amount'        => (float)$row->Amount,
                                    'ReceiptNumber' => $row->PaymentUniqueNumber ?? '',
                                    'PaymentMode'   => $row->PaymentTypeName ?? '',
                                    'PaymentStatus' => $row->IsFullyPaid ? 'Paid' : 'Partially Paid',
                                    'ReceiptLink'   => $receiptUrl,
                                ]), ENT_QUOTES); ?>'
                                style="color:#1565c0;">
                            <i class="bx bx-envelope me-2"></i>Send Email
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>

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
        <td colspan="7">
            <div class="d-flex flex-column align-items-center justify-content-center py-5" style="gap:10px;">
                <div style="width:56px;height:56px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;">
                    <i class="bx bx-credit-card-front" style="font-size:1.6rem;color:#0d6efd;"></i>
                </div>
                <div class="fw-semibold text-dark" style="font-size:.92rem;">No Payments Yet</div>
                <div class="text-muted" style="font-size:.8rem;">Payments received from customers will appear here.</div>
            </div>
        </td>
    </tr>
<?php endif; ?>
