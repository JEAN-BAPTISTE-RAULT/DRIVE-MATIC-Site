<?php

declare(strict_types=1);

namespace Drupal\drivematic_partner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Page « Mes devis » (F13, étape 2/2, ADR-051) : 3 onglets par statut.
 *
 * Une seule route, `?onglet=` sélectionne l'onglet actif (convention actée
 * par l'ADR-046). Menu déroulant par ligne (3 points verticaux) volontairement
 * hors périmètre à cette étape — voir ADR-051.
 */
final class MyQuotesController extends ControllerBase {

  private const PAGE_SIZE = 10;

  private const TAB_STATUSES = [
    'a-finaliser' => [Quote::STATUS_A_FINALISER],
    'en-cours' => [Quote::STATUS_A_COMMANDER, Quote::STATUS_COMMANDE],
    'archives' => [Quote::STATUS_ARCHIVE],
  ];

  private const DEFAULT_TAB = 'a-finaliser';

  public function __construct(
    private readonly DateFormatterInterface $dateFormatter,
    private readonly PagerManagerInterface $pagerManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('date.formatter'),
      $container->get('pager.manager'),
    );
  }

  /**
   * Construit la page.
   */
  public function build(Request $request): array {
    $active_tab = (string) $request->query->get('onglet', self::DEFAULT_TAB);
    if (!isset(self::TAB_STATUSES[$active_tab])) {
      $active_tab = self::DEFAULT_TAB;
    }
    $show_reference = $active_tab === 'en-cours';
    $show_amount = $active_tab !== 'a-finaliser';

    $uid = (int) $this->currentUser()->id();
    $storage = $this->entityTypeManager()->getStorage('quote');
    $configuration_storage = $this->entityTypeManager()->getStorage('quote_configuration');
    $equipment_storage = $this->entityTypeManager()->getStorage('quote_equipment_line');

    // `accessCheck(FALSE)` : requête toujours bornée à l'utilisateur courant
    // (jamais un id transmis par le client), aucun lien vers un devis
    // individuel à cette étape — même justification que DashboardController
    // (ADR-046).
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('status', self::TAB_STATUSES[$active_tab], 'IN')
      ->sort('created', 'DESC');

    $total = (int) (clone $query)->count()->execute();
    $pager = $this->pagerManager->createPager($total, self::PAGE_SIZE);
    $ids = $query->range($pager->getCurrentPage() * self::PAGE_SIZE, self::PAGE_SIZE)->execute();

    $rows = [];
    /** @var \Drupal\drivematic_configurator\Entity\Quote $quote */
    foreach ($storage->loadMultiple($ids) as $quote) {
      [$vehicles, $equipment] = $this->buildVehiclesAndEquipment($configuration_storage, $equipment_storage, $quote);

      $rows[] = [
        '#type' => 'component',
        '#component' => 'drive_matic:quote-row',
        '#props' => [
          'date' => $this->formatDate($quote->get('created')->value),
          'vehicles' => $vehicles,
          'equipment' => $equipment,
          'status_lines' => $this->formatStatusLines($quote),
          'status_modifier' => str_replace('_', '-', (string) $quote->get('status')->value),
          'reference' => $show_reference ? (string) $quote->get('reference')->value : NULL,
          'amount' => $show_amount ? $this->formatAmount($quote->get('total_ht')->value) : NULL,
        ],
      ];
    }

    return [
      '#cache' => [
        'contexts' => ['user', 'url.query_args:onglet', 'url.query_args.pagers:0'],
        'tags' => $this->entityTypeManager()->getDefinition('quote')->getListCacheTags(),
      ],
      'list' => [
        '#type' => 'component',
        '#component' => 'drive_matic:quote-list',
        '#props' => [
          'tabs' => $this->buildTabs($active_tab),
          'show_reference' => $show_reference,
          'show_amount' => $show_amount,
          'create_href' => Url::fromRoute('drivematic_configurator.configuration')->toString(),
          'empty_message' => $rows ? NULL : (string) $this->emptyMessage($active_tab),
        ],
        // Un tableau vide n'est pas une valeur de slot valide (le rendu SDC
        // exige un render array ou un scalaire) : omettre le slot plutot que
        // de lui passer `[]` quand l'onglet actif n'a aucun devis.
        '#slots' => $rows ? ['rows' => $rows] : [],
      ],
      'pager' => $rows ? ['#type' => 'pager'] : [],
    ];
  }

  /**
   * Construit les 3 onglets (libellé, lien, actif).
   */
  private function buildTabs(string $active_tab): array {
    $tabs = [];
    foreach (array_keys(self::TAB_STATUSES) as $tab) {
      $tabs[] = [
        'label' => (string) $this->tabLabel($tab),
        'href' => Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => $tab]])->toString(),
        'active' => $tab === $active_tab,
      ];
    }
    return $tabs;
  }

  /**
   * Libellé d'un onglet.
   */
  private function tabLabel(string $tab): TranslatableMarkup {
    return match ($tab) {
      'en-cours' => $this->t('Mes devis / commandes en cours'),
      'archives' => $this->t('Mes devis / commandes archivés'),
      default => $this->t('Mes devis à finaliser'),
    };
  }

  /**
   * Message affiché quand l'onglet actif ne contient aucun devis.
   */
  private function emptyMessage(string $tab): TranslatableMarkup {
    return match ($tab) {
      'en-cours' => $this->t("Vous n'avez pas de devis en cours."),
      'archives' => $this->t("Vous n'avez pas de devis archivé."),
      default => $this->t("Vous n'avez pas de devis à finaliser."),
    };
  }

  /**
   * Lignes du badge de statut affiché au partenaire.
   *
   * `commande` ET `archive` partagent le même libellé avec date : aucune des
   * maquettes de cette page ne montre de badge « Archivé » distinct, seul le
   * back-office DM (QuoteDetailController) le fait — voir ADR-051. Rendu sur
   * 2 lignes (maquette 493-15278 : « Commandé le » / date) — scission
   * purement présentationnelle d'un même message, comme les lignes véhicule.
   *
   * @return string[]
   *   1 ligne pour les autres statuts, 2 pour `commande`/`archive`.
   */
  private function formatStatusLines(Quote $quote): array {
    $status = (string) $quote->get('status')->value;
    if (in_array($status, [Quote::STATUS_COMMANDE, Quote::STATUS_ARCHIVE], TRUE)) {
      return [
        (string) $this->t('Commandé le'),
        $this->formatDate($quote->get('date_confirmation')->value),
      ];
    }

    $allowed_values = $quote->getFieldDefinition('status')->getSetting('allowed_values');
    return [(string) ($allowed_values[$status] ?? $status)];
  }

  /**
   * Lignes « Marque / Modèle / Type » et résumé des équipements d'un devis.
   *
   * Une ligne véhicule par `quote_configuration` ; équipements dédupliqués
   * (libellé) sur toutes les configurations du devis, joints par virgule.
   *
   * @return array{0: string[], 1: string}
   *   Un tuple [lignes véhicule, résumé des équipements].
   */
  private function buildVehiclesAndEquipment(
    EntityStorageInterface $configuration_storage,
    EntityStorageInterface $equipment_storage,
    Quote $quote,
  ): array {
    $vehicles = [];
    $equipment_labels = [];

    /** @var \Drupal\drivematic_configurator\Entity\QuoteConfiguration $configuration */
    foreach ($configuration_storage->loadByProperties(['quote_id' => $quote->id()]) as $configuration) {
      $vehicles[] = trim(sprintf(
        '%s / %s / %s',
        (string) $configuration->get('vehicle_brand')->value,
        (string) $configuration->get('vehicle_model')->value,
        (string) $configuration->get('motorisation')->value,
      ));

      /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine $line */
      foreach ($equipment_storage->loadByProperties(['configuration_id' => $configuration->id()]) as $line) {
        $equipment_labels[(string) $line->get('label')->value] = TRUE;
      }
    }

    return [$vehicles, implode(', ', array_keys($equipment_labels))];
  }

  /**
   * Formate une date en jj/mm/aaaa (maquettes 493-14389/493-15278/493-16109).
   *
   * Distinct du format `short` (« j M Y - H:i ») utilisé par
   * QuoteDetailController et la Vue admin : ceux-ci s'adressent à Drive
   * Matic (back-office), cette page au partenaire — les 5 maquettes de
   * cette page n'affichent jamais d'heure, sur aucune ligne.
   */
  private function formatDate(mixed $timestamp): string {
    return $timestamp ? $this->dateFormatter->format((int) $timestamp, 'custom', 'd/m/Y') : (string) $this->t('—');
  }

  /**
   * Formate un montant en euros (convention française : virgule, espace).
   */
  private function formatAmount(mixed $value): string {
    return number_format((float) $value, 2, ',', ' ') . ' €';
  }

}
