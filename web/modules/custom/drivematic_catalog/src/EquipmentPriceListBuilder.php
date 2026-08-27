<?php

declare(strict_types=1);

namespace Drupal\drivematic_catalog;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Ecran de controle en lecture seule apres import.
 *
 * Aucune action d'edition/suppression ligne par ligne (docs/plans/
 * catalogue-tarifs-import.md, §5) : corriger un tarif se fait en corrigeant
 * le fichier Excel source et en reimportant.
 */
final class EquipmentPriceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['type'] = $this->t('Équipement');
    $header['vehicle'] = $this->t('Véhicule');
    $header['motorisation'] = $this->t('Motorisation');
    $header['tarif'] = $this->t('Tarif (€ HT)');
    $header['reference'] = $this->t('Référence');
    $header['chassis'] = $this->t('Type châssis');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\drivematic_catalog\Entity\EquipmentPrice $entity */
    $type_labels = $entity->getFieldDefinition('type_equipement')->getSetting('allowed_values');

    $row['type'] = $type_labels[$entity->get('type_equipement')->value] ?? $entity->get('type_equipement')->value;
    $row['vehicle'] = $entity->get('vehicle_model')->entity?->label() ?? '—';
    $row['motorisation'] = $entity->get('motorisation')->entity?->label() ?? '—';
    $row['tarif'] = $entity->get('tarif_ht')->value;
    $row['reference'] = $entity->get('reference')->value ?: '—';
    $row['chassis'] = $entity->get('type_chassis')->value ?: '—';
    return $row;
  }

  /**
   * {@inheritdoc}
   *
   * Pas d'operations (edition/suppression) : voir la note de classe.
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    return [];
  }

}
