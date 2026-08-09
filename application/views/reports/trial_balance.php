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
.rpt-icon-purple{background:#ede9fe}.rpt-icon-purple i{color:#7c3aed}
.rpt-icon-blue{background:#dbeafe}.rpt-icon-blue i{color:#2563eb}
.rpt-icon-orange{background:#ffedd5}.rpt-icon-orange i{color:#ea580c}
.rpt-icon-green{background:#dcfce7}.rpt-icon-green i{color:#16a34a}
.rpt-stat-body{flex:1;min-width:0}
.rpt-stat-label{font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em}
.rpt-stat-val{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rpt-date-group{display:flex;align-items:center;gap:6px;flex-shrink:0}
.rpt-date-label{font-size:.75rem;color:#64748b;font-weight:600;white-space:nowrap}
.rpt-date-input-wrap{display:flex;align-items:center;gap:4px;background:#fff;border:1.5px solid #e2e8f0;border-radius:7px;padding:0 8px;height:34px;cursor:pointer;transition:border-color .12s}
.rpt-date-input-wrap:focus-within{border-color:#7c3aed}
.rpt-date-input-wrap i{color:#7c3aed;font-size:.88rem;flex-shrink:0}
.rpt-fp-input{border:none;outline:none;background:transparent;font-size:.82rem;font-weight:600;color:#1e293b;width:108px;cursor:pointer}

/* Trial Balance Table */
.rpt-table-wrap{overflow-x:auto}
.rpt-table{width:100%;border-collapse:collapse;font-size:.82rem}
.rpt-table thead th{background:#f8fafc;padding:8px 12px;font-size:.70rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.rpt-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.rpt-table tbody tr:hover{background:#f8fafc}
.rpt-table td{padding:8px 12px;vertical-align:middle}
.rpt-table tfoot td{padding:9px 12px;font-weight:700;font-size:.82rem;background:#f8fafc;border-top:2px solid #e2e8f0}
.rpt-col-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.rpt-col-num-hd{text-align:right}
.tb-group-hd td{background:#f1f5f9;font-size:.70rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;padding:6px 12px}
.tb-type-chip{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:6px;font-size:.70rem;font-weight:700}
.tb-type-asset{background:#dbeafe;color:#1d4ed8}
.tb-type-liability{background:#ffedd5;color:#9a3412}
.tb-type-income{background:#dcfce7;color:#166534}
.tb-type-expense{background:#fee2e2;color:#991b1b}
.tb-type-bank{background:#ccfbf1;color:#065f46}
.tb-type-cash{background:#fef3c7;color:#92400e}
.tb-type-customer{background:#ede9fe;color:#5b21b6}
.tb-type-vendor{background:#fce7f3;color:#9d174d}
.tb-dr{color:#2563eb;font-weight:600}
.tb-cr{color:#dc2626;font-weight:600}
.tb-zero{color:#94a3b8}
.tb-balanced-ok{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#dcfce7;color:#166534;border-radius:8px;font-size:.75rem;font-weight:700}
.tb-balanced-off{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#fee2e2;color:#991b1b;border-radius:8px;font-size:.75rem;font-weight:700}
.rpt-loading-cell{padding:48px 0!important;text-align:center}
.rpt-loading{display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem}
.rpt-empty{display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8}
.rpt-empty i{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.rpt-empty-title{font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px}
.rpt-tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px}
.rpt-footer-left{color:#64748b}
.rpt-footer-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600}
.rpt-footer-chip-blue{background:#dbeafe;color:#1d4ed8}
.rpt-footer-chip-red{background:#fee2e2;color:#991b1b}

@media(prefers-color-scheme:dark){
    .rpt-stat-card,.rpt-date-input-wrap{background:#1e293b;border-color:#334155}
    .rpt-stat-val{color:#f1f5f9}
    .rpt-table thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .rpt-table tbody tr{border-color:#1e293b}
    .rpt-table tbody tr:hover{background:#0f172a}
    .rpt-table tfoot td,.tb-group-hd td{background:#0f172a;border-color:#334155}
    .rpt-tbl-footer{background:#0f172a;border-color:#334155}
    .rpt-fp-input{color:#f1f5f9}
}
:root[data-theme="dark"] .rpt-stat-card,:root[data-theme="dark"] .rpt-date-input-wrap{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .rpt-stat-val{color:#f1f5f9}
:root[data-theme="dark"] .rpt-table thead th{background:#0f172a;border-color:#334155}
:root[data-theme="dark"] .rpt-table tbody tr:hover{background:#0f172a}
:root[data-theme="dark"] .rpt-tbl-footer{background:#0f172a;border-color:#334155}
:root[data-theme="dark"] .rpt-fp-input{color:#f1f5f9}
:root[data-theme="light"] .rpt-stat-card{background:#fff;border-color:#e2e8f0}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Trial Balance',
                    'pageDescription' => 'Debit and credit totals for all accounts',
                    'pageIcon'        => 'bx-transfer-alt',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-list-ul"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Accounts</div>
                                    <div class="rpt-stat-val" id="tbStatCount">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-down-arrow-circle"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Debit</div>
                                    <div class="rpt-stat-val" id="tbStatDebit">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-orange"><i class="bx bx-up-arrow-circle"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Credit</div>
                                    <div class="rpt-stat-val" id="tbStatCredit">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-check-shield"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Balanced</div>
                                    <div class="rpt-stat-val" id="tbStatBalanced">—</div>
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
                                    <input type="text" id="tbFromDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="tbFrom" value="<?php echo htmlspecialchars($_initFrom, ENT_QUOTES); ?>" />
                                </div>
                            </div>
                            <div class="rpt-date-group">
                                <span class="rpt-date-label">To</span>
                                <div class="rpt-date-input-wrap">
                                    <i class="bx bx-calendar"></i>
                                    <input type="text" id="tbToDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="tbTo" value="<?php echo htmlspecialchars($_initTo, ENT_QUOTES); ?>" />
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary" id="tbApplyBtn">
                                <i class="bx bx-search me-1"></i>Apply
                            </button>
                        </div>

                        <div class="rpt-table-wrap">
                            <table class="rpt-table" id="tbTable">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Account</th>
                                        <th>Type</th>
                                        <th class="rpt-col-num-hd">Opening Dr</th>
                                        <th class="rpt-col-num-hd">Opening Cr</th>
                                        <th class="rpt-col-num-hd">Period Dr</th>
                                        <th class="rpt-col-num-hd">Period Cr</th>
                                        <th class="rpt-col-num-hd">Closing Dr</th>
                                        <th class="rpt-col-num-hd">Closing Cr</th>
                                    </tr>
                                </thead>
                                <tbody id="tbTableBody">
                                    <tr><td colspan="9" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="tbTableFoot" class="d-none">
                                    <tr>
                                        <td colspan="3">Grand Total</td>
                                        <td class="rpt-col-num tb-dr" id="tbFtObDr"></td>
                                        <td class="rpt-col-num tb-cr" id="tbFtObCr"></td>
                                        <td class="rpt-col-num tb-dr" id="tbFtPeriodDr"></td>
                                        <td class="rpt-col-num tb-cr" id="tbFtPeriodCr"></td>
                                        <td class="rpt-col-num tb-dr" id="tbFtCloseDr"></td>
                                        <td class="rpt-col-num tb-cr" id="tbFtCloseCr"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rpt-tbl-footer d-none" id="tbTblFooter">
                            <div class="rpt-footer-left"><span id="tbRowCount"></span></div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="rpt-footer-chip rpt-footer-chip-blue"><i class="bx bx-down-arrow-circle"></i>Closing Dr: <span id="tbFooterDr"></span></span>
                                <span class="rpt-footer-chip rpt-footer-chip-red"><i class="bx bx-up-arrow-circle"></i>Closing Cr: <span id="tbFooterCr"></span></span>
                                <span id="tbFooterBalanced"></span>
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
var _tbToday    = '<?php echo $_today; ?>';
var _tbInitFrom = '<?php echo htmlspecialchars($_initFrom, ENT_QUOTES); ?>';
var _tbInitTo   = '<?php echo htmlspecialchars($_initTo,   ENT_QUOTES); ?>';
var _tbListFmt  = '<?php echo addslashes($_listFmt); ?>';
ajaxLoading(0);
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/trial_balance.js"></script>
<?php $this->load->view('common/footer_desc'); ?>
