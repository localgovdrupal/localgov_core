<?php

namespace Drupal\localgov_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for LocalGov Core routes.
 */
class Http4xxController extends ControllerBase
{

  /**
   * The default 403 content.
   *
   * @return array
   *   A render array containing the message to display for 403 pages.
   */
  public function on403()
  {
    return [
        'message' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('You are not authorised to access this page.')
        ],
        'login_link' => [
            '#type' => 'html_tag',
            '#tag' => 'a',
            '#attributes'=> [
                'href'=> '/user/login'
            ],
            '#value' => $this->t('Login?')
        ]
    ];
  }

  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404()
  {
    return [
        'message' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('The page you are requesting cannot be found.')
        ],
    ];
  }

}
