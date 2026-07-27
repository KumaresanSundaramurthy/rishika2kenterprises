<?php defined('BASEPATH') or exit('No direct script access allowed');

$_today      = date('Y-m-d');
$_listFmt    = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$_formFmt    = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y';
$_initDate   = $_initDate   ?? $_today;
$_initSearch = $_initSearch ?? '';
$this->load->view('common/transactions/header'); ?>

<style>
/* ── Summary stat cards ──────────────────────────────────────── */
.db-stat-card {
    background:#fff;border:1px solid #e2e8f0;border-radius:10px;
    padding:14px 16px;display:flex;align-items:center;gap:12px;
    transition:box-shadow .15s;
}
.db-stat-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.07); }
.db-stat-icon {
    width:40px;height:40px;border-radius:10px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
}
.db-stat-icon i { font-size:1.2rem; }
.db-stat-icon-purple { background:#ede9fe; } .db-stat-icon-purple i { color:#7c3aed; }
.db-stat-icon-green  { background:#dcfce7; } .db-stat-icon-green  i { color:#16a34a; }
.db-stat-icon-blue   { background:#dbeafe; } .db-stat-icon-blue   i { color:#2563eb; }
.db-stat-icon-emerald{ background:#f0fdf4; } .db-stat-icon-emerald i { color:#059669; }
.db-stat-body { flex:1;min-width:0; }
.db-stat-label { font-size:.72rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.03em; }
.db-stat-val { font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }

/* ── Date wrap (inline in filter row) ────────────────────────── */
.db-today-btn { height:34px;font-size:.82rem;padding:0 12px; }
.db-date-wrap {
    display:flex;align-items:center;gap:5px;
    background:#fff;border:1.5px solid #e2e8f0;border-radius:7px;
    padding:0 10px;height:34px;cursor:pointer;flex-shrink:0;
    transition:all .12s;
}
.db-date-wrap.is-active { border-color:#2563eb;background:#eff6ff; }
.db-date-wrap.is-active .db-date-icon { color:#2563eb; }
.db-date-wrap.is-active .db-date-input { color:#2563eb;font-weight:700; }
.db-date-icon { color:#7c3aed;font-size:.9rem; }
.db-date-input {
    border:none;outline:none;background:transparent;
    font-size:.82rem;font-weight:600;color:#1e293b;width:110px;cursor:pointer;
}

/* ── Table ───────────────────────────────────────────────────── */
.db-table-wrap { overflow-x:auto; }
.db-table { width:100%;border-collapse:collapse;font-size:.82rem; }
.db-table thead th {
    background:#f8fafc;padding:10px 14px;
    font-size:.73rem;font-weight:700;color:#475569;text-transform:uppercase;
    letter-spacing:.04em;border-bottom:1px solid #e2e8f0;white-space:nowrap;
    position:sticky;top:0;z-index:1;
}
.db-table tbody tr { border-bottom:1px solid #f1f5f9;transition:background .1s; }
.db-table tbody tr:last-child { border-bottom:none; }
.db-table tbody tr:hover { background:#f8fafc; }
.db-table td { padding:10px 14px;vertical-align:middle; }

.db-th-time      { width:80px; }
.db-th-ptype     { width:95px; }
.db-th-party     { min-width:200px; }
.db-th-type      { width:155px; }
.db-th-serial    { width:140px; }
.db-th-mode      { width:115px; }
.db-th-amount    { width:125px;text-align:right; }

.db-col-time     { color:#64748b;font-size:.78rem;white-space:nowrap; }
.db-col-party    { font-weight:500;color:#1e293b; }
.db-col-amount   { text-align:right;font-weight:600;white-space:nowrap; }

/* Party sub-info (area / mobile) */
.db-party-name   { font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px; }
.db-party-sub    { font-size:.72rem;color:#64748b;margin-top:2px;display:flex;align-items:center;gap:3px;white-space:nowrap; }
.db-party-sub i  { font-size:.75rem;flex-shrink:0; }

/* Party type chip (Customer / Vendor) */
.db-ptype-c { display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:10px;font-size:.71rem;font-weight:700;background:#dbeafe;color:#1d4ed8; }
.db-ptype-s { display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:10px;font-size:.71rem;font-weight:700;background:#fef3c7;color:#92400e; }
.db-ptype-c i,.db-ptype-s i { font-size:.77rem; }

/* Transaction type badges */
.db-badge { display:inline-block;padding:3px 9px;border-radius:12px;font-size:.72rem;font-weight:700;letter-spacing:.02em;white-space:nowrap; }
.db-badge-invoice  { background:#dcfce7;color:#166534; }
.db-badge-purchase { background:#fee2e2;color:#991b1b; }
.db-badge-payin    { background:#dbeafe;color:#1d4ed8; }
.db-badge-payout   { background:#fef3c7;color:#92400e; }
.db-badge-sr       { background:#f3e8ff;color:#6b21a8; }
.db-badge-pr       { background:#ccfbf1;color:#065f46; }
.db-badge-so       { background:#e0f2fe;color:#0369a1; }
.db-badge-po       { background:#fce7f3;color:#9d174d; }
.db-badge-pf       { background:#ede9fe;color:#5b21b6; }
.db-badge-quot     { background:#f1f5f9;color:#475569; }
.db-badge-dc       { background:#f1f5f9;color:#334155; }
.db-badge-default  { background:#f1f5f9;color:#334155; }

/* Amount flow colors */
.db-amt-in      { color:#16a34a; }
.db-amt-out     { color:#dc2626; }
.db-amt-neutral { color:#334155; }

/* Payment mode chip */
.db-mode-chip { display:inline-block;padding:2px 7px;border-radius:8px;font-size:.72rem;font-weight:600;background:#f1f5f9;color:#475569; }
.db-mode-na   { color:#cbd5e1;font-size:.8rem; }

/* Serial link */
.db-serial-link { color:#3b82f6;text-decoration:none;font-weight:600; }
.db-serial-link:hover { text-decoration:underline; }

/* Loading / empty states */
.db-loading-cell { padding:40px 0 !important;text-align:center; }
.db-loading { display:flex;align-items:center;justify-content:center;color:#64748b;font-size:.85rem; }
.db-empty { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:50px 20px;color:#94a3b8; }
.db-empty i { font-size:2.5rem;margin-bottom:8px;opacity:.5; }
.db-empty-title { font-size:.9rem;font-weight:600;color:#64748b;margin-bottom:4px; }
.db-empty-sub { font-size:.78rem;text-align:center; }

/* Table footer */
.db-table-footer {
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 14px;border-top:1px solid #e2e8f0;
    background:#f8fafc;font-size:.8rem;flex-wrap:wrap;gap:8px;
}
.db-footer-left { color:#64748b; }
.db-footer-right { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.db-footer-item { white-space:nowrap; }
.db-footer-sep { color:#cbd5e1; }

/* ── Print styles ────────────────────────────────────────────── */
@media print {
    .apex-page-header,.apex-filter-row,.db-table-footer,
    .layout-menu,.apex-menu,.common-footer { display:none !important; }
    .db-table thead th { background:#f8fafc !important; }
    .db-stat-card { box-shadow:none !important;border:1px solid #e2e8f0 !important; }
}

/* ── Dark mode ───────────────────────────────────────────────── */
@media (prefers-color-scheme: dark) {
    .db-stat-card,.db-date-wrap { background:#1e293b;border-color:#334155; }
    .db-stat-label,.db-footer-left,.db-col-time { color:#94a3b8; }
    .db-stat-val,.db-col-party { color:#f1f5f9; }
    .db-table thead th { background:#0f172a;color:#94a3b8;border-color:#334155; }
    .db-table tbody tr { border-color:#1e293b; }
    .db-table tbody tr:hover { background:#0f172a; }
    .db-table td { color:#cbd5e1; }
    .db-table-footer { background:#0f172a;border-color:#334155; }
    .db-date-input { color:#f1f5f9; }
    .db-mode-chip { background:#334155;color:#94a3b8; }
}
:root[data-theme="dark"] .db-stat-card,:root[data-theme="dark"] .db-date-wrap { background:#1e293b;border-color:#334155; }
:root[data-theme="dark"] .db-stat-val,:root[data-theme="dark"] .db-col-party { color:#f1f5f9; }
:root[data-theme="dark"] .db-table thead th { background:#0f172a;border-color:#334155; }
:root[data-theme="dark"] .db-table tbody tr:hover { background:#0f172a; }
:root[data-theme="dark"] .db-table-footer { background:#0f172a;border-color:#334155; }
:root[data-theme="dark"] .db-date-input { color:#f1f5f9; }
:root[data-theme="light"] .db-stat-card,:root[data-theme="light"] .db-date-wrap { background:#fff;border-color:#e2e8f0; }
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => t('lbl_day_book', 'Day Book'),
                    'pageDescription' => t('lbl_daybook_desc', 'All transactions recorded on a given date'),
                    'pageIcon'        => 'bx-calendar-event',
                    'pageIconBg'      => '#f0ebff',
                    'pageIconColor'   => '#7c3aed',
                    'pageBackUrl'     => '/reports',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Summary Cards ──────────────────────────────────── -->
                    <div class="row g-3 mb-3" id="dbSummaryRow">
                        <div class="col-6 col-md-3">
                            <div class="db-stat-card">
                                <div class="db-stat-icon db-stat-icon-purple"><i class="bx bx-list-ul"></i></div>
                                <div class="db-stat-body">
                                    <div class="db-stat-label"><?php echo t('lbl_total_entries', 'Total Entries'); ?></div>
                                    <div class="db-stat-val" id="statTotal">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="db-stat-card">
                                <div class="db-stat-icon db-stat-icon-green"><i class="bx bx-receipt"></i></div>
                                <div class="db-stat-body">
                                    <div class="db-stat-label"><?php echo t('lbl_sales_invoiced', 'Sales (Invoiced)'); ?></div>
                                    <div class="db-stat-val" id="statSales">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="db-stat-card">
                                <div class="db-stat-icon db-stat-icon-blue"><i class="bx bx-down-arrow-circle"></i></div>
                                <div class="db-stat-body">
                                    <div class="db-stat-label"><?php echo t('lbl_collections', 'Collections'); ?></div>
                                    <div class="db-stat-val" id="statCollections">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="db-stat-card">
                                <div class="db-stat-icon db-stat-icon-emerald"><i class="bx bx-trending-up"></i></div>
                                <div class="db-stat-body">
                                    <div class="db-stat-label"><?php echo t('lbl_net_cash_flow', 'Net Cash Flow'); ?></div>
                                    <div class="db-stat-val" id="statNet">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap<?php echo $_initSearch ? ' is-expanded r2k-search-active' : ''; ?>" id="dbSearchWrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="dbSearch" placeholder="<?php echo t('lbl_party_search_placeholder', 'Party name or serial no…'); ?>"
                                       value="<?php echo htmlspecialchars($_initSearch, ENT_QUOTES); ?>"
                                       autocomplete="off">
                                <i class="bx bx-x r2k-clear<?php echo $_initSearch ? '' : ' d-none'; ?>" id="dbSearchClear"></i>
                            </div>
                            <div class="db-date-wrap" id="dbDateWrap">
                                <i class="bx bx-calendar db-date-icon"></i>
                                <input type="text" id="dbDateDisplay" class="db-date-input" readonly />
                                <input type="hidden" id="dbDate" value="<?php echo htmlspecialchars($_initDate, ENT_QUOTES); ?>" />
                            </div>
                            <button class="btn btn-sm btn-outline-primary db-today-btn" id="dbTodayBtn"><?php echo t('lbl_today', 'Today'); ?></button>
                            <a href="javascript:void(0);" id="dbTypeFilterBtn" class="apex-filter-btn" title="Filter by Transaction Type">
                                <i class="bx bx-category me-1"></i><span id="dbTypeFilterLabel"><?php echo t('lbl_all_types', 'All Types'); ?></span>
                            </a>
                            <div class="apex-filter-spacer"></div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                        </div>

                        <!-- ── Table ──────────────────────────────────────── -->
                        <div class="db-table-wrap">
                            <table class="db-table" id="dbTable">
                                <thead>
                                    <tr>
                                        <th class="db-th-time"><?php echo t('col_time', 'Time'); ?></th>
                                        <th class="db-th-ptype"><?php echo t('col_party_type', 'Party Type'); ?></th>
                                        <th class="db-th-party"><?php echo t('col_party_details', 'Party Details'); ?></th>
                                        <th class="db-th-type"><?php echo t('col_transaction_type', 'Transaction Type'); ?></th>
                                        <th class="db-th-serial"><?php echo t('col_serial_number', 'Serial Number'); ?></th>
                                        <th class="db-th-mode"><?php echo t('col_payment_mode', 'Payment Mode'); ?></th>
                                        <th class="db-th-amount"><?php echo t('col_amount', 'Amount'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="dbTableBody">
                                    <tr><td colspan="7" class="db-loading-cell">
                                        <div class="db-loading"><span class="spinner-border spinner-border-sm me-2"></span><?php echo t('lbl_loading', 'Loading…'); ?></div>
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="db-table-footer d-none" id="dbTableFooter">
                            <div class="db-footer-left"><span id="dbFooterCount"></span></div>
                            <div class="db-footer-right">
                                <span class="db-footer-item"><span class="text-muted me-1"><?php echo t('lbl_in', 'In:'); ?></span><strong class="text-success" id="dbFooterIn"></strong></span>
                                <span class="db-footer-sep">|</span>
                                <span class="db-footer-item"><span class="text-muted me-1"><?php echo t('lbl_out', 'Out:'); ?></span><strong class="text-danger" id="dbFooterOut"></strong></span>
                                <span class="db-footer-sep">|</span>
                                <span class="db-footer-item"><span class="text-muted me-1"><?php echo t('lbl_net', 'Net:'); ?></span><strong id="dbFooterNet"></strong></span>
                            </div>
                        </div>

                    </div>

                </div>
                <?php $this->load->view('common/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Type Filter Box ───────────────────────────────────────────── -->
<div id="dbTypeBox" class="card mp-filterbox" style="z-index:9999;display:none;position:fixed;width:240px;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-category me-1"></i><?php echo t('lbl_filter_by_type', 'Filter by Type'); ?></span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge" id="dbTypeBoxBadge"></span>
            <button type="button" class="catg-filter-close-btn" id="dbTypeBoxClose" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-filter-search-wrap">
        <div class="catg-search-inner">
            <input type="text" class="form-control form-control-sm" id="dbTypeBoxSearch" placeholder="<?php echo t('lbl_search_type', 'Search type...'); ?>">
            <button type="button" class="catg-search-clear" id="dbTypeBoxSearchClear" title="Clear" style="display:none;">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="dbTypeBoxSelectAll" checked>
        <label class="small fw-semibold mb-0" for="dbTypeBoxSelectAll"><?php echo t('lbl_select_all', 'Select All'); ?></label>
    </div>
    <div class="catg-list" id="dbTypeBoxList" style="max-height:240px;overflow-y:auto;">
        <!-- populated by JS -->
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" id="dbTypeBoxApply">
            <i class="bx bx-check me-1"></i><?php echo t('btn_apply', 'Apply'); ?>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="dbTypeBoxReset">
            <i class="bx bx-reset me-1"></i><?php echo t('btn_reset', 'Reset'); ?>
        </button>
    </div>
</div>

<script>
var _dbToday      = '<?php echo $_today; ?>';
var _dbInitDate   = '<?php echo htmlspecialchars($_initDate, ENT_QUOTES); ?>';
var _dbInitSearch = '<?php echo addslashes($_initSearch); ?>';
var _dbListFmt    = '<?php echo addslashes($_listFmt); ?>';
var _dbFormFmt    = '<?php echo addslashes($_formFmt); ?>';

/* Globals required by viewmodal.js */
var ajaxLoading(0);
var CsrfName    = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken   = '<?php echo $this->security->get_csrf_hash(); ?>';
var CDN_URL     = '<?php echo getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'); ?>';
const genSettings = <?php echo json_encode($JwtData->GenSettings ?? new stdClass()); ?>;
var _transListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
</script>
<script src="/js/reports/daybook.js"></script>
<script src="/js/transactions/viewmodal.js"></script>

<?php $this->load->view('common/transactions/print_modals'); ?>
<?php $this->load->view('common/footer_desc'); ?>
