<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Upstashservice — Serverless Redis cache via Upstash REST API.
 *
 * Wraps every operation in a try/catch; if Upstash is unreachable or
 * misconfigured the application falls back to the primary database
 * transparently — no crash, no user impact.
 *
 * Environment variables required:
 *   UPSTASH_REDIS_REST_URL    — e.g. https://xxxx.upstash.io
 *   UPSTASH_REDIS_REST_TOKEN  — Bearer token from Upstash dashboard
 *
 * Optional:
 *   UPSTASH_LOGGING_ENABLED   — set to "true" to write error logs
 */
class Upstashservice {

    private $url;
    private $token;
    private $enabled;
    private $logging;

    // TTL constants (seconds) — matches spec
    const TTL_CUSTOMER  = 1800;   // 30 min
    const TTL_VENDOR    = 1800;   // 30 min
    const TTL_PRODUCT   = 3600;   // 1 hour
    const TTL_CATEGORY  = 86400;  // 24 hours

    public function __construct() {
        $this->url     = rtrim((string)(getenv('UPSTASH_REDIS_REST_URL')   ?: ''), '/');
        $this->token   = (string)(getenv('UPSTASH_REDIS_REST_TOKEN') ?: '');
        $this->enabled = !empty($this->url) && !empty($this->token);
        $this->logging = (getenv('UPSTASH_LOGGING_ENABLED') === 'true');
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * GET a cached value.
     *
     * @param  string $key
     * @return mixed  Decoded PHP value on HIT, null on MISS or error.
     */
    public function get(string $key) {
        if (!$this->enabled) return null;
        try {
            $raw = $this->cmd(['GET', $key]);
            if ($raw === null) return null;
            $decoded = json_decode($raw, true);
            return ($decoded !== null) ? $decoded : $raw;
        } catch (Exception $e) {
            $this->log("GET [{$key}]", $e->getMessage());
            return null;
        }
    }

    /**
     * SET a value with TTL.
     *
     * @param  string $key
     * @param  mixed  $value  Scalar or array/object — will be JSON-encoded.
     * @param  int    $ttl    Seconds until expiry (default 3600).
     * @return bool
     */
    public function set(string $key, $value, int $ttl = 3600): bool {
        if (!$this->enabled) return false;
        try {
            $payload = is_string($value)
                ? $value
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $cmd = ['SET', $key, $payload];
            if ($ttl > 0) { $cmd[] = 'EX'; $cmd[] = (string)$ttl; }
            return $this->cmd($cmd) === 'OK';
        } catch (Exception $e) {
            $this->log("SET [{$key}]", $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE one or more keys (variadic).
     *
     * @param  string ...$keys
     * @return int  Number of keys actually deleted.
     */
    public function del(string ...$keys): int {
        if (!$this->enabled || empty($keys)) return 0;
        try {
            return (int)$this->cmd(array_merge(['DEL'], $keys));
        } catch (Exception $e) {
            $this->log('DEL [' . implode(', ', $keys) . ']', $e->getMessage());
            return 0;
        }
    }

    /**
     * DELETE keys supplied as an array — convenience wrapper around del().
     *
     * @param  string[] $keys
     * @return int
     */
    public function delMany(array $keys): int {
        if (empty($keys)) return 0;
        return $this->del(...$keys);
    }

    /**
     * Check whether this service is configured and active.
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    // ── Cache key helpers (static) ────────────────────────────────────────────

    public static function keyCustomer(int $id): string        { return "customer:{$id}"; }
    public static function keyVendor(int $id): string          { return "vendor:{$id}"; }
    public static function keyVendorProducts(int $id): string  { return "vendor:{$id}:products"; }
    public static function keyProduct(int $id): string         { return "product:{$id}"; }
    public static function keyProductsAll(): string            { return 'products:all'; }
    public static function keyCategory(int $id): string        { return "category:{$id}"; }
    public static function keyCategoriesAll(): string          { return 'categories:all'; }

    // ── Dev / Monitor helpers ─────────────────────────────────────────────────

    /**
     * Scan every key in Upstash and return key name, type, TTL, and decoded value.
     * Used by the cache monitor page only.
     */
    public function getAllKeysData(): array {
        if (!$this->enabled) return [];
        try {
            $allKeys = [];
            $cursor  = '0';
            do {
                $scan = $this->cmd(['SCAN', $cursor, 'COUNT', '200']);
                if (!is_array($scan) || count($scan) < 2) break;
                $cursor  = (string)$scan[0];
                $keys    = $scan[1];
                if (!empty($keys)) $allKeys = array_merge($allKeys, $keys);
            } while ($cursor !== '0');

            $result = [];
            foreach ($allKeys as $key) {
                try {
                    $type = $this->cmd(['TYPE', $key]);
                    $ttl  = (int)$this->cmd(['TTL', $key]);
                    $raw  = ($type === 'string') ? $this->cmd(['GET', $key]) : null;
                    $val  = null;
                    if ($raw !== null) {
                        $dec = json_decode($raw, true);
                        $val = (json_last_error() === JSON_ERROR_NONE) ? $dec : $raw;
                    }
                    // Size: use MEMORY USAGE (bytes including overhead)
                    $size = 0;
                    try {
                        $mem  = $this->cmd(['MEMORY', 'USAGE', $key, 'SAMPLES', '0']);
                        $size = (int)$mem;
                    } catch (Exception $me) {
                        $size = $raw !== null ? strlen($raw) : 0;
                    }
                    $result[] = ['key' => $key, 'type' => $type ?? 'string', 'ttl' => $ttl, 'size' => $size, 'value' => $val];
                } catch (Exception $e) {
                    $result[] = ['key' => $key, 'type' => 'unknown', 'ttl' => -1, 'size' => 0, 'value' => null];
                }
            }
            usort($result, fn($a, $b) => strcmp($a['key'], $b['key']));
            return $result;
        } catch (Exception $e) {
            $this->log('getAllKeysData', $e->getMessage());
            return [];
        }
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Execute a Redis command via Upstash REST API.
     * POST body: JSON array  e.g. ["SET","key","value","EX","3600"]
     * Response:  {"result": ..., "error": null}
     *
     * @throws Exception on network error or non-200 HTTP response
     */
    private function cmd(array $command) {
        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($command),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body    = curl_exec($ch);
        $status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception("cURL: {$curlErr}");
        }
        if ($status !== 200) {
            throw new Exception("HTTP {$status}");
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new Exception("Invalid JSON response");
        }
        if (!empty($decoded['error'])) {
            throw new Exception("Upstash: " . $decoded['error']);
        }

        return $decoded['result'] ?? null;
    }

    private function log(string $op, string $msg): void {
        if (!$this->logging) return;
        $line = date('Y-m-d H:i:s') . " [Upstash] {$op} failed — {$msg}" . PHP_EOL;
        @file_put_contents(
            APPPATH . 'logs/upstash-' . date('Y-m-d') . '.log',
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}
