Architecture: Unified Transaction System

Concept:
- Single table stores all transaction types
- No separate QuotationTbl, InvoiceTbl, etc.

Tables:
- TransactionTbl (header)
- TransactionItemsTbl (line items)

Key Field:
- ModuleUID → identifies type:
    1 = Quotation
    2 = Sales Order
    3 = Proforma Invoice
    4 = Invoice
    5 = Purchase
    6 = Purchase Order
    7 = Credit Notes
    8 = Sales Return
    9 = E-Invoices
    10 = Debit Notes
    11 = Delivery Challans

Rules:
- All queries MUST filter by ModuleUID
- No separate tables for each module
- Shared columns used across all transaction types

Future Support:
- Conversion between modules (Quotation → Invoice)
- Status tracking
- Reusable logic