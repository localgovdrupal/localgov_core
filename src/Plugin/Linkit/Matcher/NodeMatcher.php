<?php

namespace Drupal\localgov_core\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\NodeMatcher as LinkitNodeMatcher;

/**
 * Provides specific linkit matchers for the node entity type.
 *
 * @Matcher(
 *   id = "entity:node",
 *   label = @Translation("Node"),
 *   target_entity = "node",
 *   provider = "node"
 * )
 */
class NodeMatcher extends LinkitNodeMatcher {

  /**
   * {@inheritdoc}
   */
  protected function buildLabel(EntityInterface $entity) {
    $label = Html::escape($entity->label());

    // Prepend "Unpublished: " for unpublished nodes.
    if (method_exists($entity, 'isPublished') && !$entity->isPublished()) {
      $label = 'Unpublished: ' . $label;
    }

    return $label;
  }

}
