<?php

namespace Drupal\mystics_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MSController.
 */
class MSController extends ControllerBase {

  /**
   * Drupal\mystics_services\Services\MSManager definition.
   *
   * @var \Drupal\mystics_services\Services\MSManager
   */
  protected $mysticsServicesManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->mysticsServicesManager = $container->get('mystics_services.manager');
    return $instance;
  }

  /**
   * Services_list.
   *
   * @return string
   *   Return Hello string.
   */
  public function services_list() {
    $manager = $this->mysticsServicesManager;
    $services = $manager->getServices();

    return [
      '#theme' => 'services_list',
      '#content' => ['services' => $services]
    ];
  }

}
