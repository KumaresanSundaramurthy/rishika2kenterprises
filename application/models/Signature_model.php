<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Signature_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getSignatureList($userUID, $orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'SignatureUID',
                'Label',
                'SignatureType',
                'ImagePath',
                'DrawData',
                'MimeType',
                'FileSize',
                'Width',
                'Height',
                'IsDefault',
                'CreatedOn',
            ]);
            $this->ReadDb->from('Users.UserSignaturesTbl');
            $this->ReadDb->where('UserUID', (int)$userUID);
            $this->ReadDb->where('OrgUID',  (int)$orgUID);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $this->ReadDb->order_by('CreatedOn', 'DESC');
            $query = $this->ReadDb->get();

            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Query failed');
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $query->result();

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = [];
        }

        return $this->EndReturnData;

    }

    public function getSignatureByUID($signatureUID, $userUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'SignatureUID', 'Label', 'SignatureType',
                'ImagePath', 'DrawData', 'MimeType', 'FileSize',
                'Width', 'Height', 'IsDefault',
            ]);
            $this->ReadDb->from('Users.UserSignaturesTbl');
            $this->ReadDb->where('SignatureUID', (int)$signatureUID);
            $this->ReadDb->where('UserUID',      (int)$userUID);
            $this->ReadDb->where('IsDeleted',    0);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();

            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Query failed');
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $query->row();

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = null;
        }

        return $this->EndReturnData;

    }

}
