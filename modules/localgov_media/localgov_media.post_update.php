<?php

/**
 * @file
 * LocalGov Drupal Media module post update file.
 */

use Drupal\Component\Utility\DiffArray;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Entity\View;
use Symfony\Component\Yaml\Yaml;

/**
 * Change media administration view to include usage.
 */
function localgov_media_post_update_media_admin_view_count(&$sandbox): TranslatableMarkup {
  if (!\Drupal::moduleHandler()->moduleExists('entity_usage')) {
    return new TranslatableMarkup('The Media administration view /admin/content/media has not been update as Media Usage is not enabled.');
  }

  $media_view = View::load('media');
  $original = Yaml::parseFile(\Drupal::moduleHandler()->getModule('media')->getPath() . '/config/optional/views.view.media.yml');
  // Diff this way round gets new keys.
  $diff = DiffArray::diffAssocRecursive($media_view->toArray(), $original);
  // Which should only include uuid and _core.
  if (count($diff) == 2
    && array_key_exists('uuid', $diff)
    && array_key_exists('_core', $diff)
  ) {
    $new = Yaml::parseFile(\Drupal::moduleHandler()->getModule('localgov_media')->getPath() . '/config/optional/views.view.media.yml');
    foreach ($new as $key => $value) {
      $media_view->set($key, $value);
    }
    $media_view->save();
    return new TranslatableMarkup('The Media administration view has been update to include a usage count column.');
  }
  else {
    return new TranslatableMarkup('The Media administration view /admin/content/media has not been update as it has been changed.');
  }
}
