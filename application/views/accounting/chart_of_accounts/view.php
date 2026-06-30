<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $Stats */ $Stats = $Stats ?? new stdClass();
/** @var array  $ParentLedgers */ $ParentLedgers = $ParentLedgers ?? [];
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Chart of Accounts',
                    'pageDescription' => 'Manage your organisation\'s ledger accounts',
                    'pageIcon'        => 'bx-spreadsheet',
                    'pageIconBg'      => '#ede9ff',
                    'pageIconColor'   => '#7c3aed',
                ]); ?>

                <!-- ── Stats Strip ──────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item coa-type-filter active" data-type="All" style="--stat-color:#7c3aed">
                        <div class="apex-stat-icon" style="background:#ede9ff"><i class="bx bx-spreadsheet" style="color:#7c3aed"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Accounts</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count coa-s-total"><?php echo (int)($Stats->TotalCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item coa-type-filter" data-type="Asset" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-building" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Assets</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count coa-s-asset"><?php echo (int)($Stats->AssetCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item coa-type-filter" data-type="Liability" style="--stat-color:#ef4444">
                        <div class="apex-stat-icon" style="background:#fef2f2"><i class="bx bx-minus-circle" style="color:#ef4444"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Liabilities</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count coa-s-liability"><?php echo (int)($Stats->LiabilityCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item coa-type-filter" data-type="Income" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-trending-up" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Income</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count coa-s-income"><?php echo (int)($Stats->IncomeCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item coa-type-filter" data-type="Expense" style="--stat-color:#f59e0b">
                        <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-trending-down" style="color:#f59e0b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Expenses</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count coa-s-expense"><?php echo (int)($Stats->ExpenseCount ?? 0); ?></span></div>
                        </div>
                    </a>
                </div>

                <div class="container-xxl flex-grow-1 py-3">
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="coaSearch" placeholder="Search code or name...">
                                <i class="bx bx-x r2k-clear d-none" id="coaSearchClear"></i>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn" id="coaRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button class="btn btn-primary btn-sm" id="btnNewLedger">
                                <i class="bx bx-plus me-1"></i>New Account
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active coa-tab" data-type="All"       href="javascript:void(0);">All</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Asset"      href="javascript:void(0);">Asset</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Liability"  href="javascript:void(0);">Liability</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Income"     href="javascript:void(0);">Income</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Expense"    href="javascript:void(0);">Expense</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Customer"   href="javascript:void(0);">Customer</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Vendor"     href="javascript:void(0);">Vendor</a></li>
                                <li class="nav-item"><a class="nav-link coa-tab"        data-type="Bank"       href="javascript:void(0);">Bank/Cash</a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="coaTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:44px;">#</th>
                                        <th style="width:130px;">Code</th>
                                        <th>Account Name</th>
                                        <th style="width:110px;">Type</th>
                                        <th>Parent Account</th>
                                        <th class="text-end" style="width:140px;">Current Balance</th>
                                        <th class="text-center" style="width:90px;">Status</th>
                                        <th class="th-act" style="width:80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="coaTableBody">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center coaPagination" id="coaPagination">
                            <?php echo $ModPagination ?? ''; ?>
                        </div>

                    </div>
                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ── Ledger Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="ledgerModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#7c3aed;--vtm-bg:#ede9ff;--vtm-icon-bg:rgba(124,58,237,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-spreadsheet"></i></div>
                        <div>
                            <div class="vtm-doc-number" id="ledgerModalTitle">New Account</div>
                            <div class="vtm-doc-meta" id="ledgerModalMeta">Fill in the ledger account details</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="ledgerUID" value="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ledger Code <span class="text-danger">*</span></label>
                        <input type="text" id="ledgerCode" class="form-control" placeholder="e.g. ACC-001" maxlength="50">
                        <div class="form-text" id="ledgerCodeNote"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Account Type <span class="text-danger">*</span></label>
                        <select id="ledgerType" class="form-select">
                            <option value="">— Select Type —</option>
                            <option value="Asset">Asset</option>
                            <option value="Liability">Liability</option>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                            <option value="Customer">Customer</option>
                            <option value="Vendor">Vendor</option>
                            <option value="Employee">Employee</option>
                            <option value="Bank">Bank</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                        <input type="text" id="ledgerName" class="form-control" placeholder="e.g. Sales Revenue" maxlength="255">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Parent Account</label>
                        <select id="parentLedgerUID" class="form-select">
                            <option value="">— None (top-level) —</option>
                            <?php foreach ($ParentLedgers as $p): ?>
                            <option value="<?php echo (int)$p->LedgerUID; ?>">
                                <?php echo htmlspecialchars($p->LedgerName . ' (' . $p->LedgerType . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Opening Balance</label>
                        <input type="number" id="openingBalance" class="form-control" value="0" min="0" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Balance Type</label>
                        <select id="openingBalanceType" class="form-select">
                            <option value="Debit">Debit</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveLedger">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="ledgerSpinner"></span>
                    <i class="bx bx-save me-1" id="ledgerIcon"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
(function () {
    'use strict';

    var _currentPage  = 1;
    var _filter       = {};

    function _updateStats(s) {
        if (!s) return;
        $('.coa-s-total').text(s.TotalCount    || 0);
        $('.coa-s-asset').text(s.AssetCount    || 0);
        $('.coa-s-liability').text(s.LiabilityCount || 0);
        $('.coa-s-income').text(s.IncomeCount  || 0);
        $('.coa-s-expense').text(s.ExpenseCount || 0);
    }

    function _applyResponse(r) {
        _currentPage = 1;
        $('#coaTableBody').html(r.RecordHtmlData);
        $('.coaPagination').html(r.Pagination);
        _updateStats(r.Stats);
    }

    function _load(page) {
        _currentPage = page || 1;
        $.post('/accounting/getChartOfAccountsPage/' + _currentPage, { Filter: _filter, [CsrfName]: CsrfToken }, function (r) {
            CsrfToken = r.NewCsrfToken || CsrfToken;
            if (!r.Error) { $('#coaTableBody').html(r.RecordHtmlData); $('.coaPagination').html(r.Pagination); _updateStats(r.Stats); }
        });
    }

    // Stat strip + tabs
    $(document).on('click', '.coa-type-filter', function () {
        $('.coa-type-filter').removeClass('active');
        $(this).addClass('active');
        var t = $(this).data('type');
        $('.coa-tab').removeClass('active');
        $('.coa-tab[data-type="' + t + '"]').addClass('active');
        if (t === 'All') delete _filter.LedgerType; else _filter.LedgerType = t;
        _load(1);
    });
    $(document).on('click', '.coa-tab', function (e) {
        e.preventDefault();
        $('.coa-tab').removeClass('active');
        $(this).addClass('active');
        var t = $(this).data('type');
        $('.coa-type-filter').removeClass('active');
        $('.coa-type-filter[data-type="' + (t === 'Bank' ? 'Bank' : t) + '"]').addClass('active');
        if (t === 'All') delete _filter.LedgerType;
        else if (t === 'Bank') _filter.LedgerType = 'Bank';
        else _filter.LedgerType = t;
        _load(1);
    });

    // Search
    var _st;
    $('#coaSearch').on('input', function () {
        clearTimeout(_st);
        var v = $.trim($(this).val());
        $('#coaSearchClear').toggleClass('d-none', !v);
        if (v) _filter.SearchAllData = v; else delete _filter.SearchAllData;
        _st = setTimeout(function () { _load(1); }, 400);
    });
    $('#coaSearchClear').on('click', function () { $('#coaSearch').val('').trigger('input'); });
    $('#coaRefresh').on('click', function () { _load(_currentPage); });

    // Pagination
    $(document).on('click', '.coaPagination .page-link', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page')); if (pg) _load(pg);
    });

    // ── New Account modal ────────────────────────────────────────────────────
    $('#btnNewLedger').on('click', function () {
        $('#ledgerUID').val(0);
        $('#ledgerCode').val('').prop('readonly', false);
        $('#ledgerCodeNote').text('');
        $('#ledgerName').val('');
        $('#ledgerType').val('');
        $('#parentLedgerUID').val('');
        $('#openingBalance').val('0');
        $('#openingBalanceType').val('Debit');
        $('#ledgerModalTitle').text('New Account');
        $('#ledgerModalMeta').text('Fill in the ledger account details');
        $('#ledgerModal').modal('show');
    });

    // ── Edit ─────────────────────────────────────────────────────────────────
    $(document).on('click', '.coa-edit-btn', function () {
        var $r = $(this).closest('tr');
        $('#ledgerUID').val($r.data('uid'));
        $('#ledgerCode').val($r.data('code')).prop('readonly', true);
        $('#ledgerCodeNote').text('Code cannot be changed after creation.');
        $('#ledgerName').val($r.data('name'));
        $('#ledgerType').val($r.data('type'));
        $('#parentLedgerUID').val($r.data('parent') || '');
        $('#openingBalance').val($r.data('opening') || 0);
        $('#openingBalanceType').val($r.data('openingtype') || 'Debit');
        $('#ledgerModalTitle').text('Edit Account');
        $('#ledgerModalMeta').text('Update ledger account details');
        $('#ledgerModal').modal('show');
    });

    // ── Save ─────────────────────────────────────────────────────────────────
    $('#btnSaveLedger').on('click', function () {
        var code = $.trim($('#ledgerCode').val());
        var name = $.trim($('#ledgerName').val());
        var type = $('#ledgerType').val();
        var uid  = parseInt($('#ledgerUID').val()) || 0;
        if (!name) { showToastNotification('Account name is required.', 'warning'); return; }
        if (!type) { showToastNotification('Account type is required.', 'warning'); return; }
        if (!uid && !code) { showToastNotification('Ledger code is required.', 'warning'); return; }

        var $btn = $(this).prop('disabled', true);
        $('#ledgerSpinner').removeClass('d-none'); $('#ledgerIcon').addClass('d-none');

        $.post('/accounting/saveLedger', {
            LedgerUID:          uid,
            LedgerCode:         code,
            LedgerName:         name,
            LedgerType:         type,
            ParentLedgerUID:    $('#parentLedgerUID').val() || 0,
            OpeningBalance:     $('#openingBalance').val() || 0,
            OpeningBalanceType: $('#openingBalanceType').val(),
            Filter:             _filter,
            [CsrfName]:         CsrfToken,
        }, function (r) {
            CsrfToken = r.NewCsrfToken || CsrfToken;
            $btn.prop('disabled', false);
            $('#ledgerSpinner').addClass('d-none'); $('#ledgerIcon').removeClass('d-none');
            if (!r.Error) {
                $('#ledgerModal').modal('hide');
                _applyResponse(r);
                showToastNotification(r.Message, 'success');
            } else {
                showToastNotification(r.Message, 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false);
            $('#ledgerSpinner').addClass('d-none'); $('#ledgerIcon').removeClass('d-none');
            showToastNotification('Request failed.', 'error');
        });
    });

    // ── Toggle Status ─────────────────────────────────────────────────────────
    $(document).on('click', '.coa-toggle-btn', function () {
        var uid = $(this).data('uid'), status = parseInt($(this).data('newstatus'));
        var label = status ? 'Activate' : 'Deactivate';
        Swal.fire({ title: label + ' this account?', icon: 'question', showCancelButton: true,
            confirmButtonColor: status ? '#10b981' : '#f59e0b', confirmButtonText: 'Yes, ' + label })
        .then(function (r) {
            if (!r.isConfirmed) return;
            $.post('/accounting/toggleLedgerStatus', { LedgerUID: uid, IsActive: status, Filter: _filter, [CsrfName]: CsrfToken }, function (r) {
                CsrfToken = r.NewCsrfToken || CsrfToken;
                if (!r.Error) { _applyResponse(r); showToastNotification(r.Message, 'success'); }
                else showToastNotification(r.Message, 'error');
            });
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    $(document).on('click', '.coa-delete-btn', function () {
        var uid = $(this).data('uid');
        Swal.fire({ title: 'Delete this account?', text: 'Cannot delete accounts with journal entries.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete' })
        .then(function (r) {
            if (!r.isConfirmed) return;
            $.post('/accounting/deleteLedger', { LedgerUID: uid, Filter: _filter, [CsrfName]: CsrfToken }, function (r) {
                CsrfToken = r.NewCsrfToken || CsrfToken;
                if (!r.Error) { _applyResponse(r); showToastNotification(r.Message, 'success'); }
                else showToastNotification(r.Message, 'error');
            });
        });
    });

}());
</script>
