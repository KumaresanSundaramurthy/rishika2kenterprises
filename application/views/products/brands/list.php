<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($BrandsList) > 0) {
    foreach ($BrandsList as $list) {
        $SerialNumber++; ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input brandsCheck" type="checkbox" value="<?php echo $list->BrandUID; ?>"></div>
            </td>
            <td><?php echo $SerialNumber; ?></td>
            <td><?php echo $list->Name; ?></td>
            <td><?php echo $list->Description ? $list->Description : '-'; ?></td>
            <td class="text-end"><?php echo $list->UpdatedOn ? changeTimeZomeDateFormat($list->UpdatedOn, $JwtData->User->Timezone) : ''; ?></td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="javascript: void(0);" data-uid="<?php echo $list->BrandUID; ?>" class="btn btn-icon text-warning editBrand"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteBrand" data-branduid="<?php echo $list->BrandUID; ?>"><i class="bx bx-trash"></i></button>
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
                        <span class="mb-2">Add a Brand Now</span>
                        <a href="javascript: void(0);" class="btn btn-primary px-3 addBrands">
                            <i class="bx bx-plus"></i> New Brand
                        </a>
                    </div>

                </div>
            </div>
        </td>
    </tr>

<?php } ?>