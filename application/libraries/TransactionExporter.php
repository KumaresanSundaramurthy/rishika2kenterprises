<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TransactionExporter — OOP export library for all transaction types.
 *
 * Config is keyed by ModuleUID → format → columns.
 * To change columns for any type/format: edit $configs only.
 * The export engine (_toCsv, _toExcel, _toPdf, _toPrint) never needs touching.
 */
class TransactionExporter {

    private $CI;

    // ── Human-readable column headers ────────────────────────────────────────
    private $labels = [
        // Transaction columns
        'UniqueNumber'      => '#',
        'TransDate'         => 'Date',
        'PartyName'         => 'Party Name',
        'PartyArea'         => 'Area / City',
        'SubTotal'          => 'Sub Total',
        'DiscountAmount'    => 'Discount',
        'CgstAmount'        => 'CGST',
        'SgstAmount'        => 'SGST',
        'IgstAmount'        => 'IGST',
        'TaxAmount'         => 'Total Tax',
        'RoundOff'          => 'Round Off',
        'NetAmount'         => 'Net Amount',
        'PaidAmount'        => 'Paid Amount',
        'BalanceAmount'     => 'Balance',
        'Status'            => 'Status',
        'PaymentModes'      => 'Payment Mode',
        'ValidityDate'      => 'Valid Until',
        'Reference'         => 'Reference',
        'SupplierInvoiceNo' => 'Supplier Invoice #',
        'PlaceOfSupply'     => 'Place of Supply',
        'CreatedBy'         => 'Created By',
        // Shared columns
        'MobileNumber'      => 'Mobile',
        'EmailAddress'      => 'Email',
        'UpdatedBy'         => 'Updated By',
        'UpdatedOn'         => 'Last Updated',
        // Customer / Vendor columns
        'CustName'          => 'Customer Name',
        'VendName'          => 'Vendor Name',
        'Area'              => 'Area',
        'GSTIN'             => 'GSTIN',
        'CompanyName'       => 'Company',
        'CustomerTypeName'  => 'Customer Type',
        'ClosingBalance'    => 'Balance',
        'ClosingBalanceType'=> 'Balance Type',
        'IsActiveLabel'     => 'Status',
        // Activity Log columns
        'CreatedOn'         => 'Date / Time',
        'UserName'          => 'User',
        'Module'            => 'Module',
        'AuditCategory'     => 'Category',
        'Action'            => 'Action',
        'EntityRef'         => 'Reference',
        'Result'            => 'Result',
        'Source'            => 'Type',
        'IPAddress'         => 'IP Address',
        'DeviceType'        => 'Device',
        'Summary'           => 'Summary',
        // Customer Group columns
        'GroupName'         => 'Group Name',
        'GroupCode'         => 'Code',
        'GroupType'         => 'Group Type',
        'MemberCount'       => 'Members',
        'ContactPerson'     => 'Contact Person',
        'Mobile'            => 'Mobile',
        'Email'             => 'Email',
        'TotalReceivable'   => 'Receivable',
        'TotalPayable'      => 'Payable',
        // Product columns
        'ItemName'          => 'Item Name',
        'CategoryName'      => 'Category',
        'HSNSACCode'        => 'HSN/SAC',
        'PartNumber'        => 'Part No.',
        'SellingPrice'      => 'Selling Price',
        'TaxPercentage'     => 'Tax %',
        'PurchasePrice'     => 'Purchase Price',
        'AvailableQuantity' => 'Stock',
    ];

    // ── Per-type, per-format column config ───────────────────────────────────
    // ModuleUID => [ title, party, formats => [ format => [ columns ] ] ]
    private $configs = [

        // ── 101: Quotations ──────────────────────────────────────────────────
        101 => [
            'title'   => 'Quotations',
            'party'   => 'Customer',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','Status','ValidityDate']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','NetAmount','Status','ValidityDate','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status','ValidityDate']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','Status','ValidityDate']],
            ],
        ],

        // ── 102: Sales Orders ────────────────────────────────────────────────
        102 => [
            'title'   => 'Sales Orders',
            'party'   => 'Customer',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','Status','ValidityDate']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','NetAmount','Status','ValidityDate','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','Status']],
            ],
        ],

        // ── 103: Invoices ────────────────────────────────────────────────────
        103 => [
            'title'   => 'Invoices',
            'party'   => 'Customer',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','PaidAmount','BalanceAmount','Status','PaymentModes']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','RoundOff','NetAmount','PaidAmount','BalanceAmount','Status','PaymentModes','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','PaidAmount','BalanceAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','PaidAmount','BalanceAmount','Status','PaymentModes']],
            ],
        ],

        // ── 104: Purchase Orders ─────────────────────────────────────────────
        104 => [
            'title'   => 'Purchase Orders',
            'party'   => 'Vendor',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','Status','ValidityDate']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','NetAmount','Status','ValidityDate','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','Status']],
            ],
        ],

        // ── 105: Purchases ───────────────────────────────────────────────────
        105 => [
            'title'   => 'Purchases',
            'party'   => 'Vendor',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','PaidAmount','BalanceAmount','Status','SupplierInvoiceNo']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','RoundOff','NetAmount','PaidAmount','BalanceAmount','Status','SupplierInvoiceNo','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','PaidAmount','BalanceAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','PaidAmount','BalanceAmount','Status','SupplierInvoiceNo']],
            ],
        ],

        // ── 106: Sales Returns ───────────────────────────────────────────────
        106 => [
            'title'   => 'Sales Returns',
            'party'   => 'Customer',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','Status']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','NetAmount','Status','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','Status']],
            ],
        ],

        // ── 108: Purchase Returns ────────────────────────────────────────────
        108 => [
            'title'   => 'Purchase Returns',
            'party'   => 'Vendor',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','Status']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','NetAmount','Status','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','Status']],
            ],
        ],

        // ── 112: Delivery Challans ───────────────────────────────────────────
        112 => [
            'title'   => 'Delivery Challans',
            'party'   => 'Customer',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','NetAmount','Status']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','TaxAmount','NetAmount','Status','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','TaxAmount','NetAmount','Status']],
            ],
        ],

        // ── 113: Proforma Invoices ───────────────────────────────────────────
        113 => [
            'title'   => 'Proforma Invoices',
            'party'   => 'Customer',
            'formats' => [
                'csv'   => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','SubTotal','TaxAmount','NetAmount','Status','ValidityDate']],
                'excel' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','MobileNumber','EmailAddress','SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','NetAmount','Status','ValidityDate','Reference']],
                'pdf'   => ['columns' => ['UniqueNumber','TransDate','PartyName','SubTotal','TaxAmount','NetAmount','Status','ValidityDate']],
                'print' => ['columns' => ['UniqueNumber','TransDate','PartyName','PartyArea','SubTotal','DiscountAmount','TaxAmount','NetAmount','Status','ValidityDate']],
            ],
        ],

        // ── 201: Customers ───────────────────────────────────────────────────
        201 => [
            'title'  => 'Customers',
            'party'  => '',
            'formats' => [
                'csv'   => ['columns' => ['CustName','Area','MobileNumber','EmailAddress','GSTIN','CustomerTypeName','ClosingBalance','ClosingBalanceType','IsActiveLabel','UpdatedOn','UpdatedBy']],
                'excel' => ['columns' => ['CustName','Area','MobileNumber','EmailAddress','GSTIN','CompanyName','CustomerTypeName','ClosingBalance','ClosingBalanceType','IsActiveLabel','UpdatedOn','UpdatedBy']],
                'pdf'   => ['columns' => ['CustName','Area','MobileNumber','GSTIN','ClosingBalance','ClosingBalanceType','IsActiveLabel']],
                'print' => ['columns' => ['CustName','Area','MobileNumber','EmailAddress','GSTIN','CustomerTypeName','ClosingBalance','ClosingBalanceType','IsActiveLabel','UpdatedBy']],
            ],
        ],

        // ── 204: Customer Groups ─────────────────────────────────────────────
        204 => [
            'title'  => 'Customer Groups',
            'party'  => '',
            'formats' => [
                'csv'   => ['columns' => ['GroupName','GroupCode','GroupType','MemberCount','ContactPerson','Mobile','Email','TotalReceivable','TotalPayable','IsActiveLabel']],
                'excel' => ['columns' => ['GroupName','GroupCode','GroupType','MemberCount','ContactPerson','Mobile','Email','TotalReceivable','TotalPayable','IsActiveLabel']],
                'pdf'   => ['columns' => ['GroupName','GroupCode','GroupType','MemberCount','TotalReceivable','TotalPayable','IsActiveLabel']],
                'print' => ['columns' => ['GroupName','GroupCode','GroupType','MemberCount','ContactPerson','Mobile','TotalReceivable','TotalPayable','IsActiveLabel']],
            ],
        ],

        // ── 202: Vendors ─────────────────────────────────────────────────────
        202 => [
            'title'  => 'Vendors',
            'party'  => '',
            'formats' => [
                'csv'   => ['columns' => ['VendName','Area','MobileNumber','EmailAddress','GSTIN','ClosingBalance','ClosingBalanceType','IsActiveLabel','UpdatedOn','UpdatedBy']],
                'excel' => ['columns' => ['VendName','Area','MobileNumber','EmailAddress','GSTIN','CompanyName','ClosingBalance','ClosingBalanceType','IsActiveLabel','UpdatedOn','UpdatedBy']],
                'pdf'   => ['columns' => ['VendName','Area','MobileNumber','GSTIN','ClosingBalance','ClosingBalanceType','IsActiveLabel']],
                'print' => ['columns' => ['VendName','Area','MobileNumber','EmailAddress','GSTIN','ClosingBalance','ClosingBalanceType','IsActiveLabel','UpdatedBy']],
            ],
        ],

        // ── 700: Activity Log ────────────────────────────────────────────────────
        700 => [
            'title'   => 'Activity Log',
            'party'   => '',
            'formats' => [
                'csv'   => ['columns' => ['CreatedOn','UserName','Module','AuditCategory','Action','EntityRef','Result','Source','IPAddress','DeviceType','Summary']],
                'excel' => ['columns' => ['CreatedOn','UserName','Module','AuditCategory','Action','EntityRef','Result','Source','IPAddress','DeviceType','Summary']],
                'pdf'   => ['columns' => ['CreatedOn','UserName','Module','Action','EntityRef','Result','Summary']],
                'print' => ['columns' => ['CreatedOn','UserName','Module','Action','EntityRef','Result','Summary']],
            ],
        ],

        // ── 203: Products ────────────────────────────────────────────────────
        203 => [
            'title'  => 'Products',
            'party'  => '',
            'formats' => [
                'csv'   => ['columns' => ['ItemName','CategoryName','HSNSACCode','PartNumber','SellingPrice','TaxPercentage','PurchasePrice','AvailableQuantity','IsActiveLabel','UpdatedOn','UpdatedBy']],
                'excel' => ['columns' => ['ItemName','CategoryName','HSNSACCode','PartNumber','SellingPrice','TaxPercentage','PurchasePrice','AvailableQuantity','IsActiveLabel','UpdatedOn','UpdatedBy']],
                'pdf'   => ['columns' => ['ItemName','CategoryName','SellingPrice','TaxPercentage','PurchasePrice','AvailableQuantity','IsActiveLabel']],
                'print' => ['columns' => ['ItemName','CategoryName','HSNSACCode','SellingPrice','TaxPercentage','PurchasePrice','AvailableQuantity','IsActiveLabel','UpdatedBy']],
            ],
        ],

    ];

    // ── Columns treated as money (right-aligned, formatted to 2dp) ───────────
    private $moneyCols = ['SubTotal','DiscountAmount','CgstAmount','SgstAmount','IgstAmount','TaxAmount','RoundOff','NetAmount','PaidAmount','BalanceAmount','ClosingBalance','SellingPrice','PurchasePrice','TotalReceivable','TotalPayable'];

    // ── Date columns ─────────────────────────────────────────────────────────
    private $dateCols  = ['TransDate','ValidityDate','UpdatedOn'];

    // ────────────────────────────────────────────────────────────────────────
    public function __construct() {
        $this->CI =& get_instance();
    }

    // ── Public: get config for a module (used by controller to validate) ─────
    public function getConfig($moduleUID) {
        return $this->configs[(int)$moduleUID] ?? null;
    }

    // ── Public: main entry point ─────────────────────────────────────────────
    public function export($moduleUID, $format, $filters, $orgUID, $dateFmt = 'd M Y') {

        $config = $this->getConfig($moduleUID);
        if (!$config) return null;

        $fmtCfg = $config['formats'][$format] ?? null;
        if (!$fmtCfg) return null;

        $columns = $fmtCfg['columns'];
        $labels  = array_map(fn($c) => $this->labels[$c] ?? $c, $columns);

        $rows = $this->_fetchRows((int)$moduleUID, (int)$orgUID, $filters);

        // Computed columns — differ by module family
        if ((int)$moduleUID < 200) {
            foreach ($rows as $row) {
                $row->BalanceAmount = max(0, (float)($row->NetAmount ?? 0) - (float)($row->PaidAmount ?? 0));
                $row->PlaceOfSupply = trim(($row->PlaceOfSupplyCode ?? '') . ' – ' . ($row->PlaceOfSupplyName ?? ''), ' –');
            }
        } else {
            foreach ($rows as $row) {
                $row->IsActiveLabel = ((int)($row->IsActive ?? 1)) === 1 ? 'Active' : 'Inactive';
            }
        }

        switch ($format) {
            case 'csv':   return $this->_toCsv($rows, $columns, $labels, $config['title'], $dateFmt);
            case 'excel': return $this->_toExcel($rows, $columns, $labels, $config['title'], $dateFmt);
            case 'pdf':   return $this->_toPdf($rows, $columns, $labels, $config, $dateFmt);
            case 'print': return $this->_toPrint($rows, $columns, $labels, $config, $dateFmt);
        }
        return null;
    }

    // ── Private: dispatch row fetch to the right model ────────────────────────
    private function _fetchRows(int $moduleUID, int $orgUID, array $filters): array {
        if ($moduleUID < 200) {
            $this->CI->load->model('transactions_model');
            return $this->CI->transactions_model->getTransactionExportData($moduleUID, $orgUID, $filters);
        }
        switch ($moduleUID) {
            case 201:
                $this->CI->load->model('customers_model');
                $result = $this->CI->customers_model->getCustomerListPaginated($orgUID, 0, 0, $filters);
                $rows   = $result->rows ?? [];
                foreach ($rows as $r) { $r->CustName = $r->Name ?? ''; }
                return $rows;
            case 204:
                $this->CI->load->model('customers_model');
                return $this->CI->customers_model->getCustomerGroupsForExport($orgUID, $filters);
            case 202:
                $this->CI->load->model('vendors_model');
                $result = $this->CI->vendors_model->getVendorListPaginated($orgUID, 0, 0, $filters);
                $rows   = $result->rows ?? [];
                foreach ($rows as $r) { $r->VendName = $r->Name ?? ''; }
                return $rows;
            case 203:
                $this->CI->load->model('products_model');
                return $this->CI->products_model->getProductsForExport($orgUID);
            case 700:
                $this->CI->load->model('activitylog_model');
                return $this->CI->activitylog_model->getAuditLogs($orgUID, $filters, 5000, 0);
            default:
                return [];
        }
    }

    // ── Format a single cell value ───────────────────────────────────────────
    private function _cell($row, $col, $dateFmt) {
        $val = $row->$col ?? '';
        if (in_array($col, $this->dateCols) && $val) {
            $d = date_create($val);
            return $d ? date_format($d, $dateFmt) : $val;
        }
        if (in_array($col, $this->moneyCols)) {
            return smartDecimal((float)$val);
        }
        return (string)$val;
    }

    // ── CSV ──────────────────────────────────────────────────────────────────
    private function _toCsv($rows, $columns, $labels, $title, $dateFmt) {
        $tmp = fopen('php://temp', 'r+');
        fputcsv($tmp, $labels);
        foreach ($rows as $row) {
            fputcsv($tmp, array_map(fn($c) => $this->_cell($row, $c, $dateFmt), $columns));
        }
        rewind($tmp);
        $content = stream_get_contents($tmp);
        fclose($tmp);
        return [
            'content'  => $content,
            'type'     => 'text/csv; charset=UTF-8',
            'filename' => $title . '_' . date('Y-m-d') . '.csv',
        ];
    }

    // ── Excel (XML Spreadsheet — no library needed, opens in Excel natively) ──
    private function _toExcel($rows, $columns, $labels, $title, $dateFmt) {
        $x  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $x .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $x .= "          xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        $x .= "<Styles><Style ss:ID=\"H\"><Font ss:Bold=\"1\"/><Interior ss:Color=\"#1a73e8\" ss:Pattern=\"Solid\"/><Font ss:Color=\"#FFFFFF\" ss:Bold=\"1\"/></Style></Styles>\n";
        $x .= '<Worksheet ss:Name="' . htmlspecialchars($title) . '">' . "\n<Table>\n";

        // Header
        $x .= '<Row>';
        foreach ($labels as $lbl) {
            $x .= '<Cell ss:StyleID="H"><Data ss:Type="String">' . htmlspecialchars($lbl) . '</Data></Cell>';
        }
        $x .= "</Row>\n";

        // Data
        foreach ($rows as $row) {
            $x .= '<Row>';
            foreach ($columns as $col) {
                $val  = $this->_cell($row, $col, $dateFmt);
                $type = in_array($col, $this->moneyCols) ? 'Number' : 'String';
                $x   .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($val) . '</Data></Cell>';
            }
            $x .= "</Row>\n";
        }

        $x .= "</Table>\n</Worksheet>\n</Workbook>";
        return [
            'content'  => $x,
            'type'     => 'application/vnd.ms-excel',
            'filename' => $title . '_' . date('Y-m-d') . '.xls',
        ];
    }

    // ── PDF (via Dompdf — already installed) ─────────────────────────────────
    private function _toPdf($rows, $columns, $labels, $config, $dateFmt) {
        $html = $this->_buildHtml($rows, $columns, $labels, $config, $dateFmt, 'pdf');

        $opts = new \Dompdf\Options();
        $opts->set('defaultFont', 'Helvetica');
        $dompdf = new \Dompdf\Dompdf($opts);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return [
            'content'  => $dompdf->output(),
            'type'     => 'application/pdf',
            'filename' => $config['title'] . '_' . date('Y-m-d') . '.pdf',
        ];
    }

    // ── Print (HTML returned to browser, opened in new tab) ──────────────────
    private function _toPrint($rows, $columns, $labels, $config, $dateFmt) {
        return [
            'content'  => $this->_buildHtml($rows, $columns, $labels, $config, $dateFmt, 'print'),
            'type'     => 'text/html',
            'filename' => '',
        ];
    }

    // ── Shared HTML builder (used by both PDF and Print) ─────────────────────
    private function _buildHtml($rows, $columns, $labels, $config, $dateFmt, $mode) {
        $orgName = $this->CI->pageData['JwtData']->Org->OrgName ?? '';
        $title   = $config['title'];

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>' . htmlspecialchars($title) . '</title><style>';
        $html .= 'body{font-family:Arial,sans-serif;font-size:10px;margin:16px;color:#222;}';
        $html .= '.org{font-size:13px;font-weight:bold;margin-bottom:2px;}';
        $html .= '.sub{font-size:9px;color:#555;margin-bottom:10px;}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:6px;}';
        $html .= 'th{background:#1a73e8;color:#fff;padding:5px 7px;text-align:left;font-size:9px;white-space:nowrap;}';
        $html .= 'td{padding:4px 7px;border-bottom:1px solid #e8e8e8;font-size:9px;}';
        $html .= 'tr:nth-child(even) td{background:#f4f8ff;}';
        $html .= '.num{text-align:right;}';
        $html .= '.sno{color:#999;text-align:center;}';
        if ($mode === 'print') {
            $html .= '.pbtn{margin-bottom:10px;} @media print{.pbtn{display:none;} @page{size:A4 landscape;margin:10mm;}}';
        }
        $html .= '</style></head><body>';

        if ($mode === 'print') {
            $html .= '<div class="pbtn"><button onclick="window.print()" style="padding:5px 14px;cursor:pointer;">🖨 Print</button></div>';
        }

        $html .= '<div class="org">' . htmlspecialchars($orgName) . '</div>';
        $html .= '<div class="sub">' . htmlspecialchars($title) . ' &nbsp;|&nbsp; Exported: ' . date('d M Y') . ' &nbsp;|&nbsp; ' . count($rows) . ' records</div>';

        $html .= '<table><thead><tr><th class="sno">#</th>';
        foreach ($labels as $lbl) {
            $html .= '<th>' . htmlspecialchars($lbl) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $i = 1;
        foreach ($rows as $row) {
            $html .= '<tr><td class="sno">' . $i++ . '</td>';
            foreach ($columns as $col) {
                $val   = $this->_cell($row, $col, $dateFmt);
                $cls   = in_array($col, $this->moneyCols) ? ' class="num"' : '';
                $html .= '<td' . $cls . '>' . htmlspecialchars($val) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';
        return $html;
    }
}
