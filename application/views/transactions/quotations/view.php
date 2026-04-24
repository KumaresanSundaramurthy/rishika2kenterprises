<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats        = $SummaryStats ?? [];
                    $cur          = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec          = $JwtData->GenSettings->DecimalPoints ?? 2;

                    // All = everything except Draft, Cancelled, Rejected
                    $cntAll       = array_sum(array_column(
                        array_filter($stats, fn($k) => !in_array($k, ['Draft','Cancelled','Rejected']), ARRAY_FILTER_USE_KEY),
                        'count'
                    ));
                    $amtAll       = array_sum(array_column(
                        array_filter($stats, fn($k) => !in_array($k, ['Draft','Cancelled','Rejected']), ARRAY_FILTER_USE_KEY),
                        'amount'
                    ));

                    // Open = Pending
                    $cntOpen      = $stats['Pending']['count']   ?? 0;
                    $amtOpen      = $stats['Pending']['amount']  ?? 0;

                    // Accepted
                    $cntAccepted  = $stats['Accepted']['count']  ?? 0;
                    $amtAccepted  = $stats['Accepted']['amount'] ?? 0;

                    // Draft
                    $cntDraft     = $stats['Draft']['count']     ?? 0;

                    // Converted
                    $cntConverted = $stats['Converted']['count']  ?? 0;
                    $amtConverted = $stats['Converted']['amount'] ?? 0;

                    // Cancelled = Cancelled + Rejected combined
                    $cntCancelled = ($stats['Cancelled']['count'] ?? 0) + ($stats['Rejected']['count'] ?? 0);

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="row g-3 mb-2">
                        <div class="col-6 col-md">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="trans-stat-label">All Quotations</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                <i class="bx bx-file trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md">
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Open">
                                <div class="trans-stat-label">Open</div>
                                <div class="trans-stat-count"><?php echo number_format($cntOpen); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtOpen, $cur, $dec); ?></div>
                                <i class="bx bx-send trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md">
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Accepted">
                                <div class="trans-stat-label">Accepted</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAccepted); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAccepted, $cur, $dec); ?></div>
                                <i class="bx bx-check-circle trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md">
                            <a href="javascript:void(0);" class="trans-stat-card stat-converted" data-stat-filter="Converted">
                                <div class="trans-stat-label">Converted</div>
                                <div class="trans-stat-count"><?php echo number_format($cntConverted); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtConverted, $cur, $dec); ?></div>
                                <i class="bx bx-transfer-alt trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md">
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="trans-stat-label">Drafts</div>
                                <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-pencil trans-stat-icon"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-tabs">
                                <ul class="nav trans-status-tabs" id="quotStatusTabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active quot-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count"><?php echo $ModAllCount; ?></span></a></li>
                                    <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Open" href="javascript:void(0);">Open <span class="trans-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Accepted" href="javascript:void(0);">Accepted <span class="trans-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Converted" href="javascript:void(0);">Converted <span class="trans-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <?php if ($cntCancelled > 0): ?><span class="trans-tab-count"><?php echo $cntCancelled; ?></span><?php else: ?><span class="trans-tab-count d-none"></span><?php endif; ?></a></li>
                                    <li class="nav-item"><a class="nav-link quot-status-tab" data-status="Draft" href="javascript:void(0);">Draft <span class="trans-tab-count d-none"></span></a></li>
                                </ul>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="javascript:void(0);" class="r2k-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="searchTransactionData" placeholder="Quot. # or customer...">
                                    <i class="bx bx-x r2k-clear d-none" id="clearQuotSearch"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="r2k-dd-btn" type="button" id="dateFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        <i class="bx bx-calendar"></i> <span id="dateFilterLabel">All Dates</span> <i class="bx bx-chevron-down" style="font-size:.75rem;"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" id="dateFilterMenu" style="width:240px;max-height:420px;overflow-y:auto;font-size:.82rem;z-index:9999;">
                                    </ul>
                                </div>
                                <a href="/quotations/create" class="r2k-create-btn"><i class="bx bx-plus"></i> Create</a>
                            </div>
                        </div>

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
                        <div class="row mx-3 my-2 justify-content-between align-items-center quotPagination" id="quotPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/quotations.js"></script>

<script>
const ModuleId     = 101;
const ModuleTable  = '#quotTable';
const ModulePag    = '.quotPagination';
const ModuleHeader = '.quotHeaderCheck';
const ModuleRow    = '.quotationCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.quot-status-tab').removeClass('active');
        $('.quot-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getQuotationsDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.quot-status-tab', function (e) {
        e.preventDefault();
        $('.quot-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getQuotationsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getQuotationsDetails();
    });

    $('#searchTransactionData').on('keyup', debounce(function () {
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
    }, 400));

    $('#clearQuotSearch').on('click', function () {
        $('#searchTransactionData').val('');
        $(this).addClass('d-none');
        delete Filter.Name;
        PageNo = 1;
        getQuotationsDetails();
    });

    // ── Date filter ──────────────────────────────────────────
    $('#dateFilterMenu').html(buildDateFilterHtml('customDateFrom', 'customDateTo'));
    initDateFilter({
        btnId  : 'dateFilterBtn',
        labelId: 'dateFilterLabel',
        fromId : 'customDateFrom',
        toId   : 'customDateTo',
        onApply: function (from, to) {
            Filter.DateFrom = from;
            Filter.DateTo   = to;
            PageNo = 1;
            getQuotationsDetails();
        }
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


function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}

function _stripHtml(v) {
    if (!v) return '';
    return $('<div>').html(String(v)).text().trim();
}
</script>
