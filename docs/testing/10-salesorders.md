# TC-SO — Sales Orders Test Cases

Module: Sales Orders (ModuleUID = 102)  
Route: `/salesorders`

---

## Status Flow

```
Draft → Pending → Converted (terminal)  via Convert to Invoice / Convert to DC
               └→ Cancelled (terminal)  via Cancel action
```

| From ↓ / To →  | Pending | Cancelled | Converted |
|----------------|---------|-----------|-----------|
| Draft          | ✅      | ❌        | ❌        |
| Pending        | —       | ✅        | ✅ (via convert action) |
| Cancelled      | ❌      | —         | ❌        |
| Converted      | ❌      | ❌        | —         |

> Unlike Quotations, Sales Orders have **no "Accepted" status**.  
> Convert to Invoice or DC automatically sets status to "Converted".

---

## Conversion Paths

```
Quotation (Accepted) ──→ Sales Order (Pending) ──→ Invoice
                                               └──→ Delivery Challan
```

---

## TC-SO-01 | Page Load

| # | Action | Expected |
|---|--------|----------|
| 1 | Navigate to `/salesorders` | List loads with stat cards: All Orders, Pending, Completed, Drafts |
| 2 | Check tabs | All \| Pending \| Completed \| Cancelled \| Draft |
| 3 | Check columns | #/Date, Amount, Status, Customer, Expected Delivery, Last Updated, Actions |
| 4 | Overdue pending SOs (Expected Delivery in past) | Row shows distinct overdue styling |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-02 | Create Form Load

| # | Action | Expected |
|---|--------|----------|
| 1 | Navigate to `/salesorders/create` | Form renders without error |
| 2 | Check Order Date | Defaults to today in user's date format |
| 3 | Check Expected Delivery | Defaults to today + 7 days |
| 4 | Check Type dropdown | "Regular" selected by default |
| 5 | Check buttons | "Save as Draft" and "Save" both present |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-03 | Save as Draft — Validation

| # | Action | Expected |
|---|--------|----------|
| 1 | Leave customer blank → "Save as Draft" | Error: customer required |
| 2 | Select customer, leave items blank → "Save as Draft" | Error: at least one item required |
| 3 | Add item with qty = 0 | Error: quantity must be > 0 |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-04 | Save as Draft — Success

**Pre-condition:** Customer and product exist

| # | Action | Expected |
|---|--------|----------|
| 1 | Select customer, add product qty = 5, price = 200 | Row total = 1000 (+ tax) |
| 2 | Click "Save as Draft" | Status = **Draft**, no UniqueNumber |
| 3 | Check list | "Draft" badge shown in list row |
| 4 | Check Draft tab | Count increments by 1 |
| 5 | Check ProductStockTbl | Stock **unchanged** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-05 | Save as Pending — Validation

| # | Action | Expected |
|---|--------|----------|
| 1 | Fill customer + items, click "Save" without prefix | Error: prefix required |
| 2 | Select prefix, leave number blank → "Save" | Error: number required |
| 3 | Use number already taken by another SO under same prefix | Error: number already exists |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-06 | Save as Pending — Success

**Pre-condition:** Prefix configured in settings

| # | Action | Expected |
|---|--------|----------|
| 1 | Select customer, add 2 products with qty and tax | Totals calculated |
| 2 | Select prefix, enter unique number, click "Save" | Status = **Pending**, UniqueNumber generated (e.g. SO-2026-001) |
| 3 | Check All and Pending tabs | Record appears in both |
| 4 | Check Pending stat card | Count increments |
| 5 | Check ProductStockTbl | Stock **unchanged** (SO has no stock impact) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-07 | DocType = Without_GST

| # | Action | Expected |
|---|--------|----------|
| 1 | Change Type to "Without_GST" | Tax columns hidden / zeroed |
| 2 | Add product qty = 2, price = 1000 | Total = 2000 (no GST) |
| 3 | Save as Pending | Saved with DocType = Without_GST |
| 4 | Re-open the record | Type still Without_GST, totals still no tax |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-08 | Additional Charges + Extra Discount

| # | Action | Expected |
|---|--------|----------|
| 1 | Add product ₹2000 + 18% GST | Sub-total = 2000, GST = 360, Total = 2360 |
| 2 | Add Handling charge = ₹200 | Total = 2560 |
| 3 | Set Extra Discount = 10% | Discount applied, total recalculates |
| 4 | Save and check list row amount | Correct total shown |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-09 | Save & Print A4

| # | Action | Expected |
|---|--------|----------|
| 1 | Fill form with customer and 1 product | Form ready |
| 2 | Click "Save & Print A4" | SO saved as Pending, PDF opens immediately |
| 3 | Check PDF content | Customer, order #, date, expected delivery, items, totals, terms |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-10 | Create SO from Accepted Quotation

**Pre-condition:** An Accepted Quotation exists with 2 products, notes, terms, and attachment

| # | Action | Expected |
|---|--------|----------|
| 1 | On the Quotation, click "Convert to Sales Order" | Redirected to `/salesorders/create?fromQuotation={UID}` |
| 2 | Check Customer field | Pre-filled and **locked** (no search modal) |
| 3 | Check line items | All items pre-filled with qty / price / tax from Quotation |
| 4 | Check Notes and Terms | Pre-filled from Quotation |
| 5 | Check Expected Delivery | Defaults to today + 7 days |
| 6 | Check source Quotation status now | Still **Accepted** (not yet Converted) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-11 | Quotation Marked Converted After SO Saved

| # | Action | Expected |
|---|--------|----------|
| 1 | Complete TC-SO-10, click "Save" on SO form | SO saved as Pending |
| 2 | Check source Quotation | Status = **Converted** |
| 3 | Try to edit source Quotation | Edit button **not visible** |
| 4 | Try to convert Quotation again | Convert button **not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-12 | Abandon Conversion Mid-Way (Quotation → SO)

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to SO" on Accepted Quotation | SO create form opens |
| 2 | Navigate away WITHOUT saving | Leave page |
| 3 | Return to Quotation list | Source Quotation status = **Accepted** (NOT Converted) |
| 4 | Convert button still shown | Quotation still convertible |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-13 | Edit Items Before Saving (from Quotation pre-fill)

| # | Action | Expected |
|---|--------|----------|
| 1 | Convert Accepted Quotation to SO | Pre-filled form with qty = 5 |
| 2 | Change quantity to 3 on one product | Accepted, totals recalculate |
| 3 | Save the SO | SO saved with qty = 3 (not original 5) |
| 4 | Check Quotation | Status = Converted |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-14 | Edit — Draft SO

| # | Action | Expected |
|---|--------|----------|
| 1 | Click Edit on a Draft SO | Form opens |
| 2 | Change customer | Customer field **not locked** in Draft |
| 3 | Change product qty | Accepted |
| 4 | Click "Save as Draft" | Updated, status still Draft |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-15 | Edit — Pending SO

| # | Action | Expected |
|---|--------|----------|
| 1 | Click Edit on a Pending SO | Form opens |
| 2 | Try to change customer | Customer field **locked** (no search modal) |
| 3 | Change product qty from 5 to 8 | Accepted |
| 4 | Click "Save" | Updated, same UniqueNumber retained |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-16 | Edit — Terminal SO (No Edit Button)

| # | Action | Expected |
|---|--------|----------|
| 1 | Find a Cancelled SO | Edit button **not visible** |
| 2 | Find a Converted SO | Edit button **not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-17 | Draft Clone Logic

**Pre-condition:** Draft SO exists (TransUID = N). Another SO created after it (TransUID > N).

| # | Action | Expected |
|---|--------|----------|
| 1 | Edit and re-save the Draft SO | Old draft row soft-deleted |
| 2 | Check list | New row with higher TransUID visible |
| 3 | Verify no duplicate | Only one record for this SO |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-18 | Status Transition — Pending → Cancelled

| # | Action | Expected |
|---|--------|----------|
| 1 | Find a Pending SO | Cancel option visible |
| 2 | Click Cancel, confirm dialog | Status = **Cancelled** |
| 3 | Check Cancelled tab | Record appears there |
| 4 | Check for Edit / Convert buttons | Neither visible on Cancelled |
| 5 | Check ProductStockTbl | Stock **unchanged** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-19 | Cancel Button Visibility

| # | Action | Expected |
|---|--------|----------|
| 1 | Draft SO → check Cancel | **Not shown** |
| 2 | Pending SO → check Cancel | **Shown** |
| 3 | Cancelled SO → check Cancel | **Not shown** |
| 4 | Converted SO → check Cancel | **Not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-20 | Invalid Transitions — Server Blocks

| # | Action | Expected |
|---|--------|----------|
| 1 | POST `updateSalesOrderStatus`: Draft → Cancelled | Error: invalid transition |
| 2 | POST: Cancelled → Pending | Error: terminal status cannot change |
| 3 | POST: Converted → Pending | Error: terminal status cannot change |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-21 | Convert to Invoice — Visibility

| # | Action | Expected |
|---|--------|----------|
| 1 | Draft SO → check Convert to Invoice | **Not visible** |
| 2 | Pending SO → check Convert to Invoice | **Visible** |
| 3 | Cancelled SO → check Convert to Invoice | **Not visible** |
| 4 | Converted SO → check Convert to Invoice | **Not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-22 | Convert Pending SO → Invoice

**Pre-condition:** Pending SO with 2 products, notes, charges

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to Invoice" | SO status set to **Converted** |
| 2 | Redirected to `/invoices/create?fromSalesOrder={UID}` | Invoice form opens |
| 3 | Check Customer | Pre-filled and **locked** |
| 4 | Check items | All items, qty, price, tax pre-filled |
| 5 | Check Notes / Terms / Charges | Pre-filled from SO |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-23 | Abandon Conversion (SO → Invoice)

**Pre-condition:** Pending SO

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to Invoice" | SO status → Converted, Invoice form opens |
| 2 | Navigate away WITHOUT saving the Invoice | Invoice not created |
| 3 | Check SO status | Verify: is SO still Converted even though Invoice was never saved? |

> ⚠️ Edge case: If SO becomes "Converted" at click-time and Invoice is abandoned, the SO is stranded. Document actual behavior.

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-24 | Convert Pending SO → Delivery Challan

**Pre-condition:** Pending SO with products

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to Delivery Challan" | SO status → **Converted** |
| 2 | Redirected to `/deliverychallan/create?fromSalesOrder={UID}` | DC form opens |
| 3 | Check Customer on DC form | Pre-filled and **locked** |
| 4 | Check items | All SO items pre-filled |
| 5 | Save the DC with dispatch mode | DC saved, stock **reduced** by DC qty |
| 6 | Check SO | Still Converted |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-25 | Convert to DC — Visibility

| # | Action | Expected |
|---|--------|----------|
| 1 | Draft SO → check Convert to DC | **Not visible** |
| 2 | Pending SO → check Convert to DC | **Visible** |
| 3 | Cancelled SO | **Not visible** |
| 4 | Converted SO | **Not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-26 | Convert Already Converted SO (Server Block)

| # | Action | Expected |
|---|--------|----------|
| 1 | POST to `convertSalesOrderToInvoice` with a Converted SO's UID | Error: SO is already Converted |
| 2 | POST to `convertSalesOrderToDeliveryChallan` with same | Error: already Converted |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-27 | Duplicate SO

**Pre-condition:** Pending SO exists

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Duplicate" from 3-dot menu | New Draft SO created |
| 2 | Check duplicated SO | Same customer, items, qty, prices, notes, terms |
| 3 | Check duplicated SO status | **Draft** (no number assigned) |
| 4 | Check original SO | Status unchanged (still Pending) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-28 | Delete SO

| # | Action | Expected |
|---|--------|----------|
| 1 | Delete a Draft SO, confirm | Removed from list, Draft count decrements, no stock impact |
| 2 | Delete a Pending SO, confirm | Soft-deleted, no stock impact |
| 3 | Delete a Cancelled SO, confirm | Removed from Cancelled tab |
| 4 | Delete a Converted SO | Document: blocked or allowed |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-29 | Overdue Detection

| # | Action | Expected |
|---|--------|----------|
| 1 | Create Pending SO with Expected Delivery = yesterday | List row shows **Overdue** tag (red/danger) |
| 2 | Create Pending SO with Expected Delivery = today | Shows "Due today" or similar label |
| 3 | Create Pending SO with Expected Delivery = tomorrow | Normal display, no overdue tag |
| 4 | Cancelled/Converted SO with past Expected Delivery | **No** overdue styling (only Pending gets overdue flag) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-30 | List Filters

| # | Action | Expected |
|---|--------|----------|
| 1 | Search by SO number | Filters after ~1.5s debounce |
| 2 | Search by customer name | Filters to that customer's SOs |
| 3 | Set date range = last 30 days | Only SOs in that range shown |
| 4 | Select specific customer from filter | Only that customer's records |
| 5 | Click "Pending" tab | Only Pending SOs |
| 6 | Click "Cancelled" tab | Only Cancelled SOs |
| 7 | Click "Draft" tab | Only Draft SOs |
| 8 | Click "Pending" stat card | Auto-filters to Pending |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-31 | Print / Share

| # | Action | Expected |
|---|--------|----------|
| 1 | Print/Download on Pending SO | PDF with customer, items, expected delivery, totals |
| 2 | Print on Draft SO | Print option **not shown** |
| 3 | WhatsApp share on Pending (customer with mobile) | WhatsApp link generated |
| 4 | WhatsApp share on Draft | Share buttons **not shown** |
| 5 | Export filtered list | CSV/Excel downloaded |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-32 | No Stock Impact — Full Verification

| # | Action | Expected |
|---|--------|----------|
| 1 | Note AvailableQty for Product A | Record value |
| 2 | Create Pending SO with Product A qty = 10 | Saved |
| 3 | Check Product A stock | **Unchanged** |
| 4 | Cancel the SO | Stock **unchanged** |
| 5 | Delete any Pending SO | Stock **unchanged** |
| 6 | Convert SO to Invoice (Invoice not saved yet) | Stock **unchanged** at SO level |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-33 | Full Chain — Quotation → SO → Invoice

**Pre-condition:** Product A with opening stock

| # | Action | Expected |
|---|--------|----------|
| 1 | Create Pending Quotation: Product A qty=5, ₹1000 | Saved |
| 2 | Change Quotation status → Accepted | Status = Accepted |
| 3 | Convert to SO → verify pre-fill → Save | SO = Pending, Quotation = Converted |
| 4 | Convert SO to Invoice → verify pre-fill → Save | Invoice created, SO = Converted |
| 5 | Check Product A stock after Invoice save | Stock **reduced by 5** (Invoice deducts stock) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-34 | Full Chain — Quotation → SO → Delivery Challan

**Pre-condition:** Product A with opening stock

| # | Action | Expected |
|---|--------|----------|
| 1 | Create Pending Quotation: Product A qty=3 → Accepted | Status = Accepted |
| 2 | Convert to SO → save | SO = Pending, Quotation = Converted |
| 3 | Convert SO to DC → save DC (Dispatch mode) | DC saved, SO = Converted |
| 4 | Check Product A stock | Stock **reduced by 3** (DC deducts stock) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-35 | Two SOs from Same Quotation (Should Be Blocked)

| # | Action | Expected |
|---|--------|----------|
| 1 | Create Accepted Quotation | Status = Accepted |
| 2 | Convert to SO → Save | SO created, Quotation = Converted |
| 3 | Try to convert same Quotation to another SO (via button or URL) | **Blocked**: Quotation already Converted — Convert button not shown |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-36 | Edge Cases

| # | Action | Expected |
|---|--------|----------|
| 1 | Enter `<script>alert('xss')</script>` in Notes, save, view | Displayed as plain text — not executed |
| 2 | Add 20+ product rows, save as Pending | All rows saved, PDF renders all rows |
| 3 | Navigate to `/salesorders/99999/edit` (non-existent UID) | "Not found" — no 500 error |
| 4 | Log in as Org B, access Org A's SO UID via API | Access denied / not found |
| 5 | POST `convertSalesOrderToInvoice` with a Draft SO's UID | Error: SO must be Pending to convert |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-SO-37 | Settings Integration

| # | Action | Expected |
|---|--------|----------|
| 1 | Set Terms text in General Settings, open SO create form | Terms textarea pre-filled |
| 2 | Configure signature in settings, print SO | Signature visible in PDF |
| 3 | Check date display in list | Follows `ListDateFormat` setting — no hardcoded format |
| 4 | Expected Delivery overdue styling — only Pending rows | Cancelled/Converted rows with past date show NO overdue badge |

**Pass / Fail:** ___  **Bug Ref:** ___
