# How to Use This Test Guide

## Test Case Format

Each test case follows this structure:

| Field | Meaning |
|-------|---------|
| **ID** | Unique ID — e.g. `TC-INV-003` |
| **Priority** | 🔴 Critical / 🟡 High / 🟢 Medium |
| **Pre-condition** | What must already exist before running this test |
| **Steps** | Exactly what to do, in order |
| **Expected Result** | What should happen if the feature works correctly |
| **Side Effects** | Other things that should change (balance, stock, journal) |
| **Status** | `[ ] Pass` `[ ] Fail` `[ ] Blocked` |
| **Bug** | Short note if it fails — describe what actually happened |

---

## Test Execution Order

**Run in this exact order** — each phase depends on the previous one.

```
Phase 1 → Login                   (01-login.md)
Phase 2 → Products                (02-products.md)
Phase 3 → Customers               (03-customers.md)
Phase 4 → Vendors                 (04-vendors.md)
Phase 5 → Invoices                (05-invoices.md)
Phase 6 → Purchases               (06-purchases.md)
Phase 7 → Payments                (07-payments.md)
```

---

## Reference Test Data

Use this data consistently across all tests so side effects are predictable.

### Test Product
| Field | Value |
|-------|-------|
| Item Name | `TEST PRODUCT A` |
| Type | Product |
| Selling Price | ₹1,000 |
| Purchase Price | ₹700 |
| Opening Quantity | 50 |
| HSN Code | `9999` |

### Test Customer
| Field | Value |
|-------|-------|
| Name | `TEST CUSTOMER` |
| Mobile | `9000000001` |
| Opening Balance | ₹5,000 (Debit — customer owes us) |

### Test Vendor
| Field | Value |
|-------|-------|
| Name | `TEST VENDOR` |
| Mobile | `9000000002` |
| Opening Balance | ₹3,000 (Credit — we owe vendor) |

---

## How to Mark Results

- **Pass** → Feature works exactly as described in Expected Result
- **Fail** → Something went wrong — note what actually happened in the Bug field
- **Blocked** → Cannot test because a dependency (earlier test) failed

---

## Bug Reporting

When a test fails, note:
1. **Test ID** — e.g. `TC-INV-005`
2. **What you did** — which step broke
3. **What happened** — actual result
4. **Screenshot** — if possible

Share with developer in chat: *"TC-INV-005 fails — after recording payment, customer balance did not update."*
