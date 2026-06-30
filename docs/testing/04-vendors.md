# Vendors — Test Cases

**Module:** Vendors  
**Controller:** `Vendors.php`  
**URL:** `/vendors`  
**Pre-condition for all:** Logged in

---

## TC-VND-001 — Vendors page loads

🔴 Critical

**Steps:**
1. Go to `/vendors`

**Expected Result:**
- Page loads without error
- Vendor list visible (or "No records" message)
- Stats cards visible
- Add Vendor button visible

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-002 — Create vendor (minimum required fields)

🔴 Critical

**Steps:**
1. Click Add Vendor
2. Fill in:
   - Name: `TEST VENDOR`
   - Mobile: `9000000002`
3. Click Save

**Expected Result:**
- Success message: "Created Successfully"
- `TEST VENDOR` appears in vendor list
- Balance shows ₹0

**Side Effects to Verify:**
- Vendor appears in the vendor search on Purchase form
- Accounting ledger auto-created (under Sundry Creditors)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-003 — Create vendor with opening balance (Credit)

🔴 Critical

**Steps:**
1. Click Add Vendor
2. Fill in:
   - Name: `TEST VENDOR WITH BALANCE`
   - Mobile: `9000000005`
   - Opening Balance: `3000`
   - Balance Type: `Credit` (we owe the vendor)
3. Click Save

**Expected Result:**
- Vendor created successfully
- Balance shows `₹3,000 Cr` in the list

**Side Effects to Verify:**
- `VendOpeningBalanceTbl.OpeningBalance` = 3000
- `VendOpeningBalanceTbl.PendingBalance` = 3000
- Accounting ledger `CurrentBalance` = 3000

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-004 — Create vendor with missing required fields

🔴 Critical

**Steps:**
1. Click Add Vendor
2. Leave Name empty
3. Click Save

**Expected Result:**
- Validation error shown
- Vendor NOT created
- Form stays open

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-005 — Edit vendor details

🔴 Critical | Pre-condition: TEST VENDOR exists

**Steps:**
1. Find `TEST VENDOR` in list
2. Click Edit
3. Add Email: `vendor@test.com`
4. Add GSTIN: `29ABCDE1234F1Z5`
5. Click Update

**Expected Result:**
- Success message
- Updated details visible in the list and on edit form

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-006 — Edit opening balance — verify closing balance updates

🔴 Critical | Pre-condition: `TEST VENDOR WITH BALANCE` has opening balance ₹3,000

**Steps:**
1. Edit `TEST VENDOR WITH BALANCE`
2. Change Opening Balance from `3000` to `5000`
3. Click Update

**Expected Result:**
- Success message
- Balance in list shows `₹5,000 Cr`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-007 — Toggle vendor status (Deactivate)

🟡 High | Pre-condition: TEST VENDOR is Active

**Steps:**
1. Find `TEST VENDOR` in list
2. Deactivate from action menu

**Expected Result:**
- Status changes to Inactive
- Vendor no longer appears in Purchase vendor search dropdown

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-008 — Reactivate vendor

🟡 High | Pre-condition: TC-VND-007 passed

**Steps:**
1. Find `TEST VENDOR` (Inactive) in list
2. Toggle back to Active

**Expected Result:**
- Status changes to Active
- Vendor reappears in Purchase search dropdown

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-009 — Delete vendor (no transactions)

🔴 Critical | Pre-condition: A vendor with no purchases exists

**Steps:**
1. Create new vendor `TEST DELETE VENDOR`
2. Do NOT create any purchase with it
3. Delete from action menu

**Expected Result:**
- Success message: "Deleted Successfully"
- Vendor removed from list

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-010 — Delete vendor WITH transactions (must fail)

🔴 Critical | Pre-condition: A vendor with at least one purchase exists

**Steps:**
1. Try to delete a vendor who has existing purchases
2. Click Delete in action menu

**Expected Result:**
- Error message: "Vendor has existing transactions..." or similar
- Vendor NOT deleted

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-011 — Vendor search in purchase form

🔴 Critical | Pre-condition: TEST VENDOR exists and is Active

**Steps:**
1. Go to `/purchases/create`
2. Click the Vendor search box
3. Type `TEST VENDOR`

**Expected Result:**
- `TEST VENDOR` appears in dropdown
- Selecting it populates the vendor field

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-VND-012 — Vendor closing balance after purchase (verify side effect)

🔴 Critical | Pre-condition: TEST VENDOR has opening balance ₹0; a purchase of ₹3,500 created (run after TC-PUR tests)

**Steps:**
1. Go to Vendors page
2. Find `TEST VENDOR`
3. Check their balance

**Expected Result:**
- Balance = `₹3,500 Cr` (we owe them)
- After payment of ₹3,500 is recorded → Balance = `₹0`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
