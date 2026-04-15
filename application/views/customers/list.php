<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (sizeof($DataLists) > 0) {
    foreach ($DataLists as $list) {
        $SerialNumber++; ?>

        <tr>
            <td class="td-chk">
                <div class="form-check mb-0">
                    <input class="form-check-input customerCheck" type="checkbox" value="<?php echo $list->TablePrimaryUID; ?>">
                </div>
            </td>
            <td class="td-sno <?php echo $GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $SerialNumber; ?></td>
            <?php
                $getData = format_disp_allcolumns('html', $DispViewColumns, $list, $JwtData, $JwtData->GenSettings);
                if (!empty($getData) && is_array($getData)) echo implode('', $getData);
            ?>
            <td class="td-act">
                <div class="row-acts">
                    <a href="/customers/<?php echo $list->TablePrimaryUID; ?>/edit" class="btn-re" title="Edit"><i class="bx bx-edit"></i></a>
                    <button class="btn-rd DeleteCustomer" data-customeruid="<?php echo $list->TablePrimaryUID; ?>" title="Delete"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>

    <?php }
} else { ?>

    <tr>
        <td colspan="20" style="padding:0; border:none;">
            <div class="mod-empty">
                <img src="/assets/img/elements/no-record-found.png" alt="No records" />
                <p style="font-weight:600; color:#1e293b;">No customers found</p>
                <p>Get started by adding your first customer</p>
                <a href="/customers/create" class="mod-create-btn mt-1"><i class="bx bx-plus"></i> Create Customer</a>
            </div>
        </td>
    </tr>

<?php } ?>
