<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteConfiguration;
use Drupal\drivematic_configurator\Form\QuoteDiscountForm;
use Drupal\drivematic_configurator\Service\QuotePdfGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page de detail d'un devis (back-office).
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
 *
 * N'est plus strictement en lecture seule : les actions de statut (marquer
 * commande, archiver) et le formulaire de remise par ligne
 * (QuoteDiscountForm, embarque via `formBuilder()`) sont affiches sur
 * cette meme page quand le visiteur a la permission distincte `edit
 * drivematic configurator quotes` — jamais accordee au seul fait de voir
 * la page (permission de lecture separee, moindre privilege).
 */
final class QuoteDetailController extends ControllerBase {

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected QuotePdfGenerator $pdfGenerator,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('date.formatter'),
      $container->get('drivematic_configurator.quote_pdf_generator'),
      $container->get('file_system'),
    );
  }

  /**
   * Titre de la page.
   */
  public function title(Quote $quote): TranslatableMarkup {
    return $this->t('Devis @reference', ['@reference' => (string) $quote->label()]);
  }

  /**
   * Sert le PDF du devis (genere au clic « Commander », voir DeliveryForm).
   *
   * Affichage inline (pas de telechargement force) pour s'ouvrir dans
   * l'onglet/la fenetre depuis lequel le lien a ete ouvert. Meme controle
   * d'acces que la page de detail (_entity_access: quote.view) — pas de
   * `hook_file_download()` : le fichier est servi par cette route dediee,
   * jamais via `system.private_file_download`.
   */
  public function pdf(Quote $quote): BinaryFileResponse {
    $uri = $this->pdfGenerator->getUri($quote);
    if (!file_exists($uri)) {
      throw new NotFoundHttpException();
    }

    $response = new BinaryFileResponse($this->fileSystem->realpath($uri));
    $response->headers->set('Content-Type', 'application/pdf');
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $quote->get('reference')->value . '.pdf');

    return $response;
  }

  /**
   * Construit la page de detail.
   */
  public function view(Quote $quote): array {
    $build = [
      'back' => [
        '#type' => 'link',
        '#title' => $this->t('← Retour à la liste des devis'),
        '#url' => Url::fromRoute('view.quotes.page_1'),
      ],
    ];

    $can_edit = $this->currentUser()->hasPermission('edit drivematic configurator quotes');
    if ($can_edit && $quote->get('status')->value === Quote::STATUS_A_COMMANDER) {
      $build['actions'] = $this->buildActions($quote);
    }

    if (file_exists($this->pdfGenerator->getUri($quote))) {
      $build['pdf'] = [
        '#type' => 'link',
        '#title' => $this->t('Voir le PDF du devis'),
        '#url' => Url::fromRoute('drivematic_configurator.quote_pdf', ['quote' => $quote->id()]),
        '#attributes' => ['class' => ['button'], 'target' => '_blank'],
      ];
    }

    $build['summary'] = [
      '#type' => 'details',
      '#title' => $this->t('Résumé'),
      '#open' => TRUE,
      'table' => $this->buildSummaryTable($quote),
    ];
    $build['history'] = [
      '#type' => 'details',
      '#title' => $this->t('Historique'),
      '#open' => TRUE,
      'table' => $this->buildHistoryTable($quote),
    ];
    $build['billing'] = [
      '#type' => 'details',
      '#title' => $this->t('Facturation'),
      '#open' => TRUE,
      'table' => $this->buildAddressTable($quote, 'billing', TRUE),
    ];
    $build['delivery'] = [
      '#type' => 'details',
      '#title' => $this->t('Livraison'),
      '#open' => TRUE,
      'table' => $this->buildAddressTable($quote, 'delivery', FALSE),
    ];
    $build['configurations'] = $this->buildConfigurations($quote);

    if ($can_edit && $quote->get('status')->value === Quote::STATUS_A_COMMANDER) {
      $build['discount'] = [
        '#type' => 'details',
        '#title' => $this->t('Remise exceptionnelle par équipement'),
        '#open' => TRUE,
        'form' => $this->formBuilder()->getForm(QuoteDiscountForm::class, $quote),
      ];
    }

    return $build;
  }

  /**
   * Liens d'action (marquer commandé, archiver), statut « À commander » seul.
   */
  private function buildActions(Quote $quote): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-detail__actions']],
      'mark_ordered' => [
        '#type' => 'link',
        '#title' => $this->t('Marquer comme commandé'),
        '#url' => Url::fromRoute('drivematic_configurator.quote_mark_ordered', ['quote' => $quote->id()]),
        '#attributes' => ['class' => ['button']],
      ],
      'archive' => [
        '#type' => 'link',
        '#title' => $this->t('Archiver'),
        '#url' => Url::fromRoute('drivematic_configurator.quote_archive', ['quote' => $quote->id()]),
        '#attributes' => ['class' => ['button']],
      ],
    ];
  }

  /**
   * Tableau « Résumé » : partenaire, remise, statut, totaux.
   */
  private function buildSummaryTable(Quote $quote): array {
    $account = $quote->getOwner();
    if ($account) {
      // Une cellule dont la valeur est un render array doit etre enveloppee
      // dans `['data' => ...]` : un tableau nu sans cle `data` est traite
      // par `template_preprocess_table()` comme des attributs HTML bruts
      // pour la cellule, pas comme du contenu a rendre.
      $partner = [
        'data' => Link::createFromRoute(
        $account->getDisplayName() . ' (' . $account->getEmail() . ')',
        'entity.user.edit_form',
        ['user' => $account->id()],
        )->toRenderable(),
      ];
      $discount_rate = $this->formatRate($account->get('field_discount_rate')->value);
    }
    else {
      $partner = (string) $this->t('Compte supprimé');
      $discount_rate = $this->t('—');
    }

    return [
      '#type' => 'table',
      '#rows' => [
        [$this->t('Partenaire'), $partner],
        [$this->t('Remise partenaire'), $discount_rate],
        [$this->t('Statut'), $this->formatStatus($quote)],
        [$this->t('Total HT'), $this->formatPrice($quote->get('total_ht')->value)],
        [$this->t('Remise HT'), $this->formatPrice($quote->get('total_discount')->value)],
        [$this->t('Total remisé HT'), $this->formatPrice($quote->get('total_discounted_ht')->value)],
        [$this->t('TVA'), $this->formatPrice($quote->get('total_vat')->value)],
        [$this->t('Total TTC'), $this->formatPrice($quote->get('total_ttc')->value)],
      ],
    ];
  }

  /**
   * Tableau « Historique » : statuts + remises DM, triés par date.
   *
   * @see \Drupal\drivematic_configurator\Entity\QuoteStatusChange
   * @see \Drupal\drivematic_configurator\Entity\QuoteDiscountChange
   */
  private function buildHistoryTable(Quote $quote): array {
    $entries = [
      ...$this->buildStatusHistoryEntries($quote),
      ...$this->buildDiscountHistoryEntries($quote),
    ];
    usort($entries, static fn (array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);

    $rows = array_map(
      fn (array $entry): array => [$this->formatDate($entry['timestamp']), $entry['event'], $entry['author']],
      $entries,
    );

    return [
      '#type' => 'table',
      '#header' => [$this->t('Date'), $this->t('Événement'), $this->t('Effectué par')],
      '#rows' => $rows,
    ];
  }

  /**
   * Entrées d'historique de statut, normalisées pour la fusion chronologique.
   *
   * @return array[]
   *   Chaque entrée : ['timestamp' => int, 'event' => string,
   *   'author' => string].
   */
  private function buildStatusHistoryEntries(Quote $quote): array {
    $storage = $this->entityTypeManager()->getStorage('quote_status_change');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('created', 'ASC')
      ->execute();
    $changes = $storage->loadMultiple($ids);

    // Garantit toujours une 1ere entree « creation », meme pour un devis
    // cree avant l'introduction de cet historique (ADR-038 addendum) — ces
    // devis n'ont alors aucune entree `quote_status_change` du tout.
    // Jamais de doublon : QuotePersister::persist() enregistre deja cette
    // meme entree pour tout devis cree depuis, `$changes` n'est alors
    // jamais vide.
    if (!$changes) {
      return [$this->buildCreationEntry($quote)];
    }

    $entries = [];
    /** @var \Drupal\drivematic_configurator\Entity\QuoteStatusChange $change */
    foreach ($changes as $change) {
      $author = $change->get('uid')->entity;
      $entries[] = [
        'timestamp' => (int) $change->get('created')->value,
        'event' => $this->resolveStatusLabel((string) $change->get('status')->value, $quote),
        'author' => $author ? $author->getDisplayName() : (string) $this->t('Automatique'),
      ];
    }

    return $entries;
  }

  /**
   * Entrées d'historique de remise Drive Matic, normalisées pour la fusion.
   *
   * Une entrée par ligne d'équipement dont le taux a réellement changé
   * (QuoteDiscountForm::logDiscountChange()) — jamais de cas "automatique"
   * ici, `uid` est toujours l'administrateur ayant soumis le formulaire.
   *
   * @return array[]
   *   Chaque entrée : ['timestamp' => int, 'event' => string,
   *   'author' => string].
   */
  private function buildDiscountHistoryEntries(Quote $quote): array {
    $storage = $this->entityTypeManager()->getStorage('quote_discount_change');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('created', 'ASC')
      ->execute();

    $entries = [];
    /** @var \Drupal\drivematic_configurator\Entity\QuoteDiscountChange $change */
    foreach ($storage->loadMultiple($ids) as $change) {
      $author = $change->get('uid')->entity;
      $line = $change->get('line_id')->entity;
      $entries[] = [
        'timestamp' => (int) $change->get('created')->value,
        'event' => $this->t('Remise Drive Matic : « @label » @old % → @new %', [
          '@label' => $line ? $line->label() : (string) $this->t('Équipement supprimé'),
          '@old' => number_format((float) $change->get('old_rate')->value, 2, ',', ' '),
          '@new' => number_format((float) $change->get('new_rate')->value, 2, ',', ' '),
        ]),
        'author' => $author ? $author->getDisplayName() : (string) $this->t('Compte supprimé'),
      ];
    }

    return $entries;
  }

  /**
   * Entrée « création » de repli pour un devis antérieur à l'historique.
   *
   * `date_commande` n'est jamais posée que par `QuotePersister::persist()`
   * (au clic « Commander ») ni remise à NULL ensuite (seulement remise à
   * l'heure actuelle par une remise DM, cf. ADR-038) : sa seule présence
   * suffit donc à déduire fiablement le statut initial du devis, même s'il
   * a changé depuis.
   */
  private function buildCreationEntry(Quote $quote): array {
    $initial_status = $quote->get('date_commande')->value
      ? Quote::STATUS_A_COMMANDER
      : Quote::STATUS_A_FINALISER;
    $account = $quote->getOwner();

    return [
      'timestamp' => (int) $quote->get('created')->value,
      'event' => $this->resolveStatusLabel($initial_status, $quote),
      'author' => $account ? $account->getDisplayName() : (string) $this->t('Compte supprimé'),
    ];
  }

  /**
   * Libellé du statut courant du devis (« Commandé » inclut la date).
   *
   * Le libellé riche avec date n'est composé qu'ici : le listing (Vue
   * `quotes`) reste sur le libellé statique de `allowed_values` — l'affichage
   * détaillé avec date relève surtout du futur dashboard partenaire.
   */
  private function formatStatus(Quote $quote): string|TranslatableMarkup {
    if ($quote->get('status')->value === Quote::STATUS_COMMANDE) {
      return $this->t('Commandé le @date', [
        '@date' => $this->formatDate($quote->get('date_confirmation')->value),
      ]);
    }

    return $this->resolveStatusLabel((string) $quote->get('status')->value, $quote);
  }

  /**
   * Résout un libellé de statut depuis `allowed_values` (jamais en dur).
   *
   * Utilisé pour le statut courant (formatStatus()) ET pour chaque entrée
   * de l'historique (buildHistoryTable(), un statut PASSÉ, distinct du
   * statut courant du devis) — jamais de date embarquée ici, contrairement
   * à formatStatus() : dans l'historique, la date a déjà sa propre colonne.
   */
  private function resolveStatusLabel(string $status_value, Quote $quote): string {
    $allowed_values = $quote->getFieldDefinition('status')->getSetting('allowed_values');

    return $allowed_values[$status_value] ?? $status_value;
  }

  /**
   * Formate un taux (%), ou un tiret si absent.
   */
  private function formatRate(mixed $rate): string|TranslatableMarkup {
    if ($rate === NULL || $rate === '') {
      return $this->t('—');
    }
    return number_format((float) $rate, 2, ',', ' ') . ' %';
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
    /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine $line */
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
        $this->formatPrice($line->getEffectiveDiscountedUnitPrice()),
        (string) $line->get('quantity_per_vehicle')->value,
        (string) $line->get('quantity_total')->value,
        $this->formatPrice($line->get('ht')->value),
        $this->formatPrice($line->getEffectiveDiscountedHt()),
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
