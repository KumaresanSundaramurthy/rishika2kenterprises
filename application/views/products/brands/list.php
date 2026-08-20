<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {
        $sno    = $StartFrom + $i + 1;
        $_atts  = json_decode($row->AttachmentsJson ?? '[]', true);
        $imgSrc = !empty($_atts[0]['url']) ? $_atts[0]['url'] : null;
        $desc   = $row->Description ?? '';
        $shortDesc = mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . '...' : $desc;

        $_words    = preg_split('/\s+/', trim($row->BrandName));
        $_initials = strtoupper(substr($_words[0], 0, 1));
        if (isset($_words[1]) && $_words[1] !== '') $_initials .= strtoupper(substr($_words[1], 0, 1));
?>

        <tr>
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input brandCheck" type="checkbox" value="<?php echo htmlspecialchars($row->BrandUID); ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm me-2">
                        <?php if ($imgSrc):
                            $imagesJson = htmlspecialchars($row->AttachmentsJson ?? '[]', ENT_QUOTES, 'UTF-8');
                        ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                 alt="<?php echo htmlspecialchars($row->BrandName); ?>"
                                 class="rounded cursor-pointer catg-list-img"
                                 data-images="<?php echo $imagesJson; ?>"
                                 style="width:40px;height:40px;object-fit:cover;" />
                        <?php else: ?>
                            <span class="avatar-initial rounded bg-label-info"><?php echo $_initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="fw-medium"><?php echo htmlspecialchars($row->BrandName); ?></span>
                        <?php if (!empty($row->BrandCode)): ?>
                            <div class="text-muted r2k-item-subtext"><?php echo htmlspecialchars($row->BrandCode); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <?php $prodCount = (int)($row->ProductCount ?? 0); ?>
                <?php if ($prodCount > 0): ?>
                    <span class="badge bg-label-primary"><?php echo $prodCount; ?> item<?php echo $prodCount > 1 ? 's' : ''; ?></span>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php $updatedTs = viewPageDateTimeFormat($row->UpdatedOn ?? null, $JwtData->User->Timezone ?? 'UTC', 2); ?>
                <div class="r2k-col-date"><?php echo $updatedTs->formatted; ?></div>
                <?php if ($updatedTs->ago): ?>
                <div class="r2k-col-date-ago"><?php echo $updatedTs->ago; ?></div>
                <?php endif; ?>
                <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($row->UpdatedBy ?? '—'); ?></div>
            </td>
            <td class="text-center align-middle">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editBrand"
                       data-uid="<?php echo htmlspecialchars($row->BrandUID); ?>"
                       data-name="<?php echo base64_encode($row->BrandName); ?>"
                       data-code="<?php echo base64_encode($row->BrandCode ?? ''); ?>"
                       data-description="<?php echo base64_encode($row->Description ?? ''); ?>"
                       data-attachments="<?php echo htmlspecialchars($row->AttachmentsJson ?? '[]', ENT_QUOTES, 'UTF-8'); ?>"
                       title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>
                    <div class="dropdown">
                        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm r2k-actions-menu">
                            <li>
                                <button class="dropdown-item text-danger DeleteBrand"
                                        data-branduid="<?php echo htmlspecialchars($row->BrandUID); ?>"
                                        data-productcount="<?php echo (int)($row->ProductCount ?? 0); ?>"
                                        data-brandname="<?php echo htmlspecialchars($row->BrandName); ?>">
                                    <i class="bx bx-trash me-2"></i>Delete
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>

<?php }
} else { ?>

    <tr>
        <td colspan="6">
            <div class="r2k-list-empty">
                <div class="r2k-list-empty-inner">
                    <div class="r2k-list-empty-img">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" />
                    </div>
                    <div class="r2k-list-empty-action">
                        <span class="mb-2">Add a Brand Now</span>
                        <a href="javascript: void(0);" class="btn btn-primary px-3 addBrand">
                            <i class="bx bx-plus"></i> Create Brand
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>

<?php } ?>
