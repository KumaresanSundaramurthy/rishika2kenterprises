# Payments — Test Cases

**Module:** Payments (standalone + invoice/purchase payments)  
**Controller:** `Payments.php`, `Invoices.php`, `Purchases.php`  
**URL:** `/payments`  
**Pre-condition for all:** Logged in + TEST CUSTOMER and TEST VENDOR exist + at least one invoice and one purchase exist

---

## TC-PAY-001 — Payments page loads

🔴 Critical

**Steps:**
1. Go to `/payments`

**Expected Result:**
- Page loads without error
- Payment list visible (or "No records")
- Stats cards visible
- Add Payment button visible

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-002 — Record customer payment (receipt) from Payments page

🔴 Critical | Pre-condition: An Issued invoice for TEST CUSTOMER exists

**Steps:**
1. Go to `/payments`
2. Click Add Payment / Receive Payment
3. Select Party Type: `Customer`
4. Select Customer: `TEST CUSTOMER`
5. Select the Issued invoice from the dropdown
6. Enter Amount: full invoice amount
7. Select Payment Type: `Cash`
8. Click Save

**Expected Result:**
- Payment recorded successfully
- Invoice status changes to `Paid`
- Payment appears in payments list

**Side Effects to Verify:**
- Customer balance decreases by payment amount
- If bank account selected: CR entry in `AccountLedgerTbl`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-003 — Record vendor payment from Payments page

🔴 Critical | Pre-condition: A Received purchase for TEST VENDOR exists

**Steps:**
1. Go to `/payments`
2. Click Add Payment / Pay Vendor
3. Select Party Type: `Vendor`
4. Select Vendor: `TEST VENDOR`
5. Select the purchase from dropdown
6. Enter Amount: full purchase amount
7. Select Payment Type: `Bank Transfer`
8. Select Bank Account
9. Click Save

**Expected Result:**
- Payment recorded successfully
- Purchase status changes to `Paid`
- Payment appears in payments list

**Side Effects to Verify:**
- Vendor balance decreases by payment amount
- DR entry in `AccountLedgerTbl` for the bank account

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-004 — Record payment with zero amount (must fail)

🔴 Critical

**Steps:**
1. Go to Add Payment
2. Select Customer / Vendor and invoice
3. Set Amount: `0`
4. Click Save

**Expected Result:**
- Error: "Amount must be greater than 0"
- Payment NOT created

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-005 — Record payment exceeding outstanding balance (must fail)

🔴 Critical | Pre-condition: An invoice with ₹2,000 outstanding exists

**Steps:**
1. Record payment of `5000` against a ₹2,000 invoice

**Expected Result:**
- Error: "Amount exceeds pending balance"
- Payment NOT created

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-006 — Customer balance correct after payment

🔴 Critical | Pre-condition: TEST CUSTOMER had ₹3,000 outstanding (from TC-INV-003)

**Steps:**
1. Record full payment of ₹3,000 for TEST CUSTOMER's invoice
2. Go to Customers page
3. Check TEST CUSTOMER balance

**Expected Result:**
- Balance = ₹0 (fully settled)
- Invoice shows status `Paid`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-007 — Vendor balance correct after payment

🔴 Critical | Pre-condition: TEST VENDOR had ₹7,000 outstanding (from TC-PUR-003)

**Steps:**
1. Record full payment of ₹7,000 for TEST VENDOR's purchase
2. Go to Vendors page
3. Check TEST VENDOR balance

**Expected Result:**
- Balance = ₹0 (fully settled)
- Purchase shows status `Paid`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-008 — Multiple payments on same invoice

🔴 Critical | Pre-condition: An Issued invoice of ₹5,000 exists

**Steps:**
1. Record first payment: `2000`
2. Verify invoice status = `Partial`, balance = `3000`
3. Record second payment: `3000`
4. Verify invoice status = `Paid`, balance = `0`

**Expected Result:**
- Each payment recorded separately
- Status transitions correctly: Issued → Partial → Paid
- Customer balance reduces with each payment

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-009 — Payment receipt / receipt number generated

🟡 High | Pre-condition: A payment prefix is configured (module 110)

**Steps:**
1. Record a customer payment
2. Check the payment in the list

**Expected Result:**
- Payment has a unique receipt number (e.g. `REC-001`)
- Receipt number format matches configured prefix

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-010 — Bank ledger entry verified after payment

🔴 Critical | Pre-condition: A payment was recorded using a Bank Account (not Cash)

**Steps:**
1. Record a customer payment for ₹2,000 using Bank Account
2. Check the AccountLedgerTbl (or if there is a Bank Statement report — view it)

**Expected Result:**
- A `CR` entry of ₹2,000 exists for the bank account on the payment date
- Narration references the invoice number

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-011 — On-Account balance after invoice cancellation

🔴 Critical | Pre-condition: A fully paid invoice is cancelled (TC-INV-012)

**Steps:**
1. After cancelling a paid invoice
2. Go to Customers page
3. Check if the paid amount shows as On-Account balance

**Expected Result:**
- On-Account balance = previously paid amount
- Customer can use this credit on future invoices

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PAY-012 — Apply on-account payment to a new invoice

🟡 High | Pre-condition: TC-PAY-011 passed (on-account balance exists)

**Steps:**
1. Create a new invoice for the same customer
2. On the invoice, look for "Apply On-Account" option
3. Apply the on-account credit

**Expected Result:**
- Invoice balance reduced by the on-account amount
- On-Account balance cleared

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
