<?php

namespace Drupal\Tests\localgov_workflows\Kernel;

use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Kernel test application default workflow.
 *
 * @group localgov_workflows
 */
class WorkflowsInstallTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'field',
    'user',
    'node',
    'text',
    'content_moderation',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('workflow');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'content_moderation',
      'node',
      'system',
    ]);

  }

  /**
   * Test enabling workflow.
   */
  public function testEnableModule() {
    // Create existing content types.
    $this->createContentType(['type' => 'localgov_test_1']);
    $this->createContentType(['type' => 'localgov_test_2']);
    $workflow = Workflow::create([
      'id' => 'other_workflow',
      'type' => 'content_moderation',
    ]);
    $workflow->save();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'localgov_test_2');
    $workflow->save();

    // Enable module.
    // Should add default to 1 as no workflow.
    // Should leave 2 as has workflow already.
    \Drupal::service('module_installer')->install(['localgov_workflows']);
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
    $node_types = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
    $this->assertEquals($node_types['localgov_test_1']['workflow'], 'localgov_editorial');
    $this->assertEquals($node_types['localgov_test_2']['workflow'], 'other_workflow');

    // Add new types.
    // Should add default as localgov_ type 3.
    // Should leave 4 as not localgov_ type.
    $this->createContentType(['type' => 'localgov_test_3']);
    $this->createContentType(['type' => 'test_4']);
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
    $node_types = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
    $this->assertEquals($node_types['localgov_test_1']['workflow'], 'localgov_editorial');
    $this->assertEquals($node_types['localgov_test_2']['workflow'], 'other_workflow');
    $this->assertEquals($node_types['localgov_test_3']['workflow'], 'localgov_editorial');
    $this->assertTrue(empty($node_types['test_4']['workflow']));
  }

}
