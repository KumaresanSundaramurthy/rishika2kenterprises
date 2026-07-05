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
            $CI = &get_instance();
            // ReadDb may not be loaded in all controller contexts (e.g. delete flow).
            // Fall back to WriteDB so cache sync always runs regardless of which db is available.
            $db = ($CI->ReadDb ?? null) ?: ($CI->WriteDB ?? null);
            if (!$db) return [];

            // Raw SQL avoids CI3 query-builder quoting DISTINCT as a column name.
            // No IsDeleted filter — must find products even after soft-delete (delete flow).
            $query = $db->query(
                'SELECT DISTINCT ProductUID FROM `Transaction`.`TransProductsTbl` WHERE TransUID = ?',
                [(int)$transUID]
            );
            if (!$query) return [];

            return array_map('intval', array_column($query->result_array(), 'ProductUID'));
        } catch (Throwable $e) {
            log_message('error', 'MY_Model::getProductUIDsByTransUID failed for TransUID=' . $transUID . ': ' . $e->getMessage());
            return [];
        }
    }

}
