<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $cntAll      = array_sum(array_column($stats, 'count'));
                    $cntPending  = ($stats['Approved']['count'] ?? 0) + ($stats['Partial']['count'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']      ?? 0;
                    $cntDraft    = $stats['Draft']['count']     ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtPending  = ($stats['Approved']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#f3e8ff;">
                                <i class="bx bx-undo" style="color:#a855f7;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Purchase Returns'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/purchasereturns/create" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>New Purchase Return
                            </a>
                        </div>
                    </div>

                    <!-- ── Stat Cards ─────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-undo"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Returns</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="PRPending">
                                <div class="tsc-icon-wrap"><i class="bx bx-time"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Pending Refund</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPending); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtPending, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Paid">
                                <div class="tsc-icon-wrap"><i class="bx bx-check-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Settled</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPaid); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="tsc-icon-wrap"><i class="bx bx-pencil"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Drafts</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-tabs">
                                <ul class="nav trans-status-tabs" id="prStatusTabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active pr-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                    <li class="nav-item"><a class="nav-link pr-status-tab" data-status="PRPending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pr-status-tab" data-status="Paid" href="javascript:void(0);">Settled <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pr-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pr-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                </ul>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="javascript:void(0);" class="r2k-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                                <div class="dropdown">
                                    <button class="r2k-dd-btn<?php echo (!empty($SavedDateRange) && $SavedDateRange !== 'all') ? ' r2k-date-active' : ''; ?>" type="button" id="dateFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        <i class="bx bx-calendar"></i> <span id="dateFilterLabel"><?php echo htmlspecialchars($SavedDateLabel ?? 'All Dates'); ?></span><?php if (!empty($SavedDateFromDisplay ?? '')): ?> <strong id="dateFilterDates" class="r2k-df-dates"><?php echo $SavedDateFromDisplay === $SavedDateToDisplay ? $SavedDateFromDisplay : $SavedDateFromDisplay . ' – ' . $SavedDateToDisplay; ?></strong><?php else: ?><strong id="dateFilterDates" class="r2k-df-dates" style="display:none;"></strong><?php endif; ?> <i class="bx bx-chevron-down" style="font-size:.75rem;"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" id="dateFilterMenu" style="width:240px;max-height:420px;overflow-y:auto;font-size:.82rem;z-index:9999;">
                                    </ul>
                                </div>
                                <?php $this->load->view('common/transactions/filter_bar', [
                                    'FilterBarConfig' => [
                                        'paymentStatus' => true,
                                        'paymentMode'   => true,
                                        'party'         => false,
                                        'lastUpdated'   => false,
                                        'PaymentTypes'  => $PaymentTypes ?? [],
                                        'OrgUsers'      => $OrgUsers     ?? [],
                                    ],
                                ]); ?>
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="searchTransactionData" placeholder="Return # or vendor...">
                                    <i class="bx bx-x r2k-clear d-none"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="prTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox prHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Return # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Refund Status</th>
                                        <th>Status</th>
                                        <th>
                                            Vendor
                                            <a href="javascript:void(0);" id="prPartyFilterTrigger" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Vendor"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Return Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>
                                            Last Updated
                                            <?php if (count($OrgUsers ?? []) > 1): ?>
                                            <a href="javascript:void(0);" id="prCreatedByFilter" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by User"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                            <?php endif; ?>
                                        </th>
                                        <th style="width:110px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center prPagination" id="prPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#6610f2'; $rpAccentBg = '#f0ebff';
            $rpPartyIcon   = 'bx-store'; $rpDocLabel = 'Purchase Return';
            $rpTotalIcon   = 'bx-undo';
            $rpNumId       = 'rpInvNum'; $rpDateId   = 'rpInvDate';
            $rpBtnLabel    = 'Record Refund';
            $this->load->view('common/transactions/payment_modal');
            ?>

            <!-- Apply Debit to Purchase Modal -->
            <div class="modal fade" id="applyDebitModal" tabindex="-1" aria-hidden="true" aria-labelledby="applyDebitModalLabel">
                <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
                    <div class="modal-content">
                        <div class="modal-header py-3" style="background:#fff3e0;border-bottom:1px solid #ffe0b2;">
                            <div>
                                <h6 class="modal-title fw-semibold mb-0" style="color:#e65100;" id="applyDebitModalLabel">
                                    <i class="bx bx-credit-card me-2"></i>Apply Debit to Purchase
                                </h6>
                                <div class="text-muted" style="font-size:.78rem;margin-top:2px;">
                                    Purchase Return: <strong id="adPrNum">—</strong>
                                </div>
                            </div>
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <input type="hidden" id="adPurchaseReturnUID">

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded text-center" style="background:#e8f5e9;border:1px solid #c8e6c9;">
                                        <div class="text-muted" style="font-size:.7rem;">Vendor</div>
                                        <div class="fw-semibold text-truncate" style="font-size:.82rem;" id="adPartyName">—</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded text-center" style="background:#fff3e0;border:1px solid #ffe0b2;">
                                        <div class="text-muted" style="font-size:.7rem;">Available Debit</div>
                                        <div class="fw-semibold" style="font-size:.88rem;color:#e65100;" id="adDebitBalance">—</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Select Purchase <span class="text-danger">*</span></label>
                                <select id="adPurchaseUID" class="form-select form-select-sm">
                                    <option value="">— Select Purchase —</option>
                                </select>
                            </div>

                            <div id="adPurchaseInfo" class="d-none mb-3">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#f3e5f5;border:1px solid #e1bee7;">
                                            <div class="text-muted" style="font-size:.68rem;">Purchase Total</div>
                                            <div class="fw-semibold" style="font-size:.82rem;" id="adPurchTotal">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#e8eaf6;border:1px solid #c5cae9;">
                                            <div class="text-muted" style="font-size:.68rem;">Paid</div>
                                            <div class="fw-semibold" style="font-size:.82rem;" id="adPurchPaid">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#fff3e0;border:1px solid #ffe0b2;">
                                            <div class="text-muted" style="font-size:.68rem;">Pending</div>
                                            <div class="fw-semibold" style="font-size:.82rem;color:#e65100;" id="adPurchBalance">—</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Amount to Apply <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="adCurrencySymbol">₹</span>
                                    <input type="number" class="form-control" id="adAmount" min="0.01" step="0.01" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Notes</label>
                                <input type="text" class="form-control form-control-sm" id="adNotes" placeholder="Optional note">
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sm btn-warning" id="btnSubmitApplyDebit">
                                <i class="bx bx-check me-1"></i>Apply Debit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'prCreatedByFilterBox',
        'triggerId'  => 'prCreatedByFilter',
        'checkClass' => 'pr-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'prPartyFilterBox',
        'title' => 'Filter by Vendor',
        'icon'  => 'bx-store',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/purchasereturns.js"></script>

<script>

const ModuleId     = 108;
const ModuleTable  = '#prTable';
const ModulePag    = '.prPagination';
const ModuleHeader = '.prHeaderCheck';
const ModuleRow    = '.prCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';
    initExport({ moduleUID: 108, getFilters: function () { return Filter; } });

    // ── Filter bar ──────────────────────────────────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getPurchaseReturnsDetails(); } })
        : null;

    var prCreatedByFilter = (document.getElementById('prCreatedByFilterBox'))
        ? new TransColFilter({
            boxId     : 'prCreatedByFilterBox',
            triggerId : 'prCreatedByFilter',
            filterKey : 'UpdatedByUIDs',
            onApply   : function () { PageNo = 1; getPurchaseReturnsDetails(); }
        })
        : null;

    var prPartyFilter = new TransPartyColFilter({
        boxId     : 'prPartyFilterBox',
        triggerId : 'prPartyFilterTrigger',
        partyType : 'vendor',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getPurchaseReturnsDetails(); }
    });

    var _origGetPurchaseReturnsDetails = getPurchaseReturnsDetails;
    getPurchaseReturnsDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            tfb               ? tfb.getState()               : {},
            prCreatedByFilter ? prCreatedByFilter.getState() : {},
            prPartyFilter     ? prPartyFilter.getState()     : {}
        );
        _origGetPurchaseReturnsDetails(pageNo, rowLimit, f);
    };

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
    });

    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat'); $(this).addClass('active-stat');
        $('.pr-status-tab').removeClass('active');
        $('.pr-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status; PageNo = 1; getPurchaseReturnsDetails();
    });

    $(document).on('click', '.pr-status-tab', function (e) {
        e.preventDefault();
        $('.pr-status-tab').removeClass('active'); $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status; PageNo = 1; getPurchaseReturnsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) { e.preventDefault(); PageNo = 1; getPurchaseReturnsDetails(); });

    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getPurchaseReturnsDetails();
    }, 1500));

    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active'); $(this).addClass('active');
        var dates = getDateRange($(this).data('range') || '');
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from; Filter.DateTo = dates.to;
        PageNo = 1; getPurchaseReturnsDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        Filter.SortDir = (Filter.SortBy === col && Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        Filter.SortBy  = col;
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getPurchaseReturnsDetails();
    });

    $(document).on('click', '.prPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getPurchaseReturnsDetails(); }
    });

    $(document).on('click', '.pr-status-update', function () {
        var uid = $(this).data('uid'), status = $(this).data('status');
        if ($(this).data('_confirmed')) { $(this).removeData('_confirmed'); return; }
        if (status === 'Cancelled') {
            var num = $(this).data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this purchase return';
            var $btn = $(this);
            Swal.fire({ title: 'Cancel Purchase Return?', html: 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It' }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        $.ajax({ url: '/purchasereturns/updatePurchaseReturnStatus', method: 'POST', data: { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) { if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); } else { getPurchaseReturnsDetails(); } }
        });
    });

    $(document).on('click', '.deletePurchaseReturn', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({ title: 'Delete Purchase Return?', html: num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({ url: '/purchasereturns/deletePurchaseReturn', method: 'POST', data: { TransUID: uid, [CsrfName]: CsrfToken },
                    success: function (resp) { if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); } else { getPurchaseReturnsDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); } }
                });
            });
    });


    $(document).on('change', '.prHeaderCheck', function () { $('.prCheck').prop('checked', $(this).is(':checked')); });

    // ── Record Refund (Purchase Return) ────────────────────────────
    $(document).on('click', '.prReceivePayment', function (e) {
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
        if (!_rpDropzone && typeof Dropzone !== 'undefined') {
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

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid purchase return.' }); return; }
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
            url: '/purchasereturns/recordPayment', method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Refund');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal')).hide();
                    getPurchaseReturnsDetails();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Refund');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

}());

// ── Apply Debit to Purchase ──────────────────────────────────────
(function () {
    'use strict';
    var _adCur     = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var _adDec     = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    var _adBalance = 0;
    var _adSelect2 = null;

    $(document).on('click', '.prApplyDebit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $el     = $(this);
        var prUID   = $el.data('uid')     || 0;
        var num     = $el.data('num')     || '—';
        var party   = $el.data('party')   || '—';
        var balance = parseFloat($el.data('balance')) || 0;

        _adBalance = balance;
        $('#adPurchaseReturnUID').val(prUID);
        $('#adPrNum').text(num);
        $('#adPartyName').text(party);
        $('#adDebitBalance').text(_adCur + ' ' + balance.toFixed(_adDec));
        $('#adCurrencySymbol').text(_adCur);
        $('#adAmount').val(balance.toFixed(_adDec)).attr('max', balance);
        $('#adNotes').val('');
        $('#adPurchaseInfo').addClass('d-none');

        var $sel = $('#adPurchaseUID').empty().append('<option value="">— Loading… —</option>');
        if (_adSelect2) { try { _adSelect2.destroy(); } catch(ex){} _adSelect2 = null; }

        $.ajax({
            url: '/purchasereturns/getPendingPurchases', method: 'POST',
            data: { PurchaseReturnUID: prUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                $sel.empty().append('<option value="">— Select Purchase —</option>');
                if (!resp.Error && resp.Purchases && resp.Purchases.length) {
                    $.each(resp.Purchases, function (i, p) {
                        var bal = parseFloat(p.BalanceAmount) || 0;
                        $sel.append('<option value="' + p.TransUID
                            + '" data-total="'   + p.NetAmount
                            + '" data-paid="'    + p.PaidAmount
                            + '" data-balance="' + bal + '">'
                            + _esc(p.UniqueNumber) + ' — ' + _adCur + ' ' + bal.toFixed(_adDec) + ' pending'
                            + '</option>');
                    });
                } else {
                    $sel.append('<option value="" disabled>No pending purchases found</option>');
                }
                if (typeof $.fn.select2 !== 'undefined') {
                    _adSelect2 = $sel.select2({ dropdownParent: $('#applyDebitModal'), placeholder: '— Select Purchase —' });
                }
            }
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('applyDebitModal')).show();
    });

    $(document).on('change', '#adPurchaseUID', function () {
        var $opt    = $(this).find('option:selected');
        var pUID    = parseInt($(this).val(), 10) || 0;
        if (!pUID) { $('#adPurchaseInfo').addClass('d-none'); return; }
        var total   = parseFloat($opt.data('total'))   || 0;
        var paid    = parseFloat($opt.data('paid'))    || 0;
        var balance = parseFloat($opt.data('balance')) || 0;
        $('#adPurchTotal').text(_adCur + ' ' + total.toFixed(_adDec));
        $('#adPurchPaid').text(_adCur + ' ' + paid.toFixed(_adDec));
        $('#adPurchBalance').text(_adCur + ' ' + balance.toFixed(_adDec));
        $('#adPurchaseInfo').removeClass('d-none');
        var maxApply = Math.min(_adBalance, balance);
        $('#adAmount').val(maxApply.toFixed(_adDec)).attr('max', maxApply);
    });

    $('#btnSubmitApplyDebit').on('click', function () {
        var prUID      = parseInt($('#adPurchaseReturnUID').val(), 10) || 0;
        var purchaseUID = parseInt($('#adPurchaseUID').val(), 10) || 0;
        var amount     = parseFloat($('#adAmount').val()) || 0;
        var notes      = $.trim($('#adNotes').val());

        if (!prUID)       { Swal.fire({ icon: 'warning', text: 'Invalid purchase return.' }); return; }
        if (!purchaseUID) { Swal.fire({ icon: 'warning', text: 'Please select a purchase.' }); return; }
        if (amount <= 0)  { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Applying…');
        $.ajax({
            url: '/purchasereturns/applyDebit', method: 'POST',
            data: { PurchaseReturnUID: prUID, PurchaseUID: purchaseUID, Amount: amount, Notes: notes, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Apply Debit');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('applyDebitModal')).hide();
                    getPurchaseReturnsDetails();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Apply Debit');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

    $('#applyDebitModal').on('hidden.bs.modal', function () {
        if (_adSelect2) { try { _adSelect2.destroy(); } catch(ex){} _adSelect2 = null; }
        $('#adPurchaseUID').empty().append('<option value="">— Select Purchase —</option>');
        $('#adPurchaseInfo').addClass('d-none');
    });

}());
</script>
