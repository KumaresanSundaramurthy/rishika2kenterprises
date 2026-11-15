<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Formvalidation_model extends CI_Model {

	function __construct() {
        parent::__construct();

		$this->load->library('form_validation');
        $this->load->helper('security');

    }

    public function validateForm(array $data): string {

        $this->form_validation->set_data($data);

        $dd['FirstName'] = array('field' => 'FirstName', 'label' => 'First Name', 'rules' => 'trim|required');
        $dd['LastName'] = array('field' => 'LastName', 'label' => 'Last Name', 'rules' => 'trim|alpha_numeric');
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => 'trim|regex_match[/^[0-9]{10}$/]');
        $dd['EmailAddress'] = array('field' => 'EmailAddress', 'label' => 'Email Address', 'rules' => 'trim|valid_email');
        $dd['UserName'] = array('field' => 'UserName', 'label' => 'User Name', 'rules' => 'trim|required');
        $dd['Password'] = array('field' => 'Password', 'label' => 'Password', 'rules' => 'trim|required|min_length[6]');

        $dd['UserUID'] = array('field' => 'UserUID', 'label' => 'User Information', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['NewPassword'] = array('field' => 'NewPassword', 'label' => 'New Password', 'rules' => 'trim|required|min_length[6]');
        $dd['ConfirmPassword'] = array('field' => 'ConfirmPassword', 'label' => 'Confirm Password', 'rules' => 'trim|required|matches[NewPassword]');

        $config = array();

        foreach($data as $key=>$value) {
            if (array_key_exists($key, $dd)) {
                array_push($config, $dd[$key]);
            }
        }

        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() == FALSE) {
            return validation_errors();
        } else {
            return '';
        }

    }

    public function transactionValidateForm(array $data): string {

        $this->form_validation->set_data($data);

        $dd['customerSearch']    = ['field' => 'customerSearch',    'label' => 'Customer',          'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transPrefixSelect'] = ['field' => 'transPrefixSelect', 'label' => 'Prefix',            'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transNumber']       = ['field' => 'transNumber',       'label' => 'Transaction Number', 'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transDate']         = ['field' => 'transDate',         'label' => 'Transaction Date',   'rules' => ['trim', 'required', 'xss_clean', 'regex_match[/^\d{4}-\d{2}-\d{2}$/]']];
        $dd['validityDate']      = ['field' => 'validityDate',      'label' => 'Validity Date',      'rules' => ['trim', 'xss_clean', 'regex_match[/^\d{4}-\d{2}-\d{2}$/]']];
        $dd['globalDiscount']    = ['field' => 'globalDiscount',    'label' => 'Global Discount',    'rules' => ['trim', 'xss_clean', 'numeric', 'greater_than_equal_to[0]', ['validateGlobalDiscount', [$this, 'validateGlobalDiscount']]]];
        $dd['extraDiscount']     = ['field' => 'extraDiscount',     'label' => 'Extra Discount',     'rules' => 'trim|xss_clean|numeric|greater_than_equal_to[0]'];
        $dd['extDiscountType']   = ['field' => 'extDiscountType',   'label' => 'Extra Discount Type','rules' => 'trim|xss_clean'];
        $dd['NetAmount']         = ['field' => 'NetAmount',         'label' => 'Net Amount',         'rules' => 'trim|xss_clean|numeric|greater_than_equal_to[0]'];
        $dd['transNotes']        = ['field' => 'transNotes',        'label' => 'Notes',              'rules' => 'trim|xss_clean|max_length[500]'];
        $dd['transTermsCond']    = ['field' => 'transTermsCond',    'label' => 'Terms & Conditions', 'rules' => 'trim|xss_clean|max_length[1000]'];
        $dd['referenceDetails']  = ['field' => 'referenceDetails',  'label' => 'Reference',          'rules' => 'trim|xss_clean|max_length[100]'];
        $dd['action']            = ['field' => 'action',            'label' => 'Action',             'rules' => 'trim|xss_clean|in_list[save,draft]'];

        $config = [];
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $dd)) {
                $config[] = $dd[$key];
            }
        }

        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() == FALSE) {
            return validation_errors();
        }
        return '';

    }

    public function validateQuotationItems(string $itemsJson): string {

        if (empty($itemsJson)) return 'Items are required.';

        $items = json_decode($itemsJson, true);
        if (!is_array($items) || count($items) === 0) return 'At least one line item is required.';

        $validTaxPercents = [0, 5, 12, 18, 28]; // GST slabs — extend if needed

        foreach ($items as $index => $item) {
            $row = $index + 1;

            // Product UID
            $productUID = isset($item['id']) ? (int) $item['id'] : 0;
            if ($productUID <= 0) return "Row {$row}: Invalid product.";

            // Item name
            if (empty(trim($item['itemName'] ?? ''))) return "Row {$row}: Item name is required.";

            // Quantity — must be a positive number (decimals like 0.25 are allowed)
            $qty = $item['quantity'] ?? '';
            $qtyVal = is_numeric($qty) ? (float) $qty : null;
            if ($qtyVal === null || $qtyVal <= 0) {
                return "Row {$row}: Quantity must be greater than zero.";
            }

            // Unit price (SellingPrice) — must be >= 0
            $unitPrice = isset($item['unitPrice']) ? $item['unitPrice'] : null;
            if ($unitPrice === null || !is_numeric($unitPrice) || (float) $unitPrice < 0) {
                return "Row {$row}: Selling price must be zero or greater.";
            }

            // Tax percent — must be numeric, >= 0, <= 100
            $taxPercent = isset($item['taxPercent']) ? $item['taxPercent'] : null;
            if ($taxPercent === null || !is_numeric($taxPercent)) {
                return "Row {$row}: Tax percentage is invalid.";
            }
            $taxPct = (float) $taxPercent;
            if ($taxPct < 0 || $taxPct > 100) {
                return "Row {$row}: Tax percentage must be between 0 and 100.";
            }
            if (!in_array($taxPct, $validTaxPercents)) {
                return "Row {$row}: Tax percentage {$taxPct}% is not a valid GST slab.";
            }

            // CGST + SGST must sum to tax percent (for intra-state); skip if IGST > 0
            $cgst = (float) ($item['cgstPercent'] ?? 0);
            $sgst = (float) ($item['sgstPercent'] ?? 0);
            $igst = (float) ($item['igstPercent'] ?? 0);
            if ($igst == 0 && round($cgst + $sgst, 4) != round($taxPct, 4)) {
                return "Row {$row}: CGST + SGST must equal total tax percentage.";
            }

            // Per-item discount — must be >= 0
            $discountAmt = isset($item['discountAmount']) ? (float) $item['discountAmount'] : 0;
            if ($discountAmt < 0) {
                return "Row {$row}: Discount amount cannot be negative.";
            }

            // Discount cannot exceed line subtotal
            $lineSubtotal = (float) $qty * (float) $unitPrice;
            if ($discountAmt > $lineSubtotal) {
                return "Row {$row}: Discount cannot exceed line total ({$lineSubtotal}).";
            }

            // Line total — must be >= 0
            $lineTotal = isset($item['line_total']) ? (float) $item['line_total'] : 0;
            if ($lineTotal < 0) {
                return "Row {$row}: Line total cannot be negative.";
            }

            // Brand — required when IsBrandApplicable is set and no variant was selected.
            // A selected variant (variantUID > 0) encodes brand+size together — no separate brandUID needed.
            if (!empty($item['IsBrandApplicable']) && (int)$item['IsBrandApplicable'] === 1) {
                $brandUID   = isset($item['brandUID'])   ? (int)$item['brandUID']   : 0;
                $variantUID = isset($item['variantUID']) ? (int)$item['variantUID'] : 0;
                if ($brandUID <= 0 && $variantUID <= 0) {
                    return "Row {$row}: Brand selection is required for '{$item['itemName']}'.";
                }
            }
        }

        return '';

    }

    /* ===== QUOTATION CUSTOM RULES ===== */
    public function validateGlobalDiscount(mixed $value): bool {
        if (empty($value) || (float) $value == 0) return true;

        $discount = (float) $value;
        if ($discount > 100) {
            $this->form_validation->set_message('validateGlobalDiscount', 'Global discount cannot exceed 100%.');
            return false;
        }
        return true;
    }



}
