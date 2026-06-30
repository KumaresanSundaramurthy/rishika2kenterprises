# Testing Progress Tracker

## Critical Phase Status

| File | Module | Total TCs | Pass | Fail | Blocked | Done? |
|------|--------|-----------|------|------|---------|-------|
| 01-login.md | Login | 6 | | | | [ ] |
| 02-products.md | Products | 12 | | | | [ ] |
| 03-customers.md | Customers | 15 | | | | [ ] |
| 04-vendors.md | Vendors | 12 | | | | [ ] |
| 05-invoices.md | Invoices | 18 | | | | [ ] |
| 06-purchases.md | Purchases | 13 | | | | [ ] |
| 07-payments.md | Payments | 12 | | | | [ ] |
| **TOTAL** | | **88** | | | | |

## How to Update

After testing each file, fill in Pass / Fail / Blocked counts and check Done.

Example: `| 01-login.md | Login | 6 | 5 | 1 | 0 | ✅ |`

## Launch Readiness Checklist

- [ ] All Critical (🔴) test cases = Pass
- [ ] All Blocker bugs = Fixed
- [ ] Customer balance correct after invoice + payment
- [ ] Vendor balance correct after purchase + payment  
- [ ] Stock quantity correct after invoice, purchase, and adjustments
- [ ] Login / logout works
- [ ] No console errors on main pages
