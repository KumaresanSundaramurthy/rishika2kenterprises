<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Managecache extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /** Clear all user-scoped cache entries for the currently logged-in user. */
    public function clear_all() {
        $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
        if ($userUID) {
            $this->redisservice->deleteAllUserCache($userUID);
        }
        // Also sweep any wildcard user-context patterns
        $this->redisservice->clearCacheByPattern('UserActiveSession_*');
        echo "Cache cleared.<br>";
        echo '<a href="/">Go Home</a>';
    }

}
