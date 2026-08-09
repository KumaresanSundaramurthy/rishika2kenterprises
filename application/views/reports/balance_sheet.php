<?php defined('BASEPATH') or exit('No direct script access allowed');

$_today    = date('Y-m-d');
$_listFmt  = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$_initAsOf = $_initAsOf ?? $_today;
$this->load->view('common/transactions/header'); ?>

<style>
.rpt-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.rpt-stat-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07)}
.rpt-stat-icon{width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.rpt-stat-icon i{font-size:1.2rem}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-green{background:#dcfce7}.rpt-icon-green i{color:#16a34a}
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rpt-date-group{display:flex;align-items:center;gap:6px;flex-shrink:0}
.rpt-date-label{font-size:.75rem;color:#64748b;font-weight:600;white-space:nowrap}
.rpt-date-input-wrap{display:flex;align-items:center;gap:4px;background:#fff;border:1.5px solid #e2e8f0;border-radius:7px;padding:0 8px;height:34px;cursor:pointer;transition:border-color .12s}
.rpt-date-input-wrap:focus-within{border-color:#3b82f6}
.rpt-date-input-wrap i{color:#3b82f6;font-size:.88rem;flex-shrink:0}
.rpt-fp-input{border:none;outline:none;background:transparent;font-size:.82rem;font-weight:600;color:#1e293b;width:140px;cursor:pointer}

/* Balance Sheet layout */
.fin-wrap{padding:20px 24px}
.fin-cols{display:grid;grid-template-columns:1fr 1fr;gap:32px}
@media(max-width:768px){.fin-cols{grid-template-columns:1fr}}
.fin-section-title{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding-bottom:8px;border-bottom:2px solid #e2e8f0;margin-bottom:4px}
.fin-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f8fafc}
.fin-row-label{font-size:.81rem;color:#334155}
.fin-row-sub{font-size:.76rem;color:#94a3b8;margin-left:8px}
.fin-row-val{font-size:.81rem;font-weight:600;color:#1e293b;white-space:nowrap}
.fin-total-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-top:2px solid #e2e8f0;margin-top:2px}
.fin-total-label{font-size:.82rem;font-weight:700;color:#1e293b}
.fin-total-val{font-size:.88rem;font-weight:700;white-space:nowrap}
.fin-section-gap{margin-top:20px}
.fin-equity-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f0fdf4;border-radius:8px;border:1.5px solid #bbf7d0;margin-top:8px}
.fin-equity-neg{background:#fef2f2;border-color:#fecaca}
.fin-equity-label{font-size:.82rem;font-weight:700;color:#15803d}
.fin-equity-neg .fin-equity-label{color:#b91c1c}
.fin-equity-val{font-size:.88rem;font-weight:700;color:#15803d;white-space:nowrap}
.fin-equity-neg .fin-equity-val{color:#b91c1c}
.fin-check-balanced{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:8px;font-size:.75rem;font-weight:700}
.fin-check-ok{background:#dcfce7;color:#166534}
.fin-check-off{background:#fee2e2;color:#991b1b}
.fin-loading{display:flex;align-items:center;justify-content:center;padding:60px;color:#64748b;font-size:.85rem;gap:8px}
.fin-empty{display:flex;flex-direction:column;align-items:center;padding:60px 20px;color:#94a3b8}
.fin-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.fin-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.fin-grand-total-bar{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#f8fafc;border-top:2px solid #e2e8f0;border-radius:0 0 8px 8px;margin-top:16px}
.fin-grand-label{font-size:.88rem;font-weight:800;color:#1e293b;text-transform:uppercase;letter-spacing:.04em}
.fin-grand-val{font-size:.95rem;font-weight:800;color:#2563eb;white-space:nowrap}
.fin-zero{color:#94a3b8}
.rpt-num-green{color:#16a34a;font-weight:700}
.rpt-num-red{color:#dc2626;font-weight:700}
.rpt-num-blue{color:#2563eb;font-weight:700}
.rpt-num-orange{color:#ea580c;font-weight:700}

@media(prefers-color-scheme:dark){
    .rpt-stat-card,.rpt-date-input-wrap{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .fin-row-label,.fin-row-val,.fin-total-label{color:#f1f5f9}
    .fin-section-title{color:#94a3b8;border-color:#334155}
    .fin-grand-total-bar{background:#0f172a;border-color:#334155}
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
                    'pageTitle'       => 'Balance Sheet',
                    'pageDescription' => 'Assets, liabilities and equity summary',
                    'pageIcon'        => 'bx-equalizer',
                    'pageIconBg'      => '#fee2e2',
                    'pageIconColor'   => '#ef4444',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-building-house"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Assets</div>
                                    <div class="rpt-stat-val" id="bsStatAssets">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-credit-card-alt"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Liabilities</div>
                                    <div class="rpt-stat-val" id="bsStatLiab">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-wallet"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Equity</div>
                                    <div class="rpt-stat-val" id="bsStatEquity">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-check-shield"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Balance Check</div>
                                    <div class="rpt-stat-val" id="bsStatCheck">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="apex-filter-row flex-wrap gap-2">
                            <div class="rpt-date-group">
                                <span class="rpt-date-label">As of</span>
                                <div class="rpt-date-input-wrap">
                                    <i class="bx bx-calendar"></i>
                                    <input type="text" id="bsAsOfDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="bsAsOf" value="<?php echo htmlspecialchars($_initAsOf, ENT_QUOTES); ?>" />
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary" id="bsApplyBtn">
                                <i class="bx bx-search me-1"></i>Apply
                            </button>
                        </div>

                        <div id="bsContent">
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
var _bsToday    = '<?php echo $_today; ?>';
var _bsInitAsOf = '<?php echo htmlspecialchars($_initAsOf, ENT_QUOTES); ?>';
var _bsListFmt  = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/balance_sheet.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
