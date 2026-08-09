<?php defined('BASEPATH') or exit('No direct script access allowed');

$_listFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.rpt-stat-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07)}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-red{background:#fee2e2}.rpt-icon-red i{color:#dc2626}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-yellow{background:#fef9c3}.rpt-icon-yellow i{color:#ca8a04}
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rpt-table-wrap{overflow-x:auto}
.rpt-table{width:100%;border-collapse:collapse;font-size:.82rem}
.rpt-table thead th{background:#f8fafc;padding:10px 14px;font-size:.70rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.rpt-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.rpt-table tbody tr:hover{background:#f8fafc}
.rpt-table td{padding:9px 14px;vertical-align:middle}
.rpt-col-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.rpt-col-num-hd{text-align:right}
.rpt-num-red{color:#dc2626;font-weight:700}
.rpt-num-orange{color:#ea580c;font-weight:700}
.rpt-num-yellow{color:#ca8a04;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem;gap:8px}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.rpt-tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px}
.rpt-footer-left{color:#64748b}
.rpt-footer-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.rpt-footer-chip-red{background:#fee2e2;color:#991b1b}
.ls-bar-wrap{min-width:100px;display:flex;align-items:center;gap:6px}
.ls-bar-bg{flex:1;height:6px;border-radius:3px;background:#e2e8f0;min-width:60px}
.ls-bar-fill{height:6px;border-radius:3px}
.ls-bar-ok{background:#16a34a}
.ls-bar-low{background:#ea580c}
.ls-bar-zero{background:#dc2626}
.ls-severity-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600}
.ls-sev-out{background:#fee2e2;color:#991b1b}
.ls-sev-critical{background:#ffedd5;color:#9a3412}
.ls-sev-low{background:#fef9c3;color:#854d0e}
@media(prefers-color-scheme:dark){
    .rpt-stat-card{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover,.rpt-tbl-footer{background:#0f172a}
    .rpt-tbl-footer{border-color:#334155}
    .ls-bar-bg{background:#334155}
}
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
                    'pageTitle'       => 'Low Stock Alert',
                    'pageDescription' => 'Products below minimum stock threshold',
                    'pageIcon'        => 'bx-error-alt',
                    'pageIconBg'      => '#fee2e2',
                    'pageIconColor'   => '#dc2626',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-error-circle"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Alerts Total</div>
                                    <div class="rpt-stat-val" id="lsStatTotal">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-x-circle"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Out of Stock</div>
                                    <div class="rpt-stat-val" id="lsStatOut">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-alarm-exclamation"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Critical (≤50% threshold)</div>
                                    <div class="rpt-stat-val" id="lsStatCritical">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-yellow"><i class="bx bx-time"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Low Stock</div>
                                    <div class="rpt-stat-val" id="lsStatLow">—</div>
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
                                        <th>Status</th>
                                        <th class="rpt-col-num-hd">In Stock</th>
                                        <th class="rpt-col-num-hd">Threshold</th>
                                        <th class="rpt-col-num-hd">Shortfall</th>
                                        <th>Stock Level</th>
                                    </tr>
                                </thead>
                                <tbody id="lsTableBody">
                                    <tr><td colspan="9" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="rpt-tbl-footer d-none" id="lsTblFooter">
                            <div class="rpt-footer-left"><span id="lsRowCount"></span></div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rpt-footer-chip rpt-footer-chip-red"><i class="bx bx-error-circle"></i>Alerts: <span id="lsFooterTotal"></span></span>
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
var _lsListFmt = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/low_stock_alert.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
