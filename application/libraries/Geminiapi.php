<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Geminiapi — thin cURL wrapper for Google Gemini Flash.
 * No external dependencies; works in the existing Docker PHP environment.
 *
 * Usage (from a controller):
 *   $this->load->library('Geminiapi');
 *   $reply = $this->geminiapi->chat($systemPrompt, $history, $userMessage);
 */
class Geminiapi {

    private string $_apiKey;
    private string $_model;
    private int    $_maxTokens;
    private float  $_temperature = 0.25;

    public function __construct() {
        $CI =& get_instance();
        $CI->config->load('ai_config', true);
        $this->_apiKey    = (string)($CI->config->item('gemini_api_key',            'ai_config') ?? '');
        $this->_model     = (string)($CI->config->item('gemini_model',              'ai_config') ?? 'gemini-1.5-flash');
        $this->_maxTokens = (int)   ($CI->config->item('gemini_max_output_tokens',  'ai_config') ?? 400);
    }

    /**
     * Send a chat message to Gemini with optional conversation history.
     *
     * @param string $systemPrompt  Business context + behavioral instructions
     * @param array  $history       Prior turns: [['role'=>'user'|'model','text'=>string], ...]
     * @param string $userMessage   The new user question
     * @returns string  AI reply text (or a user-safe error string on failure)
     */
    public function chat(string $systemPrompt, array $history, string $userMessage): string {
        if (empty($this->_apiKey)) {
            return 'AI assistant is not configured yet. Please set the Gemini API key in application/config/ai_config.php.';
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            urlencode($this->_model),
            urlencode($this->_apiKey)
        );

        // Build contents from history then add the new user turn
        $contents = [];
        foreach ($history as $turn) {
            $contents[] = [
                'role'  => $turn['role'],
                'parts' => [['text' => (string)$turn['text']]],
            ];
        }
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents'           => $contents,
            'generationConfig'   => [
                'temperature'     => $this->_temperature,
                'maxOutputTokens' => $this->_maxTokens,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            log_message('error', '[Geminiapi] cURL error: ' . $err);
            return 'Network error connecting to AI service. Please try again.';
        }

        $data = @json_decode($raw, true);

        if (!is_array($data)) {
            log_message('error', '[Geminiapi] Unparseable response (HTTP ' . $code . '): ' . substr($raw, 0, 300));
            return 'AI service returned an unexpected response. Please try again.';
        }

        if (!empty($data['error'])) {
            log_message('error', '[Geminiapi] API error: ' . json_encode($data['error']));
            $msg = $data['error']['message'] ?? 'Unknown error';
            if ($code === 400) return 'Request was rejected by AI service. Try rephrasing your question.';
            if ($code === 429) return 'AI service rate limit reached. Please wait a moment and try again.';
            return 'AI service error: ' . $msg;
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text === null) {
            log_message('error', '[Geminiapi] No text in response: ' . json_encode($data));
            return 'I couldn\'t generate a response. Please try rephrasing your question.';
        }

        return trim($text);
    }
}
