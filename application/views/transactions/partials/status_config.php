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
    'Returned'           => 'trans-badge-Fulfilled',
    'Partially Returned' => 'trans-badge-Partial',
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
    'Returned'           => 'bx-undo',
    'Partially Returned' => 'bx-adjust',
];

// ── Terminal states (no more transitions) ───────────────────────
$terminalStatuses = ['Paid', 'Cancelled', 'Completed', 'Converted', 'Fulfilled', 'Rejected', 'Received', 'Delivered', 'Returned'];

// ── Translated display labels (DB key → UI string) ──────────────
$statusLabels = [
    'Draft'              => t('status_draft',              'Draft'),
    'Dispatched'         => t('status_dispatched',         'Dispatched'),
    'Delivered'          => t('status_delivered',          'Delivered'),
    'Issued'             => t('status_issued',             'Issued'),
    'Sent'               => t('status_sent',               'Sent'),
    'Paid'               => t('status_paid',               'Paid'),
    'Partial'            => t('status_partial',            'Partial'),
    'Overdue'            => t('status_overdue',            'Overdue'),
    'Cancelled'          => t('status_cancelled',          'Cancelled'),
    'Completed'          => t('status_completed',          'Completed'),
    'Converted'          => t('status_converted',          'Converted'),
    'Pending'            => t('status_pending',            'Pending'),
    'Accepted'           => t('status_accepted',           'Accepted'),
    'Rejected'           => t('status_rejected',           'Rejected'),
    'Confirmed'          => t('status_confirmed',          'Confirmed'),
    'Expired'            => t('status_expired',            'Expired'),
    'Fulfilled'          => t('status_fulfilled',          'Fulfilled'),
    'Received'           => t('status_received',           'Received'),
    'Approved'           => t('status_approved',           'Approved'),
    'Returned'           => t('status_returned',           'Returned'),
    'Partially Returned' => t('status_partially_returned', 'Partially Returned'),
];

// ── Status transitions per module ───────────────────────────────
$statusTransitions = [
    'invoice' => [
        'Draft'  => [['db' => 'Issued',    'label' => t('trans_issue_invoice',  'Issue Invoice')]],
        'Issued' => [['db' => 'Paid',      'label' => t('trans_mark_paid',      'Mark as Paid')],
                     ['db' => 'Partial',   'label' => t('trans_mark_partial',   'Mark as Partial')],
                     ['db' => 'Cancelled', 'label' => t('trans_cancel_invoice', 'Cancel Invoice')]],
        'Partial'=> [['db' => 'Paid',      'label' => t('trans_mark_paid',      'Mark as Paid')],
                     ['db' => 'Cancelled', 'label' => t('trans_cancel_invoice', 'Cancel Invoice')]],
    ],
    'purchase' => [
        'Draft'    => [['db' => 'Received',  'label' => t('trans_mark_received',   'Mark as Received')]],
        'Received' => [['db' => 'Paid',      'label' => t('trans_mark_paid',       'Mark as Paid')],
                       ['db' => 'Partial',   'label' => t('trans_mark_partial',    'Mark as Partial')],
                       ['db' => 'Cancelled', 'label' => t('trans_cancel_purchase', 'Cancel Purchase')]],
        'Partial'  => [['db' => 'Paid',      'label' => t('trans_mark_paid',       'Mark as Paid')]],
    ],
    'quotation' => [
        'Draft'    => [
            ['db' => 'Pending',   'label' => t('trans_send_quotation',     'Send Quotation')],
        ],
        'Pending'  => [
            ['db' => 'Accepted',  'label' => t('trans_mark_accepted',      'Mark as Accepted')],
        ],
        'Accepted' => [
            ['db' => 'Converted', 'label' => t('trans_convert_to_invoice', 'Convert to Invoice'),     'target' => 'Invoice'],
            ['db' => 'Converted', 'label' => t('trans_convert_to_so',      'Convert to Sales Order'), 'target' => 'SalesOrder'],
            ['db' => 'Pending',   'label' => t('trans_revert_to_open',     'Revert to Open'),         'target' => ''],
        ],
        'Converted'=> [],
        'Cancelled'=> [],
    ],
    'salesorder'       => [],
    'deliverychallan'  => [],
    'proformainvoice'  => [
        'Draft'   => [['db' => 'Sent',      'label' => t('trans_send_proforma',        'Send Pro Forma')]],
        'Sent'    => [
            ['db' => 'Converted', 'label' => t('trans_convert_to_invoice', 'Convert to Invoice')],
            ['db' => 'Expired',   'label' => t('trans_mark_expired',       'Mark as Expired')],
            ['db' => 'Cancelled', 'label' => t('cancel',                   'Cancel')],
        ],
        'Expired' => [['db' => 'Sent', 'label' => t('trans_reactivate', 'Reactivate')]],
    ],
    'salesreturn'   => [
        'Draft'    => [['db' => 'Approved',  'label' => t('trans_approve_return', 'Approve Return')]],
        'Approved' => [['db' => 'Cancelled', 'label' => t('cancel',               'Cancel')]],
    ],
    'purchasereturn'=> [
        'Draft'    => [['db' => 'Approved',  'label' => t('trans_approve_return', 'Approve Return')]],
        'Approved' => [['db' => 'Cancelled', 'label' => t('cancel',               'Cancel')]],
    ],
    'expense' => [
        'Pending' => [
            ['db' => 'Paid',      'label' => t('trans_mark_paid',             'Mark as Paid')],
            ['db' => 'Partial',   'label' => t('trans_record_partial_payment','Record Partial Payment')],
            ['db' => 'Cancelled', 'label' => t('trans_cancel_expense',        'Cancel Expense')],
        ],
        'Partial' => [
            ['db' => 'Paid',      'label' => t('trans_mark_paid',      'Mark as Paid')],
            ['db' => 'Cancelled', 'label' => t('trans_cancel_expense', 'Cancel Expense')],
        ],
    ],
    'indirectincome' => [
        'Pending' => [
            ['db' => 'Received',  'label' => t('trans_mark_received',          'Mark as Received')],
            ['db' => 'Partial',   'label' => t('trans_record_partial_receipt', 'Record Partial Receipt')],
            ['db' => 'Cancelled', 'label' => t('trans_cancel_income',          'Cancel Income')],
        ],
        'Partial' => [
            ['db' => 'Received',  'label' => t('trans_mark_received', 'Mark as Received')],
            ['db' => 'Cancelled', 'label' => t('trans_cancel_income', 'Cancel Income')],
        ],
    ],
];

$moduleTransitions = $statusTransitions[$moduleContext] ?? [];
