<?php

namespace Drupal\mystics_stripe\Services;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mystics_stripe\Services\MStripeOrderManager;
use Drupal\mystics_stripe\StripeAuthentication;
use Drupal\user\Entity\User;

/**
 * Class MStripeManager.
 */
class MStripeManager {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Drupal\mystics_stripe\Services\MStripeOrderManager definition.
   *
   * @var \Drupal\mystics_stripe\Services\MStripeOrderManager
   */
  protected $mStripeOrderManager;

  /**
   * Constructs a new MStripeManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, Connection $database, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, LoggerChannelFactory $logger_factory, MStripeOrderManager $m_stripe_order_manager) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->loggerFactory = $logger_factory->get('mystics_stripe');
    $this->mStripeOrderManager = $m_stripe_order_manager;
  }

  /**
   * Get customer_id from Stripe and sync it with Drupal $user.
   */
  private function getCustomerId($user) {
    $userName = $user->get('field_full_name')->getValue();
    $userName = reset($userName)['value'];
    $userMail = $user->get('mail')->getValue();
    $userMail = reset($userMail)['value'];
    $userPhone = $user->get('field_phone')->getValue();
    $userPhone = reset($userPhone)['value'];
    $customerId = $user->get('field_stripe_customer_id')->getvalue();
    $customerId = reset($customerId)['value'];
    new StripeAuthentication;
    if(empty($customerId)) {
      try {
        $customer = \Stripe\Customer::create(
          [
            'name' => $userName,
            'email' => $userMail,
            'phone' => $userPhone
          ]
        );
        $customerId = $customer->id;
        $user->set('field_stripe_customer_id', $customerId);
        $user->save();
      } catch(Exception $e) {
        $this->logger->error($e);
      }
    } else {
      try {
        $customer = \Stripe\Customer::retrieve($customerId);
      } catch(\Stripe\Exception\InvalidRequestException $e) {
        $errorCode = $e->getError()->code;
        if($errorCode == 'resource_missing') {
          $customer = \Stripe\Customer::create(
            [
              'name' => $userName,
              'email' => $userMail,
              'phone' => $userPhone
            ]
          );
          $customerId = $customer->id;
          $user->set('field_stripe_customer_id', $customerId);
          $user->save();
        }
      }
    }

    return $customerId;
  }

  /**
   * Generate PaymentIntent and sync it with Drupal $user.
   */
  private function generatePaymentIntent($uid, $orderAmount, $customerId) {
    $manager = $this->mStripeOrderManager;
    $orderData = $manager->getOrderInProgressByUser($uid);
    $clientSecret = '';
    $paymentIntentId = '';
    $orderId = '';
    foreach($orderData as $data) {
      if(!empty($data)) {
        $clientSecret = $data->mystics_client_secret;
        $paymentIntentId = $data->mystics_payment_intent_id;
        $orderId = $data->mystics_order_id;
      }
    }
    if(empty($clientSecret) || empty($paymentIntentId)) {
      $orderId = $uid . time() . bin2hex(random_bytes(4));
      new StripeAuthentication;
      try {
        $intent = \Stripe\PaymentIntent::create([
          'amount' => $orderAmount * 100,
          'currency' => 'usd',
          'customer' => $customerId,
          'metadata' => ['order_id' => $orderId],
          'capture_method' => 'manual'
        ]);
        $paymentIntentId = $intent->id;
        $clientSecret = $intent->client_secret;
        $orderStatus = $intent->status;
        $orderData = ['uid' => $uid, 'clientSecret' => $clientSecret, 'paymentIntentId' => $paymentIntentId, 'orderId' => $orderId, 'orderAmount' => $orderAmount, 'orderStatus' => $orderStatus];
        $manager->dbOrder($orderData);
      } catch(Exception $e) {
        $this->logger->error($e);
      }
    } else {
      $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
      $orderAmount = $intent->amount;
      if(($orderAmount * 100) != $orderAmount) {
        try {
          $intent = \Stripe\PaymentIntent::update(
            $paymentIntentId,
            ['amount' => $orderAmount * 100]
          );
          $orderStatus = $intent->status;
          $orderData = ['orderId' => $orderId, 'orderAmount' => $orderAmount, 'orderStatus' => $orderStatus];
          $manager->dbOrder($orderData);
        } catch(Exception $e) {
          $this->logger->error($e);
        }
      }
    }
  }

  /**
   * Perform pre checkout operations.
   */
  function preCheckout() {
    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $customerId = $this->getCustomerId($user);
    $orderSummary = null;
    $shoppingCart = isset($_SESSION['shoppingCart']) ? $_SESSION['shoppingCart'] : null;
    if(!empty($shoppingCart)) {
      $header = [
        'item' => $this->t('Item'),
        'price' => $this->t('Price'),
        'total' => $this->t('Total')
      ];
      $orderAmount = 0;
      $rows[] = [];
      foreach($shoppingCart as $key => $item) {
        $id = $key;
        $product = $this->entityTypeManager->getStorage('mystics_product')->load($id);
        $name = $product->getName();
        $quantity = $item['quantity'];
        $price = $product->get('field_product_price')->getValue();
        $price = $price[0]['value'];
        $rowTotal = $price * $quantity;
        $orderAmount += $rowTotal;
        $rowTotal = '$' . number_format($rowTotal, 2, '.', '');
        $price = '$' . number_format($price, 2, '.', '');
        $rows[] = [$name, $price, $rowTotal];
      }
      $this->generatePaymentIntent($uid, $orderAmount, $customerId);
      $orderAmount = '$' . number_format($orderAmount, 2, '.', '');
      $rows[] = ['', '', $orderAmount];
      $orderSummary = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      );
    }

    return $orderSummary;
  }

  /**
   * Perform post checkout operations.
   */
  function postCheckout() {
    unset($_SESSION['shoppingCart']);
    $clientSecret = $_POST['clientSecret'];
    $orderStatus = $_POST['orderStatus'];
    $manager = $this->mStripeOrderManager;
    $manager->updateOrderStatus($clientSecret, $orderStatus);
    $variables = [];
    $this->moduleHandler->invokeAll('mystics_stripe_post_checkout', [$variables]);
  }

}
