<?php defined('BASEPATH') or exit('No direct script access allowed');

$_from    = $this->pageData['_iwpInitFrom'] ?? date('Y-01-01');
$_to      = $this->pageData['_iwpInitTo']   ?? date('Y-m-d');
$_listFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-filter-bar{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.rpt-filter-group{display:flex;flex-direction:column;gap:4px}
.rpt-filter-label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.rpt-filter-date{width:150px}
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.rpt-stat-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07)}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
.rpt-icon-red{background:#fee2e2}.rpt-icon-red i{color:#dc2626}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rpt-table-wrap{overflow-x:auto}
.rpt-table{width:100%;border-collapse:collapse;font-size:.82rem}
.rpt-table thead th{background:#f8fafc;padding:10px 14px;font-size:.70rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.rpt-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.rpt-table tbody tr:hover{background:#f8fafc}
.rpt-table td{padding:9px 14px;vertical-align:middle}
.rpt-table tfoot td{padding:9px 14px;font-weight:700;font-size:.82rem;background:#f8fafc;border-top:2px solid #e2e8f0}
.rpt-col-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.rpt-col-num-hd{text-align:right}
.rpt-num-orange{color:#ea580c;font-weight:700}
.rpt-num-blue{color:#2563eb;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.rpt-tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px}
.rpt-footer-left{color:#64748b}
.rpt-footer-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.rpt-footer-chip-orange{background:#ffedd5;color:#9a3412}
.iwp-cat-chip{display:inline-block;padding:1px 7px;border-radius:5px;font-size:.72rem;font-weight:600;background:#f1f5f9;color:#475569}
@media(prefers-color-scheme:dark){
    .rpt-filter-bar,.rpt-tbl-footer{border-color:#334155}
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-table tfoot td,.rpt-tbl-footer{background:#0f172a}
    .iwp-cat-chip{background:#334155;color:#94a3b8}
}
:root[data-theme="dark"] .rpt-filter-bar,.rpt-tbl-footer{border-color:#334155}
:root[data-theme="dark"] .rpt-stat-card{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .rpt-stat-val{color:#f1f5f9}
:root[data-theme="dark"] .rpt-table thead th{background:#0f172a;border-color:#334155}
:root[data-theme="dark"] .rpt-tbl-footer{background:#0f172a;border-color:#334155}
:root[data-theme="light"] .rpt-stat-card{background:#fff;border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Item Wise Purchase',
                    'pageDescription' => 'Product-wise total purchase quantity and cost',
                    'pageIcon'        => 'bx-trending-down',
                    'pageIconBg'      => '#ffedd5',
                    'pageIconColor'   => '#ea580c',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card mb-3">
                        <div class="rpt-filter-bar">
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">From</div>
                                <input type="text" id="iwpFromDisplay" class="form-control rpt-filter-date" placeholder="From date" readonly>
                                <input type="hidden" id="iwpFrom" value="<?php echo htmlspecialchars($_from); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">To</div>
                                <input type="text" id="iwpToDisplay" class="form-control rpt-filter-date" placeholder="To date" readonly>
                                <input type="hidden" id="iwpTo" value="<?php echo htmlspecialchars($_to); ?>">
                            </div>
                            <div class="rpt-filter-group">
                                <div class="rpt-filter-label">&nbsp;</div>
                                <button class="btn btn-primary" id="iwpApplyBtn">
                                    <i class="bx bx-search me-1"></i>Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-package"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Products Purchased</div>
                                    <div class="rpt-stat-val" id="iwpStatProducts">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Bills</div>
                                    <div class="rpt-stat-val" id="iwpStatBills">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-sort-alt-2"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Quantity</div>
                                    <div class="rpt-stat-val" id="iwpStatQty">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-money"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Purchase Cost</div>
                                    <div class="rpt-stat-val" id="iwpStatCost">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="rpt-table-wrap">
                            <table class="rpt-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Category</th>
                                        <th class="rpt-col-num-hd">Bills</th>
                                        <th class="rpt-col-num-hd">Qty Purchased</th>
                                        <th class="rpt-col-num-hd">Avg Cost</th>
                                        <th class="rpt-col-num-hd">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="iwpTableBody">
                                    <tr><td colspan="8" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="iwpTableFoot" class="d-none">
                                    <tr>
                                        <td colspan="4">Total</td>
                                        <td class="rpt-col-num" id="iwpFtBills"></td>
                                        <td class="rpt-col-num rpt-num-blue" id="iwpFtQty"></td>
                                        <td class="rpt-col-num"></td>
                                        <td class="rpt-col-num rpt-num-orange" id="iwpFtCost"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="rpt-tbl-footer d-none" id="iwpTblFooter">
                            <div class="rpt-footer-left"><span id="iwpRowCount"></span></div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rpt-footer-chip rpt-footer-chip-orange"><i class="bx bx-money"></i>Total Cost: <span id="iwpFooterCost"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $this->load->view('common/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<script>
var _iwpInitFrom = '<?php echo addslashes($_from); ?>';
var _iwpInitTo   = '<?php echo addslashes($_to); ?>';
var _iwpListFmt  = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/item_wise_purchase.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
