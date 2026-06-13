<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-file-text',
                    'pageIconBg'      => '#fef3c7',
                    'pageIconColor'   => '#f59e0b',
                    'pageTitle'       => $PageTitle       ?? 'Sales Invoices',
                    'pageDescription' => $PageDescription ?? 'Create and manage customer invoices',
                ]); ?>
                <?php
                // ── Build summary numbers ─────────────────────────
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                $activeInvStatuses = ['Issued', 'Partial', 'Paid'];
                $cntAll     = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activeInvStatuses));
                $amtAll     = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activeInvStatuses));
                $cntPending = ($stats['Issued']['count']   ?? 0) + ($stats['Partial']['count'] ?? 0);
                $amtPending = ($stats['Issued']['amount']  ?? 0) + ($stats['Partial']['amount'] ?? 0);
                $cntPaid    = $stats['Paid']['count']  ?? 0;
                $amtPaid    = $stats['Paid']['amount'] ?? 0;
                $cntDraft   = $stats['Draft']['count'] ?? 0;

                $statsItems = [
                    ['label' => 'All Invoices', 'status' => 'All',        'icon' => 'bx-receipt',      'iconBg' => '#fef3c7', 'iconColor' => '#f59e0b', 'count' => $cntAll,     'amount' => $amtAll],
                    ['label' => 'Pending',      'status' => 'InvPending', 'icon' => 'bx-time-five',    'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntPending, 'amount' => $amtPending],
                    ['label' => 'Paid',         'status' => 'Paid',       'icon' => 'bx-check-circle', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntPaid,    'amount' => $amtPaid],
                    ['label' => 'Drafts',       'status' => 'Draft',      'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,   'amount' => 0],
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

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Invoice # or customer...">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/invoices/create" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>New Invoice</a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="invStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active inv-status-tab" data-status="All" href="javascript:void(0);">All <span class="inv-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link inv-status-tab" data-status="InvPending" href="javascript:void(0);">Pending <span class="inv-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link inv-status-tab" data-status="Paid" href="javascript:void(0);">Paid <span class="inv-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link inv-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="inv-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link inv-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="inv-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link inv-status-tab inv-cn-tab" data-status="CreditNotes" href="javascript:void(0);"><i class="bx bx-transfer-alt me-1"></i>Credit Notes <span class="inv-cn-count ms-1 d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- Invoice Table (hidden when Credit Notes tab is active) -->
                        <div id="invTableSection">
                            <div class="table-responsive">
                                <table class="table trans-table table-hover MainviewTable mb-0" id="invTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:36px">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input table-chkbox invHeaderCheck" type="checkbox">
                                                </div>
                                            </th>
                                            <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                            <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                                # Bill <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                            </th>
                                            <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                                Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                            </th>
                                            <th>
                                                Payment Status
                                                <a href="javascript:void(0);" id="invPayStatusFilter" class="text-body ms-1"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Payment Status"
                                                   style="font-size:.85rem;">
                                                    <i class="bx bx-filter-alt align-middle"></i>
                                                </a>
                                            </th>
                                            <th>
                                                Payment Mode
                                                <a href="javascript:void(0);" id="invPayModeFilter" class="text-body ms-1"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Payment Mode"
                                                   style="font-size:.85rem;">
                                                    <i class="bx bx-filter-alt align-middle"></i>
                                                </a>
                                            </th>
                                            <th>
                                                Customer
                                                <a href="javascript:void(0);" id="invPartyFilterTrigger" class="text-body ms-1"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Customer"
                                                   style="font-size:.85rem;">
                                                    <i class="bx bx-filter-alt align-middle"></i>
                                                </a>
                                            </th>
                                            <th>
                                                Last Updated
                                                <?php if (count($OrgUsers ?? []) > 1): ?>
                                                <a href="javascript:void(0);" id="invCreatedByFilter" class="text-body ms-1"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by User"
                                                   style="font-size:.85rem;">
                                                    <i class="bx bx-filter-alt align-middle"></i>
                                                </a>
                                                <?php endif; ?>
                                            </th>
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
                            <div class="row mx-3 my-2 justify-content-between align-items-center invPagination" id="invPagination">
                                <?php echo $ModPagination ?: ''; ?>
                            </div>
                        </div>

                        <!-- Credit Notes Table (shown only when Credit Notes tab is active) -->
                        <div id="invCNSection" style="display:none;">
                            <div class="px-3 py-2 d-flex align-items-center gap-2 border-bottom">
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="cnSearchInput" placeholder="CN # or customer...">
                                    <i class="bx bx-x r2k-clear d-none"></i>
                                </div>
                                <select id="cnStatusFilter" class="form-select form-select-sm" style="width:140px;">
                                    <option value="All">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Applied">Applied</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                                <a href="javascript:void(0);" id="cnRefreshBtn" class="r2k-icon-btn ms-1" title="Refresh"><i class="bx bx-refresh"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="table trans-table table-hover mb-0" id="cnTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:44px">S.No</th>
                                            <th>CN Number</th>
                                            <th>Customer</th>
                                            <th>Source SR</th>
                                            <th>SR Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Created On</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cnTableBody" class="r2k-tbody table-border-bottom-0">
                                        <tr><td colspan="8" class="text-center py-4 text-muted">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr class="my-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center" id="cnPagination"></div>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="invStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center invPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#0d6efd'; $rpAccentBg = '#e8f0fe';
            $rpPartyIcon   = 'bx-user';  $rpDocLabel  = 'Invoice';
            $rpTotalIcon   = 'bx-receipt';
            $this->load->view('common/transactions/payment_modal');
            ?>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
            
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'invPayStatusFilterBox',
        'triggerId'  => 'invPayStatusFilter',
        'title'      => 'Payment Status',
        'icon'       => 'bx-wallet-alt',
        'filterKey'  => 'PaymentStatus',
        'checkClass' => 'inv-pay-status-chk',
        'items'      => [
            ['value' => 'Pending',        'label' => 'Pending',        'icon' => 'bx-time-five',    'color' => '#e65100'],
            ['value' => 'Partially Paid', 'label' => 'Partially Paid', 'icon' => 'bx-adjust',       'color' => '#0d47a1'],
            ['value' => 'Paid',           'label' => 'Paid',           'icon' => 'bx-check-circle', 'color' => '#2e7d32'],
        ],
    ],
]); ?>

<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'invPayModeFilterBox',
        'triggerId'  => 'invPayModeFilter',
        'title'      => 'Payment Mode',
        'icon'       => 'bx-credit-card',
        'filterKey'  => 'PaymentMode',
        'checkClass' => 'inv-pay-mode-chk',
        'items'      => array_map(function($t) {
            return ['value' => $t->Name, 'label' => $t->Name, 'icon' => 'bx-credit-card', 'color' => '#1565c0'];
        }, $PaymentTypes ?? []),
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'invCreatedByFilterBox',
        'triggerId'  => 'invCreatedByFilter',
        'checkClass' => 'inv-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'invPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/invoices.js"></script>

<script>

const ModuleId     = 103;
const ModuleTable  = '#invTable';
const ModulePag    = '.invPagination';
const ModuleHeader = '.invHeaderCheck';
const ModuleRow    = '.invCheck';

$(function () {
    'use strict';

    // Initialize Bootstrap tooltips — container:'body' prevents tooltip div from
    // firing mouseleave on the icon, which caused the heartbeat flicker.
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
    });

    Filter['Status'] = 'All';
    initExport({ moduleUID: 103, getFilters: function () { return Filter; } });

    // ── Filter bar (mode / customer / user pills) ────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getInvoicesDetails(); } })
        : null;

    // ── Column-level Payment Status filter ──────────────────────────────
    var payStatusFilter = new TransColFilter({
        boxId     : 'invPayStatusFilterBox',
        triggerId : 'invPayStatusFilter',
        filterKey : 'PaymentStatus',
        onApply   : function () { PageNo = 1; getInvoicesDetails(); }
    });

    var payModeFilter = new TransColFilter({
        boxId     : 'invPayModeFilterBox',
        triggerId : 'invPayModeFilter',
        filterKey : 'PaymentMode',
        onApply   : function () { PageNo = 1; getInvoicesDetails(); }
    });

    var invCreatedByFilter = (document.getElementById('invCreatedByFilterBox'))
        ? new TransColFilter({
            boxId     : 'invCreatedByFilterBox',
            triggerId : 'invCreatedByFilter',
            filterKey : 'UpdatedByUIDs',
            onApply   : function () { PageNo = 1; getInvoicesDetails(); }
        })
        : null;

    var invPartyFilter = new TransPartyColFilter({
        boxId     : 'invPartyFilterBox',
        triggerId : 'invPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getInvoicesDetails(); }
    });

    // Wrap getInvoicesDetails so every call automatically merges all filter states.
    var _origGetInvoicesDetails = getInvoicesDetails;
    getInvoicesDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            tfb                ? tfb.getState()                : {},
            payStatusFilter    ? payStatusFilter.getState()    : {},
            payModeFilter      ? payModeFilter.getState()      : {},
            invCreatedByFilter ? invCreatedByFilter.getState() : {},
            invPartyFilter     ? invPartyFilter.getState()     : {}
        );
        _origGetInvoicesDetails(pageNo, rowLimit, f, afterLoad);
    };

    // ── Sticky pagination ──
    var $invStaticPag = $('#invPagination');
    var $invStickyPag = $('#invStickyPagination');
    function _syncInvSticky() { $invStickyPag.find('.invPagination').html($invStaticPag.html()); }
    function _toggleInvSticky() {
        if (!$invStaticPag.length) return;
        var r = $invStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $invStickyPag.stop(true,true).fadeOut(150); }
        else { _syncInvSticky(); $invStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleInvSticky);
    _toggleInvSticky();

    // ── Stat card click → filter by status ─────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        // Sync tabs
        $('.inv-status-tab').removeClass('active');
        $('.inv-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.inv-status-tab', function (e) {
        e.preventDefault();
        $('.inv-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getInvoicesDetails();
    });

    // Search
    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getInvoicesDetails();
    }, 1500));

    // Date filter
    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getInvoicesDetails();
    });

    // Column sorting
    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) {
            Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        } else {
            Filter.SortBy  = col;
            Filter.SortDir = 'DESC';
        }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        var icon = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
        PageNo = 1;
        getInvoicesDetails();
    });

    // Pagination
    $(document).on('click', '.invPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getInvoicesDetails(); }
    });

    // View modal — handled by /js/transactions/viewmodal.js (.viewTransaction)

    // ── A4 Print — handled by /js/transactions/a4_print.js ──

    // ── Delete ──────────────────────────────────────────────
    $(document).on('click', '.deleteInvoice', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Invoice?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/deleteInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); }
                    else { getInvoicesDetails(); Swal.fire({ icon:'success', text:resp.Message, timer:1500, showConfirmButton:false }); }
                }
            });
        });
    });

    // ── Cancel Invoice ──────────────────────────────────────────────────────
    var _invCancelSetting = '<?php echo addslashes($JwtData->TransSettings->InvoiceCancelAction ?? 'ask'); ?>';

    var _cancelActionMeta = {
        credit_note : {
            label: 'Convert to Credit Note',
            desc : 'The paid amount will be converted into a <strong>Credit Note</strong> for this customer. It can be applied to a future invoice or refunded later.'
        },
        refund : {
            label: 'Mark as Refund',
            desc : 'The paid amount will be marked as a <strong>Refund</strong> due to this customer. You must physically return the money (cash / bank transfer).'
        },
        cancel_only : {
            label: 'Cancel Only',
            desc : 'The invoice will be cancelled. The paid amount remains as a <strong>credit</strong> in the customer\'s balance. Handle payment adjustments manually.'
        }
    };

    function _buildPaymentActionHtml(defaultAction) {
        var isAsk = (defaultAction === 'ask');
        var html  = '';

        if (isAsk) {
            // Ask mode — show dropdown directly
            html += '<div class="mt-3 text-start">';
            html += '<label class="form-label fw-semibold small mb-1">Select action for the received payment:</label>';
            html += '<select class="form-select form-select-sm" id="swalCancelAction">';
            html += '<option value="">— Choose an action —</option>';
            $.each(_cancelActionMeta, function (val, m) {
                html += '<option value="' + val + '">' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;min-height:36px;"></div>';
            html += '</div>';
        } else {
            // Preset mode — show description + "Click here to change"
            var meta = _cancelActionMeta[defaultAction] || {};
            html += '<div class="mt-3 text-start" id="swalPresetWrap">';
            html += '<div class="p-2 rounded small" style="background:#f0f4ff;border-left:3px solid #696cff;">';
            html += meta.desc || '';
            html += '</div>';
            html += '<a href="javascript:void(0)" class="small text-primary mt-2 d-inline-block" id="swalChangeAction">&#9998; Click here to change</a>';
            html += '</div>';
            // Hidden dropdown (shown on "Click here to change")
            html += '<div class="mt-2 text-start d-none" id="swalChangeWrap">';
            html += '<label class="form-label fw-semibold small mb-1">Select a different action:</label>';
            html += '<select class="form-select form-select-sm" id="swalCancelAction">';
            $.each(_cancelActionMeta, function (val, m) {
                html += '<option value="' + val + '"' + (val === defaultAction ? ' selected' : '') + '>' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;">' + meta.desc + '</div>';
            html += '</div>';
        }

        return html;
    }

    $(document).on('click', '.cancelInvoice', function () {
        var uid        = $(this).attr('data-uid');
        var num        = $(this).attr('data-num') || '';
        var paidAmt    = parseFloat($(this).attr('data-paid') || 0);
        var hasPaid    = paidAmt > 0;

        var baseHtml = num
            ? 'Cancel invoice <strong>' + num + '</strong>? This cannot be undone.'
            : 'This cannot be undone.';

        if (hasPaid) {
            baseHtml += '<div class="mt-2 text-muted small">Paid amount: <strong>&#8377;' +
                paidAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</strong></div>';
            baseHtml += _buildPaymentActionHtml(_invCancelSetting);
        }

        Swal.fire({
            title             : 'Cancel Invoice?',
            html              : baseHtml,
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : 'Yes, Cancel It',
            confirmButtonColor: '#fd7e14',
            didOpen: function () {
                // Shrink icon only — no other side effects
                var $icon = $(Swal.getIcon());
                $icon.css({ width: '3em', height: '3em', borderWidth: '2px' });
                $icon.find('.swal2-icon-content').css({ fontSize: '1.5em' });
                // Dropdown change → update description
                $(document).on('change', '#swalCancelAction', function () {
                    var val  = $(this).val();
                    var desc = val && _cancelActionMeta[val] ? _cancelActionMeta[val].desc : '';
                    $('#swalCancelDesc').html(desc);
                });
                // "Click here to change" link
                $(document).on('click', '#swalChangeAction', function () {
                    $('#swalPresetWrap').addClass('d-none');
                    $('#swalChangeWrap').removeClass('d-none');
                });
            },
            willClose: function () {
                $(document).off('change', '#swalCancelAction');
                $(document).off('click', '#swalChangeAction');
            }
        }).then(function (r) {
            if (!r.isConfirmed) return;

            // Determine payment action to send
            var cancelPaymentAction = '';
            if (hasPaid) {
                var chosen = $('#swalCancelAction').val();
                if (_invCancelSetting === 'ask') {
                    cancelPaymentAction = chosen || '';
                    if (!cancelPaymentAction) {
                        Swal.fire({ icon: 'warning', text: 'Please select an action for the received payment.' });
                        return;
                    }
                } else {
                    cancelPaymentAction = chosen || _invCancelSetting;
                }
            }

            $.ajax({
                url   : '/invoices/updateInvoiceStatus',
                method: 'POST',
                data  : {
                    TransUID           : uid,
                    Status             : 'Cancelled',
                    CancelPaymentAction: cancelPaymentAction,
                    [CsrfName]         : CsrfToken
                },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        var msg = resp.Message || 'Invoice cancelled.';
                        if (resp.CreditNoteAmount) {
                            msg += '<br><small class="text-muted mt-1 d-block">Credit Note: <strong>&#8377;' +
                                   parseFloat(resp.CreditNoteAmount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
                                   '</strong> (' + (resp.CreditNoteStatus || 'Pending') + ')</small>';
                        }
                        if (resp.CustomerBalance !== undefined) {
                            msg += '<br><small class="text-muted mt-1 d-block">Customer Balance: <strong>' +
                                   resp.CustomerBalanceType + ' &#8377;' +
                                   parseFloat(resp.CustomerBalance).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
                                   '</strong></small>';
                        }
                        getInvoicesDetails(undefined, undefined, undefined, function () {
                            Swal.fire({ icon: 'success', html: msg, timer: 3000, showConfirmButton: false });
                        });
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
                }
            });
        });
    });

});

function updateSummaryStats(stats) {
    if (!stats) return;
    var cur = '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>';
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    function cnt(s) { return (stats[s] && stats[s].count)  ? parseInt(stats[s].count)   : 0; }
    function amt(s) { return (stats[s] && stats[s].amount) ? parseFloat(stats[s].amount) : 0; }
    function fmtAmt(v) {
        return cur + ' ' + parseFloat(v).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    var cntAll     = cnt('Issued') + cnt('Partial') + cnt('Paid');
    var amtAll     = amt('Issued') + amt('Partial') + amt('Paid');
    var cntPending = cnt('Issued') + cnt('Partial');
    var amtPending = amt('Issued') + amt('Partial');
    var cntPaid    = cnt('Paid'),  amtPaid  = amt('Paid');
    var cntDraft   = cnt('Draft');

    $('.apex-stat-item[data-stat-filter="All"]        .apex-stat-count').text(cntAll.toLocaleString());
    $('.apex-stat-item[data-stat-filter="All"]        .apex-stat-amount').text(fmtAmt(amtAll));
    $('.apex-stat-item[data-stat-filter="InvPending"] .apex-stat-count').text(cntPending.toLocaleString());
    $('.apex-stat-item[data-stat-filter="InvPending"] .apex-stat-amount').text(fmtAmt(amtPending));
    $('.apex-stat-item[data-stat-filter="Paid"]       .apex-stat-count').text(cntPaid.toLocaleString());
    $('.apex-stat-item[data-stat-filter="Paid"]       .apex-stat-amount').text(fmtAmt(amtPaid));
    $('.apex-stat-item[data-stat-filter="Draft"]      .apex-stat-count').text(cntDraft.toLocaleString());
}

// ── Detail HTML builder ─────────────────────────────────────────
function _buildInvDetailHtml(resp) {
    window._invLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-receipt',
        typeColor   : '#0d6efd',
        typeBg      : '#e8f0fe',
        hasPayments : true,
    });
}

// ── Init shared payment modal (invoices) ─────────────────────────────────────
initRecordPaymentModal(
    <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>,
    <?php echo json_encode(array_values(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
    }, array_filter($BankAccounts ?? [], function($b) { return !(int)$b->IsCash; })))); ?>,
    '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>'
);

window.rpAfterSuccess = function (resp) {
    if (resp.SummaryStats) updateSummaryStats(resp.SummaryStats);
};

// Open modal when "Receive Payment" clicked on an invoice row
$(document).on('click', '.invReceivePayment', function () {
    window.rpOpenModal({
        transUID  : $(this).data('uid'),
        submitUrl : '/invoices/recordInvoicePayment',
        docNum    : $(this).data('num')   || '',
        docDate   : $(this).data('date')  || '',
        partyName : $(this).data('party') || '',
        total     : parseFloat($(this).data('total'))   || 0,
        paid      : parseFloat($(this).data('paid'))    || 0,
        pending   : parseFloat($(this).data('pending')) || 0,
    });
});

// Update invoice row after payment without full page reload
function updateInvoiceRow(invoice, payments, paidTotal) {
    var $row = $('tr[data-trans-uid="' + invoice.TransUID + '"]');
    if (!$row.length) return;
    
    // Update paid amount
    $row.find('.inv-paid-amt').text('₹' + parseFloat(paidTotal || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    // Update balance amount
    var balance = parseFloat(invoice.BalanceAmount || 0);
    $row.find('.inv-balance-amt').text('₹' + balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    // Update status badge
    var statusBadge = '';
    if (invoice.DocStatus === 'Paid') {
        statusBadge = '<span class="badge bg-label-success">Paid</span>';
    } else if (invoice.DocStatus === 'Partial') {
        statusBadge = '<span class="badge bg-label-warning">Partial</span>';
    } else if (invoice.DocStatus === 'Issued') {
        statusBadge = '<span class="badge bg-label-primary">Issued</span>';
    } else if (invoice.DocStatus === 'Draft') {
        statusBadge = '<span class="badge bg-label-secondary">Draft</span>';
    }
    $row.find('.inv-status-badge').html(statusBadge);
    
    // Update payment mode badges
    var paymentHtml = '';
    if (payments && payments.length > 0) {
        payments.forEach(function(p) {
            paymentHtml += '<span class="badge bg-label-info me-1">' + (p.PaymentTypeName || 'Payment') + '</span>';
        });
        var hasAttach = invoice.PaymentAttachmentCount > 0;
        if (hasAttach) {
            paymentHtml += '<button type="button" class="btn btn-icon btn-sm transPayAttachBtn" data-uid="' + invoice.TransUID + '" data-num="' + (invoice.UniqueNumber || '') + '" data-url="/invoices/getPaymentAttachments" title="View Payment Attachments"><i class="bx bx-paperclip text-primary"></i></button>';
        }
    } else {
        paymentHtml = '<span class="text-muted">—</span>';
    }
    $row.find('.inv-payment-mode').html(paymentHtml);

}

// ── Credit Notes tab ─────────────────────────────────────────────────────────
var _cnPageNo    = 1;
var _cnRowLimit  = 10;
var _cnStatus    = 'All';
var _cnSearch    = '';
var _cnLoading   = false;

function loadCreditNotes(pageNo) {
    if (_cnLoading) return;
    _cnLoading = true;
    pageNo = pageNo || _cnPageNo;
    var $tbody = $('#cnTableBody');
    $tbody.html('<tr><td colspan="8" class="text-center py-4"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading...</td></tr>');

    $.ajax({
        url  : '/invoices/getCreditNotesList',
        type : 'POST',
        data : {
            PageNo   : pageNo,
            RowLimit : _cnRowLimit,
            Status   : _cnStatus,
            Search   : _cnSearch,
            [CsrfName]: CsrfToken,
        },
        success: function (resp) {
            _cnLoading = false;
            if (resp.Error) {
                $tbody.html('<tr><td colspan="8" class="text-center py-3 text-danger">' + (resp.Message || 'Error loading credit notes') + '</td></tr>');
                return;
            }
            _cnPageNo = resp.PageNo || pageNo;
            var rows  = resp.Data  || [];
            var cur   = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
            var dec   = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

            if (!rows.length) {
                $tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted">No credit notes found.</td></tr>');
                $('#cnPagination').empty();
                return;
            }

            var html = '';
            $.each(rows, function (i, cn) {
                var statusBadge = '';
                if (cn.Status === 'Pending')  statusBadge = '<span class="badge bg-label-warning">Pending</span>';
                if (cn.Status === 'Applied')  statusBadge = '<span class="badge bg-label-success">Applied</span>';
                if (cn.Status === 'Refunded') statusBadge = '<span class="badge bg-label-secondary">Refunded</span>';
                if (cn.Status === 'Cancelled') statusBadge = '<span class="badge bg-label-danger">Cancelled</span>';

                var createdOn = cn.CreatedOn ? new Date(parseInt(cn.CreatedOn) * 1000).toLocaleDateString('en-IN') : '—';
                var srcDate   = cn.SourceTransDate ? new Date(cn.SourceTransDate).toLocaleDateString('en-IN') : '—';
                var amt       = parseFloat(cn.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });

                html += '<tr>';
                html += '<td class="text-muted">' + ((_cnPageNo - 1) * _cnRowLimit + i + 1) + '</td>';
                html += '<td><span class="fw-semibold text-primary">' + (cn.CreditNoteNumber || '—') + '</span></td>';
                html += '<td>' + (cn.CustomerName ? '<div class="fw-semibold">' + cn.CustomerName + '</div>' : '—') + (cn.MobileNo ? '<small class="text-muted d-block">' + cn.MobileNo + '</small>' : '') + '</td>';
                html += '<td>' + (cn.SourceTransNumber || '—') + '</td>';
                html += '<td>' + srcDate + '</td>';
                html += '<td class="fw-semibold">' + cur + ' ' + amt + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '<td class="text-muted">' + createdOn + '</td>';
                html += '</tr>';
            });
            $tbody.html(html);

            // Build simple pagination
            var total    = resp.TotalCount || 0;
            var lastPage = Math.ceil(total / _cnRowLimit) || 1;
            var pagHtml  = '';
            if (lastPage > 1) {
                pagHtml += '<div class="col-auto text-muted small">Showing ' + ((_cnPageNo - 1) * _cnRowLimit + 1) + '–' + Math.min(_cnPageNo * _cnRowLimit, total) + ' of ' + total + '</div>';
                pagHtml += '<div class="col-auto"><ul class="pagination pagination-sm mb-0">';
                pagHtml += '<li class="page-item' + (_cnPageNo <= 1 ? ' disabled' : '') + '"><a class="page-link cn-page-link" href="#" data-page="' + (_cnPageNo - 1) + '">&laquo;</a></li>';
                for (var p = Math.max(1, _cnPageNo - 2); p <= Math.min(lastPage, _cnPageNo + 2); p++) {
                    pagHtml += '<li class="page-item' + (p === _cnPageNo ? ' active' : '') + '"><a class="page-link cn-page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                }
                pagHtml += '<li class="page-item' + (_cnPageNo >= lastPage ? ' disabled' : '') + '"><a class="page-link cn-page-link" href="#" data-page="' + (_cnPageNo + 1) + '">&raquo;</a></li>';
                pagHtml += '</ul></div>';
            } else {
                pagHtml = '<div class="col-auto text-muted small">' + total + ' record' + (total !== 1 ? 's' : '') + '</div>';
            }
            $('#cnPagination').html(pagHtml);

            // Update tab count badge
            $('.inv-cn-count').removeClass('d-none').text(total);
        },
        error: function () {
            _cnLoading = false;
            $('#cnTableBody').html('<tr><td colspan="8" class="text-center py-3 text-danger">Request failed.</td></tr>');
        }
    });
}

// Tab click — toggle invoice vs credit notes sections
$(document).on('click', '.inv-status-tab', function () {
    var status = $(this).data('status');
    if (status === 'CreditNotes') {
        $('#invTableSection').hide();
        $('#invCNSection').show();
        if (_cnPageNo === 1) loadCreditNotes(1);
    } else {
        $('#invCNSection').hide();
        $('#invTableSection').show();
    }
});

// CN status filter
$('#cnStatusFilter').on('change', function () {
    _cnStatus = $(this).val();
    _cnPageNo = 1;
    loadCreditNotes(1);
});

// CN search
var _cnSearchTimer;
$('#cnSearchInput').on('input', function () {
    clearTimeout(_cnSearchTimer);
    var val = $.trim($(this).val());
    _cnSearchTimer = setTimeout(function () {
        _cnSearch = val;
        _cnPageNo = 1;
        loadCreditNotes(1);
    }, 600);
});

// CN refresh
$('#cnRefreshBtn').on('click', function () { loadCreditNotes(_cnPageNo); });

// CN pagination
$(document).on('click', '.cn-page-link', function (e) {
    e.preventDefault();
    var page = parseInt($(this).data('page'));
    if (page > 0) { _cnPageNo = page; loadCreditNotes(page); }
});
</script>
