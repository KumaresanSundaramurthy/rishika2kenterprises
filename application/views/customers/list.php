<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($CustomersList) > 0) {
    foreach ($CustomersList as $list) { ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input customersCheck" type="checkbox" value="<?php echo $list->CustomerUID; ?>"></div>
            </td>
            <td><?php echo $list->Name; ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-end"><?php echo $list->UpdatedOn ? changeTimeZomeDateFormat($list->UpdatedOn, 'Asia/Kolkata') : ''; ?></td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="/customers/<?php echo $list->CustomerUID; ?>/edit" class="btn btn-icon text-warning"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteCustomer" data-customeruid="<?php echo $list->CustomerUID; ?>"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>

    <?php }
} else { ?>

    <tr>
        <td colspan="10" class="text-center">No Records Found!</td>
    </tr>

<?php } ?>