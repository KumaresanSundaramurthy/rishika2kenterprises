<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Generic reusable checklist filter panel.
 * Works with TransColFilter (js/transactions/col_filter.js).
 * Trigger element must have class "apex-filter-btn"; TransColFilter will
 * toggle "has-filter" on it when a selection is active.
 *
 * Required $ChecklistFilterConfig keys:
 *   'id'                => string   Unique DOM id for the box div
 *   'triggerId'         => string   id of the apex-filter-btn trigger element
 *   'checkClass'        => string   CSS class applied to each item checkbox
 *   'title'             => string   Panel header title
 *   'icon'              => string   Boxicons class  e.g. 'bx-purchase-tag'
 *   'items'             => array    Each element: ['value' => mixed, 'label' => string]
 *   'searchPlaceholder' => string   (optional) Search input placeholder text
 */
$cfg       = $ChecklistFilterConfig ?? [];
$boxId     = htmlspecialchars($cfg['id']                    ?? 'checklistFilterBox');
$triggerId = htmlspecialchars($cfg['triggerId']             ?? '');
$chkClass  = htmlspecialchars($cfg['checkClass']            ?? 'checklist-filter-chk');
$title     = htmlspecialchars($cfg['title']                 ?? 'Filter');
$icon      = htmlspecialchars($cfg['icon']                  ?? 'bx-filter-alt');
$searchPh  = htmlspecialchars($cfg['searchPlaceholder']     ?? 'Search...');
$items     = $cfg['items']                                  ?? [];
?>
<div id="<?php echo $boxId; ?>"
     class="card mp-filterbox trans-col-filterbox"
     data-trigger-id="<?php echo $triggerId; ?>"
     data-chk-class="<?php echo $chkClass; ?>"
     style="min-width:220px;z-index:9999;display:none;position:fixed;">

    <div class="catg-filter-header">
        <span class="catg-filter-title">
            <i class="bx <?php echo $icon; ?> me-1"></i><?php echo $title; ?>
        </span>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($items)): ?>
                <span class="badge"><?php echo count($items); ?></span>
            <?php endif; ?>
            <button type="button" class="catg-filter-close-btn tcf-close-btn" title="Close">&times;</button>
        </div>
    </div>

    <?php if (!empty($items)): ?>

    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control tcf-search-input" placeholder="<?php echo $searchPh; ?>">
        </div>
    </div>

    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input tcf-select-all"
               id="<?php echo $boxId; ?>SelectAll">
        <label class="small fw-semibold mb-0"
               for="<?php echo $boxId; ?>SelectAll">Select All</label>
    </div>

    <div class="catg-list" style="max-height:200px;overflow-y:auto;">
        <?php foreach ($items as $item): ?>
        <label class="catg-list-item">
            <input class="form-check-input <?php echo $chkClass; ?>"
                   type="checkbox"
                   value="<?php echo htmlspecialchars((string)($item['value'] ?? '')); ?>">
            <span><?php echo htmlspecialchars((string)($item['label'] ?? '')); ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <?php else: ?>

    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span style="font-size:.8rem;">No items found</span>
    </div>

    <?php endif; ?>

    <div class="catg-filter-footer d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm tcf-apply-btn flex-fill">
            <i class="bx bx-check me-1"></i>Apply
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm tcf-reset-btn flex-fill">
            <i class="bx bx-reset me-1"></i>Reset
        </button>
    </div>
</div>
