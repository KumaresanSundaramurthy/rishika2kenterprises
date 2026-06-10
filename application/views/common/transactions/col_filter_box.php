<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Reusable column-level filter box partial.
 *
 * Renders a floating card filter panel (body-level, position:fixed)
 * that can be shared across any transaction list page.
 *
 * Required $ColFilterConfig keys:
 *   'id'          => string   Unique DOM id for the box div  e.g. 'invPayStatusFilterBox'
 *   'triggerId'   => string   DOM id of the <a> icon that opens it  e.g. 'invPayStatusFilter'
 *   'title'       => string   Header label  e.g. 'Payment Status'
 *   'icon'        => string   Boxicons class for header  e.g. 'bx-wallet-alt'
 *   'filterKey'   => string   JS Filter object key  e.g. 'PaymentStatus'
 *   'checkClass'  => string   CSS class applied to each checkbox  e.g. 'inv-pay-status-chk'
 *   'items'       => array    [ ['value' => '', 'label' => '', 'icon' => '', 'color' => ''] ]
 *                             icon/color are optional
 */

$cfg        = $ColFilterConfig ?? [];
$boxId      = htmlspecialchars($cfg['id']        ?? 'colFilterBox');
$triggerId  = htmlspecialchars($cfg['triggerId'] ?? 'colFilterTrigger');
$title      = htmlspecialchars($cfg['title']     ?? 'Filter');
$icon       = htmlspecialchars($cfg['icon']      ?? 'bx-filter-alt');
$filterKey  = htmlspecialchars($cfg['filterKey'] ?? 'Filter');
$chkClass   = htmlspecialchars($cfg['checkClass'] ?? 'col-filter-chk');
$items      = $cfg['items'] ?? [];
?>
<div id="<?php echo $boxId; ?>"
     class="card mp-filterbox trans-col-filterbox"
     data-trigger-id="<?php echo $triggerId; ?>"
     data-filter-key="<?php echo $filterKey; ?>"
     data-chk-class="<?php echo $chkClass; ?>"
     style="min-width:190px;z-index:9999;display:none;position:fixed;">

    <!-- Header -->
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

    <!-- Search -->
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control tcf-search-input"
                   placeholder="Search <?php echo strtolower($title); ?>...">
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
    <div class="catg-list" style="max-height:180px;">
        <?php foreach ($items as $item): ?>
        <?php
            $val   = htmlspecialchars($item['value'] ?? '', ENT_QUOTES);
            $label = htmlspecialchars($item['label'] ?? '');
        ?>
        <label class="catg-list-item">
            <input class="form-check-input <?php echo $chkClass; ?>"
                   type="checkbox" value="<?php echo $val; ?>">
            <span><?php echo $label; ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm tcf-apply-btn">
            <i class="bx bx-check me-1"></i>Apply
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm tcf-reset-btn">
            <i class="bx bx-reset me-1"></i>Reset
        </button>
    </div>

    <?php else: ?>
    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
        <i class="bx bx-info-circle fs-2 mb-2"></i>
        <span style="font-size:.8rem;">No items found</span>
    </div>
    <?php endif; ?>
</div>
