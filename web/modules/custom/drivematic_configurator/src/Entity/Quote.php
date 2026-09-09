<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drivematic_configurator\QuoteAccessControlHandler;
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
 * confirme une commande passee par telephone) -> STATUS_ARCHIVE, auto a J+30
 * apres `date_confirmation`, depuis STATUS_COMMANDE uniquement
 * (drivematic_configurator_cron()), delai fixe sans mecanisme de report ; ou
 * manuel, uniquement par le partenaire depuis son tableau de bord — Drive
 * Matic n'a plus la possibilite d'archiver un devis depuis le back-office
 * (cf. PRD F15, « cas limites »).
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
    'access' => QuoteAccessControlHandler::class,
  ],
  links: [
    'canonical' => '/admin/content/devis/{quote}',
  ],
  admin_permission: 'view drivematic configurator quotes',
  base_table: 'quote',
)]
final class Quote extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

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
        self::STATUS_A_COMMANDER => 'Commande en cours',
        self::STATUS_COMMANDE => 'Commandé',
        self::STATUS_ARCHIVE => 'Archivé',
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Date de création'));

    // Mise a jour automatique a chaque enregistrement
    // (ChangedItem::preSave()) : sert de « date de derniere modification » a
    // la page « Mes devis » (colonne Date de l'onglet « à finaliser »,
    // ADR-051 addendum).
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modifié le'));

    $fields['date_commande'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Date de commande'));

    // Distinct de `date_commande` (qui documente le clic « Commander »
    // initial) : posee par QuotePersister au meme instant aujourd'hui, mais
    // conceptuellement la date du passage au statut STATUS_A_COMMANDER —
    // amenee a diverger si une future action fait passer un devis « à
    // finaliser » existant a ce statut sans repasser par QuotePersister.
    // Jamais remise a jour ensuite (ADR-051 addendum) : colonne Date des
    // onglets « en cours »/« archivés », classement decroissant.
    $fields['date_comptable'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Date comptable'));

    $fields['date_confirmation'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Date de confirmation (téléphone)'));

    $fields['date_archivage'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup("Date d'archivage"));

    // Reference a l'entite DeliveryAddress utilisee, en plus des champs
    // delivery_* figes ci-dessous (ADR-052) : sert UNIQUEMENT a
    // preselectionner la bonne adresse a la reprise/duplication (Modifier/
    // Dupliquer) — jamais relue pour l'affichage/PDF/e-mail, qui restent
    // exclusivement sur les champs geles (ne contredit pas le principe de
    // gel d'ADR-033). Absente (NULL) pour tout devis cree avant ce champ :
    // repli sur le comportement existant de DeliveryForm dans ce seul cas.
    $fields['delivery_address_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Adresse de livraison utilisée'))
      ->setSetting('target_type', 'delivery_address');

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
    $fields['billing_vat'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Facturation — TVA intracommunautaire'))
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
