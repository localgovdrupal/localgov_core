<?php

namespace Drupal\Tests\localgov_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

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
    $query = $this->xpath('.//h1[contains(concat(" ",normalize-space(@class)," ")," header ")]');
    $page_title = $query[0]->getText();
    $this->assertEquals($page_title, $node_title);
    $this->assertSession()->pageTextNotContains($node_summary);
    $page->set('body', [
      'summary' => $node_summary,
      'value' => '',
    ]);
    $page->save();
    $this->drupalGet($page->toUrl()->toString());
    $query = $this->xpath('.//h1[contains(concat(" ",normalize-space(@class)," ")," header ")]');
    $page_title = $query[0]->getText();
    $this->assertEquals($page_title, $node_title);
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
    $query = $this->xpath('.//h1[contains(concat(" ",normalize-space(@class)," ")," header ")]');
    $page_title = $query[0]->getText();
    $this->assertEquals($page_title, $term_name);
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
    $query = $this->xpath('.//h1[contains(concat(" ",normalize-space(@class)," ")," header ")]');
    $page_title = $query[0]->getText();
    $this->assertNotEquals($page_title, $title);
    $this->assertSession()->pageTextNotContains($summary);
    $this->assertEquals($page_title, 'Overridden title');
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

    // There should be no h1 visible.
    $query = $this->xpath('.//h1[contains(concat(" ",normalize-space(@class)," ")," header ")]');
    $this->assertEmpty($query);

    // Using pageTextNotContains also fetches the title tag, so do an xpath on
    // the body tag to check the title text is not present.
    $body_query = $this->xpath('.//body');
    $body_text = $body_query[0]->getText();
    $this->assertStringNotContainsString($title, $body_text);

    // Check summary and overridden title and summary not present in page.
    $this->assertSession()->pageTextNotContains($summary);
    $this->assertSession()->pageTextNotContains('Overridden title');
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

    // Set up a page4 that can reference other page4 nodes with
    // subtitle in header.
    $this->createContentType(['type' => 'page4']);

    FieldConfig::create([
      'field_name' => 'parent',
      'entity_type' => 'node',
      'bundle' => 'page4',
      'label' => 'Parent',
      'cardinality' => -1,
    ])->save();

    $page4parent = $this->createNode([
      'type' => 'page4',
      'title' => 'page 4 parent title',
      'body' => [
        'summary' => 'page 4 parent summary',
        'value' => '',
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $page4child = $this->createNode([
      'type' => 'page4',
      'title' => 'page 4 child title',
      'body' => [
        'summary' => 'page 4 child summary',
        'value' => '',
      ],
      'parent' => [
        'target_id' => $page4parent->id(),
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Load the child page.
    $this->drupalGet($page4child->toUrl()->toString());
    $query = $this->xpath('.//h1[contains(concat(" ",normalize-space(@class)," ")," header ")]//div');
    $page_subtitle = $query[0]->getText();
    $this->assertEquals($page_subtitle, 'page 4 parent title');

  }

}
