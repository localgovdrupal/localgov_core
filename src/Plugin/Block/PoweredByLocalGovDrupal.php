<?php

namespace Drupal\localgov_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Powered by LocalGovDrupal' block.
 *
 * @Block(
 *   id = "localgov_powered_by_block",
 *   admin_label = @Translation("Powered by LocalGovDrupal")
 * )
 */
class PoweredByLocalGovDrupal extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>' . $this->t('Powered by <a href=":poweredby">LocalGovDrupal</a>', [':poweredby' => 'https://github.com/localgovdrupal/localgov']) . '</span>'];
  }

}
