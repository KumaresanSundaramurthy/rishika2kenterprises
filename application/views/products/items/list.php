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

        <tr>
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input table-chkbox productsCheck" type="checkbox" value="<?php echo htmlspecialchars($row->ProductUID); ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm me-2">
                    <?php if ($imgSrc) { ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($row->ItemName); ?>" class="rounded cursor-pointer preview-image" data-src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 40px; height: 40px; object-fit: cover;" />
                    <?php } else { ?>
                        <span class="avatar-initial rounded bg-label-secondary"><?php echo strtoupper(substr($row->ItemName, 0, 1)); ?></span>
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
            <td><?php echo htmlspecialchars($row->CategoryName ?? '—'); ?></td>
            <td></td>
            <td>
                <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . number_format((float) $row->SellingPrice, 2); ?></div>
                <?php echo $sellingTaxStr; ?>
            </td>
            <td>
                <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . number_format((float) $row->PurchasePrice, 2); ?></div>
                <?php echo $purchaseTaxStr; ?>
            </td>
            <td>
                <div><?php echo changeTimeZonefromDateTime($row->UpdatedOn, $JwtData->User->Timezone, 2); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;"><?php echo 'by ' . $row->UpdatedBy; ?></div>
            </td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="javascript: void(0);" class="btn btn-icon text-warning EditProduct" data-uid="<?php echo htmlspecialchars($row->ProductUID); ?>"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteProduct" data-productuid="<?php echo htmlspecialchars($row->ProductUID); ?>"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>

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