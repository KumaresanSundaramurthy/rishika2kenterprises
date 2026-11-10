<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * GET a services endpoint and return the decoded JSON body, or null on failure.
 *
 * @param string   $endpoint  Path after base URL, e.g. 'api/auth/login/config'
 * @param string[] $headers   Extra HTTP headers
 * @param int      $timeout   cURL timeout in seconds
 * @return object|null
 */
function services_get(string $endpoint, array $headers = [], int $timeout = 10): ?object
{
    $base = rtrim(getenv('SERVICES_BASE_URL') ?: '', '/');
    if (!$base) return null;

    $ch = curl_init($base . '/' . ltrim($endpoint, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($raw && $httpCode === 200) ? json_decode($raw) : null;
}

/**
 * POST JSON to a services endpoint and return the decoded response body.
 * Returns null when the base URL is not configured, cURL fails, or the response
 * cannot be decoded. The raw HTTP status and body are available via $httpCode and
 * $rawBody out-parameters when the caller needs them.
 *
 * @param string   $endpoint  Path after base URL, e.g. 'api/auth/login'
 * @param array    $payload   Data to JSON-encode as the request body
 * @param string[] $headers   Extra HTTP headers (Content-Type: application/json added automatically)
 * @param int      $timeout   cURL timeout in seconds
 * @param int|null $httpCode  OUT: actual HTTP status code
 * @param string   $rawBody   OUT: raw response body including headers when CURLOPT_HEADER is set
 * @return object|null
 */
function services_post(string $endpoint, array $payload = [], array $headers = [], int $timeout = 10, ?int &$httpCode = null, ?string &$rawBody = null): ?object
{
    $base = rtrim(getenv('SERVICES_BASE_URL') ?: '', '/');
    if (!$base) return null;

    $headers = array_merge(['Content-Type: application/json'], $headers);

    $ch = curl_init($base . '/' . ltrim($endpoint, '/'));
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hdrSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if (!$raw) return null;

    $rawBody = $raw;
    $body    = substr($raw, $hdrSize);
    return json_decode($body) ?: null;
}

/**
 * Fetches a print theme config + template HTML for a transaction type (public endpoint, no JWT).
 * Returns {Error, Data} — use ->Data ?? null; Data is null when no theme is configured.
 *
 * @param int    $orgUID
 * @param string $transactionType  e.g. 'Invoice', 'Payment', 'Quotation'
 * @return object  {Error: bool, Data: object|null}
 */
function get_print_theme_by_type(int $orgUID, string $transactionType): object
{
    $r        = new stdClass();
    $r->Error = TRUE;
    $r->Data  = null;
    try {
        $resp = services_get(
            'api/printthemes/getPrintThemeByType?orgId=' . $orgUID . '&transactionType=' . urlencode($transactionType),
            [],
            10
        );
        if ($resp !== null) {
            $r->Error = $resp->Error ?? TRUE;
            $r->Data  = $resp->Data  ?? null;
        }
    } catch (Exception $e) {
        notifyError('get_print_theme_by_type', $e);
    }
    return $r;
}

/**
 * Fetches org receipt header from services (public endpoint, no JWT required).
 * Returns {Error, Data} — use ->Data ?? null. DevPassword is never included.
 *
 * @param int $orgUID
 * @return object  {Error: bool, Data: object|null}
 */
function get_org_info(int $orgUID): object
{
    $r        = new stdClass();
    $r->Error = TRUE;
    $r->Data  = null;
    try {
        $resp = services_get('api/organisation/getOrgInfo?orgId=' . $orgUID, [], 10);
        if ($resp !== null) {
            $r->Error = $resp->Error ?? TRUE;
            $r->Data  = $resp->Data  ?? null;
        }
    } catch (Exception $e) {
        notifyError('get_org_info', $e);
    }
    return $r;
}
