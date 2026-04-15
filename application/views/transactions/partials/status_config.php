<?php
/**
 * Shared Transaction Status Config
 * Include in every module's list.php partial.
 *
 * Expects: $moduleContext (string) = 'invoice' | 'purchase' | 'quotation' |
 *          'salesorder' | 'salesreturn' | 'creditnote' | 'purchasereturn' | 'debitnote'
 */

$moduleContext = $moduleContext ?? 'invoice';

// ── Badge CSS class per status ──────────────────────────────────
$statusBadgeClass = [
    'Draft'     => 'trans-badge-Draft',
    'Issued'    => 'trans-badge-Issued',
    'Sent'      => 'trans-badge-Sent',
    'Paid'      => 'trans-badge-Paid',
    'Partial'   => 'trans-badge-Partial',
    'Overdue'   => 'trans-badge-Overdue',
    'Cancelled' => 'trans-badge-Cancelled',
    'Converted' => 'trans-badge-Converted',
    'Pending'   => 'trans-badge-Pending',
    'Accepted'  => 'trans-badge-Accepted',
    'Rejected'  => 'trans-badge-Rejected',
    'Confirmed' => 'trans-badge-Confirmed',
    'Expired'   => 'trans-badge-Expired',
    'Fulfilled' => 'trans-badge-Fulfilled',
    'Received'  => 'trans-badge-Paid',
    'Approved'  => 'trans-badge-Confirmed',
];

// ── Boxicon per status ──────────────────────────────────────────
$statusIcon = [
    'Draft'     => 'bx-pencil',
    'Issued'    => 'bx-send',
    'Sent'      => 'bx-send',
    'Paid'      => 'bx-check-circle',
    'Partial'   => 'bx-adjust',
    'Overdue'   => 'bx-time-five',
    'Cancelled' => 'bx-x-circle',
    'Converted' => 'bx-transfer-alt',
    'Pending'   => 'bx-time',
    'Accepted'  => 'bx-check',
    'Rejected'  => 'bx-x',
    'Confirmed' => 'bx-check-double',
    'Expired'   => 'bx-calendar-x',
    'Fulfilled' => 'bx-package',
    'Received'  => 'bx-check-circle',
    'Approved'  => 'bx-check-double',
];

// ── Terminal states (no more transitions) ───────────────────────
$terminalStatuses = ['Paid', 'Cancelled', 'Converted', 'Fulfilled', 'Rejected', 'Received'];

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
            ['db' => 'Cancelled', 'label' => 'Cancel Quotation'],
        ],
        'Accepted' => [
            ['db' => 'Converted', 'label' => 'Convert to Invoice',     'target' => 'Invoice'],
            ['db' => 'Converted', 'label' => 'Convert to Sales Order', 'target' => 'SalesOrder'],
            ['db' => 'Pending',   'label' => 'Revert to Open',         'target' => ''],
            ['db' => 'Cancelled', 'label' => 'Cancel Quotation',       'target' => ''],
        ],
        'Converted'=> [],
        'Cancelled'=> [],
    ],
    'salesorder' => [
        'Draft'     => [['db' => 'Confirmed', 'label' => 'Confirm Order']],
        'Confirmed' => [['db' => 'Fulfilled', 'label' => 'Mark Fulfilled'],
                        ['db' => 'Cancelled', 'label' => 'Cancel Order']],
    ],
    'salesreturn'   => [
        'Draft'    => [['db' => 'Approved',  'label' => 'Approve Return']],
        'Approved' => [['db' => 'Cancelled', 'label' => 'Cancel']],
    ],
    'creditnote'    => [
        'Draft'  => [['db' => 'Issued',    'label' => 'Issue Credit Note']],
        'Issued' => [['db' => 'Cancelled', 'label' => 'Cancel']],
    ],
    'purchasereturn'=> [
        'Draft'    => [['db' => 'Approved',  'label' => 'Approve Return']],
        'Approved' => [['db' => 'Cancelled', 'label' => 'Cancel']],
    ],
    'debitnote'     => [
        'Draft'  => [['db' => 'Issued',    'label' => 'Issue Debit Note']],
        'Issued' => [['db' => 'Cancelled', 'label' => 'Cancel']],
    ],
];

$moduleTransitions = $statusTransitions[$moduleContext] ?? [];
