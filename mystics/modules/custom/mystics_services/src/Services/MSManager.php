<?php

namespace Drupal\mystics_services\Services;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\mystics_services\Service;

/**
 * Class MSManager.
 */
class MSManager {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MSManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Queries for a node of type service.
   */
  private function queryServices() {
    $query = $this->database->select('node', 'n');
    $query->fields('n', ['nid']);
    $query->condition('type', 'service');
    $results = $query->execute();

    return $results;
  }

  /**
   * Retrieves nodes of type service and returns them to the controller.
   */
  public function getServices() {
    $results = $this->queryServices();
    $services = [];

    if(!empty($results)) {
      foreach($results as $result) {
        $node = $this->entityTypeManager->getStorage('node')->load($result->nid);
        $link = $node->toUrl()->toString();
        $title = SafeMarkup::checkPlain($node->getTitle());
        $desc = $node->body->view('teaser');
        $file = $node->get('field_s_image')->getValue();
        $image = $this->entityTypeManager->getStorage('file')->load(reset($file)['target_id']);
        $imageUri = $image->getFileUri();
        $imageUrl = $this->entityTypeManager->getStorage('image_style')->load('medium')->buildUrl($imageUri);;
        $services[] = new Service($link, $title, $desc, $imageUrl);
      }
    }

    return $services;
  }

}
