<?php

declare(strict_types=1);

namespace Drupal\drivematic_catalog\Batch;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Callbacks Batch API pour l'import du combinatoire (F17).
 *
 * Regroupees en 3 etapes pour un retour de progression a l'utilisateur
 * (docs/plans/catalogue-tarifs-import.md, §5) : taxonomie, purge du
 * catalogue, creation des lignes de tarif par lots de self::CHUNK_SIZE.
 * Callbacks statiques (contrainte Batch API : pas d'injection de
 * dependances) — chacun recupere le service via \Drupal::service().
 */
final class CatalogImportBatch {

  private const CHUNK_SIZE = 40;

  /**
   * Construit les operations du batch a partir de la structure parsee.
   *
   * @param array $parsed
   *   Retour de CatalogImporter::parse().
   *
   * @return array
   *   Operations pretes pour batch_set().
   */
  public static function buildOperations(array $parsed): array {
    $operations = [
      [[self::class, 'applyTaxonomy'], [$parsed]],
      [[self::class, 'clearPrices'], []],
    ];
    foreach (array_chunk($parsed['prices'], self::CHUNK_SIZE) as $chunk) {
      $operations[] = [[self::class, 'createPriceRowsChunk'], [$chunk]];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public static function applyTaxonomy(array $parsed, array &$context): void {
    $context['results']['taxonomy'] = \Drupal::service('drivematic_catalog.importer')->applyTaxonomy($parsed);
    $context['message'] = new TranslatableMarkup('Référentiel véhicules mis à jour…');
  }

  /**
   * {@inheritdoc}
   */
  public static function clearPrices(array &$context): void {
    $context['results']['prices_removed'] = \Drupal::service('drivematic_catalog.importer')->clearPrices();
    $context['results']['prices_created'] = 0;
    $context['message'] = new TranslatableMarkup('Ancien catalogue de tarifs supprimé…');
  }

  /**
   * {@inheritdoc}
   */
  public static function createPriceRowsChunk(array $chunk, array &$context): void {
    $created = \Drupal::service('drivematic_catalog.importer')->createPriceRows($chunk);
    $context['results']['prices_created'] = ($context['results']['prices_created'] ?? 0) + $created;
    $context['message'] = new TranslatableMarkup('@count lignes de tarif créées…', ['@count' => $context['results']['prices_created']]);
  }

  /**
   * {@inheritdoc}
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();
    if (!$success) {
      $messenger->addError(new TranslatableMarkup("L'import a échoué en cours de traitement — le catalogue peut être dans un état partiel, à réimporter."));
      return;
    }

    $taxonomy = $results['taxonomy'] ?? [];
    $messenger->addStatus(new TranslatableMarkup(
      "Import terminé : @brands_c marque(s) créée(s), @brands_d supprimée(s) ; @models_c modèle(s) créé(s), @models_u mis à jour, @models_d supprimé(s) ; @prices_c lignes de tarif créées (@prices_r supprimées de l'ancien catalogue).",
      [
        '@brands_c' => $taxonomy['marques_creees'] ?? 0,
        '@brands_d' => $taxonomy['marques_supprimees'] ?? 0,
        '@models_c' => $taxonomy['modeles_crees'] ?? 0,
        '@models_u' => $taxonomy['modeles_mis_a_jour'] ?? 0,
        '@models_d' => $taxonomy['modeles_supprimes'] ?? 0,
        '@prices_c' => $results['prices_created'] ?? 0,
        '@prices_r' => $results['prices_removed'] ?? 0,
      ],
    ));
    $messenger->addStatus(new TranslatableMarkup('<a href=":url">Voir le catalogue</a>', [
      ':url' => Url::fromRoute('entity.equipment_price.collection')->toString(),
    ]));
  }

}
