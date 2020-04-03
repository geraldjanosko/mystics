<?php

namespace Drupal\mystics_products\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MPAdvertisementBlock' block.
 *
 * @Block(
 *  id = "mpadvertisement_block",
 *  admin_label = @Translation("MPAdvertisement Block"),
 * )
 */
class MPAdvertisementBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\mystics_products\Services\MProductsManager definition.
   *
   * @var \Drupal\mystics_products\Services\MProductsManager
   */
  protected $mysticsProductsManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->mysticsProductsManager = $container->get('mystics_products.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $manager = $this->mysticsProductsManager;
    $products = $manager->getProducts();
    $build = [];
    $build['#theme'] = 'mpadvertisement_block';
     $build['#content']['products'] = $products;

    return $build;
  }

}
