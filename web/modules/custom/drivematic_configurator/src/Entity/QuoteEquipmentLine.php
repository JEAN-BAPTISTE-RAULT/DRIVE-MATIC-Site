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
 * `unit_price`/`discounted_unit_price`/`ht`/`discounted_ht` sont geles a la
 * creation (copies depuis QuoteCalculator, lui-meme deja fige sur le
 * catalogue au moment du calcul, ADR-030/031) : jamais recalcules ni relus
 * depuis `equipment_price` ensuite, et jamais mutes non plus par
 * `dm_discount_rate` — ils restent la base « catalogue + remise partenaire »
 * de reference. Seul `dm_discount_rate` (remise exceptionnelle Drive Matic,
 * PRD F15/F16) est modifiable apres coup (QuoteDiscountForm) : le prix EFFECTIF
 * (partenaire + DM, en cascade) se calcule a la lecture via
 * getEffectiveDiscountedUnitPrice()/getEffectiveDiscountedHt(), jamais stocke
 * — pour eviter qu'une remise appliquee deux fois ne se cumule sur
 * elle-meme.
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

    // Copie gelee de `equipment_price.reference` (F17), au meme titre que
    // `unit_price` : jamais relue depuis le catalogue ensuite. Utilisee
    // uniquement par le PDF du devis pour l'instant (pas affichee dans
    // QuoteDetailController) — absente sur les lignes creees avant ce champ.
    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Référence catalogue'))
      ->setSetting('max_length', 64);

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

    // Remise exceptionnelle accordee par Drive Matic sur cette ligne
    // (PRD F15/F16, « cas limites »), tant que le devis parent est au statut
    // Quote::STATUS_A_COMMANDER. S'applique en cascade sur le prix DEJA
    // remise par la remise globale du partenaire (discounted_unit_price),
    // jamais sur le tarif catalogue brut (unit_price).
    $fields['dm_discount_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Remise Drive Matic (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue(0);

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

  /**
   * Prix unitaire remisé effectif (remise partenaire PUIS remise Drive Matic).
   *
   * @return float|null
   *   NULL si la ligne est indisponible (`unavailable`, aucun prix a geler).
   */
  public function getEffectiveDiscountedUnitPrice(): ?float {
    if ($this->get('unavailable')->value) {
      return NULL;
    }

    $base = (float) $this->get('discounted_unit_price')->value;
    $dm_rate = (float) ($this->get('dm_discount_rate')->value ?? 0);

    return $base * (1 - $dm_rate / 100);
  }

  /**
   * Total HT remisé effectif (remise partenaire PUIS remise Drive Matic).
   *
   * @return float|null
   *   NULL si la ligne est indisponible (`unavailable`, aucun prix a geler).
   */
  public function getEffectiveDiscountedHt(): ?float {
    $unit_price = $this->getEffectiveDiscountedUnitPrice();

    return $unit_price === NULL ? NULL : $unit_price * (int) $this->get('quantity_total')->value;
  }

}
