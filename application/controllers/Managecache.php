<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Managecache extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /** Clear all user-scoped cache entries for the currently logged-in user. */
    public function clear_all() {
        $org          = $this->pageData['JwtData']->Org ?? null;
        $userUID      = $this->pageData['JwtData']->User->UserUID ?? null;
        $orgToken = $org->OrgToken ?? '';
        if ($userUID) {
            $this->redisservice->deleteAllUserCache($userUID, $orgToken);
        }
        // Also sweep any wildcard user-context patterns (env-prefixed, e.g. P-UserActiveSession_*)
        $this->redisservice->clearCacheByPattern($this->redisservice->envKey('UserActiveSession_') . '*');
        echo "Cache cleared.<br>";
        echo '<a href="/">Go Home</a>';
    }

}
