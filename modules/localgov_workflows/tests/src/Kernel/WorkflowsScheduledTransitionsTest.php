<?php

namespace Drupal\Tests\localgov_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\workflows\Entity\Workflow;

/**
 * Kernel test for scheduling transitions.
 *
 * @group localgov_workflows
 */
class WorkflowsScheduledTransitionsTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_moderation',
    'dynamic_entity_reference',
    'field',
    'filter',
    'node',
    'scheduled_transitions',
    'system',
    'text',
    'user',
    'views',
    'workflows',
    'localgov_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('scheduled_transition');
    $this->installEntitySchema('workflow');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'content_moderation',
      'filter',
      'node',
      'scheduled_transitions',
      'system',
      'views',
      'localgov_workflows',
    ]);
  }

  /**
   * Test scheduled transition configuration on enabling workflow.
   */
  public function testWorkflowsScheduledTransitions() {

    // Create a content type and add to workflow.
    $this->createContentType([
      'type' => 'page',
      'title' => 'Page',
    ]);
    $workflow = Workflow::load('localgov_editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Create a draft node.
    $node = $this->createNode([
      'type' => 'page',
      'title' => $this->randomMachineName(12),
      'moderation_state' => 'draft',
    ]);
    $nid = $node->id();
    $this->assertEquals('draft', $node->moderation_state->value);
    $this->assertEquals(1, $node->getRevisionId());

    // Create a new revision for review.
    $node->setNewRevision();
    $node->moderation_state = 'review';
    $node->save();
    $this->assertEquals('review', $node->moderation_state->value);
    $this->assertEquals(2, $node->getRevisionId());

    // Publish revision on schedule.
    $scheduled_transition = ScheduledTransition::create([
      'entity' => $node,
      'entity_revision_id' => 2,
      'author' => 1,
      'workflow' => $workflow->id(),
      'moderation_state' => 'published',
      'transition_on' => (new \DateTime('1 Jan 2020 12am'))->getTimestamp(),
    ]);
    $scheduled_transition->save();
    $runner = $this->container->get('scheduled_transitions.runner');
    $runner->runTransition($scheduled_transition);
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node = $node_storage->load($nid);
    $this->assertEquals('published', $node->moderation_state->value);
    $this->assertEquals(3, $node->getRevisionId());

    // Archive published revision on schedule.
    $scheduled_transition = ScheduledTransition::create([
      'entity' => $node,
      'entity_revision_id' => 3,
      'author' => 1,
      'workflow' => $workflow->id(),
      'moderation_state' => 'archived',
      'transition_on' => (new \DateTime('1 Jan 2021 12am'))->getTimestamp(),
    ]);
    $scheduled_transition->save();
    $runner->runTransition($scheduled_transition);
    $node = $node_storage->load($nid);
    $this->assertEquals(4, $node->getRevisionId());
    $this->assertEquals('archived', $node->moderation_state->value);
  }

}
