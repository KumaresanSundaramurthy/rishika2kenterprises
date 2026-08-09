<?php defined('BASEPATH') or exit('No direct script access allowed');

$_today      = date('Y-m-d');
$_listFmt    = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$_formFmt    = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y';
$_initFrom   = $_initFrom   ?? date('Y-01-01');
$_initTo     = $_initTo     ?? $_today;
$_initGroupBy = $_initGroupBy ?? 'month';
$this->load->view('common/transactions/header'); ?>

<style>
/* ── Shared report stat cards ───────────────────────────────────── */
.rpt-stat-card { background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s; }
.rpt-stat-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.07); }
.rpt-stat-icon { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.rpt-stat-icon i { font-size:1.2rem; }
.rpt-icon-green  { background:#dcfce7; } .rpt-icon-green  i { color:#16a34a; }
.rpt-icon-blue   { background:#dbeafe; } .rpt-icon-blue   i { color:#2563eb; }
.rpt-icon-red    { background:#fee2e2; } .rpt-icon-red    i { color:#dc2626; }
.rpt-icon-purple { background:#ede9fe; } .rpt-icon-purple i { color:#7c3aed; }
.rpt-stat-body { flex:1;min-width:0; }
.rpt-stat-label { font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em; }
.rpt-stat-val   { font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }

/* ── Date range inputs ──────────────────────────────────────────── */
.rpt-date-group { display:flex;align-items:center;gap:6px;flex-shrink:0; }
.rpt-date-label { font-size:.75rem;color:#64748b;font-weight:600;white-space:nowrap; }
.rpt-date-input-wrap { display:flex;align-items:center;gap:4px;background:#fff;border:1.5px solid #e2e8f0;border-radius:7px;padding:0 8px;height:34px;cursor:pointer;transition:border-color .12s; }
.rpt-date-input-wrap:focus-within { border-color:#2563eb; }
.rpt-date-input-wrap i { color:#7c3aed;font-size:.88rem;flex-shrink:0; }
.rpt-fp-input { border:none;outline:none;background:transparent;font-size:.82rem;font-weight:600;color:#1e293b;width:108px;cursor:pointer; }

/* ── Group-by toggle ────────────────────────────────────────────── */
.rpt-groupby-wrap { display:flex;align-items:center;gap:4px; }
.rpt-gb-btn { font-size:.76rem;padding:4px 12px;border-radius:6px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .12s;font-weight:600; }
.rpt-gb-btn.active { border-color:#2563eb;background:#eff6ff;color:#2563eb; }

/* ── Main table ─────────────────────────────────────────────────── */
.rpt-table-wrap { overflow-x:auto; }
.rpt-table { width:100%;border-collapse:collapse;font-size:.82rem; }
.rpt-table thead th { background:#f8fafc;padding:10px 14px;font-size:.73rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1; }
.rpt-table tbody tr { border-bottom:1px solid #f1f5f9;transition:background .1s; }
.rpt-table tbody tr:hover { background:#f8fafc; }
.rpt-table tbody tr:last-child { border-bottom:none; }
.rpt-table td { padding:10px 14px;vertical-align:middle; }
.rpt-table tfoot tr { background:#f8fafc;border-top:2px solid #e2e8f0; }
.rpt-table tfoot td { padding:10px 14px;font-weight:700;font-size:.82rem; }
.rpt-col-period { font-weight:600;color:#1e293b;white-space:nowrap; }
.rpt-col-num { text-align:right;white-space:nowrap; }
.rpt-col-num-hd { text-align:right; }
.rpt-num-green { color:#16a34a;font-weight:700; }
.rpt-num-red   { color:#dc2626;font-weight:700; }
.rpt-num-blue  { color:#2563eb;font-weight:700; }
.rpt-count-chip { display:inline-block;padding:2px 7px;background:#f1f5f9;color:#475569;border-radius:8px;font-size:.72rem;font-weight:600; }

/* ── Loading / empty ────────────────────────────────────────────── */
.rpt-loading-cell { padding:48px 0 !important;text-align:center; }
.rpt-loading { display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem; }
.rpt-empty { display:flex;flex-direction:column;align-items:center;padding:50px 20px;color:#94a3b8; }
.rpt-empty i { font-size:2.5rem;margin-bottom:8px;opacity:.5; }
.rpt-empty-title { font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px; }

/* ── Table footer totals ────────────────────────────────────────── */
.rpt-tbl-footer { display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px; }
.rpt-footer-left { color:#64748b; }
.rpt-footer-chip { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;font-size:.78rem;font-weight:600; }
.rpt-footer-chip-green { background:#dcfce7;color:#166534; }
.rpt-footer-chip-red   { background:#fee2e2;color:#991b1b; }
.rpt-footer-chip-blue  { background:#dbeafe;color:#1d4ed8; }

/* ── Section divider in thead ───────────────────────────────────── */
.rpt-th-group { text-align:center;border-bottom:none !important;padding-bottom:2px !important;color:#2563eb !important;font-size:.68rem !important; }
.rpt-th-group-red { color:#dc2626 !important; }

/* ── Dark mode ──────────────────────────────────────────────────── */
@media (prefers-color-scheme: dark) {
    .rpt-stat-card,.rpt-date-input-wrap { background:#1e293b;border-color:#334155; }
    .rpt-stat-label,.rpt-footer-left { color:#94a3b8; }
    .rpt-stat-val { color:#f1f5f9; }
    .rpt-table thead th { background:#0f172a;color:#94a3b8;border-color:#334155; }
    .rpt-table tbody tr { border-color:#1e293b; }
    .rpt-table tbody tr:hover { background:#0f172a; }
    .rpt-table tfoot tr { background:#0f172a;border-color:#334155; }
    .rpt-tbl-footer { background:#0f172a;border-color:#334155; }
    .rpt-fp-input { color:#f1f5f9; }
    .rpt-count-chip,.rpt-gb-btn { background:#334155;color:#94a3b8;border-color:#334155; }
    .rpt-gb-btn.active { background:#1e3a5f;color:#60a5fa;border-color:#2563eb; }
}
:root[data-theme="dark"] .rpt-stat-card,:root[data-theme="dark"] .rpt-date-input-wrap { background:#1e293b;border-color:#334155; }
:root[data-theme="dark"] .rpt-stat-val { color:#f1f5f9; }
:root[data-theme="dark"] .rpt-table thead th { background:#0f172a;border-color:#334155; }
:root[data-theme="dark"] .rpt-table tbody tr:hover { background:#0f172a; }
:root[data-theme="dark"] .rpt-tbl-footer { background:#0f172a;border-color:#334155; }
:root[data-theme="dark"] .rpt-fp-input { color:#f1f5f9; }
:root[data-theme="light"] .rpt-stat-card { background:#fff;border-color:#e2e8f0; }
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Sales Summary',
                    'pageDescription' => 'Month-wise or period-wise sales totals',
                    'pageIcon'        => 'bx-trending-up',
                    'pageIconBg'      => '#dcfce7',
                    'pageIconColor'   => '#16a34a',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- Stat Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-purple"><i class="bx bx-receipt"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Total Invoices</div>
                                    <div class="rpt-stat-val" id="ssStatInvCount">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-green"><i class="bx bx-rupee"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Gross Sales</div>
                                    <div class="rpt-stat-val" id="ssStatGross">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-red"><i class="bx bx-undo"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Sales Returns</div>
                                    <div class="rpt-stat-val" id="ssStatReturns">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rpt-stat-card">
                                <div class="rpt-stat-icon rpt-icon-blue"><i class="bx bx-trending-up"></i></div>
                                <div class="rpt-stat-body">
                                    <div class="rpt-stat-label">Net Revenue</div>
                                    <div class="rpt-stat-val" id="ssStatNet">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Card -->
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row flex-wrap gap-2">

                            <!-- From date -->
                            <div class="rpt-date-group">
                                <span class="rpt-date-label">From</span>
                                <div class="rpt-date-input-wrap">
                                    <i class="bx bx-calendar"></i>
                                    <input type="text" id="ssFromDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="ssFrom" value="<?php echo htmlspecialchars($_initFrom, ENT_QUOTES); ?>" />
                                </div>
                            </div>

                            <!-- To date -->
                            <div class="rpt-date-group">
                                <span class="rpt-date-label">To</span>
                                <div class="rpt-date-input-wrap">
                                    <i class="bx bx-calendar"></i>
                                    <input type="text" id="ssToDisplay" class="rpt-fp-input" readonly />
                                    <input type="hidden" id="ssTo" value="<?php echo htmlspecialchars($_initTo, ENT_QUOTES); ?>" />
                                </div>
                            </div>

                            <!-- Group by -->
                            <div class="rpt-groupby-wrap">
                                <button class="rpt-gb-btn <?php echo $_initGroupBy === 'month'   ? 'active' : ''; ?>" data-groupby="month">Month</button>
                                <button class="rpt-gb-btn <?php echo $_initGroupBy === 'quarter' ? 'active' : ''; ?>" data-groupby="quarter">Quarter</button>
                                <button class="rpt-gb-btn <?php echo $_initGroupBy === 'year'    ? 'active' : ''; ?>" data-groupby="year">Year</button>
                            </div>

                            <!-- Apply -->
                            <button class="btn btn-sm btn-primary" id="ssApplyBtn">
                                <i class="bx bx-search me-1"></i>Apply
                            </button>

                            <div class="apex-filter-spacer"></div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                        </div>

                        <!-- Table -->
                        <div class="rpt-table-wrap">
                            <table class="rpt-table" id="ssTable">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Period</th>
                                        <th colspan="4" class="rpt-th-group text-center">Invoices</th>
                                        <th colspan="2" class="rpt-th-group rpt-th-group-red text-center">Sales Returns</th>
                                        <th rowspan="2" class="rpt-col-num-hd">Net Revenue</th>
                                    </tr>
                                    <tr>
                                        <th class="rpt-col-num-hd">#</th>
                                        <th class="rpt-col-num-hd">Taxable</th>
                                        <th class="rpt-col-num-hd">Tax</th>
                                        <th class="rpt-col-num-hd">Amount</th>
                                        <th class="rpt-col-num-hd">#</th>
                                        <th class="rpt-col-num-hd">Returns</th>
                                    </tr>
                                </thead>
                                <tbody id="ssTableBody">
                                    <tr><td colspan="8" class="rpt-loading-cell">
                                        <div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
                                    </td></tr>
                                </tbody>
                                <tfoot id="ssTableFoot" class="d-none">
                                    <tr>
                                        <td>Total</td>
                                        <td class="rpt-col-num" id="ssFtInvCount"></td>
                                        <td class="rpt-col-num" id="ssFtSubTotal"></td>
                                        <td class="rpt-col-num" id="ssFtTax"></td>
                                        <td class="rpt-col-num" id="ssFtGross"></td>
                                        <td class="rpt-col-num" id="ssFtRetCount"></td>
                                        <td class="rpt-col-num" id="ssFtReturns"></td>
                                        <td class="rpt-col-num" id="ssFtNet"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rpt-tbl-footer d-none" id="ssTblFooter">
                            <div class="rpt-footer-left"><span id="ssPeriodCount"></span></div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="rpt-footer-chip rpt-footer-chip-green"><i class="bx bx-trending-up"></i><span id="ssFooterGross"></span></span>
                                <span class="text-muted">−</span>
                                <span class="rpt-footer-chip rpt-footer-chip-red"><i class="bx bx-undo"></i><span id="ssFooterReturns"></span></span>
                                <span class="text-muted">=</span>
                                <span class="rpt-footer-chip rpt-footer-chip-blue"><i class="bx bx-coin-stack"></i><span id="ssFooterNet"></span></span>
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
var _ssToday    = '<?php echo $_today; ?>';
var _ssInitFrom = '<?php echo htmlspecialchars($_initFrom, ENT_QUOTES); ?>';
var _ssInitTo   = '<?php echo htmlspecialchars($_initTo,   ENT_QUOTES); ?>';
var _ssGroupBy  = '<?php echo htmlspecialchars($_initGroupBy, ENT_QUOTES); ?>';
var _ssListFmt  = '<?php echo addslashes($_listFmt); ?>';
var _ssFormFmt  = '<?php echo addslashes($_formFmt); ?>';
ajaxLoading(0);
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/sales_summary.js"></script>

<?php $this->load->view('common/footer_desc'); ?>
