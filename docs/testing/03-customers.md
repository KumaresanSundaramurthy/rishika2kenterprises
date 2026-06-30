# Customers — Test Cases

**Module:** Customers  
**Controller:** `Customers.php`  
**URL:** `/customers`  
**Pre-condition for all:** Logged in

---

## TC-CST-001 — Customers page loads

🔴 Critical

**Steps:**
1. Go to `/customers`

**Expected Result:**
- Page loads without error
- Customer list visible (or "No records" message)
- Stats cards visible (Total, To Collect, To Pay, etc.)
- Add Customer button visible

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-002 — Create customer (minimum required fields)

🔴 Critical

**Steps:**
1. Click Add Customer
2. Fill in:
   - Name: `TEST CUSTOMER`
   - Mobile: `9000000001`
3. Click Save

**Expected Result:**
- Success message: "Created Successfully"
- `TEST CUSTOMER` appears in the customer list
- Balance shows ₹0

**Side Effects to Verify:**
- Customer appears in the customer search on Invoice form
- A ledger account auto-created in Accounting (can verify via Chart of Accounts page)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-003 — Create customer with opening balance (Debit)

🔴 Critical

**Steps:**
1. Click Add Customer
2. Fill in:
   - Name: `TEST CUSTOMER WITH BALANCE`
   - Mobile: `9000000003`
   - Opening Balance: `5000`
   - Balance Type: `Debit` (customer owes us)
3. Click Save

**Expected Result:**
- Customer created successfully
- Balance shows `₹5,000 Dr` in the list
- Opening balance reflected in customer's account

**Side Effects to Verify:**
- `CustOpeningBalanceTbl.OpeningBalance` = 5000
- `CustOpeningBalanceTbl.PendingBalance` = 5000
- Accounting ledger `CurrentBalance` = 5000

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-004 — Create customer with billing and shipping address

🟡 High

**Steps:**
1. Click Add Customer
2. Fill in Name: `TEST CUSTOMER ADDR`, Mobile: `9000000004`
3. Fill in Billing Address:
   - Line 1: `123 Test Street`
   - City: `Chennai`
   - State: `Tamil Nadu`
   - Pincode: `600001`
4. Fill in Shipping Address (same or different)
5. Click Save

**Expected Result:**
- Customer created successfully
- On editing the customer, billing and shipping addresses are pre-filled correctly
- Address appears on invoice when this customer is selected

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-005 — Create customer with missing required fields

🔴 Critical

**Steps:**
1. Click Add Customer
2. Leave Name empty
3. Click Save

**Expected Result:**
- Validation error shown: "Name is required" or similar
- Customer NOT created
- Form stays open with error highlighted

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-006 — Edit customer details

🔴 Critical | Pre-condition: TEST CUSTOMER exists

**Steps:**
1. Find `TEST CUSTOMER` in list
2. Click Edit
3. Change Mobile to `9000000099`
4. Add Email: `test@customer.com`
5. Click Update

**Expected Result:**
- Success message: "Updated Successfully"
- Updated details visible in the list
- Upstash cache updated (verify by opening customer in invoice form — shows new details)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-007 — Edit opening balance — verify closing balance updates

🔴 Critical | Pre-condition: `TEST CUSTOMER WITH BALANCE` has opening balance of ₹5,000

**Steps:**
1. Edit `TEST CUSTOMER WITH BALANCE`
2. Change Opening Balance from `5000` to `8000`
3. Click Update

**Expected Result:**
- Success message
- Balance in list shows `₹8,000 Dr`

**Side Effects to Verify:**
- `CustOpeningBalanceTbl.OpeningBalance` updated to 8000
- Closing balance recalculated and synced

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-008 — Toggle customer status (Deactivate)

🟡 High | Pre-condition: TEST CUSTOMER is Active

**Steps:**
1. Find `TEST CUSTOMER` in list
2. Click toggle / action menu → Deactivate

**Expected Result:**
- Status changes to Inactive
- Customer no longer appears in Invoice customer search dropdown
- Still visible in list with Inactive badge

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-009 — Reactivate customer

🟡 High | Pre-condition: TC-CST-008 passed

**Steps:**
1. Find `TEST CUSTOMER` (Inactive) in list
2. Toggle back to Active

**Expected Result:**
- Status changes to Active
- Customer reappears in Invoice search dropdown

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-010 — Delete customer (no transactions)

🔴 Critical | Pre-condition: A customer with no invoices/payments exists

**Steps:**
1. Create new customer `TEST DELETE CUSTOMER`
2. Do NOT create any invoice with it
3. Delete from action menu

**Expected Result:**
- Success message: "Deleted Successfully"
- Customer removed from list
- Customer does NOT appear in Invoice search

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-011 — Delete customer WITH transactions (must fail)

🔴 Critical | Pre-condition: A customer with at least one invoice exists

**Steps:**
1. Try to delete a customer who has existing invoices
2. Click Delete in action menu

**Expected Result:**
- Error message: "Customer has existing transactions..."
- Customer NOT deleted
- Customer still visible in list

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-012 — Customer search in invoice form

🔴 Critical | Pre-condition: TEST CUSTOMER exists and is Active

**Steps:**
1. Go to `/invoices/create`
2. Click the Customer search box
3. Type `TEST CUSTOMER`

**Expected Result:**
- `TEST CUSTOMER` appears in dropdown
- Selecting it populates the customer field
- Customer's billing address (if set) auto-fills dispatch/billing on invoice

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-013 — Customer closing balance after invoice (verify side effect)

🔴 Critical | Pre-condition: TEST CUSTOMER has opening balance ₹0; Invoice of ₹2,000 created for them (run after TC-INV tests)

**Steps:**
1. Go to Customers page
2. Find `TEST CUSTOMER`
3. Check their balance

**Expected Result:**
- Balance = `₹2,000 Dr` (they owe us the invoice amount)
- After payment of ₹2,000 is recorded → Balance = `₹0`

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-014 — Export customers to CSV

🟡 High | Pre-condition: At least one customer exists

**Steps:**
1. On Customers page click Export → CSV

**Expected Result:**
- CSV file downloaded
- Contains columns: Name, Mobile, Email, Balance, Status, etc.
- Data matches what is in the list

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-CST-015 — Sync customer cache

🟡 High | Pre-condition: Customers exist

**Steps:**
1. On Customers page, find the Sync Cache button (admin action)
2. Click Sync

**Expected Result:**
- Success message: "X customer(s) synced to cache"
- Count matches the number of active customers

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
