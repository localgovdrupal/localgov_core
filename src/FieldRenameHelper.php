<?php

namespace Drupal\localgov_core;

use Drupal\field\FieldStorageConfigInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Renames an existing entity field.
 *
 * - Creates Field storage for renamed field.
 * - Updates config dependencies with the new field name.
 * - Updates entity view display and form display with the new field name.
 *   Covers even field groups!
 * - Removes the old field storage.
 */
class FieldRenameHelper {

  /**
   * Rename field.
   *
   * @param string $old_field_name
   *   Old field machine name.
   * @param string $new_field_name
   *   New field machine name.
   * @param string $entity_type
   *   Entity type name (eg. node, paragraph etc...)
   *
   * @see https://www.drupal.org/docs/drupal-apis/update-api/updating-entities-and-fields-in-drupal-8
   */
  public static function renameField(string $old_field_name, string $new_field_name, string $entity_type) {

    // Make sure new field name is correctly formatted.
    $new_field_name = substr($new_field_name, 0, 32);

    // Get the old field config.
    $field_storage = FieldStorageConfig::loadByName($entity_type, $old_field_name);

    // If the field config does not exist, just return now.
    // (Assume it was deleted intentionally)
    if (!$field_storage instanceof FieldStorageConfigInterface) {
      return;
    }

    // Create new field storage.
    $new_field_storage = $field_storage->toArray();
    unset($new_field_storage['uuid']);
    unset($new_field_storage['_core']);
    $new_field_storage['field_name'] = $new_field_name;
    $new_field_storage['id'] = str_replace($old_field_name, $new_field_name, $new_field_storage['id']);
    $new_field_storage = FieldStorageConfig::create($new_field_storage);
    $new_field_storage->original = $new_field_storage;
    $new_field_storage->enforceIsNew(TRUE);
    $new_field_storage->save();

    // Copy table data.
    $copied_tables = self::copyFieldTables($entity_type, $old_field_name, $new_field_name);
    if (empty($copied_tables)) {
      \Drupal::service('logger.factory')->get('localgov_core')->warning('Could not copy field data from source %src-field to target %target-field.', [
        '%src-field' => $old_field_name,
        '%target-field' => $new_field_name,
      ]);
    }

    // Update the field config on each bundle.
    $config_manager = \Drupal::service('config.manager');
    $field_storage_name = $field_storage->getConfigDependencyName();
    $dependents = $config_manager->findConfigEntityDependentsAsEntities('config', [$field_storage_name]);
    foreach ($dependents as $dependent) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $dependent */
      if ($dependent instanceof FieldConfig) {
        $new_field = $dependent->toArray();
        unset($new_field['uuid']);
        unset($new_field['_core']);
        $new_field['field_name'] = $new_field_name;
        $new_field['id'] = str_replace($old_field_name, $new_field_name, $new_field['id']);
        $new_field['dependencies']['config'][0] = str_replace($old_field_name, $new_field_name, $new_field['dependencies']['config'][0]);
        $new_field = FieldConfig::create($new_field);
        $new_field->original = $dependent;
        $new_field->enforceIsNew(TRUE);
        $new_field->save();
      }
    }

    // Update entity view display and entity form display.
    foreach ($dependents as $dependent) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $dependent */
      if ($dependent instanceof EntityViewDisplay || $dependent instanceof EntityFormDisplay) {
        if ($component = $dependent->getComponent($old_field_name)) {
          $dependent->setComponent($new_field_name, $component);
          $groups = $dependent->getThirdPartySettings('field_group');
          foreach ($groups as $group_key => $group) {
            foreach ($group['children'] as $child_key => $child) {
              if ($child == $old_field_name) {
                $group['children'][$child_key] = $new_field_name;
                $dependent->setThirdPartySetting('field_group', $group_key, $group);
              }
            }
          }
        }
      }
      $dependent->save();
    }

    // Deleting field storage which will also delete bundles(fields).
    // Get a fresh copy of field storage here,
    // otherwise dependant config gets deleted.
    unset($field_storage);
    $field_storage = FieldStorageConfig::loadByName($entity_type, $old_field_name);
    $field_storage->delete();

    return TRUE;
  }

  /**
   * Copy data from one field's tables to another.
   */
  public static function copyFieldTables(string $host_entity_type_id, string $src_field, string $cloned_field): array {

    $copied_tables = [];
    $db = \Drupal::database();
    $db_schema = $db->schema();
    $logger = \Drupal::service('logger.factory')->get('localgov_core');

    // Fetch table schemas for the source field.
    $key_value_storage = \Drupal::keyValue('entity.storage_schema.sql');
    $field_storage_key_name = $host_entity_type_id . '.field_schema_data.' . $src_field;
    $src_table_schema_list = $key_value_storage->get($field_storage_key_name);

    // Copy source field's table data into the cloned field's tables.
    foreach ($src_table_schema_list as $src_table_name => $src_table_schema) {
      $cloned_table_name = str_replace($src_field, $cloned_field, $src_table_name);
      if (!$db_schema->tableExists($cloned_table_name)) {
        $logger->warning('Cloned Field table does not exists: %table.  Skipping copy operation.', ['%table' => $cloned_table_name]);
        continue;
      }

      try {
        // @todo Can we do better here?
        $db->query('INSERT INTO {' . $cloned_table_name . '} SELECT * FROM {' . $src_table_name . '}');
      }
      catch (\Exception $e) {
        $logger->warning('Failed to copy table data into cloned field table: %table.  More: %msg', [
          '%table' => $cloned_table_name,
          '%msg' => $e->getMessage(),
        ]);
      }

      $copied_tables[] = $cloned_table_name;
    }

    return $copied_tables;
  }

}
