<?php

namespace Drupal\mystics_store\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MysticsStoreController.
 */
class MysticsStoreController extends ControllerBase {

  /**
   * Overview.
   *
   * @return string
   *   Return Hello string.
   */
  public function overview() {
    $links =[
      [
        'title' => 'Test',
        'url' => \Drupal\Core\Url::fromUri('internal:/node'),
        'localized_options' => '',
        'description' => 'This is a test description'
      ],
      [
        'title' => 'Test',
        'url' => \Drupal\Core\Url::fromUri('internal:/node'),
        'localized_options' => '',
        'description' => 'This is a test description'
      ]
    ];

    if ($links) {
      $output = [
        '#theme' => 'admin_block_content',
        '#content' => $links,
      ];
    }
    else {
      $output = [
        '#markup' => t('You do not have any administrative items.'),
      ];
    }

    return $output;
  }

}
