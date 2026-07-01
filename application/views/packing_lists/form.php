<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object      $SrcHeader  Source transaction (DC, Invoice, etc.) */
/** @var array       $SrcItems   Source transaction line items */
/** @var object|null $PL */
/** @var array       $PLItems */
/** @var object|null $OrgInfo */
/** @var object      $JwtData */

$dc      = $SrcHeader;
$isEdit  = !empty($PL);
$plUID   = $isEdit ? (int) $PL->PackingListUID : 0;
$plNum   = $isEdit ? htmlspecialchars($PL->UniqueNumber) : 'New';
$dcNum   = htmlspecialchars($dc->UniqueNumber ?? '—');
$transUID= (int) $dc->TransUID;

// Module type label
$moduleUID    = (int) ($dc->ModuleUID ?? 0);
$moduleLabels = [112 => 'Delivery Challan', 103 => 'Sales Invoice', 113 => 'Proforma Invoice'];
$moduleIcons  = [112 => 'bx-package',       103 => 'bx-receipt',    113 => 'bx-file'];
$moduleColors = [112 => '#16a34a',           103 => '#0d6efd',       113 => '#7c3aed'];
$moduleBgs    = [112 => '#dcfce7',           103 => '#dbeafe',       113 => '#ede9fe'];
$srcTypeLabel = $moduleLabels[$moduleUID] ?? 'Transaction';
$srcIcon      = $moduleIcons[$moduleUID]  ?? 'bx-file-blank';
$srcColor     = $moduleColors[$moduleUID] ?? '#6c757d';
$srcBg        = $moduleBgs[$moduleUID]    ?? '#f1f5f9';

// Build a map of saved PL items keyed by TransProductUID for quick lookup
$plItemMap = [];
foreach ($PLItems as $pli) {
    $plItemMap[(int) $pli->TransProductUID] = $pli;
}

$_fmt        = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$_printFmt   = $JwtData->GenSettings->PrintDateFormat ?? 'd M Y';
$dateToday   = date('Y-m-d');
$plDate      = $isEdit ? $PL->PLDate             : ($dc->TransDate ?? $dateToday);
$vehicleNum  = $isEdit ? ($PL->VehicleNumber   ?? '') : ($dc->Reference ?? '');
$lrNum       = $isEdit ? ($PL->LRNumber        ?? '') : '';
$transporter = $isEdit ? ($PL->TransporterName ?? '') : '';
$notes       = $isEdit ? ($PL->Notes           ?? '') : '';

$challanType = htmlspecialchars($dc->QuotationType ?? '');
$partyName   = htmlspecialchars($dc->PartyName     ?? '—');
$dcDate      = !empty($dc->TransDate) ? date($_fmt, strtotime($dc->TransDate)) : '—';
?>
<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <input type="hidden" id="hdnTransUID"  value="<?php echo $transUID; ?>">
                    <input type="hidden" id="hdnPLUID"     value="<?php echo $plUID; ?>">
                    <input type="hidden" id="hdnCsrfName"  value="<?php echo $this->security->get_csrf_token_name(); ?>">
                    <input type="hidden" id="hdnCsrfToken" value="<?php echo $this->security->get_csrf_hash(); ?>">

                    <div class="card mb-3">

                        <!-- ── Card Header ─────────────────────────────────────── -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3">
                                <div class="trans-doc-icon" style="background:<?php echo $srcBg; ?>;">
                                    <i class="bx <?php echo $srcIcon; ?>" style="color:<?php echo $srcColor; ?>;font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">Packing List</span>
                                        <?php if ($isEdit): ?>
                                            <span class="trans-form-doc-number"><?php echo $plNum; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-label-warning" style="font-size:.68rem;">New</span>
                                        <?php endif; ?>
                                        <span class="badge bg-label-secondary" style="font-size:.68rem;"><?php echo $srcTypeLabel; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isEdit): ?>
                                <a href="/packing-list/<?php echo $transUID; ?>/print" target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-printer me-1"></i>Print
                                </a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary px-3" id="btnSave" onclick="savePL()">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger px-3" onclick="history.back()">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>

                        <!-- ── Card Body ──────────────────────────────────────── -->
                        <div class="card-body p-4">

                            <!-- Source reference strip -->
                            <div class="d-flex align-items-center gap-3 flex-wrap p-3 mb-4 rounded-2"
                                 style="background:#f8f9ff;border:1px solid #e4e6f0;font-size:.82rem;color:#555;">
                                <div style="background:<?php echo $srcBg; ?>;border-radius:6px;padding:6px 10px;flex-shrink:0;">
                                    <i class="bx <?php echo $srcIcon; ?>" style="color:<?php echo $srcColor; ?>;font-size:1rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.9rem;color:#1e1e2d;"><?php echo $srcTypeLabel; ?></div>
                                    <div style="font-size:.75rem;color:#888;"><?php echo $srcTypeLabel; ?> reference details</div>
                                </div>
                                <div class="vr mx-1 d-none d-md-block"></div>
                                <div><span class="text-muted">Ref #</span> <strong><?php echo $dcNum; ?></strong></div>
                                <div><span class="text-muted">Party</span> <strong><?php echo $partyName; ?></strong></div>
                                <?php if ($challanType): ?>
                                <div><span class="text-muted">Type</span> <strong><?php echo $challanType; ?></strong></div>
                                <?php endif; ?>
                                <div><span class="text-muted">Date</span> <strong><?php echo $dcDate; ?></strong></div>
                            </div>

                            <!-- ── Section: Basic Details ─────────────────────── -->
                            <div class="mb-4">
                                <div class="fw-semibold text-uppercase mb-3"
                                     style="font-size:.7rem;letter-spacing:.8px;color:#6c757d;border-bottom:1px solid #eee;padding-bottom:8px;">
                                    Basic Details
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3 col-6">
                                        <label class="form-label" style="font-size:.78rem;">Date</label>
                                        <input type="date" id="fPLDate" class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($plDate ?? $dateToday); ?>">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label" style="font-size:.78rem;">Vehicle Number</label>
                                        <input type="text" id="fVehicleNumber" class="form-control form-control-sm"
                                               maxlength="100" placeholder="e.g. TN 01 AB 1234"
                                               value="<?php echo htmlspecialchars($vehicleNum); ?>">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label" style="font-size:.78rem;">LR Number</label>
                                        <input type="text" id="fLRNumber" class="form-control form-control-sm"
                                               maxlength="100" placeholder="Lorry Receipt No."
                                               value="<?php echo htmlspecialchars($lrNum); ?>">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label" style="font-size:.78rem;">Transporter Name</label>
                                        <input type="text" id="fTransporterName" class="form-control form-control-sm"
                                               maxlength="200" placeholder="Transporter / Courier"
                                               value="<?php echo htmlspecialchars($transporter); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-size:.78rem;">Notes / Special Instructions</label>
                                        <textarea id="fNotes" class="form-control form-control-sm" rows="2"
                                                  placeholder="Any special instructions for packing or delivery..."><?php echo htmlspecialchars($notes); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Section: Product & Package Details ─────────── -->
                            <div>
                                <div class="fw-semibold text-uppercase mb-3"
                                     style="font-size:.7rem;letter-spacing:.8px;color:#6c757d;border-bottom:1px solid #eee;padding-bottom:8px;">
                                    Product &amp; Package Details
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0" style="min-width:860px;">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width:36px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">#</th>
                                                <th style="width:220px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;">Product</th>
                                                <th class="text-center" style="width:100px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">Qty / Unit</th>
                                                <th style="width:140px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">Package Kind</th>
                                                <th style="width:90px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">No. of Pkgs</th>
                                                <th style="width:95px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">Net Wt (kg)</th>
                                                <th style="width:95px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">Gross Wt (kg)</th>
                                                <th style="width:85px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:#eef0ff;color:#4a4f9e;border-color:#d8dbff;white-space:nowrap;">CBM (m³)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="plItemsTbody">
                                        <?php
                                        $pkgKindOptions = ['', 'Box', 'Bag', 'Bundle', 'Pallet', 'Carton', 'Loose'];
                                        foreach ($SrcItems as $i => $dcItem):
                                            $tpUID   = (int) $dcItem->TransProdUID;
                                            $pUID    = (int) $dcItem->ProductUID;
                                            $pli     = $plItemMap[$tpUID] ?? null;
                                            $pkgKind = htmlspecialchars($pli->PackageKind      ?? '');
                                            $numPkgs = $pli ? (int)   $pli->NumberOfPackages   : 0;
                                            $netWt   = $pli ? (float) $pli->NetWeight          : 0;
                                            $grossWt = $pli ? (float) $pli->GrossWeight        : 0;
                                            $cbm     = $pli ? (float) $pli->CBM                : 0;
                                        ?>
                                        <tr>
                                            <td class="text-center text-muted" style="font-size:.78rem;"><?php echo $i + 1; ?></td>
                                            <td>
                                                <div class="fw-semibold" style="font-size:.82rem;"><?php echo htmlspecialchars($dcItem->ProductName ?? ''); ?></div>
                                                <?php if (!empty($dcItem->PartNumber)): ?>
                                                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($dcItem->PartNumber); ?></div>
                                                <?php endif; ?>
                                                <input type="hidden" name="items[<?php echo $i; ?>][TransProductUID]" value="<?php echo $tpUID; ?>">
                                                <input type="hidden" name="items[<?php echo $i; ?>][ProductUID]"      value="<?php echo $pUID; ?>">
                                                <input type="hidden" name="items[<?php echo $i; ?>][Quantity]"        value="<?php echo (float) $dcItem->Quantity; ?>">
                                            </td>
                                            <td class="text-center" style="font-size:.82rem;">
                                                <span class="fw-semibold"><?php echo number_format((float)($dcItem->Quantity ?? 0), 2); ?></span>
                                                <span class="text-muted ms-1" style="font-size:.72rem;"><?php echo htmlspecialchars($dcItem->PrimaryUnitName ?? ''); ?></span>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm pkg-kind" name="items[<?php echo $i; ?>][PackageKind]" style="min-width:120px;">
                                                    <?php foreach ($pkgKindOptions as $opt): ?>
                                                    <option value="<?php echo $opt; ?>" <?php echo $pkgKind === $opt ? 'selected' : ''; ?>>
                                                        <?php echo $opt === '' ? '— Select —' : $opt; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm num-pkgs"
                                                       min="0" step="1" placeholder="0"
                                                       name="items[<?php echo $i; ?>][NumberOfPackages]"
                                                       value="<?php echo $numPkgs ?: ''; ?>"
                                                       oninput="updateTotals()">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm net-wt"
                                                       min="0" step="0.001" placeholder="0.000"
                                                       name="items[<?php echo $i; ?>][NetWeight]"
                                                       value="<?php echo $netWt > 0 ? $netWt : ''; ?>"
                                                       oninput="updateTotals()">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm gross-wt"
                                                       min="0" step="0.001" placeholder="0.000"
                                                       name="items[<?php echo $i; ?>][GrossWeight]"
                                                       value="<?php echo $grossWt > 0 ? $grossWt : ''; ?>"
                                                       oninput="updateTotals()">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm cbm-val"
                                                       min="0" step="0.0001" placeholder="0.0000"
                                                       name="items[<?php echo $i; ?>][CBM]"
                                                       value="<?php echo $cbm > 0 ? $cbm : ''; ?>">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr style="background:linear-gradient(90deg,#eef0ff,#f5f6ff);border-top:2px solid #d8dbff;">
                                                <td colspan="3" class="text-end fw-semibold" style="font-size:.78rem;color:#4a4f9e;letter-spacing:.04em;text-transform:uppercase;padding:10px 8px;">Totals</td>
                                                <td></td>
                                                <td class="fw-bold" id="totalPkgs" style="font-size:.85rem;color:#1e1e2d;padding:10px 8px;">0</td>
                                                <td class="fw-bold" id="totalNetWt" style="font-size:.85rem;color:#1e1e2d;padding:10px 8px;">0.000</td>
                                                <td class="fw-bold" id="totalGrossWt" style="font-size:.85rem;color:#1e1e2d;padding:10px 8px;">0.000</td>
                                                <td style="padding:10px 8px;"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                        </div><!-- /.card-body -->
                    </div><!-- /.card -->

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script>
/**
 * Recalculate column totals (packages, net weight, gross weight).
 * @returns {void}
 */
function updateTotals() {
    var pkgs = 0, net = 0, gross = 0;
    document.querySelectorAll('#plItemsTbody tr').forEach(function (row) {
        pkgs  += parseInt(row.querySelector('.num-pkgs')?.value  || 0) || 0;
        net   += parseFloat(row.querySelector('.net-wt')?.value  || 0) || 0;
        gross += parseFloat(row.querySelector('.gross-wt')?.value|| 0) || 0;
    });
    document.getElementById('totalPkgs').textContent    = pkgs;
    document.getElementById('totalNetWt').textContent   = net.toFixed(3);
    document.getElementById('totalGrossWt').textContent = gross.toFixed(3);
}

/**
 * Build the items array from table row inputs.
 * @returns {Array<Object>}
 */
function collectItems() {
    var items = [];
    document.querySelectorAll('#plItemsTbody tr').forEach(function (row) {
        items.push({
            TransProductUID  : row.querySelector('input[name*="TransProductUID"]')?.value || 0,
            ProductUID       : row.querySelector('input[name*="ProductUID"]')?.value      || 0,
            Quantity         : row.querySelector('input[name*="Quantity"]')?.value        || 0,
            PackageKind      : row.querySelector('.pkg-kind')?.value                      || '',
            NumberOfPackages : row.querySelector('.num-pkgs')?.value                      || 0,
            NetWeight        : row.querySelector('.net-wt')?.value                        || 0,
            GrossWeight      : row.querySelector('.gross-wt')?.value                      || 0,
            CBM              : row.querySelector('.cbm-val')?.value                       || 0,
        });
    });
    return items;
}

/**
 * POST the packing list via FormData (CI3 CSRF reads from $_POST).
 * @returns {void}
 */
function savePL() {
    var $btn = $('#btnSave');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving…');

    var fd = new FormData();
    fd.append('TransUID',        document.getElementById('hdnTransUID').value);
    fd.append('PLUID',           document.getElementById('hdnPLUID').value);
    fd.append('PLDate',          document.getElementById('fPLDate').value);
    fd.append('VehicleNumber',   document.getElementById('fVehicleNumber').value.trim());
    fd.append('LRNumber',        document.getElementById('fLRNumber').value.trim());
    fd.append('TransporterName', document.getElementById('fTransporterName').value.trim());
    fd.append('Notes',           document.getElementById('fNotes').value.trim());
    fd.append('items',           JSON.stringify(collectItems()));
    fd.append(document.getElementById('hdnCsrfName').value,
              document.getElementById('hdnCsrfToken').value);

    $.ajax({
        url         : '/packing-list/save',
        type        : 'POST',
        data        : fd,
        processData : false,
        contentType : false,
        success: function (resp) {
            $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (resp.csrf_token_name && resp.csrf_hash) {
                document.getElementById('hdnCsrfName').value  = resp.csrf_token_name;
                document.getElementById('hdnCsrfToken').value = resp.csrf_hash;
            }
            if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
            document.getElementById('hdnPLUID').value = resp.PLUID;
            showToastNotification(resp.Message, 'success');
            if (document.getElementById('hdnPLUID').value > 0) {
                setTimeout(function () { location.reload(); }, 800);
            }
        },
        error: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            showToastNotification('Network error. Please try again.', 'error');
        }
    });
}

// Calculate totals on page load
updateTotals();
</script>
