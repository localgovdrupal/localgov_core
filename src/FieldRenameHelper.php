<?php

namespace Drupal\localgov_core;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

class FieldRenameHelper {

  /**
   * Rename field
   * @param  string $old_field_name
   *   Old field machine name.
   * @param  string $new_field_name
   *   New field machine name.
   * @param  string $entity_type
   *   Entity type name (eg. node, paragraph etc...)
   */
  public static function renameField(string $old_field_name, string $new_field_name, string $entity_type) {

    // Rename field.
    // https://www.drupal.org/docs/drupal-apis/update-api/updating-entities-and-fields-in-drupal-8

    // Make sure new field name is correctly formatted.
    $new_field_name = substr($new_field_name, 0, 32);

    // Field variables.
    // @TODO find out how to get the correct table names.
    $old_table = $entity_type . '__' . $old_field_name;
    $new_table = $entity_type . '__' . $new_field_name;
    $revision_old_table = $entity_type . '_revision__' . $old_field_name;
    $revision_new_table = $entity_type . '_revision__' . $new_field_name;

    // Get config storage.
    $config_storage = \Drupal::service('config.storage');
    $config_factory = \Drupal::configFactory();

    // Get the old field config.
    $field_storage = FieldStorageConfig::loadByName($entity_type, $old_field_name);

    // If the field config does not exist, just return now.
    // (Assume it was deleted intentionally)
    if (!$field_storage instanceof FieldStorageConfig) {
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

    // Duplicate the field data.
    $database = \Drupal::database();
    $prefix = $database->tablePrefix();
    // $prefix .= ($prefix ? '_' : '');
    $sql[] = "DROP TABLE IF EXISTS $prefix$new_table";
    $sql[] = "DROP TABLE IF EXISTS $prefix$revision_new_table";
    $sql[] = "CREATE TABLE $prefix$new_table LIKE $prefix$old_table";
    $sql[] = "INSERT INTO $prefix$new_table SELECT * FROM $prefix$old_table";
    $sql[] = "CREATE TABLE $prefix$revision_new_table LIKE $prefix$revision_old_table";
    $sql[] = "INSERT INTO $prefix$revision_new_table SELECT * FROM $prefix$revision_old_table";

    // SQL for update field column names.
    $field_columns = array_keys($field_storage->getColumns());
    foreach($field_columns as $column) {
      $old_table_value_column = $old_field_name . '_' . $column;
      $new_table_value_column = $new_field_name . '_' . $column;
      $sql[] = "ALTER TABLE $prefix$new_table CHANGE $old_table_value_column $new_table_value_column varchar(255)";
      $sql[] = "ALTER TABLE $prefix$revision_new_table CHANGE $old_table_value_column $new_table_value_column varchar(255)";
    }

    foreach ($sql as $indv_sql) {
      $database->query($indv_sql);
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
            foreach($group['children'] as $child_key => $child) {
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
}
