<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Curlservice {

    protected $CI;
    private $EndReturnData;

    public function __construct() {
        $this->CI = &get_instance();
    }


    public function retrieve($URL, $Method, $PostData) {

        $this->EndReturnData = new stdClass();
        try {

            $ch = curl_init($URL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $Method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            
            $JsonResp = json_decode(curl_exec($ch), TRUE);

            curl_close($ch);

            if(sizeof($JsonResp) > 0) {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Data = $JsonResp;
                $this->EndReturnData->Message = 'Data Received';
                
            } else {
                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = 'Unable to retrieve Data';
            }

        } catch (Exception $e) {
            
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return  $this->EndReturnData;

    }

    public function request($method, $url, $data = [], $headers = []) {

        $curl = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json'
        ];

        $finalHeaders = array_merge($defaultHeaders, $headers);

        switch (strtoupper($method)) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;

            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;

            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;

            default: // GET
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                }
                break;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $finalHeaders
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['error' => true, 'message' => $err];
        }

        return json_decode($response, true); // Return as associative array
    }

}