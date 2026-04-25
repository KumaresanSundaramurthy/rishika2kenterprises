<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');

if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {

        $sno    = $StartFrom + $i + 1;
        $imgSrc = !empty($row->Image) ? $cdnUrl . $row->Image : null;

        // Avatar initials: up to 2 letters from first 2 words
        $_words   = preg_split('/\s+/', trim($row->ItemName));
        $_initials = strtoupper(substr($_words[0], 0, 1));
        if (isset($_words[1]) && $_words[1] !== '') $_initials .= strtoupper(substr($_words[1], 0, 1));

        $typeBadge = ($row->ProductType === 'Service')
            ? '<span class="badge bg-label-info">Service</span>'
            : '<span class="badge bg-label-primary">Product</span>';

        $comboBadge = $row->IsComposite
            ? ' <span class="badge bg-label-warning ms-1">Combo</span>'
            : '';

        // Tax display
        $sellingTaxStr = '';
        if (!empty($row->TaxPercentage)) {
            $taxType = ($row->SellingTaxType == 'With Tax') ? 'incl.' : 'excl.';
            $sellingTaxStr = '<div class="text-muted tinysmall mt-1">' . $row->TaxPercentage . '% ' . $taxType . ' tax</div>';
        }

        $purchaseTaxStr = '';
        if (!empty($row->PurchaseTaxType)) {
            $taxType = ($row->PurchaseTaxType == 'With Tax') ? 'incl.' : 'excl.';
            $purchaseTaxStr = '<div class="text-muted tinysmall mt-1">' . $row->TaxPercentage . '% ' . $taxType . ' tax</div>';
        }

        // Data attributes shared across barcode/QR buttons
        $pn       = htmlspecialchars($row->PartNumber ?? '');
        $iname    = htmlspecialchars($row->ItemName ?? '');
        $price    = htmlspecialchars(smartDecimal($row->SellingPrice ?? 0));
        $mrp      = htmlspecialchars(smartDecimal($row->MRP ?? 0));
        $purPrice = htmlspecialchars(smartDecimal($row->PurchasePrice ?? 0));
        $catName  = htmlspecialchars($row->CategoryName ?? '');
        $hsnCode  = htmlspecialchars($row->HSNSACCode ?? '');
        $uid      = (int)$row->ProductUID;

        $bcAttrs = 'data-uid="' . $uid . '"'
            . ' data-partnumber="' . $pn . '"'
            . ' data-itemname="'   . $iname . '"'
            . ' data-price="'      . $price . '"'
            . ' data-mrp="'        . $mrp . '"'
            . ' data-purchaseprice="' . $purPrice . '"'
            . ' data-category="'   . $catName . '"'
            . ' data-hsncode="'    . $hsnCode . '"';
?>

        <tr id="product-row-<?php echo $uid; ?>" <?php if ($row->IsComposite): ?>class="combo-parent-row"<?php endif; ?>>

            <!-- Checkbox -->
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input table-chkbox productsCheck" type="checkbox" value="<?php echo $uid; ?>">
                </div>
            </td>

            <!-- S.No -->
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">
                <span class="text-muted" style="font-size:.78rem;"><?php echo $sno; ?></span>
            </td>

            <!-- Item name + barcode/QR icons -->
            <td>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($row->IsComposite): ?>
                    <button type="button" class="btn btn-icon btn-sm text-warning ComboExpandBtn p-0 me-1"
                        data-uid="<?php echo $uid; ?>" data-loaded="0" title="View Components">
                        <i class="bx bx-chevron-right fs-5"></i>
                    </button>
                    <?php else: ?>
                        <span style="display:inline-block;"></span>
                    <?php endif; ?>

                    <div class="avatar avatar-sm me-2">
                    <?php if ($imgSrc): ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                             alt="<?php echo htmlspecialchars($row->ItemName); ?>"
                             class="rounded cursor-pointer preview-image"
                             data-src="<?php echo htmlspecialchars($imgSrc); ?>"
                             style="width:40px;height:40px;object-fit:cover;" />
                    <?php else: ?>
                        <span class="avatar-initial rounded <?php echo $row->IsComposite ? 'bg-label-warning' : 'bg-label-secondary'; ?>">
                            <?php echo $_initials; ?>
                        </span>
                    <?php endif; ?>
                    </div>

                    <div>
                        <div class="text-dark fw-semibold"><?php echo htmlspecialchars($row->ItemName); ?><?php echo $comboBadge; ?></div>
                        <div class="d-flex align-items-center gap-2 mt-1" style="font-size:0.75rem;">
                            <?php echo $typeBadge; ?>
                            <?php if (!empty($row->HSNSACCode)): ?>
                                <span class="text-muted"><?php echo htmlspecialchars($row->HSNSACCode); ?></span>
                            <?php endif; ?>
                            <?php if (!$row->IsComposite && !empty($row->PartNumber)): ?>
                                <button type="button" class="btn p-0 border-0 bg-transparent BarcodeOnlyBtn"
                                    <?php echo $bcAttrs; ?>
                                    title="Print Barcode — <?php echo $pn; ?>">
                                    <i class="bx bx-barcode text-primary" style="font-size:1.35rem;vertical-align:middle;"></i>
                                </button>
                                <button type="button" class="btn p-0 border-0 bg-transparent QROnlyBtn"
                                    <?php echo $bcAttrs; ?>
                                    title="Print QR Code — <?php echo $pn; ?>">
                                    <i class="bx bx-qr text-info" style="font-size:1.1rem;vertical-align:middle;"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>

            <!-- Status -->
            <td>
                <?php if ($row->IsActive == 1): ?>
                <span class="badge bg-label-primary change-status"
                    data-uid="<?php echo $uid; ?>"
                    data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-title="Change status to InActive?<br><button class='btn btn-sm btn-danger confirm-change' data-uid='<?php echo $uid; ?>'>Yes</button>">
                    Active
                </span>
                <?php else: ?>
                <span class="badge bg-label-danger change-status"
                    data-uid="<?php echo $uid; ?>"
                    data-bs-toggle="tooltip" data-bs-html="true"
                    data-bs-title="Change status to Active?<br><button class='btn btn-sm btn-success confirm-change' data-uid='<?php echo $uid; ?>'>Yes</button>">
                    InActive
                </span>
                <?php endif; ?>
            </td>

            <!-- Category -->
            <td><?php echo htmlspecialchars($row->CategoryName ?? '—'); ?></td>

            <!-- Qty -->
            <td>
                <?php if ($row->IsComposite || $row->ProductType === 'Service'): ?>
                    <span class="text-muted">—</span>
                <?php else:
                    $qty      = (float)$row->AvailableQuantity;
                    $lowStock = !empty($row->LowStockAlertAt) && $qty <= (float)$row->LowStockAlertAt && $qty > 0;
                    if ($qty > 0) {
                        $qtyClass = $lowStock ? 'text-warning fw-semibold' : 'text-dark fw-semibold';
                        echo '<span class="' . $qtyClass . '">' . smartDecimal($qty) . '</span>';
                        if ($lowStock) echo ' <span class="badge bg-label-warning" style="font-size:.65rem;">Low</span>';
                    } elseif ($qty == 0) {
                        echo '<span class="text-danger fw-semibold">0</span>';
                    } else {
                        echo '<span class="text-danger fw-semibold">' . smartDecimal($qty) . '</span> <span class="badge bg-label-danger" style="font-size:.65rem;">Out</span>';
                    }
                endif; ?>
            </td>

            <!-- MRP -->
            <td>
                <?php if (!empty($row->MRP) && $row->MRP > 0): ?>
                    <div class="text-muted" style="font-size:.8rem;"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->MRP); ?></div>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>

            <!-- Selling Price -->
            <td>
                <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->SellingPrice); ?></div>
                <?php echo $sellingTaxStr; ?>
            </td>

            <!-- Purchase Price -->
            <td>
                <?php if ($row->IsComposite): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->PurchasePrice); ?></div>
                    <?php echo $purchaseTaxStr; ?>
                <?php endif; ?>
            </td>

            <!-- Last Updated -->
            <td>
                <div style="font-size:.8rem;"><?php echo changeTimeZonefromDateTime($row->UpdatedOn, $JwtData->User->Timezone, 2); ?></div>
                <div class="text-muted" style="font-size:.7rem;"><?php echo 'by ' . $row->UpdatedBy; ?></div>
            </td>

            <!-- Actions: edit icon + 3-dot dropdown -->
            <td>
                <div class="d-flex align-items-center justify-content-end gap-1">

                    <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning EditProduct"
                       data-uid="<?php echo $uid; ?>"
                       data-iscomposite="<?php echo (int)$row->IsComposite; ?>"
                       title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>

                    <div class="dropdown">
                        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:185px;">

                            <?php if (!$row->IsComposite && !empty($row->PartNumber)): ?>
                            <li><span class="dropdown-header text-uppercase" style="font-size:.68rem;letter-spacing:.4px;color:#adb5bd;padding:4px 12px 2px;">Print Label</span></li>
                            <li>
                                <button class="dropdown-item BarcodeOnlyBtn" <?php echo $bcAttrs; ?>>
                                    <i class="bx bx-barcode me-2 text-primary"></i>Print Barcode
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item QROnlyBtn" <?php echo $bcAttrs; ?>>
                                    <i class="bx bx-qr me-2 text-info"></i>Print QR Code
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>

                            <!-- Item actions group -->
                            <li>
                                <a class="dropdown-item" href="/products/<?php echo $uid; ?>/clone">
                                    <i class="bx bx-copy me-2 text-secondary"></i>Clone
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>

                            <!-- Danger zone -->
                            <li>
                                <button class="dropdown-item text-danger DeleteProduct"
                                        data-productuid="<?php echo $uid; ?>">
                                    <i class="bx bx-trash me-2"></i>Delete
                                </button>
                            </li>

                        </ul>
                    </div>

                </div>
            </td>

        </tr>

        <?php if ($row->IsComposite): ?>
        <tr id="combo-bom-row-<?php echo $uid; ?>" class="d-none combo-bom-row">
            <td colspan="10" class="p-0">
                <div class="combo-bom-content px-3 py-0" style="border-left:4px solid #fd7e14;background:linear-gradient(to right,rgba(253,126,20,.06),transparent 60%);">
                    <div class="combo-bom-loading text-muted small py-2 ps-1">
                        <i class="bx bx-loader-alt bx-spin me-1"></i> Loading components...
                    </div>
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
                        <a href="javascript:void(0);" class="btn btn-primary px-3 addItem" id="NewItem">
                            <i class="bx bx-plus"></i> Create Item
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>

<?php } ?>
