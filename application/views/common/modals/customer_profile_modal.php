<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Customer Profile Modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="customerProfileModal" tabindex="-1"
     aria-labelledby="cpModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable profile-modal-dialog">
        <div class="modal-content" style="min-height:520px;">

            <!-- ── Modal Header (vtm-banner theme) ──────────────────────── -->
            <div class="vtm-banner flex-shrink-0"
                 style="--vtm-color:#696cff;--vtm-bg:#eff0ff;--vtm-icon-bg:rgba(105,108,255,.18);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <span id="cpAvatarInitials" class="cp-avatar-initials">?</span>
                        </div>
                        <div>
                            <div class="vtm-doc-number" id="cpModalTitle">Customer Profile</div>
                            <div class="vtm-doc-meta" id="cpModalSubtitle"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <!-- Single Transaction dropdown replaces individual shortcut buttons -->
                        <div class="dropdown">
                            <button type="button"
                                    class="btn btn-sm btn-primary cp-qa-btn dropdown-toggle"
                                    id="cpTxDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="bx bx-plus me-1"></i>Transaction
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="cpTxDropdown">
                                <li><h6 class="dropdown-header" style="font-size:.68rem;letter-spacing:.04em;">SALES</h6></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/invoices/create">
                                    <i class="bx bx-file-blank me-2 text-primary"></i>Invoice</a></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/quotations/create">
                                    <i class="bx bx-file me-2 text-warning"></i>Quotation</a></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/salesorders/create">
                                    <i class="bx bx-cart me-2 text-info"></i>Sales Order</a></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/proformainvoices/create">
                                    <i class="bx bx-clipboard me-2 text-secondary"></i>Proforma Invoice</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/payments/create">
                                    <i class="bx bx-dollar-circle me-2 text-success"></i>Record Payment</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/deliverychallans/create">
                                    <i class="bx bx-package me-2 text-secondary"></i>Delivery Challan</a></li>
                                <li><a class="dropdown-item cp-tx-dd-item" href="javascript:void(0);" data-route="/salesreturns/create">
                                    <i class="bx bx-undo me-2 text-danger"></i>Sales Return</a></li>
                            </ul>
                        </div>
                        <div class="cp-qa-sep"></div>
                        <button type="button" class="vtm-edit-btn" id="cpBtnEdit">
                            <i class="bx bx-edit"></i>Edit
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Tab Nav ────────────────────────────────────────────────── -->
            <div class="px-3 border-bottom" id="cpTabNav"
                 style="background:#fff;overflow-x:auto;white-space:nowrap;">
                <ul class="nav nav-tabs border-bottom-0 flex-nowrap">
                    <li class="nav-item">
                        <a class="nav-link cp-tab-link active px-3" id="cpTab_overview"
                           data-tab="overview" href="javascript:void(0);">
                            <i class="bx bx-user-circle me-1"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link cp-tab-link px-3" id="cpTab_transactions"
                           data-tab="transactions" href="javascript:void(0);">
                            <i class="bx bx-receipt me-1"></i>Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link cp-tab-link px-3" id="cpTab_statement"
                           data-tab="statement" href="javascript:void(0);">
                            <i class="bx bx-file me-1"></i>Statement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link cp-tab-link px-3" id="cpTab_notes"
                           data-tab="notes" href="javascript:void(0);">
                            <i class="bx bx-note me-1"></i>Notes
                        </a>
                    </li>
                </ul>
            </div>

            <!-- ── Tab Content ────────────────────────────────────────────── -->
            <div class="modal-body p-0" id="cpTabContent">
                <div class="cp-tab-pane d-block" id="cpTabContent_overview"></div>
                <div class="cp-tab-pane d-none"  id="cpTabContent_transactions"></div>
                <div class="cp-tab-pane d-none"  id="cpTabContent_statement"></div>
                <div class="cp-tab-pane d-none"  id="cpTabContent_notes"></div>
            </div>

        </div>
    </div>
</div>
