<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drivematic_configurator\DeliveryAddressAccessControlHandler;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Adresse de livraison d'un partenaire (F14 3/3, PRD §5).
 *
 * Multi-instance par partenaire — contrairement a `equipment_price`
 * (drivematic_catalog), seule autre entite custom du projet, catalogue
 * unique importe en bloc — d'ou un controle d'acces par proprietaire
 * (DeliveryAddressAccessControlHandler), premier besoin de ce type ici.
 *
 * Amorcee automatiquement depuis les champs du compte (field_company_name/
 * field_company_address/field_address_complement/field_postal_code/
 * field_city) a la premiere visite de l'ecran Livraison si le partenaire n'a
 * encore aucune adresse (DeliveryForm::ensureAtLeastOneAddress()) — voir
 * docs/plans/configurateur-etape-3-livraison.md §7. Cette adresse par
 * defaut (`is_default`, seule obligatoire a la creation du compte, identique
 * a l'adresse de facturation) reste persistee comme une entite normale,
 * mais n'expose pas les liens Modifier/Supprimer (retour utilisatrice) :
 * seules les adresses ajoutees en plus par le partenaire le sont.
 *
 * Une adresse deja utilisee par un devis reste modifiable/supprimable sans
 * risque : le devis gele ses propres valeurs a la creation
 * (Quote::delivery_*) et n'est jamais affecte par une modification
 * ulterieure de cette entite.
 */
#[ContentEntityType(
  id: 'delivery_address',
  label: new TranslatableMarkup('Adresse de livraison'),
  label_collection: new TranslatableMarkup('Adresses de livraison'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'owner' => 'uid',
  ],
  handlers: [
    'access' => DeliveryAddressAccessControlHandler::class,
  ],
  base_table: 'delivery_address',
)]
final class DeliveryAddress extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']->setLabel(new TranslatableMarkup('Partenaire'));

    $fields['raison_sociale'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Raison sociale'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['adresse'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Adresse'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['complement'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("Complément d'adresse"))
      ->setSetting('max_length', 255);

    $fields['code_postal'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Code postal'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5);

    $fields['ville'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Ville'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    // TRUE uniquement pour l'adresse amorcee automatiquement depuis le
    // compte (DeliveryForm::ensureAtLeastOneAddress()) — jamais posee a TRUE
    // par le formulaire d'ajout/edition (DeliveryAddressForm), qui laisse la
    // valeur par defaut FALSE. Pilote l'affichage des liens Modifier/
    // Supprimer (DeliveryForm::buildAddressRow()), jamais l'acces lui-meme.
    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Adresse par défaut (compte)'))
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Créée le'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modifiée le'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->get('raison_sociale')->value;
  }

}
