<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Calcule les lignes et totaux d'un devis a partir d'un brouillon.
 *
 * Entrees : le brouillon d'etape 1 (structure exacte des valeurs soumises
 * par ConfigurationForm, une entree par configuration valide), le catalogue
 * de tarifs (drivematic_catalog, entite equipment_price) et le compte
 * partenaire courant — dont la remise, par equipement, est resolue via
 * PartnerDiscountResolver (ADR-043 : 4 taux independants, plus un seul
 * champ global) UNE SEULE FOIS ici, au moment du calcul : le taux resolu
 * est gele (copie dans `dm_discount_rate` par QuotePersister) et ne suit
 * plus jamais le compte partenaire ensuite (ADR-043 addendum 2).
 *
 * Ne resout aucun libelle de vehicule (marque/modele/motorisation) : ceci
 * reste la responsabilite de l'appelant (QuoteForm), pour garder ce service
 * concentre sur le calcul financier — le meme calcul devra etre rejoue tel
 * quel par la future persistance F15 pour geler les prix a la creation d'un
 * devis (cf. ADR-030).
 */
final class QuoteCalculator {

  /**
   * Case a cocher (ConfigurationForm::EQUIPMENT_LABELS) => [libelle, type].
   *
   * Le 2e element est le `type_equipement` du catalogue. Confirme avec
   * l'utilisatrice : « Double pédalier auto-école » utilise le meme tarif
   * pedalier PMR (variable par vehicule x motorisation) que les autres
   * vehicules du catalogue — ce n'est pas un produit distinct, malgre son
   * libelle.
   */
  private const EQUIPMENT_CATALOG_TYPES = [
    'equipment_telecommande_vor' => ['Télécommande VOR', 'telecommande_vor'],
    'equipment_retrovision_ext' => ['Rétrovision extérieure', 'retrovision_ext'],
    'equipment_retrovision_int' => ['Rétrovision intérieure', 'retrovision_int'],
    'equipment_double_pedalier' => ['Double pédalier auto-école', 'pedalier'],
  ];

  private const VAT_RATE = 0.20;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly PartnerDiscountResolver $discountResolver,
  ) {}

  /**
   * Calcule les lignes/totaux pour toutes les configurations d'un brouillon.
   *
   * @param array $draftConfigurations
   *   Configurations valides telles que soumises par ConfigurationForm
   *   (cle stable => valeurs `card.vehicle.*`/`card.equipment.*`).
   * @param \Drupal\user\UserInterface|null $partner
   *   Le compte partenaire courant, dont la remise par equipement est
   *   resolue via PartnerDiscountResolver (NULL => aucune remise).
   *
   * @return array
   *   ['configurations' => [cle => [...]], 'grand_totals' => [...]].
   */
  public function calculate(array $draftConfigurations, ?UserInterface $partner): array {
    $configurations = [];
    $grand_totals = $this->emptyTotals();

    foreach ($draftConfigurations as $key => $configuration) {
      $result = $this->calculateConfiguration($configuration, $partner);
      $configurations[$key] = $result;
      foreach ($grand_totals as $metric => $value) {
        $grand_totals[$metric] = $value + $result['totals'][$metric];
      }
    }

    return [
      'configurations' => $configurations,
      'grand_totals' => $grand_totals,
    ];
  }

  /**
   * Calcule les lignes/totaux d'une seule configuration.
   *
   * @return array
   *   ['vehicle_model' => tid, 'motorisation' => tid, 'vehicle_count' => int,
   *   'lines' => [...], 'totals' => [...], 'totals_per_vehicle' => [...]].
   *   `totals` porte le cumul pour tous les vehicules de la configuration ;
   *   `totals_per_vehicle` le meme calcul divise par `vehicle_count` (les
   *   deux sont affiches separement des que la configuration compte plus
   *   d'un vehicule, maquette 508:13961 « Tarif par vehicule » / « Tarif
   *   total vehicules »).
   */
  private function calculateConfiguration(array $configuration, ?UserInterface $partner): array {
    $vehicle = $configuration['card']['vehicle'];
    $equipment = $configuration['card']['equipment'];
    $vehicle_count = (int) ($configuration['card']['vehicle_count']['quantity'] ?? 1);
    $model_tid = $vehicle['model'];
    $motorisation_tid = $vehicle['motorisation'];

    $lines = [];
    foreach (self::EQUIPMENT_CATALOG_TYPES as $field_name => [$label, $catalog_type]) {
      if (empty($equipment[$field_name])) {
        continue;
      }

      $quantity_per_vehicle = $field_name === 'equipment_retrovision_ext'
        ? (int) ($equipment['retrovision_ext_quantity']['quantity'] ?? 1)
        : 1;
      $quantity_total = $quantity_per_vehicle * $vehicle_count;

      // Rétrovision ext./int. sont des tarifs fixes (vehicle_model/
      // motorisation NULL en base, ADR-030) : ne jamais filtrer dessus,
      // sans quoi la ligne ne correspond plus a aucune entree du catalogue.
      $needs_model = in_array($catalog_type, ['telecommande_vor', 'pedalier'], TRUE);
      $needs_motorisation = $catalog_type === 'pedalier';
      $price = $this->loadPrice(
        $catalog_type,
        $needs_model ? $model_tid : NULL,
        $needs_motorisation ? $motorisation_tid : NULL,
      );

      if (!$price) {
        $lines[] = [
          'label' => $label,
          'unavailable' => TRUE,
          'equipment_type' => $catalog_type,
          'quantity_per_vehicle' => $quantity_per_vehicle,
          'quantity_total' => $quantity_total,
        ];
        continue;
      }

      // Remise partenaire resolue UNE FOIS ici, a la creation du devis, et
      // gelee dans `dm_discount_rate` (ADR-043 addendum 2) : un devis ne
      // doit plus jamais changer de prix parce que le compte partenaire a
      // ete modifie depuis — seule une action explicite sur CE devis
      // (QuoteDiscountForm) peut ensuite faire evoluer son prix.
      $discount_rate = $this->discountResolver->resolve($partner, $catalog_type) ?? 0.0;
      $unit_price = (float) $price->get('tarif_ht')->value;
      $discounted_unit_price = $unit_price * (1 - $discount_rate / 100);
      $lines[] = [
        'label' => $label,
        'unavailable' => FALSE,
        'equipment_type' => $catalog_type,
        'reference' => (string) $price->get('reference')->value,
        'unit_price' => $unit_price,
        'discounted_unit_price' => $discounted_unit_price,
        'quantity_per_vehicle' => $quantity_per_vehicle,
        'quantity_total' => $quantity_total,
        'ht' => $unit_price * $quantity_total,
        'discounted_ht' => $discounted_unit_price * $quantity_total,
        'dm_discount_rate' => $discount_rate,
      ];
    }

    $totals = $this->emptyTotals();
    foreach ($lines as $line) {
      if ($line['unavailable']) {
        continue;
      }
      $totals['ht'] += $line['ht'];
      $totals['discounted_ht'] += $line['discounted_ht'];
    }
    $totals['discount'] = $totals['ht'] - $totals['discounted_ht'];
    $totals['vat'] = $totals['discounted_ht'] * self::VAT_RATE;
    $totals['ttc'] = $totals['discounted_ht'] + $totals['vat'];

    $totals_per_vehicle = array_map(
      static fn (float $value): float => $value / $vehicle_count,
      $totals,
    );

    return [
      'vehicle_model' => $model_tid,
      'motorisation' => $motorisation_tid,
      'vehicle_count' => $vehicle_count,
      'lines' => $lines,
      'totals' => $totals,
      'totals_per_vehicle' => $totals_per_vehicle,
    ];
  }

  /**
   * Construit une structure de totaux initialisee a zero.
   *
   * @return array{ht: float, discount: float, discounted_ht: float, vat: float, ttc: float}
   *   Totaux vierges.
   */
  private function emptyTotals(): array {
    return ['ht' => 0.0, 'discount' => 0.0, 'discounted_ht' => 0.0, 'vat' => 0.0, 'ttc' => 0.0];
  }

  /**
   * Charge la ligne de catalogue correspondante, ou NULL si absente.
   *
   * Une absence (donnee disparue entre l'etape 1 et l'etape 2 — catalogue
   * reimporte entre temps) ne doit jamais faire echouer le calcul.
   */
  private function loadPrice(string $type, ?string $model_tid, ?string $motorisation_tid): ?object {
    $properties = ['type_equipement' => $type];
    if ($model_tid !== NULL) {
      $properties['vehicle_model'] = $model_tid;
    }
    if ($motorisation_tid !== NULL) {
      $properties['motorisation'] = $motorisation_tid;
    }
    $prices = $this->entityTypeManager->getStorage('equipment_price')->loadByProperties($properties);
    return $prices ? reset($prices) : NULL;
  }

}
