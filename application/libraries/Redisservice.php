<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Predis\Client;

/**
 * RedisService — single, centralized Redis client for the entire application.
 *
 * Both Cacheservice and Redis_cache are thin wrappers around this class.
 * Key naming convention:
 *   session:{token}       — JWT payload data
 *   active_session:{uid}  — single-session enforcement token
 *   menus:{uid}           — role main menus          (was Redis_UserMainModule)
 *   submenus:{uid}        — role sub menus           (was Redis_UserSubModule)
 *   modules:{uid}         — org module info          (was Redis_UserModuleInfo)
 *   permissions:{uid}     — role permissions         (was Redis_UserPermissions)
 *   settings:{uid}        — org general settings     (was Redis_UserGenSettings)
 *   userinfo:{uid}        — user row                 (was Redis_UserInfo)
 *   <anything else>       — stored as-is (model/org-level keys)
 */
class RedisService {

    /** @var Client */
    private $client;

    private $connected = false;

    // Old flat key names → new semantic prefix (resolved with UserUID at read-time)
    private static $keyAliases = [
        'Redis_UserMainModule'  => 'menus',
        'Redis_UserSubModule'   => 'submenus',
        'Redis_UserModuleInfo'  => 'modules',
        'Redis_UserPermissions' => 'permissions',
        'Redis_UserGenSettings' => 'settings',
        'Redis_UserInfo'        => 'userinfo',
    ];

    public function __construct() {
        $this->connect();
    }

    // ─── Connection ──────────────────────────────────────────────────────────

    private function connect() {
        try {
            $scheme = getenv('REDIS_PROTOCOL') ?: 'redis';
            $params = [
                'scheme'   => $scheme,
                'host'     => getenv('REDIS_HOST'),
                'port'     => (int)(getenv('REDIS_PORT') ?: 6379),
                'database' => (int)(getenv('REDIS_DATABASE') ?: 0),
            ];
            if (getenv('REDIS_USERNAME')) $params['username'] = getenv('REDIS_USERNAME');
            if (getenv('REDIS_PASSWORD')) $params['password'] = getenv('REDIS_PASSWORD');

            $this->client    = new Client($params);
            $this->client->ping();
            $this->connected = true;
            $this->log('INFO', 'Connection established');
        } catch (Exception $e) {
            $this->connected = false;
            $this->log('ERROR', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function isConnected() {
        return $this->connected;
    }

    // ─── Key resolution ──────────────────────────────────────────────────────

    /** Maps CI_ENV value to a short environment tag used in Redis keys. */
    private function envShort() {
        $env = defined('ENVIRONMENT') ? ENVIRONMENT : (getenv('CI_ENV') ?: 'production');
        $map = ['development' => 'dev', 'staging' => 'stg', 'production' => 'prod'];
        return $map[$env] ?? $env;
    }

    /**
     * Build the org-level key prefix: {SHORTCODE}:{OrgToken}:{env}
     * Auto-resolves ShortCode/OrgToken from JWT when not supplied explicitly.
     * Returns '' when neither is available (callers fall back to bare key names).
     */
    private function buildOrgPrefix($shortCode = '', $token = '') {
        if (empty($shortCode) || empty($token)) {
            try {
                $CI        = &get_instance();
                $user      = $CI->pageData['JwtData']->User;
                $shortCode = $shortCode ?: ($user->OrgShortCode ?? '');
                $token     = $token     ?: ($user->OrgToken     ?? '');
            } catch (Exception $e) {}
        }
        if (empty($shortCode) || empty($token)) return '';
        return strtolower($shortCode) . ':' . strtolower($token) . ':' . $this->envShort();
    }

    /**
     * Translates legacy flat key names to new user-scoped keys when a valid
     * authenticated session is present. Unknown keys are returned unchanged.
     */
    private function resolveKey($key) {
        if (!isset(self::$keyAliases[$key])) {
            return $key;
        }
        $prefix = self::$keyAliases[$key];
        $uid    = 'global';
        try {
            $CI  = &get_instance();
            $uid = $CI->pageData['JwtData']->User->UserUID ?? 'global';
        } catch (Exception $e) {}
        return $this->userScopedKey($prefix, $uid);
    }

    /**
     * Build a user-scoped key: {ShortCode}:{OrgToken}:{env}:{type}:{uid}
     * Falls back to {type}:{uid} when the org prefix is unavailable.
     */
    private function userScopedKey($type, $uid, $shortCode = '', $token = '') {
        $prefix = $this->buildOrgPrefix($shortCode, $token);
        return ($prefix !== '') ? "{$prefix}:{$type}:{$uid}" : "{$type}:{$uid}";
    }

    /**
     * Build an org-level key (no UID): {ShortCode}:{OrgToken}:{env}:{type}
     * Falls back to {type} when the org prefix is unavailable.
     */
    public function orgKey($type, $shortCode = '', $token = '') {
        $prefix = $this->buildOrgPrefix($shortCode, $token);
        return ($prefix !== '') ? "{$prefix}:{$type}" : $type;
    }

    // ─── Core cache methods ──────────────────────────────────────────────────

    public function setCache($key, $value, $ttl = 300) {
        $result      = new stdClass();
        $resolvedKey = $this->resolveKey($key);
        try {
            if (!$this->connected) $this->connect();
            $this->client->set($resolvedKey, json_encode($value));
            $this->client->expire($resolvedKey, (int)$ttl);
            $result->Error   = false;
            $result->Message = 'Cached';
            $result->Key     = $resolvedKey;
            $result->TTL     = (int)$ttl;
            $this->log('SET', "{$resolvedKey} ttl={$ttl}");
        } catch (Exception $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
            $result->Key     = $resolvedKey;
            $result->TTL     = (int)$ttl;
            $this->log('ERROR', "SET {$resolvedKey}: " . $e->getMessage());
        }
        return $result;
    }

    public function getCache($key) {
        $result      = new stdClass();
        $resolvedKey = $this->resolveKey($key);
        try {
            if (!$this->connected) $this->connect();
            $raw = $this->client->get($resolvedKey);
            if ($raw === null) {
                $result->Error   = true;
                $result->Message = 'Cache miss';
                $result->Key     = $resolvedKey;
                $result->Value   = null;
                $this->log('MISS', "GET {$resolvedKey}");
            } else {
                $decoded         = json_decode($raw);
                $result->Error   = false;
                $result->Message = 'Cache hit';
                $result->Key     = $resolvedKey;
                $result->Value   = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $raw;
                $result->TTL     = (int)$this->client->ttl($resolvedKey);
                $this->log('HIT', "GET {$resolvedKey}");
            }
        } catch (Exception $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
            $result->Key     = $resolvedKey;
            $result->Value   = null;
            $this->log('ERROR', "GET {$resolvedKey}: " . $e->getMessage());
        }
        return $result;
    }

    public function deleteCache($key) {
        $result      = new stdClass();
        $resolvedKey = $this->resolveKey($key);
        try {
            if (!$this->connected) $this->connect();
            $deleted         = (int)$this->client->del($resolvedKey);
            $result->Error   = false;
            $result->Message = $deleted > 0 ? 'Deleted' : 'Key not found';
            $result->Key     = $resolvedKey;
            $result->Deleted = (bool)$deleted;
            $this->log('DEL', $resolvedKey);
        } catch (Exception $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
            $result->Key     = $resolvedKey;
            $result->Deleted = false;
            $this->log('ERROR', "DEL {$resolvedKey}: " . $e->getMessage());
        }
        return $result;
    }

    public function cacheExists($key) {
        $resolvedKey = $this->resolveKey($key);
        try {
            if (!$this->connected) $this->connect();
            return (bool)$this->client->exists($resolvedKey);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete all keys matching a glob pattern using SCAN (production-safe).
     * Returns the count of deleted keys.
     */
    public function clearCacheByPattern($pattern) {
        $count  = 0;
        $cursor = null;
        try {
            if (!$this->connected) $this->connect();
            do {
                $result = $this->client->scan($cursor, ['match' => $pattern, 'count' => 100]);
                $cursor = $result[0];
                $keys   = $result[1];
                if (!empty($keys)) {
                    $this->client->del($keys);
                    $count += count($keys);
                }
            } while ($cursor !== '0');
            $this->log('SCAN', "pattern={$pattern} deleted={$count}");
        } catch (Exception $e) {
            $this->log('ERROR', "SCAN {$pattern}: " . $e->getMessage());
        }
        return $count;
    }

    // ─── User-scoped helpers (called with explicit UserUID) ──────────────────

    /**
     * Store user-scoped cache. $type is the semantic name:
     *   menus, submenus, modules, permissions, settings, userinfo
     */
    public function setUserCache($type, $userUID, $value, $ttl = 0, $shortCode = '', $token = '') {
        $ttl = $ttl ?: (int)(getenv('LOGIN_EXPIRE_SECS') ?: 7200);
        return $this->setCache($this->userScopedKey($type, $userUID, $shortCode, $token), $value, $ttl);
    }

    /**
     * Read user-scoped cache. Returns the VALUE directly (null on miss).
     * $userUID is auto-resolved from JwtData when 0.
     */
    public function getUserCache($type, $userUID = 0) {
        if (!$userUID) {
            try {
                $CI      = &get_instance();
                $userUID = $CI->pageData['JwtData']->User->UserUID ?? 0;
            } catch (Exception $e) {}
        }
        if (!$userUID) return null;
        return $this->getCache($this->userScopedKey($type, $userUID))->Value;
    }

    /** Delete one user-scoped cache type. */
    public function deleteUserCache($type, $userUID) {
        $this->deleteCache($this->userScopedKey($type, $userUID));
    }

    /** Delete ALL six standard user-scoped keys for a given user at once. */
    public function deleteAllUserCache($userUID, $shortCode = '', $token = '') {
        foreach (array_values(self::$keyAliases) as $type) {
            $this->deleteCache($this->userScopedKey($type, $userUID, $shortCode, $token));
        }
    }

    // ─── Dev / Monitor helpers ───────────────────────────────────────────────

    /**
     * Scan every key in the DB and return key name, type, TTL, and decoded value.
     * Used by the cache monitor page only.
     */
    public function getAllKeysData($pattern = '*') {
        $result = [];
        $cursor = null;
        try {
            if (!$this->connected) $this->connect();
            do {
                $scan   = $this->client->scan($cursor, ['match' => $pattern, 'count' => 200]);
                $cursor = $scan[0];
                $keys   = $scan[1];
                foreach ($keys as $key) {
                    try {
                        $type = $this->client->type($key)->getPayload();
                        $ttl  = (int)$this->client->ttl($key);
                        $raw  = ($type === 'string') ? $this->client->get($key) : null;
                        $val  = null;
                        if ($raw !== null) {
                            $dec = json_decode($raw);
                            $val = (json_last_error() === JSON_ERROR_NONE) ? $dec : $raw;
                        }
                        // Memory usage in bytes (MEMORY USAGE includes key+value+overhead)
                        $size = 0;
                        try {
                            $mem  = $this->client->executeRaw(['MEMORY', 'USAGE', $key, 'SAMPLES', '0']);
                            $size = (int)$mem;
                        } catch (Exception $me) {
                            $size = $raw !== null ? strlen($raw) : 0;
                        }
                        $result[] = ['key' => $key, 'type' => $type, 'ttl' => $ttl, 'size' => $size, 'value' => $val];
                    } catch (Exception $e) {
                        $result[] = ['key' => $key, 'type' => 'unknown', 'ttl' => -1, 'size' => 0, 'value' => null];
                    }
                }
            } while ($cursor !== '0');
            usort($result, fn($a, $b) => strcmp($a['key'], $b['key']));
        } catch (Exception $e) {
            $this->log('ERROR', 'getAllKeysData: ' . $e->getMessage());
        }
        return $result;
    }

    /** Delete a key by its exact name — skips alias resolution. Used by cache monitor. */
    public function deleteExactKey($key) {
        try {
            if (!$this->connected) $this->connect();
            $deleted = (int)$this->client->del($key);
            $this->log('DEL', $key . ' (exact)');
            return (bool)$deleted;
        } catch (Exception $e) {
            $this->log('ERROR', "DEL {$key} (exact): " . $e->getMessage());
            return false;
        }
    }

    // ─── Admin helpers ───────────────────────────────────────────────────────

    /** Flush the entire Redis database (use with caution). */
    public function flush() {
        $result = new stdClass();
        try {
            if (!$this->connected) $this->connect();
            $this->client->flushdb();
            $result->Error   = false;
            $result->Message = 'Database flushed';
            $this->log('FLUSH', 'flushdb called');
        } catch (Exception $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
            $this->log('ERROR', 'FLUSH: ' . $e->getMessage());
        }
        return $result;
    }

    // ─── Session helpers ─────────────────────────────────────────────────────

    /** Store JWT payload under session:{token} */
    public function storeSession($token, $data, $ttl = 0) {
        $ttl = $ttl ?: (int)(getenv('LOGIN_EXPIRE_SECS') ?: 7200);
        return $this->setCache("session:{$token}", $data, $ttl);
    }

    public function getSession($token) {
        return $this->getCache("session:{$token}");
    }

    public function removeSession($token) {
        return $this->deleteCache("session:{$token}");
    }

    // ─── Logging ─────────────────────────────────────────────────────────────

    private function log($level, $message) {
        if (!getenv('REDIS_LOGGING_ENABLED')) return;
        $line    = '[' . date('Y-m-d H:i:s') . '] [REDIS:' . $level . '] ' . $message . PHP_EOL;
        $logFile = APPPATH . 'logs/redis-' . date('Y-m-d') . '.log';
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

}
