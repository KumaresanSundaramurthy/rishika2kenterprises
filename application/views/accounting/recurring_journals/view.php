<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $Stats */ $Stats    = $Stats    ?? new stdClass();
/** @var int $TotalCount */ $TotalCount = $TotalCount ?? 0;
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? 'â‚¹');
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Recurring Journals',
                    'pageDescription' => 'Automate journal entries on a defined schedule',
                    'pageIcon'        => 'bx-repeat',
                    'pageIconBg'      => '#e0f2fe',
                    'pageIconColor'   => '#0284c7',
                ]); ?>

                <!-- â”€â”€ Stats Strip â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter active" data-filter="All" style="--stat-color:#0284c7">
                        <div class="apex-stat-icon" style="background:#e0f2fe"><i class="bx bx-repeat" style="color:#0284c7"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">All Journals</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-total"><?php echo (int)($Stats->TotalCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter" data-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-active"><?php echo (int)($Stats->ActiveCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter" data-filter="Due" style="--stat-color:#f59e0b">
                        <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-bell" style="color:#f59e0b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Due / Overdue</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-due"><?php echo (int)($Stats->DueCount ?? 0); ?></span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter" data-filter="Paused" style="--stat-color:#64748b">
                        <div class="apex-stat-icon" style="background:#f1f5f9"><i class="bx bx-pause-circle" style="color:#64748b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Paused</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-paused"><?php echo (int)($Stats->PausedCount ?? 0); ?></span></div>
                        </div>
                    </a>
                </div>

                <div class="container-xxl flex-grow-1">
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="rjSearch" placeholder="Search by title or narration...">
                                <i class="bx bx-x r2k-clear d-none" id="rjSearchClear"></i>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn" id="rjRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button class="btn btn-sm btn-outline-warning" id="rjPostAllBtn">
                                <i class="bx bx-play-circle me-1"></i>Post All Due
                            </button>
                            <button class="btn btn-primary btn-sm" id="rjNewBtn">
                                <i class="bx bx-plus me-1"></i>New Recurring Journal
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover apex-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Title / Narration</th>
                                        <th style="width:100px;">Frequency</th>
                                        <th style="width:120px;">Period</th>
                                        <th style="width:110px;">Next Run</th>
                                        <th style="width:110px;">Last Run</th>
                                        <th style="width:90px;">Status</th>
                                        <th style="width:130px;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="rjTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="apex-pagination-wrap" id="rjPagination">
                            <?php echo $ModPagination; ?>
                        </div>
                    </div>
                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<!-- â”€â”€ Recurring Journal Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="modal fade" id="recurJournalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header vtm-banner" style="background:linear-gradient(135deg,#0284c7,#0ea5e9);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-repeat fs-4 text-white"></i>
                    <span class="fw-semibold text-white" id="rjModalTitle">New Recurring Journal</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rjRecurUID" value="0">

                <!-- Header fields -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" id="rjTitle" class="form-control" placeholder="e.g. Monthly Depreciation">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Frequency <span class="text-danger">*</span></label>
                        <select id="rjFrequency" class="form-select">
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly" selected>Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Yearly">Yearly</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="text" id="rjStartDate" class="form-control" readonly placeholder="Select date">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Narration <span class="text-danger">*</span></label>
                        <input type="text" id="rjNarration" class="form-control" placeholder="Journal narration / description">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">End Date <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" id="rjEndDate" class="form-control" readonly placeholder="No end date">
                    </div>
                </div>

                <!-- Lines table -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold" style="font-size:.85rem;">Journal Lines</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="rjAddRowBtn">
                        <i class="bx bx-plus me-1"></i>Add Line
                    </button>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Account</th>
                                <th style="width:130px;">Debit</th>
                                <th style="width:130px;">Credit</th>
                                <th>Particulars</th>
                                <th style="width:38px;"></th>
                            </tr>
                        </thead>
                        <tbody id="rjLinesBody"></tbody>
                    </table>
                </div>

                <!-- Balance indicator -->
                <div class="d-flex align-items-center justify-content-end gap-3">
                    <span class="text-muted" style="font-size:.8rem;">
                        Dr: <strong id="rjTotalDr"><?php echo $cur; ?> 0.00</strong>
                        &nbsp;|&nbsp;
                        Cr: <strong id="rjTotalCr"><?php echo $cur; ?> 0.00</strong>
                    </span>
                    <span id="rjBalanceBadge" class="badge bg-secondary" style="font-size:.8rem;">Not balanced</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="rjSaveBtn">
                    <i class="bx bx-save me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var _baseUrl       = '<?php echo base_url(); ?>';
    var _cur           = '<?php echo $cur; ?>';
    var _dec           = <?php echo $dec; ?>;
    var _dateFmt       = '<?php echo $dateFmt; ?>';
    var _activeFilter  = 'All';
    var _searchTimer   = null;
    var _rjRowIdx      = 0;

    // â”€â”€ Row HTML â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    /**
     * @param {number} idx
     * @returns {string}
     */
    function _rjRowHtml(idx) {
        return '<tr id="rj-row-' + idx + '">' +
            '<td class="text-center text-muted" style="vertical-align:middle;">' + (idx + 1) + '</td>' +
            '<td>' +
                '<select class="rj-ledger-select form-select form-select-sm" data-idx="' + idx + '" style="min-width:200px;">' +
                    '<option value="">Search account...</option>' +
                '</select>' +
            '</td>' +
            '<td><input type="number" class="form-control form-control-sm rj-dr" min="0" step="0.01" placeholder="0.00" data-idx="' + idx + '"></td>' +
            '<td><input type="number" class="form-control form-control-sm rj-cr" min="0" step="0.01" placeholder="0.00" data-idx="' + idx + '"></td>' +
            '<td><input type="text" class="form-control form-control-sm rj-part" placeholder="Particulars (optional)" data-idx="' + idx + '"></td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-icon btn-sm text-danger rj-remove-row" data-idx="' + idx + '" title="Remove"><i class="bx bx-minus-circle"></i></button>' +
            '</td>' +
        '</tr>';
    }

    /**
     * @returns {void}
     */
    function _rjAddRow() {
        var html = _rjRowHtml(_rjRowIdx);
        document.getElementById('rjLinesBody').insertAdjacentHTML('beforeend', html);
        var sel = document.querySelector('#rj-row-' + _rjRowIdx + ' .rj-ledger-select');
        $(sel).select2({
            dropdownParent: $('#recurJournalModal'),
            placeholder   : 'Search account...',
            minimumInputLength: 1,
            ajax: {
                url      : _baseUrl + 'accounting/getLedgersForJournal',
                dataType : 'json',
                delay    : 250,
                data     : function (p) { return { q: p.term }; },
                processResults: function (d) { return { results: d.results || [] }; },
            },
        });
        var drEl = document.querySelector('#rj-row-' + _rjRowIdx + ' .rj-dr');
        var crEl = document.querySelector('#rj-row-' + _rjRowIdx + ' .rj-cr');
        drEl.addEventListener('input', function () { if (parseFloat(this.value) > 0) crEl.value = ''; _rjUpdateTotals(); });
        crEl.addEventListener('input', function () { if (parseFloat(this.value) > 0) drEl.value = ''; _rjUpdateTotals(); });
        _rjRowIdx++;
    }

    /**
     * @returns {void}
     */
    function _rjUpdateTotals() {
        var dr = 0, cr = 0;
        document.querySelectorAll('.rj-dr').forEach(function (el) { dr += parseFloat(el.value) || 0; });
        document.querySelectorAll('.rj-cr').forEach(function (el) { cr += parseFloat(el.value) || 0; });
        document.getElementById('rjTotalDr').textContent = _cur + ' ' + dr.toFixed(_dec);
        document.getElementById('rjTotalCr').textContent = _cur + ' ' + cr.toFixed(_dec);
        var badge  = document.getElementById('rjBalanceBadge');
        var diff   = Math.abs(dr - cr);
        if (diff < 0.01 && dr > 0) {
            badge.className  = 'badge bg-success';
            badge.textContent = 'Balanced âœ“';
        } else {
            badge.className  = 'badge bg-danger';
            badge.textContent = 'Off by ' + _cur + ' ' + diff.toFixed(_dec);
        }
    }

    /**
     * @returns {void}
     */
    function _rjReset() {
        document.getElementById('rjRecurUID').value   = '0';
        document.getElementById('rjTitle').value      = '';
        document.getElementById('rjNarration').value  = '';
        document.getElementById('rjFrequency').value  = 'Monthly';
        document.getElementById('rjStartDate').value  = '';
        document.getElementById('rjEndDate').value    = '';
        document.getElementById('rjModalTitle').textContent = 'New Recurring Journal';
        document.getElementById('rjLinesBody').innerHTML = '';
        document.getElementById('rjTotalDr').textContent = _cur + ' 0.00';
        document.getElementById('rjTotalCr').textContent = _cur + ' 0.00';
        document.getElementById('rjBalanceBadge').className = 'badge bg-secondary';
        document.getElementById('rjBalanceBadge').textContent = 'Not balanced';
        _rjRowIdx = 0;
        _rjAddRow();
        _rjAddRow();
    }

    // â”€â”€ Flatpickr â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#rjStartDate', { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat || 'd M Y' });
        flatpickr('#rjEndDate',   { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat || 'd M Y' });
    }

    // â”€â”€ Add row button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjAddRowBtn').addEventListener('click', _rjAddRow);

    // â”€â”€ Remove row â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjLinesBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.rj-remove-row');
        if (!btn) return;
        var rows = document.querySelectorAll('#rjLinesBody tr');
        if (rows.length <= 2) { Swal.fire({ icon: 'warning', title: 'Minimum 2 lines required', timer: 2000, showConfirmButton: false }); return; }
        btn.closest('tr').remove();
        _rjUpdateTotals();
    });

    // â”€â”€ New button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjNewBtn').addEventListener('click', function () {
        _rjReset();
        var m = new bootstrap.Modal(document.getElementById('recurJournalModal'));
        m.show();
    });

    // â”€â”€ Edit button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjTableBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.rj-edit-btn');
        if (!btn) return;
        var uid = btn.dataset.uid;
        fetch(_baseUrl + 'accounting/getRecurringJournalAjax?RecurUID=' + uid)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                _rjReset();
                document.getElementById('rjRecurUID').value  = d.RecurUID;
                document.getElementById('rjTitle').value     = d.Title;
                document.getElementById('rjNarration').value = d.Narration;
                document.getElementById('rjFrequency').value = d.Frequency;
                document.getElementById('rjModalTitle').textContent = 'Edit Recurring Journal';

                // Set dates via flatpickr
                var fpStart = document.getElementById('rjStartDate')._flatpickr;
                var fpEnd   = document.getElementById('rjEndDate')._flatpickr;
                if (fpStart) fpStart.setDate(d.StartDate);
                if (fpEnd && d.EndDate)   fpEnd.setDate(d.EndDate);

                // Rebuild lines
                document.getElementById('rjLinesBody').innerHTML = '';
                _rjRowIdx = 0;
                d.Lines.forEach(function (l, i) {
                    _rjAddRow();
                    var rowIdx = _rjRowIdx - 1;
                    var sel = document.querySelector('#rj-row-' + rowIdx + ' .rj-ledger-select');
                    var opt = new Option(l.LedgerName, l.LedgerUID, true, true);
                    $(sel).append(opt).trigger('change');
                    if (l.TransactionType === 'Debit') {
                        document.querySelector('#rj-row-' + rowIdx + ' .rj-dr').value = l.Amount;
                    } else {
                        document.querySelector('#rj-row-' + rowIdx + ' .rj-cr').value = l.Amount;
                    }
                    document.querySelector('#rj-row-' + rowIdx + ' .rj-part').value = l.Particulars || '';
                });
                _rjUpdateTotals();
                new bootstrap.Modal(document.getElementById('recurJournalModal')).show();
            });
    });

    // â”€â”€ Save button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjSaveBtn').addEventListener('click', function () {
        var recurUID  = document.getElementById('rjRecurUID').value;
        var title     = document.getElementById('rjTitle').value.trim();
        var narration = document.getElementById('rjNarration').value.trim();
        var frequency = document.getElementById('rjFrequency').value;
        var startDate = document.getElementById('rjStartDate').value;
        var endDate   = document.getElementById('rjEndDate').value;

        if (!title)     { Swal.fire({ icon: 'warning', title: 'Title is required' }); return; }
        if (!narration) { Swal.fire({ icon: 'warning', title: 'Narration is required' }); return; }
        if (!startDate) { Swal.fire({ icon: 'warning', title: 'Start date is required' }); return; }

        var lines = [];
        var dr = 0, cr = 0;
        var valid = true;
        document.querySelectorAll('#rjLinesBody tr').forEach(function (row, i) {
            var sel   = row.querySelector('.rj-ledger-select');
            var drEl  = row.querySelector('.rj-dr');
            var crEl  = row.querySelector('.rj-cr');
            var partEl= row.querySelector('.rj-part');
            var ledger= sel ? parseInt($(sel).val()) : 0;
            var drAmt = parseFloat(drEl ? drEl.value : 0) || 0;
            var crAmt = parseFloat(crEl ? crEl.value : 0) || 0;
            var part  = partEl ? partEl.value.trim() : '';
            if (!ledger) { valid = false; return; }
            if (drAmt > 0) { lines.push({ LedgerUID: ledger, Type: 'Debit',  Amount: drAmt, Particulars: part }); dr += drAmt; }
            if (crAmt > 0) { lines.push({ LedgerUID: ledger, Type: 'Credit', Amount: crAmt, Particulars: part }); cr += crAmt; }
        });
        if (!valid || lines.length === 0) { Swal.fire({ icon: 'warning', title: 'All lines must have an account selected.' }); return; }
        if (Math.abs(dr - cr) > 0.01)    { Swal.fire({ icon: 'warning', title: 'Journal is not balanced', text: 'Debit must equal Credit.' }); return; }

        var btn = document.getElementById('rjSaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

        var fd = new FormData();
        fd.append('RecurUID',  recurUID);
        fd.append('Title',     title);
        fd.append('Narration', narration);
        fd.append('Frequency', frequency);
        fd.append('StartDate', startDate);
        if (endDate) fd.append('EndDate', endDate);
        fd.append('Lines',     JSON.stringify(lines));

        fetch(_baseUrl + 'accounting/saveRecurringJournal', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-save me-1"></i>Save';
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                bootstrap.Modal.getInstance(document.getElementById('recurJournalModal')).hide();
                Swal.fire({ icon: 'success', title: d.Message, timer: 1800, showConfirmButton: false })
                    .then(function () { _rjLoad(1); });
            })
            .catch(function () { btn.disabled = false; btn.innerHTML = '<i class="bx bx-save me-1"></i>Save'; });
    });

    // â”€â”€ Post Now button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjTableBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.rj-post-btn');
        if (!btn) return;
        var uid   = btn.dataset.uid;
        var title = btn.dataset.title;
        Swal.fire({
            title: 'Post Now?',
            text : 'Post "' + title + '" for today\'s date?',
            icon : 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Post',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var fd = new FormData(); fd.append('RecurUID', uid);
            fetch(_baseUrl + 'accounting/postRecurringJournal', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                    var msg = d.Message;
                    if (d.Ended) msg += ' Schedule ended â€” journal paused.';
                    Swal.fire({ icon: 'success', title: 'Posted!', text: msg, timer: 2200, showConfirmButton: false })
                        .then(function () { _rjLoad(1); });
                });
        });
    });

    // â”€â”€ Post All Due â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjPostAllBtn').addEventListener('click', function () {
        Swal.fire({
            title: 'Post All Due Journals?',
            text : 'This will post all due and overdue recurring journals for today.',
            icon : 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Post All',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            fetch(_baseUrl + 'accounting/postAllDueJournals', { method: 'POST', body: new FormData() })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var icon = d.Error ? 'error' : (d.Posted > 0 ? 'success' : 'info');
                    Swal.fire({ icon: icon, title: d.Posted + ' Posted', text: d.Message, timer: 2500, showConfirmButton: false })
                        .then(function () { _rjLoad(1); });
                });
        });
    });

    // â”€â”€ Toggle Pause/Resume â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjTableBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.rj-toggle-btn');
        if (!btn) return;
        var uid    = btn.dataset.uid;
        var active = btn.dataset.active === '1';
        var fd = new FormData(); fd.append('RecurUID', uid);
        fetch(_baseUrl + 'accounting/toggleRecurringStatus', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                _rjLoad(1);
            });
    });

    // â”€â”€ Delete â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjTableBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.rj-delete-btn');
        if (!btn) return;
        var uid   = btn.dataset.uid;
        var title = btn.dataset.title;
        Swal.fire({
            title: 'Delete Recurring Journal?',
            text : '"' + title + '" will be deleted. Posted journals are not affected.',
            icon : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#dc3545',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var fd = new FormData(); fd.append('RecurUID', uid);
            fetch(_baseUrl + 'accounting/deleteRecurringJournal', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                    Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false })
                        .then(function () { _rjLoad(1); });
                });
        });
    });

    // â”€â”€ Stats filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.querySelectorAll('.rj-stat-filter').forEach(function (el) {
        el.addEventListener('click', function () {
            document.querySelectorAll('.rj-stat-filter').forEach(function (x) { x.classList.remove('active'); });
            this.classList.add('active');
            _activeFilter = this.dataset.filter;
            _rjLoad(1);
        });
    });

    // â”€â”€ Search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('rjSearch').addEventListener('input', function () {
        clearTimeout(_searchTimer);
        var val = this.value;
        document.getElementById('rjSearchClear').classList.toggle('d-none', !val);
        _searchTimer = setTimeout(function () { _rjLoad(1); }, 400);
    });
    document.getElementById('rjSearchClear').addEventListener('click', function () {
        document.getElementById('rjSearch').value = '';
        this.classList.add('d-none');
        _rjLoad(1);
    });
    document.getElementById('rjRefresh').addEventListener('click', function () { _rjLoad(1); });

    // â”€â”€ Load / paginate â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _rjLoad(pageNo) {
        pageNo = pageNo || 1;
        var search = document.getElementById('rjSearch').value.trim();
        var filter = { SearchAllData: search };
        if (_activeFilter === 'Active') filter.IsActive = 1;
        if (_activeFilter === 'Paused') filter.IsActive = 0;
        if (_activeFilter === 'Due')    filter.Due = 1;

        ajaxLoading(1);
        var fd = new FormData();
        fd.append('Filter', JSON.stringify(filter));

        fetch(_baseUrl + 'accounting/getRecurringJournalsPage/' + pageNo, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                ajaxLoading(0);
                if (d.Error) return;
                document.getElementById('rjTableBody').innerHTML    = d.RecordHtmlData;
                document.getElementById('rjPagination').innerHTML   = d.Pagination;
                if (d.Stats) {
                    document.querySelector('.rj-s-total').textContent  = d.Stats.TotalCount  || 0;
                    document.querySelector('.rj-s-active').textContent = d.Stats.ActiveCount || 0;
                    document.querySelector('.rj-s-due').textContent    = d.Stats.DueCount    || 0;
                    document.querySelector('.rj-s-paused').textContent = d.Stats.PausedCount || 0;
                }
            })
            .catch(function () { ajaxLoading(0); });
    }

    // Pagination delegate
    document.getElementById('rjPagination').addEventListener('click', function (e) {
        var a = e.target.closest('a[data-page]');
        if (!a) return;
        e.preventDefault();
        _rjLoad(parseInt(a.dataset.page));
    });

    // Reset modal on close
    document.getElementById('recurJournalModal').addEventListener('hidden.bs.modal', function () {
        _rjReset();
    });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
