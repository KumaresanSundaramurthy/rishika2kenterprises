<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$_assignedLabels = ['All' => 'All Customers', 'Groups' => 'Customer Groups', 'Customers' => 'Specific Customers'];
$_assignedColors = ['All' => 'bg-label-primary', 'Groups' => 'bg-label-info', 'Customers' => 'bg-label-warning'];
$_scopeLabels    = ['All' => 'All Products', 'Specific' => 'Specific Products'];
$_scopeColors    = ['All' => 'bg-label-secondary', 'Specific' => 'bg-label-success'];
$_basedOnLabels  = ['SellingPrice' => 'Selling Price', 'UnitPrice' => 'Unit Price', 'PurchasePrice' => 'Purchase Price'];

if (!empty($DataLists)):
    foreach ($DataLists as $i => $row):
        $sno = $StartFrom + $i + 1;
        $assignedTo  = $row->AssignedToType ?? 'All';
        $scope       = $row->Scope ?? 'All';
        $validFrom   = $row->ValidFrom ?? null;
        $validTo     = $row->ValidTo   ?? null;
        $updatedTs = viewPageDateTimeFormat($row->UpdatedAt ?? null, $JwtData->User->Timezone ?? 'UTC', 2);
?>
        <tr class="pl-list-row">
            <td class="table-checkbox text-center align-middle">
                <div class="form-check d-flex justify-content-center align-items-center mb-0">
                    <input class="form-check-input table-chkbox priceListCheck" type="checkbox" value="<?php echo (int)$row->PriceListUID; ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>
            <td>
                <div class="fw-medium"><?php echo htmlspecialchars($row->Name); ?></div>
                <div class="text-muted" style="font-size:.74rem;">Priority: <?php echo (int)($row->Priority ?? 1); ?></div>
            </td>
            <td>
                <span class="badge <?php echo $_assignedColors[$assignedTo] ?? 'bg-label-secondary'; ?>" style="font-size:.74rem;">
                    <?php echo $_assignedLabels[$assignedTo] ?? $assignedTo; ?>
                </span>
            </td>
            <td>
                <span class="badge <?php echo $_scopeColors[$scope] ?? 'bg-label-secondary'; ?>" style="font-size:.74rem;">
                    <?php echo $_scopeLabels[$scope] ?? $scope; ?>
                </span>
                <?php if ($scope === 'All'): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo $_basedOnLabels[$row->GlobalBasedOn ?? 'SellingPrice'] ?? ''; ?></div>
                <?php endif; ?>
            </td>
            <td class="text-nowrap" style="font-size:.82rem;">
                <?php echo $validFrom ? date($JwtData->GenSettings->ListDateFormat ?? 'd M Y', strtotime($validFrom)) : '<span class="text-muted">Permanent</span>'; ?>
            </td>
            <td class="text-nowrap" style="font-size:.82rem;">
                <?php echo $validTo ? date($JwtData->GenSettings->ListDateFormat ?? 'd M Y', strtotime($validTo)) : '<span class="text-muted">No expiry</span>'; ?>
            </td>
            <td>
                <?php if ((int)($row->Status ?? 1) === 1): ?>
                    <span class="badge bg-label-success" style="font-size:.74rem;">Active</span>
                <?php else: ?>
                    <span class="badge bg-label-danger" style="font-size:.74rem;">In-Active</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="r2k-col-date"><?php echo $updatedTs->formatted; ?></div>
                <?php if ($updatedTs->ago): ?>
                <div class="r2k-col-date-ago"><?php echo $updatedTs->ago; ?></div>
                <?php endif; ?>
                <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($row->UpdatedByName ?? '—'); ?></div>
            </td>
            <td class="text-center align-middle">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editPriceList"
                       data-uid="<?php echo (int)$row->PriceListUID; ?>"
                       title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>
                    <div class="dropdown">
                        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">
                            <li>
                                <button class="dropdown-item text-danger deletePriceList"
                                        data-uid="<?php echo (int)$row->PriceListUID; ?>"
                                        data-name="<?php echo htmlspecialchars($row->Name); ?>">
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
        <td colspan="10">
            <div class="d-flex justify-content-center align-items-center" style="height:57vh;">
                <div class="d-flex flex-column align-items-center w-100" style="max-width:500px;padding:1rem;">
                    <div class="w-100 mb-3" style="flex:3;display:flex;justify-content:center;align-items:center;">
                        <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid" style="max-height:40vh;object-fit:contain;" />
                    </div>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <span class="mb-2">No price lists found. Create one to get started.</span>
                        <a href="javascript:void(0);" class="btn btn-primary px-3" id="NewPriceListEmpty">
                            <i class="bx bx-plus"></i> Create Price List
                        </a>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<?php endif; ?>
