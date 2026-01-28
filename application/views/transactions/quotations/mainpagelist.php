<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (count($dataLists) > 0) {
    foreach ($dataLists as $list) {
        $SerialNumber++; ?>

        <tr>
            <td>
                <div class="form-check form-check-inline"><input class="form-check-input table-chkbox productsCheck" type="checkbox" value="<?php echo htmlspecialchars($list->TablePrimaryUID); ?>"></div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $SerialNumber; ?></td>
            <?php
                $getData = format_disp_allcolumns('html', $DispViewColumns, $list, $JwtData, $JwtData->GenSettings);
                if(!empty($getData) && is_array($getData)) {
                    echo implode('', $getData);
                }
            ?>
            <td>
                <div class="d-flex align-items-sm-center justify-content-sm-center">
                    <a href="javascript: void(0);" class="btn btn-icon text-warning EditProduct" data-uid="<?php echo htmlspecialchars($list->TablePrimaryUID); ?>"><i class="bx bx-edit me-1"></i></a>
                    <button class="btn btn-icon text-danger DeleteProduct" data-productuid="<?php echo htmlspecialchars($list->TablePrimaryUID); ?>"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>

    <?php }
} else { ?>

    <tr>
        <td colspan="10">
            <div class="d-flex justify-content-center align-items-center vh-50">
                <div class="d-flex flex-column align-items-center w-100" style="max-width:500px;">
                    <div class="w-100 mb-3 d-flex justify-content-center align-items-center flex-grow-1">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" style="max-height:40vh;object-fit:contain;" />
                    </div>
                    <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                        <span class="mb-2">Add a Product Now</span>
                        <a href="/products/add" class="btn btn-primary px-3">
                            <i class="bx bx-plus"></i> Create Product
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>

<?php } ?>