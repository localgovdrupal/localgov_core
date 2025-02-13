<?php

namespace Drupal\Tests\localgov_media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test locagov_media installs with the Standard profile.
 *
 * @group localgov_media
 */
class StandardProfileTest extends BrowserTestBase {

  /**
   * Test media in the Standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Test localgov_media installs with the Standard profile.
   */
  public function testEnablingLocalGovMedia() {
    \Drupal::service('module_installer')->install(['localgov_media']);
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('localgov_media'));
  }

}
