<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Accounts */       $Accounts       = $Accounts       ?? [];
/** @var float $GrandOpeningBal */$GrandOpeningBal= (float)($GrandOpeningBal ?? 0);
/** @var float $GrandPeriodDr */  $GrandPeriodDr  = (float)($GrandPeriodDr  ?? 0);
/** @var float $GrandPeriodCr */  $GrandPeriodCr  = (float)($GrandPeriodCr  ?? 0);
/** @var float $GrandClosingBal */$GrandClosingBal= (float)($GrandClosingBal ?? 0);
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$dfDisp  = date($dateFmt, strtotime($DateFrom));
$dtDisp  = date($dateFmt, strtotime($DateTo));

// Reference type → human label
$refLabels = [
    'Invoice'      => 'Sales / Invoice Receipts',
    'Payment-In'   => 'Customer Payment Receipts',
    'Return-In'    => 'Sales Return Receipts',
    'Purchase'     => 'Purchase / Bill Payments',
    'Payment-Out'  => 'Vendor Payment Outflows',
    'Return-Out'   => 'Purchase Return Outflows',
    'Recurring'    => 'Recurring Journal Entries',
    'Manual'       => 'Manual Journal Entries',
    'Bank Recon'   => 'Bank Reconciliation Adjustments',
];

/**
 * @param float  $n
 * @param string $cur
 * @param int    $dec
 * @param bool   $showSign
 * @returns string
 */
function _cfFmt(float $n, string $cur, int $dec, bool $showSign = false): string {
    if (abs($n) < 0.005) return '<span class="text-muted">—</span>';
    $prefix = $showSign ? ($n >= 0 ? '+' : '−') : '';
    return $prefix . $cur . ' ' . number_format(abs($n), $dec);
}

$grandNetChange = $GrandPeriodDr - $GrandPeriodCr;
$netPos         = $grandNetChange >= 0;
?>
<div class="cf-statement pl-print-header" id="cfStatement">

    <!-- Statement Header -->
    <div class="text-center mb-4">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold mt-1" style="font-size:.92rem;">Cash Flow Statement (Direct Method)</div>
        <div class="text-muted" style="font-size:.8rem;">
            Period: <?php echo $dfDisp; ?> to <?php echo $dtDisp; ?>
        </div>
    </div>

    <?php if (empty($Accounts)): ?>
    <div class="text-center text-muted py-4">
        <i class="bx bx-wallet fs-3 d-block mb-2"></i>
        No Cash or Bank accounts found.
    </div>
    <?php else: ?>

    <!-- ── Per-Account Sections ───────────────────────────────────────────── -->
    <?php foreach ($Accounts as $acc): ?>
    <div class="cf-account-block mb-4">

        <!-- Account Header -->
        <div class="cf-account-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bx <?php echo $acc->LedgerType === 'Cash' ? 'bx-money' : 'bx-bank'; ?> fs-5"></i>
                <span class="fw-semibold"><?php echo htmlspecialchars($acc->LedgerName); ?></span>
                <span class="badge bg-label-secondary" style="font-size:.65rem;"><?php echo $acc->LedgerType; ?></span>
            </div>
            <div class="text-end">
                <div class="text-muted" style="font-size:.72rem;">Net Change</div>
                <div class="fw-bold <?php echo $acc->NetChange >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-size:.88rem;">
                    <?php echo _cfFmt($acc->NetChange, $cur, $dec, true); ?>
                </div>
            </div>
        </div>

        <table class="table table-sm align-middle mb-0 cf-tbl">
            <tbody>

                <!-- Opening Balance -->
                <tr class="cf-balance-row">
                    <td class="fw-semibold" style="font-size:.82rem;">Opening Balance</td>
                    <td></td>
                    <td class="text-end fw-bold" style="font-size:.82rem;">
                        <?php echo _cfFmt($acc->OpeningBal, $cur, $dec); ?>
                        <?php if (abs($acc->OpeningBal) > 0.005): ?>
                        <span class="ms-1 text-muted" style="font-size:.68rem;"><?php echo $acc->OpeningBal >= 0 ? 'Dr' : 'Cr'; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Inflows -->
                <?php if (!empty($acc->Inflows)): ?>
                <tr class="cf-section-head cf-inflow-head">
                    <td colspan="3" class="fw-semibold" style="font-size:.76rem;">INFLOWS (Money Received)</td>
                </tr>
                <?php
                $totalIn = 0.0;
                arsort($acc->Inflows);
                foreach ($acc->Inflows as $refType => $amt):
                    $totalIn += $amt;
                    $label = $refLabels[$refType] ?? htmlspecialchars($refType);
                ?>
                <tr class="cf-entry-row cf-entry-in">
                    <td style="width:16px;border-left:3px solid #bbf7d0;"></td>
                    <td style="font-size:.82rem;padding-left:12px;"><?php echo $label; ?></td>
                    <td class="text-end text-success fw-semibold" style="font-size:.82rem;">
                        <?php echo _cfFmt($amt, $cur, $dec); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="cf-subtotal cf-inflow-subtotal">
                    <td colspan="2" class="fw-semibold" style="font-size:.78rem;">Total Inflows</td>
                    <td class="text-end fw-bold text-success" style="font-size:.82rem;">
                        <?php echo _cfFmt($totalIn, $cur, $dec); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- Outflows -->
                <?php if (!empty($acc->Outflows)): ?>
                <tr class="cf-section-head cf-outflow-head">
                    <td colspan="3" class="fw-semibold" style="font-size:.76rem;">OUTFLOWS (Money Paid)</td>
                </tr>
                <?php
                $totalOut = 0.0;
                arsort($acc->Outflows);
                foreach ($acc->Outflows as $refType => $amt):
                    $totalOut += $amt;
                    $label = $refLabels[$refType] ?? htmlspecialchars($refType);
                ?>
                <tr class="cf-entry-row cf-entry-out">
                    <td style="width:16px;border-left:3px solid #fecaca;"></td>
                    <td style="font-size:.82rem;padding-left:12px;"><?php echo $label; ?></td>
                    <td class="text-end text-danger fw-semibold" style="font-size:.82rem;">
                        (<?php echo _cfFmt($amt, $cur, $dec); ?>)
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="cf-subtotal cf-outflow-subtotal">
                    <td colspan="2" class="fw-semibold" style="font-size:.78rem;">Total Outflows</td>
                    <td class="text-end fw-bold text-danger" style="font-size:.82rem;">
                        (<?php echo _cfFmt($totalOut, $cur, $dec); ?>)
                    </td>
                </tr>
                <?php endif; ?>

                <!-- Closing Balance -->
                <tr class="cf-balance-row cf-closing-row">
                    <td class="fw-bold" style="font-size:.82rem;">Closing Balance</td>
                    <td></td>
                    <td class="text-end fw-bold" style="font-size:.88rem;
                        color:<?php echo $acc->ClosingBal >= 0 ? '#059669' : '#dc2626'; ?>;">
                        <?php echo _cfFmt($acc->ClosingBal, $cur, $dec); ?>
                        <?php if (abs($acc->ClosingBal) > 0.005): ?>
                        <span class="ms-1 text-muted" style="font-size:.68rem;"><?php echo $acc->ClosingBal >= 0 ? 'Dr' : 'Cr'; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <!-- ── Consolidated Summary ────────────────────────────────────────────── -->
    <?php if (count($Accounts) > 1): ?>
    <div class="cf-consolidated mt-2">
        <div class="cf-consolidated-head">CONSOLIDATED SUMMARY — All Cash &amp; Bank</div>
        <table class="table table-sm align-middle mb-0 cf-tbl">
            <tbody>
                <tr>
                    <td style="font-size:.82rem;">Total Opening Balance</td>
                    <td class="text-end fw-semibold" style="font-size:.82rem;"><?php echo _cfFmt($GrandOpeningBal, $cur, $dec); ?></td>
                </tr>
                <tr>
                    <td style="font-size:.82rem;color:#059669;">+ Total Inflows</td>
                    <td class="text-end fw-bold text-success" style="font-size:.82rem;"><?php echo _cfFmt($GrandPeriodDr, $cur, $dec); ?></td>
                </tr>
                <tr>
                    <td style="font-size:.82rem;color:#dc2626;">− Total Outflows</td>
                    <td class="text-end fw-bold text-danger" style="font-size:.82rem;">(<?php echo _cfFmt($GrandPeriodCr, $cur, $dec); ?>)</td>
                </tr>
                <tr class="cf-grand-net <?php echo $netPos ? 'cf-net-pos' : 'cf-net-neg'; ?>">
                    <td class="fw-bold" style="font-size:.85rem;">Net Change in Cash &amp; Bank</td>
                    <td class="text-end fw-bold" style="font-size:.88rem;">
                        <?php echo _cfFmt($grandNetChange, $cur, $dec, true); ?>
                    </td>
                </tr>
                <tr class="cf-grand-closing">
                    <td class="fw-bold" style="font-size:.85rem;">Total Closing Balance</td>
                    <td class="text-end fw-bold" style="font-size:.88rem;color:<?php echo $GrandClosingBal >= 0 ? '#059669' : '#dc2626'; ?>;">
                        <?php echo _cfFmt($GrandClosingBal, $cur, $dec); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
