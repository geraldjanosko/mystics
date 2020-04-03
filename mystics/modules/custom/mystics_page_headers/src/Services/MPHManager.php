<?php

namespace Drupal\mystics_page_headers\Services;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\Statement;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Controller\TitleResolver;
use Drupal\mystics_page_headers\PageHeader;

/**
 * Class MPHManager.
 */
class MPHManager {

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
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var Drupal\Core\Path\CurrentPathStack;
   */
  protected $currentPath;

  /**
   * Drupal\Core\Path\AliasManager definition.
   *
   * @var Drupal\Core\Path\AliasManager;
   */
  protected $aliasManager;

  /**
   * Drupal\Core\Controller\TitleResolver definition.
   *
   * @var Drupal\Core\Controller\TitleResolver;
   */
  protected $titleResolver;

  /**
   * Constructs a new MPHManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, AliasManager $alias_manager, TitleResolver $title_resolver) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->aliasManager = $alias_manager;
    $this->titleResolver = $title_resolver;
  }

  /**
   * Queries for a media object of type page_header matching the current path alias.
   */
  private function queryPageHeaders(string $currentAlias) {
    $query = $this->database->select('media', 'm');
    $query->leftJoin('media__field_ph_path_alias', 'pa', 'pa.entity_id = m.mid');
    $query->leftJoin('media__field_media_image_1', 'mi', 'mi.entity_id = m.mid');
    $query->fields('m', ['mid']);
    $query->fields('mi', ['field_media_image_1_target_id']);
    $query->condition('field_ph_path_alias_value', $currentAlias, '=');
    $query->range(0, 1);
    $results = $query->execute();

    return $results;
  }

  /**
   * Sets the bg_image property of the PageHeader object.
   */
  public function setBgImages(PageHeader $pageHeader, Statement $results) {
    foreach($results as $result) {
      $entityId = $result->field_media_image_1_target_id;
      $file = $this->entityTypeManager->getStorage('file')->load($entityId);
      $fileUri = $file->getFileUri();
      $xsImageUrl = $this->entityTypeManager->getStorage('image_style')->load('page_header_xs')->buildUrl($fileUri);
      $smImageUrl = $this->entityTypeManager->getStorage('image_style')->load('page_header_sm')->buildUrl($fileUri);
      $mdImageUrl = $this->entityTypeManager->getStorage('image_style')->load('page_header_md')->buildUrl($fileUri);
      $xlImageUrl = $this->entityTypeManager->getStorage('image_style')->load('page_header_xl')->buildUrl($fileUri);
      $bgImages = [$xsImageUrl, $smImageUrl, $mdImageUrl, $xlImageUrl];
      $pageHeader->setBgImages($bgImages);
    }
  }

  /**
   * Constructs page header data to be returned to the block template.
   */
  public function getPageHeader() {
    $title = 'Default Title';
    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $title = $this->titleResolver->getTitle($request, $route);
      if(gettype($title) == 'array') {
        $title = $title['#markup'];
      }
    }
    $pageHeader = new PageHeader($title);

    $currentPath = $this->currentPath->getPath();
    $currentAlias = $this->aliasManager->getAliasByPath($currentPath);
    $results = $this->queryPageHeaders($currentAlias);
    $this->setBgImages($pageHeader, $results);

    if($pageHeader->getBgImages() == null) {
      $currentAlias = array_filter(explode('/', $currentAlias));
      $currentAlias = '/' . reset($currentAlias) . '/*';
      $results = $this->queryPageHeaders($currentAlias);
      $this->setBgImages($pageHeader, $results);
    }

    return $pageHeader;
  }

}
