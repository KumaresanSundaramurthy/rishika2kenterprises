<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Days */    $Days    = $Days    ?? [];
/** @var float $GrandDr */ $GrandDr = (float)($GrandDr ?? 0);
/** @var float $GrandCr */ $GrandCr = (float)($GrandCr ?? 0);
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$dfDisp  = date($dateFmt, strtotime($DateFrom));
$dtDisp  = date($dateFmt, strtotime($DateTo));

/**
 * @param float  $n
 * @param string $cur
 * @param int    $dec
 * @returns string
 */
function _dbFmt(float $n, string $cur, int $dec): string {
    if (abs($n) < 0.005) return '';
    return $cur . ' ' . number_format($n, $dec);
}

$refBadge = [
    'Manual'    => ['bg-label-secondary', 'bx-edit-alt'],
    'Recurring' => ['bg-label-info',      'bx-repeat'],
    'Invoice'   => ['bg-label-primary',   'bx-purchase-tag'],
    'Purchase'  => ['bg-label-warning',   'bx-cart'],
    'Payment-In'=> ['bg-label-success',   'bx-money'],
    'Payment-Out'=> ['bg-label-danger',   'bx-money'],
    'Return-In' => ['bg-label-dark',      'bx-undo'],
    'Return-Out'=> ['bg-label-dark',      'bx-redo'],
    'Bank Recon'=> ['bg-label-info',      'bx-bank'],
];
?>
<div id="dbEntriesWrap">

    <!-- Print header -->
    <div class="text-center mb-3 pt-3 pl-print-header px-3">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold" style="font-size:.92rem;">Day Book</div>
        <div class="text-muted" style="font-size:.8rem;">
            Period: <?php echo $dfDisp; ?> to <?php echo $dtDisp; ?>
        </div>
    </div>

    <?php if (empty($Days)): ?>
    <div class="text-center text-muted py-4" style="font-size:.88rem;">
        <i class="bx bx-calendar-x fs-3 d-block mb-2"></i>
        No journal entries found for this period.
    </div>
    <?php else: ?>

    <?php foreach ($Days as $dateKey => $day): ?>
    <!-- ── Date Group ───────────────────────────────────────────────── -->
    <div class="db-date-group">
        <div class="db-date-header d-flex align-items-center justify-content-between">
            <span class="fw-semibold">
                <i class="bx bx-calendar me-1"></i>
                <?php echo date($dateFmt, strtotime($dateKey)); ?>
            </span>
            <span class="db-daily-totals">
                <span class="text-success me-2"><i class="bx bx-trending-up me-1"></i>Dr <?php echo _dbFmt((float)$day['dailyDr'], $cur, $dec); ?></span>
                <span class="text-danger"><i class="bx bx-trending-down me-1"></i>Cr <?php echo _dbFmt((float)$day['dailyCr'], $cur, $dec); ?></span>
            </span>
        </div>

        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 db-tbl">
            <thead class="db-tbl-head">
                <tr>
                    <th style="width:130px;">Journal #</th>
                    <th style="width:110px;">Reference</th>
                    <th>Account</th>
                    <th>Narration / Particulars</th>
                    <th class="text-end" style="width:130px;">Debit</th>
                    <th class="text-end" style="width:130px;">Credit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($day['journals'] as $jid => $journal):
                $firstEntry = true;
                $refType    = $journal['ReferenceType'] ?? 'Manual';
                $badge      = $refBadge[$refType] ?? ['bg-label-secondary', 'bx-receipt'];
                foreach ($journal['entries'] as $entry):
                    $isDr = $entry->TransactionType === 'Debit';
            ?>
            <tr class="db-entry-row <?php echo $isDr ? 'db-row-dr' : 'db-row-cr'; ?>">
                <?php if ($firstEntry): ?>
                <td rowspan="<?php echo count($journal['entries']); ?>" class="db-journal-cell">
                    <span class="fw-semibold" style="font-size:.78rem;">
                        <?php echo htmlspecialchars($journal['JournalNo'] ?? ''); ?>
                    </span>
                    <div class="mt-1">
                        <span class="badge <?php echo $badge[0]; ?> db-ref-badge">
                            <i class="bx <?php echo $badge[1]; ?> me-1"></i>
                            <?php echo htmlspecialchars($refType); ?>
                        </span>
                    </div>
                    <?php if (!empty($journal['ReferenceNo'])): ?>
                    <div class="text-muted mt-1" style="font-size:.7rem;"><?php echo htmlspecialchars($journal['ReferenceNo']); ?></div>
                    <?php endif; ?>
                </td>
                <?php $firstEntry = false; ?>
                <?php endif; ?>

                <td style="font-size:.78rem;"></td>

                <td>
                    <span style="font-size:.82rem;"><?php echo htmlspecialchars($entry->LedgerName ?? ''); ?></span>
                    <span class="badge bg-label-secondary ms-1" style="font-size:.62rem;"><?php echo htmlspecialchars($entry->LedgerType ?? ''); ?></span>
                </td>

                <td style="font-size:.78rem;color:#6c757d;">
                    <?php
                    $narr = !empty($entry->Particulars) ? $entry->Particulars : ($journal['Narration'] ?? '');
                    echo htmlspecialchars($narr);
                    ?>
                </td>

                <td class="text-end fw-semibold text-success db-amt" style="font-size:.82rem;">
                    <?php echo $isDr ? _dbFmt((float)$entry->Amount, $cur, $dec) : ''; ?>
                </td>
                <td class="text-end fw-semibold text-danger db-amt" style="font-size:.82rem;">
                    <?php echo !$isDr ? _dbFmt((float)$entry->Amount, $cur, $dec) : ''; ?>
                </td>
            </tr>
            <?php endforeach; // entries
            endforeach; // journals ?>
            </tbody>
            <tfoot>
                <tr class="db-daily-total">
                    <td colspan="4" class="text-end fw-bold" style="font-size:.78rem;">
                        Day Total
                    </td>
                    <td class="text-end fw-bold text-success" style="font-size:.82rem;">
                        <?php echo _dbFmt((float)$day['dailyDr'], $cur, $dec); ?>
                    </td>
                    <td class="text-end fw-bold text-danger" style="font-size:.82rem;">
                        <?php echo _dbFmt((float)$day['dailyCr'], $cur, $dec); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
    <?php endforeach; // days ?>

    <!-- ── Grand Total ──────────────────────────────────────────────── -->
    <div class="db-grand-total d-flex justify-content-end align-items-center gap-4 px-3 py-2">
        <div class="fw-bold" style="font-size:.85rem;">Grand Total</div>
        <div>
            <span class="text-success fw-bold" style="font-size:.85rem;">
                Dr <?php echo $cur . ' ' . number_format($GrandDr, $dec); ?>
            </span>
            <span class="mx-2 text-muted">|</span>
            <span class="text-danger fw-bold" style="font-size:.85rem;">
                Cr <?php echo $cur . ' ' . number_format($GrandCr, $dec); ?>
            </span>
        </div>
    </div>

    <?php endif; ?>

</div>
