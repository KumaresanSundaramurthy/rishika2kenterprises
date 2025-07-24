<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($DataLists) > 0) {
    foreach ($DataLists as $list) {
        $SerialNumber++; ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input customerCheck" type="checkbox" value="<?php echo $list->TablePrimaryUID; ?>"></div>
            </td>
            <td><?php echo $SerialNumber; ?></td>
            <?php
                $DataPassing['ViewColumns'] = $ViewColumns;
                $DataPassing['list'] = $list;
                echo $this->load->view('common/form/list', $DataPassing, TRUE);
            ?>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="/customers/<?php echo $list->TablePrimaryUID; ?>/edit" class="btn btn-icon text-warning"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteCustomer" data-customeruid="<?php echo $list->TablePrimaryUID; ?>"><i class="bx bx-trash"></i></button>
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
                        <span class="mb-2">Add a Customer Now</span>
                        <a href="/customers/add" class="btn btn-primary px-3">
                            <i class="bx bx-plus"></i> New Customer
                        </a>
                    </div>

                </div>
            </div>
        </td>
    </tr>

<?php } ?>