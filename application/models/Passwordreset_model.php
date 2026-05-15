<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Passwordreset_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getUserByEmail(string $email) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('UserUID, FirstName, LastName, EmailAddress, UserName');
        $this->ReadDb->from('Users.UserTbl');
        $this->ReadDb->where('EmailAddress', $email);
        $this->ReadDb->where('IsActive', 1);
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return $q ? $q->row() : null;
    }

    /**
     * Returns token row joined with user name/email only if token is:
     *   - not yet used
     *   - not expired (ExpiresAt > NOW())
     */
    public function getValidToken(string $token) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('r.ResetUID, r.UserUID, r.ExpiresAt, u.FirstName, u.EmailAddress');
        $this->ReadDb->from('Users.PasswordResetTbl r');
        $this->ReadDb->join('Users.UserTbl u', 'u.UserUID = r.UserUID', 'left');
        $this->ReadDb->where('r.Token', $token);
        $this->ReadDb->where('r.IsUsed', 0);
        $this->ReadDb->where('r.ExpiresAt >', date('Y-m-d H:i:s'));
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return $q ? $q->row() : null;
    }

    /**
     * Check whether the token exists at all (for expired vs. invalid distinction).
     */
    public function tokenExists(string $token) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('ResetUID, IsUsed, ExpiresAt');
        $this->ReadDb->from('Users.PasswordResetTbl');
        $this->ReadDb->where('Token', $token);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return $q ? $q->row() : null;
    }
}
