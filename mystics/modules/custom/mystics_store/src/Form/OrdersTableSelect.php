<?php

namespace Drupal\mystics_store\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OrdersTableSelect.
 */
class OrdersTableSelect extends FormBase {

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\mystics_store\Services\MStoreManager definition.
   *
   * @var \Drupal\mystics_store\Services\MStoreManager
   */
  protected $mysticsStoreManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->loggerFactory = $container->get('logger.factory');
    $instance->mysticsStoreManager = $container->get('mystics_store.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'orders_table_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $manager = $this->mysticsStoreManager;
    $orders = $manager->getOrders();
    $options = [];
    foreach($orders as $order) {
      $moid = $order->moid;
      $mystics_order_id = $order->mystics_order_id;
      $mystics_order_uid = $order->mystics_order_uid;
      $mystics_client_secret = $order->mystics_client_secret;
      $mystics_payment_intent_id = $order->mystics_payment_intent_id;
      $mystics_order_amount = $order->mystics_order_amount;
      $mystics_order_date = $order->mystics_order_date;
      $mystics_order_status = $order->mystics_order_status;
      $options[] = ['moid' => $moid, 'mystics_order_id' => $mystics_order_id, 'mystics_order_uid' => $mystics_order_uid, 'mystics_client_secret' => $mystics_client_secret, 'mystics_payment_intent_id' => $mystics_payment_intent_id, 'mystics_order_amount' => $mystics_order_amount, 'mystics_order_date' => $mystics_order_date, 'mystics_order_status' => $mystics_order_status];
    }

    $header = [
      'moid' => $this->t('ID'),
      'mystics_order_id' => $this->t('Order Id'),
      'mystics_order_uid' => $this->t('Order Uid'),
      'mystics_client_secret' => $this->t('Client Secret'),
      'mystics_payment_intent_id' => $this->t('Payment Intent Id'),
      'mystics_order_amount' => $this->t('Order Amount'),
      'mystics_order_date' => $this->t('Order Date'),
      'mystics_order_status' => $this->t('Order Status')
    ];

    $form['table'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this
        ->t('No orders found'),
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Capture Payments'),
    ];

    $form['pager']['#type'] = 'pager';

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
