<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object|null $CurrentLock */ $CurrentLock = $CurrentLock ?? null;
$dateFmt   = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$isLocked  = $CurrentLock !== null;
$lockDisplay = $isLocked ? date($dateFmt, strtotime($CurrentLock->LockedUpTo)) : null;
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
                                        <div class="pl-status-icon <?php echo $isLocked ? 'pl-icon-locked' : 'pl-icon-open'; ?>">
                                            <i class="bx <?php echo $isLocked ? 'bx-lock-alt' : 'bx-lock-open-alt'; ?> fs-3"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:1rem;" id="plStatusTitle">
                                                <?php echo $isLocked ? 'Books Locked' : 'No Lock Active'; ?>
                                            </div>
                                            <div class="text-muted" style="font-size:.83rem;" id="plStatusDesc">
                                                <?php if ($isLocked): ?>
                                                    All journal entries on or before
                                                    <strong><?php echo $lockDisplay; ?></strong>
                                                    are blocked from editing or posting.
                                                <?php else: ?>
                                                    All periods are open. Anyone can post journal entries to any date.
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($isLocked): ?>
                                    <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between" style="font-size:.8rem;">
                                        <span class="text-muted">
                                            Locked on <?php echo date($dateFmt, strtotime($CurrentLock->LockedOn)); ?>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="plRemoveBtn">
                                            <i class="bx bx-lock-open-alt me-1"></i>Remove Lock
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- ── Set Lock Date Card ──────────────────────── -->
                            <div class="card">
                                <div class="card-header" style="background:transparent;border-bottom:1px solid var(--bs-border-color);">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="bx bx-calendar-check me-2 text-warning"></i>
                                        <?php echo $isLocked ? 'Advance Lock Date' : 'Lock a Period'; ?>
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <p class="text-muted mb-3" style="font-size:.83rem;">
                                        Select the last date of the period you want to lock.
                                        All journal entries dated on or before this date will be blocked from new postings.
                                        <?php if ($isLocked): ?>
                                        The lock date can only be moved forward, not backward.
                                        <?php endif; ?>
                                    </p>

                                    <div class="d-flex gap-2 align-items-end">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold" style="font-size:.83rem;">Lock All Periods Up To</label>
                                            <input type="text" id="plLockDate" class="form-control" readonly
                                                   placeholder="Select date"
                                                   <?php if ($isLocked): ?>
                                                   data-mindate="<?php echo date('Y-m-d', strtotime($CurrentLock->LockedUpTo . ' +1 day')); ?>"
                                                   <?php endif; ?>>
                                        </div>
                                        <button type="button" class="btn btn-warning" id="plSaveBtn">
                                            <i class="bx bx-lock-alt me-1"></i><?php echo $isLocked ? 'Advance Lock' : 'Lock Period'; ?>
                                        </button>
                                    </div>

                                    <div class="mt-3 p-3 rounded" style="background:#fef9ee;border:1px solid #fde68a;font-size:.78rem;" id="plWarningBox">
                                        <i class="bx bx-info-circle me-1 text-warning"></i>
                                        <strong>Important:</strong> Once a period is locked, no journal can be posted or reversed
                                        within that period — including invoices, payments, expenses, and manual entries.
                                        Only remove the lock if a correction is genuinely required.
                                    </div>
                                </div>
                            </div>

                            <!-- ── Lock History Note ───────────────────────── -->
                            <?php if ($isLocked): ?>
                            <div class="mt-3 text-center text-muted" style="font-size:.75rem;">
                                Lock was set by user #<?php echo (int)$CurrentLock->LockedBy; ?>.
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <?php $this->load->view('common/footer'); ?>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/apex/sweetalert2'); ?>

<script>
(function () {
    'use strict';

    var _baseUrl  = '<?php echo base_url(); ?>';
    var _isLocked = <?php echo $isLocked ? 'true' : 'false'; ?>;
    var _dateFmt  = '<?php echo $dateFmt; ?>';

    // ── Flatpickr ─────────────────────────────────────────────────────────────
    var _fpOptions = {
        static   : true,
        position : 'below left',
        dateFormat: 'Y-m-d',
        altInput : true,
        altFormat: _transFormDateFormat || 'd M Y',
    };
    <?php if ($isLocked): ?>
    _fpOptions.minDate = '<?php echo date('Y-m-d', strtotime($CurrentLock->LockedUpTo . ' +1 day')); ?>';
    <?php endif; ?>
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#plLockDate', _fpOptions);
    }

    // ── Save / Advance lock ───────────────────────────────────────────────────
    document.getElementById('plSaveBtn').addEventListener('click', function () {
        var lockDate = document.getElementById('plLockDate').value;
        if (!lockDate) {
            Swal.fire({ icon: 'warning', title: 'Please select a date', timer: 1800, showConfirmButton: false });
            return;
        }

        var actionText = _isLocked ? 'advance the lock to' : 'lock all periods up to';
        var dispDate   = document.querySelector('#plLockDate').nextElementSibling
                         ? document.querySelector('#plLockDate').nextElementSibling.value
                         : lockDate;

        Swal.fire({
            title: _isLocked ? 'Advance Lock Date?' : 'Lock This Period?',
            html : 'This will ' + actionText + ' <strong>' + dispDate + '</strong>.<br>' +
                   'No journals can be posted to dates on or before this date.',
            icon : 'warning',
            showCancelButton : true,
            confirmButtonText: _isLocked ? 'Yes, Advance' : 'Yes, Lock',
            confirmButtonColor: '#d97706',
        }).then(function (res) {
            if (!res.isConfirmed) return;

            var btn = document.getElementById('plSaveBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            var fd = new FormData();
            fd.append('LockDate', lockDate);
            fetch(_baseUrl + 'accounting/savePeriodLock', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-lock-alt me-1"></i>' + (_isLocked ? 'Advance Lock' : 'Lock Period');
                    if (d.Error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: d.Message });
                        return;
                    }
                    Swal.fire({ icon: 'success', title: 'Period Locked', text: d.Message, timer: 2000, showConfirmButton: false })
                        .then(function () { window.location.reload(); });
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-lock-alt me-1"></i>' + (_isLocked ? 'Advance Lock' : 'Lock Period');
                });
        });
    });

    // ── Remove lock ───────────────────────────────────────────────────────────
    var removeBtn = document.getElementById('plRemoveBtn');
    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Remove Period Lock?',
                text : 'This opens all periods for posting. Only do this if a genuine correction is needed.',
                icon : 'warning',
                showCancelButton : true,
                confirmButtonText: 'Yes, Remove Lock',
                confirmButtonColor: '#dc3545',
            }).then(function (res) {
                if (!res.isConfirmed) return;
                fetch(_baseUrl + 'accounting/removePeriodLock', { method: 'POST', body: new FormData() })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.Error) { Swal.fire({ icon: 'error', title: 'Error', text: d.Message }); return; }
                        Swal.fire({ icon: 'success', title: 'Lock Removed', text: d.Message, timer: 2000, showConfirmButton: false })
                            .then(function () { window.location.reload(); });
                    });
            });
        });
    }

}());
</script>

<?php $this->load->view('common/footer_scripts'); ?>
