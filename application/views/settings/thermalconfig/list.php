<?php defined('BASEPATH') or exit('No direct script access allowed');

if (empty($DataLists)) { ?>
    <tr>
        <td colspan="7" class="text-center py-4 text-muted">
            <i class="bx bx-printer fs-3 d-block mb-2"></i>
            <?php echo t('empty_thermal', 'No thermal templates configured.'); ?>
        </td>
    </tr>
<?php return; }

$idx = 1;
foreach ($DataLists as $row):
    $typeLabel = $row->ModuleName ?? $row->TransactionType ?? 'Unknown';
    $updatedOn     = !empty($row->UpdatedOn) ? $row->UpdatedOn : ($row->CreatedOn ?? null);
    $updatedTs     = viewPageDateTimeFormat($updatedOn, $JwtData->User->Timezone ?? 'UTC', 2);
    $updatedByName = htmlspecialchars($row->UpdatedByName ?? '—');

    // Receipt element badges
    $badges = '';
    if (!empty($row->ShowCompanyDetails))  $badges .= '<span class="badge bg-label-primary me-1 mb-1">' . t('lbl_co_details', 'Co. Details') . '</span>';
    if (!empty($row->ShowGSTIN))           $badges .= '<span class="badge bg-label-info me-1 mb-1">' . t('lbl_gstin', 'GSTIN') . '</span>';
    if (!empty($row->ShowMobile))          $badges .= '<span class="badge bg-label-info me-1 mb-1">Mobile</span>';
    if (!empty($row->ShowHSN))             $badges .= '<span class="badge bg-label-secondary me-1 mb-1">HSN</span>';
    if (!empty($row->ShowTaxBreakdown))    $badges .= '<span class="badge bg-label-warning me-1 mb-1">Tax</span>';
    if (!empty($row->ShowTaxableAmount))   $badges .= '<span class="badge bg-label-warning me-1 mb-1">Taxable</span>';
    if (!empty($row->ShowCashReceived))    $badges .= '<span class="badge bg-label-success me-1 mb-1">Cash Rcvd</span>';
    if (!empty($row->ShowPaymentQR))       $badges .= '<span class="badge bg-label-danger me-1 mb-1">Pay QR</span>';
    if (!empty($row->ShowGoogleReviewQR))  $badges .= '<span class="badge bg-label-danger me-1 mb-1">Review QR</span>';
    if (!$badges)                          $badges  = '<span class="text-muted small">—</span>';

    // JSON for edit modal (all fields)
    $editData = htmlspecialchars(json_encode([
        'ThermalConfigUID'    => (int)$row->ThermalConfigUID,
        'ModuleUID'           => (int)$row->ModuleUID,
        'ModuleName'          => $typeLabel,
        'PaperWidth'          => $row->PaperWidth          ?? '80mm',
        'FooterMessage'       => $row->FooterMessage       ?? '',
        'ShowTerms'           => (int)($row->ShowTerms           ?? 0),
        'ShowCompanyDetails'  => (int)($row->ShowCompanyDetails  ?? 1),
        'ShowItemDescription' => (int)($row->ShowItemDescription ?? 0),
        'ShowTaxableAmount'   => (int)($row->ShowTaxableAmount   ?? 0),
        'ShowHSN'             => (int)($row->ShowHSN             ?? 1),
        'ShowTaxBreakdown'    => (int)($row->ShowTaxBreakdown    ?? 1),
        'ShowGSTIN'           => (int)($row->ShowGSTIN           ?? 1),
        'ShowMobile'          => (int)($row->ShowMobile          ?? 1),
        'ShowCashReceived'    => (int)($row->ShowCashReceived    ?? 1),
        'ShowLogo'            => (int)($row->ShowLogo            ?? 0),
        'ShowGoogleReviewQR'  => (int)($row->ShowGoogleReviewQR  ?? 0),
        'ShowPaymentQR'       => (int)($row->ShowPaymentQR       ?? 1),
        'OrgNameFontSize'     => (int)($row->OrgNameFontSize     ?? 22),
        'CompanyNameFontSize' => (int)($row->CompanyNameFontSize ?? 18),
        'ProductInfoFontSize' => (int)($row->ProductInfoFontSize ?? 12),
    ]), ENT_QUOTES);
?>
<tr>
    <td class="text-center align-middle"><?php echo $idx++; ?></td>
    <td class="align-middle fw-semibold"><?php echo htmlspecialchars($typeLabel); ?></td>
    <td class="align-middle">
        <span class="badge bg-label-<?php echo ($row->PaperWidth ?? '80mm') === '58mm' ? 'success' : 'primary'; ?>">
            <?php echo htmlspecialchars($row->PaperWidth ?? '80mm'); ?>
        </span>
    </td>
    <td class="align-middle" style="white-space:normal;max-width:220px;"><?php echo $badges; ?></td>
    <td class="align-middle text-nowrap">
        <small class="text-muted">Org: </small><?php echo (int)($row->OrgNameFontSize ?? 16); ?>px &nbsp;
        <small class="text-muted">Addr: </small><?php echo (int)($row->CompanyNameFontSize ?? 14); ?>px &nbsp;
        <small class="text-muted">Prod: </small><?php echo (int)($row->ProductInfoFontSize ?? 12); ?>px
    </td>
    <td class="align-middle">
        <div class="r2k-col-date"><?php echo $updatedTs->formatted; ?></div>
        <?php if ($updatedTs->ago): ?>
        <div class="r2k-col-date-ago"><?php echo $updatedTs->ago; ?></div>
        <?php endif; ?>
        <div class="text-muted r2k-col-date-by">by <?php echo $updatedByName; ?></div>
    </td>
    <td class="text-center align-middle">
        <div class="d-flex align-items-center justify-content-center gap-1">
            <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning EditThermalConfig"
                data-config='<?php echo $editData; ?>'
                title="Edit">
                <i class="bx bx-edit"></i>
            </a>
            <a href="javascript:void(0);" class="btn btn-icon btn-sm text-danger DeleteThermalConfig"
                data-uid="<?php echo (int)$row->ThermalConfigUID; ?>"
                data-type="<?php echo htmlspecialchars($typeLabel); ?>"
                title="Delete">
                <i class="bx bx-trash"></i>
            </a>
        </div>
    </td>
</tr>
<?php endforeach; ?>
