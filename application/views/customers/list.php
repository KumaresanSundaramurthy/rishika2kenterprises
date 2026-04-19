<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$showSerial = isset($GenSettings->SerialNoDisplay) && $GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $uid  = (int)$list->TablePrimaryUID;
        $name = htmlspecialchars($list->Name ?? '—');
?>
    <tr>

        <!-- Checkbox -->
        <td>
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox customerCheck" type="checkbox" value="<?php echo $uid; ?>">
            </div>
        </td>

        <!-- S.No -->
        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Dynamic columns -->
        <?php
            $getData = format_disp_allcolumns('html', $DispViewColumns, $list, $JwtData, $JwtData->GenSettings);
            if (!empty($getData) && is_array($getData)) echo implode('', $getData);
        ?>

        <!-- Actions -->
        <td>
            <div class="d-flex align-items-center justify-content-end gap-1">

                <!-- Edit icon (always visible) -->
                <a class="btn btn-icon btn-sm text-warning"
                   href="/customers/<?php echo $uid; ?>/edit"
                   title="Edit">
                    <i class="bx bx-edit"></i>
                </a>

                <!-- 3-dot dropdown -->
                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:170px;">

                        <li>
                            <a class="dropdown-item" href="/customers/<?php echo $uid; ?>/clone">
                                <i class="bx bx-copy me-2 text-secondary"></i>Clone
                            </a>
                        </li>

                        <?php if (!empty($list->MobileNumber)): ?>
                        <li>
                            <a class="dropdown-item" href="https://wa.me/<?php echo htmlspecialchars($list->MobileNumber); ?>?text=Hi" target="_blank">
                                <i class="bx bxl-whatsapp me-2 text-success"></i>Send WhatsApp
                            </a>
                        </li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider my-1"></li>

                        <li>
                            <button class="dropdown-item text-danger DeleteCustomer"
                                    data-customeruid="<?php echo $uid; ?>"
                                    data-name="<?php echo $name; ?>">
                                <i class="bx bx-trash me-2"></i>Delete
                            </button>
                        </li>

                    </ul>
                </div>

            </div>
        </td>

    </tr>
<?php
    endforeach;
else:
?>
    <tr>
        <td colspan="20" style="padding:0;border:none;">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No customers found</span>
                <a href="/customers/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Create Customer
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
