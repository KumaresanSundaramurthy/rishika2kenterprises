<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('detectTextLang')) {
    /**
     * Detects whether text is Tamil or English by scanning for Tamil Unicode characters.
     * Tamil Unicode block: U+0B80–U+0BFF
     * @param string $text Input text to inspect
     * @returns string 'ta' if Tamil script detected, 'en' otherwise
     */
    function detectTextLang(string $text): string {
        return preg_match('/[\x{0B80}-\x{0BFF}]/u', $text) ? 'ta' : 'en';
    }
}

if (!function_exists('translateViaMymemory')) {
    /**
     * Translates text using the MyMemory free translation API.
     * Returns original text unchanged on any failure (fail-safe).
     * @param string $text Input text to translate
     * @param string $from Source language code e.g. 'en', 'ta'
     * @param string $to   Target language code e.g. 'ta', 'en'
     * @returns string Translated text, or original if translation fails
     */
    function translateViaMymemory(string $text, string $from, string $to): string {
        $text = trim($text);
        if ($text === '' || $from === $to) return $text;

        $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($text)
             . '&langpair=' . urlencode($from . '|' . $to);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response) return $text;

        $data       = json_decode($response, true);
        $translated = $data['responseData']['translatedText'] ?? '';

        if ($translated === '' || ($data['responseStatus'] ?? 0) != 200) return $text;

        /* MyMemory sometimes prefixes proper nouns with "- " — strip it */
        $translated = trim($translated, "- \t\n\r\0\x0B");

        return $translated !== '' ? $translated : $text;
    }
}
