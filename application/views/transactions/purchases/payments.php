<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                                        $totals   = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $totalPaid = (float)($totals->TotalPaid ?? 0);

                    function ppFmt($val, $sym) {
                        return $sym . ' ' . smartDecimal((float)$val);
                    }
                    ?>

                    <!-- ── Stat Cards ─────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="trans-stat-card stat-active">
                                <div class="trans-stat-label">Total Payments</div>
                                <div class="trans-stat-count" id="ppStatCount">0</div>
                                <div class="trans-stat-amount" id="ppStatAmount">—</div>
                                <i class="bx bx-money-withdraw trans-stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Toolbar -->
                        <div class="trans-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="ppStatusTabs">
                                <li class="nav-item">
                                    <a class="nav-link pp-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-primary ms-1" id="ppTabCountSuccess">0</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pp-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <a href="<?= site_url('purchases') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Purchases
                                </a>
                                <div class="input-group input-group-sm" style="width:210px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="ppSearch" placeholder="Search vendor, amount, ref…">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="ppDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="ppDateFilterLabel">This Month</span><strong id="ppDateFilterDates" class="r2k-df-dates" style="display:none;"></strong>
                                    </button>
                                    <ul class="dropdown-menu shadow" id="ppDateFilterMenu"
                                        style="width:220px;max-height:360px;overflow-y:auto;font-size:var(--r2k-text-82);"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="ppTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:130px;">Amount</th>
                                        <th style="width:150px;">Mode / Bank</th>
                                        <th style="width:130px;">Linked Bill</th>
                                        <th>Vendor</th>
                                        <th style="width:160px;">Recorded By</th>
                                        <th style="width:120px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ppTableBody">
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:var(--r2k-text-md);">
                                    Total Paid Out: &nbsp;<strong class="text-danger" id="ppFooterPaid">—</strong>
                                </span>
                            </div>
                            <div id="ppPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:var(--r2k-text-78);">Total: <strong id="ppTotalCount">0</strong></span>
                            </div>
                        </div>

                    </div>

                </div>
                <?php $this->load->view('common/footer_desc'); ?>
            </div>
        </div>

    </div>
</div>

<!-- ── Payment Detail Modal (reused from payments page) ──────── -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"
                    style="background-color:#fff;border-radius:50%;padding:8px;box-shadow:0 2px 6px rgba(0,0,0,.15);"></button>
            <div style="background:#fff3e0;border-left:4px solid #f57c00;border-radius:8px 8px 0 0;padding:16px 20px 14px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;border-radius:10px;background:rgba(245,124,0,.12);display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-money-withdraw fs-4" style="color:#f57c00;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:1rem;" id="pdUniqueNumber">—</div>
                        <div class="text-muted" style="font-size:var(--r2k-text-78);" id="pdDateLabel">—</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="font-size:1.2rem;color:#f57c00;" id="pdAmount">—</div>
                        <div id="pdModeBadge" class="mt-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-body px-4 py-3">
                <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Vendor</div>
                            <div class="fw-semibold" style="font-size:var(--r2k-text-85);" id="pdParty">—</div>
                            <div class="text-muted" style="font-size:var(--r2k-text-sm);" id="pdPartyMobile"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Linked Bill</div>
                            <div class="fw-semibold text-primary" style="font-size:var(--r2k-text-85);" id="pdTransNumber">—</div>
                        </div>
                    </div>
                </div>
                <div id="pdBankSection" style="display:none;background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                    <div class="text-muted mb-2" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">
                        <i class="bx bx-building-house me-1"></i>Bank Details
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <div class="text-muted" style="font-size:var(--r2k-text-70);">Bank / Account Name</div>
                            <div class="fw-semibold" style="font-size:var(--r2k-text-82);" id="pdBankName">—</div>
                        </div>
                        <div class="col-5">
                            <div class="text-muted" style="font-size:var(--r2k-text-70);">Account Number</div>
                            <div class="fw-semibold" style="font-size:var(--r2k-text-82);font-family:monospace;" id="pdAccountNumber">—</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Reference No</div>
                        <div style="font-size:var(--r2k-text-85);" id="pdReference">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Recorded By</div>
                        <div style="font-size:var(--r2k-text-85);" id="pdCreatedBy">—</div>
                    </div>
                    <div class="col-12" id="pdNotesWrap" style="display:none;">
                        <div class="text-muted" style="font-size:var(--r2k-text-70);text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Notes</div>
                        <div style="font-size:var(--r2k-text-85);color:#444;" id="pdNotes"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script type="text/javascript" src="/js/common/datefilter.js"></script>
<script src="/js/core/pagination.js"></script>

<style>
.pp-tab { border-radius: 6px; font-size:var(--r2k-text-82); transition: background .15s; }
.pp-tab.active { background: #fff3e0; color: #f57c00 !important; }
.pp-tab:not(.active):hover { background: #f5f5f5; }
.pmt-mode-badge { font-size:var(--r2k-text-70); padding: 2px 8px; border-radius: 20px; }
</style>

<script>
var PpFilter = { DocTypeModuleUIDs: [105] };
var PpPageNo = 1;
var PpLimit  = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 10); ?>;

var _ppSym = '<?php echo addslashes(htmlspecialchars_decode($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>';
var _ppDec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? $JwtData->GenSettings->DecimalPlaces ?? 2); ?>;

/**
 * @param {number} v
 * @returns {string}
 */
function _ppFmt(v) {
    return _ppSym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: _ppDec, maximumFractionDigits: _ppDec });
}

var _PP_MODE_COLORS = {
    'cash'       : '#e8f5e9|#2e7d32',
    'upi'        : '#ede7f6|#4527a0',
    'card'       : '#e3f2fd|#1565c0',
    'net banking': '#fff8e1|#f57f17',
    'cheque'     : '#fce4ec|#880e4f',
    'debit note' : '#fff3e0|#e65100',
};

/**
 * @param {Array} rows
 * @returns {string}
 */
function _buildPpRows(rows) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="7" class="text-center py-5 text-muted">No payments found.</td></tr>';
    }
    var html = '';
    $.each(rows, function (i, r) {
        var isCancelled = r.IsCancelled == 1 || r.Status === 'Cancelled';
        var dateStr = (r.PaymentDate || '').slice(0, 10);
        var dateFmt = dateStr ? formatDateDisplay(dateStr) : '—';
        var mc = _PP_MODE_COLORS[(r.PaymentTypeName || '').toLowerCase().trim()] || '#f0f0f0|#555';
        var mcParts = mc.split('|');
        var modeBadge = '<span class="pmt-mode-badge" style="background:' + mcParts[0] + ';color:' + mcParts[1] + ';">' +
            (r.PaymentTypeName || '—') + '</span>';
        var bankLine = '';
        if (r.BankName && !r.IsCash) {
            bankLine = '<div class="text-muted" style="font-size:var(--r2k-text-70);margin-top:2px;">' +
                r.BankName + (r.AccountNumber ? ' · ' + r.AccountNumber : '') + '</div>';
        }

        html += '<tr class="pmt-row' + (isCancelled ? ' table-secondary' : '') + '"' +
            ' data-payment-uid="'   + parseInt(r.PaymentUID, 10) + '"' +
            ' data-unique-number="' + (r.UniqueNumber || '') + '"' +
            ' data-payment-date="'  + dateStr + '"' +
            ' data-raw-amount="'    + (r.Amount || 0) + '"' +
            ' data-payment-type="'  + (r.PaymentTypeName || '') + '"' +
            ' data-bank-name="'     + (r.BankName || '') + '"' +
            ' data-account-number="'+ (r.AccountNumber || '') + '"' +
            ' data-party-name="'    + (r.PartyName || '') + '"' +
            ' data-party-mobile="'  + (r.PartyMobile || '') + '"' +
            ' data-trans-number="'  + (r.TransNumber || '') + '"' +
            ' data-created-by="'    + (r.CreatedByName || '') + '"' +
            ' data-reference="'     + (r.ReferenceNo || '') + '"' +
            ' data-notes="'         + (r.Notes || '') + '"' +
            ' data-direction="'     + (r.Direction || 'Out') + '"' +
            ' data-is-cash="'       + (r.IsCash ? '1' : '0') + '"' +
            ' data-is-cn="0" data-is-oa="0">';

        /* Ref No */
        html += '<td class="ps-3"><div class="fw-semibold viewPaymentDetail" style="cursor:pointer;color:#696cff;">' +
            (r.UniqueNumber || '—') + '</div>' +
            '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + dateFmt + '</div></td>';

        /* Amount */
        html += '<td><div class="fw-semibold">' + _ppFmt(r.Amount) + '</div>' +
            (isCancelled ? '<span class="badge bg-label-danger" style="font-size:var(--r2k-text-xs);">Cancelled</span>' : '') + '</td>';

        /* Mode / Bank */
        html += '<td>' + modeBadge + bankLine + '</td>';

        /* Linked Bill */
        html += '<td><span class="text-muted" style="font-size:var(--r2k-text-82);">' + (r.TransNumber || '—') + '</span></td>';

        /* Vendor */
        html += '<td><div class="fw-semibold">' + (r.PartyName || '—') + '</div></td>';

        /* Recorded By */
        html += '<td><div class="text-muted" style="font-size:var(--r2k-text-82);">' + (r.CreatedByName || '—') + '</div></td>';

        /* Actions */
        html += '<td class="text-end pe-3">';
        if (!isCancelled) {
            html += '<button class="btn btn-sm btn-icon text-danger deletePayment" data-payment-uid="' + parseInt(r.PaymentUID, 10) + '"' +
                ' data-bs-toggle="tooltip" title="Delete payment"><i class="bx bx-trash"></i></button>';
        }
        html += '</td>';
        html += '</tr>';
    });
    return html;
}

/**
 * @param {number} pageNo
 * @returns {void}
 */
function getPurchasePayments(pageNo) {
    pageNo   = pageNo || 1;
    PpPageNo = pageNo;
    $('#ppTableBody').html('<tr><td colspan="7" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary"></span></td></tr>');
    $.ajax({
        url    : global_services_url + 'api/payments/getPaymentsPageDetails',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId   : parseInt(JwtData.Org.OrgUID, 10),
            userId  : parseInt(JwtData.User.UserUID, 10),
            branchId: parseInt(JwtData.Org.BranchUID, 10) || 1,
            RowLimit: PpLimit,
            PageNo  : pageNo,
            Filter  : JSON.stringify(PpFilter),
        },
        success: function (resp) {
            if (resp.Error) {
                $('#ppTableBody').html('<tr><td colspan="7" class="text-center py-4 text-danger">' + (resp.Message || 'Failed to load.') + '</td></tr>');
                return;
            }
            var total = parseInt(resp.TotalCount || 0, 10);
            var pg    = parseInt(resp.PageNo     || pageNo,   10);
            var rl    = parseInt(resp.RowLimit   || PpLimit,  10);
            var paid  = parseFloat(resp.TotalPaid || 0);

            $('#ppTableBody').html(_buildPpRows(resp.Data));
            $('#ppTotalCount').text(total.toLocaleString('en-IN'));
            $('#ppStatCount').text(total.toLocaleString('en-IN'));
            $('#ppTabCountSuccess').text(total.toLocaleString('en-IN'));
            if (paid > 0) {
                $('#ppFooterPaid').text(_ppFmt(paid));
                $('#ppStatAmount').text(_ppFmt(paid));
            }

            var pagHtml = generatePagination(total, pg, rl);
            var $pag    = $('#ppPagination');
            $pag.find('.r2k-pag-wrap').remove();
            if (pagHtml) { $pag.append(pagHtml); }
            if (typeof initTooltips === 'function') initTooltips();
        },
        error: function () {
            $('#ppTableBody').html('<tr><td colspan="7" class="text-center py-4 text-danger">Request failed.</td></tr>');
        }
    });
}

$(function () {
    'use strict';

    // Tab switch
    $(document).on('click', '.pp-tab', function (e) {
        e.preventDefault();
        $('.pp-tab').removeClass('active');
        $(this).addClass('active');
        PpFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        getPurchasePayments(1);
    });

    // Pagination
    $(document).on('click', '#ppPagination .r2k-pag-btn', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (pg && !$(this).hasClass('disabled')) getPurchasePayments(pg);
    });

    // Search
    var ppSearchTimer;
    $('#ppSearch').on('input', function () {
        clearTimeout(ppSearchTimer);
        var v = $.trim($(this).val());
        ppSearchTimer = setTimeout(function () {
            PpFilter.Search = v || undefined;
            getPurchasePayments(1);
        }, 400);
    });

    // Date filter — defaults to This Month
    initDateFilter({
        btnId  : 'ppDateFilterBtn',
        labelId: 'ppDateFilterLabel',
        fromId : 'ppCustomDateFrom',
        toId   : 'ppCustomDateTo',
        onApply: function (from, to) {
            PpFilter.DateFrom = from;
            PpFilter.DateTo   = to;
            getPurchasePayments(1);
        }
    });
    var _ppInitDr = getDateRange('this_month');
    PpFilter.DateFrom = _ppInitDr.from;
    PpFilter.DateTo   = _ppInitDr.to;
    $('#ppDateFilterBtn').addClass('r2k-date-active');
    $('#ppDateFilterDates').text(formatDateDisplay(_ppInitDr.from) + ' – ' + formatDateDisplay(_ppInitDr.to)).show();
    $(document).on('shown.bs.dropdown', '#ppDateFilterBtn', function () {
        if (!$('#ppCustomDateFrom').data('fpInit')) {
            flatpickr('#ppCustomDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#ppCustomDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#ppCustomDateFrom').data('fpInit', true);
        }
    });

    // View payment detail
    $(document).on('click', '.viewPaymentDetail', function () {
        var paymentUID = $(this).data('payment-uid');
        $.ajax({
            url    : global_services_url + 'api/payments/getPaymentDetail',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { orgId: parseInt(JwtData.Org.OrgUID, 10), PaymentUID: paymentUID },
            success: function (resp) {
                if (resp.Error) { Swal.fire('Error', resp.Message || 'Could not load payment.', 'error'); return; }
                var d = resp.Data;
                $('#pdUniqueNumber').text(d.UniqueNumber || ('Payment #' + d.PaymentUID));
                var dateStr = d.PaymentDate || (d.CreatedOn ? d.CreatedOn.slice(0, 10) : '');
                if (dateStr) {
                    var p = dateStr.split('-'), mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    dateStr = p[2] + ' ' + mo[parseInt(p[1], 10) - 1] + ' ' + p[0];
                }
                $('#pdDateLabel').text(dateStr || '—');
                $('#pdAmount').text(_ppFmt(d.Amount));
                var modeMap = { 'cash':'#e8f5e9|#2e7d32','upi':'#ede7f6|#4527a0','card':'#e3f2fd|#1565c0','net banking':'#fff8e1|#f57f17','cheque':'#fce4ec|#880e4f','debit note':'#fff3e0|#e65100' };
                var mk = (d.PaymentTypeName || '').toLowerCase().trim();
                var mc = modeMap[mk] ? modeMap[mk].split('|') : ['#f0f0f0','#555'];
                $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + (d.PaymentTypeName || '—') + '</span>');
                $('#pdParty').text(d.PartyName || '—');
                $('#pdPartyMobile').text(d.PartyMobile || '').toggle(!!d.PartyMobile);
                $('#pdTransNumber').text(d.TransNumber || '—');
                if (d.BankName) {
                    $('#pdBankName').text(d.BankName + (d.AccountName ? ' (' + d.AccountName + ')' : ''));
                    $('#pdAccountNumber').text(d.AccountNumber || '—');
                    $('#pdBankSection').show();
                } else {
                    $('#pdBankSection').hide();
                }
                $('#pdReference').text(d.ReferenceNo || '—');
                $('#pdCreatedBy').text(d.CreatedByName || '—');
                $('#pdNotes').text(d.Notes || '');
                $('#pdNotesWrap').toggle(!!d.Notes);
                $('#paymentDetailModal').modal('show');
            }
        });
    });

    // Delete payment
    $(document).on('click', '.deletePayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text : 'This will permanently remove the payment and restore the purchase bill balance.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url    : global_services_url + 'api/payments/deletePayment',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : { orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1, PaymentUID: paymentUID, Action: 'delete' },
                success: function (resp) {
                    ajaxLoading(0);
                    if (!resp.Error) {
                        $row.fadeOut(300, function () { $(this).remove(); });
                        Swal.fire({ icon: 'success', text: 'Payment deleted.', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', resp.Message, 'error');
                    }
                },
                error: function () {
                    ajaxLoading(0);
                    Swal.fire('Error', 'Server error. Please try again.', 'error');
                }
            });
        });
    });

    getPurchasePayments(1);

});
</script>
