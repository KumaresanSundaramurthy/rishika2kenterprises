<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MY_Model — Base model for all application models.
 *
 * Provides shared database helper methods so controllers
 * never need to query the DB directly.
 * All models should extend MY_Model instead of CI_Model.
 */
class MY_Model extends CI_Model {

    // ── Transaction Product helpers ───────────────────────────────────────────

    /**
     * Returns an array of distinct ProductUIDs for all active line items
     * belonging to the given transaction. Used to sync product caches after
     * stock movements are applied or reversed.
     *
     * @param  int   $transUID
     * @return int[]           Empty array on failure.
     */
    public function getProductUIDsByTransUID(int $transUID): array {
        try {
            // Use get_instance() for a direct reference — avoids CI3's __get()
            // "Indirect modification" issue when setting db_debug on a magic property.
            $CI = &get_instance();
            $db = $CI->ReadDb ?? null;
            if (!$db) return [];

            $db->db_debug = FALSE;
            $db->select('DISTINCT ProductUID');
            $db->from('Transaction.TransProductsTbl');
            $db->where(['TransUID' => $transUID, 'IsDeleted' => 0]);
            $query = $db->get();
            if (!$query) return [];

            $uids = [];
            foreach ($query->result() as $row) {
                $uid = (int)$row->ProductUID;
                if ($uid > 0) $uids[] = $uid;
            }
            return $uids;
        } catch (Throwable $e) {
            log_message('error', 'MY_Model::getProductUIDsByTransUID failed for TransUID=' . $transUID . ': ' . $e->getMessage());
            return [];
        }
    }

}
