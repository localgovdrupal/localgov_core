<?php

namespace Drupal\Tests\localgov_roles\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_roles\RolesHelper;
use Drupal\user\Entity\Role;

/**
 * Tests ModuleHandler functionality.
 *
 * @group localgov_roles
 */
class ModuleRolesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_revisions',
    'node',
    'path',
    'system',
    'toolbar',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'user',
    ]);
  }

  /**
   * Test enabling localgov_roles after other modules.
   */
  public function testEnablingRolesModule() {
    $this->moduleInstaller()->install([
      'localgov_roles_test_one',
      'localgov_roles_test_two',
    ]);
    $this->assertEmpty(Role::load(RolesHelper::EDITOR_ROLE));
    $this->assertEmpty(Role::load(RolesHelper::AUTHOR_ROLE));
    $this->moduleInstaller()->install(['localgov_roles']);
    $editor = Role::load(RolesHelper::EDITOR_ROLE);
    $author = Role::load(RolesHelper::AUTHOR_ROLE);

    $this->assertFalse($author->hasPermission('administer localgov roles test one'));
    $this->assertTrue($author->hasPermission('create localgov roles test one'));
    $this->assertTrue($author->hasPermission('view localgov roles test one'));
    $this->assertFalse($author->hasPermission('delete localgov roles test one'));
    $this->assertFalse($author->hasPermission('view localgov roles test two'));
    $this->assertFalse($author->hasPermission('administer localgov roles test two'));

    $this->assertFalse($editor->hasPermission('administer localgov roles test one'));
    $this->assertTrue($editor->hasPermission('create localgov roles test one'));
    $this->assertTrue($editor->hasPermission('view localgov roles test one'));
    $this->assertTrue($editor->hasPermission('delete localgov roles test one'));
    $this->assertTrue($editor->hasPermission('view localgov roles test two'));
    $this->assertTrue($editor->hasPermission('administer localgov roles test two'));
  }

  /**
   * Test enabling localgov_roles before other modules.
   */
  public function testEnablingModulesImplementing() {
    $this->moduleInstaller()->install(['localgov_roles']);
    $this->assertNotEmpty(Role::load(RolesHelper::EDITOR_ROLE));
    $this->assertNotEmpty(Role::load(RolesHelper::AUTHOR_ROLE));

    $this->moduleInstaller()->install([
      'localgov_roles_test_one',
      'localgov_roles_test_two',
    ]);
    $editor = Role::load(RolesHelper::EDITOR_ROLE);
    $author = Role::load(RolesHelper::AUTHOR_ROLE);

    $this->assertFalse($author->hasPermission('administer localgov roles test one'));
    $this->assertTrue($author->hasPermission('create localgov roles test one'));
    $this->assertTrue($author->hasPermission('view localgov roles test one'));
    $this->assertFalse($author->hasPermission('delete localgov roles test one'));
    $this->assertFalse($author->hasPermission('view localgov roles test two'));
    $this->assertFalse($author->hasPermission('administer localgov roles test two'));

    $this->assertFalse($editor->hasPermission('administer localgov roles test one'));
    $this->assertTrue($editor->hasPermission('create localgov roles test one'));
    $this->assertTrue($editor->hasPermission('view localgov roles test one'));
    $this->assertTrue($editor->hasPermission('delete localgov roles test one'));
    $this->assertTrue($editor->hasPermission('view localgov roles test two'));
    $this->assertTrue($editor->hasPermission('administer localgov roles test two'));
  }

  /**
   * Returns the ModuleInstaller.
   *
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   *   The module installer.
   */
  protected function moduleInstaller() {
    return $this->container->get('module_installer');
  }

}
