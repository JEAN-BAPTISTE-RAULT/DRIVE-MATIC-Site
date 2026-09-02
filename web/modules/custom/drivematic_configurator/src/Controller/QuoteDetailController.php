<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteConfiguration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page de detail en lecture seule d'un devis (back-office).
 *
 * Seul moyen de consulter le contenu complet d'un devis (configurations
 * vehicule, lignes d'equipement, adresses gelees) depuis
 * `/admin/content/devis` (Vue `quotes`, qui ne montre que reference/
 * partenaire/statut/total TTC/date). Route canonique (`entity.quote.
 * canonical`), protegee par la meme permission que la Vue —
 * `admin_permission` de l'entite `quote` ('view drivematic configurator
 * quotes'), accordee automatiquement par le handler d'acces par defaut de
 * Drupal (`EntityAccessControlHandler::checkAccess()`), aucun handler
 * d'acces custom necessaire.
 *
 * `QuoteConfiguration`/`QuoteEquipmentLine` n'ont pas de controle d'acces
 * propre : chargees uniquement ici, en interne, apres que l'acces au
 * `Quote` parent a deja ete verifie par la route.
 */
final class QuoteDetailController extends ControllerBase {

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('date.formatter'));
  }

  /**
   * Titre de la page.
   */
  public function title(Quote $quote): TranslatableMarkup {
    return $this->t('Devis @reference', ['@reference' => (string) $quote->label()]);
  }

  /**
   * Construit la page de detail.
   */
  public function view(Quote $quote): array {
    return [
      'back' => [
        '#type' => 'link',
        '#title' => $this->t('← Retour à la liste des devis'),
        '#url' => Url::fromRoute('view.quotes.page_1'),
      ],
      'summary' => [
        '#type' => 'details',
        '#title' => $this->t('Résumé'),
        '#open' => TRUE,
        'table' => $this->buildSummaryTable($quote),
      ],
      'billing' => [
        '#type' => 'details',
        '#title' => $this->t('Facturation'),
        '#open' => TRUE,
        'table' => $this->buildAddressTable($quote, 'billing', TRUE),
      ],
      'delivery' => [
        '#type' => 'details',
        '#title' => $this->t('Livraison'),
        '#open' => TRUE,
        'table' => $this->buildAddressTable($quote, 'delivery', FALSE),
      ],
      'configurations' => $this->buildConfigurations($quote),
    ];
  }

  /**
   * Tableau « Résumé » : partenaire, statut, dates, totaux.
   */
  private function buildSummaryTable(Quote $quote): array {
    $statuses = [
      Quote::STATUS_A_FINALISER => $this->t('À finaliser'),
      Quote::STATUS_EN_COURS => $this->t('En cours'),
      Quote::STATUS_ARCHIVE => $this->t('Archivé'),
    ];
    $status_value = (string) $quote->get('status')->value;
    $account = $quote->getOwner();
    $partner = $account
      ? $account->getDisplayName() . ' (' . $account->getEmail() . ')'
      : (string) $this->t('Compte supprimé');

    return [
      '#type' => 'table',
      '#rows' => [
        [$this->t('Partenaire'), $partner],
        [$this->t('Statut'), $statuses[$status_value] ?? $status_value],
        [$this->t('Date de création'), $this->formatDate($quote->get('created')->value)],
        [$this->t('Date de commande'), $this->formatDate($quote->get('date_commande')->value)],
        [$this->t("Date d'archivage"), $this->formatDate($quote->get('date_archivage')->value)],
        [$this->t('Total HT'), $this->formatPrice($quote->get('total_ht')->value)],
        [$this->t('Remise HT'), $this->formatPrice($quote->get('total_discount')->value)],
        [$this->t('Total remisé HT'), $this->formatPrice($quote->get('total_discounted_ht')->value)],
        [$this->t('TVA'), $this->formatPrice($quote->get('total_vat')->value)],
        [$this->t('Total TTC'), $this->formatPrice($quote->get('total_ttc')->value)],
      ],
    ];
  }

  /**
   * Tableau d'adresse (facturation ou livraison, champs gelés sur le devis).
   *
   * @param \Drupal\drivematic_configurator\Entity\Quote $quote
   *   Le devis.
   * @param string $prefix
   *   Préfixe des champs gelés : 'billing' ou 'delivery'.
   * @param bool $withSiret
   *   Ajoute la ligne Siret (facturation uniquement, absente côté livraison).
   */
  private function buildAddressTable(Quote $quote, string $prefix, bool $withSiret): array {
    $rows = [
      [$this->t('Raison sociale'), $quote->get("{$prefix}_raison_sociale")->value],
      [$this->t('Adresse'), $quote->get("{$prefix}_adresse")->value],
      [$this->t("Complément d'adresse"), $quote->get("{$prefix}_complement")->value],
      [$this->t('Code postal'), $quote->get("{$prefix}_code_postal")->value],
      [$this->t('Ville'), $quote->get("{$prefix}_ville")->value],
    ];

    if ($withSiret) {
      $rows[] = [$this->t('Siret'), $quote->get('billing_siret')->value];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
    ];
  }

  /**
   * Une section par configuration (véhicule + lignes d'équipement).
   */
  private function buildConfigurations(Quote $quote): array {
    $configuration_storage = $this->entityTypeManager()->getStorage('quote_configuration');
    $ids = $configuration_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('weight', 'ASC')
      ->execute();

    $build = [
      '#type' => 'container',
    ];

    if (!$ids) {
      $build['empty'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Aucune configuration.'),
      ];
      return $build;
    }

    /** @var \Drupal\drivematic_configurator\Entity\QuoteConfiguration $configuration */
    foreach ($configuration_storage->loadMultiple($ids) as $delta => $configuration) {
      $build["configuration_{$delta}"] = [
        '#type' => 'details',
        '#title' => $this->t('@brand @model — @motorisation (@count véhicule(s))', [
          '@brand' => (string) $configuration->get('vehicle_brand')->value,
          '@model' => (string) $configuration->get('vehicle_model')->value,
          '@motorisation' => (string) $configuration->get('motorisation')->value,
          '@count' => (string) $configuration->get('vehicle_count')->value,
        ]),
        '#open' => TRUE,
        'lines' => $this->buildEquipmentLinesTable($configuration),
      ];
    }

    return $build;
  }

  /**
   * Tableau des lignes d'équipement d'une configuration.
   */
  private function buildEquipmentLinesTable(QuoteConfiguration $configuration): array {
    $line_storage = $this->entityTypeManager()->getStorage('quote_equipment_line');
    $ids = $line_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('configuration_id', $configuration->id())
      ->sort('weight', 'ASC')
      ->execute();

    $rows = [];
    foreach ($line_storage->loadMultiple($ids) as $line) {
      if ($line->get('unavailable')->value) {
        $rows[] = [
          (string) $line->get('label')->value,
          ['data' => $this->t('Indisponible'), 'colspan' => 6],
        ];
        continue;
      }

      $rows[] = [
        (string) $line->get('label')->value,
        $this->formatPrice($line->get('unit_price')->value),
        $this->formatPrice($line->get('discounted_unit_price')->value),
        (string) $line->get('quantity_per_vehicle')->value,
        (string) $line->get('quantity_total')->value,
        $this->formatPrice($line->get('ht')->value),
        $this->formatPrice($line->get('discounted_ht')->value),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Équipement'),
        $this->t('Prix unitaire'),
        $this->t('Prix unitaire remisé'),
        $this->t('Qté/véhicule'),
        $this->t('Qté totale'),
        $this->t('HT'),
        $this->t('HT remisé'),
      ],
      '#rows' => $rows,
    ];
  }

  /**
   * Formate un horodatage, ou un tiret si absent.
   */
  private function formatDate(mixed $timestamp): string|TranslatableMarkup {
    return $timestamp ? $this->dateFormatter->format((int) $timestamp, 'short') : $this->t('—');
  }

  /**
   * Formate un montant en euros (convention française : virgule, espace).
   *
   * Même formule que `QuoteForm::formatPrice()` (front partenaire) — pas
   * mutualisée dans un service commun pour un helper d'une ligne.
   */
  private function formatPrice(mixed $amount): string|TranslatableMarkup {
    if ($amount === NULL || $amount === '') {
      return $this->t('—');
    }
    return number_format((float) $amount, 2, ',', ' ') . ' €';
  }

}
