<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Ajout/edition d'une adresse de livraison (modale, F14 3/3).
 *
 * Meme formulaire pour les deux cas : `$delivery_address` est fourni par le
 * convertisseur de parametre de route (upcasting `entity:delivery_address`,
 * `_entity_access: 'delivery_address.update'`) uniquement sur la route
 * d'edition — reste NULL sur la route d'ajout.
 *
 * Ouvert en modale via le mecanisme Drupal core (lien `use-ajax` +
 * `data-dialog-type: modal`, sans controleur ni JS custom) — premiere
 * utilisation de ce pattern dans le projet : le seul precedent (`help-modal`,
 * `<dialog>` HTML natif + JS vanilla, ADR-015) est pense pour du contenu
 * statique, inadapte a un formulaire valide serveur.
 */
final class DeliveryAddressForm extends FormBase {

  use ModalRequestTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_delivery_address_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?DeliveryAddress $delivery_address = NULL): array {
    $form_state->set('delivery_address_id', $delivery_address?->id());

    // Le bloc titre de page du coeur ne s'affiche que sur les routes de
    // node (meme piege que ConfigurationForm/QuoteForm/DeliveryForm) : sans
    // ce <h1>, la degradation sans JS (lien "Modifier"/"Ajouter" suivi comme
    // une navigation normale, sans modale) atterrit sur une page sans aucun
    // titre. Omis quand la route est ouverte en modale (dialog Drupal core,
    // qui porte deja son propre titre accessible) pour ne pas le dupliquer.
    if (!$this->isModalRequest()) {
      $title = $delivery_address ? $this->t('Modifier une adresse de livraison') : $this->t('Ajouter une nouvelle adresse');
      $form['#prefix'] = '<h1 class="page-title">' . $title . '</h1>';
    }

    $form['#attributes']['class'][] = 'webform-submission-form';
    $form['#attributes']['class'][] = 'delivery-address-form';

    $form['raison_sociale'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Raison sociale'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $delivery_address?->get('raison_sociale')->value,
    ];
    $form['adresse'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adresse'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $delivery_address?->get('adresse')->value,
    ];
    $form['complement'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Complément d'adresse"),
      '#maxlength' => 255,
      '#default_value' => $delivery_address?->get('complement')->value,
    ];
    $form['code_postal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code postal'),
      '#required' => TRUE,
      '#maxlength' => 5,
      '#default_value' => $delivery_address?->get('code_postal')->value,
    ];
    $form['ville'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ville'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $delivery_address?->get('ville')->value,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $delivery_address ? $this->t('Enregistrer les modifications') : $this->t('Ajouter cette adresse'),
      '#ajax' => ['callback' => '::ajaxSubmit'],
      '#attributes' => ['class' => ['delivery-address-form__submit']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $postal_code = (string) $form_state->getValue('code_postal');
    if (!preg_match('/^\d{5}$/', $postal_code)) {
      $form_state->setErrorByName('code_postal', $this->t('Le code postal doit comporter 5 chiffres.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $address_id = $form_state->get('delivery_address_id');
    $storage = $this->entityTypeManager->getStorage('delivery_address');

    if ($address_id) {
      /** @var \Drupal\drivematic_configurator\Entity\DeliveryAddress|null $address */
      $address = $storage->load($address_id);
      // Defense en profondeur : la route est deja protegee par
      // `_entity_access: 'delivery_address.update'`, mais une action
      // proprietaire ne repose jamais sur un seul point de controle
      // (CLAUDE.md).
      if (!$address || (int) $address->getOwnerId() !== (int) $this->currentUser->id()) {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      $address = $storage->create(['uid' => $this->currentUser->id()]);
    }

    $address->set('raison_sociale', $form_state->getValue('raison_sociale'));
    $address->set('adresse', $form_state->getValue('adresse'));
    $address->set('complement', $form_state->getValue('complement'));
    $address->set('code_postal', $form_state->getValue('code_postal'));
    $address->set('ville', $form_state->getValue('ville'));
    $address->save();

    $this->messenger()->addStatus($this->t('Adresse de livraison enregistrée.'));
  }

  /**
   * Callback #ajax du bouton de soumission.
   *
   * Sur erreur de validation, retourne le formulaire tel quel : Drupal core
   * le re-affiche dans la modale (mecanisme standard des formulaires ouverts
   * via `use-ajax`/`data-dialog-type: modal`). Sur succes, ferme la modale et
   * redirige vers l'ecran Livraison — la liste d'adresses s'y recharge donc
   * a jour, sans synchronisation AJAX partielle a maintenir (meme choix de
   * simplicite que QuoteForm::removeConfigurationSubmit(), ADR-031).
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Le formulaire (erreurs) ou une reponse AJAX de fermeture+redirection.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): array|AjaxResponse {
    if ($form_state->getErrors()) {
      return $form;
    }

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand(Url::fromRoute('drivematic_configurator.delivery')->toString()));
    return $response;
  }

}
