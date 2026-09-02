<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Devis d'un partenaire (F14 3/3 / F15, PRD §5).
 *
 * Cree uniquement par QuotePersister, au clic sur « Enregistrer le devis »
 * ou « Commander » (DeliveryForm) — jamais avant (PrivateTempStore jusque
 * la, ADR-031). Gele integralement l'adresse de livraison retenue et les
 * coordonnees de facturation du compte au moment de la creation (champs
 * billing_ et delivery_) : ce devis ne doit plus jamais changer si le compte
 * ou une DeliveryAddress sont modifies ensuite (meme principe que le gel des
 * prix catalogue par QuoteCalculator, ADR-031).
 *
 * Cycle de vie implemente ici (sous-ensemble de F15 — onglets « Mes devis »,
 * Dupliquer, PDF : hors perimetre) :
 * STATUS_A_FINALISER -> STATUS_A_COMMANDER -> STATUS_COMMANDE (manuel, DM
 * confirme une commande passee par telephone) ou STATUS_ARCHIVE (auto a J+30
 * apres `date_commande` depuis STATUS_A_COMMANDER uniquement,
 * drivematic_configurator_cron(), ou manuel — jamais depuis STATUS_COMMANDE).
 * `date_commande` sert aussi de point de depart au delai des 30 jours : une
 * remise DM par ligne (QuoteEquipmentLine::dm_discount_rate) le remet a
 * l'heure actuelle, ce qui redemarre ce delai (cf. PRD F15, « cas limites »).
 */
#[ContentEntityType(
  id: 'quote',
  label: new TranslatableMarkup('Devis'),
  label_collection: new TranslatableMarkup('Devis'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'reference',
    'owner' => 'uid',
  ],
  handlers: [
    'views_data' => EntityViewsData::class,
  ],
  links: [
    'canonical' => '/admin/content/devis/{quote}',
  ],
  admin_permission: 'view drivematic configurator quotes',
  base_table: 'quote',
)]
final class Quote extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  public const STATUS_A_FINALISER = 'a_finaliser';
  public const STATUS_A_COMMANDER = 'a_commander';
  public const STATUS_COMMANDE = 'commande';
  public const STATUS_ARCHIVE = 'archive';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']->setLabel(new TranslatableMarkup('Partenaire'));

    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('N° de devis'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Statut'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_A_FINALISER => 'À finaliser',
        self::STATUS_A_COMMANDER => 'À commander',
        self::STATUS_COMMANDE => 'Commandé',
        self::STATUS_ARCHIVE => 'Archivé',
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Date de création'));

    $fields['date_commande'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Date de commande'));

    $fields['date_confirmation'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Date de confirmation (téléphone)'));

    $fields['date_archivage'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup("Date d'archivage"));

    // Instantanes geles a la creation (voir note de classe) : jamais relus
    // depuis `user`/`delivery_address` ensuite.
    $billing_delivery_labels = [
      'raison_sociale' => new TranslatableMarkup('Raison sociale'),
      'adresse' => new TranslatableMarkup('Adresse'),
      'complement' => new TranslatableMarkup("Complément d'adresse"),
      'code_postal' => new TranslatableMarkup('Code postal'),
      'ville' => new TranslatableMarkup('Ville'),
    ];
    foreach (['billing' => 'Facturation', 'delivery' => 'Livraison'] as $prefix => $group_label) {
      foreach ($billing_delivery_labels as $suffix => $label) {
        $fields["{$prefix}_{$suffix}"] = BaseFieldDefinition::create('string')
          ->setLabel(new TranslatableMarkup('@group — @label', ['@group' => $group_label, '@label' => $label]))
          ->setSetting('max_length', $suffix === 'code_postal' ? 5 : 255);
      }
    }
    $fields['billing_siret'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Facturation — Siret'))
      ->setSetting('max_length', 32);

    $total_labels = [
      'total_ht' => 'Total HT',
      'total_discount' => 'Remise HT',
      'total_discounted_ht' => 'Total remisé HT',
      'total_vat' => 'TVA',
      'total_ttc' => 'Total TTC',
    ];
    foreach ($total_labels as $field_name => $label) {
      $fields[$field_name] = BaseFieldDefinition::create('decimal')
        ->setLabel(new TranslatableMarkup('@label', ['@label' => $label]))
        ->setSetting('precision', 10)
        ->setSetting('scale', 2);
    }

    return $fields;
  }

}
