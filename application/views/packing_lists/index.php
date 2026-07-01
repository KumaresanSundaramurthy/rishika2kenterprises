<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">

                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Packing Lists',
                    'pageDescription' => $PageDescription ?? 'All packing lists across transactions',
                ]); ?>

                <?php
                $plList   = $PLList ?? [];
                $total    = count($plList);
                $dateFormat = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
                ?>

                <!-- Stats strip -->
                <div class="apex-stats-strip">
                    <div class="apex-stat-item active" style="--stat-color:#696cff;">
                        <div class="apex-stat-icon" style="background:#eef2ff;">
                            <i class="bx bx-list-ul" style="color:#696cff;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Packing Lists</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $total; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-xxl flex-grow-1 py-3">
                    <div class="card">

                        <!-- Filter row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="plSearch" placeholder="PL # or party or transporter...">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh">
                                <i class="bx bx-refresh"></i>
                            </a>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="plTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:44px" class="text-center">#</th>
                                        <th>PL Number</th>
                                        <th>Source Document</th>
                                        <th>Party</th>
                                        <th>PL Date</th>
                                        <th>Transporter</th>
                                        <th>Vehicle</th>
                                        <th style="width:90px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                <?php if (empty($plList)): ?>
                                    <tr>
                                        <td colspan="8" class="py-5">
                                            <div class="d-flex flex-column align-items-center justify-content-center gap-2 text-muted">
                                                <i class="bx bx-list-ul" style="font-size:2.5rem;"></i>
                                                <span>No packing lists found</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $sno = 0; foreach ($plList as $pl): $sno++; ?>
                                    <?php
                                        $moduleUID  = (int) ($pl->ModuleUID ?? 0);
                                        $srcLabel   = $moduleUID === 103 ? 'Invoice' : ($moduleUID === 112 ? 'DC' : 'Txn');
                                        $srcBadge   = $moduleUID === 103 ? 'text-bg-primary' : 'text-bg-secondary';
                                    ?>
                                    <tr class="pl-row"
                                        data-pl-num="<?php echo htmlspecialchars(strtolower($pl->UniqueNumber ?? '')); ?>"
                                        data-party="<?php echo htmlspecialchars(strtolower($pl->PartyName ?? '')); ?>"
                                        data-transporter="<?php echo htmlspecialchars(strtolower($pl->TransporterName ?? '')); ?>">

                                        <td class="text-center text-muted" style="font-size:.78rem;"><?php echo $sno; ?></td>

                                        <td>
                                            <a href="/packing-list/<?php echo (int)$pl->TransUID; ?>" class="trans-doc-number fw-semibold">
                                                <?php echo htmlspecialchars($pl->UniqueNumber ?? '—'); ?>
                                            </a>
                                        </td>

                                        <td>
                                            <span class="badge <?php echo $srcBadge; ?>" style="font-size:.68rem;">
                                                <?php echo $srcLabel; ?>
                                            </span>
                                            <span class="ms-1" style="font-size:.82rem;">
                                                <?php echo htmlspecialchars($pl->TransNumber ?? '—'); ?>
                                            </span>
                                        </td>

                                        <td style="font-size:.85rem;">
                                            <?php echo htmlspecialchars($pl->PartyName ?? '—'); ?>
                                        </td>

                                        <td style="font-size:.82rem; white-space:nowrap;">
                                            <?php echo !empty($pl->PLDate) ? date($dateFormat, strtotime($pl->PLDate)) : '—'; ?>
                                        </td>

                                        <td style="font-size:.82rem;">
                                            <?php echo htmlspecialchars($pl->TransporterName ?? '—'); ?>
                                        </td>

                                        <td style="font-size:.82rem;">
                                            <?php echo htmlspecialchars($pl->VehicleNumber ?? '—'); ?>
                                        </td>

                                        <td class="text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="/packing-list/<?php echo (int)$pl->TransUID; ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>
                                                <a href="/packing-list/<?php echo (int)$pl->TransUID; ?>/print"
                                                   class="btn btn-sm btn-outline-primary" title="Print" target="_blank">
                                                    <i class="bx bx-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script>
(function () {
    // Client-side search
    var $input  = $('#plSearch');
    var $clear  = $('.r2k-clear');
    var $rows   = $('#plTable .pl-row');

    $input.on('input', function () {
        var q = $(this).val().trim().toLowerCase();
        $clear.toggleClass('d-none', q === '');
        $rows.each(function () {
            var match = !q
                || $(this).data('pl-num').indexOf(q) >= 0
                || $(this).data('party').indexOf(q) >= 0
                || $(this).data('transporter').indexOf(q) >= 0;
            $(this).toggle(match);
        });
    });

    $clear.on('click', function () {
        $input.val('').trigger('input');
    });

    // Refresh
    $('.pageRefresh').on('click', function () { location.reload(); });
})();
</script>
