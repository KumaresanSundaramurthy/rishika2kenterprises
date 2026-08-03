<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Customers',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <?php
                $s   = $CustStats ?? null;
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                ?>

                <?php if ($JwtData->TransSettings->ShowTransactionStats ?? 1): ?>
                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#db2777">
                        <div class="apex-stat-icon" style="background:#fce7f3"><i class="bx bxs-group" style="color:#db2777"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Customers</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-total"><?php echo number_format((int)($s->TotalCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-active"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToCollect" data-stat-filter="ToCollect" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-arrow-to-bottom" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Collect</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-tocollect"><?php echo number_format((int)($s->ToCollectCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToCollectAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToPay" data-stat-filter="ToPay" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-arrow-from-bottom" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Pay</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-topay"><?php echo number_format((int)($s->ToPayCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToPayAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#94a3b8;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f8fafc"><i class="bx bx-bar-chart-alt-2" style="color:#94a3b8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Combined Stats</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:.72rem"><span class="cust-stat-month fw-bold"><?php echo number_format((int)($s->MonthCount ?? 0)); ?></span> Month</span>
                                <span style="font-size:.72rem"><span class="cust-stat-lastmonth fw-bold"><?php echo number_format((int)($s->LastMonthCount ?? 0)); ?></span> Last</span>
                                <span style="font-size:.72rem"><span class="cust-stat-fy fw-bold"><?php echo number_format((int)($s->FYCount ?? 0)); ?></span> FY</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <?php $showUserBtn = is_array($OrgUsers) && count($OrgUsers) > 1; ?>

                    <!-- ── Main Card ── -->
                    <div class="card">

                        <!-- Filter Row -->
                        <?php $initIsGroups = ($InitTab ?? 'All') === 'Groups'; ?>
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap<?php echo !empty($InitSearch) ? ' is-expanded r2k-search-active' : ''; ?>">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="SearchDetails" placeholder="<?php echo $initIsGroups ? 'Group name, code, type...' : 'Name, mobile, GSTIN...'; ?>" value="<?php echo htmlspecialchars($InitSearch ?? ''); ?>">
                                <i class="bx bx-x r2k-clear<?php echo !empty($InitSearch) ? '' : ' d-none'; ?>" id="clearSearch"></i>
                            </div>
                            <?php if (!empty($Tags)): ?>
                            <a href="javascript:void(0);" id="custTagFilterBtn" class="apex-filter-btn cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Tag">
                                <i class="bx bx-purchase-tag"></i>Tags
                            </a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="custTypeFilterBtn" class="apex-filter-btn cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Customer Type">
                                <i class="bx bx-user-pin me-1"></i>Customer Type
                            </a>
                            <?php if ($showUserBtn): ?>
                            <a href="javascript:void(0);" id="custUserFilterBtn" class="apex-filter-btn cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by User">
                                <i class="bx bx-user"></i>Updated By
                            </a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="custStatusFilterBtn" class="apex-filter-btn cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Status">
                                <i class="bx bx-toggle-left me-1"></i>Status
                            </a>
                            <!-- Group-only filter chips -->
                            <a href="javascript:void(0);" id="grpTypeFilterBtn" class="apex-filter-btn grp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" title="Filter by Group Type">
                                <i class="bx bx-category"></i> Group Type
                            </a>
                            <a href="javascript:void(0);" id="grpCustomerFilterBtn" class="apex-filter-btn grp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" title="Filter by Customer">
                                <i class="bx bx-user"></i> Customer
                            </a>
                            <div class="apex-filter-spacer"></div>
                            <!-- Customer-only controls -->
                            <a href="javascript:void(0);" class="apex-filter-btn cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" id="btnSyncCustomersCache" title="Sync Cache"><i class="bx bx-planet"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn PageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>"><i class="bx bx-refresh"></i></a>
                            <div class="btn-group d-none cust-only-ctrl" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo t('lbl_actions', 'Actions'); ?>">
                                    <i class="bx bx-menu"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu" aria-labelledby="actionsDropdown">
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a></li>
                                </ul>
                            </div>
                            <div class="cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>">
                                <?php $this->load->view('common/partials/export_btn'); ?>
                            </div>
                            <a href="javascript:void(0);" class="btn btn-primary cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" id="btnCreateCustomerHeader"
                               data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_customer', 'Create Customer'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </a>
                            <!-- Group-only button -->
                            <button type="button" id="btnNewGroup" class="btn btn-primary grp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_item_group', 'Create Group'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="custStatusTabs" role="tablist" data-trans-path="/customers">
                                <li class="nav-item"><a class="nav-link<?php echo ($InitTab ?? 'All') === 'All' ? ' active' : ''; ?> cust-tab" data-status="All" data-url-tab="all" href="javascript:void(0);"><?php echo t('tab_all_customers', 'All Customers'); ?> <span class="trans-tab-count<?php echo (!$initIsGroups && ($CustStats->TotalCount ?? 0) > 0) ? '' : ' d-none'; ?>"><?php echo (!$initIsGroups && ($CustStats->TotalCount ?? 0) > 0) ? $CustStats->TotalCount : ''; ?></span></a></li>
                                <li class="nav-item">
                                    <?php $grpTotal = ($InitTab ?? 'All') === 'Groups' ? (int)($GrpTotal ?? 0) : 0; ?>
                                    <a class="nav-link grp-view-tab<?php echo ($InitTab ?? 'All') === 'Groups' ? ' active' : ''; ?>" href="javascript:void(0);" id="groupsViewTab" data-status="Groups" data-url-tab="groups">
                                        Groups <span class="trans-tab-count<?php echo $grpTotal > 0 ? '' : ' d-none'; ?>" id="grpTabCount"><?php echo $grpTotal > 0 ? $grpTotal : ''; ?></span>
                                    </a>
                                </li>
                                <?php
                                $showStats    = (int)($JwtData->TransSettings->ShowTransactionStats ?? 1);
                                $grpStatsVis  = ($InitTab ?? 'All') === 'Groups' && $showStats;
                                $grpS         = $GrpStats ?? null;
                                ?>
                                <!-- Group stats — visible only in groups mode when stats are enabled -->
                                <li id="grpTabStats" class="<?php echo $grpStatsVis ? 'd-flex' : 'd-none'; ?> align-items-center gap-3 ms-auto pe-2" style="font-size:.81rem;list-style:none;">
                                    <span class="text-muted"><?php echo t('lbl_total', 'Total'); ?>: <strong class="cg-stat-total text-body"><?php echo $grpS ? (int)$grpS->TotalCount : '—'; ?></strong></span>
                                    <span class="text-muted"><?php echo t('lbl_active', 'Active'); ?>: <strong class="cg-stat-active text-success"><?php echo $grpS ? (int)$grpS->ActiveCount : '—'; ?></strong></span>
                                    <span class="text-muted"><?php echo t('lbl_inactive', 'Inactive'); ?>: <strong class="cg-stat-inactive text-danger"><?php echo $grpS ? (int)$grpS->InactiveCount : '—'; ?></strong></span>
                                    <span class="text-muted"><?php echo t('col_members', 'Members'); ?>: <strong class="cg-stat-members text-body"><?php echo $grpS ? (int)$grpS->TotalMembers : '—'; ?></strong></span>
                                </li>
                            </ul>
                        </div>

                        <!-- Customer table section -->
                        <div id="custTableSection"<?php echo ($InitTab ?? 'All') === 'Groups' ? ' style="display:none;"' : ''; ?>>
                        <!-- Select-all banner (Pattern 3) -->
                        <div id="custSelectAllBanner" class="r2k-select-all-banner d-none">
                            <i class="bx bx-info-circle"></i>
                            <span id="custSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="custSelectAllLink" class="r2k-sal-link"></a>
                            <a href="javascript:void(0);" id="custSelectAllClear" class="r2k-sal-clear d-none"><?php echo t('lbl_clear_selection', 'Clear selection'); ?></a>
                        </div>
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="CustomersTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="th-chk" style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox customerHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="th-sno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>" style="width:44px">#</th>
                                        <th class="cust-name-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            <span class="sort-label"><?php echo t('col_customer', 'Customer'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                        </th>
                                        <th class="cust-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_area', 'Area'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                        <th><?php echo t('col_mobile', 'Mobile'); ?></th>
                                        <th><?php echo t('col_gstin', 'GSTIN / Company'); ?></th>
                                        <th class="cust-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_balance', 'Balance'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                        <th><?php echo t('col_customer_type', 'Customer Type'); ?></th>
                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                        <th class="th-act" style="width:80px"><?php echo t('col_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center CustomersPagination apex-pag-sticky" id="CustomersPagination">
                            <?php echo $ModPagination; ?>
                        </div>
                        </div><!-- /#custTableSection -->

                        <!-- Groups table section -->
                        <?php $isGroupsTab = ($InitTab ?? 'All') === 'Groups'; ?>
                        <div id="grpTableSection"<?php echo $isGroupsTab ? '' : ' style="display:none;"'; ?>>
                            <div class="table-responsive">
                                <table class="table trans-table MainviewTable mb-0" id="GroupsTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th class="th-chk" style="width:36px">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input table-chkbox grpHeaderCheck" type="checkbox">
                                                </div>
                                            </th>
                                            <th><?php echo t('col_group_name', 'Group Name'); ?></th>
                                            <th style="width:110px;"><?php echo t('col_code', 'Code'); ?></th>
                                            <th style="width:140px;"><?php echo t('col_type', 'Type'); ?></th>
                                            <th class="text-center" style="width:90px;"><?php echo t('col_members', 'Members'); ?></th>
                                            <th style="width:150px;"><?php echo t('col_contact', 'Contact'); ?></th>
                                            <th class="text-end" style="width:140px;"><?php echo t('col_outstanding', 'Outstanding'); ?></th>
                                            <th style="width:90px;"><?php echo t('col_status', 'Status'); ?></th>
                                            <th class="text-center" style="width:100px;"><?php echo t('col_actions', 'Actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="GroupsTableBody" class="r2k-tbody table-border-bottom-0">
                                        <?php if ($isGroupsTab && !empty($GrpRowData)): ?>
                                            <?php echo $GrpRowData; ?>
                                        <?php elseif ($isGroupsTab): ?>
                                            <tr><td colspan="9" class="text-center py-4 text-muted"><?php echo t('empty_groups', 'No groups found'); ?></td></tr>
                                        <?php else: ?>
                                            <tr><td colspan="9" class="text-center py-4 text-muted">Loading groups…</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="GroupsPagination"><?php echo $isGroupsTab ? ($GrpPagination ?? '') : ''; ?></div>
                        </div><!-- /#grpTableSection -->

                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/modals/customer_form'); ?>
            <?php $this->load->view('common/modals/customer_group_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>

    </div>
</div>

<!-- Filter panels (body-level to avoid overflow clipping) -->
<?php if (!empty($Tags)): ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'custTagFilterBox',
        'triggerId'         => 'custTagFilterBtn',
        'checkClass'        => 'cust-tag-chk',
        'title'             => 'Tag Filter',
        'icon'              => 'bx-purchase-tag',
        'searchPlaceholder' => 'Search tags...',
        'items'             => array_map(function ($t) { return ['value' => $t, 'label' => $t]; }, $Tags),
    ],
]); ?>
<?php endif; ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'custTypeFilterBox',
        'triggerId'         => 'custTypeFilterBtn',
        'checkClass'        => 'cust-type-chk',
        'title'             => 'Customer Type',
        'icon'              => 'bx-user-pin',
        'searchPlaceholder' => 'Search types...',
        'items'             => [],
    ],
]); ?>
<?php if ($showUserBtn): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'custUserFilterBox',
        'triggerId'  => 'custUserFilterBtn',
        'checkClass' => 'cust-user-chk',
        'title'      => 'Updated By',
        'OrgUsers'   => $OrgUsers,
    ],
]); ?>
<?php endif; ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'grpTypeFilterBox',
        'triggerId'         => 'grpTypeFilterBtn',
        'checkClass'        => 'grp-type-chk',
        'title'             => 'Group Type',
        'icon'              => 'bx-category',
        'searchPlaceholder' => 'Search types...',
        'items'             => [],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'grpCustomerFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'custStatusFilterBox',
        'triggerId'         => 'custStatusFilterBtn',
        'checkClass'        => 'cust-status-chk',
        'title'             => 'Status',
        'icon'              => 'bx-toggle-left',
        'searchPlaceholder' => 'Search...',
        'items'             => [
            ['value' => '1', 'label' => 'Active'],
            ['value' => '0', 'label' => 'Inactive'],
        ],
    ],
]); ?>

<!-- ── Group Detail Modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="grpDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="overflow-x:hidden;">

            <!-- Header -->
            <div class="modal-header py-3 px-4" id="grpDetailModalHeader" style="border-bottom:1px solid var(--bs-border-color);flex-wrap:nowrap;overflow:hidden;">
                <div class="d-flex align-items-center gap-3" style="min-width:0;flex:1 1 0;overflow:hidden;">
                    <div id="grpDetailIconWrap" style="width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bx bxs-layer" style="font-size:1.5rem;"></i>
                    </div>
                    <div style="min-width:0;overflow:hidden;">
                        <div class="fw-bold" id="grpDetailTitle" style="font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Customer Group</div>
                        <div class="d-flex align-items-center gap-1 flex-wrap mt-1" id="grpDetailBadges"></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;margin-left:12px;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="grpDetailEditBtn" style="display:none;white-space:nowrap;">
                        <i class="bx bx-edit me-1"></i>Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x fs-5"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-0" id="grpDetailModalBody" style="overflow-x:hidden;">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var _grpDetailUID = 0;
    var _currency             = '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>';
    var _dec                  = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

    var _typeColors = {
        'Business Group' : { bg: '#f0efff', c: '#696cff' },
        'Branch Group'   : { bg: '#e0f7fa', c: '#0097a7' },
        'Family Group'   : { bg: '#fce8ff', c: '#9333ea' },
        'Corporate Group': { bg: '#e8f5e9', c: '#2e7d32' },
        'Dealer Network' : { bg: '#fff3e0', c: '#ef6c00' },
        'Franchise Group': { bg: '#fce4ec', c: '#c62828' },
        'Custom'         : { bg: '#f5f5f5', c: '#616161' },
    };

    // ── Open modal on view button click ───────────────────────────────────────
    $(document).on('click', '.grp-view-btn', function () {
        var uid = parseInt($(this).data('uid'));
        if (!uid) return;

        _grpDetailUID = uid;

        // Reset header
        $('#grpDetailTitle').text('Customer Group');
        $('#grpDetailBadges').empty();
        $('#grpDetailIconWrap').css({ background: '#f0efff', color: '#696cff' });
        $('#grpDetailEditBtn').hide().off('click');

        // Show spinner in body
        $('#grpDetailModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-primary"></div></div>'
        );

        $('#grpDetailModal').modal('show');

        ajaxLoading(0);
        $.ajax({
            url   : '/customers/getGroupDetail/' + uid,
            method: 'GET',
            cache : false,
            success: function (res) {
                ajaxLoading(1);
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) {
                    $('#grpDetailModalBody').html(
                        '<div class="alert alert-danger m-4">' + _esc(res.Message || 'Failed to load group.') + '</div>'
                    );
                    return;
                }
                _renderGroupDetail(res.Data, res.Overview, res.Members || []);
            },
            error: function () {
                ajaxLoading(1);
                $('#grpDetailModalBody').html(
                    '<div class="alert alert-danger m-4">Failed to load group details. Please try again.</div>'
                );
            }
        });
    });

    // ── Render header + body ──────────────────────────────────────────────────
    function _renderGroupDetail(g, ov, members) {
        var tc        = _typeColors[g.GroupType] || { bg: '#f5f5f5', c: '#616161' };
        var memberCnt = parseInt(ov ? ov.MemberCount      : 0);
        var recvAmt   = parseFloat(ov ? ov.TotalReceivable : 0);
        var payAmt    = parseFloat(ov ? ov.TotalPayable    : 0);

        // Header icon + title + badges
        $('#grpDetailIconWrap').css({ background: tc.bg, color: tc.c });
        $('#grpDetailTitle').text(g.GroupName || '—');

        var badges = '';
        if (g.GroupCode) {
            badges += '<span class="badge bg-label-secondary" style="font-size:.7rem;font-family:monospace;">' + _esc(g.GroupCode) + '</span>';
        }
        badges += '<span class="badge" style="background:' + tc.bg + ';color:' + tc.c + ';font-size:.7rem;font-weight:600;">' + _esc(g.GroupType || '') + '</span>';
        badges += '<span class="badge ' + (g.IsActive ? 'bg-label-success' : 'bg-label-danger') + '" style="font-size:.68rem;">' + (g.IsActive ? 'Active' : 'Inactive') + '</span>';
        $('#grpDetailBadges').html(badges);

        $('#grpDetailEditBtn').show().off('click').on('click', function () {
            $('#grpDetailModal').modal('hide');
            CustomerGroupForm.open('edit', _grpDetailUID, {
                onSaveSuccess: function (res) { _applyGrpData(res); }
            });
        });

        // ── Summary stat cards ──────────────────────────────────────────────
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
                        '<div style="font-size:.85rem;font-weight:600;color:#7c3aed;">' + _esc(g.ContactPerson || '—') + '</div>' +
                        '<div class="text-muted" style="font-size:.74rem;">' + _esc(g.Mobile || 'Contact Person') + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        // ── Members table (with balance, no separate Outstanding tab) ───────
        var totalRec = 0, totalPay = 0;
        var membersBody = '';
        if (members.length) {
            membersBody = members.map(function (m, i) {
                var isPri    = parseInt(m.IsGroupPrimary || 0) === 1;
                var bal      = parseFloat(m.Balance || 0);
                var balType  = m.BalanceType || 'Debit';
                var balColor = balType === 'Credit' ? '#dc3545' : '#28a745';
                if (balType === 'Debit')  totalRec += bal;
                if (balType === 'Credit') totalPay += bal;
                return '<tr>' +
                    '<td class="text-muted text-center" style="width:36px;">' + (i + 1) + '</td>' +
                    '<td>' +
                        '<div class="fw-semibold">' + _esc(m.Name) +
                            (isPri ? ' <span class="badge bg-label-warning ms-1" style="font-size:.62rem;">Primary</span>' : '') +
                        '</div>' +
                        (m.Area ? '<div class="text-muted" style="font-size:.74rem;">' + _esc(m.Area) + '</div>' : '') +
                    '</td>' +
                    '<td class="text-muted" style="font-size:.82rem;">' + _esc(m.MobileNumber || '—') + '</td>' +
                    '<td class="text-end" style="font-weight:600;color:' + balColor + ';">' +
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
                '<th>Customer Name</th>' +
                '<th style="width:130px;">Mobile</th>' +
                '<th class="text-end" style="width:150px;">Balance</th>' +
            '</tr></thead>' +
            '<tbody>' + membersBody + '</tbody>' +
            footerHtml +
            '</table></div>';

        $('#grpDetailModalBody').html(statsHtml + membersHtml);
    }

    // Clear state when modal closes
    $('#grpDetailModal').on('hidden.bs.modal', function () {
        _grpDetailUID = 0;
        $('#grpDetailModalBody').html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-primary"></div></div>'
        );
        $('#grpDetailBadges').empty();
        $('#grpDetailEditBtn').hide();
    });

    function _esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

});
</script>

<?php $this->load->view('common/footer'); ?>

<script src="<?php echo _assetV('/js/common/address.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/bankdetails.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/gstin_fetch.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo _assetV('/assets/vendor/css/attachments.css'); ?>">
<script src="<?php echo _assetV('/js/common/attachments.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_group_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/transactions/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/party_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/customers.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/pagecheckbox.js'); ?>"></script>
<script src="/js/common/communication.js"></script>

<script>
let ModuleId = 2;
const ModuleTable  = '#CustomersTable';
const ModulePag    = '.CustomersPagination';
const ModuleHeader = '.customerHeaderCheck';
const ModuleRow    = '.customerCheck';
const ModuleFileName  = 'Customer_Data';
const ModuleSheetName = 'Customer';
const previewName     = 'Customer Details';
let nameSortState = 0;
let balSortState  = 0;
let areaSortState = 0;
var StateInfo = [];
var CityInfo  = [];
var OrgCountryISO2 = <?php echo json_encode($JwtData->Org->OrgCISO2 ?? 'IN'); ?>;
var CustShowUserFilter = <?php echo $showUserBtn ? 'true' : 'false'; ?>;
var _custInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _custInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;
var _custShowStats  = <?php echo (int)($JwtData->TransSettings->ShowTransactionStats ?? 1); ?>;

$(function () {
    'use strict';

    // ── Groups mode state ──
    var _inGroupsMode   = false;
    var _grpPageNo      = 1;
    var _grpFilter      = {};
    var _grpLoaded      = false;
    var _custDataLoaded = (_custInitTab !== 'Groups'); // false when page loaded with ?tab=groups (Option B)

    $('#SearchDetails').val(_custInitSearch || '');
    if (_custInitSearch && _custInitSearch.length >= 3) { Filter['SearchAllData'] = _custInitSearch; }
    $(ModuleRow).prop('checked', false).trigger('change');

    // Auto-show/hide the Actions button based on whether DeleteOption is visible
    (function () {
        var $dd = $('#ActionsDD-Div');
        function syncDD() {
            $dd.toggleClass('d-none', $('#DeleteOption').hasClass('d-none'));
        }
        var el = document.getElementById('DeleteOption');
        if (el) new MutationObserver(syncDD).observe(el, { attributes: true, attributeFilter: ['class'] });
    })();

    // Seed initial total from server-side render
    _custTotalRecords = <?php echo (int)($InitTotalCount ?? 0); ?>;
    _custPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;

    basePaginationFunc(ModulePag, getCustomersDetails);
    baseRefreshPageFunc('.PageRefresh', getCustomersDetails);
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    // ── Select-all banner (Pattern 3) ──────────────────────────────────────
    // After header checkbox — show banner when all page rows selected
    $(ModuleHeader).on('click', function () {
        if ($(this).prop('checked')) {
            _custUpdateSelectAllBanner();
        } else {
            _custClearSelectAll();
        }
    });

    // Individual row uncheck → exit select-all mode
    $(document).on('click', ModuleRow, function () {
        if (!$(this).prop('checked')) {
            _custClearSelectAll();
        }
    });

    // Pagination → clear select-all mode
    $(ModulePag).on('click', 'a', function () {
        _custClearSelectAll();
    });

    // "Select all N customers?" link
    $(document).on('click', '#custSelectAllLink', function () {
        _custSelectAllMode = true;
        _custUpdateSelectAllBanner();
        MultipleDeleteOption();
        _updateBulkCommOptions();
    });

    // "Clear selection" link
    $(document).on('click', '#custSelectAllClear', function () {
        _custSelectAllMode = false;
        SelectedUIDs       = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false);
        _custClearSelectAll();
        MultipleDeleteOption();
        _updateBulkCommOptions();
    });
    // ── End select-all banner ───────────────────────────────────────────────

    // ── Sync customers to Upstash cache ───────────────────────────────────────
    $(document).on('click', '#btnSyncCustomersCache', function () {
        var $btn = $(this);
        $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        $.ajax({
            url    : '/customers/syncCustomersCache',
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

    // Header "New Customer" button
    $(document).on('click', '#btnCreateCustomerHeader', function () {
        CustomerForm.open('add', null, { onSaveSuccess: _custPageSaveSuccess });
    });

    // ── Stat card clicks ──
    $(document).on('click', '.apex-stat-item[data-stat-filter]', function () {
        var filterType = $(this).data('stat-filter');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');

        if (filterType === 'Active') {
            Filter['IsActive'] = 1;
            if (custStatusFilter) custStatusFilter.setState(['1']);
        } else if (filterType === 'ToCollect') {
            Filter['BalanceType'] = 'Debit';
            if (custStatusFilter) custStatusFilter.reset();
        } else if (filterType === 'ToPay') {
            Filter['BalanceType'] = 'Credit';
            if (custStatusFilter) custStatusFilter.reset();
        } else {
            if (custStatusFilter) custStatusFilter.reset();
        }

        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── All tab click (also exits groups mode) ──
    $(document).on('click', '.cust-tab', function (e) {
        e.preventDefault();

        if (_inGroupsMode) {
            // Switching Groups → All: restore UI
            _inGroupsMode = false;

            // Reset group filters before leaving groups mode
            GrpTypeFilter.reset();
            grpCustomerFilter.reset();
            delete _grpFilter['GroupType'];
            delete _grpFilter['CustomerUID'];
            delete _grpFilter['SearchAllData'];

            $('.cust-only-ctrl').removeClass('d-none');
            $('.grp-only-ctrl').addClass('d-none');
            $('#SearchDetails').attr('placeholder', 'Name, mobile, GSTIN...').val('');
            $('#clearSearch').addClass('d-none');
            delete Filter['SearchAllData'];

            $('#custTableSection').show();
            $('#grpTableSection').hide();
            $('#grpTabStats').removeClass('d-flex').addClass('d-none');
            $('.grp-view-tab').removeClass('active');
            $('.cust-tab').removeClass('active');
            $(this).addClass('active');
            $('#grpTabCount').text('').addClass('d-none');
            var $allBadge = $('.cust-tab .trans-tab-count');
            if ($allBadge.text().trim()) { $allBadge.removeClass('d-none'); }

            _pushTabUrl('All', '');

            // Clear group checkbox selections so Actions dropdown doesn't linger
            SelectedUIDs = [];
            $('.grpHeaderCheck').prop('checked', false).prop('indeterminate', false);
            $('#GroupsTable tbody .grpCheck').prop('checked', false).closest('tr').removeClass('row-sel');
            $(ModuleHeader).prop('checked', false);
            $('#ActionsDD-Div').addClass('d-none');
            _custClearSelectAll();

            // Option B: if page loaded directly on ?tab=groups, customer table was empty — reload now
            if (!_custDataLoaded) {
                _custDataLoaded = true;
                PageNo = 0;
                getCustomersDetails(PageNo, RowLimit, Filter);
            }

            return;
        }

        // Already in customer mode — reset filters and reload
        $('.cust-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="All"]').addClass('active');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        if (custStatusFilter) custStatusFilter.reset();
        _pushTabUrl('All', $.trim($('#SearchDetails').val()));
        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Search — immediate URL update ──
    $('#SearchDetails').on('input', function () {
        var stat = $('.trans-status-tabs .nav-link.active').data('status') || 'All';
        _pushTabUrl(stat, $.trim($(this).val()));
    });

    // ── Search — debounced AJAX ──
    $('#SearchDetails').on('input', inputDelay(function () {
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        if (_inGroupsMode) {
            delete _grpFilter['SearchAllData'];
            if (val.length >= 3) _grpFilter['SearchAllData'] = val;
            if (val.length === 0 || val.length >= 3) { _grpReload(1); }
            return;
        }
        delete Filter['SearchAllData'];
        if (val.length >= 3) Filter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) { PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter); }
    }, 1500));

    $('#clearSearch').on('click', function () {
        $('#SearchDetails').val('');
        $(this).addClass('d-none');
        var stat = $('.trans-status-tabs .nav-link.active').data('status') || 'All';
        _pushTabUrl(stat, '');
        if (_inGroupsMode) {
            delete _grpFilter['SearchAllData'];
            _grpReload(1);
            return;
        }
        delete Filter['SearchAllData'];
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Row checkbox ──
    $(document).on('change', ModuleRow, function () {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
    });
    $(document).on('click', ModuleRow, function () {
        onClickOfCheckbox($(this), ModuleTable, ModuleHeader, ModuleRow);
        MultipleDeleteOption();
    });

    // ── Delete single ──
    $(document).on('click', '.DeleteCustomer', function (e) {
        e.preventDefault();
        var id = $(this).data('customeruid');
        if (!id) return;
        Swal.fire({
            title: 'Delete this customer?',
            text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete',
        }).then(function (r) { if (r.isConfirmed) deleteCustomer(id); });
    });

    // ── Delete bulk ──
    $('#btnDelete').on('click', function (e) {
        e.preventDefault();
        if (!_custSelectAllMode && !SelectedUIDs.length) return;
        if (_inGroupsMode) {
            var grpCount = SelectedUIDs.length;
            Swal.fire({
                title: 'Delete ' + grpCount + ' group(s)?',
                text : 'Members will be unlinked. This cannot be undone.',
                icon : 'warning', showCancelButton: true,
                confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete',
            }).then(function (r) { if (r.isConfirmed) _deleteMultipleGroups(); });
            return;
        }
        var deleteCount = _custSelectAllMode ? _custTotalRecords : SelectedUIDs.length;
        var titleText   = _custSelectAllMode
            ? 'Delete ALL ' + deleteCount + ' customers?'
            : 'Delete ' + deleteCount + ' customer(s)?';
        Swal.fire({
            title: titleText,
            text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete all',
        }).then(function (r) { if (r.isConfirmed) deleteMultipleCustomers(); });
    });

    function _deleteMultipleGroups() {
        var uids = SelectedUIDs.slice();
        var idx  = 0;
        var successCount = 0;
        var failCount    = 0;
        ajaxLoading(0);
        function _next() {
            if (idx >= uids.length) {
                ajaxLoading(1);
                SelectedUIDs = [];
                $('.grpHeaderCheck').prop('checked', false).prop('indeterminate', false);
                $('#GroupsTable tbody .grpCheck').prop('checked', false).closest('tr').removeClass('row-sel');
                MultipleDeleteOption();
                if (failCount > 0) {
                    showToastNotification(failCount + ' group(s) could not be deleted.', 'error');
                } else {
                    showToastNotification(successCount + ' group(s) deleted successfully.', 'success');
                }
                _grpReload(_grpPageNo);
                return;
            }
            var uid = uids[idx++];
            $.ajax({
                url   : '/customers/deleteGroup',
                method: 'POST',
                data  : { GroupUID: uid, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { failCount++; } else { successCount++; }
                    _next();
                },
                error: function () { failCount++; _next(); }
            });
        }
        _next();
    }

    // ── Name sort ──
    $(document).on('click', '.cust-name-sortable', function (e) {
        e.preventDefault();
        nameSortState = (nameSortState + 1) % 3;
        // reset other sort columns
        if (nameSortState !== 0) {
            areaSortState = 0; balSortState = 0;
            delete Filter['AreaSorting']; delete Filter['BalanceSorting'];
            $('.cust-area-sortable .sort-icon, .cust-bal-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.cust-area-sortable').attr('data-bs-title', 'Click for ascending order');
            $('.cust-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (nameSortState === 1) {
            icon.addClass('bx-sort-up text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['NameSorting'] = 1;
        } else if (nameSortState === 2) {
            icon.addClass('bx-sort-down text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['NameSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['NameSorting'];
        }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Balance sort ──
    $(document).on('click', '.cust-bal-sortable', function (e) {
        e.preventDefault();
        balSortState = (balSortState + 1) % 3;
        // reset other sort columns
        if (balSortState !== 0) {
            nameSortState = 0; areaSortState = 0;
            delete Filter['NameSorting']; delete Filter['AreaSorting'];
            $('.cust-name-sortable .sort-icon, .cust-area-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.cust-name-sortable').attr('data-bs-title', 'Click for ascending order');
            $('.cust-area-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (balSortState === 1) {
            icon.addClass('bx-sort-up text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['BalanceSorting'] = 1;
        } else if (balSortState === 2) {
            icon.addClass('bx-sort-down text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['BalanceSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['BalanceSorting'];
        }
        var _tt2 = bootstrap.Tooltip.getInstance(this); if (_tt2) _tt2.dispose(); new bootstrap.Tooltip(this);
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Area sort ──
    $(document).on('click', '.cust-area-sortable', function (e) {
        e.preventDefault();
        areaSortState = (areaSortState + 1) % 3;
        // reset other sort columns
        if (areaSortState !== 0) {
            nameSortState = 0; balSortState = 0;
            delete Filter['NameSorting']; delete Filter['BalanceSorting'];
            $('.cust-name-sortable .sort-icon, .cust-bal-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.cust-name-sortable').attr('data-bs-title', 'Click for ascending order');
            $('.cust-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (areaSortState === 1) {
            icon.addClass('bx-sort-up text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['AreaSorting'] = 1;
        } else if (areaSortState === 2) {
            icon.addClass('bx-sort-down text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['AreaSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['AreaSorting'];
        }
        var _tt3 = bootstrap.Tooltip.getInstance(this); if (_tt3) _tt3.dispose(); new bootstrap.Tooltip(this);
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Tag filter ──
    <?php if (!empty($Tags)): ?>
    var custTagFilter = new TransColFilter({
        boxId       : 'custTagFilterBox',
        triggerId   : 'custTagFilterBtn',
        filterKey   : 'Tags',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = custTagFilter.getState();
            if (state.Tags && state.Tags.length) Filter.Tags = state.Tags;
            else delete Filter.Tags;
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });
    <?php endif; ?>

    // ── Customer Type filter ──
    var custTypeFilter = new TransColFilter({
        boxId       : 'custTypeFilterBox',
        triggerId   : 'custTypeFilterBtn',
        filterKey   : 'CustomerTypeUIDs',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = custTypeFilter.getState();
            if (state.CustomerTypeUIDs && state.CustomerTypeUIDs.length) Filter.CustomerTypeUIDs = state.CustomerTypeUIDs;
            else delete Filter.CustomerTypeUIDs;
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });

    // Lazy-load customer types into filter box on first click (Upstash → AJAX)
    var _custTypeFilterPromise = null;
    $(document).on('click', '#custTypeFilterBtn', function () {
        if (_custTypeFilterPromise) return;
        _custTypeFilterPromise = loadCachedFilterData('customer-types', '/customers/getCustomerTypes').then(function (types) {
            custTypeFilter.setItems(
                types.map(function (type) {
                    return {
                        value: type.CustomerTypeUID,
                        label: type.TypeName
                    };
                })
            );
        }).catch(function () {
            // Allow retry on next click
            _custTypeFilterPromise = null;
        });
    });

    // ── Status toggle ──
    $(document).on('click', '.cust-status-toggle', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var uid = $btn.data('uid');
        var newStatus = $btn.data('newstatus');
        var label = newStatus == 1 ? 'Active' : 'In-Active';
        Swal.fire({
            title: 'Change status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            toggleCustomerStatus(uid, newStatus);
        });
    });

    // ── User filter ──
    if (CustShowUserFilter) {
        var custUserFilter = new TransColFilter({
            boxId       : 'custUserFilterBox',
            triggerId   : 'custUserFilterBtn',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () {
                var state = custUserFilter.getState();
                if (state.UpdatedByUIDs && state.UpdatedByUIDs.length) Filter.UpdatedByUIDs = state.UpdatedByUIDs;
                else delete Filter.UpdatedByUIDs;
                PageNo = 0;
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        });
    }

    // ── Status filter ──
    var custStatusFilter = new TransColFilter({
        boxId      : 'custStatusFilterBox',
        triggerId  : 'custStatusFilterBtn',
        filterKey  : 'IsActive',
        activeClass: 'has-filter',
        onApply    : function () {
            var state = custStatusFilter.getState();
            delete Filter['IsActive'];
            if (state.IsActive && state.IsActive.length === 1) {
                Filter['IsActive'] = parseInt(state.IsActive[0], 10);
            }
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });

    // ══════════════════════════════════════════════════════════════
    // GROUPS TAB
    // ══════════════════════════════════════════════════════════════

    // ── Groups tab click ──
    $(document).on('click', '.grp-view-tab', function (e) {
        e.preventDefault();
        if (_inGroupsMode) return;
        _inGroupsMode = true;

        // Reset all customer filters before entering groups mode
        custTypeFilter.reset();
        custStatusFilter.reset();
        <?php if (!empty($Tags)): ?>if (typeof custTagFilter !== 'undefined') custTagFilter.reset();<?php endif; ?>
        if (CustShowUserFilter && typeof custUserFilter !== 'undefined') custUserFilter.reset();
        delete Filter['CustomerTypeUIDs'];
        delete Filter['Tags'];
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        delete Filter['UpdatedByUIDs'];

        // Clear search
        $('#SearchDetails').val('').attr('placeholder', 'Group name, code, type...');
        $('#clearSearch').addClass('d-none');
        delete Filter['SearchAllData'];
        delete _grpFilter['SearchAllData'];

        // Clear customer selections before leaving the All tab
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false);
        _custClearSelectAll();

        $('.cust-tab').removeClass('active');
        $('.grp-view-tab').addClass('active');
        $('.cust-only-ctrl').addClass('d-none');
        $('.grp-only-ctrl').removeClass('d-none');
        $('#custTableSection').hide();
        $('#grpTableSection').show();
        if (_custShowStats) { $('#grpTabStats').removeClass('d-none').addClass('d-flex'); }
        $('.cust-tab .trans-tab-count').addClass('d-none');

        _pushTabUrl('Groups', '');

        _grpLoaded = true; _grpReload(1);
    });

    // ── Apply groups response data to DOM ──
    function _applyGrpData(res) {
        _grpPageNo = 1;
        $('#GroupsTableBody').html(res.RecordHtmlData);
        $('#GroupsPagination').html(res.Pagination);
        if (_custShowStats) { _updateGrpStats(res.Stats); }
        var cnt = res.TotalCount || 0;
        $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);

        // Refresh the Customer Group dropdown in the customer form so newly created/updated groups appear
        _refreshCustomerGroupDropdown();
    }

    function _refreshCustomerGroupDropdown() {
        if (typeof CustomerForm !== 'undefined' && CustomerForm.clearGroupsCache) {
            CustomerForm.clearGroupsCache();
        }
        $.get('/customers/getGroupsForDropdown', function (res) {
            if (res && !res.Error && res.Groups && res.Groups.length) {
                var $sel = $('#CM_GroupUID');
                var selected = $sel.val();
                $sel.find('option:not([value=""])').remove();
                $.each(res.Groups, function (_, g) {
                    $sel.append(new Option(g.GroupName, g.GroupUID, false, String(g.GroupUID) === String(selected)));
                });
            }
        });
    }

    // ── Groups AJAX reload ──
    function _grpReload(page) {
        _grpPageNo = page || 1;
        ajaxLoading(0);
        $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        $.ajax({
            url   : '/customers/getGroupsData/' + _grpPageNo,
            method: 'POST',
            data  : { Filter: _grpFilter, [CsrfName]: CsrfToken },
            success: function (res) {
                ajaxLoading(1);
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                $('#GroupsTableBody').html(res.RecordHtmlData);
                $('#GroupsPagination').html(res.Pagination);
                if (_custShowStats) { _updateGrpStats(res.Stats); }
                var cnt = res.TotalCount || 0;
                $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
            },
            error: function () { ajaxLoading(1); }
        });
    }

    // ── Groups stats update ──
    function _updateGrpStats(s) {
        if (!s) return;
        $('.cg-stat-total').text(s.TotalCount   || 0);
        $('.cg-stat-active').text(s.ActiveCount  || 0);
        $('.cg-stat-inactive').text(s.InactiveCount || 0);
        $('.cg-stat-members').text(s.TotalMembers || 0);
    }

    // ── Groups pagination ──
    $(document).on('click', '#GroupsPagination .pagination .page-link', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (!isNaN(pg) && pg !== _grpPageNo) _grpReload(pg);
    });

    // ── Groups header checkbox ──
    $(document).on('change', '.grpHeaderCheck', function () {
        var isChecked = $(this).is(':checked');
        $('#GroupsTable tbody .grpCheck').prop('checked', isChecked)
            .closest('tr').toggleClass('row-sel', isChecked);
        SelectedUIDs = [];
        if (isChecked) {
            $('#GroupsTable tbody .grpCheck').each(function () {
                var uid = parseInt($(this).val());
                if (uid) SelectedUIDs.push(uid);
            });
        }
        MultipleDeleteOption();
    });
    $(document).on('change', '.grpCheck', function () {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        var total   = $('#GroupsTable tbody .grpCheck').length;
        var checked = $('#GroupsTable tbody .grpCheck:checked').length;
        $('.grpHeaderCheck').prop('checked', total > 0 && checked === total)
                            .prop('indeterminate', checked > 0 && checked < total);
        var uid = parseInt($(this).val());
        if ($(this).is(':checked')) {
            if (SelectedUIDs.indexOf(uid) === -1) SelectedUIDs.push(uid);
        } else {
            SelectedUIDs = SelectedUIDs.filter(function (id) { return id !== uid; });
        }
        MultipleDeleteOption();
    });

    // ── Group status toggle ──
    $(document).on('click', '.grp-status-toggle', function (e) {
        e.preventDefault();
        var $btn      = $(this);
        var uid       = $btn.data('uid');
        var newStatus = $btn.data('newstatus');
        var label     = newStatus == 1 ? 'Active' : 'Inactive';
        Swal.fire({
            title: 'Change group status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
            ajaxLoading(0);
            $.ajax({
                url   : '/customers/toggleGroupStatus',
                method: 'POST',
                data  : { GroupUID: uid, IsActive: newStatus, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#GroupsTableBody').html(res.RecordHtmlData);
                    $('#GroupsPagination').html(res.Pagination);
                    if (_custShowStats) { _updateGrpStats(res.Stats); }
                    _refreshCustomerGroupDropdown();
                },
                error: function () { showToastNotification('Failed to update status.', 'error'); }
            });
        });
    });

    // ── Group delete ──
    $(document).on('click', '.grp-delete-btn', function (e) {
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
            $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-danger" role="status"></span></td></tr>');
            ajaxLoading(0);
            $.ajax({
                url   : '/customers/deleteGroup',
                method: 'POST',
                data  : { GroupUID: uid, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#GroupsTableBody').html(res.RecordHtmlData);
                    $('#GroupsPagination').html(res.Pagination);
                    if (_custShowStats) { _updateGrpStats(res.Stats); }
                    var cnt = res.TotalCount || 0;
                    $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                    _refreshCustomerGroupDropdown();
                },
                error: function () { showToastNotification('Delete failed.', 'error'); }
            });
        });
    });

    // ── New Group button (toolbar) ──
    $(document).on('click', '#btnNewGroup, .btn-new-group', function () {
        CustomerGroupForm.open('add', null, { onSaveSuccess: function (res) { _applyGrpData(res); } });
    });

    // ── Edit Group button (from groups list) ──
    $(document).on('click', '.grp-edit-btn', function () {
        var uid = parseInt($(this).data('uid'));
        if (!uid) return;
        CustomerGroupForm.open('edit', uid, { onSaveSuccess: function (res) { _applyGrpData(res); } });
    });

    // ── Group Type filter ──
    var GrpTypeFilter = new TransColFilter({
        boxId       : 'grpTypeFilterBox',
        triggerId   : 'grpTypeFilterBtn',
        filterKey   : 'GroupType',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = GrpTypeFilter.getState();
            if (state.GroupType && state.GroupType.length) _grpFilter.GroupType = state.GroupType;
            else delete _grpFilter.GroupType;
            _grpReload(1);
        }
    });

    // Lazy-load group types into filter box on first click (Upstash → AJAX)
    var _grpTypeFilterPromise = null;
    $(document).on('click', '#grpTypeFilterBtn', function () {
        if (_grpTypeFilterPromise) return;
        _grpTypeFilterPromise = loadCachedFilterData('customer-group-types', '/customers/getGroupTypes').then(function (types) {
            GrpTypeFilter.setItems(types.map(function (t) { return { value: t, label: t }; }));
        }).catch(function () { _grpTypeFilterPromise = null; });
    });

    // ── Customer filter for Groups tab (Upstash lazy-load via TransPartyColFilter) ──
    var grpCustomerFilter = new TransPartyColFilter({
        boxId     : 'grpCustomerFilterBox',
        triggerId : 'grpCustomerFilterBtn',
        partyType : 'customer',
        filterKey : 'CustomerUID',
        onApply   : function () {
            var state = grpCustomerFilter.getState();
            if (state.CustomerUID) _grpFilter.CustomerUID = state.CustomerUID;
            else delete _grpFilter.CustomerUID;
            _grpReload(1);
        }
    });

    initExport({ moduleUID: 201, getFilters: function () { return Filter; } });

    // ── URL tab state init ───────────────────────────────────────────────────
    if (_custInitTab === 'Groups') {
        // Groups data is server-rendered by PHP — just wire up JS state, no AJAX needed
        _inGroupsMode = true;
        _grpLoaded    = true;
        // PHP already rendered the correct d-none states for all filter/button elements
        if (_custInitSearch && _custInitSearch.length >= 3) {
            _grpFilter['SearchAllData'] = _custInitSearch;
        }
    } else if (_custInitSearch && _custInitSearch.length >= 3) {
        Filter['SearchAllData'] = _custInitSearch;
        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    }

    if (window.location.search.indexOf('action=create') !== -1) {
        $('#btnCreateCustomerHeader').trigger('click');
    }

    /**
     * Called by the Quick Create handler in default.js when the user is already
     * on this page. Returns true if the create modal was opened, false if the
     * caller should navigate instead (e.g. we're on the Groups tab).
     * @returns {boolean}
     */
    window._qcPageCreate = function () {
        if ($('#btnCreateCustomerHeader').hasClass('d-none')) return false;
        $('#btnCreateCustomerHeader').trigger('click');
        return true;
    };

});
</script>