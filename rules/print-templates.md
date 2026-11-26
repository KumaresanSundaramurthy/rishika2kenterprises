# Print Templates Specification (Modal, Browser Print & PDF)

## 1. Core Principle: Single Source of Truth
- **Modal Preview**, **Browser Print**, and **PDF Export** for quotations (e.g., `EST26-27/066`) MUST use the **same underlying item dataset and row ordering**.
- Do **not** maintain separate, out-of-sync item loops or duplicate DOM clones across the modal preview, print view, and PDF generator.
- When fixing a bug in one view, check whether the change affects the other two views.

## 2. Target-Specific Rules

### A. Modal Preview
- Render the full quotation items list cleanly without duplicating rows across simulated page breaks.
- Ensure every `<tr>` / item row has `break-inside: avoid; page-break-inside: avoid;` and intact bottom/side borders.
- Do not clip or hide the last row on a page/container.

### B. Browser Print (`@media print`)
- Hide UI controls, modals, sidebars, and action buttons outside the quotation sheet.
- Keep table rows (`tr`) intact across page breaks (`break-inside: avoid; page-break-inside: avoid;`).
- Ensure every line item from index `1` to `N` prints once in exact order (no skipped items like `#29` and no misplaced rows inside summary/bank blocks).
- Fix page counter placeholders so they never render `Page Page 0 of 0`.

### C. PDF Export
- Keep PDF layout and item sequence identical to the printed quotation (all items `1..N` present, no missing items, no `Page Page 0 of 0`).
- Keep the summary totals, HSN/SAC tax table, UPI/QR, bank details, and footer aligned in their intended order after the items table.

## 3. Invariants (Never Break These)
1. **Item Completeness & Order:** Every quotation item appears once, in sequential `#` order, with no duplicates across page breaks and no omitted items.
2. **Totals Consistency:** Line item amounts/taxes and summary blocks (`Total Items / Qty`, `Taxable Amount`, `Tax Amount`, `Total`) stay consistent.
3. **Page Numbering:** Page indicators show valid current/total page numbers (never `0 of 0` or doubled `"Page Page"`).
4. **Minimal Scoped Edits:** Only edit files directly involved in quotation rendering/printing/PDF generation; do not refactor unrelated modules.

## 4. Required Verification After Every Fix
Before concluding a fix, verify all three outputs on a multi-page quotation (such as `EST26-27/066` with 25–30+ items):
1. Modal preview item list and borders.
2. Browser print output / print PDF.
3. Downloaded PDF export.