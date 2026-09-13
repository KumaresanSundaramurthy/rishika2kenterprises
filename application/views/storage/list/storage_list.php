<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$GenSettings = $GenSettings ?? new stdClass();
$StartFrom   = $StartFrom   ?? 0;

if (!empty($DataLists)) {
    foreach ($DataLists as $i => $row) {
        $sno    = $StartFrom + $i + 1;
        $uid    = (int) $row->StorageUID;
        $name   = htmlspecialchars($row->Name ?? '');
        $sname  = htmlspecialchars($row->ShortName ?? '');
        $stName = htmlspecialchars($row->StorageTypeName ?? '—');
        $stUID  = (int) ($row->StorageTypeUID ?? 0);
        $desc   = htmlspecialchars($row->Description ?? '');
        $img    = $row->Image ?? '';
?>
<tr>
    <td class="table-checkbox">
        <div class="form-check form-check-inline">
            <input class="form-check-input storageCheck" type="checkbox" value="<?php echo $uid; ?>">
        </div>
    </td>
    <td class="table-serialno <?php echo $GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
    <td>
        <?php if (!empty($img)): ?>
            <img src="<?php echo CDN_URL . htmlspecialchars($img); ?>" alt="<?php echo $name; ?>" class="rounded" width="40" height="40" style="object-fit:cover;">
        <?php else: ?>
            <span class="avatar avatar-sm bg-label-secondary rounded"><?php echo strtoupper(substr($name, 0, 1)); ?></span>
        <?php endif; ?>
    </td>
    <td><?php echo $name; ?></td>
    <td><?php echo $sname ?: '—'; ?></td>
    <td><?php echo $stName; ?></td>
    <td class="r2k-col-date"><?php echo formatListDate($row->UpdatedOn ?? '', $GenSettings->ListDateFormat ?? 'd M Y'); ?></td>
    <td class="text-center">
        <div class="dropdown">
            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="bx bx-dots-vertical-rounded"></i>
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item editStorage cursor-pointer"
                    data-uid="<?php echo $uid; ?>"
                    data-name="<?php echo base64_encode($row->Name ?? ''); ?>"
                    data-shortname="<?php echo base64_encode($row->ShortName ?? ''); ?>"
                    data-description="<?php echo base64_encode($row->Description ?? ''); ?>"
                    data-image="<?php echo base64_encode($img); ?>"
                    data-storagetypeuid="<?php echo base64_encode($stUID); ?>">
                    <i class="bx bx-edit-alt me-1"></i> Edit
                </a>
                <a class="dropdown-item DeleteStorage cursor-pointer"
                    data-storageuid="<?php echo $uid; ?>">
                    <i class="bx bx-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </td>
</tr>
<?php
    }
} else {
?>
<tr>
    <td colspan="8" class="text-center text-muted py-4">No storage records found.</td>
</tr>
<?php } ?>
