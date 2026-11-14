<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Delivery Challans',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <?php if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)): ?>
                <div class="apex-stats-strip" id="dcStatsStrip">
                    <div class="apex-stat-item" data-status="All" data-stat-filter="All" style="--stat-color:#696cff">
                        <div class="apex-stat-icon" style="background:#eef2ff;"><i class="bx bx-package" style="color:#696cff;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">All Challans</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="dcStatAllCount">0</span>
                                <span class="apex-stat-amount" id="dcStatAllAmount"></span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" data-status="Dispatched" data-stat-filter="Dispatched" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed;"><i class="bx bx-time" style="color:#f97316;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Dispatched</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="dcStatDispatchedCount">0</span>
                                <span class="apex-stat-amount" id="dcStatDispatchedAmount"></span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" data-status="Delivered" data-stat-filter="Delivered" style="--stat-color:#16a34a">
                        <div class="apex-stat-icon" style="background:#dcfce7;"><i class="bx bx-check-circle" style="color:#16a34a;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Delivered</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="dcStatDeliveredCount">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" data-status="Draft" data-stat-filter="Draft" style="--stat-color:#64748b">
                        <div class="apex-stat-icon" style="background:#f1f5f9;"><i class="bx bx-edit" style="color:#64748b;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Drafts</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="dcStatDraftCount">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Challan # or customer..." value="">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <a href="javascript:void(0);" id="dcPartyFilterTrigger" class="apex-filter-btn" title="Filter by Customer">
                                <i class="bx bx-store me-1"></i>Customer
                            </a>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>"><i class="bx bx-refresh"></i></a>
                            <div class="btn-group d-none" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-slider-alt"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu">
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a></li>
                                </ul>
                            </div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="<?= site_url('deliverychallan/create') ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_delivery_challan', 'Create Delivery Challan'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="dcStatusTabs" role="tablist" data-trans-path="/deliverychallan">
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="All" data-url-tab="all" href="javascript:void(0);">All <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Dispatched" data-url-tab="dispatched" href="javascript:void(0);">Dispatched <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Delivered" data-url-tab="delivered" href="javascript:void(0);">Delivered <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Converted" data-url-tab="converted" href="javascript:void(0);">Converted <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Cancelled" data-url-tab="cancelled" href="javascript:void(0);">Cancelled <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link dc-status-tab" data-status="Draft" data-url-tab="draft" href="javascript:void(0);">Drafts <span class="dc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Select-all banner -->
                        <div id="dcSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="dcSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="dcSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="dcSelectAllClear" class="ms-2 d-none">Clear selection</a>
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
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Challan # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Expected Return <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Last Updated</th>
                                        <th class="text-center" style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0"></tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center dcPagination apex-pag-sticky" id="dcPagination"></div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- ── Partial Return Modal ───────────────────────────────── -->
                    <div class="modal fade" id="dcPartialReturnModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header" style="background:linear-gradient(135deg,#0f766e,#0d9488);padding:14px 20px;">
                                    <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                        <div style="background:rgba(255,255,255,.18);border-radius:8px;padding:8px 10px;flex-shrink:0;">
                                            <i class="bx bx-undo" style="font-size:1.4rem;color:#fff;display:block;"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div style="font-size:.95rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="dcPRModalTitle">Partial / Full Return</div>
                                            <div style="font-size:var(--r2k-text-sm);color:rgba(255,255,255,.75);">Enter qty to return for each item</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div id="dcPRLoadingState" class="text-center py-5">
                                        <div class="spinner-border text-primary"></div>
                                    </div>
                                    <div id="dcPRContent" style="display:none;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="px-3">Product</th>
                                                    <th class="text-center" style="width:110px;">Dispatched</th>
                                                    <th class="text-center" style="width:110px;">Returned</th>
                                                    <th class="text-center" style="width:110px;">Still Out</th>
                                                    <th class="text-center" style="width:130px;">Return Now</th>
                                                </tr>
                                            </thead>
                                            <tbody id="dcPRTableBody"></tbody>
                                        </table>
                                        <div class="px-3 pt-3 pb-3">
                                            <label class="form-label small fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                            <textarea id="dcPRNotes" class="form-control form-control-sm" rows="2" placeholder="Reason for return, condition of goods, etc."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer" style="border-top:1px solid #dee2e6;padding:10px 16px;justify-content:flex-end;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-sm px-4" id="dcPRSubmitBtn" style="background:#0f766e;color:#fff;border:none;" disabled>
                                        <i class="bx bx-undo me-1"></i>Confirm Return
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'dcPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/party_filter.js"></script>
<script src="/js/common/communication.js"></script>
<script src="<?php echo _assetV('/js/transactions/attachments.js'); ?>"></script>
<script src="/js/core/viewmodal.js"></script>
<script src="/js/core/a4_print.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/transactions/deliverychallans.js"></script>

<script>

const ModuleId     = 112;
const ModuleTable  = '#dcTable';
const ModulePag    = '.dcPagination';
const ModuleHeader = '.dcHeaderCheck';
const ModuleRow    = '.dcCheck';

var _dcTabFilterMap = { All: ['dcPartyFilterTrigger'], Dispatched: ['dcPartyFilterTrigger'], Delivered: ['dcPartyFilterTrigger'], Converted: ['dcPartyFilterTrigger'], Cancelled: ['dcPartyFilterTrigger'], Draft: ['dcPartyFilterTrigger'] };
var _allDcFilterEls = ['dcPartyFilterTrigger'];

var _dcTabSlugMap = { all: 'All', dispatched: 'Dispatched', delivered: 'Delivered', converted: 'Converted', cancelled: 'Cancelled', draft: 'Draft' };

$(function () {
    'use strict';

    _checkPendingToast('_dcPendingToast');

    // Read state from URL params
    var _uP           = new URLSearchParams(window.location.search);
    var _dcInitTab    = _dcTabSlugMap[(_uP.get('tab') || 'all').toLowerCase()] || 'All';
    var _dcInitSearch = _uP.get('search') || '';
    var _initPage     = parseInt(_uP.get('page') || 1) || 1;

    PageNo = _initPage;
    Filter['Status'] = _dcInitTab;
    if (_dcInitSearch) { Filter.Name = _dcInitSearch; }

    // Set active tab + search bar state
    $('.dc-status-tab[data-status="' + _dcInitTab + '"]').addClass('active');
    $('.apex-stat-item[data-stat-filter="' + _dcInitTab + '"]').addClass('active');
    if (_dcInitSearch) {
        var $sw = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val(_dcInitSearch);
        $sw.addClass('is-expanded r2k-search-active');
        $sw.find('.r2k-clear').removeClass('d-none');
    }

    initExport({ moduleUID: 112, getFilters: function () {
        return $.extend({}, Filter,
            dcPartyFilter ? dcPartyFilter.getState() : {}
        );
    } });
    _applyTabFilters(_dcInitTab, _dcTabFilterMap, _allDcFilterEls);

    var dcPartyFilter = new TransPartyColFilter({
        boxId     : 'dcPartyFilterBox',
        triggerId : 'dcPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getDeliveryChallansDetails(); }
    });

    var _origGetDeliveryChallansDetails = getDeliveryChallansDetails;
    getDeliveryChallansDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            dcPartyFilter ? dcPartyFilter.getState() : {}
        );
        _origGetDeliveryChallansDetails(pageNo, rowLimit, f, afterLoad);
    };

    // ── Create / Edit — inject returnTab + returnPage ──────────────────
    $(document).on('click', 'a[href="<?= site_url('deliverychallan/create') ?>"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = global_base_url + 'deliverychallan/create?' + params.toString();
    });
    $(document).on('click', 'a[href*="/deliverychallan/"][href*="/edit"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = $(this).attr('href') + '?' + params.toString();
    });

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.dc-status-tab').removeClass('active');
        $('.dc-status-tab[data-status="' + status + '"]').addClass('active');
        _resetDcFilters();
        _applyTabFilters(status, _dcTabFilterMap, _allDcFilterEls);
        _updateTransTabUrl(status, '');
        Filter.Status = status;
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.dc-status-tab', function (e) {
        e.preventDefault();
        SelectedUIDs = []; _dcClearSelectAll(); MultipleDeleteOption();
        var status = $(this).data('status') || 'All';
        $('.dc-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        _resetDcFilters();
        _applyTabFilters(status, _dcTabFilterMap, _allDcFilterEls);
        _updateTransTabUrl(status, '');
        Filter.Status = status;
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    $('#searchTransactionData').on('input', function () {
        var curTab = $('.dc-status-tab.active').data('status') || 'All';
        _updateTransTabUrl(curTab, $.trim($(this).val()));
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
        var $th = $(this);
        if (Filter.SortBy !== col) {
            Filter.SortBy  = col;
            Filter.SortDir = 'ASC';
        } else if (Filter.SortDir === 'ASC') {
            Filter.SortDir = 'DESC';
        } else {
            delete Filter.SortBy;
            delete Filter.SortDir;
        }
        $('.col-sortable').each(function () {
            $(this).attr('data-bs-title', 'Click for ascending order');
            var tt = bootstrap.Tooltip.getInstance(this);
            if (tt) { tt.dispose(); new bootstrap.Tooltip(this); }
        });
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        if (Filter.SortBy) {
            var icon    = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
            var tipText = Filter.SortDir === 'ASC' ? 'Click for descending order' : 'Click to remove sorting';
            $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
            $th.attr('data-bs-title', tipText);
            var tt = bootstrap.Tooltip.getInstance($th[0]);
            if (tt) { tt.dispose(); new bootstrap.Tooltip($th[0]); }
        }
        PageNo = 1;
        getDeliveryChallansDetails();
    });

    $(document).on('click', '.dcPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _dcClearSelectAll(); getDeliveryChallansDetails(); }
    });

    function _resetDcFilters() {
        var $wrap = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val('');
        $wrap.find('.r2k-clear').addClass('d-none');
        $wrap.removeClass('is-expanded r2k-search-active');
        Filter.Name = '';
        if (dcPartyFilter) { dcPartyFilter.reset(); }
        $('.trans-col-filterbox, .tpcf-box').hide();
    }

    function _actionPostData(extra) {
        Filter.Status = $('.dc-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 }, extra);
    }

    function _renderListResponse() {
        getDeliveryChallansDetails(PageNo, RowLimit, Filter);
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
        ajaxLoading(1);
        $.ajax({
            url    : global_services_url + 'api/deliverychallan/updateDeliveryChallanStatus',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _actionPostData({ TransUID: uid, Status: status }),
            success: function (resp) {
                ajaxLoading(0);
                hideUIBlock();
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                showToastNotification(resp.Message, 'success');
                _renderListResponse();
            },
            error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
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
            ajaxLoading(1);
            $.ajax({
                url    : global_services_url + 'api/deliverychallan/deleteDeliveryChallan',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    showToastNotification(resp.Message, 'success');
                    _renderListResponse();
                },
                error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
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
                url    : global_services_url + 'api/deliverychallan/convertChallanToInvoice',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : { TransUID: uid, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 },
                success: function (resp) {
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });

    // ── Clone ────────────────────────────────────────────
    $(document).on('click', '.duplicateDeliveryChallan', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Clone Challan?',
            html : num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Clone this delivery challan?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Clone', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            window.location.href = global_base_url + 'deliverychallan/create?fromClone=' + uid;
        });
    });

    // ── Partial / Full Return ─────────────────────────────────────

    /**
     * Returns true if the unit name is a weight/volume/measurement type
     * that requires decimal input.
     * @param {string} unitName
     * @returns {boolean}
     */
    function _isDecimalUnit(unitName) {
        var u = (unitName || '').toLowerCase().trim();
        var decimalKeywords = ['gram', 'gm', 'kg', 'kilogram', 'litre', 'liter', 'l',
            'ml', 'milliliter', 'millilitre', 'tonne', 'ton', 'mg', 'milligram',
            'oz', 'ounce', 'pound', 'lb', 'meter', 'metre', 'cm', 'mm', 'ft',
            'inch', 'km', 'kilometer', 'kilometre'];
        return decimalKeywords.some(function(k) { return u === k || u.startsWith(k + 's'); });
    }

    var _dcPRTransUID = 0;
    var $dcPRModal    = $('#dcPartialReturnModal');

    $(document).on('click', '.dc-partial-return-btn', function () {
        _dcPRTransUID = $(this).data('uid');
        var num       = $(this).data('num') || '';
        $('#dcPRModalTitle').text('Partial / Full Return' + (num ? ' — ' + num : ''));
        $('#dcPRContent').hide();
        $('#dcPRLoadingState').show();
        $('#dcPRSubmitBtn').prop('disabled', true);
        $dcPRModal.modal('show');

        ajaxLoading(0);

        $.ajax({
            url    : global_services_url + 'api/deliverychallan/getPartialReturnData',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { TransUID: _dcPRTransUID, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 },
            success: function (resp) {
                ajaxLoading(1);
                $('#dcPRLoadingState').hide();
                if (resp.Error) {
                    $('#dcPRContent').html('<p class="text-danger text-center py-3 px-3">' + resp.Message + '</p>').show();
                    return;
                }
                // Build rows
                var rows = '';
                $.each(resp.Items, function (_, item) {
                    var stillOut   = item.StillOut;
                    var disabled   = stillOut <= 0 ? 'disabled' : '';
                    var rowClass   = stillOut <= 0 ? 'table-success' : '';
                    var unit       = $('<span>').text(item.UnitName || '').html();
                    var allowDec   = _isDecimalUnit(item.UnitName);
                    var step       = allowDec ? '0.001' : '1';
                    var unitSpan   = unit ? '<span class="text-muted ms-1" style="font-size:var(--r2k-text-sm);font-weight:400;">' + unit + '</span>' : '';
                    var stillColor = stillOut > 0 ? '#0f766e' : '#6c757d';
                    rows += '<tr class="' + rowClass + '">' +
                        '<td class="px-3" style="font-size:.83rem;">' +
                            '<div class="fw-semibold">' + $('<span>').text(item.ProductName).html() + '</div>' +
                        '</td>' +
                        '<td class="text-center" style="font-size:.83rem;">' + item.DispatchedQty + unitSpan + '</td>' +
                        '<td class="text-center" style="font-size:.83rem;">' + item.ReturnedQty + unitSpan + '</td>' +
                        '<td class="text-center fw-semibold" style="font-size:.83rem;color:' + stillColor + ';">' + stillOut + unitSpan + '</td>' +
                        '<td class="text-center">' +
                            '<div class="d-flex align-items-center justify-content-center gap-1">' +
                                '<input type="number" class="form-control form-control-sm text-center dc-pr-qty" ' +
                                    'data-trans-prod-uid="' + item.TransProdUID + '" ' +
                                    'data-product-uid="' + item.ProductUID + '" ' +
                                    'data-max="' + stillOut + '" ' +
                                    'data-allow-decimal="' + (allowDec ? '1' : '0') + '" ' +
                                    'min="0" max="' + stillOut + '" step="' + step + '" ' +
                                    'value="0" ' + disabled + ' ' +
                                    'style="width:75px;">' +
                                (unit ? '<span class="text-muted" style="font-size:var(--r2k-text-sm);white-space:nowrap;">' + unit + '</span>' : '') +
                            '</div>' +
                        '</td>' +
                    '</tr>';
                });
                $('#dcPRTableBody').html(rows);
                $('#dcPRNotes').val('');
                $('#dcPRContent').show();
                _dcPRCheckSubmit();
            },
            error: function () {
                ajaxLoading(1);
                $('#dcPRLoadingState').hide();
                $('#dcPRContent').html('<p class="text-danger text-center py-3 px-3">Failed to load return data.</p>').show();
            }
        });
    });

    // Enable submit only when at least one qty > 0 and all within range
    function _dcPRCheckSubmit() {
        var valid = false, hasError = false;
        $('.dc-pr-qty').each(function () {
            var v   = parseFloat($(this).val()) || 0;
            var max = parseFloat($(this).data('max')) || 0;
            if (v > 0) valid = true;
            if (v < 0 || v > max + 0.001) hasError = true;
        });
        $('#dcPRSubmitBtn').prop('disabled', !valid || hasError);
    }
    $(document).on('keydown', '.dc-pr-qty', function (e) {
        var allowDecimal = $(this).data('allow-decimal') === '1' || $(this).data('allow-decimal') === 1;
        if (!allowDecimal && (e.key === '.' || e.key === ',')) {
            e.preventDefault();
        }
    });

    $(document).on('input', '.dc-pr-qty', function () {
        var allowDecimal = $(this).data('allow-decimal') === '1' || $(this).data('allow-decimal') === 1;
        var max = parseFloat($(this).data('max')) || 0;
        var raw = $(this).val();

        if (!allowDecimal && raw !== '' && raw.indexOf('.') !== -1) {
            raw = String(Math.floor(parseFloat(raw) || 0));
            $(this).val(raw);
        }

        var v = parseFloat(raw) || 0;
        if (v > max) {
            v = allowDecimal ? max : Math.floor(max);
            $(this).val(v);
        }

        $(this).toggleClass('is-invalid', v < 0 || v > max + 0.001);
        _dcPRCheckSubmit();
    });

    $('#dcPRSubmitBtn').on('click', function () {
        var returnItems = [];
        $('.dc-pr-qty').each(function () {
            var qty = parseFloat($(this).val()) || 0;
            if (qty > 0) {
                returnItems.push({
                    TransProdUID: $(this).data('trans-prod-uid'),
                    ProductUID  : $(this).data('product-uid'),
                    ReturnQty   : qty,
                });
            }
        });
        if (!returnItems.length) return;

        $('#dcPRSubmitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

        $.ajax({
            url    : global_services_url + 'api/deliverychallan/partialReturn',
            method : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : {
                TransUID   : _dcPRTransUID,
                ReturnItems: JSON.stringify(returnItems),
                Notes      : $('#dcPRNotes').val().trim(),
                PageNo     : PageNo,
                RowLimit   : RowLimit,
                Filter     : Filter,
                orgId      : parseInt(JwtData.Org.OrgUID, 10),
                userId     : parseInt(JwtData.User.UserUID, 10),
                branchId   : parseInt(JwtData.Org.BranchUID, 10) || 1,
            },
            success: function (resp) {
                $('#dcPRSubmitBtn').prop('disabled', false).html('<i class="bx bx-undo me-1"></i>Confirm Return');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                $dcPRModal.modal('hide');
                _renderListResponse();
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $('#dcPRSubmitBtn').prop('disabled', false).html('<i class="bx bx-undo me-1"></i>Confirm Return');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    // ── Checkbox / select-all wiring ─────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(ModuleHeader).on('click', function () {
        _dcUpdateSelectAllBanner();
    });

    $(document).on('change', ModuleRow, function () {
        onClickOfCheckbox(this, ModuleTable, ModuleHeader, ModuleRow);
        _dcUpdateSelectAllBanner();
        MultipleDeleteOption();
    });

    $(document).on('click', '#dcSelectAllLink', function (e) {
        e.preventDefault();
        _dcSelectAllMode = true;
        _dcUpdateSelectAllBanner();
    });

    $(document).on('click', '#dcSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _dcClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Bulk delete ───────────────────────────────────────────────────────
    $('#btnDelete').on('click', function () {
        var count = _dcSelectAllMode ? _dcTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' delivery challan' + (count === 1 ? '' : 's') + '?',
            text : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            deleteMultipleDeliveryChallans();
        });
    });

    // ── syncDD: show/hide ActionsDD-Div when DeleteOption visibility changes ──
    (function syncDD() {
        var $div = $('#ActionsDD-Div');
        var $del = $('#DeleteOption');
        if (!$div.length || !$del.length) return;
        new MutationObserver(function () {
            $div.toggleClass('d-none', $del.hasClass('d-none'));
        }).observe($del[0], { attributes: true, attributeFilter: ['class'] });
    })();

    getDeliveryChallansDetails(_initPage, RowLimit, Filter);

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
