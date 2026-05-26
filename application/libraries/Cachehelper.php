<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cachehelper — centralised customer and vendor Upstash cache operations.
 *
 * All methods are fire-and-forget: every Upstash call is wrapped in a
 * try/catch so a cache failure NEVER surfaces as an application error.
 *
 * Autoloaded — available in every controller as $this->cachehelper.
 *
 * Customer methods:
 *   upsertCustomer($uid)  — full refresh of one entry in the bulk search map
 *   removeCustomer($uid)  — remove from bulk map + invalidate individual key
 *   touchCustomer($uid)   — stamp LastTransactionAt (no DB round-trip)
 *
 * Vendor methods:
 *   upsertVendor($uid)    — same as above for vendors
 *   removeVendor($uid)    — removes from bulk map + individual + vendor-products keys
 *   touchVendor($uid)     — stamp LastTransactionAt (no DB round-trip)
 */
class Cachehelper {

    // ── Customer ──────────────────────────────────────────────────────────────

    /**
     * Add or refresh a single customer entry in the org bulk search map.
     * Call after: create, update, status change, opening balance change.
     */
    public function upsertCustomer($customerUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->User->OrgUID;
            $uid    = (int) $customerUID;
            if ($uid <= 0) return;

            $CI->load->model('customers_model');

            $rows = $CI->customers_model->getCustomers([
                'Customers.OrgUID'      => $orgUID,
                'Customers.CustomerUID' => $uid,
            ]);
            if (empty($rows)) return;
            $cust = $rows[0];

            $addrInfo    = $CI->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $uid]);
            $addressList = [];
            foreach ($addrInfo as $addr) {
                $addressList[] = [
                    'AddressType' => $addr->AddressType,
                    'Line1'       => $addr->Line1     ?? '',
                    'Line2'       => $addr->Line2     ?? '',
                    'Pincode'     => $addr->Pincode   ?? '',
                    'CityText'    => $addr->CityText  ?? '',
                    'StateText'   => $addr->StateText ?? '',
                ];
            }

            $obRow          = $CI->customers_model->getCustomerOpeningBalance($orgUID, $uid);
            $openingBalance = $obRow ? (float)$obRow->OpeningBalance : 0.0;
            $openingBalType = $obRow ? $obRow->OpeningBalType        : 'Debit';

            $cacheKey = $CI->redisservice->orgKey('customers');
            $cacheMap = $CI->upstashservice->get($cacheKey);
            if (!is_array($cacheMap)) $cacheMap = [];

            $lastTxAt = $cacheMap[(string)$uid]['LastTransactionAt'] ?? '';

            $entry = [
                'CustomerUID'     => $uid,
                'Name'            => $cust->Name            ?? '',
                'CompanyName'     => $cust->CompanyName     ?? '',
                'ContactPerson'   => $cust->ContactPerson   ?? '',
                'MobileNumber'    => $cust->MobileNumber    ?? '',
                'CountryCode'     => $cust->CountryCode     ?? '',
                'CountryISO2'     => $cust->CountryISO2     ?? '',
                'EmailAddress'    => $cust->EmailAddress    ?? '',
                'CCEmails'        => $cust->CCEmails        ?? '',
                'GSTIN'           => $cust->GSTIN           ?? '',
                'PANNumber'       => $cust->PANNumber       ?? '',
                'CustomerTypeUID' => (int)($cust->CustomerTypeUID  ?? 0),
                'DiscountPercent' => (float)($cust->DiscountPercent ?? 0),
                'CreditPeriod'    => (int)($cust->CreditPeriod     ?? 0),
                'CreditLimit'     => (float)($cust->CreditLimit    ?? 0),
                'OpeningBalance'  => $openingBalance,
                'OpeningBalType'  => $openingBalType,
                'Area'            => $cust->Area  ?? '',
                'Tags'            => $cust->Tags  ?? '',
                'Notes'           => $cust->Notes ?? '',
                'Image'           => $cust->Image ?? '',
                'Address'         => $addressList,
            ];
            if ($lastTxAt) $entry['LastTransactionAt'] = $lastTxAt;

            $cacheMap[(string)$uid] = $entry;
            $CI->upstashservice->set($cacheKey, $cacheMap, 0);

            // Invalidate individual modal cache — stale after any field change
            $CI->upstashservice->del(Upstashservice::keyCustomer($uid));

        } catch (Exception $e) {}
    }

    /**
     * Remove a customer from the org bulk map and invalidate its individual key.
     * Call after soft-delete.
     */
    public function removeCustomer($customerUID) {
        try {
            $CI       =& get_instance();
            $uid      = (int) $customerUID;
            $cacheKey = $CI->redisservice->orgKey('customers');
            $cacheMap = $CI->upstashservice->get($cacheKey);
            if (is_array($cacheMap)) {
                unset($cacheMap[(string)$uid]);
                $CI->upstashservice->set($cacheKey, $cacheMap, 0);
            }
            $CI->upstashservice->del(Upstashservice::keyCustomer($uid));
        } catch (Exception $e) {}
    }

    /**
     * Stamp LastTransactionAt on a customer entry (no DB round-trip).
     * Call after every transaction create / update.
     */
    public function touchCustomer($customerUID) {
        try {
            $CI       =& get_instance();
            $uid      = (string)(int)$customerUID;
            $cacheKey = $CI->redisservice->orgKey('customers');
            $cacheMap = $CI->upstashservice->get($cacheKey);
            if (is_array($cacheMap) && isset($cacheMap[$uid])) {
                $cacheMap[$uid]['LastTransactionAt'] = date('c');
                $CI->upstashservice->set($cacheKey, $cacheMap, 0);
            }
        } catch (Exception $e) {}
    }

    // ── Vendor ────────────────────────────────────────────────────────────────

    /**
     * Add or refresh a single vendor entry in the org bulk search map.
     * Call after: create, update, status change, opening balance change.
     */
    public function upsertVendor($vendorUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->User->OrgUID;
            $uid    = (int) $vendorUID;
            if ($uid <= 0) return;

            $CI->load->model('vendors_model');

            $rows = $CI->vendors_model->getVendors([
                'Vendors.OrgUID'    => $orgUID,
                'Vendors.VendorUID' => $uid,
            ]);
            if (empty($rows)) return;
            $vend = $rows[0];

            $addrInfo    = $CI->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $uid]);
            $addressList = [];
            foreach ($addrInfo as $addr) {
                $addressList[] = [
                    'AddressType' => $addr->AddressType,
                    'Line1'       => $addr->Line1     ?? '',
                    'Line2'       => $addr->Line2     ?? '',
                    'Pincode'     => $addr->Pincode   ?? '',
                    'CityText'    => $addr->CityText  ?? '',
                    'StateText'   => $addr->StateText ?? '',
                ];
            }

            $obRow          = $CI->vendors_model->getVendorOpeningBalance($orgUID, $uid);
            $openingBalance = $obRow ? (float)$obRow->OpeningBalance : 0.0;
            $openingBalType = $obRow ? $obRow->OpeningBalType        : 'Credit';

            $cacheKey = $CI->redisservice->orgKey('vendors');
            $cacheMap = $CI->upstashservice->get($cacheKey);
            if (!is_array($cacheMap)) $cacheMap = [];

            $lastTxAt = $cacheMap[(string)$uid]['LastTransactionAt'] ?? '';

            $entry = [
                'VendorUID'     => $uid,
                'Name'          => $vend->Name          ?? '',
                'CompanyName'   => $vend->CompanyName   ?? '',
                'ContactPerson' => $vend->ContactPerson ?? '',
                'MobileNumber'  => $vend->MobileNumber  ?? '',
                'CountryCode'   => $vend->CountryCode   ?? '',
                'CountryISO2'   => $vend->CountryISO2   ?? '',
                'EmailAddress'  => $vend->EmailAddress  ?? '',
                'GSTIN'         => $vend->GSTIN         ?? '',
                'PANNumber'     => $vend->PANNumber     ?? '',
                'OpeningBalance'=> $openingBalance,
                'OpeningBalType'=> $openingBalType,
                'Area'          => $vend->Area          ?? '',
                'Notes'         => $vend->Notes         ?? '',
                'Image'         => $vend->Image         ?? '',
                'Address'       => $addressList,
            ];
            if ($lastTxAt) $entry['LastTransactionAt'] = $lastTxAt;

            $cacheMap[(string)$uid] = $entry;
            $CI->upstashservice->set($cacheKey, $cacheMap, 0);

            // Invalidate individual modal cache — stale after any field change
            $CI->upstashservice->del(Upstashservice::keyVendor($uid));

        } catch (Exception $e) {}
    }

    /**
     * Remove a vendor from the org bulk map and invalidate individual + products keys.
     * Call after soft-delete.
     */
    public function removeVendor($vendorUID) {
        try {
            $CI       =& get_instance();
            $uid      = (int) $vendorUID;
            $cacheKey = $CI->redisservice->orgKey('vendors');
            $cacheMap = $CI->upstashservice->get($cacheKey);
            if (is_array($cacheMap)) {
                unset($cacheMap[(string)$uid]);
                $CI->upstashservice->set($cacheKey, $cacheMap, 0);
            }
            $CI->upstashservice->del(
                Upstashservice::keyVendor($uid),
                Upstashservice::keyVendorProducts($uid)
            );
        } catch (Exception $e) {}
    }

    /**
     * Stamp LastTransactionAt on a vendor entry (no DB round-trip).
     * Call after every purchase transaction create / update.
     */
    public function touchVendor($vendorUID) {
        try {
            $CI       =& get_instance();
            $uid      = (string)(int)$vendorUID;
            $cacheKey = $CI->redisservice->orgKey('vendors');
            $cacheMap = $CI->upstashservice->get($cacheKey);
            if (is_array($cacheMap) && isset($cacheMap[$uid])) {
                $cacheMap[$uid]['LastTransactionAt'] = date('c');
                $CI->upstashservice->set($cacheKey, $cacheMap, 0);
            }
        } catch (Exception $e) {}
    }
}
