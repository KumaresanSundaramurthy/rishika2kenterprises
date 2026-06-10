<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Reusable party (customer / vendor) column filter box.
 *
 * Renders a floating card populated dynamically by TransPartyColFilter
 * from the Upstash HGETALL cache. Scroll-to-load, single-select.
 *
 * Also emits _upstashUrl / _upstashReadToken / _custCacheKey / _vendorCacheKey
 * JS variables so individual view files do not need to repeat them.
 *
 * Required $ColPartyFilterConfig keys:
 *   'id'        => string   Unique DOM id  e.g. 'soPartyFilterBox'
 *   'title'     => string   Header label   e.g. 'Filter by Customer'
 *   'icon'      => string   Boxicons class e.g. 'bx-user'
 */

$cfg   = $ColPartyFilterConfig ?? [];
$boxId = htmlspecialchars($cfg['id']    ?? 'partyFilterBox');
$title = htmlspecialchars($cfg['title'] ?? 'Filter');
$icon  = htmlspecialchars($cfg['icon']  ?? 'bx-user');
?>

<script>
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl   ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _custCacheKey     = '<?php echo addslashes($CustomerCacheKey ?? ''); ?>';
var _vendorCacheKey   = '<?php echo addslashes($VendorCacheKey   ?? ''); ?>';
</script>

<style>
.tpcf-box{padding:0!important;border-radius:12px!important;border:1px solid #c4c6f8!important;box-shadow:0 8px 32px rgba(105,108,255,.18);display:flex;flex-direction:column;overflow:hidden;}
.tpcf-list{overflow-y:auto;flex:1;}
.tpcf-item{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 14px;cursor:pointer;user-select:none;transition:background .12s;border-bottom:1px solid rgba(105,108,255,.06);}
.tpcf-item:hover{background:rgba(105,108,255,.08);color:#4547c0;}
.tpcf-item.tpcf-selected{background:rgba(105,108,255,.13);color:#696cff;font-weight:600;}
.tpcf-name{font-size:.83rem;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.tpcf-area{font-size:.7rem;color:#999;flex-shrink:0;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.tpcf-item.tpcf-selected .tpcf-area{color:#9496ff;}
.tpcf-empty{padding:24px 16px;text-align:center;font-size:.82rem;color:#999;}
.tpcf-selected-label{font-size:.7rem;color:#fff;opacity:.85;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;text-align:right;padding-right:8px;}
</style>

<div id="<?php echo $boxId; ?>"
     class="card tpcf-box"
     style="min-width:300px;z-index:9999;display:none;position:fixed;">

    <!-- Header -->
    <div class="catg-filter-header">
        <span class="catg-filter-title">
            <i class="bx <?php echo $icon; ?> me-1"></i><?php echo $title; ?>
        </span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge tpcf-count-badge" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn tpcf-close-btn" title="Close">&times;</button>
        </div>
    </div>

    <!-- Search -->
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control tpcf-search-input"
                   placeholder="Search <?php echo strtolower(str_replace('Filter by ', '', $title)); ?>...">
        </div>
    </div>

    <!-- Party list — filled by TransPartyColFilter from Upstash cache -->
    <div class="tpcf-list" style="max-height:260px;"></div>

    <!-- Footer -->
    <div class="catg-filter-footer" style="justify-content:space-between;align-items:center;">
        <button type="button" class="btn btn-outline-secondary btn-sm tpcf-clear-btn" style="display:none;">
            <i class="bx bx-x me-1"></i>Clear Filter
        </button>
        <span class="tpcf-hint text-muted ms-auto" style="font-size:.72rem;">Scroll to load more</span>
    </div>

</div>
