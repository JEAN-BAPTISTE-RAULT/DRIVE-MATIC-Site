<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\user\UserInterface;

/**
 * Materialise un brouillon en entites Devis/Configuration/Ligne d'equipement.
 *
 * Aucun recalcul : reutilise integralement les prix deja geles par
 * QuoteCalculator (memes formules qu'affichees a l'etape 2, ADR-031), pour
 * qu'un devis enregistre reflete exactement ce que le partenaire a vu.
 */
final class QuotePersister {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QuoteCalculator $quoteCalculator,
    private readonly QuoteReferenceGenerator $referenceGenerator,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Cree le devis (et ses configurations/lignes) a partir d'un brouillon.
   *
   * @param array $draft
   *   Brouillon `PrivateTempStore` (memes valeurs que soumises par
   *   ConfigurationForm).
   * @param string $status
   *   Quote::STATUS_A_FINALISER ou Quote::STATUS_A_COMMANDER.
   * @param \Drupal\user\UserInterface $account
   *   Le partenaire proprietaire du devis.
   * @param \Drupal\drivematic_configurator\Entity\DeliveryAddress $deliveryAddress
   *   L'adresse de livraison retenue (gelee dans le devis, jamais relue
   *   ensuite).
   *
   * @return \Drupal\drivematic_configurator\Entity\Quote
   *   Le devis cree.
   */
  public function persist(array $draft, string $status, UserInterface $account, DeliveryAddress $deliveryAddress): Quote {
    $result = $this->quoteCalculator->calculate($draft, $account);
    $now = $this->time->getCurrentTime();

    $quote_storage = $this->entityTypeManager->getStorage('quote');

    /** @var \Drupal\drivematic_configurator\Entity\Quote $quote */
    $quote = $quote_storage->create([
      'uid' => $account->id(),
      'reference' => $this->referenceGenerator->generate(),
      'status' => $status,
      'date_commande' => $status === Quote::STATUS_A_COMMANDER ? $now : NULL,
      'billing_raison_sociale' => $account->get('field_company_name')->value,
      'billing_adresse' => $account->get('field_company_address')->value,
      'billing_complement' => $account->get('field_address_complement')->value,
      'billing_code_postal' => $account->get('field_postal_code')->value,
      'billing_ville' => $account->get('field_city')->value,
      'billing_siret' => $account->get('field_siret')->value,
      'delivery_raison_sociale' => $deliveryAddress->get('raison_sociale')->value,
      'delivery_adresse' => $deliveryAddress->get('adresse')->value,
      'delivery_complement' => $deliveryAddress->get('complement')->value,
      'delivery_code_postal' => $deliveryAddress->get('code_postal')->value,
      'delivery_ville' => $deliveryAddress->get('ville')->value,
      'total_ht' => $result['grand_totals']['ht'],
      'total_discount' => $result['grand_totals']['discount'],
      'total_discounted_ht' => $result['grand_totals']['discounted_ht'],
      'total_vat' => $result['grand_totals']['vat'],
      'total_ttc' => $result['grand_totals']['ttc'],
    ]);
    $quote->save();

    $this->persistConfigurations($draft, $result['configurations'], (int) $quote->id());
    $this->logStatusChange($quote, (int) $account->id());

    return $quote;
  }

  /**
   * Enregistre une entree d'historique (ADR-038, Entity/QuoteStatusChange.php).
   */
  private function logStatusChange(Quote $quote, ?int $uid): void {
    $this->entityTypeManager->getStorage('quote_status_change')->create([
      'quote_id' => $quote->id(),
      'status' => $quote->get('status')->value,
      'uid' => $uid,
    ])->save();
  }

  /**
   * Cree les entites Configuration + Ligne d'equipement d'un devis.
   *
   * @param array $draft
   *   Brouillon complet (pour la marque, absente du resultat calcule —
   *   QuoteCalculator ne resout pas les libelles vehicule, cf. sa note de
   *   classe).
   * @param array $configurations
   *   `QuoteCalculator::calculate()['configurations']`.
   * @param int $quoteId
   *   Identifiant du devis parent, deja enregistre.
   */
  private function persistConfigurations(array $draft, array $configurations, int $quoteId): void {
    $configuration_storage = $this->entityTypeManager->getStorage('quote_configuration');
    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $weight = 0;
    foreach ($configurations as $key => $configuration) {
      $brand_tid = $draft[$key]['card']['vehicle']['brand'] ?? NULL;
      $brand_term = $brand_tid ? $term_storage->load($brand_tid) : NULL;
      $model_term = $configuration['vehicle_model'] ? $term_storage->load($configuration['vehicle_model']) : NULL;
      $motorisation_term = $configuration['motorisation'] ? $term_storage->load($configuration['motorisation']) : NULL;

      /** @var \Drupal\drivematic_configurator\Entity\QuoteConfiguration $quote_configuration */
      $quote_configuration = $configuration_storage->create([
        'quote_id' => $quoteId,
        'vehicle_brand' => $brand_term?->label() ?? '',
        'vehicle_model' => $model_term?->label() ?? '',
        'motorisation' => $motorisation_term?->label() ?? '',
        'vehicle_count' => $configuration['vehicle_count'],
        'weight' => $weight++,
      ]);
      $quote_configuration->save();

      $line_weight = 0;
      foreach ($configuration['lines'] as $line) {
        $line_storage->create([
          'configuration_id' => $quote_configuration->id(),
          'label' => $line['label'],
          'unavailable' => $line['unavailable'],
          'equipment_type' => $line['equipment_type'] ?? NULL,
          'reference' => $line['reference'] ?? NULL,
          'unit_price' => $line['unit_price'] ?? NULL,
          'discounted_unit_price' => $line['discounted_unit_price'] ?? NULL,
          'quantity_per_vehicle' => $line['quantity_per_vehicle'],
          'quantity_total' => $line['quantity_total'],
          'ht' => $line['ht'] ?? NULL,
          'discounted_ht' => $line['discounted_ht'] ?? NULL,
          'dm_discount_rate' => $line['dm_discount_rate'] ?? NULL,
          'weight' => $line_weight++,
        ])->save();
      }
    }
  }

}
