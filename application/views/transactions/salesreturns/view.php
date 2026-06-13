<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-undo',
                    'pageIconBg'      => '#fff1f2',
                    'pageIconColor'   => '#f43f5e',
                    'pageTitle'       => $PageTitle       ?? 'Sales Returns',
                    'pageDescription' => $PageDescription ?? 'Manage goods returned by customers',
                ]); ?>
                <?php
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                $activeStatuses = ['Approved', 'Partial', 'Paid'];
                $cntAll     = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activeStatuses));
                $amtAll     = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activeStatuses));
                $cntPending = ($stats['Approved']['count']  ?? 0) + ($stats['Partial']['count']  ?? 0);
                $amtPending = ($stats['Approved']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                $cntPaid    = $stats['Paid']['count']  ?? 0;
                $amtPaid    = $stats['Paid']['amount'] ?? 0;
                $cntDraft   = $stats['Draft']['count'] ?? 0;

                $statsItems = [
                    ['label' => 'All Returns', 'status' => 'All',       'icon' => 'bx-undo',         'iconBg' => '#fff1f2', 'iconColor' => '#f43f5e', 'count' => $cntAll,     'amount' => $amtAll],
                    ['label' => 'Pending',     'status' => 'SRPending', 'icon' => 'bx-time-five',    'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntPending, 'amount' => $amtPending],
                    ['label' => 'Paid',        'status' => 'Paid',      'icon' => 'bx-check-circle', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntPaid,    'amount' => $amtPaid],
                    ['label' => 'Drafts',      'status' => 'Draft',     'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,   'amount' => 0],
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
                            <div class="apex-search-wrap">
                                <i class="bx bx-search apex-search-icon"></i>
                                <input type="text" id="searchTransactionData" class="apex-search-input" placeholder="Return # or customer...">
                                <i class="bx bx-x apex-search-clear d-none"></i>
                            </div>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/salesreturns/create" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>New Sales Return</a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="srStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active sr-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link sr-status-tab" data-status="SRPending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link sr-status-tab" data-status="Paid" href="javascript:void(0);">Paid <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link sr-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link sr-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="srTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox srHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            # Bill / Return <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>
                                            Payment Status
                                            <a href="javascript:void(0);" id="srPayStatusFilter" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Payment Status"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th>
                                            Payment Mode
                                            <a href="javascript:void(0);" id="srPayModeFilter" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Payment Mode"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th>
                                            Customer
                                            <a href="javascript:void(0);" id="srPartyFilterTrigger" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Customer"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th>
                                            Last Updated
                                            <?php if (count($OrgUsers ?? []) > 1): ?>
                                            <a href="javascript:void(0);" id="srCreatedByFilter" class="text-body ms-1"
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center srPagination" id="srPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#198754'; $rpAccentBg = '#e8f5e9';
            $rpPartyIcon   = 'bx-user';  $rpDocLabel  = 'Sales Return';
            $rpTotalIcon   = 'bx-undo';
            $rpNumId       = 'rpInvNum'; $rpDateId    = 'rpInvDate';
            $rpBtnLabel    = 'Refund Payment';
            $this->load->view('common/transactions/payment_modal');
            ?>

            <!-- Apply Credit to Invoice Modal -->
            <div class="modal fade" id="applyCreditModal" tabindex="-1" aria-hidden="true" aria-labelledby="applyCreditModalLabel">
                <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
                    <div class="modal-content">
                        <div class="modal-header py-3" style="background:#e3f2fd;border-bottom:1px solid #bbdefb;">
                            <div>
                                <h6 class="modal-title fw-semibold mb-0" style="color:#1565c0;" id="applyCreditModalLabel">
                                    <i class="bx bx-credit-card me-2"></i>Apply Credit to Invoice
                                </h6>
                                <div class="text-muted" style="font-size:.78rem;margin-top:2px;">
                                    Sales Return: <strong id="acSrNum">—</strong>
                                </div>
                            </div>
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <input type="hidden" id="acSalesReturnUID">

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded text-center" style="background:#e8f5e9;border:1px solid #c8e6c9;">
                                        <div class="text-muted" style="font-size:.7rem;">Customer</div>
                                        <div class="fw-semibold text-truncate" style="font-size:.82rem;" id="acPartyName">—</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded text-center" style="background:#fff3e0;border:1px solid #ffe0b2;">
                                        <div class="text-muted" style="font-size:.7rem;">Available Credit</div>
                                        <div class="fw-semibold" style="font-size:.88rem;color:#e65100;" id="acCreditBalance">—</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Select Invoice <span class="text-danger">*</span></label>
                                <select id="acInvoiceUID" class="form-select form-select-sm">
                                    <option value="">— Select Invoice —</option>
                                </select>
                            </div>

                            <div id="acInvoiceInfo" class="d-none mb-3">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#f3e5f5;border:1px solid #e1bee7;">
                                            <div class="text-muted" style="font-size:.68rem;">Invoice Total</div>
                                            <div class="fw-semibold" style="font-size:.82rem;" id="acInvTotal">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#e8eaf6;border:1px solid #c5cae9;">
                                            <div class="text-muted" style="font-size:.68rem;">Paid</div>
                                            <div class="fw-semibold" style="font-size:.82rem;" id="acInvPaid">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#e3f2fd;border:1px solid #bbdefb;">
                                            <div class="text-muted" style="font-size:.68rem;">Pending</div>
                                            <div class="fw-semibold" style="font-size:.82rem;color:#1565c0;" id="acInvBalance">—</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Amount to Apply <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="acCurrencySymbol">₹</span>
                                    <input type="number" class="form-control" id="acAmount" min="0.01" step="0.01" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Notes</label>
                                <input type="text" class="form-control form-control-sm" id="acNotes" placeholder="Optional note">
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sm btn-primary" id="btnSubmitApplyCredit">
                                <i class="bx bx-check me-1"></i>Apply Credit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'srPayStatusFilterBox',
        'triggerId'  => 'srPayStatusFilter',
        'title'      => 'Payment Status',
        'icon'       => 'bx-wallet-alt',
        'filterKey'  => 'PaymentStatus',
        'checkClass' => 'sr-pay-status-chk',
        'items'      => [
            ['value' => 'Pending',        'label' => 'Pending',        'icon' => 'bx-time-five',    'color' => '#e65100'],
            ['value' => 'Partially Paid', 'label' => 'Partially Paid', 'icon' => 'bx-adjust',       'color' => '#0d47a1'],
            ['value' => 'Paid',           'label' => 'Paid',           'icon' => 'bx-check-circle', 'color' => '#2e7d32'],
        ],
    ],
]); ?>

<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'srPayModeFilterBox',
        'triggerId'  => 'srPayModeFilter',
        'title'      => 'Payment Mode',
        'icon'       => 'bx-credit-card',
        'filterKey'  => 'PaymentMode',
        'checkClass' => 'sr-pay-mode-chk',
        'items'      => array_map(function($t) {
            return ['value' => $t->Name, 'label' => $t->Name, 'icon' => 'bx-credit-card', 'color' => '#198754'];
        }, $PaymentTypes ?? []),
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'srCreatedByFilterBox',
        'triggerId'  => 'srCreatedByFilter',
        'checkClass' => 'sr-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'srPartyFilterBox',
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
<script src="/js/transactions/salesreturns.js"></script>

<script>

const ModuleId     = 106;
const ModuleTable  = '#srTable';

function showProcessing(label) { showUIBlock(label); }
function hideProcessing()       { hideUIBlock(); }

function updateSummaryStats(stats) {
    var cur = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    function fmt(v) { return cur + ' ' + parseFloat(v).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
    function cnt(s) { return stats[s] ? parseInt(stats[s].count  || 0) : 0; }
    function amt(s) { return stats[s] ? parseFloat(stats[s].amount || 0) : 0; }

    // All = active only (excludes Draft, Cancelled, Rejected)
    var cntAll = cnt('Approved') + cnt('Partial') + cnt('Paid');
    var amtAll = amt('Approved') + amt('Partial') + amt('Paid');

    var cntPending = cnt('Approved') + cnt('Partial');
    var amtPending = amt('Approved') + amt('Partial');

    var cntPaid = cnt('Paid'), amtPaid = amt('Paid');
    var cntDraft = cnt('Draft'), amtDraft = amt('Draft');

    $('.apex-stat-item[data-stat-filter="All"]       .apex-stat-count').text(cntAll.toLocaleString());
    $('.apex-stat-item[data-stat-filter="All"]       .apex-stat-amount').text(fmt(amtAll));
    $('.apex-stat-item[data-stat-filter="SRPending"] .apex-stat-count').text(cntPending.toLocaleString());
    $('.apex-stat-item[data-stat-filter="SRPending"] .apex-stat-amount').text(fmt(amtPending));
    $('.apex-stat-item[data-stat-filter="Paid"]      .apex-stat-count').text(cntPaid.toLocaleString());
    $('.apex-stat-item[data-stat-filter="Draft"]     .apex-stat-count').text(cntDraft.toLocaleString());
}
const ModulePag    = '.srPagination';
const ModuleHeader = '.srHeaderCheck';
const ModuleRow    = '.srCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';
    initExport({ moduleUID: 106, getFilters: function () { return Filter; } });

    // ── Column-level Payment Status filter ──────────────────────────────
    var payStatusFilter = new TransColFilter({
        boxId     : 'srPayStatusFilterBox',
        triggerId : 'srPayStatusFilter',
        filterKey : 'PaymentStatus',
        onApply   : function () { PageNo = 1; getSalesReturnsDetails(); }
    });

    var payModeFilter = new TransColFilter({
        boxId     : 'srPayModeFilterBox',
        triggerId : 'srPayModeFilter',
        filterKey : 'PaymentMode',
        onApply   : function () { PageNo = 1; getSalesReturnsDetails(); }
    });

    var srCreatedByFilter = (document.getElementById('srCreatedByFilterBox'))
        ? new TransColFilter({
            boxId     : 'srCreatedByFilterBox',
            triggerId : 'srCreatedByFilter',
            filterKey : 'UpdatedByUIDs',
            onApply   : function () { PageNo = 1; getSalesReturnsDetails(); }
        })
        : null;

    var srPartyFilter = new TransPartyColFilter({
        boxId     : 'srPartyFilterBox',
        triggerId : 'srPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getSalesReturnsDetails(); }
    });

    var _origGetSalesReturnsDetails = getSalesReturnsDetails;
    getSalesReturnsDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            payStatusFilter   ? payStatusFilter.getState()   : {},
            payModeFilter     ? payModeFilter.getState()     : {},
            srCreatedByFilter ? srCreatedByFilter.getState() : {},
            srPartyFilter     ? srPartyFilter.getState()     : {}
        );
        _origGetSalesReturnsDetails(pageNo, rowLimit, f);
    };

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
    });

    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.sr-status-tab').removeClass('active');
        $('.sr-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getSalesReturnsDetails();
    });

    $(document).on('click', '.sr-status-tab', function (e) {
        e.preventDefault();
        $('.sr-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getSalesReturnsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) { e.preventDefault(); PageNo = 1; getSalesReturnsDetails(); });

    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getSalesReturnsDetails();
    }, 1500));

    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from; Filter.DateTo = dr.to;
        PageNo = 1; getSalesReturnsDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        Filter.SortDir = (Filter.SortBy === col && Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        Filter.SortBy  = col;
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getSalesReturnsDetails();
    });

    $(document).on('click', '.srPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getSalesReturnsDetails(); }
    });

    // ── Cancel Sales Return ─────────────────────────────────────────────────────
    var _srCancelSetting = '<?php echo addslashes($JwtData->TransSettings->SalesReturnCancelAction ?? 'ask'); ?>';

    var _srCancelActionMeta = {
        recover : {
            label: 'Recover from Customer',
            desc : 'The refunded amount will be recorded as <strong>due from the customer</strong>. Their balance will reflect what they need to return. Physical recovery must be arranged separately.'
        },
        writeoff: {
            label: 'Write Off',
            desc : 'The refunded amount is <strong>accepted as a business loss</strong>. No recovery will be attempted. The SR is cancelled and the payment records are marked as written off.'
        }
    };

    function _buildSRPaymentActionHtml(defaultAction) {
        var isAsk = (defaultAction === 'ask');
        var html  = '';

        if (isAsk) {
            html += '<div class="mt-3 text-start">';
            html += '<label class="form-label fw-semibold small mb-1">Select action for the paid-out refund:</label>';
            html += '<select class="form-select form-select-sm" id="swalSRCancelAction">';
            html += '<option value="">— Choose an action —</option>';
            $.each(_srCancelActionMeta, function (val, m) {
                html += '<option value="' + val + '">' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalSRCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;min-height:36px;"></div>';
            html += '</div>';
        } else {
            var meta = _srCancelActionMeta[defaultAction] || {};
            html += '<div class="mt-3 text-start" id="swalSRPresetWrap">';
            html += '<div class="p-2 rounded small" style="background:#f0f4ff;border-left:3px solid #696cff;">';
            html += meta.desc || '';
            html += '</div>';
            html += '<a href="javascript:void(0)" class="small text-primary mt-2 d-inline-block" id="swalSRChangeAction">&#9998; Click here to change</a>';
            html += '</div>';
            html += '<div class="mt-2 text-start d-none" id="swalSRChangeWrap">';
            html += '<label class="form-label fw-semibold small mb-1">Select a different action:</label>';
            html += '<select class="form-select form-select-sm" id="swalSRCancelAction">';
            $.each(_srCancelActionMeta, function (val, m) {
                html += '<option value="' + val + '"' + (val === defaultAction ? ' selected' : '') + '>' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalSRCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;">' + meta.desc + '</div>';
            html += '</div>';
        }
        return html;
    }

    $(document).on('click', '.sr-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        var num    = $(this).data('num') || '';

        if (status !== 'Cancelled') {
            showProcessing('Updating Status…');
            $.ajax({
                url: '/salesreturns/updateSalesReturnStatus', method: 'POST',
                data: { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
                success: function (resp) {
                    hideProcessing();
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getSalesReturnsDetails(); }
                },
                error: function () { hideProcessing(); Swal.fire({ icon: 'error', text: 'Request failed. Try again.' }); }
            });
            return;
        }

        // Dependencies pre-loaded at page load — read from data attributes
        var deps = {
            HasCreditApplied : parseInt($(this).data('credit-applied')) === 1,
            HasRefunds       : parseFloat($(this).data('refund')) > 0,
            RefundAmount     : parseFloat($(this).data('refund')) || 0
        };

        if (deps.HasCreditApplied) {
            Swal.fire({
                icon             : 'error',
                title            : 'Cannot Cancel',
                html             : 'This Sales Return has already been applied to one or more invoices.<br><br>'
                                 + '<span class="text-muted small">Please reverse the credit allocations before cancelling.</span>',
                confirmButtonColor: '#dc3545'
            });
            return;
        }
        _showSRCancelDialog(uid, num, deps);
    });

    function _showSRCancelDialog(uid, num, deps) {
        var sym      = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
        var safeNum  = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this sales return';
        var html     = 'Cancel ' + safeNum + '? This cannot be undone.';

        if (deps.HasRefunds) {
            var fmtAmt = parseFloat(deps.RefundAmount).toLocaleString('en-IN', { minimumFractionDigits: 2 });
            html += '<div class="mt-3 p-2 rounded text-start" style="background:#fff3cd;border-left:3px solid #ffc107;">'
                  + '<div class="small fw-semibold mb-1">Refund Already Paid</div>'
                  + '<div class="small text-muted">Amount <strong>' + sym + fmtAmt + '</strong> was refunded to the customer.</div>'
                  + '</div>';
            html += _buildSRPaymentActionHtml(_srCancelSetting);
        }

        Swal.fire({
            title             : 'Cancel Sales Return?',
            html              : html,
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : 'Yes, Cancel It',
            confirmButtonColor: '#fd7e14',
            cancelButtonText  : 'No, Keep It',
            didOpen: function () {
                var $icon = $(Swal.getIcon());
                $icon.css({ width: '3em', height: '3em', borderWidth: '2px' });
                $icon.find('.swal2-icon-content').css({ fontSize: '1.5em' });
                $(document).on('change', '#swalSRCancelAction', function () {
                    var val  = $(this).val();
                    var desc = val && _srCancelActionMeta[val] ? _srCancelActionMeta[val].desc : '';
                    $('#swalSRCancelDesc').html(desc);
                });
                $(document).on('click', '#swalSRChangeAction', function () {
                    $('#swalSRPresetWrap').addClass('d-none');
                    $('#swalSRChangeWrap').removeClass('d-none');
                });
            },
            willClose: function () {
                $(document).off('change', '#swalSRCancelAction');
                $(document).off('click', '#swalSRChangeAction');
            },
            preConfirm: function () {
                if (!deps.HasRefunds) return '';
                if (_srCancelSetting !== 'ask') return _srCancelSetting;
                var chosen = $('#swalSRCancelAction').val();
                if (!chosen) {
                    Swal.showValidationMessage('Please select an action for the paid-out refund.');
                    return false;
                }
                return chosen;
            }
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showProcessing('Cancelling Sales Return…');
            $.ajax({
                url   : '/salesreturns/updateSalesReturnStatus',
                method: 'POST',
                data  : { TransUID: uid, Status: 'Cancelled', CancelPaymentAction: r.value || '', [CsrfName]: CsrfToken },
                success: function (resp) {
                    hideProcessing();
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', title: 'Cannot Cancel', html: resp.Message, confirmButtonColor: '#dc3545' });
                    } else {
                        getSalesReturnsDetails();
                        if (resp.CustomerBalance !== undefined) {
                            var bal = parseFloat(resp.CustomerBalance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            var typ = resp.CustomerBalanceType === 'Debit' ? 'receivable <small>(customer owes you)</small>' : 'advance <small>(you owe customer)</small>';
                            Swal.fire({
                                icon : 'success',
                                title: 'Sales Return Cancelled',
                                html : 'Sales return cancelled successfully.<br><br>'
                                     + '<div style="background:#f8f9fa;border-radius:10px;padding:14px 20px;margin-top:4px;display:inline-block;min-width:220px;">'
                                     + '<div style="font-size:12px;color:#6c757d;margin-bottom:4px;">Updated Customer Balance</div>'
                                     + '<div style="font-size:22px;font-weight:700;color:' + (resp.CustomerBalanceType === 'Debit' ? '#dc3545' : '#198754') + ';">'
                                     + sym + bal + '</div>'
                                     + '<div style="font-size:11px;color:#6c757d;margin-top:2px;">' + typ + '</div>'
                                     + '</div>',
                                confirmButtonColor: '#0d6efd'
                            });
                        } else {
                            showToastNotification('Sales Return cancelled successfully.', 'success');
                        }
                    }
                },
                error: function () { hideProcessing(); Swal.fire({ icon: 'error', text: 'Request failed. Try again.' }); }
            });
        });
    }

    $(document).on('click', '.deleteSalesReturn', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({ title: 'Delete Sales Return?', html: num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                showProcessing('Deleting Sales Return…');
                $.ajax({ url: '/salesreturns/deleteSalesReturn', method: 'POST', data: { TransUID: uid, [CsrfName]: CsrfToken },
                    success: function (resp) {
                        hideProcessing();
                        if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); } else { getSalesReturnsDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                    },
                    error: function () { hideProcessing(); Swal.fire({ icon: 'error', text: 'Request failed. Try again.' }); }
                });
            });
    });


    $(document).on('change', '.srHeaderCheck', function () { $('.srCheck').prop('checked', $(this).is(':checked')); });

    // ── Refund Payment (Sales Return) ──────────────────────
    $(document).on('click', '.srReceivePayment', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $el     = $(this);
        var uid     = $el.data('uid')     || 0;
        var num     = $el.data('num')     || '';
        var date    = $el.data('date')    || '';
        var party   = $el.data('party')   || '';
        var total   = parseFloat($el.data('total'))   || 0;
        var paid    = parseFloat($el.data('paid'))    || 0;
        var pending = parseFloat($el.data('pending')) || 0;
        var _cur    = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

        $('#rpTransUID').val(uid);
        $('#rpInvNum').text(num || '—');
        $('#rpInvDate').text(date || '—');
        $('#rpPartyName').text(party || '—');
        $('#rpTotalCard').text(_cur + ' ' + total.toFixed(2));
        $('#rpPaidCard').text(_cur + ' ' + paid.toFixed(2));
        $('#rpBalanceCard').text(_cur + ' ' + pending.toFixed(2));
        $('#rpAmount').val(pending.toFixed(2)).attr('max', pending);
        $('#rpCurrencySymbol').text(_cur);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('recordPaymentModal')).show();
    });

});

// ── Detail HTML builder ─────────────────────────────────────────
function _buildSrDetailHtml(resp) {
    window._srLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-undo',
        typeColor   : '#198754',
        typeBg      : '#e8f5e9',
        hasPayments : true,
    });
}

// ── Payment Modal Init & Submit ──────────────────────────────────
(function () {
    'use strict';

    var _payTypes  = <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>;
    var _bankAccts = <?php echo json_encode(array_values(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
    }, array_filter($BankAccounts ?? [], function($b) { return !(int)$b->IsCash; })))); ?>;
    var _fpInstance = null;
    var _rpDropzone = null;
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    (function () {
        var $sel = $('#rpBankAccount').empty().append('<option value="">— Select bank account —</option>');
        $.each(_bankAccts, function (i, b) {
            $sel.append('<option value="' + b.BankAccountUID + '">' + _esc(b.BankName) + ' — ' + _esc(b.AccountName) + '</option>');
        });
    }());

    function renderPaymentTypes() {
        var $wrap = $('#rpPaymentTypes').empty();
        $.each(_payTypes, function (i, t) {
            var active = (i === 0) ? ' active' : '';
            if (i === 0) { $('#rpPaymentTypeUID').val(t.PaymentTypeUID); $('#rpIsCash').val(t.IsCash); }
            $wrap.append('<button type="button" class="rp-type-pill btn btn-sm btn-outline-secondary' + active + '" data-uid="' + t.PaymentTypeUID + '" data-iscash="' + t.IsCash + '">' + _esc(t.Name) + '</button>');
        });
        toggleBankRow();
    }

    function toggleBankRow() {
        var isCash = parseInt($('#rpIsCash').val(), 10);
        $('#rpBankRow').toggleClass('d-none', !!isCash);
        if (!isCash && !$('#rpBankAccount').val()) {
            var def = $.grep(_bankAccts, function(b) { return b.IsDefault === 1; });
            if (def.length) { $('#rpBankAccount').val(def[0].BankAccountUID); }
        }
    }

    $('#recordPaymentModal').on('shown.bs.modal', function () {
        if (!_fpInstance) {
            _fpInstance = flatpickr('#rpPaymentDate', {
                dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                maxDate: 'today', disableMobile: true, defaultDate: 'today',
                appendTo: document.querySelector('#recordPaymentModal .modal-dialog'),
            });
        } else {
            _fpInstance.setDate(new Date(), false);
        }
        if (typeof Dropzone !== 'undefined') {
            var _dzEl = document.querySelector('#rpAttachDropzone');
            if (_dzEl && _dzEl.dropzone) {
                _rpDropzone = _dzEl.dropzone;
            } else if (!_rpDropzone) {
                Dropzone.autoDiscover = false;
                _rpDropzone = new Dropzone('#rpAttachDropzone', {
                    url: '#', autoProcessQueue: false, maxFiles: 3, maxFilesize: 3,
                    acceptedFiles: '.pdf,.jpg,.jpeg,.png', parallelUploads: 3, clickable: true,
                    previewTemplate: '<div class="dz-preview dz-file-preview"><div class="dz-details"><div class="dz-filename"><span data-dz-name></span></div><div class="dz-size"><span data-dz-size></span></div></div><div class="dz-error-message"><span data-dz-errormessage></span></div><a class="dz-remove" href="javascript:undefined;" data-dz-remove>Remove</a></div>',
                    init: function () {
                        this.on('maxfilesexceeded', function (file) { this.removeFile(file); Swal.fire({ icon: 'warning', text: 'Maximum 3 attachments allowed.' }); });
                    }
                });
            }
        }
        renderPaymentTypes();
    });

    $(document).on('click', '.rp-type-pill', function () {
        $('.rp-type-pill').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
        $('#rpPaymentTypeUID').val($(this).data('uid'));
        $('#rpIsCash').val($(this).data('iscash'));
        toggleBankRow();
    });

    $('#btnSubmitPayment').on('click', function () {
        var transUID       = parseInt($('#rpTransUID').val(), 10);
        var paymentTypeUID = parseInt($('#rpPaymentTypeUID').val(), 10);
        var amount         = parseFloat($('#rpAmount').val()) || 0;
        var paymentDate    = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
        var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
        var referenceNo    = $.trim($('#rpReferenceNo').val());
        var notes          = $.trim($('#rpNotes').val());

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid sales return.' }); return; }
        if (!paymentTypeUID) { Swal.fire({ icon: 'warning', text: 'Please select a payment type.' }); return; }
        if (amount <= 0)     { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }
        var isCash = parseInt($('#rpIsCash').val(), 10);
        if (!isCash && !bankAccountUID) { Swal.fire({ icon: 'warning', text: 'Please select a bank account.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');
        var fd = new FormData();
        fd.append('TransUID',       transUID);
        fd.append('PaymentTypeUID', paymentTypeUID);
        fd.append('Amount',         amount);
        fd.append('PaymentDate',    paymentDate);
        fd.append('BankAccountUID', bankAccountUID || '');
        fd.append('ReferenceNo',    referenceNo);
        fd.append('Notes',          notes);
        fd.append(CsrfName,         CsrfToken);
        if (_rpDropzone) { _rpDropzone.files.forEach(function(file) { fd.append('PaymentFiles[]', file); }); }

        $.ajax({
            url: '/salesreturns/recordPayment', method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Refund Payment');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal')).hide();
                    getSalesReturnsDetails();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Refund Payment');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

}());

// ── Apply Credit to Invoice ──────────────────────────────────────
(function () {
    'use strict';
    var _acCur     = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var _acDec     = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    var _acBalance = 0;
    var _acSelect2 = null;

    $(document).on('click', '.srApplyCredit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $el     = $(this);
        var srUID   = $el.data('uid')     || 0;
        var num     = $el.data('num')     || '—';
        var party   = $el.data('party')   || '—';
        var balance = parseFloat($el.data('balance')) || 0;

        _acBalance = balance;
        $('#acSalesReturnUID').val(srUID);
        $('#acSrNum').text(num);
        $('#acPartyName').text(party);
        $('#acCreditBalance').text(_acCur + ' ' + balance.toFixed(_acDec));
        $('#acCurrencySymbol').text(_acCur);
        $('#acAmount').val(balance.toFixed(_acDec)).attr('max', balance);
        $('#acNotes').val('');
        $('#acInvoiceInfo').addClass('d-none');

        var $sel = $('#acInvoiceUID').empty().append('<option value="">— Loading… —</option>');
        if (_acSelect2) { try { _acSelect2.destroy(); } catch(ex){} _acSelect2 = null; }

        $.ajax({
            url: '/salesreturns/getPendingInvoices', method: 'POST',
            data: { SalesReturnUID: srUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                $sel.empty().append('<option value="">— Select Invoice —</option>');
                if (!resp.Error && resp.Invoices && resp.Invoices.length) {
                    $.each(resp.Invoices, function (i, inv) {
                        var bal = parseFloat(inv.BalanceAmount) || 0;
                        $sel.append('<option value="' + inv.TransUID
                            + '" data-total="'   + inv.NetAmount
                            + '" data-paid="'    + inv.PaidAmount
                            + '" data-balance="' + bal + '">'
                            + _esc(inv.UniqueNumber) + ' — ' + _acCur + ' ' + bal.toFixed(_acDec) + ' pending'
                            + '</option>');
                    });
                } else {
                    $sel.append('<option value="" disabled>No pending invoices found</option>');
                }
                if (typeof $.fn.select2 !== 'undefined') {
                    _acSelect2 = $sel.select2({ dropdownParent: $('#applyCreditModal'), placeholder: '— Select Invoice —' });
                }
            }
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('applyCreditModal')).show();
    });

    $(document).on('change', '#acInvoiceUID', function () {
        var $opt    = $(this).find('option:selected');
        var invUID  = parseInt($(this).val(), 10) || 0;
        if (!invUID) { $('#acInvoiceInfo').addClass('d-none'); return; }
        var total   = parseFloat($opt.data('total'))   || 0;
        var paid    = parseFloat($opt.data('paid'))    || 0;
        var balance = parseFloat($opt.data('balance')) || 0;
        $('#acInvTotal').text(_acCur + ' ' + total.toFixed(_acDec));
        $('#acInvPaid').text(_acCur + ' ' + paid.toFixed(_acDec));
        $('#acInvBalance').text(_acCur + ' ' + balance.toFixed(_acDec));
        $('#acInvoiceInfo').removeClass('d-none');
        var maxApply = Math.min(_acBalance, balance);
        $('#acAmount').val(maxApply.toFixed(_acDec)).attr('max', maxApply);
    });

    $('#btnSubmitApplyCredit').on('click', function () {
        var srUID      = parseInt($('#acSalesReturnUID').val(), 10) || 0;
        var invoiceUID = parseInt($('#acInvoiceUID').val(), 10) || 0;
        var amount     = parseFloat($('#acAmount').val()) || 0;
        var notes      = $.trim($('#acNotes').val());

        if (!srUID)      { Swal.fire({ icon: 'warning', text: 'Invalid sales return.' }); return; }
        if (!invoiceUID) { Swal.fire({ icon: 'warning', text: 'Please select an invoice.' }); return; }
        if (amount <= 0) { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Applying…');
        $.ajax({
            url: '/salesreturns/applyCredit', method: 'POST',
            data: { SalesReturnUID: srUID, InvoiceUID: invoiceUID, Amount: amount, Notes: notes, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Apply Credit');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('applyCreditModal')).hide();
                    getSalesReturnsDetails();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Apply Credit');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

    $('#applyCreditModal').on('hidden.bs.modal', function () {
        if (_acSelect2) { try { _acSelect2.destroy(); } catch(ex){} _acSelect2 = null; }
        $('#acInvoiceUID').empty().append('<option value="">— Select Invoice —</option>');
        $('#acInvoiceInfo').addClass('d-none');
    });

}());
</script>
