<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Functional tests for LocalGovDrupal install profile.
 */
class PageHeaderBlockTest extends BrowserTestBase {

  use NodeCreationTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'localgov_core',
    'localgov_core_page_header_event_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('localgov_page_header_block');
  }

  /**
   * Test block display.
   */
  public function testPageHeaderBlockDisplay() {

    // Check node title and summary display on a page.
    $this->createContentType(['type' => 'page']);
    $node_title = $this->randomMachineName(8);
    $node_summary = $this->randomMachineName(16);
    $page = $this->createNode([
      'title' => $node_title,
      'type' => 'page',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet($page->toUrl()->toString());
    $this->assertSession()->responseContains('<h1 class="header">' . $node_title . '</h1>');
    $this->assertSession()->pageTextNotContains($node_summary);
    $page->set('body', [
      'summary' => $node_summary,
      'value' => '',
    ]);
    $page->save();
    $this->drupalGet($page->toUrl()->toString());
    $this->assertSession()->responseContains('<h1 class="header">' . $node_title . '</h1>');
    $this->assertSession()->pageTextContains($node_summary);

    // Check title and lede display on a taxonomy term page.
    $vocabulary = $this->createVocabulary();
    $term_name = $this->randomMachineName(8);
    $term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => $term_name,
      'status' => 1,
    ]);
    $term->save();
    $this->drupalGet($term->toUrl()->toString());
    $this->assertSession()->responseContains('<h1 class="header">' . $term_name . '</h1>');
    $this->assertSession()->pageTextContains('All pages relating to ' . $term_name);
  }

  /**
   * Test block content override.
   */
  public function testPageHeaderDisplayEvent() {
    $title = $this->randomMachineName(8);
    $summary = $this->randomMachineName(16);

    // Check title and lede override.
    $this->createContentType(['type' => 'page1']);
    $page1 = $this->createNode([
      'type' => 'page1',
      'title' => $title,
      'body' => [
        'summary' => $summary,
        'value' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet($page1->toUrl()->toString());
    $this->assertSession()->responseNotContains('<h1 class="header">' . $title . '</h1>');
    $this->assertSession()->pageTextNotContains($summary);
    $this->assertSession()->responseContains('<h1 class="header">Overridden title</h1>');
    $this->assertSession()->pageTextContains('Overridden lede');

    // Check hidden page header block.
    $this->createContentType(['type' => 'page2']);
    $page2 = $this->createNode([
      'type' => 'page2',
      'title' => $title,
      'body' => [
        'summary' => $summary,
        'value' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet($page2->toUrl()->toString());
    $this->assertSession()->responseNotContains('<h1 class="header">' . $title . '</h1>');
    $this->assertSession()->pageTextNotContains($summary);
    $this->assertSession()->responseNotContains('<h1 class="header">Overridden title</h1>');
    $this->assertSession()->pageTextNotContains('Overridden lede');
  }

}
