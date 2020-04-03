<?php

namespace Drupal\mystics_products\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Mystics product entities.
 *
 * @ingroup mystics_products
 */
interface MysticsProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Mystics product name.
   *
   * @return string
   *   Name of the Mystics product.
   */
  public function getName();

  /**
   * Sets the Mystics product name.
   *
   * @param string $name
   *   The Mystics product name.
   *
   * @return \Drupal\mystics_products\Entity\MysticsProductInterface
   *   The called Mystics product entity.
   */
  public function setName($name);

  /**
   * Gets the Mystics product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Mystics product.
   */
  public function getCreatedTime();

  /**
   * Sets the Mystics product creation timestamp.
   *
   * @param int $timestamp
   *   The Mystics product creation timestamp.
   *
   * @return \Drupal\mystics_products\Entity\MysticsProductInterface
   *   The called Mystics product entity.
   */
  public function setCreatedTime($timestamp);

}
