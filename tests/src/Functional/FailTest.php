<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * A test that fails.
 */
class FailTest extends BrowserTestBase {

  /**
   * The test that fails.
   */
  public function testFail() {
    $this->assertEquals(TRUE, FALSE, 'None shall pass.');
  }

}
