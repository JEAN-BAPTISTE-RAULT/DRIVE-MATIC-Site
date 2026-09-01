<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Ligne d'equipement d'une configuration de devis (PRD §5).
 *
 * Tarifs geles a la creation (copies depuis QuoteCalculator, lui-meme deja
 * fige sur le catalogue au moment du calcul, ADR-030/031) : jamais recalcules
 * ni relus depuis `equipment_price` ensuite.
 */
#[ContentEntityType(
  id: 'quote_equipment_line',
  label: new TranslatableMarkup("Ligne d'équipement de devis"),
  label_collection: new TranslatableMarkup("Lignes d'équipement de devis"),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  base_table: 'quote_equipment_line',
)]
final class QuoteEquipmentLine extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['configuration_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Configuration'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'quote_configuration');

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Équipement'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['unavailable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Tarif indisponible au moment du devis'))
      ->setDefaultValue(FALSE);

    $fields['unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tarif catalogue unitaire HT'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['discounted_unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tarif unitaire remisé HT'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['quantity_per_vehicle'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Quantité par véhicule'));

    $fields['quantity_total'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Quantité totale'));

    $fields['ht'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total HT'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['discounted_ht'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total remisé HT'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Poids'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->get('label')->value;
  }

}
