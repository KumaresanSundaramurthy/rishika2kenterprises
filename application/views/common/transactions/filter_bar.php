<?php
/**
 * Reusable filter bar partial for transaction list pages.
 *
 * Include this inside the trans-toolbar-actions div, before the date filter.
 *
 * Required view data:
 *   $FilterBarConfig = [
 *     'paymentStatus' => bool,          // show Payment Status filter
 *     'paymentMode'   => bool,          // show Payment Mode filter
 *     'party'         => 'customer'|'vendor'|false,
 *     'lastUpdated'   => bool,          // show only when user count > 1
 *     'PaymentTypes'  => [],            // array of objects from getPaymentTypesList()
 *     'OrgUsers'      => [],            // array of objects from getOrgUsersForCache()
 *   ];
 *
 * JS: Instantiate TransFilterBar once per page.
 *   var tfb = new TransFilterBar({ onChange: function () { getXxxDetails(1); } });
 *
 *   In your loadTransactionList call, merge state:
 *   var f = $.extend({}, Filter, tfb ? tfb.getState() : {});
 */

$cfg = $FilterBarConfig ?? [];

$showStatus      = !empty($cfg['paymentStatus']);
$showMode        = !empty($cfg['paymentMode']);
$partyType       = $cfg['party'] ?? false;          // 'customer'|'vendor'|false
$showLastUpdated = !empty($cfg['lastUpdated']);
$paymentTypes    = $cfg['PaymentTypes'] ?? [];
$orgUsers        = $cfg['OrgUsers']     ?? [];

// Build the JS config object
$jsConfig = json_encode([
    'paymentStatus' => $showStatus,
    'paymentMode'   => $showMode,
    'party'         => $partyType,
    'lastUpdated'   => $showLastUpdated,
    'partyType'     => $partyType ?: 'customer',
]);

$partyLabel = ($partyType === 'vendor') ? 'Vendor' : 'Customer';
?>

<?php if ($showStatus || $showMode || $partyType || $showLastUpdated): ?>
<div id="transFilterBar" data-filter-config='<?php echo $jsConfig; ?>' class="d-flex align-items-center gap-2">

    <?php /* ── Payment Status ─────────────────────────────────────────── */ ?>
    <?php if ($showStatus): ?>
    <div class="dropdown tfb-pill-wrap" id="tfbStatusWrap">
        <button class="r2k-dd-btn tfb-pill" type="button"
                data-filter-type="paymentStatus"
                data-default-label="Status"
                data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false">
            <span class="tfb-label">Status</span>
            <i class="bx bx-x tfb-clear-x d-none" style="font-size:.8rem;margin-left:2px;"></i>
            <i class="bx bx-chevron-down" style="font-size:.72rem;"></i>
        </button>
        <ul class="dropdown-menu shadow" style="min-width:160px;font-size:.82rem;">
            <li><a class="dropdown-item tfb-status-item" href="javascript:void(0);" data-val="Pending">
                <span class="badge me-1" style="background:#fff3e0;color:#e65100;border:1px solid #ffcc80;font-weight:500;">●</span> Pending
            </a></li>
            <li><a class="dropdown-item tfb-status-item" href="javascript:void(0);" data-val="Partially Paid">
                <span class="badge me-1" style="background:#e3f2fd;color:#0d47a1;border:1px solid #90caf9;font-weight:500;">●</span> Partially Paid
            </a></li>
            <li><a class="dropdown-item tfb-status-item" href="javascript:void(0);" data-val="Paid">
                <span class="badge me-1" style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;font-weight:500;">●</span> Paid
            </a></li>
        </ul>
    </div>
    <?php endif; ?>

    <?php /* ── Payment Mode ──────────────────────────────────────────── */ ?>
    <?php if ($showMode && !empty($paymentTypes)): ?>
    <div class="dropdown tfb-pill-wrap" id="tfbModeWrap">
        <button class="r2k-dd-btn tfb-pill" type="button"
                data-filter-type="paymentMode"
                data-default-label="Mode"
                data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false">
            <span class="tfb-label">Mode</span>
            <i class="bx bx-x tfb-clear-x d-none" style="font-size:.8rem;margin-left:2px;"></i>
            <i class="bx bx-chevron-down" style="font-size:.72rem;"></i>
        </button>
        <ul class="dropdown-menu shadow" style="min-width:160px;font-size:.82rem;">
            <?php foreach ($paymentTypes as $pt): ?>
            <li><a class="dropdown-item tfb-mode-item" href="javascript:void(0);"
                   data-val="<?php echo htmlspecialchars($pt->Name, ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($pt->Name); ?>
            </a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php /* ── Customer / Vendor ─────────────────────────────────────── */ ?>
    <?php if ($partyType): ?>
    <div class="dropdown tfb-pill-wrap" id="tfbPartyWrap">
        <button class="r2k-dd-btn tfb-pill" type="button"
                id="tfbPartyBtn"
                data-filter-type="party"
                data-default-label="<?php echo $partyLabel; ?>"
                data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false">
            <i class="bx bx-user" style="font-size:.8rem;"></i>
            <span class="tfb-label"><?php echo $partyLabel; ?></span>
            <i class="bx bx-x tfb-clear-x d-none" style="font-size:.8rem;margin-left:2px;"></i>
            <i class="bx bx-chevron-down" style="font-size:.72rem;"></i>
        </button>
        <div class="dropdown-menu shadow tfb-party-dropdown p-0" style="min-width:230px;font-size:.82rem;">
            <div class="p-2 border-bottom">
                <input type="text" id="tfbPartySearch" class="form-control form-control-sm"
                       placeholder="Search <?php echo strtolower($partyLabel); ?>…" autocomplete="off">
            </div>
            <div id="tfbPartyList" class="tfb-party-list">
                <div class="tfb-party-empty">Open to load list</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Last Updated (User) ─────────────────────────────────────── */ ?>
    <?php if ($showLastUpdated && !empty($orgUsers)): ?>
    <div class="dropdown tfb-pill-wrap" id="tfbUserWrap">
        <button class="r2k-dd-btn tfb-pill" type="button"
                data-filter-type="updatedBy"
                data-default-label="User"
                data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false">
            <i class="bx bx-user-check" style="font-size:.8rem;"></i>
            <span class="tfb-label">User</span>
            <i class="bx bx-x tfb-clear-x d-none" style="font-size:.8rem;margin-left:2px;"></i>
            <i class="bx bx-chevron-down" style="font-size:.72rem;"></i>
        </button>
        <ul class="dropdown-menu shadow" style="min-width:180px;font-size:.82rem;max-height:220px;overflow-y:auto;">
            <?php foreach ($orgUsers as $u): ?>
            <li><a class="dropdown-item tfb-user-item" href="javascript:void(0);"
                   data-uid="<?php echo (int)($u->UserUID ?? 0); ?>"
                   data-name="<?php echo htmlspecialchars(trim(($u->FirstName ?? '') . ' ' . ($u->LastName ?? '')), ENT_QUOTES); ?>">
                <?php echo htmlspecialchars(trim(($u->FirstName ?? '') . ' ' . ($u->LastName ?? ''))); ?>
            </a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php /* ── Active filter count badge + Clear All ─────────────────── */ ?>
    <span id="tfbActiveBadge" class="badge bg-primary d-none" style="font-size:.68rem;padding:3px 7px;border-radius:10px;"></span>
    <a href="javascript:void(0);" id="tfbClearAll" class="tfb-clear-all d-none" title="Clear all filters">
        <i class="bx bx-filter-alt"></i> Clear
    </a>

</div>

<style>
/* ── Filter bar pill (active state) ────────────────────────────────── */
.tfb-pill.tfb-active {
    background  : #e8f0fe;
    color       : #1a73e8;
    border-color: #a8c7fa;
    font-weight : 500;
}
.tfb-pill.tfb-active .tfb-clear-x { color: #1a73e8; }
.tfb-pill.tfb-active:hover         { background: #d2e3fc; }

/* ── Clear All link ─────────────────────────────────────────────────── */
.tfb-clear-all {
    font-size  : .78rem;
    color      : #6c757d;
    white-space: nowrap;
    display    : flex;
    align-items: center;
    gap        : 3px;
}
.tfb-clear-all:hover { color: #dc3545; }

/* ── Party dropdown list ────────────────────────────────────────────── */
.tfb-party-list {
    max-height  : 240px;
    overflow-y  : auto;
    padding     : 4px 0;
}
.tfb-party-item {
    display    : flex;
    align-items: center;
    justify-content: space-between;
    padding    : 5px 12px;
    color      : #212529;
    transition : background .12s;
    text-decoration: none;
}
.tfb-party-item:hover         { background: #f0f4ff; color: #212529; }
.tfb-party-item.tfb-party-selected { background: #e8f0fe; color: #1a73e8; }
.tfb-party-name { font-size: .82rem; }
.tfb-party-area { font-size: .72rem; color: #888; }
.tfb-party-empty {
    padding   : 10px 12px;
    color     : #888;
    font-size : .8rem;
    text-align: center;
}
</style>
<?php endif; ?>
