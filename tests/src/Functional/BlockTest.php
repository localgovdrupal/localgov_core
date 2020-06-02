<?php

namespace Drupal\Tests\localgov\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for LocalGovDrupal install profile.
 */
class BlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_theme';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'localgov';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'localgov_core',
    'localgov_services_page',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create localgov_services_page content',
      'edit own localgov_services_page content',
    ]);
  }

  /**
   * Test blocks display.
   */
  public function testBlocksDisplay() {
    $this->drupalLogin($this->adminUser);

    // Check node title and summary display on a landing page.
    $title = $this->randomMachineName(8);
    $summary = $this->randomMachineName(16);
    $body = $this->randomMachineName(32);
    $edit = [
      'title[0][value]' => $title,
      'body[0][summary]' => $summary,
      'body[0][value]' => $body,
      'status[value]' => 1,
    ];
    $this->drupalPostForm('/node/add/localgov_services_page', $edit, 'Save');
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains($summary);
    $this->assertSession()->pageTextContains($body);
  }

}
