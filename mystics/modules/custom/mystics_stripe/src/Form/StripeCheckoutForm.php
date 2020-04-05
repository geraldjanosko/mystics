<?php

namespace Drupal\mystics_stripe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\mystics_stripe\Services\MStripeOrderManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StripeCheckoutForm.
 */
class StripeCheckoutForm extends FormBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\mystics_stripe\Services\MStripeOrderManager definition.
   *
   * @var \Drupal\mystics_stripe\Services\MStripeOrderManager
   */
  protected $mStripeOrderManager;

  /**
   * Constructs a new MStripeManager object.
   */
  public function __construct(AccountProxyInterface $current_user, MStripeOrderManager $m_stripe_order_manager) {
    $this->currentUser = $current_user;
    $this->mStripeOrderManager = $m_stripe_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('mystics_stripe.order_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripe_checkout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $userName = $user->get('field_full_name')->getValue();
    $userName = reset($userName)['value'];
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

    $form['user_name'] = [
      '#type' => 'hidden',
      '#default_value' => $userName
    ];

    $form['client_secret'] = [
      '#type' => 'hidden',
      '#default_value' => $clientSecret
    ];

    $form['stripe_elements_wrapper'] = [
       '#type' => 'markup',
       '#markup' => '<div id="card-element"></div><div id="card-errors" role="alert"></div>',
       '#prefix' => '<div class="form-group">',
       '#suffix' => '</div>'
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
  }

}
