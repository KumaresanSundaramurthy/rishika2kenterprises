<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $categories = [
                        [
                            'id'      => 'tax',
                            'name'    => 'Tax Reports',
                            'icon'    => 'bx-receipt',
                            'color'   => '#4f46e5',
                            'bg'      => '#ede9fe',
                            'reports' => [
                                ['name' => 'GSTR - 1',             'desc' => 'Outward supplies — B2B, B2CS, CDNR, exports',          'url' => '/reports/gstr1',           'icon' => 'bx-file-blank'],
                                ['name' => 'GSTR - 2B',            'desc' => 'Auto-drafted inward supplies ITC statement',           'url' => '/reports/gstr2b',          'icon' => 'bx-file'],
                                ['name' => 'GSTR - 3B',            'desc' => 'Monthly consolidated summary return filing',           'url' => '/reports/gstr3b',          'icon' => 'bx-spreadsheet'],
                                ['name' => 'GSTR - 7',             'desc' => 'TDS deducted at source under GST',                    'url' => '/reports/gstr7',           'icon' => 'bx-minus-circle'],
                                ['name' => 'Sale Summary by HSN',  'desc' => 'HSN / SAC code-wise outward supplies summary',         'url' => '/reports/hsn',             'icon' => 'bx-list-check'],
                                ['name' => 'TDS Receivable',       'desc' => 'TDS deducted on payments received from customers',     'url' => '/reports/tds-receivable',  'icon' => 'bx-trending-up'],
                                ['name' => 'TDS Payable',          'desc' => 'TDS to be deducted on vendor payments made',           'url' => '/reports/tds-payable',     'icon' => 'bx-trending-down'],
                                ['name' => 'TCS Receivable',       'desc' => 'TCS collected on receipts from customers',             'url' => '/reports/tcs-receivable',  'icon' => 'bx-coin'],
                                ['name' => 'TCS Payable',          'desc' => 'TCS payable to the government on sales',               'url' => '/reports/tcs-payable',     'icon' => 'bx-money'],
                            ],
                        ],
                        [
                            'id'      => 'transaction',
                            'name'    => 'Transaction Reports',
                            'icon'    => 'bx-transfer',
                            'color'   => '#0d9488',
                            'bg'      => '#ccfbf1',
                            'reports' => [
                                ['name' => 'Sales Register',          'desc' => 'Invoice-wise sales with tax breakup in a date range',    'url' => '/reports/sales-register',          'icon' => 'bx-store'],
                                ['name' => 'Purchase Register',       'desc' => 'Purchase bill-wise report with tax breakup',             'url' => '/reports/purchase-register',       'icon' => 'bx-cart'],
                                ['name' => 'Sales Return Register',   'desc' => 'Credit note-wise sales returns in a date range',         'url' => '/reports/sales-return-register',   'icon' => 'bx-undo'],
                                ['name' => 'Purchase Return Register','desc' => 'Debit note-wise purchase returns in a date range',       'url' => '/reports/purchase-return-register','icon' => 'bx-redo'],
                                ['name' => 'Delivery Challan Register','desc' => 'Dispatch-wise delivery challans in a date range',       'url' => '/reports/dc-register',             'icon' => 'bx-package'],
                                ['name' => 'Expense Register',        'desc' => 'All expenses recorded in a date range',                 'url' => '/reports/expense-register',        'icon' => 'bx-wallet'],
                            ],
                        ],
                        [
                            'id'      => 'billwise',
                            'name'    => 'Bill-wise Item Reports',
                            'icon'    => 'bx-list-ul',
                            'color'   => '#f59e0b',
                            'bg'      => '#fef3c7',
                            'reports' => [
                                ['name' => 'Invoice Item-wise',          'desc' => 'Each invoice line-item with rate, qty and tax',   'url' => '/reports/invoice-itemwise',      'icon' => 'bx-receipt'],
                                ['name' => 'Purchase Item-wise',         'desc' => 'Each purchase line-item with rate, qty and tax',  'url' => '/reports/purchase-itemwise',     'icon' => 'bx-purchase-tag'],
                                ['name' => 'Sales Return Item-wise',     'desc' => 'Returned items per credit note in detail',        'url' => '/reports/sr-itemwise',           'icon' => 'bx-undo'],
                                ['name' => 'Purchase Return Item-wise',  'desc' => 'Returned items per debit note in detail',         'url' => '/reports/pr-itemwise',           'icon' => 'bx-redo'],
                            ],
                        ],
                        [
                            'id'      => 'item',
                            'name'    => 'Item Reports',
                            'icon'    => 'bx-box',
                            'color'   => '#10b981',
                            'bg'      => '#d1fae5',
                            'reports' => [
                                ['name' => 'Item Wise Sales',     'desc' => 'Product-wise total sales quantity and value',   'url' => '/reports/item-sales',   'icon' => 'bx-trending-up'],
                                ['name' => 'Item Wise Purchase',  'desc' => 'Product-wise total purchase quantity and cost', 'url' => '/reports/item-purchase','icon' => 'bx-trending-down'],
                                ['name' => 'Low Stock Alert',     'desc' => 'Products below minimum stock threshold',        'url' => '/reports/low-stock',    'icon' => 'bx-error'],
                                ['name' => 'Stock Summary',       'desc' => 'Current stock position for all products',       'url' => '/reports/stock-summary','icon' => 'bx-layer'],
                            ],
                        ],
                        [
                            'id'      => 'party',
                            'name'    => 'Party Reports',
                            'icon'    => 'bx-group',
                            'color'   => '#3b82f6',
                            'bg'      => '#dbeafe',
                            'reports' => [
                                ['name' => 'Customer Outstanding',  'desc' => 'Pending receivables from customers',            'url' => '/reports/customer-outstanding', 'icon' => 'bx-user-pin'],
                                ['name' => 'Customer Ledger',       'desc' => 'Transaction-wise customer account statement',   'url' => '/reports/customer-ledger',      'icon' => 'bx-user'],
                                ['name' => 'Customer Ageing',       'desc' => 'Age-wise overdue receivables from customers',   'url' => '/reports/customer-ageing',      'icon' => 'bx-time'],
                                ['name' => 'Supplier Outstanding',  'desc' => 'Pending payables to suppliers',                 'url' => '/reports/supplier-outstanding', 'icon' => 'bx-briefcase'],
                                ['name' => 'Supplier Ledger',       'desc' => 'Transaction-wise supplier account statement',   'url' => '/reports/supplier-ledger',      'icon' => 'bx-buildings'],
                            ],
                        ],
                        [
                            'id'      => 'pl',
                            'name'    => 'Profit & Loss Reports',
                            'icon'    => 'bx-line-chart',
                            'color'   => '#ef4444',
                            'bg'      => '#fee2e2',
                            'reports' => [
                                ['name' => 'P&L Statement',  'desc' => 'Gross and net profit for a period',         'url' => '/reports/pl-statement', 'icon' => 'bx-bar-chart-alt-2'],
                                ['name' => 'Balance Sheet',  'desc' => 'Assets, liabilities and equity summary',    'url' => '/reports/balance-sheet','icon' => 'bx-equalizer'],
                                ['name' => 'Trial Balance',  'desc' => 'Debit and credit totals for all accounts',  'url' => '/reports/trial-balance','icon' => 'bx-transfer-alt'],
                            ],
                        ],
                        [
                            'id'      => 'payment',
                            'name'    => 'Payment Reports',
                            'icon'    => 'bx-credit-card',
                            'color'   => '#8b5cf6',
                            'bg'      => '#ede9fe',
                            'reports' => [
                                ['name' => 'Payment Received',  'desc' => 'Receipts collected from customers',    'url' => '/reports/payment-received', 'icon' => 'bx-down-arrow-circle'],
                                ['name' => 'Payment Made',      'desc' => 'Payments made to suppliers',           'url' => '/reports/payment-made',     'icon' => 'bx-up-arrow-circle'],
                                ['name' => 'Bank Statement',    'desc' => 'Bank and cash account-wise statement', 'url' => '/reports/bank-statement',   'icon' => 'bx-bank'],
                            ],
                        ],
                        [
                            'id'      => 'summary',
                            'name'    => 'Summary Reports',
                            'icon'    => 'bx-bar-chart-alt-2',
                            'color'   => '#ec4899',
                            'bg'      => '#fce7f3',
                            'reports' => [
                                ['name' => 'Sales Summary',     'desc' => 'Month-wise or period-wise sales totals',    'url' => '/reports/sales-summary',    'icon' => 'bx-trending-up'],
                                ['name' => 'Purchase Summary',  'desc' => 'Month-wise or period-wise purchase totals', 'url' => '/reports/purchase-summary', 'icon' => 'bx-trending-down'],
                                ['name' => 'Monthly Summary',   'desc' => 'Combined income and expense by month',      'url' => '/reports/monthly-summary',  'icon' => 'bx-calendar-check'],
                            ],
                        ],
                        [
                            'id'      => 'daybook',
                            'name'    => 'Day Book',
                            'icon'    => 'bx-calendar',
                            'color'   => '#64748b',
                            'bg'      => '#f1f5f9',
                            'reports' => [
                                ['name' => 'Day Book', 'desc' => 'All transactions recorded on a given date', 'url' => '/reports/daybook', 'icon' => 'bx-calendar-event'],
                            ],
                        ],
                    ];

                    $totalReports = array_sum(array_map(fn($c) => count($c['reports']), $categories));
                    ?>

                    <!-- ── Page Header ─────────────────────────────────────── -->
                    <div class="rpt-page-header d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rpt-ph-icon">
                                <i class="bx bx-bar-chart-alt-2"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-semibold">Reports</h5>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo $totalReports; ?> reports across <?php echo count($categories); ?> categories</div>
                            </div>
                        </div>
                        <div class="rpt-search-wrap">
                            <i class="bx bx-search rpt-search-icon"></i>
                            <input type="text" id="rptSearch" class="rpt-search-input" placeholder="Search reports..." autocomplete="off" />
                            <i class="bx bx-x rpt-search-clear d-none" id="rptSearchClear"></i>
                        </div>
                    </div>

                    <!-- ── Hub Body ─────────────────────────────────────────── -->
                    <div class="row g-3 align-items-start">

                        <!-- Left Nav -->
                        <div class="col-md-3">
                            <div class="rpt-nav-card">
                                <div class="rpt-nav-item active" data-cat="all">
                                    <span class="rpt-nav-icon-wrap" style="background:#f1f5f9;">
                                        <i class="bx bx-grid-alt" style="color:#64748b;"></i>
                                    </span>
                                    <span class="rpt-nav-label">All Reports</span>
                                    <span class="rpt-nav-badge"><?php echo $totalReports; ?></span>
                                </div>
                                <div class="rpt-nav-divider"></div>
                                <?php foreach ($categories as $cat): ?>
                                <div class="rpt-nav-item" data-cat="<?php echo $cat['id']; ?>">
                                    <span class="rpt-nav-icon-wrap" style="background:<?php echo $cat['bg']; ?>;">
                                        <i class="bx <?php echo $cat['icon']; ?>" style="color:<?php echo $cat['color']; ?>;"></i>
                                    </span>
                                    <span class="rpt-nav-label"><?php echo $cat['name']; ?></span>
                                    <span class="rpt-nav-badge"><?php echo count($cat['reports']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Right Content -->
                        <div class="col-md-9">

                            <!-- No results message -->
                            <div id="rptNoResults" class="d-none text-center py-5 text-muted">
                                <i class="bx bx-search-alt" style="font-size:2.5rem;display:block;margin-bottom:.5rem;"></i>
                                No reports match your search.
                            </div>

                            <?php foreach ($categories as $cat): ?>
                            <div class="rpt-section mb-4" data-section="<?php echo $cat['id']; ?>">
                                <div class="rpt-section-header mb-3">
                                    <span class="rpt-section-icon-wrap" style="background:<?php echo $cat['bg']; ?>;">
                                        <i class="bx <?php echo $cat['icon']; ?>" style="color:<?php echo $cat['color']; ?>;"></i>
                                    </span>
                                    <span class="rpt-section-title"><?php echo $cat['name']; ?></span>
                                    <span class="rpt-section-count"><?php echo count($cat['reports']); ?></span>
                                </div>
                                <div class="row g-3">
                                    <?php foreach ($cat['reports'] as $rpt): ?>
                                    <div class="col-md-4 rpt-card-col" data-name="<?php echo strtolower($rpt['name']); ?>" data-cat="<?php echo $cat['id']; ?>">
                                        <a href="<?php echo $rpt['url']; ?>" class="rpt-card">
                                            <div class="rpt-card-icon-wrap" style="background:<?php echo $cat['bg']; ?>;">
                                                <i class="bx <?php echo $rpt['icon']; ?>" style="color:<?php echo $cat['color']; ?>;"></i>
                                            </div>
                                            <div class="rpt-card-body">
                                                <div class="rpt-card-name"><?php echo $rpt['name']; ?></div>
                                                <div class="rpt-card-desc"><?php echo $rpt['desc']; ?></div>
                                            </div>
                                            <i class="bx bx-chevron-right rpt-card-arrow"></i>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                </div>
                <?php $this->load->view('common/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Reports Hub Theme ─────────────────────────────────────── */
.rpt-ph-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: #dbeafe; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.rpt-ph-icon i { font-size: 1.35rem; color: #3b82f6; }

/* Search */
.rpt-search-wrap {
    position: relative; display: flex; align-items: center;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 0 10px; min-width: 260px;
}
.rpt-search-icon { color: #94a3b8; font-size: 1rem; margin-right: 6px; flex-shrink: 0; }
.rpt-search-input {
    border: none; outline: none; background: transparent;
    font-size: .82rem; width: 100%; padding: 8px 0; color: #334155;
}
.rpt-search-input::placeholder { color: #94a3b8; }
.rpt-search-clear { color: #94a3b8; cursor: pointer; font-size: 1rem; flex-shrink: 0; }
.rpt-search-clear:hover { color: #64748b; }

/* Left Nav */
.rpt-nav-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    overflow: hidden; position: sticky; top: 76px;
}
.rpt-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; cursor: pointer;
    transition: background .15s;
}
.rpt-nav-item:hover { background: #f8fafc; }
.rpt-nav-item.active { background: #f0f4ff; }
.rpt-nav-icon-wrap {
    width: 28px; height: 28px; border-radius: 6px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.rpt-nav-icon-wrap i { font-size: .88rem; }
.rpt-nav-label { flex: 1; font-size: .82rem; color: #374151; font-weight: 500; }
.rpt-nav-item.active .rpt-nav-label { color: #3b82f6; font-weight: 600; }
.rpt-nav-badge {
    font-size: .68rem; font-weight: 600; color: #64748b;
    background: #f1f5f9; border-radius: 10px; padding: 1px 7px;
    min-width: 22px; text-align: center;
}
.rpt-nav-item.active .rpt-nav-badge { background: #dbeafe; color: #3b82f6; }
.rpt-nav-divider { height: 1px; background: #f1f5f9; margin: 2px 0; }

/* Section Header */
.rpt-section-header {
    display: flex; align-items: center; gap: 10px;
}
.rpt-section-icon-wrap {
    width: 30px; height: 30px; border-radius: 7px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.rpt-section-icon-wrap i { font-size: .95rem; }
.rpt-section-title { font-size: .88rem; font-weight: 700; color: #1e293b; letter-spacing: .01em; }
.rpt-section-count {
    font-size: .7rem; color: #64748b; background: #f1f5f9;
    border-radius: 10px; padding: 1px 8px; font-weight: 600;
}

/* Report Card */
.rpt-card {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 13px 14px; text-decoration: none; color: inherit;
    transition: box-shadow .15s, border-color .15s, transform .1s;
    height: 100%;
}
.rpt-card:hover {
    border-color: #cbd5e1; box-shadow: 0 4px 16px rgba(0,0,0,.07);
    transform: translateY(-1px); color: inherit; text-decoration: none;
}
.rpt-card-icon-wrap {
    width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.rpt-card-icon-wrap i { font-size: 1.1rem; }
.rpt-card-body { flex: 1; min-width: 0; }
.rpt-card-name { font-size: .82rem; font-weight: 600; color: #1e293b; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rpt-card-desc { font-size: .72rem; color: #64748b; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.rpt-card-arrow { font-size: 1.1rem; color: #cbd5e1; flex-shrink: 0; transition: color .15s, transform .15s; }
.rpt-card:hover .rpt-card-arrow { color: #3b82f6; transform: translateX(2px); }
</style>

<script>
(function () {
    var navItems   = document.querySelectorAll('.rpt-nav-item');
    var sections   = document.querySelectorAll('.rpt-section');
    var cardCols   = document.querySelectorAll('.rpt-card-col');
    var searchInput = document.getElementById('rptSearch');
    var clearBtn   = document.getElementById('rptSearchClear');
    var noResults  = document.getElementById('rptNoResults');
    var activeCat  = 'all';

    // ── Category filter ────────────────────────────────────────
    navItems.forEach(function (item) {
        item.addEventListener('click', function () {
            navItems.forEach(function (n) { n.classList.remove('active'); });
            item.classList.add('active');
            activeCat = item.dataset.cat;
            applyFilter();

            if (activeCat !== 'all') {
                var target = document.querySelector('.rpt-section[data-section="' + activeCat + '"]');
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ── Search ─────────────────────────────────────────────────
    searchInput.addEventListener('input', function () {
        clearBtn.classList.toggle('d-none', !this.value);
        applyFilter();
    });
    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        clearBtn.classList.add('d-none');
        applyFilter();
    });

    // ── Scroll spy — update active nav ────────────────────────
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting && searchInput.value === '') {
                var id = entry.target.dataset.section;
                navItems.forEach(function (n) { n.classList.remove('active'); });
                var match = document.querySelector('.rpt-nav-item[data-cat="' + id + '"]');
                if (match) match.classList.add('active');
            }
        });
    }, { rootMargin: '-30% 0px -60% 0px' });
    sections.forEach(function (s) { observer.observe(s); });

    // ── Filter logic ───────────────────────────────────────────
    function applyFilter() {
        var term = searchInput.value.trim().toLowerCase();
        var visibleCount = 0;

        cardCols.forEach(function (col) {
            var matchCat  = (activeCat === 'all') || (col.dataset.cat === activeCat);
            var matchTerm = !term || col.dataset.name.includes(term);
            var show = matchCat && matchTerm;
            col.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Show/hide section headers based on visible cards
        sections.forEach(function (sec) {
            var hasVisible = sec.querySelectorAll('.rpt-card-col:not([style*="display: none"])').length > 0;
            sec.style.display = hasVisible ? '' : 'none';
        });

        noResults.classList.toggle('d-none', visibleCount > 0);
    }
})();
</script>

<?php $this->load->view('common/footer_desc'); ?>
