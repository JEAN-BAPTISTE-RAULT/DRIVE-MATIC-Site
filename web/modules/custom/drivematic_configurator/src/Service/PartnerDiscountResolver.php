<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\user\UserInterface;

/**
 * Resout la remise partenaire negociee pour un type d'equipement donne.
 *
 * Remplace l'ancien taux unique `field_discount_rate` (ADR-043) : chaque
 * type d'equipement catalogue (`retrovision_ext`/`retrovision_int`/
 * `telecommande_vor`/`pedalier`, memes valeurs que
 * EquipmentPrice::type_equipement) a son propre champ sur le compte
 * partenaire (`field_discount_<type>`), independant des 3 autres.
 */
final class PartnerDiscountResolver {

  /**
   * Resout le taux de remise du partenaire pour un type d'equipement.
   *
   * @param \Drupal\user\UserInterface|null $partner
   *   Le compte partenaire, ou NULL (compte supprime, ligne orpheline).
   * @param string|null $catalogType
   *   Le type d'equipement catalogue (`retrovision_ext`, `retrovision_int`,
   *   `telecommande_vor` ou `pedalier`), ou NULL si inconnu (ligne
   *   anterieure a l'introduction de `equipment_type`, ADR-043).
   *
   * @return float|null
   *   Le taux (%), ou NULL si absent/vide — jamais 0.0 par defaut, pour
   *   distinguer « pas de remise negociee » d'une remise explicitement
   *   nulle saisie par Drive Matic sur une ligne.
   */
  public function resolve(?UserInterface $partner, ?string $catalogType): ?float {
    if (!$partner || !$catalogType) {
      return NULL;
    }

    $field_name = 'field_discount_' . $catalogType;
    if (!$partner->hasField($field_name)) {
      return NULL;
    }

    $value = $partner->get($field_name)->value;

    return $value === NULL || $value === '' ? NULL : (float) $value;
  }

}
