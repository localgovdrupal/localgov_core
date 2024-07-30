<?php

namespace Drupal\localgov_roles;

/**
 * Helper class to for Roles.
 */
class RolesHelper {

  /**
   * Admin role machine name.
   */
  const ADMIN_ROLE = 'localgov_admin';

  /**
   * Editor role machine name.
   */
  const EDITOR_ROLE = 'localgov_editor';

  /**
   * Author role machine name.
   */
  const AUTHOR_ROLE = 'localgov_author';

  /**
   * Contributor role machine name.
   */
  const CONTRIBUTOR_ROLE = 'localgov_contributor';

  /**
   * User manager role machine name.
   */
  const USER_MANAGER_ROLE = 'localgov_user_manager';

  /**
   * Assign permissions to roles if module has defaults.
   */
  public static function assignModuleRoles($module) {
    if ($roles = self::getModuleRoles($module)) {
      foreach ($roles as $role => $permissions) {
        \user_role_grant_permissions($role, $permissions);
      }
    }
  }

  /**
   * Retrieve default role permissions from module if implemented.
   *
   * A module can implement the HOOK_localgov_roles_default which returns an
   * array [ RolesHelper::ROLE => [ 'permissions' ] ].
   *
   * @param string $module
   *   Module name.
   *
   * @return array|void
   *   Array if implemented.
   */
  public static function getModuleRoles($module) {
    if (function_exists($module . '_localgov_roles_default')) {
      return \call_user_func($module . '_localgov_roles_default');
    }
  }

}
