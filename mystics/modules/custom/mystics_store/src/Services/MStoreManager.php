<?php

namespace Drupal\mystics_store\Services;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * Class MStoreManager.
 */
class MStoreManager {

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
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;  

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
   * Constructs a new MStoreManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, DateFormatter $date_formatter, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, AliasManagerInterface $path_alias_manager) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->DateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->pathAliasManager = $path_alias_manager;
  }

  /**
   * Get orders list.
   */
  public function getOrders() {
    $query = $this->database->select('mystics_orders', 'mo');
    $query->fields('mo');
    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20);
    $results = $query->execute();

    return $results;
  }
}
