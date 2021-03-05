<?php

namespace Drupal\Tests\localgov_core\Kernel;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
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
    'field_ui',
    'field_group',
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
      'field_ui',
      'field_group',
    ]);
  }

  /**
   * Tests for FieldRenameHelper::renameField().
   *
   * Adds two fields of different types and then renames them.
   */
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
    FieldStorageConfig::create([
      'id'          => 'node.field_another_test_field',
      'field_name'  => 'field_another_test_field',
      'type'        => 'integer',
      'entity_type' => 'node',
    ])->enforceIsNew(TRUE)
      ->save();
    FieldConfig::create([
      'field_name'    => 'field_another_test_field',
      'entity_type'   => 'node',
      'bundle'        => 'test_type',
      'label'         => 'Another test field',
    ])->enforceIsNew(TRUE)
      ->save();

    // Set up Entity form display with a field group.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'test_type',
      'mode' => 'default',
    ])->setComponent('field_test_field', [
      'weight' => -1,
    ])->setThirdPartySetting('field_group', 'test_group', [
      'children' => [
        'field_test_field',
      ],
    ])->setComponent('field_another_test_field')
      ->setThirdPartySetting('field_group', 'another_test_group', [
        'children' => [
          'field_another_test_field',
        ],
      ])
      ->save();

    // Set up Entity view display with a field group.
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'test_type',
      'mode' => 'default',
    ])->setComponent('field_test_field', [
      'weight' => -1,
      'label'  => 'hidden',
    ])->setThirdPartySetting('field_group', 'test_group', [
      'children' => [
        'field_test_field',
      ],
    ])->setComponent('field_another_test_field')
      ->setThirdPartySetting('field_group', 'another_test_group', [
        'children' => [
          'field_another_test_field',
        ],
      ])
      ->save();

    // Set up some nodes.
    $test_field_value = $this->randomMachineName(8);
    $another_test_field_value = 42;
    $test_node = $this->createNode([
      'type'             => 'test_type',
      'title'            => $this->randomMachineName(8),
      'field_test_field' => $test_field_value,
      'field_another_test_field' => $another_test_field_value,
    ]);
    $test_node_id = $test_node->id();

    // Rename the node type fields.
    FieldRenameHelper::renameField('field_test_field', 'renamed_test_field', 'node');
    FieldRenameHelper::renameField('field_another_test_field', 'another_renamed_test_field', 'node');

    // Reload the node for the tests.
    $result_node = Node::load($test_node_id);

    // Asset that the old field names do not exist on the node type.
    $this->assertEmpty($result_node->hasField('field_test_field'));
    $this->assertEmpty($result_node->hasField('field_another_test_field'));

    // Assert that the new field names do exist on the node type.
    $this->assertEquals(TRUE, $result_node->hasField('renamed_test_field'));
    $this->assertEquals(TRUE, $result_node->hasField('another_renamed_test_field'));

    // Assert the field rename is the new name and the data is preserved.
    $this->assertEquals($test_field_value, $result_node->get('renamed_test_field')->value);
    $this->assertEquals($another_test_field_value, $result_node->get('another_renamed_test_field')->value);

    // Assert the entity form displays preserve the field groups.
    $form_display = EntityFormDisplay::load('node.test_type.default');
    $form_groups = $form_display->getThirdPartySettings('field_group');
    $this->assertEquals(TRUE, in_array('renamed_test_field', $form_groups['test_group']['children']));
    $this->assertEquals(TRUE, in_array('another_renamed_test_field', $form_groups['another_test_group']['children']));

    // Assert the entity view displays preserve the field groups.
    $view_display = EntityViewDisplay::load('node.test_type.default');
    $view_groups = $view_display->getThirdPartySettings('field_group');
    $this->assertEquals(TRUE, in_array('renamed_test_field', $view_groups['test_group']['children']));
    $this->assertEquals(TRUE, in_array('another_renamed_test_field', $view_groups['another_test_group']['children']));

    // Assert the field config is preserved.
    $form_component = $form_display->getComponent('renamed_test_field');
    $this->assertEquals(-1, $form_component['weight']);
    $view_component = $view_display->getComponent('renamed_test_field');
    $this->assertEquals(-1, $view_component['weight']);
    $this->assertEquals('hidden', $view_component['label']);
  }

}
