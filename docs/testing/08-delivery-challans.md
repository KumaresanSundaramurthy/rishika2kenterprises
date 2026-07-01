# TC-DC — Delivery Challans Test Cases

Module: Delivery Challans (ModuleUID = 112)  
Route: `/deliverychallan`

---

## TC-DC-01 | Create — Non-Returnable

**Pre-condition:** Product with stock > 0 exists

| # | Action | Expected |
|---|--------|----------|
| 1 | New DC → Challan Type = **Non-Returnable** | Expected Return Date field hidden |
| 2 | Select customer | Address box populates |
| 3 | Set Dispatch Date = today | Shows in user's date format (no Y-m-d flash) |
| 4 | Add product qty = 5, Save | Status = **Dispatched**, stock − 5 |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-02 | Create — Returnable

| # | Action | Expected |
|---|--------|----------|
| 1 | Challan Type = **Returnable** | Expected Return Date field **appears** |
| 2 | Check return date (auto) | Auto-set to **today + 7 days** (smart default) |
| 3 | Manually change return date if needed | User can override via date picker |
| 4 | Switch back to Non-Returnable | Return date field **hides** + clears |
| 5 | Switch to Returnable again | Return date auto-fills **today + 7** again (fresh) |
| 6 | Save | Status = **Dispatched**, stock reduced |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-03 | Create — Job Work

| # | Action | Expected |
|---|--------|----------|
| 1 | Challan Type = **Job Work** | Return date visible (same as Returnable) |
| 2 | Save | Status = **Dispatched** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-04 | Overdue Indicator

**Pre-condition:** Returnable DC, Return Date = yesterday, Status = Dispatched

| # | Action | Expected |
|---|--------|----------|
| 1 | Open DC list | Row highlighted **red** |
| 2 | Expected Return Date column | Shows date + **"Overdue"** tag |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-05 | Due Soon Indicator

**Pre-condition:** Returnable DC, Return Date = 2 days from now

| # | Action | Expected |
|---|--------|----------|
| 1 | Open DC list | Row highlighted **yellow** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-06 | Days Out Counter

**Pre-condition:** DC dispatched 5 days ago, Status = Dispatched

| # | Action | Expected |
|---|--------|----------|
| 1 | Open DC list | Expected Return Date column shows **"5 days out"** (orange pill) |
| 2 | DC dispatched today | Shows **"Today"** (grey pill) |
| 3 | DC dispatched 10 days ago | Shows **"10 days out"** (red pill) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-07 | Mark as Delivered (Non-Returnable)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (Non-Returnable, Dispatched) → More Options | **"Mark as Delivered"** visible |
| 2 | Click Mark as Delivered | Status → **Delivered** |
| 3 | More Options | **"Convert to Invoice"** now visible |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-08 | Convert to Invoice

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (Delivered) → More Options → Convert to Invoice | Confirmation dialog |
| 2 | Confirm | Status → **Converted**, redirect to Invoice form |
| 3 | Invoice form | Pre-filled with DC customer + items |
| 4 | Save invoice | Invoice created successfully |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-09 | Full Return (Returnable)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (Returnable, Dispatched) → More Options | **"Partial / Full Return"** visible |
| 2 | Click it | Modal opens with item list |
| 3 | Enter full qty for all items | Submit button enables |
| 4 | Confirm Return | Status → **Returned**, stock fully restored |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-10 | Partial Return — First Batch

**Pre-condition:** DC has Product A (qty=10), Product B (qty=5)

| # | Action | Expected |
|---|--------|----------|
| 1 | Open Partial Return modal | A: Still Out=10, B: Still Out=5 |
| 2 | Enter A=6, B=5 | Submit enabled |
| 3 | Confirm Return | Status → **Partially Returned**, stock A+6, B+5 |
| 4 | Reopen modal | A: Returned=6, Still Out=4 / B: disabled (all returned) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-11 | Partial Return — Final Batch

**Pre-condition:** TC-DC-10 completed (A: 4 still out)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (Partially Returned) → More Options | **"Partial / Full Return"** still visible |
| 2 | Open modal | B input disabled, A Still Out = 4 |
| 3 | Enter A = 4, Confirm | Status → **Returned**, stock A+4 |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-12 | Partial Return — Validation

| # | Action | Expected |
|---|--------|----------|
| 1 | Enter qty > Still Out | Input red border, Submit **disabled** |
| 2 | Enter negative qty | Input red border, Submit **disabled** |
| 3 | All inputs = 0 | Submit stays **disabled** |
| 4 | Valid qty for 1 item | Submit **enables** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-13 | Cancel (Dispatched)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (Dispatched) → More Options → Cancel | Confirmation dialog |
| 2 | Confirm | Status → **Cancelled**, stock **restored** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-14 | Attachment Icon on List

**Pre-condition:** DC has attachments uploaded

| # | Action | Expected |
|---|--------|----------|
| 1 | Open DC list | Paperclip **🖇** icon visible next to DC number |
| 2 | Click paperclip | Attachment gallery modal opens (teal header) |
| 3 | Click image thumbnail | Preview modal opens, image **centered** |
| 4 | DC with no attachments | No paperclip icon |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-15 | Email Share

**Pre-condition:** Customer has email address

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft) → More Options | **"Send Email"** visible |
| 2 | Click Send Email | Email composition modal opens |
| 3 | Customer has no email | "Send Email" option **not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-16 | Duplicate

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft) → More Options → Duplicate | Confirmation |
| 2 | Confirm | New DC created as **Draft** with same items |
| 3 | Check stock | Stock **not affected** (Draft = no stock movement) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-17 | Stock Accuracy (End-to-End)

**Pre-condition:** Product A initial stock = 20

| # | Action | Expected |
|---|--------|----------|
| 1 | Create DC qty=8, Dispatch | Stock = **12** |
| 2 | Partial return qty=3 | Stock = **15** |
| 3 | Return remaining qty=5 | Stock = **20** (fully restored) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-18 | Packing List

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft) → More Options → Packing List | Opens in new tab |
| 2 | Verify content | Shows all items + quantities |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-19 | Date Format — No Flash

| # | Action | Expected |
|---|--------|----------|
| 1 | Open Create DC form | Dispatch Date already shows in **user's format** from first render |
| 2 | No Y-m-d format visible at any point | ✅ No format flash |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-20 | SO → DC Conversion

**Pre-condition:** Sales Order in Pending status exists

| # | Action | Expected |
|---|--------|----------|
| 1 | Sales Order → Convert to DC | DC form opens, customer **locked** |
| 2 | Try to change customer | Not possible (field disabled) |
| 3 | Try to add new product | Only SO products allowed |
| 4 | Save | DC created, SO status → Converted |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## Bug Log

| Bug Ref | TC | Description | Status |
|---------|----|-------------|--------|
| DC-BUG-01 | | | Open |

---

*Last updated: 2026-07-01*
