<?php
/**
 * Shared Transaction Status Config
 * Include in every module's list.php partial.
 *
 * Expects: $moduleContext (string) = 'invoice' | 'purchase' | 'quotation' |
 *          'salesorder' | 'salesreturn' | 'purchasereturn'
 */

$moduleContext = $moduleContext ?? 'invoice';

// ── Badge CSS class per status ──────────────────────────────────
$statusBadgeClass = [
    'Draft'      => 'trans-badge-Draft',
    'Dispatched' => 'trans-badge-Pending',
    'Delivered'  => 'trans-badge-Confirmed',
    'Issued'    => 'trans-badge-Issued',
    'Sent'      => 'trans-badge-Sent',
    'Paid'      => 'trans-badge-Paid',
    'Partial'   => 'trans-badge-Partial',
    'Overdue'   => 'trans-badge-Overdue',
    'Cancelled'  => 'trans-badge-Cancelled',
    'Completed'  => 'trans-badge-Confirmed',
    'Converted'  => 'trans-badge-Converted',
    'Pending'    => 'trans-badge-Pending',
    'Accepted'  => 'trans-badge-Accepted',
    'Rejected'  => 'trans-badge-Rejected',
    'Confirmed' => 'trans-badge-Confirmed',
    'Expired'   => 'trans-badge-Expired',
    'Fulfilled' => 'trans-badge-Fulfilled',
    'Received'  => 'trans-badge-Paid',
    'Approved'  => 'trans-badge-Confirmed',
    'Returned'  => 'trans-badge-Fulfilled',
];

// ── Boxicon per status ──────────────────────────────────────────
$statusIcon = [
    'Draft'      => 'bx-pencil',
    'Dispatched' => 'bx-package',
    'Delivered'  => 'bx-check-circle',
    'Issued'    => 'bx-send',
    'Sent'      => 'bx-send',
    'Paid'      => 'bx-check-circle',
    'Partial'   => 'bx-adjust',
    'Overdue'   => 'bx-time-five',
    'Cancelled'  => 'bx-x-circle',
    'Completed'  => 'bx-check-double',
    'Converted'  => 'bx-transfer-alt',
    'Pending'    => 'bx-time',
    'Accepted'  => 'bx-check',
    'Rejected'  => 'bx-x',
    'Confirmed' => 'bx-check-double',
    'Expired'   => 'bx-calendar-x',
    'Fulfilled' => 'bx-package',
    'Received'  => 'bx-check-circle',
    'Approved'  => 'bx-check-double',
    'Returned'  => 'bx-undo',
];

// ── Terminal states (no more transitions) ───────────────────────
$terminalStatuses = ['Paid', 'Cancelled', 'Completed', 'Converted', 'Fulfilled', 'Rejected', 'Received', 'Delivered', 'Returned'];

// ── Status transitions per module ───────────────────────────────
$statusTransitions = [
    'invoice' => [
        'Draft'  => [['db' => 'Issued',    'label' => 'Issue Invoice']],
        'Issued' => [['db' => 'Paid',      'label' => 'Mark as Paid'],
                     ['db' => 'Partial',   'label' => 'Mark as Partial'],
                     ['db' => 'Cancelled', 'label' => 'Cancel Invoice']],
        'Partial'=> [['db' => 'Paid',      'label' => 'Mark as Paid'],
                     ['db' => 'Cancelled', 'label' => 'Cancel Invoice']],
    ],
    'purchase' => [
        'Draft'    => [['db' => 'Received',  'label' => 'Mark as Received']],
        'Received' => [['db' => 'Paid',      'label' => 'Mark as Paid'],
                       ['db' => 'Partial',   'label' => 'Mark as Partial'],
                       ['db' => 'Cancelled', 'label' => 'Cancel Purchase']],
        'Partial'  => [['db' => 'Paid',      'label' => 'Mark as Paid']],
    ],
    'quotation' => [
        'Draft'    => [
            ['db' => 'Pending',   'label' => 'Send Quotation'],
        ],
        'Pending'  => [
            ['db' => 'Accepted',  'label' => 'Mark as Accepted'],
        ],
        'Accepted' => [
            ['db' => 'Converted', 'label' => 'Convert to Invoice',     'target' => 'Invoice'],
            ['db' => 'Converted', 'label' => 'Convert to Sales Order', 'target' => 'SalesOrder'],
            ['db' => 'Pending',   'label' => 'Revert to Open',         'target' => ''],
        ],
        'Converted'=> [],
        'Cancelled'=> [],
    ],
    'salesorder'       => [],
    'deliverychallan'  => [],
    'proformainvoice'  => [
        'Draft'   => [['db' => 'Sent',      'label' => 'Send Pro Forma']],
        'Sent'    => [
            ['db' => 'Converted', 'label' => 'Convert to Invoice'],
            ['db' => 'Expired',   'label' => 'Mark as Expired'],
            ['db' => 'Cancelled', 'label' => 'Cancel'],
        ],
        'Expired' => [['db' => 'Sent', 'label' => 'Reactivate']],
    ],
    'salesreturn'   => [
        'Draft'    => [['db' => 'Approved',  'label' => 'Approve Return']],
        'Approved' => [['db' => 'Cancelled', 'label' => 'Cancel']],
    ],
    'purchasereturn'=> [
        'Draft'    => [['db' => 'Approved',  'label' => 'Approve Return']],
        'Approved' => [['db' => 'Cancelled', 'label' => 'Cancel']],
    ],
    'expense' => [
        'Pending' => [
            ['db' => 'Paid',      'label' => 'Mark as Paid'],
            ['db' => 'Partial',   'label' => 'Record Partial Payment'],
            ['db' => 'Cancelled', 'label' => 'Cancel Expense'],
        ],
        'Partial' => [
            ['db' => 'Paid',      'label' => 'Mark as Paid'],
            ['db' => 'Cancelled', 'label' => 'Cancel Expense'],
        ],
    ],
    'indirectincome' => [
        'Pending' => [
            ['db' => 'Received',  'label' => 'Mark as Received'],
            ['db' => 'Partial',   'label' => 'Record Partial Receipt'],
            ['db' => 'Cancelled', 'label' => 'Cancel Income'],
        ],
        'Partial' => [
            ['db' => 'Received',  'label' => 'Mark as Received'],
            ['db' => 'Cancelled', 'label' => 'Cancel Income'],
        ],
    ],
];

$moduleTransitions = $statusTransitions[$moduleContext] ?? [];
