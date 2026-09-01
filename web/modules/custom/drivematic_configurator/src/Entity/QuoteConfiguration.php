<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Groupe vehicule d'un devis (PRD §5 : « N → 1 devis ; 1 → N lignes »).
 *
 * `vehicle_brand`/`vehicle_model`/`motorisation` sont des libelles geles
 * (chaines, pas de reference vers `taxonomy_term`) : un terme renomme ou
 * supprime apres coup ne doit jamais alterer un devis deja enregistre —
 * meme principe de gel que les champs billing_/delivery_ de Quote.
 */
#[ContentEntityType(
  id: 'quote_configuration',
  label: new TranslatableMarkup('Configuration de devis'),
  label_collection: new TranslatableMarkup('Configurations de devis'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  base_table: 'quote_configuration',
)]
final class QuoteConfiguration extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['quote_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Devis'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'quote');

    $fields['vehicle_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Marque'))
      ->setSetting('max_length', 255);

    $fields['vehicle_model'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Modèle'))
      ->setSetting('max_length', 255);

    $fields['motorisation'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setSetting('max_length', 255);

    $fields['vehicle_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Nombre de véhicules'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Poids'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return trim($this->get('vehicle_brand')->value . ' / ' . $this->get('vehicle_model')->value);
  }

}
