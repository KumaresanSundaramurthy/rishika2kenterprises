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
/* global $, global_base_url, global_services_url, JwtData, ajaxLoading, CsrfName, CsrfToken, currencySymbol, decimalPlaces, Swal, flatpickr, _transFormDateFormat */
(function () {
    'use strict';

    var _cur    = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _dec    = (typeof decimalPlaces  !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;
    var _fpFrom = null, _fpTo = null;

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + Math.abs(n).toLocaleString('en-IN',
            { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
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
        var payload = {
            DateFrom    : from,
            DateTo      : to,
            CashBankOnly: (filter === 'cashbank') ? '1' : '0',
            LedgerUID   : (typeof filter === 'string' && filter.indexOf('ledger:') === 0) ? filter.split(':')[1] : '0',
        };
        payload[CsrfName] = CsrfToken;
        $.post(global_base_url + 'accounting/getDayBookAjax', payload, function (d) {
            CsrfToken = d.NewCsrfToken || CsrfToken;
            $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Day Book');
            ajaxLoading(0);
            if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
            $('#dbSummaryBar').removeClass('d-none');
            $('#dbSumDr').text(_fmt(d.GrandDr));
            $('#dbSumCr').text(_fmt(d.GrandCr));
            $('#dbDayCount').text(d.DayCount + ' day(s)');
            $('#dbEntriesArea').html(d.Html || '<div class="text-center text-muted py-4">No entries found for this period.</div>');
            $('#dbPrintBtn').removeClass('d-none');
        }).fail(function () {
            $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Load Day Book');
            ajaxLoading(0);
        });
    });

    $(document).on('click', '#dbPrintBtn', function () { window.print(); });

    $(function () { _loadAccountOptions(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
