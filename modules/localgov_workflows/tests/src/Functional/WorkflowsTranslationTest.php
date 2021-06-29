<?php

namespace Drupal\Tests\localgov_workflows\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test workflow works with translated content.
 */
class WorkflowsTranslationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'locale',
    'localgov_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Create a content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'title' => 'Page',
    ]);

    // Enable workflow for content.
    $workflow = Workflow::load('localgov_editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Enable Welsh for page.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm([
      'predefined_langcode' => 'cy',
    ], 'Add language');
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm([
      'entity_types[node]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
      'settings[node][page][settings][language][language_alterable]' => TRUE,
    ], 'Save configuration');
    $this->rebuildContainer();
  }

  /**
   * Test translated content.
   */
  public function testTranslatedContent() {

    // Create a published page in English.
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Published English node',
      'langcode[0][value]' => 'en',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $en_node = $this->drupalGetNodeByTitle('Published English node');
    $this->assertEquals('published', $en_node->moderation_state->value);

    // Add a draft Welsh translation.
    $this->drupalGet('node/' . $en_node->id() . '/translations');
    $this->clickLink('Add');
    $this->submitForm([
      'title[0][value]' => 'Draft Welsh node',
      'moderation_state[0][state]' => 'draft',
    ], 'Save (this translation)');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node_storage->resetCache([$en_node->id()]);
    $en_node = $node_storage->loadRevision($node_storage->getLatestRevisionId($en_node->id()));
    $cy_translation = $en_node->getTranslation('cy');
    $this->assertEquals('published', $en_node->moderation_state->value);
    $this->assertEquals('draft', $cy_translation->moderation_state->value);

    // Publish Welsh translation.
    $this->drupalGet('cy/node/' . $en_node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'published',
    ], 'Save (this translation)');
    $node_storage->resetCache([$en_node->id()]);
    $en_node = $node_storage->loadRevision($node_storage->getLatestRevisionId($en_node->id()));
    $cy_translation = $en_node->getTranslation('cy');
    $this->assertEquals('published', $en_node->moderation_state->value);
    $this->assertEquals('published', $cy_translation->moderation_state->value);

    // Archive English page.
    $this->drupalGet('node/' . $en_node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'archived',
    ], 'Save (this translation)');
    $node_storage->resetCache([$en_node->id()]);
    $en_node = $node_storage->loadRevision($node_storage->getLatestRevisionId($en_node->id()));
    $cy_translation = $en_node->getTranslation('cy');
    $this->assertEquals('archived', $en_node->moderation_state->value);
    $this->assertEquals('published', $cy_translation->moderation_state->value);
  }

}
