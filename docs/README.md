# Developer Documentation — rishika2kenterprises ERP

## Project Overview

A multi-tenant SaaS ERP built on **CodeIgniter 3 (PHP)**, **Aiven Cloud MySQL**, **Upstash Redis** (remote HTTPS), and **Bootstrap 5**. Each organisation is isolated by `OrgUID`. All financial modules post double-entry accounting journals. All party (customer/vendor) closing balances are auto-recalculated after every transaction.

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | CodeIgniter 3, PHP |
| Database | MySQL (Aiven Cloud) — multiple schemas |
| Cache | Upstash Redis (REST HTTPS) |
| Frontend | Bootstrap 5, jQuery, Chart.js |
| File Storage | AWS S3 / Cloudflare R2 |
| PDF | Dompdf |

## Database Schemas

| Schema | Purpose |
|--------|---------|
| `Customers` | Customer master, addresses, balances, groups |
| `Vendors` | Vendor master, addresses, balances, groups |
| `Products` | Product/SKU master, stock, categories |
| `Transaction` | All financial transactions (invoices, purchases, payments, etc.) |
| `Organisation` | Org settings, bank accounts, branches |
| `Settings` | General settings, prefixes, templates |
| `Accounting` | Double-entry journal, chart of accounts, ledger balances |
| `Users` | User accounts, roles, HRMS data |
| `Modules` | Module registry, role permissions |

## Shared Base — `MY_Controller`

All controllers extend `MY_Controller` (`application/core/MY_Controller.php`), which provides:

- `_orgUID()`, `_userUID()`, `_branchUID()` — JWT accessors
- `_rowLimit()`, `_dateFormat()`, `_currency()`, `_decimals()` — settings accessors
- `_syncProductCacheFromItems()`, `_syncProductCacheByTransUID()` — product cache sync after stock movements
- `_recalcCustomerBalance()`, `_recalcVendorBalance()` — closing balance recalc
- `_writeBankLedgerEntry()` — writes to `AccountLedgerTbl` (bank/cash book)
- `_sendExport()` — CSV / Excel / PDF export
- `_saveAttachments()` — file upload handling
- `_loadPageTitle()`, `_loadUpstashConfig()`, `_requireCache()`

## Module Index

### Transactions
- [Customers](modules/customers.md)
- Vendors *(coming soon)*
- Invoices *(coming soon)*
- Purchases *(coming soon)*
- Payments *(coming soon)*
- Sales Returns *(coming soon)*
- Purchase Returns *(coming soon)*
- Delivery Challans *(coming soon)*
- Quotations *(coming soon)*
- Sales Orders *(coming soon)*
- Purchase Orders *(coming soon)*
- Proforma Invoices *(coming soon)*

### Inventory
- Products *(coming soon)*
- Inventory / Stock Adjustments *(coming soon)*

### HRMS
- Payroll *(coming soon)*
- Salary Advances *(coming soon)*
- Attendance *(coming soon)*
- Departments / Designations *(coming soon)*
- Holidays *(coming soon)*

### Accounting
- Chart of Accounts *(coming soon)*
- General Journal *(coming soon)*
- Trial Balance *(coming soon)*

### Settings
- Organisation *(coming soon)*
- Bank Accounts / Fund Transfers *(coming soon)*
- General Settings *(coming soon)*

### Other
- Reports *(coming soon)*
- Rental *(coming soon)*
- Expenses *(coming soon)*
- Indirect Income *(coming soon)*
