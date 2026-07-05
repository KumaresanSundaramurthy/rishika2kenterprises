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
| 1 | DC (non-Draft, non-Cancelled) → More Options → Packing List | Navigates to packing list form in **same window** |
| 2 | DC with status Cancelled | Packing List option **not shown** in More Options |
| 3 | Verify content | Shows all DC items with quantities, vehicle, transporter fields |

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

## TC-DC-21 | Edit — Draft DC

**Pre-condition:** A Draft DC exists (saved without products, or saved mid-way)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC list → Draft DC row | Pencil (edit) icon visible in the row actions |
| 2 | Click pencil icon | Edit form opens with all previously saved data pre-filled |
| 3 | Change a product qty or add a new product line | Field accepts input normally |
| 4 | Save | DC updated; status remains **Draft**; **stock unchanged** (Draft never deducts stock) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-22 | Edit — Dispatched DC (Stock Re-adjustment)

**Pre-condition:** Dispatched DC exists with Product A qty = 10 (stock was deducted by 10)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC list → Dispatched DC row | Pencil (edit) icon visible |
| 2 | Click pencil icon | Edit form opens with pre-filled data |
| 3 | Change Product A qty from 10 to 7 | Field accepts the new value |
| 4 | Save | DC updated with qty = 7 |
| 5 | Check Product A stock | Stock corrected: previous 10 reversed then 7 deducted → net stock **+3 vs post-dispatch level** |
| 6 | Confirm no double-deduction | Stock = (pre-dispatch stock − 7), not (pre-dispatch stock − 17) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-23 | Delete — Draft DC

| # | Action | Expected |
|---|--------|----------|
| 1 | DC list → Draft DC → More Options | **Delete** option visible |
| 2 | Click Delete | Confirmation dialog appears with DC number |
| 3 | Confirm | DC removed from list; **no stock change** (Draft never deducted stock) |
| 4 | Verify stock | Product stock identical to before the Draft was created |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-24 | Delete — Dispatched DC (Stock Restored)

**Pre-condition:** Dispatched DC with Product A qty = 5 (stock was deducted by 5)

| # | Action | Expected |
|---|--------|----------|
| 1 | DC list → Dispatched DC → More Options | **Delete** option visible |
| 2 | Click Delete | Confirmation dialog appears |
| 3 | Confirm | DC removed from list; stock **+5 restored** |
| 4 | Verify stock | Product A stock = pre-dispatch level |
| 5 | Terminal DCs (Cancelled, Returned, Converted, Delivered) | Delete option **not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-25 | Cancel — Partially Returned DC (UI / Backend Mismatch)

**Pre-condition:** DC in "Partially Returned" status (some items returned, remainder still out)

> ⚠️ This test case documents a known gap — the Cancel button appears in the UI but the backend rejects it.

| # | Action | Expected | Actual |
|---|--------|----------|--------|
| 1 | DC (Partially Returned) → More Options | Cancel option is **visible** | Visible (UI does not guard this) |
| 2 | Click Cancel | Should show confirmation and cancel | Server returns error: *"Cannot change status from Partially Returned to Cancelled"* |
| 3 | Correct behaviour | Cancel button should be **hidden** for Partially Returned DCs | Bug: hidden guard missing in the dropdown |

**Resolution needed:** Either (a) add `'Partially Returned' => ['Cancelled']` to `validTransitions` in `updateDeliveryChallanStatus()` with stock reversal for remaining-out qty, or (b) hide the Cancel button in `list.php` when `$status === 'Partially Returned'`.

**Pass / Fail:** ___  **Bug Ref:** DC-BUG-02

---

## TC-DC-26 | Print — A4 and Download PDF

**Pre-condition:** A non-Draft DC exists

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft) → More Options | **"Print / Download"** and **"Download PDF"** both visible |
| 2 | Click **Print / Download** | Browser print dialog (or preview modal) opens with A4-formatted DC |
| 3 | Check printed content | DC number, party name, items, quantities, amounts, totals all correct |
| 4 | Click **Download PDF** | PDF file downloaded to browser with correct DC data |
| 5 | Draft DC → More Options | Print and Download options **not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-27 | Thermal Print

**Pre-condition:** A non-Draft DC exists; thermal print theme configured in Settings

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft) → More Options → **Thermal Print** | Thermal receipt preview opens |
| 2 | Check content | Items, quantities, amounts formatted for thermal paper width |
| 3 | Check header/footer | Org name, DC number, date visible |
| 4 | Draft DC → More Options | Thermal Print **not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-28 | Share via WhatsApp

**Pre-condition:** Customer has a mobile number; DC is non-Draft

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft, customer has mobile) → More Options | **"Share via WhatsApp"** visible |
| 2 | Click Share via WhatsApp | Opens `wa.me/` link with customer's number pre-filled |
| 3 | Customer has no mobile number | WhatsApp option **not shown** |
| 4 | Draft DC | WhatsApp option **not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-DC-29 | Send SMS

**Pre-condition:** Customer has a mobile number; DC is non-Draft

| # | Action | Expected |
|---|--------|----------|
| 1 | DC (non-Draft, customer has mobile) → More Options | **"Send SMS"** visible |
| 2 | Click Send SMS | SMS modal / confirmation dialog opens with customer number |
| 3 | Confirm | SMS dispatched; success toast shown |
| 4 | Customer has no mobile | SMS option **not shown** |
| 5 | Draft DC | SMS option **not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## Bug Log

| Bug Ref | TC | Description | Status |
|---------|----|-------------|--------|
| DC-BUG-01 | | | Open |
| DC-BUG-02 | TC-DC-25 | Cancel button visible for Partially Returned DCs but backend rejects the transition — UI guard missing | Open |

---

*Last updated: 2026-07-02*
