<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<style>
/* ── 21st.dev Line Charts 6 — KPI Tab Switcher ── */
.kpi-tab {
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 10px;
    transition: all .18s ease;
    user-select: none;
}
.kpi-tab:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.kpi-tab.active {
    border-color: var(--kpi-color, #0d6efd);
    background: rgba(var(--kpi-rgb, 13,110,253), .05) !important;
    box-shadow: 0 4px 18px rgba(var(--kpi-rgb, 13,110,253), .15);
}
.kpi-tab .kpi-icon-wrap {
    border-radius: 8px;
    padding: 6px 8px;
    transition: background .18s;
}
.kpi-tab .kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
.kpi-tab .kpi-label { font-size:var(--r2k-text-sm); text-transform: uppercase; letter-spacing: .4px; }
.kpi-tab .kpi-sub   { font-size:var(--r2k-text-sm); margin-top: 3px; }
.kpi-badge {
    display: inline-flex; align-items: center; gap: 2px;
    font-size:var(--r2k-text-xs); font-weight: 600;
    padding: 2px 6px; border-radius: 20px;
}
.kpi-badge.up   { background: #d1e7dd; color: #198754; }
.kpi-badge.down { background: #f8d7da; color: #dc3545; }
.kpi-badge.flat { background: #e9ecef; color: #6c757d; }

/* ── Chart container ── */
#dashChartCard .chart-title {
    font-size:var(--r2k-text-88); font-weight: 600; transition: color .18s;
}
#dashChartCard .chart-metric-dot {
    width: 8px; height: 8px; border-radius: 50%;
    display: inline-block; margin-right: 5px;
    transition: background .18s;
}

/* ── Info-only KPI cards (purchases — no chart switching) ── */
.kpi-info {
    border: 2px solid #e9ecef;
    border-radius: 10px;
}
.kpi-info .kpi-icon-wrap { border-radius: 8px; padding: 6px 8px; }
.kpi-info .kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
.kpi-info .kpi-label { font-size:var(--r2k-text-sm); text-transform: uppercase; letter-spacing: .4px; }
.kpi-info .kpi-sub   { font-size:var(--r2k-text-sm); margin-top: 3px; }

/* ── Pending action strip ── */
.pend-strip { font-size:var(--r2k-text-78); }
.pend-badge { display: inline-flex; align-items: center; gap: 6px; }
.pend-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; height: 22px; border-radius: 50%;
    font-size:var(--r2k-text-70); font-weight: 700; padding: 0 6px;
}
.pend-count-draft { background: #e9ecef; color: #495057; }
.pend-count-inv   { background: #dbeafe; color: #1d4ed8; }
.pend-count-po    { background: #fef3c7; color: #b45309; }
.pend-count-purch { background: #ede9fe; color: #6d28d9; }
.pend-lnk { color: #566a7f; text-decoration: none; }
.pend-lnk:hover { color: #0d6efd; }

/* ── View all link (chart card) ── */
#chartViewAllLink { font-size:var(--r2k-text-75); }

/* ── Purchase KPI info cards ── */
.kpi-purch-icon   { background: #ede9fe; }
.kpi-purch-ico    { font-size: 1.15rem; color: #7c3aed; }
.kpi-purch-val    { color: #7c3aed; }
.kpi-mpurch-icon  { background: #e0f2fe; }
.kpi-mpurch-ico   { font-size: 1.15rem; color: #0284c7; }
.kpi-mpurch-val   { color: #0284c7; }

/* ── Pending strip label ── */
.pend-strip-label { font-size:var(--r2k-text-sm); text-transform: uppercase; letter-spacing: .4px; }

/* ── Top Vendors card ── */
.vend-avatar { width: 32px; height: 32px; background: #fef3c7; color: #b45309; font-size:var(--r2k-text-sm); font-weight: 700; }
.vend-balance { font-size:var(--r2k-text-82); color: #dc3545; }

/* ── Expense Summary card ── */
.exp-cat-name  { font-size:var(--r2k-text-md); font-weight: 500; min-width: 100px; }
.exp-cat-bar   { flex: 1; height: 6px; border-radius: 3px; background: #f0f0f0; overflow: hidden; }
.exp-cat-fill  { height: 100%; border-radius: 3px; background: #dc3545; }
.exp-cat-amt   { font-size:var(--r2k-text-78); font-weight: 600; min-width: 80px; text-align: right; color: #dc3545; }
.exp-total-lbl { font-size:var(--r2k-text-sm); text-transform: uppercase; letter-spacing: .4px; }
.exp-total-val { font-size: 1.1rem; font-weight: 700; color: #dc3545; }

/* ── Top Products card ── */
.prod-rank     { width: 20px; font-size:var(--r2k-text-75); font-weight: 700; color: #adb5bd; flex-shrink: 0; }
.prod-name     { font-size:var(--r2k-text-82); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.prod-bar      { flex: 1; height: 5px; border-radius: 3px; background: #f0f0f0; overflow: hidden; }
.prod-bar-fill { height: 100%; border-radius: 3px; background: #0d6efd; }
.prod-qty      { font-size:var(--r2k-text-70); color: #6c757d; flex-shrink: 0; }
.prod-rev      { font-size:var(--r2k-text-78); font-weight: 600; color: #0d6efd; min-width: 90px; text-align: right; flex-shrink: 0; }

/* ── Last Updated badge in page header ── */
.dash-lu-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: var(--r2k-text-70);
    font-weight: 500;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    color: #4f46e5;
    border: 1px solid #c7d2fe;
    border-radius: 20px;
    padding: 2px 10px 2px 7px;
    margin-top: 4px;
    letter-spacing: .1px;
    white-space: nowrap;
}
.dash-lu-badge i { font-size: .8rem; opacity: .85; }
.dash-lu-badge.loading { color: #94a3b8; background: #f1f5f9; border-color: #e2e8f0; }

/* ── Widget loading spinner ── */
.dash-spinner {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; padding: 24px 0; color: #94a3b8;
    font-size: var(--r2k-text-82);
}
/* ── Chart loading overlay ── */
#dashChartOverlay {
    position: absolute; inset: 0; z-index: 2;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.75);
    border-radius: 4px;
}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php
                $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Dashboard',
                    'pageIcon'        => 'bx-home-circle',
                    'pageIconBg'      => '#eef2ff',
                    'pageIconColor'   => '#696cff',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y px-4">

                    <!-- ── KPI Tabs ──────────────────────────────────────────── -->
                    <div class="row g-3 mb-3">

                        <!-- Today's Sales -->
                        <div class="col-6 col-md-2">
                            <div class="card h-100 kpi-tab active"
                                 data-metric="sales"
                                 data-color="#0d6efd" data-rgb="13,110,253"
                                 style="--kpi-color:#0d6efd;--kpi-rgb:13,110,253;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted kpi-label">Today's Sales</span>
                                        <div class="kpi-icon-wrap" style="background:#e8f0fe;">
                                            <i class="bx bx-receipt" style="font-size:1.15rem;color:#0d6efd;"></i>
                                        </div>
                                    </div>
                                    <div class="kpi-value" style="color:#0d6efd;" id="kpiTodaySalesVal">—</div>
                                    <div class="kpi-sub text-muted" id="kpiTodaySalesSub">...</div>
                                </div>
                            </div>
                        </div>

                        <!-- This Month Sales -->
                        <div class="col-6 col-md-2">
                            <div class="card h-100 kpi-tab"
                                 data-metric="monthly"
                                 data-color="#f59e0b" data-rgb="245,158,11"
                                 style="--kpi-color:#f59e0b;--kpi-rgb:245,158,11;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted kpi-label">This Month</span>
                                        <div class="kpi-icon-wrap" style="background:#fef3c7;">
                                            <i class="bx bx-trending-up" style="font-size:1.15rem;color:#f59e0b;"></i>
                                        </div>
                                    </div>
                                    <div class="kpi-value" style="color:#f59e0b;" id="kpiMonthSalesVal">—</div>
                                    <div class="kpi-sub" id="kpiMonthSalesSub">
                                        <span class="kpi-badge flat">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- To Collect (Receivable) -->
                        <div class="col-6 col-md-2">
                            <div class="card h-100 kpi-tab"
                                 data-metric="receivable"
                                 data-color="#198754" data-rgb="25,135,84"
                                 style="--kpi-color:#198754;--kpi-rgb:25,135,84;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted kpi-label">To Collect</span>
                                        <div class="kpi-icon-wrap" style="background:#d1e7dd;">
                                            <i class="bx bx-down-arrow-circle" style="font-size:1.15rem;color:#198754;"></i>
                                        </div>
                                    </div>
                                    <div class="kpi-value" style="color:#198754;" id="kpiReceivableVal">—</div>
                                    <div class="kpi-sub text-muted">Customer outstanding</div>
                                </div>
                            </div>
                        </div>

                        <!-- To Pay (Payable) -->
                        <div class="col-6 col-md-2">
                            <div class="card h-100 kpi-tab"
                                 data-metric="payable"
                                 data-color="#dc3545" data-rgb="220,53,69"
                                 style="--kpi-color:#dc3545;--kpi-rgb:220,53,69;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted kpi-label">To Pay</span>
                                        <div class="kpi-icon-wrap" style="background:#f8d7da;">
                                            <i class="bx bx-up-arrow-circle" style="font-size:1.15rem;color:#dc3545;"></i>
                                        </div>
                                    </div>
                                    <div class="kpi-value" style="color:#dc3545;" id="kpiPayableVal">—</div>
                                    <div class="kpi-sub text-muted">Vendor outstanding</div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Purchases (info card — no chart switch) -->
                        <div class="col-6 col-md-2">
                            <div class="card h-100 kpi-info">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted kpi-label">Today Bought</span>
                                        <div class="kpi-icon-wrap kpi-purch-icon">
                                            <i class="bx bx-purchase-tag kpi-purch-ico"></i>
                                        </div>
                                    </div>
                                    <div class="kpi-value kpi-purch-val" id="kpiTodayPurchVal">—</div>
                                    <div class="kpi-sub text-muted" id="kpiTodayPurchSub">...</div>
                                </div>
                            </div>
                        </div>

                        <!-- This Month Purchases (info card — no chart switch) -->
                        <div class="col-6 col-md-2">
                            <div class="card h-100 kpi-info">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted kpi-label">Month Purchases</span>
                                        <div class="kpi-icon-wrap kpi-mpurch-icon">
                                            <i class="bx bx-store kpi-mpurch-ico"></i>
                                        </div>
                                    </div>
                                    <div class="kpi-value kpi-mpurch-val" id="kpiMonthPurchVal">—</div>
                                    <div class="kpi-sub" id="kpiMonthPurchSub">
                                        <span class="kpi-badge flat">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Pending Actions Strip ──────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body py-2 px-3 d-flex align-items-center gap-4 flex-wrap pend-strip">
                                    <span class="text-muted fw-semibold pend-strip-label">Pending Actions</span>
                                    <div class="pend-badge">
                                        <span class="pend-count pend-count-draft" id="pendDraftCount">—</span>
                                        <a href="<?= site_url('invoices') ?>?tab=Draft" class="pend-lnk">Drafts</a>
                                    </div>
                                    <div class="pend-badge">
                                        <span class="pend-count pend-count-inv" id="pendOpenInv">—</span>
                                        <a href="<?= site_url('invoices') ?>" class="pend-lnk">Open Invoices</a>
                                    </div>
                                    <div class="pend-badge">
                                        <span class="pend-count pend-count-po" id="pendOpenPOs">—</span>
                                        <a href="<?= site_url('purchaseorders') ?>" class="pend-lnk">Open POs</a>
                                    </div>
                                    <div class="pend-badge">
                                        <span class="pend-count pend-count-purch" id="pendOpenPurch">—</span>
                                        <a href="<?= site_url('purchases') ?>" class="pend-lnk">Open Purchases</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Chart + Overdue ───────────────────────────────── -->
                    <div class="row g-3 mb-4">

                        <!-- Interactive Chart -->
                        <div class="col-md-8">
                            <div class="card h-100" id="dashChartCard">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="chart-title">
                                        <span class="chart-metric-dot" id="chartDot" style="background:#0d6efd;"></span>
                                        <span id="chartTitle">Sales — Last 30 Days</span>
                                    </span>
                                    <a href="<?= site_url('invoices') ?>" id="chartViewAllLink" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body pt-2 pb-3" style="position:relative;">
                                    <div id="dashChartOverlay">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    </div>
                                    <!-- Dotted grid background (21st.dev pattern) -->
                                    <svg style="position:absolute;inset:16px;pointer-events:none;opacity:.4;" width="100%" height="100%">
                                        <defs>
                                            <pattern id="dotGrid" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                                                <circle cx="10" cy="10" r="1" fill="#94a3b8"/>
                                            </pattern>
                                        </defs>
                                        <rect width="100%" height="100%" fill="url(#dotGrid)"/>
                                    </svg>
                                    <canvas id="salesChart" height="110" style="position:relative;z-index:1;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Overdue Invoices -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:var(--r2k-text-88);color:#dc3545;"><i class="bx bx-time-five me-1"></i>Overdue Invoices</span>
                                    <span class="badge bg-danger" id="overdueCount">—</span>
                                </div>
                                <div class="card-body p-0" id="overdueBody" style="overflow-y:auto;max-height:240px;">
                                    <div class="dash-spinner">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Top Customers + Recent Transactions ──────────── -->
                    <div class="row g-3">

                        <!-- Top Customers + Top Vendors (stacked) -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:var(--r2k-text-88);">Top Customers</span>
                                    <a href="<?= site_url('customers') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:var(--r2k-text-sm);">View All</a>
                                </div>
                                <div class="card-body p-0" id="topCustBody">
                                    <div class="dash-spinner">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Vendors -->
                            <div class="card mt-3">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:var(--r2k-text-88);">Top Vendors</span>
                                    <a href="<?= site_url('vendors') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:var(--r2k-text-sm);">View All</a>
                                </div>
                                <div class="card-body p-0" id="topVendBody">
                                    <div class="dash-spinner">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:var(--r2k-text-88);">Recent Transactions</span>
                                    <a href="<?= site_url('invoices') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:var(--r2k-text-sm);">View All</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="font-size:var(--r2k-text-md);">
                                        <thead style="background:#f8f9fa;">
                                            <tr>
                                                <th class="ps-3 fw-semibold text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;">Date</th>
                                                <th class="fw-semibold text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;">Doc No</th>
                                                <th class="fw-semibold text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;">Type</th>
                                                <th class="fw-semibold text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;">Party</th>
                                                <th class="fw-semibold text-muted text-end pe-3" style="font-size:var(--r2k-text-sm);text-transform:uppercase;">Amount</th>
                                                <th class="fw-semibold text-muted" style="font-size:var(--r2k-text-sm);text-transform:uppercase;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentTxnBody">
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="spinner-border spinner-border-sm text-muted" role="status"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Expense Summary + Top Products ────────────────── -->
                    <div class="row g-3 mt-1 mb-4">

                        <!-- Expense Summary -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:var(--r2k-text-88);"><i class="bx bx-wallet text-danger me-1"></i>Expenses — This Month</span>
                                    <a href="<?= site_url('expenses') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:var(--r2k-text-sm);">View All</a>
                                </div>
                                <div class="card-body py-3" id="expSummaryBody">
                                    <div class="dash-spinner">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Products -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:var(--r2k-text-88);"><i class="bx bx-box text-primary me-1"></i>Top Products — This Month</span>
                                    <a href="<?= site_url('products') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:var(--r2k-text-sm);">View All</a>
                                </div>
                                <div class="card-body py-3" id="topProductsBody">
                                    <div class="dash-spinner">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?php echo _assetV('/js/common/datefilter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/dashboard.js'); ?>"></script>