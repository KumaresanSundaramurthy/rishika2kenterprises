<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($VendorsList) > 0) {
    foreach ($VendorsList as $list) { ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input vendorsCheck" type="checkbox" value="<?php echo $list->VendorUID; ?>"></div>
            </td>
            <td><?php echo $list->Name; ?></td>
            <td><?php echo $list->VillageName ? $list->VillageName : '-'; ?></td>
            <td class="text-center"><?php echo $list->MobileNumber ? $list->CountryCode.'-'.$list->MobileNumber : '-'; ?></td>
            <td class="text-end">0.00</td>
            <td class="text-end"><?php echo $list->UpdatedOn ? changeTimeZomeDateFormat($list->UpdatedOn, $JwtData->User->Timezone) : ''; ?></td>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="/vendors/<?php echo $list->VendorUID; ?>/edit" class="btn btn-icon text-warning"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteVendor" data-vendoruid="<?php echo $list->VendorUID; ?>"><i class="bx bx-trash"></i></button>
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
                        <span class="mb-2">Add a Vendor Now</span>
                        <a href="/vendors/add" class="btn btn-primary px-3">
                            <i class="bx bx-plus"></i> New Vendor
                        </a>
                    </div>

                </div>
            </div>
        </td>
    </tr>

<?php } ?>