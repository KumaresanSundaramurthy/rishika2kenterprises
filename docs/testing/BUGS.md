# Bug Tracker

Log every failed test case here. Format: one row per bug.

| Bug # | Test ID | Module | What You Did | What Happened (Actual) | Severity | Status |
|-------|---------|--------|-------------|----------------------|----------|--------|
| BUG-001 | | | | | | Open |

## Severity Guide

| Level | Meaning |
|-------|---------|
| 🔴 Blocker | Cannot proceed without fixing — launch blocked |
| 🟠 Critical | Major feature broken — must fix before launch |
| 🟡 Major | Feature works but result is wrong |
| 🟢 Minor | UI issue, cosmetic, or edge case |

## Example Entry

| Bug # | Test ID | Module | What You Did | What Happened (Actual) | Severity | Status |
|-------|---------|--------|-------------|----------------------|----------|--------|
| BUG-001 | TC-INV-008 | Invoices | Recorded full payment of ₹3,000 | Customer balance did not update — still shows ₹3,000 Dr | 🔴 Blocker | Fixed |
