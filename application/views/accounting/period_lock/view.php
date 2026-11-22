<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Period Lock',
                    'pageDescription' => 'Lock closed accounting periods to prevent back-dated journal entries',
                    'pageIcon'        => 'bx-lock-alt',
                    'pageIconBg'      => '#fef3c7',
                    'pageIconColor'   => '#d97706',
                ]); ?>

                <div class="container-xxl flex-grow-1">
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6">

                            <!-- ── Current Status Card ─────────────────────── -->
                            <div class="card mb-3">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="pl-status-icon pl-icon-open" id="plStatusIcon">
                                            <i class="bx bx-loader-alt bx-spin fs-3"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:1rem;" id="plStatusTitle">Loading...</div>
                                            <div class="text-muted" style="font-size:.83rem;" id="plStatusDesc"></div>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between d-none" id="plLockMeta" style="font-size:var(--r2k-text-md);">
                                        <span class="text-muted" id="plLockedOnText"></span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="plRemoveBtn">
                                            <i class="bx bx-lock-open-alt me-1"></i>Remove Lock
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Set Lock Date Card ──────────────────────── -->
                            <div class="card">
                                <div class="card-header" style="background:transparent;border-bottom:1px solid var(--bs-border-color);">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="bx bx-calendar-check me-2 text-warning"></i>
                                        <span id="plCardAction">Lock a Period</span>
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <p class="text-muted mb-3" style="font-size:.83rem;">
                                        Select the last date of the period you want to lock.
                                        All journal entries dated on or before this date will be blocked from new postings.
                                    </p>

                                    <div class="d-flex gap-2 align-items-end">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold" style="font-size:.83rem;">Lock All Periods Up To</label>
                                            <input type="text" id="plLockDate" class="form-control" readonly placeholder="Select date">
                                        </div>
                                        <button type="button" class="btn btn-warning" id="plSaveBtn">
                                            <i class="bx bx-lock-alt me-1"></i><span id="plSaveBtnText">Lock Period</span>
                                        </button>
                                    </div>

                                    <div class="mt-3 p-3 rounded" style="background:#fef9ee;border:1px solid #fde68a;font-size:var(--r2k-text-78);" id="plWarningBox">
                                        <i class="bx bx-info-circle me-1 text-warning"></i>
                                        <strong>Important:</strong> Once a period is locked, no journal can be posted or reversed
                                        within that period — including invoices, payments, expenses, and manual entries.
                                        Only remove the lock if a correction is genuinely required.
                                    </div>
                                </div>
                            </div>

                            <!-- ── Lock History Note ───────────────────────── -->
                            <div class="d-none mt-3 text-center text-muted" id="plLockHistoryNote" style="font-size:var(--r2k-text-75);"></div>

                        </div>
                    </div>
                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<script>
/* global $, global_base_url, global_services_url, JwtData, ajaxLoading, CsrfName, CsrfToken, showToastNotification, showAlertMessageSwal, Swal, flatpickr, _transFormDateFormat */
(function () {
    'use strict';

    var _isLocked = false;
    var _fp       = null;

    /**
     * @param {object|null} data
     * @returns {void}
     */
    function _renderStatus(data) {
        _isLocked = !!(data && data.IsLocked);
        var $icon = $('#plStatusIcon');
        if (_isLocked) {
            $icon.attr('class', 'pl-status-icon pl-icon-locked').html('<i class="bx bx-lock-alt fs-3"></i>');
            $('#plStatusTitle').text('Books Locked');
            $('#plStatusDesc').html('All journal entries on or before <strong>' + (data.LockDisplay || data.LockedUpTo || '') + '</strong> are blocked from editing or posting.');
            $('#plLockedOnText').text('Locked on ' + (data.LockedOnDisplay || data.LockedOn || ''));
            $('#plLockMeta').removeClass('d-none');
            $('#plCardAction').text('Advance Lock Date');
            $('#plSaveBtnText').text('Advance Lock');
            if (_fp && data.MinDate) _fp.set('minDate', data.MinDate);
            if (data.LockedBy) $('#plLockHistoryNote').text('Lock was set by user #' + data.LockedBy + '.').removeClass('d-none');
        } else {
            $icon.attr('class', 'pl-status-icon pl-icon-open').html('<i class="bx bx-lock-open-alt fs-3"></i>');
            $('#plStatusTitle').text('No Lock Active');
            $('#plStatusDesc').text('All periods are open. Anyone can post journal entries to any date.');
            $('#plLockMeta').addClass('d-none');
            $('#plLockHistoryNote').addClass('d-none');
            $('#plCardAction').text('Lock a Period');
            $('#plSaveBtnText').text('Lock Period');
            if (_fp) _fp.set('minDate', null);
        }
    }

    /**
     * @returns {void}
     */
    function _loadStatus() {
        $.ajax({
            url:      global_services_url + 'api/accounting/getPeriodLock',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) { _renderStatus(r.Data || null); },
            error:    function () { $('#plStatusTitle').text('Could not load status.'); $('#plStatusDesc').text(''); },
        });
    }

    // ── Flatpickr ─────────────────────────────────────────────────────────────
    if (typeof flatpickr !== 'undefined') {
        _fp = flatpickr('#plLockDate', {
            static   : true,
            position : 'below left',
            dateFormat: 'Y-m-d',
            altInput : true,
            altFormat: (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd M Y',
        });
    }

    // ── Save / Advance lock ───────────────────────────────────────────────────
    $(document).on('click', '#plSaveBtn', function () {
        var lockDate = $('#plLockDate').val();
        if (!lockDate) { Swal.fire({ icon: 'warning', title: 'Please select a date', timer: 1800, showConfirmButton: false }); return; }
        var dispDate   = $('#plLockDate').next('.flatpickr-input').val() || lockDate;
        var actionText = _isLocked ? 'advance the lock to' : 'lock all periods up to';
        Swal.fire({
            title: _isLocked ? 'Advance Lock Date?' : 'Lock This Period?',
            html : 'This will ' + actionText + ' <strong>' + dispDate + '</strong>.<br>No journals can be posted to dates on or before this date.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: _isLocked ? 'Yes, Advance' : 'Yes, Lock', confirmButtonColor: '#d97706',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var $btn = $('#plSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
            $.ajax({
                url:      global_services_url + 'api/accounting/savePeriodLock',
                method:   'POST',
                data:     { LockDate: lockDate, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
                dataType: 'json',
                success: function (d) {
                    $btn.prop('disabled', false).html('<i class="bx bx-lock-alt me-1"></i><span id="plSaveBtnText">' + (_isLocked ? 'Advance Lock' : 'Lock Period') + '</span>');
                    if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
                    Swal.fire({ icon: 'success', title: 'Period Locked', text: d.Message, timer: 2000, showConfirmButton: false })
                        .then(function () { _loadStatus(); if (_fp) _fp.clear(); });
                },
                error: function () {
                    $btn.prop('disabled', false).html('<i class="bx bx-lock-alt me-1"></i><span id="plSaveBtnText">' + (_isLocked ? 'Advance Lock' : 'Lock Period') + '</span>');
                    showToastNotification('Request failed.', 'error');
                }
            });
        });
    });

    // ── Remove lock ───────────────────────────────────────────────────────────
    $(document).on('click', '#plRemoveBtn', function () {
        Swal.fire({
            title: 'Remove Period Lock?',
            text : 'This opens all periods for posting. Only do this if a genuine correction is needed.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Remove Lock', confirmButtonColor: '#dc3545',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url:      global_services_url + 'api/accounting/removePeriodLock',
                method:   'POST',
                data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
                dataType: 'json',
                success: function (d) {
                    ajaxLoading(0);
                    if (d.Error) { showAlertMessageSwal('error', '', d.Message); return; }
                    Swal.fire({ icon: 'success', title: 'Lock Removed', text: d.Message, timer: 2000, showConfirmButton: false })
                        .then(function () { _loadStatus(); });
                },
                error: function () {
                    ajaxLoading(0);
                    showToastNotification('Request failed.', 'error');
                }
            });
        });
    });

    $(function () { _loadStatus(); });

}());
</script>

<?php $this->load->view('common/footer_script'); ?>
