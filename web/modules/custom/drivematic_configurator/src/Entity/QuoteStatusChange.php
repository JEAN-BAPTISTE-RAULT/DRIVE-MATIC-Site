<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Une entree d'historique des changements de statut d'un devis (ADR-038).
 *
 * Une entree par transition : creation du devis (statut initial, `uid` =
 * le partenaire), marquage manuel « Commande »/« Archive » (`uid` =
 * l'administrateur Drive Matic), ou archivage automatique a 30 jours
 * (`uid` absent = « Automatique », drivematic_configurator_cron()).
 * Jamais mise a jour ni supprimee : `created` porte la date exacte de la
 * transition (le moment de creation de CETTE entree l'est).
 *
 * Meme statut technique, deliberement duplique, que `Quote::status`
 * (Entity/Quote.php) — garder les deux `allowed_values` synchronises en
 * cas de futur ajout de statut.
 */
#[ContentEntityType(
  id: 'quote_status_change',
  label: new TranslatableMarkup('Changement de statut de devis'),
  label_collection: new TranslatableMarkup('Changements de statut de devis'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  base_table: 'quote_status_change',
)]
final class QuoteStatusChange extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['quote_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Devis'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'quote');

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Nouveau statut'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        Quote::STATUS_A_FINALISER => 'À finaliser',
        Quote::STATUS_A_COMMANDER => 'À commander',
        Quote::STATUS_COMMANDE => 'Commandé',
        Quote::STATUS_ARCHIVE => 'Archivé',
      ]);

    // Absent (NULL) pour une transition automatique (cron) : voir la note
    // de classe. Pas d'EntityOwnerInterface/Trait ici — « proprietaire »
    // n'a pas de sens pour cette entite (le vrai proprietaire du devis est
    // le partenaire, pas forcement l'auteur de CETTE transition).
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Effectué par'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Date du changement'));

    return $fields;
  }

}
