<?php

namespace Drupal\localgov_core\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task for files view under entity media collection.
 */
class FilesLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a \Drupal\localgov_core\Plugin\Derivative\FilesLocalTasks instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    try {
      if ($this->routeProvider->getRouteByName('view.files.page_1')) {
        $this->derivatives['localgov_core.files'] = $base_plugin_definition;
        $this->derivatives['localgov_core.files']['parent_id'] = 'entity.media.collection';
        $this->derivatives['localgov_core.files']['title'] = 'Files';
        $this->derivatives['localgov_core.files']['route_name'] = 'view.files.page_1';
      }
    }
    catch (\Exception $exception) {
      // Throw $th;.
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
