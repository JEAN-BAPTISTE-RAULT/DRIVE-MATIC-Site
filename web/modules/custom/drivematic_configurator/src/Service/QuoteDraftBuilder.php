<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteConfiguration;

/**
 * Reconstruit un brouillon PrivateTempStore depuis un devis deja persiste.
 *
 * Sert Modifier et Dupliquer (ADR-052) : les deux ont besoin de rejouer un
 * devis existant dans la structure exacte lue par
 * ConfigurationForm/QuoteCalculator (tid de taxonomie, pas les libelles
 * geles de `quote_configuration`) — voir QuoteVehicleTermResolver pour la
 * resolution des tid.
 */
final class QuoteDraftBuilder {

  /**
   * Mapping `equipment_type` (catalogue) => champ du brouillon.
   *
   * Inverse de `QuoteCalculator::EQUIPMENT_CATALOG_TYPES` (prive, non
   * reutilisable telle quelle) — meme dette de mapping duplique que celle
   * deja documentee sur QuoteDetailController/ADR-028.
   */
  private const EQUIPMENT_TYPE_TO_FIELD = [
    'telecommande_vor' => 'equipment_telecommande_vor',
    'retrovision_ext' => 'equipment_retrovision_ext',
    'retrovision_int' => 'equipment_retrovision_int',
    'pedalier' => 'equipment_double_pedalier',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QuoteVehicleTermResolver $termResolver,
  ) {}

  /**
   * Construit le brouillon d'un devis (une entree par configuration resolue).
   *
   * @return array{draft: array, dropped: int}
   *   `draft` : structure `card.vehicle.*`/`card.equipment.*` (memes cles
   *   que soumises par ConfigurationForm). `dropped` : nombre de
   *   configurations ecartees faute de resolution (catalogue vehicule
   *   change depuis) — le message d'avertissement (ADR-052) etant un texte
   *   fixe, un compte suffit a decider de l'afficher, pas besoin du detail
   *   de chaque configuration ecartee.
   */
  public function build(Quote $quote): array {
    $configuration_storage = $this->entityTypeManager->getStorage('quote_configuration');
    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');

    $configuration_ids = $configuration_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('weight', 'ASC')
      ->execute();

    $draft = [];
    $dropped = 0;
    $key = 0;

    /** @var \Drupal\drivematic_configurator\Entity\QuoteConfiguration $configuration */
    foreach ($configuration_storage->loadMultiple($configuration_ids) as $configuration) {
      $tids = $this->termResolver->resolve($configuration);
      if ($tids === NULL) {
        $dropped++;
        continue;
      }

      $draft[$key] = [
        'card' => [
          'vehicle' => $tids,
          'vehicle_count' => ['quantity' => (int) $configuration->get('vehicle_count')->value],
          'equipment' => $this->buildEquipment($line_storage, $configuration),
        ],
      ];
      $key++;
    }

    return ['draft' => $draft, 'dropped' => $dropped];
  }

  /**
   * Reconstruit les cases a cocher equipement d'une configuration.
   */
  private function buildEquipment(EntityStorageInterface $line_storage, QuoteConfiguration $configuration): array {
    $equipment = array_fill_keys(array_values(self::EQUIPMENT_TYPE_TO_FIELD), 0);
    $retrovision_ext_quantity = 1;

    $line_ids = $line_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('configuration_id', $configuration->id())
      ->execute();

    /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine $line */
    foreach ($line_storage->loadMultiple($line_ids) as $line) {
      $field_name = self::EQUIPMENT_TYPE_TO_FIELD[$line->get('equipment_type')->value] ?? NULL;
      if ($field_name === NULL) {
        continue;
      }

      $equipment[$field_name] = 1;
      if ($field_name === 'equipment_retrovision_ext') {
        $retrovision_ext_quantity = (int) $line->get('quantity_per_vehicle')->value;
      }
    }

    $equipment['retrovision_ext_quantity'] = ['quantity' => $retrovision_ext_quantity];

    return $equipment;
  }

}
