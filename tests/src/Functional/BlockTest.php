<?php

namespace Drupal\Tests\localgov_core\Functional;

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

    // Create a dummy content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'dummy',
        'name' => 'Dummy',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create localgov_services_page content',
      'edit own localgov_services_page content',
      'create dummy content',
      'edit own dummy content',
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

  /**
   * Test blocks display when content type has no body field.
   */
  public function testBlocksDisplayWhenNoSummary() {
    $this->drupalLogin($this->adminUser);

    // Check node title and summary display on a landing page.
    $title = $this->randomMachineName(8);
    $edit = [
      'title[0][value]' => $title,
      'status[value]' => 1,
    ];
    $this->drupalPostForm('/node/add/dummy', $edit, 'Save');
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($title);
  }

}
