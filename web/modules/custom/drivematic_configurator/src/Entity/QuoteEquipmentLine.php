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
 * depuis `equipment_price` ensuite.
 *
 * `dm_discount_rate` (remise Drive Matic, PRD F15/F16) est lui aussi gele a
 * la creation — snapshot du taux partenaire de cet equipement A CET INSTANT
 * (ADR-043 addendum 2, QuoteCalculator/QuotePersister) : un devis ne change
 * plus jamais de prix parce que le compte partenaire a ete modifie depuis.
 * Seule une action explicite sur CE devis precis, via QuoteDiscountForm,
 * peut ensuite le faire evoluer — remplacement, jamais de cumul avec
 * `discounted_unit_price` (simple instantane de creation, plus utilise par
 * le calcul du prix effectif). `dm_discount_rate` ne devrait plus jamais
 * etre NULL passee la creation ; les methodes ci-dessous traitent NULL comme
 * 0% par securite (lignes anterieures a cette regle, cf. hook_update
 * 11010/11011).
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

    // Cle machine du type d'equipement catalogue (ADR-043), distincte de
    // `label` (chaine traduite, pas une cle stable) : sert a resoudre la
    // remise partenaire de cet equipement via PartnerDiscountResolver
    // (`field_discount_<equipment_type>` sur le compte proprietaire).
    $fields['equipment_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type équipement catalogue'))
      ->setSetting('max_length', 32);

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

    // Snapshot du taux partenaire de cet equipement, gele a la creation du
    // devis (ADR-043 addendum 2) — jamais NULL passee la creation. Editable
    // ensuite uniquement via QuoteDiscountForm, tant que le devis parent est
    // au statut Quote::STATUS_A_COMMANDER : remplace alors la valeur pour
    // TOUTES les lignes de cet equipement sur CE devis, jamais un cumul.
    $fields['dm_discount_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Remise Drive Matic (%)'))
      ->setSetting('precision', 5)
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

  /**
   * Prix unitaire remisé effectif (`dm_discount_rate`, gelé, ADR-043).
   *
   * Toujours calculé depuis le tarif catalogue brut (`unit_price`), jamais
   * depuis `discounted_unit_price` (simple instantané de création,
   * inutilisé par ce calcul) : pas de cumul possible avec quoi que ce soit
   * d'autre. `dm_discount_rate` étant gelé dès la création (snapshot du
   * taux partenaire à cet instant, QuoteCalculator/QuotePersister), ce
   * prix ne varie plus tant que personne n'agit explicitement sur CE devis
   * via QuoteDiscountForm.
   *
   * @return float|null
   *   NULL si la ligne est indisponible (`unavailable`, aucun prix à geler).
   */
  public function getEffectiveDiscountedUnitPrice(): ?float {
    if ($this->get('unavailable')->value) {
      return NULL;
    }

    $dm_rate = $this->get('dm_discount_rate')->value;
    $rate = $dm_rate !== NULL ? (float) $dm_rate : 0.0;
    $unit_price = (float) $this->get('unit_price')->value;

    return $unit_price * (1 - $rate / 100);
  }

  /**
   * Total HT remisé effectif (`dm_discount_rate`, gelé, ADR-043).
   *
   * @return float|null
   *   NULL si la ligne est indisponible (`unavailable`, aucun prix à geler).
   */
  public function getEffectiveDiscountedHt(): ?float {
    $unit_price = $this->getEffectiveDiscountedUnitPrice();

    return $unit_price === NULL ? NULL : $unit_price * (int) $this->get('quantity_total')->value;
  }

}
