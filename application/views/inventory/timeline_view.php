<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur           = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec           = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $defaultFilter = $DefaultFilter ?? ['DateFrom' => date('Y').'-01-01', 'DateTo' => date('Y').'-12-31'];
                    ?>

                    <!-- ── Page Header ────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#e0f2fe;">
                                <i class="bx bx-history" style="color:#0284c7;font-size:1.3rem;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Inventory Timeline'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php else: ?>
                                <div class="text-muted" style="font-size:.76rem;">All stock movements across products</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Export dropdown -->
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-export me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('Print')"><i class="bx bx-printer me-1"></i>Print</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('CSV')"><i class="bx bx-file me-1"></i>CSV</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('Excel')"><i class="bx bxs-file-export me-1"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('Pdf')"><i class="bx bxs-file-pdf me-1"></i>PDF</a></li>
                                </ul>
                            </div>
                            <a href="/inventory" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-package me-1"></i>Back to Inventory
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <!-- Item search -->
                                <div style="min-width:260px;">
                                    <select id="tlProductSearch" class="form-select form-select-sm" style="width:100%;"></select>
                                </div>
                                <!-- Date range -->
                                <div class="d-flex align-items-center gap-1">
                                    <input type="text" id="tlDateFrom" class="form-control form-control-sm" style="width:125px;"
                                           placeholder="From date"
                                           value="<?php echo date('d-m-Y', strtotime($defaultFilter['DateFrom'])); ?>">
                                    <span class="text-muted px-1" style="font-size:1rem;">→</span>
                                    <input type="text" id="tlDateTo" class="form-control form-control-sm" style="width:125px;"
                                           placeholder="To date"
                                           value="<?php echo date('d-m-Y', strtotime($defaultFilter['DateTo'])); ?>">
                                </div>
                                <!-- Movement type filter -->
                                <select id="tlMovementFilter" class="form-select form-select-sm" style="width:130px;">
                                    <option value="">All Movements</option>
                                    <option value="IN">Stock In only</option>
                                    <option value="OUT">Stock Out only</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <span class="badge text-bg-light border" id="tlTotalCount"><?php echo number_format($ModAllCount); ?> records</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="tlTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th>Item</th>
                                        <th style="text-align:right;white-space:nowrap;">Stock In</th>
                                        <th style="text-align:right;white-space:nowrap;">Stock Out</th>
                                        <th style="text-align:right;">Price</th>
                                        <th>Source</th>
                                        <th>Category</th>
                                        <th style="min-width:140px;">Remarks</th>
                                        <th style="white-space:nowrap;">Date / Updated By</th>
                                        <th style="width:80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tlTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center tlPagination" id="tlPagination">
                            <?php echo $ModPagination; ?>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>
            <?php $this->load->view('common/footer'); ?>
        </div>

    </div>
</div>

<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/inventory.js"></script>
<script src="/js/inventory_timeline.js"></script>

<script>
var TlCurrency        = <?php echo json_encode($cur); ?>;
var TlDecimals        = <?php echo (int)$dec; ?>;
var TlDefaultDateFrom = <?php echo json_encode($defaultFilter['DateFrom']); ?>;
var TlDefaultDateTo   = <?php echo json_encode($defaultFilter['DateTo']); ?>;
</script>
