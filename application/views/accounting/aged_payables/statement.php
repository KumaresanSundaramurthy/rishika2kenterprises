<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Rows */   $Rows    = $Rows    ?? [];
/** @var array $Totals */ $Totals  = $Totals  ?? [];
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$asOfDisp = date($dateFmt, strtotime($AsOfDate));

/**
 * @param float  $n
 * @param string $cur
 * @param int    $dec
 * @returns string
 */
function _apFmt(float $n, string $cur, int $dec): string {
    if (abs($n) < 0.005) return '<span class="text-muted aged-dash">—</span>';
    return $cur . ' ' . number_format($n, $dec);
}
?>
<div class="aged-statement pl-print-header" id="apStatement">

    <div class="text-center mb-3">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold" style="font-size:.92rem;">Aged Payables</div>
        <div class="text-muted" style="font-size:.8rem;">As of <?php echo $asOfDisp; ?></div>
    </div>

    <?php if (empty($Rows)): ?>
    <div class="text-center text-muted py-4" style="font-size:.88rem;">
        <i class="bx bx-check-circle fs-3 d-block mb-2 text-success"></i>
        No outstanding payables as of <?php echo $asOfDisp; ?>
    </div>
    <?php else: ?>

    <!-- Aging bar chart -->
    <div class="aged-bar-wrap mb-3">
        <?php
        $total = max(0.005, (float)($Totals['outstanding'] ?? 0));
        $bands = [
            ['key' => '0to30',  'label' => '0–30 days',  'cls' => 'aged-bar-current'],
            ['key' => '31to60', 'label' => '31–60 days', 'cls' => 'aged-bar-warn1'],
            ['key' => '61to90', 'label' => '61–90 days', 'cls' => 'aged-bar-warn2'],
            ['key' => '90plus', 'label' => '90+ days',   'cls' => 'aged-bar-overdue'],
        ];
        foreach ($bands as $b):
            $pct = round(($Totals[$b['key']] / $total) * 100, 1);
            if ($pct < 0.1) continue;
        ?>
        <div class="aged-bar-seg <?php echo $b['cls']; ?>" style="width:<?php echo $pct; ?>%;"
             title="<?php echo $b['label'] . ': ' . $cur . ' ' . number_format($Totals[$b['key']], $dec); ?>">
        </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex gap-3 mb-3 flex-wrap aged-legend">
        <span class="aged-legend-item"><span class="aged-dot aged-bar-current"></span>0–30 days</span>
        <span class="aged-legend-item"><span class="aged-dot aged-bar-warn1"></span>31–60 days</span>
        <span class="aged-legend-item"><span class="aged-dot aged-bar-warn2"></span>61–90 days</span>
        <span class="aged-legend-item"><span class="aged-dot aged-bar-overdue"></span>90+ days</span>
    </div>

    <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle aged-tbl">
        <thead>
            <tr class="aged-thead aged-thead-ap">
                <th style="min-width:180px;">Vendor</th>
                <th class="text-end" style="width:140px;">Outstanding</th>
                <th class="text-end aged-col-current" style="width:120px;">0–30 days</th>
                <th class="text-end aged-col-warn1"   style="width:120px;">31–60 days</th>
                <th class="text-end aged-col-warn2"   style="width:120px;">61–90 days</th>
                <th class="text-end aged-col-overdue" style="width:120px;">90+ days</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($Rows as $r): ?>
        <tr>
            <td>
                <span style="font-size:.82rem;"><?php echo htmlspecialchars($r->LedgerName); ?></span>
                <?php if (!empty($r->LedgerCode)): ?>
                <code style="font-size:.7rem;color:#7c3aed;display:block;"><?php echo htmlspecialchars($r->LedgerCode); ?></code>
                <?php endif; ?>
            </td>
            <td class="text-end fw-semibold" style="font-size:.82rem;color:#92400e;">
                <?php echo _apFmt((float)$r->NetOutstanding, $cur, $dec); ?>
            </td>
            <td class="text-end aged-col-current" style="font-size:.82rem;">
                <?php echo _apFmt((float)$r->Band0to30, $cur, $dec); ?>
            </td>
            <td class="text-end aged-col-warn1" style="font-size:.82rem;">
                <?php echo _apFmt((float)$r->Band31to60, $cur, $dec); ?>
            </td>
            <td class="text-end aged-col-warn2" style="font-size:.82rem;">
                <?php echo _apFmt((float)$r->Band61to90, $cur, $dec); ?>
            </td>
            <td class="text-end aged-col-overdue fw-semibold" style="font-size:.82rem;">
                <?php echo _apFmt((float)$r->Band90plus, $cur, $dec); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="aged-tfoot aged-tfoot-ap">
                <td class="fw-bold">TOTAL</td>
                <td class="text-end fw-bold" style="color:#92400e;">
                    <?php echo $cur . ' ' . number_format((float)($Totals['outstanding'] ?? 0), $dec); ?>
                </td>
                <td class="text-end fw-bold aged-col-current">
                    <?php echo $cur . ' ' . number_format((float)($Totals['0to30'] ?? 0), $dec); ?>
                </td>
                <td class="text-end fw-bold aged-col-warn1">
                    <?php echo $cur . ' ' . number_format((float)($Totals['31to60'] ?? 0), $dec); ?>
                </td>
                <td class="text-end fw-bold aged-col-warn2">
                    <?php echo $cur . ' ' . number_format((float)($Totals['61to90'] ?? 0), $dec); ?>
                </td>
                <td class="text-end fw-bold aged-col-overdue">
                    <?php echo $cur . ' ' . number_format((float)($Totals['90plus'] ?? 0), $dec); ?>
                </td>
            </tr>
        </tfoot>
    </table>
    </div>

    <?php endif; ?>

    <div class="text-muted mt-2" style="font-size:.72rem;">
        Aging is based on FIFO matching of payments against oldest bills.
        Opening balances are treated as pre-period (90+ band).
    </div>
</div>
