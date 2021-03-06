<?php

namespace Drupal\mystics_stripe\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class MStripeController.
 */
class MStripeController extends ControllerBase {

  /**
   * Drupal\Core\Form\FormBuilderInterface definition.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Drupal\mystics_stripe\Services\MStripeManager definition.
   *
   * @var \Drupal\mystics_stripe\Services\MStripeManager
   */
  protected $mysticsStripeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->formBuilder = $container->get('form_builder');
    $instance->mysticsStripeManager = $container->get('mystics_stripe.manager');
    return $instance;
  }

  /**
   * Checkout.
   *
   * @return array
   *   Return checkout page.
   */
  public function stripeCheckout() {
    $page['#attached']['library'] = ['mystics_stripe/stripe', 'mystics_stripe/global-styling'];
    $page['#theme'] = 'stripe_checkout';
    $manager = $this->mysticsStripeManager;
    $orderSummary = $manager->preCheckout();
    $page['#content']['order_summary'] = $orderSummary;
    $stripeCheckoutForm = $this->formBuilder->getForm('Drupal\mystics_stripe\Form\StripeCheckoutForm');
    $page['#content']['stripe_checkout_form'] = $stripeCheckoutForm;

    return $page;
  }

  /**
   * Ajax callback for post checkout logic.
   */
  public function postCheckout() {
    $manager = $this->mysticsStripeManager;
    $manager->postCheckout();
    return new JsonResponse([
      'status' => 'success'
    ]);
  }

  /**
   * Checkout Success.
   *
   * @return array
   *   Return checkout page.
   */
  public function checkoutSuccess() {
    return [
      '#type' => 'markup',
      '#markup' => '<p>Your payments was completed successfully.</p>',
      '#prefix' => '<div class="container"><div class="row"><div class="col">',
      '#suffix' => '</div></div></div>'
    ];
  }

}
