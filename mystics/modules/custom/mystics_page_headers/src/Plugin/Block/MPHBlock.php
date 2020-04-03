<?php

namespace Drupal\mystics_page_headers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MPHBlock' block.
 *
 * @Block(
 *  id = "mphblock",
 *  admin_label = @Translation("MPHBlock"),
 * )
 */
class MPHBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\mystics_page_headers\Services\MPHManager definition.
   *
   * @var \Drupal\mystics_page_headers\Services\MPHManager
   */
  protected $mysticsPageHeadersManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->mysticsPageHeadersManager = $container->get('mystics_page_headers.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $mphManager = $this->mysticsPageHeadersManager;
    $pageHeader = $mphManager->getPageHeader();
    $build = [];
    $build['#theme'] = 'mphblock';
    $build['#content'] = $pageHeader;

    return $build;
  }

}
