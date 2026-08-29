<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Product Profile: Transaction Tab ──────────────────────────────────── */
.pp-tx-topbar{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;padding:10px 16px;border-bottom:1px solid rgba(0,0,0,.06)}
.pp-tx-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pp-tx-filter-sel{font-size:.78rem;padding:4px 28px 4px 10px;width:auto;min-width:130px;max-width:190px}
.pp-tx-reset{font-size:.78rem;color:#64748b;cursor:pointer;text-decoration:underline;white-space:nowrap}
.pp-tx-reset:hover{color:#374151}
.pp-tx-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
.pp-tx-count{font-size:.75rem;color:#94a3b8;white-space:nowrap}
/* Export button */
.pp-tx-export-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;font-size:.78rem;font-weight:600;color:#475569;background:#fff;border:1px solid #e2e8f0;border-radius:7px;cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap}
.pp-tx-export-btn:hover:not([disabled]){background:#f8fafc;border-color:#cbd5e1}
.pp-tx-export-btn[disabled]{opacity:.5;cursor:not-allowed}
.pp-tx-export-btn .bx-download{font-size:1rem;color:#6366f1}
.pp-tx-export-caret{font-size:.85rem;color:#94a3b8;margin-left:1px}
/* Export menu */
.pp-tx-export-menu{padding:6px 0;min-width:220px;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 8px 24px rgba(0,0,0,.10)}
.pp-tx-export-section{padding:6px 14px 3px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;pointer-events:none}
.pp-tx-export-item{display:flex;align-items:center;gap:10px;padding:7px 14px;text-decoration:none;color:#374151;transition:background .12s}
.pp-tx-export-item:hover{background:#f1f5f9;color:#1e293b}
.pp-tx-export-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;font-size:1rem;flex-shrink:0}
.pp-tx-ei-print{background:#eff6ff;color:#3b82f6}
.pp-tx-ei-pdf{background:#fef2f2;color:#ef4444}
.pp-tx-ei-csv{background:#f0fdf4;color:#22c55e}
.pp-tx-ei-xls{background:#f0fdf4;color:#16a34a}
.pp-tx-export-label{display:flex;flex-direction:column;gap:1px;line-height:1.2}
.pp-tx-export-label strong{font-size:.8rem;font-weight:600}
.pp-tx-export-label small{font-size:.69rem;color:#94a3b8}

.pp-tx-wrap{overflow-x:auto}
.pp-tx-tbl{width:100%;border-collapse:collapse;font-size:.81rem}
.pp-tx-tbl thead th{background:#f8fafc;padding:9px 12px;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.pp-tx-tbl tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.pp-tx-tbl tbody tr:last-child td{border-bottom:none}
.pp-tx-tbl tbody tr:hover td{background:#f8fafc}

.pp-tx-ref{font-weight:700;color:#374151;font-size:.82rem}
.pp-tx-date{font-size:.72rem;color:#94a3b8;margin-top:2px}
.pp-tx-party{font-size:.82rem;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.pp-tx-badge{font-size:.68rem!important}
.pp-tx-qty-in{color:#16a34a;font-weight:700}
.pp-tx-qty-out{color:#ef4444;font-weight:700}
.pp-tx-qty-neutral{color:#374151;font-weight:600}
.pp-tx-amt{font-weight:700;color:#1e293b;white-space:nowrap}
.pp-tx-up{font-size:.78rem}
.pp-tx-variant-chip{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.7rem;font-weight:600;background:#dbeafe;color:#1d4ed8;white-space:nowrap}
.pp-tx-footer-note{padding:8px 14px;font-size:.72rem;color:#94a3b8;border-top:1px solid #f1f5f9;background:#f8fafc}
.pp-tx-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 20px;color:#94a3b8;text-align:center}
.pp-tx-empty i{font-size:2.2rem;margin-bottom:10px;opacity:.35}
.pp-tx-empty-title{font-size:.88rem;font-weight:600;color:#64748b}

@media(prefers-color-scheme:dark){
    .pp-tx-export-btn{background:#1e293b;border-color:#334155;color:#cbd5e1}
    .pp-tx-export-btn:hover:not([disabled]){background:#0f172a;border-color:#475569}
    .pp-tx-export-menu{background:#1e293b;border-color:#334155;box-shadow:0 8px 24px rgba(0,0,0,.4)}
    .pp-tx-export-item{color:#cbd5e1}
    .pp-tx-export-item:hover{background:#0f172a;color:#f1f5f9}
    .pp-tx-export-label small{color:#64748b}
    .pp-tx-topbar{border-color:rgba(255,255,255,.08)}
    .pp-tx-tbl thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .pp-tx-tbl tbody td{border-color:#1e293b}
    .pp-tx-tbl tbody tr:hover td{background:#0f172a}
    .pp-tx-ref{color:#e2e8f0}
    .pp-tx-party{color:#e2e8f0}
    .pp-tx-amt{color:#f1f5f9}
    .pp-tx-variant-chip{background:#1e3a5f;color:#93c5fd}
    .pp-tx-footer-note{background:#0f172a;border-color:#334155;color:#64748b}
    .pp-tx-reset{color:#94a3b8}
}
:root[data-theme="dark"] .pp-tx-export-btn{background:#1e293b;border-color:#334155;color:#cbd5e1}
:root[data-theme="dark"] .pp-tx-export-btn:hover:not([disabled]){background:#0f172a;border-color:#475569}
:root[data-theme="dark"] .pp-tx-export-menu{background:#1e293b;border-color:#334155}
:root[data-theme="dark"] .pp-tx-export-item{color:#cbd5e1}
:root[data-theme="dark"] .pp-tx-export-item:hover{background:#0f172a}
:root[data-theme="dark"] .pp-tx-export-label small{color:#64748b}
:root[data-theme="dark"] .pp-tx-topbar{border-color:rgba(255,255,255,.08)}
:root[data-theme="dark"] .pp-tx-tbl thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
:root[data-theme="dark"] .pp-tx-tbl tbody td{border-color:#1e293b}
:root[data-theme="dark"] .pp-tx-tbl tbody tr:hover td{background:#0f172a}
:root[data-theme="dark"] .pp-tx-ref{color:#e2e8f0}
:root[data-theme="dark"] .pp-tx-party{color:#e2e8f0}
:root[data-theme="dark"] .pp-tx-amt{color:#f1f5f9}
:root[data-theme="dark"] .pp-tx-variant-chip{background:#1e3a5f;color:#93c5fd}
:root[data-theme="dark"] .pp-tx-footer-note{background:#0f172a;border-color:#334155;color:#64748b}
:root[data-theme="dark"] .pp-tx-reset{color:#94a3b8}
:root[data-theme="light"] .pp-tx-topbar{border-color:rgba(0,0,0,.06)}
</style>

<!-- ── Product Profile Modal ─────────────────────────────────────────────── -->
<div class="modal fade" id="productProfileModal" tabindex="-1"
     aria-labelledby="ppModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable profile-modal-dialog">
        <div class="modal-content pp-modal-content">

            <!-- ── Modal Header (vtm-banner theme — emerald green) ───────── -->
            <div class="vtm-banner flex-shrink-0 pp-vtm-banner">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon" id="ppAvatarWrap">
                            <span id="ppAvatarInitials" class="cp-avatar-initials">?</span>
                        </div>
                        <div>
                            <div class="vtm-doc-number" id="ppModalTitle">Product Profile</div>
                            <div class="vtm-doc-meta" id="ppModalSubtitle"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-edit-btn" id="ppBtnEdit">
                            <i class="bx bx-edit"></i>Edit
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Tab Nav ────────────────────────────────────────────────── -->
            <div class="px-3 border-bottom pp-tab-nav-wrap" id="ppTabNav">
                <ul class="nav nav-tabs border-bottom-0 flex-nowrap">
                    <li class="nav-item">
                        <a class="nav-link pp-tab-link active px-3" id="ppTab_overview"
                           data-tab="overview" href="javascript:void(0);">
                            <i class="bx bx-package me-1"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pp-tab-link px-3" id="ppTab_transactions"
                           data-tab="transactions" href="javascript:void(0);">
                            <i class="bx bx-receipt me-1"></i>Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pp-tab-link px-3" id="ppTab_stock"
                           data-tab="stock" href="javascript:void(0);">
                            <i class="bx bx-trending-up me-1"></i>Stock
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pp-tab-link px-3" id="ppTab_history"
                           data-tab="history" href="javascript:void(0);">
                            <i class="bx bx-history me-1"></i>History
                        </a>
                    </li>
                </ul>
            </div>

            <!-- ── Tab Content ────────────────────────────────────────────── -->
            <div class="modal-body p-0" id="ppTabContent">
                <div class="pp-tab-pane d-block" id="ppTabContent_overview"></div>
                <div class="pp-tab-pane d-none"  id="ppTabContent_transactions"></div>
                <div class="pp-tab-pane d-none"  id="ppTabContent_stock"></div>
                <div class="pp-tab-pane d-none"  id="ppTabContent_history"></div>
            </div>

        </div>
    </div>
</div>
