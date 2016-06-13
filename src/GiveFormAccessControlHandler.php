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
    if ($operation == 'delete') {
      // Only administrator can delete forms.  @TODO this probably isn't how to handle this permission.
      return AccessResult::allowedIf($account->hasPermission('administer give'))->cachePerPermissions();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
