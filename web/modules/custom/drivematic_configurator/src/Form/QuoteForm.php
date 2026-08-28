<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Service\QuoteCalculator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ecran « Devis » du configurateur (F14, etape 2/3).
 *
 * Lit le brouillon laisse par ConfigurationForm (etape 1) dans une
 * PrivateTempStore — jamais de BDD a ce stade : decision explicite de
 * l'utilisatrice, un enregistrement reel (entite Devis/Configuration/Ligne
 * d'equipement, F15) n'a lieu qu'a l'etape 3 (Livraison, pas encore
 * implementee), sur un clic « Enregistrer le devis » ou « Commander ».
 * `PrivateTempStore` scope automatiquement par utilisateur courant : un seul
 * brouillon par partenaire a la fois, coherent avec le parcours lineaire
 * actuel (pas de devis en parallele).
 *
 * Les calculs (tarifs, remise, TVA, totaux) sont delegues a
 * QuoteCalculator, pense pour etre rejoue tel quel par la future
 * persistance F15 (memes formules pour geler les prix a la creation).
 */
final class QuoteForm extends FormBase {

  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_KEY = 'draft';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected AccountProxyInterface $currentUser,
    protected QuoteCalculator $quoteCalculator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('drivematic_configurator.quote_calculator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_quote_form';
  }

  /**
   * Brouillon du devis en cours (voir la note de classe).
   */
  private function tempStore(): PrivateTempStore {
    return $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $title = $this->t('Votre devis');
    $form['#prefix'] = '<div class="configurator-page"><h1 class="page-title configurator-form__title">' . $title . '</h1>';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'webform-submission-form';
    $form['#attributes']['class'][] = 'configurator-form';
    $form['#attributes']['class'][] = 'quote-form';
    $form['#attached']['library'][] = 'drivematic_configurator/quote_toggle';

    $form['stepper'] = $this->buildStepper();

    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    if (!$draft) {
      $form['empty'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['quote-form__empty']],
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Aucun devis en cours.'),
        ],
        'link' => [
          '#type' => 'link',
          '#title' => $this->t('Configurer un véhicule'),
          '#url' => Url::fromRoute('drivematic_configurator.configuration'),
          '#attributes' => ['class' => ['configurator-form__submit']],
        ],
      ];
      return $form;
    }

    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $discount_rate = $account->get('field_discount_rate')->value;
    $result = $this->quoteCalculator->calculate($draft, $discount_rate);

    $brand_labels = $this->loadTermLabels('vehicle_brand');
    $model_labels = $this->loadTermLabels('vehicle_model');
    $motorisation_labels = $this->loadTermLabels('motorisation');

    $form['configurations'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => ['class' => ['quote-form__configurations']],
    ];

    $position = 0;
    foreach ($result['configurations'] as $key => $configuration) {
      $position++;
      $form['configurations'][$key] = $this->buildConfigurationDisplay(
        $key,
        $position,
        $configuration,
        $draft[$key]['card']['vehicle'],
        $brand_labels,
        $model_labels,
        $motorisation_labels,
      );
    }

    $form['grand_totals'] = $this->buildGrandTotals($result['grand_totals']);
    $form['note'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Devis hors frais de livraison.'),
      '#attributes' => ['class' => ['quote-form__note']],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ajouter une configuration'),
      '#submit' => ['::addConfigurationSubmit'],
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['configurator-form__add']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choisir ma livraison'),
      '#submit' => ['::deliveryPlaceholderSubmit'],
      '#attributes' => ['class' => ['configurator-form__submit']],
    ];

    return $form;
  }

  /**
   * Construit le fil d'etapes (variante de celui de ConfigurationForm).
   *
   * « Configuration » y est marquee comme franchie et « Devis » courante.
   *
   * @return array
   *   Render array du fil d'etapes.
   */
  private function buildStepper(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'ol',
      '#attributes' => ['class' => ['configurator-form__stepper']],
      'configuration' => [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#value' => $this->t('Configuration'),
        '#attributes' => ['class' => ['configurator-form__step', 'is-done']],
      ],
      'quote' => [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#value' => $this->t('Devis'),
        '#attributes' => ['class' => ['configurator-form__step', 'is-current']],
      ],
      'delivery' => [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#value' => $this->t('Livraison'),
        '#attributes' => ['class' => ['configurator-form__step']],
      ],
    ];
  }

  /**
   * Construit l'affichage d'une configuration (resume repliable + detail).
   *
   * @param int $key
   *   Cle stable de la configuration dans le brouillon.
   * @param int $position
   *   Position affichee (1-based).
   * @param array $configuration
   *   Resultat calcule (QuoteCalculator) pour cette configuration.
   * @param array $vehicle
   *   Valeurs brutes `card.vehicle` du brouillon (term id bruts).
   * @param array $brand_labels
   *   Term id => libelle, vocabulaire vehicle_brand.
   * @param array $model_labels
   *   Term id => libelle, vocabulaire vehicle_model.
   * @param array $motorisation_labels
   *   Term id => libelle, vocabulaire motorisation.
   *
   * @return array
   *   Render array de la configuration.
   */
  private function buildConfigurationDisplay(
    int $key,
    int $position,
    array $configuration,
    array $vehicle,
    array $brand_labels,
    array $model_labels,
    array $motorisation_labels,
  ): array {
    $vehicle_label = implode(' / ', array_filter([
      $brand_labels[$vehicle['brand']] ?? NULL,
      $model_labels[$vehicle['model']] ?? NULL,
      $motorisation_labels[$vehicle['motorisation']] ?? NULL,
    ]));

    $element = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__configuration']],
    ];

    $element['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__configuration-header']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Configuration @number', ['@number' => $position]),
        '#attributes' => ['class' => ['quote-form__configuration-title']],
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['quote-form__actions']],
        'modify' => [
          '#type' => 'link',
          '#title' => [
            'icon' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => '',
              '#attributes' => ['class' => ['quote-form__modify-icon']],
            ],
            'text' => [
              '#plain_text' => $this->t('Modifier'),
            ],
          ],
          '#url' => Url::fromRoute('drivematic_configurator.configuration'),
          '#attributes' => [
            'class' => ['quote-form__modify'],
            'aria-label' => $this->t('Modifier la configuration @number', ['@number' => $position]),
          ],
        ],
        'delete' => [
          '#type' => 'submit',
          '#value' => $this->t('Supprimer'),
          '#name' => 'remove_configuration_' . $key,
          '#configuration_key' => $key,
          '#submit' => ['::removeConfigurationSubmit'],
          '#limit_validation_errors' => [],
          '#attributes' => [
            'class' => ['quote-form__delete'],
            'aria-label' => $this->t('Supprimer la configuration @number', ['@number' => $position]),
          ],
        ],
      ],
    ];

    $element['card'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__card'], 'data-quote-toggle' => TRUE],
      // Bandeau sombre repliable : maquette mobile uniquement (606:37565,
      // Group 106/107) — le desktop (508:13961) n'a pas de vehicule en
      // bandeau, il l'affiche en 1re colonne du tableau (cf. rowspan
      // buildEquipmentTable ci-dessous). Masque en desktop par CSS.
      'trigger' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'type' => 'button',
          'class' => ['quote-form__card-trigger'],
          'aria-expanded' => 'false',
        ],
        'vehicle' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $vehicle_label,
          '#attributes' => ['class' => ['quote-form__card-trigger-vehicle']],
        ],
        'count' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->formatPlural($configuration['vehicle_count'], '1 véhicule', '@count véhicules'),
          '#attributes' => ['class' => ['quote-form__card-trigger-count']],
        ],
        'chevron' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => '',
          '#attributes' => ['class' => ['quote-form__card-chevron'], 'aria-hidden' => 'true'],
        ],
      ],
      // Apercu replie (mobile uniquement) : seul le Total TTC est visible
      // tant que le detail n'est pas deplie (maquette 671:20896).
      'collapsed_total' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['quote-form__card-collapsed-total']],
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t('Total TTC'),
        ],
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->formatPrice($configuration['totals']['ttc']),
        ],
      ],
      'details' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['quote-form__card-details']],
        'table' => $this->buildEquipmentTable($configuration['lines'], $vehicle_label, $configuration['vehicle_count']),
        'totals' => $this->buildConfigurationTotals($configuration),
      ],
    ];

    return $element;
  }

  /**
   * Construit le tableau d'equipements d'une configuration.
   *
   * 1re colonne (marque/modele/type + nombre de vehicules) en rowspan sur
   * tout le groupe : n'apparait qu'une fois par configuration, comme dans
   * la maquette desktop (508:13961, Group 105 : « Citroen / C5 / Manuelle /
   * Nombre de véhicule(s) : 3 » ne se repete pas par ligne d'equipement).
   * Masquee en mobile par CSS, ou cette meme info vient du bandeau
   * repliable (`quote-form__card-trigger`) plutot que d'une colonne.
   *
   * @param array $lines
   *   Lignes calculees (QuoteCalculator) pour une configuration.
   * @param string $vehicle_label
   *   Libelle marque/modele/type (deja resolu par l'appelant).
   * @param int $vehicle_count
   *   Nombre de vehicules de la configuration.
   *
   * @return array
   *   Render array `#type: table`.
   */
  private function buildEquipmentTable(array $lines, string $vehicle_label, int $vehicle_count): array {
    $rows = [];
    $first = TRUE;
    foreach ($lines as $line) {
      $row = [];
      if ($first) {
        $row[] = [
          'data' => [
            'name' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $vehicle_label,
              '#attributes' => ['class' => ['quote-form__vehicle-name']],
            ],
            'count' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $this->t('Nombre de véhicule(s) : @count', ['@count' => $vehicle_count]),
              '#attributes' => ['class' => ['quote-form__vehicle-count']],
            ],
          ],
          'rowspan' => count($lines),
          'class' => ['quote-form__col--vehicle'],
        ];
        $first = FALSE;
      }

      $row[] = ['data' => $line['label'], 'class' => ['quote-form__col--equipment']];
      if ($line['unavailable']) {
        $row[] = [
          'data' => $this->t('Tarif indisponible — contactez Drive Matic'),
          'colspan' => 5,
        ];
        $rows[] = $row;
        continue;
      }

      $row[] = ['data' => $this->formatPrice($line['unit_price']), 'class' => ['quote-form__col--catalog-price']];
      $row[] = [
        'data' => $this->formatPrice($line['discounted_unit_price']),
        'class' => ['quote-form__col--discounted-price'],
      ];
      $row[] = ['data' => $line['quantity_per_vehicle'], 'class' => ['quote-form__col--qty-vehicle']];
      $row[] = ['data' => $line['quantity_total'], 'class' => ['quote-form__col--qty-total']];
      $row[] = ['data' => $this->formatPrice($line['discounted_ht']), 'class' => ['quote-form__col--total']];
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Marque/ modèle/ type'), 'class' => ['quote-form__col--vehicle']],
        ['data' => $this->t('Equipement(s)'), 'class' => ['quote-form__col--equipment']],
        ['data' => $this->t('Tarif catalogue unitaire € HT'), 'class' => ['quote-form__col--catalog-price']],
        ['data' => $this->t('Tarif unitaire remisé € HT'), 'class' => ['quote-form__col--discounted-price']],
        [
          'data' => $this->buildColumnLabel($this->t('Quantité par véhicule'), $this->t('Qté par véhicule')),
          'class' => ['quote-form__col--qty-vehicle'],
        ],
        ['data' => $this->t('Quantité totale'), 'class' => ['quote-form__col--qty-total']],
        [
          'data' => $this->buildColumnLabel($this->t('Total remisé € HT'), $this->t('Total remisé HT')),
          'class' => ['quote-form__col--total'],
        ],
      ],
      '#rows' => $rows,
      '#attributes' => ['class' => ['quote-form__equipment-table']],
    ];
  }

  /**
   * Construit un libelle de colonne dont le texte differe en mobile.
   *
   * Deux libelles distincts sont mesures sur les maquettes (508:13961
   * desktop / 671:20897 mobile) pour « Quantité par véhicule » ->
   * « Qté par véhicule » et « Total remisé € HT » -> « Total remisé HT » :
   * CSS bascule entre les deux versions par media query, jamais de
   * troncature ni d'abreviation deduite au hasard.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $full
   *   Libelle desktop.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $short
   *   Libelle mobile.
   *
   * @return array
   *   Render array du libelle (deux `<span>`, un seul visible a la fois).
   */
  private function buildColumnLabel(TranslatableMarkup $full, TranslatableMarkup $short): array {
    return [
      'full' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $full,
        '#attributes' => ['class' => ['quote-form__col-label--full']],
      ],
      'short' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $short,
        '#attributes' => ['class' => ['quote-form__col-label--short']],
      ],
    ];
  }

  /**
   * Construit le(s) bandeau(x) de totaux d'une configuration.
   *
   * Un seul bandeau « Tarif par véhicule » si la configuration ne compte
   * qu'un vehicule (les deux seraient identiques — maquette 508:13961,
   * Frame 106, configuration « Renault », 1 vehicule) ; deux bandeaux
   * (« Tarif par véhicule » puis « Tarif total véhicules ») des que
   * `vehicle_count` depasse 1 (meme maquette, configuration « Citroen »,
   * 3 vehicules).
   *
   * @param array $configuration
   *   Resultat calcule (QuoteCalculator) pour cette configuration.
   *
   * @return array
   *   Render array des bandeaux de totaux.
   */
  private function buildConfigurationTotals(array $configuration): array {
    $element = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__totals', 'quote-form__configuration-totals']],
      'per_vehicle' => $this->buildTotalsRow($configuration['totals_per_vehicle'], $this->t('Tarif par véhicule :')),
    ];
    if ($configuration['vehicle_count'] > 1) {
      $element['all_vehicles'] = $this->buildTotalsRow($configuration['totals'], $this->t('Tarif total véhicules :'));
    }
    return $element;
  }

  /**
   * Construit le bandeau de total general (toutes configurations).
   *
   * @param array $totals
   *   Totaux cumules (QuoteCalculator::calculate()['grand_totals']).
   *
   * @return array
   *   Render array du bloc de total general.
   */
  private function buildGrandTotals(array $totals): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__totals', 'quote-form__grand-totals']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Total configuration(s)'),
        '#attributes' => ['class' => ['quote-form__totals-title']],
      ],
      'row' => $this->buildTotalsRow($totals, NULL),
    ];
  }

  /**
   * Construit un bandeau de totaux (Total HT/Remise HT/TVA/Total TTC).
   *
   * @param array $totals
   *   Totaux calcules par QuoteCalculator (cles ht/discount/discounted_ht/
   *   vat/ttc).
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $row_label
   *   Libelle affiche a gauche du bandeau (« Tarif par véhicule : »...),
   *   ou NULL pour un bandeau sans libelle (total general).
   *
   * @return array
   *   Render array du bandeau de totaux.
   */
  private function buildTotalsRow(array $totals, ?TranslatableMarkup $row_label): array {
    $metrics_definitions = [
      ['label' => $this->t('Total HT'), 'value' => $totals['ht']],
      ['label' => $this->t('Remise HT'), 'value' => $totals['discount']],
      ['label' => $this->t('Total remisé HT'), 'value' => $totals['discounted_ht']],
      ['label' => $this->t('TVA 20 %'), 'value' => $totals['vat']],
      ['label' => $this->t('Total TTC'), 'value' => $totals['ttc']],
    ];

    // Le libelle est toujours rendu (vide sur le total general, sans
    // libelle) : reserve la meme largeur sur les 3 bandeaux (CSS,
    // `&__totals-row-label`) pour que les tarifs demarrent au meme x et
    // s'alignent en colonnes entre « Tarif par véhicule », « Tarif total
    // véhicules » et « Total configuration(s) » — sans quoi la largeur
    // variable du libelle deciderait a chaque fois d'un depart different.
    $row = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__totals-row']],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $row_label ?? '',
        '#attributes' => $row_label
          ? ['class' => ['quote-form__totals-row-label']]
          : ['class' => ['quote-form__totals-row-label'], 'aria-hidden' => 'true'],
      ],
    ];

    $metrics = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quote-form__totals-metrics']],
    ];
    $column_modifiers = ['ht', 'discount', 'discounted-ht', 'vat', 'ttc'];
    $last_index = count($metrics_definitions) - 1;
    foreach ($metrics_definitions as $index => $metric) {
      $classes = ['quote-form__metric', 'quote-form__metric--' . $column_modifiers[$index]];
      if ($index === $last_index) {
        $classes[] = 'quote-form__metric--emphasis';
      }
      $metrics[$index] = [
        '#type' => 'container',
        '#attributes' => ['class' => $classes],
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t('@label :', ['@label' => $metric['label']]),
          '#attributes' => ['class' => ['quote-form__metric-label']],
        ],
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->formatPrice($metric['value']),
          '#attributes' => ['class' => ['quote-form__metric-value']],
        ],
      ];
    }
    $row['metrics'] = $metrics;

    return $row;
  }

  /**
   * Formate un montant en euros (convention francaise : virgule, espace).
   */
  private function formatPrice(float $amount): string {
    return number_format($amount, 2, ',', ' ') . ' €';
  }

  /**
   * Charge les termes d'un vocabulaire, indexes par libelle.
   *
   * @return array<string,int>
   *   Term id => libelle, pour un vocabulaire donne.
   */
  private function loadTermLabels(string $vocabulary): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vocabulary]);
    $labels = [];
    foreach ($terms as $term) {
      $labels[$term->id()] = $term->label();
    }
    return $labels;
  }

  /**
   * Callback #submit de « Supprimer la configuration ».
   *
   * Retire l'entree du brouillon et reste sur cette page (contrairement a
   * ConfigurationForm, pas d'AJAX ici : chaque suppression recharge
   * l'ecran, plus simple pour un premier jet, sans etat client a
   * synchroniser).
   */
  public function removeConfigurationSubmit(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    $key = $triggering_element['#configuration_key'] ?? NULL;
    if ($key === NULL) {
      return;
    }

    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    unset($draft[$key]);
    if ($draft) {
      $this->tempStore()->set(self::TEMPSTORE_KEY, $draft);
    }
    else {
      $this->tempStore()->delete(self::TEMPSTORE_KEY);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback #submit de « Ajouter une configuration ».
   *
   * Ajoute un bloc vide au brouillon puis renvoie a l'etape 1, ou
   * ConfigurationForm le pre-remplira avec le reste du brouillon (cf. sa
   * note de classe).
   */
  public function addConfigurationSubmit(array &$form, FormStateInterface $form_state): void {
    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    $new_key = $draft ? max(array_keys($draft)) + 1 : 0;
    $draft[$new_key] = [
      'card' => [
        'vehicle' => ['brand' => '', 'model' => '', 'motorisation' => ''],
        'equipment' => [
          'equipment_retrovision_ext' => 0,
          'equipment_retrovision_int' => 0,
          'equipment_telecommande_vor' => 0,
          'equipment_double_pedalier' => 0,
          'retrovision_ext_quantity' => ['quantity' => 1],
        ],
        'vehicle_count' => ['quantity' => 1],
      ],
    ];
    $this->tempStore()->set(self::TEMPSTORE_KEY, $draft);
    $form_state->setRedirect('drivematic_configurator.configuration');
  }

  /**
   * Callback #submit de « Choisir ma livraison ».
   *
   * L'etape 3 n'existe pas encore (F14 3/3, hors perimetre) : message
   * temporaire, meme logique que le placeholder d'origine de
   * ConfigurationForm::submitForm().
   */
  public function deliveryPlaceholderSubmit(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t("L'étape Livraison arrive bientôt."));
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Non utilise : chaque bouton a son propre #submit.
  }

}
