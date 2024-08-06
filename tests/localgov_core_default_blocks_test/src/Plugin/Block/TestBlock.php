<?php

namespace Drupal\localgov_core_default_blocks_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for testing default block placement.
 *
 * @Block(
 *   id = "localgov_core_test_block",
 *   admin_label = @Translation("Default block test block")
 * )
 */
class TestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return ['#markup' => 'Default block has been placed!'];
  }

}
