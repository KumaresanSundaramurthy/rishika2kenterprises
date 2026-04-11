<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php if (!empty($DataLists)): $sn = (int)$SerialNumber; ?>
    <?php foreach ($DataLists as $row): $sn++; ?>
        <tr>
            <td class="text-center"><?php echo $sn; ?></td>
            <td>
                <a href="#" class="viewPaymentDetail fw-semibold" data-payment-uid="<?php echo (int)$row->PaymentUID; ?>">
                    <?php echo htmlspecialchars($row->TransNumber ?? '—'); ?>
                </a>
                <div class="transtext-small text-muted"><?php echo date('d M Y', strtotime($row->TransDate ?? 'now')); ?></div>
            </td>
            <td><?php echo htmlspecialchars($row->PartyName ?? '—'); ?></td>
            <td>
                <?php echo htmlspecialchars($row->PaymentTypeName ?? '—'); ?>
                <?php if (empty($row->IsCash) && !empty($row->AccountName)): ?>
                    <div class="transtext-small text-muted"><?php echo htmlspecialchars($row->AccountName . ' - ' . $row->BankName); ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($row->ReferenceNo)): ?>
                    <span class="badge bg-label-secondary"><?php echo htmlspecialchars($row->ReferenceNo); ?></span>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="text-end fw-semibold">
                <?php echo $JwtData->GenSettings->CurrenySymbol; ?> <?php echo smartDecimal($row->Amount, $JwtData->GenSettings->DecimalPoints, true); ?>
                <?php if ($row->ExcessAmount > 0): ?>
                    <div class="transtext-small text-warning">Excess: <?php echo $JwtData->GenSettings->CurrenySymbol; ?> <?php echo smartDecimal($row->ExcessAmount, $JwtData->GenSettings->DecimalPoints, true); ?></div>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <?php if ($row->IsFullyPaid): ?>
                    <span class="badge bg-success">Fully Paid</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Partial</span>
                <?php endif; ?>
            </td>
            <td class="text-muted small"><?php echo date('d M Y', strtotime($row->CreatedOn)); ?></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger deletePayment"
                    data-payment-uid="<?php echo (int)$row->PaymentUID; ?>"
                    title="Delete Payment">
                    <i class="bx bx-trash"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="9" class="text-center text-muted py-4">
            <i class="bx bx-credit-card fs-3 d-block mb-2"></i>
            No payment records found.
        </td>
    </tr>
<?php endif; ?>
