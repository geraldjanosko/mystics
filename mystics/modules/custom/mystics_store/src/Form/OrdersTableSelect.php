<?php

namespace Drupal\mystics_store\Form;

require_once drupal_get_path('module', 'mystics_stripe') . '/vendor/autoload.php';

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mystics_stripe\StripeAuthentication;
use Drupal\user\Entity\User;
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
   * Drupal\Core\Messenger\Messenger definition.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger; 

  /**
   * Drupal\mystics_stripe\Services\MStripeOrderManager definition.
   *
   * @var \Drupal\mystics_stripe\Services\MStripeOrderManager
   */
  protected $mStripeOrderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->loggerFactory = $container->get('logger.factory')->get('mystics_store');
    $instance->messenger = $container->get('messenger');
    $instance->mStripeOrderManager = $container->get('mystics_stripe.order_manager');
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
    $options = [
      null => $this->t('All'),
      'requires_capture' => $this->t('requires_capture'),
      'succeeded' => $this->t('succeeded'),
      'requires_payment_method' => $this->t('requires_payment_method')
    ];
    $orderStatus = isset($_GET['orderStatus']) ? $_GET['orderStatus'] : null;
    $form['filter_order_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Order Status'),
      '#options' => $options,
      '#default_value' => $orderStatus
    ];

    $customerName = isset($_GET['customerName']) ? $_GET['customerName'] : null;
    $form['filter_customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Name'),
      '#default_value' => $customerName
    ];

    $form['submit_filters'] = [
      '#type' => 'submit',
      '#prefix' => '<div class="form-actions js-form-wrapper form-wrapper">',
      '#submit' => ['::filterOrdersListSubmitForm'],
      '#suffix' => '</div>',
      '#value' => $this->t('Filter'),
    ];

    $options = [
      'capture_funds' => $this->t('Capture Funds')
    ];
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => $options,
      '#default_value' => key(reset($options))
    ];

    $form['submit_top'] = [
      '#type' => 'submit',
      '#prefix' => '<div class="form-actions js-form-wrapper form-wrapper">',
      '#suffix' => '</div>',
      '#value' => $this->t('Apply to selected items'),
    ];

    $header = [
      'moid' => ['data' => $this->t('ID'), 'field' => 'moid'],
      'mystics_order_id' => ['data' => $this->t('Order Id'), 'field' => 'mystics_order_id'],
      'mystics_order_full_name' => ['data' => $this->t('Order Full Name'), 'field' => 'field_full_name_value'],
      'mystics_client_secret' => ['data' => $this->t('Client Secret'), 'field' => 'mystics_client_secret'],
      'mystics_payment_intent_id' => ['data' => $this->t('Payment Intent Id'), 'field' => 'mystics_payment_intent_id'],
      'mystics_order_amount' => ['data' => $this->t('Order Amount'), 'field' => 'mystics_order_amount'],
      'mystics_order_date' => ['data' => $this->t('Order Date'), 'field' => 'mystics_order_date'],
      'mystics_order_status' => ['data' => $this->t('Order Status'), 'field' => 'mystics_order_status']
    ];

    $manager = $this->mStripeOrderManager;
    $orders = $manager->getOrders($customerName, $orderStatus, $header);
    $options = [];
    foreach($orders as $order) {
      $moid = $order->moid;
      $mysticsOrderId = $order->mystics_order_id;
      $mysticsOrderFullName = $order->field_full_name_value;
      $mysticsClientSecret = $order->mystics_client_secret;
      $mysticsPaymentIntentId = $order->mystics_payment_intent_id;
      $mysticsOrderAmount = $order->mystics_order_amount;
      $mysticsOrderDate = $order->mystics_order_date;
      $mysticsOrderStatus = $order->mystics_order_status;
      $options[$mysticsPaymentIntentId] = [
        'moid' => $moid,
        'mystics_order_id' => $mysticsOrderId,
        'mystics_order_full_name' => $mysticsOrderFullName,
        'mystics_client_secret' => $mysticsClientSecret,
        'mystics_payment_intent_id' => $mysticsPaymentIntentId,
        'mystics_order_amount' => $mysticsOrderAmount,
        'mystics_order_date' => $mysticsOrderDate,
        'mystics_order_status' => $mysticsOrderStatus
      ];
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No orders found.'),
    ];

    $form['submit_bottom'] = [
      '#type' => 'submit',
      '#prefix' => '<div class="form-actions js-form-wrapper form-wrapper">',
      '#suffix' => '</div>',
      '#value' => $this->t('Apply to selected items'),
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
  public function filterOrdersListSubmitForm(array &$form, FormStateInterface $form_state) {
    $args = [];
    $values = $form_state->getValues();
    foreach($values as $key => $value) {
      if(substr($key, 0, 6) == 'filter') {
        $argKey = substr($key, 7);
        $argKeys = explode('_', $argKey);
        $first = true;
        foreach($argKeys as &$argKey) {
          if($first == false) {
            $argKey = ucfirst($argKey);
          }
          $first = false;
        }
        $argKey = implode('', $argKeys);
        $args[$argKey] = $value;
      }
    }
    if(!empty($_GET)) {
      foreach($_GET as $key => $arg) {
        if(!array_key_exists($key, $args)) {
          $args[$key] = $arg;
        }
      }
    }
    $form_state->setRedirect('mystics_store.orders_table_select', [], ['query' => $args]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('action');
    switch($action) {
      case 'capture_funds':
        $selected = array_filter($form_state->getValue('table'));
        foreach ($selected as $paymentIntentId) {
          new StripeAuthentication;
          try{
            $payment_intent = \Stripe\PaymentIntent::retrieve(
              $paymentIntentId
            );
            $orderStatus = $payment_intent->status;
            try {
              $payment_intent->capture();
              $orderStatus = $payment_intent->status;
              try {
                $manager = $this->mStripeOrderManager;
                $manager->updateOrderStatus('payment_intent_id', $paymentIntentId, $orderStatus);
                $this->messenger->addStatus($this->t('Funds were successfully captured.'));
              } catch(Exception $e) {
                $this->loggerFactory->error($e);
              }
            } catch(\Stripe\Exception\CardException $e) {
              $errorCode = $e->getError()->code;
              $errorMessage = $e->getError()->message;
              $this->messenger->addError($this->t($errorMessage));
              $this->loggerFactory->error($errorCode);
            } catch (\Stripe\Exception\RateLimitException $e) {
              $errorCode = $e->getError()->code;
              $errorMessage = $e->getError()->message;
              $this->messenger->addError($this->t($errorMessage));
              $this->loggerFactory->error($errorCode);
            } catch(\Stripe\Exception\InvalidRequestException $e) {
              $errorCode = $e->getError()->code;
              $errorMessage = $e->getError()->message;
              $this->messenger->addError($this->t($errorMessage));
              $this->loggerFactory->error($errorCode);
              if($errorCode == 'payment_intent_unexpected_state') {
                try {
                  $manager = $this->mStripeOrderManager;
                  $manager->updateOrderStatus('payment_intent_id', $paymentIntentId, $orderStatus);
                } catch(Exception $e) {
                  $this->loggerFactory->error($e);
                }
              }
            } catch (\Stripe\Exception\AuthenticationException $e) {
              $errorCode = $e->getError()->code;
              $errorMessage = $e->getError()->message;
              $this->messenger->addError($this->t($errorMessage));
              $this->loggerFactory->error($errorCode);
            } catch (\Stripe\Exception\ApiConnectionException $e) {
              $errorMessage = $e->getError()->message;
              $this->messenger->addError($this->t($errorMessage));
              $this->loggerFactory->error($errorCode);
            } catch (\Stripe\Exception\ApiErrorException $e) {
              $errorMessage = $e->getError()->message;
              $this->messenger->addError($this->t($errorMessage));
              $this->loggerFactory->error($errorCode);
            } catch(Exception $e) {
              $this->loggerFactory->error($e);
            } catch(Exception $e) {
              $this->loggerFactory->error($e);
            }
          } catch (\Stripe\Exception\RateLimitException $e) {
            $errorCode = $e->getError()->code;
            $errorMessage = $e->getError()->message;
            $this->messenger->addError($this->t($errorMessage));
            $this->loggerFactory->error($errorCode);
          } catch(\Stripe\Exception\InvalidRequestException $e) {
            $errorCode = $e->getError()->code;
            $errorMessage = $e->getError()->message;
            $this->messenger->addError($this->t($errorMessage));
            $this->loggerFactory->error($errorCode);
          } catch (\Stripe\Exception\AuthenticationException $e) {
            $errorCode = $e->getError()->code;
            $errorMessage = $e->getError()->message;
            $this->messenger->addError($this->t($errorMessage));
            $this->loggerFactory->error($errorCode);
          } catch (\Stripe\Exception\ApiConnectionException $e) {
            $errorMessage = $e->getError()->message;
            $this->messenger->addError($this->t($errorMessage));
            $this->loggerFactory->error($errorCode);
          } catch (\Stripe\Exception\ApiErrorException $e) {
            $errorMessage = $e->getError()->message;
            $this->messenger->addError($this->t($errorMessage));
            $this->loggerFactory->error($errorCode);
          } catch(Exception $e) {
            $this->loggerFactory->error($e);
          }
        }

        break;  
    }
  }

}
