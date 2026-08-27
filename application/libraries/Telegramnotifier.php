<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Telegram Error Notifier
 *
 * Sends formatted error/alert messages to a Telegram chat via Bot API.
 * Uses fire-and-forget cURL with a short timeout so it never blocks the app.
 *
 * Usage (static â€” works anywhere, no CI load needed):
 *   Telegramnotifier::error('Something broke', $exception);
 *   Telegramnotifier::alert('Low stock warning', ['product' => 'Widget']);
 *
 * Config via .env:
 *   TELEGRAM_BOT_TOKEN   â€” token from @BotFather
 *   TELEGRAM_CHAT_ID     â€” your personal or group chat ID
 */
class Telegramnotifier
{
    private static string $apiBase = 'https://api.telegram.org/bot';

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Public API
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Send an error notification (red alert â€” exceptions, DB failures, etc.)
     *
     * @param string         $title   Short description of where the error occurred
     * @param Throwable|null $e       The caught exception (optional)
     * @param array          $context Extra keyâ†’value pairs to include in the message
     * @return void
     */
    public static function error(string $title, ?Throwable $e = null, array $context = []): void
    {
        $lines   = [];
        $lines[] = 'ðŸš¨ <b>ERROR</b>';
        $lines[] = '<b>' . self::_esc($title) . '</b>';
        $lines[] = '';

        if ($e !== null) {
            $lines[] = 'ðŸ“‹ <b>Message:</b> ' . self::_esc($e->getMessage());
            $lines[] = 'ðŸ“ <b>File:</b> <code>' . self::_esc(self::_shortPath($e->getFile())) . ':' . $e->getLine() . '</code>';
        }

        if (!empty($context)) {
            $lines[] = '';
            foreach ($context as $key => $val) {
                $lines[] = "â€¢ <b>{$key}:</b> " . self::_esc((string)$val);
            }
        }

        $lines[] = '';
        $lines[] = 'ðŸŒ <b>URL:</b> <code>' . self::_esc(self::_currentUrl()) . '</code>';
        $lines[] = 'ðŸ• <b>Time:</b> ' . date('d M Y  H:i:s');
        $lines[] = 'âš™ï¸ <b>Env:</b> ' . self::_esc(getenv('CI_ENV') ?: 'unknown');

        self::_send(implode("\n", $lines));
    }

    /**
     * Send a plain informational alert (no exception required).
     *
     * @param string $title
     * @param array  $context
     * @return void
     */
    public static function alert(string $title, array $context = []): void
    {
        $lines   = [];
        $lines[] = 'âš ï¸ <b>ALERT</b>';
        $lines[] = '<b>' . self::_esc($title) . '</b>';

        if (!empty($context)) {
            $lines[] = '';
            foreach ($context as $key => $val) {
                $lines[] = "â€¢ <b>{$key}:</b> " . self::_esc((string)$val);
            }
        }

        $lines[] = '';
        $lines[] = 'ðŸ• <b>Time:</b> ' . date('d M Y  H:i:s');

        self::_send(implode("\n", $lines));
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Internal helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * @param string $message  HTML-formatted message text
     * @return void
     */
    private static function _send(string $message): void
    {
        $token  = getenv('TELEGRAM_BOT_TOKEN');
        $chatId = getenv('TELEGRAM_CHAT_ID');

        if (empty($token) || empty($chatId)) {
            return;
        }

        $url     = self::$apiBase . $token . '/sendMessage';
        $payload = json_encode([
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,       // never block the request
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
        } elseif ($response) {
            $decoded = json_decode($response, true);
            if (!($decoded['ok'] ?? false)) {
            }
        }
    }

    /**
     * Escape HTML special characters for Telegram HTML parse mode.
     * @param string $text
     * @return string
     */
    private static function _esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Strip the server document root from a file path for cleaner display.
     * @param string $path
     * @return string
     */
    private static function _shortPath(string $path): string
    {
        $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        return $root ? str_replace($root, '', $path) : $path;
    }

    /**
     * Build the current request URL.
     * @return string
     */
    private static function _currentUrl(): string
    {
        if (php_sapi_name() === 'cli') return 'CLI';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}
