<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cachehelper â€” centralised customer and vendor Upstash cache operations.
 *
 * All methods are fire-and-forget: every Upstash call is wrapped in a
 * try/catch so a cache failure NEVER surfaces as an application error.
 *
 * Autoloaded â€” available in every controller as $this->cachehelper.
 *
 * Customer methods:
 *   upsertCustomer($uid)  â€” full refresh of one entry in the bulk search map
 *   removeCustomer($uid)  â€” remove from bulk map + invalidate individual key
 *   touchCustomer($uid)   â€” stamp LastTransactionAt (no DB round-trip)
 *
 * Vendor methods:
 *   upsertVendor($uid)    â€” same as above for vendors
 *   removeVendor($uid)    â€” removes from bulk map + individual + vendor-products keys
 *   touchVendor($uid)     â€” stamp LastTransactionAt (no DB round-trip)
 */
class Cachehelper {

    // â”€â”€ Customer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Add or refresh a single customer entry in the org bulk search map.
     * Call after: create, update, status change, opening balance change.
     */
    public function upsertCustomer($customerUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
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
            // ClosingBalance = current outstanding after all invoices & payments
            $closingBalance = $obRow ? (float)($obRow->PendingBalance ?? $obRow->OpeningBalance) : 0.0;
            $closingBalType = $obRow ? ($obRow->PendingBalType        ?? $obRow->OpeningBalType)  : 'Debit';

            // On Account = unapplied credits from cancelled invoices (Cancel Only)
            $onAccountRows    = $CI->customers_model->getCustomerOnAccountPayments($orgUID, $uid);
                        $onAccountBalance = round(array_sum(array_column($onAccountRows, 'Amount')), 4);
            // Cache full records for FIFO panel (no AJAX needed on invoice form)
            $onAccountRecords = array_map(function($r) {
                return [
                    'PaymentUID'          => (int)$r['PaymentUID'],
                    'Amount'              => (float)$r['Amount'],
                    'CreatedOn'           => $r['CreatedOn'] ?? '',
                    'SourceInvoiceNumber' => $r['SourceInvoiceNumber'] ?? 'â€”',
                ];
            }, $onAccountRows);

            // Advance = unused excess payments (overpayments not yet applied to any invoice)
            $advanceTotal    = round($CI->customers_model->getCustomerAdvanceTotal($orgUID, $uid), 4);
            // Credit Notes = pending (unApplied) credit notes from SR or cancelled invoices
            $creditNoteTotal = round($CI->customers_model->getCustomerCreditNoteTotal($orgUID, $uid), 4);

            $cacheKey = $CI->redisservice->orgKey('customers');

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
                'SalutationUID'   => (int)($cust->SalutationUID    ?? 0) ?: null,
                'CustomerTypeUID' => (int)($cust->CustomerTypeUID  ?? 0),
                'GroupUID'        => (int)($cust->GroupUID         ?? 0) ?: null,
                'DiscountPercent' => (float)($cust->DiscountPercent ?? 0),
                'CreditPeriod'    => (int)($cust->CreditPeriod     ?? 0),
                'CreditLimit'     => (float)($cust->CreditLimit    ?? 0),
                'ClosingBalance'   => $closingBalance,
                'ClosingBalType'   => $closingBalType,
                'OnAccountBalance' => $onAccountBalance,
                'OnAccountRecords' => $onAccountRecords,
                'AdvanceTotal'    => $advanceTotal,
                'CreditNoteTotal' => $creditNoteTotal,
                'Area'            => $cust->Area  ?? '',
                'Tags'            => $cust->Tags  ?? '',
                'Notes'           => $cust->Notes ?? '',
                'Image'           => $cust->Image ?? '',
                'Address'         => $addressList,
                'LastUpdatedAt'     => date('c'),
            ];

            // Write the updated entry and invalidate the individual modal cache in one pipeline call
            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL',  Upstashservice::keyCustomer($uid)],
            ]);

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
            $CI->upstashservice->hdel($cacheKey, (string)$uid);
            $CI->upstashservice->del(Upstashservice::keyCustomer($uid));
        } catch (Exception $e) {}
    }

    /**
     * Stamp LastTransactionAt on a customer entry in the Upstash hash (no DB round-trip).
     * Reads the existing entry, updates only the timestamp, writes it back.
     * If the customer is not in cache, does nothing (avoids creating a stale partial entry).
     * Call after every transaction create / update.
     */
    public function touchCustomer($customerUID) {
        try {
            $CI       =& get_instance();
            $uid      = (int)$customerUID;
            $cacheKey = $CI->redisservice->orgKey('customers');

            // Read the current entry â€” HGET returns decoded array or null on MISS
            $entry = $CI->upstashservice->hget($cacheKey, (string)$uid);
            if (!is_array($entry) || empty($entry)) return; // not in cache, nothing to do

            // Update only the timestamp field in-place
            $entry['LastUpdatedAt'] = date('c');

            // Write the updated entry back and invalidate the individual modal key
            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL',  Upstashservice::keyCustomer($uid)],
            ]);
        } catch (Exception $e) {}
    }

    // â”€â”€ Vendor â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Add or refresh a single vendor entry in the org bulk search map.
     * Call after: create, update, status change, opening balance change.
     */
    public function upsertVendor($vendorUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
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
                    'State'       => $addr->State     ?? '',
                    'StateText'   => $addr->StateText ?? '',
                ];
            }

            // Issue ReadDb COMMIT first so all subsequent reads see the latest WriteDb-committed data.
            $balFresh       = $CI->vendors_model->getVendorClosingBalanceFresh($uid);

            $obRow          = $CI->vendors_model->getVendorOpeningBalance($orgUID, $uid);
            $openingBalance = $obRow ? (float)$obRow->OpeningBalance : 0.0;
            $openingBalType = $obRow ? $obRow->OpeningBalType        : 'Credit';

            // PendingBalance (set by recalcAndSync) is the authoritative running balance.
            // Fall back to ChartOfAccounts only when no VendOpeningBalanceTbl row exists yet.
            if ($obRow !== null) {
                $closingBalance = (float)$obRow->PendingBalance;
                $closingBalType = (string)$obRow->PendingBalType;
            } else {
                $closingBalance = $balFresh['balance'];
                $closingBalType = $balFresh['balType'];
            }

            $cacheKey = $CI->redisservice->orgKey('vendors');

            $entry = [
                'VendorUID'       => $uid,
                'Name'            => $vend->Name          ?? '',
                'CompanyName'     => $vend->CompanyName   ?? '',
                'ContactPerson'   => $vend->ContactPerson ?? '',
                'MobileNumber'    => $vend->MobileNumber  ?? '',
                'CountryCode'     => $vend->CountryCode   ?? '',
                'CountryISO2'     => $vend->CountryISO2   ?? '',
                'EmailAddress'    => $vend->EmailAddress  ?? '',
                'GSTIN'           => $vend->GSTIN         ?? '',
                'PANNumber'       => $vend->PANNumber     ?? '',
                'OpeningBalance'  => $openingBalance,
                'OpeningBalType'  => $openingBalType,
                'ClosingBalance'  => $closingBalance,
                'ClosingBalType'  => $closingBalType,
                'Area'            => $vend->Area          ?? '',
                'Notes'           => $vend->Notes         ?? '',
                'Image'           => $vend->Image         ?? '',
                'Address'         => $addressList,
                'LastUpdatedAt'     => date('c'),
            ];

            // Write the updated entry and invalidate the individual modal cache in one pipeline call
            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL',  Upstashservice::keyVendor($uid)],
            ]);

        } catch (Exception $e) {
            notifyError($e, 'Cachehelper::upsertVendor');
        }
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
            $CI->upstashservice->hdel($cacheKey, (string)$uid);
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
            $uid      = (int)$vendorUID;
            $cacheKey = $CI->redisservice->orgKey('vendors');

            // Read the current entry â€” HGET returns decoded array or null on MISS
            $entry = $CI->upstashservice->hget($cacheKey, (string)$uid);
            if (!is_array($entry) || empty($entry)) return; // not in cache, nothing to do

            // Update only the timestamp field in-place
            $entry['LastUpdatedAt'] = date('c');

            // Write the updated entry back and invalidate the individual modal key
            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL',  Upstashservice::keyVendor($uid)],
            ]);
        } catch (Exception $e) {}
    }

    // â”€â”€ Product â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Add or refresh a single product entry in the org bulk search map.
     * Call after: create, update, status change, stock adjustment.
     */
    public function upsertProduct($productUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $productUID;
            if ($uid <= 0) return;

            $CI->load->model('products_model');
            $prod = $CI->products_model->getProductForCache($orgUID, $uid);
            if (!$prod) return;

            // NotForSale items must never live in the cache â€” remove if present
            if ((int)($prod->NotForSale ?? 0) === 1) {
                $this->removeProduct($uid);
                return;
            }

            $cacheKey = $CI->redisservice->orgKey('products');
            $entry = [
                'ProductUID'                 => $uid,
                'ItemName'                   => $prod->ItemName                   ?? '',
                'ProductType'                => $prod->ProductType                ?? '',
                'CategoryUID'                => (int)($prod->CategoryUID          ?? 0),
                'CategoryName'               => $prod->CategoryName               ?? '',
                'HSNSACCode'                 => $prod->HSNSACCode                 ?? '',
                'PartNumber'                 => $prod->PartNumber                 ?? '',
                'SKU'                        => $prod->SKU                        ?? '',
                'Description'                => $prod->Description                ?? '',
                'PrimaryUnitUID'             => (int)($prod->PrimaryUnitUID       ?? 0),
                'PrimaryUnitName'            => $prod->PrimaryUnitName            ?? '',
                'MRP'                        => (float)($prod->MRP                ?? 0),
                'SellingPrice'               => (float)($prod->SellingPrice       ?? 0),
                'PurchasePrice'              => (float)($prod->PurchasePrice      ?? 0),
                'SellingProductTaxUID'       => (int)($prod->SellingProductTaxUID ?? 0),
                'PurchasePriceProductTaxUID' => (int)($prod->PurchasePriceProductTaxUID ?? 0),
                'TaxDetailsUID'              => (int)($prod->TaxDetailsUID        ?? 0),
                'TaxPercentage'              => (float)($prod->TaxPercentage      ?? 0),
                'CGST'                       => (float)($prod->CGST               ?? 0),
                'SGST'                       => (float)($prod->SGST               ?? 0),
                'IGST'                       => (float)($prod->IGST               ?? 0),
                'AvailableQuantity'          => (float)($prod->AvailableQuantity  ?? 0),
                'Discount'                   => (float)($prod->Discount           ?? 0),
                'DiscountTypeUID'            => (int)($prod->DiscountTypeUID      ?? 0),
                'LowStockAlertAt'            => (float)($prod->LowStockAlertAt    ?? 0),
                'NotForSale'                 => (int)($prod->NotForSale           ?? 0),
                'IsComboItem'                => (int)($prod->IsComboItem          ?? 0),
                'IsComposite'                => (int)($prod->IsComposite          ?? 0),
                'IsSerialTracked'            => (int)($prod->IsSerialTracked      ?? 0),
                'IsBrandApplicable'          => (int)($prod->IsBrandApplicable    ?? 0),
                'Image'                      => $prod->Image                      ?? '',
                'Variants'                   => $CI->products_model->getProductVariantsForPricelist($uid, $orgUID),
            ];

            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL',  Upstashservice::keyProduct($uid)],
            ]);

        } catch (Exception $e) {}
    }

    /**
     * Remove a product from the org bulk map and invalidate its individual key.
     * Call after soft-delete.
     */
    public function removeProduct($productUID) {
        try {
            $CI       =& get_instance();
            $uid      = (int) $productUID;
            $cacheKey = $CI->redisservice->orgKey('products');
            $CI->upstashservice->hdel($cacheKey, (string)$uid);
            $CI->upstashservice->del(Upstashservice::keyProduct($uid));
        } catch (Exception $e) {}
    }

    // â”€â”€ Composite product â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Write a composite product into the same orgKey('products') hash used by
     * normal products, adding an extra "items" attribute for BOM components.
     *
     * Stored JSON shape (same as upsertProduct + items):
     *   { ...all standard product fields..., "items": [ {"uid":N,"qty":N}, ... ] }
     *
     * Component uid+qty only â€” name/price are always read live from the products
     * hash entries of the child products, so they never go stale.
     *
     * Call after addComboItem / editComboItem (post-commit so ReadDB sees new rows).
     * For delete, call the existing removeProduct() â€” it handles HDEL from the same hash.
     */
    public function upsertComboProduct($productUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $productUID;
            if ($uid <= 0) return;

            $CI->load->model('products_model');
            $prod = $CI->products_model->getProductForCache($orgUID, $uid);
            if (!$prod) return;

            // NotForSale items must never live in the cache â€” remove if present
            if ((int)($prod->NotForSale ?? 0) === 1) {
                $this->removeProduct($uid);
                return;
            }

            $rows  = $CI->products_model->getProductBOM($uid);
            $items = array_map(function ($r) {
                return [
                    'uid' => (int)   $r->ChildProductUID,
                    'qty' => (float) $r->Quantity,
                ];
            }, $rows ?: []);

            $cacheKey = $CI->redisservice->orgKey('products');
            $entry = [
                'ProductUID'                 => $uid,
                'ItemName'                   => $prod->ItemName                   ?? '',
                'ProductType'                => $prod->ProductType                ?? '',
                'CategoryUID'                => (int)($prod->CategoryUID          ?? 0),
                'CategoryName'               => $prod->CategoryName               ?? '',
                'HSNSACCode'                 => $prod->HSNSACCode                 ?? '',
                'PartNumber'                 => $prod->PartNumber                 ?? '',
                'SKU'                        => $prod->SKU                        ?? '',
                'Description'                => $prod->Description                ?? '',
                'PrimaryUnitUID'             => (int)($prod->PrimaryUnitUID       ?? 0),
                'PrimaryUnitName'            => $prod->PrimaryUnitName            ?? '',
                'MRP'                        => (float)($prod->MRP                ?? 0),
                'SellingPrice'               => (float)($prod->SellingPrice       ?? 0),
                'PurchasePrice'              => (float)($prod->PurchasePrice      ?? 0),
                'SellingProductTaxUID'       => (int)($prod->SellingProductTaxUID ?? 0),
                'PurchasePriceProductTaxUID' => (int)($prod->PurchasePriceProductTaxUID ?? 0),
                'TaxDetailsUID'              => (int)($prod->TaxDetailsUID        ?? 0),
                'TaxPercentage'              => (float)($prod->TaxPercentage      ?? 0),
                'CGST'                       => (float)($prod->CGST               ?? 0),
                'SGST'                       => (float)($prod->SGST               ?? 0),
                'IGST'                       => (float)($prod->IGST               ?? 0),
                'AvailableQuantity'          => (float)($prod->AvailableQuantity  ?? 0),
                'Discount'                   => (float)($prod->Discount           ?? 0),
                'DiscountTypeUID'            => (int)($prod->DiscountTypeUID      ?? 0),
                'LowStockAlertAt'            => (float)($prod->LowStockAlertAt    ?? 0),
                'NotForSale'                 => (int)($prod->NotForSale           ?? 0),
                'IsComboItem'                => (int)($prod->IsComboItem          ?? 0),
                'IsComposite'                => (int)($prod->IsComposite          ?? 0),
                'IsSerialTracked'            => (int)($prod->IsSerialTracked      ?? 0),
                'IsBrandApplicable'          => (int)($prod->IsBrandApplicable    ?? 0),
                'Image'                      => $prod->Image                      ?? '',
                'items'                      => $items,
            ];

            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL',  Upstashservice::keyProduct($uid)],
            ]);

        } catch (Exception $e) {}
    }

    // â”€â”€ Customer Group â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Add or refresh a single customer group entry in the org dropdown hash.
     * Call after: create, update, status toggle to active.
     */
    public function upsertCustomerGroup($groupUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $groupUID;
            if ($uid <= 0) return;

            $CI->load->model('customers_model');
            $group = $CI->customers_model->getGroupDropdownDataByUID($orgUID, $uid);
            if (!$group) return;

            $cacheKey = $CI->redisservice->orgKey('customer-groups');
            $CI->upstashservice->hset($cacheKey, (string) $uid, json_encode([
                'GroupUID'  => $uid,
                'GroupName' => $group->GroupName ?? '',
                'GroupCode' => $group->GroupCode ?? '',
                'GroupType' => $group->GroupType ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {}
    }

    /**
     * Remove a customer group from the org dropdown hash.
     * Call after: delete, status toggle to inactive.
     */
    public function removeCustomerGroup($groupUID) {
        try {
            $CI       =& get_instance();
            $uid      = (int) $groupUID;
            $cacheKey = $CI->redisservice->orgKey('customer-groups');
            $CI->upstashservice->hdel($cacheKey, (string) $uid);
        } catch (Exception $e) {}
    }

    // â”€â”€ Vendor Group â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Add or refresh a single vendor group entry in the org dropdown hash.
     * Call after: create, update, status toggle to active.
     */
    public function upsertVendorGroup(int $groupUID): void {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $groupUID;
            if ($uid <= 0) return;

            $CI->load->model('vendors_model');
            $groups = $CI->vendors_model->getActiveVendorGroupsForDropdown($orgUID);
            $group  = null;
            foreach ($groups as $g) {
                if ((int) $g->GroupUID === $uid) { $group = $g; break; }
            }
            if (!$group) return;

            $cacheKey = $CI->redisservice->orgKey('vendor-groups');
            $CI->upstashservice->hset($cacheKey, (string) $uid, json_encode([
                'GroupUID'  => $uid,
                'GroupName' => $group->GroupName ?? '',
                'GroupCode' => $group->GroupCode ?? '',
                'GroupType' => $group->GroupType ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {}
    }

    /**
     * Remove a vendor group from the org dropdown hash.
     * Call after: delete, status toggle to inactive.
     */
    public function removeVendorGroup(int $groupUID): void {
        try {
            $CI       =& get_instance();
            $uid      = (int) $groupUID;
            $cacheKey = $CI->redisservice->orgKey('vendor-groups');
            $CI->upstashservice->hdel($cacheKey, (string) $uid);
        } catch (Exception $e) {}
    }

    // â”€â”€ Category â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Add or refresh a single category entry in the org bulk map.
     * Call after: create, update, status change.
     */
    public function upsertCategory($categoryUID) {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $categoryUID;
            if ($uid <= 0) return;

            $CI->load->model('products_model');
            $rows = $CI->products_model->getCategoriesForCache($orgUID);
            if (empty($rows)) return;

            $cacheKey = $CI->redisservice->orgKey('categories');
            $CI->upstashservice->del($cacheKey);
            $newMap = [];
            foreach ($rows as $row) {
                $rUid = (int)$row->CategoryUID;
                $newMap[(string)$rUid] = [
                    'CategoryUID' => $rUid,
                    'Name'        => $row->Name        ?? '',
                    'Description' => $row->Description ?? '',
                    'Image'       => $row->Image       ?? '',
                ];
            }
            $CI->upstashservice->hmset($cacheKey, $newMap);
            $CI->upstashservice->del(Upstashservice::keyCategory($uid));

        } catch (Exception $e) {}
    }

    /**
     * Remove a category from the org bulk map and invalidate its individual key.
     * Call after soft-delete.
     */
    public function removeCategory($categoryUID) {
        try {
            $CI       =& get_instance();
            $uid      = (int) $categoryUID;
            $cacheKey = $CI->redisservice->orgKey('categories');
            $CI->upstashservice->hdel($cacheKey, (string)$uid);
            $CI->upstashservice->del(
                Upstashservice::keyCategory($uid),
                Upstashservice::keyCategoriesAll()
            );
        } catch (Exception $e) {}
    }

    public function upsertBrand($brandUID): void {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $brandUID;
            if ($uid <= 0) return;

            $CI->load->model('products_model');
            $rows = $CI->products_model->getBrandsForCache($orgUID);
            if (empty($rows)) return;

            $brand = null;
            foreach ($rows as $row) {
                if ((int)$row->BrandUID === $uid) { $brand = $row; break; }
            }
            if (!$brand) return;

            $attRows = $CI->products_model->getEntityAttachments('Brand', $uid, $orgUID);
            $images  = [];
            foreach ($attRows as $att) {
                $images[] = [
                    'path' => '/' . ltrim($att['FilePath'], '/'),
                    'name' => $att['FileName'],
                ];
            }

            $cacheKey = $CI->redisservice->orgKey('brands');
            $CI->upstashservice->pipeline([
                ['HSET', $cacheKey, (string)$uid, json_encode([
                    'BrandUID'    => $uid,
                    'BrandName'   => $brand->BrandName   ?? '',
                    'BrandCode'   => $brand->BrandCode   ?? '',
                    'Description' => $brand->Description ?? '',
                    'Images'      => $images,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ['DEL', Upstashservice::keyBrand($uid)],
            ]);
        } catch (Exception $e) {}
    }

    public function removeBrand($brandUID): void {
        try {
            $CI       =& get_instance();
            $uid      = (int) $brandUID;
            $cacheKey = $CI->redisservice->orgKey('brands');
            $CI->upstashservice->hdel($cacheKey, (string)$uid);
            $CI->upstashservice->del(
                Upstashservice::keyBrand($uid),
                Upstashservice::keyBrandsAll()
            );
        } catch (Exception $e) {}
    }

    public function touchSize($sizeUID): void {
        try {
            $CI     =& get_instance();
            $orgUID = (int) $CI->pageData['JwtData']->Org->OrgUID;
            $uid    = (int) $sizeUID;
            if ($uid <= 0) return;

            $CI->load->model('products_model');
            $rows = $CI->products_model->getSizesForCache($orgUID);
            if (empty($rows)) return;

            $size = null;
            foreach ($rows as $row) {
                if ((int)$row->SizeUID === $uid) { $size = $row; break; }
            }
            if (!$size) return;

            $cacheKey = $CI->redisservice->orgKey('sizes');
            $CI->upstashservice->hset($cacheKey, (string)$uid, json_encode([
                'SizeUID'      => $uid,
                'SizeName'     => $size->SizeName     ?? '',
                'SizeCode'     => $size->SizeCode     ?? '',
                'Length'       => $size->Length       ?? null,
                'Width'        => $size->Width        ?? null,
                'Height'       => $size->Height       ?? null,
                'Depth'        => $size->Depth        ?? null,
                'Diameter'     => $size->Diameter     ?? null,
                'Thickness'    => $size->Thickness    ?? null,
                'Weight'       => $size->Weight       ?? null,
                'DimensionUOM' => $size->DimensionUOM ?? '',
                'WeightUOM'    => $size->WeightUOM    ?? '',
                'Description'  => $size->Description  ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {}
    }

    public function removeSize($sizeUID): void {
        try {
            $CI       =& get_instance();
            $uid      = (int) $sizeUID;
            $cacheKey = $CI->redisservice->orgKey('sizes');
            $CI->upstashservice->hdel($cacheKey, (string)$uid);
        } catch (Exception $e) {}
    }
}
