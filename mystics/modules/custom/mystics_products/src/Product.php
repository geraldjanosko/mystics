<?php

namespace Drupal\mystics_products;

/**
 * Class Product
 */
class Product {

  var $id, $title, $desc, $price, $addToCartForm;

  /**
   * Constructs a new Page Header object.
   */
  public function __construct(string $id, string $title, array $desc, string $price, $add_to_cart_form) {
    $this->id = $id;
    $this->title = $title;
    $this->desc = $desc;
    $this->price = $price;
    $this->addToCartForm = $add_to_cart_form;
  }
}
