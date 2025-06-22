<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($CategoriesList) > 0) {
    foreach ($CategoriesList as $list) { ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input categoryCheck" type="checkbox" value="<?php echo $list->CategoryUID; ?>"></div>
            </td>
            <td><?php echo $list->Name; ?></td>
            <td><?php echo $list->Description ? $list->Description : '-'; ?></td>
            <td class="text-end"><?php echo $list->UpdatedOn ? changeTimeZomeDateFormat($list->UpdatedOn, 'Asia/Kolkata') : ''; ?></td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="/customers/<?php echo $list->CategoryUID; ?>/edit" class="btn btn-icon text-warning"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteCategory" data-categoryuid="<?php echo $list->CategoryUID; ?>"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>

    <?php }
} else { ?>

    <tr>
        <td colspan="10">
            <div class="d-flex justify-content-center align-items-center" style="height: 57vh;">
                <div class="d-flex flex-column align-items-center w-100" style="max-width: 500px; padding: 1rem;">

                    <div class="w-100 mb-3" style="flex: 3; display: flex; justify-content: center; align-items: center;">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" style="max-height: 40vh;object-fit: contain;" />
                    </div>

                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span class="mb-2">Add a Category Now</span>
                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory">
                            <i class="bx bx-plus"></i> New Category
                        </a>
                    </div>

                </div>
            </div>
        </td>
    </tr>

<?php } ?>