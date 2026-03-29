<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');

if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {
        $sno    = $StartFrom + $i + 1;
        $imgSrc = !empty($row->Image) ? $cdnUrl . $row->Image : null;
        $desc   = $row->Description ?? '';
        $shortDesc = mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . '...' : $desc;
?>

        <tr>
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input categoryCheck" type="checkbox" value="<?php echo htmlspecialchars($row->CategoryUID); ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm me-2">
                        <?php if ($imgSrc) { ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($row->Name); ?>" class="rounded cursor-pointer preview-image" data-src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 40px; height: 40px; object-fit: cover;" />
                        <?php } else { ?>
                            <span class="avatar-initial rounded bg-label-secondary"><?php echo strtoupper(substr($row->Name, 0, 1)); ?></span>
                        <?php } ?>
                    </div>
                    <span class="fw-medium"><?php echo htmlspecialchars($row->Name); ?></span>
                </div>
            </td>
            <td>
                <div><?php echo changeTimeZonefromDateTime($row->UpdatedOn, $JwtData->User->Timezone, 2); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;"><?php echo 'by ' . $row->UpdatedBy; ?></div>
            </td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="javascript: void(0);" data-uid="<?php echo htmlspecialchars($row->CategoryUID); ?>" class="btn btn-icon text-warning editCategory"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteCategory" data-categoryuid="<?php echo htmlspecialchars($row->CategoryUID); ?>"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>

<?php }
} else { ?>

    <tr>
        <td colspan="6">
            <div class="d-flex justify-content-center align-items-center" style="height: 57vh;">
                <div class="d-flex flex-column align-items-center w-100" style="max-width: 500px; padding: 1rem;">
                    <div class="w-100 mb-3" style="flex: 3; display: flex; justify-content: center; align-items: center;">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" style="max-height: 40vh;object-fit: contain;" />
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span class="mb-2">Add a Category Now</span>
                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory">
                            <i class="bx bx-plus"></i> Create Category
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>

<?php } ?>
