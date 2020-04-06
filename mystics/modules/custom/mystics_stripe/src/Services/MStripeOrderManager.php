<?php

namespace Drupal\mystics_stripe\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class MStripeOrderManager.
 */
class MStripeOrderManager {

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
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructs a new MStripeOrderManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, DateFormatter $date_formatter, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactory $logger_factory) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->DateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory->get('mystics_stripe');
  }

  /**
   * Get orders list.
   */
  public function getOrders(string $customerName = null, string $orderStatus = null, array $header = null) {
    $query = $this->database->select('mystics_orders', 'mo')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->leftjoin('user__field_full_name', 'fn', 'fn.entity_id = mo.mystics_order_uid');
    $query->fields('mo');
    $query->fields('fn', array('field_full_name_value'));
    if(!empty($customerName)) {
      $query->condition('field_full_name_value', '%' . $this->database->escapeLike($customerName) . '%', 'LIKE');
    }
    if(!empty($orderStatus)) {
      $query->condition('mystics_order_status', $orderStatus, '=');
    }
    $query->limit(20)
      ->orderByHeader($header); 
    $results = $query->execute();

    return $results;
  }

  /**
   * Get order in progress data for the user.
   */
  public function getOrderInProgressByUser($uid) {
    $query = $this->database->select('mystics_orders', 'mo');
    $query->fields('mo');
    $query->condition('mystics_order_status', array('requires_capture', 'succeeded'), 'NOT IN');
    $query->condition('mystics_order_uid', $uid, '=');
    $query->range(0, 1);
    $results = $query->execute();

    return $results;
  }

  /**
   * Database order.
   */
  public function dbOrder($orderData) {
    $orderId = isset($orderData['orderId']) ? $orderData['orderId'] : null;
    $orderUid = isset($orderData['uid']) ? $orderData['uid'] : null;
    $orderDateTime = $this->DateFormatter->format(time(), 'custom', \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $clientSecret = isset($orderData['clientSecret']) ? $orderData['clientSecret'] : null;
    $paymentIntentId = isset($orderData['paymentIntentId']) ? $orderData['paymentIntentId'] : null;
    $orderAmount = isset($orderData['orderAmount']) ? $orderData['orderAmount'] : null;
    $orderStatus = isset($orderData['orderStatus']) ? $orderData['orderStatus'] : null;
    try {
      $db = $this->database->merge('mystics_orders')
        ->key(array('mystics_order_id' => $orderId))
        ->insertFields([
          'mystics_order_id' => $orderId,
          'mystics_order_uid' => $orderUid,
          'mystics_client_secret' => $clientSecret,
          'mystics_payment_intent_id' => $paymentIntentId,
          'mystics_order_amount' => $orderAmount,
          'mystics_order_date' => $orderDateTime,
          'mystics_order_status' => $orderStatus
        ])
        ->updateFields([
          'mystics_order_amount' => $orderAmount,
          'mystics_order_date' => $orderDateTime,
          'mystics_order_status' => $orderStatus
        ])
        ->execute();
    } catch(Exception $e) {
      $this->loggerFactory->error($e);
    }
  }

  /**
   * Update order status.
   */
  public function updateOrderStatus($key, $value, $orderStatus) {
    try {
      $query = $this->database->update('mystics_orders')
        ->fields([
          'mystics_order_status' => $orderStatus
        ])
        ->condition('mystics_'.$key, $value, '=')
        ->execute();
    } catch(Exception $e) {
      $this->loggerFactory->error($e);
    }
  }

}
