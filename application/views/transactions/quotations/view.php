<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Quotations',
                    'pageDescription' => $PageDescription ?? 'Create and send sales quotations to customers',
                ]); ?>
                <?php
                $stats        = $SummaryStats ?? [];
                $cur          = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec          = $JwtData->GenSettings->DecimalPoints ?? 2;

                $cntAll       = array_sum(array_column(
                    array_filter($stats, fn($k) => !in_array($k, ['Draft','Cancelled','Rejected']), ARRAY_FILTER_USE_KEY),
                    'count'
                ));
                $amtAll       = array_sum(array_column(
                    array_filter($stats, fn($k) => !in_array($k, ['Draft','Cancelled','Rejected']), ARRAY_FILTER_USE_KEY),
                    'amount'
                ));
                $cntOpen      = $stats['Pending']['count']    ?? 0;
                $amtOpen      = $stats['Pending']['amount']   ?? 0;
                $cntAccepted  = $stats['Accepted']['count']   ?? 0;
                $amtAccepted  = $stats['Accepted']['amount']  ?? 0;
                $cntConverted = $stats['Converted']['count']  ?? 0;
                $amtConverted = $stats['Converted']['amount'] ?? 0;
                $cntDraft     = $stats['Draft']['count']      ?? 0;

                $statsItems = [
                    ['label' => 'All Quotations', 'status' => 'All',       'icon' => 'bx-file',         'iconBg' => '#f0fdf4', 'iconColor' => '#22c55e', 'count' => $cntAll,       'amount' => $amtAll],
                    ['label' => 'Open',           'status' => 'Open',      'icon' => 'bx-send',         'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $cntOpen,      'amount' => $amtOpen],
                    ['label' => 'Accepted',       'status' => 'Accepted',  'icon' => 'bx-check-circle', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntAccepted,  'amount' => $amtAccepted],
                    ['label' => 'Converted',      'status' => 'Converted', 'icon' => 'bx-transfer-alt', 'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntConverted, 'amount' => $amtConverted],
                    ['label' => 'Drafts',         'status' => 'Draft',     'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,     'amount' => 0],
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

                    <!-- ── Main Card ────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Quot. # or customer...">
                                <i class="bx bx-x r2k-clear d-none" id="clearQuotSearch"></i>
                            </div>
                            <a href="javascript:void(0);" id="quotStatusFilter" class="apex-filter-btn" title="Filter by Status"><i class="bx bx-transfer-alt me-1"></i>Status</a>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="quotCreatedByFilter" class="apex-filter-btn" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="quotPartyFilterTrigger" class="apex-filter-btn" title="Filter by Customer"><i class="bx bx-store me-1"></i>Customer</a>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/quotations/create" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>New Quotation</a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="quotStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active quot-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Draft" href="javascript:void(0);">Draft <span class="trans-tab-count d-none"></span></a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="quotTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox quotHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Quotation # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Valid Until <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-1 justify-content-between align-items-center quotPagination" id="quotPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="quotStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-1 justify-content-between align-items-center quotPagination"></div>
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
        'id'         => 'quotStatusFilterBox',
        'triggerId'  => 'quotStatusFilter',
        'title'      => 'Status',
        'icon'       => 'bx-transfer-alt',
        'filterKey'  => 'StatusList',
        'checkClass' => 'quot-status-chk',
        'items'      => [
            ['value' => 'Pending',   'label' => 'Pending'],
            ['value' => 'Accepted',  'label' => 'Accepted'],
            ['value' => 'Converted', 'label' => 'Converted'],
        ],
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/partials/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'quotCreatedByFilterBox',
        'triggerId'  => 'quotCreatedByFilter',
        'checkClass' => 'quot-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'quotPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/common/party_filter.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/quotations.js"></script>

<script>
// ── Comm modal pre-loaded data (eliminates getCommTemplate AJAX call) ─────────
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

const ModuleId     = 101;
const ModuleTable  = '#quotTable';
const ModulePag    = '.quotPagination';
const ModuleHeader = '.quotHeaderCheck';
const ModuleRow    = '.quotationCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';
    initExport({ moduleUID: 101, getFilters: function () { return Filter; } });

    // ── Filter bar ──────────────────────────────────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getQuotationsDetails(); } })
        : null;

    var quotStatusFilter = new TransColFilter({
        boxId       : 'quotStatusFilterBox',
        triggerId   : 'quotStatusFilter',
        filterKey   : 'StatusList',
        activeClass : 'has-filter',
        onApply     : function () {
            var vals = quotStatusFilter.getState()['StatusList'] || [];
            if (vals.length) Filter['StatusList'] = vals; else delete Filter['StatusList'];
            PageNo = 1;
            getQuotationsDetails();
        }
    });

    var quotCreatedByFilter = (document.getElementById('quotCreatedByFilterBox'))
        ? new TransColFilter({
            boxId       : 'quotCreatedByFilterBox',
            triggerId   : 'quotCreatedByFilter',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () { PageNo = 1; getQuotationsDetails(); }
        })
        : null;

    var quotPartyFilter = new TransPartyColFilter({
        boxId     : 'quotPartyFilterBox',
        triggerId : 'quotPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getQuotationsDetails(); }
    });

    var _origGetQuotationsDetails = getQuotationsDetails;
    getQuotationsDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            tfb                 ? tfb.getState()                 : {},
            quotStatusFilter    ? quotStatusFilter.getState()    : {},
            quotCreatedByFilter ? quotCreatedByFilter.getState() : {},
            quotPartyFilter     ? quotPartyFilter.getState()     : {}
        );
        _origGetQuotationsDetails(pageNo, rowLimit, f);
    };

    // ── Sticky pagination ──
    var $quotStaticPag = $('#quotPagination');
    var $quotStickyPag = $('#quotStickyPagination');
    function _syncQuotSticky() { $quotStickyPag.find('.quotPagination').html($quotStaticPag.html()); }
    function _toggleQuotSticky() {
        if (!$quotStaticPag.length) return;
        var r = $quotStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $quotStickyPag.stop(true,true).fadeOut(150); }
        else { _syncQuotSticky(); $quotStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleQuotSticky);
    _toggleQuotSticky();

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var statFilter = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        delete Filter['StatusList'];
        Filter.Status = 'All';
        $('.quot-status-tab').removeClass('active');
        if (statFilter === 'Cancelled' || statFilter === 'Draft') {
            Filter.Status = statFilter;
            quotStatusFilter.reset();
            $('.quot-status-tab[data-status="' + statFilter + '"]').addClass('active');
        } else if (statFilter === 'Open') {
            quotStatusFilter.setState(['Pending']);
            $('.quot-status-tab[data-status="All"]').addClass('active');
        } else if (statFilter === 'Accepted' || statFilter === 'Converted') {
            quotStatusFilter.setState([statFilter]);
            $('.quot-status-tab[data-status="All"]').addClass('active');
        } else {
            quotStatusFilter.reset();
            $('.quot-status-tab[data-status="All"]').addClass('active');
        }
        PageNo = 1;
        getQuotationsDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.quot-status-tab', function (e) {
        e.preventDefault();
        $('.quot-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        Filter.Status = status;
        quotStatusFilter.reset();
        PageNo = 1;
        getQuotationsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getQuotationsDetails();
    });

    $('#searchTransactionData').on('input', debounce(function () {
        var val = $.trim($(this).val());
        if (val === '') {
            $('#clearQuotSearch').addClass('d-none');
            delete Filter.Name;
            PageNo = 1;
            getQuotationsDetails();
            return;
        }
        $('#clearQuotSearch').removeClass('d-none');
        Filter.Name = val;
        PageNo = 1;
        getQuotationsDetails();
    }, 1500));

    $('#clearQuotSearch').on('click', function () {
        $('#searchTransactionData').val('');
        $(this).addClass('d-none');
        delete Filter.Name;
        PageNo = 1;
        getQuotationsDetails();
    });

    // ── Date filter ──────────────────────────────────────────
    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getQuotationsDetails();
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
        getQuotationsDetails();
    });

    $(document).on('click', '.quotPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getQuotationsDetails(); }
    });

    // ── Inline status update ────────────────────────────────
    $(document).on('click', '.quot-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        var target = $(this).data('target') || '';

        // Conversion actions — redirect to create form, do NOT change status here
        if (status === 'Converted') {
            $.ajax({
                url   : '/quotations/convertQuotationToInvoice',
                method: 'POST',
                data  : { TransUID: uid, ConvertTarget: target, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        window.location.href = resp.RedirectURL;
                    }
                }
            });
            return;
        }

        if ($(this).data('_confirmed')) { $(this).removeData('_confirmed'); return; }
        if (status === 'Cancelled') {
            var num = $(this).data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this quotation';
            var $btn = $(this);
            Swal.fire({ title: 'Cancel Quotation?', html: 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It' }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        // All other status changes
        $.ajax({
            url   : '/quotations/updateQuotationStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                else            { getQuotationsDetails(); }
            }
        });
    });

    // View modal — handled by /js/transactions/viewmodal.js (.viewTransaction)

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deleteQuotation', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Quotation?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/quotations/deleteQuotation',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getQuotationsDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                }
            });
        });
    });

    $(document).on('change', '.quotHeaderCheck', function () {
        $('.quotationCheck').prop('checked', $(this).is(':checked'));
    });


});

function _buildQuotDetailHtml(resp) {
    window._quotLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-file-blank',
        typeColor   : '#0891b2',
        typeBg      : '#e0f5fb',
        hasPayments : false,
        validLabel  : 'Valid Until',
    });
}

function _stripHtml(v) {
    if (!v) return '';
    return $('<div>').html(String(v)).text().trim();
}
</script>
