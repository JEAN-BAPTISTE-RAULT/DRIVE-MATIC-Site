<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ecran « Configuration » du configurateur de devis (F14, etape 1/3).
 *
 * Chaque bloc de configuration combine une cascade vehicule (taxonomies
 * `vehicle_brand` -> `vehicle_model` -> `motorisation`, ADR-003) et une liste
 * d'equipements fixe (aucun catalogue produit, F17, n'existe encore). Les
 * blocs sont identifies par une cle stable (pas par leur position affichee)
 * pour que retirer un bloc du milieu ne fasse pas perdre la saisie des blocs
 * suivants au rechargement AJAX.
 */
final class ConfigurationForm extends FormBase {

  /**
   * Nombre maximum de configurations par devis (PRD F14).
   */
  private const MAX_CONFIGURATIONS = 10;

  /**
   * Equipements proposes, dans l'ordre de la maquette (508:12894 et suiv.).
   *
   * Cle du champ => libelle. Seule « equipment_retrovision_ext » a un
   * selecteur de quantite (bornes 1-2, PRD F14).
   */
  private const EQUIPMENT_LABELS = [
    'equipment_retrovision_ext' => 'Rétrovision extérieure',
    'equipment_retrovision_int' => 'Rétrovision intérieure',
    'equipment_telecommande_vor' => 'Télécommande VOR',
    'equipment_double_pedalier' => 'Double pédalier auto-école',
  ];

  /**
   * Collection et cle du brouillon tempstore (voir self::tempStore()).
   */
  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_KEY = 'draft';

  public function __construct(
    // `protected`, pas `private` sur les deux : ce formulaire utilise #ajax
    // (rechargement du bloc « configurations »), qui repasse par un cycle
    // serialize/unserialize du form_state (cache_form).
    // `DependencySerializationTrait` (deja incluse par FormBase) restaure
    // ces proprietes a la deserialisation, mais son `__sleep()` est defini
    // dans FormBase — une
    // propriete `private` declaree ici, dans la sous-classe, lui est
    // invisible (portee `private` = classe declarante uniquement) et reste
    // donc non initialisee. Pas non plus `readonly` : `__wakeup()` doit
    // pouvoir les reaffecter. Rencontre au 2e clic « Ajouter une
    // configuration » (500 sur /configurer?ajax_form=1).
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
  ) {}

  /**
   * Brouillon du devis en cours (meme mecanisme que QuoteForm, etape 2).
   *
   * Voir la note de classe de QuoteForm pour le raisonnement complet. Un
   * seul brouillon par partenaire a la fois, le parcours etant strictement
   * lineaire (pas de devis en parallele).
   */
  private function tempStore(): PrivateTempStore {
    return $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Meme repartition en deux couches que `.field--type-webform` /
    // `.webform-submission-form` (fondation `forms`) : la gouttiere et le
    // rythme vertical de page sur l'enveloppe, le padding interieur de
    // chaque carte separement (ADR-015 §1, meme pattern que
    // PersonalInformationForm/`.personal-information-page`).
    //
    // Le <h1> est rendu ici, hors <form> : le bloc titre de page du coeur
    // (`block.block.drive_matic_page_title`) n'apparait que sur les routes
    // de node (sa condition de visibilite exige un contexte `node`), absent
    // sur cette route FormBase — sans ce rendu manuel, la page n'a aucun
    // titre. Le texte doit rester identique a `_title` dans
    // drivematic_configurator.routing.yml (utilise pour l'onglet et le fil
    // d'Ariane) : pas de source commune faute d'API pour lire le `_title`
    // resolu de la route courante depuis un FormBase.
    $title = $this->t('Configurez votre véhicule et obtenez votre tarif');
    $form['#prefix'] = '<div class="configurator-page"><h1 class="page-title configurator-form__title">' . $title . '</h1>';
    $form['#suffix'] = '</div>';

    $form['#attributes']['class'][] = 'webform-submission-form';
    $form['#attributes']['class'][] = 'configurator-form';
    $form['#attached']['library'][] = 'drivematic_forms/vehicle_select';
    $form['#attached']['library'][] = 'drivematic_configurator/quantity_stepper';
    $form['#attached']['library'][] = 'drivematic_configurator/configurator_reveal';
    $form['#attached']['drupalSettings']['drivematicForms'] = drivematic_forms_vehicle_map();

    // Le brouillon tempstore n'est lu qu'au tout premier rendu (pas a chaque
    // reconstruction AJAX, qui a deja sa propre saisie en cours dans
    // $form_state) : permet "Modifier"/"Ajouter une configuration" depuis
    // l'etape 2 (QuoteForm) de revenir ici avec les configurations
    // precedemment validees pre-remplies.
    $keys = $form_state->get('configuration_keys');
    $defaults = $form_state->get('configuration_defaults') ?? [];
    if ($keys === NULL) {
      $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
      $keys = $draft ? array_keys($draft) : [0];
      $defaults = $draft;
      $form_state->set('configuration_keys', $keys);
      $form_state->set('configuration_defaults', $defaults);
    }

    $brand_options = $this->loadTermOptions('vehicle_brand');
    $model_options = $this->loadTermOptions('vehicle_model');
    $motorisation_options = $this->loadTermOptions('motorisation');

    $form['stepper'] = $this->buildStepper();

    $form['configurations'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'configurator-configurations',
        'class' => ['configurator-form__configurations'],
      ],
    ];

    foreach ($keys as $position => $key) {
      $form['configurations'][$key] = $this->buildConfigurationElement(
        $key,
        $position + 1,
        $position > 0,
        $brand_options,
        $model_options,
        $motorisation_options,
        $defaults[$key] ?? NULL,
      );
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ajouter une configuration'),
      '#submit' => ['::addConfigurationSubmit'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::configurationsAjaxCallback',
        'wrapper' => 'configurator-configurations',
      ],
      '#disabled' => count($keys) >= self::MAX_CONFIGURATIONS,
      '#attributes' => ['class' => ['configurator-form__add']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Voir mon devis'),
      '#attributes' => ['class' => ['configurator-form__submit']],
    ];

    return $form;
  }

  /**
   * Construit le fil d'etapes « Configuration / Devis / Livraison ».
   *
   * Etape courante uniquement (les deux suivantes n'existent pas encore,
   * F14 etapes 2 et 3) : rendu statique, sans lien.
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
        '#attributes' => ['class' => ['configurator-form__step', 'is-current']],
      ],
      'quote' => [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#value' => $this->t('Devis'),
        '#attributes' => ['class' => ['configurator-form__step']],
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
   * Construit un bloc « Configuration N ».
   *
   * @param int $key
   *   Cle stable du bloc (independante de sa position affichee).
   * @param int $position
   *   Position affichee (1-based), pour le titre « Configuration N ».
   * @param bool $show_remove
   *   Affiche le bouton de suppression (jamais sur le premier bloc affiche).
   * @param array $brand_options
   *   Options du select marque (term id => libelle).
   * @param array $model_options
   *   Options du select modele, liste complete (filtree en JS).
   * @param array $motorisation_options
   *   Options du select type/motorisation, liste complete (filtree en JS).
   * @param array|null $defaults
   *   Valeurs du brouillon tempstore pour ce bloc (meme structure que les
   *   valeurs soumises), ou NULL pour un bloc vierge.
   *
   * @return array
   *   Render array du bloc.
   */
  private function buildConfigurationElement(
    int $key,
    int $position,
    bool $show_remove,
    array $brand_options,
    array $model_options,
    array $motorisation_options,
    ?array $defaults = NULL,
  ): array {
    // Groupe purement presentationnel : le titre « Configuration N » est
    // visuellement HORS de la carte grise (maquette 508:7306, y=0, vs la
    // carte qui commence a y=48) — un frere de `card`, pas un enfant, pour
    // que le fond gris de `.configurator-form__card` ne l'englobe pas.
    $element = [
      '#type' => 'container',
      '#attributes' => ['class' => ['configurator-form__group']],
    ];

    $element['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['configurator-form__card-header']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Configuration @number', ['@number' => $position]),
        '#attributes' => ['class' => ['configurator-form__card-title']],
      ],
    ];

    if ($show_remove) {
      $element['header']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Supprimer'),
        '#name' => 'remove_configuration_' . $key,
        '#configuration_key' => $key,
        '#submit' => ['::removeConfigurationSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::configurationsAjaxCallback',
          'wrapper' => 'configurator-configurations',
        ],
        '#attributes' => [
          'class' => ['configurator-form__remove'],
          'aria-label' => $this->t('Supprimer la configuration @number', ['@number' => $position]),
        ],
      ];
    }

    $element['card'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['configurator-form__card']],
    ];

    $element['card']['vehicle'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sélectionner un véhicule'),
      '#attributes' => [
        'class' => ['configurator-form__vehicle'],
        'data-vehicle-cascade' => TRUE,
      ],
    ];
    $element['card']['vehicle']['brand'] = [
      '#type' => 'select',
      '#title' => $this->t('Marque'),
      '#required' => TRUE,
      '#empty_option' => $this->t('Sélectionnez'),
      '#options' => $brand_options,
      '#default_value' => $defaults['card']['vehicle']['brand'] ?? NULL,
      '#attributes' => ['data-vehicle-role' => 'brand'],
    ];
    $element['card']['vehicle']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Modèle'),
      '#required' => TRUE,
      '#empty_option' => $this->t('Sélectionnez'),
      '#options' => $model_options,
      '#default_value' => $defaults['card']['vehicle']['model'] ?? NULL,
      '#attributes' => ['data-vehicle-role' => 'model'],
    ];
    $element['card']['vehicle']['motorisation'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#empty_option' => $this->t('Sélectionnez'),
      '#options' => $motorisation_options,
      '#default_value' => $defaults['card']['vehicle']['motorisation'] ?? NULL,
      '#attributes' => ['data-vehicle-role' => 'motorisation'],
    ];

    $element['card']['equipment'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sélectionner les équipements'),
      '#attributes' => ['class' => ['configurator-form__equipment']],
    ];
    foreach (self::EQUIPMENT_LABELS as $field_name => $label) {
      $element['card']['equipment'][$field_name] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@label', ['@label' => $label]),
        '#default_value' => !empty($defaults['card']['equipment'][$field_name]),
        // Classe stable (independante du delta de configuration, contrairement
        // a la classe `form-item-configurations-N-...` que Drupal genere) :
        // permet a la grille CSS de placer chaque equipement par role plutot
        // que par ordre du DOM (la quantite rétrovision s'intercale entre le
        // 1er equipement et les suivants en mobile, mais se place APRES les 4
        // en desktop — cf. maquettes 493-16990 / 606-36813).
        '#wrapper_attributes' => [
          'class' => [
            'configurator-form__equipment-item',
            'configurator-form__equipment-item--' . str_replace('_', '-', $field_name),
          ],
        ],
      ];
    }
    $element['card']['equipment']['retrovision_ext_quantity'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['configurator-form__equipment-quantity']],
      '#states' => [
        'visible' => [
          ':input[name="configurations[' . $key . '][card][equipment][equipment_retrovision_ext]"]' => ['checked' => TRUE],
        ],
      ],
      'quantity' => $this->buildQuantityStepper(
        1,
        2,
        (int) ($defaults['card']['equipment']['retrovision_ext_quantity']['quantity'] ?? 1),
        $this->t('Quantité de rétrovision extérieure'),
        FALSE,
      ),
    ];

    $element['card']['vehicle_count'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['configurator-form__vehicle-count']],
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Quantité'),
      ],
      'quantity' => $this->buildQuantityStepper(
        1,
        NULL,
        (int) ($defaults['card']['vehicle_count']['quantity'] ?? 1),
        $this->t('Nombre de véhicule(s) identique(s) à équiper'),
      ),
    ];

    return $element;
  }

  /**
   * Construit un champ numerique avec boutons -/+ (SDC-less, ADR-015).
   *
   * @param int $min
   *   Valeur minimale.
   * @param int|null $max
   *   Valeur maximale, ou NULL si non bornee.
   * @param int $default_value
   *   Valeur par defaut.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   Nom accessible du champ (toujours pose, meme quand non affiche
   *   visuellement — la maquette n'a pas de libelle a cote de la quantite
   *   d'un equipement, mais un lecteur d'ecran a besoin du contexte).
   * @param bool $title_visible
   *   FALSE pour un libelle uniquement accessible (equipement), TRUE pour un
   *   libelle affiche (quantite de vehicules identiques).
   *
   * @return array
   *   Render array de l'element `number`.
   */
  private function buildQuantityStepper(int $min, ?int $max, int $default_value, string|TranslatableMarkup $title, bool $title_visible = TRUE): array {
    $element = [
      '#type' => 'number',
      '#title' => $title,
      '#title_display' => $title_visible ? 'before' : 'invisible',
      '#min' => $min,
      '#default_value' => $default_value,
      '#attributes' => ['class' => ['configurator-form__quantity-input']],
      '#wrapper_attributes' => [
        'class' => ['configurator-form__quantity'],
        'data-quantity-stepper' => TRUE,
      ],
      '#field_prefix' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'type' => 'button',
          'class' => ['configurator-form__quantity-decrement'],
          'data-quantity-decrement' => TRUE,
          'aria-label' => $this->t('Diminuer la quantité'),
        ],
      ],
      '#field_suffix' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'type' => 'button',
          'class' => ['configurator-form__quantity-increment'],
          'data-quantity-increment' => TRUE,
          'aria-label' => $this->t('Augmenter la quantité'),
        ],
      ],
    ];
    if ($max !== NULL) {
      $element['#max'] = $max;
    }

    return $element;
  }

  /**
   * Charge les options d'un select depuis un vocabulaire de taxonomie.
   *
   * Liste complete (non filtree) : la cascade JS (drivematic_forms/js/
   * vehicle-select.js) restreint modele/motorisation cote client, mais
   * degrade sans JS en listes completes.
   *
   * @param string $vocabulary
   *   Identifiant machine du vocabulaire.
   *
   * @return array
   *   Options triees par libelle (term id => libelle).
   */
  private function loadTermOptions(string $vocabulary): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vocabulary]);

    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }
    asort($options);

    return $options;
  }

  /**
   * Callback #ajax commun a « Ajouter » et « Supprimer » une configuration.
   *
   * @param array $form
   *   Le formulaire complet.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   L'etat du formulaire.
   *
   * @return array
   *   Le conteneur des configurations, pour remplacement AJAX.
   */
  public function configurationsAjaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['configurations'];
  }

  /**
   * Callback #submit du bouton « Ajouter une configuration ».
   *
   * Fonctionne aussi sans JS (vrai #submit, l'#ajax n'est qu'une
   * amelioration progressive) : le rechargement de page affiche alors le
   * nouveau bloc en bas de formulaire.
   *
   * @param array $form
   *   Le formulaire complet.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   L'etat du formulaire.
   */
  public function addConfigurationSubmit(array &$form, FormStateInterface $form_state): void {
    $keys = $form_state->get('configuration_keys') ?? [0];
    if (count($keys) < self::MAX_CONFIGURATIONS) {
      $keys[] = $keys ? max($keys) + 1 : 0;
      $form_state->set('configuration_keys', $keys);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback #submit du bouton « Supprimer la configuration ».
   *
   * La cle retiree n'est jamais reattribuee : les blocs restants gardent
   * leurs noms de champs (donc leur saisie) inchanges au rechargement, seule
   * leur numerotation affichee (« Configuration N ») est recalculee depuis
   * leur position dans la liste.
   *
   * @param array $form
   *   Le formulaire complet.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   L'etat du formulaire.
   */
  public function removeConfigurationSubmit(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    $key = $triggering_element['#configuration_key'] ?? NULL;
    $keys = $form_state->get('configuration_keys') ?? [0];

    if ($key !== NULL && count($keys) > 1) {
      $keys = array_values(array_diff($keys, [$key]));
      $form_state->set('configuration_keys', $keys);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $vehicle_map = drivematic_forms_vehicle_map();
    $configurations = $form_state->getValue('configurations') ?? [];

    foreach ($configurations as $key => $configuration) {
      $brand = $configuration['card']['vehicle']['brand'] ?? '';
      $model = $configuration['card']['vehicle']['model'] ?? '';
      $motorisation = $configuration['card']['vehicle']['motorisation'] ?? '';

      if ($model !== '' && !in_array((int) $model, $vehicle_map['modelsByBrand'][$brand] ?? [], TRUE)) {
        $form_state->setError(
          $form['configurations'][$key]['card']['vehicle']['model'],
          $this->t('Le modèle sélectionné ne correspond pas à la marque choisie.'),
        );
      }
      if ($motorisation !== '' && !in_array((int) $motorisation, $vehicle_map['motosByModel'][$model] ?? [], TRUE)) {
        $form_state->setError(
          $form['configurations'][$key]['card']['vehicle']['motorisation'],
          $this->t('Le type sélectionné ne correspond pas au modèle choisi.'),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Une configuration sans aucun equipement coche est abandonnee : c'est
    // le SEUL controle qui compte (le bouton desactive tant qu'aucune case
    // n'est cochee, cote client, n'est qu'une amelioration progressive —
    // configurator-reveal.js — jamais fiable seule, cf. meme logique deja
    // appliquee aux bornes de quantite de la rétrovision exterieure).
    $configurations = $form_state->getValue('configurations') ?? [];
    $valid_configurations = array_filter(
      $configurations,
      fn (array $configuration): bool => $this->hasEquipmentChecked($configuration),
    );

    if (!$valid_configurations) {
      // Aucune redirection vers l'etape 2 : rien a y montrer. Le brouillon
      // existant (le cas echeant) n'est pas touche.
      $this->messenger()->addWarning($this->t('Aucune configuration valide : sélectionnez au moins un équipement par véhicule avant de continuer.'));
      return;
    }

    // Pas d'entite Devis/Configuration a ce stade (decision utilisatrice) :
    // le brouillon ne devient un enregistrement qu'a l'etape 3 (Livraison),
    // sur "Enregistrer le devis" ou "Commander" — hors perimetre ici.
    $this->tempStore()->set(self::TEMPSTORE_KEY, $valid_configurations);
    $form_state->setRedirect('drivematic_configurator.quote');
  }

  /**
   * Determine si au moins un equipement est coche sur une configuration.
   *
   * @param array $configuration
   *   Valeurs soumises d'un bloc de configuration.
   *
   * @return bool
   *   TRUE si au moins une des 4 cases equipement est cochee.
   */
  private function hasEquipmentChecked(array $configuration): bool {
    foreach (array_keys(self::EQUIPMENT_LABELS) as $field_name) {
      if (!empty($configuration['card']['equipment'][$field_name])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
