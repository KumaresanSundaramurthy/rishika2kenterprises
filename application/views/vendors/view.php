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
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                ?>

                <?php if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)): ?>
                <!-- ── Stats Strip — values populated by JS ──────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#ca8a04">
                        <div class="apex-stat-icon" style="background:#fef9c3"><i class="bx bxs-store" style="color:#ca8a04"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Vendors</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-total">0</span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-active">0</span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToCollect" data-stat-filter="ToCollect" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-arrow-to-bottom" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Collect</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-tocollect">0</span>
                                <span class="apex-stat-amount"><?php echo $cur; ?> 0</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToPay" data-stat-filter="ToPay" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-arrow-from-bottom" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Pay</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-topay">0</span>
                                <span class="apex-stat-amount"><?php echo $cur; ?> 0</span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#94a3b8;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f8fafc"><i class="bx bx-bar-chart-alt-2" style="color:#94a3b8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Combined Stats</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:var(--r2k-text-sm)"><span class="vend-stat-month fw-bold">0</span> Month</span>
                                <span style="font-size:var(--r2k-text-sm)"><span class="vend-stat-lastmonth fw-bold">0</span> Last</span>
                                <span style="font-size:var(--r2k-text-sm)"><span class="vend-stat-fy fw-bold">0</span> FY</span>
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
                            <a href="javascript:void(0);" id="vendTagFilter" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Tag"><i class="bx bx-purchase-tag me-1"></i>Tag</a>
                            <a href="javascript:void(0);" id="vendStatusFilterBtn" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by Status"><i class="bx bx-toggle-left me-1"></i>Status</a>
                            <?php if ($showUserBtn): ?>
                            <a href="javascript:void(0);" id="vendUserFilterBtn" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <!-- Group-only filters -->
                            <a href="javascript:void(0);" id="vendGrpTypeFilterBtn" class="apex-filter-btn vgrp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" title="Filter by Group Type"><i class="bx bx-category me-1"></i>Group Type</a>
                            <a href="javascript:void(0);" id="vendGrpPartyFilterBtn" class="apex-filter-btn vgrp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" title="Filter by Vendor"><i class="bx bx-store me-1"></i>Vendor</a>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn vend-only-ctrl<?php echo $initIsGroups ? ' d-none' : ''; ?>" id="btnSyncVendorsCache" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('sync_cache', 'Sync Cache'); ?>"><i class="bx bx-planet"></i></a>
                            <!-- Group-only sync -->
                            <a href="javascript:void(0);" class="apex-filter-btn vgrp-only-ctrl<?php echo $initIsGroups ? '' : ' d-none'; ?>" id="btnSyncVendorGroupsCache" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('sync_groups_cache', 'Sync Groups Cache'); ?>"><i class="bx bx-planet"></i></a>
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
                                <li class="nav-item"><a class="nav-link<?php echo ($InitTab ?? 'All') === 'All' ? ' active' : ''; ?> vend-tab" data-status="All" data-url-tab="all" href="javascript:void(0);"> All <span class="trans-tab-count d-none"></span></a></li>
                                <li class="nav-item">
                                    <a class="nav-link vgrp-view-tab<?php echo ($InitTab ?? 'All') === 'Groups' ? ' active' : ''; ?>" href="javascript:void(0);" id="vendGroupsViewTab" data-status="Groups" data-url-tab="groups">
                                        Groups <span class="trans-tab-count d-none" id="vgrpTabCount"></span>
                                    </a>
                                </li>
                                <?php
                                $vShowStats   = (int)($JwtData->GenSettings->ShowStats ?? 1) && (int)($JwtData->TransSettings->ShowTransactionStats ?? 1);
                                $vGrpStatsVis = ($InitTab ?? 'All') === 'Groups';
                                ?>
                                <!-- Group stats — visible only in groups mode when stats are enabled; values populated by JS -->
                                <li id="vgrpTabStats" class="<?php echo $vGrpStatsVis ? 'd-flex' : 'd-none'; ?> align-items-center gap-3 ms-auto pe-2" style="font-size:.81rem;list-style:none;">
                                    <span class="text-muted"><?php echo t('lbl_total', 'Total'); ?>: <strong class="vg-stat-total text-body">-</strong></span>
                                    <span class="text-muted"><?php echo t('lbl_active', 'Active'); ?>: <strong class="vg-stat-active text-success">-</strong></span>
                                    <span class="text-muted"><?php echo t('lbl_inactive', 'Inactive'); ?>: <strong class="vg-stat-inactive text-danger">-</strong></span>
                                    <span class="text-muted"><?php echo t('col_members', 'Members'); ?>: <strong class="vg-stat-members text-body">-</strong></span>
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
                                    <tbody class="r2k-tbody table-border-bottom-0"></tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center VendorsPagination apex-pag-sticky" id="VendorsPagination"></div>

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
                                    <tbody id="VendorGroupsTableBody" class="r2k-tbody table-border-bottom-0"></tbody>
                                </table>
                            </div>
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="VendorGroupsPagination"></div>
                        </div><!-- /#vgrpTableSection -->

                    </div>


                </div>

            </div>
            <!-- Content wrapper -->
            
            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/modals/send_communication'); ?>
            <?php $this->load->view('common/modals/vendor_group_form'); ?>
            <?php $this->load->view('common/modals/vendor_profile_modal'); ?>

            <?php $this->load->view('common/modals/vendor_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<!-- Filter panels (body-level to avoid overflow clipping) -->
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'                => 'vendTagFilterBox',
        'triggerId'         => 'vendTagFilter',
        'checkClass'        => 'vend-tag-chk',
        'title'             => 'Tag Filter',
        'icon'              => 'bx-purchase-tag',
        'searchPlaceholder' => 'Search tags...',
        'items'             => [],
    ],
]); ?>

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

<script src="<?php echo _assetV('/js/core/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/party_filter.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo _assetV('/assets/vendor/css/attachments.css'); ?>">
<script src="<?php echo _assetV('/js/common/attachments.js'); ?>"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="<?php echo _assetV('/js/common/vendor_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/core/pagination.js'); ?>"></script>
<script src="<?php echo _assetV('/js/vendors/vendors.js'); ?>"></script>
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
    var _dec           = 2;

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
            url    : global_services_url + 'api/vendors/getGroupDetail/' + uid,
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            cache  : false,
            data   : { orgId: JwtData.Org.OrgUID },
            success: function (res) {
                ajaxLoading(1);
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
        $('#vgrpDetailTitle').text(g.GroupName || '-');

        var badges = '';
        if (g.GroupCode) {
            badges += '<span class="badge bg-label-secondary" style="font-size:var(--r2k-text-70);font-family:monospace;">' + _vesc(g.GroupCode) + '</span>';
        }
        badges += '<span class="badge" style="background:' + tc.bg + ';color:' + tc.c + ';font-size:var(--r2k-text-70);font-weight:600;">' + _vesc(g.GroupType || '') + '</span>';
        badges += '<span class="badge ' + (g.IsActive ? 'bg-label-success' : 'bg-label-danger') + '" style="font-size:var(--r2k-text-xs);">' + (g.IsActive ? 'Active' : 'Inactive') + '</span>';
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
                        '<div style="font-size:var(--r2k-text-85);font-weight:600;color:#7c3aed;">' + _vesc(g.ContactPerson || '-') + '</div>' +
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
                    '<td class="text-muted" style="font-size:var(--r2k-text-82);">' + _vesc(m.MobileNumber || '-') + '</td>' +
                    '<td class="text-end" style="font-weight:600;color:' + balColor + ';width:150px;">' +
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
var OrgCountryISO2 = <?php echo json_encode($JwtData->Org->OrgCISO2 ?? 'IN'); ?>;
var VendShowUserFilter = <?php echo $showUserBtn ? 'true' : 'false'; ?>;
var _vendInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _vendInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;
var _vendShowStats  = <?php echo (int)(($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)); ?>;

$(function() {
    'use strict'

    $('#SearchDetails').val(_vendInitSearch || '');
    if (_vendInitSearch) { $('#SearchDetails').closest('.r2k-search-wrap').addClass('is-expanded r2k-search-active'); }
    if (_vendInitSearch && _vendInitSearch.length >= 3) { Filter['SearchAllData'] = _vendInitSearch; }
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
            url    : global_services_url + 'api/vendors/syncVendorsCache',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { orgId: JwtData.Org.OrgUID },
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
            }
        });
    });
    // ── Sync vendor groups to Upstash cache ──────────────────────────────────
    $(document).on('click', '#btnSyncVendorGroupsCache', function () {
        var $btn = $(this);
        $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        $.ajax({
            url    : global_services_url + 'api/vendors/syncVendorGroupsCache',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { orgId: JwtData.Org.OrgUID },
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
            }
        });
    });
    // ────────────────────────────────────────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);
    $(ModuleHeader).on('click', function () { _vendUpdateSelectAllBanner(); });


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
        $('#vgrpTabStats').removeClass('d-none').addClass('d-flex');
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
        _vgrpReload(1, res.Message);
    }

    // ── Groups AJAX reload ──
    function _vgrpReload(page, toastMsg) {
        _vgrpPageNo = page || 1;
        ajaxLoading(0);
        $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        $.ajax({
            url    : global_services_url + 'api/vendors/getGroupsData/' + _vgrpPageNo,
            method : 'GET',
            cache  : false,
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { Filter: JSON.stringify(_vgrpFilter), orgId: JwtData.Org.OrgUID },
            success: function (res) {
                ajaxLoading(1);
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                $('#VendorGroupsTableBody').html(generateVendorGroupsHtml(res.Data || []));
                $('#VendorGroupsPagination').html(generateVendorsPagination(res.TotalCount, _vgrpPageNo, RowLimit));
                if (res.Stats) _updateVgrpStats(res.Stats);
                var cnt = parseInt(res.TotalCount, 10) || 0;
                $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                if (toastMsg) showToastNotification(toastMsg, 'success');
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
                url    : global_services_url + 'api/vendors/deleteGroup',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : { GroupUID: uids[idx], orgId: JwtData.Org.OrgUID },
                success: function (res) {
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
        var _cacheKey = (typeof UpstashService !== 'undefined' && UpstashService.isEnabled())
            ? UpstashService.orgKey('vendor-group-types') : null;

        function _loadGroupTypesFromServices() {
            return $.ajax({
                url: global_services_url + 'api/vendors/getGroupTypes',
                method: 'GET',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: { orgId: parseInt(JwtData.Org.OrgUID, 10) },
                dataType: 'json'
            }).then(function (res) { return res.Data || []; });
        }

        var _fetchPromise = _cacheKey
            ? UpstashService.get(_cacheKey).then(function (data) {
                return (Array.isArray(data) && data.length) ? data : _loadGroupTypesFromServices();
            }).catch(_loadGroupTypesFromServices)
            : _loadGroupTypesFromServices();

        _vendGrpTypeFilterPromise = _fetchPromise.then(function (types) {
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
                url    : global_services_url + 'api/vendors/toggleGroupStatus',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : { GroupUID: uid, IsActive: newStatus, orgId: JwtData.Org.OrgUID },
                success: function (res) {
                    if (res.Error) { ajaxLoading(1); showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    _vgrpReload(_vgrpPageNo);
                },
                error: function () { ajaxLoading(1); showToastNotification('Failed to update status.', 'error'); }
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
                url    : global_services_url + 'api/vendors/deleteGroup',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : { GroupUID: uid, orgId: JwtData.Org.OrgUID },
                success: function (res) {
                    if (res.Error) { ajaxLoading(1); showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    _vgrpReload(_vgrpPageNo);
                },
                error: function () { ajaxLoading(1); showToastNotification('Delete failed.', 'error'); }
            });
        });
    });


    initExport({ moduleUID: 202, getFilters: function () { return Filter; } });

    // ── URL tab state init ───────────────────────────────────────────────────
    if (_vendInitTab === 'Groups') {
        _inVgrpMode = true;
        _vgrpLoaded = true;
        if (_vendInitSearch && _vendInitSearch.length >= 3) {
            _vgrpFilter['SearchAllData'] = _vendInitSearch;
        }
        _vgrpReload(1);
    } else {
        if (_vendInitSearch && _vendInitSearch.length >= 3) {
            Filter['SearchAllData'] = _vendInitSearch;
        }
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

    // ── Vendor Profile Modal ────────────────────────────────────────────────
    var _vpCurrentUID = 0;
    var _vpTabLoaded  = {};

    /* ── VP helpers ──────────────────────────────────────────────────────── */
    /**
     * @param {string} s
     * @returns {string}
     */
    function _vpEsc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    /**
     * @param {string} dateStr  ISO date Y-m-d
     * @returns {string}
     */
    function _vpFmtDate(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        var fmt    = (typeof _transListDateFormat !== 'undefined' && _transListDateFormat) ? _transListDateFormat : 'd M Y';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var dd     = String(d.getUTCDate()).padStart(2, '0');
        var mm     = String(d.getUTCMonth() + 1).padStart(2, '0');
        var mmm    = months[d.getUTCMonth()];
        var yyyy   = d.getUTCFullYear();
        return fmt.replace('d', dd).replace('M', mmm).replace('m', mm).replace('Y', yyyy);
    }
    /**
     * @param {number} n
     * @returns {string}
     */
    function _vpFmtNum(n) {
        return typeof smartDecimal === 'function' ? smartDecimal(parseFloat(n) || 0) : parseFloat(n || 0).toFixed(2);
    }

    /* ── VP tab renderers ────────────────────────────────────────────────── */
    /**
     * @param {string} tab
     * @param {object} d
     * @returns {string}
     */
    function _renderVPTab(tab, d) {
        switch (tab) {
            case 'overview':     return _renderVPOverview(d);
            case 'transactions': return _renderVPTransactions(d);
            case 'statement':    return _renderVPStatement(d);
            case 'notes':        return _renderVPNotes(d);
            default:             return '<div class="alert alert-danger m-4">Unknown tab.</div>';
        }
    }

    /**
     * @param {object} d
     * @returns {string}
     */
    function _renderVPOverview(d) {
        var vend    = d.Vend || {};
        var cur     = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
        var grp     = d.GroupMembership || null;
        var ob      = d.OpeningBal      || {};
        var billing = d.BillingAddr     || null;
        var ship    = d.ShippingAddr    || null;
        var monthly = d.MonthlyPurchase || [];

        var name     = vend.Name || '';
        var words    = name.trim().split(/\s+/);
        var initials = ((words[0] || '').charAt(0) + ((words[1] || '').charAt(0))).toUpperCase();
        var mobile   = vend.MobileNumber || '';
        var ccode    = vend.CountryCode  || '';
        var email    = vend.EmailAddress || '';
        var fullMob  = mobile ? (ccode ? ccode + ' ' : '') + mobile : '';
        var subtitle = fullMob + (email ? (fullMob ? ' · ' : '') + email : '');

        $('#vpAvatarInitials').text(initials || '?');
        $('#vpModalTitle').text(name);
        $('#vpModalSubtitle').text(subtitle);
        $('#vpBtnEdit').data('uid', parseInt(vend.VendorUID || 0, 10));

        var obVal  = parseFloat(ob.OpeningBalance || 0);
        var obType = ob.OpeningBalType || 'Credit';
        var days   = d.DaysSinceLastTx;
        var actTxt = days == null ? 'No transactions yet'
                   : days === 0  ? 'Active today'
                   : days === 1  ? 'Last activity yesterday'
                   : 'Last activity <strong>' + days + 'd</strong> ago';
        var tags = (vend.Tags || '').split(',').map(function(t){return t.trim();}).filter(Boolean);

        var html = '<div class="p-3 p-md-4"><div class="row g-4"><div class="col-md-7">';

        html += '<div class="d-flex align-items-center justify-content-end mb-3">'
             + '<span class="text-muted" style="font-size:.74rem;"><i class="bx bx-time-five me-1"></i>' + actTxt + '</span></div>';

        /* contact card */
        var cRows = '';
        if (mobile) {
            var waNum = (ccode + mobile).replace(/[^0-9]/g, '');
            cRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Mobile</div>'
                + '<div class="fw-semibold">' + (ccode ? '<span class="text-muted">' + _vpEsc(ccode) + '</span> ' : '') + _vpEsc(mobile)
                + ' <a href="https://wa.me/' + waNum + '?text=Hi" target="_blank" class="ms-1 text-success" title="WhatsApp"><i class="bx bxl-whatsapp"></i></a>'
                + '</div></div>';
        }
        if (email) { cRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Email</div><div class="fw-semibold text-truncate">' + _vpEsc(email) + '</div></div>'; }
        if (vend.CompanyName)   { cRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Company</div><div>' + _vpEsc(vend.CompanyName) + '</div></div>'; }
        if (vend.Area)          { cRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Area</div><div>' + _vpEsc(vend.Area) + '</div></div>'; }
        if (vend.ContactPerson) { cRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Contact Person</div><div>' + _vpEsc(vend.ContactPerson) + '</div></div>'; }
        html += '<div class="card border shadow-none mb-3"><div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
             + '<i class="bx bx-store text-warning"></i><span class="fw-semibold" style="font-size:var(--r2k-text-82);">CONTACT INFORMATION</span></div>'
             + '<div class="card-body p-3"><div class="row g-2" style="font-size:.83rem;">' + cRows + '</div></div></div>';

        /* GST & Financial card */
        var gRows = '';
        if (vend.GSTIN)     { gRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">GSTIN</div><div class="fw-semibold" style="font-family:monospace;">' + _vpEsc(vend.GSTIN) + '</div></div>'; }
        if (vend.PANNumber) { gRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">PAN</div><div style="font-family:monospace;">' + _vpEsc(vend.PANNumber) + '</div></div>'; }
        if (obVal > 0) {
            gRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Opening Balance</div>'
                + '<div class="' + (obType === 'Credit' ? 'text-danger' : 'text-success') + '">'
                + cur + ' ' + _vpFmtNum(obVal)
                + ' <span class="badge bg-label-secondary ms-1" style="font-size:var(--r2k-text-65);">' + (obType === 'Credit' ? 'To Pay' : 'Advance') + '</span></div></div>';
        }
        html += '<div class="card border shadow-none mb-3"><div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
             + '<i class="bx bx-buildings text-warning"></i><span class="fw-semibold" style="font-size:var(--r2k-text-82);">GST & FINANCIAL</span></div>'
             + '<div class="card-body p-3"><div class="row g-2" style="font-size:.83rem;">' + gRows + '</div></div></div>';

        /* Address card */
        if (billing || ship) {
            var aRows = '';
            if (billing) { var bp = [billing.Line1,billing.Line2,billing.CityText,billing.StateText,billing.Pincode].filter(Boolean); aRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Billing Address</div><div>' + _vpEsc(bp.join(', ')) + '</div></div>'; }
            if (ship)    { var sp = [ship.Line1,ship.Line2,ship.CityText,ship.StateText,ship.Pincode].filter(Boolean); aRows += '<div class="col-sm-6"><div class="text-muted mb-1" style="font-size:var(--r2k-text-sm);">Shipping Address</div><div>' + _vpEsc(sp.join(', ')) + '</div></div>'; }
            html += '<div class="card border shadow-none mb-3"><div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
                 + '<i class="bx bx-map text-danger"></i><span class="fw-semibold" style="font-size:var(--r2k-text-82);">ADDRESS</span></div>'
                 + '<div class="card-body p-3"><div class="row g-3" style="font-size:var(--r2k-text-82);">' + aRows + '</div></div></div>';
        }

        /* Tags, type, group badges */
        var bdgs = '';
        if (vend.VendorTypeName)       { bdgs += '<span class="badge bg-label-warning"><i class="bx bx-store me-1"></i>' + _vpEsc(vend.VendorTypeName) + '</span>'; }
        if (grp && grp.GroupName)      { bdgs += '<span class="badge bg-label-info"><i class="bx bxs-group me-1"></i>' + _vpEsc(grp.GroupName) + '</span>'; }
        tags.forEach(function(t) { bdgs += '<span class="badge bg-label-secondary">' + _vpEsc(t) + '</span>'; });
        if (bdgs) { html += '<div class="d-flex flex-wrap gap-2 align-items-center">' + bdgs + '</div>'; }
        if (vend.Notes) { html += '<div class="mt-3 p-3 rounded" style="background:#fffbe6;border:1px solid #ffe58f;font-size:var(--r2k-text-82);"><i class="bx bx-info-circle text-warning me-1"></i>' + _vpEsc(vend.Notes).replace(/\n/g,'<br>') + '</div>'; }

        html += '</div>'; /* col-md-7 */

        /* RIGHT: KPI 2×2 + chart */
        var cBal  = parseFloat(d.ClosingBalance || 0);
        var cType = d.ClosingBalType || 'Credit';
        var bCls  = cBal === 0 ? 'secondary' : (cType === 'Credit' ? 'danger' : 'success');
        var bLbl  = cBal === 0 ? 'No Balance' : (cType === 'Credit' ? 'To Pay' : 'Advance Paid');
        var kpis  = [
            { label: 'Outstanding',     value: cur + ' ' + _vpFmtNum(cBal),                  icon: 'bx-wallet',       color: bCls,     sub: bLbl },
            { label: 'Total Purchased', value: cur + ' ' + _vpFmtNum(d.TotalPurchased || 0), icon: 'bx-package',      color: 'primary', sub: 'Lifetime' },
            { label: 'Total Paid',      value: cur + ' ' + _vpFmtNum(d.TotalPaid     || 0),  icon: 'bx-up-arrow-alt', color: 'success', sub: 'Payments out' },
            { label: 'Total Returns',   value: cur + ' ' + _vpFmtNum(d.TotalReturned || 0),  icon: 'bx-undo',         color: 'warning', sub: 'Purchase returns' },
        ];
        html += '<div class="col-md-5"><div class="row g-2 mb-3">';
        kpis.forEach(function(k) {
            html += '<div class="col-6"><div class="card border shadow-none h-100"><div class="card-body p-3">'
                + '<div class="d-flex align-items-center gap-2 mb-1"><div class="avatar avatar-xs"><span class="avatar-initial rounded bg-label-' + k.color + '"><i class="bx ' + k.icon + '" style="font-size:var(--r2k-text-90);"></i></span></div>'
                + '<span class="text-muted" style="font-size:var(--r2k-text-sm);">' + k.label + '</span></div>'
                + '<div class="fw-bold text-' + k.color + '" style="font-size:.92rem;">' + k.value + '</div>'
                + '<div class="text-muted" style="font-size:var(--r2k-text-70);">' + k.sub + '</div></div></div></div>';
        });
        html += '</div>';

        /* 6-month chart */
        html += '<div class="card border shadow-none"><div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">'
             + '<i class="bx bx-bar-chart-alt-2 text-warning"></i>'
             + '<span class="fw-semibold" style="font-size:var(--r2k-text-82);">PURCHASES — LAST 6 MONTHS</span>'
             + '</div><div class="card-body p-3">';
        if (!monthly.length) {
            html += '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:var(--r2k-text-82);"><i class="bx bx-bar-chart-alt fs-3 mb-1"></i>No purchase data in this period</div>';
        } else {
            var maxV = Math.max.apply(null, monthly.map(function(r){ return parseFloat(r.Total || 0); })) || 1;
            html += '<div class="d-flex align-items-flex-end gap-1 justify-content-between" style="height:100px;">';
            monthly.forEach(function(row) {
                var pct  = Math.min(100, Math.round((parseFloat(row.Total || 0) / maxV) * 100));
                var lbl  = (row.MonthLabel || '').substr(0, 3);
                var valK = cur + (parseFloat(row.Total || 0) / 1000).toFixed(1) + 'k';
                html += '<div class="d-flex flex-column align-items-center gap-1 flex-grow-1">'
                    + '<div class="text-muted" style="font-size:var(--r2k-text-65);" title="' + cur + ' ' + _vpFmtNum(parseFloat(row.Total || 0)) + '">' + valK + '</div>'
                    + '<div class="w-100 rounded-top" style="height:' + Math.max(4, pct) + 'px;background:#f59e0b;opacity:.8;min-width:20px;"></div>'
                    + '<div class="text-muted" style="font-size:var(--r2k-text-xs);">' + _vpEsc(lbl) + '</div></div>';
            });
            html += '</div>';
        }
        html += '</div></div>';

        html += '</div></div></div>'; /* col-md-5 + row + outer div */
        return html;
    }

    /**
     * @param {object} d
     * @returns {string}
     */
    function _renderVPTransactions(d) {
        var cur     = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
        var txData  = d.TxData  || [];
        var vendUID = d.VendorUID || _vpCurrentUID;
        var modRoutes = { 105:'purchases', 104:'purchaseorders', 108:'purchasereturns', 109:'debitnotes' };
        var modCreate = { 105:'purchases/create', 104:'purchaseorders/create', 108:'purchasereturns/create', 109:'debitnotes/create' };
        var stColors  = { Draft:'secondary', Confirmed:'primary', Issued:'primary', Partial:'warning', Completed:'success', Paid:'success', Cancelled:'danger', Rejected:'danger' };

        var html = '<div class="p-3 p-md-4"><div class="accordion" id="vpTxAccordion">';
        txData.forEach(function(section) {
            var mod       = section.module || {};
            var rows      = section.rows   || [];
            var mUID      = parseInt(mod.uid || 0, 10);
            var cid       = 'vpTxSection_' + mUID;
            var isOpen    = rows.length > 0;
            var createUrl = modCreate[mUID] ? global_base_url + modCreate[mUID] : '';

            html += '<div class="accordion-item border mb-2 rounded overflow-hidden"><div class="cp-tx-acc-header"><h2 class="accordion-header flex-grow-1">';
            html += '<button class="accordion-button ' + (isOpen ? '' : 'collapsed') + ' py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#' + cid + '" style="font-size:var(--r2k-text-85);font-weight:600;">'
                + '<i class="bx ' + _vpEsc(mod.icon || '') + ' me-2 text-warning"></i>' + _vpEsc(mod.label || '')
                + (rows.length ? ' <span class="badge bg-label-warning ms-2">' + rows.length + '</span>' : '')
                + '<span class="ms-auto me-2 text-muted fw-normal" style="font-size:var(--r2k-text-sm);">' + (rows.length ? 'Recent ' + rows.length : 'No records') + '</span>'
                + '</button></h2>';
            if (createUrl) { html += '<a href="javascript:void(0);" class="btn btn-sm btn-outline-warning vp-tx-new-btn cp-tx-new-btn" data-route="' + _vpEsc(createUrl) + '" title="Create ' + _vpEsc(mod.label || '') + '"><i class="bx bx-plus me-1"></i>New</a>'; }
            html += '</div>';
            html += '<div id="' + cid + '" class="accordion-collapse collapse ' + (isOpen ? 'show' : '') + '"><div class="accordion-body p-0">';
            if (!rows.length) {
                html += '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:var(--r2k-text-82);"><i class="bx bx-package fs-2 mb-1 opacity-50"></i>No ' + _vpEsc((mod.label || '').toLowerCase()) + ' found for this vendor.</div>';
            } else {
                html += '<div class="table-responsive"><table class="table table-sm table-hover mb-0" style="font-size:var(--r2k-text-82);">'
                    + '<thead class="table-light"><tr><th class="px-3">Ref No</th><th>Date</th><th class="text-end">Amount</th><th class="text-end">Balance</th><th>Status</th></tr></thead><tbody>';
                rows.forEach(function(r) {
                    var st  = r.DocStatus || '—';
                    var bc  = stColors[st] || 'secondary';
                    var bal = parseFloat(r.BalanceAmount || r.NetAmount || 0);
                    html += '<tr><td class="px-3 fw-semibold">' + _vpEsc(r.TransNo || '—') + '</td>'
                        + '<td>' + (r.DocDate ? _vpFmtDate(r.DocDate) : '—') + '</td>'
                        + '<td class="text-end">' + cur + ' ' + _vpFmtNum(parseFloat(r.NetAmount || 0)) + '</td>'
                        + '<td class="text-end ' + (bal > 0 ? 'text-danger' : 'text-success') + '">' + (bal > 0 ? cur + ' ' + _vpFmtNum(bal) : '—') + '</td>'
                        + '<td><span class="badge bg-label-' + bc + '">' + _vpEsc(st) + '</span></td></tr>';
                });
                html += '</tbody></table></div>';
                if (modRoutes[mUID]) { html += '<div class="px-3 py-2 border-top text-end"><a href="' + global_base_url + modRoutes[mUID] + '?party=' + vendUID + '" class="text-warning" style="font-size:var(--r2k-text-78);" target="_blank">View all ' + _vpEsc((mod.label || '').toLowerCase()) + ' <i class="bx bx-link-external ms-1"></i></a></div>'; }
            }
            html += '</div></div></div>';
        });
        html += '</div></div>';
        return html;
    }

    /**
     * @param {object} d
     * @returns {string}
     */
    function _renderVPStatement(d) {
        var cur     = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
        var vend    = d.Vend      || {};
        var stmts   = d.Statement || [];
        var ob      = d.OpeningBal || {};
        var org     = d.OrgInfo   || {};
        var obVal   = parseFloat(ob.OpeningBalance || 0);
        var obType  = ob.OpeningBalType || 'Credit';
        var obSgn   = obType === 'Credit' ? obVal : -obVal;
        var running = obSgn;

        var rows = stmts.map(function(r) {
            var debit  = parseFloat(r.Debit  || 0);
            var credit = parseFloat(r.Credit || 0);
            running += credit - debit;
            return { TxDate: r.TxDate, TxType: r.TxType, RefNo: r.RefNo, Debit: debit, Credit: credit, RunningBalance: running };
        });
        var closingBal = running;

        var orgBrand = _vpEsc(org.BrandName || org.Name || '');
        var orgGSTIN = _vpEsc(org.GSTIN || '');
        var orgAP    = [org.Line1, org.Line2, org.CityText, org.StateText ? (org.StateText + (org.Pincode ? ' - ' + org.Pincode : '')) : org.Pincode].filter(Boolean);
        var orgAddr  = _vpEsc(orgAP.join(', '));

        var html = '<div class="px-3 pt-3 d-flex align-items-end gap-2 flex-wrap" id="vpStmtControls">'
            + '<div><label class="form-label mb-1 cp-stmt-label">From Date</label><input type="text" id="vpStmtFrom" class="form-control form-control-sm cp-stmt-input" placeholder="Select date" readonly></div>'
            + '<div><label class="form-label mb-1 cp-stmt-label">To Date</label><input type="text" id="vpStmtTo" class="form-control form-control-sm cp-stmt-input" placeholder="Select date" readonly></div>'
            + '<button type="button" class="btn btn-sm btn-warning cp-stmt-btn" id="vpStmtApplyBtn"><i class="bx bx-filter-alt me-1"></i>Apply</button>'
            + '<button type="button" class="btn btn-sm btn-outline-secondary ms-auto cp-stmt-btn" id="vpStmtPrintBtn"><i class="bx bx-printer me-1"></i>Print</button>'
            + '</div>';
        html += '<div class="p-3" id="vpStmtPrintArea">';
        html += '<div class="stmt-header"><div class="stmt-header-left">';
        if (org.Logo) { html += '<img src="' + _vpEsc(org.Logo) + '" class="stmt-logo" alt="Logo">'; }
        html += '<div class="stmt-org-name">' + orgBrand + '</div>';
        if (orgAddr)  { html += '<div class="stmt-org-addr">' + orgAddr + '</div>'; }
        if (orgGSTIN) { html += '<div class="stmt-org-gstin">GSTIN: ' + orgGSTIN + '</div>'; }
        html += '</div><div class="stmt-header-right"><div class="stmt-title">Payables Statement</div>'
             + '<div class="stmt-period">' + _vpEsc(d.FromDate || '') + ' &ndash; ' + _vpEsc(d.ToDate || '') + '</div></div></div>';
        html += '<hr class="stmt-divider">';
        html += '<div class="stmt-bill-to"><div class="stmt-bill-label">Vendor</div><div class="stmt-cust-name">' + _vpEsc(vend.Name || '') + '</div>';
        if (vend.Area)         { html += '<div class="stmt-cust-meta">' + _vpEsc(vend.Area) + '</div>'; }
        if (vend.MobileNumber) { html += '<div class="stmt-cust-meta">' + _vpEsc((vend.CountryCode ? vend.CountryCode + ' ' : '') + vend.MobileNumber) + '</div>'; }
        if (vend.EmailAddress) { html += '<div class="stmt-cust-meta">' + _vpEsc(vend.EmailAddress) + '</div>'; }
        html += '</div>';

        html += '<div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0 cp-stmt-table">'
             + '<thead class="table-light"><tr><th class="px-2">Date</th><th>Type</th><th>Ref No</th>'
             + '<th class="text-end">Payment/Return (' + _vpEsc(cur) + ')</th>'
             + '<th class="text-end">Purchase (' + _vpEsc(cur) + ')</th>'
             + '<th class="text-end">Balance (' + _vpEsc(cur) + ')</th></tr></thead><tbody>';
        html += '<tr class="table-light"><td colspan="5" class="px-2 fw-semibold">Opening Balance</td>'
             + '<td class="text-end fw-semibold ' + (obSgn >= 0 ? 'text-danger' : 'text-success') + '">'
             + _vpFmtNum(Math.abs(obSgn)) + ' <span class="badge bg-label-secondary ms-1 cp-stmt-badge-dr">' + (obSgn >= 0 ? 'Payable' : 'Advance') + '</span></td></tr>';
        if (!rows.length) {
            html += '<tr><td colspan="6" class="text-center text-muted py-4">No transactions in this period.</td></tr>';
        } else {
            var tbadges = { Purchase:'danger', Payment:'success', Return:'primary' };
            rows.forEach(function(r) {
                html += '<tr><td class="px-2">' + (r.TxDate ? _vpFmtDate(r.TxDate) : '—') + '</td>'
                    + '<td><span class="badge bg-label-' + (tbadges[r.TxType] || 'secondary') + '">' + _vpEsc(r.TxType || '—') + '</span></td>'
                    + '<td class="fw-semibold">' + _vpEsc(r.RefNo || '—') + '</td>'
                    + '<td class="text-end ' + (r.Debit  > 0 ? 'text-success' : 'text-muted') + '">' + (r.Debit  > 0 ? _vpFmtNum(r.Debit)  : '—') + '</td>'
                    + '<td class="text-end ' + (r.Credit > 0 ? 'text-danger'  : 'text-muted') + '">' + (r.Credit > 0 ? _vpFmtNum(r.Credit) : '—') + '</td>'
                    + '<td class="text-end fw-semibold ' + (r.RunningBalance >= 0 ? 'text-danger' : 'text-success') + '">'
                    + _vpFmtNum(Math.abs(r.RunningBalance)) + ' <span class="text-muted cp-stmt-badge-bal">' + (r.RunningBalance >= 0 ? 'Payable' : 'Advance') + '</span></td></tr>';
            });
        }
        html += '<tr class="table-active fw-bold"><td colspan="5" class="px-2">Closing Balance</td>'
             + '<td class="text-end ' + (closingBal >= 0 ? 'text-danger' : 'text-success') + '">'
             + _vpFmtNum(Math.abs(closingBal))
             + ' <span class="badge ms-1 cp-stmt-badge-dr ' + (closingBal >= 0 ? 'bg-danger' : 'bg-success') + '">' + (closingBal >= 0 ? 'To Pay' : 'Advance') + '</span></td></tr>';
        html += '</tbody></table></div></div>'; /* table + print area */
        return html;
    }

    /**
     * @param {object} d
     * @returns {string}
     */
    function _renderVPNotes(d) {
        var notes = d.Notes || [];
        var html  = '<div class="p-3 p-md-4"><div class="mb-4"><div id="vpNoteEditor" class="cp-note-quill-wrap"></div>'
            + '<div class="d-flex justify-content-end mt-2"><button type="button" class="btn btn-sm btn-warning" id="vpNoteSaveBtn"><i class="bx bx-save me-1"></i>Save Note</button></div></div>'
            + '<div id="vpNotesList">';
        if (!notes.length) {
            html += '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" id="vpNotesEmpty"><i class="bx bx-note fs-2 mb-2"></i><span>No notes yet. Add the first one above.</span></div>';
        } else {
            notes.forEach(function(n) {
                var author  = _vpEsc(((n.FirstName || '') + ' ' + (n.LastName || '')).trim() || 'Unknown');
                var initial = ((n.FirstName || '?').charAt(0)).toUpperCase();
                var dt      = n.CreatedDate ? _vpFmtDate(n.CreatedDate) : '';
                html += '<div class="cp-note-card mb-3"><div class="d-flex align-items-center gap-2 mb-1">'
                    + '<div class="avatar avatar-xs"><span class="avatar-initial rounded-circle bg-label-warning">' + initial + '</span></div>'
                    + '<span class="fw-semibold cp-note-author">' + author + '</span>'
                    + '<span class="text-muted cp-note-date">' + dt + '</span></div>'
                    + '<div class="cp-note-body ql-editor">' + (n.NoteContent || '') + '</div></div>';
            });
        }
        html += '</div></div>';
        return html;
    }

    /**
     * Opens the vendor profile modal and loads the overview tab.
     * @param {number} uid
     * @returns {void}
     */
    window.openVendorProfile = function (uid) {
        _vpCurrentUID = uid;
        _vpTabLoaded  = {};
        $('#vpTabContent .vp-tab-pane').empty().removeClass('d-block').addClass('d-none');
        $('#vpTabContent_overview').removeClass('d-none').addClass('d-block');
        $('#vpTabNav .vp-tab-link').removeClass('active');
        $('#vpTab_overview').addClass('active');
        $('#vendorProfileModal').modal('show');
        _loadVPTab('overview');
    };

    /**
     * Loads a vendor profile tab via AJAX (cached after first load).
     * @param {string} tab
     * @returns {void}
     */
    function _loadVPTab(tab) {
        var $pane = $('#vpTabContent_' + tab);
        if (_vpTabLoaded[tab]) return;

        $pane.html(
            '<div class="d-flex justify-content-center align-items-center py-5">' +
            '<div class="spinner-border text-warning" role="status"></div></div>'
        );

        ajaxLoading(0);
        $.ajax({
            url     : global_services_url + 'api/vendors/getVendorProfileTab/' + _vpCurrentUID + '/' + tab,
            type    : 'GET',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success : function (res) {
                if (!res || res.Error) {
                    $pane.html('<div class="alert alert-danger m-4">' + (res && res.Message ? res.Message : 'Failed to load.') + '</div>');
                    return;
                }
                var data = res.Data || res;
                $pane.html(_renderVPTab(tab, data));
                _vpTabLoaded[tab] = true;
                if (tab === 'notes')     { _initVPNotes(); }
                if (tab === 'statement') { _initVPStatement(data); }
            },
            error   : function () {
                $pane.html('<div class="alert alert-danger m-4">An error occurred. Please try again.</div>');
            },
            complete: function () { ajaxLoading(1); }
        });
    }

    /**
     * Inits Quill editor and wires the notes-tab save button; safe to call multiple times.
     * @returns {void}
     */
    function _initVPNotes() {
        if (window._vpNoteQuill) { try { window._vpNoteQuill = null; } catch(e){} }
        window._vpNoteQuill = new Quill('#vpNoteEditor', {
            theme      : 'snow',
            placeholder: 'Add a note about this vendor…',
            modules    : { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['clean']] }
        });
        $(document).off('click', '#vpNoteSaveBtn').on('click', '#vpNoteSaveBtn', function () {
            var note = '';
            if (window._vpNoteQuill) {
                note = window._vpNoteQuill.getText().trim()
                    ? window._vpNoteQuill.root.innerHTML
                    : '';
            }
            if (!note.trim()) return;
            var $btn = $(this).prop('disabled', true).text('Saving…');
            ajaxLoading(0);
            $.ajax({
                url    : global_services_url + 'api/vendors/saveVendorNote',
                type   : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : {
                    VendorUID : _vpCurrentUID,
                    Note      : note,
                    orgId     : parseInt(JwtData.Org.OrgUID, 10),
                    userId    : parseInt(JwtData.User.UserUID, 10),
                },
                success: function (res) {
                    if (res && !res.Error) {
                        _vpTabLoaded['notes'] = false;
                        _loadVPTab('notes');
                    } else {
                        showToastNotification(res && res.Message ? res.Message : 'Failed to save note.', 'error');
                        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Note');
                    }
                },
                error  : function () {
                    showToastNotification('Error saving note.', 'error');
                    $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Note');
                },
                complete: function () { ajaxLoading(1); }
            });
        });
    }

    /**
     * Inits Flatpickr date pickers and wires Apply + Print buttons for the statement tab.
     * @param {object} d  statement data (must include FromDate, ToDate)
     * @returns {void}
     */
    function _initVPStatement(d) {
        var altFmt = (typeof _transFormDateFormat !== 'undefined' && _transFormDateFormat) ? _transFormDateFormat : 'd-m-Y';
        flatpickr('#vpStmtFrom', { dateFormat:'Y-m-d', altInput:true, altFormat:altFmt, defaultDate:d.FromDate||undefined, static:true, position:'below left', allowInput:false });
        flatpickr('#vpStmtTo',   { dateFormat:'Y-m-d', altInput:true, altFormat:altFmt, defaultDate:d.ToDate  ||undefined, static:true, position:'below left', allowInput:false });

        $(document).off('click.vpStmt', '#vpStmtApplyBtn').on('click.vpStmt', '#vpStmtApplyBtn', function () {
            var from = $('#vpStmtFrom').val();
            var to   = $('#vpStmtTo').val();
            if (!from || !to) return;
            var $body = $('#vpTabContent_statement');
            $body.html('<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-warning" role="status"></div></div>');
            ajaxLoading(0);
            $.ajax({
                url     : global_services_url + 'api/vendors/getVendorStatementTab',
                type    : 'GET',
                headers : { Authorization: 'Bearer ' + JwtToken },
                data    : { VendorUID: _vpCurrentUID, FromDate: from, ToDate: to, orgId: parseInt(JwtData.Org.OrgUID, 10) },
                dataType: 'json',
                success : function (res) {
                    if (res && !res.Error) {
                        var data = res.Data || res;
                        $body.html(_renderVPStatement(data));
                        _initVPStatement(data);
                    } else {
                        $body.html('<div class="alert alert-danger m-4">' + (res && res.Message ? res.Message : 'Error loading statement.') + '</div>');
                    }
                },
                error   : function () { $body.html('<div class="alert alert-danger m-4">An error occurred.</div>'); },
                complete: function () { ajaxLoading(1); }
            });
        });

        $(document).off('click.vpStmtPrint', '#vpStmtPrintBtn').on('click.vpStmtPrint', '#vpStmtPrintBtn', function () {
            var area = document.getElementById('vpStmtPrintArea');
            if (!area) return;
            var win = window.open('', '_blank', 'width=900,height=700');
            win.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Payables Statement</title>'
                + '<link rel="stylesheet" href="/assets/vendor/css/core.css">'
                + '<link rel="stylesheet" href="/css/apex-theme.css">'
                + '<style>body{padding:20px;font-family:sans-serif;}</style></head><body>'
                + area.innerHTML + '</body></html>');
            win.document.close();
            win.onload = function () { win.print(); };
        });
    }

    // Tab link clicks
    $(document).on('click', '.vp-tab-link', function () {
        var tab = $(this).data('tab');
        $('#vpTabNav .vp-tab-link').removeClass('active');
        $(this).addClass('active');
        $('#vpTabContent .vp-tab-pane').removeClass('d-block').addClass('d-none');
        $('#vpTabContent_' + tab).removeClass('d-none').addClass('d-block');
        _loadVPTab(tab);
    });

    // Open from vendor name link in the list
    $(document).on('click', '.vend-profile-link', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openVendorProfile(parseInt($(this).data('uid'), 10));
    });

    // Edit button inside modal — close modal, then open the edit form
    $(document).on('click', '#vpBtnEdit', function () {
        var uid = $(this).data('uid') || _vpCurrentUID;
        $('#vendorProfileModal').modal('hide');
        setTimeout(function () {
            $('[data-vendoruid="' + uid + '"].EditVendor').first().trigger('click');
        }, 350);
    });

    // Transaction dropdown items in vendor modal header
    $(document).on('click', '.vp-tx-dd-item', function () {
        var route = $(this).data('route');
        if (route && _vpCurrentUID) {
            window.open(route + '?party=' + _vpCurrentUID, '_blank', 'noopener');
        }
    });

    // + New button inside each vendor transaction accordion section
    $(document).on('click', '.vp-tx-new-btn', function (e) {
        e.stopPropagation();
        var route = $(this).data('route');
        if (route && _vpCurrentUID) {
            window.open(route + '?party=' + _vpCurrentUID, '_blank', 'noopener');
        }
    });

    // Reset state when modal is fully hidden
    $('#vendorProfileModal').on('hidden.bs.modal', function () {
        _vpTabLoaded  = {};
        _vpCurrentUID = 0;
        $('#vpTabContent .vp-tab-pane').empty();
    });

});
</script>