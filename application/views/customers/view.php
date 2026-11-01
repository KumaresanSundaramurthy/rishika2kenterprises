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

                <?php $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>

                <?php if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)): ?>
                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#db2777">
                        <div class="apex-stat-icon" style="background:#fce7f3"><i class="bx bxs-group" style="color:#db2777"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Customers</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-total">0</span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-active">0</span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToCollect" data-stat-filter="ToCollect" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-arrow-to-bottom" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Collect</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-tocollect">0</span>
                                <span class="apex-stat-amount"><?php echo $cur; ?> 0</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToPay" data-stat-filter="ToPay" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-arrow-from-bottom" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Pay</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-topay">0</span>
                                <span class="apex-stat-amount"><?php echo $cur; ?> 0</span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#94a3b8;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f8fafc"><i class="bx bx-bar-chart-alt-2" style="color:#94a3b8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Combined Stats</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:var(--r2k-text-sm)"><span class="cust-stat-month fw-bold">0</span> Month</span>
                                <span style="font-size:var(--r2k-text-sm)"><span class="cust-stat-lastmonth fw-bold">0</span> Last</span>
                                <span style="font-size:var(--r2k-text-sm)"><span class="cust-stat-fy fw-bold">0</span> FY</span>
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
                            <a href="javascript:void(0);" id="custTagFilterBtn" class="apex-filter-btn cust-only-ctrl d-none" title="Filter by Tag">
                                <i class="bx bx-purchase-tag"></i>Tags
                            </a>
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
                            <a href="javascript:void(0);" class="apex-filter-btn cust-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" id="btnSyncCustomersCache" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('sync_cache', 'Sync Cache'); ?>"><i class="bx bx-planet"></i></a>
                            <!-- Group-only sync -->
                            <a href="javascript:void(0);" class="apex-filter-btn grp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" id="btnSyncCustomerGroupsCache" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('sync_groups_cache', 'Sync Groups Cache'); ?>"><i class="bx bx-planet"></i></a>
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
                            <div class="grp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>">
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
                                <li class="nav-item"><a class="nav-link<?php echo ($InitTab ?? 'All') === 'All' ? ' active' : ''; ?> cust-tab" data-status="All" data-url-tab="all" href="javascript:void(0);"><?php echo t('tab_all_customers', 'All Customers'); ?> <span class="trans-tab-count<?php echo (!$initIsGroups && ($InitTotalCount ?? 0) > 0) ? '' : ' d-none'; ?>"><?php echo (!$initIsGroups && ($InitTotalCount ?? 0) > 0) ? $InitTotalCount : ''; ?></span></a></li>
                                <li class="nav-item">
                                    <?php $grpTotal = ($InitTab ?? 'All') === 'Groups' ? (int)($GrpTotal ?? 0) : 0; ?>
                                    <a class="nav-link grp-view-tab<?php echo ($InitTab ?? 'All') === 'Groups' ? ' active' : ''; ?>" href="javascript:void(0);" id="groupsViewTab" data-status="Groups" data-url-tab="groups">
                                        Groups <span class="trans-tab-count<?php echo $grpTotal > 0 ? '' : ' d-none'; ?>" id="grpTabCount"><?php echo $grpTotal > 0 ? $grpTotal : ''; ?></span>
                                    </a>
                                </li>
                                <?php $grpStatsVis = ($InitTab ?? 'All') === 'Groups'; ?>
                                <!-- Group stats — visible only in groups mode; populated by JS via services -->
                                <li id="grpTabStats" class="<?php echo $grpStatsVis ? 'd-flex' : 'd-none'; ?> align-items-center gap-3 ms-auto pe-2" style="font-size:.81rem;list-style:none;">
                                    <span class="text-muted"><?php echo t('lbl_total', 'Total'); ?>: <strong class="cg-stat-total text-body">—</strong></span>
                                    <span class="text-muted"><?php echo t('lbl_active', 'Active'); ?>: <strong class="cg-stat-active text-success">—</strong></span>
                                    <span class="text-muted"><?php echo t('lbl_inactive', 'Inactive'); ?>: <strong class="cg-stat-inactive text-danger">—</strong></span>
                                    <span class="text-muted"><?php echo t('col_members', 'Members'); ?>: <strong class="cg-stat-members text-body">—</strong></span>
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
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center CustomersPagination apex-pag-sticky" id="CustomersPagination">
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
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="GroupsPagination"></div>
                        </div><!-- /#grpTableSection -->

                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/modals/customer_form'); ?>
            <?php $this->load->view('common/modals/customer_group_form'); ?>
            <?php $this->load->view('common/modals/customer_profile_modal'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>

    </div>
</div>

<!-- Filter panels (body-level to avoid overflow clipping) -->
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'custTagFilterBox',
        'triggerId'         => 'custTagFilterBtn',
        'checkClass'        => 'cust-tag-chk',
        'title'             => 'Tag Filter',
        'icon'              => 'bx-purchase-tag',
        'searchPlaceholder' => 'Search tags...',
        'items'             => [], // populated by JS via services
    ],
]); ?>
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
    var _dec                  = 2;

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
            url     : global_services_url + 'api/customers/getGroupDetail/' + uid,
            method  : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: JwtData.Org.OrgUID },
            success: function (res) {
                ajaxLoading(1);
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
            badges += '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-70);font-family:monospace;">' + _esc(g.GroupCode) + '</span>';
        }
        badges += '<span class="badge" style="background:' + tc.bg + ';color:' + tc.c + ';font-size:var(--r2k-text-70);font-weight:600;">' + _esc(g.GroupType || '') + '</span>';
        badges += '<span class="badge ' + (g.IsActive ? 'bg-label-success' : 'bg-label-danger') + '" style="font-size:var(--r2k-text-xs);">' + (g.IsActive ? 'Active' : 'Inactive') + '</span>';
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
                        '<div style="font-size:var(--r2k-text-85);font-weight:600;color:#7c3aed;">' + _esc(g.ContactPerson || '—') + '</div>' +
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
                    '<td class="text-muted" style="font-size:var(--r2k-text-82);">' + _esc(m.MobileNumber || '—') + '</td>' +
                    '<td class="text-end" style="font-weight:600;color:' + balColor + ';">' +
                        _currency + ' ' + bal.toFixed(_dec) +
                        '<div class="text-muted fw-normal" style="font-size:var(--r2k-text-70);">' + (balType === 'Credit' ? 'Payable' : 'Receivable') + '</div>' +
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
            '<table class="table table-hover align-middle mb-0" style="font-size:var(--r2k-text-85);">' +
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
<script src="<?php echo _assetV('/js/common/country_picker.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/phone_cc_dropdown.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_group_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/core/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/party_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/customers/customers.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/pagecheckbox.js'); ?>"></script>
<script src="/js/common/communication.js"></script>

<script>
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
var _custShowStats  = <?php echo (int)(($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)); ?>;

$(function () {
    'use strict';

    // ── Groups mode state ──
    var _inGroupsMode   = false;
    var _grpPageNo      = 1;
    var _grpFilter      = {};
    var _grpLoaded      = false;
    var _custDataLoaded = false;  // PHP no longer pre-renders; JS always loads initial data
    var _custTagsLoaded = false;  // tags fetched from services once; no re-fetch on tab switch
    var _grpStatsLoaded = false;  // group stats fetched once; no re-fetch on tab switch

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

    // Override refresh to be mode-aware — groups tab must hit _grpReload, not getCustomersDetails
    $('.PageRefresh').off('click').on('click', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        if (_inGroupsMode) {
            _grpReload(_grpPageNo);
            return;
        }
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Initial table load — PHP no longer pre-renders; JS fetches from services ──
    if (_custInitTab !== 'Groups') {
        _custDataLoaded = true;
        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    }

    // ── Services lazy-load ────────────────────────────────────────────────────
    // Stats always load (both tabs use the same customer stats strip)
    if (_custShowStats) { loadCustomerStats(); }

    /* Tags are only relevant on the All Customers tab.
       Skip the fetch when the page lands on the Groups tab;
       the All-tab click handler will load them on first switch. */
    if (_custInitTab !== 'Groups') {
        loadCustomerTags(function (response) {
            if (response.Error || !Array.isArray(response.Tags) || !response.Tags.length) return;
            _custTagsLoaded = true;
            custTagFilter.setItems($.map(response.Tags, function (t) { return { value: t, label: t }; }));
            $('#custTagFilterBtn').removeClass('d-none');
        });
    }

    /* Group stats are only relevant when Groups tab is active.
       For the Groups-tab direct load, see the _custInitTab === 'Groups' block below.
       For tab-click loads, see the .grp-view-tab click handler. */

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
        ajaxLoading(0);
        $.ajax({
            url     : global_services_url + 'api/customers/syncCustomersCache',
            method  : 'POST',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: JwtData.Org.OrgUID },
            dataType: 'json',
            success: function (resp) {
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
            },
            complete: function() { ajaxLoading(1); }
        });
    });
    // ── Sync customer groups to Upstash cache ────────────────────────────────
    $(document).on('click', '#btnSyncCustomerGroupsCache', function () {
        var $btn = $(this);
        $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        ajaxLoading(0);
        $.ajax({
            url     : global_services_url + 'api/customers/syncCustomerGroupsCache',
            method  : 'POST',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: JwtData.Org.OrgUID },
            dataType: 'json',
            success: function (resp) {
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
            },
            complete: function() { ajaxLoading(1); }
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
            $('#grpTabCount').addClass('d-none');
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

            // If page loaded directly on ?tab=groups, customer table was empty — load now
            if (!_custDataLoaded) {
                _custDataLoaded = true;
                PageNo = 0;
                getCustomersDetails(PageNo, RowLimit, Filter);
            }

            // Tags were skipped on groups-tab direct load — fetch them now on first switch
            if (!_custTagsLoaded) {
                loadCustomerTags(function (response) {
                    if (response.Error || !Array.isArray(response.Tags) || !response.Tags.length) return;
                    _custTagsLoaded = true;
                    custTagFilter.setItems($.map(response.Tags, function (t) { return { value: t, label: t }; }));
                    $('#custTagFilterBtn').removeClass('d-none');
                });
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
                url     : global_services_url + 'api/customers/deleteGroup',
                method  : 'POST',
                dataType: 'json',
                headers : { Authorization: 'Bearer ' + JwtToken },
                data    : { orgId: JwtData.Org.OrgUID, GroupUID: uid },
                success: function (res) {
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

    // ── Tag filter — always init; items populated via services on load ──
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
        custTagFilter.reset();
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
        $('#grpTabStats').removeClass('d-none').addClass('d-flex');
        $('.cust-tab .trans-tab-count').addClass('d-none');

        _pushTabUrl('Groups', '');

        // Load group stats once; skip if already fetched
        if (!_grpStatsLoaded) {
            loadGroupStats(function (response) {
                if (!response.Error) { _grpStatsLoaded = true; _updateGrpStats(response.Data); }
            });
        }

        if (!_grpLoaded) {
            _grpLoaded = true;
            _grpReload(1);
        } else {
            // Data already loaded — just restore the count badge visibility
            var _grpCnt = parseInt($('#grpTabCount').text(), 10);
            if (_grpCnt > 0) $('#grpTabCount').removeClass('d-none');
        }
    });

    // ── Apply groups response data to DOM ──
    function _applyGrpData(res) {
        if (res && res.Message) showToastNotification(res.Message, 'success');
        _grpReload(1);
        _refreshCustomerGroupDropdown();
    }

    function _refreshCustomerGroupDropdown() {
        if (typeof CustomerForm !== 'undefined' && CustomerForm.clearGroupsCache) {
            CustomerForm.clearGroupsCache();
        }
        if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) { return; }
        UpstashService.hgetall(UpstashService.orgKey('customer-groups')).then(function (map) {
            if (!map || !Object.keys(map).length) { return; }
            var groups = Object.values(map)
                .map(function (v) { return typeof v === 'string' ? JSON.parse(v) : v; })
                .sort(function (a, b) { return (a.GroupName || '').localeCompare(b.GroupName || ''); });
            var $sel     = $('#CM_GroupUID');
            var selected = $sel.val();
            $sel.find('option:not([value=""])').remove();
            groups.forEach(function (g) {
                $sel.append(new Option(g.GroupName, g.GroupUID, false, String(g.GroupUID) === String(selected)));
            });
        }).catch(function () {});
    }

    // ── Groups AJAX reload — calls services endpoint ──
    function _grpReload(page, toastMsg) {
        _grpPageNo = page || 1;
        var serialStart = (_grpPageNo - 1) * parseInt(RowLimit, 10);
        ajaxLoading(0);
        $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        $.ajax({
            url     : global_services_url + 'api/customers/getGroupsData/' + _grpPageNo,
            method  : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : {
                orgId    : JwtData.Org.OrgUID,
                RowLimit : RowLimit,
                langCode : (JwtData.User && JwtData.User.LangCode) ? JwtData.User.LangCode : 'en',
                Filter   : JSON.stringify(_grpFilter)
            },
            success: function (res) {
                ajaxLoading(1);
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                var pagHtml = generateGroupsPagination(res.TotalCount, _grpPageNo, RowLimit);
                $('#GroupsTableBody').html(generateGroupsHtml(res.Data, serialStart));
                $('#GroupsPagination').html(pagHtml);
                if (res.Stats) _updateGrpStats(res.Stats);
                var cnt = parseInt(res.TotalCount, 10) || 0;
                $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                if (toastMsg) showToastNotification(toastMsg, 'success');
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
    $(document).on('click', '#GroupsPagination .r2k-pag-btn:not(.disabled)', function (e) {
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
                url      : global_services_url + 'api/customers/toggleGroupStatus',
                method   : 'POST',
                headers  : { Authorization: 'Bearer ' + JwtToken },
                data     : { GroupUID: uid, IsActive: newStatus, orgId: JwtData.Org.OrgUID },
                dataType : 'json',
                success: function (res) {
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#GroupsTableBody').html(res.RecordHtmlData);
                    $('#GroupsPagination').html(res.Pagination);
                    _updateGrpStats(res.Stats);
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
                url     : global_services_url + 'api/customers/deleteGroup',
                method  : 'POST',
                dataType: 'json',
                headers : { Authorization: 'Bearer ' + JwtToken },
                data    : { orgId: JwtData.Org.OrgUID, GroupUID: uid },
                success: function (res) {
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#GroupsTableBody').html(res.RecordHtmlData);
                    $('#GroupsPagination').html(res.Pagination);
                    _updateGrpStats(res.Stats);
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

    /* Customer export goes to services; group export uses the generic PHP path */
    $(document).on('click', '.r2k-export-btn[data-format]', function (e) {
        if (_inGroupsMode) return;
        e.stopImmediatePropagation();
        var format  = $(this).data('format');
        var typeMap = { print: 'Print', csv: 'CSV', excel: 'Excel', pdf: 'Pdf' };
        var svcType = typeMap[format] || format;
        var payload = {
            orgId   : JwtData.Org.OrgUID,
            langCode: (JwtData.User && JwtData.User.LangCode) ? JwtData.User.LangCode : 'en',
            Type    : svcType,
            Filter  : Filter
        };
        if (format === 'print' || format === 'pdf') {
            /* Print / Pdf — server returns JSON with HtmlData */
            $.ajax({
                url        : global_services_url + 'api/customers/exportCustomers',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                dataType   : 'json',
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    var win = window.open('', '_blank');
                    if (!win) { Swal.fire({ icon: 'warning', text: 'Pop-up blocked. Please allow pop-ups.' }); return; }
                    win.document.open(); win.document.write(resp.HtmlData); win.document.close();
                    /* Pdf: auto-trigger print dialog so user can Save as PDF */
                    if (format === 'pdf') {
                        win.onload = function () { win.print(); };
                    }
                },
                error: function () { Swal.fire({ icon: 'error', text: 'Failed to load preview. Please try again.' }); }
            });
        } else {
            /* CSV / Excel — server returns binary file; download as blob */
            var ext = format === 'excel' ? 'xlsx' : 'csv';
            $.ajax({
                url        : global_services_url + 'api/customers/exportCustomers',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                xhrFields  : { responseType: 'blob' },
                success: function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'Customers.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error: function () { Swal.fire({ icon: 'error', text: 'Export failed. Please try again.' }); }
            });
        }
    });

    initExport({
        getModuleUID: function () { return _inGroupsMode ? 204 : 201; },
        getFilters  : function () { return _inGroupsMode ? _grpFilter : Filter; }
    });

    // ── URL tab state init ───────────────────────────────────────────────────
    if (_custInitTab === 'Groups') {
        _inGroupsMode = true;
        _grpLoaded    = true;
        if (_custInitSearch && _custInitSearch.length >= 3) {
            _grpFilter['SearchAllData'] = _custInitSearch;
        }
        loadGroupStats(function (response) {
            if (!response.Error) { _grpStatsLoaded = true; _updateGrpStats(response.Data); }
        });
        _grpReload(1);
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

    // ── Customer Profile Modal ──────────────────────────────────────────────
    var _cpCurrentUID  = 0;
    var _cpTabLoaded   = {};
    var _cpOrgInfo     = null; // lazily fetched from Upstash org-info key

    /* ── Tab render helpers ─────────────────────────────────────────────────── */

    /**
     * Formats a date string (Y-m-d) into the user's display format.
     * @param {string} dateStr
     * @returns {string}
     */
    function _cpFmtDate(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        var fmt = (typeof _transListDateFormat !== 'undefined' && _transListDateFormat) ? _transListDateFormat : 'd M Y';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var dd = String(d.getUTCDate()).padStart(2, '0');
        var mm = String(d.getUTCMonth() + 1).padStart(2, '0');
        var mmm = months[d.getUTCMonth()];
        var yyyy = d.getUTCFullYear();
        return fmt.replace('d', dd).replace('M', mmm).replace('m', mm).replace('Y', yyyy);
    }

    /**
     * Formats a numeric amount with the org currency symbol.
     * @param {number} v
     * @returns {string}
     */
    function _cpFmt(v) {
        var cur = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
        return cur + ' ' + smartDecimal(parseFloat(v) || 0);
    }

    /**
     * Dispatches to the correct tab renderer.
     * @param {string} tab
     * @param {object} data
     * @returns {string}
     */
    function _renderCPTab(tab, data) {
        switch (tab) {
            case 'overview':     return _renderCPOverview(data);
            case 'transactions': return _renderCPTransactions(data);
            case 'statement':    return _renderCPStatementFull(data);
            case 'notes':        return _renderCPNotesList(data);
            default:             return '<div class="alert alert-danger m-4">Unknown tab.</div>';
        }
    }

    /**
     * Renders the Overview tab from services JSON data.
     * @param {object} d
     * @returns {string}
     */
    function _renderCPOverview(d) {
        var c         = d.Cust || d.customer || {};
        var health    = d.HealthData || d.healthData || {};
        var ageing    = d.Ageing    || d.ageing    || {};
        var profit    = d.Profitability || d.profitability || {};
        var monthly   = d.MonthlySales  || d.monthlySales  || [];
        var group     = d.GroupMembership || d.groupMembership || null;
        var ob        = d.OpeningBal   || d.openingBalance || null;
        var billing   = d.BillingAddr  || d.billingAddress  || null;
        var shipping  = d.ShippingAddr || d.shippingAddress || null;
        var cur       = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';

        var totalInvoiced  = parseFloat(d.TotalInvoiced  || d.totalInvoiced  || 0);
        var totalReceived  = parseFloat(d.TotalReceived  || d.totalReceived  || 0);
        var totalReturned  = parseFloat(d.TotalReturned  || d.totalReturned  || 0);
        var closingBalance = parseFloat(d.ClosingBalance || d.closingBalance || 0);
        var closingBalType = d.ClosingBalanceType || d.closingBalanceType || 'Debit';

        var name       = c.Name || c.name || '';
        var words      = name.trim().split(/\s+/);
        var initials   = ((words[0] || '').charAt(0) + ((words[1] || '').charAt(0))).toUpperCase();
        var mobile     = c.MobileNumber || c.mobileNumber || '';
        var ccode      = c.CountryCode  || c.countryCode  || '';
        var email      = c.EmailAddress || c.emailAddress || '';
        var fullMobile = mobile ? (ccode ? ccode + ' ' : '') + mobile : '';
        var subtitle   = fullMobile + (email ? (fullMobile ? ' · ' : '') + email : '');

        /* update modal header (.text() handles escaping — pass raw strings) */
        $('#cpAvatarInitials').text(initials || '?');
        $('#cpModalTitle').text(name);
        $('#cpModalSubtitle').text(subtitle);
        $('#cpBtnEdit').data('uid', parseInt(c.CustomerUID || c.customerUID || 0, 10));

        var tags = (c.Tags || c.tags || '').split(',').map(function(t){return t.trim();}).filter(Boolean);

        /* ── health bar ── */
        var daysSince = health.DaysSinceLastTx != null ? health.DaysSinceLastTx : (health.daysSinceLastTx != null ? health.daysSinceLastTx : null);
        var lastActiv = daysSince === null ? 'No transactions yet'
                      : daysSince === 0    ? 'Active today'
                      : daysSince === 1    ? 'Last activity yesterday'
                      : 'Last activity <strong>' + daysSince + 'd</strong> ago';
        var overdueCount   = health.OverdueCount   || health.overdueCount   || 0;
        var collectionRate = health.CollectionRate  || health.collectionRate || 0;
        var healthColor    = health.HealthColor     || health.healthColor    || 'secondary';
        var healthScore    = health.HealthScore     || health.healthScore    || 'N/A';

        var html = '<div class="p-3 p-md-4"><div class="row g-4">';

        /* ── LEFT col ── */
        html += '<div class="col-md-7">';

        /* health + last activity */
        html += '<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">';
        html += '<div class="d-flex align-items-center gap-2">';
        html += '<span class="badge bg-' + _cpEsc(healthColor) + ' px-3 py-2" style="font-size:var(--r2k-text-md);">'
             +  '<i class="bx bx-heart-circle me-1"></i>' + _cpEsc(String(healthScore)) + '</span>';
        if (overdueCount > 0) {
            html += '<span class="badge bg-label-danger" style="font-size:var(--r2k-text-sm);">'
                 +  '<i class="bx bx-error-circle me-1"></i>' + overdueCount + ' overdue</span>';
        }
        if (collectionRate > 0 && collectionRate < 100) {
            html += '<span class="text-muted" style="font-size:.74rem;">' + collectionRate + '% collected</span>';
        }
        html += '</div>';
        html += '<span class="text-muted" style="font-size:.74rem;"><i class="bx bx-time-five me-1"></i>' + lastActiv + '</span>';
        html += '</div>';

        /* contact info card */
        var contactRows = '';
        if (mobile) contactRows += _cpField('Mobile', (ccode ? '<span class="text-muted">' + _cpEsc(ccode) + '</span> ' : '') + _cpEsc(mobile)
            + '<a href="https://wa.me/' + (ccode + mobile).replace(/\D/g,'') + '?text=Hi" target="_blank" class="ms-1 text-success" title="WhatsApp"><i class="bx bxl-whatsapp"></i></a>', true);
        if (email)  contactRows += _cpField('Email',          '<div class="fw-semibold text-truncate">' + _cpEsc(email) + '</div>', true);
        if (c.CompanyName  || c.companyName)  contactRows += _cpField('Company',        _cpEsc(c.CompanyName  || c.companyName), false);
        if (c.Area         || c.area)         contactRows += _cpField('Area',            _cpEsc(c.Area         || c.area), false);
        if (c.ContactPerson|| c.contactPerson)contactRows += _cpField('Contact Person',  _cpEsc(c.ContactPerson|| c.contactPerson), false);
        if (c.DateOfBirth  || c.dateOfBirth)  contactRows += _cpField('Date of Birth',   _cpFmtDate(c.DateOfBirth || c.dateOfBirth), false);
        html += _cpCard('bx-user', 'text-primary', 'CONTACT INFORMATION', '<div class="row g-2" style="font-size:.83rem;">' + contactRows + '</div>');

        /* GST & financial card */
        var finRows = '';
        if (c.GSTIN       || c.gstin)        finRows += _cpField('GSTIN',    '<div class="fw-semibold" style="font-family:monospace;">' + _cpEsc(c.GSTIN || c.gstin) + '</div>', true);
        if (c.PANNumber   || c.panNumber)     finRows += _cpField('PAN',      '<div style="font-family:monospace;">' + _cpEsc(c.PANNumber || c.panNumber) + '</div>', true);
        var creditLimit   = parseFloat(c.CreditLimit   || c.creditLimit   || 0);
        var creditPeriod  = parseInt  (c.CreditPeriod  || c.creditPeriod  || 0);
        var discountPct   = parseFloat(c.DiscountPercent|| c.discountPercent|| 0);
        if (creditLimit  > 0) finRows += _cpField('Credit Limit',    _cpFmt(creditLimit), false);
        if (creditPeriod > 0) finRows += _cpField('Credit Period',   creditPeriod + ' days', false);
        if (discountPct  > 0) finRows += _cpField('Default Discount', smartDecimal(discountPct) + '%', false);
        var obVal  = parseFloat((ob && (ob.OpeningBalance || ob.openingBalance)) || 0);
        var obType = (ob && (ob.OpeningBalType || ob.openingBalType)) || 'Debit';
        if (obVal > 0) {
            var obCls = obType === 'Debit' ? 'text-success' : 'text-danger';
            var obLbl = obType === 'Debit' ? 'To Collect' : 'To Pay';
            finRows += _cpField('Opening Balance',
                '<div class="' + obCls + '">' + _cpFmt(obVal) + ' <span class="badge bg-label-secondary ms-1" style="font-size:var(--r2k-text-65);">' + obLbl + '</span></div>', true);
        }
        html += _cpCard('bx-buildings', 'text-warning', 'GST & FINANCIAL', '<div class="row g-2" style="font-size:.83rem;">' + finRows + '</div>');

        /* address card */
        if (billing || shipping) {
            var addrRows = '';
            if (billing) {
                var bp = [billing.Line1 || billing.line1, billing.Line2 || billing.line2,
                          billing.CityText || billing.cityText, billing.StateText || billing.stateText,
                          billing.Pincode || billing.pincode].filter(Boolean).join(', ');
                addrRows += _cpField('Billing Address', _cpEsc(bp), false);
            }
            if (shipping) {
                var sp = [shipping.Line1 || shipping.line1, shipping.Line2 || shipping.line2,
                          shipping.CityText || shipping.cityText, shipping.StateText || shipping.stateText,
                          shipping.Pincode || shipping.pincode].filter(Boolean).join(', ');
                addrRows += _cpField('Shipping Address', _cpEsc(sp), false);
            }
            html += _cpCard('bx-map', 'text-danger', 'ADDRESS', '<div class="row g-3" style="font-size:var(--r2k-text-82);">' + addrRows + '</div>');
        }

        /* tags / type / group */
        var badges = '';
        if (c.CustomerTypeName || c.customerTypeName) badges += '<span class="badge bg-label-primary"><i class="bx bx-user-pin me-1"></i>' + _cpEsc(c.CustomerTypeName || c.customerTypeName) + '</span>';
        if (group && (group.GroupName || group.groupName)) badges += '<span class="badge bg-label-info"><i class="bx bxs-group me-1"></i>' + _cpEsc(group.GroupName || group.groupName) + '</span>';
        tags.forEach(function(t){ badges += '<span class="badge bg-label-secondary">' + _cpEsc(t) + '</span>'; });
        if (badges) html += '<div class="d-flex flex-wrap gap-2 align-items-center">' + badges + '</div>';

        /* notes blurb */
        if (c.Notes || c.notes) {
            html += '<div class="mt-3 p-3 rounded" style="background:#fffbe6;border:1px solid #ffe58f;font-size:var(--r2k-text-82);">'
                 +  '<i class="bx bx-info-circle text-warning me-1"></i>' + _cpEsc(c.Notes || c.notes) + '</div>';
        }
        html += '</div>'; /* end left col */

        /* ── RIGHT col ── */
        html += '<div class="col-md-5">';

        /* KPI 2×2 grid */
        var balClass = closingBalance === 0 ? 'secondary' : (closingBalType === 'Debit' ? 'success' : 'danger');
        var balLabel = closingBalance === 0 ? 'No Balance'  : (closingBalType === 'Debit' ? 'To Collect' : 'To Pay');
        var kpiCards = [
            { label:'Outstanding',    value:_cpFmt(closingBalance),  icon:'bx-wallet',         color:balClass, sub:balLabel },
            { label:'Total Invoiced', value:_cpFmt(totalInvoiced),   icon:'bx-receipt',        color:'primary', sub:'Lifetime' },
            { label:'Total Received', value:_cpFmt(totalReceived),   icon:'bx-down-arrow-alt', color:'success', sub:'Payments in' },
            { label:'Total Returns',  value:_cpFmt(totalReturned),   icon:'bx-undo',           color:'warning', sub:'Sales returns' },
        ];
        html += '<div class="row g-2 mb-3">';
        kpiCards.forEach(function(k){
            html += '<div class="col-6"><div class="card border shadow-none h-100"><div class="card-body p-3">'
                 +  '<div class="d-flex align-items-center gap-2 mb-1">'
                 +  '<div class="avatar avatar-xs"><span class="avatar-initial rounded bg-label-' + k.color + '"><i class="bx ' + k.icon + '" style="font-size:var(--r2k-text-90);"></i></span></div>'
                 +  '<span class="text-muted" style="font-size:var(--r2k-text-sm);">' + k.label + '</span></div>'
                 +  '<div class="fw-bold text-' + k.color + '" style="font-size:.92rem;">' + k.value + '</div>'
                 +  '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + k.sub + '</div>'
                 +  '</div></div></div>';
        });
        html += '</div>';

        /* profitability */
        var pRev = parseFloat(profit.Revenue || profit.revenue || 0);
        if (pRev > 0) {
            var pCogs   = parseFloat(profit.COGS   || profit.cogs   || 0);
            var pProfit = parseFloat(profit.Profit || profit.profit || 0);
            var pMargin = parseFloat(profit.Margin || profit.margin || 0);
            var mrgColor = pMargin >= 20 ? 'success' : (pMargin >= 10 ? 'warning' : 'danger');
            html += '<div class="card border shadow-none mb-3">'
                 +  '<div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
                 +  '<i class="bx bx-trending-up text-success"></i>'
                 +  '<span class="fw-semibold" style="font-size:var(--r2k-text-82);">PROFITABILITY</span>'
                 +  '<span class="badge bg-label-' + mrgColor + ' ms-auto" style="font-size:var(--r2k-text-70);">' + pMargin + '% margin</span>'
                 +  '</div><div class="card-body p-3"><div class="row g-2 text-center" style="font-size:var(--r2k-text-md);">'
                 +  '<div class="col-4"><div class="text-muted mb-1" style="font-size:var(--r2k-text-xs);">Revenue</div><div class="fw-bold text-primary" style="font-size:var(--r2k-text-82);">' + _cpFmt(pRev) + '</div></div>'
                 +  '<div class="col-4"><div class="text-muted mb-1" style="font-size:var(--r2k-text-xs);">Est. COGS</div><div class="fw-bold text-danger" style="font-size:var(--r2k-text-82);">' + _cpFmt(pCogs) + '</div></div>'
                 +  '<div class="col-4"><div class="text-muted mb-1" style="font-size:var(--r2k-text-xs);">Gross Profit</div><div class="fw-bold text-' + (pProfit >= 0 ? 'success' : 'danger') + '" style="font-size:var(--r2k-text-82);">' + _cpFmt(Math.abs(pProfit)) + '</div></div>'
                 +  '</div></div></div>';
        }

        /* monthly sales bar chart */
        html += '<div class="card border shadow-none"><div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
             +  '<i class="bx bx-bar-chart-alt-2 text-primary"></i>'
             +  '<span class="fw-semibold" style="font-size:var(--r2k-text-82);">SALES — LAST 6 MONTHS</span>'
             +  '</div><div class="card-body p-3">';
        if (!monthly.length) {
            html += '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:var(--r2k-text-82);">'
                 +  '<i class="bx bx-bar-chart-alt fs-3 mb-1"></i>No invoice data in this period</div>';
        } else {
            var maxVal = Math.max.apply(null, monthly.map(function(r){ return parseFloat(r.Total || r.total || 0); })) || 1;
            html += '<div class="d-flex align-items-flex-end gap-1 justify-content-between" style="height:100px;">';
            monthly.forEach(function(row) {
                var tot = parseFloat(row.Total || row.total || 0);
                var pct = Math.min(100, Math.round((tot / maxVal) * 100));
                var lbl = (row.MonthLabel || row.monthLabel || '').substring(0, 3);
                html += '<div class="d-flex flex-column align-items-center gap-1 flex-grow-1">'
                     +  '<div class="text-muted" style="font-size:var(--r2k-text-65);" title="' + cur + ' ' + smartDecimal(tot) + '">'
                     +  cur + (tot / 1000).toFixed(1) + 'k</div>'
                     +  '<div class="w-100 rounded-top" style="height:' + Math.max(4, pct) + 'px;background:#696cff;opacity:.8;min-width:20px;"></div>'
                     +  '<div class="text-muted" style="font-size:var(--r2k-text-xs);">' + _cpEsc(lbl) + '</div>'
                     +  '</div>';
            });
            html += '</div>';
        }
        html += '</div></div>';

        /* ageing buckets */
        var b0  = parseFloat(ageing.Bucket_0_30  || ageing.bucket_0_30  || 0);
        var b31 = parseFloat(ageing.Bucket_31_60 || ageing.bucket_31_60 || 0);
        var b61 = parseFloat(ageing.Bucket_61_90 || ageing.bucket_61_90 || 0);
        var b90 = parseFloat(ageing.Bucket_90Plus|| ageing.bucket_90Plus|| 0);
        if (b0 + b31 + b61 + b90 > 0) {
            var ageingTotal = b0 + b31 + b61 + b90;
            var buckets = [
                {label:'0–30 days',  val:b0,  color:'success'},
                {label:'31–60 days', val:b31, color:'warning'},
                {label:'61–90 days', val:b61, color:'warning'},
                {label:'90+ days',   val:b90, color:'danger'},
            ];
            html += '<div class="card border shadow-none mt-3">'
                 +  '<div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
                 +  '<i class="bx bx-time text-danger"></i>'
                 +  '<span class="fw-semibold" style="font-size:var(--r2k-text-82);">OUTSTANDING AGEING</span>'
                 +  '<span class="text-muted ms-auto" style="font-size:var(--r2k-text-70);">Total: ' + _cpFmt(ageingTotal) + '</span>'
                 +  '</div><div class="card-body p-2"><div class="row g-1 text-center" style="font-size:.74rem;">';
            buckets.forEach(function(b){
                html += '<div class="col-3"><div class="p-2 rounded bg-label-' + b.color + '">'
                     +  '<div class="text-muted mb-1" style="font-size:var(--r2k-text-65);">' + b.label + '</div>'
                     +  '<div class="fw-bold" style="font-size:var(--r2k-text-75);">' + (b.val > 0 ? _cpFmt(b.val) : '—') + '</div>'
                     +  '</div></div>';
            });
            html += '</div></div></div>';
        }

        html += '</div>'; /* end right col */
        html += '</div></div>'; /* end row + wrapper */
        return html;
    }

    /**
     * Renders the Transactions tab from services JSON data.
     * @param {object} d
     * @returns {string}
     */
    function _renderCPTransactions(d) {
        var txData = d.TxData || d.txData || [];
        var custUID = d.CustomerUID || d.customerUID || _cpCurrentUID;
        var moduleRoutes = {103:'/invoices',102:'/salesorders',101:'/quotations',112:'/deliverychallans',106:'/salesreturns',113:'/proformainvoices'};
        var moduleCreate  = {103:'/invoices/create',102:'/salesorders/create',101:'/quotations/create',112:'/deliverychallans/create',106:'/salesreturns/create',113:'/proformainvoices/create'};
        var statusColors  = {Draft:'secondary',Confirmed:'primary',Completed:'success',Cancelled:'danger',Rejected:'danger',Paid:'success',Partial:'warning'};

        var html = '<div class="p-3 p-md-4"><div class="accordion" id="cpTxAccordion">';
        txData.forEach(function(section) {
            var mod  = section.module || section.Module || {};
            var rows = section.rows   || section.Rows   || [];
            var mUID = parseInt(mod.uid || mod.Uid || 0);
            var cId  = 'cpTxSection_' + mUID;
            var isOpen = rows.length > 0;
            var createUrl = moduleCreate[mUID] || '';
            html += '<div class="accordion-item border mb-2 rounded overflow-hidden">';
            html += '<div class="cp-tx-acc-header"><h2 class="accordion-header flex-grow-1">';
            html += '<button class="accordion-button ' + (isOpen ? '' : 'collapsed') + ' py-2 px-3" type="button"'
                 +  ' data-bs-toggle="collapse" data-bs-target="#' + cId + '"'
                 +  ' style="font-size:var(--r2k-text-85);font-weight:600;">'
                 +  '<i class="bx ' + _cpEsc(mod.icon || mod.Icon || '') + ' me-2 text-primary"></i>'
                 +  _cpEsc(mod.label || mod.Label || '')
                 +  (rows.length ? '<span class="badge bg-label-primary ms-2">' + rows.length + '</span>' : '')
                 +  '<span class="ms-auto me-2 text-muted fw-normal" style="font-size:var(--r2k-text-sm);">'
                 +  (rows.length ? 'Recent ' + rows.length : 'No records') + '</span>'
                 +  '</button></h2>';
            if (createUrl) {
                html += '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary cp-tx-new-btn" data-route="' + createUrl + '">'
                     +  '<i class="bx bx-plus me-1"></i>New</a>';
            }
            html += '</div>';
            html += '<div id="' + cId + '" class="accordion-collapse collapse ' + (isOpen ? 'show' : '') + '">';
            html += '<div class="accordion-body p-0">';
            if (!rows.length) {
                html += '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:var(--r2k-text-82);">'
                     +  '<i class="bx bx-file fs-2 mb-1 opacity-50"></i>No ' + _cpEsc((mod.label || mod.Label || '').toLowerCase()) + ' found for this customer.</div>';
            } else {
                html += '<div class="table-responsive"><table class="table table-sm table-hover mb-0" style="font-size:var(--r2k-text-82);">'
                     +  '<thead class="table-light"><tr><th class="px-3">Ref No</th><th>Date</th><th class="text-end">Amount</th><th class="text-end">Balance</th><th>Status</th></tr></thead><tbody>';
                rows.forEach(function(r) {
                    var status   = r.DocStatus || r.docStatus || '—';
                    var badgeCls = statusColors[status] || 'secondary';
                    var bal      = parseFloat(r.BalanceAmount || r.balanceAmount || r.NetAmount || r.netAmount || 0);
                    html += '<tr>'
                         +  '<td class="px-3 fw-semibold">' + _cpEsc(r.TransNo || r.transNo || '—') + '</td>'
                         +  '<td>' + _cpFmtDate(r.DocDate || r.docDate) + '</td>'
                         +  '<td class="text-end">' + _cpFmt(parseFloat(r.NetAmount || r.netAmount || 0)) + '</td>'
                         +  '<td class="text-end ' + (bal > 0 ? 'text-danger' : 'text-success') + '">' + (bal > 0 ? _cpFmt(bal) : '—') + '</td>'
                         +  '<td><span class="badge bg-label-' + badgeCls + '">' + _cpEsc(status) + '</span></td>'
                         +  '</tr>';
                });
                html += '</tbody></table></div>';
                if (moduleRoutes[mUID]) {
                    html += '<div class="px-3 py-2 border-top text-end">'
                         +  '<a href="' + moduleRoutes[mUID] + '?party=' + custUID + '" class="text-primary" style="font-size:var(--r2k-text-78);" target="_blank">'
                         +  'View all ' + _cpEsc((mod.label || mod.Label || '').toLowerCase()) + ' <i class="bx bx-link-external ms-1"></i></a></div>';
                }
            }
            html += '</div></div></div>';
        });
        html += '</div></div>';
        return html;
    }

    /**
     * Renders the full Statement tab (controls + placeholder body; org-info loaded async in _initCPStatement).
     * @param {object} d
     * @returns {string}
     */
    function _renderCPStatementFull(d) {
        var cur = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
        var html = '<div class="px-3 pt-3 d-flex align-items-end gap-2 flex-wrap" id="cpStmtControls">'
                 + '<div><label class="form-label mb-1 cp-stmt-label">From Date</label>'
                 + '<input type="text" id="cpStmtFrom" class="form-control form-control-sm cp-stmt-input" placeholder="Select date" readonly></div>'
                 + '<div><label class="form-label mb-1 cp-stmt-label">To Date</label>'
                 + '<input type="text" id="cpStmtTo" class="form-control form-control-sm cp-stmt-input" placeholder="Select date" readonly></div>'
                 + '<button type="button" class="btn btn-sm btn-primary cp-stmt-btn" id="cpStmtApplyBtn"><i class="bx bx-filter-alt me-1"></i>Apply</button>'
                 + '<button type="button" class="btn btn-sm btn-outline-secondary ms-auto cp-stmt-btn" id="cpStmtPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>'
                 + '</div>';
        html += '<div class="p-3" id="cpStmtPrintArea">' + _renderCPStatementBody(d, cur, d.OrgInfo || d.orgInfo || null) + '</div>';
        return html;
    }

    /**
     * Renders just the statement body rows (used on Apply re-fetch).
     * @param {object} d
     * @param {string|null} [cur]
     * @param {object|null} [orgInfo]  Org data from Upstash org-info key
     * @returns {string}
     */
    function _renderCPStatementBody(d, cur, orgInfo) {
        cur = cur || ((JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹');
        var cust       = d.Cust || d.customer || {};
        var org        = orgInfo || _cpOrgInfo || d.OrgInfo || d.orgInfo || {};
        var statement  = d.Statement || d.statement || [];
        var ob         = d.OpeningBal || d.openingBalance || null;
        var fromDate   = d.FromDate   || d.fromDate   || '';
        var toDate     = d.ToDate     || d.toDate     || '';

        var obVal    = parseFloat((ob && (ob.OpeningBalance || ob.openingBalance)) || 0);
        var obType   = (ob && (ob.OpeningBalType || ob.openingBalType)) || 'Debit';
        var obSigned = obType === 'Debit' ? obVal : -obVal;

        var running = obSigned;
        var rows = statement.map(function(row) {
            var debit  = parseFloat(row.Debit  || row.debit  || 0);
            var credit = parseFloat(row.Credit || row.credit || 0);
            running += debit - credit;
            return { debit:debit, credit:credit, balance:running, txDate:row.TxDate||row.txDate, txType:row.TxType||row.txType||'—', refNo:row.RefNo||row.refNo||'—' };
        });
        var closing = running;

        var orgName = _cpEsc(org.BrandName || org.brandName || org.Name || org.name || '');
        var orgAddr = [org.Line1||org.line1, org.Line2||org.line2, org.CityText||org.cityText,
                       (org.StateText||org.stateText) ? (org.StateText||org.stateText) + ((org.Pincode||org.pincode) ? ' - '+(org.Pincode||org.pincode) : '') : (org.Pincode||org.pincode)]
                      .filter(Boolean).join(', ') || org.addr || '';
        var orgGSTIN = org.GSTIN || org.gstin || '';
        var orgLogo  = org.Logo  || org.logo  || org.logo_url || '';

        var custName   = _cpEsc(cust.Name   || cust.name   || '');
        var custMobile = _cpEsc(((cust.CountryCode||cust.countryCode||'') ? (cust.CountryCode||cust.countryCode)+' ' : '') + (cust.MobileNumber||cust.mobileNumber||''));
        var custEmail  = _cpEsc(cust.EmailAddress || cust.emailAddress || '');
        var custArea   = _cpEsc(cust.Area         || cust.area         || '');

        var typeBadges = {Invoice:'primary', Receipt:'success', Return:'warning'};

        var html = '<div class="stmt-header">';
        html += '<div class="stmt-header-left">';
        if (orgLogo) html += '<img src="' + orgLogo + '" class="stmt-logo" alt="Logo">';
        if (orgName) html += '<div class="stmt-org-name">' + orgName + '</div>';
        if (orgAddr) html += '<div class="stmt-org-addr">' + _cpEsc(orgAddr) + '</div>';
        if (orgGSTIN) html += '<div class="stmt-org-gstin">GSTIN: ' + _cpEsc(orgGSTIN) + '</div>';
        html += '</div><div class="stmt-header-right"><div class="stmt-title">Statement of Accounts</div>'
             +  '<div class="stmt-period">' + _cpFmtDate(fromDate) + ' &ndash; ' + _cpFmtDate(toDate) + '</div></div></div>';

        html += '<hr class="stmt-divider">';
        html += '<div class="stmt-bill-to"><div class="stmt-bill-label">Bill To</div>';
        if (custName)   html += '<div class="stmt-cust-name">'  + custName   + '</div>';
        if (custArea)   html += '<div class="stmt-cust-meta">'  + custArea   + '</div>';
        if (custMobile) html += '<div class="stmt-cust-meta">'  + custMobile + '</div>';
        if (custEmail)  html += '<div class="stmt-cust-meta">'  + custEmail  + '</div>';
        html += '</div>';

        html += '<div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0 cp-stmt-table">'
             +  '<thead class="table-light"><tr>'
             +  '<th class="px-2">Date</th><th>Type</th><th>Ref No</th>'
             +  '<th class="text-end">Debit (' + cur + ')</th><th class="text-end">Credit (' + cur + ')</th><th class="text-end">Balance (' + cur + ')</th>'
             +  '</tr></thead><tbody>';

        html += '<tr class="table-light"><td colspan="5" class="px-2 fw-semibold">Opening Balance</td>'
             +  '<td class="text-end fw-semibold ' + (obSigned >= 0 ? 'text-success' : 'text-danger') + '">'
             +  smartDecimal(Math.abs(obSigned)) + ' <span class="badge bg-label-secondary ms-1 cp-stmt-badge-dr">' + (obSigned >= 0 ? 'Dr' : 'Cr') + '</span></td></tr>';

        if (!rows.length) {
            html += '<tr><td colspan="6" class="text-center text-muted py-4">No transactions in this period.</td></tr>';
        } else {
            rows.forEach(function(r) {
                var badgeCls = typeBadges[r.txType] || 'secondary';
                html += '<tr>'
                     +  '<td class="px-2">' + _cpFmtDate(r.txDate) + '</td>'
                     +  '<td><span class="badge bg-label-' + badgeCls + '">' + _cpEsc(r.txType) + '</span></td>'
                     +  '<td class="fw-semibold">' + _cpEsc(r.refNo) + '</td>'
                     +  '<td class="text-end ' + (r.debit  > 0 ? 'text-danger' : 'text-muted') + '">' + (r.debit  > 0 ? smartDecimal(r.debit)  : '—') + '</td>'
                     +  '<td class="text-end ' + (r.credit > 0 ? 'text-success': 'text-muted') + '">' + (r.credit > 0 ? smartDecimal(r.credit) : '—') + '</td>'
                     +  '<td class="text-end fw-semibold ' + (r.balance >= 0 ? 'text-success' : 'text-danger') + '">'
                     +  smartDecimal(Math.abs(r.balance)) + ' <span class="text-muted cp-stmt-badge-bal">' + (r.balance >= 0 ? 'Dr' : 'Cr') + '</span></td>'
                     +  '</tr>';
            });
        }
        html += '<tr class="table-active fw-bold"><td colspan="5" class="px-2">Closing Balance</td>'
             +  '<td class="text-end ' + (closing >= 0 ? 'text-success' : 'text-danger') + '">'
             +  smartDecimal(Math.abs(closing))
             +  ' <span class="badge ms-1 cp-stmt-badge-dr ' + (closing >= 0 ? 'bg-success' : 'bg-danger') + '">' + (closing >= 0 ? 'To Collect' : 'To Pay') + '</span>'
             +  '</td></tr>';
        html += '</tbody></table></div>';
        return html;
    }

    /**
     * Renders the Notes tab list from services JSON data.
     * @param {object} d
     * @returns {string}
     */
    function _renderCPNotesList(d) {
        var notes = d.Notes || d.notes || [];
        var html = '<div class="p-3 p-md-4">';

        /* add note editor */
        html += '<div class="mb-3"><div class="cp-note-editor-wrap mb-2"><div id="cpNoteEditor"></div></div>'
             +  '<div class="text-end"><button type="button" class="btn btn-sm btn-primary" id="cpSaveNoteBtn">'
             +  '<i class="bx bx-save me-1"></i>Add Note</button></div></div>';

        /* notes list */
        html += '<div id="cpNotesList">';
        if (!notes.length) {
            html += '<div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted cp-note-empty" id="cpNoNotes">'
                 +  '<i class="bx bx-note fs-2 mb-2"></i>No notes yet. Add the first note above.</div>';
        } else {
            notes.forEach(function(note) {
                var uname    = _cpEsc(note.UserName  || note.userName  || 'Unknown');
                var noteHtml = note.Note || note.note || '';
                var words2   = (note.UserName || note.userName || 'U').trim().split(' ');
                var init2    = ((words2[0]||'').charAt(0) + ((words2[1]||'').charAt(0))).toUpperCase();
                var created  = note.CreatedOn || note.createdOn || '';
                var ago      = '';
                if (created) {
                    var diff = Math.floor((Date.now() - new Date(created).getTime()) / 1000);
                    if (diff < 60)       ago = 'just now';
                    else if (diff < 3600) ago = Math.floor(diff / 60) + 'm ago';
                    else if (diff < 86400)ago = Math.floor(diff / 3600) + 'h ago';
                    else                  ago = _cpFmtDate(created.substring(0, 10));
                }
                html += '<div class="d-flex gap-3 mb-3 pb-3 border-bottom align-items-start cp-note-item">'
                     +  '<div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-primary cp-note-avatar">' + (init2 || 'U') + '</span></div>'
                     +  '<div class="flex-grow-1 min-w-0">'
                     +  '<div class="d-flex justify-content-between align-items-center mb-1">'
                     +  '<span class="fw-semibold cp-note-user">' + uname + '</span>'
                     +  '<span class="text-muted cp-note-time" title="' + _cpEsc(created) + '">' + ago + '</span>'
                     +  '</div><div class="cp-note-html">' + noteHtml + '</div>'
                     +  '</div></div>';
            });
        }
        html += '</div></div>';

        /* init Quill after DOM insert (called from _initCPNotes) */
        return html;
    }

    /* ── small helpers ── */
    /**
     * Escapes a string for safe HTML insertion.
     * @param {string} s
     * @returns {string}
     */
    function _cpEsc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    /**
     * Renders a label/value field column.
     * @param {string} label
     * @param {string} valueHtml
     * @param {boolean} isSemibold
     * @returns {string}
     */
    function _cpField(label, valueHtml, isSemibold) {
        return '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">' + label + '</div>'
             + '<div' + (isSemibold ? '' : '') + '>' + valueHtml + '</div></div>';
    }
    /**
     * Renders a labelled card section.
     * @param {string} icon
     * @param {string} iconCls
     * @param {string} heading
     * @param {string} bodyHtml
     * @returns {string}
     */
    function _cpCard(icon, iconCls, heading, bodyHtml) {
        return '<div class="card border shadow-none mb-3">'
             + '<div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
             + '<i class="bx ' + icon + ' ' + iconCls + '"></i>'
             + '<span class="fw-semibold" style="font-size:var(--r2k-text-82);">' + heading + '</span>'
             + '</div><div class="card-body p-3">' + bodyHtml + '</div></div>';
    }

    /**
     * Opens the customer profile modal and loads the overview tab.
     * @param {number} uid
     */
    window.openCustomerProfile = function (uid) {
        _cpCurrentUID = uid;
        _cpTabLoaded  = {};
        $('#cpTabContent .cp-tab-pane').empty().removeClass('d-block').addClass('d-none');
        $('#cpTabContent_overview').removeClass('d-none').addClass('d-block');
        $('#cpTabNav .cp-tab-link').removeClass('active');
        $('#cpTab_overview').addClass('active');
        $('#customerProfileModal').modal('show');
        _loadCPTab('overview');
    };

    /**
     * Loads a profile tab via AJAX (only on first click, cached thereafter).
     * @param {string} tab
     */
    function _loadCPTab(tab) {
        var $pane = $('#cpTabContent_' + tab);
        if (_cpTabLoaded[tab]) return;

        $pane.html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-primary" role="status"></div></div>'
        );

        ajaxLoading(0); // suppress full-page overlay; inline spinner is used inside the modal
        $.ajax({
            url     : global_services_url + 'api/customers/getCustomerProfileTab/' + _cpCurrentUID + '/' + tab,
            method  : 'GET',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: JwtData.Org.OrgUID },
            dataType: 'json',
            success : function (res) {
                if (!res || res.Error) {
                    $pane.html('<div class="alert alert-danger m-4">' + (res && res.Message ? res.Message : 'Failed to load.') + '</div>');
                    return;
                }
                var data = res.Data || res;
                $pane.html(_renderCPTab(tab, data));
                _cpTabLoaded[tab] = true;

                if (tab === 'notes')      { _initCPNotes(); }
                if (tab === 'statement')  { _initCPStatement(data); }
            },
            error   : function () {
                $pane.html('<div class="alert alert-danger m-4">An error occurred. Please try again.</div>');
            },
            complete: function () { ajaxLoading(1); } // restore so page-level AJAX keeps the overlay
        });
    }

    // Tab link clicks
    $(document).on('click', '.cp-tab-link', function () {
        var tab = $(this).data('tab');
        $('#cpTabNav .cp-tab-link').removeClass('active');
        $(this).addClass('active');
        $('#cpTabContent .cp-tab-pane').removeClass('d-block').addClass('d-none');
        $('#cpTabContent_' + tab).removeClass('d-none').addClass('d-block');
        _loadCPTab(tab);
    });

    // Open from customer name link in the list
    $(document).on('click', '.cust-profile-link', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openCustomerProfile(parseInt($(this).data('uid'), 10));
    });

    // Edit button inside modal — close modal, then open edit form
    $(document).on('click', '#cpBtnEdit', function () {
        $('#customerProfileModal').modal('hide');
        setTimeout(function () {
            $('[data-uid="' + _cpCurrentUID + '"].cust-edit-btn').first().trigger('click');
        }, 350);
    });

    // Transaction dropdown items in modal header
    $(document).on('click', '.cp-tx-dd-item', function () {
        var route = $(this).data('route');
        if (route && _cpCurrentUID) {
            window.open(route + '?party=' + _cpCurrentUID, '_blank', 'noopener');
        }
    });

    // + New button inside each transaction accordion section
    $(document).on('click', '.cp-tx-new-btn', function (e) {
        e.stopPropagation();
        var route = $(this).data('route');
        if (route && _cpCurrentUID) {
            window.open(route + '?party=' + _cpCurrentUID, '_blank', 'noopener');
        }
    });

    // Reset state when modal is fully hidden
    $('#customerProfileModal').on('hidden.bs.modal', function () {
        _cpTabLoaded  = {};
        _cpCurrentUID = 0;
        $('#cpTabContent .cp-tab-pane').empty();
    });

    // ── Statement tab helpers ───────────────────────────────────────────────
    /**
     * Fetches org info from Upstash org-info key (cached after first call).
     * @param {function} cb  Called with orgInfo object (may be {}).
     */
    function _fetchCPOrgInfo(cb) {
        /* Cache hit: only skip the fetch when we have real data (name present) */
        if (_cpOrgInfo && (_cpOrgInfo.name || _cpOrgInfo.Name)) { cb(_cpOrgInfo); return; }
        $.ajax({
            url     : 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/billing_seller.json',
            method  : 'GET',
            dataType: 'json',
            success : function (res) {
                _cpOrgInfo = res || {};
                cb(_cpOrgInfo);
            },
            error: function () { _cpOrgInfo = {}; cb({}); }
        });
    }

    function _initCPStatement(initialData) {
        var _altFmt = (typeof _transFormDateFormat !== 'undefined' && _transFormDateFormat) ? _transFormDateFormat : 'd-m-Y';
        flatpickr('#cpStmtFrom', { dateFormat:'Y-m-d', altInput:true, altFormat:_altFmt, defaultDate: initialData.FromDate || initialData.fromDate || undefined, static:true, position:'below left', allowInput:false });
        flatpickr('#cpStmtTo',   { dateFormat:'Y-m-d', altInput:true, altFormat:_altFmt, defaultDate: initialData.ToDate   || initialData.toDate   || undefined, static:true, position:'below left', allowInput:false });

        /* Fetch seller info from R2 and re-render the print area with it */
        _fetchCPOrgInfo(function (orgInfo) {
            if (orgInfo && (orgInfo.name || orgInfo.Name)) {
                $('#cpStmtPrintArea').html(_renderCPStatementBody(initialData, null, orgInfo));
            }
        });

        $(document).off('click.cpStmt', '#cpStmtApplyBtn').on('click.cpStmt', '#cpStmtApplyBtn', function () {
            var from = $('#cpStmtFrom').val();
            var to   = $('#cpStmtTo').val();
            if (!from || !to) return;
            var $body = $('#cpStmtPrintArea');
            $body.html('<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
            ajaxLoading(0);
            $.ajax({
                url     : global_services_url + 'api/customers/getCustomerStatementTab',
                method  : 'GET',
                headers : { Authorization: 'Bearer ' + JwtToken },
                data    : { customerUID: _cpCurrentUID, fromDate: from, toDate: to, orgId: JwtData.Org.OrgUID, userId: JwtData.User.UserUID },
                dataType: 'json',
                success : function (res) {
                    if (res && !res.Error) {
                        _fetchCPOrgInfo(function (orgInfo) {
                            $body.html(_renderCPStatementBody(res.Data || res, null, orgInfo));
                        });
                    } else {
                        $body.html('<div class="alert alert-danger m-4">' + (res && res.Message ? res.Message : 'Error loading statement.') + '</div>');
                    }
                },
                error   : function () { $body.html('<div class="alert alert-danger m-4">An error occurred.</div>'); },
                complete: function () { ajaxLoading(1); }
            });
        });

        $(document).off('click.cpStmtPrint', '#cpStmtPrintBtn').on('click.cpStmtPrint', '#cpStmtPrintBtn', function () {
            var area = document.getElementById('cpStmtPrintArea');
            if (!area) return;
            var win = window.open('', '_blank', 'width=900,height=700');
            win.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Statement</title>'
                + '<link rel="stylesheet" href="/assets/vendor/css/core.css">'
                + '<link rel="stylesheet" href="/css/apex-theme.css">'
                + '<style>body{padding:20px;font-family:sans-serif;}</style></head><body>'
                + area.innerHTML + '</body></html>');
            win.document.close();
            win.onload = function () { win.print(); };
        });
    }

    // ── Notes tab helpers ───────────────────────────────────────────────────
    function _initCPNotes() {
        if (window._cpNoteQuill) { try { window._cpNoteQuill = null; } catch(e){} }
        window._cpNoteQuill = new Quill('#cpNoteEditor', {
            theme: 'snow',
            placeholder: 'Type an internal note about this customer…',
            modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['clean']] }
        });

        $(document).off('click', '#cpSaveNoteBtn').on('click', '#cpSaveNoteBtn', function () {
            var note = '';
            if (window._cpNoteQuill) {
                note = window._cpNoteQuill.getText().trim()
                    ? window._cpNoteQuill.root.innerHTML
                    : '';
            }
            if (!note.trim()) return;
            var $btn = $(this).prop('disabled', true).text('Saving…');
            ajaxLoading(0);
            $.ajax({
                url     : global_services_url + 'api/customers/saveCustomerNote',
                type    : 'POST',
                headers : { Authorization: 'Bearer ' + JwtToken },
                data    : { CustomerUID: _cpCurrentUID, Note: note, orgId: JwtData.Org.OrgUID, userId: JwtData.User.UserUID },
                dataType: 'json',
                success: function (res) {
                    if (res && !res.Error) {
                        _cpTabLoaded['notes'] = false;
                        _loadCPTab('notes'); // reload tab — new Quill instance created fresh
                    } else {
                        showToastNotification(res && res.Message ? res.Message : 'Failed to save note.', 'error');
                        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Add Note');
                    }
                },
                error  : function () {
                    showToastNotification('Error saving note.', 'error');
                    $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Add Note');
                },
                complete: function () { ajaxLoading(1); }
            });
        });
    }

});
</script>