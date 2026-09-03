<?php

declare(strict_types=1);

namespace Drupal\drivematic_partner\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulaire « Mes informations personnelles » (espace partenaire).
 *
 * Reprend les champs du webform `account_request` (maquette Figma
 * 524:20069), preremplis depuis le compte courant. Seuls civilite / prenom /
 * nom / fonction / telephone sont enregistrables ; l'e-mail et le bloc
 * « Votre entreprise » restent en lecture seule, pilotes par le back-office.
 */
final class PersonalInformationForm extends FormBase {

  /**
   * Champs User modifiables par le partenaire depuis ce formulaire.
   *
   * Sert aussi de liste blanche a la sauvegarde : aucune autre valeur
   * soumise (e-mail, champs « Votre entreprise ») n'est jamais persistee,
   * meme si un champ en lecture seule etait trafique cote client.
   */
  private const EDITABLE_FIELDS = [
    'field_civility',
    'field_first_name',
    'field_last_name',
    'field_job_title',
    'field_phone',
  ];

  /**
   * Constructeur.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   L'utilisateur courant.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Le gestionnaire d'entites.
   */
  public function __construct(
    private readonly AccountProxyInterface $currentUser,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_partner_personal_information_form';
  }

  /**
   * Charge le compte de l'utilisateur courant.
   *
   * Le formulaire n'opere jamais que sur son propre compte : aucun
   * identifiant n'est jamais lu depuis l'URL ou la requete, ce qui exclut
   * toute manipulation vers le compte d'un autre partenaire.
   *
   * @return \Drupal\user\UserInterface
   *   Le compte de l'utilisateur courant.
   */
  private function loadCurrentAccount(): UserInterface {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $account = $this->loadCurrentAccount();

    $form['#prefix'] = '<div class="personal-information-page">';
    $form['#suffix'] = '</div>';

    $form['you_are'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Vous êtes'),
    ];
    $form['you_are']['field_civility'] = [
      '#type' => 'select',
      '#title' => $this->t('Civilité'),
      '#required' => TRUE,
      '#empty_option' => $this->t('Sélectionnez'),
      '#options' => [
        'madame' => $this->t('Madame'),
        'monsieur' => $this->t('Monsieur'),
      ],
      '#default_value' => $account->get('field_civility')->value,
    ];
    $form['you_are']['field_first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prénom'),
      '#required' => TRUE,
      '#default_value' => $account->get('field_first_name')->value,
    ];
    $form['you_are']['field_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom'),
      '#required' => TRUE,
      '#default_value' => $account->get('field_last_name')->value,
    ];
    $form['you_are']['field_job_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fonction'),
      '#required' => TRUE,
      '#default_value' => $account->get('field_job_title')->value,
    ];
    $form['you_are']['field_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Téléphone'),
      '#required' => TRUE,
      '#default_value' => $account->get('field_phone')->value,
    ];
    $form['you_are']['email'] = [
      '#type' => 'email',
      // #context distinct : une traduction fr generique de "E-mail" existe
      // deja pour ce terme (sans contexte, "Courriel") et l'ecraserait sinon
      // silencieusement — meme piege que sur le libelle E-mail de
      // `user_login_form` (drivematic_forms.module).
      '#title' => $this->t('E-mail', [], ['context' => 'personal-information-form']),
      '#default_value' => $account->getEmail(),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['company'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Votre entreprise'),
    ];
    $form['company']['field_siret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de Siret (14 caractères)'),
      '#default_value' => $account->get('field_siret')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['company']['field_vat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de TVA intracommunautaire (13 caractères)'),
      '#default_value' => $account->get('field_vat')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['company']['field_company_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Raison sociale'),
      '#default_value' => $account->get('field_company_name')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['company']['field_company_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Adresse de l'entreprise"),
      '#default_value' => $account->get('field_company_address')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['company']['field_address_complement'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Complément d'adresse"),
      '#default_value' => $account->get('field_address_complement')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['company']['field_postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code postal'),
      '#default_value' => $account->get('field_postal_code')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['company']['field_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ville'),
      '#default_value' => $account->get('field_city')->value,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['change_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Modifier mon mot de passe'),
      '#url' => Url::fromRoute('user.pass'),
      '#attributes' => ['class' => ['personal-information-form__password-link']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Mettre à jour mes informations'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $account = $this->loadCurrentAccount();

    foreach (self::EDITABLE_FIELDS as $field_name) {
      $account->set($field_name, $form_state->getValue($field_name));
    }
    $account->save();

    $this->messenger()->addStatus($this->t('Vos informations personnelles ont été mises à jour.'));
  }

}
