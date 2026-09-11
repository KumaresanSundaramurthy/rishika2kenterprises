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
                SO.NetAmount, SO.Status, SO.PaymentMode, SO.PaidOn, SO.Notes,
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

            /* 4. Swap menus safely — delete Plan-sourced rows, keep AddOn rows */
            $this->_swapPlanMenus($orgUID, $newSectorPlanUID, (int)$plan->SectorUID, (int)$plan->PlanUID, $adminRoleUID, $userUID);

            /* 5. Update OrgSubscriptionTbl */
            $now     = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d', strtotime('+' . (int)$plan->DurationDays . ' days')) . ' 23:59:59';
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

    /* ── Private: swap Plan-sourced menus ────────────────────────────────── */

    private function _swapPlanMenus(int $orgUID, int $newSectorPlanUID, int $sectorUID, int $planUID, int $adminRoleUID, int $userUID): void {

        /* Collect current Plan-sourced SubMenu UIDs before deletion */
        $oldSubMenus = $this->ReadDb->select('SubMenuUID')
            ->from('Modules.SubMenusTbl')
            ->where('OrgUID',    $orgUID)
            ->where('Source',    'Plan')
            ->where('IsDeleted', 0)
            ->get()->result();
        $oldSubUIDs = array_column($oldSubMenus, 'SubMenuUID');

        /* Collect current Plan-sourced MainMenu UIDs before deletion */
        $oldMainMenus = $this->ReadDb->select('MainMenuUID')
            ->from('Modules.MainMenusTbl')
            ->where('OrgUID',    $orgUID)
            ->where('Source',    'Plan')
            ->where('IsDeleted', 0)
            ->get()->result();
        $oldMainUIDs = array_column($oldMainMenus, 'MainMenuUID');

        /* Delete child role rows first, then the menus */
        if (!empty($oldSubUIDs)) {
            $this->WriteDb->where_in('SubMenuUID', $oldSubUIDs)->delete('UserRole.RoleSubMenusTbl');
        }
        if (!empty($oldMainUIDs)) {
            $this->WriteDb->where_in('MainMenuUID', $oldMainUIDs)->delete('UserRole.RoleMainMenusTbl');
        }
        $this->WriteDb->where('OrgUID', $orgUID)->where('Source', 'Plan')->delete('Modules.SubMenusTbl');
        $this->WriteDb->where('OrgUID', $orgUID)->where('Source', 'Plan')->delete('Modules.MainMenusTbl');

        /* Re-copy from template filtered by new plan's modules */
        $this->_copyPlanMenus($orgUID, $newSectorPlanUID, $sectorUID, $planUID, $adminRoleUID, $userUID);
    }

    /* ── Private: copy plan menus from template ──────────────────────────── */

    private function _copyPlanMenus(int $orgUID, int $sectorPlanUID, int $sectorUID, int $planUID, int $adminRoleUID, int $userUID): void {

        /* Template org = lowest OrgUID org */
        $tplRow = $this->ReadDb->select('OrgUID')
            ->from('Organisation.OrganisationTbl')
            ->where('IsDeleted', 0)
            ->where('OrgUID !=', $orgUID)
            ->order_by('OrgUID', 'ASC')
            ->limit(1)
            ->get();

        if (!$tplRow || $tplRow->num_rows() === 0) return;
        $tplOrgUID = (int)$tplRow->row()->OrgUID;

        /* Module filter from SectorPlanModulesTbl — if empty, copy all */
        $moduleFilter = $this->ReadDb->select('ModuleUID')
            ->from('Billing.SectorPlanModulesTbl')
            ->where('SectorUID', $sectorUID)
            ->where('PlanUID',   $planUID)
            ->get()->result();
        $allowedModuleUIDs = array_column($moduleFilter, 'ModuleUID');

        /* Main menus from template */
        $mmQuery = $this->ReadDb->select('*')
            ->from('Modules.MainMenusTbl')
            ->where('OrgUID',    $tplOrgUID)
            ->where('IsDeleted', 0)
            ->order_by('Sorting', 'ASC');

        /* If module filter is active, only copy main menus that have at least one matching sub menu */
        if (!empty($allowedModuleUIDs)) {
            $this->ReadDb->db_debug = FALSE;
            $subWithModule = $this->ReadDb->select('DISTINCT MainMenuUID')
                ->from('Modules.SubMenusTbl')
                ->where('OrgUID',    $tplOrgUID)
                ->where('IsDeleted', 0)
                ->where_in('ModuleUID', $allowedModuleUIDs)
                ->get()->result();
            $allowedMainUIDs = array_column($subWithModule, 'MainMenuUID');
            if (empty($allowedMainUIDs)) return;
            $mmQuery->where_in('MainMenuUID', $allowedMainUIDs);
        }

        $mainMenuRows = $mmQuery->get()->result();
        $mainMenuMap  = [];

        foreach ($mainMenuRows as $mm) {
            $this->WriteDb->insert('Modules.MainMenusTbl', [
                'OrgUID'       => $orgUID,
                'Source'       => 'Plan',
                'Name'         => $mm->Name,
                'Icon'         => $mm->Icon ?? '',
                'IsDirectLink' => $mm->IsDirectLink ?? 0,
                'DirectUrl'    => $mm->DirectUrl ?? '',
                'Sorting'      => $mm->Sorting ?? 0,
                'IsActive'     => (int)(bool)$mm->IsActive,
                'IsDeleted'    => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
            ]);
            $newMMUID = (int)$this->WriteDb->insert_id();
            $mainMenuMap[(int)$mm->MainMenuUID] = $newMMUID;

            /* Role main menu — full access for admin */
            $this->WriteDb->insert('UserRole.RoleMainMenusTbl', [
                'RoleUID'     => $adminRoleUID,
                'MainMenuUID' => $newMMUID,
                'Sorting'     => $mm->Sorting ?? 0,
                'CanView'     => 1,
                'CanCreate'   => 1,
                'CanEdit'     => 1,
                'CanDelete'   => 1,
                'IsActive'    => 1,
                'IsDeleted'   => 0,
                'CreatedBy'   => $userUID,
                'UpdatedBy'   => $userUID,
            ]);
            $mainMenuMap['role_' . $newMMUID] = (int)$this->WriteDb->insert_id();
        }

        /* Sub menus from template */
        $smQuery = $this->ReadDb->select('*')
            ->from('Modules.SubMenusTbl')
            ->where('OrgUID',    $tplOrgUID)
            ->where('IsDeleted', 0)
            ->order_by('ParentSubMenuUID', 'ASC')
            ->order_by('Sorting', 'ASC');

        if (!empty($allowedModuleUIDs)) {
            $smQuery->where_in('ModuleUID', $allowedModuleUIDs);
        }

        $subMenuRows = $smQuery->get()->result();
        $subMenuMap  = [];
        $subRoleMMMap = [];

        foreach ($subMenuRows as $sm) {
            $oldMain  = (int)$sm->MainMenuUID;
            $newMMUID = $mainMenuMap[$oldMain] ?? 0;
            if (!$newMMUID) continue;

            $roleMMUID = $mainMenuMap['role_' . $newMMUID] ?? 0;

            $this->WriteDb->insert('Modules.SubMenusTbl', [
                'OrgUID'           => $orgUID,
                'Source'           => 'Plan',
                'MainMenuUID'      => $newMMUID,
                'Name'             => $sm->Name,
                'UrlPath'          => $sm->UrlPath ?? '',
                'ParentSubMenuUID' => null,
                'IsParent'         => $sm->IsParent ?? 0,
                'Icon'             => $sm->Icon ?? '',
                'ModuleUID'        => ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null,
                'Sorting'          => $sm->Sorting ?? 0,
                'IsActive'         => (int)(bool)$sm->IsActive,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ]);
            $newSubUID = (int)$this->WriteDb->insert_id();
            $subMenuMap[(int)$sm->SubMenuUID] = $newSubUID;
            $subRoleMMMap[$newSubUID]          = $roleMMUID;
        }

        /* Role sub menus — full access for admin */
        $sort = 1;
        foreach ($subMenuMap as $newSubUID) {
            $roleMMUID = $subRoleMMMap[$newSubUID] ?? 0;
            $this->WriteDb->insert('UserRole.RoleSubMenusTbl', [
                'RoleUID'         => $adminRoleUID,
                'RoleMainMenuUID' => $roleMMUID,
                'SubMenuUID'      => $newSubUID,
                'Sorting'         => $sort++,
                'CanView'         => 1,
                'CanCreate'       => 1,
                'CanEdit'         => 1,
                'CanDelete'       => 1,
                'IsActive'        => 1,
                'IsDeleted'       => 0,
            ]);
        }
    }

}
