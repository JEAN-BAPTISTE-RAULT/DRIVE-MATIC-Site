<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\drivematic_configurator\Entity\Quote;

/**
 * Controle d'acces de `quote` : proprietaire, en plus de l'admin existant.
 *
 * Premiere verification d'acces par propriete sur cette entite (ADR-052,
 * necessaire pour les actions Modifier/Dupliquer/Supprimer de la page « Mes
 * devis » — gap identifie des ADR-046/051). `Quote` porte deja un
 * `admin_permission` (« view drivematic configurator quotes », back-office
 * DM) : `parent::checkAccess()` l'applique automatiquement, on ne fait que
 * l'etendre au proprietaire pour les operations partenaire.
 */
final class QuoteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if (!$entity instanceof Quote || !in_array($operation, ['view', 'update', 'delete'], TRUE)) {
      return parent::checkAccess($entity, $operation, $account);
    }

    $admin_access = parent::checkAccess($entity, $operation, $account);
    if ($admin_access->isAllowed()) {
      return $admin_access;
    }

    return AccessResult::allowedIf((int) $entity->getOwnerId() === (int) $account->id())
      ->addCacheableDependency($entity)
      ->cachePerUser();
  }

}
