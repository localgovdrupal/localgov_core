<?php

namespace Drupal\localgov_core\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'NodeHeaderBlock' block.
 *
 * @Block(
 *  id = "localgov_node_header_block",
 *  admin_label = @Translation("Node header block"),
 * )
 */
class NodeHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Node to display header for.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node = FALSE;

  /**
   * Initialise new NodeHeaderBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if ($route_match->getParameter('node')) {
      $this->node = $route_match->getParameter('node');
      if (!$this->node instanceof NodeInterface) {
        $node_storage = $entity_type_manager->getStorage('node');
        $this->node = $node_storage->load($this->node);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($this->node);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build[] = [
      '#theme' => 'page_header',
      '#title' => $this->node->getTitle(),
    ];

    if (!$this->node->get('body')->isEmpty()) {
      $body = $this->node->get('body')->first()->getValue();
      if ($body and array_key_exists('summary', $body)) {
        $build[0]['#lede'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $body['summary'],
        ];
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node:' . $this->node->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

}
