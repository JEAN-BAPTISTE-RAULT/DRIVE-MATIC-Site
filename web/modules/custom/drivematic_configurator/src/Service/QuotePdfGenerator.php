<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteConfiguration;

/**
 * Genere le PDF d'un devis (maquette Figma 714:9296).
 *
 * N'affiche que des valeurs gelees (Quote/QuoteConfiguration/
 * QuoteEquipmentLine) : jamais de relecture du catalogue `equipment_price`,
 * jamais du compte partenaire non plus (`dm_discount_rate` est lui-meme un
 * snapshot gele a la creation du devis, ADR-043 addendum 2). Les totaux
 * generaux (Quote::total_*) sont deja tenus a jour par
 * QuoteDiscountForm::recalculateTotals() a chaque remise DM ; les bandeaux
 * par configuration/par vehicule n'existent nulle part en stock et sont
 * recalcules ici a partir du prix effectif de chaque ligne
 * (QuoteEquipmentLine::getEffectiveDiscountedUnitPrice()/
 * getEffectiveDiscountedHt()) — de l'arithmetique pure sur des valeurs deja
 * gelees.
 *
 * Toutes les valeurs sont pre-formatees ici (jamais dans le gabarit Twig,
 * qui reste un simple assemblage de chaines — meme convention que les
 * e-mails webform/mailer_policy du site).
 *
 * Genere uniquement au clic « Commander » (DeliveryForm) et regenere (ecrase
 * le fichier d'origine) a chaque remise Drive Matic (QuoteDiscountForm) —
 * seul autre evenement qui change les prix apres coup. Jamais a
 * « Enregistrer le devis » (decide avec l'utilisatrice).
 */
final class QuotePdfGenerator {

  private const DIRECTORY = 'private://devis-pdf';

  private const VAT_RATE = 0.20;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RendererInterface $renderer,
    private readonly FileSystemInterface $fileSystem,
    private readonly ThemeExtensionList $themeExtensionList,
    private readonly TimeInterface $time,
    private readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * URI du PDF d'un devis (deterministe, base sur sa reference).
   */
  public function getUri(Quote $quote): string {
    return self::DIRECTORY . '/' . $quote->get('reference')->value . '.pdf';
  }

  /**
   * Genere le PDF et l'enregistre sur le disque (ecrase s'il existe deja).
   *
   * @return string
   *   L'URI (`private://...`) du fichier ecrit.
   */
  public function generate(Quote $quote): string {
    $build = [
      '#theme' => 'quote_pdf',
      '#logo_data_uri' => $this->buildLogoDataUri(),
      '#generated_date' => $this->dateFormatter->format($this->time->getRequestTime(), 'custom', 'd/m/Y'),
      '#quote_number' => (string) $quote->get('reference')->value,
      '#billing' => $this->buildAddress($quote, 'billing', TRUE),
      '#delivery' => $this->buildAddress($quote, 'delivery', FALSE),
      '#configurations' => $this->buildConfigurations($quote),
      '#grand_totals' => [
        'ht' => $this->formatPrice($quote->get('total_ht')->value),
        'discount' => $this->formatPrice($quote->get('total_discount')->value),
        'discounted_ht' => $this->formatPrice($quote->get('total_discounted_ht')->value),
        'vat' => $this->formatPrice($quote->get('total_vat')->value),
        'ttc' => $this->formatPrice($quote->get('total_ttc')->value),
      ],
    ];
    $html = (string) $this->renderer->renderInIsolation($build);

    $options = new Options();
    $options->setIsRemoteEnabled(FALSE);
    $dompdf = new Dompdf($options);
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    $directory = self::DIRECTORY;
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $uri = $this->getUri($quote);
    $this->fileSystem->saveData($dompdf->output(), $uri, FileExists::Replace);

    return $uri;
  }

  /**
   * Encode le logo en data URI (Dompdf ne charge aucune ressource distante).
   *
   * SVG (export Figma du logo, node 714:9297) plutot que le PNG des
   * e-mails (`logo-drive-matic-legrand-email.png`, 633x73) : ce dernier,
   * rasterise a basse resolution, laisse apparaitre un residu de trait
   * parasite sous l'icone une fois agrandi au rendu print (300 DPI) — un
   * vectoriel n'a pas cette limite, quelle que soit la taille de sortie.
   */
  private function buildLogoDataUri(): string {
    $path = $this->themeExtensionList->getPath('drive_matic') . '/images/logo-drive-matic-legrand-pdf.svg';
    return 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($path));
  }

  /**
   * Lignes d'adresse geleees sur le devis (facturation ou livraison).
   *
   * @return string[]
   *   Lignes non vides, pretes a afficher.
   */
  private function buildAddress(Quote $quote, string $prefix, bool $withSiret): array {
    $lines = array_filter([
      (string) $quote->get("{$prefix}_raison_sociale")->value,
      (string) $quote->get("{$prefix}_adresse")->value,
      (string) $quote->get("{$prefix}_complement")->value,
      trim((string) $quote->get("{$prefix}_code_postal")->value . ' ' . (string) $quote->get("{$prefix}_ville")->value),
    ], static fn (string $line): bool => $line !== '');

    if ($withSiret && $quote->get('billing_siret')->value) {
      $lines[] = 'Siret ' . $quote->get('billing_siret')->value;
    }

    return array_values($lines);
  }

  /**
   * Une entree par configuration (vehicule + lignes + totaux formates).
   *
   * @return array[]
   *   Une entree par configuration, triee par poids.
   */
  private function buildConfigurations(Quote $quote): array {
    $configuration_storage = $this->entityTypeManager->getStorage('quote_configuration');
    $ids = $configuration_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('weight', 'ASC')
      ->execute();

    $configurations = [];
    /** @var \Drupal\drivematic_configurator\Entity\QuoteConfiguration $configuration */
    foreach ($configuration_storage->loadMultiple($ids) as $configuration) {
      $vehicle_count = (int) $configuration->get('vehicle_count')->value;
      [$lines, $totals] = $this->buildLinesAndTotals($configuration);

      $totals_per_vehicle = array_map(
        static fn (float $value): float => $vehicle_count > 0 ? $value / $vehicle_count : 0.0,
        $totals,
      );

      $configurations[] = [
        'vehicle_label' => sprintf(
          '%s / %s / %s',
          (string) $configuration->get('vehicle_brand')->value,
          (string) $configuration->get('vehicle_model')->value,
          (string) $configuration->get('motorisation')->value,
        ),
        'vehicle_count' => $vehicle_count,
        'lines' => $lines,
        'totals' => $this->formatTotals($totals),
        'totals_per_vehicle' => $this->formatTotals($totals_per_vehicle),
      ];
    }

    return $configurations;
  }

  /**
   * Lignes d'equipement (formatees) + totaux bruts d'une configuration.
   *
   * @return array
   *   Tuple [lignes formatees, totaux bruts (floats, cles ht/discount/
   *   discounted_ht/vat/ttc)].
   */
  private function buildLinesAndTotals(QuoteConfiguration $configuration): array {
    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');
    $ids = $line_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('configuration_id', $configuration->id())
      ->sort('weight', 'ASC')
      ->execute();

    $lines = [];
    $totals = ['ht' => 0.0, 'discount' => 0.0, 'discounted_ht' => 0.0, 'vat' => 0.0, 'ttc' => 0.0];

    /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine $line */
    foreach ($line_storage->loadMultiple($ids) as $line) {
      $unavailable = (bool) $line->get('unavailable')->value;
      $unit_price = (float) $line->get('unit_price')->value;
      $quantity_total = (int) $line->get('quantity_total')->value;

      $lines[] = [
        'reference' => (string) $line->get('reference')->value,
        'label' => (string) $line->label(),
        'unavailable' => $unavailable,
        'unit_price' => $this->formatPrice($line->get('unit_price')->value),
        'discounted_unit_price' => $unavailable ? NULL : $this->formatPrice($line->getEffectiveDiscountedUnitPrice()),
        'quantity_per_vehicle' => (int) $line->get('quantity_per_vehicle')->value,
        'quantity_total' => $quantity_total,
        'discounted_ht' => $unavailable ? NULL : $this->formatPrice($line->getEffectiveDiscountedHt()),
      ];

      if (!$unavailable) {
        $discounted_ht = (float) $line->getEffectiveDiscountedHt();
        $totals['ht'] += $unit_price * $quantity_total;
        $totals['discounted_ht'] += $discounted_ht;
      }
    }

    $totals['discount'] = $totals['ht'] - $totals['discounted_ht'];
    $totals['vat'] = $totals['discounted_ht'] * self::VAT_RATE;
    $totals['ttc'] = $totals['discounted_ht'] + $totals['vat'];

    return [$lines, $totals];
  }

  /**
   * Formate chaque valeur d'un tableau de totaux bruts en euros.
   *
   * @param array $totals
   *   Totaux bruts (floats, cles ht/discount/discounted_ht/vat/ttc).
   *
   * @return array
   *   Les memes cles, valeurs formatees en euros.
   */
  private function formatTotals(array $totals): array {
    return array_map(fn (float $value): string => $this->formatPrice($value), $totals);
  }

  /**
   * Formate un montant en euros.
   *
   * Meme convention que QuoteDetailController::formatPrice()/
   * QuoteForm::formatPrice() — pas mutualisee, meme raison qu'elles.
   */
  private function formatPrice(mixed $amount): string {
    if ($amount === NULL || $amount === '') {
      return '—';
    }
    return number_format((float) $amount, 2, ',', ' ') . ' €';
  }

}
