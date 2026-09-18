<?php defined('BASEPATH') OR exit('No direct script access allowed');

class BillingPlan_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    /* ── Current subscription for an org ──────────────────────────────────── */

    public function getOrgSubscription(int $orgUID): object {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('OS.OrgSubUID, OS.OrgUID, OS.SectorPlanUID,
                OS.StartDate, OS.EndDate, OS.Status, OS.AutoRenew,
                OS.GracePeriodDays,
                COALESCE(SP.PlanName, \'Trial\') AS PlanName,
                SP.PlanCode, SP.BillingCycle,
                SPT.Price, SPT.DurationDays, SPT.MaxUsers, SPT.MaxBranches,
                S.SectorUID, S.SectorCode, S.SectorName,
                DATEDIFF(OS.EndDate, CURDATE()) AS DaysRemaining');
            $this->ReadDb->from('Billing.OrgSubscriptionTbl AS OS');
            $this->ReadDb->join('Billing.SectorPlanTbl AS SPT',       'SPT.SectorPlanUID = OS.SectorPlanUID', 'left');
            $this->ReadDb->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID',            'left');
            $this->ReadDb->join('Billing.SectorsTbl AS S',            'S.SectorUID = SPT.SectorUID',         'left');
            $this->ReadDb->where('OS.OrgUID', $orgUID);
            $this->ReadDb->where_not_in('OS.Status', ['Cancelled']);
            $this->ReadDb->order_by('OS.StartDate', 'DESC');
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getOrgSubscription', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    /* ── Available plans for an org's sector ──────────────────────────────── */

    public function getAvailablePlans(int $orgUID): object {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('SPT.SectorPlanUID, SP.PlanUID, SP.PlanName,
                SP.PlanCode, SP.BillingCycle,
                SPT.Price, SPT.DurationDays, SPT.MaxUsers, SPT.MaxBranches,
                SPT.TaxRate, SPT.TaxableAmount, SPT.TaxAmount, SPT.TotalAmount,
                S.SectorUID, S.SectorCode, S.SectorName');
            $this->ReadDb->from('Organisation.OrganisationTbl AS O');
            $this->ReadDb->join('Billing.SectorPlanTbl AS SPT',       'SPT.SectorUID = O.SectorUID AND SPT.IsActive = 1');
            $this->ReadDb->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID AND SP.IsActive = 1');
            $this->ReadDb->join('Billing.SectorsTbl AS S',            'S.SectorUID = O.SectorUID');
            $this->ReadDb->where('O.OrgUID',    $orgUID);
            $this->ReadDb->where('SP.PlanCode !=', 'TRIAL');
            $this->ReadDb->order_by('SPT.Price', 'ASC');
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getAvailablePlans', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = [];
        }
        return $result;
    }

    /* ── Org info by UID (used by public renew page) ─────────────────────── */

    public function getOrgByUID(int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $row = $this->ReadDb->select('OrgUID, Name, EmailAddress, SectorUID')
                ->from('Organisation.OrganisationTbl')
                ->where('OrgUID',    $orgUID)
                ->where('IsDeleted', 0)
                ->limit(1)
                ->get()->row();
            return $row ?: null;
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getOrgByUID', $e);
            return null;
        }
    }

    /* ── Order history for an org ─────────────────────────────────────────── */

    public function getOrderHistory(int $orgUID, int $limit = 20): object {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('SO.OrderUID, SO.RenewalType, SO.OrderDate, SO.DueDate,
                SO.NetAmount, SO.Status, SO.PaymentMode, SO.PaidOn,
                COALESCE(SP.PlanName, \'Trial\') AS PlanName');
            $this->ReadDb->from('Billing.SubscriptionOrdersTbl AS SO');
            $this->ReadDb->join('Billing.SectorPlanTbl AS SPT',       'SPT.SectorPlanUID = SO.SectorPlanUID', 'left');
            $this->ReadDb->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID',            'left');
            $this->ReadDb->where('SO.OrgUID', $orgUID);
            $this->ReadDb->order_by('SO.OrderDate', 'DESC');
            $this->ReadDb->limit($limit);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getOrderHistory', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = [];
        }
        return $result;
    }

    /* ── Change / renew plan ──────────────────────────────────────────────── */

    public function changePlan(int $orgUID, int $newSectorPlanUID, string $renewalType, int $adminRoleUID, int $userUID, array $paymentData = []): object {
        $result = new stdClass();
        try {
            /* 1. Validate the new SectorPlan */
            $this->ReadDb->db_debug = FALSE;
            $planRow = $this->ReadDb->select('SPT.SectorPlanUID, SPT.SectorUID, SPT.PlanUID, SPT.Price, SPT.DurationDays,
                SPT.TaxRate, SPT.TaxableAmount, SPT.TaxAmount, SPT.TotalAmount,
                SP.PlanName, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl AS SPT')
                ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $newSectorPlanUID)
                ->where('SPT.IsActive', 1)
                ->limit(1)
                ->get();

            if (!$planRow || $planRow->num_rows() === 0) {
                throw new Exception('Invalid or inactive plan selected.');
            }
            $plan = $planRow->row();

            /* 2. Get previous SectorPlanUID for audit trail */
            $prevRow = $this->ReadDb->select('SectorPlanUID')
                ->from('Billing.OrgSubscriptionTbl')
                ->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled'])
                ->order_by('StartDate', 'DESC')
                ->limit(1)
                ->get();
            $prevSectorPlanUID = ($prevRow && $prevRow->num_rows() > 0) ? (int)$prevRow->row()->SectorPlanUID : null;

            /* 3. Begin transaction */
            $this->WriteDb->trans_begin();

            /* 4. Diff old vs new plan modules and update the 4 menu tables */
            $this->_applyPlanMenus($orgUID, (int)$plan->SectorUID, (int)$plan->PlanUID, $prevSectorPlanUID, $adminRoleUID, $userUID);

            /* 5. Update OrgSubscriptionTbl */
            $now     = gmdate('Y-m-d H:i:s');
            $endDate = gmdate('Y-m-d', time() + (int)$plan->DurationDays * 86400) . ' 23:59:59';
            $fyValue = billing_fy('long');  // e.g. "2026-27"
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled'])
                ->order_by('StartDate', 'DESC')
                ->limit(1)
                ->update('Billing.OrgSubscriptionTbl', [
                    'SectorPlanUID' => $newSectorPlanUID,
                    'FinancialYear' => $fyValue,
                    'StartDate'     => $now,
                    'EndDate'       => $endDate,
                    'Status'        => 'Active',
                    'UpdatedBy'     => $userUID,
                    'UpdatedOn'     => $now,
                ]);

            $orgSubRow = $this->ReadDb->select('OrgSubUID')
                ->from('Billing.OrgSubscriptionTbl')
                ->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled'])
                ->order_by('StartDate', 'DESC')
                ->limit(1)
                ->get();
            $orgSubUID = ($orgSubRow && $orgSubRow->num_rows() > 0) ? (int)$orgSubRow->row()->OrgSubUID : 0;

            /* 6. Create order row */
            $paidAmount = (float)($paymentData['amount'] ?? $plan->Price ?? 0);

            $this->WriteDb->insert('Billing.SubscriptionOrdersTbl', [
                'OrgUID'            => $orgUID,
                'SectorPlanUID'     => $newSectorPlanUID,
                'OrgSubUID'         => $orgSubUID ?: null,
                'FinancialYear'     => $fyValue,
                'RenewalType'       => $renewalType,
                'DueDate'           => $endDate,
                'Amount'            => $paidAmount,
                'DiscountAmount'    => (float)($paymentData['discount'] ?? 0),
                'TaxAmount'         => (float)($paymentData['tax'] ?? 0),
                'NetAmount'         => $paidAmount,
                'Status'            => !empty($paymentData['paid']) ? 'Paid' : 'Pending',
                'PaymentMode'       => $paymentData['mode']  ?? null,
                'PaidOn'            => !empty($paymentData['paid']) ? date('Y-m-d H:i:s') : null,
                'PrevSectorPlanUID' => $prevSectorPlanUID,
                'CreatedBy'         => $userUID,
            ]);
            $orderUID = (int)$this->WriteDb->insert_id();

            /* 7. Payment row + invoice row for paid orders */
            if (!empty($paymentData['paid']) && $orderUID) {

                /* 7a. SubscriptionPaymentsTbl */
                $this->WriteDb->insert('Billing.SubscriptionPaymentsTbl', [
                    'OrderUID'         => $orderUID,
                    'FinancialYear'    => $fyValue,
                    'PaymentDate'      => date('Y-m-d H:i:s'),
                    'Amount'           => $paidAmount,
                    'Mode'             => $paymentData['mode']             ?? null,
                    'PaymentID'        => $paymentData['payment_id']       ?? null,
                    'GatewayOrderID'   => $paymentData['gateway_order_id'] ?? null,
                    'GatewaySignature' => $paymentData['gateway_signature'] ?? null,
                    'Status'           => 'Success',
                ]);

                /* 7b. SubscriptionInvoicesTbl */
                $orgRow = $this->ReadDb
                    ->select('O.Name, O.GSTIN, O.MobileNumber, O.EmailAddress,
                        A.Line1, A.Line2, A.CityText, A.StateText, A.Pincode')
                    ->from('Organisation.OrganisationTbl AS O')
                    ->join(
                        'Organisation.OrgAddressTbl AS A',
                        "A.OrgUID = O.OrgUID AND A.AddressType = 'Billing' AND A.IsDeleted = 0 AND A.IsActive = 1",
                        'left'
                    )
                    ->where('O.OrgUID', $orgUID)
                    ->limit(1)
                    ->get()->row();

                /* Invoice number — FY short code e.g. "2627" */
                $prefix = 'R2K-' . billing_fy('code') . '-';

                /* MAX approach — immune to deletions unlike COUNT */
                $lastRow = $this->ReadDb->select('MAX(InvoiceNumber) AS LastInv')
                    ->from('Billing.SubscriptionInvoicesTbl')
                    ->like('InvoiceNumber', $prefix, 'after')
                    ->get()->row();
                $lastSeq = 0;
                if (!empty($lastRow->LastInv)) {
                    $parts   = explode('-', $lastRow->LastInv);
                    $lastSeq = (int)end($parts);
                }
                $invoiceNumber = $prefix . str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);

                /* Tax figures — fall back to 18% GST-inclusive computation when DB columns are zero/null */
                $planTaxRate    = (float)((float)$plan->TaxRate    > 0 ? $plan->TaxRate    : 18.00);
                $planTaxableAmt = (float)((float)$plan->TaxableAmount > 0 ? $plan->TaxableAmount : round($paidAmount * 100 / (100 + $planTaxRate), 2));
                $planTaxAmt     = (float)((float)$plan->TaxAmount   > 0 ? $plan->TaxAmount   : round($paidAmount * $planTaxRate / (100 + $planTaxRate), 2));
                $planTotalAmt   = (float)((float)$plan->TotalAmount > 0 ? $plan->TotalAmount : $paidAmount);

                $this->WriteDb->insert('Billing.SubscriptionInvoicesTbl', [
                    'OrderUID'      => $orderUID,
                    'InvoiceNumber' => $invoiceNumber,
                    'FinancialYear' => $fyValue,
                    'InvoiceDate'   => date('Y-m-d'),
                    'OrgName'       => $orgRow ? $orgRow->Name          : '',
                    'GSTIN'         => $orgRow ? ($orgRow->GSTIN        ?: null) : null,
                    'OrgAddress1'   => $orgRow ? ($orgRow->Line1        ?: null) : null,
                    'OrgAddress2'   => $orgRow ? ($orgRow->Line2        ?: null) : null,
                    'OrgCity'       => $orgRow ? ($orgRow->CityText     ?: null) : null,
                    'OrgState'      => $orgRow ? ($orgRow->StateText    ?: null) : null,
                    'OrgPincode'    => $orgRow ? ($orgRow->Pincode      ?: null) : null,
                    'OrgPhone'      => $orgRow ? ($orgRow->MobileNumber ?: null) : null,
                    'OrgEmail'      => $orgRow ? ($orgRow->EmailAddress ?: null) : null,
                    'TaxableAmount' => $planTaxableAmt,
                    'TaxRate'       => $planTaxRate,
                    'TaxAmount'     => $planTaxAmt,
                    'TotalAmount'   => $planTotalAmt,
                    'PDFPath'       => null,
                ]);
                $invoiceUID = (int)$this->WriteDb->insert_id();

                /* Generate PDF and upload to R2 — non-fatal if it fails */
                $this->load->library('billinginvoice');
                $pdfUrl = $this->billinginvoice->generateAndUpload([
                    'invoice_number' => $invoiceNumber,
                    'invoice_date'   => date('Y-m-d'),
                    'financial_year' => $fyValue,
                    'org_name'       => $orgRow ? $orgRow->Name          : '',
                    'gstin'          => $orgRow ? ($orgRow->GSTIN        ?: '') : '',
                    'org_address1'   => $orgRow ? ($orgRow->Line1        ?: '') : '',
                    'org_address2'   => $orgRow ? ($orgRow->Line2        ?: '') : '',
                    'org_city'       => $orgRow ? ($orgRow->CityText     ?: '') : '',
                    'org_state'      => $orgRow ? ($orgRow->StateText    ?: '') : '',
                    'org_pincode'    => $orgRow ? ($orgRow->Pincode      ?: '') : '',
                    'org_phone'      => $orgRow ? ($orgRow->MobileNumber ?: '') : '',
                    'org_email'      => $orgRow ? ($orgRow->EmailAddress ?: '') : '',
                    'plan_name'      => $plan->PlanName,
                    'billing_cycle'  => $plan->BillingCycle ?? '',
                    'renewal_type'   => $renewalType,
                    'taxable_amount' => $planTaxableAmt,
                    'tax_rate'       => $planTaxRate,
                    'tax_amount'     => $planTaxAmt,
                    'total_amount'   => $planTotalAmt,
                    'order_uid'      => $orderUID,
                    'org_uid'        => $orgUID,
                ]);

                if ($pdfUrl) {
                    $this->WriteDb->where('InvoiceUID', $invoiceUID)
                        ->update('Billing.SubscriptionInvoicesTbl', ['PDFPath' => $pdfUrl]);
                }
            }

            if ($this->WriteDb->trans_status() === FALSE) {
                $this->WriteDb->trans_rollback();
                throw new Exception('Plan change failed — database error.');
            }
            $this->WriteDb->trans_commit();

            $result->Error   = FALSE;
            $result->Message = 'Plan changed to ' . $plan->PlanName . ' successfully.';
            $result->EndDate = $endDate;

        } catch (Exception $e) {
            try { $this->WriteDb->trans_rollback(); } catch (Exception $_) {}
            notifyError('BillingPlan_model::changePlan', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
        }
        return $result;
    }

    /* ── Add an add-on module to an org ──────────────────────────────────── */

    public function addAddonModule(int $orgUID, int $moduleUID, int $adminRoleUID, int $userUID): object {
        $result = new stdClass();
        try {
            /* Template = lowest OrgUID org */
            $tplRow = $this->ReadDb->select('OrgUID')
                ->from('Organisation.OrganisationTbl')
                ->where('IsDeleted', 0)
                ->where('OrgUID !=', $orgUID)
                ->order_by('OrgUID', 'ASC')
                ->limit(1)
                ->get();

            if (!$tplRow || $tplRow->num_rows() === 0) {
                throw new Exception('No template org found.');
            }
            $tplOrgUID = (int)$tplRow->row()->OrgUID;

            /* Template sub menus for this module */
            $tplSubMenus = $this->ReadDb->select('*')
                ->from('Modules.SubMenusTbl')
                ->where('OrgUID',    $tplOrgUID)
                ->where('ModuleUID', $moduleUID)
                ->where('IsDeleted', 0)
                ->order_by('Sorting', 'ASC')
                ->get()->result();

            if (empty($tplSubMenus)) {
                throw new Exception('No template menus found for this module.');
            }

            $this->WriteDb->trans_begin();

            $insertedCount = 0;
            foreach ($tplSubMenus as $sm) {
                $oldMMUID = (int)$sm->MainMenuUID;

                /* Find the org's MainMenu that matches the template's MainMenu name */
                $tplMM = $this->ReadDb->select('Name')
                    ->from('Modules.MainMenusTbl')
                    ->where('MainMenuUID', $oldMMUID)
                    ->limit(1)
                    ->get()->row();

                if (!$tplMM) continue;

                $orgMM = $this->ReadDb->select('MainMenuUID')
                    ->from('Modules.MainMenusTbl')
                    ->where('OrgUID',    $orgUID)
                    ->where('Name',      $tplMM->Name)
                    ->where('IsDeleted', 0)
                    ->limit(1)
                    ->get()->row();

                if (!$orgMM) continue; /* Main menu not in this org's plan — skip */

                $newMMUID     = (int)$orgMM->MainMenuUID;
                $roleMMRow    = $this->ReadDb->select('RoleMainMenuUID')
                    ->from('UserRole.RoleMainMenusTbl')
                    ->where('RoleUID',     $adminRoleUID)
                    ->where('MainMenuUID', $newMMUID)
                    ->limit(1)
                    ->get()->row();
                $roleMMUID = $roleMMRow ? (int)$roleMMRow->RoleMainMenuUID : 0;

                /* Check not already inserted */
                $exists = $this->ReadDb->select('SubMenuUID')
                    ->from('Modules.SubMenusTbl')
                    ->where('OrgUID',    $orgUID)
                    ->where('ModuleUID', $moduleUID)
                    ->where('Name',      $sm->Name)
                    ->where('IsDeleted', 0)
                    ->limit(1)
                    ->get()->row();
                if ($exists) continue;

                $this->WriteDb->insert('Modules.SubMenusTbl', [
                    'OrgUID'           => $orgUID,
                    'Source'           => 'AddOn',
                    'MainMenuUID'      => $newMMUID,
                    'Name'             => $sm->Name,
                    'UrlPath'          => $sm->UrlPath ?? '',
                    'ParentSubMenuUID' => null,
                    'IsParent'         => 0,
                    'Icon'             => $sm->Icon ?? '',
                    'ModuleUID'        => $moduleUID,
                    'Sorting'          => $sm->Sorting ?? 999,
                    'IsActive'         => 1,
                    'IsDeleted'        => 0,
                    'CreatedBy'        => $userUID,
                    'UpdatedBy'        => $userUID,
                ]);
                $newSubUID = (int)$this->WriteDb->insert_id();

                /* Auto-wire admin role */
                if ($newSubUID && $roleMMUID) {
                    $this->WriteDb->insert('UserRole.RoleSubMenusTbl', [
                        'RoleUID'         => $adminRoleUID,
                        'RoleMainMenuUID' => $roleMMUID,
                        'SubMenuUID'      => $newSubUID,
                        'Sorting'         => 999,
                        'CanView'         => 1,
                        'CanCreate'       => 1,
                        'CanEdit'         => 1,
                        'CanDelete'       => 1,
                        'IsActive'        => 1,
                        'IsDeleted'       => 0,
                    ]);
                }
                $insertedCount++;
            }

            if ($this->WriteDb->trans_status() === FALSE) {
                $this->WriteDb->trans_rollback();
                throw new Exception('Add-on module insert failed.');
            }
            $this->WriteDb->trans_commit();

            $result->Error   = FALSE;
            $result->Message = $insertedCount . ' sub-menu(s) added as add-on.';
            $result->Count   = $insertedCount;

        } catch (Exception $e) {
            try { $this->WriteDb->trans_rollback(); } catch (Exception $_) {}
            notifyError('BillingPlan_model::addAddonModule', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
        }
        return $result;
    }

    /* ── Private: diff-based plan menu update ───────────────────────────── */

    private function _applyPlanMenus(int $orgUID, int $sectorUID, int $planUID, ?int $prevSectorPlanUID, int $adminRoleUID, int $userUID): void {

        $this->ReadDb->db_debug  = FALSE;
        $this->WriteDb->db_debug = FALSE;

        /* ── 1. New plan's ModuleUIDs ────────────────────────────────────────── */
        $newModRows = $this->ReadDb->select('ModuleUID')
            ->from('Billing.SectorPlanModulesTbl')
            ->where('SectorUID', $sectorUID)
            ->where('PlanUID',   $planUID)
            ->get()->result();
        $newModuleUIDs = array_map('intval', array_column($newModRows, 'ModuleUID'));

        /* ── 2. Old plan's ModuleUIDs ────────────────────────────────────────── */
        $oldModuleUIDs = [];
        if ($prevSectorPlanUID) {
            $prevPlan = $this->ReadDb->select('PlanUID')
                ->from('Billing.SectorPlanTbl')
                ->where('SectorPlanUID', $prevSectorPlanUID)
                ->limit(1)->get()->row();
            if ($prevPlan) {
                $oldModRows = $this->ReadDb->select('ModuleUID')
                    ->from('Billing.SectorPlanModulesTbl')
                    ->where('SectorUID', $sectorUID)
                    ->where('PlanUID',   (int)$prevPlan->PlanUID)
                    ->get()->result();
                $oldModuleUIDs = array_map('intval', array_column($oldModRows, 'ModuleUID'));
            }
        }

        /* ── 3. Compute diff ─────────────────────────────────────────────────── */
        $addedModuleUIDs   = array_values(array_diff($newModuleUIDs, $oldModuleUIDs));
        $removedModuleUIDs = array_values(array_diff($oldModuleUIDs, $newModuleUIDs));

        /* Same plan or same module set — nothing to do */
        if (empty($addedModuleUIDs) && empty($removedModuleUIDs)) return;

        /* ── 4. UPGRADE — added modules ──────────────────────────────────────── */
        if (!empty($addedModuleUIDs)) {

            /* 4a. Reactivate existing SubMenu rows for added modules */
            $this->WriteDb->where('OrgUID',  $orgUID)
                ->where('Source',  'Plan')
                ->where_in('ModuleUID', $addedModuleUIDs)
                ->update('Modules.SubMenusTbl', ['IsActive' => 1, 'IsDeleted' => 0, 'UpdatedBy' => $userUID]);

            /* 4a-parent. Reactivate IsParent submenu header rows that now have ≥ 1 active child */
            $this->WriteDb->query(
                "UPDATE Modules.SubMenusTbl sm
                 SET sm.IsActive = 1, sm.IsDeleted = 0, sm.UpdatedBy = ?
                 WHERE sm.OrgUID   = ?
                   AND sm.Source   = 'Plan'
                   AND sm.IsParent = 1
                   AND EXISTS (
                       SELECT 1 FROM Modules.SubMenusTbl child
                       WHERE child.ParentSubMenuUID = sm.SubMenuUID
                         AND child.OrgUID           = sm.OrgUID
                         AND child.IsActive         = 1
                         AND child.IsDeleted        = 0
                   )",
                [$userUID, $orgUID]
            );

            /* 4a-parent-role. Sync RoleSubMenusTbl for the now-active parent rows (all org roles) */
            $this->WriteDb->query(
                "UPDATE UserRole.RoleSubMenusTbl rs
                 INNER JOIN Modules.SubMenusTbl sm ON sm.SubMenuUID = rs.SubMenuUID
                 SET rs.IsActive = 1, rs.IsDeleted = 0
                 WHERE sm.OrgUID   = ?
                   AND sm.Source   = 'Plan'
                   AND sm.IsParent = 1
                   AND sm.IsActive = 1",
                [$orgUID]
            );

            /* 4-dl. Reactivate direct-link MainMenu rows for added modules (previously deactivated on downgrade) */
            $addedInList = implode(',', $addedModuleUIDs);
            $this->WriteDb->query(
                "UPDATE Modules.MainMenusTbl mm
                 SET mm.IsActive = 1, mm.IsDeleted = 0, mm.UpdatedBy = ?
                 WHERE mm.OrgUID       = ?
                   AND mm.Source       = 'Plan'
                   AND mm.IsDirectLink = 1
                   AND mm.ModuleUID IN ($addedInList)",
                [$userUID, $orgUID]
            );

            /* 4b. Find modules that have NO row in org's SubMenusTbl yet (new sector config entries) */
            $existingMods = $this->ReadDb->select('DISTINCT ModuleUID')
                ->from('Modules.SubMenusTbl')
                ->where('OrgUID',  $orgUID)
                ->where('Source',  'Plan')
                ->where_in('ModuleUID', $addedModuleUIDs)
                ->get()->result();
            $existingModUIDs = array_map('intval', array_column($existingMods, 'ModuleUID'));
            $missingModUIDs  = array_values(array_diff($addedModuleUIDs, $existingModUIDs));

            if (!empty($missingModUIDs)) {
                $this->_insertMissingMenusFromSector($orgUID, $sectorUID, $missingModUIDs, $adminRoleUID, $userUID);
            }

            /* 4c. Reactivate MainMenu rows that now have ≥ 1 active submenu */
            $this->WriteDb->query(
                "UPDATE Modules.MainMenusTbl mm
                 SET mm.IsActive = 1, mm.IsDeleted = 0, mm.UpdatedBy = ?
                 WHERE mm.OrgUID  = ?
                   AND mm.Source  = 'Plan'
                   AND EXISTS (
                       SELECT 1 FROM Modules.SubMenusTbl sm
                       WHERE sm.MainMenuUID = mm.MainMenuUID
                         AND sm.OrgUID      = mm.OrgUID
                         AND sm.IsActive    = 1
                         AND sm.IsDeleted   = 0
                   )",
                [$userUID, $orgUID]
            );

            /* 4d. Reactivate RoleSubMenusTbl rows for added modules (all org roles) */
            $inList = implode(',', $addedModuleUIDs);
            $this->WriteDb->query(
                "UPDATE UserRole.RoleSubMenusTbl rs
                 INNER JOIN Modules.SubMenusTbl sm ON sm.SubMenuUID = rs.SubMenuUID
                 SET rs.IsActive = 1, rs.IsDeleted = 0
                 WHERE sm.OrgUID    = ?
                   AND sm.Source    = 'Plan'
                   AND sm.IsActive  = 1
                   AND sm.IsDeleted = 0
                   AND sm.ModuleUID IN ($inList)",
                [$orgUID]
            );

            /* 4e. Reactivate RoleMainMenusTbl rows whose main menu is now active (all org roles) */
            $this->WriteDb->query(
                "UPDATE UserRole.RoleMainMenusTbl rm
                 INNER JOIN Modules.MainMenusTbl mm ON mm.MainMenuUID = rm.MainMenuUID
                 SET rm.IsActive = 1, rm.IsDeleted = 0
                 WHERE mm.OrgUID    = ?
                   AND mm.Source    = 'Plan'
                   AND mm.IsActive  = 1
                   AND mm.IsDeleted = 0",
                [$orgUID]
            );

            /* 4f. Insert missing RoleSubMenusTbl entries for all org roles (covers brand-new module rows) */
            $this->WriteDb->query(
                "INSERT INTO UserRole.RoleSubMenusTbl
                     (RoleUID, SubMenuUID, Sorting, CanView, CanCreate, CanEdit, CanDelete, IsActive, IsDeleted, CreatedBy, UpdatedBy)
                 SELECT r.RoleUID, sm.SubMenuUID, sm.Sorting, 1, 1, 1, 1, 1, 0, ?, ?
                 FROM Modules.SubMenusTbl sm
                 INNER JOIN UserRole.RolesTbl r ON r.OrgUID = sm.OrgUID AND r.IsDeleted = 0
                 WHERE sm.OrgUID    = ?
                   AND sm.Source    = 'Plan'
                   AND sm.IsActive  = 1
                   AND sm.IsDeleted = 0
                   AND sm.ModuleUID IN ($inList)
                   AND NOT EXISTS (
                       SELECT 1 FROM UserRole.RoleSubMenusTbl rs2
                       WHERE rs2.RoleUID    = r.RoleUID
                         AND rs2.SubMenuUID = sm.SubMenuUID
                   )",
                [$userUID, $userUID, $orgUID]
            );

            /* 4g. Insert missing RoleMainMenusTbl entries for all org roles (covers brand-new module rows) */
            $this->WriteDb->query(
                "INSERT INTO UserRole.RoleMainMenusTbl
                     (RoleUID, MainMenuUID, Sorting, CanView, CanCreate, CanEdit, CanDelete, IsActive, IsDeleted, CreatedBy, UpdatedBy)
                 SELECT r.RoleUID, mm.MainMenuUID, mm.Sorting, 1, 1, 1, 1, 1, 0, ?, ?
                 FROM Modules.MainMenusTbl mm
                 INNER JOIN UserRole.RolesTbl r ON r.OrgUID = mm.OrgUID AND r.IsDeleted = 0
                 WHERE mm.OrgUID    = ?
                   AND mm.Source    = 'Plan'
                   AND mm.IsActive  = 1
                   AND mm.IsDeleted = 0
                   AND (
                       (mm.IsDirectLink = 1 AND mm.ModuleUID IN ($inList))
                       OR EXISTS (
                           SELECT 1 FROM Modules.SubMenusTbl sm
                           WHERE sm.MainMenuUID = mm.MainMenuUID
                             AND sm.OrgUID      = mm.OrgUID
                             AND sm.IsActive    = 1
                             AND sm.IsDeleted   = 0
                             AND sm.ModuleUID IN ($inList)
                       )
                   )
                   AND NOT EXISTS (
                       SELECT 1 FROM UserRole.RoleMainMenusTbl rm2
                       WHERE rm2.RoleUID    = r.RoleUID
                         AND rm2.MainMenuUID = mm.MainMenuUID
                   )",
                [$userUID, $userUID, $orgUID]
            );
        }

        /* ── 5. DOWNGRADE — removed modules ─────────────────────────────────── */
        if (!empty($removedModuleUIDs)) {

            /* 5a. Deactivate SubMenu rows for removed modules */
            $this->WriteDb->where('OrgUID',  $orgUID)
                ->where('Source',  'Plan')
                ->where_in('ModuleUID', $removedModuleUIDs)
                ->update('Modules.SubMenusTbl', ['IsActive' => 0, 'IsDeleted' => 1, 'UpdatedBy' => $userUID]);

            /* 5a-parent. Deactivate IsParent submenu header rows whose all children are now inactive */
            $this->WriteDb->query(
                "UPDATE Modules.SubMenusTbl sm
                 SET sm.IsActive = 0, sm.IsDeleted = 1, sm.UpdatedBy = ?
                 WHERE sm.OrgUID   = ?
                   AND sm.Source   = 'Plan'
                   AND sm.IsParent = 1
                   AND NOT EXISTS (
                       SELECT 1 FROM Modules.SubMenusTbl child
                       WHERE child.ParentSubMenuUID = sm.SubMenuUID
                         AND child.OrgUID           = sm.OrgUID
                         AND child.IsActive         = 1
                         AND child.IsDeleted        = 0
                   )",
                [$userUID, $orgUID]
            );

            /* 5a-parent-role. Sync RoleSubMenusTbl for the now-inactive parent rows (all org roles) */
            $this->WriteDb->query(
                "UPDATE UserRole.RoleSubMenusTbl rs
                 INNER JOIN Modules.SubMenusTbl sm ON sm.SubMenuUID = rs.SubMenuUID
                 SET rs.IsActive = 0, rs.IsDeleted = 1
                 WHERE sm.OrgUID   = ?
                   AND sm.Source   = 'Plan'
                   AND sm.IsParent = 1
                   AND sm.IsActive = 0",
                [$orgUID]
            );

            /* 5b. Deactivate non-direct-link MainMenu rows that now have NO active submenus */
            $this->WriteDb->query(
                "UPDATE Modules.MainMenusTbl mm
                 SET mm.IsActive = 0, mm.IsDeleted = 1, mm.UpdatedBy = ?
                 WHERE mm.OrgUID       = ?
                   AND mm.Source       = 'Plan'
                   AND mm.IsDirectLink = 0
                   AND NOT EXISTS (
                       SELECT 1 FROM Modules.SubMenusTbl sm
                       WHERE sm.MainMenuUID = mm.MainMenuUID
                         AND sm.OrgUID      = mm.OrgUID
                         AND sm.IsActive    = 1
                         AND sm.IsDeleted   = 0
                   )",
                [$userUID, $orgUID]
            );

            /* 5b-dl. Deactivate direct-link MainMenu rows for removed modules */
            $removedInList = implode(',', $removedModuleUIDs);
            $this->WriteDb->query(
                "UPDATE Modules.MainMenusTbl mm
                 SET mm.IsActive = 0, mm.IsDeleted = 1, mm.UpdatedBy = ?
                 WHERE mm.OrgUID       = ?
                   AND mm.Source       = 'Plan'
                   AND mm.IsDirectLink = 1
                   AND mm.ModuleUID IN ($removedInList)",
                [$userUID, $orgUID]
            );

            /* 5c. Deactivate RoleSubMenusTbl rows for removed modules (all org roles) */
            $inList = implode(',', $removedModuleUIDs);
            $this->WriteDb->query(
                "UPDATE UserRole.RoleSubMenusTbl rs
                 INNER JOIN Modules.SubMenusTbl sm ON sm.SubMenuUID = rs.SubMenuUID
                 SET rs.IsActive = 0, rs.IsDeleted = 1
                 WHERE sm.OrgUID    = ?
                   AND sm.Source    = 'Plan'
                   AND sm.ModuleUID IN ($inList)",
                [$orgUID]
            );

            /* 5d. Deactivate RoleMainMenusTbl rows whose main menu is now inactive (all org roles) */
            $this->WriteDb->query(
                "UPDATE UserRole.RoleMainMenusTbl rm
                 INNER JOIN Modules.MainMenusTbl mm ON mm.MainMenuUID = rm.MainMenuUID
                 SET rm.IsActive = 0, rm.IsDeleted = 1
                 WHERE mm.OrgUID   = ?
                   AND mm.Source   = 'Plan'
                   AND mm.IsActive = 0",
                [$orgUID]
            );
        }
    }

    /* ── Private: insert brand-new sector menus that don't exist for org yet ── */

    private function _insertMissingMenusFromSector(int $orgUID, int $sectorUID, array $missingModUIDs, int $adminRoleUID, int $userUID): void {

        /* ── A. Sub-menu-based missing modules ──────────────────────────────── */
        $sectorSubs = $this->ReadDb->select('ss.*, smm.Name AS MainMenuName, smm.Icon AS MainMenuIcon,
                smm.IsDirectLink, smm.DirectUrl, smm.Sorting AS MainMenuSorting')
            ->from('Modules.SectorSubMenusTbl ss')
            ->join('Modules.SectorMainMenusTbl smm', 'smm.SectorMainMenuUID = ss.SectorMainMenuUID')
            ->where('ss.SectorUID',  $sectorUID)
            ->where('ss.IsDeleted',  0)
            ->where_in('ss.ModuleUID', $missingModUIDs)
            ->order_by('ss.Sorting', 'ASC')
            ->get()->result();

        /* ── B. Direct-link missing modules (no submenus) ───────────────────── */
        $directLinkMains = $this->ReadDb->select('SectorMainMenuUID, ModuleUID, Name, Icon, DirectUrl, Sorting')
            ->from('Modules.SectorMainMenusTbl')
            ->where('SectorUID',    $sectorUID)
            ->where('IsDirectLink', 1)
            ->where('IsDeleted',    0)
            ->where_in('ModuleUID', $missingModUIDs)
            ->get()->result();

        if (empty($sectorSubs) && empty($directLinkMains)) return;

        /* Cache resolved UIDs to avoid repeated queries */
        $mainMenuCache   = []; /* MainMenuName → MainMenuUID */
        $roleMMCache     = []; /* MainMenuUID  → RoleMainMenuUID */
        $parentSubCache  = []; /* SectorSubMenuUID (parent) → org SubMenuUID */

        foreach ($sectorSubs as $ss) {
            $mmName = $ss->MainMenuName;

            /* ── Resolve or insert MainMenusTbl row ─────────────────────────── */
            if (!isset($mainMenuCache[$mmName])) {
                $orgMM = $this->ReadDb->select('MainMenuUID')
                    ->from('Modules.MainMenusTbl')
                    ->where('OrgUID',    $orgUID)
                    ->where('Name',      $mmName)
                    ->where('IsDeleted', 0)
                    ->limit(1)->get()->row();

                if ($orgMM) {
                    $newMMUID = (int)$orgMM->MainMenuUID;
                    /* Ensure it is active */
                    $this->WriteDb->where('MainMenuUID', $newMMUID)
                        ->update('Modules.MainMenusTbl', ['IsActive' => 1, 'IsDeleted' => 0, 'UpdatedBy' => $userUID]);
                } else {
                    $this->WriteDb->insert('Modules.MainMenusTbl', [
                        'OrgUID'       => $orgUID,
                        'Source'       => 'Plan',
                        'Name'         => $mmName,
                        'Icon'         => $ss->MainMenuIcon ?? '',
                        'IsDirectLink' => $ss->IsDirectLink ?? 0,
                        'DirectUrl'    => $ss->DirectUrl ?? '',
                        'Sorting'      => $ss->MainMenuSorting ?? 0,
                        'IsActive'     => 1,
                        'IsDeleted'    => 0,
                        'CreatedBy'    => $userUID,
                        'UpdatedBy'    => $userUID,
                    ]);
                    $newMMUID = (int)$this->WriteDb->insert_id();
                }
                $mainMenuCache[$mmName] = $newMMUID;
            }
            $newMMUID = $mainMenuCache[$mmName];

            /* ── Resolve or insert RoleMainMenusTbl row ─────────────────────── */
            if (!isset($roleMMCache[$newMMUID])) {
                $roleMMRow = $this->ReadDb->select('RoleMainMenuUID')
                    ->from('UserRole.RoleMainMenusTbl')
                    ->where('RoleUID',     $adminRoleUID)
                    ->where('MainMenuUID', $newMMUID)
                    ->limit(1)->get()->row();

                if ($roleMMRow) {
                    $roleMMUID = (int)$roleMMRow->RoleMainMenuUID;
                    $this->WriteDb->where('RoleMainMenuUID', $roleMMUID)
                        ->update('UserRole.RoleMainMenusTbl', ['IsActive' => 1, 'IsDeleted' => 0]);
                } else {
                    $this->WriteDb->insert('UserRole.RoleMainMenusTbl', [
                        'RoleUID'     => $adminRoleUID,
                        'MainMenuUID' => $newMMUID,
                        'Sorting'     => $ss->MainMenuSorting ?? 0,
                        'CanView'     => 1,
                        'CanCreate'   => 1,
                        'CanEdit'     => 1,
                        'CanDelete'   => 1,
                        'IsActive'    => 1,
                        'IsDeleted'   => 0,
                        'CreatedBy'   => $userUID,
                        'UpdatedBy'   => $userUID,
                    ]);
                    $roleMMUID = (int)$this->WriteDb->insert_id();
                }
                $roleMMCache[$newMMUID] = $roleMMUID;
            }
            $roleMMUID = $roleMMCache[$newMMUID];

            /* ── Resolve parent submenu row if this child has a parent ─────────── */
            $newParentSubUID   = null;
            $ssmParentSectorUID = ($ss->ParentSectorSubMenuUID !== null && $ss->ParentSectorSubMenuUID !== '')
                                  ? (int)$ss->ParentSectorSubMenuUID : 0;

            if ($ssmParentSectorUID > 0) {
                if (!isset($parentSubCache[$ssmParentSectorUID])) {
                    /* Look up the sector parent row */
                    $sectorParent = $this->ReadDb->select('Name, Icon, UrlPath, Sorting')
                        ->from('Modules.SectorSubMenusTbl')
                        ->where('SectorSubMenuUID', $ssmParentSectorUID)
                        ->limit(1)->get()->row();

                    if ($sectorParent) {
                        $orgParent = $this->ReadDb->select('SubMenuUID')
                            ->from('Modules.SubMenusTbl')
                            ->where('OrgUID',      $orgUID)
                            ->where('MainMenuUID', $newMMUID)
                            ->where('Name',        $sectorParent->Name)
                            ->where('IsParent',    1)
                            ->where('IsDeleted',   0)
                            ->limit(1)->get()->row();

                        if ($orgParent) {
                            $parentSubUID = (int)$orgParent->SubMenuUID;
                            $this->WriteDb->where('SubMenuUID', $parentSubUID)
                                ->update('Modules.SubMenusTbl', ['IsActive' => 1, 'IsDeleted' => 0, 'UpdatedBy' => $userUID]);
                            /* Also reactivate its role row */
                            $this->WriteDb->query(
                                "UPDATE UserRole.RoleSubMenusTbl rs
                                 SET rs.IsActive = 1, rs.IsDeleted = 0
                                 WHERE rs.RoleUID    = ? AND rs.SubMenuUID = ?",
                                [$adminRoleUID, $parentSubUID]
                            );
                        } else {
                            $this->WriteDb->insert('Modules.SubMenusTbl', [
                                'OrgUID'           => $orgUID,
                                'Source'           => 'Plan',
                                'MainMenuUID'      => $newMMUID,
                                'Name'             => $sectorParent->Name,
                                'UrlPath'          => $sectorParent->UrlPath ?? '',
                                'ParentSubMenuUID' => null,
                                'IsParent'         => 1,
                                'Icon'             => $sectorParent->Icon ?? '',
                                'ModuleUID'        => null,
                                'Sorting'          => $sectorParent->Sorting ?? 0,
                                'IsActive'         => 1,
                                'IsDeleted'        => 0,
                                'CreatedBy'        => $userUID,
                                'UpdatedBy'        => $userUID,
                            ]);
                            $parentSubUID = (int)$this->WriteDb->insert_id();
                            if ($parentSubUID && $roleMMUID) {
                                $this->WriteDb->insert('UserRole.RoleSubMenusTbl', [
                                    'RoleUID'         => $adminRoleUID,
                                    'RoleMainMenuUID' => $roleMMUID,
                                    'SubMenuUID'      => $parentSubUID,
                                    'Sorting'         => $sectorParent->Sorting ?? 999,
                                    'CanView'         => 1,
                                    'CanCreate'       => 1,
                                    'CanEdit'         => 1,
                                    'CanDelete'       => 1,
                                    'IsActive'        => 1,
                                    'IsDeleted'       => 0,
                                ]);
                            }
                        }
                        $parentSubCache[$ssmParentSectorUID] = $parentSubUID;
                    }
                }
                $newParentSubUID = $parentSubCache[$ssmParentSectorUID] ?? null;
            }

            /* ── Insert SubMenusTbl row ──────────────────────────────────────── */
            $this->WriteDb->insert('Modules.SubMenusTbl', [
                'OrgUID'           => $orgUID,
                'Source'           => 'Plan',
                'MainMenuUID'      => $newMMUID,
                'Name'             => $ss->Name,
                'UrlPath'          => $ss->UrlPath ?? '',
                'ParentSubMenuUID' => $newParentSubUID,
                'IsParent'         => $ss->IsParent ?? 0,
                'Icon'             => $ss->Icon ?? '',
                'ModuleUID'        => (int)$ss->ModuleUID,
                'Sorting'          => $ss->Sorting ?? 0,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ]);
            $newSubUID = (int)$this->WriteDb->insert_id();

            /* ── Insert RoleSubMenusTbl row ──────────────────────────────────── */
            if ($newSubUID && $roleMMUID) {
                $this->WriteDb->insert('UserRole.RoleSubMenusTbl', [
                    'RoleUID'         => $adminRoleUID,
                    'RoleMainMenuUID' => $roleMMUID,
                    'SubMenuUID'      => $newSubUID,
                    'Sorting'         => $ss->Sorting ?? 999,
                    'CanView'         => 1,
                    'CanCreate'       => 1,
                    'CanEdit'         => 1,
                    'CanDelete'       => 1,
                    'IsActive'        => 1,
                    'IsDeleted'       => 0,
                ]);
            }
        }

        /* ── B loop: direct-link main menus with no submenus ────────────────── */
        foreach ($directLinkMains as $dlMM) {
            $mmName = $dlMM->Name;

            /* Resolve or insert MainMenusTbl */
            $orgMM = $this->ReadDb->select('MainMenuUID')
                ->from('Modules.MainMenusTbl')
                ->where('OrgUID',    $orgUID)
                ->where('Name',      $mmName)
                ->where('IsDeleted', 0)
                ->limit(1)->get()->row();

            if ($orgMM) {
                $newMMUID = (int)$orgMM->MainMenuUID;
                $this->WriteDb->where('MainMenuUID', $newMMUID)
                    ->update('Modules.MainMenusTbl', ['IsActive' => 1, 'IsDeleted' => 0, 'UpdatedBy' => $userUID]);
            } else {
                $this->WriteDb->insert('Modules.MainMenusTbl', [
                    'OrgUID'       => $orgUID,
                    'Source'       => 'Plan',
                    'Name'         => $mmName,
                    'Icon'         => $dlMM->Icon ?? '',
                    'IsDirectLink' => 1,
                    'DirectUrl'    => $dlMM->DirectUrl ?? '',
                    'ModuleUID'    => (int)$dlMM->ModuleUID,
                    'Sorting'      => $dlMM->Sorting ?? 0,
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'CreatedBy'    => $userUID,
                    'UpdatedBy'    => $userUID,
                ]);
                $newMMUID = (int)$this->WriteDb->insert_id();
            }

            /* Resolve or insert RoleMainMenusTbl */
            $roleMMRow = $this->ReadDb->select('RoleMainMenuUID')
                ->from('UserRole.RoleMainMenusTbl')
                ->where('RoleUID',     $adminRoleUID)
                ->where('MainMenuUID', $newMMUID)
                ->limit(1)->get()->row();

            if ($roleMMRow) {
                $this->WriteDb->where('RoleMainMenuUID', (int)$roleMMRow->RoleMainMenuUID)
                    ->update('UserRole.RoleMainMenusTbl', ['IsActive' => 1, 'IsDeleted' => 0]);
            } else {
                $this->WriteDb->insert('UserRole.RoleMainMenusTbl', [
                    'RoleUID'     => $adminRoleUID,
                    'MainMenuUID' => $newMMUID,
                    'Sorting'     => $dlMM->Sorting ?? 0,
                    'CanView'     => 1,
                    'CanCreate'   => 1,
                    'CanEdit'     => 1,
                    'CanDelete'   => 1,
                    'IsActive'    => 1,
                    'IsDeleted'   => 0,
                    'CreatedBy'   => $userUID,
                    'UpdatedBy'   => $userUID,
                ]);
            }
        }
    }

    /* ══════════════════════════════════════════════════════════════════════════
       Plan-change helpers (upgrade / downgrade modal)
    ══════════════════════════════════════════════════════════════════════════ */

    /* ── 5-day change lock (GST invoice already issued) ────────────────────── */

    public function getPlanChangeLockStatus(int $orgUID): object {
        $result           = new stdClass();
        $result->IsLocked = false;
        $result->UnlocksAt     = null;
        $result->DaysRemaining = 0;
        try {
            $this->ReadDb->db_debug = FALSE;
            $cutoff = date('Y-m-d H:i:s', strtotime('-5 days'));

            /* Check immediate plan changes in order history (OrderDate = when order was placed) */
            $row = $this->ReadDb->select('OrderDate AS CreatedOn')
                ->from('Billing.SubscriptionOrdersTbl')
                ->where('OrgUID', $orgUID)
                ->where_in('RenewalType', ['Upgrade', 'Downgrade'])
                ->where('OrderDate >=', $cutoff)
                ->order_by('OrderDate', 'DESC')
                ->limit(1)
                ->get()->row();

            /* Also check scheduled changes */
            if (!$row) {
                $q = $this->ReadDb->select('CreatedOn')
                    ->from('Billing.ScheduledPlanChangeTbl')
                    ->where('OrgUID', $orgUID)
                    ->where_in('Status', ['Pending', 'Activated'])
                    ->where('CreatedOn >=', $cutoff)
                    ->order_by('CreatedOn', 'DESC')
                    ->limit(1)
                    ->get();
                $row = $q ? $q->row() : null;
            }

            if ($row) {
                $unlocksAt = date('Y-m-d H:i:s', strtotime($row->CreatedOn . ' +5 days'));
                if (strtotime($unlocksAt) > time()) {
                    $result->IsLocked      = true;
                    $result->UnlocksAt     = $unlocksAt;
                    $result->DaysRemaining = (int)ceil((strtotime($unlocksAt) - time()) / 86400);
                }
            }
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getPlanChangeLockStatus', $e);
        }
        return $result;
    }

    /* ── Pending scheduled plan change for org ─────────────────────────────── */

    public function getScheduledPlanChange(int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $q = $this->ReadDb->select(
                    'SC.ScheduledChangeUID, SC.ChangeType, SC.BillingOption,
                     SC.ScheduledStartDate, SC.ScheduledEndDate, SC.NewSectorPlanUID,
                     SP.PlanName AS NewPlanName')
                ->from('Billing.ScheduledPlanChangeTbl SC')
                ->join('Billing.SectorPlanTbl SPT', 'SPT.SectorPlanUID = SC.NewSectorPlanUID', 'left')
                ->join('Billing.SubscriptionPlansTbl SP', 'SP.PlanUID = SPT.PlanUID', 'left')
                ->where('SC.OrgUID', $orgUID)
                ->where('SC.Status', 'Pending')
                ->where('SC.OrderUID IS NOT NULL', null, false)
                ->order_by('SC.CreatedOn', 'DESC')
                ->limit(1)
                ->get();
            return ($q ? $q->row() : null) ?: null;
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getScheduledPlanChange', $e);
            return null;
        }
    }

    /* ── Module comparison between current and target plan ─────────────────── */

    public function getModuleComparison(int $orgUID, int $newSectorPlanUID): object {
        $result          = new stdClass();
        $result->Added   = [];
        $result->Removed = [];
        $result->HasData = false;
        try {
            $this->ReadDb->db_debug = FALSE;

            /* Current subscription */
            $sub = $this->getOrgSubscription($orgUID);
            if ($sub->Error || !$sub->Data) return $result;
            $current = $sub->Data;

            /* New plan's PlanUID + SectorUID */
            $newPlanRow = $this->ReadDb->select('PlanUID, SectorUID')
                ->from('Billing.SectorPlanTbl')
                ->where('SectorPlanUID', $newSectorPlanUID)
                ->limit(1)->get()->row();
            if (!$newPlanRow) return $result;

            $sectorUID    = (int)$newPlanRow->SectorUID;
            $newPlanUID   = (int)$newPlanRow->PlanUID;

            /* Current plan UID */
            $curPlanRow = $this->ReadDb->select('PlanUID')
                ->from('Billing.SectorPlanTbl')
                ->where('SectorPlanUID', (int)$current->SectorPlanUID)
                ->limit(1)->get()->row();
            $curPlanUID = $curPlanRow ? (int)$curPlanRow->PlanUID : 0;

            /* Module UIDs for each plan */
            $fetchModules = function(int $planUID) use ($sectorUID): array {
                $q = $this->ReadDb->select('M.ModuleUID, M.DisplayName, M.Description')
                    ->from('Billing.SectorPlanModulesTbl PM')
                    ->join('Modules.ModuleTbl M', 'M.ModuleUID = PM.ModuleUID', 'left')
                    ->where('PM.SectorUID', $sectorUID)
                    ->where('PM.PlanUID', $planUID)
                    ->get();
                return ($q ? $q->result() : null) ?: [];
            };

            $curModules = $fetchModules($curPlanUID);
            $newModules = $fetchModules($newPlanUID);

            $curUIDs = array_column($curModules, 'ModuleUID');
            $newUIDs = array_column($newModules, 'ModuleUID');

            foreach ($newModules as $m) {
                if (!in_array($m->ModuleUID, $curUIDs)) $result->Added[] = $m;
            }
            foreach ($curModules as $m) {
                if (!in_array($m->ModuleUID, $newUIDs)) $result->Removed[] = $m;
            }

            $result->HasData = !empty($curModules) || !empty($newModules);
        } catch (Exception $e) {
            notifyError('BillingPlan_model::getModuleComparison', $e);
        }
        return $result;
    }

    /* ── Calculate all plan-change option details (amounts + dates) ─────────── */

    public function calculatePlanChangeDetails(int $orgUID, int $newSectorPlanUID): object {
        $result        = new stdClass();
        $result->Error = false;
        try {
            $this->ReadDb->db_debug = FALSE;

            $sub = $this->getOrgSubscription($orgUID);
            if ($sub->Error || !$sub->Data) {
                $result->Error   = true;
                $result->Message = 'No active subscription found.';
                return $result;
            }
            $current = $sub->Data;

            $newPlanQ = $this->ReadDb->select(
                    'SPT.SectorPlanUID, SPT.PlanUID, SPT.SectorUID,
                     SPT.Price, SPT.TaxableAmount, SPT.TaxAmount, SPT.TotalAmount,
                     SPT.DurationDays, SPT.TaxRate,
                     SP.PlanName, SP.PlanCode, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl SPT')
                ->join('Billing.SubscriptionPlansTbl SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $newSectorPlanUID)
                ->where('SPT.IsActive', 1)
                ->limit(1)->get();
            $newPlan = $newPlanQ ? $newPlanQ->row() : null;

            if (!$newPlan) {
                $result->Error   = true;
                $result->Message = 'Plan not found.';
                return $result;
            }

            $today           = date('Y-m-d');
            $currentEndDate  = date('Y-m-d', strtotime($current->EndDate));
            $remainingDays   = max(0, (int)ceil((strtotime($currentEndDate) - strtotime($today)) / 86400));
            $curDuration     = max(1, (int)($current->DurationDays ?? 30));
            $dailyRateCur    = (float)$current->Price / $curDuration;
            $remainingCredit = round($dailyRateCur * $remainingDays, 2);

            /* Use pre-calculated tax amounts from SectorPlanTbl (Price is GST-inclusive) */
            $newTotalAmount   = (float)$newPlan->TotalAmount;
            $newTaxableAmount = (float)$newPlan->TaxableAmount;
            $newTaxAmount     = (float)$newPlan->TaxAmount;
            $newDuration      = max(1, (int)$newPlan->DurationDays);
            $taxRate          = max(0, (float)($newPlan->TaxRate ?? 18));
            $dailyRateNew     = $newTotalAmount / $newDuration;
            $isUpgrade        = $newTotalAmount > (float)$current->Price;

            /* Split an inclusive total into base + tax (back-calculation from GST-inclusive amount) */
            $splitGST = function(float $inclusiveTotal) use ($taxRate): array {
                $base = round($inclusiveTotal * 100 / (100 + $taxRate), 2);
                $tax  = round($inclusiveTotal - $base, 2);
                return ['base' => $base, 'tax' => $tax, 'total' => $inclusiveTotal, 'rate' => $taxRate];
            };

            $result->ChangeType      = $isUpgrade ? 'Upgrade' : 'Downgrade';
            $result->RemainingDays   = $remainingDays;
            $result->RemainingCredit = $remainingCredit;
            $result->CurrentEndDate  = $currentEndDate;
            $result->CurrentPlan     = $current;
            $result->NewPlan         = $newPlan;

            /* ── Option UA: Immediate upgrade, pro-rated ── */
            $ua_net   = max(0, round($newTotalAmount - $remainingCredit, 2));
            $ua_split = $splitGST($ua_net);
            $result->OptionUA = (object)[
                'StartDate'   => $today,
                'EndDate'     => date('Y-m-d', strtotime("+{$newDuration} days")),
                'BaseAmount'  => $ua_split['base'],
                'TaxAmount'   => $ua_split['tax'],
                'TaxRate'     => $taxRate,
                'TotalAmount' => $ua_split['total'],
                'CreditUsed'  => min($remainingCredit, $newTotalAmount),
                'Recommended' => true,
            ];

            /* ── Option UB: Upgrade after current plan expires ── */
            $ub_start = date('Y-m-d', strtotime($currentEndDate . ' +1 day'));
            $result->OptionUB = (object)[
                'StartDate'   => $ub_start,
                'EndDate'     => date('Y-m-d', strtotime($ub_start . " +{$newDuration} days")),
                'BaseAmount'  => $newTaxableAmount,
                'TaxAmount'   => $newTaxAmount,
                'TaxRate'     => $taxRate,
                'TotalAmount' => $newTotalAmount,
                'CreditUsed'  => 0,
                'Recommended' => false,
            ];

            /* ── Option DA: Immediate downgrade, extend days (sub-option 1) ── */
            $bonusDays = ($dailyRateNew > 0) ? (int)floor($remainingCredit / $dailyRateNew) : 0;
            $da_days   = $newDuration + $bonusDays;
            $result->OptionDA = (object)[
                'StartDate'   => $today,
                'EndDate'     => date('Y-m-d', strtotime("+{$da_days} days")),
                'BaseAmount'  => $newTaxableAmount,
                'TaxAmount'   => $newTaxAmount,
                'TaxRate'     => $taxRate,
                'TotalAmount' => $newTotalAmount,
                'BonusDays'   => $bonusDays,
                'TotalDays'   => $da_days,
                'CreditUsed'  => $remainingCredit,
                'Recommended' => false,
            ];

            /* ── Option DB: Immediate downgrade, reduce payment (sub-option 2) ── */
            $db_net   = max(0, round($newTotalAmount - $remainingCredit, 2));
            $db_split = $splitGST($db_net);
            $result->OptionDB = (object)[
                'StartDate'   => $today,
                'EndDate'     => date('Y-m-d', strtotime("+{$newDuration} days")),
                'BaseAmount'  => $db_split['base'],
                'TaxAmount'   => $db_split['tax'],
                'TaxRate'     => $taxRate,
                'TotalAmount' => $db_split['total'],
                'CreditUsed'  => min($remainingCredit, $newTotalAmount),
                'Recommended' => true,
            ];

            /* ── Option DC: Downgrade after current plan expires ── */
            $dc_start = date('Y-m-d', strtotime($currentEndDate . ' +1 day'));
            $result->OptionDC = (object)[
                'StartDate'   => $dc_start,
                'EndDate'     => date('Y-m-d', strtotime($dc_start . " +{$newDuration} days")),
                'BaseAmount'  => $newTaxableAmount,
                'TaxAmount'   => $newTaxAmount,
                'TaxRate'     => $taxRate,
                'TotalAmount' => $newTotalAmount,
                'CreditUsed'  => 0,
                'Recommended' => false,
            ];

        } catch (Exception $e) {
            notifyError('BillingPlan_model::calculatePlanChangeDetails', $e);
            $result->Error   = true;
            $result->Message = $e->getMessage();
        }
        return $result;
    }

    /* ── Schedule a future plan change (options UB and DC) ─────────────────── */

    public function schedulePlanChange(int $orgUID, int $newSectorPlanUID, string $changeType,
            string $billingOption, string $scheduledStart, string $scheduledEnd,
            int $adminRoleUID, int $userUID, array $paymentData = []): object {
        $result        = new stdClass();
        $result->Error = false;
        try {
            $this->ReadDb->db_debug  = FALSE;
            $this->WriteDb->db_debug = FALSE;

            /* Validate plan */
            $plan = $this->ReadDb->select(
                    'SPT.SectorPlanUID, SPT.SectorUID, SPT.PlanUID,
                     SPT.Price, SPT.DurationDays, SPT.TaxRate,
                     SPT.TaxableAmount, SPT.TaxAmount, SPT.TotalAmount,
                     SP.PlanName, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl SPT')
                ->join('Billing.SubscriptionPlansTbl SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $newSectorPlanUID)
                ->where('SPT.IsActive', 1)
                ->limit(1)->get()->row();

            if (!$plan) throw new ValidationException('Invalid plan selected.');

            $this->WriteDb->trans_begin();

            $fyValue     = billing_fy('long');
            $paidAmount  = (float)($paymentData['amount'] ?? $plan->Price ?? 0);

            /* Get current OrgSubUID for the order */
            $curSub = $this->ReadDb->select('OrgSubUID, SectorPlanUID')
                ->from('Billing.OrgSubscriptionTbl')
                ->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled', 'Superseded'])
                ->order_by('StartDate', 'DESC')
                ->limit(1)->get()->row();

            /* Create order row (payment is collected today) */
            $this->WriteDb->insert('Billing.SubscriptionOrdersTbl', [
                'OrgUID'            => $orgUID,
                'SectorPlanUID'     => $newSectorPlanUID,
                'OrgSubUID'         => $curSub ? (int)$curSub->OrgSubUID : null,
                'FinancialYear'     => $fyValue,
                'RenewalType'       => $changeType,
                'OrderDate'         => date('Y-m-d H:i:s'),
                'DueDate'           => $scheduledEnd . ' 23:59:59',
                'Amount'            => $paidAmount,
                'DiscountAmount'    => (float)($paymentData['discount'] ?? 0),
                'TaxAmount'         => (float)($paymentData['tax'] ?? 0),
                'NetAmount'         => $paidAmount,
                'Status'            => !empty($paymentData['paid']) ? 'Paid' : 'Pending',
                'PaymentMode'       => $paymentData['mode']  ?? null,
                'PaidOn'            => !empty($paymentData['paid']) ? date('Y-m-d H:i:s') : null,
                'PrevSectorPlanUID' => $curSub ? (int)$curSub->SectorPlanUID : null,
                'Notes'             => $paymentData['notes'] ?? null,
                'CreatedBy'         => $userUID,
            ]);
            $orderUID = (int)$this->WriteDb->insert_id();

            /* Insert scheduled change record */
            $this->WriteDb->insert('Billing.ScheduledPlanChangeTbl', [
                'OrgUID'               => $orgUID,
                'CurrentSectorPlanUID' => $curSub ? (int)$curSub->SectorPlanUID : $newSectorPlanUID,
                'NewSectorPlanUID'     => $newSectorPlanUID,
                'ChangeType'           => $changeType,
                'BillingOption'        => $billingOption,
                'ScheduledStartDate'   => $scheduledStart,
                'ScheduledEndDate'     => $scheduledEnd,
                'Status'               => 'Pending',
                'OrderUID'             => $orderUID,
                'Notes'                => $paymentData['notes'] ?? null,
                'CreatedBy'            => $userUID,
            ]);

            /* Payment row if paid today */
            if (!empty($paymentData['paid']) && $orderUID) {
                $this->WriteDb->insert('Billing.SubscriptionPaymentsTbl', [
                    'OrderUID'      => $orderUID,
                    'FinancialYear' => $fyValue,
                    'PaymentDate'   => date('Y-m-d H:i:s'),
                    'Amount'        => $paidAmount,
                    'Mode'          => $paymentData['mode']  ?? null,
                    'PaymentID'     => null,
                    'Status'        => 'Success',
                ]);
            }

            if ($this->WriteDb->trans_status() === FALSE) {
                $this->WriteDb->trans_rollback();
                throw new Exception('Scheduled plan change failed — database error.');
            }
            $this->WriteDb->trans_commit();

            $result->Error        = false;
            $result->Message      = 'Plan change scheduled. ' . $plan->PlanName . ' will activate on ' . $scheduledStart . '.';
            $result->ScheduledStart = $scheduledStart;
            $result->ScheduledEnd   = $scheduledEnd;

        } catch (ValidationException $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            try { $this->WriteDb->trans_rollback(); } catch (Exception $_) {}
            notifyError('BillingPlan_model::schedulePlanChange', $e);
            $result->Error   = true;
            $result->Message = $e->getMessage();
        }
        return $result;
    }

    /* ── Activate a pending scheduled plan (called from middleware on login) ── */

    public function activateScheduledPlan(int $scheduledChangeUID, int $orgUID, int $adminRoleUID): object {
        $result        = new stdClass();
        $result->Error = false;
        try {
            $this->ReadDb->db_debug  = FALSE;
            $this->WriteDb->db_debug = FALSE;

            /* Fetch the scheduled change */
            $sc = $this->ReadDb->select('*')
                ->from('Billing.ScheduledPlanChangeTbl')
                ->where('ScheduledChangeUID', $scheduledChangeUID)
                ->where('OrgUID', $orgUID)
                ->where('Status', 'Pending')
                ->limit(1)->get()->row();

            if (!$sc) {
                $result->Error   = true;
                $result->Message = 'Scheduled change not found or already activated.';
                return $result;
            }

            /* Get new plan details */
            $plan = $this->ReadDb->select('SPT.*, SP.PlanName, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl SPT')
                ->join('Billing.SubscriptionPlansTbl SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', (int)$sc->NewSectorPlanUID)
                ->limit(1)->get()->row();

            if (!$plan) {
                $result->Error   = true;
                $result->Message = 'Scheduled plan no longer available.';
                return $result;
            }

            $this->WriteDb->trans_begin();

            $now      = gmdate('Y-m-d H:i:s');
            $startDt  = $sc->ScheduledStartDate . ' 00:00:00';
            $endDt    = $sc->ScheduledEndDate   . ' 23:59:59';
            $fyValue  = billing_fy('long');
            $createdBy = (int)($sc->CreatedBy ?? 0);

            /* 1. Mark existing subscription row as Superseded */
            $this->WriteDb->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled', 'Superseded'])
                ->update('Billing.OrgSubscriptionTbl', [
                    'Status'    => 'Superseded',
                    'UpdatedBy' => $createdBy,
                    'UpdatedOn' => $now,
                    'ClosedOn'  => $now,
                ]);

            /* 2. Insert new subscription row */
            $this->WriteDb->insert('Billing.OrgSubscriptionTbl', [
                'OrgUID'        => $orgUID,
                'SectorPlanUID' => (int)$sc->NewSectorPlanUID,
                'FinancialYear' => $fyValue,
                'StartDate'     => $startDt,
                'EndDate'       => $endDt,
                'Status'        => 'Active',
                'CreatedBy'     => $createdBy,
                'CreatedOn'     => $now,
                'UpdatedBy'     => $createdBy,
                'UpdatedOn'     => $now,
            ]);
            $newOrgSubUID = (int)$this->WriteDb->insert_id();

            /* 3. Update order to link to new OrgSubUID */
            if ($sc->OrderUID) {
                $this->WriteDb->where('OrderUID', (int)$sc->OrderUID)
                    ->update('Billing.SubscriptionOrdersTbl', ['OrgSubUID' => $newOrgSubUID]);
            }

            /* 4. Apply menu/module changes */
            $this->_applyPlanMenus(
                $orgUID,
                (int)$plan->SectorUID,
                (int)$plan->PlanUID,
                (int)$sc->CurrentSectorPlanUID,
                $adminRoleUID,
                $createdBy
            );

            /* 5. Mark scheduled change as activated */
            $this->WriteDb->where('ScheduledChangeUID', $scheduledChangeUID)
                ->update('Billing.ScheduledPlanChangeTbl', [
                    'Status'      => 'Activated',
                    'UpdatedBy'   => $createdBy,
                    'UpdatedOn'   => $now,
                    'ActivatedOn' => $now,
                ]);

            if ($this->WriteDb->trans_status() === FALSE) {
                $this->WriteDb->trans_rollback();
                throw new Exception('Scheduled plan activation failed — database error.');
            }
            $this->WriteDb->trans_commit();

            $result->Error   = false;
            $result->Message = 'Scheduled plan activated: ' . $plan->PlanName;

        } catch (Exception $e) {
            try { $this->WriteDb->trans_rollback(); } catch (Exception $_) {}
            notifyError('BillingPlan_model::activateScheduledPlan', $e);
            $result->Error   = true;
            $result->Message = $e->getMessage();
        }
        return $result;
    }

}
