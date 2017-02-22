<?php

namespace Drupal\give;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the give form entity type.
 *
 * @see \Drupal\give\Entity\GiveForm.
 */
class GiveFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete' || $operation == 'update') {
      // Only administrator or the user with the `create and edit give forms`
      // can update or delete forms.
      return AccessResult::allowedIf($account->hasPermission('administer give'))
        ->orIf(AccessResult::allowedIf($account->hasPermission('create and edit give forms')))->cachePerPermissions();
    }
    return AccessResult::allowedIfHasPermission($account, 'access give forms');
  }

}
