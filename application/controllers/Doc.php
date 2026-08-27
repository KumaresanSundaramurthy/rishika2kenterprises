<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public document viewer — no authentication required.
 * URL: /doc/{TransToken}        → HTML preview with Print & PDF buttons
 * URL: /doc/pdf/{TransToken}    → PDF download
 * URL: /invoice/{TransToken}    → alias for backward compat with email links
 */
class Doc extends CI_Controller {

    private static $MODULE_LABELS = [
        101 => 'Quotation',
        102 => 'Sales Order',
        103 => 'Invoice',
        104 => 'Delivery Challan',
        105 => 'Purchase Bill',
        106 => 'Purchase Order',
        107 => 'Sales Return',
        108 => 'Purchase Return',
        109 => 'Credit Note',
        110 => 'Payment Receipt',
        111 => 'Payment Voucher',
    ];

    public function __construct() {
        parent::__construct();
        // No JWT / auth middleware — public page
    }

    // ── HTML preview ──────────────────────────────────────────────────────────

    public function view($token = '') {
        $token = trim($token);
        if (empty($token) || strlen($token) > 40 || !preg_match('/^[0-9a-f\-]+$/i', $token)) {
            $this->_showError('Invalid document link.');
            return;
        }

        $this->load->model('transactions_model');
        $this->load->model('organisation_model');

        $stub = $this->transactions_model->getTransactionStubByToken($token);

        if (!$stub) {
            $this->_showError('Document not found. The link may be invalid or the document has been deleted.');
            return;
        }
        if ($stub->DocStatus === 'Draft') {
            $this->_showError('This document is not yet finalized and cannot be shared.');
            return;
        }

        $header      = $this->transactions_model->getTransactionById($stub->TransUID, $stub->OrgUID, $stub->ModuleUID);
        $items       = $this->transactions_model->getTransactionItems($stub->TransUID, $stub->OrgUID);
        $orgInfo     = $this->organisation_model->getOrgInfoCached($stub->OrgUID);
        $themeResult = $this->organisation_model->getPrintThemeByType($stub->OrgUID, $stub->TransType);
        $bankAccount = $this->transactions_model->getPrintBankAccount($stub->OrgUID);

        $printHtml = $this->transactions_model->_renderA4Html(
            $stub->ModuleUID,
            $header,
            $items,
            $orgInfo->Data ?? null,
            $themeResult->Data ?? null,
            $bankAccount
        );

        $docLabel  = self::$MODULE_LABELS[$stub->ModuleUID] ?? ($stub->TransType ?? 'Document');
        $docNumber = htmlspecialchars($stub->UniqueNumber ?? '—', ENT_QUOTES, 'UTF-8');
        $pdfUrl    = base_url('doc/pdf/' . $token);

        $paidAmt    = (float)($stub->PaidAmount    ?? 0);
        $netAmt     = (float)($stub->NetAmount    ?? 0);
        $pendingAmt = max(0.0, round((float)($stub->BalanceAmount ?? ($netAmt - $paidAmt)), 2));

        if ($netAmt <= 0) {
            $payBadge = '';
        } elseif ($paidAmt <= 0) {
            $payBadge = '<span class="dab-badge dab-badge-pending">PENDING</span>';
        } elseif ($pendingAmt <= 0.01) {
            $payBadge = '<span class="dab-badge dab-badge-paid">PAID</span>';
        } else {
            $payBadge = '<span class="dab-badge dab-badge-partial">PARTIAL</span>';
        }

        // Razorpay Pay button: only for invoices with a pending balance and configured keys
        $showPayBtn  = ((int)$stub->ModuleUID === 103)
            && ($pendingAmt > 0.01)
            && !in_array($stub->DocStatus, ['Cancelled', 'Rejected', 'Draft'], true)
            && !empty(getenv('RAZORPAY_KEY_ID'));
        $pendingFmt  = '&#8377;' . smartDecimal($pendingAmt);
        $createUrl   = base_url('razorpay/createOrder/'   . $token);
        $verifyUrl   = base_url('razorpay/verifyAndRecord/' . $token);

        $css = <<<CSS
<style id="doc-viewer-bar-css">
#doc-action-bar{
  position:fixed;top:0;left:0;right:0;z-index:99999;
  background:#0f172a;height:52px;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 20px;gap:12px;
  box-shadow:0 2px 12px rgba(0,0,0,.5);
  font-family:'Segoe UI',Arial,sans-serif;
}
#doc-action-bar .dab-info{ min-width:0; }
#doc-action-bar .dab-type{ font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.9px;line-height:1; }
#doc-action-bar .dab-num { font-size:14px;color:#f1f5f9;font-weight:700;line-height:1.3;display:flex;align-items:center;gap:7px;flex-wrap:wrap; }
#doc-action-bar .dab-btns{ display:flex;align-items:center;gap:8px;flex-shrink:0; }
#doc-action-bar .dab-btn{
  display:inline-flex;align-items:center;gap:5px;
  padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;
  cursor:pointer;border:none;text-decoration:none;white-space:nowrap;
  transition:opacity .15s;
}
#doc-action-bar .dab-btn:hover{ opacity:.82; }
#doc-action-bar .dab-btn-print{ background:#f59e0b;color:#1a1a1a; }
#doc-action-bar .dab-btn-pdf  { background:#3b82f6;color:#fff; }
#doc-action-bar .dab-btn-pay  { background:#10b981;color:#fff;box-shadow:0 0 0 2px rgba(16,185,129,.35); }
#doc-action-bar .dab-btn-pay:disabled{ opacity:.6;cursor:not-allowed; }
#doc-action-bar .dab-org{ font-size:11px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;display:none; }
.dab-badge{ padding:2px 9px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.3px; }
.dab-badge-pending{ background:#fef3c7;color:#92400e; }
.dab-badge-paid   { background:#d1fae5;color:#065f46; }
.dab-badge-partial{ background:#dbeafe;color:#1e40af; }
#docPaySuccess{
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
  background:#065f46;color:#d1fae5;padding:14px 24px;border-radius:10px;
  font-family:'Segoe UI',Arial,sans-serif;font-size:14px;font-weight:600;
  box-shadow:0 4px 20px rgba(0,0,0,.35);display:none;
  align-items:center;gap:14px;z-index:999999;
}
#docPaySuccess a{ color:#6ee7b7;font-weight:700;text-decoration:underline; }
body { padding-top:52px !important; }
@media print{
  #doc-action-bar,#docPaySuccess{ display:none !important; }
  body{ padding-top:0 !important; }
}
@media(max-width:520px){
  #doc-action-bar .dab-type{ display:none; }
  #doc-action-bar .dab-num { font-size:12px; }
  #doc-action-bar .dab-btn { padding:6px 10px;font-size:11px; }
}
</style>
CSS;

        // Build action-bar HTML; pay button added only when applicable
        $payBtnHtml = '';
        if ($showPayBtn) {
            $payBtnHtml = '<button class="dab-btn dab-btn-pay" id="docPayBtn" onclick="docOpenPayment()" title="Pay this invoice online">'
                . '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">'
                . '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'
                . '</svg> Pay ' . $pendingFmt . '</button>';
        }

        $bar = '<div id="doc-action-bar">'
            . '<div class="dab-info">'
            . '<div class="dab-type">' . $docLabel . '</div>'
            . '<div class="dab-num">' . $docNumber . ' ' . $payBadge . '</div>'
            . '</div>'
            . '<div class="dab-btns">'
            . '<button class="dab-btn dab-btn-print" onclick="window.print()" title="Print this document">'
            . '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>'
            . '</svg> Print</button>'
            . '<a class="dab-btn dab-btn-pdf" href="' . $pdfUrl . '" target="_blank" rel="noopener" title="Download as PDF">'
            . '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'
            . '</svg> Download PDF</a>'
            . $payBtnHtml
            . '</div></div>';

        // Success toast (hidden; shown after payment)
        $successToast = '<div id="docPaySuccess">'
            . '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>'
            . '<span>Payment successful! </span>'
            . '<a id="docPayReceiptLink" href="#" target="_blank" rel="noopener" style="display:none">View Receipt</a>'
            . '</div>';

        // Razorpay JS — injected only when pay button is shown
        $rpScript = '';
        if ($showPayBtn) {
            $rpScript = '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>'
                . '<script>'
                . 'function docOpenPayment(){'
                .   'var btn=document.getElementById("docPayBtn");'
                .   'btn.disabled=true;btn.textContent="Loading…";'
                .   'fetch("' . $createUrl . '",{method:"POST"})'
                .   '.then(function(r){return r.json();})'
                .   '.then(function(d){'
                .     'if(d.Error){alert(d.Message||"Could not initiate payment.");btn.disabled=false;btn.innerHTML=\'Pay ' . $pendingFmt . '\';return;}'
                .     'var rzp=new Razorpay({'
                .       'key:d.key_id,'
                .       'amount:d.amount,'
                .       'currency:d.currency,'
                .       'name:d.name,'
                .       'description:d.description,'
                .       'order_id:d.order_id,'
                .       'prefill:d.prefill,'
                .       'notes:d.notes,'
                .       'theme:{color:"#10b981"},'
                .       'modal:{ondismiss:function(){btn.disabled=false;btn.innerHTML=\'Pay ' . $pendingFmt . '\';}},'
                .       'handler:function(resp){'
                .         'btn.disabled=true;btn.textContent="Verifying…";'
                .         'var fd=new FormData();'
                .         'fd.append("razorpay_order_id",resp.razorpay_order_id);'
                .         'fd.append("razorpay_payment_id",resp.razorpay_payment_id);'
                .         'fd.append("razorpay_signature",resp.razorpay_signature);'
                .         'fetch("' . $verifyUrl . '",{method:"POST",body:fd})'
                .         '.then(function(r){return r.json();})'
                .         '.then(function(v){'
                .           'if(v.Error){alert("Payment verification failed: "+(v.Message||""));btn.disabled=false;btn.innerHTML=\'Pay ' . $pendingFmt . '\';return;}'
                .           'btn.style.display="none";'
                .           'var toast=document.getElementById("docPaySuccess");'
                .           'toast.style.display="flex";'
                .           'if(v.ReceiptUrl){'
                .             'var rl=document.getElementById("docPayReceiptLink");'
                .             'rl.href=v.ReceiptUrl;rl.style.display="inline";'
                .           '}'
                .         '})'
                .         '.catch(function(){btn.disabled=false;btn.innerHTML=\'Pay ' . $pendingFmt . '\';alert("Network error during verification.");});'
                .       '}'
                .     '});'
                .     'rzp.open();'
                .   '})'
                .   '.catch(function(){btn.disabled=false;btn.innerHTML=\'Pay ' . $pendingFmt . '\';alert("Network error. Please try again.");});'
                . '}'
                . '</script>';
        }

        // Inject CSS into <head>, bar + success toast after <body>, and Razorpay JS before </body>
        $finalHtml = str_replace('</head>', $css . '</head>', $printHtml);
        $finalHtml = preg_replace('/<body([^>]*)>/i', '<body$1>' . $bar . $successToast, $finalHtml, 1);
        if ($rpScript !== '') {
            $finalHtml = str_replace('</body>', $rpScript . '</body>', $finalHtml);
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('text/html', 'utf-8')
            ->set_output($finalHtml)
            ->_display();
        exit;
    }

    // ── PDF download ──────────────────────────────────────────────────────────

    public function pdf($token = '') {
        $token = trim($token);
        if (empty($token) || strlen($token) > 40 || !preg_match('/^[0-9a-f\-]+$/i', $token)) {
            show_404();
            return;
        }

        $this->load->model('transactions_model');

        $stub = $this->transactions_model->getTransactionStubByToken($token);

        if (!$stub || $stub->DocStatus === 'Draft') {
            show_404();
            return;
        }

        $pdfBytes = $this->transactions_model->generateTransactionPdfBytes(
            $stub->TransUID,
            $stub->OrgUID,
            $stub->ModuleUID
        );

        if (!$pdfBytes) {
            show_404();
            return;
        }

        $filename = !empty($stub->UniqueNumber)
            ? preg_replace('/[^A-Za-z0-9\-_]/', '-', $stub->UniqueNumber) . '.pdf'
            : 'document.pdf';

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/pdf')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output($pdfBytes);
    }

    // ── Error page ────────────────────────────────────────────────────────────

    private function _showError($message) {
        $this->load->view('doc/error', ['message' => $message]);
    }
}
