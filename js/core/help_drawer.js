/**
 * Context-aware Help Drawer
 * Detects the current module from the URL and renders relevant help content.
 */

(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Module definitions                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * @typedef {Object} HelpModule
     * @property {string} icon        - Boxicon class (bx bx-*)
     * @property {string} title       - Module display name
     * @property {string} overview    - 2–3 sentence description
     * @property {string[]} actions   - Key things the user can do
     * @property {string[]} tips      - Practical tips (1–2)
     */

    /** @type {Object.<string, HelpModule>} */
    var HELP_MODULES = {
        customers: {
            icon: 'bx bxs-user-circle',
            title: 'Customers',
            overview: 'Manage all your customer profiles in one place — their contacts, addresses, credit limits, and transaction history. Organise customers into groups and types for easier filtering and pricing.',
            actions: [
                'Add, edit, and deactivate customer profiles',
                'Organise customers into groups and types',
                'Set credit limits and opening balances',
                'View outstanding balances and transaction history',
                'Send emails or SMS communications',
                'Export the full customer list',
            ],
            tips: [
                'Set the opening balance before recording any transactions — it cannot be changed once invoices exist against the customer.',
                'Use Customer Type to assign a price list, so the right price is applied automatically on every invoice.',
            ],
        },
        vendors: {
            icon: 'bx bxs-store',
            title: 'Vendors',
            overview: 'Maintain your supplier records — contacts, payment terms, and purchase history. Link a vendor to a customer profile if the same party acts as both a buyer and a supplier.',
            actions: [
                'Add, edit, and deactivate vendor profiles',
                'Organise vendors into groups',
                'Set credit limits and opening balances',
                'View outstanding payables and purchase history',
                'Send communications to vendors',
                'Export the vendor list',
            ],
            tips: [
                'Use the GSTIN field to auto-fill address details — it saves time and reduces data-entry errors.',
                'Opening balance represents what you owed the vendor before going live in this system.',
            ],
        },
        products: {
            icon: 'bx bxs-box',
            title: 'Products & Items',
            overview: 'Define every item you buy or sell — products, services, and combo bundles. Manage pricing, stock thresholds, variants (size, brand), and category groupings all from here.',
            actions: [
                'Add products, services, and combo items',
                'Set purchase price, selling price, and tax rates',
                'Configure HSN/SAC codes for GST compliance',
                'Create size and brand variants',
                'Organise items into categories',
                'Set minimum stock thresholds for low-stock alerts',
            ],
            tips: [
                'Mark an item as a Service to skip stock tracking — the system will never show it in inventory reports.',
                'Use Combo items to bundle multiple products and sell them as a single line item on invoices.',
            ],
        },
        inventory: {
            icon: 'bx bxs-data',
            title: 'Inventory',
            overview: 'Track stock levels across all your products in real time. See what\'s available, what\'s running low, and the full movement history for each item.',
            actions: [
                'View current stock quantity for every product',
                'See low-stock and out-of-stock items at a glance',
                'Drill into stock movement history (in/out timeline)',
                'Filter by product, category, or branch',
                'Export stock report',
            ],
            tips: [
                'Stock is updated automatically when you record purchases, sales, and returns — no manual entry needed.',
                'Use the Low Stock threshold on the product to get visual alerts here before you run out.',
            ],
        },
        invoices: {
            icon: 'bx bxs-receipt',
            title: 'Sales Invoices',
            overview: 'Create and manage GST-compliant sales invoices for your customers. Record payments, apply credit notes, and track each invoice from Draft through to Paid.',
            actions: [
                'Create new invoices with multiple line items',
                'Apply discounts, additional charges, and tax',
                'Record full or partial payments',
                'Apply credit notes from sales returns',
                'Convert quotations or delivery challans to invoices',
                'Print, download, and share invoices',
                'Cancel or duplicate existing invoices',
            ],
            tips: [
                'Finalize an invoice to lock the transaction number — you can still record payments and make edits to certain fields afterward.',
                'Use the Prefix to maintain separate numbering series for different branches or invoice types.',
            ],
        },
        purchases: {
            icon: 'bx bxs-purchase-tag',
            title: 'Purchases',
            overview: 'Record all your purchase bills from vendors. Track what you owe, apply debit notes from purchase returns, and maintain a clear payables ledger.',
            actions: [
                'Record purchase bills with multiple line items',
                'Apply tax, discounts, and additional charges',
                'Record payments against purchase bills',
                'Apply debit notes from purchase returns',
                'Duplicate and cancel purchases',
                'Download and print purchase bills',
            ],
            tips: [
                'A purchase stays in Draft until you finalize it — finalization locks the transaction number and updates stock.',
                'Debit notes are created via Purchase Returns and can be applied here to offset what you owe a vendor.',
            ],
        },
        salesreturns: {
            icon: 'bx bx-revision',
            title: 'Sales Returns',
            overview: 'Handle goods returned by customers. A sales return generates a credit note which can be applied to any outstanding invoice or held as on-account credit for the customer.',
            actions: [
                'Create sales return against a customer',
                'Apply the credit note to an existing invoice',
                'Record a cash refund for the returned amount',
                'Track pending and applied credit notes',
                'Cancel or duplicate sales returns',
            ],
            tips: [
                'A credit note is only available once the sales return is Finalized — draft returns do not affect the customer\'s balance.',
                'You can split a credit note across multiple invoices — apply it partially to several outstanding bills.',
            ],
        },
        purchasereturns: {
            icon: 'bx bx-undo',
            title: 'Purchase Returns',
            overview: 'Record goods returned to a vendor. A purchase return creates a debit note that can reduce what you owe on any open purchase bill.',
            actions: [
                'Create a purchase return against a vendor',
                'Apply the debit note to an existing purchase',
                'Track pending and applied debit notes',
                'Cancel or duplicate purchase returns',
            ],
            tips: [
                'Finalize the purchase return first — only finalized returns generate a debit note you can apply.',
                'If the vendor refunds you in cash instead, use a vendor payment record rather than applying the debit note.',
            ],
        },
        quotations: {
            icon: 'bx bxs-file-doc',
            title: 'Quotations',
            overview: 'Send price quotes to customers before converting them to invoices. Quotations do not affect stock or accounts — they are purely informational until converted.',
            actions: [
                'Create quotations with full line-item detail',
                'Convert a quotation directly to a sales invoice',
                'Mark quotations as Accepted, Rejected, or Expired',
                'Duplicate quotations for repeat enquiries',
                'Print or share quotation documents',
            ],
            tips: [
                'A quotation can be converted to an invoice with one click — all line items, prices, and discounts carry over.',
                'Set an expiry date on the quotation so customers know how long the pricing is valid.',
            ],
        },
        salesorders: {
            icon: 'bx bxs-cart-add',
            title: 'Sales Orders',
            overview: 'Confirm customer orders before dispatching goods. A sales order locks in the items and quantities, which can then be fulfilled via delivery challans or converted directly to invoices.',
            actions: [
                'Create sales orders from confirmed customer orders',
                'Convert a sales order to a delivery challan for dispatch',
                'Convert a sales order directly to an invoice',
                'Track order status (Draft, Confirmed, Fulfilled, Cancelled)',
                'Duplicate and cancel sales orders',
            ],
            tips: [
                'Use sales orders when you need to track what\'s been committed to a customer but not yet delivered or billed.',
                'A single sales order can be partially fulfilled over multiple delivery challans.',
            ],
        },
        purchaseorders: {
            icon: 'bx bxs-cart',
            title: 'Purchase Orders',
            overview: 'Raise purchase orders to vendors before goods arrive. A purchase order communicates your requirements and can be referenced when the purchase bill is recorded.',
            actions: [
                'Create purchase orders for vendors',
                'Track PO status (Draft, Sent, Received, Cancelled)',
                'Duplicate and cancel purchase orders',
                'Print and share purchase order documents',
            ],
            tips: [
                'Purchase orders are for tracking and communication only — they do not affect stock or accounts until a purchase bill is recorded.',
                'Use the Prefix to maintain separate series for different departments or locations.',
            ],
        },
        deliverychallans: {
            icon: 'bx bxs-truck',
            title: 'Delivery Challans',
            overview: 'Track the physical dispatch of goods to customers. A delivery challan records what left your warehouse and can later be converted to an invoice once the customer accepts the delivery.',
            actions: [
                'Create delivery challans from sales orders or independently',
                'Record partial deliveries against a single order',
                'Mark challans as Dispatched, Delivered, or Returned',
                'Process partial returns on delivered challans',
                'Convert a delivered challan to a sales invoice',
            ],
            tips: [
                'Always convert the challan to an invoice after delivery is confirmed — the challan itself does not generate any accounting entry.',
                'Partial returns let you handle scenarios where only some items come back, without cancelling the entire challan.',
            ],
        },
        payments: {
            icon: 'bx bxs-credit-card',
            title: 'Payments',
            overview: 'View and manage all customer payments in one place. Payments can be recorded directly from invoices, but this module gives you a consolidated view of every receipt and its current status.',
            actions: [
                'View all customer payment records',
                'Filter payments by customer, date, or type',
                'Delete payments that were recorded in error',
                'Manage customer bank accounts for payment recording',
                'Export the payments list',
            ],
            tips: [
                'Payments are linked to specific invoices — deleting a payment will restore the invoice\'s outstanding balance automatically.',
                'On-account payments are held as credits against the customer and can be applied to future invoices.',
            ],
        },
        expenses: {
            icon: 'bx bxs-wallet',
            title: 'Expenses',
            overview: 'Record and track all business expenses — rent, utilities, salaries, and other operating costs. Categorise expenses and record how and when they were paid.',
            actions: [
                'Record expenses with category and vendor',
                'Specify payment method and payment date',
                'Record partial payments for pending expenses',
                'Attach receipts and supporting documents',
                'Cancel and duplicate expense records',
                'Filter and export expense reports',
            ],
            tips: [
                'Create expense categories first so every expense is properly classified for reporting.',
                'A pending expense means you\'ve recorded the liability but haven\'t paid yet — mark it paid when you settle it.',
            ],
        },
        indirectincome: {
            icon: 'bx bxs-coin-stack',
            title: 'Other Income',
            overview: 'Record income that doesn\'t come from direct sales — interest received, rental income, commissions, and similar non-trading receipts. Keep these separate from your main sales for cleaner reporting.',
            actions: [
                'Record other income with category and source',
                'Specify payment method and receipt date',
                'Track partial receipts for pending income',
                'Attach supporting documents',
                'Cancel and duplicate income records',
                'Export income reports',
            ],
            tips: [
                'Use meaningful categories (e.g. "Bank Interest", "Commission Received") so your P&L report is easy to read.',
                'A pending status means the income is recognised but not yet received — update it when the money arrives.',
            ],
        },
        accounting: {
            icon: 'bx bxs-bar-chart-square',
            title: 'Accounting',
            overview: 'Access all financial reports and accounting tools — journals, ledgers, trial balance, profit & loss, balance sheet, and bank reconciliation. Everything you need for a complete financial picture.',
            actions: [
                'View and post manual journal entries',
                'Reconcile bank statements',
                'Set up recurring journal entries',
                'Generate Trial Balance, P&L, and Balance Sheet',
                'View the Day Book for a full daily transaction log',
                'Analyse financial ratios and cash flow',
            ],
            tips: [
                'Use Manual Journals for adjustments and corrections — all other entries are posted automatically by the system.',
                'Run Bank Reconciliation monthly to catch any discrepancies between your records and bank statements.',
            ],
        },
        hrms: {
            icon: 'bx bxs-group',
            title: 'HRMS',
            overview: 'Manage your team — staff profiles, roles, salary advances, and login access. Keep HR records alongside your business operations without needing a separate system.',
            actions: [
                'Add and manage staff profiles',
                'Assign roles and login access',
                'Record and track salary advance requests',
                'View staff details and employment information',
            ],
            tips: [
                'Only staff with "Has Login Access" enabled can log into the system — this is separate from their HR profile.',
                'Salary advances are tracked against each staff member and can be settled gradually over time.',
            ],
        },
    };

    /* ------------------------------------------------------------------ */
    /* Module detection                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Detects the current module key from the URL path.
     * @returns {string|null}
     */
    function detectModule() {
        var path = window.location.pathname.toLowerCase().replace(/^\//, '');
        var segment = path.split('/')[0];
        // Normalise aliases
        var aliases = {
            challan: 'deliverychallans',
            challanss: 'deliverychallans',
        };
        return aliases[segment] || (HELP_MODULES[segment] ? segment : null);
    }

    /* ------------------------------------------------------------------ */
    /* Render                                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Builds the inner HTML for the drawer body.
     * @param {HelpModule} mod
     * @returns {string}
     */
    function buildBody(mod) {
        var actionsHtml = mod.actions.map(function (a) {
            return '<li>' + _escHtml(a) + '</li>';
        }).join('');

        var tipsHtml = mod.tips.map(function (t) {
            return '<div class="apex-help-tip"><i class="bx bx-bulb"></i><span>' + _escHtml(t) + '</span></div>';
        }).join('');

        return (
            '<div class="apex-help-section">' +
                '<h6 class="apex-help-section-label">What is this module?</h6>' +
                '<p class="apex-help-overview">' + _escHtml(mod.overview) + '</p>' +
            '</div>' +
            '<div class="apex-help-section">' +
                '<h6 class="apex-help-section-label">What you can do here</h6>' +
                '<ul class="apex-help-actions">' + actionsHtml + '</ul>' +
            '</div>' +
            '<div class="apex-help-section">' +
                '<h6 class="apex-help-section-label">Tips</h6>' +
                tipsHtml +
            '</div>'
        );
    }

    /**
     * Renders the fallback when no module is matched.
     * @returns {string}
     */
    function buildFallback() {
        return (
            '<div class="apex-help-section">' +
                '<p class="apex-help-overview">Navigate to a specific module (Customers, Invoices, Products, etc.) to see help content for that page.</p>' +
            '</div>'
        );
    }

    /**
     * Minimal HTML escape.
     * @param {string} str
     * @returns {string}
     */
    function _escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ------------------------------------------------------------------ */
    /* Drawer open / close                                                  */
    /* ------------------------------------------------------------------ */

    var $drawer   = null;
    var $backdrop = null;
    var isOpen    = false;

    function openDrawer() {
        if (isOpen) return;

        var key = detectModule();
        var mod = key ? HELP_MODULES[key] : null;

        // Populate content
        if (mod) {
            $('#apexHelpIcon').attr('class', mod.icon);
            $('#apexHelpTitle').text(mod.title);
            $('#apexHelpBody').html(buildBody(mod));
        } else {
            $('#apexHelpIcon').attr('class', 'bx bx-help-circle');
            $('#apexHelpTitle').text('Help');
            $('#apexHelpBody').html(buildFallback());
        }

        $drawer.addClass('open');
        $('body').addClass('apex-help-open');
        isOpen = true;
    }

    function closeDrawer() {
        if (!isOpen) return;
        $drawer.removeClass('open');
        $('body').removeClass('apex-help-open');
        isOpen = false;
    }

    /* ------------------------------------------------------------------ */
    /* Init                                                                 */
    /* ------------------------------------------------------------------ */

    $(document).ready(function () {
        $drawer   = $('#apexHelpDrawer');
        $backdrop = $drawer.find('.apex-help-drawer-backdrop');

        $('#apexHelpBtn').on('click', function () {
            isOpen ? closeDrawer() : openDrawer();
        });

        $('#apexHelpClose').on('click', closeDrawer);
        $backdrop.on('click', closeDrawer);

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) closeDrawer();
        });
    });

}());
