<?php

namespace Drupal\localgov_core\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired when displaying the page header.
 */
class PageHeaderDisplayEvent extends Event {

  const EVENT_NAME = 'localgov_core.page_header_display';

  /**
   * Entity associated with the current route.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * The page lede override.
   *
   * @var array|string|null
   */
  protected $lede = NULL;

  /**
   * The page title override.
   *
   * @var array|string|null
   */
  protected $title = NULL;

  /**
   * Should the page header block be displayed?
   *
   * @var bool
   */
  protected $visibility = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity) {
    $this->entity = $entity;
  }

  /**
   * Entity getter.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Lede getter.
   *
   * @return array|string|null
   *   The lede.
   */
  public function getLede() {
    return $this->lede;
  }

  /**
   * Lede setter.
   *
   * @param array|string|null $lede
   *   The lede.
   */
  public function setLede($lede) {
    $this->lede = $lede;
  }

  /**
   * Title getter.
   *
   * @return array|string|null
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Title setter.
   *
   * @param array|string|null $title
   *   The title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Visibility getter.
   *
   * @return bool|null
   *   The title.
   */
  public function getVisibility() {
    return $this->visibility;
  }

  /**
   * Visibility setter.
   *
   * @param bool $visibility
   *   The visibility.
   */
  public function setVisibility($visibility) {
    $this->visibility = $visibility;
  }

}
