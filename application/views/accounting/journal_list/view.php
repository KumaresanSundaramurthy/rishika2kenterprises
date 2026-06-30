<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $Stats */ $Stats = $Stats ?? new stdClass();
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Journal Entries',
                    'pageDescription' => 'View all double-entry journal transactions',
                    'pageIcon'        => 'bx-list-ul',
                    'pageIconBg'      => '#ede9ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <!-- ── Stats Strip ──────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item jl-ref-filter active" data-ref="All" style="--stat-color:#7c3aed">
                        <div class="apex-stat-icon" style="background:#ede9ff"><i class="bx bx-list-ul" style="color:#7c3aed"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">All Journals</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count jl-s-total"><?php echo (int)($Stats->TotalCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item jl-ref-filter" data-ref="Invoice" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-receipt" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Invoices</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count jl-s-invoice"><?php echo (int)($Stats->InvoiceCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item jl-ref-filter" data-ref="Purchase" style="--stat-color:#f59e0b">
                        <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-cart" style="color:#f59e0b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Purchases</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count jl-s-purchase"><?php echo (int)($Stats->PurchaseCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item jl-ref-filter" data-ref="Payment" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-money" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Payments</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count jl-s-payment"><?php echo (int)($Stats->PaymentCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item jl-ref-filter" data-ref="Reversal" style="--stat-color:#ef4444">
                        <div class="apex-stat-icon" style="background:#fef2f2"><i class="bx bx-undo" style="color:#ef4444"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Reversals</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count jl-s-reversal"><?php echo (int)($Stats->ReversalCount ?? 0); ?></span></div>
                        </div>
                    </a>
                </div>

                <div class="container-xxl flex-grow-1 py-3">
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="jlSearch" placeholder="Journal #, reference, narration...">
                                <i class="bx bx-x r2k-clear d-none" id="jlSearchClear"></i>
                            </div>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn" id="jlRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:44px;">#</th>
                                        <th style="width:100px;">Date</th>
                                        <th style="width:140px;">Journal #</th>
                                        <th style="width:130px;">Reference</th>
                                        <th>Narration</th>
                                        <th class="text-end" style="width:130px;">Debit Total</th>
                                        <th class="text-end" style="width:130px;">Credit Total</th>
                                        <th class="text-center" style="width:70px;">Lines</th>
                                        <th class="th-act" style="width:70px;">View</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="jlTableBody">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center jlPagination" id="jlPagination">
                            <?php echo $ModPagination ?? ''; ?>
                        </div>

                    </div>
                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ── Journal Detail Modal ───────────────────────────────────────────────── -->
<div class="modal fade" id="journalDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#7c3aed;--vtm-bg:#ede9ff;--vtm-icon-bg:rgba(124,58,237,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-list-ul"></i></div>
                        <div>
                            <div class="vtm-doc-number" id="jlModalTitle">Journal Entry</div>
                            <div class="vtm-doc-meta" id="jlModalMeta">Double-entry details</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4" id="jlModalBody">
                <div class="text-center py-4"><span class="spinner-border text-primary"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
(function () {
    'use strict';

    var _filter = {};
    var _page   = 1;

    function _updateStats(s) {
        if (!s) return;
        $('.jl-s-total').text(s.TotalCount    || 0);
        $('.jl-s-invoice').text(s.InvoiceCount || 0);
        $('.jl-s-purchase').text(s.PurchaseCount || 0);
        $('.jl-s-payment').text(s.PaymentCount || 0);
        $('.jl-s-reversal').text(s.ReversalCount || 0);
    }

    function _load(page) {
        _page = page || 1;
        $.post('/accounting/getJournalListPage/' + _page, { Filter: _filter, [CsrfName]: CsrfToken }, function (r) {
            CsrfToken = r.NewCsrfToken || CsrfToken;
            if (!r.Error) {
                $('#jlTableBody').html(r.RecordHtmlData);
                $('.jlPagination').html(r.Pagination);
                _updateStats(r.Stats);
            }
        });
    }

    // Stat strip
    $(document).on('click', '.jl-ref-filter', function () {
        $('.jl-ref-filter').removeClass('active');
        $(this).addClass('active');
        var ref = $(this).data('ref');
        if (ref === 'All')      delete _filter.ReferenceType;
        else if (ref === 'Payment')  _filter.ReferenceType = 'Payment-In';  // partial — server uses LIKE
        else if (ref === 'Reversal') _filter.ReferenceType = 'Reversal-Invoice';
        else                    _filter.ReferenceType = ref;
        _load(1);
    });

    // Search
    var _st;
    $('#jlSearch').on('input', function () {
        clearTimeout(_st);
        var v = $.trim($(this).val());
        $('#jlSearchClear').toggleClass('d-none', !v);
        if (v) _filter.SearchAllData = v; else delete _filter.SearchAllData;
        _st = setTimeout(function () { _load(1); }, 400);
    });
    $('#jlSearchClear').on('click', function () { $('#jlSearch').val('').trigger('input'); });
    $('#jlRefresh').on('click', function () { _load(_page); });

    // Date filter
    $(document).on('r2k:datechange', function (e, dr) {
        _filter.DateFrom = dr.from;
        _filter.DateTo   = dr.to;
        _load(1);
    });

    // Pagination
    $(document).on('click', '.jlPagination .page-link', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page')); if (pg) _load(pg);
    });

    // View journal detail
    $(document).on('click', '.jl-view-btn', function () {
        var uid = $(this).data('uid');
        $('#jlModalTitle').text('Loading...');
        $('#jlModalMeta').text('');
        $('#jlModalBody').html('<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>');
        $('#journalDetailModal').modal('show');

        $.post('/accounting/getJournalDetail', { JournalUID: uid, [CsrfName]: CsrfToken }, function (r) {
            CsrfToken = r.NewCsrfToken || CsrfToken;
            if (!r.Error) {
                $('#jlModalTitle').text(r.JournalNo || 'Journal Entry');
                $('#jlModalMeta').text('Double-entry transaction details');
                $('#jlModalBody').html(r.Html);
            } else {
                $('#jlModalBody').html('<div class="alert alert-danger">' + r.Message + '</div>');
            }
        });
    });

}());
</script>
