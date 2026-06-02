<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<?php
$gs      = $JwtData->GenSettings ?? new stdClass();
$cur     = htmlspecialchars($gs->CurrenySymbol ?? '₹');
$dec     = (int)($gs->DecimalPoints ?? 2);
$dateFmt = $gs->ListDateFormat ?? 'd M Y';

function dashFmt($v, $cur, $dec) {
    return $cur . ' ' . number_format((float)$v, $dec, '.', ',');
}
function dashPct($curr, $prev) {
    if ($prev == 0) return $curr > 0 ? 100 : 0;
    return round((($curr - $prev) / $prev) * 100, 1);
}

$todaySales   = $TodaySales      ?? ['total' => 0, 'count' => 0];
$monthly      = $MonthlyComparison ?? ['this_month' => 0, 'last_month' => 0];
$salesPct     = dashPct($monthly['this_month'], $monthly['last_month']);
$overdue      = $OverdueInvoices  ?? [];
$topCust      = $TopCustomers     ?? [];
$recentTxns   = $RecentTransactions ?? [];
$chartData    = $SalesChartData   ?? [];

// Build chart labels & values for last 30 days
$chartMap = [];
foreach ($chartData as $row) { $chartMap[$row->sale_date] = (float)$row->total; }
$chartLabels = []; $chartValues = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date($dateFmt, strtotime($d));
    $chartValues[] = $chartMap[$d] ?? 0;
}
?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Page header ─────────────────────────────────── -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-0">Dashboard</h5>
                            <div class="text-muted" style="font-size:.75rem;">
                                Last updated: <?php echo htmlspecialchars($LastUpdated ?? ''); ?>
                            </div>
                        </div>
                        <!-- Quick Actions -->
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
                        </div>
                    </div>

                    <!-- ── KPI Cards ───────────────────────────────────── -->
                    <div class="row g-3 mb-4">

                        <!-- Receivable -->
                        <div class="col-6 col-md-3">
                            <div class="card h-100" style="border-left:4px solid #198754;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-muted" style="font-size:.76rem;text-transform:uppercase;letter-spacing:.4px;">To Collect</span>
                                        <div style="background:#d1e7dd;border-radius:8px;padding:6px 8px;">
                                            <i class="bx bx-down-arrow-circle" style="font-size:1.2rem;color:#198754;"></i>
                                        </div>
                                    </div>
                                    <div class="fw-bold" style="font-size:1.4rem;color:#198754;"><?php echo dashFmt($TotalReceivable ?? 0, $cur, $dec); ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">Customer outstanding</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payable -->
                        <div class="col-6 col-md-3">
                            <div class="card h-100" style="border-left:4px solid #dc3545;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-muted" style="font-size:.76rem;text-transform:uppercase;letter-spacing:.4px;">To Pay</span>
                                        <div style="background:#f8d7da;border-radius:8px;padding:6px 8px;">
                                            <i class="bx bx-up-arrow-circle" style="font-size:1.2rem;color:#dc3545;"></i>
                                        </div>
                                    </div>
                                    <div class="fw-bold" style="font-size:1.4rem;color:#dc3545;"><?php echo dashFmt($TotalPayable ?? 0, $cur, $dec); ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">Vendor outstanding</div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Sales -->
                        <div class="col-6 col-md-3">
                            <div class="card h-100" style="border-left:4px solid #0d6efd;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-muted" style="font-size:.76rem;text-transform:uppercase;letter-spacing:.4px;">Today's Sales</span>
                                        <div style="background:#e8f0fe;border-radius:8px;padding:6px 8px;">
                                            <i class="bx bx-receipt" style="font-size:1.2rem;color:#0d6efd;"></i>
                                        </div>
                                    </div>
                                    <div class="fw-bold" style="font-size:1.4rem;color:#0d6efd;"><?php echo dashFmt($todaySales['total'], $cur, $dec); ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?php echo (int)$todaySales['count']; ?> invoice<?php echo $todaySales['count'] != 1 ? 's' : ''; ?> today</div>
                                </div>
                            </div>
                        </div>

                        <!-- This Month -->
                        <div class="col-6 col-md-3">
                            <div class="card h-100" style="border-left:4px solid #f59e0b;">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-muted" style="font-size:.76rem;text-transform:uppercase;letter-spacing:.4px;">This Month</span>
                                        <div style="background:#fef3c7;border-radius:8px;padding:6px 8px;">
                                            <i class="bx bx-trending-up" style="font-size:1.2rem;color:#f59e0b;"></i>
                                        </div>
                                    </div>
                                    <div class="fw-bold" style="font-size:1.4rem;color:#f59e0b;"><?php echo dashFmt($monthly['this_month'], $cur, $dec); ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">
                                        <?php if ($salesPct > 0): ?>
                                            <span class="text-success"><i class="bx bx-up-arrow-alt"></i> <?php echo $salesPct; ?>%</span> vs last month
                                        <?php elseif ($salesPct < 0): ?>
                                            <span class="text-danger"><i class="bx bx-down-arrow-alt"></i> <?php echo abs($salesPct); ?>%</span> vs last month
                                        <?php else: ?>
                                            Same as last month
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Sales Chart + Overdue ────────────────────────── -->
                    <div class="row g-3 mb-4">

                        <!-- Sales Chart -->
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold" style="font-size:.88rem;">Sales — Last 30 Days</span>
                                    <a href="/invoices" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">View All</a>
                                </div>
                                <div class="card-body pt-2 pb-3">
                                    <canvas id="salesChart" height="110"></canvas>
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
                                        <?php foreach ($topCust as $i => $cust): ?>
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
(function() {
    var labels = <?php echo json_encode($chartLabels); ?>;
    var values = <?php echo json_encode($chartValues); ?>;

    var ctx = document.getElementById('salesChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.08)',
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 5,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return '<?php echo $cur; ?> ' + Number(ctx.parsed.y).toLocaleString('en-IN', {minimumFractionDigits: <?php echo $dec; ?>});
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 7, font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        font: { size: 10 },
                        callback: function(v) { return '<?php echo $cur; ?>' + Number(v).toLocaleString('en-IN'); }
                    }
                }
            }
        }
    });
}());
</script>
