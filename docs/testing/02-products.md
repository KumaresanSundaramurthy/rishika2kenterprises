# Products — Test Cases

**Module:** Products  
**Controller:** `Products.php`  
**URL:** `/products`  
**Pre-condition for all:** Logged in

---

## TC-PRD-001 — Products page loads

🔴 Critical

**Steps:**
1. Go to `/products`

**Expected Result:**
- Page loads without error
- Product list visible (or "No records" if empty)
- Stats cards visible (Total Products, Low Stock, etc.)
- Add Product button visible

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-002 — Create a product (minimum required fields)

🔴 Critical

**Steps:**
1. Go to `/products`
2. Click Add Product
3. Fill in:
   - Item Name: `TEST PRODUCT A`
   - Type: `Product`
   - Selling Price: `1000`
   - Purchase Price: `700`
   - Tax: select applicable tax
4. Click Save

**Expected Result:**
- Success message: "Created Successfully"
- `TEST PRODUCT A` appears in product list
- Product stats count increases by 1

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-003 — Create product with opening stock quantity

🔴 Critical | Pre-condition: TC-PRD-002 passed OR create a new product

**Steps:**
1. Click Add Product
2. Fill in:
   - Item Name: `TEST PRODUCT B`
   - Type: `Product`
   - Selling Price: `500`
   - Purchase Price: `350`
   - Opening Quantity: `50`
   - Opening Purchase Price: `350`
3. Click Save

**Expected Result:**
- Product created successfully
- Go to `/inventory`
- `TEST PRODUCT B` shows Available Qty = `50`

**Side Effects to Verify:**
- `Products.ProductStockTbl.AvailableQty` = 50
- Product appears in product search on Invoice form with qty 50

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-004 — Create a Service product (no stock)

🟡 High

**Steps:**
1. Click Add Product
2. Fill in:
   - Item Name: `TEST SERVICE`
   - Type: `Service`
   - Selling Price: `2000`
3. Click Save

**Expected Result:**
- Product created successfully
- No opening quantity field shown (or ignored)
- Product does NOT appear in inventory/stock reports
- Product appears in invoice product search

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-005 — Create product with missing required fields

🔴 Critical

**Steps:**
1. Click Add Product
2. Leave Item Name empty
3. Click Save

**Expected Result:**
- Error message shown: "Item Name is required" or similar
- Product is NOT created
- Form stays open

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-006 — Edit a product

🔴 Critical | Pre-condition: TC-PRD-002 passed

**Steps:**
1. Find `TEST PRODUCT A` in the list
2. Click Edit (pencil icon)
3. Change Selling Price to `1200`
4. Click Save / Update

**Expected Result:**
- Success message: "Updated Successfully"
- `TEST PRODUCT A` now shows Selling Price `1200` in the list
- When searched in invoice form, shows updated price

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-007 — Edit opening quantity — verify stock updates

🔴 Critical | Pre-condition: TC-PRD-003 passed (TEST PRODUCT B has qty 50)

**Steps:**
1. Edit `TEST PRODUCT B`
2. Change Opening Quantity from `50` to `60`
3. Save

**Expected Result:**
- Success message
- Go to `/inventory`
- `TEST PRODUCT B` Available Qty = `60` (increased by 10 delta)

**Side Effects to Verify:**
- Qty went UP by delta (10), not reset to 60 from scratch

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-008 — Search for product in product list

🟡 High | Pre-condition: TEST PRODUCT A exists

**Steps:**
1. On products page, use the search/filter
2. Type `TEST PRODUCT`

**Expected Result:**
- Only matching products shown
- Results update as you type or on search click

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-009 — Toggle product status (Deactivate)

🟡 High | Pre-condition: TEST PRODUCT A exists and is Active

**Steps:**
1. Find `TEST PRODUCT A` in the list
2. Click the Active/Inactive toggle or use action menu → Deactivate

**Expected Result:**
- Product status changes to Inactive
- Product no longer appears in invoice product search
- Still visible in the products list (with Inactive status)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-010 — Reactivate product

🟡 High | Pre-condition: TC-PRD-009 passed

**Steps:**
1. Find `TEST PRODUCT A` (Inactive) in the list
2. Toggle back to Active

**Expected Result:**
- Status changes to Active
- Product reappears in invoice product search

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-011 — Delete a product (no transactions)

🔴 Critical | Pre-condition: A product exists with zero transactions

**Steps:**
1. Create a new product `TEST DELETE PRODUCT`
2. Do NOT create any invoice/purchase with it
3. Delete it from the action menu

**Expected Result:**
- Success message
- Product removed from list
- Product does not appear in product search

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-PRD-012 — Export products to CSV

🟡 High | Pre-condition: At least one product exists

**Steps:**
1. On Products page, click Export
2. Select CSV

**Expected Result:**
- CSV file downloaded
- File contains headers: #, Name, Type, Selling Price, etc.
- Data rows match what's in the list

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
