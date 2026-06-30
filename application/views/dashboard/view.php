<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<?php
$gs      = $JwtData->GenSettings ?? new stdClass();
$cur     = htmlspecialchars($gs->CurrenySymbol ?? '₹');
$dec     = (int)($gs->DecimalPoints ?? 2);
$dateFmt = $gs->ListDateFormat ?? 'd M Y';

function dashFmt(float $v, string $cur, int $dec): string {
    return $cur . ' ' . number_format($v, $dec, '.', ',');
}
function dashPct(float $curr, float $prev): float {
    if ($prev == 0.0) return $curr > 0 ? 100.0 : 0.0;
    return round((($curr - $prev) / $prev) * 100, 1);
}

$todaySales   = $TodaySales        ?? ['total' => 0, 'count' => 0];
$monthly      = $MonthlyComparison ?? ['this_month' => 0, 'last_month' => 0];
$salesPct     = dashPct($monthly['this_month'], $monthly['last_month']);
$overdue      = $OverdueInvoices   ?? [];
$topCust      = $TopCustomers      ?? [];
$recentTxns   = $RecentTransactions ?? [];
$chartData    = $SalesChartData    ?? [];

$chartMap = [];
foreach ($chartData as $row) { $chartMap[$row->sale_date] = (float)$row->total; }
$chartLabels = []; $chartValues = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date($dateFmt, strtotime($d));
    $chartValues[] = $chartMap[$d] ?? 0;
}

// KPI data for JS
$kpiData = [
    'sales'       => ['values' => $chartValues, 'label' => 'Sales (Last 30 Days)',      'color' => '#0d6efd'],
    'receivable'  => ['values' => $chartValues, 'label' => 'Receivable Trend',           'color' => '#198754'],
    'payable'     => ['values' => $chartValues, 'label' => 'Payable Trend',              'color' => '#dc3545'],
    'monthly'     => ['values' => $chartValues, 'label' => 'Monthly Sales Comparison',   'color' => '#f59e0b'],
];
?>

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
.kpi-tab .kpi-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; }
.kpi-tab .kpi-sub   { font-size: .72rem; margin-top: 3px; }
.kpi-badge {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: .68rem; font-weight: 600;
    padding: 2px 6px; border-radius: 20px;
}
.kpi-badge.up   { background: #d1e7dd; color: #198754; }
.kpi-badge.down { background: #f8d7da; color: #dc3545; }
.kpi-badge.flat { background: #e9ecef; color: #6c757d; }

/* ── Chart container ── */
#dashChartCard .chart-title {
    font-size: .88rem; font-weight: 600; transition: color .18s;
}
#dashChartCard .chart-metric-dot {
    width: 8px; height: 8px; border-radius: 50%;
    display: inline-block; margin-right: 5px;
    transition: background .18s;
}
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php
                $quickAddHtml = '
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-plus me-1"></i>Quick Add
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;font-size:.82rem;">
                        <li><h6 class="dropdown-header" style="font-size:.68rem;letter-spacing:.4px;">SALES</h6></li>
                        <li><a class="dropdown-item" href="/invoices/create"><i class="bx bx-receipt me-2 text-primary"></i>New Invoice</a></li>
                        <li><a class="dropdown-item" href="/quotations/create"><i class="bx bx-file-blank me-2 text-info"></i>New Quotation</a></li>
                        <li><a class="dropdown-item" href="/salesorders/create"><i class="bx bx-cart me-2 text-success"></i>New Sales Order</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header" style="font-size:.68rem;letter-spacing:.4px;">PURCHASES</h6></li>
                        <li><a class="dropdown-item" href="/purchases/create"><i class="bx bx-purchase-tag me-2 text-warning"></i>New Purchase</a></li>
                        <li><a class="dropdown-item" href="/purchaseorders/create"><i class="bx bx-file me-2 text-secondary"></i>New Purchase Order</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header" style="font-size:.68rem;letter-spacing:.4px;">PAYMENTS</h6></li>
                        <li><a class="dropdown-item" href="/payments"><i class="bx bx-money me-2 text-success"></i>Receive Payment</a></li>
                        <li><a class="dropdown-item" href="/expenses/create"><i class="bx bx-wallet me-2 text-danger"></i>Add Expense</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header" style="font-size:.68rem;letter-spacing:.4px;">PARTIES</h6></li>
                        <li><a class="dropdown-item" href="/customers"><i class="bx bx-user-plus me-2 text-primary"></i>New Customer</a></li>
                        <li><a class="dropdown-item" href="/vendors"><i class="bx bx-store me-2 text-secondary"></i>New Vendor</a></li>
                    </ul>
                </div>';
                $this->load->view('common/apex/page_header', [
                    'pageTitle'         => 'Dashboard',
                    'pageDescription'   => 'Last updated: ' . htmlspecialchars($LastUpdated ?? ''),
                    'pageIcon'          => 'bx-home-circle',
                    'pageIconBg'        => '#eef2ff',
                    'pageIconColor'     => '#696cff',
                    'pageHeaderActions' => $quickAddHtml,
                ]); ?>

                <div class="container-xxl flex-grow-1 py-3">

                    <!-- ── KPI Tabs (21st.dev Line Charts 6 pattern) ──────── -->
                    <div class="row g-3 mb-4">

                        <!-- To Collect -->
                        <div class="col-6 col-md-3">
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
                                    <div class="kpi-value" style="color:#0d6efd;"><?php echo dashFmt($todaySales['total'], $cur, $dec); ?></div>
                                    <div class="kpi-sub text-muted">
                                        <?php echo (int)$todaySales['count']; ?> invoice<?php echo $todaySales['count'] != 1 ? 's' : ''; ?> today
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- This Month -->
                        <div class="col-6 col-md-3">
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
                                    <div class="kpi-value" style="color:#f59e0b;"><?php echo dashFmt($monthly['this_month'], $cur, $dec); ?></div>
                                    <div class="kpi-sub">
                                        <?php if ($salesPct > 0): ?>
                                            <span class="kpi-badge up"><i class="bx bx-up-arrow-alt"></i><?php echo $salesPct; ?>%</span>
                                            <span class="text-muted ms-1">vs last month</span>
                                        <?php elseif ($salesPct < 0): ?>
                                            <span class="kpi-badge down"><i class="bx bx-down-arrow-alt"></i><?php echo abs($salesPct); ?>%</span>
                                            <span class="text-muted ms-1">vs last month</span>
                                        <?php else: ?>
                                            <span class="kpi-badge flat">—</span>
                                            <span class="text-muted ms-1">same as last month</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Receivable -->
                        <div class="col-6 col-md-3">
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
                                    <div class="kpi-value" style="color:#198754;"><?php echo dashFmt($TotalReceivable ?? 0, $cur, $dec); ?></div>
                                    <div class="kpi-sub text-muted">Customer outstanding</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payable -->
                        <div class="col-6 col-md-3">
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
                                    <div class="kpi-value" style="color:#dc3545;"><?php echo dashFmt($TotalPayable ?? 0, $cur, $dec); ?></div>
                                    <div class="kpi-sub text-muted">Vendor outstanding</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Chart (switches on KPI tap) + Overdue ─────────── -->
                    <div class="row g-3 mb-4">

                        <!-- Interactive Chart -->
                        <div class="col-md-8">
                            <div class="card h-100" id="dashChartCard">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="chart-title">
                                        <span class="chart-metric-dot" id="chartDot" style="background:#0d6efd;"></span>
                                        <span id="chartTitle">Sales — Last 30 Days</span>
                                    </span>
                                    <a href="/invoices" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">View All</a>
                                </div>
                                <div class="card-body pt-2 pb-3" style="position:relative;">
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
                                    <span class="fw-semibold" style="font-size:.88rem;color:#dc3545;"><i class="bx bx-time-five me-1"></i>Overdue Invoices</span>
                                    <span class="badge bg-danger"><?php echo count($overdue); ?></span>
                                </div>
                                <div class="card-body p-0" style="overflow-y:auto;max-height:240px;">
                                    <?php if (empty($overdue)): ?>
                                        <div class="text-center text-muted py-4" style="font-size:.82rem;">No overdue invoices</div>
                                    <?php else: ?>
                                        <?php foreach ($overdue as $inv): ?>
                                        <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid #f0f0f0;">
                                            <div>
                                                <div style="font-size:.82rem;font-weight:600;color:#0d6efd;"><?php echo htmlspecialchars($inv->UniqueNumber ?? '—'); ?></div>
                                                <div style="font-size:.72rem;color:#6c757d;"><?php echo htmlspecialchars($inv->PartyName ?? '—'); ?></div>
                                                <div style="font-size:.68rem;color:#dc3545;">Due: <?php echo format_datedisplay($inv->ValidityDate, $dateFmt); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div style="font-size:.82rem;font-weight:700;color:#dc3545;"><?php echo dashFmt($inv->BalanceAmount, $cur, $dec); ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Top Customers + Recent Transactions ──────────── -->
                    <div class="row g-3">

                        <!-- Top Customers -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:.88rem;">Top Customers</span>
                                    <a href="/customers" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($topCust)): ?>
                                        <div class="text-center text-muted py-4" style="font-size:.82rem;">No outstanding customers</div>
                                    <?php else: ?>
                                        <?php foreach ($topCust as $cust): ?>
                                        <div class="d-flex align-items-center gap-2 px-3 py-2" style="border-bottom:1px solid #f0f0f0;">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="width:32px;height:32px;background:#e8f0fe;color:#0d6efd;font-size:.72rem;font-weight:700;">
                                                <?php echo strtoupper(substr(trim($cust->Name ?? 'X'), 0, 1)); ?>
                                            </div>
                                            <div class="flex-grow-1 min-width-0">
                                                <div style="font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($cust->Name ?? '—'); ?></div>
                                                <?php if (!empty($cust->MobileNumber)): ?>
                                                <div style="font-size:.7rem;color:#6c757d;"><?php echo htmlspecialchars($cust->MobileNumber); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="fw-bold text-end flex-shrink-0" style="font-size:.82rem;color:#198754;"><?php echo dashFmt($cust->PendingBalance, $cur, $dec); ?></div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:.88rem;">Recent Transactions</span>
                                    <a href="/invoices" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;">View All</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="font-size:.8rem;">
                                        <thead style="background:#f8f9fa;">
                                            <tr>
                                                <th class="ps-3 fw-semibold text-muted" style="font-size:.72rem;text-transform:uppercase;">Date</th>
                                                <th class="fw-semibold text-muted" style="font-size:.72rem;text-transform:uppercase;">Doc No</th>
                                                <th class="fw-semibold text-muted" style="font-size:.72rem;text-transform:uppercase;">Type</th>
                                                <th class="fw-semibold text-muted" style="font-size:.72rem;text-transform:uppercase;">Party</th>
                                                <th class="fw-semibold text-muted text-end pe-3" style="font-size:.72rem;text-transform:uppercase;">Amount</th>
                                                <th class="fw-semibold text-muted" style="font-size:.72rem;text-transform:uppercase;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentTxns)): ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">No transactions yet</td></tr>
                                            <?php else: ?>
                                            <?php
                                            $statusColors = [
                                                'Issued'   => '#0d6efd', 'Paid'     => '#198754',
                                                'Partial'  => '#f59e0b', 'Pending'  => '#6c757d',
                                                'Accepted' => '#198754', 'Converted'=> '#6f42c1',
                                            ];
                                            foreach ($recentTxns as $txn):
                                                $color = $statusColors[$txn->DocStatus] ?? '#6c757d';
                                            ?>
                                            <tr>
                                                <td class="ps-3" style="white-space:nowrap;"><?php echo format_datedisplay($txn->TransDate, $dateFmt); ?></td>
                                                <td style="color:#0d6efd;font-weight:600;"><?php echo htmlspecialchars($txn->UniqueNumber ?? '—'); ?></td>
                                                <td style="color:#6c757d;"><?php echo htmlspecialchars($txn->TransType ?? '—'); ?></td>
                                                <td style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;"><?php echo htmlspecialchars($txn->PartyName ?? '—'); ?></td>
                                                <td class="text-end pe-3 fw-semibold"><?php echo dashFmt($txn->NetAmount, $cur, $dec); ?></td>
                                                <td><span class="badge" style="background:<?php echo $color; ?>1a;color:<?php echo $color; ?>;font-size:.68rem;"><?php echo htmlspecialchars($txn->DocStatus); ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
<script>
(function () {
    'use strict';

    var cur     = <?php echo json_encode($cur); ?>;
    var dec     = <?php echo (int)$dec; ?>;
    var labels  = <?php echo json_encode($chartLabels); ?>;

    // KPI metric datasets — extend here when backend provides per-metric series
    var metrics = <?php echo json_encode($kpiData); ?>;

    // ── Build Chart ──────────────────────────────────────────────────────────
    var ctx    = document.getElementById('salesChart');
    var active = 'sales';

    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: metrics.sales.values,
                borderColor: metrics.sales.color,
                backgroundColor: hexToRgba(metrics.sales.color, .08),
                borderWidth: 2.5,
                pointRadius: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: metrics.sales.color,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: { duration: 350, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,.9)',
                    titleColor: '#94a3b8',
                    bodyColor: '#f1f5f9',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + cur + ' ' + Number(ctx.parsed.y).toLocaleString('en-IN', {
                                minimumFractionDigits: dec,
                                maximumFractionDigits: dec
                            });
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 7, font: { size: 10 }, color: '#94a3b8' }
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0)', drawBorder: false }, // hidden — dotted SVG handles it
                    ticks: {
                        font: { size: 10 }, color: '#94a3b8',
                        callback: function (v) {
                            return cur + Number(v).toLocaleString('en-IN');
                        }
                    }
                }
            }
        }
    });

    // ── KPI Tab Switcher ─────────────────────────────────────────────────────
    document.querySelectorAll('.kpi-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var metric = this.dataset.metric;
            var color  = this.dataset.color;
            if (metric === active) return;
            active = metric;

            // Update active tab styling
            document.querySelectorAll('.kpi-tab').forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');

            // Update chart data + color
            chart.data.datasets[0].data          = metrics[metric].values;
            chart.data.datasets[0].borderColor    = color;
            chart.data.datasets[0].backgroundColor= hexToRgba(color, .08);
            chart.data.datasets[0].pointHoverBackgroundColor = color;
            chart.update();

            // Update chart header
            document.getElementById('chartTitle').textContent = metrics[metric].label;
            document.getElementById('chartDot').style.background = color;
        });
    });

    // ── Helpers ──────────────────────────────────────────────────────────────
    function hexToRgba(hex, alpha) {
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

}());
</script>
