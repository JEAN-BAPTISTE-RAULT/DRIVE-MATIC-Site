<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Une entree d'historique de changement de remise sur un compte partenaire.
 *
 * Une entree par champ `field_discount_<type>` (ADR-043) reellement change
 * sur un compte `user`, quel que soit le canal d'ecriture (formulaire admin
 * `/user/{uid}/edit`, `drush`, script) — detecte par
 * `drivematic_configurator_user_update()` (hook_ENTITY_TYPE_update generique,
 * pas un submit handler de formulaire specifique). Une sauvegarde de compte
 * qui ne modifie aucune des 4 remises ne cree aucune entree.
 *
 * `old_rate`/`new_rate` restent NULLABLES (contrairement a
 * `QuoteDiscountChange::old_rate`/`new_rate`, toujours des nombres concrets) :
 * « vide » est ici un etat metier reel et permanent (pas de remise negociee
 * pour cet equipement), jamais assimilable a 0%.
 *
 * But (ADR-044) : permettre a Drive Matic de justifier un ecart entre le
 * taux affiche aujourd'hui sur le compte et celui reellement fige sur un
 * devis cree avant ce changement (ADR-043 addendum 2, snapshot a la
 * creation, jamais de suivi en direct).
 *
 * Meme pattern que `QuoteStatusChange`/`QuoteDiscountChange` : entite dediee,
 * aucun handler d'acces propre — protegee par la permission `administer
 * users` deja verifiee par l'appelant (drivematic_partner.module) avant
 * tout affichage.
 */
#[ContentEntityType(
  id: 'partner_discount_change',
  label: new TranslatableMarkup('Changement de remise partenaire'),
  label_collection: new TranslatableMarkup('Changements de remise partenaire'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  base_table: 'partner_discount_change',
)]
final class PartnerDiscountChange extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['partner_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Partenaire'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user');

    // Cle machine du type d'equipement (ADR-043 : retrovision_ext/
    // retrovision_int/telecommande_vor/pedalier), pas le nom du champ
    // Drupal complet — coherent avec `QuoteEquipmentLine::equipment_type`.
    $fields['equipment_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type équipement catalogue'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32);

    $fields['old_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Ancien taux (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2);

    $fields['new_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Nouveau taux (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2);

    // Non requis : absorbe un compte administrateur supprime ulterieurement.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Modifiée par'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Date de la modification'));

    return $fields;
  }

}
