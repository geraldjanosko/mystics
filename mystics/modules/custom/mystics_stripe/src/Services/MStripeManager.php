<?php

namespace Drupal\mystics_stripe\Services;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\mystics_stripe\StripeAuthentication;
use Drupal\user\Entity\User;

/**
 * Class MStripeManager.
 */
class MStripeManager {

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
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new MStripeManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Perform pre checkout operations.
   */
  function preCheckout() {
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
  }

  /**
   * Create a paymentIntent object and return the client secret.
   */
  function getClientSecret($amount) {
    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $customerIdArr = $user->get('field_stripe_customer_id')->getvalue();
    $customer_id = reset($customerIdArr)['value'];
    if(!isset($_SESSION['clientSecret'])) {
      new StripeAuthentication;
      $intent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'customer' => $customer_id,
      ]);
      $clientSecret = $intent->client_secret;
      $_SESSION['clientSecret'] = $clientSecret;
    } else {
      $clientSecret = $_SESSION['clientSecret'];
    }

    return $clientSecret;
  }

  /**
   * Perform post checkout operations.
   */
  function postCheckout() {
    unset($_SESSION['clientSecret']);
  }

}
