<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * permission_helper.php
 *
 * Provides a single function to check the current user's CRUD permissions
 * for a given controller / sub-menu entry.
 *
 * Usage (in any controller or view):
 *   $this->load->helper('permission');
 *   $perm = getUserPermission('customers');
 *   // $perm->CanView, $perm->CanCreate, $perm->CanEdit, $perm->CanDelete (all int 0|1)
 */

if (!function_exists('getUserPermission')) {

    /**
     * Return the permission object for a given ControllerName.
     *
     * @param  string $controllerName  Matches SubMenusTbl.ControllerName (case-insensitive)
     * @return object  {CanView, CanCreate, CanEdit, CanDelete}
     */
    function getUserPermission($controllerName) {

        $CI =& get_instance();

        $default = (object)[
            'CanView'   => 0,
            'CanCreate' => 0,
            'CanEdit'   => 0,
            'CanDelete' => 0,
        ];

        try {
            $permCache = $CI->redis_cache->get('Redis_UserPermissions');
            if ($permCache->Error !== false || empty($permCache->Value)) {
                return $default;
            }

            $permissions = (array)$permCache->Value;
            $key = strtolower($controllerName);

            foreach ($permissions as $ctrl => $perm) {
                if (strtolower($ctrl) === $key) {
                    $p = (array)$perm;
                    return (object)[
                        'CanView'   => (int)($p['CanView']   ?? 0),
                        'CanCreate' => (int)($p['CanCreate'] ?? 0),
                        'CanEdit'   => (int)($p['CanEdit']   ?? 0),
                        'CanDelete' => (int)($p['CanDelete'] ?? 0),
                    ];
                }
            }
        } catch (Exception $e) {
            // Silently return default — permission errors should not crash pages
        }

        return $default;

    }

}
