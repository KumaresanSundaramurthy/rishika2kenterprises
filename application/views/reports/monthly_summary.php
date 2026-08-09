<?php defined('BASEPATH') or exit('No direct script access allowed');

$_today       = date('Y-m-d');
$_listFmt     = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$_initYear    = (int)($_initYear    ?? date('Y'));
$_currentYear = (int)($_currentYear ?? date('Y'));
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-stat-card { background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s; }
.rpt-stat-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.07); }
.rpt-stat-icon { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.rpt-stat-icon i { font-size:1.2rem; }
.rpt-icon-green  { background:#dcfce7; } .rpt-icon-green  i { color:#16a34a; }
.rpt-icon-orange { background:#ffedd5; } .rpt-icon-orange i { color:#ea580c; }
.rpt-icon-blue   { background:#dbeafe; } .rpt-icon-blue   i { color:#2563eb; }
.rpt-icon-purple { background:#ede9fe; } .rpt-icon-purple i { color:#7c3aed; }
.rpt-stat-body { flex:1;min-width:0; }
.rpt-stat-label { font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em; }
.rpt-stat-val   { font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }

/* Year selector */
.ms-year-wrap { display:flex;align-items:center;gap:8px; }
.ms-year-btn { width:32px;height:32px;border-radius:6px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .12s; }
.ms-year-btn:hover { border-color:#2563eb;color:#2563eb; }
.ms-year-label { font-size:1.05rem;font-weight:700;color:#1e293b;min-width:56px;text-align:center; }
.ms-year-input { display:none; }

/* Monthly table */
.ms-table-wrap { overflow-x:auto; }
.ms-table { width:100%;border-collapse:collapse;font-size:.82rem; }
.ms-table thead th { background:#f8fafc;padding:10px 14px;font-size:.73rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1; }
.ms-table thead th.ms-hd-sales   { color:#16a34a; }
.ms-table thead th.ms-hd-pur     { color:#ea580c; }
.ms-table thead th.ms-hd-net     { color:#2563eb; }
.ms-table tbody tr { border-bottom:1px solid #f1f5f9;transition:background .1s; }
.ms-table tbody tr:hover { background:#f8fafc; }
.ms-table tbody tr.ms-row-cur { background:#eff6ff; }
.ms-table tbody tr.ms-row-future td { color:#cbd5e1; }
.ms-table td { padding:10px 14px;vertical-align:middle; }
.ms-table tfoot tr { background:#f8fafc;border-top:2px solid #e2e8f0; }
.ms-table tfoot td { padding:10px 14px;font-weight:700; }
.ms-col-month { font-weight:600;color:#1e293b;white-space:nowrap;min-width:90px; }
.ms-col-num   { text-align:right;white-space:nowrap; }
.ms-col-num-hd { text-align:right; }
.ms-num-green  { color:#16a34a;font-weight:700; }
.ms-num-orange { color:#ea580c;font-weight:700; }
.ms-num-pos    { color:#2563eb;font-weight:700; }
.ms-num-neg    { color:#dc2626;font-weight:700; }
.ms-num-zero   { color:#94a3b8; }
.ms-balance-bar { height:4px;border-radius:2px;min-width:4px;max-width:80px;display:inline-block;vertical-align:middle;margin-left:6px; }
.ms-balance-pos { background:#2563eb; }
.ms-balance-neg { background:#dc2626; }
.ms-badge-cur { display:inline-block;padding:1px 6px;background:#dbeafe;color:#1d4ed8;border-radius:6px;font-size:.68rem;font-weight:700;vertical-align:middle;margin-left:4px; }
.rpt-loading-cell { padding:48px 0 !important;text-align:center; }
.rpt-loading { display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem; }
.rpt-tbl-footer { display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px; }
.rpt-footer-left { color:#64748b; }
@media (prefers-color-scheme: dark) {
    .rpt-stat-card { background:#1e293b;border-color:#334155; }
    .rpt-stat-val { color:#f1f5f9; }
    .ms-table thead th { background:#0f172a;color:#94a3b8;border-color:#334155; }
    .ms-table thead th.ms-hd-sales { color:#4ade80; }
    .ms-table thead th.ms-hd-pur   { color:#fb923c; }
    .ms-table thead th.ms-hd-net   { color:#60a5fa; }
    .ms-table tbody tr { border-color:#1e293b; }
    .ms-table tbody tr:hover { background:#0f172a; }
    .ms-table tbody tr.ms-row-cur { background:#1e3a5f; }
    .ms-table tfoot tr { background:#0f172a;border-color:#334155; }
    .ms-col-month { color:#f1f5f9; }
    .rpt-tbl-footer { background:#0f172a;border-color:#334155; }
    .ms-year-btn { background:#1e293b;border-color:#334155;color:#94a3b8; }
    .ms-year-label { color:#f1f5f9; }
}
:root[data-theme="dark"] .rpt-stat-card { background:#1e293b;border-color:#334155; }
:root[data-theme="dark"] .rpt-stat-val { color:#f1f5f9; }
:root[data-theme="dark"] .ms-table thead th { background:#0f172a;border-color:#334155; }
:root[data-theme="dark"] .ms-table tbody tr:hover { background:#0f172a; }
:root[data-theme="dark"] .rpt-tbl-footer { background:#0f172a;border-color:#334155; }
:root[data-theme="dark"] .ms-year-label { color:#f1f5f9; }
:root[data-theme="light"] .rpt-stat-card { background:#fff;border-color:#e2e8f0; }
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Monthly Summary',
                    'pageDescription' => 'Combined income and expense by month',
                    'pageIcon'        => 'bx-calendar-check',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- Stat Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-trending-up"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Sales</div>
                                    <div class="rpt-stat-val" id="msStatSales">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-trending-down"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Purchase</div>
                                    <div class="rpt-stat-val" id="msStatPur">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-bar-chart-alt-2"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Balance</div>
                                    <div class="rpt-stat-val" id="msStatBalance">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-star"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Best Month</div>
                                    <div class="rpt-stat-val" id="msStatBest">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Card -->
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="ms-year-wrap">
                                <button class="ms-year-btn" id="msYearPrev" title="Previous year"><i class="bx bx-chevron-left"></i></button>
                                <span class="ms-year-label" id="msYearLabel"><?php echo $_initYear; ?></span>
                                <input type="hidden" id="msYear" value="<?php echo $_initYear; ?>" />
                                <button class="ms-year-btn" id="msYearNext" title="Next year"><i class="bx bx-chevron-right"></i></button>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                        </div>

                        <!-- Table -->
                        <div class="ms-table-wrap">
                            <table class="ms-table" id="msTable">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th class="ms-col-num-hd ms-hd-sales">Sales</th>
                                        <th class="ms-col-num-hd ms-hd-sales">Sales Returns</th>
                                        <th class="ms-col-num-hd ms-hd-sales">Net Sales</th>
                                        <th class="ms-col-num-hd ms-hd-pur">Purchases</th>
                                        <th class="ms-col-num-hd ms-hd-pur">Pur. Returns</th>
                                        <th class="ms-col-num-hd ms-hd-pur">Net Purchase</th>
                                        <th class="ms-col-num-hd ms-hd-net">Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="msTableBody">
                                    <tr><td colspan="8" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="msTableFoot" class="d-none">
                                    <tr>
                                        <td>Total</td>
                                        <td class="ms-col-num" id="msFtSales"></td>
                                        <td class="ms-col-num" id="msFtSalesRet"></td>
                                        <td class="ms-col-num" id="msFtNetSales"></td>
                                        <td class="ms-col-num" id="msFtPur"></td>
                                        <td class="ms-col-num" id="msFtPurRet"></td>
                                        <td class="ms-col-num" id="msFtNetPur"></td>
                                        <td class="ms-col-num" id="msFtBalance"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rpt-tbl-footer d-none" id="msTblFooter">
                            <div class="rpt-footer-left" id="msFooterNote">Showing 12 months</div>
                        </div>

                    </div>
                </div>
                <?php $this->load->view('common/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<script>
var _msToday    = '<?php echo $_today; ?>';
var _msInitYear = <?php echo (int)$_initYear; ?>;
var _msListFmt  = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/monthly_summary.js"></script>

<?php $this->load->view('common/footer_desc'); ?>
