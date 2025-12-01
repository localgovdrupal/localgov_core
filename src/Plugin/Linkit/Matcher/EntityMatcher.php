<?php

namespace Drupal\localgov_core\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher as LinkitEntityMatcher;

/**
 * Provides default linkit matchers for all entity types.
 *
 * @Matcher(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   deriver = "\Drupal\linkit\Plugin\Derivative\EntityMatcherDeriver"
 * )
 */
class EntityMatcher extends LinkitEntityMatcher {

  /**
   * {@inheritdoc}
   */
  protected function buildLabel(EntityInterface $entity) {
    $label = Html::escape($entity->label());

    // Prepend "Unpublished: " for unpublished entities.
    if (method_exists($entity, 'isPublished') && !$entity->isPublished()) {
      $label = 'Unpublished: ' . $label;
    }

    return $label;
  }

}
