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
                    <a href="javascript:void(0);" class="apex-stat-item jl-ref-filter" data-ref="Manual" style="--stat-color:#7c3aed">
                        <div class="apex-stat-icon" style="background:#ede9ff"><i class="bx bx-pencil" style="color:#7c3aed"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Manual</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count jl-s-manual"><?php echo (int)($Stats->ManualCount ?? 0); ?></span></div>
                        </div>
                    </a>
                </div>

                <div class="container-xxl flex-grow-1">
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
                            <button class="btn btn-primary btn-sm" id="jlNewManualBtn">
                                <i class="bx bx-plus me-1"></i><?php echo t('btn_new_manual_journal', 'New Journal Entry'); ?>
                            </button>
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
                                        <th class="th-act" style="width:90px;">Actions</th>
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

<!-- ── New Manual Journal Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="manualJournalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="vtm-banner" style="--vtm-color:#7c3aed;--vtm-bg:#ede9ff;--vtm-icon-bg:rgba(124,58,237,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon"><i class="bx bx-pencil"></i></div>
                        <div>
                            <div class="vtm-doc-number"><?php echo t('modal_new_manual_journal', 'New Manual Journal Entry'); ?></div>
                            <div class="vtm-doc-meta"><?php echo t('modal_manual_journal_desc', 'Enter debit and credit lines — must balance before saving'); ?></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal"><i class="bx bx-x"></i></button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-4">
                <!-- Header fields -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><?php echo t('lbl_journal_date', 'Journal Date'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="mjDate" class="form-control flatpickr-input" placeholder="Select date" autocomplete="off" readonly>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label fw-semibold"><?php echo t('lbl_narration', 'Narration'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="mjNarration" class="form-control" maxlength="250" placeholder="e.g. Monthly depreciation entry">
                    </div>
                </div>

                <!-- Journal lines table -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" id="mjLinesTable">
                        <thead class="r2k-thead">
                            <tr>
                                <th style="width:32px;">#</th>
                                <th><?php echo t('lbl_account', 'Account (Ledger)'); ?></th>
                                <th class="text-end" style="width:160px;"><?php echo t('lbl_debit', 'Debit (Dr)'); ?></th>
                                <th class="text-end" style="width:160px;"><?php echo t('lbl_credit', 'Credit (Cr)'); ?></th>
                                <th style="width:200px;"><?php echo t('lbl_particulars', 'Particulars'); ?></th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="mjLinesBody"></tbody>
                        <tfoot>
                            <tr class="fw-semibold" style="background:#f8f5ff;">
                                <td colspan="2" class="text-end text-muted" style="font-size:.8rem;"><?php echo t('lbl_totals', 'Totals'); ?></td>
                                <td class="text-end text-success" id="mjTotalDr">0.00</td>
                                <td class="text-end text-danger" id="mjTotalCr">0.00</td>
                                <td colspan="2">
                                    <span id="mjBalanceBadge" class="badge bg-label-secondary"><?php echo t('badge_enter_lines', 'Enter lines'); ?></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button class="btn btn-sm btn-label-secondary mt-3" id="mjAddRow">
                    <i class="bx bx-plus me-1"></i><?php echo t('btn_add_row', 'Add Row'); ?>
                </button>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo t('btn_cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="mjSaveBtn" disabled>
                    <span class="spinner-border spinner-border-sm d-none me-1" id="mjSaveSpinner"></span>
                    <?php echo t('btn_save_journal', 'Save Journal Entry'); ?>
                </button>
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
        $('.jl-s-manual').text(s.ManualCount || 0);
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

    // New Manual Journal button
    $('#jlNewManualBtn').on('click', function () {
        _mjReset();
        $('#manualJournalModal').modal('show');
    });

    // Delete Manual journal (with reversal)
    $(document).on('click', '.jl-delete-manual-btn', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: t('swal_delete_journal', 'Delete Journal Entry?'),
            html : (num ? '<strong>' + num + '</strong><br>' : '') +
                   t('swal_delete_journal_desc', 'A reversal entry will be posted to cancel this journal\'s effect on ledger balances.'),
            icon : 'warning', showCancelButton: true,
            confirmButtonColor: '#ef4444', cancelButtonColor: '#6c757d',
            confirmButtonText: t('btn_yes_delete', 'Yes, Delete'),
            cancelButtonText: t('btn_cancel', 'Cancel')
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(0);
            $.post('/accounting/deleteManualJournal', { JournalUID: uid, [CsrfName]: CsrfToken }, function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                ajaxLoading(1);
                if (resp.Error) {
                    showAlertMessageSwal('error', '', resp.Message);
                } else {
                    showToastNotification(resp.Message, 'success');
                    _load(_page);
                }
            });
        });
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

<script>
// ── Manual Journal modal logic ────────────────────────────────────────────────
(function () {
    'use strict';

    var _dec     = 2;
    var _cur     = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var _rowIdx  = 0;

    /** @returns {void} */
    function _mjReset() {
        _rowIdx = 0;
        $('#mjDate').val('');
        if (_mjFp) _mjFp.clear();
        $('#mjNarration').val('');
        $('#mjLinesBody').empty();
        _mjUpdateTotals();
        // Start with 2 blank rows
        _mjAddRow();
        _mjAddRow();
    }

    /** @returns {string} */
    function _mjRowHtml(idx) {
        return '<tr id="mjRow' + idx + '">' +
            '<td class="text-muted" style="font-size:.78rem;width:32px;">' + (idx + 1) + '</td>' +
            '<td><select class="form-select form-select-sm mj-ledger-sel" style="min-width:220px;" data-row="' + idx + '"></select></td>' +
            '<td><input type="number" class="form-control form-control-sm text-end mj-dr" data-row="' + idx + '" min="0" step="0.01" placeholder="0.00"></td>' +
            '<td><input type="number" class="form-control form-control-sm text-end mj-cr" data-row="' + idx + '" min="0" step="0.01" placeholder="0.00"></td>' +
            '<td><input type="text" class="form-control form-control-sm mj-part" data-row="' + idx + '" maxlength="150" placeholder="Optional"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-icon btn-sm text-danger mj-remove-row" data-row="' + idx + '" title="Remove"><i class="bx bx-x"></i></button></td>' +
            '</tr>';
    }

    /**
     * @param {number} [rowIdxHint]
     * @returns {void}
     */
    function _mjAddRow(rowIdxHint) {
        var idx = (typeof rowIdxHint !== 'undefined') ? rowIdxHint : _rowIdx++;
        $('#mjLinesBody').append(_mjRowHtml(idx));
        var $sel = $('#mjRow' + idx + ' .mj-ledger-sel');
        $sel.select2({
            dropdownParent: $('#manualJournalModal'),
            placeholder: t('ph_search_account', 'Search account…'),
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: '/accounting/getLedgersForJournal',
                dataType: 'json',
                delay: 200,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true
            },
            templateResult: function (d) {
                if (d.loading) return d.text;
                return $('<span>' + d.text + '</span>');
            }
        });
        if (typeof rowIdxHint === 'undefined') _rowIdx = idx + 1;
    }

    /** @returns {void} */
    function _mjUpdateTotals() {
        var dr = 0, cr = 0;
        $('#mjLinesBody tr').each(function () {
            dr += parseFloat($(this).find('.mj-dr').val()) || 0;
            cr += parseFloat($(this).find('.mj-cr').val()) || 0;
        });
        dr = Math.round(dr * Math.pow(10, _dec)) / Math.pow(10, _dec);
        cr = Math.round(cr * Math.pow(10, _dec)) / Math.pow(10, _dec);

        $('#mjTotalDr').text(dr.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }));
        $('#mjTotalCr').text(cr.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }));

        var $badge  = $('#mjBalanceBadge');
        var $saveBtn = $('#mjSaveBtn');
        var hasDr  = dr > 0, hasCr = cr > 0;

        if (!hasDr && !hasCr) {
            $badge.removeClass('bg-label-success bg-label-danger').addClass('bg-label-secondary')
                  .text(t('badge_enter_lines', 'Enter lines'));
            $saveBtn.prop('disabled', true);
            return;
        }
        var diff = Math.abs(dr - cr);
        if (diff < 0.01) {
            $badge.removeClass('bg-label-secondary bg-label-danger').addClass('bg-label-success')
                  .html('<i class="bx bx-check me-1"></i>' + t('badge_balanced', 'Balanced ✓'));
            $saveBtn.prop('disabled', false);
        } else {
            $badge.removeClass('bg-label-secondary bg-label-success').addClass('bg-label-danger')
                  .text(t('badge_off_by', 'Off by') + ' ' + _cur + ' ' +
                        diff.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec }));
            $saveBtn.prop('disabled', true);
        }
    }

    // Add row button
    $('#mjAddRow').on('click', function () { _mjAddRow(); });

    // Remove row
    $(document).on('click', '.mj-remove-row', function () {
        var count = $('#mjLinesBody tr').length;
        if (count <= 2) { showToastNotification(t('toast_min_rows', 'Minimum 2 rows required.'), 'warning'); return; }
        $('#mjRow' + $(this).data('row')).remove();
        _mjUpdateTotals();
    });

    // Amount input change
    $(document).on('input', '.mj-dr, .mj-cr', function () {
        _mjUpdateTotals();
    });

    // Save journal
    $('#mjSaveBtn').on('click', function () {
        var date = $('#mjDate').val();
        var narr = $.trim($('#mjNarration').val());
        if (!date) { showAlertMessageSwal('warning', '', t('alert_journal_date', 'Please select a journal date.')); return; }
        if (!narr) { showAlertMessageSwal('warning', '', t('alert_narration', 'Please enter a narration.')); return; }

        var lines = [];
        var hasAccount = true;
        $('#mjLinesBody tr').each(function () {
            var $row  = $(this);
            var uid   = parseInt($row.find('.mj-ledger-sel').val()) || 0;
            var dr    = parseFloat($row.find('.mj-dr').val()) || 0;
            var cr    = parseFloat($row.find('.mj-cr').val()) || 0;
            var part  = $.trim($row.find('.mj-part').val());
            if (!uid && (dr > 0 || cr > 0)) { hasAccount = false; return false; }
            if (!uid) return;
            if (dr > 0 && cr > 0) { hasAccount = false; return false; }
            if (dr > 0) lines.push({ LedgerUID: uid, Type: 'Debit',  Amount: dr, Particulars: part });
            if (cr > 0) lines.push({ LedgerUID: uid, Type: 'Credit', Amount: cr, Particulars: part });
        });

        if (!hasAccount) {
            showAlertMessageSwal('warning', '', t('alert_account_required', 'Each row with an amount must have a ledger account selected. A row cannot have both Debit and Credit filled.'));
            return;
        }
        if (lines.length < 2) {
            showAlertMessageSwal('warning', '', t('alert_min_lines', 'At least 2 lines with amounts are required.'));
            return;
        }

        $('#mjSaveSpinner').removeClass('d-none');
        $('#mjSaveBtn').prop('disabled', true);
        ajaxLoading(0);

        $.ajax({
            url    : '/accounting/saveManualJournal',
            method : 'POST',
            data   : { JournalDate: date, Narration: narr, Lines: lines, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                ajaxLoading(1);
                $('#mjSaveSpinner').addClass('d-none');
                if (resp.Error) {
                    showAlertMessageSwal('error', '', resp.Message);
                    $('#mjSaveBtn').prop('disabled', false);
                } else {
                    showToastNotification(resp.Message, 'success');
                    $('#manualJournalModal').modal('hide');
                    // Reload list and reset filter to Manual so the new entry is visible
                    $('.jl-ref-filter[data-ref="Manual"]').trigger('click');
                }
            },
            error: function () {
                ajaxLoading(1);
                $('#mjSaveSpinner').addClass('d-none');
                $('#mjSaveBtn').prop('disabled', false);
                showAlertMessageSwal('error', '', t('toast_save_failed', 'Failed to save journal entry.'));
            }
        });
    });

    // Reset modal when it is hidden
    $('#manualJournalModal').on('hidden.bs.modal', function () {
        _mjReset();
    });

    // ── Flatpickr for journal date ──────────────────────────────────────────
    var _mjFp = flatpickr('#mjDate', {
        dateFormat : 'Y-m-d',
        altInput   : true,
        altFormat  : (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y',
        static     : true,
        position   : 'below left',
        maxDate    : 'today',
    });

    // Prime with 2 rows on first open
    _mjReset();

}());
</script>
