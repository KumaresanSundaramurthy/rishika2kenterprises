<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Assistant — AI Business Assistant endpoints.
 * Requires valid JWT session (inherits MY_Controller auth).
 */
class Assistant extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * POST assistant/chat
     * Body: message (string), history (JSON string of [{role,text},...])
     * Returns: {Error, Message, Reply}
     *
     * @returns void
     */
    public function chat(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            if (!$jwtData) throw new Exception('Unauthorized');

            // Soft session rate limit — prevents runaway costs from one user
            $this->load->library('session');
            $sessionCalls = (int)$this->session->userdata('ai_calls') ?? 0;
            $this->load->config('ai_config', true);
            $sessionLimit = (int)($this->config->item('gemini_session_limit', 'ai_config') ?? 30);
            if ($sessionCalls >= $sessionLimit) {
                throw new Exception('AI request limit reached for this session. Please log out and back in to reset.');
            }

            $orgUID    = $this->_orgUID();
            $branchUID = $this->_branchUID();
            $gs        = $jwtData->GenSettings  ?? new stdClass();
            $orgName   = htmlspecialchars($jwtData->Org->OrgName ?? 'Your Business', ENT_QUOTES, 'UTF-8');
            $currency  = $this->_currency();
            $dec       = $this->_decimals();
            $userName  = htmlspecialchars($jwtData->User->Name ?? 'there', ENT_QUOTES, 'UTF-8');
            $today     = date('d M Y');

            // Validate input
            $rawMsg = trim((string)($this->input->post('message') ?? ''));
            if ($rawMsg === '') throw new Exception('Message cannot be empty.');
            if (mb_strlen($rawMsg) > 600) throw new Exception('Message is too long (maximum 600 characters).');

            // Validate and sanitise history
            $historyJson  = (string)($this->input->post('history') ?? '[]');
            $historyRaw   = @json_decode($historyJson, true) ?: [];
            $maxTurns     = (int)($this->config->item('gemini_max_history_turns', 'ai_config') ?? 6);
            if (count($historyRaw) > $maxTurns) {
                $historyRaw = array_slice($historyRaw, -$maxTurns);
            }
            $cleanHistory = [];
            foreach ($historyRaw as $turn) {
                if (!isset($turn['role'], $turn['text'])) continue;
                $role = in_array($turn['role'], ['user', 'model'], true) ? $turn['role'] : 'user';
                $cleanHistory[] = ['role' => $role, 'text' => strip_tags((string)$turn['text'])];
            }

            // Build business context snapshot (ReadDb only)
            $this->load->model('assistant_model');
            $ctx = $this->assistant_model->buildContext($orgUID, $branchUID);

            // Format a number value
            $fmt = static function (float $v): string {
                return smartDecimal($v);
            };

            $sales = $ctx['sales'];
            $change = $sales['last_month'] > 0
                ? round((($sales['this_month'] - $sales['last_month']) / $sales['last_month']) * 100, 1)
                : 0;
            $changeTxt = $change >= 0 ? "+{$change}% vs last month" : "{$change}% vs last month";

            $custList = [];
            foreach ($ctx['topCust'] as $r) {
                $custList[] = trim($r->Name ?? '') . ' (' . $currency . $fmt((float)($r->total ?? 0)) . ')';
            }
            $prodList = [];
            foreach ($ctx['topProd'] as $r) {
                $prodList[] = trim($r->ItemName ?? '') . ' (' . $fmt((float)($r->qty_sold ?? 0)) . ' units)';
            }
            $todayCtx = $ctx['today'];

            $systemPrompt  = "You are a smart business assistant for {$orgName}, an agricultural machinery ERP.\n";
            $systemPrompt .= "The user is {$userName}. Today is {$today}.\n";
            $systemPrompt .= "Answer questions about the business data below clearly and concisely — 2 to 4 sentences maximum.\n";
            $systemPrompt .= "Format monetary amounts with commas. Always use this currency symbol: {$currency}\n";
            $systemPrompt .= "If asked something not found in the data, say so honestly — do not fabricate data.\n\n";
            $systemPrompt .= "=== LIVE BUSINESS SNAPSHOT ===\n";
            $systemPrompt .= "This Month Sales: {$currency}{$fmt($sales['this_month'])} ({$sales['this_count']} invoices, {$changeTxt})\n";
            $systemPrompt .= "Last Month Sales: {$currency}{$fmt($sales['last_month'])}\n";
            $systemPrompt .= "Total Customer Receivable (outstanding): {$currency}{$fmt($ctx['receivable'])}\n";
            $systemPrompt .= "Total Vendor Payable (outstanding): {$currency}{$fmt($ctx['payable'])}\n";
            $systemPrompt .= "Overdue Invoices: {$ctx['overdue']}\n";
            $systemPrompt .= "Low Stock Products: {$ctx['lowstock']}\n";
            $systemPrompt .= "Today's Transactions: {$todayCtx['total']} total ";
            $systemPrompt .= "({$todayCtx['invoices']} invoices, {$todayCtx['purchases']} purchases, {$todayCtx['payments']} payments)\n";
            $systemPrompt .= empty($custList) ? "" : "Top 5 Customers This Month: " . implode('; ', $custList) . "\n";
            $systemPrompt .= empty($prodList) ? "" : "Top 5 Products This Month: "  . implode('; ', $prodList) . "\n";

            // Call Gemini Flash
            $this->load->library('Geminiapi');
            $reply = $this->geminiapi->chat($systemPrompt, $cleanHistory, $rawMsg);

            // Increment session counter only on success
            $this->session->set_userdata('ai_calls', $sessionCalls + 1);

            $out->Error   = false;
            $out->Message = 'OK';
            $out->Reply   = $reply;

        } catch (Exception $e) {
            $this->notifyError('Assistant::chat', $e);
            $out->Error   = true;
            $out->Message = $e->getMessage();
            $out->Reply   = null;
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($out))
            ->_display();
        exit;
    }
}
