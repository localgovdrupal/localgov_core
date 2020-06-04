<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Functional tests for LocalGovDrupal install profile.
 */
class BlockTest extends BrowserTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

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
   * A user with mininum permissions for test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'dummy']);
    $this->user = $this->drupalCreateUser([
      'access content',
    ]);
  }

  /**
   * Test blocks display.
   */
  public function testBlocksDisplay() {
    // Check node title and summary display on a landing page.
    $title = $this->randomMachineName(8);
    $summary = $this->randomMachineName(16);
    $body = $this->randomMachineName(32);
    $this->createNode([
      'title' => $title,
      'type' => 'localgov_services_page',
      'body' => [
        'summary' => $summary,
        'value' => $body,
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
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
    // Check node title and summary display on a landing page.
    $title = $this->randomMachineName(8);
    $this->createNode([
      'title' => $title,
      'type' => 'dummy',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($title);
  }

}
