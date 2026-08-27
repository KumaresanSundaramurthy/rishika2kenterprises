<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Vendor Profile Modal — Transactions Tab
 *
 * @var array  $TxData      [['module'=>['uid','label','icon'], 'rows'=>[...]], ...]
 * @var object $JwtData
 * @var string $Cur
 * @var int    $Dec
 * @var string $DateFormat
 * @var int    $VendorUID
 */

// "View all" filter URL for each module
$moduleRoutes = [
    105 => '/purchases',
    104 => '/purchaseorders',
    108 => '/purchasereturns',
    109 => '/debitnotes',
];

// Create URL for the + New button in each section
$moduleCreateRoutes = [
    105 => '/purchases/create',
    104 => '/purchaseorders/create',
    108 => '/purchasereturns/create',
    109 => '/debitnotes/create',
];

$statusColors = [
    'Draft'     => 'secondary',
    'Confirmed' => 'primary',
    'Issued'    => 'primary',
    'Partial'   => 'warning',
    'Completed' => 'success',
    'Paid'      => 'success',
    'Cancelled' => 'danger',
    'Rejected'  => 'danger',
];
?>
<div class="p-3 p-md-4">

    <!-- Always render all sections — each section shows its own empty state when there are no rows -->
    <div class="accordion" id="vpTxAccordion">
    <?php foreach ($TxData as $section):
        $module     = $section['module'];
        $rows       = $section['rows'] ?? [];
        $mUID       = (int) $module['uid'];
        $collapseId = 'vpTxSection_' . $mUID;
        $isOpen     = !empty($rows);
        $createUrl  = $moduleCreateRoutes[$mUID] ?? '';
    ?>
    <div class="accordion-item border mb-2 rounded overflow-hidden">

        <!-- Header: accordion toggle + "New" button side by side -->
        <div class="cp-tx-acc-header">
            <h2 class="accordion-header flex-grow-1">
                <button class="accordion-button <?php echo $isOpen ? '' : 'collapsed'; ?> py-2 px-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#<?php echo $collapseId; ?>"
                        style="font-size:.85rem;font-weight:600;">
                    <i class="bx <?php echo htmlspecialchars($module['icon']); ?> me-2 text-warning"></i>
                    <?php echo htmlspecialchars($module['label']); ?>
                    <?php if (!empty($rows)): ?>
                        <span class="badge bg-label-warning ms-2"><?php echo count($rows); ?></span>
                    <?php endif; ?>
                    <span class="ms-auto me-2 text-muted fw-normal" style="font-size:.72rem;">
                        <?php echo empty($rows) ? 'No records' : 'Recent ' . count($rows); ?>
                    </span>
                </button>
            </h2>
            <?php if ($createUrl): ?>
            <a href="javascript:void(0);"
               class="btn btn-sm btn-outline-warning vp-tx-new-btn cp-tx-new-btn"
               data-route="<?php echo $createUrl; ?>"
               title="Create <?php echo htmlspecialchars($module['label']); ?>">
                <i class="bx bx-plus me-1"></i>New
            </a>
            <?php endif; ?>
        </div>

        <!-- Collapsible body -->
        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isOpen ? 'show' : ''; ?>">
            <div class="accordion-body p-0">
                <?php if (empty($rows)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:.82rem;">
                    <i class="bx bx-package fs-2 mb-1 opacity-50"></i>
                    No <?php echo strtolower(htmlspecialchars($module['label'])); ?> found for this vendor.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Ref No</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r):
                            $status   = $r['DocStatus'] ?? '—';
                            $badgeCls = $statusColors[$status] ?? 'secondary';
                            $bal      = (float) ($r['BalanceAmount'] ?? $r['NetAmount'] ?? 0);
                        ?>
                        <tr>
                            <td class="px-3 fw-semibold"><?php echo htmlspecialchars($r['TransNo'] ?? '—'); ?></td>
                            <td><?php echo !empty($r['DocDate']) ? date($DateFormat, strtotime($r['DocDate'])) : '—'; ?></td>
                            <td class="text-end"><?php echo $Cur . ' ' . smartDecimal((float)($r['NetAmount'] ?? 0)); ?></td>
                            <td class="text-end <?php echo $bal > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $bal > 0 ? $Cur . ' ' . smartDecimal($bal) : '—'; ?>
                            </td>
                            <td><span class="badge bg-label-<?php echo $badgeCls; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($moduleRoutes[$mUID])): ?>
                <div class="px-3 py-2 border-top text-end">
                    <a href="<?php echo base_url($moduleRoutes[$mUID]); ?>?party=<?php echo $VendorUID; ?>"
                       class="text-warning" style="font-size:.78rem;" target="_blank">
                        View all <?php echo strtolower(htmlspecialchars($module['label'])); ?>
                        <i class="bx bx-link-external ms-1"></i>
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <?php endforeach; ?>
    </div>

</div>
