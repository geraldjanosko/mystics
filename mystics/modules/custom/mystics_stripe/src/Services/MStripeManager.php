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
   * Perform pre checkout operations.
   */
  function preCheckout() {
    // unset($_SESSION['shoppingCart'][1]);
    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $userName = $user->get('field_full_name')->getValue();
    $userName = $userName[0]['value'];
    $userMail = $user->get('mail')->getValue();
    $userMail = $userMail[0]['value'];
    $userPhone = $user->get('field_phone')->getValue();
    $userPhone = $userPhone[0]['value'];
    $customerIdArr = $user->get('field_stripe_customer_id')->getvalue();
    $customer_id = reset($customerIdArr)['value'];
    new StripeAuthentication;
    if(empty($customer_id)) {
      $customer = \Stripe\Customer::create(
        [
          'name' => $userName,
          'email' => $userMail,
          'phone' => $userPhone
        ]
      );
      $customer->description;
      $customer_id = $customer->id;
      $user->set('field_stripe_customer_id', $customer_id);
      $user->save();
    }
    $header = [
      'item' => $this->t('Item'),
      'price' => $this->t('Price'),
      'quantity' => $this->t('Quantity'),
      'total' => $this->t('Total')
    ];
    $shoppingCart = $_SESSION['shoppingCart'];
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
      $rows[] = [$name, $price, $quantity, $rowTotal];
    }
    if(!isset($_SESSION['clientSecret'])) {
      new StripeAuthentication;
      $intent = \Stripe\PaymentIntent::create([
        'amount' => $total * 100,
        'currency' => 'usd',
        'customer' => $customer_id,
      ]);
      $intentId = $intent->id;
      $clientSecret = $intent->client_secret;
      $_SESSION['intentId'] = $intentId;
      $_SESSION['clientSecret'] = $clientSecret;
    } else {
      $intentId = $_SESSION['intentId'];
      $clientSecret = $_SESSION['clientSecret'];
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
    $total = '$' . number_format($total, 2, '.', '');
    $rows[] = ['', '', '', $total];
    $orderSummary = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $orderSummary;
  }

  /**
   * Perform post checkout operations.
   */
  function postCheckout() {
    unset($_SESSION['shoppingCart']);
    unset($_SESSION['intentId']);
    unset($_SESSION['clientSecret']);
  }

}
