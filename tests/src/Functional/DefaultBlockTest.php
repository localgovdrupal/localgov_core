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
    'localgov_core_default_blocks_test',
  ];

  /**
   * Test block display.
   */
  public function testBlockDisplay() {
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Block in a good region.');
    $this->assertSession()->pageTextNotContains('Block in a bad region.');
  }

}
