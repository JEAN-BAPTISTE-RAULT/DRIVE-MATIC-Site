<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Une entree d'historique des remises Drive Matic accordees sur un devis.
 *
 * Une entree par LIGNE d'equipement dont `dm_discount_rate` a reellement
 * change (QuoteDiscountForm::submitForm()) — une soumission qui ne modifie
 * aucun taux ne cree aucune entree. `uid` est toujours renseigne (seul
 * point d'entree : le formulaire de remise, reserve aux administrateurs
 * Drive Matic authentifies) mais reste un `entity_reference` non requis
 * pour absorber une suppression de compte ulterieure — pas de cas
 * "automatique" ici, contrairement a `QuoteStatusChange`.
 *
 * Meme pattern que `QuoteStatusChange` (entite dediee, pas de champ JSON
 * serialise) : aucun handler d'acces propre, protegee par l'acces deja
 * verifie sur le `Quote` parent.
 */
#[ContentEntityType(
  id: 'quote_discount_change',
  label: new TranslatableMarkup('Remise Drive Matic accordée'),
  label_collection: new TranslatableMarkup('Remises Drive Matic accordées'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  base_table: 'quote_discount_change',
)]
final class QuoteDiscountChange extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['quote_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Devis'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'quote');

    $fields['line_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup("Ligne d'équipement"))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'quote_equipment_line');

    $fields['old_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Ancien taux (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2);

    $fields['new_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Nouveau taux (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2);

    // Non requis : voir la note de classe (compte admin potentiellement
    // supprime ulterieurement, jamais un cas "automatique").
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Accordée par'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Date de la remise'));

    return $fields;
  }

}
