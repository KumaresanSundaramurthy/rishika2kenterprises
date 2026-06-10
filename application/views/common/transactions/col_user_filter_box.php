<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Reusable column-level user filter box.
 * Designed to work with TransColFilter (js/transactions/col_filter.js).
 * Buttons use .tcf-apply-btn / .tcf-reset-btn so no per-page JS functions needed.
 *
 * Required $ColUserFilterConfig keys:
 *   'id'         => string   Unique DOM id for the box div  e.g. 'pmtCreatedByFilterBox'
 *   'triggerId'  => string   id of the <a> icon in the <th>  e.g. 'pmtCreatedByFilter'
 *   'checkClass' => string   CSS class on each checkbox  e.g. 'pmt-user-chk'
 *   'OrgUsers'   => array    Objects with UserUID + FullName (or FirstName / LastName)
 */
$cfg       = $ColUserFilterConfig ?? [];
$boxId     = htmlspecialchars($cfg['id']         ?? 'colUserFilterBox');
$triggerId = htmlspecialchars($cfg['triggerId']  ?? 'colUserFilterTrigger');
$chkClass  = htmlspecialchars($cfg['checkClass'] ?? 'col-user-filter-chk');
$orgUsers  = $cfg['OrgUsers'] ?? [];
?>
<div id="<?php echo $boxId; ?>"
     class="card mp-filterbox trans-col-filterbox"
     data-trigger-id="<?php echo $triggerId; ?>"
     data-chk-class="<?php echo $chkClass; ?>"
     style="min-width:220px;z-index:9999;display:none;position:fixed;">

    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-user me-1"></i>Created By</span>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($orgUsers)): ?>
            <span class="badge"><?php echo count($orgUsers); ?></span>
            <?php endif; ?>
            <button type="button" class="catg-filter-close-btn tcf-close-btn" title="Close">&times;</button>
        </div>
    </div>

    <?php if (!empty($orgUsers)): ?>

    <!-- Search -->
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control tcf-search-input" placeholder="Search users...">
        </div>
    </div>

    <!-- Select All -->
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input tcf-select-all"
               id="<?php echo $boxId; ?>SelectAll">
        <label class="small fw-semibold mb-0"
               for="<?php echo $boxId; ?>SelectAll">Select All</label>
    </div>

    <!-- Items -->
    <div class="catg-list" style="max-height:200px;overflow-y:auto;">
        <?php foreach ($orgUsers as $u):
            $name = trim($u->FullName ?? (($u->FirstName ?? '') . ' ' . ($u->LastName ?? '')));
        ?>
        <label class="catg-list-item">
            <input class="form-check-input <?php echo $chkClass; ?>"
                   type="checkbox"
                   value="<?php echo (int)$u->UserUID; ?>">
            <span><?php echo htmlspecialchars($name); ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span style="font-size:.8rem;">No users found</span>
    </div>
    <?php endif; ?>

    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm tcf-apply-btn">
            <i class="bx bx-check me-1"></i>Apply
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm tcf-reset-btn">
            <i class="bx bx-reset me-1"></i>Reset
        </button>
    </div>
</div>
