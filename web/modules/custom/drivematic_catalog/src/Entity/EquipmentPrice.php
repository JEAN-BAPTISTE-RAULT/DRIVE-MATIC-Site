<?php

declare(strict_types=1);

namespace Drupal\drivematic_catalog\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drivematic_catalog\EquipmentPriceListBuilder;

/**
 * Une ligne de tarif du catalogue (F17), pour l'un des 4 equipements.
 *
 * Catalogue vivant : entierement remplace a chaque import du combinatoire
 * (CatalogImporter::apply()). Ne gele aucun prix — le gel se fera dans la
 * future Ligne d'equipement d'un devis (F15, pas encore implementee), qui
 * copiera `tarif_ht`/`reference` a la creation sans jamais relire cette
 * entite ensuite (cf. docs/plans/catalogue-tarifs-import.md).
 *
 * `vehicle_model`/`motorisation` varient selon `type_equipement` :
 * - telecommande_vor : vehicle_model rempli, motorisation vide (le tarif VOR
 *   ne varie pas par motorisation dans le fichier source).
 * - pedalier : vehicle_model ET motorisation remplis.
 * - retrovision_ext / retrovision_int : les deux vides (tarif unique, une
 *   seule ligne en base par type, quel que soit le vehicule).
 */
#[ContentEntityType(
  id: 'equipment_price',
  label: new TranslatableMarkup('Ligne de tarif'),
  label_collection: new TranslatableMarkup('Catalogue de tarifs'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => EquipmentPriceListBuilder::class,
  ],
  admin_permission: 'administer catalog import',
  base_table: 'equipment_price',
)]
final class EquipmentPrice extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['type_equipement'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Équipement'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'telecommande_vor' => 'Télécommande VOR',
        'pedalier' => 'Pédalier',
        'retrovision_ext' => 'Rétrovision extérieure',
        'retrovision_int' => 'Rétrovision intérieure',
      ]);

    $fields['vehicle_model'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Modèle de véhicule'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['vehicle_model' => 'vehicle_model']]);

    $fields['motorisation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Motorisation'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['motorisation' => 'motorisation']]);

    $fields['tarif_ht'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tarif catalogue (€ HT)'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Référence'))
      ->setSetting('max_length', 64);

    $fields['type_chassis'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type châssis'))
      ->setSetting('max_length', 64);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Importé le'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * Aucun champ ne porte naturellement un libelle unique : compose a la
   * volee pour l'ecran de liste (EquipmentPriceListBuilder).
   */
  public function label(): string {
    $type_labels = $this->getFieldDefinition('type_equipement')
      ->getSetting('allowed_values');
    $label = $type_labels[$this->get('type_equipement')->value] ?? $this->get('type_equipement')->value;

    $vehicle = $this->get('vehicle_model')->entity;
    if ($vehicle) {
      $label .= ' — ' . $vehicle->label();
      $motorisation = $this->get('motorisation')->entity;
      if ($motorisation) {
        $label .= ' (' . $motorisation->label() . ')';
      }
    }

    return $label;
  }

}
