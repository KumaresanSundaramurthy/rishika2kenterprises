<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Product Profile Modal — Stock Movement Tab
 * Shows a running inventory ledger computed from confirmed transactions.
 *
 * @var object|null $Prod        From getProductProfile()
 * @var array       $Moves       From getProductStockMovements() — chronological ASC
 * @var object      $JwtData
 * @var string      $Cur
 * @var int         $Dec
 * @var string      $DateFormat
 * @var int         $ProductUID
 */

// Stock IN modules: Purchases (105), Sales Returns (106)
// Stock OUT modules: Invoices (103), Purchase Returns (108), Delivery Challans (112)
$inModules = [105, 106];

$moduleLabels = [
    103 => 'Invoice',
    105 => 'Purchase',
    106 => 'Sales Return',
    108 => 'Purchase Return',
    112 => 'Delivery Challan',
];
$moduleColors = [
    103 => 'primary',
    105 => 'success',
    106 => 'info',
    108 => 'danger',
    112 => 'warning',
];

$openingQty  = (float)($Prod->OpeningQuantity ?? 0);
$currentStock = (float)($Prod->AvailableQty   ?? 0);
$unitLabel   = htmlspecialchars($Prod->UnitShortName ?? '');

// Compute running balance starting from opening quantity
$runningBalance = $openingQty;
$ledger         = [];

foreach ($Moves as $m) {
    $muid = (int)($m['ModuleUID'] ?? 0);
    $qty  = (float)($m['Quantity'] ?? 0);
    $isIn = in_array($muid, $inModules, true);
    if ($isIn) {
        $runningBalance += $qty;
        $qtyIn  = $qty;
        $qtyOut = 0;
    } else {
        $runningBalance -= $qty;
        $qtyIn  = 0;
        $qtyOut = $qty;
    }
    $ledger[] = [
        'date'        => $m['TransDate'] ?? '',
        'refNo'       => $m['UniqueNumber'] ?? '—',
        'moduleUID'   => $muid,
        'moduleLabel' => $moduleLabels[$muid] ?? ('Module ' . $muid),
        'moduleColor' => $moduleColors[$muid] ?? 'secondary',
        'party'       => $m['PartyType'] === 'C'
                       ? ($m['CustomerName'] ?? '—')
                       : ($m['VendorName']   ?? '—'),
        'qtyIn'       => $qtyIn,
        'qtyOut'      => $qtyOut,
        'balance'     => $runningBalance,
        'isIn'        => $isIn,
    ];
}
?>

<div class="p-3 p-md-4">

    <!-- Current stock summary -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div class="pp-stock-summary-card <?php echo $currentStock <= 0 ? 'pp-stock-summary-red' : 'pp-stock-summary-green'; ?>">
            <div class="pp-stock-summary-label">Current Stock</div>
            <div class="pp-stock-summary-val"><?php echo smartDecimal($currentStock); ?> <?php echo $unitLabel; ?></div>
        </div>
        <div class="pp-stock-summary-card pp-stock-summary-blue">
            <div class="pp-stock-summary-label">Opening Stock</div>
            <div class="pp-stock-summary-val"><?php echo smartDecimal($openingQty); ?> <?php echo $unitLabel; ?></div>
        </div>
        <div class="pp-stock-summary-card pp-stock-summary-purple">
            <div class="pp-stock-summary-label">Movements</div>
            <div class="pp-stock-summary-val"><?php echo count($ledger); ?></div>
        </div>
    </div>

    <?php if (empty($ledger)): ?>
    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-trending-up pp-empty-icon mb-2"></i>
        <div>No stock movements recorded yet.</div>
        <div class="mt-1" style="font-size:.78rem;">Opening stock: <?php echo smartDecimal($openingQty); ?> <?php echo $unitLabel; ?></div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 pp-stock-table">
            <thead class="table-light">
                <tr>
                    <th class="px-3">Date</th>
                    <th>Ref No</th>
                    <th>Type</th>
                    <th>Party</th>
                    <th class="text-center">Qty In</th>
                    <th class="text-center">Qty Out</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening balance row -->
                <tr class="pp-stock-opening-row">
                    <td class="px-3" colspan="6"><span class="fw-semibold text-muted">Opening Balance</span></td>
                    <td class="text-end fw-semibold"><?php echo smartDecimal($openingQty); ?> <?php echo $unitLabel; ?></td>
                </tr>
                <!-- Transaction rows -->
                <?php foreach ($ledger as $row): ?>
                <tr class="<?php echo $row['isIn'] ? 'pp-stock-in' : 'pp-stock-out'; ?>">
                    <td class="px-3 text-nowrap">
                        <?php echo !empty($row['date']) ? date($DateFormat, strtotime($row['date'])) : '—'; ?>
                    </td>
                    <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($row['refNo']); ?></td>
                    <td>
                        <span class="badge bg-label-<?php echo $row['moduleColor']; ?> pp-module-badge">
                            <?php echo htmlspecialchars($row['moduleLabel']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['party']); ?></td>
                    <td class="text-center pp-dir-in fw-semibold">
                        <?php echo $row['qtyIn'] > 0 ? '+' . smartDecimal($row['qtyIn']) : '—'; ?>
                    </td>
                    <td class="text-center pp-dir-out fw-semibold">
                        <?php echo $row['qtyOut'] > 0 ? '-' . smartDecimal($row['qtyOut']) : '—'; ?>
                    </td>
                    <td class="text-end fw-semibold <?php echo $row['balance'] < 0 ? 'text-danger' : ''; ?>">
                        <?php echo smartDecimal($row['balance']); ?> <?php echo $unitLabel; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>
