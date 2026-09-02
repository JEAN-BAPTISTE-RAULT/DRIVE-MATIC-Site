<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteConfiguration;
use Drupal\drivematic_configurator\Form\QuoteDiscountForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Tableau « Historique » : statuts du plus ancien au plus récent.
   *
   * @see \Drupal\drivematic_configurator\Entity\QuoteStatusChange
   */
  private function buildHistoryTable(Quote $quote): array {
    $storage = $this->entityTypeManager()->getStorage('quote_status_change');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('created', 'ASC')
      ->execute();
    $changes = $storage->loadMultiple($ids);

    $rows = [];

    // Garantit toujours une 1ere ligne « creation », meme pour un devis
    // cree avant l'introduction de cet historique (ADR-038 addendum) — ces
    // devis n'ont alors aucune entree `quote_status_change` du tout.
    // Jamais de doublon : QuotePersister::persist() enregistre deja cette
    // meme entree pour tout devis cree depuis, `$changes` n'est alors
    // jamais vide.
    if (!$changes) {
      $rows[] = $this->buildCreationRow($quote);
    }

    /** @var \Drupal\drivematic_configurator\Entity\QuoteStatusChange $change */
    foreach ($changes as $change) {
      $author = $change->get('uid')->entity;
      $rows[] = [
        $this->formatDate($change->get('created')->value),
        $this->resolveStatusLabel((string) $change->get('status')->value, $quote),
        $author ? $author->getDisplayName() : (string) $this->t('Automatique'),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [$this->t('Date'), $this->t('Statut'), $this->t('Effectué par')],
      '#rows' => $rows,
    ];
  }

  /**
   * Ligne « création » de repli pour un devis antérieur à l'historique.
   *
   * `date_commande` n'est jamais posée que par `QuotePersister::persist()`
   * (au clic « Commander ») ni remise à NULL ensuite (seulement remise à
   * l'heure actuelle par une remise DM, cf. ADR-038) : sa seule présence
   * suffit donc à déduire fiablement le statut initial du devis, même s'il
   * a changé depuis.
   */
  private function buildCreationRow(Quote $quote): array {
    $initial_status = $quote->get('date_commande')->value
      ? Quote::STATUS_A_COMMANDER
      : Quote::STATUS_A_FINALISER;
    $account = $quote->getOwner();

    return [
      $this->formatDate($quote->get('created')->value),
      $this->resolveStatusLabel($initial_status, $quote),
      $account ? $account->getDisplayName() : (string) $this->t('Compte supprimé'),
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
