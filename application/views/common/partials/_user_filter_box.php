<?php
/**
 * Common user filter box partial.
 *
 * Include via:
 *   $this->load->view('common/partials/_user_filter_box', [
 *       'OrgUsers'   => $orgUsers,          // array of objects with UserUID + FullName
 *       'BoxId'      => 'tlUserFilterBox',  // id of the filter panel div
 *       'CheckClass' => 'tl-user-checkbox', // CSS class for checkboxes
 *       'ApplyFn'    => 'tlApplyUserFilter',// JS function called by Apply button
 *       'ResetFn'    => 'tlResetUserFilter',// JS function called by Reset button
 *   ]);
 *
 * The toggle button (id="$BtnId") and its icon (id="$IconId") must be placed by the
 * calling view. This partial only renders the floating drop-down panel itself.
 */
defined('BASEPATH') or exit('No direct script access allowed');

$BoxId      = $BoxId      ?? 'userFilterBox';
$CheckClass = $CheckClass ?? 'user-filter-checkbox';
$ApplyFn    = $ApplyFn    ?? 'applyUserFilter';
$ResetFn    = $ResetFn    ?? 'resetUserFilter';
$OrgUsers   = $OrgUsers   ?? [];
?>
<div id="<?php echo htmlspecialchars($BoxId); ?>"
     class="mp-filterbox"
     style="display:none;position:fixed;z-index:9999;width:230px;max-height:320px;flex-direction:column;">

    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-user me-1"></i>Updated By</span>
        <button type="button" class="catg-filter-close-btn"
                onclick="document.getElementById('<?php echo htmlspecialchars($BoxId); ?>').style.display='none';"
                title="Close">&times;</button>
    </div>

    <div class="catg-list" style="flex:1;min-height:0;overflow-y:auto;">
        <?php foreach ($OrgUsers as $u):
            $name = trim($u->FullName ?? (($u->FirstName ?? '') . ' ' . ($u->LastName ?? '')));
        ?>
        <label class="catg-list-item">
            <input class="form-check-input <?php echo htmlspecialchars($CheckClass); ?>"
                   type="checkbox"
                   value="<?php echo (int)$u->UserUID; ?>">
            <span><?php echo htmlspecialchars($name); ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary"
                onclick="<?php echo htmlspecialchars($ApplyFn); ?>()">
            <i class="bx bx-check me-1"></i>Apply
        </button>
        <button type="button" class="btn btn-outline-secondary"
                onclick="<?php echo htmlspecialchars($ResetFn); ?>()">
            <i class="bx bx-reset me-1"></i>Reset
        </button>
    </div>

</div>
