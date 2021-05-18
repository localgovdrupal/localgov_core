<?php

namespace Drupal\localgov_core\Plugin\Block;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\TitleResolver;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\localgov_core\Event\PageHeaderDisplayEvent;

/**
 * Provides a 'PageHeaderBlock' block.
 *
 * @Block(
 *  id = "localgov_page_header_block",
 *  admin_label = @Translation("Page header block"),
 * )
 */
class PageHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Core current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Core event_dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Core request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Core title_resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * Entity associated with the current route.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * The page title override.
   *
   * @var array|string|null
   */
  protected $title;

  /**
   * The page lede override.
   *
   * @var array|string|null
   */
  protected $lede;

  /**
   * Should the page header block be displayed?
   *
   * @var bool
   */
  protected $visible;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('event_dispatcher'),
      $container->get('request_stack'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match, ContainerAwareEventDispatcher $event_dispatcher, RequestStack $request_stack, TitleResolver $title_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentRouteMatch = $current_route_match;
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
    $this->titleResolver = $title_resolver;

    // Find the entity, if any, associated with the current route.
    if (($route = $this->currentRouteMatch->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $this->currentRouteMatch->getParameter($name);
          if ($entity instanceof EntityInterface) {
            $this->entity = $entity;
            break;
          }
        }
      }
    }

    // Dispatch event to allow modules to alter block content.
    $event = new PageHeaderDisplayEvent($this->entity);
    $this->eventDispatcher->dispatch($event, PageHeaderDisplayEvent::EVENT_NAME);

    // Set the title, lede and visibility.
    $this->title = is_null($event->getTitle()) ? $this->getTitle() : $event->getTitle();
    $this->lede = is_null($event->getLede()) ? $this->getLede() : $event->getLede();
    $this->visible = $event->getVisibility();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build[] = [
      '#theme' => 'localgov_page_header_block',
      '#title' => $this->title,
      '#lede' => $this->lede,
    ];

    return $build;
  }

  /**
   * Get the default title for the page.
   *
   * @return array|string|null
   *   Returns a title for the current page or NULL if it can't be determined.
   */
  protected function getTitle() {
    $request = $this->requestStack->getCurrentRequest();
    $route = $this->currentRouteMatch->getRouteObject();
    if ($route) {
      return $this->titleResolver->getTitle($request, $route);
    }
    return NULL;
  }

  /**
   * Get the default lede for page.
   *
   * @return array|string|null
   *   Returns a lede for the current page or NULL if it can't be determined.
   */
  protected function getLede() {

    // Return node summary if it exists.
    if ($this->entity instanceof Node && $this->entity->hasField('body')) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->entity->get('body')->summary,
      ];
    }

    // Return a term string.
    if ($this->entity instanceof Term) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('All pages relating to @label.', ['@label' => strtolower($this->entity->label())]),
      ];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->visible) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if (!is_null($this->entity)) {
      return Cache::mergeTags(parent::getCacheTags(), $this->entity->getCacheTags());
    }
    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
