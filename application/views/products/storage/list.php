<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($DataLists) > 0) {
    foreach ($DataLists as $list) {
        $SerialNumber++; ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input storageCheck" type="checkbox" value="<?php echo $list->TablePrimaryUID; ?>"></div>
            </td>
            <td><?php echo $SerialNumber; ?></td>
            <?php
                $DataPassing['ViewColumns'] = $ViewColumns;
                $DataPassing['list'] = $list;
                echo $this->load->view('common/form/list', $DataPassing, TRUE);
            ?>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="javascript: void(0);" data-uid="<?php echo $list->TablePrimaryUID; ?>" 
                        <?php
                            foreach ($ViewColumns as $column) {

                                $attrName = strtolower($column->FieldName);
                                $fieldName = $column->DisplayName;
                                $value = $list->$fieldName ?? '';

                                echo 'data-'.$attrName.'="'.($value ? base64_encode($value) : '').'"';

                            }
                        ?>
                        data-description="<?php echo $list->Description ? base64_encode($list->Description) : ''; ?>" data-image="<?php echo $list->Image ? base64_encode($list->Image) : ''; ?>" data-strtype="<?php echo $list->Storage_Type_UID ? $list->Storage_Type_UID : 0; ?>" class="btn btn-icon text-warning editStorage"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteStorage" data-productuid="<?php echo $list->ProductUID; ?>" data-storageuid="<?php echo $list->TablePrimaryUID; ?>"><i class="bx bx-trash"></i></button>
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
                        <span class="mb-2">Add a Storage Now</span>
                        <a href="javascript: void(0);" class="btn btn-primary px-3 addStorage">
                            <i class="bx bx-plus"></i> New Storage
                        </a>
                    </div>

                </div>
            </div>
        </td>
    </tr>

<?php } ?>