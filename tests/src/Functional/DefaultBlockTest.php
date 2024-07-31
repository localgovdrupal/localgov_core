<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the default blocks mechanism.
 */
class DefaultBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_core',
  ];

  /**
   * Test block display.
   */
  public function testBlockDisplay() {

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = $this->container->get('module_installer');
    $moduleInstaller->install(['localgov_core_default_blocks_test']);

    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Block in a good region.');
    $this->assertSession()->pageTextNotContains('Block in a bad region.');
  }

}
