<?php

namespace Drupal\mystics_page_headers;

/**
 * Class PageHeader
 */
class PageHeader {

  var $title, $bg_images;

  /**
   * Constructs a new Page Header object.
   */
  public function __construct(string $title, array $bgImages = NULL) {
    $this->title = $title;
    $this->bg_images = $bgImages;
  }

  /**
   * Getter for $bgImages.
   */
  public function getBgImages() {
    return $this->bg_images;
  }

  /**
   * Setter for $bgImages.
   */
  public function setBgImages($bgImages) {
    $this->bg_images = $bgImages;
  }
}
