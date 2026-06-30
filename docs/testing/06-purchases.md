# Purchases — Test Cases

**Module:** Purchases  
**Controller:** `Purchases.php`  
**URL:** `/purchases`  
**Pre-condition for all:** Logged in + TEST VENDOR exists + TEST PRODUCT B exists

---

## TC-PUR-001 — Purchases page loads

🔴 Critical

**Steps:**
1. Go to `/purchases`

**Expected Result:**
- Page loads without error
- Purchase list visible (or "No records")
- Stats cards visible
- Create Purchase button visible

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-002 — Create purchase as Draft

🔴 Critical | Pre-condition: TEST VENDOR and TEST PRODUCT B exist

**Steps:**
1. Click Create Purchase
2. Select Vendor: `TEST VENDOR`
3. Select Date: today
4. Add product: `TEST PRODUCT B` → Qty: `10`, Purchase Price: `700`
5. Verify Total = ₹7,000
6. Click **Draft**

**Expected Result:**
- Purchase created with status `Draft`
- No purchase number assigned
- Appears in list with Draft badge

**Side Effects to Verify:**
- Stock NOT increased (draft = no stock movement)
- Vendor balance NOT changed

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-003 — Create purchase — Save (Received)

🔴 Critical | Pre-condition: TEST VENDOR exists, prefix configured

**Steps:**
1. Click Create Purchase
2. Select Vendor: `TEST VENDOR`
3. Select Date: today
4. Select Prefix (e.g. PUR)
5. Add product: `TEST PRODUCT B` → Qty: `10`, Purchase Price: `700`
6. Verify Total = ₹7,000
7. Click **Save**

**Expected Result:**
- Purchase created with status `Received` (or `Approved`)
- Purchase number assigned (e.g. `PUR-001`)
- Appears in list with Received badge

**Side Effects to Verify:**
- Stock increased: TEST PRODUCT B Available Qty increases by 10
- Vendor balance increases by ₹7,000 (we owe them ₹7,000 more)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-004 — Create purchase with multiple products

🔴 Critical | Pre-condition: Multiple products exist

**Steps:**
1. Create Purchase for `TEST VENDOR`
2. Add `TEST PRODUCT A` → Qty: `5`, Price: `700`
3. Add `TEST PRODUCT B` → Qty: `5`, Price: `700`
4. Verify Total = ₹7,000
5. Save

**Expected Result:**
- Purchase created with 2 line items
- Both products' stock increases by 5 each

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-005 — Edit a Draft purchase

🔴 Critical | Pre-condition: TC-PUR-002 passed (Draft exists)

**Steps:**
1. Find the Draft purchase in list
2. Click Edit
3. Change Qty from 10 to 20
4. Click Save

**Expected Result:**
- Purchase updated to Received/Approved status
- Purchase number now assigned
- Stock increased by 20 (not 10)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-006 — Edit a saved (Received) purchase

🔴 Critical | Pre-condition: TC-PUR-003 passed (PUR-001 is Received)

**Steps:**
1. Find `PUR-001` in list
2. Click Edit
3. Change Qty from 10 to 15
4. Click Save

**Expected Result:**
- Purchase updated
- Old stock movement reversed (10 returned) and new applied (15 added)
- Net stock change: +15, not +25

**Side Effects to Verify:**
- Stock: increased by net 15, not 25
- Vendor balance updated to new amount

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-007 — Record full payment on purchase

🔴 Critical | Pre-condition: TC-PUR-003 passed (PUR-001 = ₹7,000 outstanding)

**Steps:**
1. Find `PUR-001` in list
2. Action menu → Record Payment
3. Enter Amount: `7000`
4. Select Payment Type + Bank Account
5. Click Save

**Expected Result:**
- Purchase status changes to `Paid`
- Paid Amount = ₹7,000
- Balance = ₹0

**Side Effects to Verify:**
- Vendor balance reduced by ₹7,000
- `AccountLedgerTbl` has a DR entry for ₹7,000 (money went out)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-008 — Record partial payment on purchase

🔴 Critical | Pre-condition: A new saved purchase of ₹10,000 exists

**Steps:**
1. Find purchase of ₹10,000
2. Record Payment: `4000`
3. Click Save

**Expected Result:**
- Purchase status = `Partial`
- Paid = ₹4,000, Balance = ₹6,000

**Side Effects to Verify:**
- Vendor balance reduced by ₹4,000 only

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-009 — Delete a purchase (Draft)

🟡 High | Pre-condition: TC-PUR-002 passed (Draft exists)

**Steps:**
1. Find the Draft purchase
2. Action menu → Delete

**Expected Result:**
- Purchase deleted from list
- No stock change
- No vendor balance change

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-010 — Cancel a purchase (Received, no payment)

🔴 Critical | Pre-condition: A Received purchase with NO payment exists

**Steps:**
1. Find a Received purchase with no payment
2. Action menu → Cancel

**Expected Result:**
- Purchase status = `Cancelled`
- Stock restored (qty added back is returned to original)
- Vendor balance reduced (we no longer owe this amount)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-011 — Vendor balance after purchase + payment

🔴 Critical | Pre-condition: TEST VENDOR had ₹0; PUR-001 created for ₹7,000 then paid

**Steps:**
1. Go to `/vendors`
2. Find `TEST VENDOR`
3. Check closing balance

**Expected Result:**
- After PUR-001 created: Balance = `₹7,000 Cr`
- After payment recorded: Balance = `₹0`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-012 — Stock increase after purchase

🔴 Critical | Pre-condition: TC-PUR-003 passed (10 units purchased)

**Steps:**
1. Go to `/inventory`
2. Find `TEST PRODUCT B`
3. Check Available Qty

**Expected Result:**
- Available Qty increased by 10 vs before purchase

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PUR-013 — Purchase PDF / Print

🟡 High | Pre-condition: A saved purchase exists

**Steps:**
1. Find a Received purchase
2. Action menu → Print / Download PDF

**Expected Result:**
- PDF opens / downloads
- Contains purchase number, vendor name, items, totals
- Organisation name and details on PDF

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
