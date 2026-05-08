<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared print / template-token helper.
 * Used by Transactions, Payments, and any controller that renders receipt HTML.
 */

if (!function_exists('print_number_to_words')) {
    function print_number_to_words(float $amount): string {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function(int $n) use (&$convert, $ones, $tens): string {
            if ($n === 0)      return '';
            if ($n < 20)       return $ones[$n] . ' ';
            if ($n < 100)      return $tens[(int)($n / 10)] . ' ' . $convert($n % 10);
            if ($n < 1000)     return $ones[(int)($n / 100)] . ' Hundred ' . $convert($n % 100);
            if ($n < 100000)   return $convert((int)($n / 1000))   . 'Thousand ' . $convert($n % 1000);
            if ($n < 10000000) return $convert((int)($n / 100000)) . 'Lakh '     . $convert($n % 100000);
            return $convert((int)($n / 10000000)) . 'Crore ' . $convert($n % 10000000);
        };

        $rupees = (int) $amount;
        $paise  = (int) round(($amount - $rupees) * 100);
        $words  = trim($convert($rupees));
        $result = $words ? $words . ' Rupees' : 'Zero Rupees';
        if ($paise > 0) {
            $result .= ' and ' . trim($convert($paise)) . ' Paise';
        }
        return $result . ' Only';
    }
}

if (!function_exists('print_process_conditionals')) {
    /**
     * Collapses {{IF:TOKEN}}...{{/IF:TOKEN}} blocks.
     * Keeps inner content when the token's resolved value is non-empty; removes the block otherwise.
     */
    function print_process_conditionals(string $html, array $tokens): string {
        return preg_replace_callback(
            '/\{\{IF:([A-Z0-9_]+)\}\}(.*?)\{\{\/IF:\1\}\}/s',
            function ($m) use ($tokens) {
                $value = trim($tokens['{{' . $m[1] . '}}'] ?? '');
                return $value !== '' ? $m[2] : '';
            },
            $html
        );
    }
}

if (!function_exists('print_apply_tokens')) {
    /**
     * Full pipeline: resolve {{IF:TOKEN}} conditionals, then replace all {{TOKEN}} placeholders.
     * Use this instead of a bare str_replace so conditional blocks are always handled.
     */
    function print_apply_tokens(string $html, array $tokens): string {
        $html = print_process_conditionals($html, $tokens);
        return str_replace(array_keys($tokens), array_values($tokens), $html);
    }
}

if (!function_exists('print_build_qr_html')) {
    /**
     * Generates a UPI QR code HTML block (with org logo overlaid at centre).
     * Returns empty string when $upiId is empty.
     */
    function print_build_qr_html(string $upiId, float $amount, string $orgName, string $orgLogo = ''): string {
        if (empty($upiId)) return '';
        $e      = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
        $netAmt = number_format($amount, 2, '.', '');
        $upiStr = 'upi://pay?pa=' . rawurlencode($upiId)
                . '&pn=' . rawurlencode($orgName)
                . '&am=' . $netAmt
                . '&cu=INR';
        $qrUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&ecc=H&data=' . rawurlencode($upiStr);
        $logoSrc = !empty($orgLogo)
            ? $e($orgLogo)
            : 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
        return '<div style="position:relative;display:inline-block;line-height:0;">'
            . '<img src="' . $qrUrl . '" width="150" height="150">'
            . '<div class="qr-logo-overlay" style="position:absolute;top:50%;left:50%;'
                . 'transform:translate(-50%,-50%);width:38px;height:38px;'
                . 'background:#fff;border-radius:4px;padding:3px;box-sizing:border-box;">'
            . '<img src="' . $logoSrc . '" style="width:100%;height:100%;object-fit:contain;">'
            . '</div>'
            . '</div>';
    }
}
