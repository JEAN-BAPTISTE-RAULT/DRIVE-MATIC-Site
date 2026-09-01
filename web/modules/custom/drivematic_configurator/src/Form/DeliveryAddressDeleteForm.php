<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Confirmation de suppression d'une adresse de livraison (modale, F14 3/3).
 *
 * Meme mecanisme de modale que DeliveryAddressForm (voir sa note de classe).
 * Une adresse deja utilisee par un devis reste supprimable sans risque :
 * `Quote` gele ses propres champs `delivery_*` a la creation, jamais relus
 * depuis cette entite ensuite.
 */
final class DeliveryAddressDeleteForm extends ConfirmFormBase {

  use ModalRequestTrait;

  /**
   * L'adresse a supprimer, fournie par l'upcasting du parametre de route.
   */
  protected ?DeliveryAddress $deliveryAddress = NULL;

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
    return 'drivematic_configurator_delivery_address_delete_form';
  }

  /**
   * {@inheritdoc}
   *
   * Vide volontairement (demande utilisatrice) : cette modale n'a pas de
   * titre — la question se lit dans `getDescription()`, seul texte visible.
   * Rien a traduire ici (PHPCS le signale), mais le type de retour de
   * l'interface exige un `TranslatableMarkup` : derogation ciblee plutot
   * qu'un texte factice.
   */
  public function getQuestion(): TranslatableMarkup {
    // phpcs:ignore Drupal.Semantics.FunctionT.EmptyString
    return new TranslatableMarkup('');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Voulez-vous vraiment supprimer cette adresse ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('drivematic_configurator.delivery');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Oui');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return $this->t('Non');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?DeliveryAddress $delivery_address = NULL): array {
    $this->deliveryAddress = $delivery_address;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];

    // Voir DeliveryAddressForm::buildForm() : meme piege (pas de <h1> hors
    // route de node), meme omission en modale (titre deja porte par le
    // dialogue). `getQuestion()` etant vide (pas de titre dans la modale,
    // demande utilisatrice), la description sert de titre de repli ici —
    // seul texte disponible qui ait un sens hors modale.
    if (!$this->isModalRequest()) {
      $form['#prefix'] = '<h1 class="page-title">' . $this->getDescription() . '</h1>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Defense en profondeur (voir DeliveryAddressForm::submitForm()) : la
    // route est deja protegee par `_entity_access: 'delivery_address.delete'`.
    if (!$this->deliveryAddress || (int) $this->deliveryAddress->getOwnerId() !== (int) $this->currentUser->id()) {
      throw new AccessDeniedHttpException();
    }

    $this->deliveryAddress->delete();
    $this->messenger()->addStatus($this->t('Adresse de livraison supprimée.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Callback #ajax du bouton de confirmation — voir DeliveryAddressForm.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ferme la modale et redirige vers l'ecran Livraison.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand($this->getCancelUrl()->toString()));
    return $response;
  }

}
