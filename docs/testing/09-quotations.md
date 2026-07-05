# TC-QT — Quotations Test Cases

Module: Quotations (ModuleUID = 101)  
Route: `/quotations`

---

## ⚠️ BLOCKER — Routes Missing

Quotation routes are **completely absent** from `application/config/routes.php`.  
All URLs below will return **404** until these are added:

```php
$route['quotations']                                  = 'quotations/index';
$route['quotations/create']                           = 'quotations/create';
$route['quotations/(:num)/edit']                      = 'quotations/edit/$1';
$route['quotations/getQuotationsPageDetails/(:num)']  = 'quotations/getQuotationsPageDetails/$1';
$route['quotations/getQuotationsPageDetails']         = 'quotations/getQuotationsPageDetails';
$route['quotations/addQuotation']                     = 'quotations/addQuotation';
$route['quotations/updateQuotation']                  = 'quotations/updateQuotation';
$route['quotations/deleteQuotation']                  = 'quotations/deleteQuotation';
$route['quotations/convertQuotationToInvoice']        = 'quotations/convertQuotationToInvoice';
$route['quotations/updateQuotationStatus']            = 'quotations/updateQuotationStatus';
```

---

## Status Flow

```
Draft → Pending → Accepted → Converted (terminal)
                └──────────→ Cancelled (terminal)
         Pending → Cancelled (terminal)
```

| From ↓ / To →  | Pending | Accepted | Cancelled | Converted |
|----------------|---------|----------|-----------|-----------|
| Draft          | ✅      | ❌       | ❌        | ❌        |
| Pending        | —       | ✅       | ✅        | ❌        |
| Accepted       | ✅      | —        | ✅        | ✅ (via convert action) |
| Cancelled      | ❌      | ❌       | —         | ❌        |
| Converted      | ❌      | ❌       | ❌        | —         |

---

## TC-QT-01 | Page Load

| # | Action | Expected |
|---|--------|----------|
| 1 | Navigate to `/quotations` | List loads with stat cards: All, Open, Accepted, Converted, Drafts |
| 2 | Check tabs | All \| Cancelled \| Draft tabs visible |
| 3 | Check columns | #/Date, Amount, Status, Customer, Valid Until, Last Updated, Actions |
| 4 | No records exist | Empty state message shown (not blank or error) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-02 | Create Form Load

| # | Action | Expected |
|---|--------|----------|
| 1 | Navigate to `/quotations/create` | Form renders without error |
| 2 | Check Quotation Date field | Defaults to today in user's date format |
| 3 | Check Validity Days | Defaults to 7 |
| 4 | Check Validity Date | Defaults to today + 7 days |
| 5 | Check Type dropdown | "Regular" selected by default |
| 6 | Check buttons | "Save as Draft" and "Save" both present |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-03 | Save as Draft — Validation

**Pre-condition:** Fresh create form

| # | Action | Expected |
|---|--------|----------|
| 1 | Leave customer blank, click "Save as Draft" | Error: customer is required |
| 2 | Select customer, leave all items blank, click "Save as Draft" | Error: at least one line item required |
| 3 | Add product row with qty = 0 | Error: quantity must be > 0 |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-04 | Save as Draft — Success

**Pre-condition:** Customer exists, product with stock > 0 exists

| # | Action | Expected |
|---|--------|----------|
| 1 | Select customer | Customer populated |
| 2 | Add product, qty = 3, price = 500 | Row total = 1500 (+ tax if Regular) |
| 3 | Click "Save as Draft" | Record saved, status = **Draft** |
| 4 | Check list | "Draft" badge shown, no UniqueNumber assigned |
| 5 | Check Draft tab count | Increments by 1 |
| 6 | Check ProductStockTbl | Stock **unchanged** (quotations have no stock impact) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-05 | Validity Date Auto-Calculation

| # | Action | Expected |
|---|--------|----------|
| 1 | Set Quotation Date = 01 Jul 2026 | Date set |
| 2 | Set Validity Days = 10 | Validity Date auto-updates to **11 Jul 2026** |
| 3 | Set Validity Days = 30 | Validity Date auto-updates to **31 Jul 2026** |
| 4 | Manually set Validity Date to 20 Jul 2026 | Validity Days recalculates to 19 (verify whether days field updates) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-06 | Save as Pending — Validation

| # | Action | Expected |
|---|--------|----------|
| 1 | Fill customer + items, click "Save" without selecting prefix | Error: prefix required |
| 2 | Select prefix, leave number blank, click "Save" | Error: quotation number required |
| 3 | Enter number already used by another quotation under same prefix | Error: number already exists |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-07 | Save as Pending — Success

**Pre-condition:** Prefix configured in settings

| # | Action | Expected |
|---|--------|----------|
| 1 | Select customer, add 2 products with tax | Totals calculated correctly |
| 2 | Select prefix, enter unique number | Fields accepted |
| 3 | Click "Save" | Status = **Pending**, UniqueNumber = prefix + number (e.g. QT-2026-001) |
| 4 | Check All tab | Record appears |
| 5 | Check "Open" stat card | Count increments |
| 6 | Check ProductStockTbl | Stock **unchanged** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-08 | DocType = Without_GST

| # | Action | Expected |
|---|--------|----------|
| 1 | On create form, change Type to "Without_GST" | Tax columns hidden / zeroed in line items |
| 2 | Add product qty = 2, price = 1000 | Total = 2000 (no GST) |
| 3 | Save as Draft | Saved with DocType = Without_GST |
| 4 | Edit the record | Type still shows Without_GST, tax still zero |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-09 | Additional Charges + Extra Discount

| # | Action | Expected |
|---|--------|----------|
| 1 | Add product: ₹1000 + 18% GST | Sub-total = 1000, GST = 180, Total = 1180 |
| 2 | Add Shipping charge = ₹100 | Total = 1280 |
| 3 | Add Extra Discount = ₹50 (Fixed) | Total = 1230 |
| 4 | Save and verify list row amount | Shows ₹1230 |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-10 | Save & Print A4

| # | Action | Expected |
|---|--------|----------|
| 1 | Fill form with customer and 1 product | Form ready |
| 2 | Click "Save & Print A4" | Quotation saved as Pending |
| 3 | Check PDF | Opens immediately; contains customer name, items, totals, validity date, terms |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-11 | Edit — Draft Quotation

| # | Action | Expected |
|---|--------|----------|
| 1 | Click Edit on a Draft quotation | Form opens |
| 2 | Change customer to a different one | Customer field **not locked** in Draft |
| 3 | Change product quantity | Accepted |
| 4 | Click "Save as Draft" | Updated, still status = Draft |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-12 | Edit — Pending Quotation

| # | Action | Expected |
|---|--------|----------|
| 1 | Click Edit on a Pending quotation | Form opens |
| 2 | Try to change customer | Customer field **locked** (no search modal) |
| 3 | Change a product qty and price | Accepted |
| 4 | Click "Save" | Record updated, same UniqueNumber retained |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-13 | Edit — Terminal Status (No Edit Button)

| # | Action | Expected |
|---|--------|----------|
| 1 | Find a Cancelled quotation | Edit button **not visible** |
| 2 | Find a Converted quotation | Edit button **not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-14 | Draft Clone Logic

**Pre-condition:** Draft quotation exists (TransUID = N). Another quotation created after it (TransUID > N).

| # | Action | Expected |
|---|--------|----------|
| 1 | Edit and re-save the Draft quotation | Old draft row soft-deleted |
| 2 | Check list | New row inserted with higher TransUID |
| 3 | Verify no duplicate entries | Only one record visible for this quotation |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-15 | Status Transition — Pending → Accepted

| # | Action | Expected |
|---|--------|----------|
| 1 | Find a Pending quotation | Status dropdown available on list row |
| 2 | Click status dropdown → select "Accepted" | Confirmation dialog shown |
| 3 | Confirm | Status = **Accepted**, badge updates immediately |
| 4 | Check "Accepted" stat card | Count increments |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-16 | Status Transition — Pending → Cancelled

| # | Action | Expected |
|---|--------|----------|
| 1 | Find a Pending quotation | Cancel option in dropdown |
| 2 | Select "Cancelled", confirm | Status = **Cancelled** |
| 3 | Check Cancelled tab | Record appears there |
| 4 | Check for Edit / Convert buttons | Neither visible |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-17 | Status Transition — Accepted → Pending (Revert)

| # | Action | Expected |
|---|--------|----------|
| 1 | Find an Accepted quotation | "Pending" option available in dropdown |
| 2 | Select "Pending", confirm | Status reverts to **Pending** |
| 3 | Check Convert buttons | Convert to Invoice / SO buttons **disappear** (only available from Accepted) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-18 | Status Transition — Accepted → Cancelled

| # | Action | Expected |
|---|--------|----------|
| 1 | Find an Accepted quotation | "Cancelled" option available |
| 2 | Select "Cancelled", confirm | Status = **Cancelled** |
| 3 | Try to revert | No transitions possible from Cancelled |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-19 | Invalid Transition — Block at Server

| # | Action | Expected |
|---|--------|----------|
| 1 | POST to `updateQuotationStatus` with Draft → Converted | Error: invalid transition |
| 2 | POST with Cancelled → Pending | Error: terminal status cannot change |
| 3 | POST with Converted → Accepted | Error: terminal status cannot change |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-20 | Cancel Button Visibility

| # | Action | Expected |
|---|--------|----------|
| 1 | Draft quotation → check Cancel option | **Not shown** |
| 2 | Pending quotation → check Cancel option | **Shown** |
| 3 | Accepted quotation → check Cancel option | **Shown** |
| 4 | Cancelled quotation → check Cancel option | **Not shown** |
| 5 | Converted quotation → check Cancel option | **Not shown** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-21 | Delete Quotation

| # | Action | Expected |
|---|--------|----------|
| 1 | Click Delete on a Draft quotation, confirm | Soft-deleted, removed from list, Draft count decrements |
| 2 | Click Delete on a Pending quotation, confirm | Soft-deleted, no stock impact |
| 3 | Click Delete on a Cancelled quotation, confirm | Removed from Cancelled tab |
| 4 | Click Delete on a Converted quotation | Document behavior: blocked or allowed |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-22 | Convert to Invoice — Visibility

| # | Action | Expected |
|---|--------|----------|
| 1 | Draft quotation → check Convert button | **Not visible** |
| 2 | Pending quotation → check Convert to Invoice | **Not visible** |
| 3 | Accepted quotation → check Convert to Invoice | **Visible** |
| 4 | Cancelled quotation → check Convert to Invoice | **Not visible** |
| 5 | Converted quotation → check Convert to Invoice | **Not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-23 | Convert Accepted → Invoice

**Pre-condition:** Accepted quotation with 2 products

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to Invoice" | Redirected to `/invoices/create?fromQuotation={TransUID}` |
| 2 | Check Invoice form | Customer pre-filled and **locked** |
| 3 | Check items | All items, qty, price, tax pre-filled from quotation |
| 4 | Check Notes / Terms | Pre-filled from quotation |
| 5 | Check source quotation status | Still **Accepted** (not yet Converted) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-24 | Quotation Marked Converted After Invoice Saved

| # | Action | Expected |
|---|--------|----------|
| 1 | Complete TC-QT-23, then save the Invoice form | Invoice saved |
| 2 | Check source quotation | Status = **Converted** |
| 3 | Try to edit source quotation | Edit button **not visible** |
| 4 | Try to convert again | Convert button **not visible** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-25 | Convert Accepted → Sales Order

**Pre-condition:** Accepted quotation

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to Sales Order" | Redirected to `/salesorders/create?fromQuotation={TransUID}` |
| 2 | Check SO form | Customer locked, items pre-filled, Expected Delivery = today + 7 |
| 3 | Save the SO | SO saved as Pending |
| 4 | Check source quotation | Status = **Converted** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-26 | Abandon Conversion Mid-Way

| # | Action | Expected |
|---|--------|----------|
| 1 | Click "Convert to Invoice" on Accepted quotation | Invoice create form opens |
| 2 | Navigate away WITHOUT saving the Invoice | Leave the page |
| 3 | Go back to quotations list | Source quotation status = **Accepted** (NOT Converted) |
| 4 | Convert button still visible | Quotation still convertible |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-27 | Valid Until — Expiry Display

| # | Action | Expected |
|---|--------|----------|
| 1 | Create quotation, Validity Date = today + 5 | "Valid Until" column shows "in 5 days" style badge |
| 2 | Create quotation, Validity Date = today | Badge shows "Expires today" (warning style) |
| 3 | Create quotation, Validity Date = yesterday | Badge shows "Overdue" (red/danger) |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-28 | List Filters

| # | Action | Expected |
|---|--------|----------|
| 1 | Search by known quotation number | Filters after ~1.5s debounce, exact match shown |
| 2 | Search by customer name | Filters to that customer's quotations |
| 3 | Check "Pending" status filter only | Only Pending quotations listed |
| 4 | Set date range = last 7 days | Only quotations within range shown |
| 5 | Select specific customer from filter | Only that customer's records |
| 6 | Click "Cancelled" tab | Only Cancelled quotations |
| 7 | Click "Draft" tab | Only Draft quotations |
| 8 | Click "Accepted" stat card | Auto-filters to Accepted |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-29 | Print / Share

| # | Action | Expected |
|---|--------|----------|
| 1 | Click Print/Download on Pending quotation | PDF opens with full details |
| 2 | Click Print on Draft quotation | Print option **not shown** for Draft |
| 3 | Click WhatsApp share on Pending (customer with mobile) | WhatsApp link generated |
| 4 | WhatsApp share on Draft | Share buttons **not shown** for Draft |
| 5 | Click "Export" on filtered list | CSV/Excel downloaded with filtered records |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-30 | No Stock Impact — Full Verification

| # | Action | Expected |
|---|--------|----------|
| 1 | Note AvailableQty for Product A | Record value |
| 2 | Create Pending quotation with Product A qty = 10 | Saved |
| 3 | Check Product A AvailableQty | **Unchanged** |
| 4 | Change status to Accepted | Stock **unchanged** |
| 5 | Cancel the quotation | Stock **unchanged** |
| 6 | Delete any quotation | Stock **unchanged** |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-31 | Edge Cases

| # | Action | Expected |
|---|--------|----------|
| 1 | Enter `<script>alert('xss')</script>` in Notes, save, view | Displayed as plain text — not executed |
| 2 | Add 20+ product rows, save | All rows saved, PDF renders all rows |
| 3 | Navigate to `/quotations/99999/edit` (non-existent UID) | "Not found" message — no 500 error |
| 4 | Log in as Org B, try to access Org A's quotation UID | Access denied / not found |
| 5 | POST to `updateQuotationStatus` with a Cancelled → Pending | Server blocks: error returned |

**Pass / Fail:** ___  **Bug Ref:** ___

---

## TC-QT-32 | Settings Integration

| # | Action | Expected |
|---|--------|----------|
| 1 | Set Terms & Conditions text in General Settings, open create form | Terms textarea pre-filled with that value |
| 2 | Configure signature in settings, print a quotation | Signature visible in PDF |
| 3 | Check date display in list | Follows `ListDateFormat` setting (e.g. "01 Jul 2026") — no hardcoded format |

**Pass / Fail:** ___  **Bug Ref:** ___
