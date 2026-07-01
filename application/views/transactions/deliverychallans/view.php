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
                            <a href="javascript:void(0);" id="dcPartyFilterTrigger" class="apex-filter-btn" title="Filter by Customer">
                                <i class="bx bx-store me-1"></i>Customer
                            </a>
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
                                        <th>Customer</th>
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
                                            <div style="font-size:.72rem;color:rgba(255,255,255,.75);">Enter qty to return for each item</div>
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
<script src="/js/common/communication.js"></script>
<script src="<?php echo _assetV('/js/transactions/attachments.js'); ?>"></script>
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
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    _renderListResponse(resp);
                    showToastNotification(resp.Message, 'success');
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
            window.location.href = '/deliverychallan/create?fromClone=' + uid;
        });
    });

    $(document).on('change', '.dcHeaderCheck', function () {
        $('.dcCheck').prop('checked', $(this).is(':checked'));
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

        $.ajax({
            url   : '/deliverychallan/getPartialReturnData',
            method: 'POST',
            data  : { TransUID: _dcPRTransUID, [CsrfName]: CsrfToken },
            success: function (resp) {
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
                    var unitSpan   = unit ? '<span class="text-muted ms-1" style="font-size:.72rem;font-weight:400;">' + unit + '</span>' : '';
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
                                (unit ? '<span class="text-muted" style="font-size:.72rem;white-space:nowrap;">' + unit + '</span>' : '') +
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
            url   : '/deliverychallan/partialReturn',
            method: 'POST',
            data  : {
                TransUID   : _dcPRTransUID,
                ReturnItems: JSON.stringify(returnItems),
                Notes      : $('#dcPRNotes').val().trim(),
                PageNo     : PageNo,
                RowLimit   : RowLimit,
                Filter     : Filter,
                [CsrfName] : CsrfToken,
            },
            success: function (resp) {
                $('#dcPRSubmitBtn').prop('disabled', false).html('<i class="bx bx-undo me-1"></i>Confirm Return');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                $dcPRModal.modal('hide');
                _renderListResponse(resp);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $('#dcPRSubmitBtn').prop('disabled', false).html('<i class="bx bx-undo me-1"></i>Confirm Return');
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
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
