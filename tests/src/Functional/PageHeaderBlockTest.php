<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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

    // Check cache tags override.
    // Set up a page3 that can reference other page3 nodes.
    $this->createContentType(['type' => 'page3']);

    FieldStorageConfig::create([
      'field_name' => 'parent',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'parent',
      'entity_type' => 'node',
      'bundle' => 'page3',
      'label' => 'Parent',
      'cardinality' => -1,
    ])->save();

    $page3parent = $this->createNode([
      'type' => 'page3',
      'title' => 'page 3 parent title',
      'body' => [
        'summary' => 'page 3 parent summary',
        'value' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $page3child = $this->createNode([
      'type' => 'page3',
      'title' => 'page 3 child title',
      'body' => [
        'summary' => 'page 3 child summary',
        'value' => '',
      ],
      'parent' => [
        'target_id' => $page3parent->id(),
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Load the child page.
    $this->drupalGet($page3child->toUrl()->toString());

    // Check the child page contains the parent summary.
    $this->assertSession()->pageTextContains('page 3 parent summary');

    // Update the parent summary.
    $page3parent->body->summary = 'page 3 parent updated summary';
    $page3parent->save();

    // Reload the child page.
    $this->drupalGet($page3child->toUrl()->toString());

    // Check the child page contains the updated parent summary.
    $this->assertSession()->pageTextContains('page 3 parent updated summary');
  }

}
