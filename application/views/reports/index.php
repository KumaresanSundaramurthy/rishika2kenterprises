<?php defined('BASEPATH') or exit('No direct script access allowed');

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
            ['name' => 'Sales Register',           'desc' => 'Invoice-wise sales with tax breakup in a date range',    'url' => '/reports/sales-register',          'icon' => 'bx-store'],
            ['name' => 'Purchase Register',        'desc' => 'Purchase bill-wise report with tax breakup',             'url' => '/reports/purchase-register',       'icon' => 'bx-cart'],
            ['name' => 'Sales Return Register',    'desc' => 'Credit note-wise sales returns in a date range',         'url' => '/reports/sales-return-register',   'icon' => 'bx-undo'],
            ['name' => 'Purchase Return Register', 'desc' => 'Debit note-wise purchase returns in a date range',       'url' => '/reports/purchase-return-register','icon' => 'bx-redo'],
            ['name' => 'Delivery Challan Register','desc' => 'Dispatch-wise delivery challans in a date range',        'url' => '/reports/dc-register',             'icon' => 'bx-package'],
            ['name' => 'Expense Register',         'desc' => 'All expenses recorded in a date range',                  'url' => '/reports/expense-register',        'icon' => 'bx-wallet'],
        ],
    ],
    [
        'id'      => 'billwise',
        'name'    => 'Bill-wise Item Reports',
        'icon'    => 'bx-list-ul',
        'color'   => '#f59e0b',
        'bg'      => '#fef3c7',
        'reports' => [
            ['name' => 'Invoice Item-wise',         'desc' => 'Each invoice line-item with rate, qty and tax',   'url' => '/reports/invoice-itemwise',  'icon' => 'bx-receipt'],
            ['name' => 'Purchase Item-wise',        'desc' => 'Each purchase line-item with rate, qty and tax',  'url' => '/reports/purchase-itemwise', 'icon' => 'bx-purchase-tag'],
            ['name' => 'Sales Return Item-wise',    'desc' => 'Returned items per credit note in detail',        'url' => '/reports/sr-itemwise',       'icon' => 'bx-undo'],
            ['name' => 'Purchase Return Item-wise', 'desc' => 'Returned items per debit note in detail',         'url' => '/reports/pr-itemwise',       'icon' => 'bx-redo'],
        ],
    ],
    [
        'id'      => 'item',
        'name'    => 'Item Reports',
        'icon'    => 'bx-box',
        'color'   => '#10b981',
        'bg'      => '#d1fae5',
        'reports' => [
            ['name' => 'Item Wise Sales',    'desc' => 'Product-wise total sales quantity and value',   'url' => '/reports/item-sales',       'icon' => 'bx-trending-up'],
            ['name' => 'Item Wise Purchase', 'desc' => 'Product-wise total purchase quantity and cost', 'url' => '/reports/item-purchase',    'icon' => 'bx-trending-down'],
            ['name' => 'Low Stock Alert',    'desc' => 'Products below minimum stock threshold',        'url' => '/reports/low-stock',        'icon' => 'bx-error'],
            ['name' => 'Stock Summary',      'desc' => 'Current stock position for all products',       'url' => '/reports/stock-summary',    'icon' => 'bx-layer'],
            ['name' => 'Brand Wise Sales',   'desc' => 'Sales quantity and value grouped by brand',     'url' => '/reports/brand-wise-sales', 'icon' => 'bx-purchase-tag-alt'],
            ['name' => 'Variant Stock',      'desc' => 'Current stock position per brand and size',     'url' => '/reports/variant-stock',    'icon' => 'bx-git-branch'],
        ],
    ],
    [
        'id'      => 'party',
        'name'    => 'Party Reports',
        'icon'    => 'bx-group',
        'color'   => '#3b82f6',
        'bg'      => '#dbeafe',
        'reports' => [
            ['name' => 'Customer Outstanding', 'desc' => 'Pending receivables from customers',            'url' => '/reports/customer-outstanding', 'icon' => 'bx-user-pin'],
            ['name' => 'Customer Ledger',      'desc' => 'Transaction-wise customer account statement',   'url' => '/reports/customer-ledger',      'icon' => 'bx-user'],
            ['name' => 'Customer Ageing',      'desc' => 'Age-wise overdue receivables from customers',   'url' => '/reports/customer-ageing',      'icon' => 'bx-time'],
            ['name' => 'Supplier Outstanding', 'desc' => 'Pending payables to suppliers',                 'url' => '/reports/supplier-outstanding', 'icon' => 'bx-briefcase'],
            ['name' => 'Supplier Ledger',      'desc' => 'Transaction-wise supplier account statement',   'url' => '/reports/supplier-ledger',      'icon' => 'bx-buildings'],
        ],
    ],
    [
        'id'      => 'pl',
        'name'    => 'Profit & Loss Reports',
        'icon'    => 'bx-line-chart',
        'color'   => '#ef4444',
        'bg'      => '#fee2e2',
        'reports' => [
            ['name' => 'P&L Statement', 'desc' => 'Gross and net profit for a period',        'url' => '/reports/pl-statement', 'icon' => 'bx-bar-chart-alt-2'],
            ['name' => 'Balance Sheet', 'desc' => 'Assets, liabilities and equity summary',   'url' => '/reports/balance-sheet','icon' => 'bx-equalizer'],
            ['name' => 'Trial Balance', 'desc' => 'Debit and credit totals for all accounts', 'url' => '/reports/trial-balance','icon' => 'bx-transfer-alt'],
        ],
    ],
    [
        'id'      => 'payment',
        'name'    => 'Payment Reports',
        'icon'    => 'bx-credit-card',
        'color'   => '#8b5cf6',
        'bg'      => '#ede9fe',
        'reports' => [
            ['name' => 'Payment Received', 'desc' => 'Receipts collected from customers',    'url' => '/reports/payment-received', 'icon' => 'bx-down-arrow-circle'],
            ['name' => 'Payment Made',     'desc' => 'Payments made to suppliers',           'url' => '/reports/payment-made',     'icon' => 'bx-up-arrow-circle'],
            ['name' => 'Bank Statement',   'desc' => 'Bank and cash account-wise statement', 'url' => '/reports/bank-statement',   'icon' => 'bx-bank'],
        ],
    ],
    [
        'id'      => 'summary',
        'name'    => 'Summary Reports',
        'icon'    => 'bx-bar-chart-alt-2',
        'color'   => '#ec4899',
        'bg'      => '#fce7f3',
        'reports' => [
            ['name' => 'Sales Summary',    'desc' => 'Month-wise or period-wise sales totals',    'url' => '/reports/sales-summary',    'icon' => 'bx-trending-up'],
            ['name' => 'Purchase Summary', 'desc' => 'Month-wise or period-wise purchase totals', 'url' => '/reports/purchase-summary', 'icon' => 'bx-trending-down'],
            ['name' => 'Monthly Summary',  'desc' => 'Combined income and expense by month',      'url' => '/reports/monthly-summary',  'icon' => 'bx-calendar-check'],
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

$this->load->view('common/transactions/header'); ?>

<style>
/* ── Reports Hub ──────────────────────────────────────────────────── */

/* Nav search box inside the left card */
.rpt-nav-search { padding:10px 12px;border-bottom:1px solid #e2e8f0 }
.rpt-nav-search-inner { display:flex;align-items:center;gap:7px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;padding:6px 10px;transition:border-color .15s }
.rpt-nav-search-inner:focus-within { border-color:#3b82f6;background:#fff }
.rpt-nav-search-icon { color:#94a3b8;font-size:.95rem;flex-shrink:0 }
.rpt-nav-search-input { border:none;outline:none;background:transparent;font-size:var(--r2k-text-82);flex:1;color:#334155;padding:0 }
.rpt-nav-search-input::placeholder { color:#94a3b8 }
.rpt-search-clear { color:#94a3b8;cursor:pointer;font-size:1rem;flex-shrink:0 }
.rpt-search-clear:hover { color:#64748b }

/* Left Nav */
.rpt-nav-card { background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;position:sticky;top:76px }
.rpt-nav-item { display:flex;align-items:center;gap:10px;padding:9px 14px 9px 11px;cursor:pointer;transition:background .15s;border-left:3px solid transparent }
.rpt-nav-item:hover { background:#f8fafc }
.rpt-nav-item.active { background:#f0f4ff;border-left-color:#3b82f6 }
.rpt-nav-icon-wrap { width:28px;height:28px;border-radius:6px;flex-shrink:0;display:flex;align-items:center;justify-content:center }
.rpt-nav-icon-wrap i { font-size:var(--r2k-text-88) }
.rpt-nav-icon-all { background:#f1f5f9 }
.rpt-nav-icon-all i { color:#64748b }
.rpt-nav-label { flex:1;font-size:var(--r2k-text-82);color:#374151;font-weight:500 }
.rpt-nav-item.active .rpt-nav-label { color:#3b82f6;font-weight:600 }
.rpt-nav-badge { font-size:var(--r2k-text-xs);font-weight:600;color:#64748b;background:#f1f5f9;border-radius:10px;padding:1px 7px;min-width:22px;text-align:center }
.rpt-nav-item.active .rpt-nav-badge { background:#dbeafe;color:#3b82f6 }
.rpt-nav-divider { height:1px;background:#f1f5f9;margin:2px 0 }

/* Section Header */
.rpt-section-header { display:flex;align-items:center;gap:10px;padding-bottom:10px;border-bottom:2px solid var(--rpt-sec-color,#e2e8f0);margin-bottom:12px }
.rpt-section-icon-wrap { width:30px;height:30px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center }
.rpt-section-icon-wrap i { font-size:.95rem }
.rpt-section-title { font-size:var(--r2k-text-88);font-weight:700;color:#1e293b;letter-spacing:.01em }
.rpt-section-count { font-size:var(--r2k-text-70);color:var(--rpt-sec-color,#64748b);background:var(--rpt-sec-bg,#f1f5f9);border-radius:10px;padding:1px 8px;font-weight:700 }

/* Report Card */
.rpt-card { display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:13px 14px;text-decoration:none;color:inherit;transition:box-shadow .15s, border-color .15s, transform .1s;height:100%;border-left:3px solid transparent }
.rpt-card:hover { border-color:#cbd5e1;box-shadow:0 4px 16px rgba(0,0,0,.07);transform:translateY(-1px);color:inherit;text-decoration:none;border-left-color:var(--rpt-sec-color,#3b82f6) }
.rpt-card-icon-wrap { width:38px;height:38px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center }
.rpt-card-icon-wrap i { font-size:1.1rem }
.rpt-card-body { flex:1;min-width:0 }
.rpt-card-name { font-size:var(--r2k-text-82);font-weight:600;color:#1e293b;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.rpt-card-desc { font-size:var(--r2k-text-sm);color:#64748b;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;overflow:hidden }
.rpt-card-arrow { font-size:1.1rem;color:#cbd5e1;flex-shrink:0;transition:color .15s, transform .15s }
.rpt-card:hover .rpt-card-arrow { color:var(--rpt-sec-color,#3b82f6);transform:translateX(2px) }

/* No results */
.rpt-no-results-icon { font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.45 }

/* ── Per-category colours ─────────────────────────────────────── */
<?php foreach ($categories as $cat): ?>
.rpt-section[data-section="<?php echo $cat['id']; ?>"] { --rpt-sec-color:<?php echo $cat['color']; ?>; --rpt-sec-bg:<?php echo $cat['bg']; ?>; }
.rpt-nav-icon-wrap[data-cat="<?php echo $cat['id']; ?>"],
.rpt-section-icon-wrap[data-cat="<?php echo $cat['id']; ?>"],
.rpt-card-icon-wrap[data-cat="<?php echo $cat['id']; ?>"] { background:<?php echo $cat['bg']; ?>; }
.rpt-nav-icon-wrap[data-cat="<?php echo $cat['id']; ?>"] i,
.rpt-section-icon-wrap[data-cat="<?php echo $cat['id']; ?>"] i,
.rpt-card-icon-wrap[data-cat="<?php echo $cat['id']; ?>"] i { color:<?php echo $cat['color']; ?>; }
<?php endforeach; ?>

@media (prefers-color-scheme: dark) {
    .rpt-nav-card,.rpt-card { background:#1e293b;border-color:#334155 }
    .rpt-nav-label,.rpt-card-name,.rpt-section-title { color:#f1f5f9 }
    .rpt-nav-item:hover { background:#0f172a }
    .rpt-nav-item.active { background:#1e3a5f }
    .rpt-nav-divider,.rpt-nav-search { border-color:#334155 }
    .rpt-nav-search-inner { background:#0f172a;border-color:#334155 }
    .rpt-nav-search-input { color:#f1f5f9 }
}
:root[data-theme="dark"] .rpt-nav-card,
:root[data-theme="dark"] .rpt-card { background:#1e293b;border-color:#334155 }
:root[data-theme="dark"] .rpt-nav-label,
:root[data-theme="dark"] .rpt-card-name,
:root[data-theme="dark"] .rpt-section-title { color:#f1f5f9 }
:root[data-theme="dark"] .rpt-nav-item:hover { background:#0f172a }
:root[data-theme="dark"] .rpt-nav-item.active { background:#1e3a5f }
:root[data-theme="dark"] .rpt-nav-search { border-color:#334155 }
:root[data-theme="dark"] .rpt-nav-search-inner { background:#0f172a;border-color:#334155 }
:root[data-theme="dark"] .rpt-nav-search-input { color:#f1f5f9 }
</style>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content r2k-form-body">

                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => t('lbl_reports', 'Reports'),
                    'pageDescription' => $totalReports . ' ' . t('lbl_reports_across', 'reports across') . ' ' . count($categories) . ' ' . t('lbl_categories', 'categories'),
                    'pageIcon'        => 'bx-bar-chart-alt-2',
                    'pageIconBg'      => '#dbeafe',
                    'pageIconColor'   => '#3b82f6',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="row g-3 align-items-start">

                        <!-- Left Nav -->
                        <div class="col-md-3">
                            <div class="rpt-nav-card">

                                <!-- Search at top of nav -->
                                <div class="rpt-nav-search">
                                    <div class="rpt-nav-search-inner">
                                        <i class="bx bx-search rpt-nav-search-icon"></i>
                                        <input type="text" id="rptSearch" class="rpt-nav-search-input" placeholder="<?php echo t('lbl_search_reports', 'Search reports...'); ?>" autocomplete="off" />
                                        <i class="bx bx-x rpt-search-clear d-none" id="rptSearchClear"></i>
                                    </div>
                                </div>

                                <div class="rpt-nav-item active" data-cat="all">
                                    <span class="rpt-nav-icon-wrap rpt-nav-icon-all">
                                        <i class="bx bx-grid-alt"></i>
                                    </span>
                                    <span class="rpt-nav-label"><?php echo t('lbl_all_reports', 'All Reports'); ?></span>
                                    <span class="rpt-nav-badge"><?php echo $totalReports; ?></span>
                                </div>
                                <div class="rpt-nav-divider"></div>
                                <?php foreach ($categories as $cat): ?>
                                <div class="rpt-nav-item" data-cat="<?php echo $cat['id']; ?>">
                                    <span class="rpt-nav-icon-wrap" data-cat="<?php echo $cat['id']; ?>">
                                        <i class="bx <?php echo $cat['icon']; ?>"></i>
                                    </span>
                                    <span class="rpt-nav-label"><?php echo t('cat_' . $cat['id'], $cat['name']); ?></span>
                                    <span class="rpt-nav-badge"><?php echo count($cat['reports']); ?></span>
                                </div>
                                <?php endforeach; ?>

                            </div>
                        </div>

                        <!-- Right Content -->
                        <div class="col-md-9">

                            <div id="rptNoResults" class="d-none text-center py-5 text-muted">
                                <i class="bx bx-search-alt rpt-no-results-icon"></i>
                                <?php echo t('lbl_no_reports_found', 'No reports found.'); ?>
                            </div>

                            <?php foreach ($categories as $cat): ?>
                            <div class="rpt-section mb-4" data-section="<?php echo $cat['id']; ?>">
                                <div class="rpt-section-header">
                                    <span class="rpt-section-icon-wrap" data-cat="<?php echo $cat['id']; ?>">
                                        <i class="bx <?php echo $cat['icon']; ?>"></i>
                                    </span>
                                    <span class="rpt-section-title"><?php echo t('cat_' . $cat['id'], $cat['name']); ?></span>
                                    <span class="rpt-section-count"><?php echo count($cat['reports']); ?></span>
                                </div>
                                <div class="row g-3">
                                    <?php foreach ($cat['reports'] as $rpt):
                                        $rptKey = 'report_' . str_replace('-', '_', basename($rpt['url']));
                                    ?>
                                    <div class="col-md-4 rpt-card-col" data-name="<?php echo strtolower($rpt['name']); ?>" data-cat="<?php echo $cat['id']; ?>">
                                        <a href="<?php echo rtrim(base_url(), '/') . $rpt['url']; ?>" class="rpt-card">
                                            <div class="rpt-card-icon-wrap" data-cat="<?php echo $cat['id']; ?>">
                                                <i class="bx <?php echo $rpt['icon']; ?>"></i>
                                            </div>
                                            <div class="rpt-card-body">
                                                <div class="rpt-card-name"><?php echo t($rptKey, $rpt['name']); ?></div>
                                                <div class="rpt-card-desc"><?php echo t($rptKey . '_desc', $rpt['desc']); ?></div>
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

<script>
(function () {
    var _activeCat = 'all';
    /* Bug 1 fix: block scroll spy on page load; enable after 500ms */
    var _spyReady  = false;
    setTimeout(function () { _spyReady = true; }, 500);

    /**
     * Set active highlight on a nav item; pass null to clear all.
     * @param {string|null} cat
     * @returns {void}
     */
    function _setNavActive(cat) {
        $('.rpt-nav-item').removeClass('active');
        if (cat) { $('.rpt-nav-item[data-cat="' + cat + '"]').addClass('active'); }
    }

    /**
     * Show/hide cards and sections based on active category and search term.
     * @returns {void}
     */
    function _applyFilter() {
        var term         = $('#rptSearch').val().trim().toLowerCase();
        var visibleCount = 0;

        /* Show all sections first so :visible works correctly on their children */
        $('.rpt-section').show();

        $('.rpt-card-col').each(function () {
            var $col     = $(this);
            var matchCat = (_activeCat === 'all') || ($col.data('cat') === _activeCat);
            var matchTxt = !term || $col.data('name').includes(term);
            if (matchCat && matchTxt) { $col.show(); visibleCount++; } else { $col.hide(); }
        });

        $('.rpt-section').each(function () {
            $(this).toggle($(this).find('.rpt-card-col:visible').length > 0);
        });

        $('#rptNoResults').toggleClass('d-none', visibleCount > 0);
    }

    /**
     * Activate a nav item, filter right side immediately, then scroll.
     * Bug 2 fix: filter runs before scroll so right side updates instantly.
     * Bug 1 fix: scroll spy is paused during programmatic scroll.
     * @param {string} cat
     * @returns {void}
     */
    function _activateNav(cat) {
        _activeCat = cat;
        _setNavActive(cat);
        _applyFilter();

        if (cat !== 'all') {
            var $target = $('.rpt-section[data-section="' + cat + '"]');
            if ($target.length) {
                _spyReady = false;
                $('html, body').animate({ scrollTop: $target.offset().top - 20 }, 300, function () {
                    _spyReady = true;
                });
            }
        }
    }

    /* ── Category nav clicks ──────────────────────────────────── */
    $(document).on('click', '.rpt-nav-item', function () {
        _activateNav($(this).data('cat'));
    });

    /* ── Search ───────────────────────────────────────────────── */
    $('#rptSearch').on('input', function () {
        var hasVal = !!$(this).val();
        $('#rptSearchClear').toggleClass('d-none', !hasVal);

        if (hasVal) {
            /* Bug 3 fix: search is cross-category — clear nav active state */
            _activeCat = 'all';
            _setNavActive(null);
        } else {
            _setNavActive('all');
        }

        _applyFilter();
    });

    $('#rptSearchClear').on('click', function () {
        $('#rptSearch').val('');
        $(this).addClass('d-none');
        _activeCat = 'all';
        _setNavActive('all');
        _applyFilter();
    });

    /* ── Scroll spy: only when page ready, no category selected, no search */
    var observer = new IntersectionObserver(function (entries) {
        if (!_spyReady || _activeCat !== 'all' || $('#rptSearch').val()) { return; }
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                _setNavActive(entry.target.dataset.section);
            }
        });
    }, { rootMargin: '-30% 0px -60% 0px' });

    document.querySelectorAll('.rpt-section').forEach(function (s) { observer.observe(s); });

}());
</script>

<?php $this->load->view('common/footer_desc'); ?>
