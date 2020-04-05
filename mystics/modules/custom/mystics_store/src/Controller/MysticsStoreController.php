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
    $menu_tree_service = \Drupal::service('menu.link_tree');
    $menu_parameters = new \Drupal\Core\Menu\MenuTreeParameters();
    $tree = $menu_tree_service->load('mystics-store', $menu_parameters);
    $links = [];

    foreach($tree as $item) {
      $title = $item->link->getTitle();
      $url = $item->link->getUrlObject()->toString();
      $description = $item->link->getDescription();
      $links[] = ['title' => $title, 'url' => \Drupal\Core\Url::fromUri('internal:'.$url), 'localized_options' => '', 'description' => $description];
    }

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
