<?php defined('BASEPATH') or exit('No direct script access allowed');

$_today    = date('Y-m-d');
$_listFmt  = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$_initFrom = $_initFrom ?? date('Y-01-01');
$_initTo   = $_initTo   ?? $_today;
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.rpt-stat-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07)}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-red{background:#fee2e2}.rpt-icon-red i{color:#dc2626}
.rpt-icon-green{background:#dcfce7}.rpt-icon-green i{color:#16a34a}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rpt-date-group{display:flex;align-items:center;gap:6px;flex-shrink:0}
.rpt-date-label{font-size:.75rem;color:#64748b;font-weight:600;white-space:nowrap}
.rpt-date-input-wrap{display:flex;align-items:center;gap:4px;background:#fff;border:1.5px solid #e2e8f0;border-radius:7px;padding:0 8px;height:34px;cursor:pointer;transition:border-color .12s}
.rpt-date-input-wrap:focus-within{border-color:#ef4444}
.rpt-date-input-wrap i{color:#ef4444;font-size:.88rem;flex-shrink:0}
.rpt-fp-input{border:none;outline:none;background:transparent;font-size:.82rem;font-weight:600;color:#1e293b;width:108px;cursor:pointer}

/* Financial Statement layout */
.fin-wrap{padding:20px 24px}
.fin-cols{display:grid;grid-template-columns:1fr 1fr;gap:32px}
@media(max-width:768px){.fin-cols{grid-template-columns:1fr}}
.fin-section{margin-bottom:0}
.fin-section-title{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding-bottom:8px;border-bottom:2px solid #e2e8f0;margin-bottom:4px}
.fin-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f8fafc}
.fin-row-label{font-size:.81rem;color:#334155}
.fin-row-val{font-size:.81rem;font-weight:600;color:#1e293b;white-space:nowrap}
.fin-total-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-top:2px solid #e2e8f0;margin-top:2px}
.fin-total-label{font-size:.82rem;font-weight:700;color:#1e293b}
.fin-total-val{font-size:.88rem;font-weight:700;white-space:nowrap}
.fin-net-row{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-radius:8px;margin-top:20px;border:1.5px solid}
.fin-net-profit{background:#f0fdf4;border-color:#bbf7d0}
.fin-net-loss{background:#fef2f2;border-color:#fecaca}
.fin-net-label{font-size:.9rem;font-weight:700}
.fin-net-profit .fin-net-label{color:#15803d}
.fin-net-loss .fin-net-label{color:#b91c1c}
.fin-net-val{font-size:1rem;font-weight:700}
.fin-net-profit .fin-net-val{color:#15803d}
.fin-net-loss .fin-net-val{color:#b91c1c}
.fin-zero{color:#94a3b8}
.fin-loading{display:flex;align-items:center;justify-content:center;padding:60px;color:#64748b;font-size:.85rem;gap:8px}
.fin-empty{display:flex;flex-direction:column;align-items:center;padding:60px 20px;color:#94a3b8}
.fin-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.fin-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.fin-divider{height:1px;background:#e2e8f0;margin:16px 0}
.rpt-num-green{color:#16a34a;font-weight:700}
.rpt-num-red{color:#dc2626;font-weight:700}

@media(prefers-color-scheme:dark){
    .rpt-stat-card,.rpt-date-input-wrap{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .fin-row-label{color:#cbd5e1}
    .fin-row-val,.fin-total-label{color:#f1f5f9}
    .fin-section-title{color:#94a3b8;border-color:#334155}
    .fin-divider,.fin-total-row{border-color:#334155}
    .rpt-fp-input{color:#f1f5f9}
}
:root[data-theme="dark"] .rpt-stat-card,:root[data-theme="dark"] .rpt-date-input-wrap{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .rpt-stat-val,:root[data-theme="dark"] .fin-row-val{color:#f1f5f9}
:root[data-theme="dark"] .fin-section-title{color:#94a3b8;border-color:#334155}
:root[data-theme="dark"] .rpt-fp-input{color:#f1f5f9}
:root[data-theme="light"] .rpt-stat-card{background:#fff;border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'P&L Statement',
                    'pageDescription' => 'Profit and Loss for a period',
                    'pageIcon'        => 'bx-bar-chart-alt-2',
                    'pageIconBg'      => '#fee2e2',
                    'pageIconColor'   => '#ef4444',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-trending-up"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Revenue</div>
                                    <div class="rpt-stat-val" id="plStatRevenue">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-trending-down"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Expenses</div>
                                    <div class="rpt-stat-val" id="plStatExpenses">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-bar-chart-alt-2"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Profit / Loss</div>
                                    <div class="rpt-stat-val" id="plStatNet">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-line-chart"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Margin</div>
                                    <div class="rpt-stat-val" id="plStatMargin">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="apex-filter-row flex-wrap gap-2">
                            <div class="rpt-date-group">
                                <span class="rpt-date-label">From</span>
                                <div class="rpt-date-input-wrap">
                                    <i class="bx bx-calendar"></i>
                                    <input type="text" id="plFromDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="plFrom" value="<?php echo htmlspecialchars($_initFrom, ENT_QUOTES); ?>" />
                                </div>
                            </div>
                            <div class="rpt-date-group">
                                <span class="rpt-date-label">To</span>
                                <div class="rpt-date-input-wrap">
                                    <i class="bx bx-calendar"></i>
                                    <input type="text" id="plToDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="plTo" value="<?php echo htmlspecialchars($_initTo, ENT_QUOTES); ?>" />
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary" id="plApplyBtn">
                                <i class="bx bx-search me-1"></i>Apply
                            </button>
                        </div>

                        <div id="plContent">
                            <div class="fin-loading">
                                <span class="spinner-border spinner-border-sm"></span>Loading…
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
var _plToday    = '<?php echo $_today; ?>';
var _plInitFrom = '<?php echo htmlspecialchars($_initFrom, ENT_QUOTES); ?>';
var _plInitTo   = '<?php echo htmlspecialchars($_initTo,   ENT_QUOTES); ?>';
var _plListFmt  = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/pl_statement.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
