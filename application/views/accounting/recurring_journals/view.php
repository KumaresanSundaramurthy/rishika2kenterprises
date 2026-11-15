<?php defined('BASEPATH') or exit('No direct script access allowed');
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

                <!-- ── Stats Strip ──────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter active" data-filter="All" style="--stat-color:#0284c7">
                        <div class="apex-stat-icon" style="background:#e0f2fe"><i class="bx bx-repeat" style="color:#0284c7"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">All Journals</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-total">0</span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter" data-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-active">0</span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter" data-filter="Due" style="--stat-color:#f59e0b">
                        <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-bell" style="color:#f59e0b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Due / Overdue</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-due">0</span></div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item rj-stat-filter" data-filter="Paused" style="--stat-color:#64748b">
                        <div class="apex-stat-icon" style="background:#f1f5f9"><i class="bx bx-pause-circle" style="color:#64748b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Paused</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count rj-s-paused">0</span></div>
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
                                <tbody id="rjTableBody"></tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="apex-pagination-wrap" id="rjPagination"></div>
                    </div>
                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Recurring Journal Modal ─────────────────────────────────────────────── -->
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
                    <span class="fw-semibold" style="font-size:var(--r2k-text-85);">Journal Lines</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="rjAddRowBtn">
                        <i class="bx bx-plus me-1"></i>Add Line
                    </button>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0" style="font-size:var(--r2k-text-82);">
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
                    <span class="text-muted" style="font-size:var(--r2k-text-md);">
                        Dr: <strong id="rjTotalDr">₹ 0.00</strong>
                        &nbsp;|&nbsp;
                        Cr: <strong id="rjTotalCr">₹ 0.00</strong>
                    </span>
                    <span id="rjBalanceBadge" class="badge bg-secondary" style="font-size:var(--r2k-text-md);">Not balanced</span>
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
/* global $, global_base_url, global_services_url, JwtData, RowLimit, ajaxLoading, CsrfName, CsrfToken, currencySymbol, decimalPlaces, showToastNotification, showAlertMessageSwal, t, Swal, flatpickr, _transFormDateFormat */
(function () {
    'use strict';

    var _cur          = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _dec          = (typeof decimalPlaces  !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;
    var _activeFilter = 'All';
    var _searchTimer  = null;
    var _rjRowIdx     = 0;
    var _rjPage       = 1;

    var _rjFreqBadge = {
        'Daily'    : 'bg-label-warning',
        'Weekly'   : 'bg-label-info',
        'Monthly'  : 'bg-label-primary',
        'Quarterly': 'bg-label-success',
        'Yearly'   : 'bg-label-secondary',
    };

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return $('<div>').text(String(str || '')).html();
    }

    /**
     * @param {Array}  rows
     * @param {number} offset
     * @returns {string}
     */
    function _rjBuildRows(rows, offset) {
        if (!rows || !rows.length) {
            return '<tr><td colspan=”8” style=”padding:0;border:none;”>' +
                '<div class=”d-flex flex-column align-items-center py-5”>' +
                '<img src=”/assets/img/elements/no-record-found.png” alt=”No Records” class=”img-fluid mb-3” style=”max-height:130px;object-fit:contain;”>' +
                '<span class=”text-muted” style=”font-size:var(--r2k-text-90);”>No recurring journals found</span>' +
                '</div></td></tr>';
        }
        var today = new Date().toISOString().slice(0, 10);
        var html  = '';
        $.each(rows, function (i, row) {
            var uid      = row.RecurUID || 0;
            var isActive = parseInt(row.IsActive || 0, 10);
            var nextRun  = row.NextRunDate || '';
            var endDate  = row.EndDate || '';
            var status, sCls;
            if (!isActive) {
                status = 'Paused'; sCls = 'bg-label-secondary';
            } else if (endDate && nextRun > endDate) {
                status = 'Ended'; sCls = 'bg-secondary';
            } else if (nextRun && nextRun < today) {
                status = 'Overdue'; sCls = 'bg-label-danger';
            } else if (nextRun === today) {
                status = 'Due Today'; sCls = 'bg-label-warning';
            } else {
                status = 'Scheduled'; sCls = 'bg-label-info';
            }
            var isDue   = isActive && (status === 'Overdue' || status === 'Due Today');
            var freq    = row.Frequency || '';
            var freqCls = _rjFreqBadge[freq] || 'bg-label-secondary';

            html += '<tr class=”' + (isDue ? 'rj-row-due' : '') + '”>';
            html += '<td class=”text-muted” style=”font-size:var(--r2k-text-78);”>' + (offset + i + 1) + '</td>';
            html += '<td><div class=”fw-semibold” style=”font-size:var(--r2k-text-85);”>' + _esc(row.Title || '—') + '</div>';
            if (row.Narration) html += '<div class=”text-muted” style=”font-size:var(--r2k-text-78);”>' + _esc(row.Narration) + '</div>';
            html += '</td>';
            html += '<td><span class=”badge ' + freqCls + '” style=”font-size:var(--r2k-text-70);”>' + _esc(freq) + '</span></td>';
            html += '<td style=”font-size:var(--r2k-text-82);white-space:nowrap;”>' + _esc(row.StartDate || '—') + '<span class=”text-muted”> → </span>';
            html += endDate ? _esc(endDate) : '<em class=”text-muted”>Ongoing</em>';
            html += '</td>';
            html += '<td style=”font-size:var(--r2k-text-82);white-space:nowrap;”>';
            if (isDue && status === 'Overdue') html += '<span class=”text-danger fw-semibold”>' + _esc(nextRun || '—') + '</span>';
            else html += _esc(nextRun || '—');
            html += '</td>';
            html += '<td style=”font-size:var(--r2k-text-82);color:#64748b;”>' + _esc(row.LastRunDate || '—') + '</td>';
            html += '<td><span class=”badge ' + sCls + '” style=”font-size:var(--r2k-text-70);”>' + status + '</span></td>';
            html += '<td class=”text-center”><div class=”d-flex align-items-center justify-content-center gap-1”>';
            if (isDue) {
                html += '<button class=”btn btn-icon btn-sm text-warning rj-post-btn” data-uid=”' + uid + '” data-title=”' + _esc(row.Title || '') + '” title=”Post Now”><i class=”bx bx-play-circle”></i></button>';
            }
            html += '<button class=”btn btn-icon btn-sm text-primary rj-edit-btn” data-uid=”' + uid + '” title=”Edit”><i class=”bx bx-edit”></i></button>';
            if (isActive) {
                html += '<button class=”btn btn-icon btn-sm text-warning rj-toggle-btn” data-uid=”' + uid + '” data-active=”1” title=”Pause”><i class=”bx bx-pause-circle”></i></button>';
            } else {
                html += '<button class=”btn btn-icon btn-sm text-success rj-toggle-btn” data-uid=”' + uid + '” data-active=”0” title=”Resume”><i class=”bx bx-play-circle”></i></button>';
            }
            html += '<button class=”btn btn-icon btn-sm text-danger rj-delete-btn” data-uid=”' + uid + '” data-title=”' + _esc(row.Title || '') + '” title=”Delete”><i class=”bx bx-trash-alt”></i></button>';
            html += '</div></td></tr>';
        });
        return html;
    }

    /**
     * @param {number} total
     * @param {number} page
     * @returns {string}
     */
    function _rjBuildPagination(total, page) {
        var totalPages = Math.ceil(total / RowLimit);
        if (totalPages <= 1) return '';
        var from = (page - 1) * RowLimit + 1;
        var to   = Math.min(page * RowLimit, total);
        var html = '<div class=”col-auto text-muted” style=”font-size:var(--r2k-text-82);”>Showing ' + from + '–' + to + ' of ' + total + '</div>';
        html += '<nav class=”col-auto”><ul class=”pagination pagination-sm mb-0 gap-1”>';
        html += '<li class=”page-item' + (page <= 1 ? ' disabled' : '') + '”><a class=”page-link” href=”#” data-page=”' + (page - 1) + '”>&laquo;</a></li>';
        var start = Math.max(1, page - 2), end = Math.min(totalPages, start + 4);
        start = Math.max(1, end - 4);
        for (var p = start; p <= end; p++) {
            html += '<li class=”page-item' + (p === page ? ' active' : '') + '”><a class=”page-link” href=”#” data-page=”' + p + '”>' + p + '</a></li>';
        }
        html += '<li class=”page-item' + (page >= totalPages ? ' disabled' : '') + '”><a class=”page-link” href=”#” data-page=”' + (page + 1) + '”>&raquo;</a></li>';
        html += '</ul></nav>';
        return html;
    }

    /**
     * @param {object} s
     * @returns {void}
     */
    function _rjUpdateStats(s) {
        if (!s) return;
        $('.rj-s-total').text(s.TotalCount  || 0);
        $('.rj-s-active').text(s.ActiveCount || 0);
        $('.rj-s-due').text(s.DueCount    || 0);
        $('.rj-s-paused').text(s.PausedCount || 0);
    }

    /**
     * @param {number} [pageNo]
     * @returns {void}
     */
    function _rjLoad(pageNo) {
        _rjPage = pageNo || 1;
        var search = $.trim($('#rjSearch').val());
        var filter = { SearchAllData: search };
        if (_activeFilter === 'Active') filter.IsActive = 1;
        if (_activeFilter === 'Paused') filter.IsActive = 0;
        if (_activeFilter === 'Due')    filter.Due      = 1;
        ajaxLoading(1);
        $.ajax({
            url:      global_services_url + 'api/accounting/getRecurringJournalsPage/' + _rjPage,
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(filter) },
            dataType: 'json',
            success:  function (r) {
                if (r.Error) { showToastNotification(r.Message || 'Failed to load.', 'error'); return; }
                var rows   = r.Data || [];
                var offset = (_rjPage - 1) * RowLimit;
                $('#rjTableBody').html(_rjBuildRows(rows, offset));
                $('#rjPagination').html(_rjBuildPagination(r.TotalCount || 0, _rjPage));
                _rjUpdateStats(r.Stats || null);
            },
            error:    function () { showToastNotification('Failed to load recurring journals.', 'error'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    // ── Row HTML builder (for modal lines) ────────────────────────────────────
    /**
     * @param {number} idx
     * @returns {string}
     */
    function _rjRowHtml(idx) {
        return '<tr id=”rj-row-' + idx + '”>' +
            '<td class=”text-center text-muted” style=”vertical-align:middle;”>' + (idx + 1) + '</td>' +
            '<td><select class=”rj-ledger-select form-select form-select-sm” data-idx=”' + idx + '” style=”min-width:200px;”><option value=””>Search account...</option></select></td>' +
            '<td><input type=”number” class=”form-control form-control-sm rj-dr” min=”0” step=”0.01” placeholder=”0.00” data-idx=”' + idx + '”></td>' +
            '<td><input type=”number” class=”form-control form-control-sm rj-cr” min=”0” step=”0.01” placeholder=”0.00” data-idx=”' + idx + '”></td>' +
            '<td><input type=”text” class=”form-control form-control-sm rj-part” placeholder=”Particulars (optional)” data-idx=”' + idx + '”></td>' +
            '<td class=”text-center”><button type=”button” class=”btn btn-icon btn-sm text-danger rj-remove-row” data-idx=”' + idx + '” title=”Remove”><i class=”bx bx-minus-circle”></i></button></td>' +
        '</tr>';
    }

    /**
     * @returns {void}
     */
    function _rjAddRow() {
        $('#rjLinesBody').append(_rjRowHtml(_rjRowIdx));
        var rowSel = '#rj-row-' + _rjRowIdx;
        $(rowSel + ' .rj-ledger-select').select2({
            dropdownParent:    $('#recurJournalModal'),
            placeholder:       'Search account...',
            minimumInputLength: 1,
            ajax: {
                url:      global_base_url + 'accounting/getLedgersForJournal',
                dataType: 'json',
                delay:    250,
                data:     function (p) { return { q: p.term }; },
                processResults: function (d) { return { results: d.results || [] }; },
            },
        });
        var $dr = $(rowSel + ' .rj-dr');
        var $cr = $(rowSel + ' .rj-cr');
        $dr.on('input', function () { if (parseFloat($(this).val()) > 0) $cr.val(''); _rjUpdateTotals(); });
        $cr.on('input', function () { if (parseFloat($(this).val()) > 0) $dr.val(''); _rjUpdateTotals(); });
        _rjRowIdx++;
    }

    /**
     * @returns {void}
     */
    function _rjUpdateTotals() {
        var dr = 0, cr = 0;
        $('.rj-dr').each(function () { dr += parseFloat($(this).val()) || 0; });
        $('.rj-cr').each(function () { cr += parseFloat($(this).val()) || 0; });
        $('#rjTotalDr').text(_cur + ' ' + dr.toFixed(_dec));
        $('#rjTotalCr').text(_cur + ' ' + cr.toFixed(_dec));
        var diff = Math.abs(dr - cr);
        if (diff < 0.01 && dr > 0) {
            $('#rjBalanceBadge').attr('class', 'badge bg-success').text('Balanced ✓');
        } else {
            $('#rjBalanceBadge').attr('class', 'badge bg-danger').text('Off by ' + _cur + ' ' + diff.toFixed(_dec));
        }
    }

    /**
     * @returns {void}
     */
    function _rjReset() {
        $('#rjRecurUID').val('0');
        $('#rjTitle').val('');
        $('#rjNarration').val('');
        $('#rjFrequency').val('Monthly');
        $('#rjModalTitle').text('New Recurring Journal');
        $('#rjLinesBody').empty();
        $('#rjTotalDr').text(_cur + ' 0.00');
        $('#rjTotalCr').text(_cur + ' 0.00');
        $('#rjBalanceBadge').attr('class', 'badge bg-secondary').text('Not balanced');
        var fpStart = $('#rjStartDate')[0] && $('#rjStartDate')[0]._flatpickr;
        var fpEnd   = $('#rjEndDate')[0]   && $('#rjEndDate')[0]._flatpickr;
        if (fpStart) fpStart.clear(); else $('#rjStartDate').val('');
        if (fpEnd)   fpEnd.clear();   else $('#rjEndDate').val('');
        _rjRowIdx = 0;
        _rjAddRow();
        _rjAddRow();
    }

    // ── Flatpickr ─────────────────────────────────────────────────────────────
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#rjStartDate', { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y' });
        flatpickr('#rjEndDate',   { static: true, position: 'below left', dateFormat: 'Y-m-d', altInput: true, altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y' });
    }

    // ── Stat strip filter ─────────────────────────────────────────────────────
    $(document).on('click', '.rj-stat-filter', function () {
        $('.rj-stat-filter').removeClass('active');
        $(this).addClass('active');
        _activeFilter = $(this).data('filter');
        _rjLoad(1);
    });

    // ── Search ────────────────────────────────────────────────────────────────
    $(document).on('input', '#rjSearch', function () {
        clearTimeout(_searchTimer);
        var val = $(this).val();
        $('#rjSearchClear').toggleClass('d-none', !val);
        _searchTimer = setTimeout(function () { _rjLoad(1); }, 400);
    });
    $(document).on('click', '#rjSearchClear', function () {
        $('#rjSearch').val('');
        $(this).addClass('d-none');
        _rjLoad(1);
    });
    $(document).on('click', '#rjRefresh', function () { _rjLoad(_rjPage); });

    // ── Pagination ────────────────────────────────────────────────────────────
    $(document).on('click', '#rjPagination a[data-page]', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10); if (pg) _rjLoad(pg);
    });

    // ── Add row ───────────────────────────────────────────────────────────────
    $(document).on('click', '#rjAddRowBtn', _rjAddRow);

    // ── Remove row ────────────────────────────────────────────────────────────
    $(document).on('click', '#rjLinesBody .rj-remove-row', function () {
        if ($('#rjLinesBody tr').length <= 2) {
            Swal.fire({ icon: 'warning', title: 'Minimum 2 lines required', timer: 2000, showConfirmButton: false });
            return;
        }
        $(this).closest('tr').remove();
        _rjUpdateTotals();
    });

    // ── New button ────────────────────────────────────────────────────────────
    $(document).on('click', '#rjNewBtn', function () {
        _rjReset();
        $('#recurJournalModal').modal('show');
    });

    // ── Edit button ───────────────────────────────────────────────────────────
    $(document).on('click', '#rjTableBody .rj-edit-btn', function () {
        var uid = $(this).data('uid');
        ajaxLoading(1);
        $.get(global_base_url + 'accounting/getRecurringJournalAjax', { RecurUID: uid }, function (d) {
            ajaxLoading(0);
            if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
            _rjReset();
            $('#rjRecurUID').val(d.RecurUID);
            $('#rjTitle').val(d.Title);
            $('#rjNarration').val(d.Narration);
            $('#rjFrequency').val(d.Frequency);
            $('#rjModalTitle').text('Edit Recurring Journal');
            var fpStart = $('#rjStartDate')[0] && $('#rjStartDate')[0]._flatpickr;
            var fpEnd   = $('#rjEndDate')[0]   && $('#rjEndDate')[0]._flatpickr;
            if (fpStart) fpStart.setDate(d.StartDate);
            if (fpEnd && d.EndDate) fpEnd.setDate(d.EndDate);
            $('#rjLinesBody').empty();
            _rjRowIdx = 0;
            $.each(d.Lines || [], function (i, l) {
                _rjAddRow();
                var rowIdx = _rjRowIdx - 1;
                var $sel   = $('#rj-row-' + rowIdx + ' .rj-ledger-select');
                var opt    = new Option(l.LedgerName, l.LedgerUID, true, true);
                $sel.append(opt).trigger('change');
                if (l.TransactionType === 'Debit') {
                    $('#rj-row-' + rowIdx + ' .rj-dr').val(l.Amount);
                } else {
                    $('#rj-row-' + rowIdx + ' .rj-cr').val(l.Amount);
                }
                $('#rj-row-' + rowIdx + ' .rj-part').val(l.Particulars || '');
            });
            _rjUpdateTotals();
            $('#recurJournalModal').modal('show');
        });
    });

    // ── Save ──────────────────────────────────────────────────────────────────
    $(document).on('click', '#rjSaveBtn', function () {
        var recurUID  = $('#rjRecurUID').val();
        var title     = $.trim($('#rjTitle').val());
        var narration = $.trim($('#rjNarration').val());
        var frequency = $('#rjFrequency').val();
        var startDate = $('#rjStartDate').val();
        var endDate   = $('#rjEndDate').val();
        if (!title)     { Swal.fire({ icon: 'warning', title: 'Title is required' }); return; }
        if (!narration) { Swal.fire({ icon: 'warning', title: 'Narration is required' }); return; }
        if (!startDate) { Swal.fire({ icon: 'warning', title: 'Start date is required' }); return; }
        var lines = [], dr = 0, cr = 0, valid = true;
        $('#rjLinesBody tr').each(function (i, row) {
            var $row   = $(row);
            var ledger = parseInt($row.find('.rj-ledger-select').val()) || 0;
            var drAmt  = parseFloat($row.find('.rj-dr').val())  || 0;
            var crAmt  = parseFloat($row.find('.rj-cr').val())  || 0;
            var part   = $.trim($row.find('.rj-part').val());
            if (!ledger) { valid = false; return; }
            if (drAmt > 0) { lines.push({ LedgerUID: ledger, Type: 'Debit',  Amount: drAmt, Particulars: part }); dr += drAmt; }
            if (crAmt > 0) { lines.push({ LedgerUID: ledger, Type: 'Credit', Amount: crAmt, Particulars: part }); cr += crAmt; }
        });
        if (!valid || lines.length === 0) { Swal.fire({ icon: 'warning', title: 'All lines must have an account selected.' }); return; }
        if (Math.abs(dr - cr) > 0.01)    { Swal.fire({ icon: 'warning', title: 'Journal is not balanced', text: 'Debit must equal Credit.' }); return; }
        var $btn = $(this).prop('disabled', true).html('<span class=”spinner-border spinner-border-sm me-1”></span>Saving...');
        var payload = { RecurUID: recurUID, Title: title, Narration: narration, Frequency: frequency, StartDate: startDate, Lines: JSON.stringify(lines) };
        if (endDate) payload.EndDate = endDate;
        payload[CsrfName] = CsrfToken;
        $.post(global_base_url + 'accounting/saveRecurringJournal', payload, function (d) {
            CsrfToken = d.NewCsrfToken || CsrfToken;
            $btn.prop('disabled', false).html('<i class=”bx bx-save me-1”></i>Save');
            if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
            $('#recurJournalModal').modal('hide');
            Swal.fire({ icon: 'success', title: d.Message, timer: 1800, showConfirmButton: false })
                .then(function () { _rjLoad(1); });
        }).fail(function () {
            $btn.prop('disabled', false).html('<i class=”bx bx-save me-1”></i>Save');
            showToastNotification('Request failed.', 'error');
        });
    });

    // ── Post Now ──────────────────────────────────────────────────────────────
    $(document).on('click', '#rjTableBody .rj-post-btn', function () {
        var uid   = $(this).data('uid');
        var title = $(this).data('title');
        Swal.fire({
            title: 'Post Now?', text: 'Post “' + title + '” for today\'s date?',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Post',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var payload = { RecurUID: uid };
            payload[CsrfName] = CsrfToken;
            ajaxLoading(1);
            $.post(global_base_url + 'accounting/postRecurringJournal', payload, function (d) {
                CsrfToken = d.NewCsrfToken || CsrfToken;
                ajaxLoading(0);
                if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
                var msg = d.Message; if (d.Ended) msg += ' Schedule ended — journal paused.';
                Swal.fire({ icon: 'success', title: 'Posted!', text: msg, timer: 2200, showConfirmButton: false })
                    .then(function () { _rjLoad(1); });
            });
        });
    });

    // ── Post All Due ──────────────────────────────────────────────────────────
    $(document).on('click', '#rjPostAllBtn', function () {
        Swal.fire({
            title: 'Post All Due Journals?', text: 'This will post all due and overdue recurring journals for today.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Post All',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var payload = {};
            payload[CsrfName] = CsrfToken;
            ajaxLoading(1);
            $.post(global_base_url + 'accounting/postAllDueJournals', payload, function (d) {
                CsrfToken = d.NewCsrfToken || CsrfToken;
                ajaxLoading(0);
                var icon = d.Error ? 'error' : (d.Posted > 0 ? 'success' : 'info');
                Swal.fire({ icon: icon, title: d.Posted + ' Posted', text: d.Message, timer: 2500, showConfirmButton: false })
                    .then(function () { _rjLoad(1); });
            });
        });
    });

    // ── Toggle Pause/Resume ───────────────────────────────────────────────────
    $(document).on('click', '#rjTableBody .rj-toggle-btn', function () {
        var uid     = $(this).data('uid');
        var payload = { RecurUID: uid };
        payload[CsrfName] = CsrfToken;
        ajaxLoading(1);
        $.post(global_base_url + 'accounting/toggleRecurringStatus', payload, function (d) {
            CsrfToken = d.NewCsrfToken || CsrfToken;
            ajaxLoading(0);
            if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
            _rjLoad(_rjPage);
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    $(document).on('click', '#rjTableBody .rj-delete-btn', function () {
        var uid   = $(this).data('uid');
        var title = $(this).data('title');
        Swal.fire({
            title: 'Delete Recurring Journal?', text: '”' + title + '” will be deleted. Posted journals are not affected.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, Delete', confirmButtonColor: '#dc3545',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var payload = { RecurUID: uid };
            payload[CsrfName] = CsrfToken;
            ajaxLoading(1);
            $.post(global_base_url + 'accounting/deleteRecurringJournal', payload, function (d) {
                CsrfToken = d.NewCsrfToken || CsrfToken;
                ajaxLoading(0);
                if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
                showToastNotification('Deleted.', 'success');
                _rjLoad(1);
            });
        });
    });

    // ── Reset modal on close ──────────────────────────────────────────────────
    $('#recurJournalModal').on('hidden.bs.modal', function () { _rjReset(); });

    $(function () { _rjLoad(1); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
