<?php

namespace Drupal\mystics_products\Services;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolver;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\mystics_products\Product;

/**
 * Class MProductsManager.
 */
class MProductsManager {

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
   * Drupal\Core\Form\FormBuilderInterface definition.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Drupal\Core\Path\AliasManagerInterface definition.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * Drupal\Core\Controller\TitleResolver definition.
   *
   * @var Drupal\Core\Controller\TitleResolver;
   */
  protected $titleResolver;

  /**
   * Constructs a new MProductsManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, AliasManagerInterface $path_alias_manager, TitleResolver $title_resolver) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->pathAliasManager = $path_alias_manager;
    $this->titleResolver = $title_resolver;
  }

  /**
   * Queries for a product matching the current service.
   */
  private function queryProducts($title) {
    $query = $this->database->select('mystics_product', 'mp');
    $query->leftJoin('mystics_product__field_product_category', 'fpc', 'fpc.entity_id = mp.id');
    $query->leftJoin('taxonomy_term_field_data', 'tfd', 'tfd.tid = fpc.field_product_category_target_id');
    $query->fields('mp', ['id']);
    $query->condition('tfd.name', $title, '=');
    $results = $query->execute();

    return $results;
  }

  /**
   * Retrieves products that match the current pages service.
   */
  public function getProducts() {
    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $title = $this->titleResolver->getTitle($request, $route);
      if(gettype($title) == 'array') {
        $title = $title['#markup'];
      }
    }
    // $title = strtolower(str_replace(' ', '_', $title));
    $results = $this->queryProducts($title);
    $products = [];

    foreach($results as $result) {
      $product = $this->entityTypeManager->getStorage('mystics_product')->load($result->id);
      $id = $product->get('id')->getValue();
      $id = $id[0]['value'];
      $title = $product->label();
      $desc = $product->get('field_product_description')->getValue();
      $price = $product->get('field_product_price')->getValue();
      $addToCartForm = $this->formBuilder->getForm('Drupal\mystics_products\Form\AddToCartForm', $id);
      $products[] = new Product($id, $title, $desc, $price[0]['value'], $addToCartForm);
    }

    return $products;
  }

}
