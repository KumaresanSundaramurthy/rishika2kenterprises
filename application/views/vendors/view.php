<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <!-- Content wrapper -->
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Vendors',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <?php
                $s   = $VendStats ?? null;
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                ?>

                <?php if ($JwtData->TransSettings->ShowTransactionStats ?? 1): ?>
                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#ca8a04">
                        <div class="apex-stat-icon" style="background:#fef9c3"><i class="bx bxs-store" style="color:#ca8a04"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Vendors</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-total"><?php echo number_format((int)($s->TotalCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-active"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToCollect" data-stat-filter="ToCollect" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-arrow-to-bottom" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Collect</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-tocollect"><?php echo number_format((int)($s->ToCollectCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToCollectAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToPay" data-stat-filter="ToPay" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-arrow-from-bottom" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Pay</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-topay"><?php echo number_format((int)($s->ToPayCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToPayAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#94a3b8;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f8fafc"><i class="bx bx-bar-chart-alt-2" style="color:#94a3b8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Combined Stats</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:.72rem"><span class="vend-stat-month fw-bold"><?php echo number_format((int)($s->MonthCount ?? 0)); ?></span> Month</span>
                                <span style="font-size:.72rem"><span class="vend-stat-lastmonth fw-bold"><?php echo number_format((int)($s->LastMonthCount ?? 0)); ?></span> Last</span>
                                <span style="font-size:.72rem"><span class="vend-stat-fy fw-bold"><?php echo number_format((int)($s->FYCount ?? 0)); ?></span> FY</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <?php $showUserBtn = isset($OrgUsers) && is_array($OrgUsers) && count($OrgUsers) > 1; ?>

                    <!-- ── Main Card ── -->
                    <div class="card">

                        <!-- Filter Row -->
                        <?php $initIsGroups = ($InitTab ?? 'All') === 'Groups'; ?>
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="SearchDetails" placeholder="<?php echo $initIsGroups ? 'Group name, code, type...' : 'Name, mobile, GSTIN...'; ?>" value="<?php echo htmlspecialchars($InitSearch ?? ''); ?>">
                                <i class="bx bx-x r2k-clear<?php echo !empty($InitSearch) ? '' : ' d-none'; ?>" id="clearSearch"></i>
                            </div>
                            <?php if (!empty($Tags)): ?>
                            <a href="javascript:void(0);" id="vendTagFilter" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Tag"><i class="bx bx-purchase-tag me-1"></i>Tag</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="vendStatusFilterBtn" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Status"><i class="bx bx-toggle-left me-1"></i>Status</a>
                            <?php if ($showUserBtn): ?>
                            <a href="javascript:void(0);" id="vendUserFilterBtn" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <!-- Group-only filters -->
                            <a href="javascript:void(0);" id="vendGrpTypeFilterBtn" class="apex-filter-btn vgrp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" title="Filter by Group Type"><i class="bx bx-category me-1"></i>Group Type</a>
                            <a href="javascript:void(0);" id="vendGrpPartyFilterBtn" class="apex-filter-btn vgrp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" title="Filter by Vendor"><i class="bx bx-store me-1"></i>Vendor</a>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" id="btnSyncVendorsCache" title="Sync Cache"><i class="bx bx-planet"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn PageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>"><i class="bx bx-refresh"></i></a>
                            <div class="btn-group d-none vend-only-ctrl" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-slider-alt"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu" aria-labelledby="actionsDropdown">
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a></li>
                                    <li class="d-none" id="BulkSmsOption"><a class="dropdown-item" href="javascript:void(0);" id="btnBulkSms"><i class="bx bx-message-rounded me-2 text-info"></i><?php echo t('act_send_sms', 'Send SMS'); ?></a></li>
                                    <li class="d-none" id="BulkEmailOption"><a class="dropdown-item" href="javascript:void(0);" id="btnBulkEmail"><i class="bx bx-envelope me-2 text-primary"></i><?php echo t('act_send_email', 'Send Email'); ?></a></li>
                                </ul>
                            </div>
                            <div class="vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>">
                                <?php $this->load->view('common/partials/export_btn'); ?>
                            </div>
                            <a href="javascript:void(0);" class="btn btn-primary vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" id="btnCreateVendorHeader"
                               data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_vendor', 'Create Vendor'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </a>
                            <!-- Group-only button -->
                            <button type="button" id="btnNewVendorGroup" class="btn btn-primary vgrp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_item_group', 'Create Group'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="vendStatusTabs" role="tablist" data-trans-path="/vendors">
                                <li class="nav-item"><a class="nav-link<?php echo ($InitTab ?? 'All') === 'All' ? ' active' : ''; ?> vend-tab" data-status="All" data-url-tab="all" href="javascript:void(0);"> All <span class="trans-tab-count<?php echo (!$initIsGroups && ($VendStats->TotalCount ?? 0) > 0) ? '' : ' d-none'; ?>"><?php echo (!$initIsGroups && ($VendStats->TotalCount ?? 0) > 0) ? $VendStats->TotalCount : ''; ?></span></a></li>
                                <li class="nav-item">
                                    <?php $grpTotal = ($InitTab ?? 'All') === 'Groups' ? (int)($GrpTotal ?? 0) : 0; ?>
                                    <a class="nav-link vgrp-view-tab<?php echo ($InitTab ?? 'All') === 'Groups' ? ' active' : ''; ?>" href="javascript:void(0);" id="vendGroupsViewTab" data-status="Groups" data-url-tab="groups">
                                        Groups <span class="trans-tab-count<?php echo $grpTotal > 0 ? '' : ' d-none'; ?>" id="vgrpTabCount"><?php echo $grpTotal > 0 ? $grpTotal : ''; ?></span>
                                    </a>
                                </li>
                                <?php
                                $vShowStats   = (int)($JwtData->TransSettings->ShowTransactionStats ?? 1);
                                $vGrpStatsVis = ($InitTab ?? 'All') === 'Groups' && $vShowStats;
                                $vGrpS        = $GrpStats ?? null;
                                ?>
                                <!-- Group stats — visible only in groups mode when stats are enabled -->
                                <li id="vgrpTabStats" class="<?php echo $vGrpStatsVis ? 'd-flex' : 'd-none'; ?> align-items-center gap-3 ms-auto pe-2" style="font-size:.81rem;list-style:none;">
                                    <span class="text-muted"><?php echo t('lbl_total', 'Total'); ?>: <strong class="vg-stat-total text-body"><?php echo $vGrpS ? (int)($vGrpS->TotalCount ?? 0) : '—'; ?></strong></span>
                                    <span class="text-muted"><?php echo t('lbl_active', 'Active'); ?>: <strong class="vg-stat-active text-success"><?php echo $vGrpS ? (int)($vGrpS->ActiveCount ?? 0) : '—'; ?></strong></span>
                                    <span class="text-muted"><?php echo t('lbl_inactive', 'Inactive'); ?>: <strong class="vg-stat-inactive text-danger"><?php echo $vGrpS ? (int)($vGrpS->InactiveCount ?? 0) : '—'; ?></strong></span>
                                    <span class="text-muted"><?php echo t('col_members', 'Members'); ?>: <strong class="vg-stat-members text-body"><?php echo $vGrpS ? (int)($vGrpS->TotalMembers ?? 0) : '—'; ?></strong></span>
                                </li>
                            </ul>
                        </div>

                        <!-- Select-all banner -->
                        <div id="vendSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="vendSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="vendSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="vendSelectAllClear" class="ms-2 d-none">Clear selection</a>
                        </div>

                        <!-- Vendor table section -->
                        <div id="vendTableSection"<?php echo ($InitTab ?? 'All') === 'Groups' ? ' style="display:none;"' : ''; ?>>
                            <div class="table-responsive">
                                <table class="table trans-table MainviewTable mb-0" id="VendorsTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:36px">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input table-chkbox vendorHeaderCheck" type="checkbox">
                                                </div>
                                            </th>
                                            <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>" style="width:44px">#</th>
                                            <th class="vend-name-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                <span class="sort-label"><?php echo t('col_vendor', 'Vendor'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                            </th>
                                            <th class="vend-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_area', 'Area'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                            <th><?php echo t('col_mobile', 'Mobile'); ?></th>
                                            <th><?php echo t('col_gstin', 'GSTIN / Company'); ?></th>
                                            <th class="vend-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_balance', 'Balance'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                            <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                            <th style="width:80px"><?php echo t('col_actions', 'Actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody class="r2k-tbody table-border-bottom-0">
                                        <?php echo $ModRowData; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center VendorsPagination apex-pag-sticky" id="VendorsPagination">
                                <?php echo $ModPagination ?: ''; ?>
                            </div>
                        </div><!-- /#vendTableSection -->

                        <!-- Vendor Groups table section (hidden by default) -->
                        <?php $isVendGroupsTab = ($InitTab ?? 'All') === 'Groups'; ?>
                        <div id="vgrpTableSection"<?php echo $isVendGroupsTab ? '' : ' style="display:none;"'; ?>>
                            <div class="table-responsive">
                                <table class="table trans-table MainviewTable mb-0" id="VendorGroupsTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:36px">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input table-chkbox vgrpHeaderCheck" type="checkbox">
                                                </div>
                                            </th>
                                            <th><?php echo t('col_group_name', 'Group Name'); ?></th>
                                            <th style="width:110px;"><?php echo t('col_code', 'Code'); ?></th>
                                            <th style="width:140px;"><?php echo t('col_type', 'Type'); ?></th>
                                            <th class="text-center" style="width:90px;"><?php echo t('col_members', 'Members'); ?></th>
                                            <th style="width:150px;"><?php echo t('col_contact', 'Contact'); ?></th>
                                            <th class="text-end" style="width:140px;"><?php echo t('col_outstanding', 'Outstanding'); ?></th>
                                            <th style="width:90px;"><?php echo t('col_status', 'Status'); ?></th>
                                            <th style="width:100px;"><?php echo t('col_actions', 'Actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="VendorGroupsTableBody" class="r2k-tbody table-border-bottom-0">
                                        <?php if ($isVendGroupsTab && !empty($GrpRowData)): ?>
                                            <?php echo $GrpRowData; ?>
                                        <?php elseif ($isVendGroupsTab): ?>
                                            <tr><td colspan="9" class="text-center py-4 text-muted"><?php echo t('empty_groups', 'No groups found'); ?></td></tr>
                                        <?php else: ?>
                                            <tr><td colspan="9" class="text-center py-4 text-muted">Loading groups…</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="VendorGroupsPagination"><?php echo $isVendGroupsTab ? ($GrpPagination ?? '') : ''; ?></div>
                        </div><!-- /#vgrpTableSection -->

                    </div>


                </div>

            </div>
            <!-- Content wrapper -->
            
            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/modals/send_communication'); ?>
            <?php $this->load->view('common/modals/vendor_group_form'); ?>

            <!-- Vendor Add / Edit / Clone Modal -->
            <div class="modal fade" id="VendorFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="padding:0!important;">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" style="height:100vh;max-height:100vh;margin:0 auto;">
                    <div class="modal-content h-100 d-flex flex-column">

                        <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-doc-icon bg-warning bg-opacity-10">
                                    <i class="bx bx-store text-warning modal-doc-icon-inner"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" id="VendorFormModalTitle">Vendor</h5>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary" id="VendorFormSaveBtn">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>

                        <div class="modal-body p-0 flex-grow-1 overflow-auto" id="VendorFormModalBody">
                            <?php $this->load->view('vendors/forms/modal_body', [
                                'FormMode'    => 'add',
                                'FormData'    => null,
                                'BankDetails' => [],
                                'BillingAddr' => null,
                                'ShippingAddr'=> null,
                                'CountryInfo' => $CountryInfo,
                                'OrgCCode'    => $OrgCCode,
                                'OrgCISO2'    => $OrgCISO2,
                                'JwtData'     => $JwtData,
                            ]); ?>
                        </div>

                    </div>
                </div>
            </div>

            <?php $this->load->view('common/form/bank_details'); ?>
            <?php $this->load->view('common/form/address_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<!-- Filter panels (body-level to avoid overflow clipping) -->
<?php if (!empty($Tags)): ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'vendTagFilterBox',
        'triggerId'         => 'vendTagFilter',
        'checkClass'        => 'vend-tag-chk',
        'title'             => 'Tag Filter',
        'icon'              => 'bx-purchase-tag',
        'searchPlaceholder' => 'Search tags...',
        'items'             => array_map(function ($t) { return ['value' => $t, 'label' => $t]; }, $Tags),
    ],
]); ?>
<?php endif; ?>

<?php if ($showUserBtn): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'vendUserFilterBox',
        'triggerId'  => 'vendUserFilterBtn',
        'checkClass' => 'vend-user-chk',
        'title'      => 'Updated By',
        'OrgUsers'   => $OrgUsers,
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'vendStatusFilterBox',
        'triggerId'         => 'vendStatusFilterBtn',
        'checkClass'        => 'vend-status-chk',
        'title'             => 'Status',
        'icon'              => 'bx-toggle-left',
        'searchPlaceholder' => 'Search...',
        'items'             => [
            ['value' => '1', 'label' => 'Active'],
            ['value' => '0', 'label' => 'Inactive'],
        ],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'vendGrpTypeFilterBox',
        'triggerId'         => 'vendGrpTypeFilterBtn',
        'checkClass'        => 'vgrp-type-chk',
        'title'             => 'Group Type',
        'icon'              => 'bx-category',
        'searchPlaceholder' => 'Search types...',
        'items'             => [],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'vendGrpPartyFilterBox',
        'title' => 'Filter by Vendor',
        'icon'  => 'bx-store',
    ],
]); ?>

<!-- ── Vendor Group Detail Modal ───────────────────────────────────────── -->
<div class="modal fade" id="vgrpDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="overflow-x:hidden;">

            <!-- Header -->
            <div class="modal-header py-3 px-4" style="border-bottom:1px solid var(--bs-border-color);flex-wrap:nowrap;overflow:hidden;">
                <div class="d-flex align-items-center gap-3" style="min-width:0;flex:1 1 0;overflow:hidden;">
                    <div id="vgrpDetailIconWrap" style="width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bx bxs-layer" style="font-size:1.5rem;"></i>
                    </div>
                    <div style="min-width:0;overflow:hidden;">
                        <div class="fw-bold" id="vgrpDetailTitle" style="font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Vendor Group</div>
                        <div class="d-flex align-items-center gap-1 flex-wrap mt-1" id="vgrpDetailBadges"></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;margin-left:12px;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="vgrpDetailEditBtn" style="display:none;white-space:nowrap;">
                        <i class="bx bx-edit me-1"></i>Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x fs-5"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-0" id="vgrpDetailModalBody" style="overflow-x:hidden;">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="<?php echo _assetV('/js/transactions/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/party_filter.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo _assetV('/assets/vendor/css/attachments.css'); ?>">
<script src="<?php echo _assetV('/js/common/attachments.js'); ?>"></script>
<script src="<?php echo _assetV('/js/vendors.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/pagecheckbox.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/communication.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/gstin_fetch.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/bankdetails.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/address.js'); ?>"></script>
<script src="/js/common/vendor_group_form.js"></script>

<script>
$(function () {
    'use strict';

    var _vgrpDetailUID = 0;
    var _currency      = '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>';
    var _dec           = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

    var _typeColors = {
        'Business Group' : { bg: '#f0efff', c: '#696cff' },
        'Branch Group'   : { bg: '#e0f7fa', c: '#0097a7' },
        'Family Group'   : { bg: '#fce8ff', c: '#9333ea' },
        'Corporate Group': { bg: '#e8f5e9', c: '#2e7d32' },
        'Dealer Network' : { bg: '#fff3e0', c: '#ef6c00' },
        'Franchise Group': { bg: '#fce4ec', c: '#c62828' },
        'Custom'         : { bg: '#f5f5f5', c: '#616161' },
    };

    $(document).on('click', '.vgrp-view-btn', function () {
        var uid = parseInt($(this).data('uid'));
        if (!uid) return;
        _vgrpDetailUID = uid;

        $('#vgrpDetailTitle').text('Vendor Group');
        $('#vgrpDetailBadges').empty();
        $('#vgrpDetailIconWrap').css({ background: '#f0efff', color: '#696cff' });
        $('#vgrpDetailEditBtn').hide().off('click');
        $('#vgrpDetailModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-primary"></div></div>'
        );
        $('#vgrpDetailModal').modal('show');

        ajaxLoading(0);
        $.ajax({
            url   : '/vendors/getGroupDetail/' + uid,
            method: 'GET',
            cache : false,
            success: function (res) {
                ajaxLoading(1);
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) {
                    $('#vgrpDetailModalBody').html(
                        '<div class="alert alert-danger m-4">' + _vesc(res.Message || 'Failed to load group.') + '</div>'
                    );
                    return;
                }
                _renderVgrpDetail(res.Data, res.Overview, res.Members || []);
            },
            error: function () {
                ajaxLoading(1);
                $('#vgrpDetailModalBody').html(
                    '<div class="alert alert-danger m-4">Failed to load group details. Please try again.</div>'
                );
            }
        });
    });

    function _renderVgrpDetail(g, ov, members) {
        var tc        = _typeColors[g.GroupType] || { bg: '#f5f5f5', c: '#616161' };
        var memberCnt = parseInt(ov ? ov.MemberCount      : 0);
        var recvAmt   = parseFloat(ov ? ov.TotalReceivable : 0);
        var payAmt    = parseFloat(ov ? ov.TotalPayable    : 0);

        $('#vgrpDetailIconWrap').css({ background: tc.bg, color: tc.c });
        $('#vgrpDetailTitle').text(g.GroupName || '—');

        var badges = '';
        if (g.GroupCode) {
            badges += '<span class="badge bg-label-secondary" style="font-size:.7rem;font-family:monospace;">' + _vesc(g.GroupCode) + '</span>';
        }
        badges += '<span class="badge" style="background:' + tc.bg + ';color:' + tc.c + ';font-size:.7rem;font-weight:600;">' + _vesc(g.GroupType || '') + '</span>';
        badges += '<span class="badge ' + (g.IsActive ? 'bg-label-success' : 'bg-label-danger') + '" style="font-size:.68rem;">' + (g.IsActive ? 'Active' : 'Inactive') + '</span>';
        $('#vgrpDetailBadges').html(badges);

        $('#vgrpDetailEditBtn').show().off('click').on('click', function () {
            $('#vgrpDetailModal').modal('hide');
            VendorGroupForm.open('edit', _vgrpDetailUID, {
                onSaveSuccess: function (res) { _applyVgrpData(res); }
            });
        });

        // Summary cards
        var statsHtml =
            '<div class="row g-3 p-4 pb-3">' +
                '<div class="col-6 col-md-3">' +
                    '<div class="p-3 rounded-3 text-center" style="background:#f8f9fa;">' +
                        '<div style="font-size:1.4rem;font-weight:700;color:#9333ea;">' + memberCnt + '</div>' +
                        '<div class="text-muted" style="font-size:.74rem;">Members</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-6 col-md-3">' +
                    '<div class="p-3 rounded-3 text-center" style="background:#f0fdf4;">' +
                        '<div style="font-size:1rem;font-weight:700;color:#16a34a;">' + _currency + ' ' + recvAmt.toFixed(_dec) + '</div>' +
                        '<div class="text-muted" style="font-size:.74rem;">Total Receivable</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-6 col-md-3">' +
                    '<div class="p-3 rounded-3 text-center" style="background:#fff5f5;">' +
                        '<div style="font-size:1rem;font-weight:700;color:#dc2626;">' + _currency + ' ' + payAmt.toFixed(_dec) + '</div>' +
                        '<div class="text-muted" style="font-size:.74rem;">Total Payable</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-6 col-md-3">' +
                    '<div class="p-3 rounded-3 text-center" style="background:#f5f3ff;">' +
                        '<div style="font-size:.85rem;font-weight:600;color:#7c3aed;">' + _vesc(g.ContactPerson || '—') + '</div>' +
                        '<div class="text-muted" style="font-size:.74rem;">' + _vesc(g.Mobile || 'Contact Person') + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        // Members table with balance
        var totalRec = 0, totalPay = 0;
        var membersBody = '';
        if (members.length) {
            membersBody = members.map(function (m, i) {
                var isPri    = parseInt(m.IsGroupPrimary || 0) === 1;
                var bal      = parseFloat(m.Balance || 0);
                var balType  = m.BalanceType || 'Credit';
                var balColor = balType === 'Credit' ? '#dc3545' : '#28a745';
                if (balType === 'Debit')   totalRec += bal;
                if (balType === 'Credit')  totalPay += bal;
                return '<tr>' +
                    '<td class="text-muted text-center" style="width:36px;">' + (i + 1) + '</td>' +
                    '<td>' +
                        '<div class="fw-semibold">' + _vesc(m.Name) +
                            (isPri ? ' <span class="badge bg-label-warning ms-1" style="font-size:.62rem;">Primary</span>' : '') +
                        '</div>' +
                        (m.Area ? '<div class="text-muted" style="font-size:.74rem;">' + _vesc(m.Area) + '</div>' : '') +
                    '</td>' +
                    '<td class="text-muted" style="font-size:.82rem;">' + _vesc(m.MobileNumber || '—') + '</td>' +
                    '<td class="text-end" style="font-weight:600;color:' + balColor + ';width:150px;">' +
                        _currency + ' ' + bal.toFixed(_dec) +
                        '<div class="text-muted fw-normal" style="font-size:.7rem;">' + (balType === 'Credit' ? 'Payable' : 'Receivable') + '</div>' +
                    '</td>' +
                '</tr>';
            }).join('');
        } else {
            membersBody = '<tr><td colspan="4" class="text-center py-4 text-muted">No members in this group.</td></tr>';
        }

        var footerParts = [];
        if (totalRec > 0) footerParts.push('<span class="me-3" style="font-weight:700;color:#16a34a;">Receivable: ' + _currency + ' ' + totalRec.toFixed(_dec) + '</span>');
        if (totalPay > 0) footerParts.push('<span style="font-weight:700;color:#dc2626;">Payable: ' + _currency + ' ' + totalPay.toFixed(_dec) + '</span>');
        var footerHtml = footerParts.length
            ? '<tfoot><tr><td colspan="4" class="text-end py-3 pe-3 border-top" style="background:#f8f9fa;">' + footerParts.join('') + '</td></tr></tfoot>'
            : '';

        var membersHtml =
            '<div class="px-4 pb-4">' +
            '<table class="table table-hover align-middle mb-0" style="font-size:.85rem;">' +
            '<thead class="r2k-thead"><tr>' +
                '<th class="text-center" style="width:36px;">#</th>' +
                '<th>Vendor Name</th>' +
                '<th style="width:130px;">Mobile</th>' +
                '<th class="text-end" style="width:150px;">Balance</th>' +
            '</tr></thead>' +
            '<tbody>' + membersBody + '</tbody>' +
            footerHtml +
            '</table></div>';

        $('#vgrpDetailModalBody').html(statsHtml + membersHtml);
    }

    $('#vgrpDetailModal').on('hidden.bs.modal', function () {
        _vgrpDetailUID = 0;
        $('#vgrpDetailModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-primary"></div></div>'
        );
        $('#vgrpDetailBadges').empty();
        $('#vgrpDetailEditBtn').hide();
    });

    function _vesc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
});
</script>

<script>
let ModuleId = <?php echo $ModuleId; ?>;
const ModuleTable = '#VendorsTable';
const ModulePag = '.VendorsPagination';
const ModuleHeader = '.vendorHeaderCheck';
const ModuleRow = '.vendorsCheck';
const ModuleFileName = 'Vendor_Data';
const ModuleSheetName = 'Vendor';
const previewName = 'Vendor Details';
let nameSortState = 0;
let balSortState  = 0;
let areaSortState = 0;
var StateInfo = [];
var CityInfo  = [];
var OrgCountryISO2 = <?php echo json_encode($OrgCISO2 ?? 'IN'); ?>;
var VendShowUserFilter = <?php echo $showUserBtn ? 'true' : 'false'; ?>;
var _vendInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _vendInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;
var _vendShowStats  = <?php echo (int)($JwtData->TransSettings->ShowTransactionStats ?? 1); ?>;

$(function() {
    'use strict'

    $('#SearchDetails').val(_vendInitSearch || '');
    if (_vendInitSearch) { $('#SearchDetails').closest('.r2k-search-wrap').addClass('is-expanded r2k-search-active'); }
    $(ModuleRow).prop('checked', false).trigger('change');

    basePaginationFunc(ModulePag, function (pg, rl, f) {
        _vendClearSelectAll();
        getVendorsDetails(pg, rl, f);
    });
    baseRefreshPageFunc('.PageRefresh', getVendorsDetails);

    // ── Banner interactions ──
    $(document).on('click', '#vendSelectAllLink', function (e) {
        e.preventDefault();
        _vendSelectAllMode = true;
        _vendUpdateSelectAllBanner();
    });
    $(document).on('click', '#vendSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _vendClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Sync vendors to Upstash cache ───────────────────────────────────────
    $(document).on('click', '#btnSyncVendorsCache', function () {
        var $btn = $(this);
        $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        $.ajax({
            url    : '/vendors/syncVendorsCache',
            method : 'POST',
            data   : { [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
                showToastNotification('Sync failed. Please try again.', 'error');
            }
        });
    });
    // ────────────────────────────────────────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);
    $(ModuleHeader).on('click', function () { _vendUpdateSelectAllBanner(); });

    $(document).on('click', '#btnCreateVendorHeader', function () { openVendorModal('add'); });

    // ── Auto-hide ActionsDD until options are visible ──
    (function () {
        var $dd = $('#ActionsDD-Div');
        function syncDD() {
            var anyVisible = $('#DeleteOption, #BulkSmsOption, #BulkEmailOption')
                .filter(function () { return !$(this).hasClass('d-none'); }).length > 0;
            $dd.toggleClass('d-none', !anyVisible);
        }
        var observer = new MutationObserver(syncDD);
        ['DeleteOption', 'BulkSmsOption', 'BulkEmailOption'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) observer.observe(el, { attributes: true, attributeFilter: ['class'] });
        });
    })();

    // ── Stat card clicks ──
    $(document).on('click', '.apex-stat-item[data-stat-filter]', function () {
        var filterType = $(this).data('stat-filter');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');

        if (filterType === 'Active') {
            Filter['IsActive'] = 1;
            if (vendStatusFilter) vendStatusFilter.setState(['1']);
        } else if (filterType === 'ToCollect') {
            Filter['BalanceType'] = 'Debit';
            if (vendStatusFilter) vendStatusFilter.reset();
        } else if (filterType === 'ToPay') {
            Filter['BalanceType'] = 'Credit';
            if (vendStatusFilter) vendStatusFilter.reset();
        } else {
            if (vendStatusFilter) vendStatusFilter.reset();
        }

        PageNo = 0;
        getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── All tab click (also exits groups mode) ──
    $(document).on('click', '.vend-tab', function (e) {
        e.preventDefault();

        if (_inVgrpMode) {
            // Switching Groups → All: restore UI
            _inVgrpMode = false;
            vendGrpTypeFilter.reset();
            vendGrpPartyFilter.reset();
            delete _vgrpFilter['GroupType'];
            delete _vgrpFilter['VendorUID'];
            delete _vgrpFilter['SearchAllData'];
            $('.vend-only-ctrl').removeClass('d-none');
            $('.vgrp-only-ctrl').addClass('d-none');
            $('#SearchDetails').attr('placeholder', 'Name, mobile, GSTIN...').val('');
            $('#clearSearch').addClass('d-none');
            $('#vendTableSection').show();
            $('#vgrpTableSection').hide();
            $('#vgrpTabStats').removeClass('d-flex').addClass('d-none');
            $('.vgrp-view-tab').removeClass('active');
            $('.vend-tab').removeClass('active');
            $(this).addClass('active');
            $('#vgrpTabCount').text('').addClass('d-none');
            var $vAllBadge = $('.vend-tab .trans-tab-count');
            if ($vAllBadge.text().trim()) { $vAllBadge.removeClass('d-none'); }
            SelectedUIDs = [];
            $('.vgrpHeaderCheck').prop('checked', false).prop('indeterminate', false);
            $('#VendorGroupsTableBody .vgrpCheck').prop('checked', false).closest('tr').removeClass('row-sel');
            $(ModuleHeader).prop('checked', false);
            $('#ActionsDD-Div').addClass('d-none');
            _vendClearSelectAll();
            _pushTabUrl('All', '');
            // Option B: if page loaded directly on ?tab=groups, vendor table was empty — reload now
            if (!_vendDataLoaded) {
                _vendDataLoaded = true;
                PageNo = 0;
                getVendorsDetails(PageNo, RowLimit, Filter);
            }
            return;
        }

        // Already in vendor mode — reset filters and reload
        $('.vend-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="All"]').addClass('active');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        if (vendStatusFilter) vendStatusFilter.reset();
        _pushTabUrl('All', $.trim($('#SearchDetails').val()));
        PageNo = 0;
        getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Search — immediate URL update ──
    $('#SearchDetails').on('input', function () {
        var stat = $('.trans-status-tabs .nav-link.active').data('status') || 'All';
        _pushTabUrl(stat, $.trim($(this).val()));
    });

    // ── Search — debounced AJAX ──
    $('#SearchDetails').on('input', inputDelay(function () {
        if (_inVgrpMode) return;
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        delete Filter['SearchAllData'];
        if (val.length >= 3) Filter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) { PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter); }
    }, 750));

    $('#clearSearch').on('click', function () {
        $('#SearchDetails').val('');
        $(this).addClass('d-none');
        var stat = $('.trans-status-tabs .nav-link.active').data('status') || 'All';
        _pushTabUrl(stat, '');
        if (_inVgrpMode) {
            delete _vgrpFilter['SearchAllData'];
            _vgrpReload(1);
            return;
        }
        delete Filter['SearchAllData'];
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Row checkbox ──
    $(document).on('change', ModuleRow, function () {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        onClickOfCheckbox($(this), ModuleTable, ModuleHeader, ModuleRow);
        _vendClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Delete single ──
    $(document).on('click', '.DeleteVendor', function (e) {
        e.preventDefault();
        var id = $(this).data('vendoruid');
        if (!id) return;
        Swal.fire({
            title: 'Delete this vendor?', text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete',
        }).then(function (r) { if (r.isConfirmed) deleteVendor(id); });
    });

    // ── Delete bulk ──
    $('#btnDelete').on('click', function (e) {
        e.preventDefault();
        if (!_vendSelectAllMode && !SelectedUIDs.length) return;
        if (_inVgrpMode) {
            var grpCount = SelectedUIDs.length;
            Swal.fire({
                title: 'Delete ' + grpCount + ' group(s)?', text: 'Members will be unlinked. This cannot be undone.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete all',
            }).then(function (r) { if (r.isConfirmed) _deleteMultipleVendorGroups(); });
            return;
        }
        var count = _vendSelectAllMode ? _vendTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' vendor(s)?', text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete all',
        }).then(function (r) { if (r.isConfirmed) deleteMultipleVendors(); });
    });

    // ── Name sort ──
    $(document).on('click', '.vend-name-sortable', function (e) {
        e.preventDefault();
        nameSortState = (nameSortState + 1) % 3;
        if (nameSortState !== 0) {
            areaSortState = 0; balSortState = 0;
            delete Filter['AreaSorting']; delete Filter['BalanceSorting'];
            $('.vend-area-sortable .sort-icon, .vend-bal-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.vend-area-sortable, .vend-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (nameSortState === 1)      { icon.addClass('bx-sort-up text-primary');   $(this).attr('data-bs-title', 'Click for descending order'); Filter['NameSorting'] = 1; }
        else if (nameSortState === 2) { icon.addClass('bx-sort-down text-primary'); $(this).attr('data-bs-title', 'Click to remove sorting');   Filter['NameSorting'] = 2; }
        else                          { icon.addClass('bx-sort-alt-2'); $(this).attr('data-bs-title', 'Click for ascending order'); delete Filter['NameSorting']; }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Area sort ──
    $(document).on('click', '.vend-area-sortable', function (e) {
        e.preventDefault();
        areaSortState = (areaSortState + 1) % 3;
        if (areaSortState !== 0) {
            nameSortState = 0; balSortState = 0;
            delete Filter['NameSorting']; delete Filter['BalanceSorting'];
            $('.vend-name-sortable .sort-icon, .vend-bal-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.vend-name-sortable, .vend-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (areaSortState === 1)      { icon.addClass('bx-sort-up text-primary');   $(this).attr('data-bs-title', 'Click for descending order'); Filter['AreaSorting'] = 1; }
        else if (areaSortState === 2) { icon.addClass('bx-sort-down text-primary'); $(this).attr('data-bs-title', 'Click to remove sorting');   Filter['AreaSorting'] = 2; }
        else                          { icon.addClass('bx-sort-alt-2'); $(this).attr('data-bs-title', 'Click for ascending order'); delete Filter['AreaSorting']; }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Balance sort ──
    $(document).on('click', '.vend-bal-sortable', function (e) {
        e.preventDefault();
        balSortState = (balSortState + 1) % 3;
        if (balSortState !== 0) {
            nameSortState = 0; areaSortState = 0;
            delete Filter['NameSorting']; delete Filter['AreaSorting'];
            $('.vend-name-sortable .sort-icon, .vend-area-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.vend-name-sortable, .vend-area-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (balSortState === 1)      { icon.addClass('bx-sort-up text-primary');   $(this).attr('data-bs-title', 'Click for descending order'); Filter['BalanceSorting'] = 1; }
        else if (balSortState === 2) { icon.addClass('bx-sort-down text-primary'); $(this).attr('data-bs-title', 'Click to remove sorting');   Filter['BalanceSorting'] = 2; }
        else                         { icon.addClass('bx-sort-alt-2'); $(this).attr('data-bs-title', 'Click for ascending order'); delete Filter['BalanceSorting']; }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Tag filter (TransColFilter) ──
    <?php if (!empty($Tags)): ?>
    var vendTagFilter = new TransColFilter({
        boxId      : 'vendTagFilterBox',
        triggerId  : 'vendTagFilter',
        filterKey  : 'Tags',
        activeClass: 'has-filter',
        onApply    : function () {
            var state = vendTagFilter.getState();
            if (state.Tags && state.Tags.length) Filter['Tags'] = state.Tags;
            else delete Filter['Tags'];
            PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
        }
    });
    <?php endif; ?>

    // ── Status toggle ──
    $(document).on('click', '.vend-status-toggle', function (e) {
        e.preventDefault();
        var uid = $(this).data('uid');
        var newStatus = $(this).data('newstatus');
        var label = newStatus == 1 ? 'Active' : 'In-Active';
        Swal.fire({
            title: 'Change status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            toggleVendorStatus(uid, newStatus);
        });
    });

    // ── User filter (TransColFilter) ──
    var vendUserFilter = null;
    if (VendShowUserFilter) {
        vendUserFilter = new TransColFilter({
            boxId      : 'vendUserFilterBox',
            triggerId  : 'vendUserFilterBtn',
            filterKey  : 'UpdatedByUIDs',
            activeClass: 'has-filter',
            onApply    : function () {
                var state = vendUserFilter.getState();
                if (state.UpdatedByUIDs && state.UpdatedByUIDs.length) Filter['UpdatedByUIDs'] = state.UpdatedByUIDs;
                else delete Filter['UpdatedByUIDs'];
                PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
            }
        });
    }

    // ── Status filter (TransColFilter) ──
    var vendStatusFilter = new TransColFilter({
        boxId      : 'vendStatusFilterBox',
        triggerId  : 'vendStatusFilterBtn',
        filterKey  : 'IsActive',
        activeClass: 'has-filter',
        onApply    : function () {
            var state = vendStatusFilter.getState();
            delete Filter['IsActive'];
            if (state.IsActive && state.IsActive.length === 1) {
                Filter['IsActive'] = parseInt(state.IsActive[0], 10);
            }
            PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
        }
    });

    // ══════════════════════════════════════════════════════════════
    // VENDOR GROUPS TAB
    // ══════════════════════════════════════════════════════════════
    var _inVgrpMode    = false;
    var _vgrpPageNo    = 1;
    var _vgrpFilter    = {};
    var _vgrpLoaded    = false;
    var _vendDataLoaded = (_vendInitTab !== 'Groups'); // false when page loaded with ?tab=groups (Option B)

    // ── Groups tab click ──
    $(document).on('click', '.vgrp-view-tab', function (e) {
        e.preventDefault();
        if (_inVgrpMode) return;
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false);
        _vendClearSelectAll();
        _inVgrpMode = true;
        $('.vend-tab').removeClass('active');
        $('.vgrp-view-tab').addClass('active');
        $('.vend-only-ctrl').addClass('d-none');
        $('.vgrp-only-ctrl').removeClass('d-none');
        $('#SearchDetails').attr('placeholder', 'Group name, code, type...').val('');
        delete _vgrpFilter['SearchAllData'];
        $('#clearSearch').addClass('d-none');
        $('#vendTableSection').hide();
        $('#vgrpTableSection').show();
        if (_vendShowStats) { $('#vgrpTabStats').removeClass('d-none').addClass('d-flex'); }
        $('.vend-tab .trans-tab-count').addClass('d-none');
        _pushTabUrl('Groups', '');
        if (!_vgrpLoaded) { _vgrpLoaded = true; _vgrpReload(1); }
    });

    // ── Extend search to groups mode ──
    $('#SearchDetails').on('input.vgrp', inputDelay(function () {
        if (!_inVgrpMode) return;
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        delete _vgrpFilter['SearchAllData'];
        if (val.length >= 3) _vgrpFilter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) _vgrpReload(1);
    }, 750));

    // ── Apply groups response data to DOM ──
    function _applyVgrpData(res) {
        _vgrpPageNo = 1;
        $('#VendorGroupsTableBody').html(res.RecordHtmlData);
        $('#VendorGroupsPagination').html(res.Pagination);
        if (_vendShowStats) { _updateVgrpStats(res.Stats); }
        var cnt = res.TotalCount || 0;
        $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
    }

    // ── Groups AJAX reload ──
    function _vgrpReload(page) {
        _vgrpPageNo = page || 1;
        ajaxLoading(0);
        $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        $.ajax({
            url   : '/vendors/getGroupsData/' + _vgrpPageNo,
            method: 'POST',
            data  : { Filter: _vgrpFilter, [CsrfName]: CsrfToken },
            success: function (res) {
                ajaxLoading(1);
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                $('#VendorGroupsTableBody').html(res.RecordHtmlData);
                $('#VendorGroupsPagination').html(res.Pagination);
                if (_vendShowStats) { _updateVgrpStats(res.Stats); }
                var cnt = res.TotalCount || 0;
                $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
            },
            error: function () { ajaxLoading(1); }
        });
    }

    function _updateVgrpStats(s) {
        if (!s) return;
        $('.vg-stat-total').text(s.TotalCount    || 0);
        $('.vg-stat-active').text(s.ActiveCount   || 0);
        $('.vg-stat-inactive').text(s.InactiveCount || 0);
        $('.vg-stat-members').text(s.TotalMembers  || 0);
    }

    // ── Groups pagination ──
    $(document).on('click', '#VendorGroupsPagination .pagination .page-link', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        $('.vgrpHeaderCheck').prop('checked', false).prop('indeterminate', false);
        $('#VendorGroupsTableBody .vgrpCheck').prop('checked', false).closest('tr').removeClass('row-sel');
        MultipleDeleteOption();
        var pg = parseInt($(this).data('page'), 10);
        if (!isNaN(pg) && pg !== _vgrpPageNo) _vgrpReload(pg);
    });

    // ── Groups header checkbox ──
    $(document).on('change', '.vgrpHeaderCheck', function () {
        var isChecked = $(this).is(':checked');
        $('#VendorGroupsTableBody .vgrpCheck').prop('checked', isChecked)
            .closest('tr').toggleClass('row-sel', isChecked);
        SelectedUIDs = [];
        if (isChecked) {
            $('#VendorGroupsTableBody .vgrpCheck').each(function () {
                var uid = parseInt($(this).val());
                if (uid) SelectedUIDs.push(uid);
            });
        }
        MultipleDeleteOption();
    });

    // ── Groups row checkbox ──
    $(document).on('change', '.vgrpCheck', function () {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        var total   = $('#VendorGroupsTableBody .vgrpCheck').length;
        var checked = $('#VendorGroupsTableBody .vgrpCheck:checked').length;
        $('.vgrpHeaderCheck').prop('checked', total > 0 && checked === total)
                             .prop('indeterminate', checked > 0 && checked < total);
        var uid = parseInt($(this).val());
        if ($(this).is(':checked')) {
            if (SelectedUIDs.indexOf(uid) === -1) SelectedUIDs.push(uid);
        } else {
            SelectedUIDs = SelectedUIDs.filter(function (id) { return id !== uid; });
        }
        MultipleDeleteOption();
    });

    // ── Delete multiple groups ──
    /**
     * @returns {void}
     */
    function _deleteMultipleVendorGroups() {
        if (!SelectedUIDs.length) return;
        var uids = SelectedUIDs.slice();
        var idx  = 0;
        ajaxLoading(0);
        function _next() {
            if (idx >= uids.length) {
                ajaxLoading(1);
                SelectedUIDs = [];
                $('.vgrpHeaderCheck').prop('checked', false).prop('indeterminate', false);
                MultipleDeleteOption();
                _vgrpReload(_vgrpPageNo);
                return;
            }
            $.ajax({
                url   : '/vendors/deleteGroup',
                method: 'POST',
                data  : { GroupUID: uids[idx], [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    idx++;
                    _next();
                },
                error: function () { idx++; _next(); }
            });
        }
        _next();
    }

    // ── New Group button ──
    $(document).on('click', '#btnNewVendorGroup, .vbtn-new-group', function () {
        VendorGroupForm.open('add', null, { onSaveSuccess: function (res) { _applyVgrpData(res); } });
    });

    // ── Edit Group button ──
    $(document).on('click', '.vgrp-edit-btn', function () {
        var uid = parseInt($(this).data('uid'));
        if (!uid) return;
        VendorGroupForm.open('edit', uid, { onSaveSuccess: function (res) { _applyVgrpData(res); } });
    });

    // ── Group Type filter (groups mode only) ──
    var vendGrpTypeFilter = new TransColFilter({
        boxId       : 'vendGrpTypeFilterBox',
        triggerId   : 'vendGrpTypeFilterBtn',
        filterKey   : 'GroupType',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = vendGrpTypeFilter.getState();
            if (state.GroupType && state.GroupType.length) _vgrpFilter.GroupType = state.GroupType;
            else delete _vgrpFilter.GroupType;
            _vgrpReload(1);
        }
    });

    // Lazy-load vendor group types into filter box on first click (Upstash → AJAX)
    var _vendGrpTypeFilterPromise = null;
    $(document).on('click', '#vendGrpTypeFilterBtn', function () {
        if (_vendGrpTypeFilterPromise) return;
        _vendGrpTypeFilterPromise = loadCachedFilterData('vendor-group-types', '/vendors/getGroupTypes', true).then(function (types) {
            vendGrpTypeFilter.setItems(types.map(function (t) { return { value: t, label: t }; }));
        }).catch(function () { _vendGrpTypeFilterPromise = null; });
    });

    // ── Vendor filter for Groups tab (Upstash lazy-load via TransPartyColFilter) ──
    var vendGrpPartyFilter = new TransPartyColFilter({
        boxId     : 'vendGrpPartyFilterBox',
        triggerId : 'vendGrpPartyFilterBtn',
        partyType : 'vendor',
        filterKey : 'VendorUID',
        onApply   : function () {
            var state = vendGrpPartyFilter.getState();
            if (state.VendorUID) _vgrpFilter.VendorUID = state.VendorUID;
            else delete _vgrpFilter.VendorUID;
            _vgrpReload(1);
        }
    });

    // ── Group status toggle ──
    $(document).on('click', '.vgrp-status-toggle', function (e) {
        e.preventDefault();
        var uid       = $(this).data('uid');
        var newStatus = $(this).data('newstatus');
        var label     = newStatus == 1 ? 'Active' : 'Inactive';
        Swal.fire({
            title: 'Change group status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
            ajaxLoading(0);
            $.ajax({
                url   : '/vendors/toggleGroupStatus',
                method: 'POST',
                data  : { GroupUID: uid, IsActive: newStatus, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#VendorGroupsTableBody').html(res.RecordHtmlData);
                    $('#VendorGroupsPagination').html(res.Pagination);
                    if (_vendShowStats) { _updateVgrpStats(res.Stats); }
                },
                error: function () { showToastNotification('Failed to update status.', 'error'); }
            });
        });
    });

    // ── Group delete ──
    $(document).on('click', '.vgrp-delete-btn', function (e) {
        e.preventDefault();
        var uid = $(this).data('uid');
        if (!uid) return;
        Swal.fire({
            title: 'Delete this group?',
            text : 'Members will be unlinked. This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText : 'Yes, delete',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-danger" role="status"></span></td></tr>');
            ajaxLoading(0);
            $.ajax({
                url   : '/vendors/deleteGroup',
                method: 'POST',
                data  : { GroupUID: uid, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#VendorGroupsTableBody').html(res.RecordHtmlData);
                    $('#VendorGroupsPagination').html(res.Pagination);
                    if (_vendShowStats) { _updateVgrpStats(res.Stats); }
                    var cnt = res.TotalCount || 0;
                    $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                },
                error: function () { showToastNotification('Delete failed.', 'error'); }
            });
        });
    });


    initExport({ moduleUID: 202, getFilters: function () { return Filter; } });

    // ── URL tab state init ───────────────────────────────────────────────────
    if (_vendInitTab === 'Groups') {
        // Groups data is server-rendered by PHP — just wire up JS state, no AJAX needed
        _inVgrpMode = true;
        _vgrpLoaded = true;
        // PHP already rendered the correct d-none states for all filter/button elements
        if (_vendInitSearch && _vendInitSearch.length >= 3) {
            _vgrpFilter['SearchAllData'] = _vendInitSearch;
        }
    } else if (_vendInitSearch && _vendInitSearch.length >= 3) {
        Filter['SearchAllData'] = _vendInitSearch;
        PageNo = 0;
        getVendorsDetails(PageNo, RowLimit, Filter);
    }

    if (window.location.search.indexOf('action=create') !== -1) {
        $('#btnCreateVendorHeader').trigger('click');
    }

    /**
     * Called by the Quick Create handler in default.js when the user is already
     * on this page. Returns true if the create modal was opened, false if the
     * caller should navigate instead (e.g. we're on the Groups tab).
     * @returns {boolean}
     */
    window._qcPageCreate = function () {
        if ($('#btnCreateVendorHeader').hasClass('d-none')) return false;
        $('#btnCreateVendorHeader').trigger('click');
        return true;
    };

});
</script>