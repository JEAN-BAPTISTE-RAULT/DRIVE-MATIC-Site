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
 * Controle d'acces de `delivery_address` : proprietaire, + lecture admin.
 *
 * Premiere entite multi-instance par partenaire du projet (voir
 * docs/plans/configurateur-etape-3-livraison.md §4) : empeche un partenaire
 * de consulter/modifier/supprimer l'adresse d'un autre, y compris via une
 * URL forgee. Les routes d'edition/suppression s'appuient sur
 * `_entity_access: 'delivery_address.update'`/`'.delete'`, verifiees ici
 * cote serveur avant tout rendu de formulaire.
 *
 * La lecture ('view') est en plus ouverte a tout compte ayant la permission
 * `administer users` : consomme par le recapitulatif en lecture seule ajoute
 * a `/user/{uid}/edit` (drivematic_partner_form_user_form_alter()), pour que
 * l'admin retrouve les adresses de livraison d'un partenaire sans avoir a
 * se connecter a sa place. 'update'/'delete' restent strictement
 * proprietaire : aucun besoin metier de les ouvrir a l'admin.
 */
final class DeliveryAddressAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if (!$entity instanceof DeliveryAddress || !in_array($operation, ['view', 'update', 'delete'], TRUE)) {
      return AccessResult::neutral();
    }

    if ($operation === 'view' && $account->hasPermission('administer users')) {
      return AccessResult::allowed()->cachePerPermissions();
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
