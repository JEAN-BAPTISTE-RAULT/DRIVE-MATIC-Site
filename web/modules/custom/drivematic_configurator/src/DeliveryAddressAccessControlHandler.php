<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;

/**
 * Controle d'acces de `delivery_address` : proprietaire uniquement.
 *
 * Premiere entite multi-instance par partenaire du projet (voir
 * docs/plans/configurateur-etape-3-livraison.md §4) : empeche un partenaire
 * de consulter/modifier/supprimer l'adresse d'un autre, y compris via une
 * URL forgee. Les routes d'edition/suppression s'appuient sur
 * `_entity_access: 'delivery_address.update'`/`'.delete'`, verifiees ici
 * cote serveur avant tout rendu de formulaire.
 */
final class DeliveryAddressAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if (!$entity instanceof DeliveryAddress || !in_array($operation, ['view', 'update', 'delete'], TRUE)) {
      return AccessResult::neutral();
    }

    return AccessResult::allowedIf((int) $entity->getOwnerId() === (int) $account->id())
      ->addCacheableDependency($entity)
      ->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIf($account->isAuthenticated())->cachePerPermissions();
  }

}
