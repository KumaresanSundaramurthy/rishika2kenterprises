<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Delivery Challans',
                    'pageDescription' => $PageDescription ?? 'Manage goods delivery notes',
                ]); ?>
                <?php
                $stats         = $SummaryStats ?? [];
                $cur           = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec           = $JwtData->GenSettings->DecimalPoints ?? 2;

                $cntAll        = array_sum(array_column($stats, 'count'));
                $cntDispatched = $stats['Dispatched']['count']  ?? 0;
                $cntDelivered  = $stats['Delivered']['count']   ?? 0;
                $cntDraft      = $stats['Draft']['count']       ?? 0;

                $amtAll        = array_sum(array_column($stats, 'amount'));
                $amtDispatched = $stats['Dispatched']['amount'] ?? 0;

                $statsItems = [
                    ['label' => 'All Challans', 'status' => 'All',        'icon' => 'bx-package',     'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $cntAll,        'amount' => $amtAll],
                    ['label' => 'Dispatched',   'status' => 'Dispatched', 'icon' => 'bx-time',         'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntDispatched, 'amount' => $amtDispatched],
                    ['label' => 'Delivered',    'status' => 'Delivered',  'icon' => 'bx-check-circle', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntDelivered,  'amount' => 0],
                    ['label' => 'Drafts',       'status' => 'Draft',      'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,      'amount' => 0],
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
                                <input type="text" id="searchTransactionData" placeholder="Challan # or customer...">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/deliverychallan/create" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>New Delivery Challan</a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="dcStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active dc-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Dispatched" href="javascript:void(0);">Dispatched <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Delivered" href="javascript:void(0);">Delivered <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Converted" href="javascript:void(0);">Converted <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="dcTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox dcHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Challan # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>
                                            Customer
                                            <a href="javascript:void(0);" id="dcPartyFilterTrigger" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Customer"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Expected Return <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center dcPagination" id="dcPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="dcStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center dcPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'dcPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/deliverychallans.js"></script>

<script>

const ModuleId     = 112;
const ModuleTable  = '#dcTable';
const ModulePag    = '.dcPagination';
const ModuleHeader = '.dcHeaderCheck';
const ModuleRow    = '.dcCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';
    initExport({ moduleUID: 112, getFilters: function () { return Filter; } });

    var dcPartyFilter = new TransPartyColFilter({
        boxId     : 'dcPartyFilterBox',
        triggerId : 'dcPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getDeliveryChallansDetails(); }
    });

    var _origGetDeliveryChallansDetails = getDeliveryChallansDetails;
    getDeliveryChallansDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            dcPartyFilter ? dcPartyFilter.getState() : {}
        );
        _origGetDeliveryChallansDetails(pageNo, rowLimit, f);
    };

    // ── Sticky pagination ──
    var $dcStaticPag = $('#dcPagination');
    var $dcStickyPag = $('#dcStickyPagination');
    function _syncDcSticky() { $dcStickyPag.find('.dcPagination').html($dcStaticPag.html()); }
    function _toggleDcSticky() {
        if (!$dcStaticPag.length) return;
        var r = $dcStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $dcStickyPag.stop(true,true).fadeOut(150); }
        else { _syncDcSticky(); $dcStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleDcSticky);
    _toggleDcSticky();

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.dc-status-tab').removeClass('active');
        $('.dc-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.dc-status-tab', function (e) {
        e.preventDefault();
        $('.dc-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getDeliveryChallansDetails();
    }, 1500));

    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getDeliveryChallansDetails();
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
        getDeliveryChallansDetails();
    });

    $(document).on('click', '.dcPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getDeliveryChallansDetails(); }
    });

    function _actionPostData(extra) {
        Filter.Status = $('.dc-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $dcBadge = $('.dc-status-tab.active .trans-tab-count');
        if (count > 0) { $dcBadge.text(count).removeClass('d-none'); } else { $dcBadge.text('').addClass('d-none'); }
        initTooltips();
    }

    // ── Inline status update ─────────────────────────────────
    $(document).on('click', '.dc-status-update', function () {
        var $btn   = $(this);
        var uid    = $btn.data('uid');
        var status = $btn.data('status');
        var num    = $btn.data('num') || '';

        var confirmMap = {
            'Delivered' : { title: 'Mark as Delivered?',  html: num ? 'Mark <strong>' + $('<span>').text(num).html() + '</strong> as delivered?' : 'Mark this challan as delivered?', color: '#198754', text: 'Yes, Mark Delivered' },
            'Cancelled' : { title: 'Cancel Challan?',      html: num ? 'Cancel <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'This cannot be undone.', color: '#d33', text: 'Yes, Cancel It' },
        };

        var cfg = confirmMap[status];
        if (cfg && !$btn.data('_confirmed')) {
            Swal.fire({
                title: cfg.title, html: cfg.html, icon: 'warning', showCancelButton: true,
                confirmButtonColor: cfg.color, cancelButtonColor: '#6c757d',
                confirmButtonText: cfg.text, cancelButtonText: 'No, Keep It'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $btn.data('_confirmed', true).trigger('click');
            });
            return;
        }

        $btn.removeData('_confirmed');
        $.ajax({
            url   : '/deliverychallan/updateDeliveryChallanStatus',
            method: 'POST',
            data  : _actionPostData({ TransUID: uid, Status: status }),
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderListResponse(resp);
                showToastNotification(resp.Message, 'success');
            }
        });
    });

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deleteDeliveryChallan', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Delivery Challan?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/deliverychallan/deleteDeliveryChallan',
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
    $(document).on('click', '.convertChallanToInvoice', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Invoice?',
            html : num ? 'Convert <strong>' + num + '</strong> to an Invoice?' : 'Convert this challan to an Invoice?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/deliverychallan/convertChallanToInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });

    // ── Duplicate ────────────────────────────────────────────
    $(document).on('click', '.duplicateDeliveryChallan', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Duplicate Challan?',
            html : num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Duplicate this delivery challan?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Duplicate', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/deliverychallan/duplicateDeliveryChallan',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    Swal.fire({
                        icon: 'success', text: resp.Message, timer: 2000, showConfirmButton: false
                    }).then(function () {
                        if (resp.EditURL) window.location.href = resp.EditURL;
                        else getDeliveryChallansDetails();
                    });
                }
            });
        });
    });

    $(document).on('change', '.dcHeaderCheck', function () {
        $('.dcCheck').prop('checked', $(this).is(':checked'));
    });

});

function _buildDCDetailHtml(resp) {
    window._dcLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-package',
        typeColor   : '#16a34a',
        typeBg      : '#dcfce7',
        hasPayments : false,
        validLabel  : 'Expected Return',
    });
}
</script>
