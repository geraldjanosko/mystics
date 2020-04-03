<?php

namespace Drupal\mystics_stripe;

/**
 * Class StripeAuthentication
 */
class StripeAuthentication {

  protected $apiKey; 

  /**
   * Constructs a new StripeAuthentication Object.
   */
  public function __construct() {
    $this->apiKey = \Stripe\Stripe::setApiKey('sk_test_ad0OpwpRUhWmIBihxTqLyViv00BGlPKJCc');
  }
}
