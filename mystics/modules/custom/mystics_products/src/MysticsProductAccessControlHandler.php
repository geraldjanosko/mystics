<?php

namespace Drupal\mystics_products;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Mystics product entity.
 *
 * @see \Drupal\mystics_products\Entity\MysticsProduct.
 */
class MysticsProductAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\mystics_products\Entity\MysticsProductInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished mystics product entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published mystics product entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit mystics product entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete mystics product entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add mystics product entities');
  }


}
