<?php defined('BASEPATH') or exit('No direct script access allowed');

class Redis_cache {

    protected $client;
    protected $cfg;

    public function __construct() {

        $CI = &get_instance();
        $CI->load->config('redis', TRUE);
        $this->cfg = $CI->config->item('redis')['redis'];

        $this->client = new Redis();
        $connected = $this->client->connect($this->cfg['host'], $this->cfg['port'], $this->cfg['timeout']);
        if (!$connected) {
            log_message('error', 'Redis connect failed');
            $this->client = NULL;
            return;
        }
        if (!empty($this->cfg['password'])) {
            $this->client->auth($this->cfg['password']);
        }
        if (isset($this->cfg['database'])) {
            $this->client->select((int)$this->cfg['database']);
        }
    }

    public function available(): bool {
        return $this->client instanceof Redis;
    }

    public function set(string $key, $value, int $ttl = 300): bool {
        if (!$this->available()) return FALSE;
        $payload = is_string($value) ? $value : json_encode($value);
        return $this->client->set($key, $payload, ['ex' => $ttl]);
    }

    public function get(string $key) {
        if (!$this->available()) return NULL;
        $val = $this->client->get($key);
        if ($val === FALSE || $val === NULL) return NULL;
        // Try JSON decode; if fails, return raw
        $json = json_decode($val);
        return (json_last_error() === JSON_ERROR_NONE) ? $json : $val;
    }

    public function delete(string $key): bool {
        if (!$this->available()) return FALSE;
        return (bool)$this->client->del($key);
    }

    public function flush(): bool {
        if (!$this->available()) return FALSE;
        return $this->client->flushDB();
    }
    
}
