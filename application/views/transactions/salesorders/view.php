<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Sales Orders',
                    'pageDescription' => $PageDescription ?? 'Manage and track customer sales orders',
                ]); ?>
                <?php
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                $cntAll       = array_sum(array_column($stats, 'count'));
                $cntConfirmed = $stats['Pending']['count']   ?? 0;
                $cntCompleted = $stats['Completed']['count'] ?? 0;
                $cntDraft     = $stats['Draft']['count']     ?? 0;

                $amtAll       = array_sum(array_column($stats, 'amount'));
                $amtConfirmed = $stats['Pending']['amount']   ?? 0;
                $amtCompleted = $stats['Completed']['amount'] ?? 0;

                $statsItems = [
                    ['label' => 'All Orders', 'status' => 'All',       'icon' => 'bx-cart',        'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $cntAll,       'amount' => $amtAll],
                    ['label' => 'Pending',    'status' => 'Pending',   'icon' => 'bx-time',         'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntConfirmed, 'amount' => $amtConfirmed],
                    ['label' => 'Completed',  'status' => 'Completed', 'icon' => 'bx-check-double', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntCompleted, 'amount' => $amtCompleted],
                    ['label' => 'Drafts',     'status' => 'Draft',     'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,     'amount' => 0],
                ];
                ?>
                <div class="apex-stats-strip">
                    <?php foreach ($statsItems as $stat): ?>
                    <div class="apex-stat-item <?php echo $stat['status'] === 'All' ? 'active' : ''; ?>" data-status="<?php echo $stat['status']; ?>" data-stat-filter="<?php echo $stat['status']; ?>" style="--stat-color:<?php echo $stat['iconColor']; ?>">
                        <div class="apex-stat-icon" style="background:<?php echo $stat['iconBg']; ?>;">
                            <i class="bx <?php echo $stat['icon']; ?>" style="color:<?php echo $stat['iconColor']; ?>;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label"><?php echo $stat['label']; ?></div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $stat['count']; ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)$stat['amount'], $dec); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="container-xxl flex-grow-1 py-3">

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Order # or customer...">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="soCreatedByFilter" class="apex-filter-btn" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="soPartyFilterTrigger" class="apex-filter-btn" title="Filter by Customer"><i class="bx bx-store me-1"></i>Customer</a>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/salesorders/create" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>New Sales Order</a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="soStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active so-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link so-status-tab" data-status="Pending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link so-status-tab" data-status="Completed" href="javascript:void(0);">Completed <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link so-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link so-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="soTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox soHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Order # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Expected Delivery <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center soPagination" id="soPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="soStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center soPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'soCreatedByFilterBox',
        'triggerId'  => 'soCreatedByFilter',
        'checkClass' => 'so-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'soPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/salesorders.js"></script>

<script>
var _commOrgContext = <?php
    $org     = $CommOrgContext ?? null;
    $orgAddr = $org ? implode(', ', array_filter([
        $org->Line1 ?? '', $org->Line2 ?? '',
        $org->CityText ?? '', $org->StateText ?? '', $org->Pincode ?? '',
    ])) : '';
    echo json_encode([
        'OrgName'    => $org ? ($org->BrandName ?? $org->Name ?? '') : '',
        'OrgPhone'   => $org->MobileNumber  ?? '',
        'OrgEmail'   => $org->EmailAddress  ?? '',
        'OrgGSTIN'   => $org->GSTIN         ?? '',
        'OrgAddress' => $orgAddr,
    ]);
?>;
var _commGenSettings  = <?php echo json_encode([
    'CurrenySymbol' => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'DecimalPoints' => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
]); ?>;
var _rawEmailTemplate = <?php echo json_encode($CommEmailTemplate ?? null); ?>;
var _r2CdnBase        = <?php echo json_encode(rtrim(getenv('CFLARE_R2_CDN') ?: getenv('CDN_URL'), '/')); ?>;

const ModuleId     = 102;
const ModuleTable  = '#soTable';
const ModulePag    = '.soPagination';
const ModuleHeader = '.soHeaderCheck';
const ModuleRow    = '.soCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';
    initExport({ moduleUID: 102, getFilters: function () { return Filter; } });

    // ── Filter bar ──────────────────────────────────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getSalesOrdersDetails(); } })
        : null;

    var soCreatedByFilter = (document.getElementById('soCreatedByFilterBox'))
        ? new TransColFilter({
            boxId       : 'soCreatedByFilterBox',
            triggerId   : 'soCreatedByFilter',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () { PageNo = 1; getSalesOrdersDetails(); }
        })
        : null;

    var soPartyFilter = new TransPartyColFilter({
        boxId     : 'soPartyFilterBox',
        triggerId : 'soPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getSalesOrdersDetails(); }
    });

    var _origGetSalesOrdersDetails = getSalesOrdersDetails;
    getSalesOrdersDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            tfb               ? tfb.getState()               : {},
            soCreatedByFilter ? soCreatedByFilter.getState() : {},
            soPartyFilter     ? soPartyFilter.getState()     : {}
        );
        _origGetSalesOrdersDetails(pageNo, rowLimit, f);
    };

    // ── Sticky pagination ──
    var $soStaticPag = $('#soPagination');
    var $soStickyPag = $('#soStickyPagination');
    function _syncSoSticky() { $soStickyPag.find('.soPagination').html($soStaticPag.html()); }
    function _toggleSoSticky() {
        if (!$soStaticPag.length) return;
        var r = $soStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $soStickyPag.stop(true,true).fadeOut(150); }
        else { _syncSoSticky(); $soStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleSoSticky);
    _toggleSoSticky();

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.so-status-tab').removeClass('active');
        $('.so-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.so-status-tab', function (e) {
        e.preventDefault();
        $('.so-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getSalesOrdersDetails();
    }, 1500));

    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) {
            Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        } else {
            Filter.SortBy  = col;
            Filter.SortDir = 'DESC';
        }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.soPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getSalesOrdersDetails(); }
    });

    // helper: sync active tab status into Filter then build POST data
    function _actionPostData(extra) {
        Filter.Status = $('.so-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    // helper: render list response returned by cancel/delete directly into the table
    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $soBadge = $('.so-status-tab.active .trans-tab-count');
        if (count > 0) { $soBadge.text(count).removeClass('d-none'); } else { $soBadge.text('').addClass('d-none'); }
        initTooltips();
    }

    // ── Inline status update (Cancel from 3-dot or status badge) ──
    $(document).on('click', '.so-status-update', function () {
        var $btn   = $(this);
        var uid    = $btn.data('uid');
        var status = $btn.data('status');

        if (status === 'Cancelled' && !$btn.data('_confirmed')) {
            var num = $btn.data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this sales order';
            Swal.fire({
                title: 'Cancel Sales Order?',
                html : 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.',
                icon : 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $btn.data('_confirmed', true).trigger('click');
            });
            return;
        }

        $btn.removeData('_confirmed');
        $.ajax({
            url   : '/salesorders/updateSalesOrderStatus',
            method: 'POST',
            data  : _actionPostData({ TransUID: uid, Status: status }),
            success: function (resp) {
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                    return;
                }
                _renderListResponse(resp);
                showToastNotification('Sales order cancelled.', 'success');
            }
        });
    });

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deleteSalesOrder', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Sales Order?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/deleteSalesOrder',
                method: 'POST',
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    _renderListResponse(resp);
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false });
                }
            });
        });
    });

    // ── Convert to Invoice ───────────────────────────────────
    $(document).on('click', '.convertSOToInvoice', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Invoice?',
            html : num ? 'Convert <strong>' + num + '</strong> to an Invoice?' : 'Convert this sales order to an Invoice?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/convertSalesOrderToInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });

    // ── Convert to Delivery Challan ──────────────────────────
    $(document).on('click', '.convertSOToChallan', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Delivery Challan?',
            html : num ? 'Convert <strong>' + num + '</strong> to a Delivery Challan?' : 'Convert this sales order to a Delivery Challan?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/convertSalesOrderToDeliveryChallan',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });


    $(document).on('change', '.soHeaderCheck', function () {
        $('.soCheck').prop('checked', $(this).is(':checked'));
    });

});

function _buildSODetailHtml(resp) {
    window._soLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-store-alt',
        typeColor   : '#d97706',
        typeBg      : '#fff8e1',
        hasPayments : false,
        validLabel  : 'Expected Delivery',
    });
}

</script>
