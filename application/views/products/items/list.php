<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');

if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {
        
        $sno    = $StartFrom + $i + 1;
        $imgSrc = !empty($row->Image) ? $cdnUrl . $row->Image : null;

        $typeBadge = ($row->ProductType === 'Service')
            ? '<span class="badge bg-label-info">Service</span>'
            : '<span class="badge bg-label-primary">Product</span>';

        $comboBadge = $row->IsComposite
            ? ' <span class="badge bg-label-warning ms-1">Combo</span>'
            : '';

        // Logic for Tax Display
        $sellingTaxStr = "";
        if (!empty($row->TaxPercentage)) {
            $taxType = ($row->SellingTaxType == 'With Tax') ? 'incl.' : 'excl.';
            $sellingTaxStr = '<div class="text-muted tinysmall mt-1">' . $row->TaxPercentage . '% ' . $taxType . ' tax</div>';
        }

        $purchaseTaxStr = "";
        if (!empty($row->PurchaseTaxType)) {
            $taxType = ($row->PurchaseTaxType == 'With Tax') ? 'incl.' : 'excl.';
            $purchaseTaxStr = '<div class="text-muted tinysmall mt-1">' . $row->TaxPercentage . '% ' . $taxType . ' tax</div>';
        }
?>

        <tr id="product-row-<?php echo $row->ProductUID; ?>" <?php if ($row->IsComposite): ?>class="combo-parent-row"<?php endif; ?>>
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input table-chkbox productsCheck" type="checkbox" value="<?php echo htmlspecialchars($row->ProductUID); ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($row->IsComposite): ?>
                    <button type="button" class="btn btn-icon btn-sm text-warning ComboExpandBtn p-0 me-1" data-uid="<?php echo $row->ProductUID; ?>" data-loaded="0" title="View Components"><i class="bx bx-chevron-right fs-5"></i></button>
                    <?php else: ?>
                        <span style="display:inline-block;"></span>
                    <?php endif; ?>
                    <div class="avatar avatar-sm me-2">
                    <?php if ($imgSrc) { ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($row->ItemName); ?>" class="rounded cursor-pointer preview-image" data-src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 40px; height: 40px; object-fit: cover;" />
                    <?php } else { ?>
                        <span class="avatar-initial rounded <?php echo $row->IsComposite ? 'bg-label-warning' : 'bg-label-secondary'; ?>"><?php echo strtoupper(substr($row->ItemName, 0, 1)); ?></span>
                    <?php } ?>
                    </div>
                    <div>
                        <div class="text-dark fw-semibold"><?php echo htmlspecialchars($row->ItemName); ?><?php echo $comboBadge; ?></div>
                        <div class="d-flex align-items-center gap-2 mt-1" style="font-size: 0.75rem;">
                            <?php echo $typeBadge; ?>
                            <?php if (!empty($row->HSNSACCode)): ?>
                                <span class="text-muted"><?php echo htmlspecialchars($row->HSNSACCode); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($row->PartNumber)): ?>
                                <span class="text-secondary" title="Part Number: <?php echo $row->PartNumber; ?>">
                                    <i class="bx bx-barcode" style="font-size: 1.5rem; vertical-align: middle;"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($row->IsActive == 1): ?>
                <span class="badge bg-label-primary change-status"
                    data-uid="<?= htmlspecialchars($row->ProductUID) ?>"
                    data-bs-toggle="tooltip"
                    data-bs-html="true"
                    data-bs-title="Change status to InActive?<br><button class='btn btn-sm btn-danger confirm-change' data-uid='<?= htmlspecialchars($row->ProductUID) ?>'>Yes</button>">
                    Active
                </span>
            <?php else: ?>
                <span class="badge bg-label-danger change-status"
                    data-uid="<?= htmlspecialchars($row->ProductUID) ?>"
                    data-bs-toggle="tooltip"
                    data-bs-html="true"
                    data-bs-title="Change status to Active?<br><button class='btn btn-sm btn-success confirm-change' data-uid='<?= htmlspecialchars($row->ProductUID) ?>'>Yes</button>">
                    InActive
                </span>
            <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row->CategoryName ?? '—'); ?></td>
            <td>
                <?php if ($row->IsComposite || $row->ProductType === 'Service'): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <?php
                        $qty = (float) $row->AvailableQuantity;
                        $lowStock = !empty($row->LowStockAlertAt) && $qty <= (float) $row->LowStockAlertAt && $qty > 0;
                        if ($qty > 0) {
                            $qtyClass = $lowStock ? 'text-warning fw-semibold' : 'text-dark fw-semibold';
                            echo '<span class="' . $qtyClass . '">' . smartDecimal($qty) . '</span>';
                            if ($lowStock) echo ' <span class="badge bg-label-warning" style="font-size:0.65rem;">Low</span>';
                        } else {
                            echo '<span class="text-danger fw-semibold">0</span> <span class="badge bg-label-danger" style="font-size:0.65rem;">Out</span>';
                        }
                    ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($row->MRP) && $row->MRP > 0): ?>
                    <div class="text-muted" style="font-size:0.8rem;"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->MRP); ?></div>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->SellingPrice); ?></div>
                <?php echo $sellingTaxStr; ?>
            </td>
            <td>
                <?php if ($row->IsComposite): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->PurchasePrice); ?></div>
                    <?php echo $purchaseTaxStr; ?>
                <?php endif; ?>
            </td>
            <td>
                <div><?php echo changeTimeZonefromDateTime($row->UpdatedOn, $JwtData->User->Timezone, 2); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;"><?php echo 'by ' . $row->UpdatedBy; ?></div>
            </td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="javascript: void(0);" class="btn btn-icon text-warning EditProduct" data-uid="<?php echo htmlspecialchars($row->ProductUID); ?>" data-iscomposite="<?php echo (int) $row->IsComposite; ?>"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteProduct" data-productuid="<?php echo htmlspecialchars($row->ProductUID); ?>"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php if ($row->IsComposite): ?>
        <tr id="combo-bom-row-<?php echo $row->ProductUID; ?>" class="d-none combo-bom-row">
            <td colspan="10" class="p-0">
                <div class="combo-bom-content px-3 py-0" style="border-left: 4px solid #fd7e14; background: linear-gradient(to right, rgba(253,126,20,0.06), transparent 60%);">
                    <div class="combo-bom-loading text-muted small py-2 ps-1"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading components...</div>
                </div>
            </td>
        </tr>
        <?php endif; ?>

<?php }
} else { ?>

    <tr>
        <td colspan="10">
            <div class="d-flex justify-content-center align-items-center vh-50">
                <div class="d-flex flex-column align-items-center w-100" style="max-width:500px;">
                    <div class="w-100 mb-3 d-flex justify-content-center align-items-center flex-grow-1">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" style="max-height:40vh;object-fit:contain;" />
                    </div>
                    <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                        <span class="mb-2">Add a Product Now</span>
                        <a href="javascript: void(0);" class="btn btn-primary px-3 addItem" id="NewItem">
                            <i class="bx bx-plus"></i> Create Item
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>

<?php } ?>