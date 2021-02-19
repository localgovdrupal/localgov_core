<?php

namespace Drupal\Tests\localgov_core\Kernel;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Core\DateTime\DrupalDateTime;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Config\FileStorage;
use Drupal\localgov_core\FieldRenameHelper;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test for field rename helper.
 *
 * @group localgov_core
 */
class FieldRenameHelperTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'text',
    'options',
    'user',
    'node',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig([
      'system',
      'field',
      'text',
      'filter',
    ]);
  }

  public function testRenameField() {

    // Set up node type with the old fields.
    NodeType::create(['type' => 'test_type'])->save();
    FieldStorageConfig::create([
      'id'          => 'node.field_test_field',
      'field_name'  => 'field_test_field',
      'type'        => 'string',
      'entity_type' => 'node',
    ])->enforceIsNew(TRUE)
      ->save();
    FieldConfig::create([
      'field_name'    => 'field_test_field',
      'entity_type'   => 'node',
      'bundle'        => 'test_type',
      'label'         => 'Test field',
    ])->enforceIsNew(TRUE)
      ->save();

    // Set up some nodes.
    $test_field_value = $this->randomMachineName(8);
    $test_node = $this->createNode([
      'type'             => 'test_type',
      'title'            => $this->randomMachineName(8),
      'field_test_field' => $test_field_value,
    ]);
    $test_node_id = $test_node->id();

    // Rename the node type fields.
    FieldRenameHelper::renameField('field_test_field', 'renamed_test_field', 'node');

    // Reload the node for the tests.
    $result_node = Node::load($test_node_id);

    // Asset that the old field name does not exist on the node type.
    $this->assertEmpty($result_node->hasField('field_test_field'));

    // Assert that the new field name does exist on the node type.
    $this->assertEquals(TRUE, $result_node->hasField('renamed_test_field'));

    // Assert the field rename is the new name and the data is preserved.
    $this->assertEquals($test_field_value, $result_node->get('renamed_test_field')->value);
  }

}
