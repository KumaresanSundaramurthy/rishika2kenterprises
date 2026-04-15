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

                    <!-- ── Print Modal ──────────────────────────────────── -->
                    <div class="modal fade" id="a4PrintModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg" style="border-radius:12px;overflow:hidden;">
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-white">
                                    <div class="fw-semibold" style="font-size:.92rem;">
                                        <i class="bx bx-file-blank text-primary me-1"></i>Quotation Preview
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="a4PaperSize" id="psA4" value="A4" checked>
                                            <label class="form-check-label small fw-semibold" for="psA4">A4</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="a4PaperSize" id="psA5" value="A5">
                                            <label class="form-check-label small fw-semibold" for="psA5">A5</label>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-success px-3" id="a4PrintBtn">
                                            <i class="bx bx-printer me-1"></i>Print
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger px-3" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <div id="a4PrintPreview" style="background:#404040;overflow-y:auto;height:82vh;display:flex;align-items:flex-start;justify-content:center;padding:24px 16px;">
                                    <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                        <div class="spinner-border text-light"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Thermal Print Modal ─────────────────────────────── -->
                    <div class="modal fade" id="thermalPrintModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-top" style="max-width:600px">
                            <div class="modal-content">
                                <div class="modal-header p-3">
                                    <h6 class="modal-title text-primary fw-bold fs-6 mb-0"><i class="bx bx-printer me-1"></i>Thermal Receipt Preview</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-2 bg-white" id="thermalPrintBody">
                                    <div class="d-flex justify-content-center py-5">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>
                                <div class="modal-footer py-2">
                                    <a href="/quotations/thermalPrintConfig" class="btn btn-outline-secondary btn-sm me-auto">
                                        <i class="bx bx-cog me-1"></i>Configure
                                    </a>
                                    <button type="button" class="btn btn-dark btn-sm" id="thermalPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── View Quotation Modal ──────────────────────────── -->
                    <div class="modal fade" id="viewQuotationModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header p-3 d-flex justify-content-between align-items-center">
                                    <h6 class="modal-title fw-semibold text-primary mb-0" id="viewQuotModalTitle">Quotation Details</h6>
                                    <div class="gap-2">
                                        <a href="javascript:void(0);" id="viewQuotEditBtn" class="btn btn-warning btn-sm me-2">
                                            <i class="bx bx-edit me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <div class="modal-body p-0" id="viewQuotModalBody">
                                    <div class="d-flex justify-content-center align-items-center py-5">
                                        <div class="spinner-border text-primary"></div>
                                    </div>
                                </div>
                                <div class="modal-footer py-2"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

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

    // ── View modal ──────────────────────────────────────────
    $(document).on('click', '.viewQuotation', function () {
        var uid = $(this).data('uid');
        $('#viewQuotationModal').modal('show');
        $('#viewQuotModalBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#viewQuotEditBtn').attr('href', '/quotations/edit/' + uid);
        $.ajax({
            url   : '/quotations/getQuotationDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#viewQuotModalBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                } else {
                    $('#viewQuotModalTitle').text('Quotation — ' + (resp.Header.UniqueNumber || 'Details'));
                    $('#viewQuotModalBody').html(_buildQuotDetailHtml(resp));
                }
            },
            error: function () {
                $('#viewQuotModalBody').html('<div class="alert alert-danger m-3">Failed to load quotation.</div>');
            }
        });
    });

    // ── A4 Print ─────────────────────────────────────────────
    $(document).on('click', '.a4PrintQuotation', function () {
        var uid = $(this).data('uid');
        $('#a4PrintModal').modal('show');
        $('#a4PrintPreview').html('<div class="d-flex justify-content-center align-items-center w-100 h-100"><div class="spinner-border text-light"></div></div>');
        $.ajax({
            url   : '/quotations/getQuotationDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { $('#a4PrintPreview').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>'); }
                else { window._quotLastPrintData = resp; $('#a4PrintPreview').html(_buildA4Html(resp, $('input[name="a4PaperSize"]:checked').val() || 'A4')); }
            }
        });
    });
    $('input[name="a4PaperSize"]').on('change', function () {
        if (window._quotLastPrintData) $('#a4PrintPreview').html(_buildA4Html(window._quotLastPrintData, $(this).val()));
    });
    $('#a4PrintBtn').on('click', function () {
        var frame = document.getElementById('a4PrintFrame');
        if (!frame) { frame = document.createElement('iframe'); frame.id = 'a4PrintFrame'; frame.style.display = 'none'; document.body.appendChild(frame); }
        var content = _buildA4Html(window._quotLastPrintData, $('input[name="a4PaperSize"]:checked').val() || 'A4', true);
        frame.contentDocument.open(); frame.contentDocument.write(content); frame.contentDocument.close();
        frame.onload = function () { frame.contentWindow.print(); };
    });

    // ── Download PDF ──────────────────────────────────────────
    $(document).on('click', '.downloadPdfQuotation', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || ('Quotation_' + uid);
        AjaxLoading = 0;
        $.ajax({
            url   : '/quotations/getQuotationDetail',
            method: 'POST',
            data  : { TransUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                AjaxLoading = 1;
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                var html    = _buildA4Html(resp, 'A4', true);
                var blob    = new Blob([html], { type: 'text/html' });
                var url     = URL.createObjectURL(blob);
                var frame   = document.getElementById('_pdfDownloadFrame');
                if (!frame) {
                    frame    = document.createElement('iframe');
                    frame.id = '_pdfDownloadFrame';
                    frame.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;';
                    document.body.appendChild(frame);
                }
                frame.src = url;
                frame.onload = function () {
                    frame.contentWindow.document.title = num;
                    frame.contentWindow.print();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 3000);
                };
            },
            error: function () {
                AjaxLoading = 1;
                Swal.fire({ icon: 'error', text: 'Failed to load quotation data.' });
            }
        });
    });

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
    var h = resp.Header || {}, org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ', dec = h.DecimalPoints || 2;
    var rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr><td class="text-center">' + (i + 1) + '</td><td>' + _esc(item.ProductName) + '</td>' +
            '<td class="text-center">' + _esc(item.Quantity) + ' ' + _esc(item.PrimaryUnitName) + '</td>' +
            '<td class="text-end">' + cur + parseFloat(item.UnitPrice||0).toFixed(dec) + '</td>' +
            '<td class="text-end">' + cur + parseFloat(item.NetAmount||0).toFixed(dec) + '</td></tr>';
    });
    return '<div class="p-3"><div class="row mb-3"><div class="col-md-6"><strong>' + _esc(org.OrgName||'') + '</strong><br>' +
        '<small class="text-muted">' + _esc(h.UniqueNumber||'—') + ' | ' + _esc(h.TransDate||'') + '</small></div>' +
        '<div class="col-md-6 text-end"><strong>Customer:</strong> ' + _esc(h.PartyName||'—') +
        (h.ValidityDate ? '<br><small class="text-muted">Valid Until: ' + _esc(h.ValidityDate) + '</small>' : '') + '</div></div>' +
        '<table class="table table-bordered table-sm"><thead class="table-light"><tr><th>#</th><th>Product</th>' +
        '<th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr></thead>' +
        '<tbody>' + rows + '</tbody><tfoot class="table-light">' +
        '<tr><td colspan="4" class="text-end fw-semibold">Sub Total</td><td class="text-end">' + cur + parseFloat(h.SubTotal||0).toFixed(dec) + '</td></tr>' +
        (parseFloat(h.DiscountAmount)>0 ? '<tr><td colspan="4" class="text-end text-danger">Discount</td><td class="text-end text-danger">- ' + cur + parseFloat(h.DiscountAmount).toFixed(dec) + '</td></tr>' : '') +
        (parseFloat(h.TaxAmount)>0 ? '<tr><td colspan="4" class="text-end">Tax</td><td class="text-end">' + cur + parseFloat(h.TaxAmount).toFixed(dec) + '</td></tr>' : '') +
        '<tr><td colspan="4" class="text-end fw-bold">Net Amount</td><td class="text-end fw-bold">' + cur + parseFloat(h.NetAmount||0).toFixed(dec) + '</td></tr>' +
        '</tfoot></table>' +
        (h.Notes ? '<p class="small text-muted mt-2"><strong>Notes:</strong> ' + _esc(h.Notes) + '</p>' : '') + '</div>';
}

function _buildA4Html(resp, size, forPrint) {
    if (!resp) return '';
    window._quotLastPrintData = resp;
    var w = size === 'A5' ? '148mm' : '210mm';
    var h = resp.Header || {}, org = resp.OrgInfo || {};
    var cur = (org.CurrenySymbol || '₹') + ' ', dec = 2, rows = '';
    (resp.Items || []).forEach(function (item, i) {
        rows += '<tr><td style="text-align:center">' + (i+1) + '</td><td>' + _esc(item.ProductName) + '</td>' +
            '<td style="text-align:center">' + _esc(item.Quantity) + ' ' + _esc(item.PrimaryUnitName) + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.UnitPrice||0).toFixed(dec) + '</td>' +
            '<td style="text-align:right">' + cur + parseFloat(item.NetAmount||0).toFixed(dec) + '</td></tr>';
    });
    var ps = forPrint ? '@media print{body{margin:0}.page{box-shadow:none}}' : '';
    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:Arial,sans-serif;font-size:12px;background:#404040}' +
        '.page{width:' + w + ';background:#fff;margin:0 auto;padding:20px;box-shadow:0 0 20px rgba(0,0,0,.5)}' +
        'table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;font-size:11px}th{background:#f5f5f5}' + ps + '</style></head>' +
        '<body><div class="page"><div style="display:flex;justify-content:space-between;margin-bottom:12px">' +
        '<div><strong style="font-size:14px">' + _esc(org.OrgName||'') + '</strong>' +
        (org.GSTNumber ? '<br><span style="color:#666">GSTIN: ' + _esc(org.GSTNumber) + '</span>' : '') + '</div>' +
        '<div style="text-align:right"><strong style="font-size:16px">QUOTATION</strong><br><span style="color:#666">' + _esc(h.UniqueNumber||'—') + '</span><br>' +
        '<span style="color:#666">Date: ' + _esc(h.TransDate||'') + '</span>' +
        (h.ValidityDate ? '<br><span style="color:#666">Valid Until: ' + _esc(h.ValidityDate) + '</span>' : '') + '</div></div>' +
        '<div style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:12px"><strong>Customer:</strong> ' + _esc(h.PartyName||'—') + '</div>' +
        '<table><thead><tr><th style="width:30px">#</th><th>Product</th><th style="width:60px;text-align:center">Qty</th>' +
        '<th style="width:90px;text-align:right">Unit Price</th><th style="width:90px;text-align:right">Amount</th></tr></thead>' +
        '<tbody>' + rows + '</tbody><tfoot>' +
        '<tr><td colspan="4" style="text-align:right;font-weight:bold">Sub Total</td><td style="text-align:right">' + cur + parseFloat(h.SubTotal||0).toFixed(dec) + '</td></tr>' +
        (parseFloat(h.DiscountAmount)>0 ? '<tr><td colspan="4" style="text-align:right;color:#c00">Discount</td><td style="text-align:right;color:#c00">- ' + cur + parseFloat(h.DiscountAmount).toFixed(dec) + '</td></tr>' : '') +
        (parseFloat(h.TaxAmount)>0 ? '<tr><td colspan="4" style="text-align:right">Tax</td><td style="text-align:right">' + cur + parseFloat(h.TaxAmount).toFixed(dec) + '</td></tr>' : '') +
        '<tr><td colspan="4" style="text-align:right;font-weight:bold">Net Amount</td><td style="text-align:right;font-weight:bold">' + cur + parseFloat(h.NetAmount||0).toFixed(dec) + '</td></tr>' +
        '</tfoot></table>' + (h.TermsConditions ? '<p style="font-size:11px;color:#666;margin-top:12px"><strong>Terms:</strong> ' + _esc(h.TermsConditions) + '</p>' : '') +
        '</div></body></html>';
    return forPrint ? html : '<iframe srcdoc="' + html.replace(/"/g, '&quot;') + '" style="width:100%;height:100%;border:0;min-height:75vh"></iframe>';
}

function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}

// ── Thermal Print ─────────────────────────────────────────────────────────
var _thermalData = null;

$(document).on('click', '.thermalPrintQuotation', function () {
    var uid = $(this).data('uid');
    _thermalData = null;
    $('#thermalPrintBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('thermalPrintModal')).show();
    AjaxLoading = 0;
    $.ajax({
        url   : '/quotations/getQuotationDetail',
        method: 'GET',
        data  : { TransUID: uid },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) { $('#thermalPrintBody').html('<div class="alert alert-danger m-2">' + _esc(resp.Message) + '</div>'); return; }
            _thermalData = resp;
            $('#thermalPrintBody').html(_buildThermalHtml(resp, 0));
        },
        error: function () { AjaxLoading = 1; $('#thermalPrintBody').html('<div class="alert alert-danger m-2">Failed to load receipt.</div>'); }
    });
});

$('#thermalPrintBtn').on('click', function () {
    if (!_thermalData) return;
    var cfg = _thermalData.ThermalConfig;
    var paperWidth = (cfg && cfg.PaperWidth) ? cfg.PaperWidth : '80mm';
    var receiptHtml = _buildThermalHtml(_thermalData, 1);
    var win = window.open('', '_blank', 'width=400,height=700');
    win.document.write('<!DOCTYPE html><html><head><title>Thermal Receipt</title><style>' +
        '* { margin:0; padding:0; box-sizing:border-box; }' +
        'body { font-family: Arial, Helvetica, sans-serif; font-size:12px; width:' + paperWidth + '; padding:4px; }' +
        '.fs-6 { font-size: 0.8rem !important; } .tp-center { text-align: center; } .tp-bold { font-weight: bold; }' +
        '.tp-hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }' +
        '.tp-row { display: flex; justify-content: space-between; margin: 1px 0; }' +
        '.tp-row-end { display: flex; justify-content: end; margin: 1px 0; }' +
        '.tp-item-name { font-weight: bold; margin-top: 2px; } .tp-small { font-size:11px; }' +
        '.tp-total { font-size:13px; font-weight:bold; border-top:1px solid #000; padding-top:3px; margin-top:3px; }' +
        '.tp-footer { text-align:center; margin-top:6px; font-size:11px; }' +
        '@media print { @page { margin:0; size:' + paperWidth + ' auto; } body { width:' + paperWidth + '; } }' +
        '</style></head><body style="font-family:Arial,Helvetica,sans-serif!important;font-size:12px!important;width:' + paperWidth + ';padding:4px;">' +
        receiptHtml + '</body></html>');
    win.document.close(); win.focus();
    setTimeout(function () { win.print(); }, 300);
});

function _buildThermalHtml(resp, type) {
    var h   = resp.Header;
    var org = resp.OrgInfo  || {};
    var cfg = resp.ThermalConfig || {};
    var sym = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var line1 = cfg.HeaderLine1 || org.BrandName || org.Name || '';
    var line2 = cfg.HeaderLine2 || '';
    var line3 = cfg.HeaderLine3 || [org.CityText, org.StateText, org.Pincode].filter(Boolean).join(', ');
    var showGSTIN  = cfg.ShowGSTIN   !== undefined ? parseInt(cfg.ShowGSTIN)   : 1;
    var showMobile = cfg.ShowMobile  !== undefined ? parseInt(cfg.ShowMobile)  : 1;
    var showHSN    = cfg.ShowHSN     !== undefined ? parseInt(cfg.ShowHSN)     : 1;
    var showTaxBkd = cfg.ShowTaxBreakdown !== undefined ? parseInt(cfg.ShowTaxBreakdown) : 1;
    var footer     = cfg.FooterMessage || 'Thank you for your business!';
    var html = '';
    html += '<div style="display:flex;align-items:center;justify-content:center;"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="60px" height="60px" alt="Logo">';
    html += '<div class="fs-6 ms-1"><div class="tp-center tp-bold">' + _esc(line1) + '</div>';
    if (line2) html += '<div class="tp-center tp-small">' + _esc(line2) + '</div>';
    if (line3) html += '<div class="tp-center tp-small">' + _esc(line3) + '</div>';
    if (showMobile && org.MobileNumber) html += '<div class="tp-center tp-small">Ph: ' + _esc(org.MobileNumber) + '</div>';
    if (showGSTIN && org.GSTIN) html += '<div class="tp-center tp-small">GSTIN: ' + _esc(org.GSTIN) + '</div>';
    html += '</div></div>';
    html += '<hr class="tp-hr my-1">';
    html += '<div class="fs-6"><div class="d-flex justify-content-between align-items-center mb-1">';
    html += '<div class="tp-row fs-6"><span class="tp-bold">Quotation: </span><span class="tp-bold">' + _esc(h.UniqueNumber || '—') + '</span></div>';
    html += '<div class="tp-row"><span>Date: </span><span>' + _esc(h.TransDate) + '</span></div></div>';
    html += '<div class="d-flex justify-content-between align-items-center">';
    html += '<div class="tp-row"><span>Customer: </span><span style="text-align:right;max-width:60%">' + _esc(h.PartyName) + '</span></div>';
    if (h.PartyMobile) html += '<div class="tp-row"><span>Phone: </span><span>' + _esc(h.PartyMobile) + '</span></div>';
    html += '</div></div>';
    html += '<hr class="tp-hr my-1">';
    html += '<div class="fs-6"><div class="mb-1" style="display:flex;align-items:center;justify-content:space-between;">';
    html += '<div class="tp-row tp-item-name tp-bold"><span>Item </span></div><div></div></div>';
    html += '<div style="display:flex;align-items:center;justify-content:space-between;">';
    html += '<div class="tp-row" style="font-size:smaller;">Quantity x Price</div><div class="tp-row">Amount</div></div></div>';
    html += '<hr class="tp-hr my-1">';
    $.each(resp.Items || [], function (i, item) {
        html += '<div class="fs-6">';
        var lineAmt = parseFloat(item.NetAmount) || 0;
        var hsnLine = (showHSN && item.HSNCode) ? ' [HSN:' + item.HSNCode + ']' : '';
        html += '<div class="mb-1" style="display:flex;align-items:center;justify-content:space-between;">';
        html += '<div class="tp-item-name fs-6">' + _esc(item.ProductName) + _esc(hsnLine) + '</div><div></div></div>';
        html += '<div class="mb-1" style="display:flex;align-items:center;justify-content:space-between;">';
        html += '<div class="tp-row tp-small" style="font-size:smaller;">' + _esc(item.Quantity) + ' (' + _esc(item.PrimaryUnitName || 'PCS') + ') x ' + _esc(item.UnitPrice) + '</div>';
        html += '<div class="fs-6">' + lineAmt.toFixed(2) + '</div></div>';
        if (showTaxBkd && parseFloat(item.TaxPercentage) > 0) {
            var cgst = parseFloat(item.CgstAmount) || 0, sgst = parseFloat(item.SgstAmount) || 0, igst = parseFloat(item.IgstAmount) || 0;
            html += '<div style="display:flex;align-items:center;justify-content:space-between;">';
            if (cgst > 0 && sgst > 0) {
                html += '<div class="tp-row tp-small" style="color:#555;font-size:smaller;">CGST ' + item.CGST + '% ' + cgst.toFixed(2) + '</div>';
                html += '<div class="tp-row tp-small" style="color:#555;font-size:smaller;">SGST ' + item.SGST + '% ' + sgst.toFixed(2) + '</div>';
            } else if (igst > 0) { html += '<div class="tp-row tp-small" style="color:#555;font-size:smaller;">IGST ' + item.IGST + '% ' + igst.toFixed(2) + '</div>'; }
            html += '</div>';
        }
        html += '</div>';
        if (resp.Items.length > 1 && i != resp.Items.length - 1) html += '<hr class="tp-hr my-1">';
    });
    html += '<hr class="tp-hr my-1">';
    html += '<div class="tp-small fs-6" style="text-align:center!important;">Items/Qty: ' + (resp.Items ? resp.Items.length : 0) + ' / ' + (function(){ var q=0; $.each(resp.Items||[],function(i,it){q+=parseFloat(it.Quantity)||0;}); return q; }()) + '</div>';
    html += '<hr class="tp-hr my-1">';
    html += '<div style="text-align:end!important;">';
    html += '<div class="tp-row-end fw-semibold tp-item-name"><span>Subtotal: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.SubTotal || 0).toFixed(2) + '</span></div>';
    if (parseFloat(h.DiscountAmount) > 0) html += '<div class="tp-row-end fw-semibold"><span>Discount: </span><span class="fs-6">- ' + sym + ' ' + parseFloat(h.DiscountAmount).toFixed(2) + '</span></div>';
    if (parseFloat(h.TaxAmount) > 0) {
        html += '<div class="tp-row-end fw-semibold"><span>Total Tax: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.TaxAmount).toFixed(2) + '</span></div>';
        if (showTaxBkd) {
            if (parseFloat(h.CgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  CGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.CgstAmount).toFixed(2) + '</span></div>';
            if (parseFloat(h.SgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  SGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.SgstAmount).toFixed(2) + '</span></div>';
            if (parseFloat(h.IgstAmount) > 0) html += '<div class="tp-row-end tp-small"><span>  IGST: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.IgstAmount).toFixed(2) + '</span></div>';
        }
    }
    if (parseFloat(h.AdditionalCharges) > 0) html += '<div class="tp-row-end fw-semibold"><span>Charges: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.AdditionalCharges).toFixed(2) + '</span></div>';
    if (parseFloat(h.RoundOff || 0) !== 0) html += '<div class="tp-row-end tp-small fw-semibold"><span>Round Off: </span><span class="fs-6">' + sym + ' ' + parseFloat(h.RoundOff).toFixed(2) + '</span></div>';
    html += '<div class="tp-total tp-row-end fw-semibold tp-item-name"><span>Total Amount: </span><span class="fs-5">' + sym + ' ' + parseFloat(h.NetAmount || 0).toFixed(2) + '</span></div>';
    html += '</div>';
    html += '<hr class="tp-hr my-1">';
    html += '<div class="tp-footer" style="text-align:center!important;">' + _esc(footer) + '</div>';
    html += '<div style="margin-bottom:8px"></div>';
    return type === 0 ? '<div style="font-family:\'Courier New\',Courier,monospace;font-size:13px;padding:8px;max-width:580px;margin:0 auto;font-weight:900;">' + html + '</div>' : html;
}
</script>
