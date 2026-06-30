# Invoices — Test Cases

**Module:** Invoices (Sales)  
**Controller:** `Invoices.php`  
**URL:** `/invoices`  
**Pre-condition for all:** Logged in + TEST CUSTOMER exists + TEST PRODUCT B (qty 60) exists

---

## TC-INV-001 — Invoices page loads

🔴 Critical

**Steps:**
1. Go to `/invoices`

**Expected Result:**
- Page loads without error
- Invoice list visible (or "No records")
- Stats cards visible (Total, Paid, Pending, Overdue, etc.)
- Create Invoice button visible

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-002 — Create invoice as Draft

🔴 Critical | Pre-condition: TEST CUSTOMER and TEST PRODUCT B exist

**Steps:**
1. Click Create Invoice
2. Select Customer: `TEST CUSTOMER`
3. Select Date: today
4. Add product: `TEST PRODUCT B` → Qty: `2`
5. Verify Net Total = ₹2,000 (2 × ₹1,000)
6. Click **Draft** (not Save)

**Expected Result:**
- Invoice created with status `Draft`
- Appears in invoice list with Draft badge
- No invoice number assigned (shows "—" or "Draft")

**Side Effects to Verify:**
- Stock NOT deducted (TEST PRODUCT B still shows original qty)
- Customer balance NOT changed

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-003 — Create invoice — Save (Issued)

🔴 Critical | Pre-condition: TEST CUSTOMER and TEST PRODUCT B exist, prefix configured

**Steps:**
1. Click Create Invoice
2. Select Customer: `TEST CUSTOMER`
3. Select Date: today
4. Select Prefix (e.g. INV)
5. Add product: `TEST PRODUCT B` → Qty: `3`
6. Verify Net Total = ₹3,000
7. Click **Save**

**Expected Result:**
- Invoice created with status `Issued`
- Invoice number assigned (e.g. `INV-001`)
- Appears in invoice list with Issued badge

**Side Effects to Verify:**
- Stock reduced: TEST PRODUCT B Available Qty decreases by 3
- Customer balance increases by ₹3,000 (now owes ₹3,000 more)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-004 — Create invoice with multiple products

🔴 Critical | Pre-condition: TEST PRODUCT A (active) and TEST PRODUCT B exist

**Steps:**
1. Create Invoice for `TEST CUSTOMER`
2. Add `TEST PRODUCT A` → Qty: `1` (₹1,200)
3. Add `TEST PRODUCT B` → Qty: `2` (₹2,000)
4. Verify total = ₹3,200
5. Save

**Expected Result:**
- Invoice created with 2 line items
- Total = ₹3,200 (before GST)
- Both products' stock decreases

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-005 — Create invoice with GST

🔴 Critical | Pre-condition: Product with tax configured exists

**Steps:**
1. Create Invoice for `TEST CUSTOMER`
2. Add a product with 18% GST
3. Verify CGST 9% and SGST 9% calculated correctly
4. Verify Net Amount = SubTotal + Tax
5. Save

**Expected Result:**
- CGST and SGST amounts shown separately
- Net Total = base amount + 18% GST
- Tax breakdown visible on invoice preview

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-006 — Edit a Draft invoice

🔴 Critical | Pre-condition: TC-INV-002 passed (draft exists)

**Steps:**
1. Find the Draft invoice in list
2. Click Edit
3. Change Qty from 2 to 5
4. Click Save

**Expected Result:**
- Invoice updated to Issued status
- Invoice number now assigned
- Stock reduced by 5 (not 2)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-007 — Edit an Issued invoice

🔴 Critical | Pre-condition: TC-INV-003 passed (INV-001 is Issued)

**Steps:**
1. Find `INV-001` in list
2. Click Edit
3. Change Qty from 3 to 4
4. Click Save

**Expected Result:**
- Invoice updated
- Old stock movement reversed (3 returned) and new movement applied (4 deducted)
- Net stock change: −4 (not −3−4=−7)

**Side Effects to Verify:**
- Stock deducted: total deducted = 4, not 7
- Customer balance updated to new amount

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-008 — Record full payment on invoice

🔴 Critical | Pre-condition: TC-INV-003 passed (INV-001 = ₹3,000 Issued)

**Steps:**
1. Find `INV-001` in list
2. Click action menu → Record Payment (or payment button)
3. Enter Amount: `3000`
4. Select Payment Type: `Cash` (or Bank)
5. Select Bank Account (if applicable)
6. Click Save

**Expected Result:**
- Invoice status changes to `Paid`
- Payment amount shown on invoice
- Balance remaining = ₹0

**Side Effects to Verify:**
- Customer balance reduced by ₹3,000
- If bank account selected: `AccountLedgerTbl` has a CR entry for ₹3,000

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-009 — Record partial payment on invoice

🔴 Critical | Pre-condition: A new Issued invoice of ₹5,000 exists

**Steps:**
1. Find invoice of ₹5,000
2. Record Payment: Amount = `2000`
3. Click Save

**Expected Result:**
- Invoice status changes to `Partial`
- Paid Amount = ₹2,000
- Balance Remaining = ₹3,000

**Side Effects to Verify:**
- Customer balance reduced by ₹2,000 only

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-010 — Record second payment to fully pay invoice

🔴 Critical | Pre-condition: TC-INV-009 passed (₹3,000 still pending)

**Steps:**
1. Find the Partial invoice
2. Record Payment: Amount = `3000`
3. Click Save

**Expected Result:**
- Invoice status changes to `Paid`
- Total Paid = ₹5,000
- Balance = ₹0
- Customer balance further reduced by ₹3,000

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-011 — Cancel an invoice

🔴 Critical | Pre-condition: An Issued invoice with NO payment exists

**Steps:**
1. Find an Issued invoice with no payments
2. Action menu → Cancel

**Expected Result:**
- Invoice status changes to `Cancelled`
- Edit button disappears
- Invoice still visible in list with Cancelled badge

**Side Effects to Verify:**
- Stock restored: products from this invoice returned to inventory
- Customer balance reduced (they no longer owe this amount)
- Accounting journal reversed

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-012 — Cancel a PAID invoice (should create credit note)

🔴 Critical | Pre-condition: A fully Paid invoice exists

**Steps:**
1. Find a Paid invoice
2. Action menu → Cancel

**Expected Result:**
- Invoice status changes to `Cancelled`
- A credit note is auto-created for the paid amount
- Credit note appears in customer's on-account balance

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-013 — Delete a Draft invoice

🟡 High | Pre-condition: A Draft invoice exists (TC-INV-002)

**Steps:**
1. Find the Draft invoice
2. Action menu → Delete

**Expected Result:**
- Invoice deleted from list
- No stock change (draft had no stock movement)
- No customer balance change

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-014 — Cannot delete a non-Draft invoice directly

🟡 High | Pre-condition: An Issued invoice exists

**Steps:**
1. Find an Issued invoice
2. Try to delete it

**Expected Result:**
- Either Delete option is hidden for Issued status
- OR error message if deletion is attempted

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-015 — Customer balance verification after invoice + payment

🔴 Critical | Pre-condition: TEST CUSTOMER had ₹0 balance; TC-INV-003 + TC-INV-008 completed

**Steps:**
1. Go to `/customers`
2. Find `TEST CUSTOMER`
3. Check closing balance

**Expected Result:**
- After TC-INV-003 (₹3,000 invoice): Balance = `₹3,000 Dr`
- After TC-INV-008 (₹3,000 payment): Balance = `₹0`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-016 — Stock quantity verification after invoice

🔴 Critical | Pre-condition: TEST PRODUCT B had 60 qty; 3 sold via TC-INV-003

**Steps:**
1. Go to `/inventory`
2. Find `TEST PRODUCT B`
3. Check Available Qty

**Expected Result:**
- Available Qty = `57` (60 − 3)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-017 — Stock restored after invoice cancel

🔴 Critical | Pre-condition: TC-INV-011 passed

**Steps:**
1. Go to `/inventory`
2. Check Available Qty for products on the cancelled invoice

**Expected Result:**
- Qty restored to what it was before the invoice

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-INV-018 — Invoice PDF / Print

🟡 High | Pre-condition: A saved invoice exists

**Steps:**
1. Find an Issued invoice
2. Action menu → Print / Download PDF

**Expected Result:**
- PDF opens / downloads
- Contains invoice number, customer name, items, totals, GST breakdown
- Organisation name and details on the PDF

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
