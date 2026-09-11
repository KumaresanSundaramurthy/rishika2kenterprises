<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Billinginvoice library
 *
 * Renders a subscription invoice to PDF and uploads it to Cloudflare R2.
 * Returns the public CDN URL so it can be stored in SubscriptionInvoicesTbl.PDFPath.
 *
 * Usage:
 *   $this->load->library('billinginvoice');
 *   $url = $this->billinginvoice->generateAndUpload($invoiceData);
 *
 * $invoiceData keys:
 *   invoice_number, invoice_date, financial_year,
 *   org_name, gstin, org_address1, org_address2, org_city,
 *   org_state, org_pincode, org_phone, org_email,
 *   plan_name, billing_cycle, renewal_type,
 *   taxable_amount, tax_rate, tax_amount, total_amount,
 *   order_uid, org_uid
 */
class Billinginvoice {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Generate PDF and upload to R2.
     *
     * @param array $data  Invoice data (see class doc-block)
     * @return string|null  Public CDN URL on success, null on failure
     */
    public function generateAndUpload(array $data): ?string {
        try {
            $html = $this->_buildHtml($data);
            $pdf  = $this->_renderPdf($html);
            return $this->_uploadToR2($pdf, $data);
        } catch (Exception $e) {
            notifyError('Billinginvoice::generateAndUpload', $e);
            return null;
        }
    }

    /* ── HTML template ─────────────────────────────────────────────────────── */

    private function _buildHtml(array $d): string {
        $invoiceNumber = htmlspecialchars($d['invoice_number']  ?? '', ENT_QUOTES, 'UTF-8');
        $invoiceDate   = !empty($d['invoice_date'])
            ? date('d M Y', strtotime($d['invoice_date']))
            : date('d M Y');
        $fy            = htmlspecialchars($d['financial_year'] ?? '', ENT_QUOTES, 'UTF-8');

        $orgName       = htmlspecialchars($d['org_name']      ?? '', ENT_QUOTES, 'UTF-8');
        $gstin         = htmlspecialchars($d['gstin']         ?? '', ENT_QUOTES, 'UTF-8');
        $addr1         = htmlspecialchars($d['org_address1']  ?? '', ENT_QUOTES, 'UTF-8');
        $addr2         = htmlspecialchars($d['org_address2']  ?? '', ENT_QUOTES, 'UTF-8');
        $city          = htmlspecialchars($d['org_city']      ?? '', ENT_QUOTES, 'UTF-8');
        $state         = htmlspecialchars($d['org_state']     ?? '', ENT_QUOTES, 'UTF-8');
        $pincode       = htmlspecialchars($d['org_pincode']   ?? '', ENT_QUOTES, 'UTF-8');
        $phone         = htmlspecialchars($d['org_phone']     ?? '', ENT_QUOTES, 'UTF-8');
        $email         = htmlspecialchars($d['org_email']     ?? '', ENT_QUOTES, 'UTF-8');

        $planName      = htmlspecialchars($d['plan_name']     ?? '', ENT_QUOTES, 'UTF-8');
        $billingCycle  = htmlspecialchars($d['billing_cycle'] ?? '', ENT_QUOTES, 'UTF-8');
        $renewalType   = htmlspecialchars($d['renewal_type']  ?? 'Renewal', ENT_QUOTES, 'UTF-8');

        $taxableAmt    = number_format((float)($d['taxable_amount'] ?? 0), 2);
        $taxRate       = number_format((float)($d['tax_rate']       ?? 0), 2);
        $taxAmt        = number_format((float)($d['tax_amount']     ?? 0), 2);
        $totalAmt      = number_format((float)($d['total_amount']   ?? 0), 2);

        /* Build address line */
        $addressParts = array_filter([$addr1, $addr2, $city, $state, $pincode]);
        $addressHtml  = implode('<br>', $addressParts);

        /* Seller (Rishika 2K Enterprises) details */
        $sellerName  = 'Rishika 2K Enterprises';
        $sellerGstin = '33ESZPK0894R1ZH';
        $sellerAddr  = '123, Tech Park Road<br>Chennai, Tamil Nadu 600001';
        $sellerPhone = '+91 98765 43210';
        $sellerEmail = 'billing@rishika2k.com';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      color: #1a1a2e;
      background: #fff;
      padding: 32px 36px;
  }
  .inv-header {
      border-bottom: 3px solid #1a1a2e;
      padding-bottom: 16px;
      margin-bottom: 20px;
  }
  .inv-header table { width: 100%; }
  .inv-title {
      font-size: 26px;
      font-weight: bold;
      letter-spacing: 2px;
      color: #1a1a2e;
  }
  .inv-badge {
      display: inline-block;
      background: #1a1a2e;
      color: #fff;
      padding: 3px 10px;
      border-radius: 3px;
      font-size: 10px;
      letter-spacing: 1px;
      margin-top: 4px;
  }
  .inv-meta { text-align: right; line-height: 1.7; }
  .inv-meta .label { color: #666; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
  .inv-meta .value { font-weight: bold; font-size: 13px; }
  .inv-meta .fy { font-size: 10px; color: #888; }

  .parties { width: 100%; margin-bottom: 20px; }
  .parties td { width: 50%; vertical-align: top; }
  .party-box {
      background: #f7f8fc;
      border: 1px solid #e0e4ef;
      border-radius: 4px;
      padding: 12px 14px;
  }
  .party-box.right { margin-left: 8px; }
  .party-label {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #888;
      margin-bottom: 6px;
  }
  .party-name { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
  .party-detail { font-size: 11px; color: #444; line-height: 1.6; }
  .party-gstin { font-size: 10px; color: #666; margin-top: 4px; }

  .inv-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  .inv-items thead tr { background: #1a1a2e; color: #fff; }
  .inv-items thead th {
      padding: 8px 10px;
      text-align: left;
      font-size: 11px;
      letter-spacing: 0.5px;
  }
  .inv-items thead th.right { text-align: right; }
  .inv-items tbody td {
      padding: 10px 10px;
      border-bottom: 1px solid #e8ebf4;
      font-size: 11px;
      vertical-align: top;
  }
  .inv-items tbody td.right { text-align: right; }
  .inv-items tbody tr:nth-child(even) td { background: #fafbfe; }

  .totals-wrap { width: 100%; }
  .totals-wrap td.spacer { width: 55%; }
  .totals-table {
      width: 45%;
      border-collapse: collapse;
  }
  .totals-table tr td {
      padding: 5px 8px;
      font-size: 11px;
  }
  .totals-table tr td.lbl { color: #555; }
  .totals-table tr td.amt { text-align: right; }
  .totals-table tr.total-row td {
      border-top: 2px solid #1a1a2e;
      padding-top: 8px;
      font-size: 13px;
      font-weight: bold;
  }
  .totals-table tr.sub-row td { border-bottom: 1px solid #eee; }

  .inv-footer {
      margin-top: 28px;
      border-top: 1px solid #dde0ec;
      padding-top: 14px;
      font-size: 10px;
      color: #888;
      text-align: center;
      line-height: 1.7;
  }
  .inv-footer strong { color: #444; }

  .paid-stamp {
      display: inline-block;
      border: 3px solid #16a34a;
      color: #16a34a;
      padding: 4px 16px;
      font-size: 16px;
      font-weight: bold;
      letter-spacing: 3px;
      border-radius: 4px;
      transform: rotate(-8deg);
      float: right;
      margin-top: -36px;
      margin-right: 8px;
  }
</style>
</head>
<body>

<!-- Header -->
<div class="inv-header">
  <table>
    <tr>
      <td>
        <div class="inv-title">INVOICE</div>
        <div class="inv-badge">SUBSCRIPTION</div>
      </td>
      <td class="inv-meta">
        <div class="label">Invoice Number</div>
        <div class="value">{$invoiceNumber}</div>
        <div class="fy">FY {$fy}</div>
        <br>
        <div class="label">Invoice Date</div>
        <div class="value">{$invoiceDate}</div>
      </td>
    </tr>
  </table>
</div>

<!-- Parties -->
<table class="parties">
  <tr>
    <td>
      <div class="party-box">
        <div class="party-label">Billed By</div>
        <div class="party-name">{$sellerName}</div>
        <div class="party-detail">{$sellerAddr}</div>
        <div class="party-detail">{$sellerPhone} &nbsp;|&nbsp; {$sellerEmail}</div>
        <div class="party-gstin">GSTIN: {$sellerGstin}</div>
      </div>
    </td>
    <td>
      <div class="party-box right">
        <div class="party-label">Billed To</div>
        <div class="party-name">{$orgName}</div>
        <div class="party-detail">{$addressHtml}</div>
        <div class="party-detail">{$phone}</div>
        <div class="party-detail">{$email}</div>
        <div class="party-gstin">GSTIN: {$gstin}</div>
      </div>
    </td>
  </tr>
</table>

<!-- Line items -->
<table class="inv-items">
  <thead>
    <tr>
      <th style="width:5%">#</th>
      <th style="width:40%">Description</th>
      <th style="width:20%">Type</th>
      <th class="right" style="width:17%">Base Amount</th>
      <th class="right" style="width:18%">Total</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>1</td>
      <td>
        <strong>{$planName}</strong>
        <br><span style="color:#666;font-size:10px;">{$billingCycle} Subscription</span>
      </td>
      <td>{$renewalType}</td>
      <td class="right">&#8377; {$taxableAmt}</td>
      <td class="right">&#8377; {$totalAmt}</td>
    </tr>
  </tbody>
</table>

<!-- Totals -->
<table class="totals-wrap">
  <tr>
    <td class="spacer">
      <div class="paid-stamp">PAID</div>
    </td>
    <td>
      <table class="totals-table">
        <tr class="sub-row">
          <td class="lbl">Taxable Amount</td>
          <td class="amt">&#8377; {$taxableAmt}</td>
        </tr>
        <tr class="sub-row">
          <td class="lbl">GST @ {$taxRate}%</td>
          <td class="amt">&#8377; {$taxAmt}</td>
        </tr>
        <tr class="total-row">
          <td class="lbl">Total</td>
          <td class="amt">&#8377; {$totalAmt}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- Footer -->
<div class="inv-footer">
  <strong>This is a computer-generated invoice and does not require a signature.</strong><br>
  For queries, contact {$sellerEmail} &nbsp;|&nbsp; {$sellerPhone}
</div>

</body>
</html>
HTML;
    }

    /* ── PDF render ─────────────────────────────────────────────────────────── */

    private function _renderPdf(string $html): string {
        require_once FCPATH . 'vendor/autoload.php';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial');
        $options->set('chroot', FCPATH);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /* ── Upload to Cloudflare R2 ─────────────────────────────────────────────── */

    private function _uploadToR2(string $pdfBytes, array $d): ?string {
        /* Save to a temp file so Fileupload can determine mime type correctly */
        $tmpFile = tempnam(sys_get_temp_dir(), 'r2k_inv_') . '.pdf';
        file_put_contents($tmpFile, $pdfBytes);

        try {
            /* Path inside bucket: user_uploads/ is prepended by Fileupload */
            $fy       = preg_replace('/[^0-9\-]/', '', $d['financial_year'] ?? billing_fy('long'));
            $orgUID   = (int)($d['org_uid'] ?? 0);
            $invNum   = preg_replace('/[^A-Za-z0-9\-]/', '', $d['invoice_number'] ?? 'invoice');
            $r2Path   = "Billing/Invoices/{$fy}/{$orgUID}/{$invNum}.pdf";

            $this->CI->load->library('fileupload');
            $upload = $this->CI->fileupload->fileUpload('file', $r2Path, $tmpFile);

            if ($upload->Error) {
                throw new Exception('R2 upload failed: ' . $upload->Message);
            }

            /* Build CDN URL from env — FullPath from S3Client is the endpoint URL, not the CDN URL */
            $cdnBase = rtrim(getenv('CFLARE_R2_CDN'), '/');
            return $cdnBase . '/user_uploads/' . $r2Path;

        } finally {
            @unlink($tmpFile);
        }
    }

}
