<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$hideType = $HideType ?? false;
$showUnit = $ShowUnit ?? false;

if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {

        $sno     = $StartFrom + $i + 1;
        $_atts   = json_decode($row->AttachmentsJson ?? '[]', true);
        $imgSrc  = !empty($_atts[0]['url']) ? $_atts[0]['url'] : null;

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
                        <?php
                        // Attachments already fetched in the model — embed as JSON, open gallery instantly from DOM
                        $imagesJson = htmlspecialchars($row->AttachmentsJson ?? '[]', ENT_QUOTES, 'UTF-8');
                        ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                             alt="<?php echo htmlspecialchars($row->ItemName); ?>"
                             class="rounded cursor-pointer prod-list-img"
                             data-images="<?php echo $imagesJson; ?>"
                             style="width:40px;height:40px;object-fit:cover;" />
                    <?php else: ?>
                        <span class="avatar-initial rounded <?php echo $row->IsComposite ? 'bg-label-warning' : 'bg-label-secondary'; ?>">
                            <?php echo $_initials; ?>
                        </span>
                    <?php endif; ?>
                    </div>

                    <div>
                        <a href="javascript:void(0);" class="prod-profile-link text-dark fw-semibold text-decoration-none"
                           data-uid="<?php echo $uid; ?>"
                           title="View Profile"><?php echo htmlspecialchars($row->ItemName); ?></a><?php echo $comboBadge; ?><?php if (!empty($row->IsBrandApplicable) || !empty($row->IsSizeApplicable)): ?>
                        <i class="bx bx-grid-alt BrandStockModalBtn bsm-name-tag"
                           data-uid="<?php echo $uid; ?>"
                           data-name="<?php echo htmlspecialchars($row->ItemName, ENT_QUOTES); ?>"
                           data-unit="<?php echo htmlspecialchars($row->PUShortName ?? '', ENT_QUOTES); ?>"
                           title="Variant stock breakdown"></i>
                        <?php endif; ?>
                        <div class="d-flex align-items-center gap-2 mt-1 prod-item-meta">
                            <?php if (!$hideType) echo $typeBadge; ?>
                            <?php if (!empty($row->HSNSACCode)): ?>
                                <span class="prod-hsn-code"><?php echo htmlspecialchars($row->HSNSACCode); ?></span>
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
                <div class="dropdown d-inline-block">
                    <span class="badge <?php echo $row->IsActive == 1 ? 'bg-label-success' : 'bg-label-danger'; ?> cursor-pointer" style="font-size:.68rem;" data-bs-toggle="dropdown">
                        <?php echo $row->IsActive == 1 ? 'Active' : 'In-Active'; ?>
                        <i class="bx bx-chevron-down" style="font-size:.65rem;"></i>
                    </span>
                    <ul class="dropdown-menu r2k-action-menu">
                        <li>
                            <button class="dropdown-item prod-status-toggle"
                                    data-uid="<?php echo $uid; ?>"
                                    data-newstatus="<?php echo $row->IsActive == 1 ? 0 : 1; ?>">
                                <?php if ($row->IsActive == 1): ?>
                                    <i class="bx bx-x-circle me-2 text-danger"></i>Mark In-Active
                                <?php else: ?>
                                    <i class="bx bx-check-circle me-2 text-success"></i>Mark Active
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                </div>
            </td>

            <!-- Category (items tab only) -->
            <?php if (!$showUnit): ?>
            <td><?php echo htmlspecialchars($row->CategoryName ?? '—'); ?></td>
            <?php endif; ?>

            <!-- Unit (groups tab only) -->
            <?php if ($showUnit): ?>
            <td><?php echo htmlspecialchars($row->PUShortName ?? '—'); ?></td>
            <?php endif; ?>

            <!-- Qty (items tab only) -->
            <?php if (!$showUnit): ?>
            <td>
                <?php if ($row->IsComposite || $row->ProductType === 'Service'): ?>
                    <span class="text-muted">—</span>
                <?php else:
                    $qty      = (float) $row->AvailableQuantity;
                    $lowStock = !empty($row->LowStockAlertAt) && $qty <= (float)$row->LowStockAlertAt && $qty > 0;
                    if ($qty > 0) {
                        $qtyClass = $lowStock ? 'text-warning fw-semibold' : 'text-dark fw-semibold';
                        echo '<span class="' . $qtyClass . '">' . smartDecimal($qty) .' <span class="text-primary">'.$row->PUShortName.'</span></span>';
                    } elseif ($qty == 0) {
                        echo '<span class="text-danger fw-semibold">0 <span class="text-primary">'.$row->PUShortName.'</span></span>';
                    } else {
                        echo '<span class="text-danger fw-semibold">' . smartDecimal($qty).' <span class="text-primary">'.$row->PUShortName.'</span></span>';
                    }
                endif; ?>
            </td>
            <?php endif; ?>

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

            <!-- Purchase Price (items tab only) -->
            <?php if (!$showUnit): ?>
            <td>
                <?php if ($row->IsComposite): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <div class="text-dark fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol . ' ' . smartDecimal($row->PurchasePrice); ?></div>
                    <?php echo $purchaseTaxStr; ?>
                <?php endif; ?>
            </td>
            <?php endif; ?>

            <!-- Last Updated -->
            <td>
                <?php $updatedTs = viewPageDateTimeFormat($row->UpdatedOn ?? null, $JwtData->User->Timezone ?? 'UTC', 2); ?>
                <div class="r2k-col-date"><?php echo $updatedTs->formatted; ?></div>
                <?php if ($updatedTs->ago): ?>
                <div class="r2k-col-date-ago"><?php echo $updatedTs->ago; ?></div>
                <?php endif; ?>
                <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($row->UpdatedBy ?? '—'); ?></div>
            </td>

            <!-- Actions: edit icon + 3-dot dropdown -->
            <td>
                <div class="d-flex align-items-center justify-content-center gap-1">

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
                        <ul class="dropdown-menu dropdown-menu-end r2k-action-menu">

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
                                <button class="dropdown-item CloneProduct" data-uid="<?php echo $uid; ?>">
                                    <i class="bx bx-copy me-2 text-secondary"></i>Clone
                                </button>
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
            <td colspan="<?php echo $showUnit ? 9 : 11; ?>" class="p-0">
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
        <td colspan="<?php echo $showUnit ? 9 : 11; ?>">
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
