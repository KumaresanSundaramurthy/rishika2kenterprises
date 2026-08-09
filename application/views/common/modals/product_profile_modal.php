<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

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
