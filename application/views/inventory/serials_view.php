<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Serial Numbers',
                    'pageDescription' => 'Manage serial numbers for tracked products',
                    'pageBackUrl'     => '/inventory',
                ]); ?>

                <?php
                $_showStats = (bool)($JwtData->GenSettings->ShowStats ?? 1);
                $stats      = $Stats ?? ['Available' => 0, 'Sold' => 0, 'Returned' => 0, 'Damaged' => 0, 'total' => 0];
                ?>

                <?php if ($_showStats): ?>
                <!-- Stats Strip -->
                <div class="apex-stats-strip" id="snStatsSection">
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#16a34a">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#16a34a"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Available</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statAvailable"><?php echo number_format($stats['Available']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#696cff">
                        <div class="apex-stat-icon" style="background:#e8e8ff"><i class="bx bx-shopping-bag" style="color:#696cff"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Sold</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statSold"><?php echo number_format($stats['Sold']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#0891b2">
                        <div class="apex-stat-icon" style="background:#cffafe"><i class="bx bx-undo" style="color:#0891b2"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Returned</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statReturned"><?php echo number_format($stats['Returned']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#dc2626">
                        <div class="apex-stat-icon" style="background:#fee2e2"><i class="bx bx-error-circle" style="color:#dc2626"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Damaged</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statDamaged"><?php echo number_format($stats['Damaged']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="snSearchInput" placeholder="Search serial number or product..." value="<?php echo htmlspecialchars($InitSearch ?? ''); ?>">
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button type="button" class="btn btn-sm btn-primary" id="btnAddSerial">
                                <i class="bx bx-plus me-1"></i>Add Serial
                            </button>
                        </div>

                        <!-- Tabs -->
                        <?php
                        $snTabList   = ['' => 'All', 'Available' => 'Available', 'Sold' => 'Sold', 'Returned' => 'Returned', 'Damaged' => 'Damaged'];
                        $snInitStatus = $InitStatus ?? '';
                        ?>
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs gap-1" id="snStatusTabs" role="tablist">
                                <?php foreach ($snTabList as $snStatus => $snLabel):
                                    $snActive = ($snInitStatus === $snStatus);
                                    $snCount  = $snActive && $ModAllCount > 0 ? number_format($ModAllCount) : '';
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $snActive ? 'active' : ''; ?> sn-tab" data-status="<?php echo $snStatus; ?>" href="javascript:void(0);">
                                        <?php echo $snLabel; ?>
                                        <span class="sn-tab-count trans-tab-count ms-1<?php echo (!$snActive || $ModAllCount == 0) ? ' d-none' : ''; ?>">
                                            <?php echo $snCount; ?>
                                        </span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="snTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th>Serial Number</th>
                                        <th>Product</th>
                                        <th style="width:110px;">Status</th>
                                        <th style="width:130px;">Source</th>
                                        <th style="width:140px;">Transaction</th>
                                        <th style="width:110px;">Added On</th>
                                        <th style="width:60px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="snTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center" id="snPagination">
                            <?php echo $ModPagination; ?>
                        </div>

                    </div><!-- /card -->
                </div><!-- /container-xxl -->

            </div><!-- /content-wrapper -->
            <?php $this->load->view('common/footer'); ?>
        </div><!-- /layout-page -->
    </div><!-- /layout-container -->
</div><!-- /layout-wrapper -->

<!-- ── Add Serial Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="addSerialModal" tabindex="-1" aria-labelledby="addSerialModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-barcode text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Add Serial Number</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btnSaveSerial"><i class="bx bx-check me-1"></i>Save</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x me-1"></i>Close</button>
                </div>
            </div>

            <div class="modal-body">
                <div class="card-body p-2 mb-2">

                    <div id="addSerialError" class="alert alert-danger d-none py-2 px-3 mb-3"></div>

                    <div class="row">
                        <div class="mb-3 col-12">
                            <label class="form-label" for="snProductSelect">Product <span class="text-danger">*</span></label>
                            <select id="snProductSelect" class="form-select">
                                <option value="">— Select a product —</option>
                            </select>
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" for="snSerialInput">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="snSerialInput"
                                   placeholder="Enter serial number" autocomplete="off" maxlength="100">
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" for="snNotesInput">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control" id="snNotesInput"
                                   placeholder="e.g. Opening stock" maxlength="255">
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
window._snCsrf      = '<?php echo $this->security->get_csrf_token_name(); ?>';
window._snCsrfVal   = '<?php echo $this->security->get_csrf_hash(); ?>';
window._snRowLimit  = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 10); ?>;
</script>
<script src="/js/inventory/serials.js"></script>
