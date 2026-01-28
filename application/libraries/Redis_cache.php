<?php defined('BASEPATH') or exit('No direct script access allowed');

class Redis_cache {

    protected $client;
    protected $cfg;
    private $EndReturnData;

    public function __construct() {

        $CI = &get_instance();
        $CI->load->config('redis', TRUE);
        $this->cfg = $CI->config->item('redis')['redis'];

        $this->client = new Redis();
        $connected = $this->client->connect($this->cfg['host'], $this->cfg['port'], $this->cfg['timeout']);
        if (!$connected) {
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

    public function set(string $key, $value, int $ttl = 300) {

        $this->EndReturnData = new stdClass();
        try {

            $payload = is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR);

            $this->client->set($key, $payload, ['ex' => $ttl]);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Key     = $key;
            $this->EndReturnData->Value   = $value;
            $this->EndReturnData->TTL     = $ttl;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Key     = $key;
            $this->EndReturnData->Value   = NULL;
            $this->EndReturnData->TTL     = $ttl;
        }
        return $this->EndReturnData;
    }

    public function get(string $key) {

        $this->EndReturnData = new stdClass();
        try {

            $val = $this->client->get($key);
            if ($val === FALSE || $val === NULL) {
                $this->EndReturnData->Error   = TRUE;
                $this->EndReturnData->Message = 'Key not found';
                $this->EndReturnData->Key     = $key;
                $this->EndReturnData->Value   = NULL;
            } else {
                $json  = json_decode($val);
                $value = (json_last_error() === JSON_ERROR_NONE) ? $json : $val;

                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Success';
                $this->EndReturnData->Key     = $key;
                $this->EndReturnData->Value   = $value;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Key     = $key;
            $this->EndReturnData->Value   = NULL;
        }

        return $this->EndReturnData;
    }

    public function delete(string $key) {
        $this->EndReturnData = new stdClass();

        try {
            $deleted = $this->client->del($key);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = ($deleted > 0) ? 'Deleted' : 'Key not found';
            $this->EndReturnData->Key     = $key;
            $this->EndReturnData->Deleted = (bool) $deleted;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Key     = $key;
            $this->EndReturnData->Deleted = FALSE;
        }

        return $this->EndReturnData;
    }

    public function flush() {
        $this->EndReturnData = new stdClass();

        try {
            $result = $this->client->flushDB();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Database flushed';
            $this->EndReturnData->Result  = $result;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Result  = FALSE;
        }

        return $this->EndReturnData;
    }
    
}
