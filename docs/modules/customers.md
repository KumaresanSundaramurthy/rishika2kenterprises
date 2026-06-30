# Customers Module

## Overview

The Customers module manages the complete lifecycle of customer records in the ERP. It handles customer creation, editing, deletion, grouping, balance tracking (opening + closing), communication (SMS/Email), and export. Every customer gets a corresponding ledger account in the Accounting database automatically on creation. Customer data is also mirrored into an Upstash Redis cache (hash map) so that transaction forms (Invoices, Sales Orders, etc.) can search and load customer info without hitting the database.

**Controller:** `application/controllers/Customers.php`  
**Model:** `application/models/Customers_model.php`  
**Views:** `application/views/customers/`  
**Database:** `Customers` schema

---

## Key Concepts

- **OrgUID isolation** — every query is scoped to the current org. A customer from Org A can never be seen by Org B.
- **CustToken** — a UUID4 generated on create. Used for public share links. Immutable after creation.
- **Closing Balance** — not stored in `CustomerTbl`. It is computed as:  
  `ClosingBalance = OpeningBalance + TotalInvoiced − TotalReceived − EffectiveSalesReturns − PendingCreditNotes + PendingDebitNotes`  
  Stored in `CustOpeningBalanceTbl.PendingBalance` and synced to `Accounting.ChartOfAccounts.CurrentBalance` and Upstash on every transaction.
- **Opening Balance** — the amount the customer owed (or we owed them) at the time they were added to the system. Stored in `CustOpeningBalanceTbl` (one row per customer, year-agnostic) and `CustYearOpeningBalanceTbl` (one row per financial year).
- **On Account Balance** — unapplied credit sitting against a customer. Created when a paid invoice is cancelled — the payments become on-account funds that can be applied to future invoices.
- **Customer Group** — a logical grouping of multiple customers (e.g., franchise chain, business conglomerate). A group has its own contact details, address, and aggregated outstanding view.
- **Financial Year** — April to March (India standard). `_currentFinancialYear()` returns the year the current April belongs to (e.g., April 2025–March 2026 → FY 2025).
- **Upstash cache key** — `org:{OrgUID}:customers` (HSET, one field per CustomerUID). Stale on edit/delete/status change.

---

## Database Tables

| Table | DB | Purpose |
|-------|----|---------|
| `CustomerTbl` | Customers | Master customer record |
| `CustAddressTbl` | Customers | Billing and Shipping addresses (one row each per customer) |
| `CustBankDetailsTbl` | Customers | Customer bank account details (for payment reference) |
| `CustOpeningBalanceTbl` | Customers | Opening balance + computed closing (PendingBalance). One row per customer. |
| `CustYearOpeningBalanceTbl` | Customers | Opening balance snapshot per financial year. Used for year-wise P&L reports. |
| `CustomerGroupTbl` | Customers | Customer group master |
| `CustomerGroupMembersTbl` | Customers | Many-to-many: group ↔ customers |
| `CustomerTypeTbl` | Customers | Lookup — e.g. Retail, Wholesale, Distributor |
| `ChartOfAccounts` | Accounting | Customer ledger account (auto-created on add) |
| `EntityLedgerMap` | Accounting | Links CustomerUID → LedgerUID |

### Key columns — `CustomerTbl`

| Column | Type | Notes |
|--------|------|-------|
| `CustomerUID` | int PK | Auto-increment |
| `CustToken` | varchar(36) | UUID4, immutable, for public links |
| `OrgUID` | int | Multi-tenant isolation |
| `Name` | varchar | Customer display name |
| `CompanyName` | varchar | Optional company/firm name |
| `MobileNumber` | varchar | With `CountryCode` prefix |
| `GSTIN` | varchar | GST registration number |
| `CustomerTypeUID` | tinyint FK | → `CustomerTypeTbl` |
| `GroupUID` | int FK | → `CustomerGroupTbl` (nullable) |
| `DiscountPercent` | decimal | Default discount % applied on invoices |
| `CreditPeriod` | int | Payment due days (default 30) |
| `CreditLimit` | decimal | Max outstanding allowed (0 = no limit) |
| `DebitCreditAmount` | decimal | Adjustment delta field — do NOT use as closing balance |
| `DebitCreditType` | enum | 'Debit' or 'Credit' |
| `Tags` | varchar | Comma-separated free-text tags |
| `IsActive` | bit | Inactive customers excluded from search cache |
| `IsDeleted` | bit | Soft delete — never physically removed |

---

## API Endpoints

All AJAX endpoints return `{ Error: bool, Message: string, ... }`.

### Page Routes

| Method | URL | Controller Method | Purpose |
|--------|-----|------------------|---------|
| GET | `/customers` | `index()` | Customer list page |
| GET | `/customers/{uid}/edit` | `edit()` (inherited) | Edit customer page |
| GET | `/customers/{uid}/clone` | `clonecustomer()` (inherited) | Clone customer page |
| GET | `/customers/modal/{type}` | `loadModalForm($type)` | Load add/edit/clone modal HTML |
| GET | `/customers/modal/{type}/{uid}` | `loadModalForm($type, $uid)` | Load modal with existing data |

### Customer CRUD

| Method | URL | Controller Method | Purpose |
|--------|-----|------------------|---------|
| POST | `/customers/addCustomerData` | `addCustomerData()` | Create a new customer |
| POST | `/customers/updateCustomerData` | `updateCustomerData()` | Update existing customer |
| POST | `/customers/deleteCustomerData` | `deleteCustomerData()` | Soft delete a single customer |
| POST | `/customers/deleteBulkCustomers` | `deleteBulkCustomers()` | Soft delete multiple customers |
| POST | `/customers/toggleCustomerStatus` | `toggleCustomerStatus()` | Activate / deactivate customer |
| GET | `/customers/getCustomersPageDetails/{pageNo}` | `getCustomersPageDetails()` | Paginated list refresh (AJAX) |
| GET | `/customers/searchCustomers?term=` | `searchCustomers()` | Typeahead search for Select2 dropdowns |
| POST | `/customers/getCustomerSearchList` | `getCustomerSearchList()` | Paginated search with balance info |
| GET | `/customers/getCustomerForModal/{uid}` | `getCustomerForModal()` | Fetch single customer for view modal (cache-aside) |
| POST | `/customers/getCustomerTags` | `getCustomerTags()` | All tags for this org |
| POST | `/customers/getCustomerTypesList` | `getCustomerTypesList()` | All customer types for this org |
| POST | `/customers/getStats` | `getStats()` | Dashboard stats (total, active, to-collect, to-pay) |

### Balance Management

| Method | URL | Controller Method | Purpose |
|--------|-----|------------------|---------|
| POST | `/customers/saveCustomerOpeningBalance` | `saveCustomerOpeningBalance()` | Set / update opening balance for a customer + financial year |
| GET/POST | `/customers/getCustomerOpeningBalance` | `getCustomerOpeningBalance()` | Fetch current + year opening balance |
| POST | `/customers/updateCustomerBalance` | `updateCustomerBalance()` | Force-recalculate closing balance for one or all customers |
| GET/POST | `/customers/getCustomerBalance` | `getCustomerBalance()` | Fetch live closing balance with full breakdown |
| POST | `/customers/getCustomerOnAccountBalance` | `getCustomerOnAccountBalance()` | Get unapplied on-account payment total + records |
| POST | `/customers/applyOnAccountPayment` | `applyOnAccountPayment()` | Apply an on-account payment to a specific invoice |

### Export & Communication

| Method | URL | Controller Method | Purpose |
|--------|-----|------------------|---------|
| GET | `/customers/exportCustomers?Type=CSV&Filter=` | `exportCustomers()` | Export to CSV / Excel / PDF |
| POST | `/customers/sendCommunication` | `sendCommunication()` | Send bulk SMS or Email to selected customers |

### Cache

| Method | URL | Controller Method | Purpose |
|--------|-----|------------------|---------|
| POST | `/customers/syncCustomersCache` | `syncCustomersCache()` | Full rebuild of Upstash customer hash for this org |

### Customer Groups

| Method | URL | Controller Method | Purpose |
|--------|-----|------------------|---------|
| GET | `/customers/addGroup` | `addGroup()` | Group create form page |
| POST | `/customers/addGroupData` | `addGroupData()` | Save new group + assign members |
| GET | `/customers/groupEdit/{groupUID}` | `groupEdit()` | Group edit form page |
| POST | `/customers/updateGroupData` | `updateGroupData()` | Update group + sync member list |
| POST | `/customers/deleteGroup` | `deleteGroup()` | Soft delete group (unlinks all members first) |
| POST | `/customers/toggleGroupStatus` | `toggleGroupStatus()` | Activate / deactivate group |
| GET | `/customers/groupDetail/{groupUID}` | `groupDetail()` | Group detail view page |
| POST | `/customers/getGroupsData/{pageNo}` | `getGroupsData()` | Paginated group list (AJAX) |
| GET | `/customers/getGroupForModal/{groupUID}` | `getGroupForModal()` | Fetch group + members for modal |
| GET | `/customers/getGroupOutstanding/{groupUID}` | `getGroupOutstanding()` | Aggregated outstanding for all members |
| POST | `/customers/getGroupsForDropdown` | `getGroupsForDropdown()` | Active groups for dropdown on customer form |
| POST | `/customers/getGroupTypes` | `getGroupTypes()` | Lookup list — e.g. Business Group, Family, etc. |

---

## Business Rules

### Customer Create (`addCustomerData`)
1. Form is validated via `formvalidation_model->custValidateForm()` before any DB write.
2. Customer is inserted into `Customers.CustomerTbl`.
3. Profile image (if uploaded) is saved to cloud storage and the path written back to `CustomerTbl.Image`.
4. Bank details (JSON array) are saved to `CustBankDetailsTbl` via `globalservice->saveBankDetails()`.
5. Billing and Shipping addresses are saved to `CustAddressTbl` (one row per type).
6. A ledger account is auto-created in `Accounting.ChartOfAccounts` under the "Sundry Debtors" parent via `accountledger->createLedgerAccountingInfo()`. The ledger is linked in `Accounting.EntityLedgerMap`.
7. If `OpeningBalance > 0`, a row is inserted into `CustOpeningBalanceTbl` and `CustYearOpeningBalanceTbl` for the current financial year.
8. All of the above (steps 2–7) run inside a single DB transaction — if any step fails, everything rolls back.
9. After commit, `cachehelper->upsertCustomer()` syncs the new customer into the Upstash hash.

### Customer Edit (`updateCustomerData`)
1. **Opening balance delta** — the old `DebitCreditAmount` is read from DB before the update. The delta (`new − old`) is applied to `CustOpeningBalanceTbl.OpeningBalance` and the year snapshot. The closing balance is then recalculated via `customerbalance->recalcAndSync()`.
2. **Ledger sync** — if the customer name or balance changed, `accountledger->updateEntityLedgerInfo()` updates the ledger name and opening balance in `Accounting.ChartOfAccounts`.
3. Bank details: existing records are soft-deleted by UID if `delBankDataFlag = 1`, then new records are inserted.
4. Addresses: same soft-delete + re-insert pattern.
5. After commit, `cachehelper->upsertCustomer()` refreshes the Upstash entry.

### Customer Delete (`deleteCustomerData` / `deleteBulkCustomers`)
- **Pre-condition:** Customer must have zero transactions (no Invoices, Payments, or Sales Orders). Checked via `customerHasTransactions()`.
- Sets `IsDeleted = 1`, `IsActive = 0` in `CustomerTbl` (soft delete — row is never physically removed).
- Deactivates the corresponding accounting ledger via `accountledger->deactivateEntityLedger()`.
- Removes customer from Upstash cache via `cachehelper->removeCustomer()`.
- Bulk delete wraps all deletes in a single transaction; if any customer has transactions the whole batch is rejected.

### Toggle Status (`toggleCustomerStatus`)
- Active (1) → adds/refreshes customer in Upstash cache.
- Inactive (0) → removes customer from Upstash cache so they don't appear in transaction search dropdowns.

### Closing Balance Recalculation (`updateCustomerBalance` / `customerbalance->recalcAndSync`)
Formula:
```
SignedBalance = signedOpening
              + TotalInvoiced       (ModuleUID 103, non-Draft, non-Cancelled)
              − TotalReceived       (PaymentsTbl, Direction=In, not transferred to credit note, not cancelled)
              − EffectiveReturned   (Sales Returns, minus those already covered by a pending credit note)
              − PendingCreditNotes  (TransCreditNoteTbl, Status=Pending)
              + PendingDebitNotes   (TransDebitNoteTbl, Status=Pending)

ClosingBalance = abs(SignedBalance)
ClosingBalType = SignedBalance >= 0 ? 'Debit' : 'Credit'
```
Result is written to:
- `CustOpeningBalanceTbl.PendingBalance` + `PendingBalType`
- `Accounting.ChartOfAccounts.CurrentBalance` + `CurrentBalanceType` (via ledger UID)
- Upstash cache (via `cachehelper->upsertCustomer()`)

### On-Account Payments (`applyOnAccountPayment`)
- On-account funds are rows in `PaymentsTbl` where `Source = 'OnAccount'` or `IsTransferredToCreditNote = 1` — unapplied credits.
- `applyOnAccountPayment()` links the payment to a target invoice, updates the invoice's paid/balance amounts, and triggers `customerbalance->recalcAndSync()`.

### Opening Balance (`saveCustomerOpeningBalance`)
- Can be set any time — not locked after first entry.
- Saves to `CustOpeningBalanceTbl` (upsert — one row per customer).
- Also saves a year snapshot to `CustYearOpeningBalanceTbl` (upsert per customer per year).
- After save, triggers `cachehelper->upsertCustomer()`.

### Communication (`sendCommunication`)
- `CommType`: `SMS` or `Email`.
- Recipients: array of `CustomerUIDs` — mobile numbers and emails are looked up internally.
- Email supports up to 3 file attachments (PDF, JPG, PNG — max 3 MB each). Temp files are written to `uploads/comm_tmp/` and deleted immediately after send.
- Delegates to `communicationservice->sendSMS()` / `sendEmail()`.

### Export (`exportCustomers`)
- Accepts `Type`: `CSV`, `Excel`, or `PDF` via GET.
- Accepts `Filter` as a JSON-encoded string via GET.
- Fetches all matching customers (no pagination limit when exporting).
- Delegates to `MY_Controller->_sendExport()`.

### Cache (`syncCustomersCache`)
- Full rebuild — deletes the existing Upstash hash key and rewrites all customers from DB.
- Used as a recovery tool when the cache gets out of sync.
- Normal updates use `upsertCustomer()` (single-entry update) instead.

### Customer Groups
- A group can have multiple members. One member can be marked as `Primary`.
- `syncGroupMembers()` does a diff: it links new members and unlinks removed ones without touching members that haven't changed.
- Deleting a group first calls `unlinkAllGroupMembers()` to clean the membership table, then soft-deletes the group.
- `getGroupOutstanding()` aggregates `PendingBalance` across all active members for a combined outstanding view.

---

## Connected Modules

| Module | How it connects |
|--------|----------------|
| **Invoices** | Customer is the party on every invoice. Invoice save triggers `_recalcCustomerBalance()`. |
| **Payments** | Customer payments (In) reduce the closing balance. |
| **Sales Returns** | Increase closing balance or generate a credit note. |
| **Accounting** | Auto-creates / updates a `ChartOfAccounts` ledger entry. `EntityLedgerMap` links the two. |
| **Upstash Cache** | Every create/edit/delete/status-change pushes to the customer hash for fast transaction-form lookup. |
| **Communication Service** | `sendCommunication()` sends SMS/Email to selected customers. |
| **Reports / Export** | `exportCustomers()` feeds CSV/Excel/PDF download. |

---

## Caching Strategy

| Cache key | Type | Content | Invalidated by |
|-----------|------|---------|---------------|
| `org:{OrgUID}:customers` | HSET | One field per CustomerUID. Contains name, mobile, balance, addresses, on-account records. | `upsertCustomer()` on create/edit/balance-change; `removeCustomer()` on delete/deactivate |
| `customer:{CustomerUID}` | STRING (TTL) | Full customer detail + bank details + addresses for the view modal. | Invalidated by edit — next modal load fetches fresh and re-sets. |

---

## Validation Rules

Applied by `formvalidation_model->custValidateForm()` before any DB write:

| Field | Rule |
|-------|------|
| `Name` | Required |
| `MobileNumber` | Required; numeric; validated with country code |
| `GSTIN` | Optional; if provided, must match standard GSTIN format |
| `EmailAddress` | Optional; if provided, must be valid email |
| `CreditPeriod` | Integer ≥ 0 |
| `CreditLimit` | Decimal ≥ 0 |
| `DiscountPercent` | Decimal 0–100 |

---

## Error Handling

- All AJAX methods wrap logic in `try/catch`. On exception: `Error = true`, `Message = exception message`.
- `addCustomerData` and `updateCustomerData` use `InvalidArgumentException` for validation errors (returns field-level error HTML) vs `Exception` for system errors (returns plain message string).
- DB writes are always wrapped in `startTransaction()` / `commitTransaction()` / `rollbackTransaction()`.
- Post-commit side effects (cache sync, ledger update, balance recalc) are non-fatal — they log errors but do not flip `Error = true`.
