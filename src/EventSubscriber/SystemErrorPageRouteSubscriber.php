<?php

namespace Drupal\localgov_core\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
class SystemErrorPageRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.403')) {
      $route->setDefault('_controller', '\Drupal\localgov_core\Controller\Http4xxController::on403');
    }

    if ($route = $collection->get('system.404')) {
      $route->setDefault('_controller', '\Drupal\localgov_core\Controller\Http4xxController::on404');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
