<?php

namespace Drupal\mystics_stripe\Services;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
   * Constructs a new MStripeManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
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

    return $customerId;
  }

  /**
   * Generate PaymentIntent and sync it with Drupal $user.
   */
  private function generatePaymentIntent($user, $total) {
    $clientSecret = $user->get('field_stripe_client_secret')->getvalue();
    $clientSecret = reset($clientSecret)['value'];
    $intentId = $user->get('field_stripe_intent_id')->getvalue();
    $intentId = reset($intentId)['value'];
    if(empty($clientSecret) || empty($intentId)) {
      new StripeAuthentication;
      $intent = \Stripe\PaymentIntent::create([
        'amount' => $total * 100,
        'currency' => 'usd',
        'customer' => $customerId
      ]);
      $intentId = $intent->id;
      $clientSecret = $intent->client_secret;
      $user->set('field_stripe_client_secret', $clientSecret);
      $user->set('field_stripe_intent_id', $intentId);
      $user->save();
    } else {
      $intent = \Stripe\PaymentIntent::retrieve($intentId);
      $amount = $intent->amount;
      if(($total * 100) != $amount) {
        \Stripe\PaymentIntent::update(
          $intentId,
          ['amount' => $total * 100]
        );
      } else {
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
      $rows[] = [];
      $total = 0;
      foreach($shoppingCart as $key => $item) {
        $id = $key;
        $product = $this->entityTypeManager->getStorage('mystics_product')->load($id);
        $name = $product->getName();
        $quantity = $item['quantity'];
        $price = $product->get('field_product_price')->getValue();
        $price = $price[0]['value'];
        $rowTotal = $price * $quantity;
        $total += $rowTotal;
        $rowTotal = '$' . number_format($rowTotal, 2, '.', '');
        $price = '$' . number_format($price, 2, '.', '');
        $rows[] = [$name, $price, $rowTotal];
      }
      $this->generatePaymentIntent($user, $total);
      $total = '$' . number_format($total, 2, '.', '');
      $rows[] = ['', '', $total];
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
    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $user->set('field_stripe_client_secret', '');
    $user->set('field_stripe_intent_id', '');
    $user->save();
    $orderId = bin2hex(random_bytes(16));
  }

}
