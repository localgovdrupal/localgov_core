<?php

namespace Drupal\localgov_core_page_header_event_test\EventSubscriber;

use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\localgov_core\Event\PageHeaderDisplayEvent;

/**
 * Test page header events.
 *
 * @package Drupal\localgov_core_page_header_event_test\EventSubscriber
 */
class PageHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PageHeaderDisplayEvent::EVENT_NAME => ['setPageHeader', 0],
    ];
  }

  /**
   * Set page title and lede.
   */
  public function setPageHeader(PageHeaderDisplayEvent $event) {

    // Override title and lede for page1 node content types.
    if ($event->getEntity() instanceof Node &&
      $event->getEntity()->bundle() == 'page1'
    ) {
      $event->setTitle('Overridden title');
      $event->setLede('Overridden lede');
    }

    // Hide page header block for page2 content types.
    if ($event->getEntity() instanceof Node &&
      $event->getEntity()->bundle() == 'page2'
    ) {
      $event->setVisibility(FALSE);
    }
  }

}
