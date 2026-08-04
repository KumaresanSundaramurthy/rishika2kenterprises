<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Quotations',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <?php
                $initTab    = $InitTab    ?? 'All';
                $initSearch = $InitSearch ?? '';
                $tabFilterMap = [
                    'All'       => ['quotCreatedByFilter', 'quotPartyFilterTrigger'],
                    'Open'      => ['quotCreatedByFilter', 'quotPartyFilterTrigger'],
                    'Accepted'  => ['quotCreatedByFilter', 'quotPartyFilterTrigger'],
                    'Converted' => ['quotCreatedByFilter', 'quotPartyFilterTrigger'],
                    'Cancelled' => ['quotCreatedByFilter', 'quotPartyFilterTrigger'],
                    'Draft'     => ['quotCreatedByFilter', 'quotPartyFilterTrigger'],
                ];
                $visibleFilters = $tabFilterMap[$initTab] ?? $tabFilterMap['All'];

                if ($JwtData->TransSettings->ShowTransactionStats ?? 1):
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
                $cntCancelled = ($stats['Cancelled']['count'] ?? 0) + ($stats['Rejected']['count'] ?? 0);
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
                    <div class="apex-stat-item <?php echo $stat['status'] === $initTab ? 'active' : ''; ?>" data-status="<?php echo $stat['status']; ?>" data-stat-filter="<?php echo $stat['status']; ?>" style="--stat-color:<?php echo $stat['iconColor']; ?>">
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
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Main Card ────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap<?php echo $initSearch ? ' is-expanded r2k-search-active' : ''; ?>">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Quot. # or customer..." value="<?php echo htmlspecialchars($initSearch, ENT_QUOTES); ?>">
                                <i class="bx bx-x r2k-clear<?php echo $initSearch ? '' : ' d-none'; ?>" id="clearQuotSearch"></i>
                            </div>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="quotCreatedByFilter" class="apex-filter-btn<?php echo in_array('quotCreatedByFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="quotPartyFilterTrigger" class="apex-filter-btn<?php echo in_array('quotPartyFilterTrigger', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Customer"><i class="bx bx-store me-1"></i>Customer</a>
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
                            <a href="/quotations/create" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_quotation', 'Create Quotation'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="quotStatusTabs" role="tablist" data-trans-path="/quotations">
                                <?php
                                $tabs = [
                                    ['label' => 'All',       'status' => 'All',       'urlTab' => 'all'],
                                    ['label' => 'Pending',   'status' => 'Open',      'urlTab' => 'open'],
                                    ['label' => 'Accepted',  'status' => 'Accepted',  'urlTab' => 'accepted'],
                                    ['label' => 'Converted', 'status' => 'Converted', 'urlTab' => 'converted'],
                                    ['label' => 'Cancelled', 'status' => 'Cancelled', 'urlTab' => 'cancelled'],
                                    ['label' => 'Drafts',    'status' => 'Draft',     'urlTab' => 'draft'],
                                ];
                                foreach ($tabs as $tab):
                                    $isActive = ($initTab === $tab['status']);
                                    $tc       = $isActive ? $ModAllCount : 0;
                                ?>
                                <li class="nav-item"><a class="nav-link <?php echo $isActive ? 'active' : ''; ?> quot-status-tab" data-status="<?php echo $tab['status']; ?>" data-url-tab="<?php echo $tab['urlTab']; ?>" href="javascript:void(0);"><?php echo $tab['label']; ?> <span class="trans-tab-count ms-1<?php echo $tc == 0 ? ' d-none' : ''; ?>"><?php echo $tc ?: ''; ?></span></a></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Select-all banner -->
                        <div id="quotSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="quotSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="quotSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="quotSelectAllClear" class="ms-2 d-none">Clear selection</a>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Table -->
                        <style>
                            #quotTable.quot-draft-mode .quot-col-status,
                            #quotTable.quot-draft-mode .quot-col-valid-until { display: none; }
                        </style>
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0<?= $initTab === 'Draft' ? ' quot-draft-mode' : ''; ?>" id="quotTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox quotHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Quotation # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th class="quot-col-status">Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none quot-col-valid-until" data-sort="Date" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
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
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center quotPagination apex-pag-sticky" id="quotPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>


                </div>
            </div>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'quotCreatedByFilterBox',
        'triggerId'  => 'quotCreatedByFilter',
        'checkClass' => 'quot-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'quotPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/attachments.js"></script>
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

var _quotInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _quotInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;

var _quotTabFilterMap = <?= json_encode($tabFilterMap); ?>;
var _allQuotFilterEls = <?= json_encode(array_values(array_unique(array_merge(...array_values($tabFilterMap))))); ?>;
var _initPage         = <?php echo (int)($InitPage ?? 1); ?>;

$(function () {
    'use strict';

    _checkPendingToast('_quotPendingToast');
    PageNo = _initPage;
    Filter['Status'] = _quotInitTab;
    if (_quotInitSearch) { Filter.Name = _quotInitSearch; }
    initExport({ moduleUID: 101, getFilters: function () {
        return $.extend({}, Filter,
            tfb                 ? tfb.getState()                 : {},
            quotCreatedByFilter ? quotCreatedByFilter.getState() : {},
            quotPartyFilter     ? quotPartyFilter.getState()     : {}
        );
    } });
    _applyTabFilters(_quotInitTab, _quotTabFilterMap, _allQuotFilterEls);

    // ── Filter bar ──────────────────────────────────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getQuotationsDetails(); } })
        : null;

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
    getQuotationsDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            tfb                 ? tfb.getState()                 : {},
            quotCreatedByFilter ? quotCreatedByFilter.getState() : {},
            quotPartyFilter     ? quotPartyFilter.getState()     : {}
        );
        _origGetQuotationsDetails(pageNo, rowLimit, f, afterLoad);
    };

    // ── Create / Edit — inject returnTab + returnPage ──────────────────
    $(document).on('click', 'a[href="/quotations/create"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = '/quotations/create?' + params.toString();
    });
    $(document).on('click', 'a[href^="/quotations/edit/"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = $(this).attr('href') + '?' + params.toString();
    });

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var statFilter = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.quot-status-tab').removeClass('active');
        $('.quot-status-tab[data-status="' + statFilter + '"]').addClass('active');
        _resetQuotFilters();
        _applyTabFilters(statFilter, _quotTabFilterMap, _allQuotFilterEls);
        _updateTransTabUrl(statFilter, '');
        $('#quotTable').toggleClass('quot-draft-mode', statFilter === 'Draft');
        Filter.Status = statFilter;
        PageNo = 1;
        getQuotationsDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.quot-status-tab', function (e) {
        e.preventDefault();
        SelectedUIDs = []; _quotClearSelectAll(); MultipleDeleteOption();
        var status = $(this).data('status') || 'All';
        $('.quot-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        _resetQuotFilters();
        _applyTabFilters(status, _quotTabFilterMap, _allQuotFilterEls);
        _updateTransTabUrl(status, '');
        $('#quotTable').toggleClass('quot-draft-mode', status === 'Draft');
        Filter.Status = status;
        PageNo = 1;
        getQuotationsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getQuotationsDetails();
    });

    $('#searchTransactionData').on('input', function () {
        var curTab = $('.quot-status-tab.active').data('status') || 'All';
        _updateTransTabUrl(curTab, $.trim($(this).val()));
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
        var curTab = $('.quot-status-tab.active').data('status') || 'All';
        $('#searchTransactionData').val('');
        $(this).addClass('d-none');
        delete Filter.Name;
        _updateTransTabUrl(curTab, '');
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
        getQuotationsDetails();
    });

    $(document).on('click', '.quotPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _quotClearSelectAll(); getQuotationsDetails(); }
    });

    function _resetQuotFilters() {
        var $wrap = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val('');
        $wrap.find('.r2k-clear').addClass('d-none');
        $wrap.removeClass('is-expanded r2k-search-active');
        delete Filter.Name;
        if (quotCreatedByFilter) { quotCreatedByFilter.reset(); }
        if (quotPartyFilter)     { quotPartyFilter.reset(); }
        if (tfb)                 { tfb.reset(); }
        $('.trans-col-filterbox, .tpcf-box').hide();
    }

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

        if (status === 'Cancelled' && !$(this).data('_confirmed')) {
            var num = $(this).data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this quotation';
            var $btn = $(this);
            Swal.fire({ title: 'Cancel Quotation?', html: 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It' }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        $(this).removeData('_confirmed');
        // All other status changes (and confirmed Cancelled)
        $.ajax({
            url   : '/quotations/updateQuotationStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                var _msg = resp.Message || 'Status updated.';
                getQuotationsDetails(undefined, undefined, undefined, function () {
                    showToastNotification(_msg, 'success');
                });
            }
        });
    });

    // View modal — handled by /js/transactions/viewmodal.js (.viewTransaction)

    // ── Delete ───────────────────────────────────────────────
    function _actionPostData(extra) {
        Filter.Status = $('.quot-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $badge = $('.quot-status-tab.active .trans-tab-count');
        if (count > 0) { $badge.text(count).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
        initTooltips();
    }

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
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (PageNo > 1 && (resp.TotalCount || 0) <= (PageNo - 1) * RowLimit) {
                        PageNo--;
                        getQuotationsDetails();
                    } else {
                        _renderListResponse(resp);
                    }
                }
            });
        });
    });

    // ── Checkbox / select-all wiring ─────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(ModuleHeader).on('click', function () {
        _quotUpdateSelectAllBanner();
    });

    $(document).on('change', ModuleRow, function () {
        onClickOfCheckbox(this, ModuleTable, ModuleHeader, ModuleRow);
        _quotUpdateSelectAllBanner();
        MultipleDeleteOption();
    });

    $(document).on('click', '#quotSelectAllLink', function (e) {
        e.preventDefault();
        _quotSelectAllMode = true;
        _quotUpdateSelectAllBanner();
    });

    $(document).on('click', '#quotSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _quotClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Bulk delete ───────────────────────────────────────────────────────
    $('#btnDelete').on('click', function () {
        var count = _quotSelectAllMode ? _quotTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' quotation' + (count === 1 ? '' : 's') + '?',
            text : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            deleteMultipleQuotations();
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
