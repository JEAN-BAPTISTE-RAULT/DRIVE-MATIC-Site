<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drivematic_configurator\Entity\QuoteConfiguration;

/**
 * Resout les tid vehicule d'une configuration depuis ses libelles geles.
 *
 * `QuoteConfiguration` ne garde que des libelles (ADR-033 : jamais de
 * reference vivante vers la taxonomie) — Modifier/Dupliquer ont besoin de
 * les retraduire en tid pour reconstruire un brouillon exploitable par
 * ConfigurationForm/QuoteCalculator (ADR-052). Un terme renomme ou supprime
 * depuis la creation du devis fait echouer la resolution : a l'appelant de
 * decider quoi faire de la configuration (voir QuoteDraftBuilder).
 */
final class QuoteVehicleTermResolver {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Resout les tid marque/modele/motorisation d'une configuration.
   *
   * Strategie (ADR-052) : chercher `vehicle_model` par nom, verifier que son
   * `field_brand` correspond au libelle `vehicle_brand` gele (desambiguise
   * un homonyme entre marques), puis chercher `motorisation` par nom
   * (vocabulaire ferme a 4 valeurs, ADR-003 — pas d'homonyme possible).
   *
   * @return array{brand: string, model: string, motorisation: string}|null
   *   Les tid (chaines, meme convention que le brouillon ConfigurationForm),
   *   ou NULL si l'un des 3 termes ne peut plus etre resolu.
   */
  public function resolve(QuoteConfiguration $configuration): ?array {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $brand_label = (string) $configuration->get('vehicle_brand')->value;
    $model_label = (string) $configuration->get('vehicle_model')->value;
    $motorisation_label = (string) $configuration->get('motorisation')->value;

    $model = NULL;
    $brand_tid = NULL;
    /** @var \Drupal\taxonomy\TermInterface $candidate */
    foreach ($term_storage->loadByProperties(['vid' => 'vehicle_model', 'name' => $model_label]) as $candidate) {
      $candidate_brand_tid = $candidate->get('field_brand')->target_id;
      $candidate_brand = $candidate_brand_tid ? $term_storage->load($candidate_brand_tid) : NULL;
      if ($candidate_brand && $candidate_brand->label() === $brand_label) {
        $model = $candidate;
        $brand_tid = $candidate_brand_tid;
        break;
      }
    }
    if (!$model) {
      return NULL;
    }

    $motorisation_terms = $term_storage->loadByProperties(['vid' => 'motorisation', 'name' => $motorisation_label]);
    $motorisation = $motorisation_terms ? reset($motorisation_terms) : NULL;
    if (!$motorisation) {
      return NULL;
    }

    return [
      'brand' => (string) $brand_tid,
      'model' => (string) $model->id(),
      'motorisation' => (string) $motorisation->id(),
    ];
  }

}
