<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Vendor Profile Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="vendorProfileModal" tabindex="-1"
     aria-labelledby="vpModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable profile-modal-dialog">
        <div class="modal-content" style="min-height:520px;">

            <!-- ── Modal Header (vtm-banner theme) ──────────────────────── -->
            <div class="vtm-banner flex-shrink-0"
                 style="--vtm-color:#f59e0b;--vtm-bg:#fffbeb;--vtm-icon-bg:rgba(245,158,11,.18);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <span id="vpAvatarInitials" class="cp-avatar-initials">?</span>
                        </div>
                        <div>
                            <div class="vtm-doc-number" id="vpModalTitle">Vendor Profile</div>
                            <div class="vtm-doc-meta" id="vpModalSubtitle"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <!-- Single Transaction dropdown for vendor modules -->
                        <div class="dropdown">
                            <button type="button"
                                    class="btn btn-sm btn-warning cp-qa-btn dropdown-toggle"
                                    id="vpTxDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="bx bx-plus me-1"></i>Transaction
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="vpTxDropdown">
                                <li><h6 class="dropdown-header" style="font-size:.68rem;letter-spacing:.04em;">PURCHASES</h6></li>
                                <li><a class="dropdown-item vp-tx-dd-item" href="javascript:void(0);" data-route="/purchases/create">
                                    <i class="bx bx-package me-2 text-warning"></i>Purchase</a></li>
                                <li><a class="dropdown-item vp-tx-dd-item" href="javascript:void(0);" data-route="/purchaseorders/create">
                                    <i class="bx bx-cart-alt me-2 text-info"></i>Purchase Order</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item vp-tx-dd-item" href="javascript:void(0);" data-route="/payments/create">
                                    <i class="bx bx-dollar-circle me-2 text-success"></i>Pay Vendor</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item vp-tx-dd-item" href="javascript:void(0);" data-route="/purchasereturns/create">
                                    <i class="bx bx-undo me-2 text-danger"></i>Purchase Return</a></li>
                                <li><a class="dropdown-item vp-tx-dd-item" href="javascript:void(0);" data-route="/debitnotes/create">
                                    <i class="bx bx-file-blank me-2 text-secondary"></i>Debit Note</a></li>
                            </ul>
                        </div>
                        <div class="cp-qa-sep"></div>
                        <button type="button" class="vtm-edit-btn" id="vpBtnEdit">
                            <i class="bx bx-edit"></i>Edit
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Tab Nav ────────────────────────────────────────────────── -->
            <div class="px-3 border-bottom" id="vpTabNav"
                 style="background:#fff;overflow-x:auto;white-space:nowrap;">
                <ul class="nav nav-tabs border-bottom-0 flex-nowrap">
                    <li class="nav-item">
                        <a class="nav-link vp-tab-link active px-3" id="vpTab_overview"
                           data-tab="overview" href="javascript:void(0);">
                            <i class="bx bx-store me-1"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link vp-tab-link px-3" id="vpTab_transactions"
                           data-tab="transactions" href="javascript:void(0);">
                            <i class="bx bx-receipt me-1"></i>Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link vp-tab-link px-3" id="vpTab_statement"
                           data-tab="statement" href="javascript:void(0);">
                            <i class="bx bx-file me-1"></i>Statement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link vp-tab-link px-3" id="vpTab_notes"
                           data-tab="notes" href="javascript:void(0);">
                            <i class="bx bx-note me-1"></i>Notes
                        </a>
                    </li>
                </ul>
            </div>

            <!-- ── Tab Content ────────────────────────────────────────────── -->
            <div class="modal-body p-0" id="vpTabContent">
                <div class="vp-tab-pane d-block" id="vpTabContent_overview"></div>
                <div class="vp-tab-pane d-none"  id="vpTabContent_transactions"></div>
                <div class="vp-tab-pane d-none"  id="vpTabContent_statement"></div>
                <div class="vp-tab-pane d-none"  id="vpTabContent_notes"></div>
            </div>

        </div>
    </div>
</div>
