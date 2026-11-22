<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Day Book',
                    'pageDescription' => 'Chronological journal entry view for any date range',
                    'pageIcon'        => 'bx-book',
                    'pageIconBg'      => '#f0fdf4',
                    'pageIconColor'   => '#15803d',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Filter Card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">From Date</label>
                                    <input type="text" id="dbDateFrom" class="form-control form-control-sm" readonly
                                           placeholder="From">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">To Date</label>
                                    <input type="text" id="dbDateTo" class="form-control form-control-sm" readonly
                                           placeholder="To">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Account Filter</label>
                                    <select id="dbAccountFilter" class="form-select form-select-sm">
                                        <option value="all">All Accounts (Day Book)</option>
                                        <option value="cashbank">Cash &amp; Bank Only (Cash Book)</option>
                                    </select>
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-success btn-sm" id="dbLoadBtn">
                                        <i class="bx bx-play me-1"></i>Load Day Book
                                    </button>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button class="btn btn-outline-secondary btn-sm d-none" id="dbPrintBtn">
                                        <i class="bx bx-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Summary bar ────────────────────────────────────── -->
                    <div class="d-none mb-3" id="dbSummaryBar">
                        <div class="d-flex gap-3 flex-wrap align-items-center">
                            <span class="badge bg-label-success" style="font-size:var(--r2k-text-md);">
                                <i class="bx bx-trending-up me-1"></i>Total Dr: <strong id="dbSumDr">—</strong>
                            </span>
                            <span class="badge bg-label-danger" style="font-size:var(--r2k-text-md);">
                                <i class="bx bx-trending-down me-1"></i>Total Cr: <strong id="dbSumCr">—</strong>
                            </span>
                            <span class="text-muted" style="font-size:var(--r2k-text-78);" id="dbDayCount"></span>
                        </div>
                    </div>

                    <!-- ── Entries Area ───────────────────────────────────── -->
                    <div class="card">
                        <div class="card-body p-0" id="dbEntriesArea">
                            <div class="d-flex flex-column align-items-center py-5 text-muted" id="dbEmptyState">
                                <i class="bx bx-book fs-1 mb-2" style="color:#ccc;"></i>
                                <span style="font-size:var(--r2k-text-90);">Select dates and click Load Day Book</span>
                            </div>
                        </div>
                    </div>

                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<script>
/* global $, global_services_url, JwtData, ajaxLoading, currencySymbol, decimalPlaces, Swal, flatpickr, _transFormDateFormat */
(function () {
    'use strict';

    var _cur    = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _dec    = (typeof decimalPlaces  !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;
    var _fpFrom = null, _fpTo = null;

    var _refBadge = {
        'Manual'     : ['bg-label-secondary', 'bx-edit-alt'],
        'Recurring'  : ['bg-label-info',      'bx-repeat'],
        'Invoice'    : ['bg-label-primary',   'bx-purchase-tag'],
        'Purchase'   : ['bg-label-warning',   'bx-cart'],
        'Payment-In' : ['bg-label-success',   'bx-money'],
        'Payment-Out': ['bg-label-danger',    'bx-money'],
        'Return-In'  : ['bg-label-dark',      'bx-undo'],
        'Return-Out' : ['bg-label-dark',      'bx-redo'],
        'Bank Recon' : ['bg-label-info',      'bx-bank'],
    };

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) { return $('<span>').text(String(s || '')).html(); }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN',
            { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function _dbFmt(n) {
        if (Math.abs(n || 0) < 0.005) return '';
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN',
            { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {object} d  NestJS getDayBookAjax response
     * @returns {string}
     */
    function _dbBuildHtml(d) {
        var orgName  = _esc((JwtData && JwtData.Org) ? JwtData.Org.OrgName : '');
        var days     = d.Days || [];
        var grandDr  = parseFloat(d.GrandDr || 0);
        var grandCr  = parseFloat(d.GrandCr || 0);

        var html = '<div id="dbEntriesWrap">'
            + '<div class="text-center mb-3 pt-3 pl-print-header px-3">'
            + '<div class="fw-bold" style="font-size:1rem;">' + orgName + '</div>'
            + '<div class="fw-semibold" style="font-size:.92rem;">Day Book</div>'
            + '<div class="text-muted" style="font-size:var(--r2k-text-md);">Period: ' + _esc(d.DateFrom || '') + ' to ' + _esc(d.DateTo || '') + '</div>'
            + '</div>';

        if (!days.length) {
            html += '<div class="text-center text-muted py-4" style="font-size:var(--r2k-text-88);">'
                + '<i class="bx bx-calendar-x fs-3 d-block mb-2"></i>No journal entries found for this period.</div>';
        } else {
            $.each(days, function (i, day) {
                var dateDisp = _esc(day.date || '');
                var dailyDr  = parseFloat(day.dailyDr || 0);
                var dailyCr  = parseFloat(day.dailyCr || 0);

                html += '<div class="db-date-group">'
                    + '<div class="db-date-header d-flex align-items-center justify-content-between">'
                    + '<span class="fw-semibold"><i class="bx bx-calendar me-1"></i>' + dateDisp + '</span>'
                    + '<span class="db-daily-totals">'
                    + '<span class="text-success me-2"><i class="bx bx-trending-up me-1"></i>Dr ' + _dbFmt(dailyDr) + '</span>'
                    + '<span class="text-danger"><i class="bx bx-trending-down me-1"></i>Cr ' + _dbFmt(dailyCr) + '</span>'
                    + '</span></div>'
                    + '<div class="table-responsive"><table class="table table-sm align-middle mb-0 db-tbl">'
                    + '<thead class="db-tbl-head"><tr>'
                    + '<th style="width:130px;">Journal #</th>'
                    + '<th style="width:110px;">Reference</th>'
                    + '<th>Account</th>'
                    + '<th>Narration / Particulars</th>'
                    + '<th class="text-end" style="width:130px;">Debit</th>'
                    + '<th class="text-end" style="width:130px;">Credit</th>'
                    + '</tr></thead><tbody>';

                $.each(day.journals || [], function (j, journal) {
                    var refType  = journal.ReferenceType || 'Manual';
                    var badge    = _refBadge[refType] || ['bg-label-secondary', 'bx-receipt'];
                    var entries  = journal.entries || [];
                    var rowspan  = entries.length || 1;

                    $.each(entries, function (k, entry) {
                        var isDr    = entry.TransactionType === 'Debit';
                        var narr    = entry.Particulars || journal.Narration || '';
                        html += '<tr class="db-entry-row ' + (isDr ? 'db-row-dr' : 'db-row-cr') + '">';

                        if (k === 0) {
                            html += '<td rowspan="' + rowspan + '" class="db-journal-cell">'
                                + '<span class="fw-semibold" style="font-size:var(--r2k-text-78);">' + _esc(journal.JournalNo || '') + '</span>'
                                + '<div class="mt-1"><span class="badge ' + badge[0] + ' db-ref-badge"><i class="bx ' + badge[1] + ' me-1"></i>' + _esc(refType) + '</span></div>'
                                + (journal.ReferenceNo ? '<div class="text-muted mt-1" style="font-size:var(--r2k-text-70);">' + _esc(journal.ReferenceNo) + '</div>' : '')
                                + '</td>';
                        }

                        html += '<td style="font-size:var(--r2k-text-78);"></td>'
                            + '<td><span style="font-size:var(--r2k-text-82);">' + _esc(entry.LedgerName || '') + '</span>'
                            + '<span class="badge bg-label-secondary ms-1" style="font-size:.62rem;">' + _esc(entry.LedgerType || '') + '</span></td>'
                            + '<td style="font-size:var(--r2k-text-78);color:#6c757d;">' + _esc(narr) + '</td>'
                            + '<td class="text-end fw-semibold text-success db-amt" style="font-size:var(--r2k-text-82);">' + (isDr ? _dbFmt(parseFloat(entry.Amount || 0)) : '') + '</td>'
                            + '<td class="text-end fw-semibold text-danger db-amt" style="font-size:var(--r2k-text-82);">' + (!isDr ? _dbFmt(parseFloat(entry.Amount || 0)) : '') + '</td>'
                            + '</tr>';
                    });
                });

                html += '</tbody><tfoot><tr class="db-daily-total">'
                    + '<td colspan="4" class="text-end fw-bold" style="font-size:var(--r2k-text-78);">Day Total</td>'
                    + '<td class="text-end fw-bold text-success" style="font-size:var(--r2k-text-82);">' + _dbFmt(dailyDr) + '</td>'
                    + '<td class="text-end fw-bold text-danger" style="font-size:var(--r2k-text-82);">' + _dbFmt(dailyCr) + '</td>'
                    + '</tr></tfoot></table></div></div>';
            });

            /* Grand total bar */
            html += '<div class="db-grand-total d-flex justify-content-end align-items-center gap-4 px-3 py-2">'
                + '<div class="fw-bold" style="font-size:var(--r2k-text-85);">Grand Total</div>'
                + '<div>'
                + '<span class="text-success fw-bold" style="font-size:var(--r2k-text-85);">Dr ' + _fmt(grandDr) + '</span>'
                + '<span class="mx-2 text-muted">|</span>'
                + '<span class="text-danger fw-bold" style="font-size:var(--r2k-text-85);">Cr ' + _fmt(grandCr) + '</span>'
                + '</div></div>';
        }

        html += '</div>';
        return html;
    }

    /**
     * @returns {void}
     */
    function _loadAccountOptions() {
        $.ajax({
            url:      global_services_url + 'api/accounting/getDayBookPageData',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) {
                if (r.Error || !r.Data) return;
                var $sel = $('#dbAccountFilter');
                var grp  = $('<optgroup>').attr('label', 'Specific Account');
                $.each(r.Data || [], function (i, l) {
                    grp.append($('<option>').val('ledger:' + l.LedgerUID)
                        .text(l.LedgerType + ' — ' + l.LedgerName));
                });
                if (grp.children().length) $sel.append(grp);
            },
        });
    }

    var _fpCfg = {
        static: true, position: 'below left',
        dateFormat: 'Y-m-d', altInput: true,
        altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y',
    };

    if (typeof flatpickr !== 'undefined') {
        _fpFrom = flatpickr('#dbDateFrom', $.extend({}, _fpCfg, {
            defaultDate: new Date(),
            onChange: function (d) { if (_fpTo && d[0]) _fpTo.set('minDate', d[0]); },
        }));
        _fpTo = flatpickr('#dbDateTo', $.extend({}, _fpCfg, {
            defaultDate: new Date(),
            onChange: function (d) { if (_fpFrom && d[0]) _fpFrom.set('maxDate', d[0]); },
        }));
    }

    $(document).on('click', '#dbLoadBtn', function () {
        var from   = $('#dbDateFrom').val();
        var to     = $('#dbDateTo').val();
        var filter = $('#dbAccountFilter').val();
        if (!from || !to) {
            Swal.fire({ icon: 'warning', title: 'Please select both dates', timer: 1800, showConfirmButton: false });
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
        ajaxLoading(1);

        $.ajax({
            url:      global_services_url + 'api/accounting/getDayBookAjax',
            method:   'GET',
            data:     {
                DateFrom    : from,
                DateTo      : to,
                CashBankOnly: (filter === 'cashbank') ? '1' : '0',
                LedgerUID   : (typeof filter === 'string' && filter.indexOf('ledger:') === 0) ? filter.split(':')[1] : '0',
                orgId       : parseInt(JwtData.Org.OrgUID, 10),
            },
            dataType: 'json',
            success: function (d) {
                if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                $('#dbSummaryBar').removeClass('d-none');
                $('#dbSumDr').text(_fmt(d.GrandDr || 0));
                $('#dbSumCr').text(_fmt(d.GrandCr || 0));
                $('#dbDayCount').text((d.DayCount || 0) + ' day(s)');
                $('#dbEntriesArea').html(_dbBuildHtml(d));
                $('#dbPrintBtn').removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Day Book');
                ajaxLoading(0);
            },
        });
    });

    $(document).on('click', '#dbPrintBtn', function () { window.print(); });

    $(function () { _loadAccountOptions(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
