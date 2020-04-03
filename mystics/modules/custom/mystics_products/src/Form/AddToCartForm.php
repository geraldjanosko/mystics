<?php

namespace Drupal\mystics_products\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddToCartForm.
 */
class AddToCartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = null) {
    $form['product_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id
    ];

    $form['buy_now'] = [
      '#type' => 'submit',
      '#title' => $this->t('Buy Now'),
      '#default_value' => $this->t('Buy Now'),
      '#weight' => '0',
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
    if(!isset($_SESSION['shoppingCart'])) {
      $_SESSION['shoppingCart'] = array();
    }
    // unset($_SESSION['shoppingCart']);
    foreach ($form_state->getValues() as $key => $value) {
      if($key == 'product_id') {
        $shoppingCart = &$_SESSION['shoppingCart'];
        if(array_key_exists($value, $shoppingCart)) {
          // $quantity = $shoppingCart[$value]['quantity'] + 1;
          // $shoppingCart[$value] = ['quantity' => $quantity];
          $shoppingCart[$value] = ['quantity' => 1];
        } else {
          $shoppingCart[$value] = ['quantity' => 1];
        }
      }
    }
    $form_state->setRedirect('mystics_stripe.controller_stripe_checkout');
  }

}
