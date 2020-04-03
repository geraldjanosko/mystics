<?php

namespace Drupal\mystics_services;

/**
 * Class Service
 */
class Service {

  var $link, $title, $desc, $bg_image;

  /**
   * Constructs a new Page Header object.
   */
  public function __construct(string $link, string $title, array $desc, string $bgImage) {
    $this->link = $link;
    $this->title = $title;
    $this->desc = $desc;
    $this->bg_image = $bgImage;
  }
}
